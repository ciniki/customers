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
            'member_lastpaid'=>array('default'=>'0000-00-00 00:00:00'),
            'member_expires'=>array('default'=>'0000-00-00 00:00:00'),
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
            'birthdate'=>array('name'=>'Birthdate', 'default'=>'0000-00-00 00:00:00'),
            'connection'=>array('name'=>'Connection', 'default'=>''),
            'language'=>array('name'=>'Preferred Language', 'default'=>''),
            'pricepoint_id'=>array('ref'=>'ciniki.customers.pricepoint', 'default'=>'0'), //deprecated
            'salesrep_id'=>array('ref'=>'ciniki.users.user', 'default'=>'0'), //deprecated
            'tax_number'=>array('default'=>''),
            'tax_location_id'=>array('ref'=>'ciniki.taxes.location', 'default'=>'0'),
            'reward_level'=>array('default'=>''), //deprecated
            'sales_total'=>array('default'=>''), //deprecated
            'sales_total_prev'=>array('default'=>''), //deprecated
            'discount_percent'=>array('default'=>'0'),
            'start_date'=>array('name'=>'Start Date', 'default'=>'0000-00-00'),
            'stripe_customer_id'=>array('name'=>'Stripe Customer ID', 'default'=>''),
            'webflags'=>array('default'=>'0'),
            'permalink'=>array('default'=>''),
            'primary_image_id'=>array('ref'=>'ciniki.images.image', 'default'=>'0'),
            'primary_image_caption'=>array('default'=>''),
            'intro_image_id'=>array('ref'=>'ciniki.images.image', 'default'=>'0'),
            'intro_image_caption'=>array('default'=>''),
            'short_bio'=>array('default'=>''),
            'short_description'=>array('default'=>''),
            'full_bio'=>array('default'=>''),
            'other1'=>array('name'=>'Other Data 1', 'default'=>''),
            'other2'=>array('name'=>'Other Data 2', 'default'=>''),
            'other3'=>array('name'=>'Other Data 3', 'default'=>''),
            'other4'=>array('name'=>'Other Data 4', 'default'=>''),
            'other5'=>array('name'=>'Other Data 5', 'default'=>''),
            'other6'=>array('name'=>'Other Data 6', 'default'=>''),
            'other7'=>array('name'=>'Other Data 7', 'default'=>''),
            'other8'=>array('name'=>'Other Data 8', 'default'=>''),
            'other9'=>array('name'=>'Other Data 9', 'default'=>''),
            'other10'=>array('name'=>'Other Data 10', 'default'=>''),
            'other11'=>array('name'=>'Other Data 11', 'default'=>''),
            'other12'=>array('name'=>'Other Data 12', 'default'=>''),
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
            'name'=>array('name'=>'Name'),
            'url'=>array('name'=>'URL'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'webflags'=>array('name'=>'Options', 'default'=>0x01),
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
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'tag_type'=>array('name'=>'Tag Type',),
            'tag_name'=>array('name'=>'Tag'),
            'permalink'=>array('name'=>'Permalink'),
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
    $objects['reminder'] = array(
        'name' => 'Reminders',
        'sync' => 'yes',
        'o_name' => 'reminder',
        'o_container' => 'reminders',
        'table' => 'ciniki_customer_reminders',
        'fields' => array(
            'customer_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'reminder_date' => array('name'=>'Date'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'repeat_type' => array('name'=>'Repeat Type', 'default'=>'0'),
            'repeat_interval' => array('name'=>'Repeat Interval', 'default'=>'1'),
            'repeat_end' => array('name'=>'Repeat End Date', 'default'=>''),
            'description' => array('name'=>'Description'),
            'category' => array('name'=>'Category', 'default'=>''),
            'notes' => array('name'=>'Notes', 'default'=>''),
            'email_time' => array('name'=>'Email Time', 'default'=>''),
            'email_next_dt' => array('name'=>'Next Email Date', 'default'=>''),
            'email_subject' => array('name'=>'Email Subject', 'default'=>''),
            'email_html' => array('name'=>'Email Content', 'default'=>''),
            ),
        'history_table' => 'ciniki_customer_history',
        );
    $objects['product'] = array(
        'name' => 'Membership Products',
        'sync' => 'yes',
        'o_name' => 'product',
        'o_container' => 'products',
        'table' => 'ciniki_customer_products',
        'fields' => array(
            'name' => array('name'=>'Product Name'),
            'short_name' => array('name'=>'Short Name'),
            'code' => array('name'=>'Code', 'default'=>''),
            'permalink' => array('name'=>'URL', 'default'=>''),
            'type' => array('name'=>'Product Type', 'default'=>''),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'months' => array('name'=>'Months', 'default'=>'12'),
            'sequence' => array('name'=>'Order', 'default'=>'1'),
            'primary_image_id' => array('name'=>'Primary Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'description' => array('name'=>'Description', 'default'=>''),
            'unit_amount' => array('name'=>'Unit Amount', 'default'=>''),
            ),
        'history_table' => 'ciniki_customer_history',
        );
    $objects['product_purchase'] = array(
        'name' => 'Membership Product Purchases',
        'sync' => 'yes',
        'o_name' => 'purchase',
        'o_container' => 'purchases',
        'table' => 'ciniki_customer_product_purchases',
        'fields' => array(
            'product_id' => array('name'=>'Product', 'ref'=>'ciniki.customers.product'),
            'customer_id' => array('name'=>'Product', 'ref'=>'ciniki.customers.customer'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'purchase_date' => array('name'=>'Date Purchased', 'default'=>''),
            'invoice_id' => array('name'=>'Invoice ID', 'ref'=>'ciniki.sapos.invoice', 'default'=>'0'),
            'invoice_item_id' => array('name'=>'Invoice Item ID', 'ref'=>'ciniki.sapos.invoice_item', 'default'=>'0'),
            'start_date' => array('name'=>'Start Date', 'default'=>''),
            'end_date' => array('name'=>'End Date', 'default'=>''),
            'stripe_customer_id' => array('name'=>'Stripe Customer', 'default'=>''),
            'stripe_subscription_id' => array('name'=>'Stripe Subscription', 'default'=>''),
            ),
        'history_table' => 'ciniki_customer_history',
        );
    $objects['log'] = array(
        'name' => 'Log Entry',
        'sync' => 'yes',
        'o_name' => 'log',
        'o_container' => 'logs',
        'table' => 'ciniki_customer_logs',
        'fields' => array(
            'log_date' => array('name'=>'Date'),
            'status' => array('name'=>'Status'),
            'ip_address' => array('name'=>'IP'),
            'action' => array('name'=>'Action'),
            'customer_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customers'),
            'email' => array('name'=>'Email'),
            'error_code' => array('name'=>'Code'),
            'error_msg' => array('name'=>'Message'),
            ),
        'history_table' => 'ciniki_customer_history',
        );
    $objects['signup'] = array(
        'name' => 'Signups',
        'sync' => 'yes',
        'o_name' => 'signup',
        'o_container' => 'signups',
        'table' => 'ciniki_customer_signups',
        'fields' => array(
            'signupkey' => array('name'=>'Key'),
            'first' => array('name'=>'First Name', 'default'=>''),
            'last' => array('name'=>'Last Name', 'default'=>''),
            'email' => array('name'=>'Email', 'default'=>''),
            'password' => array('name'=>'Password', 'default'=>''),
            'details' => array('name'=>'Details', 'default'=>''),
            ),
        'history_table' => 'ciniki_customer_history',
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
