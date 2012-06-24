<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// key:					The detail key to get the history for.
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_customers_addressHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No customer specified'), 
		'address_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No address specified'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No field specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
	$rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.addressHistory', $args['customer_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check the address ID belongs to the requested customer
	//
	$strsql = "SELECT ciniki_customer_addresses.id, customer_id "
		. "FROM ciniki_customers, ciniki_customer_addresses "
		. "WHERE ciniki_customer_addresses.id = '" . ciniki_core_dbQuote($ciniki, $args['address_id']) . "' "
		. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND customer_id = ciniki_customers.id "
		. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'customers', 'address');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( !isset($rc['address']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'740', 'msg'=>'Access denied'));
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbGetModuleHistory.php');
	return ciniki_core_dbGetModuleHistory($ciniki, 'customers', 'ciniki_customer_history', $args['business_id'], 'ciniki_customer_addresses', $args['address_id'], $args['field'], 'customers');
}
?>
