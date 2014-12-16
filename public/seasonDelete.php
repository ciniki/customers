<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_seasonDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'season_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Season'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.seasonDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

	//
	// Check if any customers are still attached to this season
	//
	$strsql = "SELECT 'customers', COUNT(*) "
		. "FROM ciniki_customer_season_members "
		. "WHERE season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['num']['customers']) && $rc['num']['customers'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1833', 'msg'=>'Customers are still using this season, it cannot be deleted.'));
	}

	//
	// Delete the season
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.customers.season', 
		$args['season_id'], NULL, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	return array('stat'=>'ok');
}
?>
