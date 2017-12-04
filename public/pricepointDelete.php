<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_pricepointDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'pricepoint_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Price Point'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.pricepointDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

    //
    // Check if any customers are still attached to this price point
    //
    $strsql = "SELECT 'customers', COUNT(*) "
        . "FROM ciniki_customers "
        . "WHERE pricepoint_id = '" . ciniki_core_dbQuote($ciniki, $args['pricepoint_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['num']['customers']) && $rc['num']['customers'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.127', 'msg'=>'Customers are still using this price point, it cannot be deleted.'));
    }

    //
    // Check if any tax types are currently using this price point
    //
    $num_invoices = 0;
    if( isset($modules['ciniki.products']) ) {
        $strsql = "SELECT 'products', COUNT(*) "
            . "FROM ciniki_product_prices "
            . "WHERE pricepoint_id = '" . ciniki_core_dbQuote($ciniki, $args['pricepoint_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['products']) && $rc['num']['products'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.128', 'msg'=>'Products are still using this price point, it cannot be deleted.'));
        }
    }

    //
    // Delete the price point
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.pricepoint', 
        $args['pricepoint_id'], NULL, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return array('stat'=>'ok');
}
?>
