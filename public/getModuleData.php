<?php
//
// Description
// -----------
// This method will return the detail of a customer along with data for customers from other modules.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_getModuleData($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'eid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'),
        'display_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Display Name'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.getModuleData', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Check either ID or code has been specified
    //
    if( (!isset($args['customer_id']) || $args['customer_id'] == '')
        && (!isset($args['eid']) || $args['eid'] == '') 
        && (!isset($args['display_name']) || $args['display_name'] == '') 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.90', 'msg'=>'You must specify either a customer or ID'));
    }

    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
//  $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//  $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $mysql_date_format = ciniki_users_dateFormat($ciniki);

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
    // Get the settings for customer module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // Get the relationship types
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getRelationshipTypes');
    $rc = ciniki_customers_getRelationshipTypes($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $relationship_types = $rc['types'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//  $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Get the customer details and emails
    //
    $strsql = "SELECT ciniki_customers.id, eid, parent_id, type, callsign, prefix, first, middle, last, suffix, "
        . "display_name, company, department, title, "
        . "phone_home, phone_work, phone_cell, phone_fax, "
        . "status, status AS status_text, "
        . "member_status, member_status AS member_status_text, "
        . "member_lastpaid, member_expires, membership_length, membership_type, "
        . "dealer_status, dealer_status AS dealer_status_text, "
        . "distributor_status, distributor_status AS distributor_status_text, "
        . "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, '%b %e, %Y') . "'), '') AS birthdate, "
        . "connection, language, "
        . "tax_number, tax_location_id, "
        . "discount_percent, start_date, webflags, "
        . "notes "
        . "FROM ciniki_customers "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
        $strsql .= "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['eid']) && $args['eid'] != '' ) {
        $strsql .= "AND ciniki_customers.eid = '" . ciniki_core_dbQuote($ciniki, $args['eid']) . "' ";
    } elseif( isset($args['display_name']) && $args['display_name'] != '' ) {
        $strsql .= "AND ciniki_customers.display_name = '" . ciniki_core_dbQuote($ciniki, $args['display_name']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.91', 'msg'=>'You must specify either a customer or ID'));
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'eid', 'parent_id', 'type', 'callsign', 'prefix', 'first', 'middle', 'last', 'suffix', 'display_name', 
                'status', 'status_text',
                'phone_home', 'phone_work', 'phone_cell', 'phone_fax',
                'member_status', 'member_status_text', 'member_lastpaid', 'member_expires', 'membership_length', 'membership_type',
                'dealer_status', 'dealer_status_text',
                'distributor_status', 'distributor_status_text',
                'company', 'department', 'title', 
                'notes', 'birthdate', 'connection', 'language', 'tax_number', 'tax_location_id',
                'discount_percent', 'start_date', 'webflags'),
            'maps'=>array('status_text'=>$maps['customer']['status'],
                'member_status_text'=>$maps['customer']['member_status'],
                'dealer_status_text'=>$maps['customer']['dealer_status'],
                'distributor_status_text'=>$maps['customer']['distributor_status']),
            'utctotz'=>array(
                'member_lastpaid'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'member_expires'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.customers.92', 'msg'=>'Invalid customer'));
    }
    if( isset($rc['customers']) && count($rc['customers']) > 1 ) {
        return array('stat'=>'ambiguous', 'err'=>array('code'=>'ciniki.customers.93', 'msg'=>'Multiple customers found'));
    }
    $customer = $rc['customers'][0]['customer'];
    $customer['discount_percent_text'] = (float)$customer['discount_percent'] . '%';
    $customer['addresses'] = array();
    $customer['subscriptions'] = array();


    //
    // Get the membership products purchased
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'productsPurchased');
        $rc = ciniki_customers_productsPurchased($ciniki, $args['tnid'], array('customer_id' => $customer['id']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.427', 'msg'=>'Unable to get purchases', 'err'=>$rc['err']));
        }
        $customer['membership_details'] = isset($rc['membership_details']) ? $rc['membership_details'] : array();

        if( $customer['member_status'] == 0 ) {
            array_unshift($customer['membership_details'], array(
                'label' => 'Status',
                'value' => 'Not a member',
                ));
        } elseif( $customer['member_status'] == 10 ) {
            array_unshift($customer['membership_details'], array(
                'label' => 'Status',
                'value' => 'Active',
                ));

        } elseif( $customer['member_status'] == 60 ) {
            array_unshift($customer['membership_details'], array(
                'label' => 'Status',
                'value' => 'Inactive',
                ));
        }
    }

    //
    // Get the tax location
    //
    if( isset($customer['tax_location_id']) && $customer['tax_location_id'] > 0 
        && isset($modules['ciniki.taxes'])
        && ($modules['ciniki.taxes']['flags']&0x01) > 0
        && ($modules['ciniki.customers']['flags']&0x2000) > 0 
        ) {
        $strsql = "SELECT ciniki_tax_locations.id, ciniki_tax_locations.code, ciniki_tax_locations.name, "
            . "ciniki_tax_rates.id AS rate_id, ciniki_tax_rates.name AS rate_name "
            . "FROM ciniki_tax_locations "
            . "LEFT JOIN ciniki_tax_rates ON ( "
                . "ciniki_tax_locations.id = ciniki_tax_rates.location_id "
                . "AND ciniki_tax_rates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_tax_rates.start_date < UTC_TIMESTAMP() "
                . "AND (ciniki_tax_rates.end_date = '0000-00-00 00:00:00' "
                    . "OR ciniki_tax_rates.end_date > UTC_TIMESTAMP()) "
                . ") "
            . "WHERE ciniki_tax_locations.id = '" . ciniki_core_dbQuote($ciniki, $customer['tax_location_id']) . "' "
            . "AND ciniki_tax_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
            array('container'=>'taxes', 'fname'=>'id',
                'fields'=>array('id', 'code', 'name')),
            array('container'=>'rates', 'fname'=>'rate_id',
                'fields'=>array('name'=>'rate_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['taxes'][$customer['tax_location_id']]) ) {
            $tax = $rc['taxes'][$customer['tax_location_id']];
            $customer['tax_location_id_text'] = '';
//          if( ($modules['ciniki.taxes']['flags']&0x02) && $tax['code'] != '' ) {
//              $customer['tax_location_id_text'] = $tax['code'] . ' - ';
//          }
            $customer['tax_location_id_text'] .= $tax['name'];
            $customer['tax_location_id_rates'] = '';
            if( isset($tax['rates']) ) {
                foreach($tax['rates'] as $rid => $rate) {
                    $customer['tax_location_id_rates'] .= ($customer['tax_location_id_rates']!=''?', ':'') . $rate['name'];
                }
            }
        }
    }

    //
    // Get the categories and tags for the customer
    //
    if( ($modules['ciniki.customers']['flags']&0xC00224) > 0 ) {
        $strsql = "SELECT tag_type, tag_name AS lists "
            . "FROM ciniki_customer_tags "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tags']) ) {
            foreach($rc['tags'] as $tags) {
                if( $tags['tags']['tag_type'] == 10 ) {
                    $customer['customer_categories'] = $tags['tags']['lists'];
                }
                if( $tags['tags']['tag_type'] == 20 ) {
                    $customer['customer_tags'] = $tags['tags']['lists'];
                }
                if( $tags['tags']['tag_type'] == 40 ) {
                    $customer['member_categories'] = $tags['tags']['lists'];
                }
                if( $tags['tags']['tag_type'] == 60 ) {
                    $customer['dealer_categories'] = $tags['tags']['lists'];
                }
                if( $tags['tags']['tag_type'] == 80 ) {
                    $customer['distributor_categories'] = $tags['tags']['lists'];
                }
            }
        }
    }

    //
    // Get the customer email addresses
    //
//    if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x20000000) > 0 ) {
        $strsql = "SELECT id, customer_id, email AS address, flags "
            . "FROM ciniki_customer_emails "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'emails', 'fname'=>'id', 'name'=>'email',
                'fields'=>array('id', 'customer_id', 'address', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['emails']) ) {
            $customer['emails'] = $rc['emails'];
        } else {
            $customer['emails'] = array();
        }
 //   }
    
    //
    // Get phones
    //
    if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x10000000) == 0 ) {
        $strsql = "SELECT id, phone_label, phone_number, flags "
            . "FROM ciniki_customer_phones "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'phones', 'fname'=>'id', 'name'=>'phone',
                'fields'=>array('id', 'phone_label', 'phone_number', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['phones']) ) {
            $customer['phones'] = $rc['phones'];
        }
    }

    //
    // Get the customer addresses
    //
    $strsql = "SELECT id, customer_id, "
        . "address1, address2, city, province, postal, country, flags, phone "
        . "FROM ciniki_customer_addresses "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'addresses', 'fname'=>'id', 'name'=>'address',
            'fields'=>array('id', 'customer_id', 'address1', 'address2', 'city', 'province', 'postal', 
                'country', 'flags', 'phone')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['addresses']) ) {
        $customer['addresses'] = $rc['addresses'];
    }

    //
    // Get the customer links
    //
    $strsql = "SELECT id, customer_id, "
        . "name, url, webflags "
        . "FROM ciniki_customer_links "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'links', 'fname'=>'id', 'name'=>'link',
            'fields'=>array('id', 'name', 'url', 'webflags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['links']) ) {
        $customer['links'] = $rc['links'];
    }

    //
    // Get the relationships for the customer
    //
