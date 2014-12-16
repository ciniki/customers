<?php
//
// Description
// -----------
// This method will return the customers module settings for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the settings for.
// 
// Returns
// -------
//
function ciniki_customers_getSettings($ciniki) {
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.getSettings', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];
	
	//
	// Grab the settings for the business from the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'business_id', $args['business_id'], 'ciniki.customers', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp = $rc;	

	//
	// Grab the list of price points
	//
	if( ($modules['ciniki.customers']['flags']&0x1000) > 0 ) {
		$strsql = "SELECT id, name, code, sequence, flags "
			. "FROM ciniki_customer_pricepoints "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY sequence, id "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'pricepoints', 'fname'=>'id', 'name'=>'pricepoint',
				'fields'=>array('id', 'name', 'code', 'sequence', 'flags')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['pricepoints']) ) {
			$rsp['pricepoints'] = $rc['pricepoints'];
		}
	}

	//
	// Get the membership seasons
	//
	if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
		$strsql = "SELECT ciniki_customer_seasons.id, "
			. "ciniki_customer_seasons.name, "
			. "ciniki_customer_seasons.flags "
			. "FROM  ciniki_customer_seasons "
			. "WHERE ciniki_customer_seasons.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_customer_seasons.start_date DESC "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'seasons', 'fname'=>'id', 'name'=>'season',
				'fields'=>array('id', 'name', 'flags')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['seasons']) ) {	
			$rsp['seasons'] = $rc['seasons'];
		}
	}

	//
	// Return the response, including colour arrays and todays date
	//
	return $rsp;
}
?>
