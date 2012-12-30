<?php
//
// Description
// -----------
// This method will add a customer to local server
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_sync_customerUpdate($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['customer']) || $args['customer'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'273', 'msg'=>'No type specified'));
	}
	$remote_customer = $args['customer'];

	//
	// Check if customer already exists, and if not run the add script
	//
	$strsql = "SELECT id FROM ciniki_customers "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customers.uuid = '" . ciniki_core_dbQuote($ciniki, $remote_customer['uuid']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customer']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerAdd');
		return ciniki_customers_sync_customerAdd($ciniki, $sync, $business_id, $args);
	}

	//
	// Get the local customer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'sync', 'customerGet');
	$rc = ciniki_customers_sync_customerGet($ciniki, $sync, $business_id, array('uuid'=>$remote_customer['uuid']));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customer']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'228', 'msg'=>'Customer not found on remote server'));
	}
	$local_customer = $rc['customer'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Compare basic elements of customer
	//
	$updatable_fields = array(
		'cid'=>array(),
		'type'=>array(),
		'prefix'=>array(),
		'first'=>array(),
		'middle'=>array(),
		'last'=>array(),
		'suffix'=>array(),
		'company'=>array(),
		'department'=>array(),
		'title'=>array(),
		'phone_home'=>array(),
		'phone_work'=>array(),
		'phone_cell'=>array(),
		'phone_fax'=>array(),
		'notes'=>array(),
		'birthdate'=>array(),
		'date_added'=>array('type'=>'uts'),
		'last_updated'=>array('type'=>'uts'),
		);
	$strsql = '';
	$comma = '';
	foreach($updatable_fields as $field => $finfo) {
		//
		// Check if the fields are different, and if so, figure out which one is newer
		//
		if( $remote_customer[$field] != $local_customer[$field] ) {
			//
			// Check the history for each field, and see which side is newer, remote or local
			//
			$remote_uts = 0;
			$local_uts = 0;
			if( isset($remote_customer['history']) ) {
				foreach($remote_customer['history'] as $history_uuid => $history) {
					if( $history['table_field'] == $field && $history['log_date'] > $remote_uts ) {
						$remote_uts = $history['log_date'];
					}
				}
			}
			if( isset($local_customer['history']) ) {
				foreach($local_customer['history'] as $history_uuid => $history) {
					if( $history['table_field'] == $field && $history['log_date'] > $local_uts ) {
						$local_uts = $history['log_date'];
					}
				}
			}
			
			//
			// Check if the field should be updated locally
			//
			if( $remote_uts > $local_uts || ($remote_uts == 0 && $local_uts == 0) ) {
				// Find the first occurance of field in history for both local and remote, compare log_date
				if( isset($finfo['type']) && $finfo['type'] == 'uts' ) {
					$strsql .= $comma . " $field = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_customer[$field]) . "') ";
				} else {
					$strsql .= $comma . " $field = '" . ciniki_core_dbQuote($ciniki, $remote_customer[$field]) . "' ";
				}
				$comma = ',';
			}
		}
	}
	if( $strsql != '' ) {
		$strsql = "UPDATE ciniki_customers SET " . $strsql . " "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Update the customer history
	//
	if( isset($remote_customer['history']) ) {
		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
			'ciniki_customer_history', $local_customer['id'], 'ciniki_customers', $remote_customer['history'], $local_customer['history'], array());
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'229', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
		}
	}

	//
	// Check for any deleted emails
	//
	if( isset($remote_customer['deleted_emails']) ) {
		foreach($remote_customer['deleted_emails'] as $uuid => $history) {
			if( isset($local_customer['emails'][$uuid]) ) {
				$local_email = $local_customer['emails'][$uuid];
				//
				// Delete the email
				//
				$strsql = "DELETE FROM ciniki_customer_emails "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_email['id']) . "' ";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}

				//
				// Update history
				//
				$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
					'ciniki_customer_history', $local_email['id'], 'ciniki_customer_emails', array($history['uuid']=>$history), array(), array(
						'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
	}

	//
	// Compare customer emails
	//
	if( isset($remote_customer['emails']) ) {
		foreach($remote_customer['emails'] as $uuid => $remote_email) {
			//
			// Check if email exists in local
			//
			if( !isset($local_customer['emails'][$uuid]) ) {
				//
				// Don't bother checking the deleted list, we need to know the email_id anyway
				//
				//
				// Find all history for this email if it has existed in the local server
				// Check history for moves or deletions
				//
				$strsql = "SELECT ciniki_customer_history.id AS history_id, "
					. "ciniki_customer_history.uuid AS history_uuid, "
					. "ciniki_users.uuid AS user_uuid, "
					. "ciniki_customer_history.session, "
					. "ciniki_customer_history.action, "
					. "ciniki_customer_history.table_name, "
					. "ciniki_customer_history.table_key, "
					. "ciniki_customer_history.table_field, "
					. "ciniki_customer_history.new_value, "
					. "UNIX_TIMESTAMP(ciniki_customer_history.log_date) AS log_date "
					. "FROM ciniki_customer_history "
					. "LEFT JOIN ciniki_users ON (ciniki_customer_history.user_id = ciniki_users.id) "
					. "WHERE ciniki_customer_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND ciniki_customer_history.table_name = 'ciniki_customer_emails' "
					. "AND ciniki_customer_history.table_key = ("
						. "SELECT table_key FROM ciniki_customer_history "
						. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
						. "AND table_name = 'ciniki_customer_emails' "
						. "AND table_field = 'uuid' "
						. "AND action = 1 "
						. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $uuid) . "' "
						. ")"
					. "ORDER BY log_date DESC "	// Have the newest changes at the top
					. "";
				$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.customers', 'history', 'history_uuid');
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'261', 'msg'=>'Unable to check for customer email history', 'err'=>$rc['err']));
				}
				$delete_uts = 0;
				$move_uts = 0;
				if( isset($rc['history']) ) {
					$local_history = $rc['history'];
					foreach($local_history as $uuid => $history) {
						if( $history['action'] == 3 ) {
							$delete_uts = $history['log_date'];
							$email_id = $history['table_key'];
							break;
						}
						if( $history['action'] == 4 ) {	 // Check if UUID was moved to another customer
							$move_uts = $history['log_date'];
							$email_id = $history['table_key'];
							break;
						}
					}
				}

				//
				// Create the email record, if it hasn't previously been deleted or moved
				//
				if( $delete_uts == 0 && $move_uts == 0 ) {
					$strsql = "INSERT INTO ciniki_customer_emails (uuid, business_id, customer_id, email, password, "
						. "temp_password, temp_password_date, flags, "
						. "date_added, last_updated) VALUES ("
						. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_email['email']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_email['password']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_email['temp_password']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_email['temp_password_date']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_email['flags']) . "', "
						. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_email['date_added']) . "'), "
						. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_email['last_updated']) . "') "
						. ") "
						. "";
					$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
					if( $rc['stat'] != 'ok' ) { 
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return $rc;
					}
					if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'265', 'msg'=>'Unable to add customer'));
					}
					$email_id = $rc['insert_id'];
				} 
				//
				// If the record was previsouly and the records has been updated since on the remote,
				// it should be restored to the last know condition on this server, before updates are applied
				//
				elseif( $delete_uts > 0 && $delete_uts < $remote_email['last_updated'] ) {
					// FIXME: Add restore call
//					$rc = ciniki_customers_emailRestore($ciniki, $email_id);
				}
				
				if( isset($remote_email['history']) ) {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
						'ciniki_customer_history', $email_id, 'ciniki_customer_emails', $remote_email['history'], array(), array(
							'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
						));
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'262', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
					}
				}
			} else {
				// Update
				$local_email = $local_customer['emails'][$uuid];
				//
				// Compare basic elements of customer
				//
				$updatable_fields = array(
					'email'=>array(),
					'password'=>array(),
					'temp_password'=>array(),
					'temp_password_date'=>array(),
					'flags'=>array(),
					'date_added'=>array('type'=>'uts'),
					'last_updated'=>array('type'=>'uts'),
					);
				$strsql = '';
				$comma = '';
				foreach($updatable_fields as $field => $finfo) {
					//
					// Check if the fields are different, and if so, figure out which one is newer
					//
					if( $remote_email[$field] != $local_email[$field] ) {
						//
						// Check the history for each field, and see which side is newer, remote or local
						//
						$remote_uts = 0;
						$local_uts = 0;
						if( isset($remote_email['history']) ) {
							foreach($remote_email['history'] as $history_uuid => $history) {
								if( $history['table_field'] == $field && $history['log_date'] > $remote_uts ) {
									$remote_uts = $history['log_date'];
								}
							}
						}
						if( isset($local_email['history']) ) {
							foreach($local_email['history'] as $history_uuid => $history) {
								if( $history['table_field'] == $field && $history['log_date'] > $local_uts ) {
									$local_uts = $history['log_date'];
								}
							}
						}
						
						//
						// Check if the field should be updated locally
						//
						if( $remote_uts > $local_uts ) {
							// Find the first occurance of field in history for both local and remote, compare log_date
							if( isset($finfo['type']) && $finfo['type'] == 'uts' ) {
								$strsql .= $comma . " $field = FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_email[$field]) . "') ";
							} else {
								$strsql .= $comma . " $field = '" . ciniki_core_dbQuote($ciniki, $remote_email[$field]) . "' ";
							}
							$comma = ',';
						}
					}
				}
				if( $strsql != '' ) {
					$strsql = "UPDATE ciniki_customer_emails SET " . $strsql . " "
						. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_email['id']) . "' "
						. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
						. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}

				//
				// Update the email history
				//
				if( isset($remote_email['history']) ) {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
						'ciniki_customer_history', $local_email['id'], 'ciniki_customer_emails', $remote_email['history'], $local_email['history'], array());
					if( $rc['stat'] != 'ok' ) {
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'229', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
					}
				}
			}
		}
	}
	// FIXME: Need to cycle through local emails, to see if they need updating on remote


	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
