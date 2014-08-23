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
function ciniki_customers_web_dealerList($ciniki, $settings, $business_id, $args) {


	$tag_name = '';
	if( isset($args['country']) && $args['country'] != '' ) {
		// Get the list of dealers base on country, province, city
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.display_name AS title, "
			. "ciniki_customers.permalink, "
			. "ciniki_customers.short_description, "
			. "ciniki_customers.primary_image_id, "
			. "IF(full_bio<>'', 'yes', 'no') AS is_details "
			. "FROM ciniki_customer_addresses, ciniki_customers "
			. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_addresses.country = '" . ciniki_core_dbQuote($ciniki, $args['country']) . "' "
			. "";
		if( isset($args['province']) && $args['province'] != '' && $args['province'] != '-' ) {
			$strsql .= "AND ciniki_customer_addresses.province = '" . ciniki_core_dbQuote($ciniki, $args['province']) . "' ";
		}
		if( isset($args['city']) && $args['city'] != '' && $args['city'] != '-' ) {
			$strsql .= "AND ciniki_customer_addresses.city = '" . ciniki_core_dbQuote($ciniki, $args['city']) . "' ";
		}
		$strsql .= "AND ciniki_customer_addresses.customer_id = ciniki_customers.id "
			// Check the dealer is visible on the website
			. "AND ciniki_customers.dealer_status = 10 "
			. "AND (ciniki_customers.webflags&0x02) = 2 "
			. "ORDER BY ciniki_customers.sort_name ";
	} elseif( isset($args['category']) && $args['category'] != '' ) {
		$strsql = "SELECT tag_name FROM ciniki_customer_tags "
			. "WHERE permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'tag');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['rows'][0]['tag_name']) ) {
			$tag_name = $rc['rows'][0]['tag_name'];
		}

		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.display_name AS title, "
			. "ciniki_customers.permalink, "
			. "ciniki_customers.short_description, "
			. "ciniki_customers.primary_image_id, "
			. "IF(full_bio<>'', 'yes', 'no') AS is_details "
			. "FROM ciniki_customer_tags, ciniki_customers "
			. "WHERE ciniki_customer_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_customer_tags.tag_type = '60' "
			. "AND ciniki_customer_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "AND ciniki_customer_tags.customer_id = ciniki_customers.id "
			// Check the dealer is visible on the website
			. "AND ciniki_customers.dealer_status = 10 "
			. "AND (ciniki_customers.webflags&0x02) = 2 "
			. "ORDER BY ciniki_customers.sort_name ";
	} else {
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.display_name AS title, "
			. "ciniki_customers.permalink, "
			. "ciniki_customers.short_description, "
			. "ciniki_customers.primary_image_id, "
			. "IF(full_bio<>'', 'yes', 'no') AS is_details "
			. "FROM ciniki_customers "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			// Check the dealer is visible on the website
			. "AND ciniki_customers.dealer_status = 10 "
			. "AND (ciniki_customers.webflags&0x02) = 2 "
			. "ORDER BY ciniki_customers.sort_name ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	if( isset($args['format']) && $args['format'] == '2dlist' ) {
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'dealers', 'fname'=>'id',
				'fields'=>array('id', 'name'=>'title')),
			array('container'=>'list', 'fname'=>'id', 
				'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id',
					'description'=>'short_description', 'is_details')),
			));
	} else {
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'dealers', 'fname'=>'id', 
				'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id',
					'description'=>'short_description', 'is_details')),
			));
	}
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['dealers']) ) {
		return array('stat'=>'ok', 'tag_name'=>$tag_name, 'dealers'=>array());
	}
	return array('stat'=>'ok', 'tag_name'=>$tag_name, 'dealers'=>$rc['dealers']);
}
?>
