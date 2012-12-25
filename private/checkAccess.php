<?php
//
// Description
// -----------
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Arguments
// ---------
// ciniki:
// business_id: 		The ID of the business the request is for.
// method:				The method requested.
// req_id:				The ID of the customer or ID of the relationship for the 
//						method, or 0 if no customer or relationship specified.
// 
// Returns
// -------
//
function ciniki_customers_checkAccess($ciniki, $business_id, $method, $req_id) {
	//
	// Check if the business is active and the module is enabled
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
	$rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['ruleset']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'690', 'msg'=>'No permissions granted'));
	}
	$modules = $rc['modules'];

	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok', 'modules'=>$modules);
	}

	//
	// Check the session user is a business owner
	//
	if( $business_id <= 0 ) {
		// If no business_id specified, then fail
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'376', 'msg'=>'Access denied'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	//
	// Find any users which are owners of the requested business_id
	//
	$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "AND package = 'ciniki' "
		. "AND (permission_group = 'owners' OR permission_group = 'employees') "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
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


	if( $method == 'ciniki.customers.relationshipHistory' 
		|| $method == 'ciniki.customers.relationshipGet'
		|| $method == 'ciniki.customers.relationshipDelete'
		|| $method == 'ciniki.customers.relationshipUpdate' ) {
		//
		// Make sure the relationship is owned by the business
		//
		$strsql = "SELECT business_id, id "
			. "FROM ciniki_customer_relationships "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $req_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'319', 'msg'=>'Access denied', 'err'=>$rc['err']));
		}
		if( !isset($rc['relationship']) 
			|| $rc['relationship']['business_id'] != $business_id
			|| $rc['relationship']['id'] != $req_id ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'298', 'msg'=>'Access denied'));
		}
	}

	//
	// Check the customer is attached to the business
	//
	elseif( $req_id > 0 ) {
		//
		// Make sure the customer is attached to the business
		//
		$strsql = "SELECT business_id, id FROM ciniki_customers "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $req_id) . "' "
			. "";
		$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'customers', 'customer', array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'377', 'msg'=>'Access denied')));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'515', 'msg'=>'Access denied', 'err'=>$rc['err']));
		}
		if( $rc['num_rows'] != 1 
			|| $rc['customers'][0]['customer']['business_id'] != $business_id
			|| $rc['customers'][0]['customer']['id'] != $req_id ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'379', 'msg'=>'Access denied'));
		}
	}

	//
	// All checks passed, return ok
	//
	return array('stat'=>'ok', 'modules'=>$modules);
}
?>
