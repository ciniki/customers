<?php
//
// Description
// -----------
// Update members lastpaid based on membership purchases
//
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_updateMembersLastPaid(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.updateMembersLastPaid');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the list of purchases and customer details
    //
    $strsql = "SELECT customers.id, "
        . "customers.display_name, "
        . "customers.status, "
        . "customers.member_status, "
        . "customers.member_lastpaid, "
        . "customers.member_expires, "
        . "products.type, "
        . "products.months, "
        . "purchases.id AS purchase_id, "
        . "purchases.purchase_date, "
        . "purchases.end_date "
        . "FROM ciniki_customer_product_purchases AS purchases "
        . "INNER JOIN ciniki_customer_products AS products ON ("
            . "purchases.product_id = products.id "
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "purchases.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (products.type = 10 OR products.type = 20) "
        . "ORDER BY customers.id, purchases.purchase_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'status', 'member_status', 'member_lastpaid', 'member_expires'),
            ),
        array('container'=>'purchases', 'fname'=>'purchase_id',
            'fields'=>array('type', 'months', 'purchase_date', 'end_date'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.445', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
    }
    $customers = isset($rc['customers']) ? $rc['customers'] : array();

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

    foreach($customers as $customer) {
        $customer_updates = array(); 
        if( isset($customer['purchases']) ) {
            foreach($customer['purchases'] as $purchase) {
                if( $purchase['purchase_date'] > $customer['member_lastpaid'] ) {
                    $customer_updates['member_lastpaid'] = $purchase['purchase_date'];
                    if( $customer['status'] != 10 ) {
                        $customer_updates['status'] = 10;
                    }
                    if( $customer['member_status'] != 10 ) {
                        $customer_updates['member_status'] = 10;
                    }
                    if( $purchase['end_date'] > $customer['member_expires'] ) {
                        $customer_updates['member_expires'] = $purchase['end_date'];
                    }
                    if( $purchase['type'] == 20 ) {
                        $customer_updates['membership_length'] = 60;
                    } else {
                        if( $purchase['months'] == 1 ) {
                            $customer_updates['membership_length'] = 10;
                        } else {
                            // Assume yearly, but this is not accurate when dealing with Customer Products for memberships
                            $customer_updates['membership_length'] = 20;
                        }
                    }
                    $customer_updates['membership_type'] = 200; // Purchase type
                }
            }
        }
        if( count($customer_updates) > 0 ) {
            //
            // Update the customer records with new membership details
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $customer['id'], $customer_updates, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.424', 'msg'=>'Unable to update the customer'));
            } 
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

    return array('stat'=>'ok');
}
?>
