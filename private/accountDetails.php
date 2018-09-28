<?php
//
// Description
// -----------
// This function return the details for a customer when the tenant IFB/Accounts flag is enabled.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers__accountDetails($ciniki, $tnid, $customer_id, $args) {
 
    $rsp = array('stat'=>'ok');

    //
    // Load the details of the requested customer. This may or may not be the parent account.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerLoad');
    $rc = ciniki_customers_customerLoad($ciniki, $tnid, $customer_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.231', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
    }
    $rsp['customer'] = $rc['customer'];

    //
    // Process the details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'processDetails');
    $rc = ciniki_customers_processDetails($ciniki, $tnid, $rsp['customer'], array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.233', 'msg'=>'Unable to process results', 'err'=>$rc['err']));
    }
    $rsp['customer_details'] = $rc['details'];

    //
    // Load account details if any
    //
    if( isset($rsp['customer']['parent_id']) && $rsp['customer']['parent_id'] > 0 ) {
        //
        // Load the account customer, the rsp['customer'] is then the requested customer information
        //
        $rc = ciniki_customers_customerLoad($ciniki, $tnid, $rsp['customer']['parent_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.232', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        $rsp['account'] = $rc['customer'];

        //
        // Process the account details
        //
        $rc = ciniki_customers_processDetails($ciniki, $tnid, $rsp['account'], array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.255', 'msg'=>'Unable to process results', 'err'=>$rc['err']));
        }
        $rsp['account_details'] = $rc['details'];
    }
    else {
        //
        // The customer requested is the account
        $rsp['account'] = $rsp['customer'];
        $rsp['account_details'] = $rsp['customer_details'];
    }

    $name = array_shift($rsp['account_details']);
    $rsp['account_name'] = array(array('id'=>$rsp['account']['id'], 'display_name'=>$name['value']));

    if( $rsp['account']['type'] == 10 ) {
        return $rsp;
    }

    //
    // Load any child accounts for the customer or the parent
    //
    $rsp['child_accounts'] = array();
    if( $rsp['account']['type'] == 20 || $rsp['account']['type'] == 30 ) {
        $strsql = "SELECT id, type, display_name "
            . "FROM ciniki_customers "
            . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $rsp['account']['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 'fields'=>array('id', 'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.234', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
        }
        $rsp['child_accounts'] = isset($rc['customers']) ? $rc['customers'] : array();
    }

    //
    // The details for business/parent accounts and children
    //
//    $rsp['parent_details'] = array();
//    $rsp['admin_details'] = array();
    $rsp['parents'] = array();
    $rsp['children'] = array();

    if( isset($rsp['child_accounts']) ) {
        foreach($rsp['child_accounts'] as $child) {
            //
            // Load the customer details
            //
            $rc = ciniki_customers_customerLoad($ciniki, $tnid, $child['id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.237', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
            }
            $customer = $rc['customer'];
            if( $customer['type'] == 21 ) {
                //
                // Process the details
                //
                $rc = ciniki_customers_processDetails($ciniki, $tnid, $customer, array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.238', 'msg'=>'Unable to process results', 'err'=>$rc['err']));
                }
//                $rsp['parent_details'] = array_merge($rsp['parent_details'], $rc['details']);
                $rsp['parents'][] = $customer;
            }
            elseif( $customer['type'] == 22 ) {
                $rsp['children'][] = $customer;
            }
            elseif( $customer['type'] == 31 ) {
                //
                // Process the details
                //
                $rc = ciniki_customers_processDetails($ciniki, $tnid, $customer, array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.254', 'msg'=>'Unable to process results', 'err'=>$rc['err']));
                }
//                $rsp['admin_details'] = array_merge($rsp['admin_details'], $rc['details']);
                $rsp['parents'][] = $customer;
            }
            elseif( $customer['type'] == 32 ) {
                $rsp['children'][] = $customer;
            }
        }
    }

    return $rsp;
}
?>
