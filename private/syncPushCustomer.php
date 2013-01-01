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
function ciniki_customers_syncPushCustomer(&$ciniki, &$sync, $business_id, $args) {
	if( !isset($args['id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'107', 'msg'=>'Missing ID argument'));
	}

	//
	// Get the local customer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerGet');
	$rc = ciniki_customers_sync_customerGet($ciniki, $sync, $business_id, array('id'=>$args['id']));
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
