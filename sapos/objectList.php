<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_customers_sapos_objectList($ciniki, $tnid) {


    $objects = array();

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02) ) {
        $objects['ciniki.customers.membership'] = array(
            'name' => 'Membership',
            );
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) ) {
        $objects['ciniki.customers.product'] = array(
            'name' => 'Membership',
            );
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
