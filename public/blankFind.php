<?php
//
// Description
// -----------
// This method will return a list of customers for the business which have blank first or last name.
// 
// Returns
// -------
//
function ciniki_customers_blankFind($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
	$ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.blankFind', 0);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Search for any potential blank customers
	//
	$strsql = "SELECT id, first, middle, last, company "
		. "FROM ciniki_customers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND (first = '' OR last = '') "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'first', 'middle', 'last', 'company'),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'768', 'msg'=>'Unable to locate any blank customers', 'err'=>$rc['err']));
	}

	return $rc;
}
?>
