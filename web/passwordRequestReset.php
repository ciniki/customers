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
function ciniki_customers_web_passwordRequestReset($ciniki, $business_id, $email, $url) {
	
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
		. "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
		. "";
	require_once($ciniki['config']['ciniki.core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'user');
	if( $rc['stat'] != 'ok' ) {
		error_log("WEB: changeTempPassword $email fail (725)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'725', 'msg'=>'Unable to reset password.', 'err'=>$rc['err']));
	}
	if( !isset($rc['user']) || !isset($rc['user']['email']) ) {
		error_log("WEB: changeTempPassword $email fail (726)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'726', 'msg'=>'Unable to reset password.'));
	}
	$user = $rc['user'];

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['ciniki.core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['ciniki.core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['ciniki.core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
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
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
		. "";
	require_once($ciniki['config']['ciniki.core']['modules_dir'] . '/core/private/dbUpdate.php');
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
	// Email the user with the new password
	//
	if( $user['email'] != '' 
		&& isset($ciniki['config']['ciniki.core']['system.email']) && $ciniki['config']['ciniki.core']['system.email'] != '' ) {
		$subject = "Password reset";
		$msg = "Hi, \n\n"
			. "You have requested a new password.  "
			. "Please click the following link to reset your password.  This link will only be valid for 2 hours.\n"
			. "\n"
			. $url . '?email=' . urlencode($user['email']) . "&pwd=$password\n"
			. "\n"
			. "\n";
		//
		// The from address can be set in the config file.
		//
		$headers = 'From: "' . $ciniki['config']['ciniki.core']['system.email.name'] . '" <' . $ciniki['config']['ciniki.core']['system.email'] . ">\r\n" .
				'Reply-To: "' . $ciniki['config']['ciniki.core']['system.email.name'] . '" <' . $ciniki['config']['ciniki.core']['system.email'] . ">\r\n" .
				'X-Mailer: PHP/' . phpversion();
		mail($user['email'], $subject, $msg, $headers, '-f' . $ciniki['config']['ciniki.core']['system.email']);
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
		$subject = $user['display_name'] . " forgot password";
		$msg = "Customer password request: \n\n"
			. "email: " . $user['email'] . "\n"
			. "time: " . date("D M j, Y H:i e") . "\n"
			. "" . "\n";
		mail($ciniki['config']['ciniki.customers']['password.forgot.notify'], $subject, $msg, $headers, '-f' . $ciniki['config']['ciniki.core']['system.email']);
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
