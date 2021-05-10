<?php
//
// Description
// -----------
// This method searchs for a Log Entrys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Log Entry for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_customers_logSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.logSearch');
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
    // Get the list of logs
    //
    $strsql = "SELECT ciniki_customer_logs.id, "
        . "ciniki_customer_logs.log_date, "
        . "ciniki_customer_logs.status, "
        . "ciniki_customer_logs.status AS status_text, "
        . "ciniki_customer_logs.ip_address, "
        . "ciniki_customer_logs.action, "
        . "ciniki_customer_logs.customer_id, "
        . "ciniki_customer_logs.email, "
        . "ciniki_customer_logs.error_code, "
        . "ciniki_customer_logs.error_msg "
        . "FROM ciniki_customer_logs "
        . "WHERE ciniki_customer_logs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "email LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR email LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "ORDER BY log_date DESC "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 35 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'logs', 'fname'=>'id', 
            'fields'=>array('id', 'log_date', 'status', 'status_text', 'ip_address', 'action', 'customer_id', 'email', 
                'error_code', 'error_msg'),
            'maps'=>array('status_text'=>$maps['log']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $logs = isset($rc['logs']) ? $rc['logs'] : array();

    return array('stat'=>'ok', 'logs'=>$logs);
}
?>
