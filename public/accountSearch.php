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
function ciniki_customers_accountSearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status', 'validlist'=>array('10','40','50','60')),
        'type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type', 'validlist'=>array('individuals','businesses','families')),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.accountSearch'); 
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
    $strsql = "SELECT DISTINCT IFNULL(parents.id, customers.id) AS id, "
        . "IFNULL(parents.type, customers.type) AS type, "
        . "IFNULL(parents.type, customers.type) AS type_text, "
        . "IFNULL(parents.display_name, customers.display_name) AS display_name, "
        . "IFNULL(parents.sort_name, customers.sort_name) AS sort_name, "
        . "IFNULL(parents.status, customers.status) AS status, "
        . "IFNULL(parents.status, customers.status) AS status_text "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS parents ON ("
            . "customers.parent_id > 0 "
            . "AND customers.parent_id = parents.id "
            . "AND parents.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "customers.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR parents.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR parents.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR emails.email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR emails.email LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR emails.email LIKE '%@" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "";
    if( isset($args['type']) && $args['type'] != '' ) {
        $strsql .= "AND customers.parent_id = 0 ";
        if( $args['type'] == 'individuals' ) {
            $strsql .= "AND (customers.type = 10 || parents.type = 10) ";
        } elseif( $args['type'] == 'families' ) {
            $strsql .= "AND (customers.type = 20 || parents.type = 20) ";
        } elseif( $args['type'] == 'businesses' ) {
            $strsql .= "AND (customers.type = 30 || parents.type = 30) ";
        }
    }
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "AND status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    $strsql .= "ORDER BY sort_name ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'accounts', 'fname'=>'id', 
            'fields'=>array('id', 'type', 'type_text', 'status', 'status_text', 'display_name'),
            'maps'=>array(
                'status_text'=>$maps['customer']['status'],
                'type_text'=>$maps['customer']['type'],
                )),
            ));
    return $rc;
}
?>
