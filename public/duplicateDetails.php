<?php
//
// Description
// -----------
// This function will return a customer record, along with a list of details for display
// in a simplegrid in the UI.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_duplicateDetails($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer1_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer 1'),
        'customer2_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer 2'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.duplicateDetails'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

/*    if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) { 
        //
        // Make sure accounts are enabled
        //
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.349', 'msg'=>'Feature not available'));
    } */

    //
    // Get the details for the account
    //
    $rsp = array('stat'=>'ok', 'data_tabs'=>array());
    foreach(['1', '2'] as $cust) {
        //
        // Check for the type of customer
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) { 
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'accountDetails');
            $rc = ciniki_customers__accountDetails($ciniki, $args['tnid'], $args['customer' . $cust . '_id'], array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $rsp['customer' . $cust] = $rc;

            $rsp['details' . $cust] = array(
                array('label'=>'ID', 'value'=>$rc['customer']['id']),
                );
            if( $rc['customer']['type'] == 10 ) {
                $rsp['details' . $cust][] = array('label'=>'Family/Business', 'value'=>'');
            } else {
                $rsp['details' . $cust][] = array('label'=>'Family/Business', 'value'=>$rc['account']['display_name']);
            }
            $rsp['details' . $cust][] = array('label'=>'Type', 'value'=>$rc['customer']['type_text']);
            $rsp['details' . $cust][] = array('label'=>'Name', 'value'=>$rc['customer']['display_name']);
            $rsp['details' . $cust][] = array('label'=>'Email', 'value'=>$rc['customer']['primary_email']);
            $rsp['details' . $cust][] = array('label'=>'Email', 'value'=>$rc['customer']['secondary_email']);
            $rsp['details' . $cust][] = array('label'=>'Home', 'value'=>$rc['customer']['phone_home']);
            $rsp['details' . $cust][] = array('label'=>'Cell', 'value'=>$rc['customer']['phone_cell']);
            $rsp['details' . $cust][] = array('label'=>'Work', 'value'=>$rc['customer']['phone_work']);
            $rsp['details' . $cust][] = array('label'=>'Fax', 'value'=>$rc['customer']['phone_fax']);
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x8000) ) {
                $rsp['details' . $cust][] = array('label'=>'Birthday', 'value'=>$rc['customer']['birthdate']);
            }
            if( $rc['customer']['mailing_address_id'] > 0 ) {
                $addr = $rc['customer']['mailing_address1'];
                if( $rc['customer']['mailing_address2'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['mailing_address2'];
                }
                if( $rc['customer']['mailing_city'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['mailing_city'];
                }
                if( $rc['customer']['mailing_province'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['mailing_province'];
                }
                if( $rc['customer']['mailing_postal'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['mailing_postal'];
                }
                if( $rc['customer']['mailing_country'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['mailing_country'];
                }
                if( $addr != '' ) {
                    $rsp['details' . $cust][] = array('label'=>'Mailing', 'value'=>$addr);
                    if( ($rc['customer']['mailing_flags']&0x02) == 0x02 ) {
                        $rsp['details' . $cust][] = array('label'=>'Billing', 'value'=>$addr);
                    }
                }
            } else {
                $rsp['details' . $cust][] = array('label'=>'Mailing', 'value'=>'');
                if( ($rc['customer']['mailing_flags']&0x02) == 0x02 ) {
                    $rsp['details' . $cust][] = array('label'=>'Billing', 'value'=>'');
                }
            }
            if( $rc['customer']['billing_address_id'] > 0 ) {
                $addr = $rc['customer']['billing_address1'];
                if( $rc['customer']['billing_address2'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['billing_address2'];
                }
                if( $rc['customer']['billing_city'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['billing_city'];
                }
                if( $rc['customer']['billing_province'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['billing_province'];
                }
                if( $rc['customer']['billing_postal'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['billing_postal'];
                }
                if( $rc['customer']['billing_country'] != '' ) {
                    $addr .= ($addr != '' ? ', ' : '') . $rc['customer']['billing_country'];
                }
                if( $addr != '' ) {
                    $rsp['details' . $cust][] = array('label'=>'Billing', 'value'=>$addr);
                }
            } elseif( ($rc['customer']['mailing_flags']&0x02) == 0 ) {
                $rsp['details' . $cust][] = array('label'=>'Billing', 'value'=>'');
            }
            
            $rsp['details' . $cust][] = array('label'=>'Notes', 'value'=>$rc['customer']['notes']);
        } else {
            //
            // Load details for customer types 1 or 2 (original customer customers)
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
            $rc = ciniki_customers__customerDetails($ciniki, $args['tnid'], $args['customer' . $cust . '_id'], array('emails'=>'yes', 'addresses'=>'yes', 'phones'=>'yes', 'children'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $rsp['customer' . $cust] = $rc;
            $rsp['details' . $cust] = array();
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x010000) ) {
                $rsp['details' . $cust][] = array('label'=>'ID', 'value'=>$rc['customer']['id']);
            }
            $rsp['details' . $cust][] = array('label'=>'Parent', 'value'=>$rc['customer']['parent_name']);
            $rsp['details' . $cust][] = array('label'=>'Title', 'value'=>$rc['customer']['prefix']);
            $rsp['details' . $cust][] = array('label'=>'First', 'value'=>$rc['customer']['first']);
            $rsp['details' . $cust][] = array('label'=>'Middle', 'value'=>$rc['customer']['middle']);
            $rsp['details' . $cust][] = array('label'=>'Last', 'value'=>$rc['customer']['last']);
            $rsp['details' . $cust][] = array('label'=>'Degrees', 'value'=>$rc['customer']['suffix']);
            $rsp['details' . $cust][] = array('label'=>'Company', 'value'=>$rc['customer']['company']);
            $rsp['details' . $cust][] = array('label'=>'Department', 'value'=>$rc['customer']['department']);
            $rsp['details' . $cust][] = array('label'=>'Title', 'value'=>$rc['customer']['title']);
            $rsp['details' . $cust][] = array('label'=>'Birthdate', 'value'=>$rc['customer']['birthdate']);

            //
            // Membership details
            //
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02) ) {
                $rsp['details' . $cust][] = array('label'=>'Member Status', 'value'=>$rc['customer']['member_status_display']);
//                $rsp['details' . $cust][] = array('label'=>'Member Last Paid', 'value'=>$rc['customer']['member_lastpaid_display']);
//                $rsp['details' . $cust][] = array('label'=>'Member Expires', 'value'=>$rc['customer']['member_expires_display']);
            }
        
            //
            // Add contact details
            //
            $sections = array(
                'ciniki.customers.emails' => array(
                    'label' => 'Emails',
                    'type' => 'simplegrid', 
                    'num_cols' => 1,
                    'noData' => 'No emails',
                    'data' => isset($rc['customer']['emails']) ? $rc['customer']['emails'] : array(),
                    'cellValues' => array(
                        '0' => 'd.email.address;',
                        ),
                    ), 
                'ciniki.customers.phones' => array( 
                    'label' => 'Phone Numbers',
                    'type' => 'simplegrid', 
                    'num_cols' => 2,
                    'noData' => 'No phone numbers',
                    'data' => isset($rc['customer']['phones']) ? $rc['customer']['phones'] : array(),
                    'cellClasses' => array('label', ''),
                    'cellValues' => array(
                        '0' => 'd.phone_label;',
                        '1' => 'd.phone_number;',
                        ),
                    ),
                'ciniki.customers.addresses' => array( 
                    'label' => 'Addresses',
                    'type' => 'simplegrid', 
                    'num_cols' => 2,
                    'noData' => 'No addresses',
                    'data' => isset($rc['customer']['addresses']) ? $rc['customer']['addresses'] : array(),
                    'cellClasses' => array('label', ''),
                    'cellValues' => array(
                        '0' => 'd.address.label;',
                        '1' => 'd.address.joined;',
                        ),
                    ),
                );
            $found = 'no';
            foreach($rsp['data_tabs'] as $tid => $existing_tab) {
                if( $existing_tab['id'] == 'ciniki.customers.contact') {
                    $found = 'yes';
                    foreach($sections as $sid => $section) { 
                        if( $cust == 1 ) {
                            $section['aside'] = 'yes';
                        }
                        $rsp['data_tabs'][$tid]['sections'][$sid . '.' . $cust] = $section;
                    }
                }
            }
            if( $found == 'no' ) {
                foreach($sections as $sid => $section) { 
                    if( $cust == 1 ) {
                        $section['aside'] = 'yes';
                    }
                    $sections[$sid . '.' . $cust] = $section;
                    unset($sections[$sid]);
                }
                $rsp['data_tabs'][] = array(
                    'id' => 'ciniki.customers.contact',
                    'label' => 'Contact',
                    'sections' => $sections,
                    );
            }

            //
            // Add children
            //
            $sections = array(
                'ciniki.customers.children' => array(
                    'label' => 'Children',
                    'type' => 'simplegrid', 
                    'num_cols' => 1,
                    'noData' => 'No children',
                    'data' => isset($rc['customer']['children']) ? $rc['customer']['children'] : array(),
                    'cellValues' => array(
                        '0' => 'd.display_name;',
                        ),
                    ), 
                );
            $found = 'no';
            foreach($rsp['data_tabs'] as $tid => $existing_tab) {
                if( $existing_tab['id'] == 'ciniki.customers.children') {
                    $found = 'yes';
                    foreach($sections as $sid => $section) { 
                        if( $cust == 1 ) {
                            $section['aside'] = 'yes';
                        }
                        $rsp['data_tabs'][$tid]['sections'][$sid . '.' . $cust] = $section;
                    }
                }
            }
            if( $found == 'no' ) {
                foreach($sections as $sid => $section) { 
                    if( $cust == 1 ) {
                        $section['aside'] = 'yes';
                    }
                    $sections[$sid . '.' . $cust] = $section;
                    unset($sections[$sid]);
                }
                $rsp['data_tabs'][] = array(
                    'id' => 'ciniki.customers.children',
                    'label' => 'Children',
                    'sections' => $sections,
                    );
            }
        }

        //
        // Build list of child ids, if the requested customer is a family or business
        //
        $uiDataArgs = array();
        if( ($rsp['customer' . $cust]['customer']['type'] == 20 || $rsp['customer' . $cust]['customer']['type'] == 30) && isset($rsp['customer' . $cust]['children']) ) {
            $uiDataArgs['customer_ids'] = array($args['customer' . $cust . '_id']);
            foreach($rsp['customer' . $cust]['parents'] as $parent) {
                $uiDataArgs['customer_ids'][] = $parent['id'];
            }
            foreach($rsp['customer' . $cust]['children'] as $child) {
                $uiDataArgs['customer_ids'][] = $child['id'];
            }
        } else {
            $uiDataArgs['customer_id'] = $args['customer' . $cust . '_id'];
        }

        //
        // Call the hooks to other modules for any data to attach to customer account
        //
        foreach($ciniki['tenant']['modules'] as $module => $m) {
            list($pkg, $mod) = explode('.', $module);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'uiCustomersData');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], $uiDataArgs);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.350', 'msg'=>'Unable to get customer information.', 'err'=>$rc['err']));
                }
                if( isset($rc['tabs']) ) {
                    foreach($rc['tabs'] as $tab) {
                        //
                        // Check if tab already exists
                        //
                        $found = 'no';
                        foreach($rsp['data_tabs'] as $tid => $existing_tab) {
                            if( $existing_tab['id'] == $tab['id'] ) {
                                $found = 'yes';
                                foreach($tab['sections'] as $sid => $section) { 
                                    if( $cust == 1 ) {
                                        $section['aside'] = 'yes';
                                    }
                                    $rsp['data_tabs'][$tid]['sections'][$sid . '.' . $cust] = $section;
                                }
                            }
                        }
                        if( $found == 'no' ) {
                            foreach($tab['sections'] as $sid => $section) { 
                                if( $cust == 1 ) {
                                    $section['aside'] = 'yes';
                                }
                                $tab['sections'][$sid . '.' . $cust] = $section;
                                unset($tab['sections'][$sid]);
                            }
                            $rsp['data_tabs'][] = $tab;
                        }
                    }
                }
            }
        }
    }

    return $rsp;
}
?>
