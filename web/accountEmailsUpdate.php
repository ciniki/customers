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
function ciniki_customers_web_accountEmailsUpdate($ciniki, $settings, $tnid, &$customer, $required) {

    if( isset($customer['emails'][0]['email']) ) {
        $email = $customer['emails'][0]['email'];
    } else {
        $email = array('id'=>'0', 'address'=>'');
    }

    //
    // Check for updates
    //
    $updated = 'no';
    $form = '';
    $errors = 'no';
    $error_msg = '';
    if( !isset($settings['page-account-email-update']) || $settings['page-account-email-update'] != 'yes' ) {
        return array('stat'=>'ok', 'form'=>'', 'updated'=>'no');
    }

    //
    // Only 1 email allowed per account
    //
    if( isset($_POST['action']) && $_POST['action'] == 'update' ) {
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x20000000) > 0 ) {
            $email_args = array();
            if( isset($_POST['email']) && $_POST['email'] != $email['address'] ) {
                $email_args['email'] = $_POST['email'];
                $email['address'] = $_POST['email'];
            }
            if( count($email_args) > 0 ) {
                if( $email['id'] == 0 ) { 
                    $email_args['customer_id'] = $customer['id'];
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email', $email_args);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your email address.";
                    } else {
                        $updated = 'yes';
                    }
                } else {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountEmailUpdate');
                    $rc = ciniki_customers_web_accountEmailUpdate($ciniki, $settings, $tnid, $email, $_POST['email']);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . $rc['err']['msg'];
                    } else {
                        $updated = 'yes';
                    }
//                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.email', $email['id'], $email_args);
//                    if( $rc['stat'] != 'ok' ) {
//                        $errors = 'yes';
//                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your email address.";
//                    } else {
//                        $updated = 'yes';
//                    }
                }
            }
        }
        //
        // Multiple emails allowed per account
        //
        else {
            //
            // Check for any changes to existing
            //
            foreach($customer['emails'] as $eid => $email) {
                $email = $email['email'];
                if( isset($_POST['email_' . $email['id']]) ) {
                    $post_email = trim($_POST['email_' . $email['id']]);
                    if( $post_email == '' ) {
                        //
                        // If this is the last email then it can't be deleted
                        //
                        if( count($customer['emails']) <= 1 ) {
                            $errors = 'yes';
                            $error_msg .= ($error_msg!=''?"\n":'') . "You must have one email address.";
                        } else {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.email', $email['id'], null);
                            if( $rc['stat'] != 'ok' ) {
                                $errors = 'yes';
                                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your email address, please try again or contact us for help.";
                            } else {
                                $updated = 'yes';
                            }
                            unset($customer['emails'][$eid]);
                        }
                    } elseif( $post_email != $email['address'] ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountEmailUpdate');
                        $rc = ciniki_customers_web_accountEmailUpdate($ciniki, $settings, $tnid, $email, $_POST['email_' . $email['id']]);
                        if( $rc['stat'] != 'ok' ) {
                            $errors = 'yes';
                            $error_msg .= ($error_msg!=''?"\n":'') . $rc['err']['msg'];
                        } else {
                            $updated = 'yes';
                        }
                        $customer['emails'][$eid]['email']['address'] = $post_email;
                    }
                }
            }

            //
            // Check for an addition
            //
            if( isset($_POST['email_0']) && $_POST['email_0'] != '' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountEmailAdd');
                $rc = ciniki_customers_web_accountEmailAdd($ciniki, $settings, $tnid, $customer['id'], $_POST['email_0']);
                if( $rc['stat'] != 'ok' ) {
                    $errors = 'yes';
                    $error_msg .= ($error_msg!=''?"\n":'') . $rc['err']['msg'];
                } else {
                    $updated = 'yes';
                    $customer['emails'][] = array('email'=>array('id'=>$rc['id'], 'address'=>$_POST['email_0']));
                }
            }
        }
    }

    if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x20000000) > 0 ) {
        $form .= "<div class='input email'>"
            . "<label for='email'>Email Address" . (in_array('email', $required)?' *':'') . "</label>"
            . "<input type='text' class='text' name='email' value='" . $email['address'] . "'>"
            . "</div>";
    } else {
/*        foreach($customer['emails'] as $email) {
            $email = $email['email'];
            $form .= "<div class='input email'>"
                . "<label for='email'>Email Address</label>"
                . "<input type='text' class='text' name='email_" . $email['id'] . "' value='" . $email['address'] . "'>"
                . "</div>";
        }
        $form .= "<div class='input email'>"
            . "<label for='email'>Add Another Email</label>"
            . "<input type='text' class='text' name='email_0' value=''>"
            . "</div>"; */
    }

    return array('stat'=>'ok', 'updated'=>$updated, 'form'=>$form, 'errors'=>$errors, 'error_msg'=>$error_msg);
}
?>
