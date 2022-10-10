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
function ciniki_customers_wng_accountChangePasswordProcess(&$ciniki, $tnid, &$request, $item) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    if( !isset($settings['account-password-change']) || $settings['account-password-change'] != 'yes' ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.490', 'msg'=>'Account page not found'));
    }

    $blocks = array();

    $display_form = 'yes';
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' 
        && isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 
        ) {
        //
        // Check if customer wants to change their password
        //
        if( isset($_POST['f-oldpassword']) && $_POST['f-oldpassword'] != '' 
            && isset($_POST['f-newpassword']) && $_POST['f-newpassword'] != '' 
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'changePassword');
            $rc = ciniki_customers_wng_changePassword($ciniki, $tnid, $request, $_POST['f-oldpassword'], $_POST['f-newpassword']);
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
            'type' => 'text',
            'class' => 'limit-width limit-width-30',
            'title' => 'Change Password',
            'content' => 'If you would like to change your password, enter your old password followed by a new one.',
            );
        $blocks[] = array(
            'type' => 'form',
            'class' => 'limit-width limit-width-30',
            'cancel-label' => 'Cancel',
            'js-cancel' => 'location.replace("' . $request['ssl_domain_base_url'] . '/account");',
            'submit-label' => 'Change Password',
            'fields' => array(
                'action' => array(
                    'id' => 'action',
                    'ftype' => 'hidden',
                    'value' => 'update',
                    ),
                'oldpassword' => array(
                    'id' => 'oldpassword',
                    'label' => 'Old Password', 
                    'ftype' => 'password',
                    'size' => 'large',
                    'required' => 'yes', 
                    'value' => '',
                    ),
                'last' => array(
                    'id' => 'newpassword',
                    'label' => 'New Password', 
                    'ftype' => 'password',
                    'size' => 'large',
                    'required' => 'yes', 
                    'value' => '',
                    ),
                ),
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
