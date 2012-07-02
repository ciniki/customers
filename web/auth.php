<?php
//
// Description
// -----------
// Authenticate the customer, and setup a session
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_customers_web_auth(&$ciniki, $business_id, $email, $password) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');

	//
	// Get customer information
	//
	$strsql = "SELECT ciniki_customers.id, ciniki_customers.first, ciniki_customers.last, "
		. "ciniki_customer_emails.email "
		. "FROM ciniki_customer_emails, ciniki_customers "
		. "WHERE ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
		. "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
		. "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customer']) || !is_array($rc['customer']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'736', 'msg'=>'Unable to update password.'));
	}
	$customer = $rc['customer'];

	//
	// Create a session for the customer
	//
	session_start();
	$_SESSION['customer'] = $customer;
	$_SESSION['change_log_id'] = 'web.' . date('ymd.His');
	$ciniki['session']['customer'] = $customer;
	$ciniki['session']['change_log_id'] = $_SESSION['change_log_id'];
	$ciniki['session']['user'] = array('id'=>'-2');

	return array('stat'=>'ok');
}
?>
