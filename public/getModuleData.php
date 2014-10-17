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
		'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
		'eid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.getModuleData', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Check either ID or code has been specified
	//
	if( (!isset($args['customer_id']) || $args['customer_id'] == '')
		&& (!isset($args['eid']) || $args['eid'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1907', 'msg'=>'You must specify either a customer or ID'));
	}

	//
	// Get the business settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
//	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');

	//
	// Load maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
	$rc = ciniki_customers_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

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
	$date_format = ciniki_users_dateFormat($ciniki, 'php');

	//
	// Get the customer details and emails
	//
	$strsql = "SELECT ciniki_customers.id, eid, parent_id, type, prefix, first, middle, last, suffix, "
		. "display_name, company, department, title, "
		. "status, status AS status_text, "
		. "dealer_status, dealer_status AS dealer_status_text, "
		. "distributor_status, distributor_status AS distributor_status_text, "
		. "ciniki_customer_emails.id AS email_id, ciniki_customer_emails.email, "
		. "ciniki_customer_emails.flags AS email_flags, "
		. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, '%b %e, %Y') . "'), '') AS birthdate, "
		. "pricepoint_id, salesrep_id, tax_number, tax_location_id, "
		. "reward_level, sales_total, start_date, webflags, "
		. "notes "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id "
			. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 ) {
		$strsql .= "AND salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
	}
	if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
		$strsql .= "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
	} elseif( isset($args['eid']) && $args['eid'] != '' ) {
		$strsql .= "AND ciniki_customers.eid = '" . ciniki_core_dbQuote($ciniki, $args['eid']) . "' ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1906', 'msg'=>'You must specify either a customer or ID'));
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'eid', 'parent_id', 'type', 'prefix', 'first', 'middle', 'last', 'suffix', 'display_name', 
				'status', 'status_text',
				'dealer_status', 'dealer_status_text',
				'distributor_status', 'distributor_status_text',
				'company', 'department', 'title', 
				'notes', 'birthdate', 'pricepoint_id', 'salesrep_id', 'tax_number', 'tax_location_id',
				'reward_level', 'sales_total', 'start_date', 'webflags'),
			'maps'=>array('status_text'=>$maps['customer']['status'],
				'dealer_status_text'=>$maps['customer']['dealer_status'],
				'distributor_status_text'=>$maps['customer']['distributor_status']),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format))),
		array('container'=>'emails', 'fname'=>'email_id', 'name'=>'email',
			'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'address'=>'email', 'flags'=>'email_flags'),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customers']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1511', 'msg'=>'Invalid customer'));
	}
	$customer = $rc['customers'][0]['customer'];
	$customer['addresses'] = array();
	$customer['subscriptions'] = array();

	//
	// Get the sales rep
	//
	if( ($modules['ciniki.customers']['flags']&0x2000) > 0 ) {
		$customer['salesrep_id_text'] = '';
		if( isset($customer['salesrep_id']) && $customer['salesrep_id'] > 0 ) {
			$strsql = "SELECT display_name "
				. "FROM ciniki_business_users, ciniki_users "
				. "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $customer['salesrep_id']) . "' "
				. "AND ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_business_users.package = 'ciniki' "
				. "AND ciniki_business_users.permission_group = 'salesreps' "
				. "AND ciniki_business_users.user_id = ciniki_users.id "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['user']) ) {
				$customer['salesrep_id_text'] = $rc['user']['display_name'];
			}
		}
	}

	//
	// Get the tax location
	//
	if( isset($customer['tax_location_id']) && $customer['tax_location_id'] > 0 
		&& isset($modules['ciniki.taxes'])
		&& ($modules['ciniki.taxes']['flags']&0x01) > 0
		&& ($modules['ciniki.customers']['flags']&0x2000) > 0 
		) {
		$strsql = "SELECT ciniki_tax_locations.id, ciniki_tax_locations.code, ciniki_tax_locations.name, "
			. "ciniki_tax_rates.id AS rate_id, ciniki_tax_rates.name AS rate_name "
			. "FROM ciniki_tax_locations "
			. "LEFT JOIN ciniki_tax_rates ON ( "
				. "ciniki_tax_locations.id = ciniki_tax_rates.location_id "
				. "AND ciniki_tax_rates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND ciniki_tax_rates.start_date < UTC_TIMESTAMP() "
				. "AND (ciniki_tax_rates.end_date = '0000-00-00 00:00:00' "
					. "OR ciniki_tax_rates.end_date > UTC_TIMESTAMP()) "
				. ") "
			. "WHERE ciniki_tax_locations.id = '" . ciniki_core_dbQuote($ciniki, $customer['tax_location_id']) . "' "
			. "AND ciniki_tax_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
			array('container'=>'taxes', 'fname'=>'id',
				'fields'=>array('id', 'code', 'name')),
			array('container'=>'rates', 'fname'=>'rate_id',
				'fields'=>array('name'=>'rate_name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['taxes'][$customer['tax_location_id']]) ) {
			$tax = $rc['taxes'][$customer['tax_location_id']];
			$customer['tax_location_id_text'] = '';
//			if( ($modules['ciniki.taxes']['flags']&0x02) && $tax['code'] != '' ) {
//				$customer['tax_location_id_text'] = $tax['code'] . ' - ';
//			}
			$customer['tax_location_id_text'] .= $tax['name'];
			$customer['tax_location_id_rates'] = '';
			if( isset($tax['rates']) ) {
				foreach($tax['rates'] as $rid => $rate) {
					$customer['tax_location_id_rates'] .= ($customer['tax_location_id_rates']!=''?', ':'') . $rate['name'];
				}
			}
		}
	}

	//
	// Get the categories and tags for the customer
	//
	if( ($modules['ciniki.customers']['flags']&0xC00224) > 0 ) {
		$strsql = "SELECT tag_type, tag_name AS lists "
			. "FROM ciniki_customer_tags "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY tag_type, tag_name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
			array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
				'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['tags']) ) {
			foreach($rc['tags'] as $tags) {
				if( $tags['tags']['tag_type'] == 10 ) {
					$customer['customer_categories'] = $tags['tags']['lists'];
				}
				if( $tags['tags']['tag_type'] == 20 ) {
					$customer['customer_tags'] = $tags['tags']['lists'];
				}
				if( $tags['tags']['tag_type'] == 40 ) {
					$customer['member_categories'] = $tags['tags']['lists'];
				}
				if( $tags['tags']['tag_type'] == 60 ) {
					$customer['dealer_categories'] = $tags['tags']['lists'];
				}
				if( $tags['tags']['tag_type'] == 80 ) {
					$customer['distributor_categories'] = $tags['tags']['lists'];
				}
			}
		}
	}

	//
	// Get phones
	//
	$strsql = "SELECT id, phone_label, phone_number, flags "
		. "FROM ciniki_customer_phones "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
