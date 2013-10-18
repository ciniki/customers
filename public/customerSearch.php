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
function ciniki_customers_customerSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('first', 'last', 'company'), 'name'=>'Field'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.customerSearch', 0); 
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

	//
	// Get the number of customers in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT DISTINCT ciniki_customers.id, status, type, company, cid, ";
	if( count($types) > 0 ) {
		// If there are customer types defined, choose the right name for the customer
		// This is required here to be able to sort properly
		$strsql .= "CASE ciniki_customers.type ";
		foreach($types as $tid => $type) {
			$strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN ";
			if( $type['detail_value'] == 'business' ) {
				$strsql .= " ciniki_customers.company ";
			} else {
				$strsql .= "CONCAT_WS(' ', prefix, first, middle, last, suffix) ";
			}
		}
		$strsql .= "ELSE CONCAT_WS(' ', prefix, first, middle, last, suffix) END AS name ";
	} else {
		// Default to a person
		$strsql .= "CONCAT_WS(' ', prefix, first, middle, last, suffix) AS name ";
	}
	$strsql .= "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id) "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.status = 1 "
		. "AND (" . $args['field'] . " LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR " . $args['field'] . " LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "ORDER BY last, first DESC ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	} else {
		$strsql .= "LIMIT 25 ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'customers', 'customer', array('stat'=>'ok', 'customers'=>array()));
}
?>
