<?php
//
// Description
// -----------
// This method will search the addresses for all customers in a business
// looking for matches based on city.  The search will return city, provice
// and country so the UI can be automatically filled in.
//
// Arguments
// ---------
// business_id: 	The business to search the addresses of.
// start_needle:	The search string provided by the user.  This is typically 
//					the first few letters of what they typed into the UI.
// limit:			(optional) The limit to the number of results, if none supplied, 
//					the limit is set at 25.
// 
// Returns
// -------
// <cities>
//	<city name="Mississauga" province="ON" country="Canada" />
// </cities>
//
function ciniki_customers_addressSearchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No search specified'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No limit specified'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.addressSearchQuick', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the number of customers in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT DISTINCT city AS name, province, country "
		. "FROM ciniki_customer_addresses "
		. "WHERE city like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
		. "ORDER BY city, province, country ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	} else {
		$strsql .= "LIMIT 25 ";
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'cities', 'city', array('stat'=>'ok', 'cities'=>array()));
}
?>
