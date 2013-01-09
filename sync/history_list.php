<?php
//
// Description
// -----------
// This method will return the history from the customer module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_history_list($ciniki, &$sync, $business_id, $args) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncListModuleHistory');
	$args['history_table'] = 'ciniki_customer_history';
	$rc = ciniki_core_syncListModuleHistory($ciniki, $sync, $business_id, $args);
	return $rc;
}
?>
