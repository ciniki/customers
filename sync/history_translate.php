<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_history_translate($ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['history']) || $args['history'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'177', 'msg'=>'No history specified'));
	}
	$history = $args['history'];

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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'938', 'msg'=>'Unable to get customer id', 'err'=>$rc['err']));
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
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'937', 'msg'=>'Unable to get customer id from history', 'err'=>$rc['err']));
			}
			if( isset($rc['customer']) ) {
				$history['table_key'] = $rc['customer']['table_key'];
			} else {
				// 
				// FIXME: Add code to add customer email if it doesn't exist
				//
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'939', 'msg'=>'Unable to get customer email id', 'err'=>$rc['err']));
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
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'940', 'msg'=>'Unable to get customer email id from history', 'err'=>$rc['err']));
			}
			if( isset($rc['email']) ) {
				$history['table_key'] = $rc['email']['table_key'];
			} else {
				//
				// The customer email has never existed in this server, add all the history for a blank table key
				//
				$history['table_key'] = '';
				// 
				// FIXME: Add code to add customer email if it doesn't exist
				//
//				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
//				error_log("history broken for: ciniki_customer_emails: " . $history['table_key']);
//				error_log(print_r($history, true));
//				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'173', 'msg'=>'History element is broken'));
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'941', 'msg'=>'Unable to get customer address id', 'err'=>$rc['err']));
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
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'942', 'msg'=>'Unable to get customer address id from history', 'err'=>$rc['err']));
			}
			if( isset($rc['address']) ) {
				$history['table_key'] = $rc['address']['table_key'];
			} else {
				$history['table_key'] = '';
				//
				// FIXME: Add code to add customer address if doesn't exist
				//
				// $rc = ciniki_customers_customer_update($ciniki, $sync, $business_id, array('uuid'=>$history['table_key']));
//				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
//				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'172', 'msg'=>'History element is broken'));
			}
		}
	}

	return array('stat'=>'ok', 'history'=>$history);
}
?>
