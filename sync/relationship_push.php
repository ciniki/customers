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
function ciniki_customers_relationship_push(&$ciniki, &$sync, $business_id, $args) {
	if( isset($args['id']) && $args['id'] != '' ) {
		//
		// Get the local customer relationship
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'relationship_get');
		$rc = ciniki_customers_relationship_get($ciniki, $sync, $business_id, array('id'=>$args['id']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1025', 'msg'=>'Unable to get customer relationship'));
		}
		if( !isset($rc['relationship']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1026', 'msg'=>'customer relationship not found on remote server'));
		}
		$relationship = $rc['relationship'];

		//
		// Update the remote customer relationship
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.relationship.update', 'relationship'=>$relationship));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1027', 'msg'=>'Unable to sync customer relationship'));
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
					. "AND ciniki_customer_history.table_name = 'ciniki_customer_relationships' "
					. "AND ciniki_customer_history.action = 3 "
					. "AND ciniki_customer_history.table_key = '" . ciniki_core_dbQuote($ciniki, $args['delete_id']) . "' "
					. "AND ciniki_customer_history.table_field = '*' "
					. "ORDER BY log_date DESC "
					. "LIMIT 1 ";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1090', 'msg'=>'Unable to sync customer relationship', 'err'=>$rc['err']));
				}
				$history = $rc['history'];
			} else {
				$history = array();
			}
		} else {
			$history = $args['history'];
		}

		//
		// Update the remote customer relationship
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.relationship.delete', 'uuid'=>$args['delete_uuid'], 'history'=>$history));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'992', 'msg'=>'Unable to sync customer relationship'));
		}
		return array('stat'=>'ok');
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'908', 'msg'=>'Missing ID argument'));
}
?>
