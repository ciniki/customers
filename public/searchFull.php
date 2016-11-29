<?php
//
// Description
// -----------
// The search return a list of results, with the most probable at the top.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to search for the customers.
// start_needle:        The search string to use.
// limit:               (optional) The maximum number of results to return.  If not
//                      specified, all results will be returned.
// 
// Returns
// -------
//
function ciniki_customers_searchFull($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.searchFull', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
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
    // Get the types of customers available for this business
    //
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
 //   $rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']); 
//  if( $rc['stat'] != 'ok' ) { 
//      return $rc;
//  }
//  $types = $rc['types'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    $strsql = "SELECT DISTINCT c1.id, "
        . "c1.display_name, "
        . "IFNULL(c2.display_name, '') AS parent_name, "
        . "c1.status, "
        . "c1.status AS status_text, "
        . "c1.type, "
        . "c1.company, "
        . "c1.eid ";
    $strsql .= "FROM ciniki_customers AS c1 "
        . "LEFT JOIN ciniki_customer_emails ON ("
            . "c1.id = ciniki_customer_emails.customer_id "
            . "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS c2 ON ("
            . "c1.parent_id = c2.id "
            . "AND c2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE c1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 ) {
        $strsql .= "AND c1.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
    }
    if( isset($args['type']) ) {
        if( $args['type'] == 'members' ) {
            $strsql .= "AND c1.member_status > 0 ";
        }
        elseif( $args['type'] == 'dealers' ) {
            $strsql .= "AND c1.dealer_status > 0 ";
        }
        elseif( $args['type'] == 'distributors' ) {
            $strsql .= "AND c1.distributor_status > 0 ";
        }
    }
    $strsql .= "AND (c1.first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.first LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.last LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.eid LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.company LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.company LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//          . "OR CONCAT_WS(' ', first, last) LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//          . "OR CONCAT_WS(' ', first, last) LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "ORDER BY c1.type DESC, c1.sort_name, c1.last, c1.first ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    }


    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'display_name', 'parent_name', 'status', 'status_text', 'type', 'company', 'eid'),
            'maps'=>array('status_text'=>$maps['customer']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        return array('stat'=>'ok', 'customers'=>array());
    }

    return array('stat'=>'ok', 'customers'=>$rc['customers']);
}
?>
