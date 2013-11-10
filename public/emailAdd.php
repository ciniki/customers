<?php
//
// Description
// -----------
// This method will add a new email address to a customer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the customer is attached to.
// customer_id:		The ID of the customer to add the email address to.
// address:			The email address to add.
// flags:			The options for the email address.
//
//					0x01 - Customer is allowed to login via the business website.
//					       This is used by the ciniki.web module.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_emailAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
		'email'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email Address'),
		'password'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'name'=>'Password'),
		'temp_password'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'name'=>'Temporary Password'),
		'temp_password_date'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'name'=>'Temporary Password Date'),
		'flags'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Options'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.emailAdd', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// FIXME: Encrypt the password
	//

	//
	// Add the address
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.email', $args, 0x07);
}
?>
