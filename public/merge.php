<?php
//
// Description
// -----------
// This method will merge two customers into one.
//
// Returns
// -------
//
function ciniki_customers_merge($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'primary_customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Primary Customer'),
        'secondary_customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Secondary Customer'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.merge', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the active modules for the tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'getActiveModules');
    $rc = ciniki_tenants_getActiveModules($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    // 
    // Check that the customers belong to the tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCopyModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    $strsql = "SELECT id, display_name, "
        . "callsign, prefix, first, middle, last, suffix, company, department, title, "
        . "member_status, member_lastpaid, member_expires, "
        . "birthdate, "
        . "notes "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
            . "OR id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "') "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'display_name', 'callsign', 'prefix', 'first', 'middle', 'last', 'suffix', 
                'company', 'department', 'title', 'birthdate', 
                'member_status', 'member_lastpaid', 'member_expires',
                'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.103', 'msg'=>'Unable to find customers', 'err'=>$rc['err']));
    }

    $primary = NULL;
    $secondary = NULL;
    foreach($rc['customers'] as $cnum => $customer) {
        if( $customer['customer']['id'] == $args['primary_customer_id'] ) {
            $primary = $customer['customer'];
        }
        if( $customer['customer']['id'] == $args['secondary_customer_id'] ) {
            $secondary = $customer['customer'];
        }
    }

    if( $primary == NULL ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.104', 'msg'=>'Unable to find customer'));
    }
    if( $secondary == NULL ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.105', 'msg'=>'Unable to find customer'));
    }

    //
    // Merge customer details
    //
    $fields = array(
        'callsign',
        'prefix',
        'first',
        'middle',
        'last',
        'suffix',
        'company',
        'department',
        'title',
        'notes',
        'birthdate',
        'member_status',
        'member_lastpaid',
        'member_expires',
    );
    $strsql_primary = "UPDATE ciniki_customers SET last_updated = UTC_TIMESTAMP() ";
    //
    // Copy all the field history for the secondary customer to primary
    //
    $strsql_history = "UPDATE ciniki_customer_history SET table_key = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND table_field IN (";
    $strsql_secondary = "UPDATE ciniki_customers SET last_updated = UTC_TIMESTAMP() ";
    $field_count = 0;
    foreach($fields as $field) {
        //
        // Check if the field exists and contains information in the secondary record
        //
        if( isset($secondary[$field]) && $secondary[$field] != '' ) {
            //
            // If the primary record field is empty,
            // copy the information across
            //
            if( !isset($primary[$field]) || $primary[$field] == '' || $primary[$field] == '0000-00-00' || $primary[$field] == '0' ) {
                // Set the information in the primary customer, and remove from secondary
                $strsql_primary .= ", $field = '" . ciniki_core_dbQuote($ciniki, $secondary[$field]) . "' ";
                $strsql_secondary .= ", $field = '' ";
                // Record update as merge action
                $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'],
                    4, 'ciniki_customers', $args['primary_customer_id'], $field, $secondary[$field]);
                // Copy the field history to the primary customer
                $rc = ciniki_core_dbCopyModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'],
                    'ciniki_customers', $args['secondary_customer_id'], $args['primary_customer_id'], $field);
                // Record secondary customer update as merge delete action
                $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'],
                    5, 'ciniki_customers', $args['secondary_customer_id'], $field, '');
                $strsql_history .= "'" . ciniki_core_dbQuote($ciniki, $field) . "'";
                $field_count++;
            }
        }
    }
    if( $field_count > 0 ) {
        $strsql_primary .= "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql_primary, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.106', 'msg'=>'Unable to update customer details', 'err'=>$rc['err']));
        }
        $strsql_secondary .= "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql_secondary, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.107', 'msg'=>'Unable to update customer details', 'err'=>$rc['err']));
        }
    }

    //
    // Get existing phones
    //
    $strsql = "SELECT id, phone_label, phone_number "
        . "FROM ciniki_customer_phones "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'phones', 'fname'=>'phone_number', 'fields'=>array('id', 'phone_label', 'phone_number')),
        ));
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.483', 'msg'=>'Unable to existing customer phones', 'err'=>$rc['err']));
    }
    $existing_phones = isset($rc['phones']) ? $rc['phones'] : array();
    $existing_labels = array();
    foreach($existing_phones as $p) {
        $existing_labels[$p['phone_label']] = $p;
    }

    //
    // Merge phones
    //
    $strsql = "SELECT id, uuid, phone_label, phone_number "
        . "FROM ciniki_customer_phones "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.357', 'msg'=>'Unable to customer phones', 'err'=>$rc['err']));
    }
    $phones = $rc['rows'];
    foreach($phones as $i => $row) {
        if( !isset($existing_phones[$row['phone_number']]) && !isset($existing_labels[$row['phone_label']]) ) {
            $strsql = "UPDATE ciniki_customer_phones "
                . "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "WHERE id = '" . $row['id'] . "' "
                . "";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.109', 'msg'=>'Unable to update customer phones', 'err'=>$rc['err']));
            }
            if( $rc['num_affected_rows'] == 1 ) {
                // Record update as merge action
                $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
                    $args['tnid'], 4, 'ciniki_customer_phones', $row['id'], 
                    'customer_id', $args['primary_customer_id']);
            }
        }
    }

    //
    // Get existing emails
    //
    $strsql = "SELECT id, email "
        . "FROM ciniki_customer_emails "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'emails', 'fname'=>'email', 'fields'=>array('id', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.358', 'msg'=>'Unable to existing customer emails', 'err'=>$rc['err']));
    }
    $existing_emails = isset($rc['emails']) ? $rc['emails'] : array();

    //
    // Merge emails
    //
    $strsql = "SELECT id, uuid, email "
        . "FROM ciniki_customer_emails "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.110', 'msg'=>'Unable to customer emails', 'err'=>$rc['err']));
    }
    $emails = $rc['rows'];
    foreach($emails as $i => $row) {
        if( !isset($existing_emails[$row['email']]) && count($existing_emails) < 2 ) {
            $strsql = "UPDATE ciniki_customer_emails "
                . "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "WHERE id = '" . $row['id'] . "' "
                . "";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.111', 'msg'=>'Unable to update customer emails', 'err'=>$rc['err']));
            }
            if( $rc['num_affected_rows'] == 1 ) {
                // Record update as merge action
                $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
                    $args['tnid'], 4, 'ciniki_customer_emails', $row['id'], 
                    'customer_id', $args['primary_customer_id']);
            }
            $existing_emails[$row['email']] = $row;
        }
    }

    //
    // Get existing addresses
    //
    $strsql = "SELECT id, uuid, flags, CONCAT_WS('-',address1,city,province) AS addr "
        . "FROM ciniki_customer_addresses "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'addresses', 'fname'=>'addr', 'fields'=>array('id', 'flags', 'addr')),
        ));
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.108', 'msg'=>'Unable to existing customer addresses', 'err'=>$rc['err']));
    }
    $existing_addresses = isset($rc['addresses']) ? $rc['addresses'] : array();
    $mailing_id = 0;
    $billing_id = 0;
    $public_id = 0;
    foreach($existing_addresses as $addr) {
        if( ($addr['flags']&0x02) ) {
            $billing_id = $addr['id'];
        }
        if( ($addr['flags']&0x04) ) {
            $mailing_id = $addr['id'];
        }
        if( ($addr['flags']&0x08) ) {
            $public_id = $addr['id'];
        }
    }

    //
    // Merge addresses
    //
    $strsql = "SELECT id, uuid, flags, CONCAT_WS('-',address1,city,province) AS addr "
        . "FROM ciniki_customer_addresses "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.407', 'msg'=>'Unable to customer addresses', 'err'=>$rc['err']));
    }
    $addresses = $rc['rows'];
    foreach($addresses as $i => $row) {
        if( !isset($existing_addresses[$row['addr']]) && ($billing_id == 0 || $mailing_id == 0 || $public_id == 0) ) {
            if( $billing_id > 0 && ($row['flags']&0x02) == 0x02 ) {
                $row['flags'] = $row['flags']&0xFFFD; 
            }
            if( $mailing_id > 0 && ($row['flags']&0x04) == 0x04 ) {
                $row['flags'] = $row['flags']&0xFFFB; 
            }
            if( $public_id > 0 && ($row['flags']&0x08) == 0x08 ) {
                $row['flags'] = $row['flags']&0xFFF8; 
            }
            if( ($row['flags']&0x0F) > 0 ) {
                $strsql = "UPDATE ciniki_customer_addresses "
                    . "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
                    . ", last_updated = UTC_TIMESTAMP() "
                    . "WHERE id = '" . $row['id'] . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.113', 'msg'=>'Unable to update customer addresses', 'err'=>$rc['err']));
                }
                if( $rc['num_affected_rows'] == 1 ) {
                    // Record update as merge action
                    $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
                        $args['tnid'], 4, 'ciniki_customer_addresses', $row['id'], 
                        'customer_id', $args['primary_customer_id']);
                }
            }
        }
    }

    //
    // Merge child accounts
    //
    $strsql = "SELECT id "
        . "FROM ciniki_customers "
        . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.112', 'msg'=>'Unable to customer addresses', 'err'=>$rc['err']));
    }
    $children = $rc['rows'];
    foreach($children as $i => $row) {
        $strsql = "UPDATE ciniki_customers "
            . "SET parent_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
            . ", last_updated = UTC_TIMESTAMP() "
            . "WHERE id = '" . $row['id'] . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.393', 'msg'=>'Unable to update customer children', 'err'=>$rc['err']));
        }
        if( $rc['num_affected_rows'] == 1 ) {
            // Record update as merge action
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
                $args['tnid'], 4, 'ciniki_customers', $row['id'], 
                'parent_id', $args['primary_customer_id']);
        }
    }

    //
    // Merge Subscriptions
    //
    if( isset($modules['ciniki.subscriptions']) ) {
        $updated = 0;
        $strsql = "SELECT ciniki_subscriptions.id, "
            . "IFNULL(c1.id, 0) AS c1_id, c1.customer_id AS c1_customer_id, c1.status AS c1_status, UNIX_TIMESTAMP(c1.last_updated) AS c1_last_updated, "
            . "IFNULL(c2.id, 0) AS c2_id, c2.customer_id AS c2_customer_id, c2.status AS c2_status, UNIX_TIMESTAMP(c2.last_updated) AS c2_last_updated "
            . "FROM ciniki_subscriptions "
            . "LEFT JOIN ciniki_subscription_customers AS c1 ON (ciniki_subscriptions.id = c1.subscription_id "
                . "AND c1.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
                . "AND c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_subscription_customers AS c2 ON (ciniki_subscriptions.id = c2.subscription_id "
                . "AND c2.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
                . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.114', 'msg'=>'Unable to find subscriptions', 'err'=>$rc['err']));
        }
        $subscriptions = $rc['rows'];
        foreach($subscriptions as $i => $row) {
            // If the secondary customer has a subscription
            if( $row['c2_id'] > 0 ) {
                // No subscription for primary
                if( $row['c1_id'] == 0 ) {
                    // Move subscription to primary
                    $strsql = "UPDATE ciniki_subscription_customers SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
                        . "WHERE ciniki_subscription_customers.id = '" . ciniki_core_dbQuote($ciniki, $row['c2_id']) . "' "
                        . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . "";
                    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.115', 'msg'=>'Unable to update subscriptions', 'err'=>$rc['err']));
                    }
                    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['tnid'],
                        4, 'ciniki_subscription_customers', $row['c2_id'], 'customer_id', $args['primary_customer_id']);
                    // subscription history automatically moves with the change in customer_id
                    $updated = 1;
                }
                // subscription for primary exists, and secondary is more recent updated, then copy
                elseif( $row['c1_id'] > 0 ) {
                    // If the secondary is more recent than the primary, update the primary
                    if( $row['c2_last_updated'] > $row['c1_last_updated'] && $row['c2_status'] != $row['c1_status']) {
                        $strsql = "UPDATE ciniki_subscription_customers "
                            . "SET last_updated = UTC_TIMESTAMP(), status = '" . ciniki_core_dbQuote($ciniki, $row['c2_status']) . "' "
                            . "WHERE ciniki_subscription_customers.id = '" . ciniki_core_dbQuote($ciniki, $row['c1_id']) . "' "
                            . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . "";
                        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.116', 'msg'=>'Unable to update subscriptions', 'err'=>$rc['err']));
                        }
                        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['tnid'],
                            4, 'ciniki_subscription_customers', $row['c1_id'], 'status', $row['c2_status']);
                        
                        // Copy subscription history
                        $rc = ciniki_core_dbCopyModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['tnid'],
                            'ciniki_subscription_customers', $row['c2_id'], $row['c1_id'], 'status');

                        $updated = 1;
                    }   
                    // Unsubscribe secondary, so that customer can be deleted
                    if( $row['c2_status'] != '60' ) {
                        $strsql = "UPDATE ciniki_subscription_customers "
                            . "SET status = 60 "
                            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $row['c2_id']) . "' "
                            . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . "";
                        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.117', 'msg'=>'Unable to update subscriptions', 'err'=>$rc['err']));
                        }

                        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['tnid'],
                            4, 'ciniki_subscription_customers', $row['c2_id'], 'status', '60');
                        $updated = 1;
                    }
                }
            }
        }
        
        if( $updated == 1 ) {
            //
            // Update the last_change date in the tenant modules
            // Ignore the result, as we don't want to stop user updates if this fails.
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
            ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'subscriptions');
        }
    }
    
    //
    // Get existing reminders
    //
    $strsql = "SELECT id "
        . "FROM ciniki_customer_reminders "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'reminders', 'fname'=>'id', 'fields'=>array('id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.498', 'msg'=>'Unable to existing customer phones', 'err'=>$rc['err']));
    }
    $reminders = isset($rc['reminders']) ? $rc['reminders'] : array();
    foreach($reminders as $reminder) {
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.reminder', $reminder['id'], array(
            'customer_id' => $args['primary_customer_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.485', 'msg'=>'', 'err'=>$rc['err']));
        }
    }

    //
    // Merge customer product purchases 
    //
    $strsql = "SELECT id "
        . "FROM ciniki_customer_product_purchases "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'purchases', 'fname'=>'id', 'fields'=>array('id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.355', 'msg'=>'Unable to existing customer phones', 'err'=>$rc['err']));
    }
    $purchases = isset($rc['purchases']) ? $rc['purchases'] : array();
    foreach($purchases as $purchase) {
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.product_purchase', $purchase['id'], array(
            'customer_id' => $args['primary_customer_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.497', 'msg'=>'', 'err'=>$rc['err']));
        }
    }

    //
    // Check for module hooks that need to be updated
    //
    foreach($ciniki['tenant']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'customerMerge');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array(
                'primary_customer_id'=>$args['primary_customer_id'], 
                'primary_display_name'=>$primary['display_name'],
                'secondary_customer_id'=>$args['secondary_customer_id'], 
                'secondary_display_name'=>$secondary['display_name'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.124', 'msg'=>'Unable to merge customer.', 'err'=>$rc['err']));
            }
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
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'customers');

    return array('stat'=>'ok');
}
?>
