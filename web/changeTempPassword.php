<?php
//
// Description
// -----------
// This function will change the users password, providing their own one is correct.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// temppassword: 	The temporary password for the user.  
//
// newpassword: 	The new password for the user.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_customers_changeTempPassword($ciniki, $business_id, $email, $temppassword, $newpassword) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');

	if( strlen($newpassword) < 8 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'730', 'msg'=>'New password must be longer than 8 characters.'));
	}

	//
	// Check temp password
	// Must change password within 2 hours (7200 seconds)
	$strsql = "SELECT id, email "
		. "FROM ciniki_customer_emails "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
		. "AND temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $temppassword) . "') "
		. "AND (UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(temp_password_date)) < 7200 "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['user']) || !is_array($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'731', 'msg'=>'Unable to update password.'));
	}
	$user = $rc['user'];

	//
	// Perform an extra check to make sure only 1 row was found, other return error
	//
	if( $rc['num_rows'] != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'732', 'msg'=>'Invalid temporary password'));
	}

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
	// Update the password, but only if the temporary one matches
	//
	$strsql = "UPDATE ciniki_customer_emails SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $newpassword) . "'), "
		. "temp_password = '', "
		. "last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
		. "AND temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $temppassword) . "') ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate(&$ciniki, $strsql, 'users');
	if( $rc['stat'] != 'ok' ) {
	error_log($strsql);
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'733', 'msg'=>'Unable to update password.'));
	}

	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'734', 'msg'=>'Unable to change password.'));
	}

	//
	// Commit all the changes to the database
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'users');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'735', 'msg'=>'Unable to update password.'));
	}

	return array('stat'=>'ok');
}
?>
