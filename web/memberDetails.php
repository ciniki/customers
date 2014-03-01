<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_memberDetails($ciniki, $settings, $business_id, $permalink) {

	$strsql = "SELECT ciniki_customers.id, "
		. "ciniki_customers.display_name AS member, "
		. "ciniki_customers.company, "
		. "ciniki_customers.permalink, "
		. "ciniki_customers.full_bio AS description, "
		. "ciniki_customers.primary_image_id, "
		. "ciniki_customer_images.image_id, "
		. "ciniki_customer_images.name AS image_name, "
		. "ciniki_customer_images.permalink AS image_permalink, "
		. "ciniki_customer_images.description AS image_description, "
		. "UNIX_TIMESTAMP(ciniki_customer_images.last_updated) AS image_last_updated "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_images ON ("
			. "ciniki_customers.id = ciniki_customer_images.customer_id "
			. "AND (ciniki_customer_images.webflags&0x01) = 1 "
			. ") "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customers.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		// Check the member is visible on the website
		. "AND ciniki_customers.member_status = 10 "
		. "AND (ciniki_customers.webflags&0x01) = 1 "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'members', 'fname'=>'id', 
			'fields'=>array('id', 'permalink', 'member', 'company', 
				'image_id'=>'primary_image_id', 'description')),
		array('container'=>'images', 'fname'=>'image_id', 
			'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
				'description'=>'image_description', 
				'last_updated'=>'image_last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['members']) || count($rc['members']) < 1 ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'938', 'msg'=>'We are sorry, but the member you requested does not exist.'));
	}
	$member = array_pop($rc['members']);

	if( isset($member['company']) && $member['company'] != '' ) {
		$member['name'] = $member['company'];
	} else {
		$member['name'] = $member['member'];
	}

	//
	// Check for any links for the member
	//
	$strsql = "SELECT id, name, url, description "
		. "FROM ciniki_customer_links "
		. "WHERE ciniki_customer_links.customer_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
		. "AND ciniki_customer_links.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_customer_links.webflags&0x01) = 1 "	// Visible on website
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'links', 'fname'=>'id', 
			'fields'=>array('name', 'url', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['links']) ) {
		$member['links'] = $rc['links'];
	} else {
		$member['links'] = array();
	}
		
	return array('stat'=>'ok', 'member'=>$member);
}
?>
