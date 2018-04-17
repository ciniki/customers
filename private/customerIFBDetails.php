<?php
//
// Description
// -----------
// This function return the details for a customer when the tenant IFB flag is enabled.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_customerIFBDetails($ciniki, $tnid, $customer_id, $args) {
 
    $rsp = array('stat'=>'ok');

    //
    // Load the customer
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

    if( $rsp['customer']['type'] == 10 ) {
        return $rsp;
    }
    
    //
    // Load parent if any
    //
    if( isset($rsp['customer']['parent_id']) && $rsp['customer']['parent_id'] > 0 ) {
        $rc = ciniki_customers_customerLoad($ciniki, $tnid, $rsp['customer']['parent_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.232', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        $rsp['parent'] = $rc['customer'];
        $rc = ciniki_customers_processDetails($ciniki, $tnid, $rsp['customer']['parent'], array(
            'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.255', 'msg'=>'Unable to process results', 'err'=>$rc['err']));
        }
        if( $rsp['parent']['type'] == 20 ) {
            $rsp['business_details'] = $rc['details'];
        } elseif( $rsp['parent']['type'] == 30 ) {
            $rsp['family_details'] = $rc['details'];
        }
    }

    //
    // Load any child accounts for the customer or the parent
    //
    $rsp['child_accounts'] = array();
    if( $rsp['customer']['type'] == 20 || $rsp['customer']['type'] == 30 || $rsp['customer']['parent_id'] > 0 ) {
        $strsql = "SELECT id, type, display_name "
            . "FROM ciniki_customers ";
        if( $rsp['customer']['type'] == 20 || $rsp['customer']['type'] == 30 ) {
            $strsql .= "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $rsp['customer']['id']) . "' ";
        } else {
            $strsql .= "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $rsp['customer']['parent_id']) . "' ";
        }
        $strsql .= "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        error_log($strsql);
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
    $rsp['parent_details'] = array();
    $rsp['admin_details'] = array();
    $rsp['children'] = array();
    $rsp['employees'] = array();

    if( isset($rsp['child_accounts']) ) {
        foreach($rsp['child_accounts'] as $child) {
            error_log('Process child');
            //
            // Load the customer details
            //
            $rc = ciniki_customers_customerLoad($ciniki, $tnid, $child['id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.237', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
            }
            $customer = $rc['customer'];
            error_log($customer['type']);
            if( $customer['type'] == 21 ) {
                //
                // Process the details
                //
                $rc = ciniki_customers_processDetails($ciniki, $tnid, $customer, array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.238', 'msg'=>'Unable to process results', 'err'=>$rc['err']));
                }
                $rsp['parent_details'] = array_merge($rsp['parent_details'], $rc['details']);
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
                error_log('--');
                $rsp['admin_details'] = array_merge($rsp['admin_details'], $rc['details']);
            }
            elseif( $customer['type'] == 32 ) {
                $rsp['employees'][] = $customer;
            }
        }
    }
    error_log(print_r($rsp['customer_details'], true));

    return $rsp;
}
?>
