<?php
//
// Description
// -----------
// This function will return a list of customers for a business.
//
// Arguments
// ---------
// ciniki:
// business_id:			The business ID to check the session user against.
// method:				The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_hooks_customerList($ciniki, $business_id, $args) {

	$strsql = "SELECT id, display_name "
		. "FROM ciniki_customers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	if( isset($args['type']) && $args['type'] != '' ) {
		$strsql .= "AND type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
	}
	if( isset($args['parent_id']) && $args['parent_id'] != '' ) {
		$strsql .= "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' ";
	}

	$strsql .= "ORDER BY display_name ";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'display_name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$customers = array();
	if( isset($rc['customers']) ) {
		$customers = $rc['customers'];
	}

	return array('stat'=>'ok', 'customers'=>$customers);
}
?>
