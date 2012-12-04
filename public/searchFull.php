<?php
//
// Description
// -----------
// The search return a list of results, with the most probable at the top.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to search for the customers.
// start_needle:		The search string to use.
// limit:				(optional) The maximum number of results to return.  If not
//						specified, all results will be returned.
// 
// Returns
// -------
//
function ciniki_customers_searchFull($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No search specified'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No limit specified'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.searchFull', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the types of customers available for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
    $rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']); 
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$types = $rc['types'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	$strsql = "SELECT DISTINCT ciniki_customers.id, CONCAT_WS(' ', first, last) AS name, first, last, "
		. "status, type, company, cid, ";
	if( count($types) > 0 ) {
		$strsql .= "CASE type ";
		foreach($types as $tid => $type) {
			$strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN '" . ciniki_core_dbQuote($ciniki, $type['detail_value']) . "' ";
		}
		$strsql .= "ELSE 'person' END AS display_type ";
	} else {
		$strsql .= "'person' AS display_type ";
	}
	
//	$strsql = "SELECT id, status, prefix, first, middle, last, suffix, "
//		. "company, department, title "
	$strsql .= "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id) "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND (first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR middle LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR cid LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR company LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR company LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR CONCAT_WS(' ', first, last) LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR CONCAT_WS(' ', first, last) LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "ORDER BY last, first ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'customers', 'customer', array('stat'=>'ok', 'customers'=>array()));

}
?>
