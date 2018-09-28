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
function ciniki_customers_customerAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
        'prefix'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Name Prefix'), 
        'first'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Name Suffix'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $perms = $rc['perms'];

    //
    // Separate out phone and email args so they don't get written to ciniki_customers table
    //
    $email_args = array();
    $phone_args = array();
    foreach(['primary_email', 'primary_email_flags', 'secondary_email', 'secondary_email_flags'] AS $field) {
        if( isset($args[$field]) ) {
            $email_args[$field] = $args[$field];
            unset($args[$field]);
        }
    }
    foreach(['phone_home', 'phone_work', 'phone_cell', 'phone_fax'] AS $field) {
        if( isset($args[$field]) ) {
            $phone_args[$field] = $args[$field];
            unset($args[$field]);
        }
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
    // Check to make sure eid is unique if specified
    //
    if( isset($args['eid']) && $args['eid'] != '' ) {
        $strsql = "SELECT id "
            . "FROM ciniki_customers "
            . "WHERE eid = '" . ciniki_core_dbQuote($ciniki, $args['eid']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'eid');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.296', 'msg'=>'The customer ID already exists'));
        }
    }

    //
    // Check parent id is specified if child account
    //
    if( ($args['type'] == 21 || $args['type'] == 22) && (!isset($args['parent_id']) || $args['parent_id'] == '' || $args['parent_id'] <= 0) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.308', 'msg'=>'You must specifiy the family'));
    } elseif( ($args['type'] == 31 || $args['type'] == 32) && (!isset($args['parent_id']) || $args['parent_id'] == '' || $args['parent_id'] <= 0) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.309', 'msg'=>'You must specifiy the employer'));
    }

    //
    // Check if trying to make a child customer
    //
    if( isset($args['parent_id']) && $args['parent_id'] > 0 ) {
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.297', 'msg'=>'The parent does not exist'));
        }
        if( isset($rc['parent']) && $rc['parent']['parent_id'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.298', 'msg'=>'The parent is already a child'));
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
    // Add the customer
    //
    if( isset($args['first']) || isset($args['last']) || isset($args['company']) ) {

        //
        // Build the persons name
        //
        $space = '';
        $person_name = '';
        $sort_person_name = '';
        if( isset($args['prefix']) && $args['prefix'] != '' ) {
            $person_name .= $args['prefix'];
        }
        if( $space == '' && $person_name != '' ) { $space = ' '; }
        if( isset($args['first']) && $args['first'] != '' ) {
            $person_name .= $space . $args['first'];
        }
        if( $space == '' && $person_name != '' ) { $space = ' '; }
        if( isset($args['middle']) && $args['middle'] != '' ) {
            $person_name .= $space . $args['middle'];
        }
        if( $space == '' && $person_name != '' ) { $space = ' '; }
        if( isset($args['last']) && $args['last'] != '' ) {
            $person_name .= $space . $args['last'];
            $sort_person_name = $args['last'];
        }
        if( $space == '' && $person_name != '' ) { $space = ' '; }
        if( isset($args['suffix']) && $args['suffix'] != '' ) {
            $person_name .= ($space!=''?',':'') . $space . $args['suffix'];
        }

        if( isset($args['first']) && $args['first'] != '' ) {
            $sort_person_name .= ($sort_person_name!=''?', ':'') . $args['first'];
        }
        //
        // Build the display_name
        //
        $type = $args['type'];
        $company = (isset($args['company'])?$args['company']:'');
        if( ($type == 2 || $type == 20 || $type = 30) && $company != '' ) {
            $format = 'company';
            if( !isset($settings['display-name-business-format']) || $settings['display-name-business-format'] == 'company' ) {
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
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.299', 'msg'=>'You must specific a name', 'err'=>$rc['err']));
    }
   
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['display_name']);

    //
    // Check to make sure name is unique
    //
    if( $args['type'] == 10 || $args['type'] == 20 || $args['type'] == 30 ) {
        $strsql = "SELECT COUNT(*) AS num "
            . "FROM ciniki_customers "
            . "WHERE permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        //
        // Child customers cannot have duplicate names within the same business/family
        //
        if( $args['type'] == 21 || $args['type'] == 22 || $args['type'] == 31 || $args['type'] == 32 ) {
            $strsql .= "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.customers', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.306', 'msg'=>'Unable to check for duplicates', 'err'=>$rc['err']));
        }
        if( isset($rc['num']) && $rc['num'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.307', 'msg'=>'You already have a customer with that name'));
        }
    }

    //
    // Check if type changes, parent_id should also change
    //
    if( isset($args['type']) && $args['type'] == 10 && $args['parent_id'] > 0 ) {
        $args['parent_id'] = 0;
    }

    //
    // Update the customer
    //
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.customer', $args, 0x06);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.302', 'msg'=>'Unable to updated customer', 'err'=>$rc['err']));
    }
    $customer_id = $rc['id'];

    //
    // Add for phone changes
    //
    foreach(['phone_home', 'phone_work', 'phone_cell', 'phone_fax'] as $field) {
        if( isset($phone_args[$field]) && $phone_args[$field] != '' ) {
            $label = ucfirst(str_replace('phone_', '', $field));
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.phone', array(
                'customer_id'=>$customer_id,
                'phone_label'=>$label,
                'phone_number'=>$phone_args[$field],
                'flags'=>0,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.303', 'msg'=>'Unable to add phone number', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Check for email changes
    //
    foreach(['primary_email', 'secondary_email'] as $field) {
        if( isset($email_args[$field]) && $email_args[$field] != '' ) {
            $email_args[$field . '_flags'] = (isset($email_args[$field . '_flags']) ? $email_args[$field . '_flags'] : 0);

            if( !preg_match("/^[^ ]+\@[^ ]+\.[^ ]+$/", $email_args[$field]) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.304', 'msg'=>'Invalid email address format ' . $email_args[$field]));
            }
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.email', array(
                'customer_id'=>$customer_id,
                'email'=>$email_args[$field],
                'flags'=>(isset($email_args[$field . '_flags']) ? $email_args[$field . '_flags'] : 0),
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.305', 'msg'=>'Unable to add email', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Check if secondary address needs to be updated.
    // This must be done first incase any changes to mailing flags
    //
    $billing_address_id = 0;
    if( ($args['mailing_flags']&0x02) == 0 ) {
        $addr = array(
            'customer_id' => $customer_id,
            'address1' => (isset($args['billing_address1']) ? $args['billing_address1'] : ''),
            'address2' => (isset($args['billing_address2']) ? $args['billing_address2'] : ''),
            'city' => (isset($args['billing_city']) ? $args['billing_city'] : ''),
            'province' => (isset($args['billing_province']) ? $args['billing_province'] : ''),
            'postal' => (isset($args['billing_postal']) ? $args['billing_postal'] : ''),
            'country' => (isset($args['billing_country']) ? $args['billing_country'] : ''),
            'flags' => (isset($args['billing_flags']) ? $args['billing_flags'] : 0x02),
            );
        //
        // Only add if a field is filled in
        //
        if( $addr['address1'] != '' || $addr['address2'] != '' || $addr['city'] != '' || $addr['province'] != '' || $addr['postal'] != '' || $addr['country'] != '' ) {
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.address', $addr, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.300', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
            }
            $billing_address_id = $rc['id'];
        }
    } 

    //
    // Check for mailing address
    //
    $addr = array(
        'customer_id' => $customer_id,
        'address1' => (isset($args['mailing_address1']) ? $args['mailing_address1'] : ''),
        'address2' => (isset($args['mailing_address2']) ? $args['mailing_address2'] : ''),
        'city' => (isset($args['mailing_city']) ? $args['mailing_city'] : ''),
        'province' => (isset($args['mailing_province']) ? $args['mailing_province'] : ''),
        'postal' => (isset($args['mailing_postal']) ? $args['mailing_postal'] : ''),
        'country' => (isset($args['mailing_country']) ? $args['mailing_country'] : ''),
        // If billing address added, this is just mailing address
        'flags' => ($billing_address_id > 0 ? 0x04 : 0x06),     
        );
    //
    // Check if any changes to mailing address
    //
    if( $addr['address1'] != '' || $addr['address2'] != '' || $addr['city'] != '' || $addr['province'] != '' || $addr['postal'] != '' || $addr['country'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.address', $addr, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.301', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
        }
        $mailing_address_id = $rc['id'];
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
            $customer_id, $args['subscriptions'], $args['unsubscriptions']);
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
            'customer_id', $customer_id, 10, $args['customer_categories']);
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
            'customer_id', $customer_id, 20, $args['customer_tags']);
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
            'customer_id', $customer_id, 40, $args['member_categories']);
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
            'customer_id', $customer_id, 60, $args['dealer_categories']);
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
            'customer_id', $customer_id, 80, $args['distributor_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    } */

    //
    // Update the short_description
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
    $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $args['tnid'], $customer_id, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }

    //
    // Update the season membership
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02000000) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateSeasons');
        $rc = ciniki_customers_customerUpdateSeasons($ciniki, $args['tnid'], $customer_id);
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

    $rsp = array('stat'=>'ok', 'id'=>$customer_id);

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$customer_id));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.members', 'object_id'=>$customer_id));
//    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.dealers', 'object_id'=>$args['customer_id']));
//    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.distributors', 'object_id'=>$args['customer_id']));

    return $rsp;
}
?>
