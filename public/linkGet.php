<?php
//
// Description
// -----------
// This function will return a customer record
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_linkGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
		'link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Address'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.linkGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	$strsql = "SELECT id, customer_id, name, url, webflags, description "
		. "FROM ciniki_customer_links "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['link_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'link');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['link']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1640', 'msg'=>'Invalid link'));
	}
	return array('stat'=>'ok', 'link'=>$rc['link']);
}
?>
