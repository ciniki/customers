<?php
//
// Description
// -----------
// This function will return info about the file and the stats
//
// Arguments
// ---------
// api_key:
// auth_token:      
// tnid:         The tenant ID the excel file is connected to.
// automerge_id:            The excel ID from the table ciniki_toolbox_excel.
//
// Returns
// -------
// <stats rows=0 matches=0 reviewed=0 deleted=0 />
//
function ciniki_customers_automergeStats($ciniki) {
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
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.automergeStats', $args['automerge_id']);
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    $stats = array(
        'rows'=>0,
        'conflicts'=>0,
        'merged'=>0,
        );

    //
    // Get the number of rows in data
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $strsql = "SELECT 'rows', COUNT(DISTINCT row) "
        . "FROM ciniki_customer_automerge_data "
        . "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
        // . "GROUP BY status "
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'excel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $stats['rows'] = $rc['excel']['rows'];

    //
    // Get the number of rows with conflicts
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $strsql = "SELECT 'rows', COUNT(DISTINCT row) "
        . "FROM ciniki_customer_automerge_data "
        . "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
        . "GROUP BY status "
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'excel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['excel']) ) {
        if( isset($rc['excel']['rows']) ) {
            $stats['conflicts'] = $rc['excel']['rows'];
        }
    }

    //
    // Get the number of columns
    //
    $strsql = "SELECT 'cols', COUNT(col) FROM ciniki_customer_automerge_data "
        . "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
        . "AND row = 1"
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['num']) || !isset($rc['num']['cols']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.58', 'msg'=>'Unable to gather statistics'));
    }
    $num_cols = $rc['num']['cols'];

    //
    // Get the number of rows merged
    //
    $strsql = "SELECT 'rows', COUNT(*) AS num_rows "
        . "FROM ("
            . "SELECT row, COUNT(status) FROM ciniki_customer_automerge_data "
            . "WHERE automerge_id = '" . ciniki_core_dbQuote($ciniki, $args['automerge_id']) . "' "
            . "AND status >= 60 "
            . "GROUP BY row HAVING COUNT(status) = '" . ciniki_core_dbQuote($ciniki, $num_cols) . "'"
            . ") AS sb"
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'excel');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['excel']) ) {
        if( isset($rc['excel']['rows']) ) {
            $stats['merged'] = $rc['excel']['rows'];
        }
    }

    return array('stat'=>'ok', 'stats'=>$stats);
}
?>
