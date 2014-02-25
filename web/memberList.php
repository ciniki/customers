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
function ciniki_customers_web_memberList($ciniki, $settings, $business_id) {

	$strsql = "SELECT ciniki_customers.id, "
		. "ciniki_customers.display_name AS name, "
		. "ciniki_customers.permalink, "
		. "ciniki_customers.short_bio, "
		. "ciniki_customers.primary_image_id "
		. "FROM ciniki_customers "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		// Check the member is visible on the website
		. "AND ciniki_customers.member_status = 10 "
		. "AND (ciniki_customers.webflags&0x01) = 1 "
		. "ORDER BY ciniki_customers.last, ciniki_customers.first ";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'members', 'fname'=>'id', 'name'=>'member',
			'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 
				'permalink', 'description'=>'short_bio')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['members']) ) {
		return array('stat'=>'ok', 'members'=>array());
	}
	return array('stat'=>'ok', 'members'=>$rc['members']);
}
?>
