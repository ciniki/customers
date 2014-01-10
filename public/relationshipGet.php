<?php
//
// Description
// -----------
// This method will return the details about a customer relationship with another customer.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the relationship from.
// relationship_id:		The ID of the relationship to get.
// 
// Returns
// -------
//
function ciniki_customers_relationshipGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'),
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.relationshipGet', $args['relationship_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the types of customers available for this business
	//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
//  $rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']); 
//	if( $rc['stat'] != 'ok' ) {	
//		return $rc;
//	}
//	$types = $rc['types'];

	//
	// Get the relationship types
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getRelationshipTypes');
    $rc = ciniki_customers_getRelationshipTypes($ciniki, $args['business_id']); 
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$relationship_types = $rc['types'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

	//
	// Build the query to get the details about a relationship, including the related customer id and name.
	//
	$strsql = "SELECT ciniki_customer_relationships.id, ciniki_customer_relationships.customer_id, "
		. "relationship_type, related_id, "
		. "IFNULL(DATE_FORMAT(date_started, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_started, "
		. "IFNULL(DATE_FORMAT(date_ended, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_ended, "
		. "ciniki_customer_relationships.notes, "
		. "ciniki_customers.name AS customer_name, ciniki_customers.company ";
//	if( count($types) > 0 ) {
//		// If there are customer types defined, choose the right name for the customer
//		// This is required here to be able to sort properly
//		$strsql .= "CASE ciniki_customers.type ";
//		foreach($types as $tid => $type) {
//			$strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN ";
//			if( $type['detail_value'] == 'business' ) {
//				$strsql .= " ciniki_customers.company ";
//			} else {
//				$strsql .= "CONCAT_WS(' ', first, last) ";
//			}
//		}
//		$strsql .= "ELSE CONCAT_WS(' ', first, last) END AS customer_name ";
//	} else {
//		// Default to a person
//		$strsql .= "CONCAT_WS(' ', first, last) AS customer_name ";
//	}
	$strsql .= "FROM ciniki_customer_relationships "
		. "LEFT JOIN ciniki_customers ON ("
			. "(ciniki_customer_relationships.customer_id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_customer_relationships.customer_id = ciniki_customers.id "
			. ") OR ("
			. "ciniki_customer_relationships.related_id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_customer_relationships.related_id = ciniki_customers.id "
			. ")) "
		. "WHERE ciniki_customer_relationships.id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
		. "AND ciniki_customer_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND (ciniki_customer_relationships.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "OR ciniki_customer_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. ") "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'595', 'msg'=>'Unable to find relationship', 'err'=>$rc['err']));
	}
	if( !isset($rc['relationship']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'577', 'msg'=>'Relationship does not exist'));
	}
	$relationship = $rc['relationship'];

	//
	// Check to see if the relationship needs to be reversed
	//
	if( $relationship['related_id'] == $args['customer_id'] ) {
		if( isset($relationship_types[-$relationship['relationship_type']]) ) {
			$relationship['type_name'] = $relationship_types[-$relationship['relationship_type']];
			$relationship['relationship_type'] = -$relationship['relationship_type'];
		}
		$relationship['related_id'] = $relationship['customer_id'];
		$relationship['customer_id'] = $args['customer_id'];
	}

	return array('stat'=>'ok', 'relationship'=>$relationship);
}
?>
