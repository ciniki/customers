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
function ciniki_customers_customer_get($ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['email_uuid']) || $args['email_uuid'] == '')
		&& (!isset($args['address_uuid']) || $args['address_uuid'] == '')
		&& (!isset($args['relationship_uuid']) || $args['relationship_uuid'] == '')
		&& (!isset($args['id']) || $args['id'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'263', 'msg'=>'No customer specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Get the customer information
	//
	$strsql = "SELECT ciniki_customers.uuid AS customer_uuid, "
		. "ciniki_customers.id, cid, ciniki_customers.status, type, prefix, first, middle, last, suffix, "
		. "company, department, title, "
		. "phone_home, phone_work, phone_cell, phone_fax, "
		. "notes, birthdate, "
		. "UNIX_TIMESTAMP(ciniki_customers.date_added) AS date_added, "
		. "UNIX_TIMESTAMP(ciniki_customers.last_updated) AS last_updated, "
		. "ciniki_customer_history.id AS history_id, "
		. "ciniki_customer_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.action, "
		. "ciniki_customer_history.table_field, "
		. "ciniki_customer_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customers.id = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_history.table_name = 'ciniki_customers' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) ";
	if( isset($args['email_uuid']) && $args['email_uuid'] != '' ) {
		$strsql .= "LEFT JOIN ciniki_customer_emails ON (ciniki_customer.id = ciniki_customer_emails.customer_id "
			. "AND ciniki_customer_emails.uuid = '" . ciniki_core_dbQuote($ciniki, $args['email_uuid']) . ") ";
	} elseif( isset($args['address_uuid']) && $args['address_uuid'] != '' ) {
		$strsql .= "LEFT JOIN ciniki_customer_addresses ON (ciniki_customer.id = ciniki_customer_addresses.customer_id "
			. "AND ciniki_customer_addressess.uuid = '" . ciniki_core_dbQuote($ciniki, $args['address_uuid']) . ") ";
	} elseif( isset($args['relationship_uuid']) && $args['relationship_uuid'] != '' ) {
		$strsql .= "LEFT JOIN ciniki_customer_relationships ON (ciniki_customer.id = ciniki_customer_relationships.customer_id "
			. "AND ciniki_customer_relationships.uuid = '" . ciniki_core_dbQuote($ciniki, $args['relationship_uuid']) . ") ";
	}
	$strsql .= "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		$strsql .= "AND ciniki_customers.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' ";
	} elseif( isset($args['id']) && $args['id'] != '' ) {
		$strsql .= "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
	} elseif( isset($args['email_uuid']) && $args['email_uuid'] != '' ) {
		$strsql .= "AND ciniki_customer_emails.uuid = '" . ciniki_core_dbQuote($ciniki, $args['email_uuid']) . "' ";
	} elseif( isset($args['address_uuid']) && $args['address_uuid'] != '' ) {
		$strsql .= "AND ciniki_customer_addresses.uuid = '" . ciniki_core_dbQuote($ciniki, $args['address_uuid']) . "' ";
	} elseif( isset($args['relationship_uuid']) && $args['relationship_uuid'] != '' ) {
		$strsql .= "AND ciniki_customer_relationships.uuid = '" . ciniki_core_dbQuote($ciniki, $args['relationship_uuid']) . "' ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'286', 'msg'=>'No customer specified'));
	}
	$strsql .= "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_uuid', 
			'fields'=>array('uuid'=>'customer_uuid', 'id', 'cid', 'status', 'type', 
				'prefix', 'first', 'middle', 'last', 'suffix',
				'company', 'department', 'title',
				'phone_home', 'phone_work', 'phone_cell', 'phone_fax',
				'notes', 'birthdate', 
				'date_added', 'last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid', 
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'281', 'msg'=>'Error retrieving the customer information', 'err'=>$rc['err']));
	}

	//
	// Check that one and only one row was returned
	//
	if( !isset($rc['customers']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'164', 'msg'=>'Customer does not exist'));
	}
	if( count($rc['customers']) > 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'931', 'msg'=>'Customer does not exist'));
	}
	$customer = array_pop($rc['customers']);

	if( !isset($customer['history']) ) {
		$customer['history'] = array();
	}

	return array('stat'=>'ok', 'customer'=>$customer);
}
?>
