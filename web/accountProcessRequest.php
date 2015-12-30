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
function ciniki_customers_web_accountProcessRequest($ciniki, $settings, $business_id, $args) {

    $page = array(
        'title'=>'Account',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );

    $base_url = $args['base_url'];

    //
    // Check for change password
    //
    if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'changepassword' ) {
//        $page['breadcrumbs'][] = array('name'=>'Change Password', 'url'=>$ciniki['request']['domain_base_url'] . '/account/changepassword');
        $page['title'] = 'Change Password';
    
        $display_form = 'yes';
        if( isset($_POST['action']) && $_POST['action'] == 'update' 
            && isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 
            ) {
			//
			// Check if customer wants to change their password
			//
			if( isset($_POST['oldpassword']) && $_POST['oldpassword'] != '' 
				&& isset($_POST['newpassword']) && $_POST['newpassword'] != '' 
				&& (!isset($settings['page-account-password-change']) 
					|| $settings['page-account-password-change'] == 'yes')
				) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'changePassword');
				$rc = ciniki_customers_web_changePassword($ciniki, $ciniki['request']['business_id'], 
					$_POST['oldpassword'], $_POST['newpassword']);
				if( $rc['stat'] != 'ok' ) {
                    $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to set your new password, please try again.');
				} else {
                    $page['blocks'][] = array('type'=>'formmessage', 'level'=>'success', 'message'=>'Your password has been updated.');
                    $display_form = 'no';
				}
			}
        }
       
        if( $display_form == 'yes' ) {
            $page['blocks'][] = array('type'=>'message', 'content'=>'If you would like to change your password, enter your old password followed by a new one.');
            $content = "<form action='' method='POST'>";
            $content .= "<div class='change-password-form'>";
            $content .= "<input type='hidden' name='action' value='update'/>";
            $content .= "<div class='input'>"
                . "<label for='oldpassword'>Old Password:</label>"
                . "<input class='text password' id='oldpassword' type='password' name='oldpassword' />"
                . "</div>";
            $content .= "<div class='input'>"
                . "<label for='newpassword'>New Password:</label>"
                . "<input class='text password' id='newpassword' type='password' name='newpassword' />"
                . "</div>";
            $content .= "<div class='submit'><input type='submit' class='button submit' value='Change Password'></div>\n";
            $content .= "</div>";
            $content .= "</form>";
            $page['blocks'][] = array('type'=>'content', 'html'=>$content);
        }
    } 
   
    //
    // Check for contact details update
    //
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'contactdetails' 
        && ((isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes')
            || (isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes')
            || (isset($settings['page-account-email-update']) && $settings['page-account-email-update'] == 'yes')
            )
        ) {
        $page['breadcrumbs'][] = array('name'=>'Contact Details', 'url'=>$ciniki['request']['domain_base_url'] . '/account/contactdetails');

        //
        // Get the customer details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
        $rc = ciniki_customers__customerDetails($ciniki, $business_id, $ciniki['session']['customer']['id'], array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to load account information. Please try again or contact us for help.');
            return array('stat'=>'ok');
        } elseif( !isset($rc['customer']) ) {
            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to load account information. Please try again or contact us for help.');
            return array('stat'=>'ok');
        }
        $customer = $rc['customer'];
//        $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($customer, true) . '</pre>');
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

        //
        // Check for updates
        //
        if( isset($_POST['action']) && $_POST['action'] == 'update' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $errors = 'no';
            $updated = 'no';
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
                    $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update contact details. Please try again or contact us for help.');
                } else {
                    $updated = 'yes';
                }
            }
//            $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($customer_args, true) . '</pre>');

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
                            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update email details. Please try again or contact us for help.');
                        } else {
                            $updated = 'yes';
                        }
                    } else {
                        $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.email', $email['id'], $email_args);
                        if( $rc['stat'] != 'ok' ) {
                            $errors = 'yes';
                            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update email details. Please try again or contact us for help.');
                        } else {
                            $updated = 'yes';
                        }
                    }
                }
//                $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($email, true) . '</pre>');
//                $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($email_args, true) . '</pre>');
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
                if( isset($_POST['province']) && $_POST['province'] != $address['province'] ) {
                    $address_args['province'] = $_POST['province'];
                    $address['province'] = $_POST['province'];
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
                            error_log("ERR: " . print_r($rc['err'], true));
                            $errors = 'yes';
                            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update address. Please try again or contact us for help.');
                        } else {
                            $updated = 'yes';
                        }
                    } else {
                        $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.address', $address['id'], $address_args);
                        if( $rc['stat'] != 'ok' ) {
                            $errors = 'yes';
                            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to update address. Please try again or contact us for help.');
                        } else {
                            $updated = 'yes';
                        }
                    }
                }
