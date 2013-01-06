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
function ciniki_customers_sync_customerGet($ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
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
		. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		$strsql .= "AND ciniki_customers.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' ";
	} elseif( isset($args['id']) && $args['id'] != '' ) {
		$strsql .= "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
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
	if( !isset($rc['customers']) || count($rc['customers']) != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'164', 'msg'=>'Customer does not exist'));
	}
	$customer = array_pop($rc['customers']);

	if( !isset($customer['history']) ) {
		$customer['history'] = array();
	}

	//
	// Get the customer email information
	//
	$strsql = "SELECT ciniki_customer_emails.id, ciniki_customer_emails.uuid AS email_uuid, ciniki_customer_emails.email, ciniki_customer_emails.password, "
		. "ciniki_customer_emails.temp_password, ciniki_customer_emails.temp_password_date, "
		. "ciniki_customer_emails.flags, "
		. "UNIX_TIMESTAMP(ciniki_customer_emails.date_added) AS date_added, "
		. "UNIX_TIMESTAMP(ciniki_customer_emails.last_updated) AS last_updated, "
		. "ciniki_customer_history.id AS history_id, "
		. "ciniki_customer_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.action, "
		. "ciniki_customer_history.table_field, "
		. "IF(ciniki_customer_history.table_field='customer_id',IFNULL(ciniki_customers.uuid, ciniki_customer_history.new_value),ciniki_customer_history.new_value) AS new_value, "
