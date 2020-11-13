<?php
//
// Description
// -----------
// This method is used for updating an account or parent/child for the account. This method
// should only be used when the accounts/ifb flag has been set on the tenant.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_customerUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'parent_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Parent'), 
        'eid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Type'), 
//        'member_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member Status'), 
//        'member_lastpaid'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Member Last Paid'),
//        'membership_length'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Membership Length'),
//        'membership_type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Membership Type'),
//        'dealer_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Dealer Status'), 
//        'distributor_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Distributor Status'), 
        'callsign'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Callsign'), 
        'prefix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Prefix'), 
        'first'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Suffix'), 
//        'display_name_format'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Display Name Format'), 
        'company'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company'), 
        'department'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Department'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Title'), 
        'phone_home'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Home Phone'), 
        'phone_work'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Work Phone'), 
        'phone_cell'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Cell Phone'), 
        'phone_fax'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Fax Number'), 
        'primary_email'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Primary Email Address'), 
        'primary_email_flags'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Primary Email Options'), 
        'secondary_email'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Secondary Email Address'), 
        'secondary_email_flags'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Secondary Email Options'), 
        'mailing_address1'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Mailing Address'),
        'mailing_address2'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Mailing Address'),
        'mailing_city'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Mailing City'),
        'mailing_province'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Mailing Province'),
        'mailing_postal'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Mailing Postal'),
        'mailing_country'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Mailing Country'),
        'mailing_flags'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Billing Address'),
        'billing_address1'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Billing Address'),
        'billing_address2'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Billing Address'),
        'billing_city'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Billing City'),
        'billing_province'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Billing Province'),
        'billing_postal'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Billing Postal'),
        'billing_country'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Billing Country'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'birthdate'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Birthday'), 
        'connection'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Connection'), 
        'language'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Language'), 
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'), 
        'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Subscriptions'),
        'unsubscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Unsubscriptions'),
