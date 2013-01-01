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
function ciniki_customers_sync_settingUpdate($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['setting']) || $args['setting'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'131', 'msg'=>'No setting specified'));
	}
	$remote_setting = $args['setting'];

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
	// Get the local setting
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'settingGet');
	$rc = ciniki_customers_sync_settingGet($ciniki, $sync, $business_id, array('setting'=>$remote_setting['detail_key']));
	if( $rc['stat'] != 'ok' && $rc['err']['code'] != 152 ) {
		return $rc;
	}
	if( !isset($rc['setting']) ) {
		//
		// Add the setting if it doesn't exist locally
		//
		$strsql = "INSERT INTO ciniki_customer_settings (business_id, detail_key, detail_value, date_added, last_updated) "
			. "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "'"
			. ", '" . ciniki_core_dbQuote($ciniki, $remote_setting['detail_key']) . "'"
			. ", '" . ciniki_core_dbQuote($ciniki, $remote_setting['detail_value']) . "'"
			. ", FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_setting['date_added']) . "') "
			. ", FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_setting['last_updated']) . "') "
			. ")";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	} else {
		$local_setting = $rc['setting'];
		// 
		// Update the existing setting
		//
		$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_setting, $local_setting, array(
			'detail_value'=>array(),
			'date_added'=>array('type'=>'uts'),
			'last_updated'=>array('type'=>'uts'),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
			$strsql = "UPDATE ciniki_customer_settings SET " . $rc['strsql'] . " "
				. "WHERE detail_key = '" . ciniki_core_dbQuote($ciniki, $local_setting['detail_key']) . "' "
				. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Update the customer history
	//
	if( isset($remote_setting['history']) ) {
		if( isset($local_setting['history']) ) {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $local_setting['detail_key'], 'ciniki_customer_settings', $remote_setting['history'], $local_setting['history'], array());
		} else {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
				'ciniki_customer_history', $remote_setting['detail_key'], 'ciniki_customer_settings', $remote_setting['history'], array(), array());
		}
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'130', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
		}
	}

	// FIXME: Add check for deleted settings

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
	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.syncPushSettings', 'args'=>array('ignore_sync_id'=>$sync['id']));

	return array('stat'=>'ok');
}
?>
