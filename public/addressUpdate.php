<?php
//
// Description
// -----------
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_addressUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No customer specified'), 
        'address_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No address specified'), 
        'address1'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No address1 specified'), 
        'address2'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No address2 specified'), 
        'city'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No city specified'), 
        'province'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No province specified'), 
        'postal'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No postal specified'), 
        'country'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No country specified'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No flags specified'), 
//        'shipping'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No shipping toggle specified'), 
//        'billing'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No billing toggle specified'), 
//        'mailing'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No mailing toggle specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.addressUpdate', $args['customer_id']); 
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'741', 'msg'=>'Access denied'));
	}

	//  
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the customer to the database
	//
	$strsql = "UPDATE ciniki_customer_addresses SET last_updated = UTC_TIMESTAMP()";
	
	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'address1',
		'address2',
		'city',
		'province',
		'postal',
		'country',
		'flags',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'customers', 'ciniki_customer_history', $args['business_id'], 
				2, 'ciniki_customer_addresses', $args['address_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['address_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'381', 'msg'=>'Unable to add customer'));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
