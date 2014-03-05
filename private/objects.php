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
function ciniki_customers_objects($ciniki) {
	$objects = array();
	$objects['customer'] = array(
		'name'=>'Customer',
		'table'=>'ciniki_customers',
		'fields'=>array(
			'cid'=>array(),
			'status'=>array(),
			'type'=>array(),
			'member_status'=>array(),
			'member_lastpaid'=>array(),
			'membership_length'=>array(),
			'membership_type'=>array(),
			'prefix'=>array(),
			'first'=>array(),
			'middle'=>array(),
			'last'=>array(),
			'suffix'=>array(),
			'display_name'=>array(),
			'company'=>array(),
			'department'=>array(),
			'title'=>array(),
//			'phone_home'=>array(),
//			'phone_work'=>array(),
//			'phone_cell'=>array(),
//			'phone_fax'=>array(),
			'notes'=>array(),
			'birthdate'=>array(),
			'webflags'=>array(),
			'permalink'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'short_bio'=>array(),
			'full_bio'=>array(),
			),
		'history_table'=>'ciniki_customer_history',
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
		'history_table'=>'ciniki_customer_history',
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
		'history_table'=>'ciniki_customer_history',
		);
	$objects['phone'] = array(
		'name'=>'Customer Phone',
		'table'=>'ciniki_customer_phones',
		'fields'=>array(
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'phone_label'=>array(),
			'phone_number'=>array(),
			'flags'=>array(),
			),
		'history_table'=>'ciniki_customer_history',
		);
	$objects['link'] = array(
		'name'=>'Customer Link',
		'table'=>'ciniki_customer_links',
		'fields'=>array(
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'name'=>array(),
			'url'=>array(),
			'description'=>array(),
			'webflags'=>array(),
			),
		'history_table'=>'ciniki_customer_history',
		);
	$objects['image'] = array(
		'name'=>'Customer Image',
		'table'=>'ciniki_customer_images',
		'fields'=>array(
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			),
		'history_table'=>'ciniki_customer_history',
		);
	$objects['relationship'] = array(
		'name'=>'Customer Relationship',
		'table'=>'ciniki_customer_relationships',
		'fields'=>array(
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'relationship_type'=>array(),
			'related_id'=>array('ref'=>'ciniki.customers.customer'),
			'date_started'=>array(),
			'date_ended'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_customer_history',
		);
	$objects['tag'] = array(
		'name'=>'Customer Tag',
		'table'=>'ciniki_customer_tags',
		'fields'=>array(
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'tag_type'=>array(),
			'tag_name'=>array(),
			'permalink'=>array(),
			),
		'history_table'=>'ciniki_customer_history',
		);
	$objects['setting'] = array(
		'type'=>'settings',
		'name'=>'Customer Settings',
		'table'=>'ciniki_customer_settings',
		'history_table'=>'ciniki_customer_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
