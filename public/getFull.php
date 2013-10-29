<?php
//
// Description
// -----------
// This function will return a full record of the customer, including attached addresses and emails.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_getFull($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.getFull', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Get the types of customers available for this business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
    $rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']); 
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$types = $rc['types'];

	//
	// Get the settings for customer module
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $args['business_id']); 
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Get the relationship types
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getRelationshipTypes');
    $rc = ciniki_customers_getRelationshipTypes($ciniki, $args['business_id']); 
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$relationship_types = $rc['types'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
//	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the customer details and emails
	//
	$strsql = "SELECT ciniki_customers.id, cid, type, prefix, first, middle, last, suffix, "
		. "CONCAT_WS(' ', prefix, first, middle, last, suffix) AS name, "
		. "company, department, title, "
		. "phone_home, phone_work, phone_cell, phone_fax, "
		. "ciniki_customer_emails.id AS email_id, ciniki_customer_emails.email, "
		. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS birthdate, "
		. "notes "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id) "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'cid', 'type', 'prefix', 'first', 'middle', 'last', 'suffix', 'name', 
				'company', 'department', 'title', 
				'phone_home', 'phone_work', 'phone_cell', 'phone_fax',
				'notes', 'birthdate')),
		array('container'=>'emails', 'fname'=>'email_id', 'name'=>'email',
			'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'address'=>'email')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customers']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'722', 'msg'=>'Invalid customer'));
	}
	//
	// Set the display type for the customer
	//
	if( $rc['customers'][0]['customer']['type'] > 0 && isset($types[$rc['customers'][0]['customer']['type']]) ) {
		$rc['customers'][0]['customer']['display_type'] = $types[$rc['customers'][0]['customer']['type']]['detail_value'];
	}


	$customer = $rc['customers'][0]['customer'];
	$customer['addresses'] = array();
	$customer['subscriptions'] = array();

	//
	// Get the customer addresses
	//
	$strsql = "SELECT id, customer_id, "
		. "address1, address2, city, province, postal, country, flags "
		. "FROM ciniki_customer_addresses "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'addresses', 'fname'=>'id', 'name'=>'address',
			'fields'=>array('id', 'customer_id', 'address1', 'address2', 'city', 'province', 'postal', 
				'country', 'flags')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['addresses']) ) {
		$customer['addresses'] = $rc['addresses'];
	}

	//
	// Get the relationships for the customer
	//
	if( isset($settings['use-relationships']) && $settings['use-relationships'] == 'yes' ) {
		$strsql = "SELECT ciniki_customer_relationships.id, relationship_type AS type, "
			. "relationship_type AS type_name, "
			. "ciniki_customer_relationships.customer_id, ciniki_customer_relationships.related_id, "
//			. "IF(customer_id='" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', related_id, customer_id) AS related_id, "
			. "date_started, date_ended, "
			. "";
		if( count($types) > 0 ) {
			// If there are customer types defined, choose the right name for the customer
			// This is required here to be able to sort properly
			$strsql .= "CASE ciniki_customers.type ";
			foreach($types as $tid => $type) {
				$strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN ";
				if( $type['detail_value'] == 'business' ) {
					$strsql .= " ciniki_customers.company ";
				} else {
					$strsql .= "CONCAT_WS(' ', first, last) ";
				}
			}
			$strsql .= "ELSE CONCAT_WS(' ', first, last) END AS customer_name ";
		} else {
			// Default to a person
			$strsql .= "CONCAT_WS(' ', first, last) AS customer_name ";
		}
		$strsql .= "FROM ciniki_customer_relationships "
			. "LEFT JOIN ciniki_customers ON ("
				. "(ciniki_customer_relationships.customer_id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
				. "AND ciniki_customer_relationships.customer_id = ciniki_customers.id "
				. ") OR ("
				. "ciniki_customer_relationships.related_id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
				. "AND ciniki_customer_relationships.related_id = ciniki_customers.id "
				. ")) "
			. "WHERE ciniki_customer_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (ciniki_customer_relationships.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
				. "OR ciniki_customer_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
				. ") "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'relationships', 'fname'=>'id', 'name'=>'relationship',
				'fields'=>array('id', 'type', 'customer_id', 'type_name', 'related_id', 
					'name'=>'customer_name', 'date_started', 'date_ended'),
				'maps'=>array('type_name'=>$relationship_types)),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['relationships']) ) {
			$customer['relationships'] = $rc['relationships'];
			foreach($customer['relationships'] as $rid => $relationship) {
				$relationship = $relationship['relationship'];
				//
				// Check if this relationship needs to be reversed
				//
				if( $relationship['related_id'] == $args['customer_id'] ) {
					if( isset($relationship_types[-$relationship['type']]) ) {
						$customer['relationships'][$rid]['relationship']['type_name'] = $relationship_types[-$relationship['type']];
					}
					$customer['relationships'][$rid]['relationship']['type'] = -$relationship['type'];
					$customer['relationships'][$rid]['relationship']['related_id'] = $relationship['customer_id'];
				}
			}
		}
	}

	// 
	// Get customer subscriptions if module is enabled
	//
	if( isset($modules['ciniki.subscriptions']) ) {
		$strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, "
			. "ciniki_subscription_customers.id AS customer_subscription_id, "
			. "ciniki_subscriptions.description, ciniki_subscription_customers.status "
			. "FROM ciniki_subscriptions "
			. "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
				. "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "') "
			. "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND status = 10 "
			. "ORDER BY ciniki_subscriptions.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
				'fields'=>array('id', 'name', 'description', 'customer_subscription_id', 'status')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['subscriptions']) ) {
			$customer['subscriptions'] = $rc['subscriptions'];
		}
	}

	return array('stat'=>'ok', 'customer'=>$customer);
}
?>
