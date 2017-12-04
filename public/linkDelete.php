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
// <rsp stat='ok' id='34' />
//
function ciniki_customers_linkDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Link'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.linkDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the uuid
    //
    $strsql = "SELECT uuid FROM ciniki_customer_links "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['link_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'link');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.100', 'msg'=>'Unable to get existing link information', 'err'=>$rc['err']));
    }
    if( !isset($rc['link']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.101', 'msg'=>'Unable to get existing link information'));
    }
    $uuid = $rc['link']['uuid'];

    //
    // Delete the link
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    return ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.link', $args['link_id'], $uuid, 0x07);
}
?>
