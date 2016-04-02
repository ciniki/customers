<?php
//
// Description
// ===========
// This function will lookup an item that is being added to a shopping cart online.  This function
// has extra checks to make sure the requested item is available to the customer.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_customers_sapos_cartItemLookup($ciniki, $business_id, $customer, $args) {

	if( !isset($args['object']) || $args['object'] == '' 
		|| !isset($args['object_id']) || $args['object_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3229', 'msg'=>'No customer specified.'));
	}

    //
    // Get the customer settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'business_id', $business_id, 'ciniki.customers', 'settings', 'membership');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_settings = $rc['settings'];

    $types = array('10'=>'Regular', '20'=>'Student', '30'=>'Individual', '40'=>'Family', 'lifetime'=>'Lifetime');

	//
	// Lookup the requested course offering if specified along with a price_id
	//
	if( $args['object'] == 'ciniki.customers.membership' ) {
        $mtype = $args['object_id'];
        if( isset($customer_settings["membership-type-$mtype-active"]) && $customer_settings["membership-type-$mtype-active"] == 'yes' 
            && isset($customer_settings["membership-type-$mtype-price"]) && $customer_settings["membership-type-$mtype-price"] > 0
            && isset($customer_settings["membership-type-$mtype-online"]) && $customer_settings["membership-type-$mtype-online"] == 'yes'
            ) {
            $item = array(
                'object'=>'ciniki.customers.membership',
                'object_id'=>$mtype,
                'price_id'=>0,
                'description'=>'Membership - ' . $types[$mtype],
                'limited_units'=>'yes',
                'units_available'=>1,
                'unit_amount'=>$customer_settings["membership-type-$mtype-price"],
                'unit_discount_amount'=>0,
                'unit_discount_percentage'=>0,
                'taxtype_id'=>0,
                'flags'=>0x08,          // No quantity item
                );
            return array('stat'=>'ok', 'item'=>$item);
        }
        else {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3227', 'msg'=>'No item specified.'));
        }
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3228', 'msg'=>'No item specified.'));
}
?>
