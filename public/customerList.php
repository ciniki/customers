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
function ciniki_customers_customerList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status',
            'validlist'=>array('10', '40','50','60')),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerList', 0); 
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
    // Get the number of customers in each status for the tenant, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT DISTINCT ciniki_customers.id, ciniki_customers.parent_id, eid, display_name, "
        . "status, status AS status_text, "
        . "type, company ";
    $strsql .= "FROM ciniki_customers "
        . "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id) "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customers.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' "
        . "ORDER BY last, first DESC ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'parent_id', 'eid', 'display_name', 'status', 'status_text', 'type', 'company'),
            'maps'=>array('status_text'=>$maps['customer']['status'])),
            ));
    return $rc;
}
?>
