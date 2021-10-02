<?php
//
// Description
// -----------
// Report of active members
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the birthdays for.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// 
// Returns
// -------
//
function ciniki_customers_reporting_blockActiveProducts(&$ciniki, $tnid, $args) {
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

    $end_dt = new DateTime('now', new DateTimezone($intl_timezone));

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
    // Store the report block chunks
    //
    $chunks = array();


    //
    // Get the membership products added/sold in the last X days
    //
    $strsql = "SELECT customers.id, "
        . "customers.status, "
        . "customers.status AS status_text, "
        . "customers.member_status, "
        . "customers.member_status AS member_status_text, "
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
        . "AND ("
            . "purchases.end_date >= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
            . "OR purchases.end_date = '0000-00-00' "
            . ") "
        . "ORDER BY products.sequence, product_name, customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'products', 'fname'=>'product_name', 
            'fields'=>array('name'=>'product_name')),
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'status', 'status_text', 'product_name', 'purchase_date', 'end_date'),
            'maps'=>array('status_text'=>$maps['customer']['status'])),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_ids = array();
    if( isset($rc['products']) ) {
        $products = $rc['products'];
        foreach($products as $product) { 
            if( isset($product['customers']) ) {
                foreach($product['customers'] as $customer) {
                    $customer_ids[] = $customer['id'];
                }
            }
        }
    }

    //
    // Get the emails and addresses
    //
    if( count($customer_ids) <= 0 ) {
        $chunks[] = array('type'=>'message', 'content'=>'No members');
        return array('stat'=>'ok', 'chunks'=>$chunks);
    }

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
    // Get the emails
    //
    $strsql = "SELECT id, customer_id, phone_label, phone_number "
        . "FROM ciniki_customer_phones "
        . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
        array('container'=>'phones', 'fname'=>'id', 'fields'=>array('label'=>'phone_label', 'number'=>'phone_number')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $phones = $rc['customers'];

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
    foreach($products as $pid => $product) {
        $chunk = array(
            'type'=>'table',
            'title'=>$product['name'],
            'columns'=>array(
                array('label'=>'Name', 'pdfwidth'=>'25%', 'field'=>'display_name'),
                array('label'=>'Product', 'pdfwidth'=>'25%', 'field'=>'product_name'),
                array('label'=>'Expiry Date', 'pdfwidth'=>'20%', 'field'=>'end_date'),
                array('label'=>'Contact', 'pdfwidth'=>'30%', 'field'=>'contact_info'),
                ),
            'data'=>array(),
            'editApp'=>array('app'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.id')),
            'textlist'=>'',
            );
        foreach($product['customers'] as $cid => $customer) {
            //
            // Add emails to customer
            //
            $chunk['textlist'] .= $customer['display_name'] . "\n";
            $chunk['textlist'] .= $customer['product_name'] . "\n";
            $chunk['textlist'] .= $customer['purchase_date'] . "\n";
            $customer['contact_info'] = '';
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
                        $customer['contact_info'] .= ($customer['contact_info'] != '' ? "\n" : '') . $addr;
                    }
                }
            }
            if( isset($phones[$customer['id']]['phones']) ) {
                foreach($phones[$customer['id']]['phones'] as $phone) {
                    $chunk['textlist'] .= $phone['label'] . ': ' . $phone['number'] . "\n";
                    $customer['contact_info'] .= ($customer['contact_info'] != '' ? "\n" : '') 
                        . $phone['label'] . ': ' . $phone['number'];
                }
            }
            if( isset($emails[$customer['id']]['emails']) ) {
                foreach($emails[$customer['id']]['emails'] as $email) {
                    $chunk['textlist'] .= $email['email'] . "\n";
                    $customer['contact_info'] .= ($customer['contact_info'] != '' ? "\n" : '') . $email['email'];
                }
            }
            $chunk['textlist'] .= "\n";
            $chunk['data'][] = $customer;
        }
        $chunks[] = $chunk;
    }

    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
