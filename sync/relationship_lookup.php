<?php
//
// Description
// -----------
// This method will lookup a customer_id in the database, and return the uuid
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_relationship_lookup(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	//
	// Look for the user based on the UUID, and if not found make a request to
	// add from remote side
	//
	if( isset($args['remote_uuid']) && $args['remote_uuid'] != '' ) {
		$strsql = "SELECT id FROM ciniki_customer_relationships "
			. "WHERE ciniki_customer_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_relationships.uuid = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1071', 'msg'=>"Unable to get the customer relationship id", 'err'=>$rc['err']));
		}
		if( isset($rc['relationship']) ) {
			return array('stat'=>'ok', 'id'=>$rc['relationship']['id']);
		}
		
		//
		// If the id was not found in the customers table, try looking up in the history
		//
		$strsql = "SELECT table_key FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 1 "
			. "AND table_name = 'ciniki_customer_relationships' "
			. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "AND table_field = 'uuid' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1072', 'msg'=>'Unable to get customer relationship id from history', 'err'=>$rc['err']));
		}
		if( isset($rc['relationship']) ) {
			return array('stat'=>'ok', 'id'=>$rc['relationship']['table_key']);
		}

		//
		// Check to see if it exists on the remote side, and add customer if necessary
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, $business_id, array('method'=>'ciniki.customers.customer.get', 'relationship_uuid'=>$args['remote_uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1073', 'msg'=>'Unable to get customer from remote server', 'err'=>$rc['err']));
		}

		if( isset($rc['customer']) ) {
			$rc = ciniki_customers_customer_add($ciniki, $sync, $business_id, array('customer'=>$rc['customer']));
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1074', 'msg'=>'Unable to add customer to local server', 'err'=>$rc['err']));
			}
			return array('stat'=>'ok', 'id'=>$rc['customer']['id']);
		}
		
		//
		// Try again to find relationship_id after adding customer
		//
		$strsql = "SELECT id FROM ciniki_customer_relationships "
			. "WHERE ciniki_customer_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_relationships.uuid = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1075', 'msg'=>"Unable to get the customer relationship id", 'err'=>$rc['err']));
		}
		if( isset($rc['relationship']) ) {
			return array('stat'=>'ok', 'id'=>$rc['relationship']['id']);
		}
		
		//
		// If the id was not found in the customers table, try looking up in the history
		//
		$strsql = "SELECT table_key FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 1 "
			. "AND table_name = 'ciniki_customer_relationships' "
			. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
			. "AND table_field = 'uuid' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1076', 'msg'=>'Unable to get customer relationship id from history', 'err'=>$rc['err']));
		}
		if( isset($rc['relationship']) ) {
			return array('stat'=>'ok', 'id'=>$rc['relationship']['table_key']);
		}

		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1077', 'msg'=>'Unable to find customer'));
	}

	//
	// If requesting the local_id, the lookup in local database, don't bother with remote,
	// ID won't be there.
	//
	elseif( isset($args['local_id']) && $args['local_id'] != '' ) {
		$strsql = "SELECT ciniki_customer_relationships.uuid FROM ciniki_customer_relationships "
			. "WHERE ciniki_customer_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_relationships.id = '" . ciniki_core_dbQuote($ciniki, $args['local_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1078', 'msg'=>"Unable to get the customer relationship uuid", 'err'=>$rc['err']));
		}
		if( isset($rc['relationship']) ) {
			return array('stat'=>'ok', 'uuid'=>$rc['relationship']['uuid']);
		}
		
		//
		// If the id was not found in the customers table, try looking up in the history from when it was added
		//
		$strsql = "SELECT new_value FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND action = 1 "
			. "AND table_name = 'ciniki_customer_relationships' "
			. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $args['local_id']) . "' "
			. "AND table_field = 'uuid' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1079', 'msg'=>'Unable to get customer relationship id from history', 'err'=>$rc['err']));
		}
		if( isset($rc['relationship']) ) {
			return array('stat'=>'ok', 'uuid'=>$rc['relationship']['new_value']);
		}
		
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1080', 'msg'=>'Unable to find customer relationship'));
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1081', 'msg'=>'No customer relationship specified'));
}
?>
