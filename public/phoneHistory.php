<?php
//
// Description
// -----------
// This method will return the history of a customers phone address field.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the phone history for.
// customer_id:         The ID of the customer to get the phone history for.
// phone_id:            The ID of the phone address to get this history for.
// field:               The field to get the history of.
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
function ciniki_customers_phoneHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'phone_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Phone'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    if( $args['field'] == 'address' ) { $args['field'] = 'phone'; }
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.phoneHistory', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
        $args['tnid'], 'ciniki_customer_phones', $args['phone_id'], $args['field']);
}
?>
