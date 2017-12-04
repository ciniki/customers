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
function ciniki_customers_recent($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.recent', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    
    //
    // Get the types of customers available for this tenant
    //
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
//  $rc = ciniki_customers_getCustomerTypes($ciniki, $args['tnid']); 
//  if( $rc['stat'] != 'ok' ) { 
//      return $rc;
//  }
//  $types = $rc['types'];

    //
    // Get the number of customers in each status for the tenant, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT id, display_name, status, type, company, eid ";
//  if( count($types) > 0 ) {
//      $strsql .= "CASE type ";
//      foreach($types as $tid => $type) {
//          $strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN '" . ciniki_core_dbQuote($ciniki, $type['detail_value']) . "' ";
//      }
//      $strsql .= "ELSE 'person' END AS display_type ";
//  } else {
//      $strsql .= "'person' AS display_type ";
//  }
    $strsql .= "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status < 50 "
        . "ORDER BY last_updated DESC, last, first DESC ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    } else {
        $strsql .= "LIMIT 25 ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'customers', 'customer', array('stat'=>'ok', 'customers'=>array()));
}
?>
