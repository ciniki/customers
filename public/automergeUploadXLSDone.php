<?php
//
// Description
// -----------
// This function will parse a selection of rows from an upload.  For large excel files,
// the process is divided into sections to get around the memory (512M) and time limits (30seconds).
//
// Info
// ----
// Status: 				alpha
//
// Arguments
// ---------
// api_key:
// auth_token:		
// business_id:			The business ID to create the excel file for.
// upload_id:			The information about the file uploaded via a file form field.
// start:				The starting row, 1 or greater.
// size:				The number of records to process, starting with the start row.
//
// Returns
// -------
// <upload id="19384992" />
//
function ciniki_customers_automergeUploadXLSDone($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'automerge_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No excel specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access to business_id
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
	$ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.automergeUploadXLSDone', $args['automerge_id']);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Remove the 
	$inputFileName = $ciniki['config']['core']['modules_dir'] . '/customers/uploads/automerge_' . $args['automerge_id'] . '.xls';
	unlink($inputFileName);

	//
	// FIXME: Delete all blank rows
	//


	//
	// Update the information in the database
	//
	$strsql = "UPDATE ciniki_customer_automerges SET status = 10 "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' ";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'customers');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return $rc;
	}

	//
	// Commit the update
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$args['automerge_id']);
}
?>