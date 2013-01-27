<?php
//
// Description
// -----------
// This method will update one or more settings for the customers module.
//
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_updateSettings(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.updateSettings', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   


	//
	// The list of allowed fields for updating
	//
	$changelog_fields = array(
		'use-cid',
		'use-relationships',
		'use-birthdate',
		'types-1-label',
		'types-1-form',
		'types-1-type',
		'types-2-label',
		'types-2-form',
		'types-2-type',
		'types-3-label',
		'types-3-form',
		'types-3-type',
		'types-4-label',
		'types-4-form',
		'types-4-type',
		'types-5-label',
		'types-5-form',
		'types-5-type',
		'types-6-label',
		'types-6-form',
		'types-6-type',
		'types-7-label',
		'types-7-form',
		'types-7-type',
		'types-8-label',
		'types-8-form',
		'types-8-type',
		);
	//
	// Check each valid setting and see if a new value was passed in the arguments for it.
	// Insert or update the entry in the ciniki_customer_settings table
	//
	foreach($changelog_fields as $field) {
		if( isset($ciniki['request']['args'][$field]) ) {
			$strsql = "INSERT INTO ciniki_customer_settings (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $field) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
				2, 'ciniki_customer_settings', $field, 'detail_value', $ciniki['request']['args'][$field]);
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
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'customers');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.customers.settings', 'args'=>array());
//	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.setting.push', 'args'=>array());

	return array('stat'=>'ok');
}
?>
