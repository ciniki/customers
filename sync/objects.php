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
function ciniki_customers_sync_objects($ciniki, &$sync, $tnid, $args) {
    ciniki_core_loadMethod($ciniki, 'ciniki' , 'customers', 'private', 'objects');
    return ciniki_customers_objects($ciniki);
}
?>
