<?php
//
// Description
// ===========
// This function will search the customers for the ciniki.sapos module.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_customers_sapos_itemSearch($ciniki, $tnid, $args) {

    if( $args['start_needle'] == '' ) {
        return array('stat'=>'ok', 'items'=>array());
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.222', 'msg'=>'Unable to load season', 'err'=>$rc['err']));
        }
        if( !isset($rc['season']) ) {
            return array('stat'=>'ok', 'items'=>array());
        }
        $season = $rc['season'];
        $season_prefix = $rc['season']['name'] . ' - '; 
    }

    $types = array('10'=>'Regular', '20'=>'Student', '30'=>'Individual', '40'=>'Family', 'lifetime'=>'Lifetime');
    $items = array();

    foreach($types as $mtype => $type) {
        //
        // Make sure the membership level is active, and a price specified
        //
        if( !isset($customer_settings["membership-type-$mtype-active"]) || $customer_settings["membership-type-$mtype-active"] != 'yes' 
            || !isset($customer_settings["membership-type-$mtype-price"]) || $customer_settings["membership-type-$mtype-price"] <= 0 
            ) {
            continue;
        }

        if( strncmp($args['start_needle'], 'membership', strlen($args['start_needle'])) > 0 
            || strncmp($args['start_needle'], $type, strlen($args['start_needle'])) > 0 
            ) {
            $items[] = array('item' => array(
                'status'=>0,
                'object'=>'ciniki.customers.membership',
                'object_id'=>$mtype,
                'price_id'=>0,
                'description'=>$season_prefix . 'Membership - ' . $type,
                'quantity'=>1,
                'unit_amount'=>$customer_settings["membership-type-$mtype-price"],
                'unit_discount_amount'=>0,
                'unit_discount_percentage'=>0,
                'taxtype_id'=>0, 
                'notes'=>'',
                'flags'=>0x08,          // No quantity item
                ));
        }
    }

    return array('stat'=>'ok', 'items'=>$items);        
}
?>
