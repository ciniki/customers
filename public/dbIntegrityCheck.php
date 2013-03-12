<?php
//
// Description
// -----------
// This function will go through the history of the ciniki.customers module and add missing history elements.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_dbIntegrityCheck($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'fix'=>array('required'=>'no', 'default'=>'no', 'name'=>'Fix Problems'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
	$rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.dbIntegrityCheck', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

	if( $args['fix'] == 'yes' ) {
		//
		// Update the history for ciniki_customers
		//
		$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.customers', $args['business_id'],
			'ciniki_customers', 'ciniki_customer_history', 
			array('uuid', 'cid', 'status', 'type', 'prefix', 'first', 'middle', 'last', 'suffix',
				'company', 'department', 'title', 'phone_home', 'phone_work', 'phone_cell', 'phone_fax', 
				'notes', 'birthdate'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Update the history for ciniki_customer_addresses
		//
		$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.customers', $args['business_id'],
			'ciniki_customer_addresses', 'ciniki_customer_history', 
			array('uuid', 'customer_id','flags','address1', 'address2', 
				'city', 'province', 'postal', 'country'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Update the history for ciniki_customer_emails
		//
		$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.customers', $args['business_id'],
			'ciniki_customer_emails', 'ciniki_customer_history', 
			array('uuid', 'customer_id','email','password', 'temp_password_date', 'flags'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Update the history for ciniki_customer_relationships
		//
		$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.customers', $args['business_id'],
			'ciniki_customer_relationships', 'ciniki_customer_history', 
			array('uuid', 'customer_id','relationship_type','related_id'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Check for items missing a UUID
		//
		$strsql = "UPDATE ciniki_customer_history SET uuid = UUID() WHERE uuid = ''";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Remote any entries with blank table_key, they are useless we don't know what they were attached to
		//
		$strsql = "DELETE FROM ciniki_customer_history WHERE table_key = ''";
		$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	return array('stat'=>'ok');
}
?>
