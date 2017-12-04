<?php
//
// Description
// -----------
// This method will remove a relationship from the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to remove the relationship from.
// relationship_id: The ID of the relationship to be removed.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_relationshipDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.relationshipDelete', $args['relationship_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // get the uuid
    //
    $strsql = "SELECT uuid FROM ciniki_customer_relationships "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.131', 'msg'=>'Unable to get existing relationship information', 'err'=>$rc['err']));
    }
    if( !isset($rc['relationship']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.132', 'msg'=>'Unable to get existing relationship information'));
    }
    $uuid = $rc['relationship']['uuid'];

    //
    // Delete the relationship
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    return ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.relationship', $args['relationship_id'], $uuid, 0x07);
}
?>
