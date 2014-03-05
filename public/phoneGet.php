<?php
//
// Description
// -----------
// This method will return the information for an phone address attached to a customer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the phone address is attached to.
// customer_id:		The ID of the customer the phone address is attached to.
// phone_id:		The ID of the phone address to be removed.
// 
// Returns
// -------
// <rsp stat="ok">
//    <phone id="7" customer_id="2" address="veggiefrog@gmail.com" flags="0" />
// </rsp>
//
function ciniki_customers_phoneGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
		'phone_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Phone'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.phoneGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	$strsql = "SELECT id, customer_id, phone_label, phone_number, flags "
		. "FROM ciniki_customer_phones "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['phone_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'phone');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'105', 'msg'=>'Unable to get phone details', 'err'=>$rc['err']));
	}
	if( !isset($rc['phone']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'721', 'msg'=>'Invalid customer'));
	}
	return array('stat'=>'ok', 'phone'=>$rc['phone']);
}
?>
