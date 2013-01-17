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
function ciniki_customers_address_lookup(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	//
	// Look for the user based on the UUID, and if not found make a request to
	// add from remote side
	//
	if( isset($args['remote_uuid']) && $args['remote_uuid'] != '' ) {
		$strsql = "SELECT ciniki_customer_addresses.id FROM ciniki_customer_addresses "
			. "WHERE ciniki_customer_addresses.uuid = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "AND ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1060', 'msg'=>"Unable to get the customer address id", 'err'=>$rc['err']));
		}
		if( isset($rc['address']) ) {
			return array('stat'=>'ok', 'id'=>$rc['address']['id']);
		}
		
		//
		// If the id was not found in the customers table, try looking up in the history
		//
		$strsql = "SELECT table_key FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 1 "
			. "AND table_name = 'ciniki_customer_addresses' "
			. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "AND table_field = 'uuid' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1082', 'msg'=>'Unable to get customer address id from history', 'err'=>$rc['err']));
		}
		if( isset($rc['history']) ) {
			return array('stat'=>'ok', 'id'=>$rc['history']['table_key']);
		}

		//
		// Check to see if it exists on the remote side, and add customer if necessary
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, $business_id, array('method'=>'ciniki.customers.address.get', 'address_uuid'=>$args['remote_uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1083', 'msg'=>'Unable to get customer address from remote server', 'err'=>$rc['err']));
		}

		if( isset($rc['address']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'address_update');
			$rc = ciniki_customers_address_update($ciniki, $sync, $business_id, array('address'=>$rc['address']));
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1063', 'msg'=>'Unable to add customer address to local server', 'err'=>$rc['err']));
			}
			return array('stat'=>'ok', 'id'=>$rc['address']['id']);
		}

		//
		// Try again to get the address id
		//
		$strsql = "SELECT ciniki_customer_addresses.id FROM ciniki_customer_addresses "
			. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_addresses.uuid = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1064', 'msg'=>"Unable to get the customer address id", 'err'=>$rc['err']));
		}
		if( isset($rc['address']) ) {
			return array('stat'=>'ok', 'id'=>$rc['address']['id']);
		}
		
		//
		// If the id was not found in the customers table, try looking up in the history
		//
		$strsql = "SELECT table_key FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 1 "
			. "AND table_name = 'ciniki_customer_addresses' "
			. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "AND table_field = 'uuid' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1065', 'msg'=>'Unable to get customer address id from history', 'err'=>$rc['err']));
		}
		if( isset($rc['history']) ) {
			return array('stat'=>'ok', 'id'=>$rc['history']['table_key']);
		}

		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1066', 'msg'=>'Unable to find customer'));
	}

	//
	// If requesting the local_id, the lookup in local database, don't bother with remote,
	// ID won't be there.
	//
	elseif( isset($args['local_id']) && $args['local_id'] != '' ) {
		$strsql = "SELECT ciniki_customer_addresses.uuid FROM ciniki_customer_addresses "
			. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_addresses.id = '" . ciniki_core_dbQuote($ciniki, $args['local_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1067', 'msg'=>"Unable to get the customer address uuid", 'err'=>$rc['err']));
		}
		if( isset($rc['address']) ) {
			return array('stat'=>'ok', 'uuid'=>$rc['address']['uuid']);
		}
		
		//
		// If the id was not found in the customers table, try looking up in the history from when it was added
		//
		$strsql = "SELECT new_value FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 1 "
			. "AND table_name = 'ciniki_customer_addresses' "
			. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $args['local_id']) . "' "
			. "AND table_field = 'uuid' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1068', 'msg'=>'Unable to get customer id from history', 'err'=>$rc['err']));
		}
		if( isset($rc['address']) ) {
			return array('stat'=>'ok', 'uuid'=>$rc['address']['new_value']);
		}
		
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1069', 'msg'=>'Unable to find customer'));
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1070', 'msg'=>'No customer specified'));
}
?>
