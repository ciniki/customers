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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'725', 'msg'=>'Unable to reset password.', 'err'=>$rc['err']));
	}
	if( !isset($rc['user']) || !isset($rc['user']['email']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'726', 'msg'=>'Unable to reset password.'));
	}
	$user = $rc['user'];

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'users');
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'727', 'msg'=>'Unable to reset password.'));
	}

	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'728', 'msg'=>'Unable to reset password.'));
	}

	//
	// FIXME: Add log entry to track password changes
	//

	//
	// Email the user with the new password
	//
	if( $user['email'] != '' 
		&& isset($ciniki['config']['core']['system.email']) && $ciniki['config']['core']['system.email'] != '' ) {
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
		$headers = 'From: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
				'Reply-To: "' . $ciniki['config']['core']['system.email.name'] . '" <' . $ciniki['config']['core']['system.email'] . ">\r\n" .
				'X-Mailer: PHP/' . phpversion();
		mail($user['email'], $subject, $msg, $headers, '-f' . $ciniki['config']['core']['system.email']);
	}

	//
	// Commit the changes and return
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'729', 'msg'=>'Unable to reset password.'));
	}

	//
	// Email sysadmins to let them know of a password reset request
	//
	if( isset($ciniki['config']['customers']['password.forgot.notify']) && $ciniki['config']['customers']['password.forgot.notify'] != '' ) {
		date_default_timezone_set('America/Eastern');
		$subject = $user['display_name'] . " forgot password";
		$msg = "Customer password request: \n\n"
			. "email: " . $user['email'] . "\n"
			. "time: " . date("D M j, Y H:i e") . "\n"
			. "" . "\n";
		mail($ciniki['config']['customers']['password.forgot.notify'], $subject, $msg, $headers, '-f' . $ciniki['config']['core']['system.email']);
	}

	return array('stat'=>'ok');
}
?>
