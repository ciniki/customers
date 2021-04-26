<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_purchaseUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'purchase_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Membership Product Purchases'),
        'product_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Product'),
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Product'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'purchase_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Date Purchased'),
        'invoice_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Invoice ID'),
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Start Date'),
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'),
        'stripe_customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stripe Customer'),
        'stripe_subscription_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stripe Subscription'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.purchaseUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the product to get the existing customer id
    //
    $strsql = "SELECT ciniki_customer_product_purchases.id, "
        . "ciniki_customer_product_purchases.product_id, "
        . "ciniki_customer_product_purchases.customer_id, "
        . "ciniki_customer_product_purchases.flags, "
        . "ciniki_customer_product_purchases.purchase_date, "
        . "ciniki_customer_product_purchases.invoice_id, "
        . "ciniki_customer_product_purchases.start_date, "
        . "ciniki_customer_product_purchases.end_date, "
        . "ciniki_customer_product_purchases.stripe_customer_id, "
        . "ciniki_customer_product_purchases.stripe_subscription_id "
        . "FROM ciniki_customer_product_purchases "
        . "WHERE ciniki_customer_product_purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customer_product_purchases.id = '" . ciniki_core_dbQuote($ciniki, $args['purchase_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'purchase');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.441', 'msg'=>'Unable to load purchase', 'err'=>$rc['err']));
    }
    if( !isset($rc['purchase']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.442', 'msg'=>'Unable to find requested purchase'));
    }
    $purchase = $rc['purchase'];

    //
    // Check if more recent purchases
    //
    $strsql = "SELECT COUNT(id) AS num "
        . "FROM ciniki_customer_product_purchases "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $purchase['customer_id']) . "' "
        . "AND end_date > '" . ciniki_core_dbQuote($ciniki, $purchase['end_date']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.customers', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.426', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    $latest_purchase = 'yes';
    if( isset($rc['num']) && $rc['num'] > 0 ) {
        $latest_purchase = 'no';
    }

    //
    // Get the current member_lastpaid and member_expires 
    //
    $strsql = "SELECT id, display_name, member_lastpaid, member_expires "
        . "FROM ciniki_customers "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $purchase['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.443', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.444', 'msg'=>'Unable to find requested customer'));
    }
    $customer = $rc['customer'];
    
    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Membership Product Purchases in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.product_purchase', $args['purchase_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }

    //
    // Check if customer record needs updating
    //
    if( $latest_purchase == 'yes' && $purchase['purchase_date'] != '0000-00-00' && $purchase['purchase_date'] != '' ) {
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $purchase['customer_id'], array(
            'member_lastpaid' => (isset($args['purchase_date']) ? $args['purchase_date'] : $purchase['purchase_date']),
            'member_expires' => (isset($args['end_date']) ? $args['end_date'] : $purchase['end_date']),
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'customers');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.product_purchase', 'object_id'=>$args['purchase_id']));

    return array('stat'=>'ok');
}
?>
