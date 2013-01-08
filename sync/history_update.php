<?php
//
// Description
// -----------
// This method will update a setting for the ciniki.customers module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_history_update(&$ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '' )
		&& (!isset($args['history']) || $args['history'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'131', 'msg'=>'No history specified'));
	}

	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		//
		// Get the remote history to update
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>"ciniki.customers.history.get", 'uuid'=>$args['uuid']));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'980', 'msg'=>"Unable to get the remote history", 'err'=>$rc['err']));
		}
		if( !isset($rc['history']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'285', 'msg'=>"history not found on remote server"));
		}
		$remote_history = $rc['history'];
	} else {
		$remote_history = $args['history'];
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get the local history
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'history_get');
	$rc = ciniki_customers_history_get($ciniki, $sync, $business_id, array('uuid'=>$remote_history['uuid']));
	if( $rc['stat'] != 'ok' && $rc['err']['code'] != 152 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'979', 'msg'=>'Unable to get history', 'err'=>$rc['err']));
	}
	if( !isset($rc['history']) ) {
		//
		// history does not exist, add
		//
		$local_history = array();

		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'history_translate');
		$rc = ciniki_customers_history_translate($ciniki, $sync, $business_id, array('history'=>$remote_history));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1016', 'msg'=>'Unable to translate customer history'));
		}
		$remote_history = $rc['history'];

		//
		// Add the history to the ciniki_customer_history table
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
			'ciniki_customer_history', $remote_history['table_key'], $remote_history['table_name'], array($remote_history['uuid']=>$remote_history), array(), array(
				'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
			));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'943', 'msg'=>'Unable to update customer history', 'err'=>$rc['err']));
		}
	} else {
		//
		// history does exist, check if it needs updating
		//
		$local_history = $rc['history'];
		$strsql = "";
		if( $local_history['user'] == '' ) {
			if( $remote_history['user'] != '' ) {
				//
				// Add the history to the ciniki_customer_history table
				//
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
				$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
					'ciniki_customer_history', $remote_history['table_key'], $remote_history['table_name'], 
						array($remote_history['uuid']=>$remote_history), array($local_history['uuid']=>$local_history), array(
						'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
					));
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'943', 'msg'=>'Unable to update customer history', 'err'=>$rc['err']));
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
//	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.history.push', 'args'=>array('ignore_sync_id'=>$sync['id']));

	return array('stat'=>'ok');
}
?>
