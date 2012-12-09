<?php
//
// Description
// -----------
// This method will merge two customers into one.
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.merge', 0); 
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
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
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
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title',
				'phone_home', 'phone_work', 'phone_cell', 'phone_fax', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
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
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'756', 'msg'=>'Unable to find customer'));
	}
	if( $secondary == NULL ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
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
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'],
					4, 'ciniki_customers', $args['primary_customer_id'], $field, $secondary[$field]);
				// Copy the field history to the primary customer
				$rc = ciniki_core_dbCopyModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'],
					'ciniki_customers', $args['secondary_customer_id'], $args['primary_customer_id'], $field);
				// Record secondary customer update as merge delete action
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'],
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
		$rc = ciniki_core_dbUpdate($ciniki, $strsql_primary, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'758', 'msg'=>'Unable to update customer details', 'err'=>$rc['err']));
		}
		$strsql_secondary .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql_secondary, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
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
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'764', 'msg'=>'Unable to customer emails', 'err'=>$rc['err']));
	}
	$emails = $rc['rows'];
	foreach($emails as $i => $row) {
		$strsql = "UPDATE ciniki_customer_emails "
			. "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
			. ", last_updated = UTC_TIMESTAMP() "
			. "WHERE id = '" . $row['id'] . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'759', 'msg'=>'Unable to update customer emails', 'err'=>$rc['err']));
		}
		if( $rc['num_affected_rows'] == 1 ) {
			// Record update as merge action
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'],
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
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'763', 'msg'=>'Unable to customer addresses', 'err'=>$rc['err']));
	}
	$addresses = $rc['rows'];
	foreach($addresses as $i => $row) {
		$strsql = "UPDATE ciniki_customer_addresses "
			. "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
			. ", last_updated = UTC_TIMESTAMP() "
			. "WHERE id = '" . $row['id'] . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'760', 'msg'=>'Unable to update customer addresses', 'err'=>$rc['err']));
		}
		if( $rc['num_affected_rows'] == 1 ) {
			// Record update as merge action
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'],
				4, 'ciniki_customer_addresses', $row['id'], 'customer_id', $args['primary_customer_id']);
		}
	}

	//
	// Merge Subscriptions
	//
	if( isset($modules['ciniki.subscriptions']) ) {
		$updated = 0;
		$strsql = "SELECT ciniki_subscriptions.id, "
			. "IFNULL(c1.id, 0) AS c1_id, c1.customer_id AS c1_customer_id, c1.status AS c1_status, UNIX_TIMESTAMP(c1.last_updated) AS c1_last_updated, "
			. "IFNULL(c2.id, 0) AS c2_id, c2.customer_id AS c2_customer_id, c2.status AS c2_status, UNIX_TIMESTAMP(c2.last_updated) AS c2_last_updated "
			. "FROM ciniki_subscriptions "
			. "LEFT JOIN ciniki_subscription_customers AS c1 ON (ciniki_subscriptions.id = c1.subscription_id "
				. "AND c1.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' ) "
			. "LEFT JOIN ciniki_subscription_customers AS c2 ON (ciniki_subscriptions.id = c2.subscription_id "
				. "AND c2.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' ) "
			. "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'780', 'msg'=>'Unable to find subscriptions', 'err'=>$rc['err']));
		}
		$subscriptions = $rc['rows'];
		foreach($subscriptions as $i => $row) {
			// If the secondary customer has a subscription
			if( $row['c2_id'] > 0 ) {
				// No subscription for primary
				if( $row['c1_id'] == 0 ) {
					// Move subscription to primary
					$strsql = "UPDATE ciniki_subscription_customers SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
						. "WHERE ciniki_subscription_customers.id = '" . ciniki_core_dbQuote($ciniki, $row['c2_id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'781', 'msg'=>'Unable to update subscriptions', 'err'=>$rc['err']));
					}
					ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'],
						4, 'ciniki_subscription_customers', $row['c2_id'], 'customer_id', $args['customer_id']);
					// subscription history automatically moves with the change in customer_id
					$updated = 1;
				}
				// subscription for primary exists, and secondary is more recent updated, then copy
				elseif( $row['c1_id'] > 0 ) {
					// If the secondary is more recent than the primary, update the primary
					if( $row['c2_last_updated'] > $row['c1_last_updated'] && $row['c2_status'] != $row['c1_status']) {
						$strsql = "UPDATE ciniki_subscription_customers "
							. "SET last_updated = UTC_TIMESTAMP(), status = '" . ciniki_core_dbQuote($ciniki, $row['c2_status']) . "' "
							. "WHERE ciniki_subscription_customers.id = '" . ciniki_core_dbQuote($ciniki, $row['c1_id']) . "' "
							. "";
						$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
						if( $rc['stat'] != 'ok' ) {
							ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
							return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'782', 'msg'=>'Unable to update subscriptions', 'err'=>$rc['err']));
						}
						ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'],
							4, 'ciniki_subscription_customers', $row['c1_id'], 'status', $row['c2_status']);
						
						// Copy subscription history
						$rc = ciniki_core_dbCopyModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'],
							'ciniki_subscription_customers', $row['c2_id'], $row['c1_id'], 'status');

						$updated = 1;
					}	
					// Unsubscribe secondary, so that customer can be deleted
					if( $row['c2_status'] != '60' ) {
						$strsql = "UPDATE ciniki_subscription_customers "
							. "SET status = 60 "
							. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $row['c2_id']) . "' "
							. "";
						$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
						if( $rc['stat'] != 'ok' ) {
							ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
							return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'783', 'msg'=>'Unable to update subscriptions', 'err'=>$rc['err']));
						}

						ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'],
							4, 'ciniki_subscription_customers', $row['c2_id'], 'status', '60');
						$updated = 1;
					}
				}
			}
		}
		
		if( $updated == 1 ) {
			//
			// Update the last_change date in the business modules
			// Ignore the result, as we don't want to stop user updates if this fails.
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
			ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'subscriptions');
		}
	}
	
	//
	// Merge wine orders
	//
	if( isset($modules['ciniki.wineproduction']) ) {
		$updated = 0;
		//
		// Get the list of orders attached to the secondary customer
		//
		$strsql = "SELECT id "
			. "FROM ciniki_wineproductions "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.wineproductions', 'wineproduction');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
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
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.wineproductions');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'762', 'msg'=>'Unable to update wine production orders', 'err'=>$rc['err']));
			}
			if( $rc['num_affected_rows'] == 1 ) {
				// Record update as merge action
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.wineproductions', 'ciniki_wineproduction_history', $args['business_id'],
					4, 'ciniki_wineproductions', $row['id'], 'customer_id', $args['primary_customer_id']);
			}
			$updated = 1;
		}

		if( $updated == 1 ) {
			//
			// Update the last_change date in the business modules
			// Ignore the result, as we don't want to stop user updates if this fails.
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
			ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'wineproduction');
		}
	}

	//
	// Merge service jobs
	//
	if( isset($modules['ciniki.services']) ) {
		$updated = 0;
		//
		// Get the list of service subscriptions for a customer
		//
		$strsql = "SELECT id "
			. "FROM ciniki_service_subscriptions "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'services');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'851', 'msg'=>'Unable to find service subscriptions', 'err'=>$rc['err']));
		}
		$services = $rc['rows'];
		foreach($services as $i => $row) {
			$strsql = "UPDATE ciniki_service_subscriptions "
				. "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND id = '" . $row['id'] . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.services');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'852', 'msg'=>'Unable to update services', 'err'=>$rc['err']));
			}
			if( $rc['num_affected_rows'] == 1 ) {
				// Record update as merge action
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'],
					4, 'ciniki_service_subscriptions', $row['id'], 'customer_id', $args['primary_customer_id']);
			}
			$updated = 1;
		}
		//
		// Get the list of service jobs for a customer
		//
		$strsql = "SELECT id "
			. "FROM ciniki_service_jobs "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.services', 'services');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'853', 'msg'=>'Unable to find service subscriptions', 'err'=>$rc['err']));
		}
		$services = $rc['rows'];
		foreach($services as $i => $row) {
			$strsql = "UPDATE ciniki_service_jobs "
				. "SET customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND id = '" . $row['id'] . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.services');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'854', 'msg'=>'Unable to update services', 'err'=>$rc['err']));
			}
			if( $rc['num_affected_rows'] == 1 ) {
				// Record update as merge action
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.services', 'ciniki_service_history', $args['business_id'],
					4, 'ciniki_service_jobs', $row['id'], 'customer_id', $args['primary_customer_id']);
			}
			$updated = 1;
		}

		if( $updated == 1 ) {
			//
			// Update the last_change date in the business modules
			// Ignore the result, as we don't want to stop user updates if this fails.
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
			ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'services');
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

	return array('stat'=>'ok');
}
?>
