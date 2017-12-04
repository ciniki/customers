<?php
//
// Description
// -----------
// This function will validate a string is proper email format.
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
function ciniki_customers_checkEmailValid(&$ciniki, $tnid, $email) {
    
    //
    // Make sure the string contains @ sign and a period.
    //
    if( !preg_match("/([^ ]+)\@([^ ]+)\.([^ ]+)/", $email) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.39', 'msg'=>"'$email' is not a valid email address."));
    }

    return array('stat'=>'ok');
}
?>
