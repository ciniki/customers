<?php
//
// Description
// -----------
// This method will update a customer address.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the customer belongs to.
// customer_id:		The ID of the customer to update the address for.
// address_id:		The ID of the address to update.
// address1:		(optional) The new first line of the address.
// address2:		(optional) The new second line of the address.
// city:			(optional) The new city of the address.
// province:		(optional) The new province or state of the address.
// postal:			(optional) The new postal code or zip code of the address.
// country:			(optional) The new country of the address.
// flags:			(optional) The new options for the address, specifing what the 
//					address should be used for.
//				
//					0x01 - Shipping
//					0x02 - Billing
//					0x04 - Mailing
//
// address_flags:	(optional) Same as flags, just allows for alternate name.
//
// notes:			(optional) The new notes for the address.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_addressUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'address_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Address ID'), 
        'address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address Line 1'), 
        'address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address Line 2'), 
        'city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'City'), 
        'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Province/State'), 
        'postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Postal/Zip Code'), 
        'country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Country'), 
        'latitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Latitude'), 
        'longitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Longitude'), 
        'phone'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Phone'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'), 
        'address_flags'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Flags'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.addressUpdate', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	if( (!isset($args['flags']) || $args['flags'] == '') && isset($args['address_flags']) && $args['address_flags'] != '' ) {
		$args['flags'] = $args['address_flags'];
	}
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	//
	// Check the address ID belongs to the requested customer
	//
	$strsql = "SELECT ciniki_customer_addresses.id, customer_id, "
		. "ciniki_customer_addresses.address1, "
		. "ciniki_customer_addresses.address2, "
		. "ciniki_customer_addresses.city, "
		. "ciniki_customer_addresses.province, "
		. "ciniki_customer_addresses.postal, "
		. "ciniki_customer_addresses.country, "
		. "ciniki_customer_addresses.latitude, "
		. "ciniki_customer_addresses.longitude, "
		. "ciniki_customer_addresses.phone, "
		. "ciniki_customer_addresses.flags "
		. "FROM ciniki_customers, ciniki_customer_addresses "
		. "WHERE ciniki_customer_addresses.id = '" . ciniki_core_dbQuote($ciniki, $args['address_id']) . "' "
		. "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND customer_id = ciniki_customers.id "
		. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'address');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( !isset($rc['address']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'741', 'msg'=>'Access denied'));
	}
	$item = $rc['address'];

	$old_address = '';
	$old_address .= ($item['address1']!=''?($old_address!=''?', ':'').$item['address1']:'');
	$old_address .= ($item['address2']!=''?($old_address!=''?', ':'').$item['address2']:'');
	$old_address .= ($item['city']!=''?($old_address!=''?', ':'').$item['city']:'');
	$old_address .= ($item['province']!=''?($old_address!=''?', ':'').$item['province']:'');
	$old_address .= ($item['country']!=''?($old_address!=''?', ':'').$item['country']:'');
	$old_address .= ($item['phone']!=''?($old_address!=''?', ':'').$item['phone']:'');

	$new_address = '';
	if( isset($args['address1']) ) {
		$new_address .= ($args['address1']!=''?($new_address!=''?', ':'').$args['address1']:'');
	} else {
		$new_address .= ($item['address1']!=''?($new_address!=''?', ':'').$item['address1']:'');
	}
	if( isset($args['address2']) ) {
		$new_address .= ($args['address2']!=''?($new_address!=''?', ':'').$args['address2']:'');
	} else {
		$new_address .= ($item['address2']!=''?($new_address!=''?', ':'').$item['address2']:'');
	}
	if( isset($args['city']) ) {
		$new_address .= ($args['city']!=''?($new_address!=''?', ':'').$args['city']:'');
	} else {
		$new_address .= ($item['city']!=''?($new_address!=''?', ':'').$item['city']:'');
	}
	if( isset($args['province']) ) {
		$new_address .= ($args['province']!=''?($new_address!=''?', ':'').$args['province']:'');
	} else {
		$new_address .= ($item['province']!=''?($new_address!=''?', ':'').$item['province']:'');
	}
	if( isset($args['country']) ) {
		$new_address .= ($args['country']!=''?($new_address!=''?', ':'').$args['country']:'');
	} else {
		$new_address .= ($item['country']!=''?($new_address!=''?', ':'').$item['country']:'');
	}
	if( isset($args['phone']) ) {
		$new_address .= ($args['phone']!=''?($new_address!=''?', ':'').$args['phone']:'');
	} else {
		$new_address .= ($item['phone']!=''?($new_address!=''?', ':'').$item['phone']:'');
	}

	//
	// If the address has changed, and the latitude and longitude were not also updated,
	// then lookup the new address
	//
	// FIXME: This is broken, need to figure out google api from php.
/*	if( $new_address != $old_address 
		&& (!isset($args['latitude']) || $args['latitude'] == $item['latitude'])
		&& (!isset($args['longitude']) || $args['longitude'] == $item['longitude'])
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'geocodeAddressLookupLatLong');
		$rc = ciniki_core_geocodeAddressLookupLatLong($ciniki, $new_address);
		if( $rc['stat'] == 'ok' ) {
			$args['latitude'] = $rc['latitude'];
			$args['longitude'] = $rc['longitude'];
		}
	}
*/

	//
	// Update the address
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.address', $args['address_id'], $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the short_description
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
	$rc = ciniki_customers_customerUpdateShortDescription($ciniki, $args['business_id'], $args['customer_id'], 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
