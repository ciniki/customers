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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

	//
	// Get customer information
	//
	$strsql = "SELECT ciniki_customers.id, parent_id, "
		. "ciniki_customers.first, ciniki_customers.last, ciniki_customers.display_name, "
		. "ciniki_customer_emails.email, ciniki_customers.status, ciniki_customers.member_status, "
		. "ciniki_customers.dealer_status, ciniki_customers.distributor_status, "
		. "ciniki_customers.pricepoint_id "
		. "FROM ciniki_customer_emails, ciniki_customers "
		. "WHERE ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
		. "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
		. "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
		. "ORDER BY parent_id ASC " 	// List parent accounts first
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		error_log("WEB [" . $ciniki['business']['details']['name'] . "]: auth $email fail (2601)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2601', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
	}
	//
	// Allow for email address to be attached to multiple accounts
	//
	if( isset($rc['rows']) ) {
		if( count($rc['rows']) > 1 ) {
			$customer = $rc['rows'][0];
			$customers = array();
			foreach($rc['rows'] as $cust) {
				$customers[$cust['id']] = $cust;
			}
		} elseif( count($rc['rows']) == 1 ) {
			$customer = $rc['rows'][0];
			$customers = array($rc['rows'][0]['id']=>$rc['rows'][0]);
		} else {
			error_log("WEB [" . $ciniki['business']['details']['name'] . "]: auth $email fail (2059)");
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2059', 'msg'=>'Unable to authenticate.'));
		}
	} else {
		error_log("WEB [" . $ciniki['business']['details']['name'] . "]: auth $email fail (736)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'736', 'msg'=>'Unable to authenticate.'));
	}

	//
	// Check the customer status
	//
	if( !isset($customer['status']) || $customer['status'] == 0 || $customer['status'] >= 40 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1840', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
	}

	//
	// Check for child accounts
	//
	if( isset($ciniki['business']['modules']['ciniki.customers']['flags']) 
		&& ($ciniki['business']['modules']['ciniki.customers']['flags']&0x200000) 
		) {
		//
		// Get all the parent customer_ids
		//
		$customer_ids = array($customer['id']);
		if( isset($customers) ) {
			foreach($customers as $cust) {
				if( $cust['parent_id'] == 0 ) {
					$customer_ids[] = $cust['id'];
				}
			}
		} elseif( $customer['parent_id'] == 0 ) {
			$customer_ids = array($customer['id']);
		}

		//
		// Get the child accounts
		//
		if( count($customer_ids) > 0 ) {
			$strsql = "SELECT ciniki_customers.id, parent_id, "
				. "ciniki_customers.first, ciniki_customers.last, ciniki_customers.display_name, "
				. "ciniki_customers.status, ciniki_customers.member_status, "
				. "ciniki_customers.dealer_status, ciniki_customers.distributor_status, "
				. "ciniki_customers.pricepoint_id "
				. "FROM ciniki_customers "
				. "WHERE ciniki_customers.parent_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
//				. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
//				. "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
//				. "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
//				. "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
				. "ORDER BY parent_id ASC " 	// List parent accounts first
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
			if( $rc['stat'] != 'ok' ) {
				error_log("WEB [" . $ciniki['business']['details']['name'] . "]: auth $email fail (2602)");
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2602', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
			}
			if( isset($rc['rows']) ) {
				foreach($rc['rows'] as $cust) {
					if( !isset($customers[$cust['id']]) ) {
						$customers[$cust['id']] = $cust;
					}
				}
			}
		}
	}

	//
	// Get the sequence for the customers pricepoint if set
	//
	if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x1000) ) {
		$strsql = "SELECT id, sequence, flags "
			. "FROM ciniki_customer_pricepoints "
//			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $customer['pricepoint_id']) . "' "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'pricepoints', 'fname'=>'id', 
				'fields'=>array('id', 'sequence', 'flags')),
			));
		if( $rc['stat'] != 'ok' ) {
			error_log("WEB [" . $ciniki['business']['details']['name'] . "]: $email pricepoints not found");
			return $rc;
		}
		if( !isset($rc['pricepoints']) ) {
			$pricepoints = array();
		} else {
			$pricepoints = $rc['pricepoints'];
		}
		if( $customer['pricepoint_id'] > 0 ) {
			if( isset($pricepoints[$customer['pricepoint_id']]) ) {
				$customer['pricepoint'] = $pricepoints[$customer['pricepoint_id']];
			} else {
				error_log("WEB [" . $ciniki['business']['details']['name'] . "]: $email pricepoints not found");
				if( isset($customer['pricepoint']) ) {
					unset($customer['pricepoint']);
				}
			}
		}
		if( isset($customers) && count($customers) > 0 ) {
			foreach($customers as $cid => $cust) {
				if( isset($cust['pricepoint_id']) 
					&& $cust['pricepoint_id'] > 0 
					&& isset($pricepoints[$cust['pricepoint_id']])
					) {
					$customers[$cid]['pricepoint'] = $pricepoints[$cust['pricepoint_id']];
				}
			}
		}
//		if( !isset($rc['pricepoint']) ) {
//			error_log("WEB: $email pricepoint not found");
//			$customer['pricepoint_id'] = 0;
//			if( isset($customer['pricepoint']) ) { unset($customer['pricepoint']); }
//		} else {
//			$customer['pricepoint'] = array('id'=>$customer['pricepoint_id'],
//				'sequence'=>$rc['pricepoint']['sequence'],
//				'flags'=>$rc['pricepoint']['flags'],
//				);
//		}
	}

	//
	// Create a session for the customer
	//
//	session_start();
	$_SESSION['change_log_id'] = 'web.' . date('ymd.His');
	$_SESSION['business_id'] = $ciniki['request']['business_id'];
	$customer['price_flags'] = 0x01;
	if( $customer['status'] < 50 ) {
		// they can see prices if not suspended/deleted
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
	foreach($customers as $cid => $cust) {
		$customers[$cid]['price_flags'] = 0x01;
		if( $cust['status'] < 50 ) {
			$customers[$cid]['price_flags'] |= 0x10;
		}
		if( $cust['member_status'] == 10 ) {
			$customers[$cid]['price_flags'] |= 0x20;
		}
		if( $cust['dealer_status'] == 10 ) {
			$customers[$cid]['price_flags'] |= 0x40;
		}
		if( $cust['distributor_status'] == 10 ) {
			$customers[$cid]['price_flags'] |= 0x80;
		}
	}
	$login = array('email'=>$email);
	$_SESSION['login'] = $login;
	$_SESSION['customer'] = $customer;
	$_SESSION['customers'] = $customers;
	$ciniki['session']['login'] = $login;
	$ciniki['session']['customer'] = $customer;
	$ciniki['session']['customers'] = $customers;
	$ciniki['session']['business_id'] = $ciniki['request']['business_id'];
	$ciniki['session']['change_log_id'] = $_SESSION['change_log_id'];
	$ciniki['session']['user'] = array('id'=>'-2');

	error_log("WEB [" . $ciniki['business']['details']['name'] . "]: auth $email success");

	return array('stat'=>'ok');
}
?>
