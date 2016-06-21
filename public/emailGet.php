<?php
//
// Description
// -----------
// This method will return the information for an email address attached to a customer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the email address is attached to.
// customer_id:     The ID of the customer the email address is attached to.
// email_id:        The ID of the email address to be removed.
// 
// Returns
// -------
// <rsp stat="ok">
//    <email id="7" customer_id="2" address="veggiefrog@gmail.com" flags="0" />
// </rsp>
//
function ciniki_customers_emailGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'email_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Email'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.emailGet', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    $strsql = "SELECT id, customer_id, email AS address, flags "
        . "FROM ciniki_customer_emails "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['email_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'105', 'msg'=>'Unable to get email details', 'err'=>$rc['err']));
    }
    if( !isset($rc['email']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'721', 'msg'=>'Invalid customer'));
    }
    return array('stat'=>'ok', 'email'=>$rc['email']);
}
?>
