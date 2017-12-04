<?php
//
// Description
// -----------
//
// Info
// ----
// Status:          defined
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_addressDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'address_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Address'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.addressDelete', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the uuid
    //
    $strsql = "SELECT uuid FROM ciniki_customer_addresses "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['address_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.53', 'msg'=>'Unable to get existing email information', 'err'=>$rc['err']));
    }
    if( !isset($rc['email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.54', 'msg'=>'Unable to get existing email information'));
    }
    $uuid = $rc['email']['uuid'];

    //
    // Delete the address
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.address', $args['address_id'], $uuid, 0x07);
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
