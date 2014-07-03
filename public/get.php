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
			. "type, cid, display_name, "
			. "member_status, member_status AS member_status_text, "
			. "member_lastpaid, membership_length, membership_type, "
			. "dealer_status, dealer_status AS dealer_status_text, "
			. "distributor_status, distributor_status AS distributor_status_text, "
			. "prefix, first, middle, last, suffix, company, department, title, "
			. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS birthdate, "
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
					'birthdate', 'short_bio', 'full_bio', 'webflags', 'notes',
					'emails'),
				'lists'=>array('emails'),
				'maps'=>array(
					'member_status_text'=>array('0'=>'Non-Member', '10'=>'Active', '60'=>'Suspended'),
					'dealer_status_text'=>array('0'=>'Non-Dealer', '10'=>'Active', '60'=>'Suspended'),
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
		$strsql = "SELECT id, type, cid, display_name, primary_image_id, "
			. "member_status, member_status AS member_status_text, "
			. "member_lastpaid, membership_length, membership_type, "
			. "dealer_status, dealer_status AS dealer_status_text, "
			. "distributor_status, distributor_status AS distributor_status_text, "
			. "prefix, first, middle, last, suffix, company, department, title, "
			. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS birthdate, "
			. "short_bio, full_bio, webflags, "
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
				'fields'=>array('id', 'type', 'cid', 'display_name', 'primary_image_id', 
					'member_status', 'member_status_text', 'member_lastpaid', 'membership_length', 'membership_type',
					'dealer_status', 'dealer_status_text', 'distributor_status', 'distributor_status_text', 
					'prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title',
					'birthdate', 'short_bio', 'full_bio', 'webflags', 'notes'),
				'maps'=>array(
					'member_status_text'=>array('0'=>'Non-Member', '10'=>'Active', '60'=>'Suspended'),
					'dealer_status_text'=>array('0'=>'Non-Dealer', '10'=>'Active', '60'=>'Suspended'),
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
	// Get the categories and tags for the post
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
			. "address1, address2, city, province, postal, country, flags "
			. "FROM ciniki_customer_addresses "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'addresses', 'fname'=>'id', 'name'=>'address',
				'fields'=>array('id', 'address1', 'address2', 'city', 'province', 'postal', 
					'country', 'flags')),
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1595', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1595', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$rsp['distributor_categories'] = $rc['tags'];
		}
	}

	return $rsp;
}
?>
