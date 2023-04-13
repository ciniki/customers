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
function ciniki_customers_productsUpdateCustomer(&$ciniki, $tnid, $args) {


    if( !isset($args['customer_id']) || $args['customer_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.492', 'msg'=>'No Customer Specified'));
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
    // Load the last product (non addon) to be purchased
    //
    $strsql = "SELECT purchases.product_id, "
        . "purchases.customer_id, "
        . "purchases.flags, "
        . "products.type, "
        . "purchases.purchase_date, "
        . "purchases.invoice_id, "
        . "purchases.invoice_item_id, "
        . "purchases.start_date, "
        . "purchases.end_date, "
        . "purchases.stripe_customer_id, "
        . "purchases.stripe_subscription_id "
        . "FROM ciniki_customer_product_purchases AS purchases "
        . "INNER JOIN ciniki_customer_products AS products ON ("
            . "purchases.product_id = products.id "
            . "AND products.type <= 20 "    // No addons
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE purchases.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY purchases.end_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'purchase');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.477', 'msg'=>'Unable to load purchase', 'err'=>$rc['err']));
    }
    if( !isset($rc['purchase']) ) {
        return array('stat'=>'ok');
    }
    $purchase = $rc['purchase'];
  
    $update_args = array();
    //
    // Compare expires
    //
    if( $customer['member_expires'] != $purchase['end_date'] ) {
        $update_args['member_expires'] = $purchase['end_date'];
    }

    //
    // Update the customer
    //
    if( count($update_args) > 0 ) {
        //
        // Update the customer records with new membership details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $customer['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.421', 'msg'=>'Unable to update the customer'));
        }
    }

    return array('stat'=>'ok');
}
?>
