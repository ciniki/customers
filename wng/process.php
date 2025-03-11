<?php
//
// Description
// -----------
// This function will return the b
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_customers_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.wng']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.532', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.533', 'msg'=>"No section specified."));
    }

    //
    // Check which section to process
    //
    if( $section['ref'] == 'ciniki.customers.memberships' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'membershipsProcess');
        return ciniki_customers_wng_membershipsProcess($ciniki, $tnid, $request, $section);
    } 
    elseif( $section['ref'] == 'ciniki.customers.membersgalleries' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'membersGalleriesProcess');
        return ciniki_customers_wng_membersGalleriesProcess($ciniki, $tnid, $request, $section);
    }
    elseif( $section['ref'] == 'ciniki.customers.memberlist' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'memberListProcess');
        return ciniki_customers_wng_memberListProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
