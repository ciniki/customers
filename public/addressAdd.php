<?php
//
// Description
// -----------
// This function will add a new customer address to a customer.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the customer belongs to.
// customer_id:		The ID of the customer to add the address to.
// address1:		(optional) The first line of the address.
// address2:		(optional) The second line of the address.
// city:			(optional) The city of the address.
// province:		(optional) The province or state of the address.
// postal:			(optional) The postal code or zip code of the address.
// country:			(optional) The country of the address.
// latitude:		(optional) The latitude of the address.
// longitude:		(optional) The longitude of the address.
// phone:			(optional) The phone number to assist in deliveries.
// flags:			(optional) The options for the address, specifing what the 
//					address should be used for.
//				
//					0x01 - Shipping
//					0x02 - Billing
//					0x04 - Mailing
//					0x08 - Public, visible on website
//
// address_flags:	(optional) Same as flags, just allows for alternate name.
//
// notes:			(optional) The notes for the address.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_addressAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
		'address1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address Line 1'),
        'address2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address Line 2'), 
        'city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'City'), 
        'province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Province/State'), 
        'postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Postal/Zip Code'), 
        'country'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Country'), 
        'latitude'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Latitude'), 
        'longitude'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Longitude'), 
        'phone'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Phone'), 
        'flags'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Flags'), 
        'address_flags'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Flags'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	//
	// They must specify something about the address
	//
	if( $args['address1'] == '' && $args['city'] == '' && $args['province'] == '' && $args['postal'] != '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'374', 'msg'=>'No address specified'));
	}

	if( (!isset($args['flags']) || $args['flags'] == '') && isset($args['address_flags']) && $args['address_flags'] != '' ) {
		$args['flags'] = $args['address_flags'];
	}
	
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.addressAdd', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Lookup the latitude/longitude
	//
	// FIXME: This is broken, need to figure out google api from php.
/*	if( $args['latitude'] == '' && $args['longitude'] == '' ) {
		$address = '';
		$address .= ($args['address1']!=''?($address!=''?', ':'').$args['address1']:'');
		$address .= ($args['address2']!=''?($address!=''?', ':'').$args['address2']:'');
		$address .= ($args['city']!=''?($address!=''?', ':'').$args['city']:'');
		$address .= ($args['province']!=''?($address!=''?', ':'').$args['province']:'');
		$address .= ($args['country']!=''?($address!=''?', ':'').$args['country']:'');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'geocodeAddressLookupLatLong');
		$rc = ciniki_core_geocodeAddressLookupLatLong($ciniki, $address);
		if( $rc['stat'] == 'ok' ) {
			$args['latitude'] = $rc['latitude'];
			$args['longitude'] = $rc['longitude'];
		}
	}
*/

	//
	// Add the address
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.address', $args, 0x07);
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
