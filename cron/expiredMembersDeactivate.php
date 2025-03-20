<?php
//
// Description
// ===========
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_customers_cron_expiredMembersDeactivate(&$ciniki) {

    //
    // Get the list of tenants with members-deactivate-days set
    //
    $strsql = "SELECT tnid, detail_value AS days "
        . "FROM ciniki_customer_settings "
        . "WHERE detail_key = 'members-deactivate-days' "
        . "AND detail_value <> '' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.562', 'msg'=>'Unable to load tenants', 'err'=>$rc['err']));
    }
    $tenants = isset($rc['rows']) ? $rc['rows'] : array();
   
    foreach($tenants as $t) {
        //
        // Make sure valid number of days, 0 or greater
        //
        if( $t['days'] == '' || !is_numeric($t['days']) || $t['days'] < 0 ) {
            continue;
        }

        //
        // Load the tenant settings
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
        $rc = ciniki_tenants_intlSettings($ciniki, $t['tnid']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $intl_timezone = $rc['settings']['intl-default-timezone'];
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $dt->sub(new DateInterval('P' . intval($t['days']) . 'D'));

        //
        // Get the expired customers
        // 
        $strsql = "SELECT ciniki_customers.id, "
            . "ciniki_customers.member_status, "
            . "ciniki_customers.member_expires "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $t['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "AND ciniki_customers.membership_length < 60 "
            . "AND member_expires < '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
            . "ORDER BY sort_name, last, first, company"
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'members');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.563', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
        }
        $members = isset($rc['rows']) ? $rc['rows'] : array();
       
        foreach($members as $member) {
            //
            // Deactivate the member status
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $t['tnid'], 'ciniki.customers.customer', $member['id'], [
                'member_status' => 60,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.564', 'msg'=>'Unable to update the customer', 'err'=>$rc['err']));
            } 
        }
    }

    return array('stat'=>'ok');
}
?>
