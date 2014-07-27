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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	error_log("WEB: auth $email");

	//
	// Get customer information
	//
	$strsql = "SELECT ciniki_customers.id, ciniki_customers.first, ciniki_customers.last, "
		. "ciniki_customer_emails.email, ciniki_customers.status, ciniki_customers.member_status, "
		. "ciniki_customers.dealer_status, ciniki_customers.distributor_status, "
		. "ciniki_customers.pricepoint_id "
		. "FROM ciniki_customer_emails, ciniki_customers "
		. "WHERE ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
		. "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
		. "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		error_log("WEB: auth $email fail");
		return $rc;
	}
	if( !isset($rc['customer']) || !is_array($rc['customer']) ) {
		error_log("WEB: auth $email fail (736)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'736', 'msg'=>'Unable to authenticate.'));
	}
	$customer = $rc['customer'];

	//
	// Check the customer status
	//
	if( !isset($customer['status']) || $customer['status'] == 0 || $customer['status'] >= 40 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1840', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
	}

	//
	// Get the sequence for the customers pricepoint if set
	//
	if( $customer['pricepoint_id'] > 0 ) {
		$strsql = "SELECT sequence, flags "
			. "FROM ciniki_customer_pricepoints "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $customer['pricepoint_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'pricepoint');
		if( $rc['stat'] != 'ok' ) {
			error_log("WEB: $email pricepoint not found");
			return $rc;
		}
		if( !isset($rc['pricepoint']) ) {
			error_log("WEB: $email pricepoint not found");
			$customer['pricepoint_id'] = 0;
			if( isset($customer['pricepoint']) ) { unset($customer['pricepoint']); }
		} else {
			$customer['pricepoint'] = array('id'=>$customer['pricepoint_id'],
				'sequence'=>$rc['pricepoint']['sequence'],
				'flags'=>$rc['pricepoint']['flags'],
				);
		}
	}

	//
	// Create a session for the customer
	//
//	session_start();
	$_SESSION['change_log_id'] = 'web.' . date('ymd.His');
	$_SESSION['business_id'] = $ciniki['request']['business_id'];
	$customer['price_flags'] = 0x01;
	if( $customer['status'] < 40 ) {
		$customer['price_flags'] |= 0x10;
	}
	if( $customer['member_status'] == 10 ) {
		$customer['price_flags'] |= 0x20;
	}
	if( $customer['dealer_status'] == 10 ) {
		$customer['price_flags'] |= 0x40;
	}
	if( $customer['distributor_status'] == 10 ) {
		$customer['price_flags'] |= 0x80;
	}
	$_SESSION['customer'] = $customer;
	$ciniki['session']['customer'] = $customer;
	$ciniki['session']['business_id'] = $ciniki['request']['business_id'];
	$ciniki['session']['change_log_id'] = $_SESSION['change_log_id'];
	$ciniki['session']['user'] = array('id'=>'-2');

	error_log("WEB: auth $email success");

	return array('stat'=>'ok');
}
?>
