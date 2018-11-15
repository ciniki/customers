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
            'validlist'=>array('eid', 'name', 'first', 'last', 'company', 'display_name', 'family', 'business')), 
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
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        $strsql = "SELECT DISTINCT c1.id, if(c1.type=20 OR c1.type = 30, '', c1.display_name) AS display_name, "
            . "IFNULL(c2.display_name, if(c1.type=20 OR c1.type = 30, c1.display_name, '')) AS parent_name, "
            . "c1.parent_id, "
            . "c1.status, "
            . "c1.status AS status_text, "
            . "c1.type, "
            . "c1.type AS type_text, "
            . "c1.company, "
            . "c1.eid "
            . "FROM ciniki_customers AS c1 "
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
        if( isset($args['parent_id']) && $args['parent_id'] != '' ) {
            $strsql .= "AND c1.parent_id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' ";
        }
        $args['start_needle'] = preg_replace("/([^\s]) ([^\s])/", '$1%$2', $args['start_needle']);
        if( isset($args['field']) && $args['field'] == 'family' ) {
            $strsql .= "AND (c1.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c1.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . ") "
                . "AND c1.type = 20 ";
        } elseif( isset($args['field']) && $args['field'] == 'business' ) {
            error_log('business');
            $strsql .= "AND (c1.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c1.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . ") "
                . "AND c1.type = 30 ";
        } else {
            $strsql .= "AND (c1.first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c1.first LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c1.last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c1.last LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c1.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c1.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c1.eid LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c2.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR c2.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR email LIKE '%@" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . ") ";
        }
        $strsql .= "ORDER BY c1.type DESC, c1.sort_name, c1.last, c1.first DESC ";
        if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
            $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
        } else {
            $strsql .= "LIMIT 25 ";
        }
error_log($strsql);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id',
                'fields'=>array('id', 'eid', 'display_name', 'parent_name', 'status', 'status_text',
                    'type', 'type_text', 'company', 
                    ),
                'maps'=>array('status_text'=>$maps['customer']['status'],
                    'type_text'=>$maps['customer']['type'],
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        return $rc;
    } 

    //
    // The default search
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
