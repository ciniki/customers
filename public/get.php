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
		'emails'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email'),
		'addresses'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Addresses'),
		'links'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Links'),
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	if( isset($args['emails']) && $args['emails'] == 'list' ) {
		$strsql = "SELECT ciniki_customers.id, type, cid, display_name, "
			. "prefix, first, middle, last, suffix, company, department, title, "
			. "phone_home, phone_work, phone_fax, phone_cell, "
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
					'prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title',
					'phone_home', 'phone_work', 'phone_cell', 'phone_fax', 'emails'),
				'lists'=>array('emails'),
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
			. "prefix, first, middle, last, suffix, company, department, title, "
			. "phone_home, phone_work, phone_cell, phone_fax, "
			. "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS birthdate, "
			. "primary_image_id, short_bio, full_bio, webflags, "
			. "notes "
			. "FROM ciniki_customers "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['customer']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'370', 'msg'=>'Invalid customer'));
		}
		$customer = $rc['customer'];
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

	return array('stat'=>'ok', 'customer'=>$customer);
}
?>
