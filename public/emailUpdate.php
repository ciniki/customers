<?php
//
// Description
// -----------
// This method will update the details for a customer email address.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:  	The ID of the business the email address is attached to.
// customer_id:		The ID of the customer the email address is attached to.
// email_id:		The ID of the email to change.
// address:			(optional) The new email address for the customer.
// flags:			(optional) The options for the email address.
//
//					0x01 - Customer is allowed to login via the business website.
//					       This is used by the ciniki.web module.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_emailUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'email_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email ID'), 
        'address'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Email Address'), 
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
	if( isset($args['address']) ) {
		$args['email'] = $args['address'];
	}
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.emailUpdate', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Check the email ID belongs to the requested customer
	//
	$strsql = "SELECT id, customer_id "
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'743', 'msg'=>'Access denied'));
	}

	//
	// Update the address
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.email', $args['email_id'], $args, 0x07);
}
?>
