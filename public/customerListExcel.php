<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the customers belong to.
//
// Returns
// -------
// A word document
//
function ciniki_customers_customerListExcel(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'columns'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Columns'), 
        'memberlist'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Members Only'),
        'subscription_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subscription'),
        'select_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'name'=>'Categories'),
        'select_products'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'name'=>'Products'),
        'select_member_status'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Member Status'),
        'select_lifetime'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Include Lifetime'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerListExcel', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $args['tnid'], 'ciniki.customers', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.556', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();
    
    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Check if we are to include ids
    //
    $ids = 'no';
    $noemails = 'include';
    foreach($args['columns'] as $column) {
        if( $column == 'ids' ) { $ids = 'yes'; }
        if( $column == 'optionnoemails' ) { $noemails = 'exclude'; }
    }

    $selector_sql = '';
    if( isset($args['select_member_status']) && count($args['select_member_status']) > 0 ) {
        $selector_sql = "AND ciniki_customers.member_status IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['select_member_status']) . ") ";
    }

    //
    // If seasons is enabled and requested, get the requested season names
    //
    $season_ids = array();
    $seasons = array();
    if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x02000000) > 0 ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_customer_seasons "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//            . "AND id IN (" . ciniki_core_dbQuoteIDs($ciniki, $season_ids) . ") "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'seasons', 'fname'=>'id', 
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $season_members_sql = '';
        if( isset($rc['seasons']) ) {
            $seasons = $rc['seasons'];
            foreach($seasons as $season) {
                // 
                // Check each season to see if a list of statuses was passed
                //
                if( isset($ciniki['request']['args']['select_season_' . $season['id']]) && $ciniki['request']['args']['select_season_' . $season['id']] != '' ) {
                    $ids = explode(',', $ciniki['request']['args']['select_season_' . $season['id']]);
                    if( count($ids) > 0 ) {
                        $season_members_sql .= ($season_members_sql != '' ? 'OR ' : '')
                        . "(season_id = '" . ciniki_core_dbQuote($ciniki, $season['id']) . "' "
                        . "AND status IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                        . ") ";
                    }
                }
            }
            if( $season_members_sql != '' ) {
                $strsql = "SELECT DISTINCT customer_id "
                    . "FROM ciniki_customer_season_members "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND ("
                    . $season_members_sql
                    . ") ";
                $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.customers', 'customers', 'customer_id');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['customers']) && count($rc['customers']) > 0 ) {
                    $restrict_customer_ids = $rc['customers'];
                }
            }
        }
        foreach($args['columns'] as $column) {
            if( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
                $season_ids[] = $matches[1];
            }
        }
        if( count($season_ids) > 0 ) {
            $strsql = "SELECT season_id, customer_id, status, "
                . "IF(date_paid > 0, DATE_FORMAT(date_paid, '%M %d, %Y'), '') AS date_paid "
                . "FROM ciniki_customer_season_members "
                . "WHERE ciniki_customer_season_members.season_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $season_ids) . ") "
                . "AND ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY season_id, customer_id "
                . "";
            $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
                array('container'=>'seasons', 'fname'=>'season_id', 'fields'=>array('season_id')),
                array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('id'=>'customer_id', 'status', 'date_paid')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['seasons']) ) {
                foreach($seasons as $sid => $season) {
                    if( isset($rc['seasons'][$sid]['customers']) ) {
                        $seasons[$sid]['customers'] = $rc['seasons'][$sid]['customers'];
                    }
                }
            }
        }
    }

    //
    // Check if categories enabled
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x400000) && isset($args['select_categories']) && count($args['select_categories']) > 0 ) {
        $strsql = "SELECT DISTINCT customer_id "
            . "FROM ciniki_customer_tags "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND tag_type = 10 "
            . "AND tag_name IN (" . ciniki_core_dbQuoteList($ciniki, $args['select_categories']) . ") "
            . "";
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.customers', 'customers', 'customer_id');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) && count($rc['customers']) > 0 ) {
            if( isset($restrict_customer_ids) ) {
                $restrict_customer_ids = array_intersect($restrict_customer_ids, $rc['customers']);
            } else {
                $restrict_customer_ids = $rc['customers'];
            }
        }
    }

    //
    // If subscriptions are enabled
    //
    $subscription_ids = array();
    $subscriptions = array();
    if( isset($ciniki['tenant']['modules']['ciniki.subscriptions']) ) {
        foreach($args['columns'] as $column) {
            if( preg_match("/^subscription-([0-9]+)$/", $column, $matches) ) {
                $subscription_ids[] = $matches[1];
            }
        }
        if( count($subscription_ids) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'subscriptionCustomers');
            $rc = ciniki_subscriptions_hooks_subscriptionCustomers($ciniki, $args['tnid'], 
                array('subscription_ids'=>$subscription_ids));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $subscriptions = $rc['subscriptions'];
        }
    }

    //
    // Load tax locations
    //
    $tax_locations = array();
    $strsql = "SELECT id, name, code "
        . "FROM ciniki_tax_locations "
        . "WHERE ciniki_tax_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
        array('container'=>'locations', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'code')),
        )); 
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['locations']) ) {
        $tax_locations = $rc['locations'];
    }

    //
    // Load the categories
    //
    $member_categories = array();
    $strsql = "SELECT customer_id, "
        . "ciniki_customer_tags.tag_name AS member_categories "
        . "FROM ciniki_customer_tags "
        . "WHERE ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customer_tags.tag_type = 40 "
        . "ORDER BY ciniki_customer_tags.customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 
            'fields'=>array('member_categories'),
            'dlists'=>array('member_categories'=>', ')),
        )); 
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $member_categories = $rc['customers'];
    }

    //
    // Load the phones
    //
    $phones = array();
    $num_phone_columns = 1;
    $strsql = "SELECT customer_id, "
        . "ciniki_customer_phones.id, "
        . "ciniki_customer_phones.phone_label, "
        . "ciniki_customer_phones.phone_number, "
        . "CONCAT_WS(': ', ciniki_customer_phones.phone_label, "
            . "ciniki_customer_phones.phone_number) AS phones "
        . "FROM ciniki_customer_phones "
        . "WHERE ciniki_customer_phones.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_customer_phones.customer_id, ciniki_customer_phones.phone_label "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 
            'fields'=>array('phones'),
            'dlists'=>array('phones'=>', ')),
        array('container'=>'split_phones', 'fname'=>'id', 'fields'=>array('id', 'phone_label', 'phone_number')),
        )); 
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $phone_columns = array();
    if( isset($rc['customers']) ) {
        $phones = $rc['customers'];
        foreach($phones as $phone) {
            if( isset($phone['split_phones']) && count($phone['split_phones']) > $num_phone_columns ) {
                $num_phone_columns = count($phone['split_phones']);
            }
            if( isset($phone['split_phones']) ) {
                foreach($phone['split_phones'] as $p) {
                    if( !in_array(ucwords($p['phone_label']), $phone_columns) ) {
                        $phone_columns[] = ucwords($p['phone_label']);
                    }
                }
            }

        }
    } 

    //
    // Load the addresses
    //
    $addresses = array();
    $num_address_columns = 1;
    $strsql = "SELECT customer_id, "
        . "ciniki_customer_addresses.id, "
        . "ciniki_customer_addresses.flags, "
        . "ciniki_customer_addresses.flags AS type, "
        . "ciniki_customer_addresses.address1, "
        . "ciniki_customer_addresses.address2, "
        . "ciniki_customer_addresses.city, "
        . "ciniki_customer_addresses.province, "
        . "ciniki_customer_addresses.postal, "
        . "CONCAT_WS(', ', ciniki_customer_addresses.address1, "
            . "ciniki_customer_addresses.address2, "
            . "ciniki_customer_addresses.city, "
            . "ciniki_customer_addresses.province, "
            . "ciniki_customer_addresses.postal) AS addresses "
        . "FROM ciniki_customer_addresses "
        . "WHERE ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_customer_addresses.customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 
            'fields'=>array('addresses'),
            'dlists'=>array('addresses'=>'/')),
        array('container'=>'split_addresses', 'fname'=>'id', 
            'fields'=>array('id', 'flags', 'type', 'address1', 'address2', 'city', 'province', 'postal'),
            'flags'=>array('type'=>$maps['address']['flags_shortcodes'])
            ),
        )); 
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $addresses = $rc['customers'];
        foreach($addresses as $address) {
            if( isset($address['split_addresses']) && count($address['split_addresses']) > $num_address_columns ) {
                $num_address_columns = count($address['split_addresses']);
            }
        }
    }

    //
    // Load the emails
    //
    $emails = array();
    $num_email_columns = 1;
    $strsql = "SELECT customer_id, "
        . "ciniki_customer_emails.id, "
        . "ciniki_customer_emails.email, "
        . "ciniki_customer_emails.email AS emails "
        . "FROM ciniki_customer_emails "
        . "WHERE ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( $noemails == 'exclude' ) {
        $strsql .= "AND (ciniki_customer_emails.flags&0x10) = 0 ";
    }
    $strsql .= "ORDER BY ciniki_customer_emails.customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 
            'fields'=>array('emails'),
            'dlists'=>array('emails'=>', ')),
        array('container'=>'split_emails', 'fname'=>'id', 'fields'=>array('id', 'email')),
        )); 
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $emails = $rc['customers'];
        foreach($emails as $email) {
            if( isset($email['split_emails']) && count($email['split_emails']) > $num_email_columns ) {
                $num_email_columns = count($email['split_emails']);
            }
        }
    }

    //
    // Load the links
    //
    $links = array();
    $num_link_columns = 1;
    $strsql = "SELECT customer_id, "
        . "ciniki_customer_links.id, "
        . "ciniki_customer_links.name, "
        . "ciniki_customer_links.url, "
        . "ciniki_customer_links.url AS links "
        . "FROM ciniki_customer_links "
        . "WHERE ciniki_customer_links.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_customer_links.customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 
            'fields'=>array('links'),
            'dlists'=>array('links'=>', ')),
        array('container'=>'split_links', 'fname'=>'id', 'fields'=>array('id', 'name', 'url')),
        )); 
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $links = $rc['customers'];
        foreach($links as $link) {
            if( isset($link['split_links']) && count($link['split_links']) > $num_link_columns ) {
                $num_link_columns = count($link['split_links']);
            }
        }
    }

    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();

    if( isset($args['membersonly']) && $args['membersonly'] == 'yes' ) {
        $strsql = "SELECT ciniki_customers.id, eid, callsign, prefix, first, middle, last, suffix, "
            . "company, department, title, display_name, "
            . "ciniki_customers.type, "
            . "ciniki_customers.status, "
            . "ciniki_customers.member_status, "
            . "ciniki_customers.member_lastpaid, "
            . "ciniki_customers.member_expires, "
            . "ciniki_customers.membership_length, "
            . "ciniki_customers.membership_type, "
            . "IF(ciniki_customers.primary_image_id>0,'yes','no') AS primary_image, "
            . "ciniki_customers.primary_image_caption, "
            . "ciniki_customers.short_description, "
            . "ciniki_customers.full_bio, "
            . "IF((ciniki_customers.webflags&0x07)>0,'Visible','Hidden') AS visible, "
            . "ciniki_customers.dealer_status, "
            . "ciniki_customers.distributor_status, "
            . "ciniki_customers.connection, "
            . "ciniki_customers.language, "
            . "ciniki_customers.tax_number, "
            . "ciniki_customers.tax_location_id, "
            . "ciniki_customers.start_date, "
            . "ciniki_customers.other1, "
            . "ciniki_customers.other2, "
            . "ciniki_customers.other3, "
            . "ciniki_customers.other4, "
            . "ciniki_customers.other5, "
            . "ciniki_customers.other6, "
            . "ciniki_customers.other7, "
            . "ciniki_customers.other8, "
            . "ciniki_customers.other9, "
            . "ciniki_customers.other10, "
            . "ciniki_customers.other11, "
            . "ciniki_customers.other12, "
            . "'' AS member_categories, "
            . "'' AS phones, "
            . "'' AS addresses, "
            . "'' AS links, "
            . "'' AS emails "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . $selector_sql
            . "ORDER BY ciniki_customers.sort_name "
            . "";
    } elseif( isset($args['subscription_id']) && $args['subscription_id'] != '' && $args['subscription_id'] > 0 ) {
        $strsql = "SELECT ciniki_customers.id, eid, callsign, prefix, first, middle, last, suffix, "
            . "company, department, title, display_name, "
            . "ciniki_customers.type, "
            . "ciniki_customers.status, "
            . "ciniki_customers.member_status, "
            . "ciniki_customers.member_lastpaid, "
            . "ciniki_customers.member_expires, "
            . "ciniki_customers.membership_length, "
            . "ciniki_customers.membership_type, "
            . "IF(ciniki_customers.primary_image_id>0,'yes','no') AS primary_image, "
            . "ciniki_customers.primary_image_caption, "
            . "ciniki_customers.short_description, "
            . "ciniki_customers.full_bio, "
            . "IF((ciniki_customers.webflags&0x07)>0,'Visible','Hidden') AS visible, "
            . "ciniki_customers.dealer_status, "
            . "ciniki_customers.distributor_status, "
            . "ciniki_customers.connection, "
            . "ciniki_customers.language, "
            . "ciniki_customers.tax_number, "
            . "ciniki_customers.tax_location_id, "
            . "ciniki_customers.start_date, "
            . "ciniki_customers.other1, "
            . "ciniki_customers.other2, "
            . "ciniki_customers.other3, "
            . "ciniki_customers.other4, "
            . "ciniki_customers.other5, "
            . "ciniki_customers.other6, "
            . "ciniki_customers.other7, "
            . "ciniki_customers.other8, "
            . "ciniki_customers.other9, "
            . "ciniki_customers.other10, "
            . "ciniki_customers.other11, "
            . "ciniki_customers.other12, "
            . "'' AS member_categories, "
            . "'' AS phones, "
            . "'' AS addresses, "
            . "'' AS links, "
            . "'' AS emails "
            . "FROM ciniki_subscription_customers, ciniki_customers "
            . "WHERE ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
            . "AND ciniki_subscription_customers.status = 10 "
            . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_subscription_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . $selector_sql
            . "ORDER BY ciniki_customers.sort_name "
            . "";
    } else {
        $strsql = "SELECT ciniki_customers.id, eid, callsign, prefix, first, middle, last, suffix, "
            . "company, department, title, display_name, "
            . "ciniki_customers.type, "
            . "ciniki_customers.status, "
            . "ciniki_customers.member_status, "
            . "ciniki_customers.member_lastpaid, "
            . "ciniki_customers.member_expires, "
            . "ciniki_customers.membership_length, "
            . "ciniki_customers.membership_type, "
            . "IF(ciniki_customers.primary_image_id>0,'yes','no') AS primary_image, "
            . "ciniki_customers.primary_image_caption, "
            . "ciniki_customers.short_description, "
            . "ciniki_customers.full_bio, "
            . "IF((ciniki_customers.webflags&0x07)>0,'Visible','Hidden') AS visible, "
            . "ciniki_customers.dealer_status, "
            . "ciniki_customers.distributor_status, "
            . "ciniki_customers.connection, "
            . "ciniki_customers.language, "
            . "ciniki_customers.tax_number, "
            . "ciniki_customers.tax_location_id, "
            . "ciniki_customers.start_date, "
            . "ciniki_customers.other1, "
            . "ciniki_customers.other2, "
            . "ciniki_customers.other3, "
            . "ciniki_customers.other4, "
            . "ciniki_customers.other5, "
            . "ciniki_customers.other6, "
            . "ciniki_customers.other7, "
            . "ciniki_customers.other8, "
            . "ciniki_customers.other9, "
            . "ciniki_customers.other10, "
            . "ciniki_customers.other11, "
            . "ciniki_customers.other12, "
            . "'' AS member_categories, "
            . "'' AS phones, "
            . "'' AS addresses, "
            . "'' AS links, "
            . "'' AS emails "
            . "FROM ciniki_customers ";
        if( isset($args['select_products']) && count($args['select_products']) > 0 && $args['select_products'][0] != '' ) {
            $strsql .= "INNER JOIN ciniki_customer_product_purchases AS purchases ON (" 
                . "ciniki_customers.id = purchases.customer_id "
                . "AND purchases.product_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['select_products']) . ") "
                . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $strsql .= ") ";
            $strsql .= "INNER JOIN ciniki_customer_products AS products ON ("
                . "purchases.product_id = products.id "
                . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            if( isset($args['select_member_status'][0]) && $args['select_member_status'][0] == 10
                && !isset($args['select_member_status'][1]) 
                ) {
                $strsql .= "AND (products.type = 20 OR purchases.end_date >= NOW()) ";
            }
            $strsql .= ") ";
        }
        $strsql .= "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . $selector_sql
            . "ORDER BY ciniki_customers.sort_name "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'eid', 'status', 'callsign', 'prefix', 'first', 'middle', 'last', 'suffix',
                'company', 'display_name', 'type', 'visible', 
                'member_status', 'member_lastpaid', 'member_expires', 'membership_length', 'membership_type', 'member_categories',
                'dealer_status', 'distributor_status',
                'connection', 'language', 'tax_number', 'tax_location_id', 
                'start_date',
                'phones', 'emails', 'addresses', 'links',
                'other1', 'other2', 'other3', 'other4', 'other5', 'other6', 'other7', 'other8', 'other9', 'other10', 'other11', 'other12',
                'primary_image', 'primary_image_caption', 'short_description', 'full_bio'),
            'maps'=>array(
                'type'=>array('1'=>'Individual', '2'=>'Business'),
                'status'=>$maps['customer']['status'], //array('10'=>'Active', '60'=>'Former'),
                'member_status'=>$maps['customer']['member_status'], //array('10'=>'Active', '60'=>'Former'),
                'membership_length'=>$maps['customer']['membership_length'], // array('10'=>'Monthly', '20'=>'Yearly', '60'=>'Lifetime'),
                'membership_type'=>$maps['customer']['membership_type'], // array('10'=>'Regular', '20'=>'Complimentary', '30'=>'Reciprocal'),
                'dealer_status'=>$maps['customer']['dealer_status'], //array('10'=>'Active', '60'=>'Former'),
                'distributor_status'=>$maps['customer']['distributor_status'], //array('10'=>'Active', '60'=>'Former'),
                ),
            'dlists'=>array('emails'=>', ')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        $customers = array();
    }

    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    //
    // Add headers
    //
    $row = 1;
    $col = 0;
    foreach($args['columns'] as $column) {
        $value = '';
        switch($column) {
            case 'ids': $value = 'ID'; break;
            case 'eid': $value = 'EID'; break;
            case 'status': $value = 'Status'; break;
            case 'callsign': $value = 'Callsign'; break;
            case 'prefix': $value = 'Prefix'; break;
            case 'first': $value = 'First'; break;
            case 'middle': $value = 'Middle'; break;
            case 'last': $value = 'Last'; break;
            case 'suffix': $value = 'Suffix'; break;
            case 'company': $value = 'Company'; break;
            case 'department': $value = 'Department'; break;
            case 'title': $value = 'Title'; break;
            case 'display_name': $value = 'Name'; break;
            case 'type': $value = 'Type'; break;
            case 'visible': $value = 'Visible'; break;
            case 'member_status': $value = 'Member'; break;
            case 'member_lastpaid': $value = 'Last Paid'; break;
            case 'member_expires': $value = 'Expires'; break;
            case 'membership_length': $value = 'Length'; break;
            case 'membership_type': $value = 'Type'; break;
            case 'member_categories': $value = 'Categories'; break;
            case 'dealer_status': $value = 'Dealer Status'; break;
            case 'tax_number': $value = 'Tax Number'; break;
            case 'tax_location_name': $value = 'Tax'; $tax_code = 'yes'; break;
            case 'tax_location_code': $value = 'Tax Code'; $tax_code = 'yes'; break;
            case 'start_date': $value = 'Start Date'; break;
            case 'distributor_status': $value = 'Distributor Status'; break;
            case 'phones': $value = 'Phones'; break;
            case 'emails': $value = 'Emails'; break;
            case 'addresses': $value = 'Addresses'; break;
            case 'links': $value = 'Websites'; break;
            case 'notes': $value = 'Notes'; break;
            case 'primary_image': $value = 'Image'; break;
            case 'primary_image_caption': $value = 'Image Caption'; break;
            case 'short_description': $value = 'Short Bio'; break;
            case 'full_bio': $value = 'Full Bio'; break;
        }
        if( $column == 'split_phones' && $num_phone_columns > 0 ) {
            for($i=0;$i<$num_phone_columns;$i++) {
                if( $ids == 'yes' ) { 
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'PID ' . ($i+1), false); 
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone ' . ($i+1), false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone ' . ($i+1) . ' Number', false);
                } else {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone Number', false);
                }
            }
        }
        elseif( $column == 'split_phone_labels' && count($phone_columns) > 0 ) {
            foreach($phone_columns as $phone_column) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $phone_column, false); 
            }
        }
        elseif( $column == 'split_addresses' && $num_address_columns > 0 ) {
            for($i=0;$i<$num_address_columns;$i++) {
                if( $ids == 'yes' ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'AID ' . ($i+1), false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address ' . ($i+1) . ' Type', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address ' . ($i+1) . ' 1', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address ' . ($i+1) . ' 2', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'City' . ($i+1) . ' ', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Province' . ($i+1) . ' ', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Postal' . ($i+1) . ' ', false);
                } else {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address Type', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address 1', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Address 2', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'City', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Province', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Postal', false);
                }
            }
        }
        elseif( $column == 'split_emails' && $num_email_columns > 0 ) {
            for($i=0;$i<$num_email_columns;$i++) {
                if( $ids == 'yes' ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'EID ' . ($i+1), false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email ' + ($i+1), false);
                } else {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email' . ($num_email_columns > 1 ? ' ' . ($i+1):''), false);
                }
            }
        } 
        elseif( $column == 'split_links' && $num_link_columns > 0 ) {
            for($i=0;$i<$num_link_columns;$i++) {
                if( $ids == 'yes' ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'LID ' . ($i+1), false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Link ' + ($i+1) . ' Name', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Link ' + ($i+1) . ' URL', false);
                } else {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Link Name', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Link URL', false);
                }
            }
        } 
        else {
            if( preg_match("/other([0-9]+)/", $column, $m) ) {
                if( isset($settings['other-' . $m[1] . '-label']) && $settings['other-' . $m[1] . '-label'] != '' ) {
                    $value = $settings['other-' . $m[1] . '-label'];
                }
            }
            if( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
                if( isset($seasons[$matches[1]]) ) {
                    $value = $seasons[$matches[1]]['name'] . ' Status';
                }
            }
            if( preg_match("/^season-datepaid-([0-9]+)$/", $column, $matches) ) {
                if( isset($seasons[$matches[1]]) ) {
                    $value = $seasons[$matches[1]]['name'] . ' Date Paid';
                }
            }
            if( preg_match("/^subscription-([0-9]+)$/", $column, $matches) ) {
                if( isset($subscriptions[$matches[1]]) ) {
                    $value = $subscriptions[$matches[1]]['name'];
                }
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $value, false);
            $col++;
        }
    }
