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
function ciniki_customers_wng_changeTempPassword(&$ciniki, $tnid, &$request, $email, $temppassword, $newpassword) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'logAdd');

    if( strlen($newpassword) < 8 ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Reset Password', 0, $email, 'ciniki.customers.465', 'New password must be 8 characters long');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.466', 'msg'=>'New password must be longer than 8 characters.'));
    }
    
    //
    // Check temp password
    // Must change password within 2 hours (7200 seconds)
    //
    $strsql = "SELECT id, email, flags "
        . "FROM ciniki_customer_emails "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "AND temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $temppassword) . "') "
        . "AND (UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(temp_password_date)) < 7200 "
        . "AND (flags&0x01) = 0x01 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'user');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Reset Password', 0, $email, $rc['err']['code'], 'Error resetting password');
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Reset Password', 0, $email, 'ciniki.customers.467', 'Temp password incorrect');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.468', 'msg'=>'Unable to update password.'));
    }
    $users = $rc['rows'];

    //
    // FIXME: Put check for active user status < 40
    //

    //
    // Perform an extra check to make sure only 1 row was found, other return error
    //
//  if( $rc['num_rows'] != 1 ) {
//      return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.469', 'msg'=>'Invalid temporary password'));
//  }

    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Reset Password', 0, $email, $rc['err']['code'], 'Internal error');
        return $rc;
    }

    foreach($users as $user) {
        //
        // Update the password, but only if the temporary one matches
        //
        $strsql = "UPDATE ciniki_customer_emails "
            . "SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $newpassword) . "'), "
            . "flags = (flags&~0x80), "
            . "failed_logins = 0, "
            . "date_locked = '', "
            . "temp_password = '', "
            . "last_updated = UTC_TIMESTAMP() "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
            . "AND temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $temppassword) . "') "
            . "AND (flags&0x01) = 0x01 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Reset Password', 0, $email, 'ciniki.customers.470', 'Internal error');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.471', 'msg'=>'Unable to update password.'));
        }

        if( $rc['num_affected_rows'] < 1 ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Reset Password', 0, $email, 'ciniki.customers.472', 'Unable to change password');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.473', 'msg'=>'Unable to change password.'));
        }
    }

    //
    // Commit all the changes to the database
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $settings, 50, 'Reset Password', 0, $email, 'ciniki.customers.474', 'Unable to commit changes');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.475', 'msg'=>'Unable to update password.'));
    }

    ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 10, 'Reset Password', 0, $email, '', 'Success');

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'customers');

    return array('stat'=>'ok');
}
?>
