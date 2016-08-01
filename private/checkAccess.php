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
// business_id:         The ID of the business the request is for.
// method:              The method requested.
// req_id:              The ID of the customer or ID of the relationship for the 
//                      method, or 0 if no customer or relationship specified.
// 
// Returns
// -------
//
function ciniki_customers_checkAccess(&$ciniki, $business_id, $method, $req_id) {
    //
    // Check if the business is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
    $rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'690', 'msg'=>'No permissions granted'));
    }
    $modules = $rc['modules'];

    //
    // Check if the business is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'getUserPermissions');
    $rc = ciniki_businesses_getUserPermissions($ciniki, $business_id);
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
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'114', 'msg'=>'Access denied'));
    }

    //
    // Check the session user is a business owner
    //
    if( $business_id <= 0 ) {
        // If no business_id specified, then fail
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2256', 'msg'=>'Access denied'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    // 
    // Resellers, Owners and Employees have access to everything
    //
    if( ($ciniki['business']['user']['perms']&0x103) > 0 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // Check for Ciniki Robot user
    //
    if( $ciniki['session']['user']['id'] == -3 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>0);
    }

    //
    // If the user is part of the salesreps, ensure they have access to request method
    //
    $salesreps_methods = array(
        'ciniki.customers.overview',
        'ciniki.customers.placeDetails',
        'ciniki.customers.getModuleData',
        'ciniki.customers.searchQuick',
        'ciniki.customers.searchFull',
        );
    if( in_array($method, $salesreps_methods) && ($perms&0x04) == 0x04 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // By default, deny access
    //
    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2234', 'msg'=>'Access denied'));

}
?>
