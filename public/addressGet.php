<?php
//
// Description
// -----------
// This function will return a customer record
//
// Info
// ----
// Status:          started
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_customers_addressGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'address_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Address'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.addressGet', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    $strsql = "SELECT id, customer_id, "
        . "address1, address2, city, province, postal, country, flags, "
        . "latitude, longitude, "
        . "phone, notes "
//      . "ELT(((flags&0x01))+1,'Off','On') AS shipping, "
//      . "ELT(((flags&0x02)>>1)+1,'Off','On') AS billing, "
//      . "ELT(((flags&0x04)>>2)+1,'Off','On') AS mailing "
        . "FROM ciniki_customer_addresses "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['address_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['address']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.55', 'msg'=>'Invalid customer'));
    }
    return array('stat'=>'ok', 'address'=>$rc['address']);
}
?>
