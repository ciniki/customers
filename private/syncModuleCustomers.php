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
function ciniki_customers_syncModuleCustomers(&$ciniki, &$sync, $business_id, $args) {

	//
	// Load required sync methods
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerList');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerGet');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerUpdate');

	//
	// Get the remote list of customers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.customerList', 'type'=>$args['type'], 'since_uts'=>$sync['last_sync']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'925', 'msg'=>'Unable to get the remote customer list', 'err'=>$rc['err']));
	}

	if( !isset($rc['customers']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'274', 'msg'=>'Unable to get remote customer'));
	}
	$remote_list = $rc['customers'];

	//
	// Get the local list of customers
	//
	$rc = ciniki_customers_sync_customerList($ciniki, $sync, $business_id, array('type'=>$args['type'], 'since_uts'=>$sync['last_sync']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'913', 'msg'=>'Unable to get the local customer list', 'err'=>$rc['err']));
	}
	if( !isset($rc['customers']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'275', 'msg'=>'Unable to get local customers'));
	}
	$local_list = $rc['customers'];

	//
	// For the pull side
	//
	if( ($sync['flags']&0x02) == 0x02 ) {
		foreach($remote_list as $uuid => $last_updated) {
			//
			// A full sync will compare every customer, 
			// a partial or incremental will only check records where the last_updated differs
			// Check if uuid does not exist, and has not been deleted
			//
			if( $args['type'] == 'full' || !isset($local_list[$uuid]) || $local_list[$uuid] != $last_updated ) {
				//
				// Get the remote customer
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.customerGet', 'uuid'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'926', 'msg'=>'Unable to get the remote customer', 'err'=>$rc['err']));
				}
				if( !isset($rc['customer']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'283', 'msg'=>'Customer not found on remote server'));
				}
				$customer = $rc['customer'];

				//
				// Add to the local database
				//
				$rc = ciniki_customers_sync_customerUpdate($ciniki, $sync, $business_id, array('customer'=>$customer));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'284', 'msg'=>'Unable to add customer', 'err'=>$rc['err']));;
				}
			} 
		}
	}

	//
	// For the push side
	//
	if( ($sync['flags']&0x01) == 0x01 ) {
		foreach($local_list as $uuid => $last_updated) {
			//
			// Check if uuid does not exist, and has not been deleted
			//
			if( $args['type'] == 'full' || !isset($remote_list[$uuid]) || $remote_list[$uuid] != $last_updated ) {
				//
				// Get the local customer
				//
				$rc = ciniki_customers_sync_customerGet($ciniki, $sync, $business_id, array('uuid'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'980', 'msg'=>'Unable to get the local customer', 'err'=>$rc['err']));
				}
				if( !isset($rc['customer']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'285', 'msg'=>'Customer not found on remote server'));
				}
				$customer = $rc['customer'];

				//
				// Update the customer
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.customerUpdate', 'customer'=>$customer));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
	}

	return array('stat'=>'ok');
}
?>
