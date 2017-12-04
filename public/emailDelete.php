<?php
//
// Description
// -----------
// This method will remove a customer email address.

// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the customer is attached to.
// customer_id:         The ID of the customer the email address is attached to.
// email_id:            The ID of the email address to be removed.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'email_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.emailDelete', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the uuid
    //
    $strsql = "SELECT uuid FROM ciniki_customer_emails "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['email_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.73', 'msg'=>'Unable to get existing email information', 'err'=>$rc['err']));
    }
    if( !isset($rc['email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.74', 'msg'=>'Unable to get existing email information'));
    }
    $uuid = $rc['email']['uuid'];

    //
    // Delete the email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.email', $args['email_id'], $uuid, 0x07);
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
