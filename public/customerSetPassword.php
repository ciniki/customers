<?php
//
// Description
// -----------
// This method will update the password for the customer email to login to the website.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the email address is attached to.
// customer_id:     The ID of the customer the email address is attached to.
// newpassword:     The new password to set for the customer
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_customerSetPassword(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'email_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email'), 
        'newpassword'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'New Password'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerSetPassword', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the emails attached to the customer
    //
    $strsql = "SELECT id, customer_id, email, temp_password "
        . "FROM ciniki_customer_emails "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
//        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['email_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.63', 'msg'=>'Customer email does not exist, unable to set the password.'));
    }
    $emails = $rc['rows'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Set the password on all emails attached to the account, that way
    // they can use whichever email they want to login
    //
    foreach($emails as $email) {
        $strsql = "UPDATE ciniki_customer_emails "
            . "SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['newpassword']) . "'), "
            . "temp_password = '', "
            . "last_updated = UTC_TIMESTAMP() "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $email['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.64', 'msg'=>'Unable to update password.'));
        }
    
        $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
            $args['tnid'], 2, 'ciniki_customer_emails', $email['id'], 'password', '');
        if( $email['temp_password'] != '' ) {
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
                $args['tnid'], 2, 'ciniki_customer_emails', $email['id'], 'temp_password', '');
        }
    }

    //
    // Commit all the changes to the database
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.65', 'msg'=>'Unable to update password.'));
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'customers');

    return array('stat'=>'ok');
}
?>
