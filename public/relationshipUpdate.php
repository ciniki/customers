<?php
//
// Description
// -----------
// This method will update an existing customer relationship.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the customer belongs to.
// customer_id:			(optional) The ID of the customer that is related to related_id. 
// relationship_id:		The ID of the relationship to change the details for.
// relationship_type:	(optional) The type of relationship between the customer_id and
//						the related_id.  
//
//						If the type is passed as a negative number, the 
//						relationship is reversed before storing in the database.
//
//						10 - business owner (related_id is the business owned)
//						-10 - owned by
//						11 - business partner
//						30 - friend
//						40 - relative
//						41 - parent
//						-41 - child
//						42 - step-parent
//						-42 - step-child
//						43 - parent-in-law
//						-43 - child-in-law
//						44 - spouse
//						45 - sibling
//						46 - step-sibling
//						47 - sibling-in-law
//
// related_id: 			(optional) The ID of the related customer.
//
// date_started:		(optional) The date the relationship started.
// date_ended:			(optional) The date the relationship ended.
// notes:				(optional) Any notes about the relationship.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_relationshipUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
        'relationship_type'=>array('required'=>'no', 'blank'=>'no', 
			'validlist'=>array('10','-10','11','30', '40', '41', '-41', '42', '-42', '43', '-43', '44', '45', '46', '47'), 
			'name'=>'Relationship Type'), 
        'related_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Related Customer'), 
        'date_started'=>array('required'=>'no', 'type'=>'date', 'blank'=>'yes', 'name'=>'Date Started'), 
        'date_ended'=>array('required'=>'no', 'type'=>'date', 'blank'=>'yes', 'name'=>'Date Ended'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.relationshipUpdate', $args['customer_id']); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Get the relationship types
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getRelationshipTypes');
    $rc = ciniki_customers_getRelationshipTypes($ciniki, $args['business_id']); 
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$relationship_types = $rc['types'];

	//
	// Check if customer_id and related_id were passed
	//
	$strsql = "SELECT customer_id, relationship_type, related_id FROM ciniki_customer_relationships "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'relationship');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'498', 'msg'=>'Unable to get existing relationship information', 'err'=>$rc['err']));
	}
	if( !isset($rc['relationship']) || !isset($rc['relationship']['related_id'])) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'500', 'msg'=>'Unable to get existing relationship information'));
	}
	$org_related_id = $rc['relationship']['related_id'];
	$org_relationship_type = $rc['relationship']['relationship_type'];
	$org_customer_id = $rc['relationship']['customer_id'];

	//
	// Check if the original was reversed for the specified customer
	//
	if( $args['customer_id'] == $org_related_id ) {
		//
		// This is how the relationship was returned throught the API via relationshipGet
		//
		$sent_customer_id = $org_related_id;
		$sent_related_id = $org_customer_id;
		// Check if negative relationship_type exists
		if( isset($relationship_types[-$org_relationship_type]) ) {
			$sent_relationship_type = -$org_relationship_type;
		} else {
			$sent_relationship_type = $org_relationship_type;
		}
	} else {
		$sent_customer_id = $org_customer_id;
		$sent_relationship_type = $org_relationship_type;
		$sent_related_id = $org_related_id;
	}

	if( !isset($args['related_id']) ) {
		$args['related_id'] = $sent_related_id;
	}
	if( !isset($args['relationship_type']) ) {
		$args['relationship_type'] = $sent_relationship_type;
	}

	//
	// Check if relationship should be flipped
	//
	if( isset($args['relationship_type']) && $args['relationship_type'] < 0 ) {
		$args['relationship_type'] = -$args['relationship_type'];
		$id = $args['customer_id'];
		$args['customer_id'] = $args['related_id'];
		$args['related_id'] = $id;
	}

	//
	// Update the relationship information
	//
	$strsql = "UPDATE ciniki_customer_relationships SET last_updated = UTC_TIMESTAMP()";
	
	if( isset($args['customer_id']) && $args['customer_id'] != $org_customer_id ) {
		$strsql .= ", customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
			2, 'ciniki_customer_relationships', $args['relationship_id'], 'customer_id', $args['customer_id']);
	}
	if( isset($args['relationship_type']) && $args['relationship_type'] != $sent_relationship_type ) {
		$strsql .= ", relationship_type = '" . ciniki_core_dbQuote($ciniki, $args['relationship_type']) . "' ";
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
			2, 'ciniki_customer_relationships', $args['relationship_id'], 'relationship_type', $args['relationship_type']);
	}
	if( isset($args['related_id']) && $args['related_id'] != $org_related_id ) {
		$strsql .= ", related_id = '" . ciniki_core_dbQuote($ciniki, $args['related_id']) . "' ";
		$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
			2, 'ciniki_customer_relationships', $args['relationship_id'], 'related_id', $args['related_id']);
	}

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'date_started',
		'date_ended',
		'notes',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
				2, 'ciniki_customer_relationships', $args['relationship_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'533', 'msg'=>'Unable to add customer'));
	}

	//
	// Update the customer last_updated date
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTouch');
	$rc = ciniki_core_dbTouch($ciniki, 'ciniki.customers', 'ciniki_customers', 'id', $org_customer_id);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'534', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
	}
	
	if( $org_customer_id != $args['customer_id'] ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTouch');
		$rc = ciniki_core_dbTouch($ciniki, 'ciniki.customers', 'ciniki_customers', 'id', $args['customer_id']);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'502', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
		}
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

	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.syncPushCustomer', 'args'=>array('id'=>$args['customer_id']));

	return array('stat'=>'ok');
}
?>
