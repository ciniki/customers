<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get dealers for.
// type:            The type of participants to get.  Refer to participantAdd for 
//                  more information on types.
//
// Returns
// -------
//
function ciniki_customers_dealerCategories($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.dealerCategories', 0);
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

    //
    // Build the query to get the tags
    //
    $strsql = "SELECT IFNULL(ciniki_customer_tags.tag_name, 'Uncategorized') AS tag_name, "
        . "IFNULL(ciniki_customer_tags.permalink, '') AS permalink, "
        . "COUNT(ciniki_customers.id) AS num_dealers "
        . "FROM ciniki_customers "
        . "LEFT JOIN ciniki_customer_tags ON ("
            . "ciniki_customers.id = ciniki_customer_tags.customer_id "
            . "AND ciniki_customer_tags.tag_type = '60' "
            . "AND ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customers.status < 50 "
        . "AND ciniki_customers.dealer_status = 10 "
        . "GROUP BY tag_name "
        . "ORDER BY tag_name "
        . "";
    //
    // Get the list of posts, sorted by publish_date for use in the web CI List Categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'categories', 'fname'=>'permalink', 'name'=>'category',
            'fields'=>array('name'=>'tag_name', 'permalink', 'num_dealers')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['categories']) ) {
        return array('stat'=>'ok', 'categories'=>$rc['categories']);
    }

    return array('stat'=>'ok', 'categories'=>array());
}
?>
