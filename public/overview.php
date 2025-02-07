<?php
//
// Description
// -----------
// Return the list of customer who have been recently updated
//
// Arguments
// ---------
// user_id:         The user making the request
// search_str:      The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_customers_overview($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.overview', 0); 
    if( $rc['stat'] != 'ok' && $rc['stat'] != 'restricted' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];
    $perms = $rc['perms'];

    $rsp = array('stat'=>'ok', 'recent'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    //
    // Get the places and customer counts
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'locationStats');
    $rc = ciniki_customers__locationStats($ciniki, $args['tnid'], array('start_level'=>'country'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['places']) ) {
        $rsp['places'] = $rc['places'];
        $rsp['place_level'] = $rc['place_level'];
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
    // Get the list of categories if specified
    //
    if( ($modules['ciniki.customers']['flags']&0xC00000) > 0 ) {
        $strsql = "SELECT tag_type, tag_name, COUNT(customer_id) AS num_customers "
            . "FROM ciniki_customer_tags "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY tag_type, tag_name "
            . "ORDER BY tag_type, tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'types', 'fname'=>'tag_type', 'fields'=>array('tag_type')),
            array('container'=>'tags', 'fname'=>'tag_name', 'fields'=>array('name'=>'tag_name', 'num_customers')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['types']) ) {
            foreach($rc['types'] as $tid => $tag_type) {
                if( $tag_type['tag_type'] == 10 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x400000) ) { 
                    $rsp['customer_categories'] = $tag_type['tags'];
                } elseif( $tag_type['tag_type'] == 20 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x800000) ) { 
                    $rsp['customer_tags'] = $tag_type['tags'];
                }
            }
        }
        $strsql = "SELECT COUNT(*) AS num FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.customers', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.558', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        $num_items = isset($rc['num']) ? $rc['num'] : '';

        array_unshift($rsp['customer_categories'], ['name'=>'All', 'num_customers'=>$num_items]);
    }

    if( isset($args['category']) && $args['category'] == 'All' ) {
        $strsql = "SELECT customers.id, "
            . "customers.display_name, "
            . "customers.status, "
            . "customers.status AS status_text, "
            . "customers.type, "
            . "customers.company, "
            . "customers.eid "
            . "FROM ciniki_customers AS customers "
            . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY display_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
                'fields'=>array('id', 'display_name', 'status', 'status_text', 'type', 'company', 'eid'),
                'maps'=>array('status_text'=>$maps['customer']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) ) { 
            $rsp['customers'] = $rc['customers'];
        }
    } elseif( isset($args['category']) && $args['category'] != '' ) {
        $strsql = "SELECT customers.id, "
            . "customers.display_name, "
            . "customers.status, "
            . "customers.status AS status_text, "
            . "customers.type, "
            . "customers.company, "
            . "customers.eid "
            . "FROM ciniki_customer_tags AS tags "
            . "JOIN ciniki_customers AS customers ON ("
                . "tags.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE tags.tag_type = 10 "
            . "AND tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY display_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
                'fields'=>array('id', 'display_name', 'status', 'status_text', 'type', 'company', 'eid'),
                'maps'=>array('status_text'=>$maps['customer']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) ) { 
            $rsp['customers'] = $rc['customers'];
        }
    } else {

        //
        // Get the recently updated customers
        //
        $strsql = "SELECT id, display_name, status, type, company, eid "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND status < 50 "
            . "";
        $strsql .= "ORDER BY last_updated DESC, last, first DESC ";
        if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
            $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
        } else {
            $strsql .= "LIMIT 25 ";
        }

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
                'fields'=>array('id', 'display_name', 'status', 'type', 'company', 'eid')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) ) { 
            $rsp['recent'] = $rc['customers'];
        }
    }

    return $rsp;
}
?>
