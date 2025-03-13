<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get members for.
//
// Returns
// -------
//
function ciniki_customers_membersExpiredDeactivate($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.membersExpiredDeactivate', 0);
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
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
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the list of Active Expired
    //
    $now = new DateTime('now', new DateTimezone($intl_timezone));
    $strsql = "SELECT members.id, "
        . "members.uuid, "
        . "members.member_status "
        . "FROM ciniki_customers AS members "
        . "WHERE members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND members.member_status = 10 "
        . "AND members.membership_length < 60 "
        . "AND member_expires < '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.560', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $members = isset($rc['rows']) ? $rc['rows'] : array();
    
    //
    // Deactive members
    //
    foreach($members as $member) {
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $member['id'], ['member_status'=>60], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.561', 'msg'=>'Unable to update the member', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
