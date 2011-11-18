<?php
//
// Description
// -----------
// This function will add a new customer given a hash of keys.
//
// Info
// ----
// Status: started
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
//
//
function ciniki_customers_addFromHash($ciniki, $customer) {

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashToSQL.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$rc = ciniki_core_dbHashToSQL($ciniki, 
		array('prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title'),
		$customer,
		'INSERT INTO ciniki_customers (business_id, status, ',
		'date_added, last_updated) VALUES ('
		'UTC_TIMESTAMP(), UTC_TIMESTAMP())');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['strsql'] != '') {
		$new_customer = ciniki_core_dbInsert($ciniki, $strsql, 'customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'83', 'msg'=>'Internal error', 'pmsg'=>'Unable to build SQL insert string'));
	}

	return array('stat'=>'ok', 'id'=>$rc['insert_id']);
}
?>
