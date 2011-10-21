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
function ciniki_customers_addressGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No customer specified'),
		'address_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No address specified'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.addressGet', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
//	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/dateFormat.php');
//	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
//	$date_format = ciniki_users_dateFormat($ciniki);
//	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	$strsql = "SELECT id, customer_id, "
		. "address1, address2, city, province, postal, country, "
		. "ELT(((flags&0x01))+1,'Off','On') AS shipping, "
		. "ELT(((flags&0x02)>>1)+1,'Off','On') AS billing, "
		. "ELT(((flags&0x04)>>2)+1,'Off','On') AS mailing "
		. "FROM customer_addresses "
		. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['address_id']) . "' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'customers', 'address');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['address']) ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'380', 'msg'=>'Invalid customer'));
	}
	return array('stat'=>'ok', 'address'=>$rc['address']);
}
?>
