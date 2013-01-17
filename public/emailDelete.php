<?php
//
// Description
// -----------
// This method will remove a customer email address.

// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the customer is attached to.
// customer_id:			The ID of the customer the email address is attached to.
// email_id:			The ID of the email address to be removed.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_emailDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'email_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.emailDelete', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// get the uuid
	//
	$strsql = "SELECT uuid, customer_id FROM ciniki_customer_emails "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['email_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'503', 'msg'=>'Unable to get existing email information', 'err'=>$rc['err']));
	}
	if( !isset($rc['email']) || !isset($rc['email']['customer_id'])) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'504', 'msg'=>'Unable to get existing email information'));
	}
	$org_customer_id = $rc['email']['customer_id'];
	$uuid = $rc['email']['uuid'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Remove the customer email address from the database.  It is still there in 
	// the ciniki_customer_history table.
	//
	$strsql = "DELETE FROM ciniki_customer_emails "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['email_id']) . "' ";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
		3, 'ciniki_customer_emails', $args['email_id'], '*', '');

	//
	// Update the customer last_updated date
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTouch');
	$rc = ciniki_core_dbTouch($ciniki, 'ciniki.customers', 'ciniki_customers', 'id', $args['customer_id']);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'605', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'customers');

	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.email.push', 'args'=>array('delete_uuid'=>$uuid, 'delete_id'=>$args['email_id']));

	return array('stat'=>'ok');
}
?>
