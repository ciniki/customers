<?php
//
// Description
// -----------
// This function will return the settings for customers.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get the settings for.
// 
// Returns
// -------
//
function ciniki_customers_getSettings($ciniki, $business_id) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    return ciniki_core_dbDetailsQuery($ciniki, 'ciniki_customer_settings', 'business_id', $business_id, 'ciniki.customers', 'settings', '');
}