//  $objPHPExcelWorksheet->getStyle('A1:' . PHPExcel_Cell::stringFromColumnIndex($col) chr(65+$col-1) . '1')->getFont()->setBold(true);
    $objPHPExcelWorksheet->getStyle('A1:' . PHPExcel_Cell::stringFromColumnIndex($col) . '1')->getFont()->setBold(true);
    $objPHPExcelWorksheet->freezePane('A2');

    $row++;

    foreach($rc['customers'] as $customer) {
        $customer = $customer['customer'];

        if( isset($restrict_customer_ids) 
            && (!isset($args['select_lifetime']) || $args['select_lifetime'] != 'yes' || ($args['select_lifetime'] == 'yes' && $customer['membership_length'] != 'Lifetime'))
            && !in_array($customer['id'], $restrict_customer_ids) ) {
            continue;
        }
        $col = 0;
        foreach($args['columns'] as $column) {
            if( $column == 'ids' ) {
                $value = $customer['id'];
            }
            elseif( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
                $value = '';
                if( isset($seasons[$matches[1]]['customers'][$customer['id']]['status'])
                    && $seasons[$matches[1]]['customers'][$customer['id']]['status'] > 0 
                    && isset($maps['season_member']['status'][$seasons[$matches[1]]['customers'][$customer['id']]['status']]) 
                    ) {
                    $value = $maps['season_member']['status'][$seasons[$matches[1]]['customers'][$customer['id']]['status']];
                } else {
                    $col++;
                    continue;
                }
            } 
            elseif( preg_match("/^season-datepaid-([0-9]+)$/", $column, $matches) ) {
                $value = '';
                if( isset($seasons[$matches[1]]['customers'][$customer['id']]['date_paid'])) {
                    $value = $seasons[$matches[1]]['customers'][$customer['id']]['date_paid'];
                } else {
                    $col++;
                    continue;
                }
            } 
            elseif( preg_match("/^subscription-([0-9]+)$/", $column, $matches) ) {
                $value = '';
                if( isset($subscriptions[$matches[1]]['customers'][$customer['id']]['status_text'])
                    && $subscriptions[$matches[1]]['customers'][$customer['id']]['status_text'] != ''
                    ) {
                    $value = $subscriptions[$matches[1]]['customers'][$customer['id']]['status_text'];
                } else {
                    $col++;
                    continue;
                }
            } 
            elseif( $column == 'member_categories' && isset($member_categories[$customer['id']]['member_categories']) ) {
                $value = $member_categories[$customer['id']]['member_categories'];
            } 
            elseif( $column == 'phones' && isset($phones[$customer['id']]['phones']) ) {
                $value = $phones[$customer['id']]['phones'];
            } 
            elseif( $column == 'addresses' && isset($addresses[$customer['id']]['addresses']) ) {
                $value = preg_replace('/, ,/', ',', $addresses[$customer['id']]['addresses']);
            } 
            elseif( $column == 'emails' && isset($emails[$customer['id']]['emails']) ) {
                $value = preg_replace('/, ,/', ',', $emails[$customer['id']]['emails']);
            } 
            elseif( $column == 'links' && isset($links[$customer['id']]['links']) ) {
                $value = $links[$customer['id']]['links'];
            } 
            elseif( $column == 'tax_location_code' && isset($tax_locations[$customer['tax_location_id']]) ) {
                $value = $tax_locations[$customer['tax_location_id']]['code'];
            } 
            elseif( $column == 'split_phones' ) {
                $i = 0;
                if( isset($phones[$customer['id']]['split_phones']) ) {
                    foreach($phones[$customer['id']]['split_phones'] as $phone) {
                        if( $ids == 'yes' ) { $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $phone['id'], false); }
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $phone['phone_label'], false);
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $phone['phone_number'], false);
                        $i++;
                    }
                }
                while($i<$num_phone_columns) { $col+=(2+($ids=='yes'?1:0)); $i++; }
                continue;
            } 
            elseif( $column == 'split_phone_labels' ) {
                foreach($phone_columns as $label) {
                    if( isset($phones[$customer['id']]['split_phones']) ) {
                        foreach($phones[$customer['id']]['split_phones'] as $phone) {
                            if( ucwords($phone['phone_label']) == $label ) {
                                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $phone['phone_number'], false);
                            }
                        }
                    }

                    $col++;
                }
                continue;
            } 
            elseif( $column == 'split_addresses' ) {
                $i = 0;
                if( isset($addresses[$customer['id']]['split_addresses']) ) {
                    foreach($addresses[$customer['id']]['split_addresses'] as $address) {
                        if( $ids == 'yes' ) { $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['id'], false); }
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, preg_replace('/, /', '', $address['type']), false);
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['address1'], false);
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['address2'], false);
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['city'], false);
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['province'], false);
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $address['postal'], false);
                        $i++;
                    }
                }
                while($i<$num_address_columns) { ($col+=6+($ids=='yes'?1:0)); $i++; }
                continue;
            } 
            elseif( $column == 'split_emails' ) {
                $i = 0;
                if( isset($emails[$customer['id']]['split_emails']) ) {
                    foreach($emails[$customer['id']]['split_emails'] as $email) {
                        if( $ids == 'yes' ) { $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $email['id'], false); }
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $email['email'], false);
                        $i++;
                    }
                }
                while($i<$num_email_columns) { ($col+=1+($ids=='yes'?1:0)); $i++; }
                continue;
            } elseif( $column == 'split_links' ) {
                $i = 0;
                if( isset($links[$customer['id']]['split_links']) ) {
                    foreach($links[$customer['id']]['split_links'] as $link) {
                        if( $ids == 'yes' ) { $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $link['id'], false); }
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $link['name'], false);
                        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $link['url'], false);
                        $i++;
                    }
                }
                while($i<$num_link_columns) { ($col+=2+($ids=='yes'?1:0)); $i++; }
                continue;
            } elseif( !isset($customer[$column]) ) {
                $col++;
                continue;
            } else {
                $value = $customer[$column];
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $value, false);
            $col++;
        }
        $row++;
    }

    $col = 0;
    PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
//  foreach($args['columns'] as $column) {
//      $objPHPExcelWorksheet->getColumnDimension(chr(65+$col))->setAutoSize(true);
//      $col++;
//  }

    //
    // Redirect output to a client’s web browser (Excel)
    //
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="export.xls"');
    header('Cache-Control: max-age=0');

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    return array('stat'=>'exit');
}
?>
