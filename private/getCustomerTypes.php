<?php
//
// Description
// -----------
// This function will return the list of customer types defined for the business, and 
// if they are an individual or business.  This is used to determine what information to 
// send back to the UI.
//
// Arguments
// ---------
// ciniki:
// business_id:         The ID of the business the request is for.
// 
// Returns
// -------
//
function ciniki_customers_getCustomerTypes($ciniki, $business_id) {
    //
    // Get the list of types and the forms specified
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT SUBSTR(detail_key, 7, 1) AS type, detail_value "
        . "FROM ciniki_customer_settings "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND detail_key LIKE 'types-%-type' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.customers', 'types', 'type');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['types']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.46', 'msg'=>'Unable to find customer types'));
    }

    return array('stat'=>'ok', 'types'=>$rc['types']);
}
?>
