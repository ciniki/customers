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
function ciniki_customers_wng_accountProfileProcess($ciniki, $tnid, &$request, $item) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    $required = array();        // List of required fields

    $blocks = array();

    $request['breadcrumbs'][] = array(
        'title' => 'Profile',
        'page-class' => 'page-account-profile',
        'url' => $request['base_url'] . '/account/profile',
        );

    $base_url = $request['base_url'] . '/account/profile';


    //
    // Get contact info
    //
    $item['return-url'] = $base_url;
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountContactProcess');
    $rc = ciniki_customers_wng_accountContactProcess($ciniki, $tnid, $request, $item);
    if( isset($rc['blocks']) ) {
        foreach($rc['blocks'] as $block) {
            $blocks[] = $block;
        }
    }

    //
    // Display Children
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountChildrenProcess');
    $rc = ciniki_customers_wng_accountChildrenProcess($ciniki, $tnid, $request, $item);
    if( isset($rc['blocks']) ) {
        foreach($rc['blocks'] as $block) {
            $blocks[] = $block;
        }
    }


    //
    // Display memberships
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'accountMembershipProcess');
    $rc = ciniki_customers_wng_accountMembershipProcess($ciniki, $tnid, $request, $item);
    if( isset($rc['blocks']) ) {
        foreach($rc['blocks'] as $block) {
            $blocks[] = $block;
        }
    }



    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
