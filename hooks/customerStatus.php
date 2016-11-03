<?php
//
// Description
// -----------
// This function will return the status of a customer.
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_hooks_customerStatus($ciniki, $business_id, $args) {
    
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $strsql = "SELECT status, member_status, dealer_status, distributor_status "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['customer']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.18', 'msg'=>'Customer not found'));
        }
        $customer = $rc['customer'];

        return array('stat'=>'ok', 'customer'=>$customer);
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.19', 'msg'=>'No customer specified'));
}
?>
