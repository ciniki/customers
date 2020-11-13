<?php
//
// Description
// -----------
// This function returns the products purchases by a customer. 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// args:            customer_id
// 
// Returns
// ---------
// 
function ciniki_customers_hooks_membershipDetails(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'productsPurchased');
    $rc = ciniki_customers_productsPurchased($ciniki, $tnid, $args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.429', 'msg'=>'', 'err'=>$rc['err']));
    }
    return $rc;
}
?>
