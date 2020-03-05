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
            'parent_id'=>array('ref'=>'ciniki.customers.customer', 'default'=>'0'),
            'eid'=>array('default'=>''),
            'status'=>array('default'=>'10'),
            'type'=>array('default'=>'1'),
            'member_status'=>array('default'=>'0'),
            'member_lastpaid'=>array('default'=>''),
            'member_expires'=>array('default'=>''),
            'membership_length'=>array('default'=>'0'),
            'membership_type'=>array('default'=>'0'),
            'dealer_status'=>array('default'=>'0'),
            'distributor_status'=>array('default'=>'0'),
            'callsign'=>array('name'=>'Callsign', 'default'=>''),
            'prefix'=>array('default'=>''),
            'first'=>array(),
            'middle'=>array('default'=>''),
            'last'=>array(),
            'suffix'=>array('default'=>''),
            'display_name'=>array(),
            'display_name_format'=>array('default'=>''),
            'sort_name'=>array(),
            'company'=>array('default'=>''),
            'department'=>array('default'=>''),
            'title'=>array('default'=>''),
            'phone_home'=>array('default'=>''),
            'phone_work'=>array('default'=>''),
            'phone_cell'=>array('default'=>''),
            'phone_fax'=>array('default'=>''),
            'notes'=>array('default'=>''),
            'birthdate'=>array('name'=>'Birthdate', 'default'=>''),
            'connection'=>array('name'=>'Connection', 'default'=>''),
            'language'=>array('name'=>'Preferred Language', 'default'=>''),
            'pricepoint_id'=>array('ref'=>'ciniki.customers.pricepoint', 'default'=>'0'),
            'salesrep_id'=>array('ref'=>'ciniki.users.user', 'default'=>'0'),
            'tax_number'=>array('default'=>''),
            'tax_location_id'=>array('ref'=>'ciniki.taxes.location', 'default'=>'0'),
            'reward_level'=>array('default'=>''),
            'sales_total'=>array('default'=>''),
            'sales_total_prev'=>array('default'=>''),
            'discount_percent'=>array('default'=>'0'),
            'start_date'=>array('default'=>''),
            'webflags'=>array('default'=>'0'),
            'permalink'=>array('default'=>''),
            'primary_image_id'=>array('ref'=>'ciniki.images.image', 'default'=>'0'),
            'primary_image_caption'=>array('default'=>''),
            'short_bio'=>array('default'=>''),
            'short_description'=>array('default'=>''),
            'full_bio'=>array('default'=>''),
            ),
        'history_table'=>'ciniki_customer_history',
        );
    $objects['address'] = array(
        'name'=>'Customer Address',
        'table'=>'ciniki_customer_addresses',
        'fields'=>array(
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'flags'=>array('name'=>'Options', 'default'=>'7'),
            'address1'=>array('name'=>'Address Line 1'),
            'address2'=>array('name'=>'Address Line 2', 'default'=>''),
            'city'=>array('name'=>'City'),
            'province'=>array('name'=>'Province'),
            'postal'=>array('name'=>'Postal'),
            'country'=>array('name'=>'Country', 'default'=>''),
            'latitude'=>array('name'=>'Latitude', 'default'=>''),
            'longitude'=>array('name'=>'Longitude', 'default'=>''),
            'phone'=>array('name'=>'Phone', 'default'=>''),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_customer_history',
        );
    $objects['email'] = array(
        'name'=>'Customer Email',
        'table'=>'ciniki_customer_emails',
        'fields'=>array(
            'customer_id'=>array('ref'=>'ciniki.customers.customer'),
            'email'=>array(),
            'password'=>array('default'=>''),
            'temp_password'=>array('default'=>''),
            'temp_password_date'=>array('default'=>''),
            'flags'=>array('default'=>'1'),
            'failed_logins'=>array('name'=>'Failed Logins', 'default'=>'0'),
            'date_locked'=>array('name'=>'Date Locked', 'default'=>''),
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
            'flags'=>array('default'=>'0'),
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
            'webflags'=>array('name'=>'Web Options', 'default'=>'1'),
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
    $objects['season'] = array(
        'name'=>'Membership Season',
        'table'=>'ciniki_customer_seasons',
        'fields'=>array(
            'name'=>array(),
            'start_date'=>array(),
            'end_date'=>array(),
            'flags'=>array(),
            ),
        'history_table'=>'ciniki_customer_history',
        );
    $objects['season_member'] = array(
        'name'=>'Season Member',
        'table'=>'ciniki_customer_season_members',
        'fields'=>array(
            'season_id'=>array('name'=>'Season', 'ref'=>'ciniki.customers.season'),
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'status'=>array('name'=>'Status', ),
            'date_paid'=>array('name'=>'Date Paid'),
            'notes'=>array('default'=>''),
            ),
        'history_table'=>'ciniki_customer_history',
        );
    $objects['pricepoint'] = array(
        'name'=>'Price Point',
        'sync'=>'yes',
        'table'=>'ciniki_customer_pricepoints',
        'fields'=>array(
            'name'=>array(),
            'code'=>array(),
            'sequence'=>array(),
            'flags'=>array(),
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
