<?php
//
// Description
// -----------
// This method will update the details for a customer phone address.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:  	The ID of the business the phone number is attached to.
// customer_id:		The ID of the customer the phone number is attached to.
// phone_id:		The ID of the phone to change.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_phoneUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'phone_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Phone'), 
        'phone_label'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Label'), 
        'phone_number'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number'), 
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.phoneUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Update the address
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.phone', 
		$args['phone_id'], $args, 0x07);
}
?>
