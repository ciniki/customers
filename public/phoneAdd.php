<?php
//
// Description
// -----------
// This method will add a new phone address to a customer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the customer is attached to.
// customer_id:		The ID of the customer to add the phone address to.
// flags:			The options for the phone address.
//
//					0x01 - 
//					0x01 - 
//					0x01 - 
//					0x08 - public, visible on member site
//
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_phoneAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
		'phone_label'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Label'),
		'phone_number'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'name'=>'Number'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.phoneAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Add the address
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.phone', $args, 0x07);
}
?>
