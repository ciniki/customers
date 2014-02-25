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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	
	//
	// Load the list of members for a business
	//
	$strsql = "SELECT ciniki_customers.id, "
		. "ciniki_customers.display_name, "
		. "ciniki_customers.company, "
		. "ciniki_customers.phone_home, "
		. "ciniki_customers.phone_work, "
		. "ciniki_customers.phone_cell, "
		. "ciniki_customers.phone_fax "
		. "FROM ciniki_customers "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.member_status = 10 "
		. "ORDER BY last, first, company";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artclub', array(
		array('container'=>'members', 'fname'=>'id', 'name'=>'member',
			'fields'=>array('id', 'display_name', 'company', 
				'phone_home', 'phone_work', 'phone_cell', 'phone_fax')),
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
