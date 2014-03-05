<?php
//
// Description
// -----------
// This method will return the detail of a customer along with data for customers from other modules.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_getModuleData($ciniki) {
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.getModuleData', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

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

	//
	// Get the customer details and emails
	//
	$strsql = "SELECT ciniki_customers.id, cid, type, prefix, first, middle, last, suffix, "
		. "display_name, company, department, title, "
		. "ciniki_customer_emails.id AS email_id, ciniki_customer_emails.email, "
		. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS birthdate, "
		. "notes "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id "
			. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'cid', 'type', 'prefix', 'first', 'middle', 'last', 'suffix', 'display_name', 
				'company', 'department', 'title', 
				'notes', 'birthdate')),
		array('container'=>'emails', 'fname'=>'email_id', 'name'=>'email',
			'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'address'=>'email')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customers']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1511', 'msg'=>'Invalid customer'));
	}
	$customer = $rc['customers'][0]['customer'];
	$customer['addresses'] = array();
	$customer['subscriptions'] = array();

	//
	// Get phones
	//
	$strsql = "SELECT id, phone_label, phone_number, flags "
		. "FROM ciniki_customer_phones "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'phones', 'fname'=>'id', 'name'=>'phone',
			'fields'=>array('id', 'phone_label', 'phone_number', 'flags')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['phones']) ) {
		$customer['phones'] = $rc['phones'];
	}

	//
	// Get the customer addresses
	//
	$strsql = "SELECT id, customer_id, "
		. "address1, address2, city, province, postal, country, flags "
		. "FROM ciniki_customer_addresses "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
	// Get the customer links
	//
	$strsql = "SELECT id, customer_id, "
		. "name, url, webflags "
		. "FROM ciniki_customer_links "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'links', 'fname'=>'id', 'name'=>'link',
			'fields'=>array('id', 'name', 'url', 'webflags')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['links']) ) {
		$customer['links'] = $rc['links'];
	}

	//
	// Get the relationships for the customer
	//
	if( isset($settings['use-relationships']) && $settings['use-relationships'] == 'yes' ) {
		$strsql = "SELECT ciniki_customer_relationships.id, relationship_type AS type, "
			. "relationship_type AS type_name, "
			. "ciniki_customer_relationships.customer_id, ciniki_customer_relationships.related_id, "
//			. "IF(customer_id='" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', related_id, customer_id) AS related_id, "
			. "date_started, date_ended, ciniki_customers.display_name, ciniki_customers.company "
			. "";
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
					'display_name', 'date_started', 'date_ended'),
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

	//
	// Get the wineproduction appointments
	//
	if( isset($modules['ciniki.wineproduction']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'wineproduction', 'private', 'appointments');
		$rc = ciniki_wineproduction__appointments($ciniki, $args['business_id'], array(
			'customer_id'=>$args['customer_id'],
			'status'=>'unbottled',
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['appointments']) ) {
			$customer['appointments'] = $rc['appointments'];
		} 

		//
		// Get the unbottled wineproduction orders
		//
		$strsql = "SELECT ciniki_wineproductions.id, "
			. "ciniki_wineproductions.invoice_number, "
			. "ciniki_products.name AS wine_name, "
			. "ciniki_wineproductions.status, "
			. "ciniki_wineproductions.status AS status_text, "
			. "DATE_FORMAT(ciniki_wineproductions.order_date, '%b %e, %Y') AS order_date, "
			. "DATE_FORMAT(ciniki_wineproductions.start_date, '%b %e, %Y') AS start_date, "
			. "DATE_FORMAT(ciniki_wineproductions.racking_date, '%b %e, %Y') AS racking_date, "
			. "DATE_FORMAT(ciniki_wineproductions.filtering_date, '%b %e, %Y') AS filtering_date, "
			. "DATE_FORMAT(ciniki_wineproductions.bottling_date, '%b %e, %Y') AS bottling_date, "
			. "DATE_FORMAT(IF(rack_date > 0, DATE_ADD(rack_date, INTERVAL (kit_length) DAY), "
				. "DATE_ADD(ciniki_wineproductions.start_date, INTERVAL kit_length WEEK)), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS approx_filtering_date "
			. "FROM ciniki_wineproductions "
			. "LEFT JOIN ciniki_products ON (ciniki_wineproductions.product_id = ciniki_products.id "
				. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_wineproductions.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_wineproductions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_wineproductions.status < 60 "
			. "ORDER BY ciniki_wineproductions.order_date DESC "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.wineproductions', array(
			array('container'=>'orders', 'fname'=>'id', 'name'=>'order',
				'fields'=>array('id', 'invoice_number', 'wine_name', 'status', 'status_text',
					'order_date', 'start_date', 'racking_date', 'filtering_date', 'bottling_date',
					'approx_filtering_date'),
				'maps'=>array('status_text'=>array(
					'10'=>'Entered',
					'20'=>'Started',
					'30'=>'Racked',
					'40'=>'Filtered',
					'60'=>'Bottled',
					))),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['orders']) ) {
			$customer['currentwineproduction'] = $rc['orders'];
		}

		//
		// Get the bottled wineproduction orders
		//
		$strsql = "SELECT ciniki_wineproductions.id, "
			. "ciniki_wineproductions.invoice_number, "
			. "ciniki_products.name AS wine_name, "
			. "ciniki_wineproductions.status, "
			. "ciniki_wineproductions.status AS status_text, "
			. "DATE_FORMAT(ciniki_wineproductions.order_date, '%b %e, %Y') AS order_date, "
			. "DATE_FORMAT(ciniki_wineproductions.start_date, '%b %e, %Y') AS start_date, "
			. "DATE_FORMAT(ciniki_wineproductions.racking_date, '%b %e, %Y') AS racking_date, "
			. "DATE_FORMAT(ciniki_wineproductions.filtering_date, '%b %e, %Y') AS filtering_date, "
			. "DATE_FORMAT(ciniki_wineproductions.bottling_date, '%b %e, %Y') AS bottling_date, "
			. "DATE_FORMAT(IF(rack_date > 0, DATE_ADD(rack_date, INTERVAL (kit_length) DAY), "
				. "DATE_ADD(ciniki_wineproductions.start_date, INTERVAL kit_length WEEK)), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS approx_filtering_date "
			. "FROM ciniki_wineproductions "
			. "LEFT JOIN ciniki_products ON (ciniki_wineproductions.product_id = ciniki_products.id "
				. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_wineproductions.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_wineproductions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_wineproductions.status = 60 "
			. "ORDER BY ciniki_wineproductions.order_date DESC "
			. "LIMIT 11 "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.wineproductions', array(
			array('container'=>'orders', 'fname'=>'id', 'name'=>'order',
				'fields'=>array('id', 'invoice_number', 'wine_name', 'status', 'status_text',
					'order_date', 'start_date', 'racking_date', 'filtering_date', 'bottling_date',
					'approx_filtering_date'),
				'maps'=>array('status_text'=>array(
					'10'=>'Entered',
					'20'=>'Started',
					'30'=>'Racked',
					'40'=>'Filtered',
					'60'=>'Bottled',
					))),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['orders']) ) {
			$customer['pastwineproduction'] = $rc['orders'];
		}
	}

	//
	// Check for invoices for the customer
	//
	if( isset($modules['ciniki.sapos']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'customerInvoices');
		$rc = ciniki_sapos_customerInvoices($ciniki, $args['business_id'], $args['customer_id'], 11);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['invoices']) ) {
			$customer['invoices'] = $rc['invoices'];
		}
	}

	return array('stat'=>'ok', 'customer'=>$customer);
}
?>