//        'customer_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Customer Categories'),
//        'customer_tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Customer Tags'),
//        'member_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Member Categories'),
//        'dealer_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Dealer Categories'),
//        'distributor_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Distributor Categories'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.update', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $perms = $rc['perms'];

    //
    // Filter arguments
    //
    if( isset($args['callsign']) ) {
        $args['callsign'] = strtoupper($args['callsign']);
    }

    //
    // Get the current settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // Load the existing customer
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerLoad');
    $rc = ciniki_customers_customerLoad($ciniki, $args['tnid'], $args['customer_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.271', 'msg'=>'Error loading customer', 'err'=>$rc['err']));
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.272', 'msg'=>'Customer does not exist'));
    }
    $customer = $rc['customer'];

    //
    // Check to make sure eid is unique if specified
    //
    if( isset($args['eid']) && $args['eid'] != '' ) {
        $strsql = "SELECT id "
            . "FROM ciniki_customers "
            . "WHERE eid = '" . ciniki_core_dbQuote($ciniki, $args['eid']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'eid');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.273', 'msg'=>'The customer ID already exists.'));
        }
    }

    //
    // Check if trying to make a child customer
    //
    if( isset($args['parent_id']) && $args['parent_id'] > 0 ) {
        //
        // Make sure parent_id is not customer id
        //
        if( $args['parent_id'] == $args['customer_id'] ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.274', 'msg'=>'Parent cannot be the same as the child.'));
        }

        // 
        // Check to make sure the parent is not a child
        //
        $strsql = "SELECT id, parent_id "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'parent');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['parent']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.275', 'msg'=>'The parent does not exist.'));
        }
        if( isset($rc['parent']) && $rc['parent']['parent_id'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.276', 'msg'=>'The parent is already a child.'));
        }
        // 
        // Check to make sure the customer does not have any children
        //
        $strsql = "SELECT 'children', COUNT(*) AS num_children  "
            . "FROM ciniki_customers "
            . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['children']) && $rc['num']['children'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.277', 'msg'=>'This customer already has children and cannot become a parent.'));
        }
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check for phone changes
    //
    foreach(['phone_home', 'phone_work', 'phone_cell', 'phone_fax'] as $field) {
        if( isset($args[$field]) ) {
            // check for delete
            if( $args[$field] == '' && isset($customer[$field . '_id']) && $customer[$field . '_id'] > 0 ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.phone', $customer[$field . '_id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.281', 'msg'=>'Unable to remove phone number', 'err'=>$rc['err']));
                }
            }
            // Add
            elseif( $args[$field] != '' && (!isset($customer[$field . '_id']) || $customer[$field . '_id'] == 0) ) {
                $label = ucfirst(str_replace('phone_', '', $field));
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.phone', array(
                    'customer_id'=>$args['customer_id'],
                    'phone_label'=>$label,
                    'phone_number'=>$args[$field],
                    'flags'=>0,
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.282', 'msg'=>'Unable to add phone number', 'err'=>$rc['err']));
                }
            }
            // Update
            elseif( $args[$field] != '' && isset($customer[$field . '_id']) && $customer[$field . '_id'] > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.phone', $customer[$field . '_id'], array('phone_number'=>$args[$field]), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.283', 'msg'=>'Unable to update phone number', 'err'=>$rc['err']));
                }
            }
            unset($args[$field]);
        }
    }

    //
    // Check for email changes
    //
    foreach(['primary_email', 'secondary_email'] as $field) {
        if( isset($args[$field]) || isset($args[$field . '_flags']) ) {
            $args[$field] = (isset($args[$field]) ? $args[$field] : (isset($customer[$field]) ? $customer[$field] : ''));
            $args[$field . '_flags'] = (isset($args[$field . '_flags']) ? $args[$field . '_flags'] : (isset($customer[$field . '_flags']) ? $customer[$field . '_flags'] : ''));
            // check for delete
            if( $args[$field] == '' && isset($customer[$field . '_id']) && $customer[$field . '_id'] > 0 ) {
                //
                // FIXME: Check if secondary email should be promoted to primary email
                //
                // if( $field == 'primary_email' ) {
                // }
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.email', $customer[$field . '_id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.284', 'msg'=>'Unable to remove email', 'err'=>$rc['err']));
                }
            }
            // Add
            elseif( $args[$field] != '' && (!isset($customer[$field . '_id']) || $customer[$field . '_id'] == 0) ) {
                if( !preg_match("/^[^ ]+\@[^ ]+\.[^ ]+$/", $args[$field]) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.287', 'msg'=>'Invalid email address format ' . $args[$field]));
                }
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.email', array(
                    'customer_id'=>$args['customer_id'],
                    'email'=>$args[$field],
                    'flags'=>$args[$field . '_flags'],
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.285', 'msg'=>'Unable to add email', 'err'=>$rc['err']));
                }
            }
            // Update
            elseif( $args[$field] != '' && isset($customer[$field . '_id']) && $customer[$field . '_id'] > 0 ) {
                $update_args = array();
                if( $args[$field] != $customer[$field] ) {    
                    if( !preg_match("/^[^ ]+\@[^ ]+\.[^ ]+$/", $args[$field]) ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.288', 'msg'=>'Invalid email address format ' . $args[$field]));
                    }
                    $update_args['email'] = $args[$field];
                }
                if( $args[$field . '_flags'] != $customer[$field . '_flags'] ) {    
                    $update_args['flags'] = $args[$field . '_flags'];
                }
                if( count($update_args) > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.email', $customer[$field . '_id'], $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.286', 'msg'=>'Unable to update email', 'err'=>$rc['err']));
                    }
                }
            }
            unset($args[$field]);
            unset($args[$field . '_flags']);
        }
    }

    //
    // Check for address changes
    //
    $args['mailing_flags'] = (isset($args['mailing_flags']) ? $args['mailing_flags'] : (isset($customer['mailing_flags']) ? $customer['mailing_flags'] : 0x06));

    //
    // Check if secondary address needs to be updated.
    // This must be done first incase any changes to mailing flags
    //
    if( ($args['mailing_flags']&0x02) == 0 ) {
        $addr = array(
            'customer_id' => $args['customer_id'],
            'address1' => (isset($args['billing_address1']) ? $args['billing_address1'] : (isset($customer['billing_address1']) ? $customer['billing_address1'] : '')),
            'address2' => (isset($args['billing_address2']) ? $args['billing_address2'] : (isset($customer['billing_address2']) ? $customer['billing_address2'] : '')),
            'city' => (isset($args['billing_city']) ? $args['billing_city'] : (isset($customer['billing_city']) ? $customer['billing_city'] : '')),
            'province' => (isset($args['billing_province']) ? $args['billing_province'] : (isset($customer['billing_province']) ? $customer['billing_province'] : '')),
            'postal' => (isset($args['billing_postal']) ? $args['billing_postal'] : (isset($customer['billing_postal']) ? $customer['billing_postal'] : '')),
            'country' => (isset($args['billing_country']) ? $args['billing_country'] : (isset($customer['billing_country']) ? $customer['billing_country'] : '')),
            'flags' => (isset($args['billing_flags']) ? $args['billing_flags'] : (isset($customer['billing_flags']) ? $customer['billing_flags'] : '')),
            );
        //
        // Check if address blank, then remove
        //
        if( $addr['address1'] == '' && $addr['address2'] == '' 
            && $addr['city'] == '' && $addr['province'] == '' && $addr['postal'] == '' && $addr['country'] == '' 
            ) {
            if( $customer['billing_address_id'] > 0 ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.address', $customer['billing_address_id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.295', 'msg'=>'Unable to remove address', 'err'=>$rc['err']));
                }
            }
            //$args['mailing_flags'] = ($args['mailing_flags']&0xfd);
            $args['mailing_flags'] |= 0x02;
        }
        // Update
        elseif( $customer['billing_address_id'] > 0 ) {
            $update_args = array();
            foreach(['address1', 'address2', 'city', 'province', 'postal', 'country'] as $field) {
                if( isset($args['billing_' . $field]) && $args['billing_' . $field] != $customer['billing_' . $field] ) {
                    $update_args[$field] = $args['billing_' . $field];
                }
            }
            if( count($update_args) > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.address', $customer['billing_address_id'], $update_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.289', 'msg'=>'Unable to update address', 'err'=>$rc['err']));
                }
            }
        } 
        // Add
        else {
            $addr['flags'] = 0x02;
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.address', $addr, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.290', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
            }
        }
    } 
    // Check if secondary address should be deleted
    elseif( ($args['mailing_flags']&0x02) == 0x02 && $customer['billing_address_id'] > 0 ) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.address', $customer['billing_address_id'], null, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.291', 'msg'=>'Unable to remove address', 'err'=>$rc['err']));
        }
        $customer['billing_address_id'] = 0;
    }

    //
    // Check if any changes to mailing address
    //
    if( isset($args['mailing_address1']) || isset($args['mailing_address2']) || isset($args['mailing_city']) 
        || isset($args['mailing_province']) || isset($args['mailing_postal']) || isset($args['mailing_country']) 
        || $args['mailing_flags'] != $customer['mailing_flags']
        ) {
        $addr = array(
            'customer_id' => $args['customer_id'],
            'address1' => (isset($args['mailing_address1']) ? $args['mailing_address1'] : (isset($customer['mailing_address1']) ? $customer['mailing_address1'] : '')),
            'address2' => (isset($args['mailing_address2']) ? $args['mailing_address2'] : (isset($customer['mailing_address2']) ? $customer['mailing_address2'] : '')),
            'city' => (isset($args['mailing_city']) ? $args['mailing_city'] : (isset($customer['mailing_city']) ? $customer['mailing_city'] : '')),
            'province' => (isset($args['mailing_province']) ? $args['mailing_province'] : (isset($customer['mailing_province']) ? $customer['mailing_province'] : '')),
            'postal' => (isset($args['mailing_postal']) ? $args['mailing_postal'] : (isset($customer['mailing_postal']) ? $customer['mailing_postal'] : '')),
            'country' => (isset($args['mailing_country']) ? $args['mailing_country'] : (isset($customer['mailing_country']) ? $customer['mailing_country'] : '')),
            'flags' => (isset($args['mailing_flags']) ? $args['mailing_flags'] : (isset($customer['mailing_flags']) ? $customer['mailing_flags'] : '')),
            );
        //
        // Check if address blank, then remove
        //
        if( $addr['address1'] == '' && $addr['address2'] == '' 
            && $addr['city'] == '' && $addr['province'] == '' && $addr['postal'] == '' && $addr['country'] == '' 
            ) {
            if( $customer['mailing_address_id'] > 0 ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.address', $customer['mailing_address_id'], null, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.368', 'msg'=>'Unable to remove address', 'err'=>$rc['err']));
                }
            }
        }
        elseif( $addr['address1'] != '' || $addr['address2'] != '' || $addr['city'] != '' || $addr['province'] != '' || $addr['postal'] != '' || $addr['country'] != '' ) {
            if( $customer['mailing_address_id'] == 0 ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.address', $addr, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.369', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
                }
            } else {
                $update_args = array();
                foreach(['address1', 'address2', 'city', 'province', 'postal', 'country', 'flags'] as $field) {
                    if( isset($addr[$field]) && $addr[$field] != $customer['mailing_' . $field] ) {
                        $update_args[$field] = $addr[$field];
                    }
                }
                if( count($update_args) > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.address', $customer['mailing_address_id'], $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.367', 'msg'=>'Unable to update address', 'err'=>$rc['err']));
                    }
                }
            }
        }
    }

    //
    // Check for changes to display name, sort name or permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
    $rc = ciniki_customers_customerUpdateName($ciniki, $args['tnid'], $customer, $args['customer_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.410', 'msg'=>'Unable to update name', 'err'=>$rc['err']));
    }
    if( isset($rc['display_name']) && $rc['display_name'] != $customer['display_name'] ) {
        $args['display_name'] = $rc['display_name'];
    }
    if( isset($rc['sort_name']) && $rc['sort_name'] != $customer['sort_name'] ) {
        $args['sort_name'] = $rc['sort_name'];
    }
    if( isset($rc['permalink']) && $rc['permalink'] != $customer['permalink'] ) {
        $args['permalink'] = $rc['permalink'];
    }

