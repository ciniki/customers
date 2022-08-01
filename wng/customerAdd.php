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
function ciniki_customers_wng_customerAdd(&$ciniki, $tnid, $request, $args) {
    
    if( !isset($args['first']) ) { $args['first'] = ''; }
    if( !isset($args['last']) ) { $args['last'] = ''; }
    if( !isset($args['name']) ) { $args['name'] = ''; }
    if( !isset($args['password']) ) { $args['password'] = ''; }
    if( !isset($args['company']) ) { $args['company'] = ''; }
    if( !isset($args['sort_name']) ) { $args['sort_name'] = ''; }
    if( !isset($args['type']) ) { $args['type'] = 1; }              // Default to person

    
    //
    // Remove extra spaces at beginning and end of fields
    //
    foreach($args as $aid => $arg) {
        if( is_string($arg) ) {
            $args[$aid] = trim($args[$aid]);
        }
    }

    $args['short_description'] = '';

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
        && (!isset($args['company']) || $args['company'] == '')
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.446', 'msg'=>'You must specify a first or last name'));
    }

    //
    // Check for a start date, default to now
    //
    if( !isset($args['start_date']) || $args['start_date'] == '' ) {
        $args['start_date'] = gmdate('Y-m-d H:i:s');
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
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'parent');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['parent']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.447', 'msg'=>'The parent does not exist.'));
        }
        if( isset($rc['parent']) && $rc['parent']['parent_id'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.448', 'msg'=>'The parent is already a child.'));
        }
    }

    //
    // Check if name should be parsed
    //
    if( $args['first'] == '' && $args['last'] == '' && $args['name'] != '' ) {
        // Check for a comma to see if was entered, "last, first"
        if( preg_match('/^\s*(.*),\s*(.*)\s*$/', $args['name'], $matches) ) {
            $args['last'] = $matches[1];
            $args['first'] = $matches[2];
        } elseif( preg_match('/^\s*(.*)\s([^\s]+)\s*$/', $args['name'], $matches) ) {
            $args['first'] = $matches[1];
            $args['last'] = $matches[2];
        } else {
            // Default to add name to first field instead of last field
            $args['first'] = $args['name'];
        }
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
    if( $args['type'] == 2 && $args['company'] != '' ) {
        // Find the format to use
        $format = 'company';
        if( isset($args['display_name_format']) && $args['display_name_format'] != '' ) {
            $format = $args['display_name_format'];
        } elseif( !isset($settings['display-name-business-format']) 
            || $settings['display-name-business-format'] == 'company' ) {
            $format = 'company';
        } elseif( $settings['display-name-business-format'] != '' ) {
            $format = $settings['display-name-business-format'];
        }
        // Format the display_name
        if( $format == 'company' ) {
            $args['display_name'] = $args['company'];
            $args['sort_name'] = $args['company'];
        } 
        elseif( $format == 'company - person' ) {
            $args['display_name'] = $args['company'] . ($person_name != ''?' - ' . $person_name:'');
            $args['sort_name'] = $args['company'];
        } 
        elseif( $format == 'person - company' ) {
            $args['display_name'] = ($person_name!=''?$person_name . ' - ':'') . $args['company'];
            $args['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $args['company'];
        } 
        elseif( $format == 'company [person]' ) {
            $args['display_name'] = $args['company'] . ($person_name!=''?' [' . $person_name . ']':'');
            $args['sort_name'] = $args['company'];
        } 
        elseif( $format == 'person [company]' ) {
            if( $person_name == '' ) {
                $args['display_name'] = $args['company'];
            } else {
                $args['display_name'] = $person_name . ' [' . $args['company'] . ']';
            }
            $args['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $args['company'];
        }
    } else {
        $args['display_name'] = $person_name;
        $args['sort_name'] = $sort_person_name;
    }

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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.customer', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_id = $rc['id'];

    //
    // Check if phone numbers to add
    //
    for($i=1;$i<5;$i++) {
        if( isset($args["phone_number_$i"]) && $args["phone_number_$i"] != '' ) {
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.phone',
                array('customer_id'=>$customer_id,
                    'phone_label'=>$args["phone_label_$i"],
                    'phone_number'=>$args["phone_number_$i"],
                    'flags'=>(isset($args["phone_flags_$i"])?$args["phone_flags_$i"]:0)), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
        }
    }

    //
    // Check if email address was specified, and add to customer emails
    //
    $email_id = 0;
    if( isset($args['email_address']) && $args['email_address'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email',
            array('customer_id'=>$customer_id,
                'email'=>$args['email_address'],
                'password'=>(isset($args['hashed_pwd']) ? $args['hashed_pwd'] : ($args['password']!='' ? sha1($args['password']) : '')),
                'temp_password'=>'',
                'temp_password_date'=>'',
                'flags'=>(isset($args['flags'])?$args['flags']:0x01),
                ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
        $email_id = $rc['id'];
    }

    //
    // Check if there is an address to add
    //
    $address_id = 0;
    if( (isset($args['address1']) && $args['address1'] != '' ) 
        || (isset($args['address2']) && $args['address2'] != '' )
        || (isset($args['city']) && $args['city'] != '' )
        || (isset($args['province']) && $args['province'] != '' )
        || (isset($args['postal']) && $args['postal'] != '' )
        ) {
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address',
            array('customer_id'=>$customer_id,
                'flags'=>(isset($args['address_flags'])?$args['address_flags']:0x07),
                'address1'=>$args['address1'],
                'address2'=>$args['address2'],
                'city'=>$args['city'],
                'province'=>$args['province'],
                'postal'=>$args['postal'],
                'country'=>$args['country'],
                'latitude'=>(isset($args['latitude'])?$args['latitude']:''),
                'longitude'=>(isset($args['longitude'])?$args['longitude']:''),
                'phone'=>(isset($args['phone'])?$args['phone']:''),
                'notes'=>'',
                ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
        $address_id = $rc['id'];
    }

    if( isset($args['link_url_1']) && $args['link_url_1'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.link',
            array('customer_id'=>$customer_id,
                'name'=>$args['link_name_1'],
                'url'=>$args['link_url_1'],
                'webflags'=>$args['link_webflags_1'],
                'description'=>'',
                ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
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
        $rc = ciniki_subscriptions_updateCustomerSubscriptions($ciniki, $tnid, 
            $customer_id, $args['subscriptions'], $args['unsubscriptions']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the categories
    //
    if( isset($args['customer_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $tnid,
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 10, $args['customer_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the tags
    //
    if( isset($args['customer_tags']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $tnid,
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
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $tnid,
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 40, $args['member_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the dealer categories
    //
    if( isset($args['dealer_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $tnid,
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
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $tnid,
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 80, $args['distributor_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the short_description
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
    $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $tnid, $customer_id, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }

    //
    // FIXME: customerUpdateSeasons is setup to take arguments from ciniki['request']['args']
    // Update the season membership
    //
/*  if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateSeasons');
        $rc = ciniki_customers_customerUpdateSeasons($ciniki, $tnid, $customer_id);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    } */

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

    $ciniki['syncqueue'][] = array('push'=>'ciniki.customers.customer', 'args'=>array('id'=>$customer_id));

    $rsp = array('stat'=>'ok', 'id'=>$customer_id);

    return $rsp;
}
?>
