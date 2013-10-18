<?php
//
// Description
// -----------
// This function will return a customer record, along with a list of details for display
// in a simplegrid in the UI.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_customerDetails($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
		'emails'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'yes', 'name'=>'Emails'),
		'addresses'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'yes', 'name'=>'Addresses'),
		'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'yes', 'name'=>'Subscriptions'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.customerDetails', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
	return ciniki_customers__customerDetails($ciniki, $args['business_id'], $args['customer_id'], $args);
}
?>
