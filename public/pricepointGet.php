<?php
//
// Description
// ===========
// This method will return the details for a price point
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_pricepointGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'pricepoint_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Price Point'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.taxes.pricepointGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the details about a tax
	//
	$strsql = "SELECT id, name, code, sequence, flags "
		. "FROM ciniki_customer_pricepoints "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['pricepoint_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'pricepoints', 'fname'=>'id', 'name'=>'pricepoint',
			'fields'=>array('id', 'name', 'code', 'sequence', 'flags')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['pricepoints']) || !isset($rc['pricepoints'][0]['pricepoint']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1832', 'msg'=>'Unable to find the price point'));
	}
	$pricepoint = $rc['pricepoints'][0]['pricepoint'];

	return array('stat'=>'ok', 'pricepoint'=>$pricepoint);
}
?>
