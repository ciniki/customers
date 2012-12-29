<?php
//
// Description
// -----------
// This method will add a customer to local server
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_sync_customerUpdate($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['customer']) || $args['customer'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'273', 'msg'=>'No type specified'));
	}
	$remote_customer = $args['customer'];

	//
	// Check if customer already exists, and if not run the add script
	//
	$strsql = "SELECT id FROM ciniki_customers "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customers.uuid = '" . ciniki_core_dbQuote($ciniki, $remote_customer['uuid']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customer']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerAdd');
		return ciniki_customers_sync_customerAdd($ciniki, $sync, $business_id, $args);
	}

	//
	// Get the local customer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerGet');
	$rc = ciniki_customers_sync_customerGet($ciniki, $sync, $business_id, array('customer'=>$remote_customer['uuid']));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customer']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'228', 'msg'=>'Customer not found on remote server'));
	}
	$local_customer = $rc['customer'];
	$customer_id = $rc['customer']['id'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Compare basic elements of customer
	//
	$updatable_fields = array(
		'cid',
		'type',
		'prefix',
		'first',
		'middle',
		'last',
		'suffix',
		'company',
		'department',
		'title',
		'phone_home',
		'phone_work',
		'phone_cell',
		'phone_fax',
		'notes',
		'birthdate',
		'date_added',
		'last_updated',
		);
	$strsql = '';
	$comma = '';
	foreach($updatable_fields as $field) {
		if( $remote_customer[$field] != $local_customer[$field] ) {
			// FIXME: Check the history to determine who is right
			// Find the first occurance of field in history for both local and remote, compare log_date
			$strsql .= $comma . " $field = '" . ciniki_core_dbQuote($ciniki, $remote_customer[$field]) . "' ";
			$comma = ',';
		}
	}
	if( $strsql != '' ) {
		$strsql = "UPDATE ciniki_customers SET " . $strsql . " "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Update the customer history
	//
	if( isset($remote_customer['history']) ) {
		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
			'ciniki_customer_history', $customer_id, 'ciniki_customers', $remote_customer['history'], $local_customer['history'], array());
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'229', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'customer_id'=>$customer_id);
}
?>
