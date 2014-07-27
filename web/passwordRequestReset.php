<?php
//
// Description
// -----------
// This method will setup a temporary password for a user, and email 
// the temporary password to them.
//
// API Arguments
// ---------
// email:			The email address of the user to reset.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_customers_web_passwordRequestReset(&$ciniki, $business_id, $email, $url) {
	
	error_log("WEB: passwordRequestReset $email");

    //  
	// Create a random password for the user
	//  
	$password = ''; 
	$chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
	for($i=0;$i<32;$i++) {
		$password .= substr($chars, rand(0, strlen($chars)-1), 1); 
	}

	//
	// Get the username for the account
	//
	$strsql = "SELECT ciniki_customer_emails.id, email, ciniki_customers.uuid "
		. "FROM ciniki_customer_emails, ciniki_customers "
		. "WHERE ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
		. "AND ciniki_customers.status < 40 "
		. "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		error_log("WEB: changeTempPassword $email fail (725)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'725', 'msg'=>'Unable to reset password.', 'err'=>$rc['err']));
	}
	if( !isset($rc['customer']) || !isset($rc['customer']['email']) ) {
		error_log("WEB: changeTempPassword $email fail (726)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'726', 'msg'=>'Unable to reset password.'));
	}
	$customer = $rc['customer'];

	//
	// Turn off autocommit
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Set the new temporary password
	//
	$strsql = "UPDATE ciniki_customer_emails SET temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "'), "
		. "temp_password_date = UTC_TIMESTAMP(), "
		. "last_updated = UTC_TIMESTAMP() "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		error_log("WEB: changeTempPassword $email fail (727)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'727', 'msg'=>'Unable to reset password.'));
	}

	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		error_log("WEB: changeTempPassword $email fail (728)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'728', 'msg'=>'Unable to reset password.'));
	}

	//
	// FIXME: Add log entry to track password changes
	//

	//
	// Email the customer with the new password
	//
	if( $customer['email'] != '' 
		&& isset($ciniki['config']['ciniki.core']['system.email']) && $ciniki['config']['ciniki.core']['system.email'] != '' ) {
		//
		// Load the business mail template
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadBusinessTemplate');
		$rc = ciniki_mail_loadBusinessTemplate($ciniki, $business_id, array());
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$template = $rc['template'];
		$theme = $rc['theme'];

		//
		// Prepare Messages
		//
		$subject = "Password reset";
		$url = $url . '?email=' . urlencode($customer['email']) . "&pwd=$password";
		$html_message = $template['html_header']
			. "<tr><td style='" . $theme['td_body'] . "'>"
			. "<p style='" . $theme['p'] . "'>Hi, </p>"
			. "<p style='" . $theme['p'] . "'>You have requested a new password.  Please click on the following link to set a new password.  This link will only be valid for 2 hours.</p>"
			. "<p style='" . $theme['p'] . "'><a style='" . $theme['a'] . "' href='$url'>$url</a></p>"
			;
		$text_message = $template['text_header']
			. "Hi, \n\n"
			. "You have requested a new password.  "
			. "Please click the following link to reset your password.  This link will only be valid for 2 hours.\n"
			. "\n"
			. $url . "\n"
			. "\n"
			. "\n"
			. $template['text_footer'];

		//
		// The from address can be set in the config file.
		//
		$ciniki['emailqueue'][] = array('to'=>$customer['email'],
			'business_id'=>$business_id,
			'subject'=>$subject,
			'textmsg'=>$text_message,
			'htmlmsg'=>$html_message,
			);
	}

	//
	// Commit the changes and return
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		error_log("WEB: changeTempPassword $email fail (729)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'729', 'msg'=>'Unable to reset password.'));
	}

	//
	// Email sysadmins to let them know of a password reset request
	//
	if( isset($ciniki['config']['ciniki.customers']['password.forgot.notify']) && $ciniki['config']['ciniki.customers']['password.forgot.notify'] != '' ) {
		date_default_timezone_set('America/Eastern');
		$subject = $customer['display_name'] . " forgot password";
		$msg = "Customer password request: \n\n"
			. "email: " . $customer['email'] . "\n"
			. "time: " . date("D M j, Y H:i e") . "\n"
			. "" . "\n";
		$ciniki['emailqueue'][] = array('to'=>$ciniki['config']['ciniki.customers']['password.forgot.notify'],
			'subject'=>$subject,
			'textmsg'=>$msg,
			);
//		mail($ciniki['config']['ciniki.customers']['password.forgot.notify'], $subject, $msg, $headers, '-f' . $ciniki['config']['ciniki.core']['system.email']);
	}
	error_log("WEB: passwordRequestReset $email success");

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, 'ciniki', 'customers');

	return array('stat'=>'ok');
}
?>
