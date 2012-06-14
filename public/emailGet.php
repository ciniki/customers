<?php
//
// Description
// -----------
// This function will return a customer record
//
// Info
// ----
// Status: 			started
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_customers_emailGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No customer specified'),
		'email_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No email specified'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.emailGet', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');

	$strsql = "SELECT id, customer_id, email "
		. "FROM ciniki_customer_emails "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['email_id']) . "' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'customers', 'email');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['email']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'721', 'msg'=>'Invalid customer'));
	}
	return array('stat'=>'ok', 'email'=>$rc['email']);
}
?>
