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
function ciniki_customers_web_accountProcessRequestContactForm($ciniki, $settings, $tnid, $args) {

    //
    // Check the customer id is specified, or in the session
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $customer_id = $args['customer_id'];
    } elseif( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $customer_id = $ciniki['session']['customer']['id'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.338', 'msg'=>'Invalid account'));
    }

    $blocks = array();

    //
    // The required fields
    //
    $required = array('first', 'last', 'primary_email');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'countryCodes');
    $rc = ciniki_core_countryCodes($ciniki);
    $country_codes = $rc['countries'];
    $province_codes = $rc['provinces'];

    //
    // Load the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerLoad');
    $rc = ciniki_customers_customerLoad($ciniki, $tnid, $customer_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.230', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    $customer = $rc['customer'];
    
    //
    // Check for any updates
    //
    if( isset($_POST['action']) && $_POST['action'] == 'update' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        $errors = 'no';
        $updated = 'no';
        //
        // Check if name is updated
        //
        $customer_args = array();
        if( isset($_POST['first']) && $_POST['first'] != $customer['first'] ) {
            if( trim($_POST['first']) == '' && in_array('first', $required) ) {
                $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'You must provide a first name');
                $errors = 'yes';
            } else {
                $customer_args['first'] = $_POST['first'];
                $customer['first'] = $_POST['first'];
            }
        }
        if( isset($_POST['last']) && $_POST['last'] != $customer['last'] ) {
            if( trim($_POST['last']) == '' && in_array('last', $required) ) {
                $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'You must provide a last name');
                $errors = 'yes';
            } else {
                $customer_args['last'] = $_POST['last'];
                $customer['last'] = $_POST['last'];
            }
        }

        //
        // Check if type was updated
        //
        if( isset($_POST['type']) && $_POST['type'] > 0 ) {
            if( $customer['type'] == 21 && $_POST['type'] == 22 ) {
                $customer_args['type'] = 22;
            } elseif( $customer['type'] == 22 && $_POST['type'] == 21 ) {
                $customer_args['type'] = 21;
            } elseif( $customer['type'] == 31 && $_POST['type'] == 32 ) {
                $customer_args['type'] = 32;
            } elseif( $customer['type'] == 32 && $_POST['type'] == 31 ) {
                $customer_args['type'] = 31;
            }
        }

        //
        // Update the customer name, then update record and then check for session
        //
        if( count($customer_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
            $rc = ciniki_customers_customerUpdateName($ciniki, $tnid, $customer, $customer_id, $customer_args);
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
            // Update database record
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $customer_id, $customer_args);
            if( $rc['stat'] != 'ok' ) {
                $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update contact information');
                $errors = 'yes';
            } else {
                $updated = 'yes';
            }
            // Update session
            if( (isset($customer_args['display_name']) || isset($customer_args['type'])) && isset($ciniki['session']['account']['parents']) ) {
                foreach($ciniki['session']['account']['parents'] as $pid => $parent) {
                    if( $parent['id'] == $customer_id ) {
                        if( isset($customer_args['display_name']) ) {
                            $_SESSION['account']['parents'][$pid]['display_name'] = $customer_args['display_name'];
                        }
                        if( isset($customer_args['type']) && ($customer_args['type'] == 22 || $customer_args['type'] == 32) ) {
                            // Move to children
                            $ciniki['session']['account']['children'][] = $ciniki['session']['account']['parents'][$pid];
                            unset($ciniki['session']['account']['parents'][$pid]);
                            usort($ciniki['session']['account']['children'], function($a, $b) {
                                return strcmp($a['display_name'], $b['display_name']);
                                });
                            $_SESSION['account']['children'] = $ciniki['session']['account']['children'];
                            $_SESSION['account']['parents'] = $ciniki['session']['account']['parents'];
                        }
                    }
                }
            }
            if( (isset($customer_args['display_name']) || isset($customer_args['type'])) && isset($ciniki['session']['account']['children']) ) {
                foreach($ciniki['session']['account']['children'] as $cid => $child) {
                    if( $child['id'] == $customer_id ) {
                        if( isset($customer_args['display_name']) ) {
                            $_SESSION['account']['children'][$cid]['display_name'] = $customer_args['display_name'];
                        }
                        if( isset($customer_args['type']) && ($customer_args['type'] == 21 || $customer_args['type'] == 31) ) {
                            // Move to parents
                            $ciniki['session']['account']['parents'][] = $ciniki['session']['account']['children'][$cid];
                            unset($ciniki['session']['account']['children'][$cid]);
                            usort($ciniki['session']['account']['parents'], function($a, $b) {
                                return strcmp($a['display_name'], $b['display_name']);
                                });
                            $_SESSION['account']['parents'] = $ciniki['session']['account']['parents'];
                            $_SESSION['account']['parents'] = $ciniki['session']['account']['parents'];
                        }
                    }
                }
            }
        }

        //
        // Check for email changes
        //
        $fields = array(
            'primary_email' => array('name'=>'Primary Email'),
            'secondary_email' => array('name'=>'Secondary Email'),
            );
        foreach($fields as $field => $details) {
            if( isset($_POST[$field]) ) {
                // check for delete
                if( trim($_POST[$field]) == '' && isset($customer[$field . '_id']) && $customer[$field . '_id'] > 0 ) {
                    if( in_array($field, $required) ) {
                        $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'You must provide a ' . $details['name'] . ' address');
                        $errors = 'yes';
                    } else {
                        //
                        // FIXME: Check if secondary email should be promoted to primary email
                        //
                        // if( $field == 'primary_email' ) {
                        // }
                        $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.email', $customer[$field . '_id'], null, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to remove email address');
                            $errors = 'yes';
                        } else {
                            $updated = 'yes';
                        }
                    }
                }
                // Add
                elseif( trim($_POST[$field]) != '' && (!isset($customer[$field . '_id']) || $customer[$field . '_id'] == 0) ) {
                    if( !preg_match("/^[^ ]+\@[^ ]+\.[^ ]+$/", trim($_POST[$field])) ) {
                        $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'The ' . $details['name'] . ' is not a valid email address format.');
                        $errors = 'yes';
                    } else {
                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email', array(
                            'customer_id'=>$customer_id,
                            'email'=>trim($_POST[$field]),
                            'flags'=>0x01,
                            ), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update email address');
                            $errors = 'yes';
                        } else {
                            $updated = 'yes';
                        }
                    }
                }
                // Update
                elseif( trim($_POST[$field]) != '' && isset($customer[$field . '_id']) && $customer[$field . '_id'] > 0 ) {
                    if( !preg_match("/^[^ ]+\@[^ ]+\.[^ ]+$/", trim($_POST[$field])) ) {
                        $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'The ' . $details['name'] . ' is not a valid email address format.');
                        $errors = 'yes';
                    } else {
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.email', $customer[$field . '_id'], array('email'=>trim($_POST[$field])), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update email address');
                            $errors = 'yes';
                        } else {
                            $updated = 'yes';
                        }
                    }
                }
                $customer[$field] = $_POST[$field];
            }
        }


        //
        // Check for phone changes
        //
        foreach(['phone_home', 'phone_work', 'phone_cell', 'phone_fax'] as $field) {
            if( isset($_POST[$field]) ) {
                // check for delete
                if( $_POST[$field] == '' && isset($customer[$field . '_id']) && $customer[$field . '_id'] > 0 ) {
                    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.phone', $customer[$field . '_id'], null, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to remove phone number');
                        $errors = 'yes';
                    } else {
                        $updated = 'yes';
                    }
                }
                // Add
                elseif( $_POST[$field] != '' && (!isset($customer[$field . '_id']) || $customer[$field . '_id'] == 0) ) {
                    $label = ucfirst(str_replace('phone_', '', $field));
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.phone', array(
                        'customer_id'=>$customer_id,
                        'phone_label'=>$label,
                        'phone_number'=>$_POST[$field],
                        'flags'=>0,
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to add phone number');
                        $errors = 'yes';
                    } else {
                        $updated = 'yes';
                    }
                }
                // Update
                elseif( $_POST[$field] != '' && isset($customer[$field . '_id']) && $customer[$field . '_id'] > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.phone', $customer[$field . '_id'], array('phone_number'=>$_POST[$field]), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update phone number');
                        $errors = 'yes';
                    } else {
                        $updated = 'yes';
                    }
                }
                $customer[$field] = $_POST[$field];
            }
        }

        if( $errors == 'no' ) {
            return array('stat'=>'updated');
        }
    }

    //
    // Display the form
    //
    $form = "<form action='' method='POST'>"
        . "<input type='hidden' name='action' value='update'>"
        . "<div class='contact-details-form'>";
    $form .= "<div class='contact-details-section contact-details-form-name'>";
    $form .= "<div class='input first'>"
        . "<label for='first'>First Name" . (in_array('first', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='first' value='" . $customer['first'] . "'>"
        . "</div>";
    $form .= "<div class='input last'>"
        . "<label for='last'>Last Name" . (in_array('last', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='last' value='" . $customer['last'] . "'>"
        . "</div>";
    $form .= "</div>";
    // Email
    $form .= "<div class='contact-details-section contact-details-form-email'>";
    $form .= "<div class='input email1'>"
        . "<label for='primary_email'>Primary Email" . (in_array('primary_email', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='primary_email' value='" . $customer['primary_email'] . "'>"
        . "</div>";
    $form .= "<div class='input email2'>"
        . "<label for='secondary_email'>Secondary Email" . (in_array('secondary_email', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='secondary_email' value='" . $customer['secondary_email'] . "'>"
        . "</div>";
    $form .= "</div>";
    // Phones
    $form .= "<div class='contact-details-section contact-details-form-phones'>";
    $form .= "<div class='input phone_cell'>"
        . "<label for='phone_cell'>Cell Phone" . (in_array('phone_cell', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='phone_cell' value='" . $customer['phone_cell'] . "'>"
        . "</div>";
    $form .= "<div class='input phone_home'>"
        . "<label for='phone_home'>Home Phone" . (in_array('phone_home', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='phone_home' value='" . $customer['phone_home'] . "'>"
        . "</div>";
    $form .= "<div class='input phone_work'>"
        . "<label for='phone_work'>Work Phone" . (in_array('phone_work', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='phone_work' value='" . $customer['phone_work'] . "'>"
        . "</div>";
    $form .= "<div class='input phone Fax'>"
        . "<label for='phone_fax'>Fax" . (in_array('phone_fax', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='phone_fax' value='" . $customer['phone_fax'] . "'>"
        . "</div>";
    $form .= "</div>";

    // Mailing Address
    $form .= "<div class='contact-details-section contact-details-form-mailing'>";
    $form .= "<div class='input mailing_address1'>"
        . "<label for='mailing_address1'>Mailing Address Line 1" . (in_array('mailing_address1', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='mailing_address1' value='" . $customer['mailing_address1'] . "'>"
        . "</div>";
    $form .= "<div class='input mailing_address2'>"
        . "<label for='mailing_address2'>Line 2" . (in_array('mailing_address2', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='mailing_address2' value='" . $customer['mailing_address2'] . "'>"
        . "</div>";
    $form .= "<div class='input mailing_city'>"
        . "<label for='mailing_city'>City" . (in_array('mailing_city', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='mailing_city' value='" . $customer['mailing_city'] . "'>"
        . "</div>";
    $form .= "<div class='input mailing_country'>"
        . "<label for='mailing_country'>Country" . (in_array('mailing_country', $required)?' *':'') . "</label>"
        . "<select id='mailing_country_code' type='select' class='select' name='mailing_country' onchange='updateMailingProvince()'>"
        . "<option value=''></option>";
    $selected_country = 'Canada';
    foreach($country_codes as $country_code => $country_name) {
        $form .= "<option value='" . $country_code . "' " 
            . (($country_code == $customer['mailing_country'] || $country_name == $customer['mailing_country'])?' selected':'')
            . ">" . $country_name . "</option>";
        if( $country_code == $customer['mailing_country'] || $country_name == $customer['mailing_country'] ) {
            $selected_country = $country_code;
        }
    }
    $form .= "</select></div>";
    $form .= "<div class='input mailing_province'>"
        . "<label for='mailing_province'>State/Province" . (in_array('mailing_province', $required)?' *':'') . "</label>"
        . "<input id='mailing_province_text' type='text' class='text' name='mailing_province' "
            . (isset($province_codes[$selected_country])?" style='display:none;'":"")
            . "value='" . $customer['mailing_province'] . "'>";
    $js = '';
    foreach($province_codes as $country_code => $provinces) {
        $form .= "<select id='mailing_province_code_{$country_code}' type='select' class='select' "
            . (($country_code != $selected_country)?" style='display:none;'":"")
            . "name='mailing_province_code_{$country_code}' >"
            . "<option value=''></option>";
        $js .= "document.getElementById('mailing_province_code_" . $country_code . "').style.display='none';";
        foreach($provinces as $province_code => $province_name) {
            $form .= "<option value='" . $province_code . "'" 
                . (($province_code == $customer['mailing_province'] || $province_name == $customer['mailing_province'])?' selected':'')
                . ">" . $province_name . "</option>";
        }
        $form .= "</select>";
    }
    $form .= "</div>";
    $form .= "<div class='input mailing_postal'>"
        . "<label for='mailing_postal'>ZIP/Postal Code" . (in_array('mailing_postal', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='mailing_postal' value='" . $customer['mailing_postal'] . "'>"
        . "</div>";
    $form .= "<script type='text/javascript'>"
        . "function updateMailingProvince() {"
            . "var cc = document.getElementById('mailing_country_code');"
            . "var pr = document.getElementById('mailing_province_text');"
            . "var pc = document.getElementById('mailing_province_code_'+cc.value);"
            . $js
            . "if( pc != null ) {"
                . "pc.style.display='';"
                . "pr.style.display='none';"
            . "}else{"
                . "pr.style.display='';"
            . "}"
        . "}"
        . "</script>";
    $form .= "</div>";


    // Billing Address

    // Check if option should be option for parent/admin  f family/company
    if( in_array($customer['type'], [21,22,31,32]) ) {
        $form .= "<div class='contact-details-section contact-details-form-admin'>";
        $form .= "<div class='input admin'>";
        if( $customer['type'] == 21 ) {
            $form .= "<label for='type'>Parent/Guardian</label>"
                . "<select id='type' name='type' type='select' class='select'>"
                    . "<option value=21 selected>Yes</option>"
                    . "<option value=22>No</option>"
                    . "</select>"
                    . "";
        } elseif( $customer['type'] == 22 ) {
            $form .= "<label for='type'>Parent/Guardian</label>"
                . "<select id='type' name='type' type='select' class='select'>"
                    . "<option value=21>Yes</option>"
                    . "<option value=22 selected>No</option>"
                    . "</select>"
                    . "";
        } elseif( $customer['type'] == 31 ) {
            $form .= "<label for='type'>Company Administrator</label>"
                . "<select id='type' name='type' type='select' class='select'>"
                    . "<option value=31 selected>Yes</option>"
                    . "<option value=32>No</option>"
                    . "</select>"
                    . "";
        } elseif( $customer['type'] == 32 ) {
            $form .= "<label for='type'>Company Administrator</label>"
                . "<select id='type' name='type' type='select' class='select'>"
                    . "<option value=31>Yes</option>"
                    . "<option value=32 selected>No</option>"
                    . "</select>"
                    . "";
        }
        $form .= "</div>";
        $form .= "</div>";
    }

    $form .= "<div class='submit'><input type='submit' class='submit' value='Save'></div>";
    $form .= "</form>";

/*
    if( isset($email_form) ) {
        $form .= $email_form;
    }
    if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' ) {
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x10000000) > 0 ) {
            $form .= "<div class='input phone_cell'>"
                . "<label for='phone_cell'>Cell Phone Number" . (in_array('phone_cell', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='phone_cell' value='" . $customer['phone_cell'] . "'>"
                . "</div>";
        } else {
            // FIXME: Manage multiple phones
        }
    }
    if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' ) {
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x40000000) > 0 ) {
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
    */
    $form .= "</div>";

    $blocks[] = array('type'=>'content', 'html'=>$form);
    //$blocks[] = array('type'=>'content', 'html'=>'<pre>' . print_r($customer, true) . '</pre>');

    return array('stat'=>'ok', 'blocks'=>$blocks);

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
        $address = $customer['addresses'][0]['address'];
        $addresses = $customer['addresses'];
    } else {
        $address = array('id'=>'0', 'address1'=>'', 'address2'=>'', 'city'=>'', 'province'=>'', 'postal'=>'', 'country'=>'');
        $addresses = array();
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
            if( isset($_POST['country']) && isset($_POST['province_code_' . $_POST['country']]) ) {
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
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $address_args);
                    if( $rc['stat'] != 'ok' ) {
                        $errors = 'yes';
                        $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update your address.";
                    } else {
                        $updated = 'yes';
                    }
                } else {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $address['id'], $address_args);
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
    if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountEmailsUpdate');
        $rc = ciniki_customers_web_accountEmailsUpdate($ciniki, $settings, $tnid, $customer);
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
    $form .= "<div class='contact-details-form-name'>";
    $form .= "<div class='input first'>"
        . "<label for='first'>First Name" . (in_array('first', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='first' value='" . $customer['first'] . "'>"
        . "</div>";
    $form .= "<div class='input last'>"
        . "<label for='last'>Last Name" . (in_array('last', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='last' value='" . $customer['last'] . "'>"
        . "</div>";
    $form .= "</div>";

    if( isset($email_form) ) {
        $form .= $email_form;
    }
    if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' ) {
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x10000000) > 0 ) {
            $form .= "<div class='input phone_cell'>"
                . "<label for='phone_cell'>Cell Phone Number" . (in_array('phone_cell', $required)?' *':'') . "</label>"
                . "<input type='text' class='text' name='phone_cell' value='" . $customer['phone_cell'] . "'>"
                . "</div>";
        } else {
            // FIXME: Manage multiple phones
        }
    }
    if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' ) {
        if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x40000000) > 0 ) {
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