/*    if( isset($args['prefix']) || isset($args['first']) 
        || isset($args['middle']) || isset($args['last']) || isset($args['suffix']) 
        || isset($args['company']) || isset($args['type']) || isset($args['display_name_format']) ) {

        if( isset($args['display_name_format']) ) {
            $customer['display_name_format'] = $args['display_name_format'];
        }

        //
        // Build the persons name
        //
        $space = '';
        $person_name = '';
        $sort_person_name = '';
        if( isset($args['prefix']) && $args['prefix'] != '' ) {
            $person_name .= $args['prefix'];
        } elseif( !isset($args['prefix']) && $customer['prefix'] != '' ) {
            $person_name .= $customer['prefix'];
        }
        if( $space == '' && $person_name != '' ) { $space = ' '; }
        if( isset($args['first']) && $args['first'] != '' ) {
            $person_name .= $space . $args['first'];
        } elseif( !isset($args['first']) && $customer['first'] != '' ) {
            $person_name .= $space . $customer['first'];
        }
        if( $space == '' && $person_name != '' ) { $space = ' '; }
        if( isset($args['middle']) && $args['middle'] != '' ) {
            $person_name .= $space . $args['middle'];
        } elseif( !isset($args['middle']) && $customer['middle'] != '' ) {
            $person_name .= $space . $customer['middle'];
        }
        if( $space == '' && $person_name != '' ) { $space = ' '; }
        if( isset($args['last']) && $args['last'] != '' ) {
            $person_name .= $space . $args['last'];
            $sort_person_name = $args['last'];
        } elseif( !isset($args['last']) && $customer['last'] != '' ) {
            $person_name .= $space . $customer['last'];
            $sort_person_name = $customer['last'];
        }
        if( $space == '' && $person_name != '' ) { $space = ' '; }
        if( isset($args['suffix']) && $args['suffix'] != '' ) {
            $person_name .= ($space!=''?',':'') . $space . $args['suffix'];
        } elseif( !isset($args['suffix']) && $customer['suffix'] != '' ) {
            $person_name .= ($space!=''?',':'') . $space . $customer['suffix'];
        }

        if( isset($args['first']) && $args['first'] != '' ) {
            $sort_person_name .= ($sort_person_name!=''?', ':'') . $args['first'];
        } elseif( !isset($args['first']) && $customer['first'] != '' ) {
            $sort_person_name .= ($sort_person_name!=''?', ':'') . $customer['first'];
        }
        //
        // Build the display_name
        //
        $type = (isset($args['type']))?$args['type']:$customer['type'];
        $company = (isset($args['company']))?$args['company']:$customer['company'];
        if( ($type == 2 || $type == 20 || $type = 30) && $company != '' ) {
            $format = 'company';
            if( isset($customer['display_name_format']) && $customer['display_name_format'] != '' ) {
                $format = $customer['display_name_format'];
            } elseif( !isset($settings['display-name-business-format']) 
                || $settings['display-name-business-format'] == 'company' ) {
                $format = 'company';
            } elseif( $settings['display-name-business-format'] != '' ) {
                $format = $settings['display-name-business-format'];
            }
            // Format the display_name
            if( $format == 'company' ) {
                $args['display_name'] = $company;
                $args['sort_name'] = $company;
            } 
            elseif( $format == 'company - person' ) {
                $args['display_name'] = $company . ($person_name!=''?' - ' . $person_name:'');
                $args['sort_name'] = $company;
            } 
            elseif( $format == 'person - company' ) {
                $args['display_name'] = ($person_name!=''?$person_name . ' - ':'') . $company;
                $args['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $company;
            } 
            elseif( $format == 'company [person]' ) {
                $args['display_name'] = $company . ($person_name!=''?' [' . $person_name . ']':'');
                $args['sort_name'] = $company;
            } 
            elseif( $format == 'person [company]' ) {
                $args['display_name'] = ($person_name!=''?$person_name . ' [' . $company . ']':$company);
                $args['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $company;
            }
        } else {
            $args['display_name'] = $person_name;
            $args['sort_name'] = $sort_person_name;
        }
    }
   
    if( isset($args['display_name']) && $args['display_name'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['display_name']);
    } */

    //
    // Check if type changes, parent_id should also change
    //
    if( isset($args['type']) && $args['type'] == 10 && $customer['parent_id'] > 0 ) {
        $args['parent_id'] = 0;
    }

    //
    // Update the customer
    //
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', 
        $args['customer_id'], $args, 0x06);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.278', 'msg'=>'Unable to updated customer', 'err'=>$rc['err']));
    }

    //
    // Hook into other modules when updating status incase orders or other items should be changed
    //
    if( isset($args['status']) && $args['status'] != '' ) {
        foreach($ciniki['tenant']['modules'] as $module => $m) {
            list($pkg, $mod) = explode('.', $module);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'customerStatusUpdate');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array(
                    'customer_id'=>$args['customer_id'], 
                    'status'=>$args['status'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.279', 'msg'=>'Unable to update customer status.', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Hook into other modules when updating name incase orders or other items should be changed
    //
    if( isset($args['display_name']) && $args['display_name'] != '' ) {
        foreach($ciniki['tenant']['modules'] as $module => $m) {
            list($pkg, $mod) = explode('.', $module);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'customerNameUpdate');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array(
                    'customer_id'=>$args['customer_id'], 
                    'display_name'=>$args['display_name'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.280', 'msg'=>'Unable to update customer name.', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Check for subscriptions
    //
    if( isset($args['subscriptions']) || isset($args['unsubscriptions']) ) {
        // incase one of the args isn't set, setup with blank arrays
        if( !isset($args['subscriptions']) ) { $args['subscriptions'] = array(); }
        if( !isset($args['unsubscriptions']) ) { $args['unsubscriptions'] = array(); }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'updateCustomerSubscriptions');
        $rc = ciniki_subscriptions_updateCustomerSubscriptions($ciniki, $args['tnid'], 
            $args['customer_id'], $args['subscriptions'], $args['unsubscriptions']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the customer categories
    //
    if( isset($args['customer_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $args['customer_id'], 10, $args['customer_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the customer tags
    //
    if( isset($args['customer_tags']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $args['customer_id'], 20, $args['customer_tags']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the member categories
    //
    if( isset($args['member_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $args['customer_id'], 40, $args['member_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

/*    //
    // Update the dealer categories
    //
    if( isset($args['dealer_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $args['customer_id'], 60, $args['dealer_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the distributor categories
    //
    if( isset($args['distributor_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $args['customer_id'], 80, $args['distributor_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    } */

    //
    // Update the short_description
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
    $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $args['tnid'], $args['customer_id'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }

    //
    // Update the season membership
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02000000) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateSeasons');
        $rc = ciniki_customers_customerUpdateSeasons($ciniki, $args['tnid'], $args['customer_id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $rsp = array('stat'=>'ok');

//
//  FIXME: Switch UI to use response for add/update to fill out details
//
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
//  $rc = ciniki_customers__customerDetails($ciniki, $args['tnid'], $args['customer_id'], array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
//  if( $rc['stat'] == 'ok' && isset($rc['details']) ) {
//      $rsp['customer_details'] = $rc['details'];
//  }

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$args['customer_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.members', 'object_id'=>$args['customer_id']));
//    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.dealers', 'object_id'=>$args['customer_id']));
//    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.distributors', 'object_id'=>$args['customer_id']));

    return $rsp;
}
?>