//			. "IF(customer_id='" . ciniki_core_dbQuote($ciniki, $customer['id']) . "', related_id, customer_id) AS related_id, "
			. "date_started, date_ended, ciniki_customers.display_name, ciniki_customers.company "
			. "";
		$strsql .= "FROM ciniki_customer_relationships "
			. "LEFT JOIN ciniki_customers ON ("
				. "(ciniki_customer_relationships.customer_id <> '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
				. "AND ciniki_customer_relationships.customer_id = ciniki_customers.id "
				. ") OR ("
				. "ciniki_customer_relationships.related_id <> '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
				. "AND ciniki_customer_relationships.related_id = ciniki_customers.id "
				. ")) "
			. "WHERE ciniki_customer_relationships.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (ciniki_customer_relationships.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
				. "OR ciniki_customer_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
				if( $relationship['related_id'] == $customer['id'] ) {
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
	// Get any child customers
	//
	if( ($modules['ciniki.customers']['flags']&0x200000) > 0 ) {
		$strsql = "SELECT id, eid, display_name "
			. "FROM ciniki_customers "
			. "WHERE ciniki_customers.parent_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'children', 'fname'=>'id', 'name'=>'customer',
				'fields'=>array('id', 'eid', 'display_name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['children']) ) {
			$customer['children'] = $rc['children'];
		} else {
			$customer['children'] = array();
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
				. "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "') "
			. "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_subscription_customers.status = 10 "
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
			'customer_id'=>$customer['id'],
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
			. "WHERE ciniki_wineproductions.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
			. "WHERE ciniki_wineproductions.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'customerInvoices');
		$rc = ciniki_sapos_hooks_customerInvoices($ciniki, $args['business_id'], array(
			'customer_id'=>$customer['id'], 
			'limit'=>11));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['types']) ) {
			foreach($rc['types'] as $tid => $type) {
				if( $type['type']['type'] == '10' ) {
					$customer['invoices'] = $type['type']['invoices'];
				} elseif( $type['type']['type'] == '20' ) {
					$customer['carts'] = $type['type']['invoices'];
				} elseif( $type['type']['type'] == '30' ) {
					$customer['pos'] = $type['type']['invoices'];
				} elseif( $type['type']['type'] == '40' ) {
					$customer['orders'] = $type['type']['invoices'];
				}
			}
		}
		//
		// Make sure at least a blank array exist for each type of invoice
		//
		if( ($modules['ciniki.sapos']['flags']&0x01) && !isset($customer['invoices']) ) {
			$customer['invoices'] = array();
		}
		if( ($modules['ciniki.sapos']['flags']&0x08) && !isset($customer['carts']) ) {
			$customer['carts'] = array();
		}
		if( ($modules['ciniki.sapos']['flags']&0x10) && !isset($customer['pos']) ) {
			$customer['pos'] = array();
		}
		if( ($modules['ciniki.sapos']['flags']&0x20) && !isset($customer['orders']) ) {
			$customer['orders'] = array();
		}
	}

	return array('stat'=>'ok', 'customer'=>$customer);
}
?>
