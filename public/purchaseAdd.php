<?php
//
// Description
// -----------
// This method will add a new membership product purchases for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Membership Product Purchases to.
//
// Returns
// -------
//
function ciniki_customers_purchaseAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Product'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'purchase_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Date Purchased'),
        'invoice_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice ID'),
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
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.purchaseAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
        . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'product');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.419', 'msg'=>'Unable to load product', 'err'=>$rc['err']));
    }
    if( !isset($rc['product']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.420', 'msg'=>'Unable to find requested product'));
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
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.422', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.423', 'msg'=>'Unable to find customer'));
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
    // Add the membership product purchases to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.product_purchase', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    $purchase_id = $rc['id'];

    //
    // Update customer record with membership details
    //
    $customer_updates = array();
    if( $product['type'] == 10 || $product['type'] == 20 ) {
        //
        // If new membership, update customer record
        //
        if( $args['purchase_date'] > $customer['member_lastpaid'] ) {
            if( $customer['status'] != 10 ) {
                $customer_updates['status'] = 10;
            }
            if( $customer['member_status'] != 10 ) {
                $customer_updates['member_status'] = 10;
            }
            $customer_updates['member_lastpaid'] = $args['purchase_date'];
            if( $args['end_date'] > $customer['member_expires'] ) {
                $customer_updates['member_expires'] = $args['end_date'];
            }
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
    } 

    if( count($customer_updates) > 0 ) {
        //
        // Update the customer records with new membership details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $args['customer_id'], $customer_updates, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.424', 'msg'=>'Unable to update the customer'));
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.product_purchase', 'object_id'=>$purchase_id));

    return array('stat'=>'ok', 'id'=>$purchase_id);
}
?>
