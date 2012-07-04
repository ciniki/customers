<?php
//
// Description
// -----------
// This method will merge two customers into
//
// Returns
// -------
//
function ciniki_customers_merge($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'primary_customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No primary customer specified'),
		'secondary_customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No secondary customer specified'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.get', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// get the active modules for the business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'getActiveModules');
    $rc = ciniki_businesses_getActiveModules($ciniki, $args['business_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	//  
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	// 
	// Check that the customers belong to the business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCopyModuleHistory');
	$strsql = "SELECT id, "
		. "prefix, first, middle, last, suffix, company, department, title, "
		. "phone_home, phone_work, phone_cell, phone_fax, "
		. "notes "
		. "FROM ciniki_customers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND (id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
			. "OR id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "') "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title',
				'phone_home', 'phone_work', 'phone_cell', 'phone_fax', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'765', 'msg'=>'Unable to find customers', 'err'=>$rc['err']));
	}

	$primary = NULL;
	$secondary = NULL;
	foreach($rc['customers'] as $cnum => $customer) {
		if( $customer['customer']['id'] == $args['primary_customer_id'] ) {
			$primary = $customer['customer'];
		}
		if( $customer['customer']['id'] == $args['secondary_customer_id'] ) {
			$secondary = $customer['customer'];
		}
	}

	if( $primary == NULL ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'756', 'msg'=>'Unable to find customer'));
	}
	if( $secondary == NULL ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'757', 'msg'=>'Unable to find customer'));
	}

	//
	// Merge customer details
	//
	$fields = array(
		'prefix',
		'first',
		'middle',
		'last',
		'suffix',
		'company',
		'department',
		'title',
		'phone_home',
		'phone_work',
		'phone_cell',
		'phone_fax',
		'notes',
	);
	$strsql_primary = "UPDATE ciniki_customers SET last_updated = UTC_TIMESTAMP() ";
	//
	// Copy all the field history for the secondary customer to primary
	//
	$strsql_history = "UPDATE ciniki_customer_history SET table_key = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND table_field IN (";
	$strsql_secondary = "UPDATE ciniki_customers SET last_updated = UTC_TIMESTAMP() ";
	$field_count = 0;
	foreach($fields as $field) {
		//
		// Check if the field exists and contains information in the secondary record
		//
		if( isset($secondary[$field]) && $secondary[$field] != '' ) {
			//
			// If the primary record field is empty,
			// copy the information across
			//
			if( !isset($primary[$field]) || $primary[$field] == '' ) {
				// Set the information in the primary customer, and remove from secondary
				$strsql_primary .= ", $field = '" . ciniki_core_dbQuote($ciniki, $secondary[$field]) . "' ";
				$strsql_secondary .= ", $field = '' ";
				// Record update as merge action
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'customers', 'ciniki_customer_history', $args['business_id'],
					4, 'ciniki_customers', $args['primary_customer_id'], $field, $secondary[$field]);
				// Copy the field history to the primary customer
				$rc = ciniki_core_dbCopyModuleHistory($ciniki, 'customers', 'ciniki_customer_history', $args['business_id'],
					'ciniki_customers', $args['secondary_customer_id'], $args['primary_customer_id'], $field);
				// Record secondary customer update as merge delete action
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'customers', 'ciniki_customer_history', $args['business_id'],
					5, 'ciniki_customers', $args['secondary_customer_id'], $field, '');
				$strsql_history .= "'" . ciniki_core_dbQuote($ciniki, $field) . "'";
				$field_count++;
			}
		}
	}
	if( $field_count > 0 ) {
		$strsql_primary .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql_primary, 'customers');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'758', 'msg'=>'Unable to update customer details', 'err'=>$rc['err']));
		}
		$strsql_secondary .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql_secondary, 'customers');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'766', 'msg'=>'Unable to update customer details', 'err'=>$rc['err']));
		}
	}

	//
	// Merge emails
	//
	$strsql = "SELECT id "
		. "FROM ciniki_customer_emails "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'764', 'msg'=>'Unable to customer emails', 'err'=>$rc['err']));
	}
	$emails = $rc['rows'];
	foreach($emails as $i => $row) {
		$strsql = "UPDATE ciniki_customer_emails "
			. "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
			. ", last_updated = UTC_TIMESTAMP() "
			. "WHERE id = '" . $row['id'] . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'customers');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'759', 'msg'=>'Unable to update customer emails', 'err'=>$rc['err']));
		}
		if( $rc['num_affected_rows'] == 1 ) {
			// Record update as merge action
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'customers', 'ciniki_customer_history', $args['business_id'],
				4, 'ciniki_customer_emails', $row['id'], 'customer_id', $args['primary_customer_id']);
		}
	}

	//
	// Merge addresses
	//
	$strsql = "SELECT id "
		. "FROM ciniki_customer_addresses "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'763', 'msg'=>'Unable to customer addresses', 'err'=>$rc['err']));
	}
	$addresses = $rc['rows'];
	foreach($addresses as $i => $row) {
		$strsql = "UPDATE ciniki_customer_addresses "
			. "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
			. ", last_updated = UTC_TIMESTAMP() "
			. "WHERE id = '" . $row['id'] . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'customers');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'760', 'msg'=>'Unable to update customer addresses', 'err'=>$rc['err']));
		}
		if( $rc['num_affected_rows'] == 1 ) {
			// Record update as merge action
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'customers', 'ciniki_customer_history', $args['business_id'],
				4, 'ciniki_customer_addresses', $row['id'], 'customer_id', $args['primary_customer_id']);
		}
	}

	//
	// Merge Subscriptions
	//
	if( isset($modules['ciniki.subscriptions']) ) {
		
	}
	
	//
	// Merge wine orders
	//
	if( isset($modules['ciniki.wineproduction']) ) {
		//
		// Get the list of orders attached to the secondary customer
		//
		$strsql = "SELECT id "
			. "FROM ciniki_wineproductions "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'wineproductions', 'wineproduction');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'761', 'msg'=>'Unable to find wine production orders', 'err'=>$rc['err']));
		}
		$wineproductions = $rc['rows'];
		foreach($wineproductions as $i => $row) {
			$strsql = "UPDATE ciniki_wineproductions "
				. "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND id = '" . $row['id'] . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'wineproductions');
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'762', 'msg'=>'Unable to update wine production orders', 'err'=>$rc['err']));
			}
			if( $rc['num_affected_rows'] == 1 ) {
				// Record update as merge action
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'wineproductions', 'ciniki_wineproduction_history', $args['business_id'],
					4, 'ciniki_wineproductions', $row['id'], 'customer_id', $args['primary_customer_id']);
			}
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
