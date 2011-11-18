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
function ciniki_customers_update($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No customer specified'), 
        'prefix'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No prefix specified'), 
        'first'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No first name specified'), 
        'middle'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No middle name specified'), 
        'last'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No last name specified'), 
        'suffix'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No suffix specified'), 
        'company'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No compan specified'), 
        'department'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No company department specified'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No company title specified'), 
        'primary_email'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No primary email specified'), 
        'alternate_email'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No alternate email specified'), 
        'phone_home'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No home phone specified'), 
        'phone_work'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No work phone specified'), 
        'phone_cell'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No cell specified'), 
        'phone_fax'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No fax specified'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No notes specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.update', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'customers');
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
		'prefix',
		'first',
		'middle',
		'last',
		'suffix',
		'company',
		'department',
		'title',
		'primary_email',
		'alternate_email',
		'phone_home',
		'phone_work',
		'phone_cell',
		'phone_fax',
		'notes',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
				'ciniki_customers', $args['customer_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'371', 'msg'=>'Unable to add customer'));
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
