<?php
//
// Description
// -----------
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_update(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'cid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Type'), 
        'prefix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Prefix'), 
        'first'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Suffix'), 
        'company'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company'), 
        'department'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Department'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Title'), 
        'phone_home'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Home Phone'), 
        'phone_work'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Phone'), 
        'phone_cell'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cell Phone'), 
        'phone_fax'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Fax Number'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'birthdate'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Birthday'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.update', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the customer to the database
	//
	$strsql = "UPDATE ciniki_customers SET last_updated = UTC_TIMESTAMP()";

	//
	// Add all the fields to the change log
	//

	$changelog_fields = array(
		'cid',
		'type',
		'prefix',
		'first',
		'middle',
		'last',
		'suffix',
		'company',
		'department',
		'title',
//		'primary_email',
//		'alternate_email',
		'phone_home',
		'phone_work',
		'phone_cell',
		'phone_fax',
		'notes',
		'birthdate',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['business_id'],
				2, 'ciniki_customers', $args['customer_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'371', 'msg'=>'Unable to add customer'));
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

	$ciniki['syncqueue'][] = array('push'=>'ciniki.customers.customer', 'args'=>array('id'=>$args['customer_id']));
//	$ciniki['syncqueue'][] = array('method'=>'ciniki.customers.customer.push', 'args'=>array('id'=>$args['customer_id']));

	return array('stat'=>'ok');
}
?>
