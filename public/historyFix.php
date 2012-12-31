<?php
//
// Description
// -----------
// This function will go through the history of the ciniki.customers module and add missing history elements.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_historyFix($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
	$rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.historyFix', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

	//
	// Check for items that are missing a add value in history
	//
	$fields = array('uuid', 'cid', 'status', 'type', 'prefix', 'first', 'middle', 'last', 'suffix',
		'company', 'department', 'title', 'phone_home', 'phone_work', 'phone_cell', 'phone_fax', 'notes', 'birthdate');
	foreach($fields as $field) {
		//
		// Get the list of customers which don't have a history for the field
		//
		$strsql = "SELECT ciniki_customers.id, ciniki_customers.$field AS field_value, "
			. "UNIX_TIMESTAMP(ciniki_customers.date_added) AS date_added, "
			. "UNIX_TIMESTAMP(ciniki_customers.last_updated) AS last_updated "
			. "FROM ciniki_customers "
			. "LEFT JOIN ciniki_customer_history ON (ciniki_customers.id = ciniki_customer_history.table_key "
				. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_customer_history.table_name = 'ciniki_customers' "
				. "AND (ciniki_customer_history.action = 1 OR ciniki_customer_history.action = 2) "
				. "AND ciniki_customer_history.table_field = '" . ciniki_core_dbQuote($ciniki, $field) . "' "
				. ") "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customers.$field <> '' "
			. "AND ciniki_customers.$field <> '0000-00-00' "
			. "AND ciniki_customers.$field <> '0000-00-00 00:00:00' "
			. "AND ciniki_customer_history.uuid IS NULL "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	
		$elements = $rc['rows'];
		foreach($elements AS $rid => $row) {
			$strsql = "INSERT INTO ciniki_customer_history (uuid, business_id, user_id, session, action, "
				. "table_name, table_key, table_field, new_value, log_date) VALUES ("
				. "UUID(), "
				. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
				. "'1', 'ciniki_customers', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['field_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $row['date_added']) . "') "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check for items that are missing a add value in history
	//
	$fields = array('uuid', 'customer_id','flags','address1', 'address2', 'city', 'province', 'postal', 'country');
	foreach($fields as $field) {
		//
		// Get the list of address which don't have a history for the field
		//
		$strsql = "SELECT ciniki_customer_addresses.id, ciniki_customer_addresses.$field AS field_value, "
			. "UNIX_TIMESTAMP(ciniki_customer_addresses.date_added) AS date_added, "
			. "UNIX_TIMESTAMP(ciniki_customer_addresses.last_updated) AS last_updated "
			. "FROM ciniki_customer_addresses "
			. "LEFT JOIN ciniki_customers ON (ciniki_customer_addresses.customer_id = ciniki_customers.id "
				. ") "
			. "LEFT JOIN ciniki_customer_history ON (ciniki_customer_addresses.id = ciniki_customer_history.table_key "
				. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_customer_history.table_name = 'ciniki_customer_addresses' "
				. "AND (ciniki_customer_history.action = 1 OR ciniki_customer_history.action = 2) "
				. "AND ciniki_customer_history.table_field = '" . ciniki_core_dbQuote($ciniki, $field) . "' "
				. ") "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customer_addresses.$field <> '' "
			. "AND ciniki_customer_addresses.$field <> '0000-00-00' "
			. "AND ciniki_customer_addresses.$field <> '0000-00-00 00:00:00' "
			. "AND ciniki_customer_history.uuid IS NULL "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	
		$elements = $rc['rows'];
		foreach($elements AS $rid => $row) {
			$strsql = "INSERT INTO ciniki_customer_history (uuid, business_id, user_id, session, action, "
				. "table_name, table_key, table_field, new_value, log_date) VALUES ("
				. "UUID(), "
				. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
				. "'1', 'ciniki_customer_addresses', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['field_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $row['date_added']) . "') "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check for items that are missing a add value in history
	//
	$fields = array('uuid', 'customer_id','email','password', 'temp_password_date', 'flags');
	foreach($fields as $field) {
		//
		// Get the list of emails which don't have a history for the field
		//
		$strsql = "SELECT ciniki_customer_emails.id, ciniki_customer_emails.$field AS field_value, "
			. "UNIX_TIMESTAMP(ciniki_customer_emails.date_added) AS date_added, "
			. "UNIX_TIMESTAMP(ciniki_customer_emails.last_updated) AS last_updated "
			. "FROM ciniki_customer_emails "
			. "LEFT JOIN ciniki_customers ON (ciniki_customer_emails.customer_id = ciniki_customers.id "
				. ") "
			. "LEFT JOIN ciniki_customer_history ON (ciniki_customer_emails.id = ciniki_customer_history.table_key "
				. "AND ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_customer_history.table_name = 'ciniki_customer_emails' "
				. "AND (ciniki_customer_history.action = 1 OR ciniki_customer_history.action = 2) "
				. "AND ciniki_customer_history.table_field = '" . ciniki_core_dbQuote($ciniki, $field) . "' "
				. ") "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customer_emails.$field <> '' "
			. "AND ciniki_customer_emails.$field <> '0000-00-00' "
			. "AND ciniki_customer_emails.$field <> '0000-00-00 00:00:00' "
			. "AND ciniki_customer_history.uuid IS NULL "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'history');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	
		$elements = $rc['rows'];
		foreach($elements AS $rid => $row) {
			$strsql = "INSERT INTO ciniki_customer_history (uuid, business_id, user_id, session, action, "
				. "table_name, table_key, table_field, new_value, log_date) VALUES ("
				. "UUID(), "
				. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['change_log_id']) . "', "
				. "'1', 'ciniki_customer_emails', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['id']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $field) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $row['field_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $row['date_added']) . "') "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>
