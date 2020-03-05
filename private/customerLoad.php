<?php
//
// Description
// -----------
// This function loads a customer record and flattens it if the IFB flag is enabled for the tenant.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_customerLoad($ciniki, $tnid, $customer_id) {

    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the customer record
    //
    $strsql = "SELECT id, "
        . "parent_id, "
        . "type, "
        . "type AS type_text, "
        . "status, "
        . "status AS status_text, "
        . "eid, "
        . "display_name, "
        . "sort_name, "
        . "permalink, "
        . "primary_image_id, "
        . "member_status, "
        . "member_status AS member_status_text, "
        . "member_lastpaid, "
        . "member_expires, "
        . "membership_length, "
        . "membership_type, "
        . "dealer_status, "
        . "dealer_status AS dealer_status_text, "
        . "distributor_status, "
        . "distributor_status AS distributor_status_text, "
        . "callsign, "
        . "prefix, "
        . "first, "
        . "middle, "
        . "last, "
        . "suffix, "
        . "company, "
        . "department, "
        . "title, "
        . "birthdate, "
        . "short_bio, "
        . "full_bio, "
        . "webflags, "
        . "pricepoint_id, "
        . "salesrep_id, "
        . "tax_number, "
        . "tax_location_id, "
        . "reward_level, "
        . "sales_total, "
        . "sales_total_prev, "
        . "discount_percent, "
        . "start_date, "
        . "connection, "
        . "language, "
        . "notes "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'parent_id', 'type', 'type_text', 'eid', 'display_name', 'sort_name', 'permalink', 'primary_image_id', 
                'status', 'status_text',
                'member_status', 'member_status_text', 'member_lastpaid', 'member_expires', 'membership_length', 'membership_type',
                'dealer_status', 'dealer_status_text', 'distributor_status', 'distributor_status_text', 
                'callsign', 'prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title',
                'pricepoint_id', 'salesrep_id', 'tax_number', 'tax_location_id',
                'reward_level', 'sales_total', 'sales_total_prev', 'discount_percent', 'start_date', 
                'birthdate', 'short_bio', 'full_bio', 'webflags', 'connection', 'language', 'notes'),
            'maps'=>array(
                'type_text'=>$maps['customer']['type'],
                'status_text'=>$maps['customer']['status'],
                'member_status_text'=>$maps['customer']['member_status'],
                'dealer_status_text'=>$maps['customer']['dealer_status'],
                'distributor_status_text'=>$maps['customer']['distributor_status'],
                ),
            'utctotz'=>array(
                'birthdate'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'member_lastpaid'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'member_expires'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                ), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.228', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    if( !isset($rc['customers'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.268', 'msg'=>'Unable to find customer'));
    }
    $customer = $rc['customers'][0];

    //
    // Load the emails
    //
    $strsql = "SELECT id, email AS address, flags "
        . "FROM ciniki_customer_emails "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY (flags&0x20) "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'emails', 'fname'=>'id', 'fields'=>array('id', 'address', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer['emails'] = isset($rc['emails']) ? $rc['emails'] : array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        $customer['primary_email_id'] = isset($rc['emails'][0]['id']) ? $rc['emails'][0]['id'] : 0;
        $customer['primary_email'] = isset($rc['emails'][0]['address']) ? $rc['emails'][0]['address'] : '';
        $customer['primary_email_flags'] = isset($rc['emails'][0]['flags']) ? $rc['emails'][0]['flags'] : 0x01;
        $customer['secondary_email_id'] = isset($rc['emails'][1]['id']) ? $rc['emails'][1]['id'] : 0;
        $customer['secondary_email'] = isset($rc['emails'][1]['address']) ? $rc['emails'][1]['address'] : '';
    }

    //
    // Load the phone numbers
    //
    $strsql = "SELECT id, phone_label, phone_number, flags "
        . "FROM ciniki_customer_phones "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'phones', 'fname'=>'id', 'fields'=>array('id', 'phone_label', 'phone_number', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer['phones'] = isset($rc['phones']) ? $rc['phones'] : array();
    //
    // IFB parsing
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        $customer['phone_cell_id'] = 0;
        $customer['phone_cell'] = '';
        $customer['phone_home_id'] = 0;
        $customer['phone_home'] = '';
        $customer['phone_work_id'] = 0;
        $customer['phone_work'] = '';
        $customer['phone_fax_id'] = 0;
        $customer['phone_fax'] = '';
        $extras = array();
        if( isset($customer['phones']) ) {
            foreach($customer['phones'] as $phone) {
                if( $phone['phone_label'] == 'Cell' && $phone['phone_number'] != '' ) {
                    $customer['phone_cell_id'] = $phone['id'];
                    $customer['phone_cell'] = $phone['phone_number'];
                } elseif( $phone['phone_label'] == 'Home' && $phone['phone_number'] != '' ) {
                    $customer['phone_home_id'] = $phone['id'];
                    $customer['phone_home'] = $phone['phone_number'];
                } elseif( $phone['phone_label'] == 'Work' && $phone['phone_number'] != '' ) {
                    $customer['phone_work_id'] = $phone['id'];
                    $customer['phone_work'] = $phone['phone_number'];
                } elseif( $phone['phone_label'] == 'Fax' && $phone['phone_number'] != '' ) {
                    $customer['phone_fax_id'] = $phone['id'];
                    $customer['phone_fax'] = $phone['phone_number'];
                } elseif( $phone['phone_number'] != '' ) {
                    $extras[] = $phone;
                }
            }
        }
        //
        // If some of the phone number labels were duplicates, deal with them by finding an empty slot
        //
        if( count($extras) > 0 ) {
            foreach($extras as $extra) {
                if( $customer['phone_cell'] != '' ) {
                    $customer['phone_cell_id'] = $phone['id'];
                    $customer['phone_cell'] = $phone['phone_number'];
                } elseif( $customer['phone_home'] != '' ) {
                    $customer['phone_home_id'] = $phone['id'];
                    $customer['phone_home'] = $phone['phone_number'];
                } elseif( $customer['phone_work'] != '' ) {
                    $customer['phone_work_id'] = $phone['id'];
                    $customer['phone_work'] = $phone['phone_number'];
                } elseif( $customer['phone_fax'] != '' ) {
                    $customer['phone_fax_id'] = $phone['id'];
                    $customer['phone_fax'] = $phone['phone_number'];
                }
            }
        }
    }

    //
    // Load the addresses
    //
    $strsql = "SELECT id, "
        . "address1, address2, city, province, postal, country, flags, latitude, longitude, phone "
        . "FROM ciniki_customer_addresses "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY (flags&0x04) DESC "     // List mailing addresses first
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'addresses', 'fname'=>'id',
            'fields'=>array('id', 'address1', 'address2', 'city', 'province', 'postal', 
                'country', 'flags', 'latitude', 'longitude', 'phone')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer['addresses'] = isset($rc['addresses']) ? $rc['addresses'] : array();
    //
    // IFB parsing
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        $customer['mailing_address_id'] = 0;
        $customer['mailing_address1'] = '';    
        $customer['mailing_address2'] = '';    
        $customer['mailing_city'] = '';    
        $customer['mailing_province'] = '';    
        $customer['mailing_postal'] = '';    
        $customer['mailing_country'] = '';    
        $customer['mailing_flags'] = 0x06;    
        $customer['billing_address_id'] = 0;
        $customer['billing_address1'] = '';    
        $customer['billing_address2'] = '';    
        $customer['billing_city'] = '';    
        $customer['billing_province'] = '';    
        $customer['billing_postal'] = '';    
        $customer['billing_country'] = '';    
        if( isset($customer['addresses'][0]) ) {
            //
            // What is the first address
            //
            if( isset($customer['addresses'][0]['flags']) && ($customer['addresses'][0]['flags']&0x04) == 0x04 ) {
                $customer['mailing_address_id'] = $customer['addresses'][0]['id'];
                $customer['mailing_address1'] = $customer['addresses'][0]['address1'];
                $customer['mailing_address2'] = $customer['addresses'][0]['address2'];
                $customer['mailing_city'] = $customer['addresses'][0]['city'];
                $customer['mailing_province'] = $customer['addresses'][0]['province'];
                $customer['mailing_postal'] = $customer['addresses'][0]['postal'];
                $customer['mailing_country'] = $customer['addresses'][0]['country'];
                $customer['mailing_flags'] = $customer['addresses'][0]['flags'];
            } else {
                $customer['mailing_flags'] = ($customer['addresses'][0]['flags']&0xfd);         // Remove billing 0x02 flag
                $customer['billing_address_id'] = $customer['addresses'][0]['id'];
                $customer['billing_address1'] = $customer['addresses'][0]['address1'];
                $customer['billing_address2'] = $customer['addresses'][0]['address2'];
                $customer['billing_city'] = $customer['addresses'][0]['city'];
                $customer['billing_province'] = $customer['addresses'][0]['province'];
                $customer['billing_postal'] = $customer['addresses'][0]['postal'];
                $customer['billing_country'] = $customer['addresses'][0]['country'];
            }
            if( isset($customer['addresses'][1]['flags']) && $customer['billing_address_id'] == 0 ) {
                $customer['billing_address_id'] = $customer['addresses'][1]['id'];
                $customer['billing_address1'] = $customer['addresses'][1]['address1'];
                $customer['billing_address2'] = $customer['addresses'][1]['address2'];
                $customer['billing_city'] = $customer['addresses'][1]['city'];
                $customer['billing_province'] = $customer['addresses'][1]['province'];
                $customer['billing_postal'] = $customer['addresses'][1]['postal'];
                $customer['billing_country'] = $customer['addresses'][1]['country'];
            }
        }
    }

    //
    // Load the links
    //
    $strsql = "SELECT id, name, url, description, webflags "
        . "FROM ciniki_customer_links "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'addresses', 'fname'=>'id', 'fields'=>array('id', 'name', 'url', 'description', 'webflags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer['links'] = isset($rc['links']) ? $rc['links'] : array();
    //
    // IFB parsing
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        $customer['website_id'] = '';
        $customer['website'] = '';
        if( isset($customer['links'][0]['url']) ) {
            $customer['website_id'] = $customer['links'][0]['id'];
            $customer['website'] = $customer['links'][0]['url'];
        }
    }

    //
    // The following sections may be needed in the future and can be copied from get.php
    // FIXME: Get the tax location
    // FIXME: Get the categories and tags for the customer
    // FIXME: Get the customer image gallery
    //

    return array('stat'=>'ok', 'customer'=>$customer);
}
?>
