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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.delete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the active modules for the business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'getActiveModules');
    $rc = ciniki_businesses_getActiveModules($ciniki, $args['business_id']); 
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
    $strsql = "SELECT uuid FROM ciniki_customers "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'227', 'msg'=>'Unable to find existing customer'));
    }
    $uuid = $rc['customer']['uuid'];

    //
    // Check if this customer is a parent
    //
    $strsql = "SELECT COUNT(*) as num_children "
        . "FROM ciniki_customers "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.customers', 'children');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['children']) && $rc['children'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3378', 'msg'=>'There are children attached to this account, unable to delete.'));
    }

    //
    // Check if any modules are currently using this customer
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['business_id'], 'ciniki.customers.customer', $args['customer_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1905', 'msg'=>'Unable to check if customer is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1903', 'msg'=>"The customer is still in use. " . $rc['msg']));
    }
    return array('stat'=>'ok');

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
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'item');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'777', 'msg'=>'Unable to remove subscriptions', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) ) {
            $subscriptions = $rc['rows'];
            foreach($subscriptions as $i => $row) {
                $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.subscriptions.customer',
                    $row['id'], $row['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return $rc;
                }
            }
            //
            // Update the last_change date in the business modules
            // Ignore the result, as we don't want to stop user updates if this fails.
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
            ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'subscriptions');
        }
    }

    //
    // Delete any phones
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_customer_phones "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.customers.phone',
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
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.customers.address',
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
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.customers.email',
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
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.customers.link',
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
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.customers.customer',
        $args['customer_id'], $uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'776', 'msg'=>'Unable to delete, internal error.', 'err'=>$rc['err']));
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'customers');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$args['customer_id']));

    return array('stat'=>'ok');
}
?>
