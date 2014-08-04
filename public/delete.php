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
	// Check for addresses
	//
	$strsql = "SELECT 'addresses', COUNT(*) "
		. "FROM ciniki_customer_addresses "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'num');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'779', 'msg'=>'Unable to check for addresses', 'err'=>$rc['err']));
	}
	if( isset($rc['num']['addresses']) && $rc['num']['addresses'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'769', 'msg'=>'Unable to delete, addresses still exist for this customer.'));
	}

	//
	// Check for emails
	//
	$strsql = "SELECT 'emails', COUNT(*) "
		. "FROM ciniki_customer_emails "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'num');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'770', 'msg'=>'Unable to check for emails', 'err'=>$rc['err']));
	}
	if( isset($rc['num']['emails']) && $rc['num']['emails'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'771', 'msg'=>'Unable to delete, emails still exist for this customer.'));
	}

	//
	// Check if any modules are currently using this customer
	//
	foreach($modules as $module => $m) {
		list($pkg, $mod) = explode('.', $module);
		$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'checkObjectUsed');
		if( $rc['stat'] == 'ok' ) {
			$fn = $rc['function_call'];
			$rc = $fn($ciniki, $args['business_id'], array(
				'object'=>'ciniki.customers.customer', 
				'object_id'=>$args['customer_id'],
				));
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1905', 'msg'=>'Unable to check if customer is still being used.', 'err'=>$rc['err']));
			}
			if( $rc['used'] != 'no' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1906', 'msg'=>"The customer is still in use. " . $rc['msg']));
			}
		}
	}

	//
	// Check for subscriptions
	//
	if( isset($modules['ciniki.subscriptions']) ) {
		$strsql = "SELECT 'subscriptions', COUNT(*) "
			. "FROM ciniki_subscription_customers "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND status = 10 "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'num');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'772', 'msg'=>'Unable to check for subscriptions', 'err'=>$rc['err']));
		}
		if( isset($rc['num']['subscriptions']) && $rc['num']['subscriptions'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'773', 'msg'=>'Unable to delete, subscriptions still exist for this customer.'));
		}
	}

	//  
	// Turn off autocommit
	//  
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Delete the customer
	//
	$strsql = "DELETE FROM ciniki_customers WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'776', 'msg'=>'Unable to delete, internal error.'));
	}

	//
	// Log the deletion
	//
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'],
		3, 'ciniki_customers', $args['customer_id'], '*', '');

	//
	// Remove any subscriptions
	//
	if( isset($modules['ciniki.subscriptions']) ) {
		$strsql = "SELECT ciniki_subscription_customers.id "
			. "FROM ciniki_subscriptions, ciniki_subscription_customers "
			. "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
			. "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'777', 'msg'=>'Unable to remove subscriptions', 'err'=>$rc['err']));
		}
		$subscriptions = $rc['rows'];
		foreach($subscriptions as $i => $row) {
			$strsql = "DELETE FROM ciniki_subscription_customers "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $row['id']) . "' "
				. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
				. "";
			$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.subscriptions');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'778', 'msg'=>'Unable to remove subscription', 'err'=>$rc['err']));
			}
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'],
				3, 'ciniki_subscription_customers', $row['id'], '*', '');
		}

		//
		// Update the last_change date in the business modules
		// Ignore the result, as we don't want to stop user updates if this fails.
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
		ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'subscriptions');
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

	$ciniki['syncqueue'][] = array('push'=>'ciniki.customers.customer', 'args'=>array('delete_uuid'=>$uuid, 'delete_id'=>$args['customer_id']));

	return array('stat'=>'ok');
}
?>
