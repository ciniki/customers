<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get members for.
// type:			The type of participants to get.  Refer to participantAdd for 
//					more information on types.
//
// Returns
// -------
//
function ciniki_customers_memberCategories($ciniki) {
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
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.memberCategories', 0);
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

	//
	// Build the query to get the tags
	//
	$strsql = "SELECT IFNULL(ciniki_customer_tags.tag_name, 'Uncategorized') AS tag_name, "
		. "IFNULL(ciniki_customer_tags.permalink, '') AS permalink, "
		. "COUNT(ciniki_customers.id) AS num_members "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_tags ON ("
			. "ciniki_customers.id = ciniki_customer_tags.customer_id "
			. "AND ciniki_customer_tags.tag_type = '40' "
			. "AND ciniki_customer_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.status = 10 "
		. "AND ciniki_customers.member_status = 10 "
		. "GROUP BY tag_name "
		. "ORDER BY tag_name "
		. "";
	//
	// Get the list of posts, sorted by publish_date for use in the web CI List Categories
	//
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'categories', 'fname'=>'permalink', 'name'=>'category',
			'fields'=>array('name'=>'tag_name', 'permalink', 'num_members')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( isset($rc['categories']) ) {
		return array('stat'=>'ok', 'categories'=>$rc['categories']);
	}

	return array('stat'=>'ok', 'categories'=>array());
}
?>
