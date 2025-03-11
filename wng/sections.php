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
    // Display membership information
    //
    $sections['ciniki.customers.memberships'] = array(
        'name'=>'Memberships',
        'module' => 'Customers',
        'settings'=>array(
            'title' => array('label' => 'Title', 'type' => 'text'),
            ),
        );

    //
    // Display members photo galleries
    //
    $sections['ciniki.customers.membersgalleries'] = array(
        'name' => 'Member Galleries',
        'module' => 'Members',
        'settings' => array(
            'title' => array('label' => 'Title', 'type' => 'text'),
            'content' => array('label' => 'Intro', 'type' => 'textarea', 'size' => 'medium'),
            ),
        );

    //
    // Display members only members list
    //
/*    $sections['ciniki.customers.memberlist'] = array(
        'name' => 'Member List',
        'module' => 'Members Only',
        'settings' => array(
            'title' => array('label' => 'Title', 'type' => 'text'),
            'content' => array('label' => 'Intro', 'type' => 'textarea', 'size' => 'medium'),
            ),
        ); */

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
