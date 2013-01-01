<?php
//
// Description
// -----------
// This method will add a customer to local server
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_sync_customerAdd($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['customer']) || $args['customer'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'264', 'msg'=>'No type specified'));
	}
	$customer = $args['customer'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Create the user record
	//
	$strsql = "INSERT INTO ciniki_customers (uuid, business_id, status, cid, type, prefix, first, middle, last, suffix, "
		. "company, department, title, notes, birthdate, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $customer['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['cid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['prefix']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['first']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['middle']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['last']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['suffix']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['company']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['department']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['title']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['notes']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $customer['birthdate']) . "', "
		. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $customer['date_added']) . "'), "
		. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $customer['last_updated']) . "') "
		. ") "
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'277', 'msg'=>'Unable to add customer'));
	}
	$customer_id = $rc['insert_id'];

	if( isset($customer['history']) ) {
		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
			'ciniki_customer_history', $customer_id, 'ciniki_customers', $customer['history'], array(), array());
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'278', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
		}
	}

	// 
	// Create emails
	//
	if( isset($customer['emails']) ) {
		foreach($customer['emails'] as $uuid => $email) {
			//
			// Create the email record
			//
			$strsql = "INSERT INTO ciniki_customer_emails (uuid, business_id, customer_id, email, password, "
				. "temp_password, temp_password_date, flags, "
				. "date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $email['email']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $email['password']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $email['temp_password']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $email['temp_password_date']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $email['flags']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $email['date_added']) . "'), "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $email['last_updated']) . "') "
				. ") "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return $rc;
			}
			if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'167', 'msg'=>'Unable to add customer'));
			}
			$email_id = $rc['insert_id'];
			
			if( isset($email['history']) ) {
				$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
					'ciniki_customer_history', $email_id, 'ciniki_customer_emails', $email['history'], array(), array(
						'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
					));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'166', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
				}
			}
		}
	}

	// 
	// Create addresses
	//
	if( isset($customer['addresses']) ) {
		foreach($customer['addresses'] as $uuid => $address) {
			//
			// Create the address record
			//
			$strsql = "INSERT INTO ciniki_customer_addresses (uuid, customer_id, flags, "
				. "address1, address2, city, province, postal, country, notes, "
				. "date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $address['flags']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $address['address1']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $address['address2']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $address['city']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $address['province']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $address['postal']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $address['country']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $address['notes']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $address['date_added']) . "'), "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $address['last_updated']) . "') "
				. ") "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return $rc;
			}
			if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'165', 'msg'=>'Unable to add customer'));
			}
			$address_id = $rc['insert_id'];
			
			if( isset($address['history']) ) {
				$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
					'ciniki_customer_history', $address_id, 'ciniki_customer_addresses', $address['history'], array(), array(
						'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
					));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'160', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Add to syncQueue to sync with other servers.  This allows for cascading syncs.
	//
	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.syncPushCustomer', 'args'=>array('id'=>$customer_id, 'ignore_sync_id'=>$sync['id']));

	return array('stat'=>'ok', 'customer_id'=>$customer_id);
}
?>
