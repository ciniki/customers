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
function ciniki_customers_email_update(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['email']) || $args['email'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1119', 'msg'=>'No email specified'));
	}

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the remote customer email to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"ciniki.customers.email.get", 'uuid'=>$args['uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1110', 'msg'=>"Unable to get the remote customer email", 'err'=>$rc['err']));
		}
		if( !isset($rc['email']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1113', 'msg'=>"customer email not found on remote server"));
		}
		$remote_email = $rc['email'];
	} else {
		$remote_email = $args['email'];
	}

	// FIXME: Check if the customer email was deleted locally before adding

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	$db_updated = 0;

	//
	// Translate customer ID fields
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customer_lookup');
	if( $remote_email['customer_id'] != '' ) {
		$rc = ciniki_customers_customer_lookup($ciniki, $sync, $business_id, array('remote_uuid'=>$remote_email['customer_id']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1114', 'msg'=>'Unable to lookup customer (' . $remote_email['customer_id'] . ')', 'err'=>$rc['err']));
		}
		if( isset($rc['id']) ) {
			$remote_email['customer_id'] = $rc['id'];
		} else {
			$remote_email['customer_id'] = '';
		}
	}

	//
	// Get the local customer email
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'email_get');
	$rc = ciniki_customers_email_get($ciniki, $sync, $business_id, array('uuid'=>$remote_email['uuid'], 'translate'=>'no'));
	if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1115', 'msg'=>'Unable to get customer email', 'err'=>$rc['err']));
	}
	if( !isset($rc['email']) ) {
		$local_email = array();

		//
		// Add the email
		//
		$strsql = "INSERT INTO ciniki_customer_emails (uuid, business_id, customer_id, "
			. "email, password, temp_password, temp_password_date, flags, "
			. "date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $remote_email['uuid']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_email['customer_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_email['email']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_email['password']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_email['temp_password']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_email['temp_password_date']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_email['flags']) . "', "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_email['date_added']) . "'), "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_email['last_updated']) . "') "
			. ") "
			. "";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1111', 'msg'=>'Unable to add customer', 'err'=>$rc['err']));
		}
		if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1112', 'msg'=>'Unable to add customer'));
		}
		$email_id = $rc['insert_id'];
		$db_updated = 1;
	} else {
		$local_email = $rc['email'];
		$email_id = $rc['email']['id'];

		//
		// Compare basic elements of customer
		//
		$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_email, $local_email, array(
			'customer_id'=>array(),
			'email'=>array(),
			'password'=>array(),
			'temp_password'=>array(),
			'temp_password_date'=>array(),
			'flags'=>array(),
			'date_added'=>array('type'=>'uts'),
			'last_updated'=>array('type'=>'uts'),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1116', 'msg'=>'Unable to update customer email', 'err'=>$rc['err']));
		}
		if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
			$strsql = "UPDATE ciniki_customer_emails SET " . $rc['strsql'] . " "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_email['id']) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1117', 'msg'=>'Unable to update customer email', 'err'=>$rc['err']));
			}
			$db_updated = 1;
		}
	}

	//
	// Update the customer email history
	//
	if( isset($remote_email['history']) ) {
		if( isset($local_email['history']) ) {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $email_id, 'ciniki_customer_emails', $remote_email['history'], $local_email['history'], 
				array('customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
				));
		} else {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $email_id, 'ciniki_customer_emails', $remote_email['history'], array(), 
				array('customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
				));
		}
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1118', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
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
	if( $db_updated > 0 ) {
		$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.email.push', 'args'=>array('id'=>$email_id, 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok', 'id'=>$email_id);
}
?>
