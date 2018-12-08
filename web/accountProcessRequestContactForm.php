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
    if( isset($args['customer_id']) && $args['customer_id'] >= 0 ) {
        $customer_id = $args['customer_id'];
    } elseif( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $customer_id = $ciniki['session']['customer']['id'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.338', 'msg'=>'Invalid account'));
    }

    $blocks = array();

    //
    // The required fields
    //
    $required = array('first', 'last');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'countryCodes');
    $rc = ciniki_core_countryCodes($ciniki);
    $country_codes = $rc['countries'];
    $province_codes = $rc['provinces'];

    //
    // Load the customer details
    //
    if( $customer_id == 0 ) {
        if( !isset($args['type']) ) {
            if( $ciniki['session']['account']['type'] == 20 ) { 
                $args['type'] = 22;
            } elseif( $ciniki['session']['account']['type'] == 30 ) { 
                $args['type'] = 32;
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.343', 'msg'=>'Invalid account'));
            }
        }
        $customer = array(
            'id' => 0,
            'parent_id' => isset($ciniki['session']['account']['id']) ? $ciniki['session']['account']['id'] : 0,
            'uuid' => '',
            'display_name' => '',
            'type' => $args['type'],
            'prefix' => '',
            'first' => '',
            'middle' => '',
            'last' => '',
            'suffix' => '',
            'company' => '',
            'primary_email' => '',
            'secondary_email' => '',
            'phone_cell' => '',
            'phone_home' => '',
            'phone_work' => '',
            'phone_fax' => '',
            'mailing_address1' => '',
            'mailing_address2' => '',
            'mailing_city' => '',
            'mailing_province' => '',
            'mailing_postal' => '',
            'mailing_country' => '',
            'mailing_flags' => 0x06,
            'billing_address1' => '',
            'billing_address2' => '',
            'billing_city' => '',
            'billing_province' => '',
            'billing_postal' => '',
            'billing_country' => '',
            );
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerLoad');
        $rc = ciniki_customers_customerLoad($ciniki, $tnid, $customer_id);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.230', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        $customer = $rc['customer'];
    }

    //
    // Check for adding a parent/child/admin/employee
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    if( isset($_POST['action']) && $_POST['action'] == 'add' ) {
        //
        // Setup transaction
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
        $errors = 'no';
        $updated = 'no';
        foreach($customer as $field => $value) {
            if( $field == 'id' || $field == 'type' ) {
                continue;
            }
            if( isset($_POST[$field]) ) {
                $customer[$field] = trim($_POST[$field]);
            }
        }
        if( isset($_POST['type']) && $_POST['type'] > 0 ) {
            if( $customer['type'] == 21 && $_POST['type'] == 22 ) {
                $customer['type'] = 22;
            } elseif( $customer['type'] == 22 && $_POST['type'] == 21 ) {
                $customer['type'] = 21;
            } elseif( $customer['type'] == 31 && $_POST['type'] == 32 ) {
                $customer['type'] = 32;
            } elseif( $customer['type'] == 32 && $_POST['type'] == 31 ) {
                $customer['type'] = 31;
            }
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
        $rc = ciniki_customers_customerUpdateName($ciniki, $tnid, $customer, 0, $customer);
        if( $rc['stat'] == 'ok' ) {
            $customer['display_name'] = $rc['display_name'];
            $customer['sort_name'] = $rc['sort_name'];
            $customer['permalink'] = $rc['permalink'];
        }

        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x8000) ) {
            if( isset($_POST['birthdate']) ) {
                $ts = strtotime($_POST['birthdate']);
                if( $ts === FALSE ) {
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to understand birthdate, please enter a proper date.');
                    $errors = 'yes';
                } else {
                    $customer['birthdate'] = strftime("%Y-%m-%d", $ts);
                }
            }
        }

        //
        // Add the customer record
        //
        if( $errors == 'no' ) {
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.customer', $customer, 0x04);
            if( $rc['stat'] != 'ok' ) {
                if( $ciniki['session']['account']['type'] == 10 ) {
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to add the account');
                } elseif( $customer['type'] == 21 ) {
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to add the Parent/Guardian');
                } elseif( $customer['type'] == 22 ) {
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to add the child');
                } elseif( $customer['type'] == 31 ) {
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to add the administrator');
                } elseif( $customer['type'] == 32 ) {
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to add the employee');
                }
                $errors = 'yes';
            } else {
                $updated = 'yes';
                $customer_id = $rc['id'];
                $customer['id'] = $rc['id'];
                $customer['uuid'] = $rc['uuid'];

                //
                // add the emails
                //
                $fields = array(
                    'primary_email' => array('name'=>'Primary Email'),
                    'secondary_email' => array('name'=>'Secondary Email'),
                    );
                foreach($fields as $field => $details) {
                    if( !isset($_POST[$field]) && in_array($field, $required) ) {
                        $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'You must provide a ' . $details['name'] . ' address');
                        $errors = 'yes';
                    } elseif( isset($_POST[$field]) && $_POST[$field] != '' ) {
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
                                $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to add email address');
                                $errors = 'yes';
                            } else {
                                $updated = 'yes';
                            }
                        }
                    }
                }

                //
                // Check for phone changes
                //
                foreach(['phone_home', 'phone_work', 'phone_cell', 'phone_fax'] as $field) {
                    if( isset($_POST[$field]) ) {
                        $label = ucfirst(str_replace('phone_', '', $field));
                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.phone', array(
                            'customer_id'=>$customer_id,
                            'phone_label'=>$label,
                            'phone_number'=>trim($_POST[$field]),
                            'flags'=>0,
                            ), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to add phone number');
                            $errors = 'yes';
                        } else {
                            $updated = 'yes';
                        }
                    }
                }

                //
                // Check for address updates
                //
                $_POST['mailing_flags'] = (isset($customer['mailing_flags']) ? $customer['mailing_flags'] : 0x06);
                if( isset($_POST['billingflag']) ) {
                    if( $_POST['billingflag'] == 'yes' ) {
                        $_POST['mailing_flags'] |= 0x02;
                    } elseif( $_POST['billingflag'] == 'no' ) {
                        $_POST['mailing_flags'] = ($_POST['mailing_flags']&0xfd);
                    }
                }
                $mailing_country = isset($_POST['mailing_country']) ? $_POST['mailing_country'] : $customer['mailing_country'];
                if( isset($_POST['mailing_province_code_' . $mailing_country]) ) {
                    $_POST['mailing_province'] = $_POST['mailing_province_code_' . $mailing_country];
                }

                //
                // Check if secondary address needs to be updated.
                // This must be done first incase any changes to mailing flags
                //
                if( ($_POST['mailing_flags']&0x02) == 0 ) {
                    $billing_country = isset($_POST['billing_country']) ? $_POST['billing_country'] : $customer['billing_country'];
                    if( isset($_POST['billing_province_code_' . $billing_country]) ) {
                        $_POST['billing_province'] = $_POST['billing_province_code_' . $billing_country];
                    }
                    $addr = array(
                        'customer_id' => $customer_id,
                        'address1' => (isset($_POST['billing_address1']) ? $_POST['billing_address1'] : (isset($customer['billing_address1']) ? $customer['billing_address1'] : '')),
                        'address2' => (isset($_POST['billing_address2']) ? $_POST['billing_address2'] : (isset($customer['billing_address2']) ? $customer['billing_address2'] : '')),
                        'city' => (isset($_POST['billing_city']) ? $_POST['billing_city'] : (isset($customer['billing_city']) ? $customer['billing_city'] : '')),
                        'province' => (isset($_POST['billing_province']) ? $_POST['billing_province'] : (isset($customer['billing_province']) ? $customer['billing_province'] : '')),
                        'postal' => (isset($_POST['billing_postal']) ? $_POST['billing_postal'] : (isset($customer['billing_postal']) ? $customer['billing_postal'] : '')),
                        'country' => (isset($_POST['billing_country']) ? $_POST['billing_country'] : (isset($customer['billing_country']) ? $customer['billing_country'] : '')),
                        'flags' => 0x02,
                        );
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $addr, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.341', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
                    }
                } 

                //
                // Check if any changes to mailing address
                //
                if( isset($_POST['mailing_address1']) || isset($_POST['mailing_address2']) || isset($_POST['mailing_city']) 
                    || isset($_POST['mailing_province']) || isset($_POST['mailing_postal']) || isset($_POST['mailing_country']) 
                    || $_POST['mailing_flags'] != $customer['mailing_flags']
                    ) {
                    $addr = array(
                        'customer_id' => $customer_id,
                        'address1' => (isset($_POST['mailing_address1']) ? $_POST['mailing_address1'] : (isset($customer['mailing_address1']) ? $customer['mailing_address1'] : '')),
                        'address2' => (isset($_POST['mailing_address2']) ? $_POST['mailing_address2'] : (isset($customer['mailing_address2']) ? $customer['mailing_address2'] : '')),
                        'city' => (isset($_POST['mailing_city']) ? $_POST['mailing_city'] : (isset($customer['mailing_city']) ? $customer['mailing_city'] : '')),
                        'province' => (isset($_POST['mailing_province']) ? $_POST['mailing_province'] : (isset($customer['mailing_province']) ? $customer['mailing_province'] : '')),
                        'postal' => (isset($_POST['mailing_postal']) ? $_POST['mailing_postal'] : (isset($customer['mailing_postal']) ? $customer['mailing_postal'] : '')),
                        'country' => (isset($_POST['mailing_country']) ? $_POST['mailing_country'] : (isset($customer['mailing_country']) ? $customer['mailing_country'] : '')),
                        'flags' => (isset($_POST['mailing_flags']) ? $_POST['mailing_flags'] : (isset($customer['mailing_flags']) ? $customer['mailing_flags'] : '')),
                        );
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $addr, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.370', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
                    }
                } 

                //
                // Add to the session
                //
                if( $customer['type'] == 21 || $customer['type'] == 31 ) {
                    $ciniki['session']['account']['parents'][] = $customer;
                    usort($ciniki['session']['account']['parents'], function($a, $b) {
                        return strcmp($a['display_name'], $b['display_name']);
                        });
                    $_SESSION['account']['parents'] = $ciniki['session']['account']['parents'];
                } else {
                    $ciniki['session']['account']['children'][] = $customer;
                    usort($ciniki['session']['account']['children'], function($a, $b) {
                        return strcmp($a['display_name'], $b['display_name']);
                        });
                    $_SESSION['account']['children'] = $ciniki['session']['account']['children'];
                }
            }
        }
        if( $errors == 'no' ) {
            ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
            return array('stat'=>'updated');
        } else {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        }
    }
    
    //
    // Check for any updates
    //
    elseif( isset($_POST['action']) && $_POST['action'] == 'update' ) {
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

        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x8000) ) {
            if( isset($_POST['birthdate']) ) {
                $ts = strtotime($_POST['birthdate']);
                if( $ts === FALSE ) {
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Unable to understand birthdate, please enter a proper date.');
                    $errors = 'yes';
                } else {
                    $customer_args['birthdate'] = strftime("%Y-%m-%d", $ts);
                }
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
                            $_SESSION['account']['children'] = $ciniki['session']['account']['children'];
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

        //
        // Check for address updates
        //
        $_POST['mailing_flags'] = (isset($customer['mailing_flags']) ? $customer['mailing_flags'] : 0x06);
        if( isset($_POST['billingflag']) ) {
            if( $_POST['billingflag'] == 'yes' ) {
                $_POST['mailing_flags'] |= 0x02;
            } elseif( $_POST['billingflag'] == 'no' ) {
                $_POST['mailing_flags'] = ($_POST['mailing_flags']&0xfd);
            }
        }
        $mailing_country = isset($_POST['mailing_country']) ? $_POST['mailing_country'] : $customer['mailing_country'];
        if( isset($_POST['mailing_province_code_' . $mailing_country]) ) {
            $_POST['mailing_province'] = $_POST['mailing_province_code_' . $mailing_country];
        }

        //
        // Check if secondary address needs to be updated.
        // This must be done first incase any changes to mailing flags
        //
        if( ($_POST['mailing_flags']&0x02) == 0 ) {
            $billing_country = isset($_POST['billing_country']) ? $_POST['billing_country'] : $customer['billing_country'];
            if( isset($_POST['billing_province_code_' . $billing_country]) ) {
                $_POST['billing_province'] = $_POST['billing_province_code_' . $billing_country];
            }
            $addr = array(
                'customer_id' => $customer_id,
                'address1' => (isset($_POST['billing_address1']) ? $_POST['billing_address1'] : (isset($customer['billing_address1']) ? $customer['billing_address1'] : '')),
                'address2' => (isset($_POST['billing_address2']) ? $_POST['billing_address2'] : (isset($customer['billing_address2']) ? $customer['billing_address2'] : '')),
                'city' => (isset($_POST['billing_city']) ? $_POST['billing_city'] : (isset($customer['billing_city']) ? $customer['billing_city'] : '')),
                'province' => (isset($_POST['billing_province']) ? $_POST['billing_province'] : (isset($customer['billing_province']) ? $customer['billing_province'] : '')),
                'postal' => (isset($_POST['billing_postal']) ? $_POST['billing_postal'] : (isset($customer['billing_postal']) ? $customer['billing_postal'] : '')),
                'country' => (isset($_POST['billing_country']) ? $_POST['billing_country'] : (isset($customer['billing_country']) ? $customer['billing_country'] : '')),
                'flags' => (isset($_POST['billing_flags']) ? $_POST['billing_flags'] : (isset($customer['billing_flags']) ? $customer['billing_flags'] : '')),
                );
            //
            // Check if address blank, then remove
            //
            if( $addr['address1'] == '' && $addr['address2'] == '' 
                && $addr['city'] == '' && $addr['province'] == '' && $addr['postal'] == '' && $addr['country'] == '' 
                ) {
                if( $customer['billing_address_id'] > 0 ) {
                    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.address', $customer['billing_address_id'], null, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.339', 'msg'=>'Unable to remove address', 'err'=>$rc['err']));
                    }
                }
                $_POST['mailing_flags'] |= 0x02;
            }
            // Update
            elseif( $customer['billing_address_id'] > 0 ) {
                $update_args = array();
                foreach(['address1', 'address2', 'city', 'province', 'postal', 'country'] as $field) {
                    if( isset($_POST['billing_' . $field]) && $_POST['billing_' . $field] != $customer['billing_' . $field] ) {
                        $update_args[$field] = $_POST['billing_' . $field];
                    }
                }
                if( count($update_args) > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $customer['billing_address_id'], $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.340', 'msg'=>'Unable to update address', 'err'=>$rc['err']));
                    }
                }
            } 
            // Add
            else {
                $addr['flags'] = 0x02;
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $addr, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.371', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
                }
            }
        } 
        // Check if secondary address should be deleted
        elseif( ($_POST['mailing_flags']&0x02) == 0x02 && $customer['billing_address_id'] > 0 ) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.address', $customer['billing_address_id'], null, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.342', 'msg'=>'Unable to remove address', 'err'=>$rc['err']));
            }
            $customer['billing_address_id'] = 0;
        }

        //
        // Check if any changes to mailing address
        //
        if( isset($_POST['mailing_address1']) || isset($_POST['mailing_address2']) || isset($_POST['mailing_city']) 
            || isset($_POST['mailing_province']) || isset($_POST['mailing_postal']) || isset($_POST['mailing_country']) 
            || $_POST['mailing_flags'] != $customer['mailing_flags']
            ) {
            $addr = array(
                'customer_id' => $customer_id,
                'address1' => (isset($_POST['mailing_address1']) ? $_POST['mailing_address1'] : (isset($customer['mailing_address1']) ? $customer['mailing_address1'] : '')),
                'address2' => (isset($_POST['mailing_address2']) ? $_POST['mailing_address2'] : (isset($customer['mailing_address2']) ? $customer['mailing_address2'] : '')),
                'city' => (isset($_POST['mailing_city']) ? $_POST['mailing_city'] : (isset($customer['mailing_city']) ? $customer['mailing_city'] : '')),
                'province' => (isset($_POST['mailing_province']) ? $_POST['mailing_province'] : (isset($customer['mailing_province']) ? $customer['mailing_province'] : '')),
                'postal' => (isset($_POST['mailing_postal']) ? $_POST['mailing_postal'] : (isset($customer['mailing_postal']) ? $customer['mailing_postal'] : '')),
                'country' => (isset($_POST['mailing_country']) ? $_POST['mailing_country'] : (isset($customer['mailing_country']) ? $customer['mailing_country'] : '')),
                'flags' => (isset($_POST['mailing_flags']) ? $_POST['mailing_flags'] : (isset($customer['mailing_flags']) ? $customer['mailing_flags'] : '')),
                );
            //
            // Check if address blank, then remove
            //
            if( $addr['address1'] == '' && $addr['address2'] == '' 
                && $addr['city'] == '' && $addr['province'] == '' && $addr['postal'] == '' && $addr['country'] == '' 
                ) {
                if( $customer['mailing_address_id'] > 0 ) {
                    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.customers.address', $customer['mailing_address_id'], null, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.292', 'msg'=>'Unable to remove address', 'err'=>$rc['err']));
                    }
                }
            }
            elseif( $addr['address1'] != '' || $addr['address2'] != '' || $addr['city'] != '' || $addr['province'] != '' || $addr['postal'] != '' || $addr['country'] != '' ) {
                if( $customer['mailing_address_id'] == 0 ) {
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $addr, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.293', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
                    }
                } else {
                    $update_args = array();
                    foreach(['address1', 'address2', 'city', 'province', 'postal', 'country', 'flags'] as $field) {
                        if( isset($addr[$field]) && $addr[$field] != $customer['mailing_' . $field] ) {
                            $update_args[$field] = $addr[$field];
                        }
                    }
                    if( count($update_args) > 0 ) {
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $customer['mailing_address_id'], $update_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.294', 'msg'=>'Unable to update address', 'err'=>$rc['err']));
                        }
                    }
                }
            }
        }

        if( $errors == 'no' ) {
            return array('stat'=>'updated');
        }
    }

    //
    // Display the form
    //
    $form = "<form action='' method='POST'>";
    if( $customer['id'] == 0 ) {
        $form .= "<input type='hidden' name='action' value='add'>";
    } else {
        $form .= "<input type='hidden' name='action' value='update'>";
    }
    $form .= "<div class='contact-details-form'>";
    $form .= "<div class='contact-details-section contact-details-form-name'>";
    $form .= "<div class='input first'>"
        . "<label for='first'>First Name" . (in_array('first', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='first' value='" . $customer['first'] . "'>"
        . "</div>";
    $form .= "<div class='input last'>"
        . "<label for='last'>Last Name" . (in_array('last', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='last' value='" . $customer['last'] . "'>"
        . "</div>";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x8000) ) {
        $form .= "<div class='input birthdate'>"
            . "<label for='last'>Birthday" . (in_array('birthdate', $required)?' *':'') . "</label>"
            . "<input type='text' class='text' name='birthdate' value='" . $customer['birthdate'] . "'>"
            . "</div>";
    }
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
    if( $customer['mailing_country'] == '' ) {
        $customer['mailing_country'] = 'Canada';
    }
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
    if( $customer['mailing_province'] == '' ) {
        $customer['mailing_province'] = 'ON';
    }
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
    $form .= "<div class='contact-details-section contact-details-form-billing'>";
    $form .= "<div class='input'>";
    $form .= "<label for='billingflag'>Same billing address</label>"
        . "<select id='billingflag' name='billingflag' type='select' class='select' onchange='updateBillingForm();'>";
    if( ($customer['mailing_flags']&0x02) == 0x02 ) {
        $form .= "<option value='yes' selected>Yes</option>"
            . "<option value='no'>No</option>";
    } else {
        $form .= "<option value='yes'>Yes</option>"
            . "<option value='no' selected>No</option>";
    }
    $form .= "</select>"
        . "</div>";
    if( ($customer['mailing_flags']&0x02) == 0x02 ) {
        $form .= "<div id='billingform' style='display:none;'>";
    } else {
        $form .= "<div id='billingform'>";
    }
    $form .= "<div class='input billing_address1'>"
        . "<label for='billing_address1'>Billing Address Line 1" . (in_array('billing_address1', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='billing_address1' value='" . $customer['billing_address1'] . "'>"
        . "</div>";
    $form .= "<div class='input billing_address2'>"
        . "<label for='billing_address2'>Line 2" . (in_array('billing_address2', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='billing_address2' value='" . $customer['billing_address2'] . "'>"
        . "</div>";
    $form .= "<div class='input billing_city'>"
        . "<label for='billing_city'>City" . (in_array('billing_city', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='billing_city' value='" . $customer['billing_city'] . "'>"
        . "</div>";
    $form .= "<div class='input billing_country'>"
        . "<label for='billing_country'>Country" . (in_array('billing_country', $required)?' *':'') . "</label>"
        . "<select id='billing_country_code' type='select' class='select' name='billing_country' onchange='updateBillingProvince()'>"
        . "<option value=''></option>";
    $selected_country = 'Canada';
    foreach($country_codes as $country_code => $country_name) {
        $form .= "<option value='" . $country_code . "' " 
            . (($country_code == $customer['billing_country'] || $country_name == $customer['billing_country'])?' selected':'')
            . ">" . $country_name . "</option>";
        if( $country_code == $customer['billing_country'] || $country_name == $customer['billing_country'] ) {
            $selected_country = $country_code;
        }
    }
    $form .= "</select></div>";
    $form .= "<div class='input billing_province'>"
        . "<label for='billing_province'>State/Province" . (in_array('billing_province', $required)?' *':'') . "</label>"
        . "<input id='billing_province_text' type='text' class='text' name='billing_province' "
            . (isset($province_codes[$selected_country])?" style='display:none;'":"")
            . "value='" . $customer['billing_province'] . "'>";
    $js = '';
    foreach($province_codes as $country_code => $provinces) {
        $form .= "<select id='billing_province_code_{$country_code}' type='select' class='select' "
            . (($country_code != $selected_country)?" style='display:none;'":"")
            . "name='billing_province_code_{$country_code}' >"
            . "<option value=''></option>";
        $js .= "document.getElementById('billing_province_code_" . $country_code . "').style.display='none';";
        foreach($provinces as $province_code => $province_name) {
            $form .= "<option value='" . $province_code . "'" 
                . (($province_code == $customer['billing_province'] || $province_name == $customer['billing_province'])?' selected':'')
                . ">" . $province_name . "</option>";
        }
        $form .= "</select>";
    }
    $form .= "</div>";
    $form .= "<div class='input billing_postal'>"
        . "<label for='billing_postal'>ZIP/Postal Code" . (in_array('billing_postal', $required)?' *':'') . "</label>"
        . "<input type='text' class='text' name='billing_postal' value='" . $customer['billing_postal'] . "'>"
        . "</div>";
    $form .= "</div>"; // End wrapper div id billingform 
    $form .= "<script type='text/javascript'>"
        . "function updateBillingForm() {"
            . "var f = document.getElementById('billingflag').value;"
            . "if(f=='yes'){"
                . "document.getElementById('billingform').style.display = 'none';"
            . "}else{"
                . "document.getElementById('billingform').style.display = 'block';"
            . "}"
            . "console.log('updateform');"
        . "}"
        . "function updateBillingProvince() {"
            . "var cc = document.getElementById('billing_country_code');"
            . "var pr = document.getElementById('billing_province_text');"
            . "var pc = document.getElementById('billing_province_code_'+cc.value);"
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
    $form .= "</div>";

    $blocks[] = array('type'=>'content', 'html'=>$form);
//    $blocks[] = array('type'=>'content', 'html'=>'<pre>' . print_r($customer, true) . '</pre>');

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
