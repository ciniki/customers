<?php
//
// Description
// -----------
// This function will add a new customer address to the customers production module.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// customer_id:		The customer id the address is to be added to.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_addressAdd($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No customer specified'), 
		'address1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No address specified'),
        'address2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No address specified'), 
        'city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No city specified'), 
        'province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No province specified'), 
        'postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No postal specified'), 
        'country'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No country specified'), 
        'shipping'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No shipping flag specified'), 
        'billing'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No billing flag specified'), 
        'mailing'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No mailing flag specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	//
	// They must specify something about the address
	//
	if( $args['address1'] == '' && $args['city'] == '' && $args['province'] == '' && $args['postal'] != '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'374', 'msg'=>'No address specified'));
	}
	
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.addressAdd', $args['customer_id']); 
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	$flags = 0;
	if( $args['shipping'] == 'On' || $args['shipping'] == 'on' ) {
		$flags = $flags | 0x01;
	}
	if( $args['billing'] == 'On' || $args['billing'] == 'on' ) {
		$flags = $flags | 0x02;
	}
	if( $args['mailing'] == 'On' || $args['mailing'] == 'on' ) {
		$flags = $flags | 0x04;
	}

	//
	// Add the customer to the database
	//
	$strsql = "INSERT INTO customer_addresses (customer_id, "
		. "flags, "
		. "address1, address2, city, province, postal, country, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', "
		. "$flags, "
		. "'" . ciniki_core_dbQuote($ciniki, $args['address1']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['address2']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['city']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['province']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['postal']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['country']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'373', 'msg'=>'Unable to add customer address'));
	}
	$address_id = $rc['insert_id'];

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
		'shipping',
		'billing',
		'mailing',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
				'customer_addresses', $args['customer_id'] + '-' + $address_id, $field, $args[$field]);
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$address_id);
}
?>
