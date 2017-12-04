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
function ciniki_customers_customerSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field',
            'validlist'=>array('eid', 'name', 'first', 'last', 'company', 'display_name')), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerSearch', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

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
    $strsql = "SELECT DISTINCT ciniki_customers.id, eid, display_name, status, type, company, eid ";
    $strsql .= "FROM ciniki_customers "
        . "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id) "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customers.status < 50 "
        . "AND (" . $args['field'] . " LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR " . $args['field'] . " LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "ORDER BY last, first DESC ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    } else {
        $strsql .= "LIMIT 25 ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'customers', 'customer', array('stat'=>'ok', 'customers'=>array()));
}
?>
