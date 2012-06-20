<?php
//
// Description
// -----------
// This method will return a list of potential duplicates
// 
// Returns
// -------
//
function ciniki_customers_duplicatesList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
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
	require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
	$ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.duplicatesList', 0);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Search for any potential duplicate customers
	//
	$strsql = "select CONCAT_WS(' ', first, last) AS name, COUNT(*) "
		. "FROM ciniki_customers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "GROUP BY name HAVING COUNT(*) > 1 "
		. "";


	return ciniki_core_dbRspQuery($ciniki, $strsql, 'customers', 'files', 'excel', array('stat'=>'ok', 'files'=>array()));
}
?>
