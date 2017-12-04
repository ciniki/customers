<?php
//
// Description
// -----------
// This function will add a new customer given a hash of keys.
//
// Info
// ----
// Status: started
//
// Arguments
// ---------
// ciniki:
//
function ciniki_customers_addFromHash($ciniki, $customer) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashToSQL');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    $rc = ciniki_core_dbHashToSQL($ciniki, 
        array('prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title'),
        $customer,
        'INSERT INTO ciniki_customers (tnid, status, ',
        'date_added, last_updated) VALUES ('
        'UTC_TIMESTAMP(), UTC_TIMESTAMP())');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['strsql'] != '') {
        $new_customer = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.32', 'msg'=>'Internal error', 'pmsg'=>'Unable to build SQL insert string'));
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'customers');

    return array('stat'=>'ok', 'id'=>$rc['insert_id']);
}
?>
