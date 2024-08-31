<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get members for.
// type:            The type of participants to get.  Refer to participantAdd for 
//                  more information on types.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.memberList', 0);
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

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
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $mysql_date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load the list of members for a tenant
    //
    $strsql = "SELECT ciniki_customers.id, "
        . "ciniki_customers.first, "
        . "ciniki_customers.last, "
        . "ciniki_customers.display_name, "
        . "ciniki_customers.member_status AS member_status_text, "
        . "ciniki_customers.member_lastpaid, "
        . "ciniki_customers.member_expires, "
        . "DATEDIFF(NOW(), ciniki_customers.member_lastpaid) AS member_lastpaid_age, "
        . "DATEDIFF(NOW(), ciniki_customers.member_expires) AS member_expires_age, "
        . "ciniki_customers.membership_length AS membership_length_text, "
        . "ciniki_customers.membership_type, "
        . "ciniki_customers.membership_type AS membership_type_text, "
        . "ciniki_customers.company ";
    if( isset($args['category']) && $args['category'] != '' ) {
        $strsql .= "FROM ciniki_customer_tags "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_customer_tags.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_customers.member_status = 10 "
                . ") ";
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x02000000) > 0 ) {
            $strsql .= "LEFT JOIN ciniki_customer_season_members ON ("
                . "ciniki_customers.id = ciniki_customer_season_members.customer_id "
                . "AND ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        }
        $strsql .= "WHERE ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customer_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND ciniki_customer_tags.tag_type = '40' "
            . "ORDER BY sort_name, last, first, company"
            . "";
    } elseif( isset($args['category']) && $args['category'] == '' ) {
//      $strsql = "SELECT ciniki_customers.id, "
//          . "ciniki_customers.first, "
//          . "ciniki_customers.last, "
//          . "ciniki_customers.display_name, "
//          . "ciniki_customers.member_status AS member_status_text, "
//          . "ciniki_customers.member_lastpaid, "
//          . "DATEDIFF(NOW(), ciniki_customers.member_lastpaid) AS member_lastpaid_age, "
//          . "ciniki_customers.membership_length AS membership_length_text, "
//          . "ciniki_customers.membership_type, "
//          . "ciniki_customers.membership_type AS membership_type_text, "
//          . "ciniki_customers.company "
        $strsql .= "FROM ciniki_customers "
            . "LEFT JOIN ciniki_customer_tags ON ("
                . "ciniki_customers.id = ciniki_customer_tags.customer_id "
                . "AND ciniki_customer_tags.tag_type = '40' "
                . "AND ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x02000000) > 0 ) {
            $strsql .= "LEFT JOIN ciniki_customer_season_members ON ("
                . "ciniki_customers.id = ciniki_customer_season_members.customer_id "
                . "AND ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        }
        $strsql .="WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "AND ISNULL(ciniki_customer_tags.tag_name) "
            . "ORDER BY sort_name, last, first, company"
            . "";
    } else {
//      $strsql = "SELECT ciniki_customers.id, "
//          . "ciniki_customers.first, "
//          . "ciniki_customers.last, "
//          . "ciniki_customers.display_name, "
//          . "ciniki_customers.member_status AS member_status_text, "
//          . "ciniki_customers.member_lastpaid, "
//          . "DATEDIFF(NOW(), ciniki_customers.member_lastpaid) AS member_lastpaid_age, "
//          . "ciniki_customers.membership_length AS membership_length_text, "
//          . "ciniki_customers.membership_type, "
//          . "ciniki_customers.membership_type AS membership_type_text, "
//          . "ciniki_customers.company "
        $strsql .= "FROM ciniki_customers ";
        $strsql .= "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "ORDER BY sort_name, last, first, company"
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customer', array(
        array('container'=>'members', 'fname'=>'id', 'name'=>'member',
            'fields'=>array('id', 'first', 'last', 'display_name', 'company', 
                'member_status_text', 
                'member_lastpaid', 'member_lastpaid_age', 
                'member_expires', 'member_expires_age', 
                'membership_length_text', 
                'membership_type', 'membership_type_text'),
            'maps'=>array(
                'member_status_text'=>$maps['customer']['member_status'],
                'membership_length_text'=>$maps['customer']['membership_length'],
                'membership_type_text'=>$maps['customer']['membership_type'],
                ),
            'utctotz'=>array(
                'member_lastpaid'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'member_expires'=>array('timezone'=>'UTC', 'format'=>$date_format),
                ), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp = array('stat'=>'ok', 'members'=>array());
    if( isset($rc['members']) ) {
        $rsp['members'] = $rc['members'];

        //
        // Get the seasons if enabled for the last_paid date
        //
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x02000000) > 0 ) {
            $strsql = "SELECT ciniki_customer_season_members.customer_id, "
                . "ciniki_customer_seasons.name, "
                . "ciniki_customer_season_members.status AS status_text, "
                . "DATE_FORMAT(ciniki_customer_season_members.date_paid, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS date_paid "
                . "FROM ciniki_customer_season_members, ciniki_customer_seasons "
                . "WHERE ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_customer_season_members.status > 0 "
                . "AND ciniki_customer_season_members.season_id = ciniki_customer_seasons.id "
                . "AND ciniki_customer_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY ciniki_customer_season_members.customer_id, ciniki_customer_seasons.start_date DESC "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
                array('container'=>'customers', 'fname'=>'customer_id', 
                    'fields'=>array('customer_id', 'name', 'status_text', 'date_paid'),
                    'maps'=>array('status_text'=>$maps['season_member']['status'])),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customers']) ) {
                $customers = $rc['customers'];
                foreach($rsp['members'] as $mid => $member) {
                    if( isset($customers[$member['member']['id']]) ) {
                        $rsp['members'][$mid]['member']['season_name'] = $customers[$member['member']['id']]['name'];
                        $rsp['members'][$mid]['member']['season_status_text'] = $customers[$member['member']['id']]['status_text'];
                        $rsp['members'][$mid]['member']['season_date_paid'] = $customers[$member['member']['id']]['date_paid'];
                    }
                }
            }
        }
    } 

    return $rsp;
}
?>
