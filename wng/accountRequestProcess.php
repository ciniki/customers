<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_wng_accountRequestProcess(&$ciniki, $tnid, &$request, $item) {

    if( !isset($item['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.513', 'msg'=>'No reference specified'));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.535', 'msg'=>'Must be logged in'));
    }

    if( $item['ref'] == 'ciniki.customers.contact' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountContactProcess');
        return ciniki_customers_wng_accountContactProcess($ciniki, $tnid, $request, $item);
    } 
    elseif( $item['ref'] == 'ciniki.customers.profile' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountProfileProcess');
        return ciniki_customers_wng_accountProfileProcess($ciniki, $tnid, $request, $item);
    } 
    elseif( $item['ref'] == 'ciniki.customers.children' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountChildrenProcess');
        return ciniki_customers_wng_accountChildrenProcess($ciniki, $tnid, $request, $item);
    } 
    elseif( $item['ref'] == 'ciniki.customers.membership' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountMembershipProcess');
        return ciniki_customers_wng_accountMembershipProcess($ciniki, $tnid, $request, $item);
    }
    elseif( $item['ref'] == 'ciniki.customers.changepassword' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountChangePasswordProcess');
        return ciniki_customers_wng_accountChangePasswordProcess($ciniki, $tnid, $request, $item);
    }

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.514', 'msg'=>'Account page not found'));
}
?>
