<?php
//
// Description
// -----------
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_customers_web_customerDetails(&$ciniki, $settings, $tnid, $customer_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
    $rc = ciniki_customers__customerDetails($ciniki, $tnid, $customer_id, 
        array('addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return $rc;
}
?>
