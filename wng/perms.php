<?php
//
// Description
// -----------
// Check if customer permissions are enabled, then load the permissions to the logged in customer
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_customers_wng_perms(&$ciniki, $tnid, &$request, $args) {

    //
    // Check customer perms is enabled for tenant
    //
    if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x1000) ) {
        return array('stat'=>'ok');
    }

    //
    // If customer perms is enabled, then make sure the default customer_perms is set to (customer_perms = '') so it only matches full access perms
    // When (customer_perms = '') then it is the most restrictive
    // Add the customer perms for the logged in customer to grant additional access
    //
    $perms = [];
    if( isset($args['sql_field']) ) {
        $sql_field = $args['sql_field'];
    } else {
        $sql_field = 'customer_perms';
    }
    $perms_sql = '(' . ciniki_core_dbQuote($ciniki, $sql_field) . " = ''";

    //
    // Check if account flag set, then redirect to authAccount
    //
    if( isset($request['session']['customer']['id']) ) {
        $strsql = "SELECT tag_name "
            . "FROM ciniki_customer_tags AS tags "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND tag_type = 30 "
            . "ORDER BY tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.customers', 'perms', 'tag_name');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.580', 'msg'=>'Unable to load the customer permissions', 'err'=>$rc['err']));
        }
        $perms = isset($rc['perms']) ? $rc['perms'] : [];

        foreach($perms as $perm) {
            $perms_sql .= ' || ' . ciniki_core_dbQuote($ciniki, $sql_field) . " LIKE '%::" . ciniki_core_dbQuote($ciniki, $perm) . "::%'";
        }
    }

    $perms_sql .= ') ';

    return array('stat'=>'ok', 'perms'=>$perms, 'perms_sql'=>$perms_sql);
}
?>
