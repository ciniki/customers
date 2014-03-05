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
// email_address:		(optional) The email address of the customer.
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
// member_categories:	(optional) The category tags for the member.
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
		'status'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Status'),
		'type'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Customer Type'),
        'member_status'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Member Status'), 
		'member_lastpaid'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Member Last Paid'),
		'membership_length'=>array('required'=>'no', 'default'=>'20', 'blank'=>'no', 'name'=>'Membership Length'),
		'membership_type'=>array('required'=>'no', 'default'=>'10', 'blank'=>'no', 'name'=>'Membership Type'),
		'name'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Name'),
        'prefix'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Prefix'), 
        'first'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Suffix'), 
        'company'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company'), 
        'department'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company Department'), 
        'title'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company Title'), 
        'email_address'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Email Address'), 
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
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'), 
        'webflags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Webflags'), 
        'short_bio'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Short Bio'), 
        'full_bio'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Full Bio'), 
        'birthdate'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'date', 'name'=>'Birthday'), 
		'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Subscriptions'),
		'unsubscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Unsubscriptions'),
		'member_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

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
	// Get the current settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
	$rc = ciniki_customers_getSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

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
	// Determine the display name
	//
	$space = '';
	$person_name = '';
	if( isset($args['prefix']) && $args['prefix'] != '' ) {
		$person_name .= $args['prefix'];
	}
	if( $space == '' && $person_name != '' ) { $space = ' '; }
	if( isset($args['first']) && $args['first'] != '' ) {
		$person_name .= $space . $args['first'];
	}
	if( $space == '' && $person_name != '' ) { $space = ' '; }
	if( isset($args['middle']) && $args['middle'] != '' ) {
		$person_name .= $space . $args['middle'];
	}
	if( $space == '' && $person_name != '' ) { $space = ' '; }
	if( isset($args['last']) && $args['last'] != '' ) {
		$person_name .= $space . $args['last'];
	}
	if( $space == '' && $person_name != '' ) { $space = ' '; }
	if( isset($args['suffix']) && $args['suffix'] != '' ) {
		$person_name .= $space . $args['suffix'];
	}
	if( $args['type'] == 2 && $args['company'] != '' ) {
		if( !isset($settings['display-name-business-format']) 
			|| $settings['display-name-business-format'] == 'company' ) {
			$args['display_name'] = $args['company'];
		} elseif( $settings['display-name-business-format'] == 'company - person' ) {
			$args['display_name'] = $args['company'] . ' - ' . $person_name;
		} elseif( $settings['display-name-business-format'] == 'person - company' ) {
			$args['display_name'] = $person_name . ' - ' . $args['company'];
		} elseif( $settings['display-name-business-format'] == 'company [person]' ) {
			$args['display_name'] = $args['company'] . ' [' . $person_name . ']';
		} elseif( $settings['display-name-business-format'] == 'person [company]' ) {
			$args['display_name'] = $person_name . ' [' . $args['company'] . ']';
		}
	} else {
		$args['display_name'] = $person_name;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
	$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['display_name']);
    
	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.customer', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$customer_id = $rc['id'];

	//
	// Check if phone numbers to add
	//
	if( isset($args['phone_home']) && $args['phone_home'] != '' ) {
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.phone',
			array('customer_id'=>$customer_id,
				'phone_label'=>'Home',
				'phone_number'=>$args['phone_home'],
				'flags'=>0), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}
	if( isset($args['phone_work']) && $args['phone_work'] != '' ) {
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.phone',
			array('customer_id'=>$customer_id,
				'phone_label'=>'Work',
				'phone_number'=>$args['phone_work'],
				'flags'=>0), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}
	if( isset($args['phone_cell']) && $args['phone_cell'] != '' ) {
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.phone',
			array('customer_id'=>$customer_id,
				'phone_label'=>'Cell',
				'phone_number'=>$args['phone_cell'],
				'flags'=>0), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}
	if( isset($args['phone_fax']) && $args['phone_fax'] != '' ) {
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.phone',
			array('customer_id'=>$customer_id,
				'phone_label'=>'Fax',
				'phone_number'=>$args['phone_fax'],
				'flags'=>0), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}

	//
	// Check if email address was specified, and add to customer emails
	//
	$email_id = 0;
	if( isset($args['email_address']) && $args['email_address'] != '' ) {
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.email',
			array('customer_id'=>$customer_id,
				'email'=>$args['email_address'],
				'password'=>'',
				'temp_password'=>'',
				'temp_password_date'=>'',
				'flags'=>$args['flags'],
				), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
		$email_id = $rc['id'];
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
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.address',
			array('customer_id'=>$customer_id,
				'flags'=>$args['address_flags'],
				'address1'=>$args['address1'],
				'address2'=>$args['address2'],
				'city'=>$args['city'],
				'province'=>$args['province'],
				'postal'=>$args['postal'],
				'country'=>$args['country'],
				'notes'=>'',
				), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
		$address_id = $rc['id'];
	}

	//
	// Check for subscriptions
	//
	if( isset($args['subscriptions']) || isset($args['unsubscriptions']) ) {
		// incase one of the args isn't set, setup with blank arrays
		if( !isset($args['subscriptions']) ) { $args['subscriptions'] = array(); }
		if( !isset($args['unsubscriptions']) ) { $args['unsubscriptions'] = array(); }
		ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'updateCustomerSubscriptions');
		$rc = ciniki_subscriptions_updateCustomerSubscriptions($ciniki, $args['business_id'], 
			$customer_id, $args['subscriptions'], $args['unsubscriptions']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Update the categories
	//
	if( isset($args['member_categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['business_id'],
			'ciniki_customer_tags', 'ciniki_customer_history',
			'customer_id', $customer_id, 40, $args['member_categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
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

	return array('stat'=>'ok', 'id'=>$customer_id);
}
?>
