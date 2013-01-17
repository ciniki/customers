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
function ciniki_customers_email_get($ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['id']) || $args['id'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1095', 'msg'=>'No email specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customer_lookup');

	//
	// Get the customer email information
	//
	$strsql = "SELECT ciniki_customer_emails.uuid AS email_uuid, ";
	if( !isset($args['translate']) || $args['translate'] != 'no' ) {	
		$strsql .= "c1.uuid AS customer_uuid , ";
	}
	$strsql .= "ciniki_customer_emails.id, "
		. "ciniki_customer_emails.customer_id, "
		. "ciniki_customer_emails.email, "
		. "ciniki_customer_emails.password, "
		. "ciniki_customer_emails.temp_password, "
		. "ciniki_customer_emails.temp_password_date, "
		. "ciniki_customer_emails.flags, "
		. "UNIX_TIMESTAMP(ciniki_customer_emails.date_added) AS date_added, "
		. "UNIX_TIMESTAMP(ciniki_customer_emails.last_updated) AS last_updated, "
		. "ciniki_customer_history.id AS history_id, "
		. "ciniki_customer_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.action, "
		. "ciniki_customer_history.table_field, "
		. "ciniki_customer_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
		. "FROM ciniki_customer_emails "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customer_emails.id = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_history.table_name = 'ciniki_customer_emails' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
		. "LEFT JOIN ciniki_customers AS c1 ON (ciniki_customer_emails.customer_id = c1.id) "
		. "WHERE ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( !isset($args['translate']) || $args['translate'] != 'no' ) {	
		$strsql .= "AND c1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	}
	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		$strsql .= "AND ciniki_customer_emails.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' ";
	} elseif( isset($args['id']) && $args['id'] != '' ) {
		$strsql .= "AND ciniki_customer_emails.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1096', 'msg'=>'No customer email specified'));
	}
	$strsql .= "ORDER BY log_date "
		. "";
	if( !isset($args['translate']) || $args['translate'] != 'no' ) {	
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'emails', 'fname'=>'email_uuid', 
				'fields'=>array('uuid'=>'email_uuid', 'id', 'customer_id'=>'customer_uuid', 'email', 'password', 'temp_password', 
					'temp_password_date', 'flags', 'date_added', 'last_updated')),
			array('container'=>'history', 'fname'=>'history_uuid', 
				'fields'=>array('user'=>'user_uuid', 'session', 
					'action', 'table_field', 'new_value', 'log_date')),
			));
	} else {
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'emails', 'fname'=>'email_uuid', 
				'fields'=>array('uuid'=>'email_uuid', 'id', 'customer_id'=>'customer_id', 'email', 'password', 'temp_password', 
					'temp_password_date', 'flags', 'date_added', 'last_updated')),
			array('container'=>'history', 'fname'=>'history_uuid', 
				'fields'=>array('user'=>'user_uuid', 'session', 
					'action', 'table_field', 'new_value', 'log_date')),
			));
	}
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1097', 'msg'=>'Error retrieving the customer email information', 'err'=>$rc['err']));
	}

	//
	// Check that one and only one row was returned
	//
	if( !isset($rc['emails']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1098', 'msg'=>'Customer email does not exist'));
	}
	if( count($rc['emails']) > 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1099', 'msg'=>'Customer email does not exist'));
	}
	$email = array_pop($rc['emails']);

	if( !isset($email['history']) ) {
		$email['history'] = array();
	}

	//
	// Lookup the uuid's for history of customer ID and related ID
	//
	if( !isset($args['translate']) || $args['translate'] == 'yes' ) {	
		foreach($email['history'] as $uuid => $entry) {
			if( ($entry['table_field'] == 'customer_id' && is_numeric($entry['new_value']))
				|| ($entry['table_field'] == 'related_id' && is_numeric($entry['new_value'])) ) {
				$rc = ciniki_customers_customer_lookup($ciniki, $sync, $business_id, array('local_id'=>$entry['new_value']));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1100', 'msg'=>'Unable to get customer id (' . $entry['new_value'] . ')', 'err'=>$rc['err']));
				}
				$email['history'][$uuid]['new_value'] = $rc['uuid'];
			}
		}
	}

//	unset($customer['id']);

	return array('stat'=>'ok', 'email'=>$email);
}
?>
