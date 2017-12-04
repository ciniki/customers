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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'phones'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Phones'),
        'emails'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Emails'),
        'addresses'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Addresses'),
        'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Subscriptions'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerDetails', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
    return ciniki_customers__customerDetails($ciniki, $args['tnid'], $args['customer_id'], $args);
}
?>
