<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// key:                 The detail key to get the history for.
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_customers_addressHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'address_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Address'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.addressHistory', $args['customer_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check the address ID belongs to the requested customer
    //
    $strsql = "SELECT ciniki_customer_addresses.id, customer_id "
        . "FROM ciniki_customers, ciniki_customer_addresses "
        . "WHERE ciniki_customer_addresses.id = '" . ciniki_core_dbQuote($ciniki, $args['address_id']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND customer_id = ciniki_customers.id "
        . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( !isset($rc['address']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.56', 'msg'=>'Access denied'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'], 'ciniki_customer_addresses', $args['address_id'], $args['field']);
}
?>
