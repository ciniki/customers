<?php
//
// Description
// -----------
// This method will update a customer link.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the customer belongs to.
// customer_id:		The ID of the customer to update the link for.
// link_id:			The ID of the link to update.
// name:			(optional) The new name of the link.
// url:				(optional) The new url of the link.
// description:		(optional) The new description for the link.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_linkUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Link'), 
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'), 
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'), 
        'webflags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Webflags'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.linkUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Update the link
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.link', $args['link_id'], $args, 0x07);
}
?>
