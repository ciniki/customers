<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_processRequest(&$ciniki, $settings, $business_id, $args) {

    if( !isset($ciniki['business']['modules']['ciniki.customers']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.210', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    if( isset($args['module_page']) && $args['module_page'] == 'ciniki.customers.dealers' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'processRequestDealers');
        return ciniki_customers_web_processRequestDealers($ciniki, $settings, $business_id, $args);
    } elseif( isset($args['module_page']) && $args['module_page'] == 'ciniki.customers.distributors' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'processRequestDistributors');
        return ciniki_customers_web_processRequestDistributors($ciniki, $settings, $business_id, $args);
    }

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.211', 'msg'=>"I'm sorry, the page you requested does not exist."));
}
?>
