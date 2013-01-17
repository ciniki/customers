<?php
//
// Description
// -----------
// This method will update a setting for the ciniki.customers module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_history_update(&$ciniki, &$sync, $business_id, $args) {
	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateModuleHistory');
	$args['history_table'] = 'ciniki_customer_history';
	$args['module'] = 'ciniki.customers';
	$args['table_key_maps'] = array('ciniki_customers'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
		'ciniki_customer_emails'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'email_lookup'),
		'ciniki_customer_addresses'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'address_lookup'),
		'ciniki_customer_relationships'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'relationship_lookup'),
		);
	$args['new_value_maps'] = array('customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
		'related_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
		);
	$rc = ciniki_core_syncUpdateModuleHistory($ciniki, $sync, $business_id, $args);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Add to syncQueue to sync with other servers.  This allows for cascading syncs.
	//
//	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.history.push', 'args'=>array('ignore_sync_id'=>$sync['id']));

	return array('stat'=>'ok');
}
?>
