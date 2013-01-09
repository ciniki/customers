<?php
//
// Description
// -----------
// This will return all the history for a module
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_history_get($ciniki, &$sync, $business_id, $args) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncGetModuleHistory');
	$rc = ciniki_core_syncGetModuleHistory($ciniki, $sync, $business_id, array(
		'history_table'=>'ciniki_customer_history',
		'uuid'=>$args['uuid'],
		'module'=>'ciniki.customers',
		'table_key_maps'=>array('ciniki_customers'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
			'ciniki_customer_emails'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'email_lookup'),
			'ciniki_customer_addreses'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'address_lookup'),
			'ciniki_customer_relationships'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'relationship_lookup'),
			),
		'new_value_maps'=>array('customer_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
			'related_id'=>array('package'=>'ciniki', 'module'=>'customers', 'lookup'=>'customer_lookup'),
			),
		));
	return $rc;
}
?>
