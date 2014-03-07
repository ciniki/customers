<?php
//
// Description
// -----------
// This function is called whenever a setting is changes in ciniki.web
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_settingChange($ciniki, $business_id, $field, $field_value) {

	if( $field == 'page-members-list-format' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
		$strsql = "SELECT id "
			. "FROM ciniki_customers "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND member_status > 0 "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$members = $rc['rows'];

		foreach($members as $member) {
			$rc = ciniki_customers_customerUpdateShortDescription($ciniki, $business_id, $member['id'], 0x04, $field_value);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>