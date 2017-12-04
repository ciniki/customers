<?php
//
// Description
// -----------
// This function will check if an email address exists for a tenant.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant the request is for.
// email:               The email address to validate.
// 
// Returns
// -------
//
function ciniki_customers_checkEmailExists(&$ciniki, $tnid, $email) {
   
    $strsql = "SELECT customer_id "
        . "FROM ciniki_customer_emails "
        . "WHERE email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.37', 'msg'=>'Unable to check if email exists.'));
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'ok');
    }

    return array('stat'=>'exists', 'err'=>array('code'=>'ciniki.customers.38', 'msg'=>'Email address already exists.'));
}
?>
