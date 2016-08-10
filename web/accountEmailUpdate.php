<?php
//
// Description
// -----------
// This function will update an existing email address for a customer.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_accountEmailUpdate($ciniki, $settings, $business_id, $email, $post_email) {

    //
    // Check to make sure a valid email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkEmailValid');
    $rc = ciniki_customers_checkEmailValid($ciniki, $business_id, $post_email);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3604', 'msg'=>"The email address '$post_email' is invalid, please try again.", 'err'=>$rc['err']));
    }

    //
    // Check to make sure that email address is unique
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkEmailExists');
    $rc = ciniki_customers_checkEmailExists($ciniki, $business_id, $post_email);
    if( $rc['stat'] == 'exists' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3605', 'msg'=>"The email address '$post_email' is already has an account.", 'err'=>$rc['err']));
    } elseif( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3606', 'msg'=>"Unable to add the email address.", 'err'=>$rc['err']));
    }

    //
    // Check to make sure this email does not already exist
    //
    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.email', $email['id'], array(
        'email'=>$email,
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3607', 'msg'=>"Unable to add the email address.", 'err'=>$rc['err']));
    }

    return array('stat'=>'ok');
}
?>
