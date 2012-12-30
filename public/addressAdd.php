<?php
//
// Description
// -----------
// This function will add a new customer address to a customer.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the customer belongs to.
// customer_id:		The ID of the customer to add the address to.
// address1:		(optional) The first line of the address.
// address2:		(optional) The second line of the address.
// city:			(optional) The city of the address.
// province:		(optional) The province or state of the address.
// postal:			(optional) The postal code or zip code of the address.
// country:			(optional) The country of the address.
// flags:			(optional) The options for the address, specifing what the 
//					address should be used for.
//				
//					0x01 - Shipping
//					0x02 - Billing
//					0x04 - Mailing
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_addressAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No customer specified'), 
		'address1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No address specified'),
        'address2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No address specified'), 
        'city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No city specified'), 
        'province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No province specified'), 
        'postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No postal specified'), 
        'country'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No country specified'), 
        'flags'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No flags specified'), 
//        'shipping'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No shipping flag specified'), 
//        'billing'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No billing flag specified'), 
//        'mailing'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No mailing flag specified'), 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.addressAdd', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Get a new UUID
	//
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$uuid = $rc['uuid'];

	//
	// Add the customer to the database
	//
	$strsql = "INSERT INTO ciniki_customer_addresses (uuid, customer_id, "
		. "flags, "
		. "address1, address2, city, province, postal, country, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['address1']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['address2']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['city']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['province']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['postal']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['country']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'373', 'msg'=>'Unable to add customer address'));
	}
	$address_id = $rc['insert_id'];

	//
	// Add the uuid to the history
	//
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
		1, 'ciniki_customer_addresses', $address_id, 'uuid', $uuid);

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'customer_id',
		'address1',
		'address2',
		'city',
		'province',
		'postal',
		'country',
		'flags',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
				1, 'ciniki_customer_addresses', $address_id, $field, $args[$field]);
		}
	}

	//
	// Update the customer last_updated date
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTouch');
	$rc = ciniki_core_dbTouch($ciniki, 'ciniki.customers', 'ciniki_customers', 'id', $args['customer_id']);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'598', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'customers');

	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.syncPushCustomer', 'args'=>array('id'=>$args['customer_id']));

	return array('stat'=>'ok', 'id'=>$address_id);
}
?>
