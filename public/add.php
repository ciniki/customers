<?php
//
// Description
// -----------
// This function will add a new customer to the customers production module.
//
// Info
// ----
// Status:          defined
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the customer to.
// eid:                 The tenant ID of the customer, not used for internal linking.
// type:                The type of customer, as specified in customer settings.
// name:                (optional) The full name of the customer.  If this is specified,
//                      it will be split at the first comma into last, first
//                      or split at the last space "first name last".  If there
//                      there is no space or comma, the name will be added as
//                      the first name.
//
//                      **note** One of either name or first must be specified.
//
// callsign:            (optional) The amateur radio callsign of the customer.
// prefix:              (optional) The prefix or title for the persons name: Ms. Mrs. Mr. Dr. etc.
// first:               (optional) The first name of the customer.
// middle:              (optional) The middle name or initial of the customer.
// last:                (optional) The last name of the customer.
// suffix:              (optional) The credentials or degrees for the customer: Ph.D, M.D., Jr., etc.
// display_name_format: (optional) The format for building the display_name.
// company:             (optional) The company the customer works for.
// department:          (optional) The department at the company the customer works for.
// title:               (optional) The customers title at the company.
// email_address:       (optional) The email address of the customer.
// flags:               (optional) The options for the customer email address.  Default: 0.
//  
//                      0x01 - The customer is allowed to login to the tenant website.
//
// address1:            (optional) The first line of the address.
// address2:            (optional) The second line of the address.
// city:                (optional) The city of the address.
// province:            (optional) The province or state of the address.
// postal:              (optional) The postal code or zip code of the address.
// country:             (optional) The country of the address.
// address_flags:       (optional) The options for the address, specifing what the 
//                      address should be used for.
//              
//                          0x01 - Shipping
//                          0x02 - Billing
//                          0x04 - Mailing
//                          0x08 - Public
//
// phone:               (optional) The phone number to assist in deliveries.
// 
// phone_label_1:       (optional) The label for the first phone number.
// phone_number_1:      (optional) The number for the first phone number.
// phone_flags_1:       (optional) The flags for the first phone number.
// phone_label_2:       (optional) The label for the second phone number.
// phone_number_2:      (optional) The number for the second phone number.
// phone_flags_2:       (optional) The flags for the second phone number.
// phone_label_3:       (optional) The label for the third phone number.
// phone_number_3:      (optional) The number for the third phone number.
// phone_flags_3:       (optional) The flags for the third phone number.
// link_name_1:         (optional) The name for the website link.
// link_url_1:          (optional) The URL for the website link.
// link_webflags_1:     (optional) The flags for the website link.
// notes:               (optional) The notes for the customer.
// birthdate:           (optional) The birthdate of the customer.
//
// member_categories:   (optional) The category tags for the member.
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_add(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'parent_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Parent'), 
        'eid'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Customer ID'),
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Status'),
        'type'=>array('required'=>'no', 'default'=>'1', 'blank'=>'yes', 'name'=>'Customer Type'),
        'member_status'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Member Status'), 
        'member_lastpaid'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Member Last Paid'),
        'member_expires'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'date', 'name'=>'Member Expires'),
        'membership_length'=>array('required'=>'no', 'default'=>'20', 'blank'=>'no', 'name'=>'Membership Length'),
        'membership_type'=>array('required'=>'no', 'default'=>'10', 'blank'=>'no', 'name'=>'Membership Type'),
        'dealer_status'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Dealer Status'), 
        'distributor_status'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Distributor Status'), 
        'name'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Name'),
        'callsign'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Callsign'), 
        'prefix'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Prefix'), 
        'first'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Suffix'), 
        'display_name_format'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Display Name Format'), 
        'company'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company'), 
        'department'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company Department'), 
        'title'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Company Title'), 
        'email_address'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Email Address'), 
