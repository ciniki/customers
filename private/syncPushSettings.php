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
function ciniki_customers_syncPushSettings(&$ciniki, &$sync, $business_id, $args) {
	$args['type'] = 'partial';
	//
	// Sync the settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'syncModuleSettings');
	$rc = ciniki_customers_syncModuleSettings($ciniki, $sync, $business_id, $args);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'254', 'msg'=>'Unable to sync settings'));
	}

	return array('stat'=>'ok');
}
?>
