<?php
//
// Description
// -----------
// This function returns the list
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get the settings for.
// 
// Returns
// -------
//
function ciniki_customers_getRelationshipTypes($ciniki, $tnid) {
    return array('stat'=>'ok', 'types'=>array('10'=>'a tenant owner of',
        '-10'=>'owned by',
        '11'=>'a tenant partner of',
        '30'=>'a friend of',
        '40'=>'a relative of',
        '41'=>'a parent to',
        '-41'=>'a child of',
        '42'=>'a step-parent to',
        '-42'=>'a step-child of',
        '43'=>'a parent-in-law to',
        '-43'=>'a child-in-law of',
        '44'=>'a spouse of',
        '45'=>'a sibling of',
        '46'=>'a step-sibling of',
        '47'=>'a sibling-in-law of',
        ));
}
?>
