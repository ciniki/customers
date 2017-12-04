<?php
//
// Description
// -----------
// This function will return the settings for customers.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get the settings for.
// 
// Returns
// -------
//
function ciniki_customers_getSettings($ciniki, $tnid) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    return ciniki_core_dbDetailsQuery($ciniki, 'ciniki_customer_settings', 'tnid', $tnid, 'ciniki.customers', 'settings', '');
}
