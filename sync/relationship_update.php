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
function ciniki_customers_relationship_update(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['relationship']) || $args['relationship'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'909', 'msg'=>'No relationship specified'));
	}

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the remote customer relationship to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"ciniki.customers.relationship.get", 'uuid'=>$args['uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'910', 'msg'=>"Unable to get the remote customer relationship", 'err'=>$rc['err']));
		}
		if( !isset($rc['relationship']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'911', 'msg'=>"customer relationship not found on remote server"));
		}
		$remote_relationship = $rc['relationship'];
	} else {
		$remote_relationship = $args['relationship'];
	}

	// FIXME: Check if the customer relationship was deleted locally before adding

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
	if( $remote_relationship['customer_id'] != '' ) {
		$rc = ciniki_customers_customer_lookup($ciniki, $sync, $business_id, array('remote_uuid'=>$remote_relationship['customer_id']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'899', 'msg'=>'Unable to lookup customer (' . $remote_relationship['customer_id'] . ')', 'err'=>$rc['err']));
		}
		if( isset($rc['id']) ) {
			$remote_relationship['customer_id'] = $rc['id'];
		} else {
			$remote_relationship['customer_id'] = '';
		}
	}
	if( $remote_relationship['related_id'] != '' ) {
		$rc = ciniki_customers_customer_lookup($ciniki, $sync, $business_id, array('remote_uuid'=>$remote_relationship['related_id']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'897', 'msg'=>'Unable to lookup customer', 'err'=>$rc['err']));
		}
		if( isset($rc['id']) ) {
			$remote_relationship['related_id'] = $rc['id'];
		} else {
			$remote_relationship['related_id'] = '';
		}
	}

	//
	// Get the local customer relationship
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'relationship_get');
	$rc = ciniki_customers_relationship_get($ciniki, $sync, $business_id, array('uuid'=>$remote_relationship['uuid'], 'translate'=>'no'));
	if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'922', 'msg'=>'Unable to get customer relationship', 'err'=>$rc['err']));
	}
	if( !isset($rc['relationship']) ) {
		$local_relationship = array();

		//
		// Add the relationship
		//
		$strsql = "INSERT INTO ciniki_customer_relationships (uuid, business_id, customer_id, relationship_type, related_id, "
			. "date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $remote_relationship['uuid']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_relationship['customer_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_relationship['relationship_type']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $remote_relationship['related_id']) . "', "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_relationship['date_added']) . "'), "
			. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_relationship['last_updated']) . "') "
			. ") "
			. "";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1035', 'msg'=>'Unable to add customer', 'err'=>$rc['err']));
		}
		if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1036', 'msg'=>'Unable to add customer'));
		}
		$relationship_id = $rc['insert_id'];
		$db_updated = 1;
	} else {
		$local_relationship = $rc['relationship'];
		$relationship_id = $rc['relationship']['id'];

		//
		// Compare basic elements of customer
		//
		$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_relationship, $local_relationship, array(
			'customer_id'=>array(),
			'relationship_type'=>array(),
			'related_id'=>array(),
			'date_added'=>array('type'=>'uts'),
			'last_updated'=>array('type'=>'uts'),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'924', 'msg'=>'Unable to update customer relationship', 'err'=>$rc['err']));
		}
		if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
			$strsql = "UPDATE ciniki_customer_relationships SET " . $rc['strsql'] . " "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_relationship['id']) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'926', 'msg'=>'Unable to update customer relationship', 'err'=>$rc['err']));
			}
			$db_updated = 1;
		}
	}

	//
	// Update the customer relationship history
	//
	if( isset($remote_relationship['history']) ) {
		if( isset($local_relationship['history']) ) {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $relationship_id, 'ciniki_customer_relationships', $remote_relationship['history'], $local_relationship['history'], 
				array('customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
					'related_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
				));
		} else {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $relationship_id, 'ciniki_customer_relationships', $remote_relationship['history'], array(), 
				array('customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
					'related_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
				));
		}
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'927', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
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
		$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.relationship.push', 'args'=>array('id'=>$relationship_id, 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok', 'id'=>$relationship_id);
}
?>
