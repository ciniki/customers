<?php
//
// Description
// -----------
// This function will remove all entries for an automerge file.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// automerge_id:            The excel spread ID that was uploaded to ciniki_customer_automerges table.
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_customers_automergeDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'automerge_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Automerge Customer'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.automergeDelete', $args['automerge_id']);
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Delete the file from the list
    //
    $strsql = "DELETE FROM ciniki_customer_automerges "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }

    //
    // Delete all data for a row
    //
    $strsql = "DELETE FROM ciniki_customer_automerge_data "
        . "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }

    //
    // Commit the changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'customers');

    return array('stat'=>'ok');
}
?>
