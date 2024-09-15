<?php
//
// Description
// -----------
// This method will delete a customer, only if all the attachments to that customer have also been deleted.
//
// Returns
// -------
//
function ciniki_customers_delete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.delete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the active modules for the tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'getActiveModules');
    $rc = ciniki_tenants_getActiveModules($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Get the uuid of the customer to be deleted
    //
    $strsql = "SELECT uuid, type FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.66', 'msg'=>'Unable to find existing customer'));
    }
    $uuid = $rc['customer']['uuid'];
    $customer_type = $rc['customer']['type'];

    //
    // Check if this customer is a parent
    //
    $strsql = "SELECT COUNT(*) as num_children "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.customers', 'children');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['children']) && $rc['children'] > 0 ) {
        if( $customer_type == 20 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.310', 'msg'=>'There are children attached to this account, unable to delete.'));
        } elseif( $customer_type == 2 || $customer_type == 30 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.311', 'msg'=>'There are admins or employees attached to this account, unable to delete.'));
        }
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.67', 'msg'=>'There are customer accounts to this account, unable to delete.'));
    }

    //
    // Check if any modules are currently using this customer
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.customers.customer', $args['customer_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.68', 'msg'=>'Unable to check if customer is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.69', 'msg'=>"The customer is still in use. " . $rc['msg']));
    }

    //  
    // Turn on autocommit
    //  
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // FIXME: Use hooks to delete objects in other modules.  This will be instead of
    // checkObjectUsed when it shouldn't be blocked from deleting.
    //

    //
    // FIXME: Convert to hooks.
    // Remove any subscriptions
    //
    if( isset($modules['ciniki.subscriptions']) ) {
        $strsql = "SELECT id, uuid "
            . "FROM ciniki_subscription_customers "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'item');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.70', 'msg'=>'Unable to remove subscriptions', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) ) {
            $subscriptions = $rc['rows'];
            foreach($subscriptions as $i => $row) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.subscriptions.customer',
                    $row['id'], $row['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return $rc;
                }
            }
            //
            // Update the last_change date in the tenant modules
            // Ignore the result, as we don't want to stop user updates if this fails.
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
            ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'subscriptions');
        }
    }

    //
    // Delete any phones
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_customer_phones "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.phone',
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
        }
    }

    //
    // Delete any addresses
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_customer_addresses "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.address',
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
        }
    }

    //
    // Delete any emails
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_customer_emails "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.email',
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
        }
    }

    //
    // Delete any links
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_customer_links "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.link',
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
        }
    }

    //
    // Delete any tags
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_customer_tags "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.tag',
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
        }
    }

    //
    // Delete the customer
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.customer',
        $args['customer_id'], $uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.71', 'msg'=>'Unable to delete, internal error.', 'err'=>$rc['err']));
    }

    //
    // Commit the database changes
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$args['customer_id']));

    return array('stat'=>'ok');
}
?>
