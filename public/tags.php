<?php
//
// Description
// ===========
// This method will return the existing categories and tags for customers categories and tags.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the item from.
// 
// Returns
// -------
//
function ciniki_customers_tags($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'types'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Types'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.tags', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Get the list of member categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsByType');
    $rc = ciniki_core_tagsByType($ciniki, 'ciniki.customers', $args['tnid'], 'ciniki_customer_tags', $args['types']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['types']) ) {
        $types = $rc['types'];
    } else {
        $types = array();
    }

    return array('stat'=>'ok', 'tag_types'=>$types);
}
?>
