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
// temppassword:    The temporary password for the user.  
//
// newpassword:     The new password for the user.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_customers_web_changePassword($ciniki, $business_id, $oldpassword, $newpassword) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    if( strlen($newpassword) < 8 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.186', 'msg'=>'New password must be longer than 8 characters.'));
    }

    if( !isset($ciniki['session']['customer']['email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.187', 'msg'=>'You must be signed in to change your password.'));
    }

    //
    // Check temp password
    // Must change password within 2 hours (7200 seconds)
    $strsql = "SELECT id, email "
        . "FROM ciniki_customer_emails "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND email = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['email']) . "' "
        . "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $oldpassword) . "') "
        . "AND (flags&0x01) = 0x01 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'user');
    if( $rc['stat'] != 'ok' ) {
        error_log("WEB [" . $ciniki['business']['details']['name'] . "]: changePassword " . $ciniki['session']['customer']['email'] . " fail (" . $rc['err']['code'] . ")");
        return $rc;
    }
//  if( !isset($rc['user']) || !is_array($rc['user']) ) {
    if( !isset($rc['rows']) || count($rc['rows']) < 1 ) {   
        error_log("WEB [" . $ciniki['business']['details']['name'] . "]: changePassword " . $ciniki['session']['customer']['email'] . " fail (751)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.188', 'msg'=>'Unable to update password.'));
    }
//  }
//  $user = $rc['user'];
    $users = $rc['rows'];

    //
    // FIXME: Put a check for customer status < 40 (active customer)
    //

    //
    // Perform an extra check to make sure only 1 row was found, other return error
    //
    if( $rc['num_rows'] < 1 ) {
        error_log("WEB [" . $ciniki['business']['details']['name'] . "]: changePassword " . $ciniki['session']['customer']['email'] . " fail (752)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.189', 'msg'=>'Invalid temporary password'));
    }

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
    // Update the password, but only if the temporary one matches
    //
    $strsql = "UPDATE ciniki_customer_emails "
        . "SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $newpassword) . "'), "
        . "temp_password = '', "
        . "last_updated = UTC_TIMESTAMP() "
//      . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND email = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['email']) . "' "
        . "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $oldpassword) . "') "
        . "AND (flags&0x01) = 0x01 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        error_log("WEB [" . $ciniki['business']['details']['name'] . "]: changePassword " . $ciniki['session']['customer']['email'] . " fail (753)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.190', 'msg'=>'Unable to update password.'));
    }

    if( $rc['num_affected_rows'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        error_log("WEB [" . $ciniki['business']['details']['name'] . "]: changePassword " . $ciniki['session']['customer']['email'] . " fail (754)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.191', 'msg'=>'Unable to change password.'));
    }

    //
    // Commit all the changes to the database
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        error_log("WEB [" . $ciniki['business']['details']['name'] . "]: changePassword " . $ciniki['session']['customer']['email'] . " fail (755)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.192', 'msg'=>'Unable to update password.'));
    }

    error_log("WEB [" . $ciniki['business']['details']['name'] . "]: changePassword " . $ciniki['session']['customer']['email'] . " success");

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, 'ciniki', 'customers');

    return array('stat'=>'ok');
}
?>
