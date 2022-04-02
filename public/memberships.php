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
function ciniki_customers_memberships($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
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
    $now = new DateTime('now', new DateTimezone($intl_timezone));
    $year_ago = clone $now;
    $year_ago->sub(new DateInterval('P1Y'));
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
    if( isset($args['category']) && $args['category'] == 'Uncategorized' ) {
        $strsql .= "FROM ciniki_customers "
            . "LEFT JOIN ciniki_customer_tags AS tags ON ("
                . "ciniki_customers.id = tags.customer_id "
                . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "AND ISNULL(tags.id) "
            . "ORDER BY sort_name, last, first, company"
            . "";
    } elseif( isset($args['category']) && $args['category'] != '' ) {
        $strsql .= "FROM ciniki_customer_tags "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_customer_tags.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_customers.member_status = 10 "
                . ") "
            . "WHERE ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customer_tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND ciniki_customer_tags.tag_type = '40' "
            . "ORDER BY sort_name, last, first, company"
            . "";
    } elseif( isset($args['category']) && $args['category'] == '' ) {
        $strsql .= "FROM ciniki_customers "
            . "LEFT JOIN ciniki_customer_tags ON ("
                . "ciniki_customers.id = ciniki_customer_tags.customer_id "
                . "AND ciniki_customer_tags.tag_type = '40' "
                . "AND ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "AND ISNULL(ciniki_customer_tags.tag_name) "
            . "ORDER BY sort_name, last, first, company"
            . "";
    } elseif( isset($args['type']) && $args['type'] == '-1' ) { // Expired
        $strsql .= "FROM ciniki_customers ";
        $strsql .= "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "AND ciniki_customers.membership_length < 60 "
            . "AND member_expires < '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
            . "ORDER BY sort_name, last, first, company"
            . "";
    } elseif( isset($args['type']) && $args['type'] != '' ) {
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) ) {
            $strsql .= "FROM ciniki_customers "
                . "INNER JOIN ciniki_customer_product_purchases AS purchases ON ("
                    . "ciniki_customers.id = purchases.customer_id "
                    . "AND (purchases.end_date = '0000-00-00' OR purchases.end_date > NOW() ) "
                    . "AND purchases.product_id = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' "
                    . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_customers.member_status = 10 "
                . "ORDER BY ciniki_customers.sort_name, last, first, company"
                . "";
        } else {
            $strsql .= "FROM ciniki_customers ";
            $strsql .= "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_customers.member_status = 10 "
                . "AND ciniki_customers.membership_type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' "
                . "ORDER BY sort_name, last, first, company"
                . "";
        } 
    } else {
        $strsql .= "FROM ciniki_customers ";
        $strsql .= "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "ORDER BY member_lastpaid DESC, sort_name, last, first, company "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customer', array(
        array('container'=>'members', 'fname'=>'id', 
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
    $rsp = array('stat'=>'ok', 'members'=>isset($rc['members']) ? $rc['members'] : array(), 'membertypes'=>array(), 'categories'=>array());


    //
    // Get the list of membership Types
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) ) {
        $strsql = "SELECT products.id, "
            . "products.short_name, "
            . "COUNT(DISTINCT purchases.customer_id) AS num_members "
            . "FROM ciniki_customer_products AS products "
            . "LEFT JOIN ciniki_customer_product_purchases AS purchases ON ("
                . "products.id = purchases.product_id "
                . "AND (purchases.end_date > NOW() OR purchases.end_date = '0000-00-00') "
                . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "purchases.customer_id = customers.id "
                . "AND customers.member_status = 10 "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE (products.type = 10 OR products.type = 20) "
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY products.id "
            . "ORDER BY products.type, products.sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'types', 'fname'=>'id', 
                'fields'=>array('membership_type'=>'id', 'name'=>'short_name', 'num_members'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.489', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $rsp['membertypes'] = isset($rc['types']) ? $rc['types'] : array();
    } else {
        $strsql = "SELECT membership_type, COUNT(id) AS num_members "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND member_status = 10 "
            . "GROUP BY membership_type "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'types', 'fname'=>'membership_type', 
                'fields'=>array('membership_type', 'name'=>'membership_type', 'num_members'),
                'maps'=>array('name'=>$maps['customer']['membership_type']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.415', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $rsp['membertypes'] = isset($rc['types']) ? $rc['types'] : array();
    }

    //
    // Get the list of add-ons
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) ) {
        $strsql = "SELECT products.id, "
            . "products.short_name, "
            . "COUNT(DISTINCT purchases.customer_id) AS num_members "
            . "FROM ciniki_customer_products AS products "
            . "LEFT JOIN ciniki_customer_product_purchases AS purchases ON ("
                . "products.id = purchases.product_id "
                . "AND (purchases.end_date > NOW() OR purchases.end_date = '0000-00-00') "
                . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "purchases.customer_id = customers.id "
                . "AND customers.member_status = 10 "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE products.type = 40 "
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY products.id "
            . "ORDER BY products.type, products.sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'types', 'fname'=>'id', 
                'fields'=>array('membership_type'=>'id', 'name'=>'short_name', 'num_members'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.511', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $rsp['memberaddons'] = isset($rc['types']) ? $rc['types'] : array();
    }
    //
    // Get the expired memberships
    //
    $strsql = "SELECT COUNT(id) AS num_members "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND member_status = 10 "
        . "AND membership_length < 60 "
        . "AND member_expires < '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
        . "GROUP BY membership_type "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.customers', 'expired');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.394', 'msg'=>'Unable to load expired members', 'err'=>$rc['err']));
    }
    if( isset($rc['expired']) ) {
        $rsp['membertypes'][] = array('membership_type'=>'-1', 'name'=>'Active Expired', 'num_members'=>$rc['expired']);
    }

    //
    // Get the list of Categories
    //
    $strsql = "SELECT IFNULL(ciniki_customer_tags.id, 0) AS id, "
        . "IFNULL(ciniki_customer_tags.tag_name, 'Uncategorized') AS tag_name, "
        . "IFNULL(ciniki_customer_tags.permalink, '') AS permalink, "
        . "COUNT(ciniki_customers.id) AS num_members "
        . "FROM ciniki_customers "
        . "LEFT JOIN ciniki_customer_tags ON ("
            . "ciniki_customers.id = ciniki_customer_tags.customer_id "
            . "AND ciniki_customer_tags.tag_type = '40' "
            . "AND ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customers.status < 50 "
        . "AND ciniki_customers.member_status = 10 "
        . "GROUP BY tag_name "
        . "ORDER BY tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'tags', 'fname'=>'id', 'fields'=>array('id', 'name'=>'tag_name', 'permalink', 'num_members')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.391', 'msg'=>'Unable to load tags', 'err'=>$rc['err']));
    }
    if( isset($rc['tags']) ) {
        $rsp['categories'] = $rc['tags'];
    }

    return $rsp;
}
?>
