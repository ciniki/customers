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
function ciniki_customers_wng_accountChangePasswordProcess($ciniki, $tnid, $request, $item) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    if( !isset($settings['account-password-change']) || $settings['account-password-change'] != 'yes' ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.514', 'msg'=>'Account page not found'));
    }

    $blocks = array();

    $display_form = 'yes';
    if( isset($_POST['action']) && $_POST['action'] == 'update' 
        && isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 
        ) {
        //
        // Check if customer wants to change their password
        //
        if( isset($_POST['oldpassword']) && $_POST['oldpassword'] != '' 
            && isset($_POST['newpassword']) && $_POST['newpassword'] != '' 
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'changePassword');
            $rc = ciniki_customers_wng_changePassword($ciniki, $tnid, $request, $_POST['oldpassword'], $_POST['newpassword']);
            if( $rc['stat'] != 'ok' ) {
                $blocks[] = array(
                    'type' => 'msg', 
                    'level'=>'error', 
                    'content' => $rc['err']['msg'],
                    );
            } else {
                $blocks[] = array(
                    'type' => 'msg', 
                    'level' => 'success', 
                    'content' => 'Your password has been updated.',
                    );
                $display_form = 'no';
            }
        }
    }
   
    if( $display_form == 'yes' ) {
        $blocks[] = array(
            'type' => 'accountchgpwd',
            'title' => 'Change Password',
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
