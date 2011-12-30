<?php
//
// Description
// -----------
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// business_id: 		The ID of the business the request is for.
// 
// Returns
// -------
//
function ciniki_customers_checkAccess($ciniki, $business_id, $method, $customer_id) {
	//
	// All customers module functions require authentication, so users 
	// are authenicated before they reach this function
	//

	//
	// Check if the module is turned on for the business
	// Check the business is active
	//
	$strsql = "SELECT ruleset FROM ciniki_businesses, ciniki_business_modules "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_businesses.status = 1 "														// Business is active
		. "AND ciniki_businesses.id = ciniki_business_modules.business_id "
		. "AND ciniki_business_modules.package = 'ciniki' "
		. "AND ciniki_business_modules.module = 'customers' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'module');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// Check the session user is a business owner
	//
	if( $business_id <= 0 ) {
		// If no business_id specified, then fail
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'376', 'msg'=>'Access denied'));
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	//
	// Find any users which are owners of the requested business_id
	//
	$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND package = 'ciniki' "
		. "AND (permission_group = 'owners' OR permission_group = 'employees') "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'378', 'msg'=>'Access denied', 'err'=>$rc['err']));
	}
	//
	// If the user has permission, return ok
	//
	if( !isset($rc['rows']) 
		|| !isset($rc['rows'][0]) 
		|| $rc['rows'][0]['user_id'] <= 0 
		|| $rc['rows'][0]['user_id'] != $ciniki['session']['user']['id'] ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'516', 'msg'=>'Access denied'));
	}

	// 
	// At this point, we have ensured the user is a part of the business.
	//

	//
	// Check the customer is attached to the business
	//
	if( $customer_id > 0 ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
		//
		// Make sure the customer is attached to the business
		//
		$strsql = "SELECT business_id, id FROM ciniki_customers "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
		$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'customers', 'customers', 'customer', array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'377', 'msg'=>'Access denied')));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'515', 'msg'=>'Access denied', 'err'=>$rc['err']));
		}
		if( $rc['num_rows'] != 1 
			|| $rc['customers'][0]['customer']['business_id'] != $business_id
			|| $rc['customers'][0]['customer']['id'] != $customer_id ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'379', 'msg'=>'Access denied'));
		}
	}

	//
	// All checks passed, return ok
	//
	return array('stat'=>'ok');
}
?>
