<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the customer image is attached to.
// customer_image_id:		The ID of the customer image to get.
//
// Returns
// -------
//
function ciniki_customers_imageGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Contact Image'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.imageGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the main information
	//
	$strsql = "SELECT ciniki_customer_images.id, "
		. "ciniki_customer_images.name, "
		. "ciniki_customer_images.permalink, "
		. "ciniki_customer_images.webflags, "
		. "ciniki_customer_images.image_id, "
		. "ciniki_customer_images.description "
		. "FROM ciniki_customer_images "
		. "WHERE ciniki_customer_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customer_images.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_image_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'images', 'fname'=>'id', 'name'=>'image',
			'fields'=>array('id', 'name', 'permalink', 'webflags', 'image_id', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['images']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'937', 'msg'=>'Unable to find image'));
	}
	$image = $rc['images'][0]['image'];
	
	return array('stat'=>'ok', 'image'=>$image);
}
?>
