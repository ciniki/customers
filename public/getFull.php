<?php
//
// Description
// -----------
// This function will return a full record of the customer, including attached addresses and emails.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_getFull($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Tags'),
        'member_categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member Categories'),
        'dealer_categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Dealer Categories'),
        'distributor_categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Distributor Categories'),
        'sales_reps'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sales Reps'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.getFull', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Get the types of customers available for this tenant
    //
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
//  $rc = ciniki_customers_getCustomerTypes($ciniki, $args['tnid']); 
//  if( $rc['stat'] != 'ok' ) { 
//      return $rc;
//  }
//  $types = $rc['types'];

    //
    // Get the settings for customer module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // Get the relationship types
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getRelationshipTypes');
    $rc = ciniki_customers_getRelationshipTypes($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $relationship_types = $rc['types'];

    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
//  $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//  $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $mysql_date_format = ciniki_users_dateFormat($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
//  $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Get the customer details and emails
    //
    $strsql = "SELECT ciniki_customers.id, eid, parent_id, type, status, "
        . "member_status, member_lastpaid, member_expires, membership_length, membership_type, "
        . "dealer_status, distributor_status, "
        . "callsign, prefix, first, middle, last, suffix, "
        . "display_name, display_name_format, company, department, title, "
        . "phone_home, phone_work, phone_cell, phone_fax, primary_email, alternate_email, "
//      . "ciniki_customer_emails.id AS email_id, ciniki_customer_emails.email, "
//      . "ciniki_customer_emails.flags AS email_flags, "
        . "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, '%b %e, %Y') . "'), '') AS birthdate, "
        . "connection, language, "
        . "pricepoint_id, salesrep_id, tax_number, tax_location_id, reward_level, sales_total, sales_total_prev, discount_percent, start_date, "
        . "notes, primary_image_id, webflags, short_bio, full_bio "
        . "FROM ciniki_customers "
//      . "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id) "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'webflags', 'parent_id', 'status',
                'member_status', 'member_lastpaid', 'member_expires', 'membership_length', 'membership_type', 
                'phone_home', 'phone_work', 'phone_cell', 'phone_fax', 'primary_email', 'alternate_email',
                'dealer_status', 'distributor_status',
                'eid', 'type', 'callsign', 'prefix', 'first', 'middle', 'last', 'suffix', 
                'display_name', 'display_name_format', 'company', 'department', 'title', 
                'pricepoint_id', 'salesrep_id', 'tax_number', 'tax_location_id', 
                'reward_level', 'sales_total', 'sales_total_prev', 'discount_percent', 'start_date', 
                'notes', 'primary_image_id', 'short_bio', 'full_bio', 'birthdate', 'connection', 'language'),
            'utctotz'=>array(
                'member_lastpaid'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'member_expires'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
                ),
//      array('container'=>'emails', 'fname'=>'email_id', 'name'=>'email',
//          'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'address'=>'email', 
//              'flags'=>'email_flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.86', 'msg'=>'Invalid customer'));
    }
    //
    // Set the display type for the customer
    //
//  if( $rc['customers'][0]['customer']['type'] > 0 && isset($types[$rc['customers'][0]['customer']['type']]) ) {
//      $rc['customers'][0]['customer']['display_type'] = $types[$rc['customers'][0]['customer']['type']]['detail_value'];
//  }

    $customer = $rc['customers'][0]['customer'];
    $customer['addresses'] = array();
    $customer['subscriptions'] = array();
    $customer['discount_percent'] = ($customer['discount_percent'] == 0 ? '' : (float)$customer['discount_percent']);

    //
    // Get the categories and tags for the customers
    //
    if( ($modules['ciniki.customers']['flags']&0xC00224) > 0 ) {
        $strsql = "SELECT tag_type, tag_name AS lists "
            . "FROM ciniki_customer_tags "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tags']) ) {
            foreach($rc['tags'] as $tags) {
                if( $tags['tags']['tag_type'] == 10 ) {
                    $customer['customer_categories'] = $tags['tags']['lists'];
                } elseif( $tags['tags']['tag_type'] == 20 ) {
                    $customer['customer_tags'] = $tags['tags']['lists'];
                } elseif( $tags['tags']['tag_type'] == 40 ) {
                    $customer['member_categories'] = $tags['tags']['lists'];
                } elseif( $tags['tags']['tag_type'] == 60 ) {
                    $customer['dealer_categories'] = $tags['tags']['lists'];
                } elseif( $tags['tags']['tag_type'] == 80 ) {
                    $customer['distributor_categories'] = $tags['tags']['lists'];
                }
            }
        }
    }

    //
    // Get the customer email addresses
    //
