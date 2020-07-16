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
function ciniki_customers_cron_jobs(&$ciniki) {
    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for customers jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'reminderEmailSend');

    //
    // Check for any customer reminders that need to be sent
    //
    $strsql = "SELECT id, tnid "
        . "FROM ciniki_customer_reminders "
        . "WHERE (flags&0x03) = 0x01 "  // Email to be sent, but currently unsent
        . "AND email_next_dt <= UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.130', 'msg'=>'Unable to get the list of customer reminder emails', 'err'=>$rc['err']));
    }
    if( !isset($rc['rows']) ) {
        return array('stat'=>'ok');
    }
    $reminders = $rc['rows'];
    foreach($reminders as $reminder) {
        $rc = ciniki_customers_reminderEmailSend($ciniki, $reminder['tnid'], $reminder['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.405', 'msg'=>'Unable to send customer reminder', 'err'=>$rc['err']));
        }
    }

    //
    // Get the list of tenants that have customers enables and dropbox flag 
    //
    $strsql = "SELECT tnid "
        . "FROM ciniki_tenant_modules "
        . "WHERE package = 'ciniki' "
        . "AND module = 'customers' "
        . "AND (flags&0x0800000000) = 0x0800000000 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.1', 'msg'=>'Unable to get list of tenants with customer profiles', 'err'=>$rc['err']));
    }
    if( !isset($rc['rows']) ) {
        return array('stat'=>'ok');
    }
    $tenants = $rc['rows'];
    
    foreach($tenants as $tenant) {
        //
        // Load tenant modules
        //
        $rc = ciniki_tenants_checkModuleAccess($ciniki, $tenant['tnid'], 'ciniki', 'customers');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'ciniki.customers.214', 'msg'=>'ciniki.customers not configured', 
                'severity'=>30, 'err'=>$rc['err']));
            continue;
        }

        ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'0', 'msg'=>'Updating customers from dropbox', 'severity'=>'10'));

        //
        // Update the tenant customers from dropbox
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'dropboxDownload');
        $rc = ciniki_customers_dropboxDownload($ciniki, $tenant['tnid']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'ciniki.customers.215', 'msg'=>'Unable to update customers', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
    }

    return array('stat'=>'ok');
}
?>
