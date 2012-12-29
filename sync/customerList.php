<?php
//
// Description
// -----------
// This method will return the list of customers and their last_updated date.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_sync_customerList($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['type']) ||
		($args['type'] != 'partial' && $args['type'] != 'full' && $args['type'] != 'incremental') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'269', 'msg'=>'No type specified'));
	}
	if( $args['type'] == 'incremental' 
		&& (!isset($args['last_timestamp']) || $args['last_timestamp'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'270', 'msg'=>'No timestamp specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');

	//
	// Prepare the query to fetch the list
	//
	$strsql = "SELECT uuid, UNIX_TIMESTAMP(last_updated) AS last_updated "	
		. "FROM ciniki_customers "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( $args['type'] == 'incremental' ) {
		$strsql .= "WHERE UNIX_TIMESTAMP(ciniki_customers.last_updated) >= '" . ciniki_core_dbQuote($ciniki, $args['last_timestamp']) . "' ";
	}
	$strsql .= "ORDER BY last_updated "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.customers', 'customers', 'uuid');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'271', 'msg'=>'Unable to get list', 'err'=>$rc['err']));
	}

	if( !isset($rc['customers']) ) {
		return array('stat'=>'ok', 'customers'=>array());
	}

	return array('stat'=>'ok', 'customers'=>$rc['customers']);
}
?>