//    if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x20000000) > 0 ) {
        $strsql = "SELECT id, customer_id, email AS address, flags "
            . "FROM ciniki_customer_emails "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'emails', 'fname'=>'id', 'name'=>'email',
                'fields'=>array('id', 'customer_id', 'address', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['emails']) ) {
            $customer['emails'] = $rc['emails'];
        } else {
            $customer['emails'] = array();
        }
//    }
    //
    // Get phones
    //
    if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x10000000) == 0 ) {
        $strsql = "SELECT id, phone_label, phone_number, flags "
            . "FROM ciniki_customer_phones "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'phones', 'fname'=>'id', 'name'=>'phone',
                'fields'=>array('id', 'phone_label', 'phone_number', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['phones']) ) {
            $customer['phones'] = $rc['phones'];
        }
    }

    //
    // Get the customer addresses
    //
    $strsql = "SELECT id, customer_id, "
        . "address1, address2, city, province, postal, country, flags, phone "
        . "FROM ciniki_customer_addresses "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'addresses', 'fname'=>'id', 'name'=>'address',
            'fields'=>array('id', 'customer_id', 'address1', 'address2', 'city', 'province', 'postal', 
                'country', 'flags', 'phone')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['addresses']) ) {
        $customer['addresses'] = $rc['addresses'];
    }

    //
    // Get the customer links
    //
    $strsql = "SELECT id, customer_id, name, url, webflags "
        . "FROM ciniki_customer_links "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'links', 'fname'=>'id', 'name'=>'link',
            'fields'=>array('id', 'customer_id', 'name', 'url', 'webflags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['links']) ) {
        $customer['links'] = $rc['links'];
    }

    //
    // Get the relationships for the customer
    //
    if( isset($settings['use-relationships']) && $settings['use-relationships'] == 'yes' ) {
        $strsql = "SELECT ciniki_customer_relationships.id, relationship_type AS type, "
            . "relationship_type AS type_name, "
            . "ciniki_customer_relationships.customer_id, ciniki_customer_relationships.related_id, "
//          . "IF(customer_id='" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', related_id, customer_id) AS related_id, "
            . "date_started, date_ended, ciniki_customers.display_name, ciniki_customers.company "
            . "";
        $strsql .= "FROM ciniki_customer_relationships "
            . "LEFT JOIN ciniki_customers ON ("
                . "(ciniki_customer_relationships.customer_id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "AND ciniki_customer_relationships.customer_id = ciniki_customers.id "
                . ") OR ("
                . "ciniki_customer_relationships.related_id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "AND ciniki_customer_relationships.related_id = ciniki_customers.id "
                . ")) "
            . "WHERE ciniki_customer_relationships.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (ciniki_customer_relationships.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "OR ciniki_customer_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . ") "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'relationships', 'fname'=>'id', 'name'=>'relationship',
                'fields'=>array('id', 'type', 'customer_id', 'type_name', 'related_id', 
                    'display_name', 'date_started', 'date_ended'),
                'maps'=>array('type_name'=>$relationship_types)),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['relationships']) ) {
            $customer['relationships'] = $rc['relationships'];
            foreach($customer['relationships'] as $rid => $relationship) {
                $relationship = $relationship['relationship'];
                //
                // Check if this relationship needs to be reversed
                //
                if( $relationship['related_id'] == $args['customer_id'] ) {
                    if( isset($relationship_types[-$relationship['type']]) ) {
                        $customer['relationships'][$rid]['relationship']['type_name'] = $relationship_types[-$relationship['type']];
                    }
                    $customer['relationships'][$rid]['relationship']['type'] = -$relationship['type'];
                    $customer['relationships'][$rid]['relationship']['related_id'] = $relationship['customer_id'];
                }
            }
        }
    }

    //
    // Get the parent info
    if( ($modules['ciniki.customers']['flags']&0x200000) > 0 ) {
        if( $customer['parent_id'] != 0 ) {
            $strsql = "SELECT id, eid, display_name "
                . "FROM ciniki_customers "
                . "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $customer['parent_id']) . "' "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'parent');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['parent']) ) {
                $customer['parent'] = $rc['parent'];
            }
        }
        //
        // Get the number of children
        //
        $strsql = "SELECT 'children', COUNT(id) "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.parent_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['children']) ) {
            $customer['num_children'] = $rc['num']['children'];
        } else {
            $customer['num_children'] = 0;
        }
    }

    //
    // Get images
    //
    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $strsql = "SELECT id, name, image_id, webflags "
            . "FROM ciniki_customer_images "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'images', 'fname'=>'id', 'name'=>'image',
                'fields'=>array('id', 'name', 'image_id', 'webflags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            $customer['images'] = $rc['images'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            foreach($customer['images'] as $inum => $img) {
                if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                    $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], 
                        $img['image']['image_id'], 75);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $customer['images'][$inum]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                }
            }
        }
    }

    //
    // Get the membership seasons
    //
    if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
        $strsql = "SELECT ciniki_customer_seasons.id, "
            . "ciniki_customer_seasons.name, "
            . "ciniki_customer_seasons.flags, "
            . "IFNULL(ciniki_customer_season_members.id, 0) AS season_member_id, "
            . "IFNULL(ciniki_customer_season_members.status, '') AS status, "
            . "DATE_FORMAT(IFNULL(ciniki_customer_season_members.date_paid, ''), '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS date_paid "
            . "FROM ciniki_customer_season_members, ciniki_customer_seasons "
            . "WHERE ciniki_customer_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (ciniki_customer_seasons.flags&0x02) > 0 "
            . "AND ciniki_customer_seasons.id = ciniki_customer_season_members.season_id "
            . "AND ciniki_customer_season_members.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_customer_seasons.start_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'seasons', 'fname'=>'id',
                'fields'=>array('id', 'name', 'flags', 'season_member_id', 'status', 'date_paid')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['seasons']) ) { 
            foreach($rc['seasons'] as $season_id => $season) {
                $customer['season-' . $season_id . '-status'] = $season['status'];
                $customer['season-' . $season_id . '-date_paid'] = $season['date_paid'];
            }
        }
    }

    // 
    // Get customer subscriptions if module is enabled
    //
    if( isset($modules['ciniki.subscriptions']) ) {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, "
            . "ciniki_subscription_customers.id AS customer_subscription_id, "
            . "ciniki_subscriptions.description, ciniki_subscription_customers.status "
            . "FROM ciniki_subscriptions "
            . "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
                . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_subscription_customers.status = 10 "
            . "ORDER BY ciniki_subscriptions.name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
                'fields'=>array('id', 'name', 'description', 'customer_subscription_id', 'status')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['subscriptions']) ) {
            $customer['subscriptions'] = $rc['subscriptions'];
        }
    }


    $rsp = array('stat'=>'ok', 'customer'=>$customer);

    //
    // Check if all available member categories should be returned
    //
    if( ($modules['ciniki.customers']['flags']&0xC00224) > 0 && $args['tags'] == 'yes' ) {
        //
        // Get the available tags
        //
        $rsp['member_categories'] = array();
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsByType');
        $rc = ciniki_core_tagsByType($ciniki, 'ciniki.blog', $args['tnid'], 
            'ciniki_customer_tags', array());
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.87', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['types']) ) {
            $rsp['tag_types'] = $rc['types'];
        } else {
            $rsp['tag_types'] = array();
        }
    }

    //
    // Check if all available dealer categories should be returned
    //
    if( ($modules['ciniki.customers']['flags']&0x20) > 0
        && isset($args['dealer_categories']) && $args['dealer_categories'] == 'yes' 
        ) {
        //
        // Get the available tags
        //
        $rsp['dealer_categories'] = array();
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['tnid'], 
            'ciniki_customer_tags', 60);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.88', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['dealer_categories'] = $rc['tags'];
        }
    }

    //
    // Check if all available distributor categories should be returned
    //
    if( ($modules['ciniki.customers']['flags']&0x0200) > 0
        && isset($args['distributor_categories']) && $args['distributor_categories'] == 'yes' 
        ) {
        //
        // Get the available tags
        //
        $rsp['distributor_categories'] = array();
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.blog', $args['tnid'], 
            'ciniki_customer_tags', 40);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.89', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['distributor_categories'] = $rc['tags'];
        }
    }

    //
    // Check if we need to return the list of sales reps
    //
    if( ($modules['ciniki.customers']['flags']&0x2000) > 0
        && isset($args['sales_reps']) && $args['sales_reps'] == 'yes' 
        ) {
        //
        // Get the active sales reps
        //
        $strsql = "SELECT ciniki_users.id, ciniki_users.display_name "
            . "FROM ciniki_tenant_users, ciniki_users "
            . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_tenant_users.package = 'ciniki' "
            . "AND ciniki_tenant_users.permission_group = 'salesreps' "
            . "AND ciniki_tenant_users.status < 60 "
            . "AND ciniki_tenant_users.user_id = ciniki_users.id "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'salesreps', 'fname'=>'id', 'name'=>'user',
                'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['salesreps']) ) {
            $rsp['salesreps'] = $rc['salesreps'];
        } else {
            $rsp['salesreps'] = array();
        }
    }

    return $rsp;
}
?>
