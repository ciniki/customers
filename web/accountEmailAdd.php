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
function ciniki_customers_web_accountEmailAdd($ciniki, $settings, $tnid, $customer_id, $new_email) {

    //
    // Check to make sure a valid email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkEmailValid');
    $rc = ciniki_customers_checkEmailValid($ciniki, $tnid, $new_email);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.168', 'msg'=>"The email address '$new_email' is invalid, please try again.", 'err'=>$rc['err']));
    }

    //
    // Check to make sure that email address is unique
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkEmailExists');
    $rc = ciniki_customers_checkEmailExists($ciniki, $tnid, $new_email);
    if( $rc['stat'] == 'exists' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.169', 'msg'=>"The email address '$new_email' is already has an account.", 'err'=>$rc['err']));
    } elseif( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.170', 'msg'=>"Unable to add the email address.", 'err'=>$rc['err']));
    }

    //
    // Check to make sure this email does not already exist
    //
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email', array(
        'customer_id'=>$customer_id,
        'email'=>$new_email,
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.171', 'msg'=>"Unable to add the email address.", 'err'=>$rc['err']));
    }

    return array('stat'=>'ok', 'id'=>$rc['id']);
}
?>
