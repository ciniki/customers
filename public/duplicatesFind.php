<?php
//
// Description
// -----------
// This method will return a list of potential duplicates
// 
// Returns
// -------
//
function ciniki_customers_duplicatesFind($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
	$ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.duplicatesFind', 0);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Search for any potential duplicate customers
	//
	$strsql = "SELECT CONCAT_WS('-', c1.id, c2.id) AS match_id, c1.id AS c1_id, CONCAT_WS(' ', c1.first, c1.last) AS c1_name, "
		. "c2.id AS c2_id, CONCAT_WS(' ', c2.first, c2.last) AS c2_name "
		. "FROM ciniki_customers AS c1, ciniki_customers AS c2 "
		. "WHERE c1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND c2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND c1.id < c2.id "
//		. "AND ((c1.first = c2.first and c1.last = c2.last) OR (c1.first = c2.last and c1.last = c2.first)) "
		. "AND ((soundex(c1.first) = soundex(c2.first) and soundex(c1.last) = soundex(c2.last)) "
			. "OR (soundex(c1.first) = soundex(c2.last) and soundex(c1.last) = soundex(c2.first))) "
		. "ORDER BY c1_name, c1.id "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'matches', 'fname'=>'match_id', 'name'=>'match',
			'fields'=>array('c1_id', 'c1_name', 'c2_id', 'c2_name'),
			),
//		array('container'=>'duplicates', 'fname'=>'c2_id', 'name'=>'customer',
//			'fields'=>array('id'=>'c2_id', 'first'=>'c2_first', 'last'=>'c2_last'),
//			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// When customers match multiple records, the second and third matches will appear in the
	// list later on by themselves, and should be removed
	//
//	foreach($rc['customers'] as $cnum => $customer) {
//		foreach($customer['customer']['duplicates'] as $dnum => $dup) {
			// because the list is sorted, we need to only start from where we are and carry forward
//			for($i=$cnum;$i<count($rc['customers']);$i++) {
//				if( $dup['customer']['id'] == $rc['customers'][$i]['customer']['id'] ) {
//					unset($rc['customers'][$i]);
//				}
//			}
//		}
//	}

	return $rc;
}
?>
