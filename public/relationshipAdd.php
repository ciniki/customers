<?php
//
// Description
// -----------
// This method will add a new relationship between customers to the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the customer belongs to.
// customer_id:			The ID of the customer to add the relationship to.
// relationship_type:	The type of relationship between the customer_id and
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
// related_id: 			The ID of the related customer.
//
// date_started:		(optional) The date the relationship started.
// date_ended:			(optional) The date the relationship ended.
// notes:				(optional) Any notes about the relationship.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_relationshipAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'relationship_type'=>array('required'=>'yes', 'blank'=>'no', 
			'validlist'=>array('10','-10','11','30', '40', '41', '-41', '42', '-42', '43', '-43', '44', '45', '46', '47'), 
			'name'=>'Relationship Type'), 
        'related_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Related Customer'), 
        'date_started'=>array('required'=>'no', 'type'=>'date', 'default'=>'', 'blank'=>'yes', 'name'=>'Date Started'), 
        'date_ended'=>array('required'=>'no', 'type'=>'date', 'default'=>'', 'blank'=>'yes', 'name'=>'Date Ended'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.relationshipAdd', $args['customer_id']); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Check if relationship should be reversed
	//
	if( $args['relationship_type'] < 0 ) {
		$args['relationship_type'] = abs($args['relationship_type']);
		$id = $args['customer_id'];
		$args['customer_id'] = $args['related_id'];
		$args['related_id'] = $id;
	}

	//
	// Get a new UUID
	//
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$uuid = $rc['uuid'];

	//
	// Add the customer relationship to the database
	//
	$strsql = "INSERT INTO ciniki_customer_relationships (uuid, business_id, customer_id, "
		. "relationship_type, related_id, "
		. "date_started, date_ended, notes, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['relationship_type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['related_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_started']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['date_ended']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['notes']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		if( $rc['err']['code'] == '73' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'530', 'msg'=>'Email address already exists'));
		}
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'531', 'msg'=>'Unable to add customer email'));
	}
	$relationship_id = $rc['insert_id'];

	//
	// Save the uuid history
	//
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
		1, 'ciniki_customer_relationships', $relationship_id, 'uuid', $uuid);

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
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 
				1, 'ciniki_customer_relationships', $relationship_id, $field, $args[$field]);
		}
	}

	//
	// Update the customer last_updated date
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTouch');
	$rc = ciniki_core_dbTouch($ciniki, 'ciniki.customers', 'ciniki_customers', 'id', $args['customer_id']);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'532', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
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

	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.relationship.push', 'args'=>array('id'=>$relationship_id));

	return array('stat'=>'ok', 'id'=>$relationship_id);
}
?>
