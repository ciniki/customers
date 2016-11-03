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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'dropboxDownload');

    //
    // Get the list of businesses that have customers enables and dropbox flag 
    //
    $strsql = "SELECT business_id "
        . "FROM ciniki_business_modules "
        . "WHERE package = 'ciniki' "
        . "AND module = 'customers' "
        . "AND (flags&0x0800000000) = 0x0800000000 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.1', 'msg'=>'Unable to get list of businesses with customer profiles', 'err'=>$rc['err']));
    }
    if( !isset($rc['rows']) ) {
        return array('stat'=>'ok');
    }
    $businesses = $rc['rows'];
    
    foreach($businesses as $business) {
        //
        // Load business modules
        //
        $rc = ciniki_businesses_checkModuleAccess($ciniki, $business['business_id'], 'ciniki', 'customers');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'ciniki.customers.214', 'msg'=>'ciniki.customers not configured', 
                'severity'=>30, 'err'=>$rc['err']));
            continue;
        }

        ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'0', 'msg'=>'Updating customers from dropbox', 'severity'=>'10'));

        //
        // Update the business customers from dropbox
        //
        $rc = ciniki_customers_dropboxDownload($ciniki, $business['business_id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'ciniki.customers.215', 'msg'=>'Unable to update customers', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
    }

    return array('stat'=>'ok');
}
?>
