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
function ciniki_customers_web_processEmbed(&$ciniki, $settings, $tnid, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.customers']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.209', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) 
        && isset($args['data']) && $args['data'] == 'membershipprices' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'processEmbedMembershipPrices');
        return ciniki_customers_web_processEmbedMembershipPrices($ciniki, $settings, $tnid, $args);
    } elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) 
        && isset($args['data']) && $args['data'] == 'membershipaddonprices' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'processEmbedMembershipAddonPrices');
        return ciniki_customers_web_processEmbedMembershipAddonPrices($ciniki, $settings, $tnid, $args);
    } elseif( isset($args['data']) && $args['data'] == 'membershipfees' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'processEmbedMembershipFees');
        return ciniki_customers_web_processEmbedMembershipFees($ciniki, $settings, $tnid, $args);
    }

    return array('stat'=>'ok', 'content'=>''); 
}
?>
