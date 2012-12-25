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
function ciniki_customers_relationshipUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
        'relationship_type'=>array('required'=>'no', 'blank'=>'no', 
			'validlist'=>array('10','-10','11','30', '40', '41', '-41', '42', '-42', '43', '44', '45'), 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Check if customer_id and related_id were passed
	//
	$strsql = "SELECT customer_id, related_id FROM ciniki_customer_relationships "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'498', 'msg'=>'Unable to get existing relationship information', 'err'=>$rc['err']));
	}
	if( !isset($rc['customer']) || !isset($rc['related_id'])) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'500', 'msg'=>'Unable to get existing relationship information'));
	}
	$related_id = $rc['related_id'];
	$customer_id = $rc['customer_id'];

	//
	// Check if relationship should be reversed
	//
	if( isset($args['relationship_type']) ) {
		if( $args['relationship_type'] < 0 ) {
			$args['relationship_type'] = abs($args['relationship_type']);
			//
			// Need to flip the customer and related ids
			//
			if( !isset($args['customer_id']) && !isset($args['related_id']) ) {
				$args['customer_id'] = $related_id;
				$args['related_id'] = $customer_id;
			} elseif( isset($args['customer_id']) ) {
				$args['related_id'] = $args['customer_id'];
				$args['customer_id'] = $related_id; 	// Original related id becomes customer id
			} elseif( isset($args['related_id']) ) {
				$args['customer_id'] = $args['related_id'];
				$args['related_id'] = $customer_id;		// Original customer id becomes related id
			}
		}
	}

	//
	// Update the relationship information
	//
	$strsql = "UPDATE ciniki_customer_relationships SET last_updated = UTC_TIMESTAMP()";
	
	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'customer_id',
		'relationship_type',
		'related_id',
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
	if( !isset($args['customer_id']) ) {
		$args['customer_id'] = $customer_id;
	}
	$rc = ciniki_core_dbTouch($ciniki, 'ciniki.customers', 'ciniki_customers', 'id', $args['customer_id']);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'534', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
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

	return array('stat'=>'ok');
}
?>
