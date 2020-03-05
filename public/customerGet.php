<?php
//
// Description
// -----------
// This method returns the customer record, and was designed to return to the IFB form.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_customerGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'parent_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Parent'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $mysql_date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // get parent information
    //
    if( isset($args['parent_id']) && $args['parent_id'] > 0 ) {
        $strsql = "SELECT id, type, display_name "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'parent');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.317', 'msg'=>'Unable to load parent', 'err'=>$rc['err']));
        }
        if( !isset($rc['parent']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.318', 'msg'=>'Unable to find requested parent'));
        }
        $parent = $rc['parent'];
    }

    if( $args['customer_id'] == 0 ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $customer = array(
            'id' => 0,
            'type' => 10,
            'eid' => '',
            'status' => 10,
            'display_name' => '',
            'primary_image_id' => 0,
            'member_status' => 0,
            'member_lastpaid' => '',
            'member_expires' => '',
            'membership_length' => 0,
            'membership_type' => 0,
            'dealer_status' => 0,
            'distributor_status' => 0,
            'prefix' => '',
            'first' => '',
            'middle' => '',
            'last' => '',
            'suffix' => '',
            'company' => '',
            'department' => '',
            'title' => '',
            'birthdate' => '',
            'short_bio' => '',
            'full_bio' => '',
            'webflags' => '',
            'start_date' => $dt->format($date_format),
            'notes' => '',
            );
        //
        // Setup the default type for adding under a parent to be child or employee
        //
        if( isset($parent) ) {
            $customer['parent_id'] = $parent['id'];
            if( $parent['type'] == 20 ) {
                $customer['type'] = 22;
            } elseif( $parent['type'] == 30 ) {
                $customer['type'] = 32;
            }
        }
        //
        // Setup IFB mode defaults
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
            $customer['phone_cell_id'] = 0;
            $customer['phone_cell'] = '';
            $customer['phone_home_id'] = 0;
            $customer['phone_home'] = '';
            $customer['phone_work_id'] = 0;
            $customer['phone_work'] = '';
            $customer['phone_fax_id'] = 0;
            $customer['phone_fax'] = '';
            $customer['primary_email_id'] = 0;
            $customer['primary_email'] = '';
            $customer['primary_email_flags'] = 0x01;
            $customer['secondary_email_id'] = 0;
            $customer['secondary_email'] = '';
            $customer['secondary_email_flags'] = 0x01;
            $customer['mailing_address_id'] = 0;
            $customer['mailing_address1'] = '';
            $customer['mailing_address2'] = '';
            $customer['mailing_city'] = '';
            $customer['mailing_province'] = '';
            $customer['mailing_postal'] = '';
            $customer['mailing_country'] = '';
            $customer['mailing_flags'] = 0x06;
            $customer['billing_address_id'] = 0;
            $customer['billing_address1'] = '';
            $customer['billing_address2'] = '';
            $customer['billing_city'] = '';
            $customer['billing_province'] = '';
            $customer['billing_postal'] = '';
            $customer['billing_country'] = '';
        }
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerLoad');
        $rc = ciniki_customers_customerLoad($ciniki, $args['tnid'], $args['customer_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.379', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        $customer = $rc['customer'];

        //
        // FIXME: The following sections may be needed in the future and can be copied from get.php
        // Get the tax location
        // Get the categories and tags for the customer
        // Get the customer image gallery
        //

        //
        // Load subscriptions
        //
        if( isset($modules['ciniki.subscriptions']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'customerSubscriptions');
            $rc = ciniki_subscriptions_hooks_customerSubscriptions($ciniki, $args['tnid'], array('customer_id'=>$args['customer_id']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['subscriptions']) ) {
                $customer['subscriptions'] = $rc['subscriptions'];
            }
        }

/* Not required here, in customerDetails
        //
        // Build list of child ids
        //
        $uiDataArgs = array();
        if( isset($customer['children']) && count($customer['children']) > 0 ) {
            $uiDataArgs['customer_ids'] = array();
            foreach($customer['children'] as $child) {
                $uiDataArgs['customer_ids'][] = $child['id'];
            }
        } else {
            $uiDataArgs['customer_id'] = $customer['id'];
        }

        //
        // Load additional information from hooks
        //
      
        $customer['data_tabs'] = array();
        foreach($ciniki['tenant']['modules'] as $module) {
            list($pkg, $mod) = explode('.', $module);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'uiCustomersData');
            if( $rc['stat'] != 'ok' ) {
                continue;
            }
            $fn = $rc['function_call']; 
            $rc = $fn($ciniki, $args['tnid'], $uiDataArgs);
            if( isset($rc['tabs']) ) {
                foreach($rc['tabs'] as $tab) {
                    $customer['data_tabs'][] = $tab;
                }
            }
        } */
    }

    $rsp = array('stat'=>'ok', 'customer'=>$customer);

    //
    // Get the list of families
    //
    $strsql = "SELECT id, display_name "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status = 10 "
        . "AND type = 20 "
        . "ORDER BY display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.267', 'msg'=>'Unable to load families', 'err'=>$rc['err']));
    }
    $rsp['families'] = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Get the list of businesses
    //
    $strsql = "SELECT id, display_name "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status = 10 "
        . "AND type = 30 "
        . "ORDER BY display_name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.229', 'msg'=>'Unable to load families', 'err'=>$rc['err']));
    }
    $rsp['businesses'] = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Get the list of subscriptions available
    //
    if( isset($modules['ciniki.subscriptions']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'subscriptionList');
        $rc = ciniki_subscriptions_hooks_subscriptionList($ciniki, $args['tnid'], array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['subscriptions']) ) {
            // Add default status of 60
            foreach($rc['subscriptions'] as $sid => $subscription) {
                if( $args['customer_id'] > 0 ) {
                    $rc['subscriptions'][$sid]['status'] = 60;
                }
            }
            // Check if customer is subscribed to any lists
            if( isset($customer['subscriptions']) ) {
                foreach($customer['subscriptions'] as $sid => $subscription) {
                    if( isset($rc['subscriptions'][$subscription['subscription']['id']]) ) {
                        $rc['subscriptions'][$subscription['subscription']['id']]['status'] = 10;
                    } else {
                        $rc['subscriptions'][$subscription['subscription']['id']]['status'] = 60;
                    }
                }
            }
            //
            // Convert from ID hash to array, this will keep it sorted properly in javascript
            //
            $rsp['subscriptions'] = array_values($rc['subscriptions']);
        }
    }

    return $rsp;
}
?>
