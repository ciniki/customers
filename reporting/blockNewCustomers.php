<?php
//
// Description
// -----------
// Return the report of new customers
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the birthdays for.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// days:                The number of days past to look for new customers.
// 
// Returns
// -------
//
function ciniki_customers_reporting_blockNewCustomers(&$ciniki, $tnid, $args) {
    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $date_text = '';
    if( isset($args['months']) && $args['months'] != '' && $args['months'] > 0 && $args['months'] < 366 ) {
        $months = $args['months'];
        $date_text .= $months . ' month' . ($months > 1 ? 's' : '');
    } else {
        $months = 0;
    }
    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 && $args['days'] < 366 ) {
        $days = $args['days'];
    } else {
        // Default to 0 when months specified, otherwise default to 7 days
        $days = ($months > 0 ? 0 : 7);
    }
    if( $days > 0 ) {
        $date_text .= $days . ' day' . ($days > 1 ? 's' : '');
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $end_dt = clone $start_dt;
    if( $days != 0 ) {
        $end_dt->sub(new DateInterval('P' . $days . 'D'));
    }
    if( $months != 0 ) {
        $end_dt->sub(new DateInterval('P' . $months . 'M'));
    }

    //
    // Store the report block chunks
    //
    $chunks = array();

    //
    // Get the new customers
    //
    $strsql = "SELECT c.id, "
        . "c.parent_id, "
        . "c.status, "
        . "c.status AS status_text, "
        . "c.display_name, "
        . "DATE_FORMAT(c.start_date, '%b %e, %Y') AS start_date "
        . "FROM ciniki_customers AS c "
        . "WHERE c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND c.status = 10 "
        . "AND c.start_date >= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
        . "ORDER BY c.start_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'parent_id', 'display_name', 'status', 'status_text', 'start_date'),
            'maps'=>array('status_text'=>$maps['customer']['status'])),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customers = isset($rc['customers']) ? $rc['customers'] : array();
    $customer_ids = array();
    foreach($customers as $customer) {
        $customer_ids[] = $customer['id'];
    }

    //
    // Get the emails and addresses
    //
    if( count($customer_ids) > 0 ) {
        //
        // Get the emails
        //
        $strsql = "SELECT id, customer_id, email "
            . "FROM ciniki_customer_emails "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x10) = 0 " // Only get emails that want to receive emails
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'emails', 'fname'=>'id', 'fields'=>array('email')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $emails = isset($rc['customers']) ? $rc['customers'] : array();

        //
        // Get the addresses
        //
        $strsql = "SELECT id, customer_id, address1, address2, city, province, postal, country "
            . "FROM ciniki_customer_addresses "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x04) = 0x04 " // Only get mailing addresses
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'addresses', 'fname'=>'id', 'fields'=>array('address1', 'address2', 'city', 'province', 'postal', 'country')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $addresses = isset($rc['customers']) ? $rc['customers'] : array();

        //
        // Create the report blocks
        //
        $chunk = array(
            'type'=>'table',
            'columns'=>array(
                array('label'=>'Name', 'pdfwidth'=>'20%', 'field'=>'display_name'),
                array('label'=>'Start Date', 'pdfwidth'=>'20%', 'field'=>'start_date'),
                array('label'=>'Email', 'pdfwidth'=>'30%', 'field'=>'email'),
                array('label'=>'Address', 'pdfwidth'=>'30%', 'field'=>'address'),
                ),
            'data'=>array(),
            'editApp'=>array('app'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.id')),
            'textlist'=>'',
            );
        foreach($customers as $cid => $customer) {
            //
            // Add emails to customer
            //
            $chunk['textlist'] .= $customer['display_name'] . "\n";
            $chunk['textlist'] .= $customer['start_date'] . "\n";
            if( isset($emails[$customer['id']]['emails']) ) {
                foreach($emails[$customer['id']]['emails'] as $email) {
                    $chunk['textlist'] .= $email['email'] . "\n";
                    if( !isset($customer['email']) ) {
                        $customer['email'] = $email['email'];
                    } else {
                        $customer['email'] .= ', ' . $email['email'];
                    }
                }
            }
            //
            // Add addresses to customer
            //
            if( isset($addresses[$customer['id']]['addresses']) ) {
                foreach($addresses[$customer['id']]['addresses'] as $address) {
                    $addr = '';
                    if( isset($address['address1']) && $address['address1'] != '' ) {
                        $addr .= $address['address1'];
                    }
                    if( isset($address['address2']) && $address['address2'] != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $address['address2'];
                    }
                    $city = '';
                    if( isset($address['city']) && $address['city'] != '' ) {
                        $city .= $address['city'];
                    }
                    if( isset($address['province']) && $address['province'] != '' ) {
                        $city .= ($city != '' ? ', ' : '') . $address['province'];
                    }
                    if( isset($address['postal']) && $address['postal'] != '' ) {
                        $city .= ($city != '' ? '  ' : '') . $address['postal'];
                    }
                    if( $city != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $city;
                    }
                    if( isset($address['country']) && $address['country'] != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $address['country'];
                    }
                    if( $addr != '' ) {
                        $chunk['textlist'] .= $addr . "\n";
                        if( !isset($customers[$cid]['address']) ) {
                            $customer['address'] = $addr;
                        } else {
                            $customer['address'] .= "\n" . $addr;
                        }
                    }
                }
            }
            $chunk['textlist'] .= "\n";
            $chunk['data'][] = $customer;
        }
        $chunks[] = $chunk;
    } 
    //
    // No customers 
    //
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No new customers in the last ' . $date_text . '.');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
