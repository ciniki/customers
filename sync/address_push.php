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
function ciniki_customers_address_push(&$ciniki, &$sync, $business_id, $args) {
	if( isset($args['id']) && $args['id'] != '' ) {
		//
		// Get the local customer address
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'address_get');
		$rc = ciniki_customers_address_get($ciniki, $sync, $business_id, array('id'=>$args['id']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1134', 'msg'=>'Unable to get customer address'));
		}
		if( !isset($rc['address']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1135', 'msg'=>'customer address not found on remote server'));
		}
		$address = $rc['address'];

		//
		// Update the remote customer address
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.address.update', 'address'=>$address));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1136', 'msg'=>'Unable to sync customer address'));
		}
		return array('stat'=>'ok');
	} 
	
	elseif( isset($args['delete_uuid']) ) {
		if( !isset($args['history']) ) {
			if( isset($args['delete_id']) ) {
				//
				// Grab the history for the latest delete
				//
				$strsql = "SELECT "
					. "ciniki_customer_history.uuid AS uuid, "
					. "ciniki_users.uuid AS user, "
					. "ciniki_customer_history.action, "
					. "ciniki_customer_history.session, "
					. "ciniki_customer_history.table_field, "
					. "ciniki_customer_history.new_value, "
					. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
					. "FROM ciniki_customer_history "
					. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
					. "WHERE ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND ciniki_customer_history.table_name = 'ciniki_customer_addresses' "
					. "AND ciniki_customer_history.action = 3 "
					. "AND ciniki_customer_history.table_key = '" . ciniki_core_dbQuote($ciniki, $args['delete_id']) . "' "
					. "AND ciniki_customer_history.table_field = '*' "
					. "ORDER BY log_date DESC "
					. "LIMIT 1 ";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1137', 'msg'=>'Unable to sync customer address', 'err'=>$rc['err']));
				}
				$history = $rc['history'];
			} else {
				$history = array();
			}
		} else {
			$history = $args['history'];
		}

		//
		// Update the remote customer address
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.address.delete', 'uuid'=>$args['delete_uuid'], 'history'=>$history));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1138', 'msg'=>'Unable to sync customer address'));
		}
		return array('stat'=>'ok');
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1139', 'msg'=>'Missing ID argument'));
}
?>
