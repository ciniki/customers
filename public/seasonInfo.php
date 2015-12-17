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
function ciniki_customers_seasonInfo($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'season_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Season'), 
		'list'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'List'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.seasonInfo', 0);
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
	$mysql_date_format = ciniki_users_dateFormat($ciniki);

	//
	// Load maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
	$rc = ciniki_customers_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Setup the return array
	//
	$rsp = array('stat'=>'ok', 'unattached'=>0, 'inactive'=>0, 'regular'=>0, 'student'=>0, 'individual'=>0, 'family'=>0, 'complimentary'=>0, 'reciprocal'=>0, 'members'=>array());

	//
	// Get the stats of the season
	//
	$strsql = "SELECT ciniki_customers.membership_type, "
		. "IFNULL(ciniki_customer_season_members.status, '0') AS status, "
		. "COUNT(ciniki_customers.id) AS num_customers "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_season_members ON ("
			. "ciniki_customers.id = ciniki_customer_season_members.customer_id "
			. "AND ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
			. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.member_status = 10 "		// Active members only
		. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
		. "GROUP BY membership_type, ciniki_customer_season_members.status "
		. "ORDER BY membership_type, ciniki_customer_season_members.status "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['rows']) ) {
		foreach($rc['rows'] as $rid => $row) {
			if( $row['status'] == '0' ) {
				$rsp['unattached'] += $row['num_customers'];
			}
			if( $row['status'] == '60' ) {
				$rsp['inactive'] += $row['num_customers'];
			}
			if( $row['status'] == '10' && $row['membership_type'] == '10' ) {
				$rsp['regular'] += $row['num_customers'];
			}
			if( $row['status'] == '10' && $row['membership_type'] == '20' ) {
				$rsp['student'] += $row['num_customers'];
			}
			if( $row['status'] == '10' && $row['membership_type'] == '30' ) {
				$rsp['individual'] += $row['num_customers'];
			}
			if( $row['status'] == '10' && $row['membership_type'] == '40' ) {
				$rsp['family'] += $row['num_customers'];
			}
			if( $row['status'] == '10' && $row['membership_type'] == '110' ) {
				$rsp['complimentary'] += $row['num_customers'];
			}
			if( $row['status'] == '10' && $row['membership_type'] == '150' ) {
				$rsp['reciprocal'] += $row['num_customers'];
			}
		}
	} 

	//
	// Load the list of requested members
	//
	if( isset($args['list']) && $args['list'] != '' ) {
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customers.first, "
			. "ciniki_customers.last, "
			. "ciniki_customers.display_name, "
			. "ciniki_customers.member_status AS member_status_text, "
			. "ciniki_customers.member_lastpaid, "
			. "DATEDIFF(NOW(), ciniki_customers.member_lastpaid) AS member_lastpaid_age, "
			. "ciniki_customers.membership_length AS membership_length_text, "
			. "ciniki_customers.membership_type, "
			. "ciniki_customers.membership_type AS membership_type_text, "
			. "ciniki_customers.company, "
			. "IFNULL(ciniki_customer_season_members.status, '0') AS member_season_status, "
			. "IFNULL(ciniki_customer_season_members.status, '0') AS member_season_status_text, "
			. "IFNULL(DATE_FORMAT(ciniki_customer_season_members.date_paid, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "'), '') AS date_paid "
			. "";
			if( $args['list'] == 'unattached' ) {
				$strsql .= "FROM ciniki_customers "
					. "LEFT JOIN ciniki_customer_season_members ON ("
						. "ciniki_customers.id = ciniki_customer_season_members.customer_id "
						. "AND ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
						. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
						. ") "
					. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customers.member_status = 10 "		// Active member
					. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
					. "HAVING member_season_status = 0 "	// Not in season_members or not active status
					. "";
			} elseif( $args['list'] == 'inactive' ) {
				$strsql .= "FROM ciniki_customer_season_members, ciniki_customers "
					. "WHERE ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
					. "AND ciniki_customer_season_members.status = 60 "
					. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customer_season_members.customer_id = ciniki_customers.id "
					. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
					. "";
			} elseif( $args['list'] == 'regular' ) {
				$strsql .= "FROM ciniki_customer_season_members, ciniki_customers "
					. "WHERE ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
					. "AND ciniki_customer_season_members.status = 10 "
					. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customer_season_members.customer_id = ciniki_customers.id "
					. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customers.membership_type = '10' "	// Regular Member
					. "AND ciniki_customers.member_status = 10 "		// Active member
					. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
					. "";
			} elseif( $args['list'] == 'student' ) {
				$strsql .= "FROM ciniki_customer_season_members, ciniki_customers "
					. "WHERE ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
					. "AND ciniki_customer_season_members.status = 10 "
					. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customer_season_members.customer_id = ciniki_customers.id "
					. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customers.membership_type = '20' "	// Regular Member
					. "AND ciniki_customers.member_status = 10 "		// Active member
					. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
					. "";
			} elseif( $args['list'] == 'individual' ) {
				$strsql .= "FROM ciniki_customer_season_members, ciniki_customers "
					. "WHERE ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
					. "AND ciniki_customer_season_members.status = 10 "
					. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customer_season_members.customer_id = ciniki_customers.id "
					. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customers.membership_type = '30' "	// Regular Member
					. "AND ciniki_customers.member_status = 10 "		// Active member
					. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
					. "";
			} elseif( $args['list'] == 'family' ) {
				$strsql .= "FROM ciniki_customer_season_members, ciniki_customers "
					. "WHERE ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
					. "AND ciniki_customer_season_members.status = 10 "
					. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customer_season_members.customer_id = ciniki_customers.id "
					. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customers.membership_type = '40' "	// Regular Member
					. "AND ciniki_customers.member_status = 10 "		// Active member
					. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
					. "";
			} elseif( $args['list'] == 'complimentary' ) {
				$strsql .= "FROM ciniki_customer_season_members, ciniki_customers "
					. "WHERE ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
					. "AND ciniki_customer_season_members.status = 10 "
					. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customer_season_members.customer_id = ciniki_customers.id "
					. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customers.membership_type = '110' "	// Complimentary Member
					. "AND ciniki_customers.member_status = 10 "		// Active member
					. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
					. "";
			} elseif( $args['list'] == 'reciprocal' ) {
				$strsql .= "FROM ciniki_customer_season_members, ciniki_customers "
					. "WHERE ciniki_customer_season_members.season_id = '" . ciniki_core_dbQuote($ciniki, $args['season_id']) . "' "
					. "AND ciniki_customer_season_members.status = 10 "
					. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customer_season_members.customer_id = ciniki_customers.id "
					. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_customers.membership_type = '150' "	// Reciprocal Member
					. "AND ciniki_customers.member_status = 10 "		// Active member
					. "AND ciniki_customers.membership_length < 60 "	// Not a lifetime member
					. "";
			} else {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2103', 'msg'=>'Invalid list type'));
			}
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artclub', array(
		array('container'=>'members', 'fname'=>'id', 'name'=>'member',
			'fields'=>array('id', 'first', 'last', 'display_name', 'company', 
				'member_status_text', 'member_lastpaid', 'member_lastpaid_age', 'membership_length_text', 
				'membership_type', 'membership_type_text', 
				'member_season_status', 'member_season_status_text', 'date_paid'),
			'maps'=>array(
				'member_status_text'=>$maps['customer']['member_status'],
				'membership_length_text'=>$maps['customer']['membership_length'],
				'membership_type_text'=>$maps['customer']['membership_type'],
				'member_season_status_text'=>$maps['season_member']['status'],
				),
			'utctotz'=>array('member_lastpaid'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['members']) ) {
		$rsp['members'] = $rc['members'];
	} 

	return $rsp;
}
?>
