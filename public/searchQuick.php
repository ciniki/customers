<?php
//
// Description
// -----------
// Search customers by name
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to search for the customers.
// start_needle:        The search string to use.
// limit:               (optional) The maximum number of results to return.  If not
//                      specified, the maximum results will be 25.
// 
// Returns
// -------
//
function ciniki_customers_searchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
        'parent_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Parent'), 
        'member_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Members'), 
        'member_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Members'), 
        'dealers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Dealers'), 
        'dealer_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Dealers'), 
        'distributors'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Dealers'), 
        'distributor_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search Distributors'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.searchQuick', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

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
    // Get the types of customers available for this tenant
    //
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
 //   $rc = ciniki_customers_getCustomerTypes($ciniki, $args['tnid']); 
//  if( $rc['stat'] != 'ok' ) { 
//      return $rc;
//  }
//  $types = $rc['types'];

    //
    // Get the number of customers in each status for the tenant, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT DISTINCT c1.id, c1.display_name, IFNULL(c2.display_name, '') AS parent_name, "
        . "c1.parent_id, "
        . "c1.status, "
        . "c1.status AS status_text, "
        . "c1.type, "
        . "c1.company, "
        . "c1.eid, "
        . "IFNULL(DATE_FORMAT(c1.member_lastpaid, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS member_lastpaid, "
        . "c1.member_status, "
        . "c1.membership_type, "
        . "c1.membership_type AS membership_type_text, "
        . "c1.membership_length, "
        . "c1.membership_length AS membership_length_text "
        . "";
//  if( count($types) > 0 ) {
//      // If there are customer types defined, choose the right name for the customer
//      // This is required here to be able to sort properly
//      $strsql .= "CASE ciniki_customers.type ";
//      foreach($types as $tid => $type) {
//          $strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN ";
//          if( $type['detail_value'] == 'tenant' ) {
//              $strsql .= " ciniki_customers.company ";
//          } else {
//              $strsql .= "CONCAT_WS(' ', first, last) ";
//          }
//      }
//      $strsql .= "ELSE CONCAT_WS(' ', first, last) END AS name ";
//  } else {
//      // Default to a person
//      $strsql .= "CONCAT_WS(' ', first, last) AS name ";
//  }
    $strsql .= "FROM ciniki_customers AS c1 "
        . "LEFT JOIN ciniki_customer_emails ON ("
            . "c1.id = ciniki_customer_emails.customer_id "
            . "AND ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS c2 ON ("
            . "c1.parent_id = c2.id "
            . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND c1.status < 60 ";
    // Check if only a sales rep
    if( isset($ciniki['tenant']['user']['perms']) && ($ciniki['tenant']['user']['perms']&0x07) == 0x04 ) {
        $strsql .= "AND c1.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
    }
    if( isset($args['parent_id']) && $args['parent_id'] != '' ) {
        $strsql .= "AND c1.parent_id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' ";
    }
    if( isset($args['member_status']) && $args['member_status'] != '' ) {
        $strsql .= "AND c1.member_status = '" . ciniki_core_dbQuote($ciniki, $args['member_status']) . "' ";
    }
    if( isset($args['dealer_status']) && $args['dealer_status'] != '' ) {
        $strsql .= "AND c1.dealer_status = '" . ciniki_core_dbQuote($ciniki, $args['dealer_status']) . "' ";
    }
    if( isset($args['dealers']) && $args['dealers'] == 'yes' ) {
        $strsql .= "AND c1.dealer_status > 0 ";
    }
    if( isset($args['distributor_status']) && $args['distributor_status']   != '' ) {
        $strsql .= "AND c1.distributor_status = '" . ciniki_core_dbQuote($ciniki, $args['distributor_status']) . "' ";
    }
    if( isset($args['distributors']) && $args['distributors'] == 'yes' ) {
        $strsql .= "AND c1.distributor_status > 0 ";
    }
    $args['start_needle'] = preg_replace("/([^\s]) ([^\s])/", '$1%$2', $args['start_needle']);
    $strsql .= "AND (c1.first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.first LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.last LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.eid LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.company LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR c1.company LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//          . "OR CONCAT_WS(' ', c1.first, c1.last) LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//          . "OR CONCAT_WS(' ', c1.first, c1.last) LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "ORDER BY c1.type DESC, c1.sort_name, c1.last, c1.first DESC ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    } else {
        $strsql .= "LIMIT 25 ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'eid', 'display_name', 'parent_name', 'status', 'status_text',
                'type', 'company', 'member_lastpaid', 'member_status', 
                'membership_type', 'membership_type_text', 'membership_length', 'membership_length_text'),
            'maps'=>array('status_text'=>$maps['customer']['status'],
                'membership_type_text'=>$maps['customer']['membership_type'],
                'member_length_text'=>$maps['customer']['membership_length'],
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    return $rc;
}
?>
