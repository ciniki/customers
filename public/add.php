<?php
//
// Description
// -----------
// This function will add a new customer to the customers production module.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the customer to.
// cid:					The business ID of the customer, not used for internal linking.
// type:				The type of customer, as specified in customer settings.
// name:				(optional) The full name of the customer.  If this is specified,
//						it will be split at the first comma into last, first
//						or split at the last space "first name last".  If there
//						there is no space or comma, the name will be added as
// 						the first name.
//
//						**note** One of either name or first must be specified.
//
// prefix:				(optional) The prefix or title for the persons name: Ms. Mrs. Mr. Dr. etc.
// first:				(optional) The first name of the customer.
// middle:				(optional) The middle name or initial of the customer.
// last:				(optional) The last name of the customer.
// suffix:				(optional) The credentials or degrees for the customer: Ph.D, M.D., Jr., etc.
// company:				(optional) The company the customer works for.
// department:			(optional) The department at the company the customer works for.
// title:				(optional) The customers title at the company.
// address:				(optional) The email address of the customer.
// flags:				(optional) The options for the customer email address.  Default: 0.
//	
//						0x01 - The customer is allowed to login to the business website.
//
// address1:			(optional) The first line of the address.
// address2:			(optional) The second line of the address.
// city:				(optional) The city of the address.
// province:			(optional) The province or state of the address.
// postal:				(optional) The postal code or zip code of the address.
// country:				(optional) The country of the address.
// flags:				(optional) The options for the address, specifing what the 
//						address should be used for.
//				
//						0x01 - Shipping
//						0x02 - Billing
//						0x04 - Mailing
// 
// phone_home:			(optional) The home phone number for the customer.
// phone_work:			(optional) The work phone number for the customer.
// phone_cell:			(optional) The cell phone number for the customer.
// phone_fax:			(optional) The fax number for the customer.
// notes:				(optional) The notes for the customer.
// birthdate:			(optional) The birthdate of the customer.
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_add(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'cid'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Customer'),
		'type'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Customer Type'),
		'name'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Name'),
        'prefix'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Prefix'), 
        'first'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Suffix'), 
        'company'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company'), 
        'department'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company Department'), 
        'title'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company Title'), 
        'address'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Email'), 
        'flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Email Options'), 
		'address1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address'),
        'address2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address'), 
        'city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'City'), 
        'province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Province'), 
        'postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Postal Code'), 
        'country'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Country'), 
        'address_flags'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Flags'), 
        'phone_home'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Home Phone'), 
        'phone_work'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Work Phone'), 
        'phone_cell'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Cell Phone'), 
        'phone_fax'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Fax Number'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
        'birthdate'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'date', 'name'=>'Birthday'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	//
	// They must specify either a firstname or lastname
	//
	if( $args['first'] == '' && $args['last'] == '' && $args['name'] == '' && $args['company'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'368', 'msg'=>'You must specify a first or last name'));
	}

	//
	// Check if name should be parsed
	//
	if( $args['first'] == '' && $args['last'] == '' && $args['name'] != '' ) {
		// Check for a comma to see if was entered, "last, first"
		if( preg_match('/^\s*(.*),\s*(.*)\s*$/', $args['name'], $matches) ) {
			$args['last'] = $matches[1];
			$args['first'] = $matches[2];
		} elseif( preg_match('/^\s*(.*)\s([^\s]+)\s*$/', $args['name'], $matches) ) {
			$args['first'] = $matches[1];
			$args['last'] = $matches[2];
		} else {
			// Default to add name to first field instead of last field
			$args['first'] = $args['name'];
		}
	}
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.add', 0); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$uuid = $rc['uuid'];

	//
	// Add the customer to the database
	//
	$strsql = "INSERT INTO ciniki_customers (uuid, business_id, status, cid, type, prefix, first, middle, last, suffix, "
		. "company, department, title, phone_home, phone_work, phone_cell, phone_fax, notes, birthdate, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "1, "
		. "'" . ciniki_core_dbQuote($ciniki, $args['cid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['prefix']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['first']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['middle']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['last']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['suffix']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['company']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['department']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['title']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['phone_home']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['phone_work']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['phone_cell']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['phone_fax']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['notes']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['birthdate']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'369', 'msg'=>'Unable to add customer'));
	}
	$customer_id = $rc['insert_id'];

	//
	// FIXME: Move to array below
	// Add the uuid to the history
	//
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
		1, 'ciniki_customers', $customer_id, 'uuid', $uuid);

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'cid',
		'prefix',
		'first',
		'middle',
		'last',
		'suffix',
		'company',
		'department',
		'title',
		'phone_home',
		'phone_work',
		'phone_cell',
		'phone_fax',
		'notes',
		'birthdate',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
				1, 'ciniki_customers', $customer_id, $field, $args[$field]);
		}
	}

	//
	// Check if email address was specified, and add to customer emails
	//
	$email_id = 0;
	if( isset($args['address']) && $args['address'] != '' ) {
		//
		// Get a new UUID
		//
		$rc = ciniki_core_dbUUID($ciniki, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$uuid = $rc['uuid'];

		//
		// Add the customer email to the database
		//
		$strsql = "INSERT INTO ciniki_customer_emails (uuid, business_id, customer_id, email, flags, "
			. "date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['address']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', "
			. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) { 
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			if( $rc['err']['code'] == '73' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'724', 'msg'=>'Email address already exists'));
			}
			return $rc;
		}
		if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'720', 'msg'=>'Unable to add customer email'));
		}
		$email_id = $rc['insert_id'];

		//
		// Add the uuid to the history
		//
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
			1, 'ciniki_customer_emails', $email_id, 'uuid', $uuid);
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
			1, 'ciniki_customer_emails', $email_id, 'customer_id', $customer_id);
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
			1, 'ciniki_customer_emails', $email_id, 'email', $args['address']);

		//
		// Add all the fields to the change log
		//
		$changelog_fields = array(
			'flags',
			);
		foreach($changelog_fields as $field) {
			if( isset($args[$field]) && $args[$field] != '' ) {
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
					1, 'ciniki_customer_emails', $email_id, $field, $args[$field]);
			}
		}
	}

	//
	// Check if there is an address to add
	//
	$address_id = 0;
	if( (isset($args['address1']) && $args['address1'] != '' ) 
		|| (isset($args['address2']) && $args['address2'] != '' )
		|| (isset($args['city']) && $args['city'] != '' )
		|| (isset($args['province']) && $args['province'] != '' )
		|| (isset($args['postal']) && $args['postal'] != '' )
		) {
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
		$strsql = "INSERT INTO ciniki_customer_addresses (uuid, business_id, customer_id, "
			. "flags, "
			. "address1, address2, city, province, postal, country, "
			. "date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['address_flags']) . "', "
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'375', 'msg'=>'Unable to add customer address'));
		}
		$address_id = $rc['insert_id'];

		//
		// Add the uuid to the history
		//
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
			1, 'ciniki_customer_addresses', $address_id, 'uuid', $uuid);
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
			1, 'ciniki_customer_addresses', $address_id, 'customer_id', $customer_id);

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
			if( isset($args[$field]) && $args[$field] != '' ) {
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 
					'ciniki_customer_history', $args['business_id'], 
					1, 'ciniki_customer_addresses', $address_id, $field, $args[$field]);
			}
		}
		// Address_flags should be addes as flags, but must be passed to this method as address_flags so 
		// not to be confused with email flags
		if( isset($args['address_flags']) && $args['address_flags'] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 
				'ciniki_customer_history', $args['business_id'], 
				1, 'ciniki_customer_addresses', $address_id, 'flags', $args['address_flags']);
		}
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

	$ciniki['syncqueue'][] = array('push'=>'ciniki.customers.customer', 
		'args'=>array('id'=>$customer_id));
	if( $email_id > 0 ) {
		$ciniki['syncqueue'][] = array('push'=>'ciniki.customers.email', 'args'=>array('id'=>$email_id));
	}
	if( $address_id > 0 ) {
		$ciniki['syncqueue'][] = array('push'=>'ciniki.customers.address', 'args'=>array('id'=>$address_id));
	}

	return array('stat'=>'ok', 'id'=>$customer_id);
}
?>