//        'primary_email'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Primary Email Address'), 
//        'alternate_email'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Alternate Email Address'), 
        'flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Email Options'), 
        'address1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address'),
        'address2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address'), 
        'city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'City'), 
        'province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Province'), 
        'postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Postal Code'), 
        'country'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Country'), 
        'phone'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Phone'), 
        'latitude'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Latitude'), 
        'longitude'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Longitude'), 
        'address_flags'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Flags'), 
        'phone_home'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Home Phone'), 
        'phone_work'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Work Phone'), 
        'phone_cell'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Cell Phone'), 
        'phone_fax'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Fax Number'), 
        'phone_label_1'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'First Phone Label'), 
        'phone_number_1'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'First Phone Number'), 
        'phone_flags_1'=>array('required'=>'no', 'default'=>'0', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'First Phone Number Flags'), 
        'phone_label_2'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Second Phone Label'), 
        'phone_number_2'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Second Phone Number'), 
        'phone_flags_2'=>array('required'=>'no', 'default'=>'0', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Second Phone Number Flags'), 
        'phone_label_3'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Third Phone Label'), 
        'phone_number_3'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Third Phone Number'), 
        'phone_flags_3'=>array('required'=>'no', 'default'=>'0', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Third Phone Number Flags'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'), 
        'primary_image_caption'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Image Caption'), 
        'intro_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Intro Image'), 
        'intro_image_caption'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Intro Image Caption'), 
        'webflags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Webflags'), 
        'short_bio'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Short Bio'), 
        'full_bio'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Full Bio'), 
        'birthdate'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'date', 'name'=>'Birthday'), 
        'connection'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Connection'), 
        'language'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Preferred Language'), 
        'tax_number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Tax Number'), 
        'tax_location_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Tax Location'), 
        'discount_percent'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Discount Percent'), 
        'start_date'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'), 
        'link_name_1'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Website Name'), 
        'link_url_1'=>array('required'=>'no', 'default'=>'', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Website URL'), 
        'link_webflags_1'=>array('required'=>'no', 'default'=>'0', 'trimblanks'=>'yes', 'blank'=>'yes', 'name'=>'Website Flags'), 
        'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Subscriptions'),
        'unsubscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Unsubscriptions'),
        'customer_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Customer Categories'),
        'customer_tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Customer Tags'),
        'member_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Member Categories'),
        'member_subcategories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Member Subcategories'),
        'dealer_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Dealer Categories'),
        'distributor_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'DistributorCategories'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.add', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Filter arguments
    //
    $args['short_description'] = '';
    if( isset($args['callsign']) ) {
        $args['callsign'] = strtoupper($args['callsign']);
    }

    //
    // Get the current settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // They must specify either a firstname or lastname
    //
    if( $args['first'] == '' && $args['last'] == '' && $args['name'] == '' && $args['company'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.47', 'msg'=>'You must specify a first or last name'));
    }

    //
    // Check for a start date, default to now
    //
    if( $args['start_date'] == '' ) {
        $args['start_date'] = gmdate('Y-m-d H:i:s');
    }

    //
    // Check to make sure eid is unique if specified
    //
    if( isset($args['eid']) && $args['eid'] != '' ) {
        $strsql = "SELECT id "
            . "FROM ciniki_customers "
            . "WHERE eid = '" . ciniki_core_dbQuote($ciniki, $args['eid']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'parent');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.48', 'msg'=>'The customer ID already exists.'));
        }
    }

    //
    // Check if trying to make a child customer
    //
    if( isset($args['parent_id']) && $args['parent_id'] > 0 ) {
        // 
        // Check to make sure the parent is not a child
        //
        $strsql = "SELECT id, parent_id "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'parent');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['parent']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.49', 'msg'=>'The parent does not exist.'));
        }
        if( isset($rc['parent']) && $rc['parent']['parent_id'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.50', 'msg'=>'The parent is already a child.'));
        }
    }

    //
    // Check if name should be parsed
    //
    if( $args['first'] == '' && $args['last'] == '' && $args['name'] != '' ) {
        // Check for a comma to see if was entered, "last, first"
        if( preg_match('/^\s*(.*),\s*(.*)\s*$/', $args['name'], $matches) ) {
            $args['last'] = $matches[1];
            $args['first'] = $matches[2];
        } elseif( preg_match('/^\s*(.*)\s([^\s]+)\s*$/', $args['name'], $matches) ) {
            $args['first'] = $matches[1];
            $args['last'] = $matches[2];
        } else {
            // Default to add name to first field instead of last field
            $args['first'] = $args['name'];
        }
    }

    //
    // Setup the display name, sort name and permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
    $rc = ciniki_customers_customerUpdateName($ciniki, $args['tnid'], $args, 0, $args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.377', 'msg'=>'Unable to update name', 'err'=>$rc['err']));
    }
    $args['display_name'] = $rc['display_name'];
    $args['sort_name'] = $rc['sort_name'];
    $args['permalink'] = $rc['permalink'];

    //
    // Determine the display name
    //
