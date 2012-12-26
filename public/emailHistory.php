<?php
//
// Description
// -----------
// This method will return the history of a customers email address field.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the email history for.
// customer_id:			The ID of the customer to get the email history for.
// email_id:			The ID of the email address to get this history for.
// field:				The field to get the history of.
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_customers_emailHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
		'email_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
	$rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.emailHistory', $args['customer_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check the email ID belongs to the requested customer
	//
	$strsql = "SELECT id, customer_id "
		. "FROM ciniki_customer_emails "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['email_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( !isset($rc['email']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'742', 'msg'=>'Access denied'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 'ciniki_customer_emails', $args['email_id'], $args['field']);
}
?>
