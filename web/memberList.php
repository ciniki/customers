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
function ciniki_customers_web_memberList($ciniki, $settings, $business_id, $args) {

	if( isset($args['category']) && $args['category'] != '' ) {
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.display_name AS title, "
			. "ciniki_customers.permalink, "
			. "ciniki_customers.short_description, "
			. "ciniki_customers.primary_image_id, "
			. "IF(full_bio<>'', 'yes', 'no') AS is_details "
			. "FROM ciniki_customer_tags, ciniki_customers "
			. "WHERE ciniki_customer_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_tags.tag_type = '40' "
			. "AND ciniki_customer_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "AND ciniki_customer_tags.customer_id = ciniki_customers.id "
			// Check the member is visible on the website
			. "AND ciniki_customers.member_status = 10 "
			. "AND (ciniki_customers.webflags&0x01) = 1 "
			. "ORDER BY ciniki_customers.last, ciniki_customers.first, ciniki_customers.company ";
	} else {
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.display_name AS title, "
			. "ciniki_customers.permalink, "
			. "ciniki_customers.short_description, "
			. "ciniki_customers.primary_image_id, "
			. "IF(full_bio<>'', 'yes', 'no') AS is_details "
			. "FROM ciniki_customers "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			// Check the member is visible on the website
			. "AND ciniki_customers.member_status = 10 "
			. "AND (ciniki_customers.webflags&0x01) = 1 "
			. "ORDER BY ciniki_customers.last, ciniki_customers.first ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'members', 'fname'=>'id', 
			'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id',
				'description'=>'short_description', 'is_details')),
		));
//	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
//		array('container'=>'members', 'fname'=>'id', 'name'=>'member',
//			'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 
//				'permalink', 'description'=>'short_bio')),
//		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['members']) ) {
		return array('stat'=>'ok', 'members'=>array());
	}
	return array('stat'=>'ok', 'members'=>$rc['members']);
}
?>
