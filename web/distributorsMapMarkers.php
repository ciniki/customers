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
function ciniki_customers_web_distributorsMapMarkers($ciniki, $settings, $business_id, $args) {

	$strsql = "SELECT "
		. "ciniki_customer_addresses.latitude AS y, "
		. "ciniki_customer_addresses.longitude AS x, "
		. "ciniki_customers.display_name AS t, "
		. "ciniki_customers.permalink AS p, "
		. "ciniki_customers.short_description AS c, "
		. "IF(full_bio<>'', 'yes', 'no') AS d "
		. "FROM ciniki_customer_addresses, ciniki_customers "
		. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customer_addresses.latitude <> 0 "
		. "AND ciniki_customer_addresses.longitude <> 0 "
		. "AND (ciniki_customer_addresses.flags&0x08) > 0 "
		. "AND ciniki_customer_addresses.customer_id = ciniki_customers.id "
		// Check the distributor is visible on the website
//		. "AND ciniki_customers.distributor_status = 10 "
		. "AND (ciniki_customers.webflags&0x04) = 0x04 "
		. "ORDER BY ciniki_customers.sort_name ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'marker');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['rows']) ) {
		return array('stat'=>'ok', 'markers'=>array());
	}
	$markers = $rc['rows'];
	foreach($markers as $mid => $marker) {
		$markers[$mid]['c'] = nl2br($marker['c']);
	}
	return array('stat'=>'ok', 'markers'=>$markers);
}
?>
