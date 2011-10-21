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
        'shipping'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No shipping toggle specified'), 
        'billing'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No billing toggle specified'), 
        'mailing'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No mailing toggle specified'), 
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
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	$on_flags = 0;
	$off_flags = 0;
	if( $args['shipping'] == 'On' || $args['shipping'] == 'on' ) {
		$on_flags = $on_flags | 0x01;
		$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
			'customer_addresses', $args['customer_id'] + '-' + $args['address_id'], 'shipping', 'On');
	} elseif( $args['shipping'] == 'Off' || $args['shipping'] == 'off' ) {
		$off_flags = $off_flags | 0x01;
		$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
			'customer_addresses', $args['customer_id'] + '-' + $args['address_id'], 'shipping', 'Off');
	}
	if( $args['billing'] == 'On' || $args['billing'] == 'on' ) {
		$on_flags = $on_flags | 0x02;
		$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
			'customer_addresses', $args['customer_id'] + '-' + $args['address_id'], 'billing', 'On');
	} elseif( $args['billing'] == 'Off' || $args['billing'] == 'off' ) {
		$off_flags = $off_flags | 0x02;
		$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
			'customer_addresses', $args['customer_id'] + '-' + $args['address_id'], 'billing', 'Off');
	}
	if( $args['mailing'] == 'On' || $args['mailing'] == 'on' ) {
		$on_flags = $on_flags | 0x04;
		$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
			'customer_addresses', $args['customer_id'] + '-' + $args['address_id'], 'mailing', 'On');
	} elseif( $args['mailing'] == 'Off' || $args['mailing'] == 'off' ) {
		$off_flags = $off_flags | 0x04;
		$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
			'customer_addresses', $args['customer_id'] + '-' + $args['address_id'], 'mailing', 'Off');
	}


	//
	// Add the customer to the database
	//
	$strsql = "UPDATE customer_addresses SET last_updated = UTC_TIMESTAMP()";
	
	if( $on_flags > 0 ) {
		$strsql .= ", flags = flags|$on_flags ";
	}
	if( $off_flags > 0 ) {
		$strsql .= ", flags = flags^$off_flags ";
	}

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
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
				'customer_addresses', $args['customer_id'] + '-' + $args['address_id'], $field, $args[$field]);
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
		return array('stat'=>'fail', 'err'=>array('code'=>'381', 'msg'=>'Unable to add customer'));
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
