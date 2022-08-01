<?php
//
// Description
// -----------
// This function will add a new customer to the tenants customers module, and
// authenticate them 
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_wng_signupComplete(&$ciniki, $tnid, &$request, $signupkey) {
   
    //
    // Lookup the signup
    //
    $strsql = "SELECT id, "
        . "uuid, "
        . "first, "
        . "last, "
        . "email, "
        . "password, "
        . "details "
        . "FROM ciniki_customer_signups "
        . "WHERE signupkey = '" . ciniki_core_dbQuote($ciniki, $signupkey) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(date_added)) < 7200 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'signup');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.529', 'msg'=>'Unable to load signup', 'err'=>$rc['err']));
    }
    if( !isset($rc['signup']) ) {
        return array('stat'=>'ok', 'blocks'=>array(
            array(
                'type' => 'msg', 
                'level' => 'error', 
                'content' => 'Signup has expired, please try again.',
                ),
            ));
    }
    $signup = $rc['signup'];
    $create_args = array(
        'first' => $signup['first'],
        'last' => $signup['last'],
        'email_address' => $signup['email'],
        'hashed_pwd' => $signup['password'],
        );
    if( $signup['details'] != '' ) {
        $details = unserialize($signup['details']);
        if( $details !== false ) {
            foreach($details as $k => $v) {
                $create_args[$k] = $v;
            }
        }
    }
   
    //
    // Create customer
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'customerAdd');
    $rc = ciniki_customers_wng_customerAdd($ciniki, $tnid, $request, $create_args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok', 'blocks'=>array(
            array(
                'type' => 'msg', 
                'level' => 'error', 
                'content' => 'We were unable to create your account, please contact us for assistance.',
                ),
            ));
    }
    $customer_id = $rc['id'];

    //
    // Remove the signup
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.signup', $signup['id'], $signup['uuid'], 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Once the account is created, authenticate
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'auth');
    $rc = ciniki_customers_wng_auth($ciniki, $tnid, $request, $signup['email'], $signup['password']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
