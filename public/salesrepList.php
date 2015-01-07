<?php
//
// Description
// -----------
// This method returns the list of sales reps and the number of customers assigned to them.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_salesrepList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'salesrep_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sales Rep'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.salesrepList', 0);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	$rsp = array('stat'=>'ok', 'salesreps'=>array());

	//
	// Get the list of sales reps and the number of customers they have
	//
	$strsql = "SELECT ciniki_users.id, "
		. "ciniki_users.firstname, "
		. "ciniki_users.lastname, "
		. "ciniki_users.display_name, "
		. "ciniki_users.email, "
		. "COUNT(ciniki_customers.salesrep_id) AS num_customers "
		. "FROM ciniki_business_users "
		. "LEFT JOIN ciniki_users ON ("
			. "ciniki_business_users.user_id = ciniki_users.id "
			. ") " 
		. "LEFT JOIN ciniki_customers ON ("
			. "ciniki_business_users.user_id = ciniki_customers.salesrep_id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") " 
		. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_business_users.permission_group = 'salesreps' "
		. "GROUP BY ciniki_business_users.user_id "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'salesreps', 'fname'=>'id', 'name'=>'salesrep',
			'fields'=>array('id', 'firstname', 'lastname', 'display_name', 'email', 'num_customers')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['salesreps']) ) {
		$rsp['salesreps'] = $rc['salesreps'];
	} 

	$salesrep_id = 0;
	if( isset($args['salesrep_id']) && $args['salesrep_id'] > 0 ) {
		$salesrep_id = $args['salesrep_id'];
	} else {
		if( count($rsp['salesreps']) > 0 ) {
			$salesrep_id = $rsp['salesreps'][0]['salesrep']['id'];
		}
	}

	if( $salesrep_id != '' && $salesrep_id > 0 ) {
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.display_name "
			. "FROM ciniki_customers "
			. "WHERE ciniki_customers.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $salesrep_id) . "' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_customers.sort_name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
				'fields'=>array('id', 'display_name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['customers']) ) {
			$rsp['customers'] = $rc['customers'];
		} 
	}

	return $rsp;
}
?>