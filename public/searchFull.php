<?php
//
// Description
// -----------
// The search return a list of results, with the most probable at the top.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// search_str:		The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_customers_searchFull($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'search_str'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No search specified'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No limit specified'), 
        'finished'=>array('required'=>'no', 'default'=>'yes', 'blank'=>'yes', 'errmsg'=>'No finished flag specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.searchFull', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/dateFormat.php');
	$date_format = ciniki_users_dateFormat($ciniki);
	
	$strsql = "SELECT id, status, prefix, first, middle, last, suffix, "
		. "company, department, title "
		. "FROM customers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ( first LIKE '%" . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
			. "OR middle LIKE '%" . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
			. "OR last LIKE '%" . ciniki_core_dbQuote($ciniki, $args['search_str']) . "%' "
			. ") "
		. "ORDER BY last, first ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'customers', 'customers', 'customer', array('stat'=>'ok', 'customers'=>array()));

}
?>
