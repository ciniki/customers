<?php
//
// Description
// -----------
// This function will return the list of available sections to the ciniki.wng module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_customers_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.531', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    $sections = array();

    //
    // Image, Menu with no drop downs/submenus
    //
    $sections['ciniki.customers.memberships'] = array(
        'name'=>'Memberships',
        'module' => 'Customers',
        'settings'=>array(
            'title' => array('label' => 'Title', 'type' => 'text'),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
