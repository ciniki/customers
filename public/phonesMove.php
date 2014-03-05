<?php
//
// Description
// -----------
// This method will move phone numbers from the customer record to the ciniki_customer_phones table.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//
function ciniki_customers_phonesMove($ciniki) {
	//
	// Must be a sysadmin to run this
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1605', 'msg'=>'Access denied'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the list of home phones, and their history
	//
	$strsql = "SELECT ciniki_customers.id, "
		. "ciniki_customers.business_id, "
		. "'Home' AS plabel, "
		. "ciniki_customers.phone_home AS pnum, "
		. "ciniki_customer_history.user_id, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.log_date "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customers.business_id = ciniki_customer_history.business_id "
			. "AND ciniki_customer_history.table_name = 'ciniki_customers' "
			. "AND ciniki_customers.id = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.table_field = 'phone_home' "
			. ") "
		. "WHERE ciniki_customers.phone_home <> '' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id',
			'fields'=>array('id', 'business_id', 'plabel', 'pnum', 
				'user_id', 'session', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customers']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1602', 'msg'=>'Unable to find customers'));
	}
	$customers = $rc['customers'];

	foreach($customers as $customer) {
		//
		// Get UUID
		//
		$rc = ciniki_core_dbUUID($ciniki, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1605', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
		}
		$uuid = $rc['uuid'];

		//
		// Insert the phone
		//
		$strsql = "INSERT INTO ciniki_customer_phones (uuid, business_id, "
			. "customer_id, phone_label, phone_number, "
			. "flags, date_added, last_updated) VALUES ("
			. "'$uuid', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['business_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['plabel']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['pnum']) . "', "
			. "0, UTC_TIMESTAMP(), UTC_TIMESTAMP()"
			. ")";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$phone_id = $rc['insert_id'];

		//
		// Add change logs, with fudged user/session
		//
		$ciniki['session']['user']['id'] = $customer['user_id'];
		$ciniki['session']['change_log_id'] = $customer['session'];
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'uuid', $uuid);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'phone_label', $customer['plabel']);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'phone_number', $customer['pnum']);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'flags', '0');
	}
	
	//
	// Get the list of work phones, and their history
	//
	$strsql = "SELECT ciniki_customers.id, "
		. "ciniki_customers.business_id, "
		. "'Work' AS plabel, "
		. "ciniki_customers.phone_work AS pnum, "
		. "ciniki_customer_history.user_id, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.log_date "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customers.business_id = ciniki_customer_history.business_id "
			. "AND ciniki_customer_history.table_name = 'ciniki_customers' "
			. "AND ciniki_customers.id = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.table_field = 'phone_work' "
			. ") "
		. "WHERE ciniki_customers.phone_work <> '' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id',
			'fields'=>array('id', 'business_id', 'plabel', 'pnum', 
				'user_id', 'session', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customers']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1602', 'msg'=>'Unable to find customers'));
	}
	$customers = $rc['customers'];

	foreach($customers as $customer) {
		//
		// Get UUID
		//
		$rc = ciniki_core_dbUUID($ciniki, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1605', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
		}
		$uuid = $rc['uuid'];

		//
		// Insert the phone
		//
		$strsql = "INSERT INTO ciniki_customer_phones (uuid, business_id, "
			. "customer_id, phone_label, phone_number, "
			. "flags, date_added, last_updated) VALUES ("
			. "'$uuid', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['business_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['plabel']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['pnum']) . "', "
			. "0, UTC_TIMESTAMP(), UTC_TIMESTAMP()"
			. ")";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$phone_id = $rc['insert_id'];

		//
		// Add change logs, with fudged user/session
		//
		$ciniki['session']['user']['id'] = $customer['user_id'];
		$ciniki['session']['change_log_id'] = $customer['session'];
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'uuid', $uuid);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'phone_label', $customer['plabel']);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'phone_number', $customer['pnum']);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'flags', '0');
	}

	//
	// Get the list of cell phones, and their history
	//
	$strsql = "SELECT ciniki_customers.id, "
		. "ciniki_customers.business_id, "
		. "'Cell' AS plabel, "
		. "ciniki_customers.phone_cell AS pnum, "
		. "ciniki_customer_history.user_id, "
		. "ciniki_customer_history.session, "
		. "ciniki_customer_history.log_date "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_history ON (ciniki_customers.business_id = ciniki_customer_history.business_id "
			. "AND ciniki_customer_history.table_name = 'ciniki_customers' "
			. "AND ciniki_customers.id = ciniki_customer_history.table_key "
			. "AND ciniki_customer_history.table_field = 'phone_cell' "
			. ") "
		. "WHERE ciniki_customers.phone_cell <> '' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id',
			'fields'=>array('id', 'business_id', 'plabel', 'pnum', 
				'user_id', 'session', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customers']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1602', 'msg'=>'Unable to find customers'));
	}
	$customers = $rc['customers'];

	foreach($customers as $customer) {
		//
		// Get UUID
		//
		$rc = ciniki_core_dbUUID($ciniki, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1605', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
		}
		$uuid = $rc['uuid'];

		//
		// Insert the phone
		//
		$strsql = "INSERT INTO ciniki_customer_phones (uuid, business_id, "
			. "customer_id, phone_label, phone_number, "
			. "flags, date_added, last_updated) VALUES ("
			. "'$uuid', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['business_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['plabel']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer['pnum']) . "', "
			. "0, UTC_TIMESTAMP(), UTC_TIMESTAMP()"
			. ")";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$phone_id = $rc['insert_id'];

		//
		// Add change logs, with fudged user/session
		//
		$ciniki['session']['user']['id'] = $customer['user_id'];
		$ciniki['session']['change_log_id'] = $customer['session'];
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'uuid', $uuid);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'phone_label', $customer['plabel']);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'phone_number', $customer['pnum']);
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$customer['business_id'], 1, 'ciniki_customer_phones', $phone_id, 
			'flags', '0');
	}

	return array('stat'=>'ok');
}
?>
