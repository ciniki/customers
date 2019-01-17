<?php
//
// Description
// -----------
// This function will add a new customer to the customers production module.
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_web_accountAdd(&$ciniki, $tnid, $args) {
    
    if( !isset($args['first']) ) { $args['first'] = ''; }
    if( !isset($args['last']) ) { $args['last'] = ''; }
    if( !isset($args['name']) ) { $args['name'] = ''; }
    if( !isset($args['password']) ) { $args['password'] = ''; }
    if( !isset($args['company']) ) { $args['company'] = ''; }
    if( !isset($args['sort_name']) ) { $args['sort_name'] = ''; }
    if( !isset($args['type']) ) { $args['type'] = 10; }              // Default to person

    
    //
    // Remove extra spaces at beginning and end of fields
    //
    foreach($args as $aid => $arg) {
        if( is_string($arg) ) {
            $args[$aid] = trim($args[$aid]);
        }
    }

    //
    // Check if birthdate specified
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x8000) ) {
        if( isset($args['birthdate']) && $args['birthdate'] != '' ) {
            $ts = strtotime($args['birthdate']);
            if( $ts === FALSE ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.382', 'msg'=>'Invalid format for birthday, please use "Month Day, Year"'));
            } else {
                $args['birthdate'] = strftime("%Y-%m-%d", $ts);
            }
        }
    }
    $args['short_description'] = '';

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

    //
    // Get the current settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // They must specify either a firstname or lastname
    //
    if( (!isset($args['first']) || $args['first'] == '') 
        && (!isset($args['last']) || $args['last'] == '')
        && (!isset($args['name']) || $args['name'] == '')
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.346', 'msg'=>'You must specify a first or last name'));
    }

    //
    // Check for a start date, default to now
    //
    if( !isset($args['start_date']) || $args['start_date'] == '' ) {
        $args['start_date'] = gmdate('Y-m-d H:i:s');
    }

    //
    // Determine the display name
    //
    $space = '';
    $person_name = '';
    $args['sort_name'] = '';
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
    }
    if( $space == '' && $person_name != '' ) { $space = ' '; }
    if( isset($args['suffix']) && $args['suffix'] != '' ) {
        $person_name .= ($space!=''?',':'') . $space . $args['suffix'];
    }
    $sort_person_name = '';
    if( isset($args['last']) && $args['last'] != '' ) {
        $sort_person_name = $args['last'];
    }
    if( isset($args['first']) && $args['first'] != '' ) {
        $sort_person_name .= ($sort_person_name!=''?', ':'') . $args['first'];
    }
    $args['display_name'] = $person_name;
    $args['sort_name'] = $sort_person_name;
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['display_name']);
    
    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check if family/business account should be setup
    //
    if( isset($args['type']) && $args['type'] == 20 ) {
        if( !isset($args['parent_name']) || trim($args['parent_name']) == '' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.347', 'msg'=>'You must family name'));
        }
        $parent = array(
            'parent_id' => 0,
            'display_name' => trim($args['parent_name']),
            'type' => 20,
            'first' => '',
            'last' => '',
            'company' => trim($args['parent_name']),
            'permalink' => ciniki_core_makePermalink($ciniki, trim($args['parent_name'])),
            'sort_name' => trim($args['parent_name']),
            'start_date' => $args['start_date'],
            'connection' => (isset($args['connection']) ? $args['connection'] : ''),
            );
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.customer', $parent, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.380', 'msg'=>'Unable to add family', 'err'=>$rc['err']));
        }
        $args['parent_id'] = $rc['id'];
        $args['type'] = 21;
    } elseif( isset($args['type']) && $args['type'] == 30 ) {
        $parent = array(
            'parent_id' => 0,
            'display_name' => trim($args['parent_name']),
            'type' => 30,
            'first' => '',
            'last' => '',
            'company' => trim($args['parent_name']),
            'sort_name' => trim($args['parent_name']),
            'start_date' => $args['start_date'],
            'connection' => (isset($args['connection']) ? $args['connection'] : ''),
            );
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.customer', $parent, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.348', 'msg'=>'Unable to add business', 'err'=>$rc['err']));
        }
        $args['parent_id'] = $rc['id'];
        $args['type'] = 31;
    }

    //
    // Check if email, phones and address to add for parent
    //
    if( isset($args['parent_id']) && $args['parent_id'] > 0 ) {
        if( isset($args['parent_email']) && trim($args['parent_email']) != '' ) {
            if( !preg_match("/^[^ ]+\@[^ ]+\.[^ ]+$/", $args['parent_email']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.385', 'msg'=>'Invalid email address format ' . $args['parent_email']));
            }
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email', array(
                'customer_id'=>$args['parent_id'],
                'email'=>$args['parent_email'],
                'flags'=>0,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.386', 'msg'=>'Unable to add email', 'err'=>$rc['err']));
            }
        } elseif( isset($args['primary_email']) && trim($args['primary_email']) != '' ) {
            if( !preg_match("/^[^ ]+\@[^ ]+\.[^ ]+$/", $args['primary_email']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.387', 'msg'=>'Invalid email address format ' . $args['primary_email']));
            }
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email', array(
                'customer_id'=>$args['parent_id'],
                'email'=>$args['primary_email'],
                'flags'=>0,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.388', 'msg'=>'Unable to add email', 'err'=>$rc['err']));
            }
        }

        foreach(['parent_work', 'parent_fax'] as $field) {
            if( isset($args[$field]) && $args[$field] != '' ) {
                $label = ucfirst(str_replace('parent_', '', $field));
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.phone', array(
                    'customer_id'=>$args['parent_id'],
                    'phone_label'=>$label,
                    'phone_number'=>$args[$field],
                    'flags'=>0,
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.389', 'msg'=>'Unable to add phone number', 'err'=>$rc['err']));
                }
            }
        }

        $addr = array(
            'customer_id' => $args['parent_id'],
            'address1' => (isset($args['parent_address1']) ? $args['parent_address1'] : ''),
            'address2' => (isset($args['parent_address2']) ? $args['parent_address2'] : ''),
            'city' => (isset($args['parent_city']) ? $args['parent_city'] : ''),
            'province' => (isset($args['parent_province']) ? $args['parent_province'] : ''),
            'postal' => (isset($args['parent_postal']) ? $args['parent_postal'] : ''),
            'country' => (isset($args['parent_country']) ? $args['parent_country'] : ''),
            'flags' => 0x06,
            );
        //
        // Only add if a field is filled in
        //
        if( $addr['address1'] != '' || $addr['address2'] != '' || $addr['city'] != '' || $addr['province'] != '' || $addr['postal'] != '' || $addr['country'] != '' ) {
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $addr, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.121', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
            }
            $parent_address_id = $rc['id'];
        }
    }

    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.customer', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_id = $rc['id'];

    //
    // Add for phone changes
    //
    foreach(['phone_home', 'phone_work', 'phone_cell', 'phone_fax'] as $field) {
        if( isset($args[$field]) && $args[$field] != '' ) {
            $label = ucfirst(str_replace('phone_', '', $field));
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.phone', array(
                'customer_id'=>$customer_id,
                'phone_label'=>$label,
                'phone_number'=>$args[$field],
                'flags'=>0,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.381', 'msg'=>'Unable to add phone number', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Check for email changes
    //
    foreach(['primary_email', 'secondary_email'] as $field) {
        if( isset($args[$field]) && $args[$field] != '' ) {
            $args[$field . '_flags'] = (isset($args[$field . '_flags']) ? $args[$field . '_flags'] : 0);

            if( !preg_match("/^[^ ]+\@[^ ]+\.[^ ]+$/", $args[$field]) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.384', 'msg'=>'Invalid email address format ' . $args[$field]));
            }
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email', array(
                'customer_id'=>$customer_id,
                'email'=>$args[$field],
                'password'=>(isset($args['password']) ? sha1($args['password']) : ''),
                'flags'=>(isset($args[$field . '_flags']) ? $args[$field . '_flags'] : 0),
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.383', 'msg'=>'Unable to add email', 'err'=>$rc['err']));
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
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $addr, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.122', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
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
    // Add the mailing address
    //
    if( $addr['address1'] != '' || $addr['address2'] != '' || $addr['city'] != '' || $addr['province'] != '' || $addr['postal'] != '' || $addr['country'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $addr, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.123', 'msg'=>'Unable to add address', 'err'=>$rc['err']));
        }
        $mailing_address_id = $rc['id'];
    }

    //
    // Check for subscriptions with autosubscribe, if no subscriptions supplied
    //
    if( !isset($args['subscriptions']) ) { 
        $strsql = "SELECT id "
            . "FROM ciniki_subscriptions "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x02) = 0x02 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.subscriptions', 'subscriptions', 'id');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.120', 'msg'=>'Unable to get subscription list', 'err'=>$rc['err']));
        }
        if( isset($rc['subscriptions']) && is_array($rc['subscriptions']) && count($rc['subscriptions']) > 0 ) {
            $args['subscriptions'] = $rc['subscriptions'];
        }
    }
    if( isset($args['subscriptions']) || isset($args['unsubscriptions']) ) {
        // incase one of the args isn't set, setup with blank arrays
        if( !isset($args['unsubscriptions']) ) { $args['unsubscriptions'] = array(); }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'updateCustomerSubscriptions');
        $rc = ciniki_subscriptions_updateCustomerSubscriptions($ciniki, $tnid, 
            $customer_id, $args['subscriptions'], $args['unsubscriptions']);
        if( $rc['stat'] != 'ok' ) {
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

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'customers');

    return array('stat'=>'ok', 'id'=>$customer_id);
}
?>
