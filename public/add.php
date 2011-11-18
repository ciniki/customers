<?php
//
// Description
// -----------
// This function will add a new customer to the customers production module.
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
function ciniki_customers_add($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'name'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No name specified'),
        'prefix'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No prefix specified'), 
        'first'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No first name specified'), 
        'middle'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No middle name specified'), 
        'last'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No last name specified'), 
        'suffix'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No suffix specified'), 
        'company'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No compan specified'), 
        'department'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No company department specified'), 
        'title'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No company title specified'), 
        'primary_email'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No primary email specified'), 
        'alternate_email'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No alternate email specified'), 
        'phone_home'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No Home Phone specified'), 
        'phone_work'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No Work Phone specified'), 
        'phone_cell'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No Cell specified'), 
        'phone_fax'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'errmsg'=>'No Fax specified'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No notes specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	//
	// They must specify either a firstname or lastname
	//
	if( $args['first'] == '' && $args['last'] == '' && $args['name'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'368', 'msg'=>'You must specify a first or last name'));
	}

	//
	// Check if name should be parsed
	//
	if( $args['first'] == '' && $args['last'] == '' && $args['name'] != '' ) {
		// Check for a comma to see if was entered, "last, first"
		if( preg_match('/^\s*(.*),\s*(.*)\s*$/', $args['name'], $matches) ) {
			$args['last'] = $matches[1];
			$args['first'] = $matches[2];
		} elseif( preg_match('/^\s*(.*)\s([^\s]+)\s*$/', $args['name'], $matches) ) {
			$args['first'] = $matches[1];
			$args['last'] = $matches[2];
		} else {
			// Default to add name to first field instead of last field
			$args['first'] = $args['name'];
		}
	}
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/customers/private/checkAccess.php');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.add', 0); 
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the customer to the database
	//
	$strsql = "INSERT INTO ciniki_customers (business_id, status, prefix, first, middle, last, suffix, "
		. "company, department, title, notes, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "1, "
		. "'" . ciniki_core_dbQuote($ciniki, $args['prefix']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['first']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['middle']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['last']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['suffix']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['company']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['department']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['title']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['notes']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'customers');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'customers');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'369', 'msg'=>'Unable to add customer'));
	}
	$customer_id = $rc['insert_id'];

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
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'customers', $args['business_id'], 
				'ciniki_customers', $customer_id, $field, $args[$field]);
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$customer_id);
}
?>
