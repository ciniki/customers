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
function ciniki_customers_syncModuleSettings(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check to see if there are any settings to be transferred
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'settingList');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'settingUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'settingGet');

	//
	// Now get the settings from each side, and make sure it's complete
	//
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.settingList', 'type'=>$args['type'], 'since_uts'=>$sync['last_sync']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'158', 'msg'=>'Unable to get remote settings', 'err'=>$rc['err']));
	}
	if( !isset($rc['settings']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'231', 'msg'=>'Unable to get remote settings'));
	}
	$remote_settings = $rc['settings'];
	
	//
	// Get the local settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'settingList');
	$rc = ciniki_customers_sync_settingList($ciniki, $sync, $business_id, array('type'=>$args['type'], 'since_uts'=>$sync['last_sync']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'197', 'msg'=>'Unable to get local settings', 'err'=>$rc['err']));
	}
	if( !isset($rc['settings']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'230', 'msg'=>'Unable to get local settings'));
	}
	$local_settings = $rc['settings'];

	//
	// Compare remote and local settings
	//
	if( ($sync['flags']&0x02) == 0x02 ) {
		foreach($remote_settings as $key => $last_updated) {
			//
			// Check if key does not exist, and has not been deleted
			//
			if( $args['type'] == 'full' || !isset($local_settings[$key]) || $local_settings[$key] != $last_updated ) {
//				error_log("Add settings: " . $key);
				
				//
				// Grab remote details
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.settingGet', 'setting'=>$key));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( !isset($rc['setting']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'157', 'msg'=>'Setting not found on remote server'));
				}
				$setting = $rc['setting'];

				//
				// Add to local server
				//
				$rc = ciniki_customers_sync_settingUpdate($ciniki, $sync, $business_id, array('setting'=>$setting));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			} 
		}
	}

	//
	// Compare local against remote settings
	//
	if( ($sync['flags']&0x01) == 0x01 ) {
		foreach($local_settings as $key => $last_updated) {
			//
			// Check if key does not exist, and has not been deleted
			//
			if( !isset($remote_settings[$key]) || $remote_settings[$key] != $last_updated ) {
//				error_log("Add remote setting: " . $key);
				$rc = ciniki_customers_sync_settingGet($ciniki, $sync, $business_id, array('setting'=>$key));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( !isset($rc['setting']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'154', 'msg'=>'Setting not found on local server'));
				}
				$setting = $rc['setting'];
				
				//
				// Add to remote server
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.customers.settingUpdate', 'setting'=>$setting));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			} 
		}
	}

	return array('stat'=>'ok');
}
?>
