<?php
//
// Description
// -----------
// Search customers by name
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to search for the customers.
// start_needle:		The search string to use.
// limit:				(optional) The maximum number of results to return.  If not
//						specified, the maximum results will be 25.
// 
// Returns
// -------
//
function ciniki_customers_searchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
        'member_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Members'), 
        'dealer_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Dealers'), 
        'distributor_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Distributors'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.searchQuick', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Load maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
	$rc = ciniki_customers_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];
	//
	// Get the types of customers available for this business
	//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
 //   $rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']); 
//	if( $rc['stat'] != 'ok' ) {	
//		return $rc;
//	}
//	$types = $rc['types'];

	//
	// Get the number of customers in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT DISTINCT ciniki_customers.id, display_name, "
		. "status, status AS status_text, "
		. "type, company, eid ";
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
//		$strsql .= "ELSE CONCAT_WS(' ', first, last) END AS name ";
//	} else {
//		// Default to a person
//		$strsql .= "CONCAT_WS(' ', first, last) AS name ";
//	}
	$strsql .= "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id) "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.status < 50 ";
	// Check if only a sales rep
	if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 ) {
		$strsql .= "AND ciniki_customers.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
	}
	if( isset($args['member_status']) && $args['member_status']	!= '' ) {
		$strsql .= "AND member_status = '" . ciniki_core_dbQuote($ciniki, $args['member_status']) . "' ";
	}
	if( isset($args['dealer_status']) && $args['dealer_status']	!= '' ) {
		$strsql .= "AND dealer_status = '" . ciniki_core_dbQuote($ciniki, $args['dealer_status']) . "' ";
	}
	if( isset($args['distributor_status']) && $args['distributor_status']	!= '' ) {
		$strsql .= "AND distributor_status = '" . ciniki_core_dbQuote($ciniki, $args['distributor_status']) . "' ";
	}
	$strsql .= "AND (first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR first LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR last LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR eid LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR company LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR company LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//			. "OR CONCAT_WS(' ', first, last) LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//			. "OR CONCAT_WS(' ', first, last) LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "ORDER BY last, first DESC ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	} else {
		$strsql .= "LIMIT 25 ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'eid', 'display_name', 'status', 'status_text',
				'type', 'company'),
			'maps'=>array('status_text'=>$maps['customer']['status'])),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	return $rc;
}
?>