/*    if( isset($settings['use-relationships']) && $settings['use-relationships'] == 'yes' ) {
        $strsql = "SELECT ciniki_customer_relationships.id, relationship_type AS type, "
            . "relationship_type AS type_name, "
            . "ciniki_customer_relationships.customer_id, ciniki_customer_relationships.related_id, "
//          . "IF(customer_id='" . ciniki_core_dbQuote($ciniki, $customer['id']) . "', related_id, customer_id) AS related_id, "
            . "date_started, date_ended, ciniki_customers.display_name, ciniki_customers.company "
            . "";
        $strsql .= "FROM ciniki_customer_relationships "
            . "LEFT JOIN ciniki_customers ON ("
                . "(ciniki_customer_relationships.customer_id <> '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                . "AND ciniki_customer_relationships.customer_id = ciniki_customers.id "
                . ") OR ("
                . "ciniki_customer_relationships.related_id <> '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                . "AND ciniki_customer_relationships.related_id = ciniki_customers.id "
                . ")) "
            . "WHERE ciniki_customer_relationships.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (ciniki_customer_relationships.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                . "OR ciniki_customer_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                . ") "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'relationships', 'fname'=>'id', 'name'=>'relationship',
                'fields'=>array('id', 'type', 'customer_id', 'type_name', 'related_id', 
                    'display_name', 'date_started', 'date_ended'),
                'maps'=>array('type_name'=>$relationship_types)),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['relationships']) ) {
            $customer['relationships'] = $rc['relationships'];
            foreach($customer['relationships'] as $rid => $relationship) {
                $relationship = $relationship['relationship'];
                //
                // Check if this relationship needs to be reversed
                //
                if( $relationship['related_id'] == $customer['id'] ) {
                    if( isset($relationship_types[-$relationship['type']]) ) {
                        $customer['relationships'][$rid]['relationship']['type_name'] = $relationship_types[-$relationship['type']];
                    }
                    $customer['relationships'][$rid]['relationship']['type'] = -$relationship['type'];
                    $customer['relationships'][$rid]['relationship']['related_id'] = $relationship['customer_id'];
                }
            }
        }
    } */

    //
    // If child account, get parent information
    //

    //
    // Get the parent information and any child customers
    //
    if( ($modules['ciniki.customers']['flags']&0x200000) > 0 ) {
        //
        // Get parent info
        //
        if( $customer['parent_id'] != 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
            $rc = ciniki_customers__customerDetails($ciniki, $args['tnid'], $customer['parent_id'], 
                array('phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']) ) {
                $customer['parent'] = array('id'=>$rc['customer']['id'], 'eid'=>$rc['customer']['eid'], 'display_name'=>$rc['customer']['display_name']);
                $customer['parent']['details'] = $rc['details'];
            }
        } 
        //
        // Get children
        //
        $strsql = "SELECT id, eid, display_name "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.parent_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_customers.last, ciniki_customers.first "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'children', 'fname'=>'id', 'name'=>'customer',
                'fields'=>array('id', 'eid', 'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['children']) ) {
            $customer['children'] = $rc['children'];
        } else {
            $customer['children'] = array();
        }
    }

    //
    // Get any membership seasons
    //
    if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
        $strsql = "SELECT ciniki_customer_seasons.id, "
            . "ciniki_customer_seasons.name, "
            . "ciniki_customer_seasons.flags, "
            . "IFNULL(ciniki_customer_season_members.id, 0) AS season_member_id, "
            . "IFNULL(ciniki_customer_season_members.status, '') AS status, "
            . "IFNULL(ciniki_customer_season_members.date_paid, '') AS date_paid "
            . "FROM ciniki_customer_season_members, ciniki_customer_seasons "
            . "WHERE ciniki_customer_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (ciniki_customer_seasons.flags&0x02) > 0 "
            . "AND ciniki_customer_seasons.id = ciniki_customer_season_members.season_id "
            . "AND ciniki_customer_season_members.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_customer_seasons.start_date DESC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'seasons', 'fname'=>'id', 'name'=>'season',
                'fields'=>array('id', 'name', 'flags', 'season_member_id', 'status', 'date_paid')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['seasons']) ) {   
            $customer['seasons'] = $rc['seasons'];
        }
    }

    $rsp = array('stat'=>'ok', 'customer'=>$customer);

    //
    // Call the hooks to other modules for any data to attach to customer
    //
    $rsp['data_tabs'] = array();
    $uiDataArgs = array('customer_id' => $args['customer_id']);
    foreach($ciniki['tenant']['modules'] as $module => $m) {
        // Skip archived modules
        if( $m['module_status'] >= 90 ) {
            continue;
        }
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'uiCustomersData');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], $uiDataArgs);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.399', 'msg'=>'Unable to get customer information.', 'err'=>$rc['err']));
            }
            if( isset($rc['tabs']) ) {
                foreach($rc['tabs'] as $tab) {
                    if( !isset($tab['priority']) ) {
                        $tab['priority'] = 0;
                    }
                    $rsp['data_tabs'][$tab['id']] = $tab;
                }
            }
        }
    }

    uasort($rsp['data_tabs'], function($a, $b) {
        if( $a['priority'] == $b['priority'] ) {
            return 0;
        }
        return ($a['priority'] < $b['priority'] ? 1 : -1);
        }); 

    return $rsp;
}
?>
