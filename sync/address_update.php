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
function ciniki_customers_address_update(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['address']) || $args['address'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1155', 'msg'=>'No address specified'));
	}

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the remote customer address to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"ciniki.customers.address.get", 'uuid'=>$args['uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1140', 'msg'=>"Unable to get the remote customer address", 'err'=>$rc['err']));
		}
		if( !isset($rc['address']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1141', 'msg'=>"customer address not found on remote server"));
		}
		$remote_address = $rc['address'];
	} else {
		$remote_address = $args['address'];
	}

	// FIXME: Check if the customer address was deleted locally before adding

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
	if( $remote_address['customer_id'] != '' ) {
		$rc = ciniki_customers_customer_lookup($ciniki, $sync, $business_id, array('remote_uuid'=>$remote_address['customer_id']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1142', 'msg'=>'Unable to lookup customer (' . $remote_address['customer_id'] . ')', 'err'=>$rc['err']));
		}
		if( isset($rc['id']) ) {
			$remote_address['customer_id'] = $rc['id'];
		} else {
			$remote_address['customer_id'] = '';
		}
	}

	//
	// Get the local customer address
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'address_get');
	$rc = ciniki_customers_address_get($ciniki, $sync, $business_id, array('uuid'=>$remote_address['uuid'], 'translate'=>'no'));
	if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1143', 'msg'=>'Unable to get customer address', 'err'=>$rc['err']));
	}
	if( !isset($rc['address']) ) {
		$local_address = array();

		//
		// Add the address
		//
		$strsql = "INSERT INTO ciniki_customer_addresses (uuid, business_id, customer_id, "
			. "address1, address2, city, province, postal, country, notes, flags, "
			. "date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['uuid']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['customer_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['address1']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['address2']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['city']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['province']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['postal']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['country']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['notes']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_address['flags']) . "', "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_address['date_added']) . "'), "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_address['last_updated']) . "') "
			. ") "
			. "";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1144', 'msg'=>'Unable to add customer address', 'err'=>$rc['err']));
		}
		if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1145', 'msg'=>'Unable to add customer address'));
		}
		$address_id = $rc['insert_id'];
		$db_updated = 1;
	} else {
		$local_address = $rc['address'];
		$address_id = $rc['address']['id'];

		//
		// Compare basic elements of customer
		//
		$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_address, $local_address, array(
			'customer_id'=>array(),
			'address1'=>array(),
			'address2'=>array(),
			'city'=>array(),
			'province'=>array(),
			'postal'=>array(),
			'country'=>array(),
			'notes'=>array(),
			'flags'=>array(),
			'date_added'=>array('type'=>'uts'),
			'last_updated'=>array('type'=>'uts'),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1146', 'msg'=>'Unable to update customer address', 'err'=>$rc['err']));
		}
		if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
			$strsql = "UPDATE ciniki_customer_addresses SET " . $rc['strsql'] . " "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_address['id']) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1147', 'msg'=>'Unable to update customer address', 'err'=>$rc['err']));
			}
			$db_updated = 1;
		}
	}

	//
	// Update the customer address history
	//
	if( isset($remote_address['history']) ) {
		if( isset($local_address['history']) ) {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $address_id, 'ciniki_customer_addresses', $remote_address['history'], $local_address['history'], 
				array('customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
				));
		} else {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $address_id, 'ciniki_customer_addresses', $remote_address['history'], array(), 
				array('customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
				));
		}
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1148', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
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
		$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.address.push', 'args'=>array('id'=>$address_id, 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok', 'id'=>$address_id);
}
?>
