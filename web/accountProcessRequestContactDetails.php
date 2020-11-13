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
function ciniki_customers_web_accountProcessRequestContactDetails($ciniki, $settings, $tnid, $args) {

    //
    // Setup the required fields array
    //
    $required = (isset($args['required'])?$args['required']:array());

    //
    // Get the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
    $rc = ciniki_customers__customerDetails($ciniki, $tnid, $ciniki['session']['customer']['id'], array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.178', 'msg'=>'Unable to find your information. Please try again or contact us for help.', 'err'=>$rc['err']));
    } elseif( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.179', 'msg'=>'Unable to find your information. Please try again or contact us for help.', 'err'=>$rc['err']));
    }
    $customer = $rc['customer'];
    if( isset($customer['emails'][0]['email']) ) {
        $email = $customer['emails'][0]['email'];
        $emails = $customer['emails'];
    } else {
        $email = array('id'=>'0', 'address'=>'');
        $emails = array();
    }
    if( isset($customer['phones']) ) {
        $phones = $customer['phones'];
    } else {
        $phones = array();
    }
    if( isset($customer['addresses'][0]['address']) ) {
        foreach($customer['addresses'] as $address) {
            if( ($address['address']['flags']&0x01) == 0x01 && !isset($shipaddress) ) {
                $shipaddress = $address['address'];
            }
            if( ($address['address']['flags']&0x02) == 0x02 && !isset($billaddress) ) {
                $billaddress = $address['address'];
            }
        }
//        $billaddress = $customer['addresses'][0]['address'];
        $addresses = $customer['addresses'];
    } else {
        $addresses = array();
    }
    if( !isset($billaddress) ) {
        $billaddress = array('id'=>'0', 'flags'=>0x07, 'address1'=>'', 'address2'=>'', 'city'=>'', 'province'=>'', 'postal'=>'', 'country'=>'');
    }
    if( !isset($shipaddress) ) {
        $shipaddress = array('id'=>'0', 'address1'=>'', 'address2'=>'', 'city'=>'', 'province'=>'', 'postal'=>'', 'country'=>'');
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
        //
        // Max of Home, Work, Cell, Fax number for a customer
        //
        if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' 
            && ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x10000000) > 0
            ) {
            if( isset($_POST['phone_cell']) && $_POST['phone_cell'] != $customer['phone_cell'] ) {
                $customer_args['phone_cell'] = $_POST['phone_cell'];
                $customer['phone_cell'] = $_POST['phone_cell'];
            }
            // FIXME: Add other phones here
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
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0400) ) {
            if( isset($_POST['callsign']) && $_POST['callsign'] != $customer['callsign'] ) {
                $customer_args['callsign'] = $_POST['callsign'];
                $customer['callsign'] = $_POST['callsign'];
            }
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
        $rc = ciniki_customers_customerUpdateName($ciniki, $tnid, $customer, $customer['id'], $customer_args);
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
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $customer['id'], $customer_args);
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your contact information.";
            } else {
                $updated = 'yes';
            }
        }


        //
        // Check address
        //
        if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' ) {
        
            //
            // Check for updates to bill address
            //
            $billaddress_args = array();
            if( isset($_POST['address1']) && $_POST['address1'] != $billaddress['address1'] ) {
                $billaddress_args['address1'] = $_POST['address1'];
                $billaddress['address1'] = $_POST['address1'];
            }
            if( isset($_POST['address2']) && $_POST['address2'] != $billaddress['address2'] ) {
                $billaddress_args['address2'] = $_POST['address2'];
                $billaddress['address2'] = $_POST['address2'];
            }
            if( isset($_POST['city']) && $_POST['city'] != $billaddress['city'] ) {
                $billaddress_args['city'] = $_POST['city'];
                $billaddress['city'] = $_POST['city'];
            }
            if( isset($_POST['country']) && isset($_POST['province_code_' . $_POST['country']]) ) {
                if( isset($_POST['province_code_' . $_POST['country']]) && $_POST['province_code_' . $_POST['country']] != $billaddress['province'] ) {
                    $billaddress_args['province'] = $_POST['province_code_' . $_POST['country']];
                    $billaddress['province'] = $_POST['province_code_' . $_POST['country']];
                }
            } else {
                if( isset($_POST['province']) && $_POST['province'] != $billaddress['province'] ) {
                    $billaddress_args['province'] = $_POST['province'];
                    $billaddress['province'] = $_POST['province'];
                }
            }
            if( isset($_POST['postal']) && $_POST['postal'] != $billaddress['postal'] ) {
                $billaddress_args['postal'] = $_POST['postal'];
                $billaddress['postal'] = $_POST['postal'];
            }
            if( isset($_POST['country']) && $_POST['country'] != $billaddress['country'] ) {
                $billaddress_args['country'] = $_POST['country'];
                $billaddress['country'] = $_POST['country'];
            }
            //
            // Check for updates to ship address
            //
            $shipaddress_args = array();
            if( isset($_POST['shipaddress1']) && $_POST['shipaddress1'] != $shipaddress['address1'] ) {
                $shipaddress_args['address1'] = $_POST['shipaddress1'];
                $shipaddress['address1'] = $_POST['shipaddress1'];
            }
            if( isset($_POST['shipaddress2']) && $_POST['shipaddress2'] != $shipaddress['address2'] ) {
                $shipaddress_args['address2'] = $_POST['shipaddress2'];
                $shipaddress['address2'] = $_POST['shipaddress2'];
            }
            if( isset($_POST['shipcity']) && $_POST['shipcity'] != $shipaddress['city'] ) {
                $shipaddress_args['city'] = $_POST['shipcity'];
                $shipaddress['city'] = $_POST['shipcity'];
            }
            if( isset($_POST['shipcountry']) && isset($_POST['shipprovince_code_' . $_POST['shipcountry']]) ) {
                if( isset($_POST['shipprovince_code_' . $_POST['shipcountry']]) && $_POST['shipprovince_code_' . $_POST['shipcountry']] != $shipaddress['province'] ) {
                    $shipaddress_args['province'] = $_POST['shipprovince_code_' . $_POST['shipcountry']];
                    $shipaddress['province'] = $_POST['shipprovince_code_' . $_POST['shipcountry']];
                }
            } else {
                if( isset($_POST['shipprovince']) && $_POST['shipprovince'] != $shipaddress['province'] ) {
                    $shipaddress_args['province'] = $_POST['shipprovince'];
                    $shipaddress['province'] = $_POST['shipprovince'];
                }
            }
            if( isset($_POST['shippostal']) && $_POST['shippostal'] != $shipaddress['postal'] ) {
                $shipaddress_args['postal'] = $_POST['shippostal'];
                $shipaddress['postal'] = $_POST['shippostal'];
            }
            if( isset($_POST['shipcountry']) && $_POST['shipcountry'] != $shipaddress['country'] ) {
                $shipaddress_args['country'] = $_POST['shipcountry'];
                $shipaddress['country'] = $_POST['shipcountry'];
            }
            //
            // Check if ship address should be separated from bill address because one has changed
            //
            if( $billaddress['id'] == $shipaddress['id'] 
                && ($billaddress['address1'] != $shipaddress['address1']
                    || $billaddress['address2'] != $shipaddress['address2']
                    || $billaddress['city'] != $shipaddress['city']
                    || $billaddress['province'] != $shipaddress['province']
                    || $billaddress['postal'] != $shipaddress['postal']
                    || $billaddress['country'] != $shipaddress['country']
                    ) ) {
                //
                // Add the new shipaddress
                //
                $shipaddress['customer_id'] = $customer['id'];
                $shipaddress['flags'] = 0x01;
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $shipaddress);
                if( $rc['stat'] != 'ok' ) {
                    $errors = 'yes';
                    $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your address.";
                } else {
                    $updated = 'yes';
                }
                //
                // Update billaddress to remove shipping
                //
                if( isset($billaddress['flags']) && ($billaddress['flags']&0x01) == 0x01 ) {
                    $billaddress['flags'] = ($billaddress['flags']&0xFFFFFFFE);    
                    $billaddress_args['flags'] = ($billaddress['flags']&0xFFFFFFFE);    
                }
            }
            
            if( count($billaddress_args) > 0 ) {
                if( $billaddress['id'] == 0 ) {
                    $billaddress['customer_id'] = $customer['id'];
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $billaddress);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your address.";
                    } else {
                        $updated = 'yes';
                    }
                } else {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $billaddress['id'], $billaddress_args);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your address.";
                    } else {
                        $updated = 'yes';
                    }
                }
            }
            if( $billaddress['id'] != $shipaddress['id'] ) {
                //
                // Add or update ship address
                //
                if( $shipaddress['id'] == 0 ) {
                    $shipaddress['customer_id'] = $customer['id'];
                    $shipaddress['flags'] = 0x01;
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $shipaddress);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your address.";
                    } else {
                        $updated = 'yes';
                    }
                } elseif( $shipaddress['id'] > 0 && count($shipaddress_args) > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $shipaddress['id'], $shipaddress_args);
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
    // Check emails
    //
    if( isset($settings['page-account-email-update']) && $settings['page-account-email-update'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountEmailsUpdate');
        $rc = ciniki_customers_web_accountEmailsUpdate($ciniki, $settings, $tnid, $customer, $required);
        if( $rc['stat'] != 'ok' ) {
            $errors = 'yes';
        } else {
            $email_form = $rc['form'];
            if( $rc['updated'] == 'yes' ) {
                $updated = 'yes';
            }
            if( isset($rc['errors']) && $rc['errors'] == 'yes' ) {
                $errors = 'yes';
                if( isset($rc['error_msg']) ) {
                    $error_msg .= ($error_msg!=''?"\n":'') . $rc['error_msg'];
                } else {
                    $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your email address.";
                }
            }
        }
    }

    //
    // Setup the form
    //
    $form = "<div class='contact-details-form'>";
    $form .= "<div class='contact-details-form-name contact-details-section'>";
    $form .= "<div class='input first'>"
        . "<label for='first'>First Name" . (in_array('first', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='first' value='" . $customer['first'] . "'>"
        . "</div>";
    $form .= "<div class='input last'>"
        . "<label for='last'>Last Name" . (in_array('last', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='last' value='" . $customer['last'] . "'>"
        . "</div>";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0400) ) {
        $form .= "<div class='input callsign'>"
            . "<label for='callsign'>Callsign" . (in_array('first', $required)?' *':'') . "</label>"
            . "<input type='text' class='text' name='callsign' value='" . $customer['callsign'] . "'>"
            . "</div>";
    }

    if( isset($email_form) ) {
        $form .= $email_form;
    }
    $form .= "</div>";

    if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' ) {
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x10000000) > 0 ) {
            $form .= "<div class='contact-details-form-phone contact-details-section'>";
            $form .= "<div class='input phone_cell'>"
                . "<label for='phone_cell'>Cell Phone Number" . (in_array('phone_cell', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='phone_cell' value='" . $customer['phone_cell'] . "'>"
                . "</div>";
            $form .= "</div>";
        } else {
            // FIXME: Manage multiple phones
        }
    }

    if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' ) {
        $form .= "<div class='contact-details-form-mailing contact-details-form-billing contact-details-section'>";
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x40000000) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'countryCodes');
            $rc = ciniki_core_countryCodes($ciniki);
            $country_codes = $rc['countries'];
            $province_codes = $rc['provinces'];
            $form .= "<div class='input address1'>"
                . "<label for='address1'>Billing Address 1" . (in_array('address1', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='address1' value='" . $billaddress['address1'] . "'>"
                . "</div>";
            $form .= "<div class='input address2'>"
                . "<label for='address2'>Billing Address 2" . (in_array('address2', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='address2' value='" . $billaddress['address2'] . "'>"
                . "</div>";
            $form .= "<div class='input city'>"
                . "<label for='city'>City" . (in_array('city', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='city' value='" . $billaddress['city'] . "'>"
                . "</div>";
            $form .= "<div class='input country'>"
                . "<label for='country'>Country" . (in_array('country', $required)?' *':'') . "</label>"
                . "<select id='country_code' type='select' class='select' name='country' onchange='updateProvince()'>"
                . "<option value=''></option>";
            $selected_country = '';
            foreach($country_codes as $country_code => $country_name) {
                $form .= "<option value='" . $country_code . "' " 
                    . (($country_code == $billaddress['country'] || $country_name == $billaddress['country'])?' selected':'')
                    . ">" . $country_name . "</option>";
                if( $country_code == $billaddress['country'] || $country_name == $billaddress['country'] ) {
                    $selected_country = $country_code;
                }
            }
            $form .= "</select></div>";
            $form .= "<div class='input province'>"
                . "<label for='province'>State/Province" . (in_array('province', $required)?' *':'') . "</label>"
                . "<input id='province_text' type='text' class='text' name='province' "
                    . (isset($province_codes[$selected_country])?" style='display:none;'":"")
                    . "value='" . $billaddress['province'] . "'>";
            $js = '';
            foreach($province_codes as $country_code => $provinces) {
                $form .= "<select id='province_code_{$country_code}' type='select' class='select' "
                    . (($country_code != $selected_country)?" style='display:none;'":"")
                    . "name='province_code_{$country_code}' >"
                    . "<option value=''></option>";
                $js .= "document.getElementById('province_code_" . $country_code . "').style.display='none';";
                foreach($provinces as $province_code => $province_name) {
                    $form .= "<option value='" . $province_code . "'" 
                        . (($province_code == $billaddress['province'] || $province_name == $billaddress['province'])?' selected':'')
                        . ">" . $province_name . "</option>";
                }
                $form .= "</select>";
            }
            $form .= "</div>";
            $form .= "<div class='input postal'>"
                . "<label for='postal'>ZIP/Postal Code" . (in_array('postal', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='postal' value='" . $billaddress['postal'] . "'>"
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
        $form .= "</div>";
    }

    //
    // Check for shipping addresses
    //
    if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' 
        && ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x40)
        ) {
        $form .= "<div class='contact-details-form-shipping contact-details-section'>";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x40000000) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'countryCodes');
            $rc = ciniki_core_countryCodes($ciniki);
            $country_codes = $rc['countries'];
            $province_codes = $rc['provinces'];
            $form .= "<div class='input shipaddress1'>"
                . "<label for='shipaddress1'>Shipping Address 1" . (in_array('shipaddress1', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='shipaddress1' value='" . $shipaddress['address1'] . "'>"
                . "</div>";
            $form .= "<div class='input shipaddress2'>"
                . "<label for='shipaddress2'>Shipping Address 2" . (in_array('shipaddress2', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='shipaddress2' value='" . $shipaddress['address2'] . "'>"
                . "</div>";
            $form .= "<div class='input shipcity'>"
                . "<label for='shipcity'>City" . (in_array('shipcity', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='shipcity' value='" . $shipaddress['city'] . "'>"
                . "</div>";
            $form .= "<div class='input shipcountry'>"
                . "<label for='shipcountry'>Country" . (in_array('shipcountry', $required)?' *':'') . "</label>"
                . "<select id='shipcountry_code' type='select' class='select' name='shipcountry' onchange='updateShipProvince()'>"
                . "<option value=''></option>";
            $selected_country = '';
            foreach($country_codes as $country_code => $country_name) {
                $form .= "<option value='" . $country_code . "' " 
                    . (($country_code == $shipaddress['country'] || $country_name == $shipaddress['country'])?' selected':'')
                    . ">" . $country_name . "</option>";
                if( $country_code == $shipaddress['country'] || $country_name == $shipaddress['country'] ) {
                    $selected_country = $country_code;
                }
            }
            $form .= "</select></div>";
            $form .= "<div class='input shipprovince'>"
                . "<label for='shipprovince'>State/Province" . (in_array('shipprovince', $required)?' *':'') . "</label>"
                . "<input id='shipprovince_text' type='text' class='text' name='shipprovince' "
                    . (isset($province_codes[$selected_country])?" style='display:none;'":"")
                    . "value='" . $shipaddress['province'] . "'>";
            $js = '';
            foreach($province_codes as $country_code => $provinces) {
                $form .= "<select id='shipprovince_code_{$country_code}' type='select' class='select' "
                    . (($country_code != $selected_country)?" style='display:none;'":"")
                    . "name='shipprovince_code_{$country_code}' >"
                    . "<option value=''></option>";
                $js .= "document.getElementById('shipprovince_code_" . $country_code . "').style.display='none';";
                foreach($provinces as $province_code => $province_name) {
                    $form .= "<option value='" . $province_code . "'" 
                        . (($province_code == $shipaddress['province'] || $province_name == $shipaddress['province'])?' selected':'')
                        . ">" . $province_name . "</option>";
                }
                $form .= "</select>";
            }
            $form .= "</div>";
            $form .= "<div class='input shippostal'>"
                . "<label for='shippostal'>ZIP/Postal Code" . (in_array('shippostal', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='shippostal' value='" . $shipaddress['postal'] . "'>"
                . "</div>";
            $form .= "<script type='text/javascript'>"
                . "function updateShipProvince() {"
                    . "var cc = document.getElementById('shipcountry_code');"
                    . "var pr = document.getElementById('shipprovince_text');"
                    . "var pc = document.getElementById('shipprovince_code_'+cc.value);"
                    . $js
                    . "if( pc != null ) {"
                        . "pc.style.display='';"
                        . "pr.style.display='none';"
                    . "}else{"
                        . "pr.style.display='';"
                    . "}"
                . "}"
                . "</script>";
        } 
        $form .= "</div>";
    }
    $form .= "</div>";

    if( $updated == 'yes' ) {
        foreach($ciniki['tenant']['modules'] as $module => $m) {
            list($pkg, $mod) = explode('.', $module);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'customerAddressUpdate');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $tnid, array('customer_id'=>$customer['id']));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.416', 'msg'=>'Unable to update customer address.', 'err'=>$rc['err']));
                }
            }
        }

    }

    return array('stat'=>'ok', 'updated'=>$updated, 'form'=>$form, 'customer'=>$customer, 'email'=>$email, 'address'=>$billaddress, 'errors'=>$errors, 'error_msg'=>$error_msg);
}
?>
