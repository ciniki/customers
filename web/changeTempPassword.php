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
function ciniki_customers_web_changeTempPassword($ciniki, $business_id, $email, $temppassword, $newpassword) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	if( strlen($newpassword) < 8 ) {
		error_log("WEB: changeTempPassword $email fail (730)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'730', 'msg'=>'New password must be longer than 8 characters.'));
	}
	
	error_log("WEB: changeTempPassword $email");

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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'user');
	if( $rc['stat'] != 'ok' ) {
		error_log("WEB: changeTempPassword $email fail (" . $rc['err']['code'] . ")");
		return $rc;
	}
	if( !isset($rc['user']) || !is_array($rc['user']) ) {
		error_log("WEB: changeTempPassword $email fail (731)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'731', 'msg'=>'Unable to update password.'));
	}
	$user = $rc['user'];

	//
	// FIXME: Put check for active user status < 40
	//

	//
	// Perform an extra check to make sure only 1 row was found, other return error
	//
	if( $rc['num_rows'] != 1 ) {
		error_log("WEB: changeTempPassword $email fail (732)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'732', 'msg'=>'Invalid temporary password'));
	}

	//
	// Turn off autocommit
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		error_log("WEB: changeTempPassword $email fail (" . $rc['err']['code'] . ")");
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		error_log("WEB: changeTempPassword $email fail (733)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'733', 'msg'=>'Unable to update password.'));
	}

	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		error_log("WEB: changeTempPassword $email fail (734)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'734', 'msg'=>'Unable to change password.'));
	}

	//
	// Commit all the changes to the database
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		error_log("WEB: changeTempPassword $email fail (735)");
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'735', 'msg'=>'Unable to update password.'));
	}

	error_log("WEB: changeTempPassword $email success");

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, 'ciniki', 'customers');

	return array('stat'=>'ok');
}
?>
