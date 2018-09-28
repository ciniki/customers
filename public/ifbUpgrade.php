<?php
//
// Description
// -----------
// This method will upgrade the customers database to the IFB mode (Individuals, Families, Businesses)
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_ifbUpgrade(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'upgrade'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Perform Upgrade'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.ifbUpgrade'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $rsp = array('stat'=>'ok', 'num_issues'=>0);

    //
    // Check to make sure the ifb flag has not been set yet
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.269', 'msg'=>'IFB Already Enabled'));
    }

    //
    // Check for more than 2 emails
    //
    $strsql = "SELECT customers.id, customers.display_name, COUNT(emails.id) AS num_items "
        . "FROM ciniki_customer_emails AS emails, ciniki_customers AS customers "
        . "WHERE emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND emails.customer_id = customers.id "
        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customers.type < 10 "
        . "GROUP BY emails.customer_id "
        . "HAVING num_items > 2 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'num_items')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.236', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
    }
    $rsp['num_issues'] += isset($rc['customers']) ? count($rc['customers']) : 0;
    $rsp['too_many_emails'] = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Check for more than 4 phone numbers
    //
    $strsql = "SELECT customers.id, customers.display_name, COUNT(phones.id) AS num_items "
        . "FROM ciniki_customer_phones AS phones, ciniki_customers AS customers "
        . "WHERE phones.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND phones.customer_id = customers.id "
        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customers.type < 10 "
        . "GROUP BY phones.customer_id "
        . "HAVING num_items > 4 "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'num_items')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.243', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
    }
    $rsp['num_issues'] += isset($rc['customers']) ? count($rc['customers']) : 0;
    $rsp['too_many_phones'] = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Check for non standard phone labels
    //
    $strsql = "SELECT customers.id, customers.display_name, COUNT(phones.id) AS num_items "
        . "FROM ciniki_customer_phones AS phones, ciniki_customers AS customers "
        . "WHERE phones.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND phones.phone_label NOT IN ('Home', 'Work', 'Cell', 'Fax') "
        . "AND phones.customer_id = customers.id "
        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customers.type < 10 "
        . "GROUP BY phones.customer_id "
        . "HAVING num_items > 0 "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'num_items')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.244', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
    }
    $rsp['num_issues'] += isset($rc['customers']) ? count($rc['customers']) : 0;
    $rsp['bad_phone_labels'] = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Check for more multiple of same label for phone numbers
    //
    $strsql = "SELECT customers.id, customers.display_name, phones.phone_label, COUNT(phones.id) AS num_items "
        . "FROM ciniki_customer_phones AS phones, ciniki_customers AS customers "
        . "WHERE phones.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND phones.customer_id = customers.id "
        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customers.type < 10 "
        . "GROUP BY phones.customer_id, phone_label "
        . "HAVING num_items > 1 "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'num_items')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.239', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
    }
    $rsp['num_issues'] += isset($rc['customers']) ? count($rc['customers']) : 0;
    $rsp['duplicate_phone_labels'] = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Check for more than 2 addresses
    //
    $strsql = "SELECT customers.id, customers.display_name, COUNT(addresses.id) AS num_items "
        . "FROM ciniki_customer_addresses AS addresses, ciniki_customers AS customers "
        . "WHERE addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND addresses.customer_id = customers.id "
        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customers.type < 10 "
        . "GROUP BY addresses.customer_id "
        . "HAVING num_items > 2 "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'num_items')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.240', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
    }
    $rsp['num_issues'] += isset($rc['customers']) ? count($rc['customers']) : 0;
    $rsp['too_many_addresses'] = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Check for more than 2 links
    //
    $strsql = "SELECT customers.id, customers.display_name, COUNT(links.id) AS num_items "
        . "FROM ciniki_customer_links AS links, ciniki_customers AS customers "
        . "WHERE links.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND links.customer_id = customers.id "
        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customers.type < 10 "
        . "GROUP BY links.customer_id "
        . "HAVING num_items > 1 "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'num_items')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.241', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
    }
    $rsp['num_issues'] += isset($rc['customers']) ? count($rc['customers']) : 0;
    $rsp['too_many_links'] = isset($rc['customers']) ? $rc['customers'] : array();

     
    //
    // Check if upgrade is to be run
    //
    if( isset($args['upgrade']) && $args['upgrade'] == 'yes' ) {
        if( $rsp['num_issues'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.242', 'msg'=>'Customers database is not year ready for upgrade', 'err'=>$rc['err']));
        }
        
        //  
        // Turn off autocommit
        //  
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   

        //
        // Load all the customers basic information
        //
        $strsql = "SELECT id, eid, parent_id, type, display_name, prefix, first, middle, last, suffix, company, connection, start_date "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 
                'fields'=>array('id', 'eid', 'parent_id', 'type', 'display_name', 
                    'prefix', 'first', 'middle', 'last', 'suffix', 'company', 'connection', 'start_date')),
            ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.245', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
        }
        if( isset($rc['customers']) ) {
            $customers = $rc['customers'];
            //
            // Find the children for each parent account
            //
            foreach($customers as $customer_id => $customer) {
                if( $customer['parent_id'] > 0 ) {
                    if( !isset($customers[$customer['parent_id']]) ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.247', 'msg'=>'Parent missing for ' . $customer['display_name'], 'err'=>$rc['err']));
                    }
                    if( !isset($customers[$customer['parent_id']]['children']) ) {
                        $customers[$customer['parent_id']]['children'] = array();
                    }
                    $customers[$customer['parent_id']]['children'][$customer_id] = $customer;
                    unset($customers[$customer_id]);
                }
            }
            foreach($customers as $customer_id => $customer) {
                //
                // If person and no parent_id and no children , then convert to individual
                //
                if( $customer['type'] == 1 && $customer['parent_id'] == 0 && !isset($customer['children']) ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $customer_id, array(
                        'company' => '',
                        'type' => 10,
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.246', 'msg'=>'Unable to update ' . $customer['display_name'], 'err'=>$rc['err']));
                    }
                }
                //
                // Person with children
                //
                elseif( $customer['type'] == 1 && isset($customer['children']) ) {
                    //
                    // Setup the family
                    //
                    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.customer', array(
                        'eid' => $customer['eid'] . '-family', 
                        'type' => 20,
                        'display_name' => $customer['last'] != '' ? $customer['last'] : $customer['first'],
                        'sort_name' => $customer['last'] != '' ? $customer['last'] : $customer['first'],
                        'company' => $customer['last'] != '' ? $customer['last'] : $customer['first'],
                        'first' => '',
                        'middle' => '',
                        'last' => '',
                        'connection' => $customer['connection'],
                        'start_date' => $customer['start_date'],
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.249', 'msg'=>'Unable to update ' . $customer['display_name'], 'err'=>$rc['err']));
                    }
                    $family_id = $rc['id'];

                    //
                    // Convert to parent
                    //
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $customer_id, array(
                        'parent_id'=>$family_id,
                        'type'=>21,
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.250', 'msg'=>'Unable to update ' . $customer['display_name'], 'err'=>$rc['err']));
                    }

                    //
                    // Convert children
                    //
                    if( isset($customer['children']) ) {
                        foreach($customer['children'] as $child) {
                            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $child['id'], array(
                                'parent_id' => $family_id, 
                                'type'=>22,
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.251', 'msg'=>'Unable to update ' . $customer['display_name'], 'err'=>$rc['err']));
                            }
                        }
                    }
                }
                //
                // Convert a business
                //
                elseif( $customer['type'] == 2 ) {
                    //
                    // Check if the contact name is already setup as a child
                    //
                    $admin_customer_id = 0;
                    if( $customer['first'] != '' && $customer['last'] != '' && isset($customer['children']) ) {
                        foreach($customer['children'] as $child) {
                            if( $child['first'] == $customer['first'] && $child['last'] == $customer['last'] ) {
                                $admin_customer_id = $child['id'];
                            }
                        }
                    }

                    //
                    // When the contact person is also setup a child customer, then a new business record doesn't need to be added.
                    //
                    if( $admin_customer_id > 0 ) {
                        //
                        // Convert business to account
                        //
                        $customer['type'] = 30;
                        $rc = ciniki_customers_customerUpdateName($ciniki, $args['tnid'], $customer, $customer['id'], array());
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.227', 'msg'=>'Unable to process customer name', 'err'=>$rc['err']));
                        }
                        $display_name = $rc['display_name'];
                        $sort_name = $rc['sort_name'];
                        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $customer_id, array(
                            'type' => 30,
                            'parent_id' => 0,
                            'display_name' => $display_name,
                            'sort_name' => $sort_name,
                            ), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.252', 'msg'=>'Unable to update ' . $customer['display_name'], 'err'=>$rc['err']));
                        }
                        $business_id = $customer['id'];
                    } else {
                        //
                        // Setup the business
                        //
                        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.customer', array(
                            'eid' => $customer['eid'] . '', 
                            'type' => 30,
                            'display_name' => $customer['company'],
                            'sort_name' => $customer['company'],
                            'company' => $customer['company'],
                            'first' => '',
                            'middle' => '',
                            'last' => '',
                            'connection' => $customer['connection'],
                            'start_date' => $customer['start_date'],
                            ), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.248', 'msg'=>'Unable to update ' . $customer['display_name'], 'err'=>$rc['err']));
                        }
                        $business_id = $rc['id'];

                        //
                        // Convert any FATT registrations to the new company
                        //
                        $strsql = "SELECT id, customer_id, student_id "
                            . "FROM ciniki_fatt_offering_registrations "
                            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . "";
                        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.313', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                        }
                        if( isset($rc['rows']) ) {
                            $regs = $rc['rows'];
                            foreach($regs as $reg) {    
                                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.offeringregistration', $reg['id'], array('customer_id'=>$business_id), 0x04);
                                if( $rc['stat'] != 'ok' ) {
                                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.314', 'msg'=>'Unable to update registration', 'err'=>$rc['err']));
                                }
                            }
                        }

                        //
                        // Convert any FATT AEDs to the new company
                        //
                        $strsql = "SELECT id, customer_id "
                            . "FROM ciniki_fatt_aeds "
                            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . "";
                        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.313', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                        }
                        if( isset($rc['rows']) ) {
                            $aeds = $rc['rows'];
                            foreach($aeds as $aed) {    
                                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.aed', $aed['id'], array('customer_id'=>$business_id), 0x04);
                                if( $rc['stat'] != 'ok' ) {
                                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.315', 'msg'=>'Unable to update aed', 'err'=>$rc['err']));
                                }
                            }
                        }

                        //
                        // Convert any Sapos Invoices to the new company
                        //
                        $strsql = "SELECT id, customer_id "
                            . "FROM ciniki_sapos_invoices "
                            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                            . "";
                        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.313', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                        }
                        if( isset($rc['rows']) ) {
                            $aeds = $rc['rows'];
                            foreach($aeds as $aed) {    
                                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice', $aed['id'], array('customer_id'=>$business_id), 0x04);
                                if( $rc['stat'] != 'ok' ) {
                                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.316', 'msg'=>'Unable to update invoice', 'err'=>$rc['err']));
                                }
                            }
                        }

                        //
                        // Convert customer to admin
                        //
                        $customer['type'] = 31;
                        $rc = ciniki_customers_customerUpdateName($ciniki, $args['tnid'], $customer, $customer['id'], array());
                        if( $rc['stat'] != 'ok' ) {
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.227', 'msg'=>'Unable to process customer name', 'err'=>$rc['err']));
                        }
                        $display_name = $rc['display_name'];
                        $sort_name = $rc['sort_name'];
                        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $customer_id, array(
                            'type' => 31,
                            'parent_id' => $business_id, 
                            'display_name' => $display_name,
                            'sort_name' => $sort_name,
                            'company' => '',
                            ), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.252', 'msg'=>'Unable to update ' . $customer['display_name'], 'err'=>$rc['err']));
                        }
                    }

                    //
                    // Convert employees
                    //
                    if( isset($customer['children']) ) {
                        foreach($customer['children'] as $child) {
                            //
                            // Update the name for the child
                            //
                            $child['type'] = 32;
                            $rc = ciniki_customers_customerUpdateName($ciniki, $args['tnid'], $child, $child['id'], array());
                            if( $rc['stat'] != 'ok' ) {
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.227', 'msg'=>'Unable to process customer name', 'err'=>$rc['err']));
                            }
                            $display_name = $rc['display_name'];
                            $sort_name = $rc['sort_name'];

                            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $child['id'], array(
                                'parent_id' => $business_id, 
                                'display_name' => $display_name,
                                'sort_name' => $sort_name,
                                'company' => '',
                                'type' => ($admin_customer_id == $child['id'] ? 31 : 32),
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.253', 'msg'=>'Unable to update ' . $customer['display_name'], 'err'=>$rc['err']));
                            }
                        }
                    }
                }
            }
        }
        
        //
        // Everything succeeded, set the IFB flag
        //
        $strsql = "UPDATE ciniki_tenant_modules SET flags = (flags | 0x0800) "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND module = 'customers' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.270', 'msg'=>'Unable to set IFB flag', 'err'=>$rc['err']));
        }

        //
        // Commit the database changes
        //
        $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

    }

    return $rsp;
}
?>