//		. "ciniki_customer_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
		. "FROM ciniki_customer_emails "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customer_emails.id = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_history.table_name = 'ciniki_customer_emails' "
			. ") "
		. "LEFT JOIN ciniki_customers ON (ciniki_customer_history.new_value = ciniki_customers.id "
			. "AND ciniki_customer_history.table_field = 'customer_id' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
		. "WHERE ciniki_customer_emails.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
		. "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'emails', 'fname'=>'email_uuid', 
			'fields'=>array('uuid'=>'email_uuid', 'id', 'email', 'password', 'temp_password', 'temp_password_date', 'flags',
				'date_added', 'last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid', 
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['emails']) ) {
		$customer['emails'] = $rc['emails'];
	
		//
		// Check for missing customer_id's, and try to get them from the history
		//
		$customer_uuids = array();
		foreach($customer['emails'] as $email_uuid => $details) {
			if( isset($details['history']) ) {
				foreach($details['history'] as $uuid => $entry) {
					if($entry['table_field'] == 'customer_id' && is_numeric($entry['new_value']) ) {
						if( isset($customer_uuids[$entry['new_value']]) ) {
							$customer['emails'][$email_uuid]['history'][$uuid]['new_value'] = $customer_uuids[$entry['new_value']];
						} else {
							$strsql = "SELECT new_value "
								. "FROM ciniki_customer_history "
								. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
								. "AND table_name = 'ciniki_customers' " 
								. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $entry['new_value']) . "' "
								. "AND table_field = 'uuid' "
								. "AND action = 1 "
								. "";
							$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
							if( $rc['stat'] != 'ok' ) {
								return $rc;
							}
							if( isset($rc['history']) ) {
								$customer['emails'][$email_uuid]['history'][$uuid]['new_value'] = $rc['history']['new_value'];
								// Save uuid to list for faster reference in future searches
								$customer_uuids[$entry['new_value']] = $rc['history']['new_value'];
							}
						}
					}
				}
			} else {
				$customer['emails'][$email_uuid]['history'] = array();
			}
		}
	}

	//
	// Get any deleted emails
	//
	$deleted = array();
	$strsql = "SELECT h1.id AS history_id, "
		. "h1.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "h1.session, "
		. "h1.action, "
		. "h1.table_field, "
		. "h1.table_key, "
		. "h1.new_value, "
		. "UNIX_TIMESTAMP(h1.log_date) AS log_date, h2.new_value AS uuid "
		. "FROM ciniki_customer_history AS h1 "
		. "LEFT JOIN ciniki_customer_history AS h2 ON (h1.table_key = h2.table_key "
			. "AND h2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND h2.table_field = 'uuid') "
		. "LEFT JOIN ciniki_users ON (h1.user_id = ciniki_users.id) "
		. "WHERE h1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND h1.table_name = 'ciniki_customer_emails' "
		. "AND h1.table_key IN (SELECT DISTINCT table_key FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
	    	. "AND table_name = 'ciniki_customer_emails' "
			. "AND table_field = 'customer_id' "
			. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
			. ") "
		. "ORDER BY h1.table_key, h1.log_date DESC "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'258', 'msg'=>'Unable to find deleted emails'));
	}
	$prev_key = 0;
	foreach($rc['rows'] as $rid => $row) {
		// Check for delete as the most recent history item
		if( $prev_key != $row['table_key'] && $row['action'] == 3 ) {
			$deleted[$row['uuid']] = array(
				'id'=>$row['history_id'],
				'uuid'=>$row['history_uuid'],
				'user'=>$row['user_uuid'],
				'session'=>$row['session'],
				'action'=>$row['action'],
				'table_field'=>$row['table_field'],
				'new_value'=>$row['new_value'],
				'log_date'=>$row['log_date']);
		}
		$prev_key = $row['table_key'];
	}
	if( count($deleted) > 0 ) {
		$customer['deleted_emails'] = $deleted;
	}

	//
	// Get the customer address information
	//
	$strsql = "SELECT ciniki_customer_addresses.uuid AS address_uuid, "
		. "ciniki_customer_addresses.id, ciniki_customer_addresses.flags, ciniki_customer_addresses.address1, "
		. "ciniki_customer_addresses.address2, ciniki_customer_addresses.city, "
		. "ciniki_customer_addresses.province, "
		. "ciniki_customer_addresses.postal, "
		. "ciniki_customer_addresses.country, "
		. "ciniki_customer_addresses.notes, "
		. "UNIX_TIMESTAMP(ciniki_customer_addresses.date_added) AS date_added, "
		. "UNIX_TIMESTAMP(ciniki_customer_addresses.last_updated) AS last_updated, "
		. "ciniki_customer_history.id AS history_id, "
		. "ciniki_customer_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.action, "
		. "ciniki_customer_history.table_field, "
		. "IF(ciniki_customer_history.table_field='customer_id',IFNULL(ciniki_customers.uuid, ciniki_customer_history.new_value),ciniki_customer_history.new_value) AS new_value, "
		. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
		. "FROM ciniki_customer_addresses "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customer_addresses.id = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_history.table_name = 'ciniki_customer_addresses' "
			. ") "
		. "LEFT JOIN ciniki_customers ON (ciniki_customer_history.new_value = ciniki_customers.id "
			. "AND ciniki_customer_history.table_field = 'customer_id' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
		. "WHERE ciniki_customer_addresses.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
		. "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'addresses', 'fname'=>'address_uuid', 
			'fields'=>array('id', 'flags', 'address1', 'address2', 'city', 'province', 'postal', 'country', 'notes',
				'date_added', 'last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid', 
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['addresses']) ) {
		$customer['addresses'] = $rc['addresses'];
	
		//
		// Check for missing customer_id's, and try to get them from the history
		//
		$customer_uuids = array();
		foreach($customer['addresses'] as $aid => $details) {
			if( isset($details['history']) ) {
				foreach($details['history'] as $uuid => $entry) {
					if($entry['table_field'] == 'customer_id' && is_numeric($entry['new_value']) ) {
						if( isset($customer_uuids[$entry['new_value']]) ) {
							$customer['addresses'][$aid]['history'][$uuid]['new_value'] = $customer_uuids[$entry['new_value']];
						} else {
							$strsql = "SELECT new_value "
								. "FROM ciniki_customer_history "
								. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
								. "AND table_name = 'ciniki_customers' " 
								. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $entry['new_value']) . "' "
								. "AND table_field = 'uuid' "
								. "AND action = 1 "
								. "";
							$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
							if( $rc['stat'] != 'ok' ) {
								return $rc;
							}
							if( isset($rc['history']) ) {
								$customer['addresses'][$aid]['history'][$uuid]['new_value'] = $rc['history']['new_value'];
								// Save uuid to list for faster reference in future searches
								$customer_uuids[$entry['new_value']] = $rc['history']['new_value'];
							}
						}
					}
				}
			} else {
				$customer['emails'][$email_uuid]['history'] = array();
			}
		}
	}

	//
	// Get any deleted addresses
	//
	$deleted = array();
	$strsql = "SELECT h1.id AS history_id, "
		. "h1.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "h1.session, "
		. "h1.action, "
		. "h1.table_field, "
		. "h1.table_key, "
		. "h1.new_value, "
		. "UNIX_TIMESTAMP(h1.log_date) AS log_date, h2.new_value AS uuid "
		. "FROM ciniki_customer_history AS h1 "
		. "LEFT JOIN ciniki_customer_history AS h2 ON (h1.table_key = h2.table_key "
			. "AND h2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND h2.table_field = 'uuid') "
		. "LEFT JOIN ciniki_users ON (h1.user_id = ciniki_users.id) "
		. "WHERE h1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND h1.table_name = 'ciniki_customer_addresses' "
		. "AND h1.table_key IN (SELECT DISTINCT table_key FROM ciniki_customer_history "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
	    	. "AND table_name = 'ciniki_customer_addresses' "
			. "AND table_field = 'customer_id' "
			. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
			. ") "
		. "ORDER BY h1.table_key, h1.log_date DESC "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'257', 'msg'=>'Unable to find deleted addresses'));
	}
	$prev_key = 0;
	foreach($rc['rows'] as $rid => $row) {
		// Check for delete as the most recent history item
		if( $prev_key != $row['table_key'] && $row['action'] == 3 ) {
			$deleted[$row['uuid']] = array(
				'id'=>$row['history_id'],
				'uuid'=>$row['history_uuid'],
				'user'=>$row['user_uuid'],
				'session'=>$row['session'],
				'action'=>$row['action'],
				'table_field'=>$row['table_field'],
				'new_value'=>$row['new_value'],
				'log_date'=>$row['log_date']);
		}
		$prev_key = $row['table_key'];
	}
	if( count($deleted) > 0 ) {
		$customer['deleted_addresses'] = $deleted;
	}

//	unset($customer['id']);

	return array('stat'=>'ok', 'customer'=>$customer);
}
?>
