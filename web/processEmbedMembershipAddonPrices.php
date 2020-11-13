<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_processEmbedMembershipAddonPrices(&$ciniki, $settings, $tnid, $args) {

    $prices = array();

    //
    // Load the membership products
    //


    //
    // Create the list of prices
    //

    //
    // Load the customers settings
    //
/*    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $tnid, 'ciniki.customers', 'settings', 'membership');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_settings = $rc['settings'];

    $types = array('10'=>'Regular', '20'=>'Student', '30'=>'Individual', '40'=>'Family', 'lifetime'=>'Lifetime');

    foreach($types as $tid => $type_name) {
        if( isset($customer_settings["membership-type-$tid-active"]) && $customer_settings["membership-type-$tid-active"] == 'yes' ) {
            $price = array();
            if( isset($customer_settings["membership-type-$tid-name"]) && $customer_settings["membership-type-$tid-name"] != '' ) {
                $price['name'] = $customer_settings["membership-type-$tid-name"];
            } else {
                $price['name'] = $type_name;
            }
            if( isset($customer_settings["membership-type-$tid-price"]) && $customer_settings["membership-type-$tid-price"] != '' ) {
                $price['unit_amount'] = $customer_settings["membership-type-$tid-price"];
                $price['units_available'] = 1;
                $price['limited_units'] = 'yes';
            }
            if( isset($customer_settings["membership-type-$tid-online"]) && $customer_settings["membership-type-$tid-online"] == 'yes' ) {
                $price['cart'] = 'yes';
                $price['object'] = 'ciniki.customers.membership';
                $price['object_id'] = $tid;
                $price['price_id'] = 0;
            }
            $prices[] = $price;
        }
    } */

    return array('stat'=>'ok', 'blocks'=>array(array('type'=>'prices', 'prices'=>$prices))); 
}
?>
