<?php
//
// Description
// -----------
// This function will sync the modules data with a remote server
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_syncModule(&$ciniki, &$sync, $business_id, $args) {

	//
	// Sync the customers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'syncModuleCustomers');
	$rc = ciniki_customers_syncModuleCustomers($ciniki, $sync, $business_id, $args);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'922', 'msg'=>'Unable to sync the customers', 'err'=>$rc['err']));
	}

	//
	// Sync the settings for customers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'syncModuleSettings');
	$rc = ciniki_customers_syncModuleSettings($ciniki, $sync, $business_id, $args);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'923', 'msg'=>'Unable to sync the customer settings', 'err'=>$rc['err']));
	}

	//
	// Sync the settings for customers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'syncModuleHistory');
	$rc = ciniki_customers_syncModuleHistory($ciniki, $sync, $business_id, $args);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'924', 'msg'=>'Unable to sync the customer history', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok');
}
?>
