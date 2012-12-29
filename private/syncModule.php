<?php
//
// Description
// -----------
// This function will sync the modules data with a remote server
//
// Arguments
// ---------
//						method, or 0 if no customer or relationship specified.
// 
// Returns
// -------
//
function ciniki_customers_syncModule($ciniki, $sync, $business_id, $type, $last_timestamp) {

	//
	// Get the remote list of customers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.customerList', 'type'=>$type, 'timestamp'=>$last_timestamp));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['customers']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'274', 'msg'=>'Unable to get remote customer'));
	}
	$remote_list = $rc['customers'];

	//
	// Load required sync methods
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerList');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerGet');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerUpdate');

	//
	// Get the local list of customers
	//
	$rc = ciniki_customers_sync_customerList($ciniki, $sync, $business_id, array('type'=>$type, 'timestamp'=>$last_timestamp));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
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
			// Check if uuid does not exist, and has not been deleted
			//
			if( !isset($local_list[$uuid]) || $local_list[$uuid] != $last_updated ) {
				//
				// Get the remote customer
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.customerGet', 'customer'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
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
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'284', 'msg'=>'Unable to add customer', $err=>$rc['err']));;
				}
				// FIXME: Add check to see if deleted
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
			if( !isset($remote_list[$uuid]) || $remote_list[$uuid] != $last_updated ) {
				// FIXME: Add check to see if deleted
				error_log("Update local customer: " . $uuid);
				//
				// Get the remote customer
				//
				$rc = ciniki_customers_sync_customerGet($ciniki, $sync, $business_id, array('customer'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
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
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'historyList');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'historyAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'historyGet');

	//
	// Now get the history from each side, and make sure it's complete
	//
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.historyList', 'type'=>$type, 'timestamp'=>$last_timestamp));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['history']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'260', 'msg'=>'Unable to get remote history'));
	}
	$remote_history = $rc['history'];
	
	//
	// Get the local history
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'historyList');
	$rc = ciniki_customers_sync_historyList($ciniki, $sync, $business_id, array('type'=>$type, 'timestamp'=>$last_timestamp));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['history']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'259', 'msg'=>'Unable to get local history'));
	}
	$local_history = $rc['history'];

	//
	// Compare remote and local history
	//
	if( ($sync['flags']&0x02) == 0x02 ) {
		foreach($remote_history as $uuid => $last_updated) {
			//
			// Check if uuid does not exist, and has not been deleted
			//
			if( !isset($local_history[$uuid]) ) {
				error_log("Add history: " . $uuid);
				
				//
				// Grab remote details
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.historyGet', 'history'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( !isset($rc['history']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'169', 'msg'=>'Customer not found on remote server'));
				}
				$history = $rc['history'];

				//
				// Add to local server
				//
				$rc = ciniki_customers_sync_historyAdd($ciniki, $sync, $business_id, array('history'=>$history));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			} 
		}
	}

	//
	// Compare local against remote history
	//
	if( ($sync['flags']&0x01) == 0x01 ) {
		foreach($local_history as $uuid => $last_updated) {
			//
			// Check if uuid does not exist, and has not been deleted
			//
			if( !isset($remote_history[$uuid]) ) {
				error_log("Add remote history: " . $uuid);
				$rc = ciniki_customers_sync_historyGet($ciniki, $sync, $business_id, array('history'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( !isset($rc['history']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'272', 'msg'=>'Customer not found on remote server'));
				}
				$history = $rc['history'];
				
				//
				// Add to remote server
				//
				$rc = ciniki_customers_sync_historyAdd($ciniki, $sync, $business_id, array('history'=>$history));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			} 
		}
	}

	return array('stat'=>'ok');
}
?>
