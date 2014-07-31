<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_customers_pricePointAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
		'code'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Code'),
		'sequence'=>array('required'=>'no', 'blank'=>'no', 'default'=>'1', 'name'=>'Sequence'),
		'flags'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Options'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.pricePointAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	
	//
	// Get the next sequence
	//
	$adjust_sequence = 'yes';
	if( $args['sequence'] == 0 ) {
		$strsql = "SELECT MAX(sequence) AS sequence "
			. "FROM ciniki_customer_pricepoints "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'max');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['max']['sequence']) && $rc['max']['sequence'] > 0 ) {
			$args['sequence'] = $rc['max']['sequence'] + 1;
			$adjust_sequence = 'no';
		}
	}

	//
	// Start a transaction
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
	// Add the price point
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.pricepoint', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}
	$pricepoint_id = $rc['id'];

	//
	// Update the sequence
	//
	if( isset($args['sequence']) && $adjust_sequence == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'pricepointUpdateSequences');
		$rc = ciniki_customers_pricepointUpdateSequences($ciniki, $args['business_id'], 
			$args['sequence'], -1);
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

	return array('stat'=>'ok', 'id'=>$pricepoint_id);
}
?>
