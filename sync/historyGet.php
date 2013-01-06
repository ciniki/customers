<?php
//
// Description
// -----------
// This method will return a history entry for a table in the customers module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_sync_historyGet($ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['history']) || $args['history'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'282', 'msg'=>'No history specified'));
	}

	//
	// Prepare the query to fetch the list
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Get the history information
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
		. "AND ciniki_customer_history.uuid = '" . ciniki_core_dbQuote($ciniki, $args['history']) . "' "
		. "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'history', 'fname'=>'history_uuid', 
			'fields'=>array('uuid'=>'history_uuid', 'user'=>'user_uuid', 'session', 
				'action', 'table_name', 'table_key', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['history'][$args['history']]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'180', 'msg'=>'History does not exist'));
	}
	$history = $rc['history'][$args['history']];

	//
	// Lookup the table_key uuid
	//
	if( $history['table_key'] != '' ) {
		if( $history['table_name'] == 'ciniki_customers' ) {
			$strsql = "SELECT uuid FROM ciniki_customers "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
				. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['customer']) ) {
				$history['table_key'] = $rc['customer']['uuid'];		
			} else {
				$strsql = "SELECT new_value FROM ciniki_customer_history "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND action = 1 "
					. "AND table_name = 'ciniki_customers' "
					. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
					. "AND table_field = 'uuid' "
					. "";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['customer']) ) {
					$history['table_key'] = $rc['customer']['new_value'];
				} else {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'181', 'msg'=>'History element is broken'));
				}
			}
		}
		elseif( $history['table_name'] == 'ciniki_customer_emails' ) {
			$strsql = "SELECT ciniki_customer_emails.uuid "
				. "FROM ciniki_customer_emails, ciniki_customers "
				. "WHERE ciniki_customer_emails.id = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
				. "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['email']) ) {
				$history['table_key'] = $rc['email']['uuid'];
			} else {
				$strsql = "SELECT new_value FROM ciniki_customer_history "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND action = 1 "
					. "AND table_name = 'ciniki_customer_emails' "
					. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
					. "AND table_field = 'uuid' "
					. "";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['email']) ) {
					$history['table_key'] = $rc['email']['new_value'];
				} else {
					// This is a rogue history element, and will be logged but shouldn't stop anything
					error_log("ERR: Bad History on ciniki_customer_emails (" . $history['table_key'] . ")");
					$history['table_key'] = '';
//					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'178', 'msg'=>'History element is broken' . $history['table_key']));
				}
			}
		}
		elseif( $history['table_name'] == 'ciniki_customer_addresses' ) {
			$strsql = "SELECT ciniki_customer_addresses.uuid "
				. "FROM ciniki_customer_addresses, ciniki_customers "
				. "WHERE ciniki_customer_addresses.id = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
				. "AND ciniki_customer_addresses.customer_id = ciniki_customers.id "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['address']) ) {
				$history['table_key'] = $rc['address']['uuid'];
			} else {
				$strsql = "SELECT new_value FROM ciniki_customer_history "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND action = 1 "
					. "AND table_name = 'ciniki_customer_addresses' "
					. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
					. "AND table_field = 'uuid' "
					. "";
				$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['address']) ) {
					$history['table_key'] = $rc['address']['new_value'];
				} else {
					// Address does not exist, and doesn't have any "add (action=1)" logs
					error_log("ERR: Bad History on ciniki_customer_emails (" . $history['table_key'] . ")");
					$history['table_key'] = '';
//					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'179', 'msg'=>'History element is broken'));
				}
			}
		}
	}

	//
	// if the history references the customer_id field, then lookup customer_id
	//
	if( $history['table_field'] == 'customer_id' ) {
		$strsql = "SELECT uuid FROM ciniki_customers "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $history['new_value']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['customer']) ) {
			$history['new_value'] = $rc['customer']['uuid'];		
		} else {
			$strsql = "SELECT new_value FROM ciniki_customer_history "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND table_name = 'ciniki_customers' "
				. "AND table_key = '" . ciniki_core_dbQuote($ciniki, $history['new_value']) . "' "
				. "AND table_field = 'uuid' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['customer']) ) {
				$history['new_value'] = $rc['customer']['new_value'];
			} else {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'182', 'msg'=>'History element is broken'));
			}
		}
	}

	return array('stat'=>'ok', 'history'=>$history);
}
?>
