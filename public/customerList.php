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
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status',
            'validlist'=>array('10','40','50','60')),
        'latest'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Latest'),
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
    $strsql = "SELECT DISTINCT customers.id, "
        . "customers.parent_id, "
        . "customers.eid, "
        . "customers.type, "
        . "customers.type AS type_text, "
        . "customers.display_name, "
        . "customers.status, "
        . "customers.status AS status_text, "
        . "customers.company, "
        . "IFNULL(parents.display_name, '') AS parent_name "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customers AS parents ON ("
            . "customers.parent_id > 0 "
            . "AND customers.parent_id = parents.id "
            . "AND parents.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "AND customers.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['latest']) && $args['latest'] == 'yes' ) {
        $strsql .= "ORDER BY customers.last_updated DESC, customers.last, customers.first, customers.company  ";
    } else {
        $strsql .= "ORDER BY status, customers.last, customers.first DESC ";
    }
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'parent_id', 'eid', 'display_name', 'status', 'status_text', 'type', 'company', 'parent_name'),
            'maps'=>array(
                'status_text'=>$maps['customer']['status'],
                'type_text'=>$maps['customer']['type'],
                )),
            ));
    return $rc;
}
?>
