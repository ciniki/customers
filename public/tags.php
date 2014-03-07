<?php
//
// Description
// ===========
// This method will return the existing categories and tags for customers categories and tags.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The ID of the business to get the item from.
// 
// Returns
// -------
//
function ciniki_customers_tags($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.tags', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Get the list of member categories
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
	$rc = ciniki_core_tagsList($ciniki, 'ciniki.customers', $args['business_id'], 'ciniki_customer_tags', 40);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['tags']) ) {
		$member_categories = $rc['tags'];
	} else {
		$member_categories = array();
	}

	return array('stat'=>'ok', 'member_categories'=>$member_categories);
}
?>
