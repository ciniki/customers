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
function ciniki_customers_sync_customerUpdate(&$ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['customer']) || $args['customer'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'273', 'msg'=>'No type specified'));
	}
	$remote_customer = $args['customer'];

	// FIXME: Check if the customer was deleted locally before adding

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
		$rc = ciniki_customers_sync_customerAdd($ciniki, $sync, $business_id, $args);
		return $rc;
	}
	
	$db_updated = 0;
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
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
	$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_customer, $local_customer, array(
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
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
		$strsql = "UPDATE ciniki_customers SET " . $rc['strsql'] . " "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
		$db_updated = 1;
	}

	//
	// Update the customer history
	//
	if( isset($remote_customer['history']) ) {
		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
			'ciniki_customer_history', $local_customer['id'], 'ciniki_customers', $remote_customer['history'], $local_customer['history'], array());
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
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
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_email['id']) . "' "
					. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' ";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
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
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
					return $rc;
				}

				$db_updated = 1;
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
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
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
					// FIXME: Put in a check if a duplicate record returned
					if( $rc['stat'] != 'ok' ) { 
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return $rc;
					}
					if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'265', 'msg'=>'Unable to add customer'));
					}
					$email_id = $rc['insert_id'];
					$db_updated = 1;
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
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'262', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
					}
				}
			} else {
				// Update
				$local_email = $local_customer['emails'][$uuid];
				//
				// Compare basic elements of customer email
				//
				$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_email, $local_email, array(
					'email'=>array(),
					'password'=>array(),
					'temp_password'=>array(),
					'temp_password_date'=>array(),
					'flags'=>array(),
					'date_added'=>array('type'=>'uts'),
					'last_updated'=>array('type'=>'uts'),
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
					$strsql = "UPDATE ciniki_customer_emails SET " . $rc['strsql'] . " "
						. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_email['id']) . "' "
						. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
						. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return $rc;
					}
					$db_updated = 1;
				}

				//
				// Update the email history
				//
				if( isset($remote_email['history']) ) {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
						'ciniki_customer_history', $local_email['id'], 'ciniki_customer_emails', $remote_email['history'], $local_email['history'], array());
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'129', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
					}
				}
			}
		}
	}

	//
	// Check for deleted addresses
	//
	if( isset($remote_customer['deleted_addresses']) ) {
		foreach($remote_customer['deleted_addresses'] as $uuid => $history) {
			if( isset($local_customer['addresses'][$uuid]) ) {
				$local_address = $local_customer['addresses'][$uuid];
				//
				// Delete the address
				//
				$strsql = "DELETE FROM ciniki_customer_addresses "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_address['id']) . "' "
					. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' ";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
					return $rc;
				}

				//
				// Update history
				//
				$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
					'ciniki_customer_history', $local_address['id'], 'ciniki_customer_addresses', array($history['uuid']=>$history), array(), array(
						'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
					));
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
					return $rc;
				}
				$db_updated = 1;
			}
		}
	}

	//
	// Compare addresses
	//
	if( isset($remote_customer['addresses']) ) {
		foreach($remote_customer['addresses'] as $uuid => $remote_address) {
			//
			// Check if address exists in local
			//
			if( !isset($local_customer['addresses'][$uuid]) ) {
				//
				// Don't bother checking the deleted list, we need to know the address_id anyway
				//
				//
				// Find all history for this address if it has existed in the local server
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
					. "AND ciniki_customer_history.table_name = 'ciniki_customer_addresses' "
					. "AND ciniki_customer_history.table_key = ("
						. "SELECT table_key FROM ciniki_customer_history "
						. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
						. "AND table_name = 'ciniki_customer_addresses' "
						. "AND table_field = 'uuid' "
						. "AND action = 1 "
						. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $uuid) . "' "
						. ")"
					. "ORDER BY log_date DESC "	// Have the newest changes at the top
					. "";
				$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.customers', 'history', 'history_uuid');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'128', 'msg'=>'Unable to check for customer address history', 'err'=>$rc['err']));
				}
				$delete_uts = 0;
				$move_uts = 0;
				if( isset($rc['history']) ) {
					$local_history = $rc['history'];
					foreach($local_history as $uuid => $history) {
						if( $history['action'] == 3 ) {
							$delete_uts = $history['log_date'];
							$address_id = $history['table_key'];
							break;
						}
						if( $history['action'] == 4 ) {	 // Check if UUID was moved to another customer
							$move_uts = $history['log_date'];
							$address_id = $history['table_key'];
							break;
						}
					}
				}

				//
				// Create the address record, if it hasn't previously been deleted or moved
				//
				if( $delete_uts == 0 && $move_uts == 0 ) {
					$strsql = "INSERT INTO ciniki_customer_addresses (uuid, customer_id, flags, address1, address2, "
						. "city, province, postal, country, notes, "
						. "date_added, last_updated) VALUES ("
						. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_address['flags']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_address['address1']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_address['address2']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_address['city']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_address['province']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_address['postal']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_address['country']) . "', "
						. "'" . ciniki_core_dbQuote($ciniki, $remote_address['notes']) . "', "
						. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_address['date_added']) . "'), "
						. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_address['last_updated']) . "') "
						. ") "
						. "";
					$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
					if( $rc['stat'] != 'ok' ) { 
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return $rc;
					}
					if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'124', 'msg'=>'Unable to add customer'));
					}
					$address_id = $rc['insert_id'];
					$db_updated = 1;
				} 
				//
				// If the record was previsouly and the records has been updated since on the remote,
				// it should be restored to the last know condition on this server, before updates are applied
				//
				elseif( $delete_uts > 0 && $delete_uts < $remote_address['last_updated'] ) {
					// FIXME: Add restore call
//					$rc = ciniki_customers_emailRestore($ciniki, $email_id);
				}
				
				if( isset($remote_address['history']) ) {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
						'ciniki_customer_history', $address_id, 'ciniki_customer_addresses', $remote_address['history'], array(), array(
							'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
						));
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'116', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
					}
				}
			} else {
				// Update
				$local_address = $local_customer['addresses'][$uuid];
				//
				// Compare basic elements of customer
				//
				$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_address, $local_address, array(
					'flags'=>array(),
					'address1'=>array(),
					'address2'=>array(),
					'city'=>array(),
					'province'=>array(),
					'postal'=>array(),
					'country'=>array(),
					'notes'=>array(),
					'date_added'=>array('type'=>'uts'),
					'last_updated'=>array('type'=>'uts'),
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
					$strsql = "UPDATE ciniki_customer_addresses SET " . $rc['strsql'] . " "
						. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_address['id']) . "' "
						. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $local_customer['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return $rc;
					}
					$db_updated = 1;
				}

				//
				// Update the address history
				//
				if( isset($remote_address['history']) ) {
					if( isset($local_address['history']) ) {
						$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
							'ciniki_customer_history', $local_address['id'], 'ciniki_customer_addresses', $remote_address['history'], $local_address['history'], array());
					} else {
						$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
							'ciniki_customer_history', $local_address['id'], 'ciniki_customer_addresses', $remote_address['history'], array(), array());
					}
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
						return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'115', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
					}
				}
			}
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
	// Add to syncQueue to sync with other servers.  This allows for cascading syncs.
	//
	if( $db_updated > 0 ) {
		$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.syncPushCustomer', 'args'=>array('id'=>$local_customer['id'], 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok');
}
?>
