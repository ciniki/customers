<?php
//
// Description
// -----------
// This function will return info about the file and the stats
//
// Arguments
// ---------
// api_key:
// auth_token:		
// business_id:			The business ID the excel file is connected to.
// automerge_id:			The excel ID from the table ciniki_toolbox_excel.
//
// Returns
// -------
// <stats rows=0 matches=0 reviewed=0 deleted=0 />
//
function ciniki_customers_automergeStats($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'automerge_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No spreadsheet specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
	$ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.automergeStats', $args['automerge_id']);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	$stats = array(
		'rows'=>0,
		'conflicts'=>0,
		'merged'=>0,
		);

	//
	// Get the number of rows in data
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbCount.php');
	$strsql = "SELECT 'rows', COUNT(DISTINCT row) "
		. "FROM ciniki_customer_automerge_data "
		. "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
		// . "GROUP BY status "
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'customers', 'excel');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$stats['rows'] = $rc['excel']['rows'];

	//
	// Get the number of rows with conflicts
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbCount.php');
	$strsql = "SELECT 'rows', COUNT(DISTINCT row) "
		. "FROM ciniki_customer_automerge_data "
		. "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
		. "GROUP BY status "
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'customers', 'excel');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['excel']) ) {
		if( isset($rc['excel']['rows']) ) {
			$stats['conflicts'] = $rc['excel']['rows'];
		}
	}

	//
	// Get the number of columns
	//
	$strsql = "SELECT 'cols', COUNT(col) FROM ciniki_customer_automerge_data "
		. "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
		. "AND row = 1"
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'customers', 'num');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['num']) || !isset($rc['num']['cols']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'571', 'msg'=>'Unable to gather statistics'));
	}
	$num_cols = $rc['num']['cols'];

	//
	// Get the number of rows merged
	//
	$strsql = "SELECT 'rows', COUNT(*) AS num_rows "
		. "FROM ("
			. "SELECT row, COUNT(status) FROM ciniki_customer_automerge_data "
			. "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
			. "AND status >= 60 "
			. "GROUP BY row HAVING COUNT(status) = '" . ciniki_core_dbQuote($ciniki, $num_cols) . "'"
			. ") AS sb"
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'customers', 'excel');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['excel']) ) {
		if( isset($rc['excel']['rows']) ) {
			$stats['merged'] = $rc['excel']['rows'];
		}
	}

	return array('stat'=>'ok', 'stats'=>$stats);
}
?>
