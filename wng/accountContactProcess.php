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
function ciniki_customers_wng_accountContactProcess($ciniki, $tnid, &$request, $item) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    $blocks = array();

    if( $item['ref'] == 'ciniki.customers.contact' ) {
        $request['breadcrumbs'][] = array(
            'title' => 'Membership',
            'page-class' => 'page-account-contact',
            'url' => $request['base_url'] . '/account/contact',
            );
    }

    $base_url = $request['base_url'] . '/account/contact';

    //
    // Double check the account is logged in, should never reach this spot
    //
    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.554', 'msg'=>'Not logged in'));
    }

    if( isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel' && isset($_POST['f-next']) && $_POST['f-next'] == 'profile' ) {
        header("Location: " . $request['ssl_domain_base_url'] . "/account/profile");
        return array('stat'=>'exit');
    }

    //
    // Get the customers current emails, phones, and addresses
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
    $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, array(
        'customer_id' => $request['session']['customer']['id'],
        'addresses' => 'yes',
        'phones' => 'yes',
        'emails' => 'yes',
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.555', 'msg'=>'', 'err'=>$rc['err']));
    } 
    $customer = $rc['customer'];
    if( !isset($customer['phones']) ) {
        $customer['phones'] = array();
    }
    if( !isset($customer['emails']) ) {
        $customer['emails'] = array();
    }
    if( !isset($customer['addresses']) ) {
        $customer['addresses'] = array();
    }
    

    $editable = 'no';
    if( isset($request['uri_split'][($request['cur_uri_pos']+2)])
        && $request['uri_split'][($request['cur_uri_pos']+2)] == 'edit' 
        ) {
        $editable = 'yes';
    }

    //
    // Trim all field inputs
    //
    if( isset($_POST) ) {
        foreach($_POST as $k => $v) {
            $_POST[$k] = trim($v);
        }
    }

    //
    // Build the form fields
    //
    $fields = array();
    $formDataAdd = '';
    $fields['action'] = array(
        'id' => 'action',
        'ftype' => 'hidden',
        'value' => 'update',
        );
    $fields['next'] = array(
        'id' => 'next',
        'ftype' => 'hidden',
        'value' => isset($_GET['next']) ? $_GET['next'] : '',
        );
    if( $editable == 'no' ) {
        $name = $customer['prefix'];
        $name .= ($name != '' ? ' ' : '') . $customer['first'];
        $name .= ($name != '' ? ' ' : '') . $customer['middle'];
        $name .= ($name != '' ? ' ' : '') . $customer['last'];
        $name .= ($name != '' ? ' ' : '') . $customer['suffix'];
        if( $customer['type'] == 2 ) {
            $fields['company'] = array(
                'id' => 'company',
                'label' => 'Business Name',
                'ftype' => 'text',
                'size' => 'large',
                'editable' => 'no',
                'value' => $customer['company'],
                );
            $fields['name'] = array(
                'id' => 'name',
                'label' => 'Contact',
                'ftype' => 'text',
                'size' => 'large',
                'editable' => 'no',
                'value' => $name,
                );
        } else {
            $fields['name'] = array(
                'id' => 'name',
                'label' => 'Name',
                'ftype' => 'text',
                'size' => 'large',
                'editable' => 'no',
                'value' => $name,
                );
            if( $customer['company'] != '' ) {
                $fields['company'] = array(
                    'id' => 'company',
                    'label' => 'Business Name',
                    'ftype' => 'text',
                    'size' => 'medium',
                    'editable' => 'no',
                    'value' => $customer['company'],
                    );
            }
        }
        foreach($customer['phones'] as $phone) {
            $fields["phone_{$phone['phone_label']}"] = array(
                'id' => "phone_{$phone['phone_label']}",
                'label' => $phone['phone_label'] . ($phone['phone_label'] != 'Fax' ? ' Phone' : ''),
                'ftype' => 'text',
                'size' => 'medium',
                'editable' => 'no',
                'value' => $phone['phone_number'],
                );
        }
        foreach($customer['emails'] as $iid => $email) {
            $fields["email_{$iid}"] = array(
                'id' => "email_{$iid}",
                'label' => 'Email',
                'ftype' => 'text',
                'size' => 'medium',
                'editable' => 'no',
                'value' => $email['address'],
                );
        }
        $fields['newline1'] = array(
            'id' => 'newline1',
            'ftype' => 'newline',
            );
        foreach($customer['addresses'] as $aid => $address) {
            $fields["address_{$aid}"] = array(
                'id' => "address_{$aid}",
                'label' => ($address['label'] != 'Address' ? $address['label'] . ' ':'') . 'Address',
                'ftype' => 'textarea',
                'size' => 'medium',
                'editable' => 'no',
                'value' => $address['joined'],
                );
        }

        $blocks[] = array(
            'type' => 'form',
            'guidelines' => '',
            'title' => 'Contact Info',
            'class' => 'limit-width limit-width-60 viewonly',
            'problem-list' => '',
            'submit-hide' => 'yes',
            'fields' => $fields,
            );
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'limit-width limit-width-60 aligncenter',
            'list' => array(
                array('url'=>"/account/contact/edit" . ($item['ref'] == 'ciniki.customers.profile' ? "?next=profile" : ''), 'text' => 'Edit Contact'),
                ),
            );

        return array('stat'=>'ok', 'blocks'=>$blocks);
    } 

    //
    // Setup the fields for an editable form
    //
    $update_args = array();
    if( $customer['type'] == 2 ) {
        $fields['company'] = array(
            'id' => 'company',
            'label' => 'Business Name',
            'ftype' => 'text',
            'size' => 'large',
            'editable' => 'yes',
            'required' => 'required',
            'autocomplete' => 'organization',
            'value' => isset($_POST['f-company']) ? $_POST['f-company'] : $customer['company'],
            );
        if( isset($_POST['f-company']) && $_POST['f-company'] != $customer['company'] ) {
            $update_args['company'] = $_POST['f-company'];
        }
    }
    if( $customer['prefix'] != '' ) {
        $fields['prefix'] = array(
            'id' => 'prefix',
            'label' => 'Title (eg: Ms., Mr., etc)',
            'ftype' => 'text',
            'size' => 'tiny',
            'editable' => $editable,
            'autocomplete' => 'honorific-prefix',
            'value' => isset($_POST['f-prefix']) ? $_POST['f-prefix'] : $customer['prefix'],
            );
        if( isset($_POST['f-prefix']) && $_POST['f-prefix'] != $customer['prefix'] ) {
            $update_args['prefix'] = $_POST['f-prefix'];
        }
    }
    $fields['first'] = array(
        'id' => 'first',
        'label' => 'First Name',
        'ftype' => 'text',
        'size' => 'small',
        'editable' => $editable,
        'required' => 'yes',
        'autocomplete' => 'given-name',
        'value' => isset($_POST['f-first']) ? $_POST['f-first'] : $customer['first'],
        );
    if( isset($_POST['f-first']) && $_POST['f-first'] != $customer['first'] ) {
        $update_args['first'] = $_POST['f-first'];
    }
    if( $customer['prefix'] != '' || $customer['suffix'] != '' ) {
        $fields['newline'] = array(
            'id' => 'newline',
            'ftype' => 'newline',
            );
    }
    $fields['middle'] = array(
        'id' => 'middle',
        'label' => 'Middle',
        'ftype' => 'text',
        'size' => 'tiny',
        'editable' => $editable,
        'autocomplete' => 'additional-name',
        'value' => isset($_POST['f-middle']) ? $_POST['f-middle'] : $customer['middle'],
        );
    if( isset($_POST['f-middle']) && $_POST['f-middle'] != $customer['middle'] ) {
        $update_args['middle'] = $_POST['f-middle'];
    }
    $fields['last'] = array(
        'id' => 'last',
        'label' => 'Last Name',
        'ftype' => 'text',
        'size' => 'small',
        'editable' => $editable,
        'required' => 'yes',
        'autocomplete' => 'family-name',
        'value' => isset($_POST['f-last']) ? $_POST['f-last'] : $customer['last'],
        );
    if( isset($_POST['f-last']) && $_POST['f-last'] != $customer['last'] ) {
        $update_args['last'] = $_POST['f-last'];
    }
    if( $customer['suffix'] != '' ) {
        $fields['suffix'] = array(
            'id' => 'suffix',
            'label' => 'Degrees',
            'ftype' => 'text',
            'size' => 'tiny',
            'editable' => $editable,
            'autocomplete' => 'honorific-suffix',
            'value' => isset($_POST['f-suffix']) ? $_POST['f-suffix'] : $customer['suffix'],
            );
        if( isset($_POST['f-suffix']) && $_POST['f-suffix'] != $customer['suffix'] ) {
            $update_args['suffix'] = $_POST['f-suffix'];
        }
    }
    if( $customer['type'] != 2 ) {
        $fields['company'] = array(
            'id' => 'company',
            'label' => 'Business Name',
            'ftype' => 'text',
            'size' => 'large',
            'editable' => 'yes',
            'required' => 'required',
            'autocomplete' => 'organization',
            'value' => isset($_POST['f-company']) ? $_POST['f-company'] : $customer['company'],
            );
        if( isset($_POST['f-company']) && $_POST['f-company'] != $customer['company'] ) {
            $update_args['company'] = $_POST['f-company'];
        }
    }
    //
    // Setup the phone fields
    //
    $i = 1;
    $fields['break_phones'] = array(
        'id' => 'break_phones',
        'ftype' => 'break',
        'label' => 'Phone Numbers',
        );
    $labels = ['Cell'=>[], 'Home'=>[], 'Work'=>[], 'Cottage'=>[]];
    foreach($customer['phones'] as $pid => $phone) {
        $customer['phones'][$pid]['update_args'] = array();
        $fields["phone_label{$i}"] = array(
            'id' => "phone_label{$i}",
            'label' => 'Location',
            'ftype' => 'text',
            'size' => 'small',
            'editable' => $editable,
            'autocomplete' => 'stop',
            'value' => isset($_POST["f-phone_label{$i}"]) ? $_POST["f-phone_label{$i}"] : $phone['phone_label'],
            );
        if( isset($_POST["f-phone_label{$i}"]) && $_POST["f-phone_label{$i}"] != $phone['phone_label'] ) {
            $customer['phones'][$pid]['update_args']['phone_label'] = $_POST["f-phone_label{$i}"];
        }
        $fields["phone_number{$i}"] = array(
            'id' => "phone_number{$i}",
            'label' => 'Number',
            'ftype' => 'text',
            'size' => 'small',
            'editable' => $editable,
            'autocomplete' => 'tel',
            'value' => isset($_POST["f-phone_number{$i}"]) ? $_POST["f-phone_number{$i}"] : $phone['phone_number'],
            );
        if( isset($_POST["f-phone_number{$i}"]) && $_POST["f-phone_number{$i}"] != $phone['phone_number'] ) {
            $customer['phones'][$pid]['update_args']['phone_number'] = $_POST["f-phone_number{$i}"];
        }
        if( isset($labels[$phone['phone_label']]) ) {
            unset($labels[$phone['phone_label']]);
        }
        if( isset($settings['account-public-member-info']) 
            && $settings['account-public-member-info'] == 'yes' 
            ) {
            $phone_public = ($phone['flags']&0x08) == 0x08 ? 'yes' : 'no';
            $fields["phone_public{$i}"] = array(
                'id' => "phone_public{$i}",
                'label' => 'Public',
                'ftype' => 'select',
                'size' => 'tiny', 
                'editable' => 'yes',
                'options' => array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    ),
                'value' => isset($_POST["f-phone_public{$i}"]) ? $_POST["f-phone_public{$i}"] : $phone_public,
                );
            if( isset($_POST["f-phone_public{$i}"]) && $_POST["f-phone_public{$i}"] != $phone_public ) {
                $customer['phones'][$pid]['update_args']['phone_public'] = $_POST["f-phone_public{$i}"];
            }
        }
        $fields["newline-p{$i}"] = array(
            'id' => "newline-p{$i}",
            'ftype' => 'newline',
            );
        $i++;
    }
    if( $i <= 3 ) {
        for(; $i <= 3; $i++) {
            $label = array_key_first($labels);
            if( $label == null ) {
                $label = ''; 
            } else {
                unset($labels[$label]);
            }
            $fields["phone_label{$i}"] = array(
                'id' => "phone_label{$i}",
                'label' => 'Location',
                'ftype' => 'text',
                'size' => 'small',
                'editable' => $editable,
                'autocomplete' => 'stop',
                'value' => isset($_POST["f-phone_label{$i}"]) ? $_POST["f-phone_label{$i}"] : $label,
                );
            $fields["phone_number{$i}"] = array(
                'id' => "phone_number{$i}",
                'label' => 'Number',
                'ftype' => 'text',
                'size' => 'small',
                'editable' => $editable,
                'autocomplete' => 'tel',
                'value' => isset($_POST["f-phone_number{$i}"]) ? $_POST["f-phone_number{$i}"] : '',
                );
            if( isset($_POST["f-phone_number{$i}"]) && $_POST["f-phone_number{$i}"] != '' ) {
                $customer['phones']["new-{$i}"] = array(
                    'id' => 0,
                    'customer_id' => $customer['id'],
                    'phone_label' => $_POST["f-phone_label{$i}"],
                    'phone_number' => $_POST["f-phone_number{$i}"],
                    'flags' => (isset($_POST["f-phone_public{$i}"]) && $_POST["f-phone_public{$i}"] == 'yes' ? 0x08 : 0),
                    );
            }
            if( isset($settings['account-public-member-info']) 
                && $settings['account-public-member-info'] == 'yes' 
                ) {
                $fields["phone_public{$i}"] = array(
                    'id' => "phone_public{$i}",
                    'label' => 'Public',
                    'ftype' => 'select',
                    'size' => 'tiny', 
                    'editable' => 'yes',
                    'options' => array(
                        'no' => 'No',
                        'yes' => 'Yes',
                        ),
                    'value' => isset($_POST["f-phone_public{$i}"]) ? $_POST["f-phone_public{$i}"] : 'no',
                    );
            }
            $fields["newline-p{$i}"] = array(
                'id' => "newline-p{$i}",
                'ftype' => 'newline',
                );
        }
    }

    //
    // Setup the email fields
    //
    $i = 1;
    $fields['break_emails'] = array(
        'id' => 'break_emails',
        'ftype' => 'break',
        'label' => 'Email Addresses',
        );
    $max_emails = count($customer['emails']);
    if( $max_emails < 2 ) {
        $max_emails = 2;
    }
    for($i = 0; $i < $max_emails; $i++) {
        $fields["email_address{$i}"] = array(
            'id' => "email_address{$i}",
            'label' => "Email " . ($i+1),
            'ftype' => 'email',
            'size' => 'small',
            'editable' => 'yes',
            'autocomplete' => ($i == 0 ? 'email' : 'stop'),   // Only mark first email as autocomplete
            'value' => isset($_POST["f-email_address{$i}"]) ? $_POST["f-email_address{$i}"] : (isset($customer['emails'][$i]['address']) ? $customer['emails'][$i]['address'] : ''),
            );
        if( !isset($customer['emails'][$i]) && isset($_POST["f-email_address{$i}"]) && $_POST["f-email_address{$i}"] != '' ) {
            $customer['emails']["new-{$i}"] = array(
                'id' => 0,
                'customer_id' => $customer['id'],
                'email' => $_POST["f-email_address{$i}"],
                'flags' => isset($_POST["f-email_public{$i}"]) && $_POST["f-email_public{$i}"] == 'yes' ? 0x09 : 0x01,
                );
        } elseif( isset($customer['emails'][$i]['address']) && isset($_POST["f-email_address{$i}"]) && $_POST["f-email_address{$i}"] != $customer['emails'][$i]['address'] ) {
            $customer['emails'][$i]['update_args'] = array(
                'email' => $_POST["f-email_address{$i}"],
                );
        }
        if( isset($settings['account-public-member-info']) 
            && $settings['account-public-member-info'] == 'yes' 
            ) {
            $email_public = isset($customer['emails'][$i]['flags']) && ($customer['emails'][$i]['flags']&0x08) == 0x08 ? 'yes' : 'no';
            $fields["email_public{$i}"] = array(
                'id' => "email_public{$i}",
                'label' => 'Public',
                'ftype' => 'select',
                'size' => 'tiny', 
                'editable' => 'yes',
                'options' => array(
                    'no' => 'No',
                    'yes' => 'Yes',
                    ),
                'value' => isset($_POST["f-email_public{$i}"]) ? $_POST["f-email_public{$i}"] : $email_public,
                );
            // If email exists, check if public flag set
            if( isset($customer['emails'][$i]) && isset($_POST["f-email_public{$i}"]) && $_POST["f-email_public{$i}"] != $email_public ) {
                if( !isset($customer['emails'][$i]['update_args']) ) {
                    $customer['emails'][$i]['update_args'] = array();
                }
                if( $_POST["f-email_public{$i}"] == 'yes' ) {
                    $customer['emails'][$i]['update_args']['flags'] |= 0x08;
                } else {
                    $customer['emails'][$i]['update_args']['flags'] = $customer['emails'][$i]['update_args']['flags'] & 0xFFF7;
                }
            }
        }
        $fields["newline-e{$i}"] = array(
            'id' => "newline-e{$i}",
            'ftype' => 'newline',
            );
    }
    //
    // Setup the addresses for the customer
    //
    foreach($customer['addresses'] as $address) {
        if( ($address['flags']&0x01) == 0x01 && !isset($shipping_address) ) {
            $shipping_address = $address;
        }
        if( ($address['flags']&0x04) == 0x04 && !isset($mailing_address) ) {
            $mailing_address = $address;
        }
        if( ($address['flags']&0x08) == 0x08 && !isset($public_address) ) {
            $public_address = $address;
        }
    }
    if( !isset($shipping_address) ) {
        $shipping_address = array(
            'id' => 0,
            'customer_id' => $customer['id'],
            'address1' => '',
            'address2' => '',
            'city' => '',
            'province' => '',
            'postal' => '',
            'flags' => 0x01,
            );
    }
    if( !isset($mailing_address) ) {
        $mailing_address = array(
            'id' => 0,
            'customer_id' => $customer['id'],
            'address1' => '',
            'address2' => '',
            'city' => '',
            'province' => '',
            'postal' => '',
            'flags' => 0x04,
            );
    }
    if( !isset($public_address) ) {
        $public_address = array(
            'id' => 0,
            'customer_id' => $customer['id'],
            'address1' => '',
            'address2' => '',
            'city' => '',
            'province' => '',
            'postal' => '',
            'flags' => 0x08,
            );
    }
    $shipping_address['update_args'] = array();
    $mailing_address['update_args'] = array();
    $public_address['update_args'] = array();
    $shipping_address['str'] = '';
    $mailing_address['str'] = '';
    $public_address['str'] = '';
    foreach(['address1', 'address2', 'city', 'province', 'postal'] as $f) {
        if( isset($_POST["f-shipping_address-{$f}"]) ) {
            if( $_POST["f-shipping_address-{$f}"] != $shipping_address[$f] ) {
                $shipping_address['update_args'][$f] = $_POST["f-shipping_address-{$f}"];
            }
            $shipping_address[$f] = $_POST["f-shipping_address-{$f}"];
        }
        if( isset($_POST["f-mailing_address-{$f}"]) ) {
            if( $_POST["f-mailing_address-{$f}"] != $mailing_address[$f] ) {
                $mailing_address['update_args'][$f] = $_POST["f-mailing_address-{$f}"];
            }
            $mailing_address[$f] = $_POST["f-mailing_address-{$f}"];
        }
        if( isset($_POST["f-public_address-{$f}"]) ) {
            if( $_POST["f-public_address-{$f}"] != $public_address[$f] ) {
                $public_address['update_args'][$f] = $_POST["f-public_address-{$f}"];
            }
            $public_address[$f] = $_POST["f-public_address-{$f}"];
        }
        $shipping_address['str'] .= $shipping_address[$f];
        $mailing_address['str'] .= $mailing_address[$f];
        $public_address['str'] .= $public_address[$f];
    }
    
    if( isset($settings['account-public-member-info']) 
        && $settings['account-public-member-info'] == 'yes' 
        && ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02)   // Members
        ) {
        $fields['break_public'] = array(
            'id' => 'break_public',
            'ftype' => 'break',
            'label' => 'Public Member Address',
            );
        $fields['public_address'] = array(
            'id' => 'public_address',
            'ftype' => 'address',
            'editable' => 'yes',
            'value' => isset($public_address) ? $public_address : array(),
            );
    }
    $fields['break_mailing'] = array(
        'id' => 'break_mailing',
        'ftype' => 'break',
        'label' => 'Mailing Address',
        );
    $fields['mailing_address'] = array(
        'id' => 'mailing_address',
        'ftype' => 'address',
        'editable' => 'yes',
        'value' => isset($mailing_address) ? $mailing_address : array(),
        );
    if( isset($settings['account-shipping-address']) && $settings['account-shipping-address'] == 'yes' ) {
        $fields['break_shipping'] = array(
            'id' => 'break_shipping',
            'ftype' => 'break',
            'label' => 'Shipping Address',
            );
        $fields['shipping_address'] = array(
            'id' => 'shipping_address',
            'ftype' => 'address',
            'editable' => 'yes',
            'value' => isset($shipping_address) ? $shipping_address : array(),
            );
    }

    //
    // Check if mailing and shipping addresses are the same
    //
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
        $remove_addresses = array();
        if( $shipping_address['str'] == $mailing_address['str'] ) {
            if( isset($mailing_address['id']) && $mailing_address['id'] > 0 ) {
                if( isset($mailing_address['update_args']['flags']) ) {
                    $mailing_address['update_args']['flags'] = $mailing_address['update_args']['flags'] | 0x01;
                } elseif( ($mailing_address['flags']&0x01) == 0 ) {
                    $mailing_address['update_args']['flags'] = $mailing_address['flags'] | 0x01;
                }
                if( isset($shipping_address['id']) 
                    && $shipping_address['id'] > 0 
                    && $shipping_address['id'] != $mailing_address['id'] 
                    ) {
                    $remove_addresses[] = $shipping_address['id'];
                }
                unset($shipping_address);
            }
            elseif( isset($shipping_address['id']) && $shipping_address['id'] > 0 ) {
                if( isset($shipping_address['update_args']['flags']) ) {
                    $shipping_address['update_args']['flags'] = $shipping_address['update_args']['flags'] | 0x04;
                } elseif( ($shipping_address['flags']&0x04) == 0 ) {
                    $shipping_address['update_args']['flags'] = $shipping_address['flags'] | 0x04;
                }
                if( $mailing_address['id'] > 0 && !in_array($mailing_address['id'], $remove_addresses) ) {
                    $remove_addresses[] = $mailing_address['id'];
                }
                unset($mailing_address);
            }
        }
        //
        // Check if mailing and public are the same
        //
        if( isset($mailing_address) && isset($public_address) && $public_address['str'] == $mailing_address['str'] ) {
            if( isset($mailing_address['id']) && $mailing_address['id'] > 0 ) {
                if( isset($mailing_address['update_args']['flags']) ) {
                    $mailing_address['update_args']['flags'] = $mailing_address['update_args']['flags'] | 0x08;
                } elseif( ($mailing_address['flags']&0x08) == 0 ) {
                    $mailing_address['update_args']['flags'] = $mailing_address['flags'] | 0x08;
                }
                if( isset($public_address['id']) 
                    && $public_address['id'] > 0 
                    && $public_address['id'] != $mailing_address['id'] 
                    ) {
                    $remove_addresses[] = $public_address['id'];
                }
                unset($public_address);
            }
        }
        if( isset($shipping_address) && isset($public_address) && $public_address['str'] == $shipping_address['str'] ) {
            if( isset($shipping_address['id']) && $shipping_address['id'] > 0 ) {
                if( isset($shipping_address['update_args']['flags']) ) {
                    $shipping_address['update_args']['flags'] = $shipping_address['update_args']['flags'] | 0x08;
                } elseif( ($shipping_address['flags']&0x08) == 0 ) {
                    $shipping_address['update_args']['flags'] = $shipping_address['flags'] | 0x08;
                }
                if( isset($public_address['id']) 
                    && $public_address['id'] > 0 
                    && $public_address['id'] != $shipping_address['id'] 
                    && !in_array($public_address['id'], $remove_addresses)
                    ) {
                    $remove_addresses[] = $public_address['id'];
                }
                unset($public_address);
            }
        }
        //
        // Check if addresses need to be separate, same ID, different addresses
        //
        if( isset($mailing_address) && isset($shipping_address) 
            && $mailing_address['str'] != $shipping_address['str'] 
            && $mailing_address['id'] == $shipping_address['id'] 
            ) {
            $shipping_address['id'] = 0;
            $shipping_address['customer_id'] = $customer['id'];
            $shipping_address['flags'] = 0x01;
            // Remove shipping flag
            if( isset($mailing_address['update_args']['flags']) ) {
                $mailing_address['update_args']['flags'] = $mailing_address['update_args']['flags'] & 0xFFFE;
            } elseif( ($mailing_address['flags']&0x01) == 0x01 ) {
                $mailing_address['update_args']['flags'] = $mailing_address['flags'] & 0xFFFE;
            }
        }
        if( isset($mailing_address) && isset($public_address) 
            && $mailing_address['str'] != $public_address['str'] 
            && $mailing_address['id'] == $public_address['id'] 
            ) {
            $public_address['id'] = 0;
            $public_address['customer_id'] = $customer['id'];
            $public_address['flags'] = 0x08;
            // Remove public flag from address
            if( isset($mailing_address['update_args']['flags']) ) {
                $mailing_address['update_args']['flags'] = $mailing_address['update_args']['flags'] & 0xFFF7;
            } elseif( ($mailing_address['flags']&0x08) == 0x08 ) {
                $mailing_address['update_args']['flags'] = $mailing_address['flags'] & 0xFFF7;
            }
        }
        if( isset($shipping_address) && isset($public_address) 
            && $shipping_address['str'] != $public_address['str'] 
            && $shipping_address['id'] == $public_address['id'] 
            ) {
            $public_address['id'] = 0;
            $public_address['customer_id'] = $customer['id'];
            $public_address['flags'] = 0x08;
            // Remove public flag from address
            if( isset($shipping_address['update_args']['flags']) ) {
                $shipping_address['update_args']['flags'] = $shipping_address['update_args']['flags'] & 0xFFF7;
            } elseif( ($shipping_address['flags']&0x08) == 0x08 ) {
                $shipping_address['update_args']['flags'] = $shipping_address['flags'] & 0xFFF7;
            }
        }
    }

    //
    // Check for any errors to information submitted
    //
    $problem_list = '';
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        //
        // Run any updates on the customer
        //
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $customer['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                $problem_list .= "We had an error (#1002) updating your information, please try again or contact us for help.";
            }
        }
        foreach($customer['phones'] as $phone) {
            if( $problem_list != '' ) {
                break;
            }
            // Remove phone number
            if( isset($phone['update_args']['phone_number']) && $phone['update_args']['phone_number'] == '' ) {
                $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.phone', $phone['id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1003) updating your information, please try again or contact us for help.";
                }
            } 
            // Update phone
            elseif( isset($phone['update_args']) && count($phone['update_args']) > 0 ) {
                if( isset($phone['update_args']['phone_public']) && $phone['update_args']['phone_public'] == 'yes' && ($phone['flags']&0x08) == 0 ) {
                    $phone['update_args']['flags'] = $phone['flags'] | 0x08;
                } elseif( isset($phone['update_args']['phone_public']) && $phone['update_args']['phone_public'] == 'no' && ($phone['flags']&0x08) == 0x08 ) {
                    $phone['update_args']['flags'] = $phone['flags'] & 0xFFF7;
                }
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.phone', $phone['id'], $phone['update_args'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1004) updating your information, please try again or contact us for help.";
                }
            } 
            // Add phone
            elseif( isset($phone['id']) && $phone['id'] == 0 ) {
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.phone', $phone, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1005) updating your information, please try again or contact us for help.";
                }
            }
        }
        foreach($customer['emails'] as $email) {
            if( $problem_list != '' ) {
                break;
            }
            if( isset($email['update_args']['address']) && $email['update_args']['address'] == '' ) {
                // Remove email address
                $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.email', $email['id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1006) updating your information, please try again or contact us for help.";
                }
            } elseif( isset($email['update_args']) && count($email['update_args']) > 0 ) {
                if( isset($email['update_args']['email_public']) && $email['update_args']['email_public'] == 'yes' && ($email['flags']&0x08) == 0 ) {
                    $email['update_args']['flags'] = $email['flags'] | 0x08;
                } elseif( isset($email['update_args']['email_public']) && $email['update_args']['email_public'] == 'no' && ($email['flags']&0x08) == 0x08 ) {
                    $email['update_args']['flags'] = $email['flags'] & 0xFFF7;
                }
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.email', $email['id'], $email['update_args'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1007) updating your information, please try again or contact us for help.";
                }
            } elseif( isset($email['id']) && $email['id'] == 0 ) {
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email', $email, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1008) updating your information, please try again or contact us for help.";
                }
            }
        }

        //
        // Update addresses
        //
        if( isset($mailing_address) ) {
            // Add mailing address
            if( $mailing_address['id'] == 0 && $mailing_address['str'] != '' ) {
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $mailing_address, 0x04);
                if( $rc['stat'] != 'ok' ) { 
                    $problem_list .= "We had an error (#1009) updating your information, please try again or contact us for help.";
                }
            }
            // Update address
            elseif( isset($mailing_address['update_args']) && count($mailing_address['update_args']) > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $mailing_address['id'], $mailing_address['update_args'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1010) updating your information, please try again or contact us for help.";
                }
            }
        }
        if( isset($shipping_address) ) {
            // Add shipping address
            if( $shipping_address['id'] == 0 && $shipping_address['str'] != '' ) {
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $shipping_address, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1011) updating your information, please try again or contact us for help.";
                }
            }
            // Update address
            elseif( isset($shipping_address['update_args']) && count($shipping_address['update_args']) > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $shipping_address['id'], $shipping_address['update_args'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1012) updating your information, please try again or contact us for help.";
                }
            }
        }
        if( isset($public_address) ) {
            // Add public address
            if( $public_address['id'] == 0 && $public_address['str'] != '' ) {
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $public_address, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1013) updating your information, please try again or contact us for help.";
                }
            }
            // Update address
            elseif( isset($public_address['update_args']) && count($public_address['update_args']) > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $public_address['id'], $public_address['update_args'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1014) updating your information, please try again or contact us for help.";
                }
            }
        }

        //
        // Check for any addresses to remove
        //
        if( isset($remove_addresses) ) {
            foreach($remove_addresses as $aid) {
                $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.address', $address['id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    $problem_list .= "We had an error (#1015) updating your information, please try again or contact us for help.";
                }
            }
        }

        if( $problem_list == '' ) {
            if( isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel' && isset($_POST['f-next']) && $_POST['f-next'] == 'profile' ) {
                header("Location: " . $request['ssl_domain_base_url'] . "/account/profile");
            } else {
                header("Location: " . $request['ssl_domain_base_url'] . "/account/contact");
            }
            return array('stat'=>'exit');
        }
    }

    $blocks[] = array(
        'type' => 'form',
        'guidelines' => '',
        'title' => 'Contact Info',
        'class' => 'limit-width limit-width-60',
        'problem-list' => $problem_list,
        'cancel-label' => 'Cancel',
        'submit-label' => 'Save',
        'fields' => $fields,
        );

//    $blocks[] = array(
//        'type' => 'html',
//        'html' => '<pre>' . print_r($customer, true) . '</pre>',
//        );


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