/*    $space = '';
    $person_name = '';
    $args['sort_name'] = '';
    if( isset($args['prefix']) && $args['prefix'] != '' ) {
        $person_name .= $args['prefix'];
    }
    if( $space == '' && $person_name != '' ) { $space = ' '; }
    if( isset($args['first']) && $args['first'] != '' ) {
        $person_name .= $space . $args['first'];
    }
    if( $space == '' && $person_name != '' ) { $space = ' '; }
    if( isset($args['middle']) && $args['middle'] != '' ) {
        $person_name .= $space . $args['middle'];
    }
    if( $space == '' && $person_name != '' ) { $space = ' '; }
    if( isset($args['last']) && $args['last'] != '' ) {
        $person_name .= $space . $args['last'];
    }
    if( $space == '' && $person_name != '' ) { $space = ' '; }
    if( isset($args['suffix']) && $args['suffix'] != '' ) {
        $person_name .= ($space!=''?',':'') . $space . $args['suffix'];
    }
    $sort_person_name = '';
    if( isset($args['last']) && $args['last'] != '' ) {
        $sort_person_name = $args['last'];
    }
    if( isset($args['first']) && $args['first'] != '' ) {
        $sort_person_name .= ($sort_person_name!=''?', ':'') . $args['first'];
    }
    if( $args['type'] == 2 && $args['company'] != '' ) {
        // Find the format to use
        $format = 'company';
        if( isset($args['display_name_format']) && $args['display_name_format'] != '' ) {
            $format = $args['display_name_format'];
        } elseif( !isset($settings['display-name-business-format']) 
            || $settings['display-name-business-format'] == 'company' ) {
            $format = 'company';
        } elseif( $settings['display-name-business-format'] != '' ) {
            $format = $settings['display-name-business-format'];
        }
        // Format the display_name
        if( $format == 'company' ) {
            $args['display_name'] = $args['company'];
            $args['sort_name'] = $args['company'];
        } 
        elseif( $format == 'company - person' ) {
            $args['display_name'] = $args['company'] . ($person_name != ''?' - ' . $person_name:'');
            $args['sort_name'] = $args['company'];
        } 
        elseif( $format == 'person - company' ) {
            $args['display_name'] = ($person_name!=''?$person_name . ' - ':'') . $args['company'];
            $args['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $args['company'];
        } 
        elseif( $format == 'company [person]' ) {
            $args['display_name'] = $args['company'] . ($person_name!=''?' [' . $person_name . ']':'');
            $args['sort_name'] = $args['company'];
        } 
        elseif( $format == 'person [company]' ) {
            if( $person_name == '' ) {
                $args['display_name'] = $args['company'];
            } else {
                $args['display_name'] = $person_name . ' [' . $args['company'] . ']';
            }
            $args['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $args['company'];
        }
    } else {
        $args['display_name'] = $person_name;
        $args['sort_name'] = $sort_person_name;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['display_name']);
*/

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.customer', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_id = $rc['id'];


    //
    // Check if phone numbers to add
    //
    for($i=1;$i<4;$i++) {
        if( isset($args["phone_number_$i"]) && $args["phone_number_$i"] != '' ) {
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.phone',
                array('customer_id'=>$customer_id,
                    'phone_label'=>$args["phone_label_$i"],
                    'phone_number'=>$args["phone_number_$i"],
                    'flags'=>$args["phone_flags_$i"]), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
        }
    }
