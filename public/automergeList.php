<?php
//
// Description
// -----------
// This method will retrieve the list of uploaded excel files uploaded to automerge.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The business ID to get the excel files uploaded to the customers automerge.
// 
// Returns
// -------
// <files>
// 		<excel id="3" name="Temp.xls" source_name="Temp.xls" date_added="2011-01-08 12:59:00" />
// </files>
//
function ciniki_customers_automergeList($ciniki) {
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
	$ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.automergeList', 0);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Load the excel information
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	$strsql = "SELECT id, name, source_name, cur_review_row, "
		. "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added "
		. "FROM ciniki_customer_automerges "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = 10 "	// The file has been uploaded and parsed into the database
		. "";
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'files', 'excel', array('stat'=>'ok', 'files'=>array()));
}
?>
