<?php
//
// Description
// -----------
// This method will retrieve the list of uploaded excel files uploaded to automerge.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The tenant ID to get the excel files uploaded to the customers automerge.
// 
// Returns
// -------
// <files>
//      <excel id="3" name="Temp.xls" source_name="Temp.xls" date_added="2011-01-08 12:59:00" />
// </files>
//
function ciniki_customers_automergeList($ciniki) {
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
    // Check access to tnid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.automergeList', 0);
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Load the excel information
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    $strsql = "SELECT id, name, source_name, cur_review_row, "
        . "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added "
        . "FROM ciniki_customer_automerges "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status = 10 "    // The file has been uploaded and parsed into the database
        . "";
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.customers', 'files', 'excel', array('stat'=>'ok', 'files'=>array()));
}
?>
