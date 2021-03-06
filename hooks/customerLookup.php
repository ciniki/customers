<?php
//
// Description
// -----------
// This function will lookup a customer based on an email address.
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_hooks_customerLookup($ciniki, $tnid, $args) {

    if( !isset($args['email']) || $args['email'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.17', 'msg'=>'No customer email specified'));
    }

    $strsql = "SELECT customer_id, email "
        . "FROM ciniki_customer_emails "
        . "WHERE email = '" . ciniki_core_dbQuote($ciniki, $args['email']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('id'=>'customer_id', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        return $rc;
    }

    return array('stat'=>'noexist');
}
?>