//                $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($address, true) . '</pre>');
//                $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($address_args, true) . '</pre>');
            }
            if( $updated == 'yes' ) {
                $page['blocks'][] = array('type'=>'formmessage', 'level'=>'success', 'message'=>'Your contact information is updated.');
            }
        }

        //
        // Setup the form
        //
        $form = "<form action='' method='POST'>"
            . "<input type='hidden' name='action' value='update'>"
            . "<div class='contact-details-form'>";
        if( isset($settings['page-account-email-update']) && $settings['page-account-email-update'] == 'yes' ) {
            if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x20000000) > 0 ) {
                $form .= "<div class='input email'>"
                    . "<label for='email'>Email Address</label>"
                    . "<input type='text' class='text' name='email' value='" . $email['address'] . "'>"
                    . "</div>";
            } else {
                // FIXME: Manage multiple emails
            }
        }
        if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' ) {
            if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x10000000) > 0 ) {
                $form .= "<div class='input phone_cell'>"
                    . "<label for='phone_cell'>Cell Phone Number</label>"
                    . "<input type='text' class='text' name='phone_cell' value='" . $customer['phone_cell'] . "'>"
                    . "</div>";
            } else {
                // FIXME: Manage multiple phones
            }
        }
        $form .= "<div class='input first'>"
            . "<label for='first'>First Name</label>"
            . "<input type='text' class='text' name='first' value='" . $customer['first'] . "'>"
            . "</div>";
        $form .= "<div class='input last'>"
            . "<label for='last'>Last Name</label>"
            . "<input type='text' class='text' name='last' value='" . $customer['last'] . "'>"
            . "</div>";
        if( isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' ) {
            if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x40000000) > 0 ) {
                $form .= "<div class='input address1'>"
                    . "<label for='address1'>Street Address 1</label>"
                    . "<input type='text' class='text' name='address1' value='" . $address['address1'] . "'>"
                    . "</div>";
                $form .= "<div class='input address2'>"
                    . "<label for='address2'>Street Address 2</label>"
                    . "<input type='text' class='text' name='address2' value='" . $address['address2'] . "'>"
                    . "</div>";
                $form .= "<div class='input city'>"
                    . "<label for='city'>City</label>"
                    . "<input type='text' class='text' name='city' value='" . $address['city'] . "'>"
                    . "</div>";
                $form .= "<div class='input province'>"
                    . "<label for='province'>State/Province</label>"
                    . "<input type='text' class='text' name='province' value='" . $address['province'] . "'>"
                    . "</div>";
                $form .= "<div class='input postal'>"
                    . "<label for='postal'>ZIP/Postal Code</label>"
                    . "<input type='text' class='text' name='postal' value='" . $address['postal'] . "'>"
                    . "</div>";
                $form .= "<div class='input country'>"
                    . "<label for='country'>Country</label>"
                    . "<input type='text' class='text' name='country' value='" . $address['country'] . "'>"
                    . "</div>";
            } else {
                // FIXME: Manage multiple addresses
            }
        }
        $form .= "<div class='submit'><input type='submit' class='submit' value='Save'></div>";
        $form .= "</div>"
            . "</form>";

        $page['title'] = 'Contact Details';
        $page['blocks'][] = array('type'=>'content', 'html'=>$form);
    } 

    //
    // Check for other accounts
    //
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'accounts' ) {
        $page['breadcrumbs'][] = array('name'=>'Accounts', 'url'=>$ciniki['request']['domain_base_url'] . '/account/accounts');
        $page['title'] = 'Other Accounts';

        $aside = "<p>Name: " . $ciniki['session']['customer']['display_name'] . "</p>";
        if( isset($customer['addresses']) ) {
            foreach($customer['addresses'] as $addr) {
                $addr = $addr['address'];
                if( ($addr['flags']&0x02) ) {
                    $aside .= "<p><b>Billing Address</b><br/>"
                        . preg_replace('/\n/', '<br/>', $addr['joined'])
                        . "</p>";
                }
                if( ($addr['flags']&0x01) ) {
                    $aside .= "<p><b>Shipping Address</b><br/>"
                        . preg_replace('/\n/', '<br/>', $addr['joined'])
                        . "</p>";
                }
            }
        }
        $page['blocks'][] = array('type'=>'asidecontent', 'title'=>'Account', 'html'=>$aside);

        $content = "<div class='largebutton-list'>";
        foreach($ciniki['session']['customers'] as $cust) {
            $content .= "<div class='button-list-wrap'><div class='button-list-button'>";
            $content .= "<a href='" . $ciniki['request']['base_url'] . '/account/switch/' . $cust['id'] . "'>" . $cust['display_name'] . "</a>";
            $content .= "</div></div><br/>";
        }
        $content .= "</div>";
        $page['blocks'][] = array('type'=>'content', 'html'=>$content);
    }

	return array('stat'=>'ok', 'page'=>$page);
}
?>
