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
function ciniki_customers_web_accountProcessRequestContactDetails($ciniki, $settings, $business_id, $args) {

    //
    // Setup the required fields array
    //
    $required = (isset($args['required'])?$args['required']:array());

    //
    // Get the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
    $rc = ciniki_customers__customerDetails($ciniki, $business_id, $ciniki['session']['customer']['id'], array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2980', 'msg'=>'Unable to find your information. Please try again or contact us for help.', 'err'=>$rc['err']));
    } elseif( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2981', 'msg'=>'Unable to find your information. Please try again or contact us for help.', 'err'=>$rc['err']));
    }
    $customer = $rc['customer'];
    if( isset($customer['emails'][0]['email']) ) {
        $email = $customer['emails'][0]['email'];
    } else {
        $email = array('id'=>'0', 'address'=>'');
    }
    if( isset($customer['addresses'][0]['address']) ) {
        $address = $customer['addresses'][0]['address'];
    } else {
        $address = array('id'=>'0', 'address1'=>'', 'address2'=>'', 'city'=>'', 'province'=>'', 'postal'=>'', 'country'=>'');
    }

    if( $customer['first'] == $email['address'] ) {
        $customer['first'] = '';
    }

    //
    // Check for updates
    //
    $error_msg = '';
    $updated = 'no';
    $errors = 'no';
    if( isset($_POST['action']) && $_POST['action'] == 'update' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        //
        // Check if name is updated
        //
        $customer_args = array();
        if( isset($_POST['first']) && $_POST['first'] != $customer['first'] ) {
            $customer_args['first'] = $_POST['first'];
            $customer['first'] = $_POST['first'];
        }
        if( isset($_POST['last']) && $_POST['last'] != $customer['last'] ) {
            $customer_args['last'] = $_POST['last'];
            $customer['last'] = $_POST['last'];
        }
        if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' ) {
            if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x10000000) > 0 ) {
                if( isset($_POST['phone_cell']) && $_POST['phone_cell'] != $customer['phone_cell'] ) {
                    $customer_args['phone_cell'] = $_POST['phone_cell'];
                    $customer['phone_cell'] = $_POST['phone_cell'];
                }
            }
        }
        if( ((!isset($customer_args['first']) && $customer['first'] == '') || (isset($customer_args['first']) && $customer_args['first'] == ''))
            && ((!isset($customer_args['last']) && $customer['last'] == '') || (isset($customer_args['last']) && $customer_args['last'] == ''))
            ) {
            if( isset($_POST['email']) && $_POST['email'] != '' ) {
                $customer_args['first'] = $_POST['email'];
            } else {
                $customer_args['first'] = $email['address'];
            }
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
        $rc = ciniki_customers_customerUpdateName($ciniki, $business_id, $customer, $customer['id'], $customer_args);
        if( $rc['stat'] == 'ok' ) {
            if( isset($rc['display_name']) && $customer['display_name'] != $rc['display_name'] ) {
                $customer_args['display_name'] = $rc['display_name'];
            }
            if( isset($rc['sort_name']) && $customer['sort_name'] != $rc['sort_name'] ) {
                $customer_args['sort_name'] = $rc['sort_name'];
            }
            if( isset($rc['permalink']) && $customer['permalink'] != $rc['permalink'] ) {
                $customer_args['permalink'] = $rc['permalink'];
            }
        }
        if( count($customer_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.customer', $customer['id'], $customer_args);
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your contact information.";
            } else {
                $updated = 'yes';
            }
        }

        //
        // Check emails
        //
        $email_args = array();
        if( isset($settings['page-account-email-update']) && $settings['page-account-email-update'] == 'yes' ) {
            if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x20000000) > 0 ) {
                if( isset($_POST['email']) && $_POST['email'] != $email['address'] ) {
                    $email_args['email'] = $_POST['email'];
                    $email['address'] = $_POST['email'];
                }
            }
            if( count($email_args) > 0 ) {
                if( $email['id'] == 0 ) { 
                    $email_args['customer_id'] = $customer['id'];
                    $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.customers.email', $email_args);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your email address.";
                    } else {
                        $updated = 'yes';
                    }
                } else {
                    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.email', $email['id'], $email_args);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your email address.";
                    } else {
                        $updated = 'yes';
                    }
                }
            }
        }

        //
        // Check address
        //
        $address_args = array();
        if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' ) {
            if( isset($_POST['address1']) && $_POST['address1'] != $address['address1'] ) {
                $address_args['address1'] = $_POST['address1'];
                $address['address1'] = $_POST['address1'];
            }
            if( isset($_POST['address2']) && $_POST['address2'] != $address['address2'] ) {
                $address_args['address2'] = $_POST['address2'];
                $address['address2'] = $_POST['address2'];
            }
            if( isset($_POST['city']) && $_POST['city'] != $address['city'] ) {
                $address_args['city'] = $_POST['city'];
                $address['city'] = $_POST['city'];
            }
            if( isset($_POST['province_code_' . $_POST['country']]) ) {
                if( isset($_POST['province_code_' . $_POST['country']]) && $_POST['province_code_' . $_POST['country']] != $address['province'] ) {
                    $address_args['province'] = $_POST['province_code_' . $_POST['country']];
                    $address['province'] = $_POST['province_code_' . $_POST['country']];
                }
            } else {
                if( isset($_POST['province']) && $_POST['province'] != $address['province'] ) {
                    $address_args['province'] = $_POST['province'];
                    $address['province'] = $_POST['province'];
                }
            }
            if( isset($_POST['postal']) && $_POST['postal'] != $address['postal'] ) {
                $address_args['postal'] = $_POST['postal'];
                $address['postal'] = $_POST['postal'];
            }
            if( isset($_POST['country']) && $_POST['country'] != $address['country'] ) {
                $address_args['country'] = $_POST['country'];
                $address['country'] = $_POST['country'];
            }
            if( count($address_args) > 0 ) {
                if( $address['id'] == 0 ) {
                    $address_args['customer_id'] = $customer['id'];
                    if( !isset($address_args['address1']) ) { $address_args['address1'] = ''; }
                    if( !isset($address_args['address2']) ) { $address_args['address2'] = ''; }
                    if( !isset($address_args['city']) ) { $address_args['city'] = ''; }
                    if( !isset($address_args['province']) ) { $address_args['province'] = ''; }
                    if( !isset($address_args['postal']) ) { $address_args['postal'] = ''; }
                    $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.customers.address', $address_args);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your address.";
                    } else {
                        $updated = 'yes';
                    }
                } else {
                    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.address', $address['id'], $address_args);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your address.";
                    } else {
                        $updated = 'yes';
                    }
                }
            }
        }
    }

    //
    // Setup the form
    //
    $form = "<div class='contact-details-form'>";
    if( isset($settings['page-account-email-update']) && $settings['page-account-email-update'] == 'yes' ) {
        if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x20000000) > 0 ) {
            $form .= "<div class='input email'>"
                . "<label for='email'>Email Address" . (in_array('email', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='email' value='" . $email['address'] . "'>"
                . "</div>";
        } else {
            // FIXME: Manage multiple emails
        }
    }
    if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' ) {
        if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x10000000) > 0 ) {
            $form .= "<div class='input phone_cell'>"
                . "<label for='phone_cell'>Cell Phone Number" . (in_array('phone_cell', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='phone_cell' value='" . $customer['phone_cell'] . "'>"
                . "</div>";
        } else {
            // FIXME: Manage multiple phones
        }
    }
    $form .= "<div class='input first'>"
        . "<label for='first'>First Name" . (in_array('first', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='first' value='" . $customer['first'] . "'>"
        . "</div>";
    $form .= "<div class='input last'>"
        . "<label for='last'>Last Name" . (in_array('last', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='last' value='" . $customer['last'] . "'>"
        . "</div>";
    if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' ) {
        if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x40000000) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'countryCodes');
            $rc = ciniki_core_countryCodes($ciniki);
            $country_codes = $rc['countries'];
            $province_codes = $rc['provinces'];
            $form .= "<div class='input address1'>"
                . "<label for='address1'>Street Address 1" . (in_array('address1', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='address1' value='" . $address['address1'] . "'>"
                . "</div>";
            $form .= "<div class='input address2'>"
                . "<label for='address2'>Street Address 2" . (in_array('address2', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='address2' value='" . $address['address2'] . "'>"
                . "</div>";
            $form .= "<div class='input city'>"
                . "<label for='city'>City" . (in_array('city', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='city' value='" . $address['city'] . "'>"
                . "</div>";
            $form .= "<div class='input country'>"
                . "<label for='country'>Country" . (in_array('country', $required)?' *':'') . "</label>"
                . "<select id='country_code' type='select' class='select' name='country' onchange='updateProvince()'>"
                . "<option value=''></option>";
            $selected_country = '';
            foreach($country_codes as $country_code => $country_name) {
                $form .= "<option value='" . $country_code . "' " 
                    . (($country_code == $address['country'] || $country_name == $address['country'])?' selected':'')
                    . ">" . $country_name . "</option>";
                if( $country_code == $address['country'] || $country_name == $address['country'] ) {
                    $selected_country = $country_code;
                }
            }
            $form .= "</select></div>";
            $form .= "<div class='input province'>"
                . "<label for='province'>State/Province" . (in_array('province', $required)?' *':'') . "</label>"
                . "<input id='province_text' type='text' class='text' name='province' "
                    . (isset($province_codes[$selected_country])?" style='display:none;'":"")
                    . "value='" . $address['province'] . "'>";
            $js = '';
            foreach($province_codes as $country_code => $provinces) {
                $form .= "<select id='province_code_{$country_code}' type='select' class='select' "
                    . (($country_code != $selected_country)?" style='display:none;'":"")
                    . "name='province_code_{$country_code}' >"
                    . "<option value=''></option>";
                $js .= "document.getElementById('province_code_" . $country_code . "').style.display='none';";
                foreach($provinces as $province_code => $province_name) {
                    $form .= "<option value='" . $province_code . "'" 
                        . (($province_code == $address['province'] || $province_name == $address['province'])?' selected':'')
                        . ">" . $province_name . "</option>";
                }
                $form .= "</select>";
            }
            $form .= "</div>";
            $form .= "<div class='input postal'>"
                . "<label for='postal'>ZIP/Postal Code" . (in_array('postal', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='postal' value='" . $address['postal'] . "'>"
                . "</div>";
            $form .= "<script type='text/javascript'>"
                . "function updateProvince() {"
                    . "var cc = document.getElementById('country_code');"
                    . "var pr = document.getElementById('province_text');"
                    . "var pc = document.getElementById('province_code_'+cc.value);"
                    . $js
                    . "if( pc != null ) {"
                        . "pc.style.display='';"
                        . "pr.style.display='none';"
                    . "}else{"
                        . "pr.style.display='';"
                    . "}"
                . "}"
                . "</script>";
        } else {
            // FIXME: Manage multiple addresses
        }
    }
    $form .= "</div>";

    return array('stat'=>'ok', 'updated'=>$updated, 'form'=>$form, 'customer'=>$customer, 'email'=>$email, 'address'=>$address, 'errors'=>$errors, 'error_msg'=>$error_msg);
}
?>
