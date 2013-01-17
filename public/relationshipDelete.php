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
// business_id:		The ID of the business to remove the relationship from.
// relationship_id:	The ID of the relationship to be removed.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.relationshipDelete', $args['relationship_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// get the uuid
	//
	$strsql = "SELECT uuid, customer_id FROM ciniki_customer_relationships "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'503', 'msg'=>'Unable to get existing relationship information', 'err'=>$rc['err']));
	}
	if( !isset($rc['relationship']) || !isset($rc['relationship']['customer_id'])) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'504', 'msg'=>'Unable to get existing relationship information'));
	}
	$org_customer_id = $rc['relationship']['customer_id'];
	$uuid = $rc['relationship']['uuid'];

	//
	// Remove the customer email address from the database.  It is still there in 
	// the ciniki_customer_history table.
	//
	$strsql = "DELETE FROM ciniki_customer_relationships "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' ";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'505', 'msg'=>'Unable to remove relationship', 'err'=>$rc['err']));
	}
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
		3, 'ciniki_customer_relationships', $args['relationship_id'], '*', '');

	//
	// Update the customer last_updated date
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTouch');
	$rc = ciniki_core_dbTouch($ciniki, 'ciniki.customers', 'ciniki_customers', 'id', $org_customer_id);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'576', 'msg'=>'Unable to remove relationship', 'err'=>$rc['err']));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'customers');

	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.relationship.push', 
		'args'=>array('delete_id'=>$args['relationship_id'], 'delete_uuid'=>$uuid));

	return array('stat'=>'ok');
}
?>
