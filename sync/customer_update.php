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
function ciniki_customers_customer_update(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['customer']) || $args['customer'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'273', 'msg'=>'No customer specified'));
	}

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the remote customer to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"ciniki.customers.customer.get", 'uuid'=>$args['uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'936', 'msg'=>"Unable to get the remote customer", 'err'=>$rc['err']));
		}
		if( !isset($rc['customer']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'935', 'msg'=>"customer not found on remote server"));
		}
		$remote_customer = $rc['customer'];
	} else {
		$remote_customer = $args['customer'];
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get the local customer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customer_get');
	$rc = ciniki_customers_customer_get($ciniki, $sync, $business_id, array('uuid'=>$remote_customer['uuid']));
	if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'962', 'msg'=>'Unable to get customer', 'err'=>$rc['err']));
	}
	$db_updated = 0;
	if( !isset($rc['customer']) ) {
		$local_customer = array();
		// FIXME: Check if the customer was deleted locally before adding

		//
		// Create the user record
		//
		$strsql = "INSERT INTO ciniki_customers (uuid, business_id, status, cid, type, prefix, first, middle, last, suffix, "
			. "company, department, title, notes, birthdate, "
			. "date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['uuid']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['status']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['cid']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['type']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['prefix']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['first']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['middle']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['last']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['suffix']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['company']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['department']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['title']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['notes']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_customer['birthdate']) . "', "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_customer['date_added']) . "'), "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_customer['last_updated']) . "') "
			. ") "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'953', 'msg'=>'Unable to add customer', 'err'=>$rc['err']));
		}
		if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'277', 'msg'=>'Unable to add customer'));
		}
		$customer_id = $rc['insert_id'];
		$db_updated = 1;
//		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customer_add');
//		$rc = ciniki_customers_customer_add($ciniki, $sync, $business_id, $args);
//		if( $rc['stat'] != 'ok' ) {
//			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'961', 'msg'=>'Unable to add customer', 'err'=>$rc['err']));
//		}
//		return $rc;
	} else {
		$local_customer = $rc['customer'];
		$customer_id = $rc['customer']['id'];

		//
		// Compare basic elements of customer
		//
		$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_customer, $local_customer, array(
			'cid'=>array(),
			'type'=>array(),
			'prefix'=>array(),
			'first'=>array(),
			'middle'=>array(),
			'last'=>array(),
			'suffix'=>array(),
			'company'=>array(),
			'department'=>array(),
			'title'=>array(),
			'phone_home'=>array(),
			'phone_work'=>array(),
			'phone_cell'=>array(),
			'phone_fax'=>array(),
			'notes'=>array(),
			'birthdate'=>array(),
			'date_added'=>array('type'=>'uts'),
			'last_updated'=>array('type'=>'uts'),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'963', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
		}
		if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
			$strsql = "UPDATE ciniki_customers SET " . $rc['strsql'] . " "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' "
				. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'964', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
			}
			$db_updated = 1;
		}
	}

	//
	// Update the customer history
	//
	if( isset($remote_customer['history']) ) {
		if( isset($local_customer['history']) ) {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $customer_id, 'ciniki_customers', $remote_customer['history'], $local_customer['history'], array());
		} else {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $customer_id, 'ciniki_customers', $remote_customer['history'], array(), array());
		}
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'229', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
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
		$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.customer.push', 'args'=>array('id'=>$customer_id, 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok', 'id'=>$customer_id);
}
?>
