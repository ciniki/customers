<?php
//
// Description
// -----------
// This is move a list of customers from one salesrep to another.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_salesrepChange($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'old_salesrep_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Original Sales Rep'), 
		'new_salesrep_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'New Sales Rep'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.salesrepChange', 0);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get the list of salesreps
	//
	$strsql = "SELECT ciniki_customers.id, ciniki_customers.uuid, "
		. "ciniki_customers.display_name "
		. "FROM ciniki_customers "
		. "WHERE ciniki_customers.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $args['old_salesrep_id']) . "' "
		. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_customers.sort_name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'display_name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		//
		// Change the salesrep_id for these customers
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
		$customers = $rc['customers'];
		foreach($customers as $customer_id => $customer) {
			$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.customer', $customer_id,
				array('salesrep_id'=>$args['new_salesrep_id']), 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return $rc;
			}
		}
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

	return array('stat'=>'ok');
}
?>
