<?php
//
// Description
// -----------
// This function will check if an email address exists for a business.
//
// Arguments
// ---------
// ciniki:
// business_id:         The ID of the business the request is for.
// email:               The email address to validate.
// 
// Returns
// -------
//
function ciniki_customers_checkEmailExists(&$ciniki, $business_id, $email) {
   
    $strsql = "SELECT customer_id "
        . "FROM ciniki_customer_emails "
        . "WHERE email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3601', 'msg'=>'Unable to check if email exists.'));
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'ok');
    }

    return array('stat'=>'exists', 'err'=>array('pkg'=>'ciniki', 'code'=>'3602', 'msg'=>'Email address already exists.'));
}
?>
