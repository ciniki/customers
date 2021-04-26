<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_productSetupCustomer(&$ciniki, $tnid, $args) {


    if( !isset($args['customer_id']) || $args['customer_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.492', 'msg'=>'No Customer Specified'));
    }

    if( !isset($args['product_id']) || $args['product_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.493', 'msg'=>'No product specified'));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Load product
    //
    $strsql = "SELECT products.id, "
        . "products.code, "
        . "products.name, "
        . "products.type, "
        . "products.status, "
        . "products.flags, "
        . "products.months "
        . "FROM ciniki_customer_products AS products "
        . "WHERE products.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
        . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.494', 'msg'=>'Unable to load product', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.495', 'msg'=>'Unable to find requested product'));
    }
    $product = $rc['product'];

    //
    // Load the customer details
    //
    $strsql = "SELECT id, "
        . "type, "
        . "parent_id, "
        . "status, "
        . "member_status, "
        . "member_lastpaid, "
        . "member_expires, "
        . "membership_length "
        . "FROM ciniki_customers "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.496', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.474', 'msg'=>'Unable to find customer'));
    }
    $customer = $rc['customer'];

    //
    // Check if previously purchased
    //
    $strsql = "SELECT purchases.product_id, "
        . "purchases.customer_id, "
        . "purchases.flags, "
        . "products.type, "
        . "purchases.purchase_date, "
        . "purchases.invoice_id, "
        . "purchases.start_date, "
        . "purchases.end_date, "
        . "purchases.stripe_customer_id, "
        . "purchases.stripe_subscription_id "
        . "FROM ciniki_customer_product_purchases AS purchases "
        . "INNER JOIN ciniki_customer_products AS products ON ("
            . "purchases.product_id = products.id "
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE purchases.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    //
    // If membership/subscription then search for last subscription/membership and use the end date as start date
    //
    if( $product['type'] == 10 ) {
        $strsql .= "AND products.type = 10 ";
    } else {
        $strsql .= "AND purchases.product_id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' ";
    }
    $strsql .= "AND purchases.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    $strsql .= "ORDER BY end_date DESC "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'purchase');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.477', 'msg'=>'Unable to load purchase', 'err'=>$rc['err']));
    }
    $purchases = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Setup the start date to be used for the membership/subscription
    //
    $start_date = new DateTime('NOW', new DateTimezone($intl_timezone));
    $purchase_date = clone $start_date;

    //
    // Check if there was a last purchase, and then use the end date as start date
    //
    if( isset($purchases[0]) ) {
        $last_purchase = $purchases[0];
    
        //
        // This makes sure if they renew before subscription over, it will add to their membership from last end date
        //
        $last_date = new DateTime($last_purchase['end_date'] . ' 12:00:00', new DateTimezone($intl_timezone));
        if( $last_date > $start_date ) {
            $start_date = $last_date;
        }
    }

    //
    // Setup end date for subscription
    //
    $end_date = clone $start_date;
    $end_date->add(new DateInterval('P' . $product['months'] . 'M'));
    $end_date->sub(new DateInterval('P1D'));


    $new_purchase = array(
        'product_id' => $product['id'],
        'customer_id' => $args['customer_id'],
        'flags' => 0,
        'purchase_date' => $purchase_date->format('Y-m-d'),
        'invoice_id' => (isset($args['invoice_id']) ? $args['invoice_id'] : 0),
        'start_date' => $start_date->format('Y-m-d'),
        'end_date' => $end_date->format('Y-m-d'),
        'stripe_customer_id' => 0,
        'stripe_subscription_id' => 0,
        );

    //
    // Add the new purchase
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.product_purchase', $new_purchase, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.119', 'msg'=>'Unable to add the product'));
    }
    
    //
    // Update customer record with membership details
    //
    $customer_updates = array();
    if( $product['type'] == 10 || $product['type'] == 20 ) {
        if( $customer['status'] != 10 ) {
            $customer_updates['status'] = 10;
        }
        if( $customer['member_status'] != 10 ) {
            $customer_updates['member_status'] = 10;
        }
        $customer_updates['member_lastpaid'] = $purchase_date->format('Y-m-d');
        $customer_updates['member_expires'] = $end_date->format('Y-m-d');
        if( $product['type'] == 20 ) {
            $customer_updates['membership_length'] = 60;
        } else {
            if( $product['months'] == 1 ) {
                $customer_updates['membership_length'] = 10;
            } else {
                // Assume yearly, but this is not accurate when dealing with Customer Products for memberships
                $customer_updates['membership_length'] = 20;
            }
        }
        $customer_updates['membership_type'] = 200;
    } 

    if( count($customer_updates) > 0 ) {
        //
        // Update the customer records with new membership details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $args['customer_id'], $customer_updates, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.421', 'msg'=>'Unable to update the customer'));
        }
    }

    return array('stat'=>'ok');
}
?>
