<?php
//
// Description
// -----------
// This function will return the default customers permission tags 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_hooks_permissionTags(&$ciniki, $tnid, $args) {


    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $tnid, 'ciniki.customers', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.579', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    if( isset($settings['customers-permission-tags']) && $settings['customers-permission-tags'] != '' ) {
        return array('stat'=>'ok', 'tags'=>explode('::', $settings['customers-permission-tags']));
    }

    return array('stat'=>'ok');
}
?>
