<?php
//
// Description
// -----------
// This function will return a customer record
//
// Info
// ----
// Status: 			started
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_customers_get($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
		'phones'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Phone'),
		'emails'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email'),
		'addresses'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Addresses'),
		'links'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Links'),
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'seasons'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Seasons'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.get', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

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
	// Get the types of customers available for this business
	//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
 //   $rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']); 
//	if( $rc['stat'] != 'ok' ) {	
//		return $rc;
//	}
//	$types = $rc['types'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	if( isset($args['emails']) && $args['emails'] == 'list' ) {
		$strsql = "SELECT ciniki_customers.id, ciniki_customers.primary_image_id, "
			. "type, eid, display_name, "
			. "member_status, member_status AS member_status_text, "
			. "member_lastpaid, membership_length, membership_type, "
			. "dealer_status, dealer_status AS dealer_status_text, "
			. "distributor_status, distributor_status AS distributor_status_text, "
			. "prefix, first, middle, last, suffix, company, department, title, "
			. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS birthdate, "
			. "ciniki_customers.pricepoint_id, ciniki_customers.salesrep_id, "
			. "ciniki_customers.tax_number, ciniki_customers.tax_location_id, "
			. "ciniki_customers.reward_level, ciniki_customers.sales_total, ciniki_customers.start_date, "
			. "ciniki_customer_emails.email AS emails "
			. "FROM ciniki_customers "
			. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id "
				. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
				'fields'=>array('id', 'type', 'display_name', 'primary_image_id', 
					'member_status', 'member_status_text', 'member_lastpaid', 'membership_length', 'membership_type',
					'dealer_status', 'dealer_status_text', 'distributor_status', 'distributor_status_text', 
					'prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title',
					'pricepoint_id', 'salesrep_id', 'tax_number', 'tax_location_id',
					'reward_level', 'sales_total', 'start_date', 
					'birthdate', 'short_bio', 'full_bio', 'webflags', 'notes',
					'emails'),
				'lists'=>array('emails'),
				'maps'=>array(
					'member_status_text'=>array('0'=>'Non-Member', '10'=>'Active', '60'=>'Suspended'),
					'dealer_status_text'=>array('0'=>'Non-Dealer', '5'=>'Prospect', '10'=>'Active', '60'=>'Suspended'),
					'distributor_status_text'=>array('0'=>'Non-Distributor', '10'=>'Active', '60'=>'Suspended'),
					),
				'utctotz'=>array('member_lastpaid'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['customers'][0]['customer']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1469', 'msg'=>'Invalid customer'));
		}
		$customer = $rc['customers'][0]['customer'];
	} else {
		$strsql = "SELECT id, type, eid, display_name, primary_image_id, "
			. "member_status, member_status AS member_status_text, "
			. "member_lastpaid, membership_length, membership_type, "
			. "dealer_status, dealer_status AS dealer_status_text, "
			. "distributor_status, distributor_status AS distributor_status_text, "
			. "prefix, first, middle, last, suffix, company, department, title, "
			. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS birthdate, "
			. "short_bio, full_bio, webflags, "
			. "ciniki_customers.pricepoint_id, ciniki_customers.salesrep_id, "
			. "ciniki_customers.tax_number, ciniki_customers.tax_location_id, "
			. "ciniki_customers.reward_level, ciniki_customers.sales_total, ciniki_customers.start_date, "
			. "notes "
			. "FROM ciniki_customers "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
//		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
//		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
//		if( $rc['stat'] != 'ok' ) {
//			return $rc;
//		}
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
				'fields'=>array('id', 'type', 'eid', 'display_name', 'primary_image_id', 
					'member_status', 'member_status_text', 'member_lastpaid', 'membership_length', 'membership_type',
					'dealer_status', 'dealer_status_text', 'distributor_status', 'distributor_status_text', 
					'prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title',
					'pricepoint_id', 'salesrep_id', 'tax_number', 'tax_location_id',
					'reward_level', 'sales_total', 'start_date', 
					'birthdate', 'short_bio', 'full_bio', 'webflags', 'notes'),
				'maps'=>array(
					'member_status_text'=>array('0'=>'Non-Member', '10'=>'Active', '60'=>'Suspended'),
					'dealer_status_text'=>array('0'=>'Non-Dealer', '5'=>'Prospect', '10'=>'Active', '60'=>'Suspended'),
					'distributor_status_text'=>array('0'=>'Non-Distributor', '10'=>'Active', '60'=>'Suspended'),
					),
				'utctotz'=>array('member_lastpaid'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['customers'][0]['customer']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'370', 'msg'=>'Invalid customer'));
		}
		$customer = $rc['customers'][0]['customer'];
	}

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
	if( ($modules['ciniki.customers']['flags']&0x03) > 0 ) {
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
				}
			}
		}
	}

	//
	// Get the categories and tags for the post
	//
	if( isset($args['member_categories']) && $args['member_categories'] == 'yes' 
		&& ($modules['ciniki.customers']['flags']&0x03) > 0 ) {
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
					$post['member_categories'] = $tags['tags']['lists'];
				}
			}
		}
	}

	//
	// Get phones
	//
	if( isset($args['phones']) && $args['phones'] == 'yes' ) {
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
	}

	//
	// Get emails
	//
	if( isset($args['emails']) && $args['emails'] == 'yes' ) {
		$strsql = "SELECT id, email AS address, flags "
			. "FROM ciniki_customer_emails "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'emails', 'fname'=>'id', 'name'=>'email',
				'fields'=>array('id', 'address', 'flags')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['emails']) ) {
			$customer['emails'] = $rc['emails'];
		}
	}

	//
	// Get the customer addresses
	//
	if( isset($args['addresses']) && $args['addresses'] == 'yes' ) {
		$strsql = "SELECT id, "
			. "address1, address2, city, province, postal, country, flags, latitude, longitude, phone "
			. "FROM ciniki_customer_addresses "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'addresses', 'fname'=>'id', 'name'=>'address',
				'fields'=>array('id', 'address1', 'address2', 'city', 'province', 'postal', 
					'country', 'flags', 'latitude', 'longitude', 'phone')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['addresses']) ) {
			$customer['addresses'] = $rc['addresses'];
		}
	}

	//
	// Get links
	//
	if( isset($args['links']) && $args['links'] == 'yes' ) {
		$strsql = "SELECT id, name, url, webflags "
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
	}

	//
	// Get images
	//
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		$strsql = "SELECT id, name, image_id, webflags "
			. "FROM ciniki_customer_images "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'images', 'fname'=>'id', 'name'=>'image',
				'fields'=>array('id', 'name', 'image_id', 'webflags')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['images']) ) {
			$customer['images'] = $rc['images'];
			ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
			foreach($customer['images'] as $inum => $img) {
				if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
					$rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], 
						$img['image']['image_id'], 75);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$customer['images'][$inum]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
				}
			}
		}
	}

	//
	// If subscriptions
	//
	if( isset($modules['ciniki.subscriptions']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'customerSubscriptions');
		$rc = ciniki_subscriptions_hooks_customerSubscriptions($ciniki, $args['business_id'], 
			array('customer_id'=>$args['customer_id']));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['subscriptions']) ) {
			$customer['subscriptions'] = $rc['subscriptions'];
		}
	}

	//
	// Get any membership seasons
	//
	if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 
		&& isset($args['seasons']) && $args['seasons'] == 'yes' 
		) {
		$strsql = "SELECT ciniki_customer_seasons.id, "
			. "ciniki_customer_seasons.name, "
			. "ciniki_customer_seasons.flags, "
			. "IFNULL(ciniki_customer_season_members.id, 0) AS season_member_id, "
			. "IFNULL(ciniki_customer_season_members.status, '') AS status, "
			. "IFNULL(ciniki_customer_season_members.date_paid, '') AS date_paid "
			. "FROM ciniki_customer_season_members, ciniki_customer_seasons "
			. "WHERE ciniki_customer_seasons.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (ciniki_customer_seasons.flags&0x02) > 0 "
			. "AND ciniki_customer_seasons.id = ciniki_customer_season_members.season_id "
			. "AND ciniki_customer_season_members.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_customer_seasons.start_date DESC "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'seasons', 'fname'=>'id', 'name'=>'season',
				'fields'=>array('id', 'name', 'flags', 'season_member_id', 'status', 'date_paid')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['seasons']) ) {
			$customer['seasons'] = $rc['seasons'];
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1595', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1790', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
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
			'ciniki_customer_tags', 80);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1791', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$rsp['distributor_categories'] = $rc['tags'];
		}
	}

	return $rsp;
}
?>
