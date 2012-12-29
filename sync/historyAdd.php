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
function ciniki_customers_sync_historyAdd($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['history']) || $args['history'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'177', 'msg'=>'No type specified'));
	}
	$history = $args['history'];

	print_r($history);

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Lookup the table_key 
	//
	if( $history['table_name'] == 'ciniki_customers' ) {
		$strsql = "SELECT id FROM ciniki_customers "
			. "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
		if( isset($rc['customer']) ) {
			$history['table_key'] = $rc['customer']['id'];
		} else {
			$strsql = "SELECT table_key FROM ciniki_customer_history "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND action = 1 "
				. "AND table_name = 'ciniki_customers' "
				. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
				. "AND table_field = 'uuid' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return $rc;
			}
			if( isset($rc['customer']) ) {
				$history['table_key'] = $rc['customer']['table_key'];
			} else {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'171', 'msg'=>'History element is broken'));
			}
		}
	}
	elseif( $history['table_name'] == 'ciniki_customer_emails' ) {
		$strsql = "SELECT id FROM ciniki_customer_emails "
			. "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
		if( isset($rc['email']) ) {
			$history['table_key'] = $rc['email']['id'];
		} else {
			$strsql = "SELECT table_key FROM ciniki_customer_history "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND action = 1 "
				. "AND table_name = 'ciniki_customer_emails' "
				. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
				. "AND table_field = 'uuid' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return $rc;
			}
			if( isset($rc['email']) ) {
				$history['table_key'] = $rc['email']['table_key'];
			} else {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'173', 'msg'=>'History element is broken'));
			}
		}
	}
	elseif( $history['table_name'] == 'ciniki_customer_addresses' ) {
		$strsql = "SELECT ciniki_customer_addresses.id FROM ciniki_customer_addresses, ciniki_customers "
			. "WHERE ciniki_customer_addresses.uuid = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
			. "AND ciniki_customer_addresses.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
		if( isset($rc['address']) ) {
			$history['table_key'] = $rc['address']['id'];
		} else {
			$strsql = "SELECT table_key FROM ciniki_customer_history "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND action = 1 "
				. "AND table_name = 'ciniki_customer_addresses' "
				. "AND new_value = '" . ciniki_core_dbQuote($ciniki, $history['table_key']) . "' "
				. "AND table_field = 'uuid' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return $rc;
			}
			if( isset($rc['address']) ) {
				$history['table_key'] = $rc['address']['table_key'];
			} else {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'172', 'msg'=>'History element is broken'));
			}
		}
	}

	//
	// Add the history to the ciniki_customer_history table
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.customers',
		'ciniki_customer_history', $history['table_key'], $history['table_name'], array($history['uuid']=>$history), array(), array(
			'customer_id'=>array('module'=>'ciniki.customers', 'table'=>'ciniki_customers'),
		));
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'176', 'msg'=>'Unable to add history', 'err'=>$rc['err']));
	}

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