//  if( isset($args['phone_home']) && $args['phone_home'] != '' ) {
//      $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.phone',
//          array('customer_id'=>$customer_id,
//              'phone_label'=>'Home',
//              'phone_number'=>$args['phone_home'],
//              'flags'=>0), 0x04);
//      if( $rc['stat'] != 'ok' ) {
//          ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
//          return $rc;
//      }
//  }
//  if( isset($args['phone_work']) && $args['phone_work'] != '' ) {
//      $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.phone',
//          array('customer_id'=>$customer_id,
//              'phone_label'=>'Work',
//              'phone_number'=>$args['phone_work'],
//              'flags'=>0), 0x04);
//      if( $rc['stat'] != 'ok' ) {
//          ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
//          return $rc;
//      }
//  }
//  if( isset($args['phone_cell']) && $args['phone_cell'] != '' ) {
//      $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.phone',
//          array('customer_id'=>$customer_id,
//              'phone_label'=>'Cell',
//              'phone_number'=>$args['phone_cell'],
//              'flags'=>0), 0x04);
//      if( $rc['stat'] != 'ok' ) {
//          ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
//          return $rc;
//      }
//  }
//  if( isset($args['phone_fax']) && $args['phone_fax'] != '' ) {
//      $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.phone',
//          array('customer_id'=>$customer_id,
//              'phone_label'=>'Fax',
//              'phone_number'=>$args['phone_fax'],
//              'flags'=>0), 0x04);
//      if( $rc['stat'] != 'ok' ) {
//          ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
//          return $rc;
//      }
//  }

    //
    // Check if email address was specified, and add to customer emails
    //
    $email_id = 0;
    if( isset($args['email_address']) && $args['email_address'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.email',
            array('customer_id'=>$customer_id,
                'email'=>$args['email_address'],
                'password'=>'',
                'temp_password'=>'',
                'temp_password_date'=>'',
                'flags'=>$args['flags'],
                ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
        $email_id = $rc['id'];
    }

    //
    // Check if there is an address to add
    //
    $address_id = 0;
    if( (isset($args['address1']) && $args['address1'] != '' ) 
        || (isset($args['address2']) && $args['address2'] != '' )
        || (isset($args['city']) && $args['city'] != '' )
        || (isset($args['province']) && $args['province'] != '' )
        || (isset($args['postal']) && $args['postal'] != '' )
        ) {
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.address',
            array('customer_id'=>$customer_id,
                'flags'=>$args['address_flags'],
                'address1'=>$args['address1'],
                'address2'=>$args['address2'],
                'city'=>$args['city'],
                'province'=>$args['province'],
                'postal'=>$args['postal'],
                'country'=>$args['country'],
                'latitude'=>$args['latitude'],
                'longitude'=>$args['longitude'],
                'phone'=>$args['phone'],
                'notes'=>'',
                ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
        $address_id = $rc['id'];
    }

    if( isset($args['link_url_1']) && $args['link_url_1'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.link',
            array('customer_id'=>$customer_id,
                'name'=>$args['link_name_1'],
                'url'=>$args['link_url_1'],
                'webflags'=>$args['link_webflags_1'],
                'description'=>'',
                ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Check for subscriptions
    //
    if( isset($args['subscriptions']) || isset($args['unsubscriptions']) ) {
        // incase one of the args isn't set, setup with blank arrays
        if( !isset($args['subscriptions']) ) { $args['subscriptions'] = array(); }
        if( !isset($args['unsubscriptions']) ) { $args['unsubscriptions'] = array(); }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'updateCustomerSubscriptions');
        $rc = ciniki_subscriptions_updateCustomerSubscriptions($ciniki, $args['tnid'], 
            $customer_id, $args['subscriptions'], $args['unsubscriptions']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the categories
    //
    if( isset($args['customer_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 10, $args['customer_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the categories
    //
    if( isset($args['customer_tags']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 20, $args['customer_tags']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the member categories
    //
    if( isset($args['member_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 40, $args['member_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the member subcategories
    //
    if( isset($args['member_subcategories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 45, $args['member_subcategories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the dealer categories
    //
    if( isset($args['dealer_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 60, $args['dealer_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the distributor categories
    //
    if( isset($args['distributor_categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['tnid'],
            'ciniki_customer_tags', 'ciniki_customer_history',
            'customer_id', $customer_id, 80, $args['distributor_categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Update the short_description
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
    $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $args['tnid'], $customer_id, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }

    //
    // Update the season membership
    //
    if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateSeasons');
        $rc = ciniki_customers_customerUpdateSeasons($ciniki, $args['tnid'], $customer_id);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'customers');

    $ciniki['syncqueue'][] = array('push'=>'ciniki.customers.customer', 'args'=>array('id'=>$customer_id));

    $rsp = array('stat'=>'ok', 'id'=>$customer_id);

//
//  FIXME: Switch UI to use response for add/update to fill out details
//
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
//  $rc = ciniki_customers__customerDetails($ciniki, $args['tnid'], $customer_id, array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
//  if( $rc['stat'] == 'ok' && isset($rc['details']) ) {
//      $rsp['customer_details'] = $rc['details'];
//  }

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$customer_id));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.members', 'object_id'=>$customer_id));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.dealers', 'object_id'=>$customer_id));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.distributors', 'object_id'=>$customer_id));

    return $rsp;
}
?>
