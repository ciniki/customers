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
function ciniki_customers_sync_objects($ciniki, &$sync, $business_id, $args) {
	
	//
	// NOTES: When pushing a change, grab the history for the current session
	// When increment/partial/full, sync history on it's own
	//

	//
	// Working on version 2 of sync, completely object based
	//
	$history_table = 'ciniki_customer_history';
	$objects = array();
	$objects['customer'] = array(
		'name'=>'Customer',
		'table'=>'ciniki_customers',
		'fields'=>array(
			'cid'=>array(),
			'status'=>array(),
			'type'=>array(),
			'prefix'=>array(),
			'first'=>array(),
			'middle'=>array(),
			'last'=>array(),
			'suffix'=>array(),
			'department'=>array(),
			'title'=>array(),
			'phone_home'=>array(),
			'phone_work'=>array(),
			'phone_cell'=>array(),
			'phone_fax'=>array(),
			'notes'=>array(),
			'birthdate'=>array(),
			),
		'history_table'=>$history_table,
		);
	$objects['address'] = array(
		'name'=>'Customer Address',
		'table'=>'ciniki_customer_addresses',
		'fields'=>array(
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'flags'=>array(),
			'address1'=>array(),
			'address2'=>array(),
			'city'=>array(),
			'province'=>array(),
			'postal'=>array(),
			'country'=>array(),
			'notes'=>array(),
			),
		'history_table'=>$history_table,
		);
	$objects['email'] = array(
		'name'=>'Customer Email',
		'table'=>'ciniki_customer_emails',
		'fields'=>array(
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'email'=>array(),
			'password'=>array(),
			'temp_password'=>array(),
			'temp_password_date'=>array(),
			'flags'=>array(),
			),
		'history_table'=>$history_table,
		);
	$objects['relationship'] = array(
		'name'=>'Customer Relationship',
		'table'=>'ciniki_customer_relationships',
		'fields'=>array(
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'relationship_type'=>array(),
			'related_id'=>array('ref'=>'ciniki.customers.customer'),
			'notes'=>array(),
			),
		'history_table'=>$history_table,
		);
	$objects['setting'] = array(
		'type'=>'settings',
		'name'=>'Customer Settings',
		'table'=>'ciniki_customer_settings',
		'history_table'=>$history_table,
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
