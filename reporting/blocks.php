<?php
//
// Description
// -----------
// This function will return the list of available blocks to the ciniki.reporting module.
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
function ciniki_customers_reporting_blocks(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.220', 'msg'=>"I'm sorry, the block you requested does not exist."));
    }

    $blocks = array();

    //
    // Return the list of blocks for the tenant
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x8000) ) {
        $blocks['ciniki.customers.birthdays'] = array(
            'name'=>'Upcoming Birthdays',
            'module' => 'Customers',
            'options'=>array(
                'days'=>array('label'=>'Number of Days', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
                ),
            );
    }
    $blocks['ciniki.customers.newcustomers'] = array(
        'name'=>'New Customers',
        'module' => 'Customers',
        'options'=>array(
            'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
            ),
        );
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02) ) {
        $blocks['ciniki.customers.newmembers'] = array(
            'name'=>'New Members',
            'module' => 'Customers',
            'options'=>array(
                'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
                ),
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
