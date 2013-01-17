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
function ciniki_customers_relationship_get($ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['id']) || $args['id'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1039', 'msg'=>'No relationship specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customer_lookup');

	//
	// Get the customer relationship information
	//
	$strsql = "SELECT ciniki_customer_relationships.uuid AS relationship_uuid, ";
	if( !isset($args['translate']) || $args['translate'] != 'no' ) {	
		$strsql .= "c1.uuid AS customer_uuid , "
			. "c2.uuid AS related_uuid, ";
	}
	$strsql .= "ciniki_customer_relationships.id, ciniki_customer_relationships.customer_id, "
		. "ciniki_customer_relationships.relationship_type, ciniki_customer_relationships.related_id, "
		. "UNIX_TIMESTAMP(ciniki_customer_relationships.date_added) AS date_added, "
		. "UNIX_TIMESTAMP(ciniki_customer_relationships.last_updated) AS last_updated, "
		. "ciniki_customer_history.id AS history_id, "
		. "ciniki_customer_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.action, "
		. "ciniki_customer_history.table_field, "
		. "ciniki_customer_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
		. "FROM ciniki_customer_relationships "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customer_relationships.id = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_history.table_name = 'ciniki_customer_relationships' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
		. "LEFT JOIN ciniki_customers AS c1 ON (ciniki_customer_relationships.customer_id = c1.id) "
		. "LEFT JOIN ciniki_customers AS c2 ON (ciniki_customer_relationships.related_id = c2.id) "
		. "WHERE ciniki_customer_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( !isset($args['translate']) || $args['translate'] != 'no' ) {	
		$strsql .= "AND c1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND c2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	}
	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		$strsql .= "AND ciniki_customer_relationships.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' ";
	} elseif( isset($args['id']) && $args['id'] != '' ) {
		$strsql .= "AND ciniki_customer_relationships.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1038', 'msg'=>'No customer relationship specified'));
	}
	$strsql .= "ORDER BY log_date "
		. "";
	if( !isset($args['translate']) || $args['translate'] != 'no' ) {	
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'relationships', 'fname'=>'relationship_uuid', 
				'fields'=>array('uuid'=>'relationship_uuid', 'id', 'customer_id'=>'customer_uuid', 'relationship_type', 'related_id'=>'related_uuid', 
					'date_added', 'last_updated')),
			array('container'=>'history', 'fname'=>'history_uuid', 
				'fields'=>array('user'=>'user_uuid', 'session', 
					'action', 'table_field', 'new_value', 'log_date')),
			));
	} else {
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'relationships', 'fname'=>'relationship_uuid', 
				'fields'=>array('uuid'=>'relationship_uuid', 'id', 'customer_id'=>'customer_id', 'relationship_type', 'related_id'=>'related_id', 
					'date_added', 'last_updated')),
			array('container'=>'history', 'fname'=>'history_uuid', 
				'fields'=>array('user'=>'user_uuid', 'session', 
					'action', 'table_field', 'new_value', 'log_date')),
			));
	}
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'928', 'msg'=>'Error retrieving the customer relationship information', 'err'=>$rc['err']));
	}

	//
	// Check that one and only one row was returned
	//
	if( !isset($rc['relationships']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'929', 'msg'=>'Customer relationship does not exist'));
	}
	if( count($rc['relationships']) > 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'923', 'msg'=>'Customer relationship does not exist'));
	}
	$relationship = array_pop($rc['relationships']);

	if( !isset($relationship['history']) ) {
		$relationship['history'] = array();
	}

	//
	// Lookup the uuid's for history of customer ID and related ID
	//
	if( !isset($args['translate']) || $args['translate'] == 'yes' ) {	
		foreach($relationship['history'] as $uuid => $entry) {
			if( ($entry['table_field'] == 'customer_id' && is_numeric($entry['new_value']))
				|| ($entry['table_field'] == 'related_id' && is_numeric($entry['new_value'])) ) {
				$rc = ciniki_customers_customer_lookup($ciniki, $sync, $business_id, array('local_id'=>$entry['new_value']));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'930', 'msg'=>'Unable to get customer id (' . $entry['new_value'] . ')', 'err'=>$rc['err']));
				}
				$relationship['history'][$uuid]['new_value'] = $rc['uuid'];
			}
		}
	}

//	unset($customer['id']);

	return array('stat'=>'ok', 'relationship'=>$relationship);
}
?>
