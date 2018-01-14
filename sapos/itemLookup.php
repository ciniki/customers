<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_customers_sapos_itemLookup($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.225', 'msg'=>'No customer specified.'));
    }

    //
    // Get the customer settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $tnid, 'ciniki.customers', 'settings', 'membership');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_settings = $rc['settings'];

    //
    // Get the current season
    //
    $season_prefix = '';
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02000000) ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_customer_seasons "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x01) = 0x01 "
            . "ORDER BY end_date DESC "
            . "LIMIT 1 ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'season');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.223', 'msg'=>'Unable to load season', 'err'=>$rc['err']));
        }
        if( !isset($rc['season']) ) {
            return array('stat'=>'ok', 'items'=>array());
        }
        $season = $rc['season'];
        $season_prefix = $rc['season']['name'] . ' - '; 
    }

    $types = array('10'=>'Regular', '20'=>'Student', '30'=>'Individual', '40'=>'Family', 'lifetime'=>'Lifetime');

    //
    // Lookup the requested course offering if specified along with a price_id
    //
    if( $args['object'] == 'ciniki.customers.membership' ) {
        $mtype = $args['object_id'];
        if( isset($types[$mtype]) 
            && isset($customer_settings["membership-type-$mtype-active"]) && $customer_settings["membership-type-$mtype-active"] == 'yes' 
            && isset($customer_settings["membership-type-$mtype-price"]) && $customer_settings["membership-type-$mtype-price"] > 0
            ) {
            $item = array(
                'object'=>'ciniki.customers.membership',
                'object_id'=>$mtype,
                'price_id'=>0,
                'description'=>$season_prefix . 'Membership - ' . $types[$mtype],
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.226', 'msg'=>'No item specified.'));
        }
    }

    return array('stat'=>'ok');
}
?>
