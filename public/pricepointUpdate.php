<?php
//
// Description
// ===========
// This method will update an existing tax for a business.  The tax amounts 
// (item_percentage, item_amount, invoice_amount) can only be changed if 
// they are not currently being referenced by any invoices.  
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_pricepointUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'pricepoint_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Pricepoint'), 
		'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
		'code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Code'),
		'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'sequence'),
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.pricepointUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	//
	// Check if sequence is being adjusted, get the old sequence number
	//
	if( isset($args['sequence']) ) {
		$strsql = "SELECT id, sequence "
			. "FROM ciniki_customer_pricepoints "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['pricepoint_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['item']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1804', 'msg'=>'Unable to find price point'));
		}
		$old_sequence = $rc['item']['sequence'];
	}

	//
	// Start the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the price point
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.pricepoint', 
		$args['pricepoint_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}

	//
	// Update the sequence
	//
	if( isset($args['sequence']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'pricepointUpdateSequences');
		$rc = ciniki_customers_pricepointUpdateSequences($ciniki, $args['business_id'], 
			$args['sequence'], $old_sequence);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}

	//
	// Commit the changes to the database
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
