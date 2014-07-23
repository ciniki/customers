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
		'member_categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member Categories'),
		'dealer_categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Dealer Categories'),
		'distributor_categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Distributor Categories'),
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
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
//	$rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']); 
//	if( $rc['stat'] != 'ok' ) {	
//		return $rc;
//	}
//	$types = $rc['types'];

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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
//	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the customer details and emails
	//
	$strsql = "SELECT ciniki_customers.id, cid, type, "
		. "member_status, member_lastpaid, membership_length, membership_type, "
		. "dealer_status, distributor_status, "
		. "prefix, first, middle, last, suffix, "
		. "display_name, display_name_format, company, department, title, "
		. "ciniki_customer_emails.id AS email_id, ciniki_customer_emails.email, "
		. "ciniki_customer_emails.flags AS email_flags, "
		. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS birthdate, "
		. "pricepoint_id, notes, primary_image_id, webflags, short_bio, full_bio "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id) "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'webflags', 
				'member_status', 'member_lastpaid', 'membership_length', 'membership_type', 
				'dealer_status', 'distributor_status',
				'cid', 'type', 'prefix', 'first', 'middle', 'last', 'suffix', 
				'display_name', 'display_name_format', 'company', 'department', 'title', 
				'pricepoint_id', 'notes', 'primary_image_id', 'short_bio', 'full_bio', 'birthdate'),
				'utctotz'=>array('member_lastpaid'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
				),
		array('container'=>'emails', 'fname'=>'email_id', 'name'=>'email',
			'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'address'=>'email', 
				'flags'=>'email_flags')),
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
//	if( $rc['customers'][0]['customer']['type'] > 0 && isset($types[$rc['customers'][0]['customer']['type']]) ) {
//		$rc['customers'][0]['customer']['display_type'] = $types[$rc['customers'][0]['customer']['type']]['detail_value'];
//	}

	$customer = $rc['customers'][0]['customer'];
	$customer['addresses'] = array();
	$customer['subscriptions'] = array();

	//
	// Get the categories and tags for the post
	//
	if( ($modules['ciniki.customers']['flags']&0x03) > 0 
		|| ($modules['ciniki.customers']['flags']&0x20) > 0 
		|| ($modules['ciniki.customers']['flags']&0x200) > 0 
		) {
		$strsql = "SELECT tag_type, tag_name AS lists "
			. "FROM ciniki_customer_tags "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
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
				if( $tags['tags']['tag_type'] == 40 ) {
					$customer['member_categories'] = $tags['tags']['lists'];
				} elseif( $tags['tags']['tag_type'] == 60 ) {
					$customer['dealer_categories'] = $tags['tags']['lists'];
				} elseif( $tags['tags']['tag_type'] == 80 ) {
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
	$strsql = "SELECT id, customer_id, name, url, webflags "
		. "FROM ciniki_customer_links "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'links', 'fname'=>'id', 'name'=>'link',
			'fields'=>array('id', 'customer_id', 'name', 'url', 'webflags')),
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
				. "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
				. "AND ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
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


	$rsp = array('stat'=>'ok', 'customer'=>$customer);

	//
	// Check if all available member categories should be returned
	//
	if( isset($args['member_categories']) && $args['member_categories'] == 'yes' ) {
		//
		// Get the available tags
		//
		$rsp['member_categories'] = array();
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['business_id'], 
			'ciniki_customer_tags', 40);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1651', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$rsp['member_categories'] = $rc['tags'];
		}
	}

	//
	// Check if all available dealer categories should be returned
	//
	if( isset($args['dealer_categories']) && $args['dealer_categories'] == 'yes' ) {
		//
		// Get the available tags
		//
		$rsp['dealer_categories'] = array();
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['business_id'], 
			'ciniki_customer_tags', 60);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1792', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$rsp['dealer_categories'] = $rc['tags'];
		}
	}

	//
	// Check if all available distributor categories should be returned
	//
	if( isset($args['distributor_categories']) && $args['distributor_categories'] == 'yes' ) {
		//
		// Get the available tags
		//
		$rsp['distributor_categories'] = array();
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['business_id'], 
			'ciniki_customer_tags', 40);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1793', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$rsp['distributor_categories'] = $rc['tags'];
		}
	}

	return $rsp;
}
?>
