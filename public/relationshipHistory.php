<?php
//
// Description
// -----------
// This method will return the history for a field that is part of a relationship.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the history for.
// relationship_id:		The ID of the relationship to get the history for.
// field:				The field to get the history for.
//
//						relationship_type
//						related_id
//						date_started
//						date_ended
//						notes
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_customers_relationshipHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
	$rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.relationshipHistory', $args['relationship_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['field'] == 'date_started'
		|| $args['field'] == 'date_ended' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 'ciniki_customer_relationships', $args['relationship_id'], $args['field'], 'date');
	}

	if( $args['field'] == 'related_id' ) {
		//
		// Check if different customer types have been enabled
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
		$rc = ciniki_customers_getCustomerTypes($ciniki, $args['business_id']); 
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		$types = $rc['types'];

		if( count($types) > 0 ) {
			// If there are customer types defined, choose the right name for the customer
			// This is required here to be able to sort properly
			$strsql .= "CASE ciniki_customers.type ";
			foreach($types as $tid => $type) {
				$strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN ";
				if( $type['detail_value'] == 'business' ) {
					$strsql .= " ciniki_customers.company ";
				} else {
					$strsql .= "CONCAT_WS(' ', first, last) ";
				}
			}
			$strsql .= "ELSE CONCAT_WS(' ', first, last) END ";
		} else {
			// Default to a person
			$strsql .= "CONCAT_WS(' ', first, last) ";
		}
		return ciniki_core_dbGetModuleHistoryFkId($ciniki, 'ciniki.customers', 'ciniki_customer_history', 
			$args['business_id'], 'ciniki_customer_relationships', 'relatedship_id', $args['field'], 
			'ciniki_customers', 'id', $strsql);
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'], 'ciniki_customer_relationships', $args['relationship_id'], $args['field']);
}
?>
