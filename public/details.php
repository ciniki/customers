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
function ciniki_customers_details($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'phones'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Phones'),
        'emails'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Emails'),
        'addresses'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Addresses'),
        'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Subscriptions'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.details', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the details for an IFB account
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerIFBDetails');
        $rc = ciniki_customers_customerIFBDetails($ciniki, $args['tnid'], $args['customer_id'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp = $rc;

        $rsp['data_tabs'] = array();

        //
        // Build list of child ids
        //
        $uiDataArgs = array();
        if( isset($rsp['children']) && $rsp['customer']['type'] == 20 || $rsp['customer']['type'] == 30 ) {
            $uiDataArgs['customer_ids'] = array($args['customer_id']);
            if( isset($rsp['parent']) ) {
                $uiDataArgs['customer_ids'][] = $rsp['parent']['id'];
            }
            foreach($rsp['children'] as $child) {
                $uiDataArgs['customer_ids'][] = $child['id'];
            }
        } else {
            $uiDataArgs['customer_id'] = $args['customer_id'];
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
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.235', 'msg'=>'Unable to get customer information.', 'err'=>$rc['err']));
                }
                if( isset($rc['tabs']) ) {
                    foreach($rc['tabs'] as $tab) {
                        $rsp['data_tabs'][] = $tab;
                    }
                }
            }
        }

        return $rsp;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
    return ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], $args);
}
?>
