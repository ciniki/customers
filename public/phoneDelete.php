<?php
//
// Description
// -----------
// This method will remove a customer phone address.

// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the customer is attached to.
// customer_id:         The ID of the customer the phone address is attached to.
// phone_id:            The ID of the phone address to be removed.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_phoneDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'phone_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Phone'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.phoneDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Delete the phone
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.phone', $args['phone_id'], NULL, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$args['customer_id']));
  
    return array('stat'=>'ok');
}
?>
