<?php
//
// Description
// -----------
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_syncPushCustomer($ciniki, $sync, $business_id, $args) {
	if( !isset($args['id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'107', 'msg'=>'Missing ID argument'));
	}

	//
	// Get the UUID for the customer
	//
	$strsql = "SELECT uuid FROM ciniki_customers "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'109', 'msg'=>'Unable to find customer'));
	}
	if( !isset($rc['customer']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'106', 'msg'=>'Unable to locate customer'));
	}
	$uuid = $rc['customer']['uuid'];

	//
	// Get the local customer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerGet');
	$rc = ciniki_customers_sync_customerGet($ciniki, $sync, $business_id, array('customer'=>$uuid));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'110', 'msg'=>'Unable to get customer'));
	}
	if( !isset($rc['customer']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'108', 'msg'=>'Customer not found on remote server'));
	}
	$customer = $rc['customer'];

	//
	// Update the remote customer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.customerUpdate', 'customer'=>$customer));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'111', 'msg'=>'Unable to sync customer'));
	}

	return array('stat'=>'ok');
}
?>
