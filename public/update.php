<?php
//
// Description
// -----------
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_update(&$ciniki) {
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
        'member_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member Status'), 
        'member_lastpaid'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Member Last Paid'),
        'member_expires'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Member Expires'),
        'membership_length'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Membership Length'),
        'membership_type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Membership Type'),
        'dealer_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Dealer Status'), 
        'distributor_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Distributor Status'), 
        'callsign'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Callsign'), 
        'prefix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Prefix'), 
        'first'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Suffix'), 
        'display_name_format'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Display Name Format'), 
        'company'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company'), 
        'department'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Department'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Title'), 
        'phone_home'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Home Phone'), 
        'phone_work'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Work Phone'), 
        'phone_cell'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Cell Phone'), 
        'phone_fax'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Fax Number'), 
        'primary_email'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Primary Email Address'), 
        'alternate_email'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Alternate Email Address'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'primary_image_caption'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image Caption'), 
        'intro_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Intro Image'), 
        'intro_image_caption'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Intro Image Caption'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Webflags'), 
        'short_bio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Bio'), 
        'full_bio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Full Bio'), 
        'birthdate'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Birthday'), 
        'connection'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Connection'), 
        'language'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Preferred Language'), 
        'tax_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tax Number'), 
        'tax_location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tax Location'), 
        'discount_percent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Discount Percent'), 
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'), 
        'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Subscriptions'),
        'unsubscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Unsubscriptions'),
        'customer_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Customer Categories'),
        'customer_tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Customer Tags'),
        'member_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Member Categories'),
        'dealer_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Dealer Categories'),
        'distributor_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Distributor Categories'),
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
    $modules = $rc['modules'];
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
    // Get the existing customer name
    //
    $strsql = "SELECT status, type, callsign, prefix, first, middle, last, suffix, "
        . "display_name, display_name_format, sort_name, permalink, company, dealer_status, distributor_status, webflags "
        . "FROM ciniki_customers "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.143', 'msg'=>'Customer does not exist'));
    }
    $customer = $rc['customer'];

    //
    // Check if dealer status and distributor status should be changed
    //
    if( isset($args['status']) && $args['status'] == 60 ) {
        if( ($customer['webflags']&0x07) > 0 ) {
            $args['webflags'] = 0;
        }
    }

    if( isset($args['dealer_status']) && $args['dealer_status'] == 60 ) {
        if( ($customer['webflags']&0x02) > 0 ) {
            $args['webflags'] = ($customer['webflags'] & !0x02);
        }
    }
    if( isset($args['distributor_status']) && $args['distributor_status'] == 60 ) {
        if( ($customer['webflags']&0x04) > 0 ) {
            $args['webflags'] = ($customer['webflags'] & !0x04);
        }
    }

    //
    // Only allow owners to change status of customer to/from suspend/delete
    //
    if( isset($args['status']) 
        && ($args['status'] >= 50 || $customer['status'] >= 50) ) {
        if( !isset($perms) || ($perms&0x01) != 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.144', 'msg'=>'You do not have permissions to change the customer status.'));
        }
    }

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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.145', 'msg'=>'The customer ID already exists.'));
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.146', 'msg'=>'Parent cannot be the same as the child.'));
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.147', 'msg'=>'The parent does not exist.'));
        }
        if( isset($rc['parent']) && $rc['parent']['parent_id'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.148', 'msg'=>'The parent is already a child.'));
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.149', 'msg'=>'This customer already has children and cannot become a parent.'));
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check for changes to display name, sort name or permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
    $rc = ciniki_customers_customerUpdateName($ciniki, $args['tnid'], $customer, $args['customer_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.413', 'msg'=>'Unable to update name', 'err'=>$rc['err']));
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

    //
    // Update the customer
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', 
        $args['customer_id'], $args, 0x06);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.150', 'msg'=>'Unable to updated customer', 'err'=>$rc['err']));
    }

    //
    // Hook into other modules when updating status incase orders or other items should be changed
    //
    if( isset($args['status']) && $args['status'] != '' ) {
        foreach($modules as $module => $m) {
            list($pkg, $mod) = explode('.', $module);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'customerStatusUpdate');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array(
                    'customer_id'=>$args['customer_id'], 
                    'status'=>$args['status'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.151', 'msg'=>'Unable to update customer status.', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Hook into other modules when updating name incase orders or other items should be changed
    //
    if( isset($args['display_name']) && $args['display_name'] != '' ) {
        foreach($modules as $module => $m) {
            list($pkg, $mod) = explode('.', $module);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'customerNameUpdate');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array(
                    'customer_id'=>$args['customer_id'], 
                    'display_name'=>$args['display_name'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.152', 'msg'=>'Unable to update customer name.', 'err'=>$rc['err']));
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

    //
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
    }

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
    if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.dealers', 'object_id'=>$args['customer_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.distributors', 'object_id'=>$args['customer_id']));

    return $rsp;
}
?>
