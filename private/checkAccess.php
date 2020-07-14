<?php
//
// Description
// -----------
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant the request is for.
// method:              The method requested.
// req_id:              The ID of the customer or ID of the relationship for the 
//                      method, or 0 if no customer or relationship specified.
// 
// Returns
// -------
//
function ciniki_customers_checkAccess(&$ciniki, $tnid, $method, $req_id=0) {
    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.33', 'msg'=>'No permissions granted'));
    }
    $modules = $rc['modules'];

    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'getUserPermissions');
    $rc = ciniki_tenants_getUserPermissions($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $perms = $rc['perms'];

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms']&0x01) == 0x01 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // Only sysadmins should have access to fix the history
    //
    if( $method == 'ciniki.customers.historyFix' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.34', 'msg'=>'Access denied'));
    }

    //
    // Check the session user is a tenant owner
    //
    if( $tnid <= 0 ) {
        // If no tnid specified, then fail
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.35', 'msg'=>'Access denied'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    // 
    // Resellers, Owners and Employees have access to everything
    //
    if( ($ciniki['tenant']['user']['perms']&0x103) > 0 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // Check for Ciniki Robot user
    //
    if( $ciniki['session']['user']['id'] == -3 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>0);
    }

    //
    // By default, deny access
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.36', 'msg'=>'Access denied'));

}
?>
