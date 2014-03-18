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
// business_id:  	The ID of the business the email address is attached to.
// customer_id:		The ID of the customer the email address is attached to.
// newpassword:		The new password to set for the customer
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'email_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'newpassword'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'New Password'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.customerSetPassword', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the emails attached to the customer
	//
	$strsql = "SELECT id, customer_id, email, temp_password "
		. "FROM ciniki_customer_emails "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['email_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( !isset($rc['email']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1622', 'msg'=>'Customer email does not exist, unable to set the password.'));
	}
	$email = $rc['email'];

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
	// Set the password
	//
	$strsql = "UPDATE ciniki_customer_emails "
		. "SET password = SHA1('" . ciniki_core_dbQuote($ciniki, $args['newpassword']) . "'), "
		. "temp_password = '', "
		. "last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $email['id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1620', 'msg'=>'Unable to update password.'));
	}
	
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
		$args['business_id'], 2, 'ciniki_customer_emails', $args['email_id'], 'password', '');
	if( $email['temp_password'] != '' ) {
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$args['business_id'], 2, 'ciniki_customer_emails', $args['email_id'], 'temp_password', '');
	}

	//
	// Commit all the changes to the database
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1623', 'msg'=>'Unable to update password.'));
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'customers');

	return array('stat'=>'ok');
}
?>
