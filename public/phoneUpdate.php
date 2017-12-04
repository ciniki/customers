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
// tnid:     The ID of the tenant the phone number is attached to.
// customer_id:     The ID of the customer the phone number is attached to.
// phone_id:        The ID of the phone to change.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.phoneUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the address
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.phone', 
        $args['phone_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the short_description
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
    $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $args['tnid'], $args['customer_id'], 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$args['customer_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.members', 'object_id'=>$args['customer_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.dealers', 'object_id'=>$args['customer_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.distributors', 'object_id'=>$args['customer_id']));

    return array('stat'=>'ok');
}
?>
