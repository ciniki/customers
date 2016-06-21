<?php
//
// Description
// ===========
// This function will return the list of pricepoints for a business
//
// Arguments
// =========
// ciniki:
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_customers_pricepoints($ciniki, $business_id) {

    //
    // Get the sequences
    //
    $strsql = "SELECT id, sequence, name, code "
        . "FROM ciniki_customer_pricepoints "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "ORDER BY sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'pricepoints', 'fname'=>'id',
            'fields'=>array('id', 'sequence', 'name', 'code')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['pricepoints']) ) {
        return array('stat'=>'ok', 'pricepoints'=>$rc['pricepoints']);
    }
    return array('stat'=>'ok');
}
?>
