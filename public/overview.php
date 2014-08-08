<?php
//
// Description
// -----------
// Return the list of customer who have been recently updated
//
// Arguments
// ---------
// user_id: 		The user making the request
// search_str:		The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_customers_overview($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.overview', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	$rsp = array('stat'=>'ok', 'recent'=>array());

	//
	// Get the places and customer counts
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'locationStats');
	$rc = ciniki_customers__locationStats($ciniki, $args['business_id'], array('start_level'=>'country'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['places']) ) {
		$rsp['places'] = $rc['places'];
		$rsp['place_level'] = $rc['place_level'];
	}

	//
	// Get the recently updated customers
	//
	$strsql = "SELECT id, display_name, status, type, company, eid "
		. "FROM ciniki_customers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = 10 "
		. "ORDER BY last_updated DESC, last, first DESC ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	} else {
		$strsql .= "LIMIT 25 ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'display_name', 'status', 'type', 'company', 'eid')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) { 
		$rsp['recent'] = $rc['customers'];
	}

	return $rsp;
}
?>
