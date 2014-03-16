<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get members for.
// type:			The type of participants to get.  Refer to participantAdd for 
//					more information on types.
//
// Returns
// -------
//
function ciniki_customers_memberList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Category'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.memberList', 0);
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

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
	// Load the list of members for a business
	//
	if( isset($args['category']) && $args['category'] != '' ) {
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.first, "
			. "ciniki_customers.last, "
			. "ciniki_customers.display_name, "
			. "ciniki_customers.member_status AS member_status_text, "
			. "ciniki_customers.member_lastpaid, "
			. "ciniki_customers.membership_length AS membership_length_text, "
			. "ciniki_customers.membership_type AS membership_type_text, "
			. "ciniki_customers.company "
			. "FROM ciniki_customer_tags, ciniki_customers "
			. "WHERE ciniki_customer_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customer_tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
			. "AND ciniki_customer_tags.tag_type = '40' "
			. "AND ciniki_customer_tags.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customers.member_status = 10 "
			. "ORDER BY last, first, company";
	} else {
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.first, "
			. "ciniki_customers.last, "
			. "ciniki_customers.display_name, "
			. "ciniki_customers.member_status AS member_status_text, "
			. "ciniki_customers.member_lastpaid, "
			. "ciniki_customers.membership_length AS membership_length_text, "
			. "ciniki_customers.membership_type AS membership_type_text, "
			. "ciniki_customers.company "
			. "FROM ciniki_customers "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customers.member_status = 10 "
			. "ORDER BY last, first, company";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artclub', array(
		array('container'=>'members', 'fname'=>'id', 'name'=>'member',
			'fields'=>array('id', 'first', 'last', 'display_name', 'company',
				'member_status_text', 'member_lastpaid', 'membership_length_text', 'membership_type_text'),
			'maps'=>array(
				'member_status_text'=>array('0'=>'Non-Member', '10'=>'Active', '60'=>'Suspended'),
				'membership_length_text'=>array('10'=>'Monthly', '20'=>'Yearly', '60'=>'Lifetime'),
				'membership_type_text'=>array('10'=>'Regular', '20'=>'Complimentary', '30'=>'Reciprocal'),
				),
			'utctotz'=>array('member_lastpaid'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['members']) ) {
		return array('stat'=>'ok', 'members'=>$rc['members']);
	} 

	return array('stat'=>'ok', 'members'=>array());
}
?>
