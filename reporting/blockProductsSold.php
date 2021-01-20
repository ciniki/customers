<?php
//
// Description
// -----------
// The report of membership products sold in the last X days
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the birthdays for.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// days:                The number of days past to look for new members.
// 
// Returns
// -------
//
function ciniki_customers_reporting_blockProductsSold(&$ciniki, $tnid, $args) {
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

    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 && $args['days'] < 365 ) {
        $days = $args['days'];
    } else {
        $days = 7;
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $end_dt = clone $start_dt;
    $end_dt->sub(new DateInterval('P' . $days . 'D'));

    //
    // Store the report block chunks
    //
    $chunks = array();


    //
    // Get the membership products added/sold in the last X days
    //
    $strsql = "SELECT customers.id, "
        . "customers.status, "
        . "customers.status AS status_text, "
        . "customers.display_name, "
        . "products.name AS product_name, "
        . "DATE_FORMAT(purchases.purchase_date, '%b %e, %Y') AS purchase_date, "
        . "DATE_FORMAT(purchases.end_date, '%b %e, %Y') AS end_date "
        . "FROM ciniki_customer_product_purchases AS purchases "
        . "LEFT JOIN ciniki_customer_products AS products ON ("
            . "purchases.product_id = products.id "
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "purchases.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND purchases.purchase_date >= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
        . "ORDER BY purchases.purchase_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'status', 'status_text', 'product_name', 'purchase_date', 'end_date'),
            'maps'=>array('status_text'=>$maps['customer']['status'])),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_ids = array();
    if( isset($rc['customers']) ) {
        $customers = $rc['customers'];
       
        foreach($customers as $customer) {
            $customer_ids[] = $customer['id'];
        }
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
        $emails = $rc['customers'];

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
        $addresses = $rc['customers'];

        //
        // Create the report blocks
        //
        $chunk = array(
            'type'=>'table',
            'columns'=>array(
                array('label'=>'Name', 'pdfwidth'=>'25%', 'field'=>'display_name'),
                array('label'=>'Product', 'pdfwidth'=>'25%', 'field'=>'product_name'),
                array('label'=>'Purchase Date', 'pdfwidth'=>'20%', 'field'=>'purchase_date'),
                array('label'=>'Email', 'pdfwidth'=>'30%', 'field'=>'email_address'),
                ),
            'data'=>array(),
            'textlist'=>'',
            );
        foreach($customers as $cid => $customer) {
            //
            // Add emails to customer
            //
            $chunk['textlist'] .= $customer['display_name'] . "\n";
            $chunk['textlist'] .= $customer['start_date'] . "\n";
            $customer['email_address'] = '';
            if( isset($emails[$customer['id']]['emails']) ) {
                foreach($emails[$customer['id']]['emails'] as $email) {
                    $chunk['textlist'] .= $email['email'] . "\n";
                    if( !isset($customer['email']) ) {
                        $customer['email_address'] = $email['email'];
                    } else {
                        $customer['email_address'] .= ', ' . $email['email'];
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
                            $customer['email_address'] = $addr;
                        } else {
                            $customer['email_address'] .= "\n" . $addr;
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
        $chunks[] = array('type'=>'message', 'content'=>'No new members in the last ' . ($days == 1 ? 'day' : $days . ' days') . '.');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
