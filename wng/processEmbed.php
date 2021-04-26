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
function ciniki_customers_wng_processEmbed(&$ciniki, $tnid, $request, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.customers']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.491', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) 
        && isset($args['data']) && $args['data'] == 'membershipprices' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'processEmbedMembershipPrices');
        return ciniki_customers_wng_processEmbedMembershipPrices($ciniki, $tnid, $request, $args);
    } elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) 
        && isset($args['data']) && $args['data'] == 'membershipaddonprices' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'processEmbedMembershipAddonPrices');
        return ciniki_customers_wng_processEmbedMembershipAddonPrices($ciniki, $tnid, $request, $args);
    } elseif( isset($args['data']) && $args['data'] == 'membershipfees' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'processEmbedMembershipFees');
        return ciniki_customers_wng_processEmbedMembershipFees($ciniki, $tnid, $request, $args);
    }

    return array('stat'=>'ok', 'content'=>''); 
}
?>
