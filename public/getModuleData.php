<?php
//
// Description
// -----------
// This method will return the detail of a customer along with data for customers from other modules.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_getModuleData($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'eid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'),
        'display_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Display Name'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.getModuleData', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Check either ID or code has been specified
    //
    if( (!isset($args['customer_id']) || $args['customer_id'] == '')
        && (!isset($args['eid']) || $args['eid'] == '') 
        && (!isset($args['display_name']) || $args['display_name'] == '') 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.90', 'msg'=>'You must specify either a customer or ID'));
    }

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

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//  $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Get the customer details and emails
    //
    $strsql = "SELECT ciniki_customers.id, eid, parent_id, type, callsign, prefix, first, middle, last, suffix, "
        . "display_name, company, department, title, "
        . "phone_home, phone_work, phone_cell, phone_fax, "
        . "status, status AS status_text, "
        . "member_status, member_status AS member_status_text, "
        . "member_lastpaid, membership_length, membership_type, "
        . "dealer_status, dealer_status AS dealer_status_text, "
        . "distributor_status, distributor_status AS distributor_status_text, "
        . "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, '%b %e, %Y') . "'), '') AS birthdate, "
        . "connection, language, "
        . "pricepoint_id, salesrep_id, tax_number, tax_location_id, "
        . "reward_level, sales_total, sales_total_prev, discount_percent, start_date, webflags, "
        . "notes "
        . "FROM ciniki_customers "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    // Check if user is only a salesrep and not a owner/employee
    if( isset($ciniki['tenant']['user']['perms']) && ($ciniki['tenant']['user']['perms']&0x07) == 0x04 ) {
        $strsql .= "AND salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
    }
    if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
        $strsql .= "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['eid']) && $args['eid'] != '' ) {
        $strsql .= "AND ciniki_customers.eid = '" . ciniki_core_dbQuote($ciniki, $args['eid']) . "' ";
    } elseif( isset($args['display_name']) && $args['display_name'] != '' ) {
        $strsql .= "AND ciniki_customers.display_name = '" . ciniki_core_dbQuote($ciniki, $args['display_name']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.91', 'msg'=>'You must specify either a customer or ID'));
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'eid', 'parent_id', 'type', 'callsign', 'prefix', 'first', 'middle', 'last', 'suffix', 'display_name', 
                'status', 'status_text',
                'phone_home', 'phone_work', 'phone_cell', 'phone_fax',
                'member_status', 'member_status_text', 'member_lastpaid', 'membership_length', 'membership_type',
                'dealer_status', 'dealer_status_text',
                'distributor_status', 'distributor_status_text',
                'company', 'department', 'title', 
                'notes', 'birthdate', 'connection', 'language', 'pricepoint_id', 'salesrep_id', 'tax_number', 'tax_location_id',
                'reward_level', 'sales_total', 'sales_total_prev', 'discount_percent', 'start_date', 'webflags'),
            'maps'=>array('status_text'=>$maps['customer']['status'],
                'member_status_text'=>$maps['customer']['member_status'],
                'dealer_status_text'=>$maps['customer']['dealer_status'],
                'distributor_status_text'=>$maps['customer']['distributor_status']),
            'utctotz'=>array(
                'member_lastpaid'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.customers.92', 'msg'=>'Invalid customer'));
    }
    if( isset($rc['customers']) && count($rc['customers']) > 1 ) {
        return array('stat'=>'ambiguous', 'err'=>array('code'=>'ciniki.customers.93', 'msg'=>'Multiple customers found'));
    }
    $customer = $rc['customers'][0]['customer'];
    $customer['discount_percent_text'] = (float)$customer['discount_percent'] . '%';
    $customer['addresses'] = array();
    $customer['subscriptions'] = array();

    //
    // Get the sales rep
    //
    if( ($modules['ciniki.customers']['flags']&0x2000) > 0 ) {
        $customer['salesrep_id_text'] = '';
        if( isset($customer['salesrep_id']) && $customer['salesrep_id'] > 0 ) {
            $strsql = "SELECT display_name "
                . "FROM ciniki_tenant_users, ciniki_users "
                . "WHERE ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $customer['salesrep_id']) . "' "
                . "AND ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_tenant_users.package = 'ciniki' "
                . "AND ciniki_tenant_users.permission_group = 'salesreps' "
                . "AND ciniki_tenant_users.user_id = ciniki_users.id "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['user']) ) {
                $customer['salesrep_id_text'] = $rc['user']['display_name'];
            }
        }
    }

    //
    // Get the tax location
    //
    if( isset($customer['tax_location_id']) && $customer['tax_location_id'] > 0 
        && isset($modules['ciniki.taxes'])
        && ($modules['ciniki.taxes']['flags']&0x01) > 0
        && ($modules['ciniki.customers']['flags']&0x2000) > 0 
        ) {
        $strsql = "SELECT ciniki_tax_locations.id, ciniki_tax_locations.code, ciniki_tax_locations.name, "
            . "ciniki_tax_rates.id AS rate_id, ciniki_tax_rates.name AS rate_name "
            . "FROM ciniki_tax_locations "
            . "LEFT JOIN ciniki_tax_rates ON ( "
                . "ciniki_tax_locations.id = ciniki_tax_rates.location_id "
                . "AND ciniki_tax_rates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_tax_rates.start_date < UTC_TIMESTAMP() "
                . "AND (ciniki_tax_rates.end_date = '0000-00-00 00:00:00' "
                    . "OR ciniki_tax_rates.end_date > UTC_TIMESTAMP()) "
                . ") "
            . "WHERE ciniki_tax_locations.id = '" . ciniki_core_dbQuote($ciniki, $customer['tax_location_id']) . "' "
            . "AND ciniki_tax_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
            array('container'=>'taxes', 'fname'=>'id',
                'fields'=>array('id', 'code', 'name')),
            array('container'=>'rates', 'fname'=>'rate_id',
                'fields'=>array('name'=>'rate_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['taxes'][$customer['tax_location_id']]) ) {
            $tax = $rc['taxes'][$customer['tax_location_id']];
            $customer['tax_location_id_text'] = '';
//          if( ($modules['ciniki.taxes']['flags']&0x02) && $tax['code'] != '' ) {
//              $customer['tax_location_id_text'] = $tax['code'] . ' - ';
//          }
            $customer['tax_location_id_text'] .= $tax['name'];
            $customer['tax_location_id_rates'] = '';
            if( isset($tax['rates']) ) {
                foreach($tax['rates'] as $rid => $rate) {
                    $customer['tax_location_id_rates'] .= ($customer['tax_location_id_rates']!=''?', ':'') . $rate['name'];
                }
            }
        }
    }

    //
    // Get the categories and tags for the customer
    //
    if( ($modules['ciniki.customers']['flags']&0xC00224) > 0 ) {
        $strsql = "SELECT tag_type, tag_name AS lists "
            . "FROM ciniki_customer_tags "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
                }
                if( $tags['tags']['tag_type'] == 20 ) {
                    $customer['customer_tags'] = $tags['tags']['lists'];
                }
                if( $tags['tags']['tag_type'] == 40 ) {
                    $customer['member_categories'] = $tags['tags']['lists'];
                }
                if( $tags['tags']['tag_type'] == 60 ) {
                    $customer['dealer_categories'] = $tags['tags']['lists'];
                }
                if( $tags['tags']['tag_type'] == 80 ) {
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
 //   }
    
    //
    // Get phones
    //
    if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x10000000) == 0 ) {
        $strsql = "SELECT id, phone_label, phone_number, flags "
            . "FROM ciniki_customer_phones "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
    $strsql = "SELECT id, customer_id, "
        . "name, url, webflags "
        . "FROM ciniki_customer_links "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'links', 'fname'=>'id', 'name'=>'link',
            'fields'=>array('id', 'name', 'url', 'webflags')),
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
//          . "IF(customer_id='" . ciniki_core_dbQuote($ciniki, $customer['id']) . "', related_id, customer_id) AS related_id, "
            . "date_started, date_ended, ciniki_customers.display_name, ciniki_customers.company "
            . "";
        $strsql .= "FROM ciniki_customer_relationships "
            . "LEFT JOIN ciniki_customers ON ("
                . "(ciniki_customer_relationships.customer_id <> '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                . "AND ciniki_customer_relationships.customer_id = ciniki_customers.id "
                . ") OR ("
                . "ciniki_customer_relationships.related_id <> '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                . "AND ciniki_customer_relationships.related_id = ciniki_customers.id "
                . ")) "
            . "WHERE ciniki_customer_relationships.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (ciniki_customer_relationships.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
                . "OR ciniki_customer_relationships.related_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
                if( $relationship['related_id'] == $customer['id'] ) {
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
    // If child account, get parent information
    //

    //
    // Get the parent information and any child customers
    //
    if( ($modules['ciniki.customers']['flags']&0x200000) > 0 ) {
        //
        // Get parent info
        //
        if( $customer['parent_id'] != 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
            $rc = ciniki_customers__customerDetails($ciniki, $args['tnid'], $customer['parent_id'], 
                array('phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']) ) {
                $customer['parent'] = array('id'=>$rc['customer']['id'], 'eid'=>$rc['customer']['eid'], 'display_name'=>$rc['customer']['display_name']);
                $customer['parent']['details'] = $rc['details'];
            }
/*          $strsql = "SELECT id, eid, display_name "
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
            }*/
        } 
        //
        // Get children
        //
        $strsql = "SELECT id, eid, display_name "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.parent_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_customers.last, ciniki_customers.first "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'children', 'fname'=>'id', 'name'=>'customer',
                'fields'=>array('id', 'eid', 'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['children']) ) {
            $customer['children'] = $rc['children'];
        } else {
            $customer['children'] = array();
        }
    }

    //
    // Get any membership seasons
    //
    if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
        $strsql = "SELECT ciniki_customer_seasons.id, "
            . "ciniki_customer_seasons.name, "
            . "ciniki_customer_seasons.flags, "
            . "IFNULL(ciniki_customer_season_members.id, 0) AS season_member_id, "
            . "IFNULL(ciniki_customer_season_members.status, '') AS status, "
            . "IFNULL(ciniki_customer_season_members.date_paid, '') AS date_paid "
            . "FROM ciniki_customer_season_members, ciniki_customer_seasons "
            . "WHERE ciniki_customer_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND (ciniki_customer_seasons.flags&0x02) > 0 "
            . "AND ciniki_customer_seasons.id = ciniki_customer_season_members.season_id "
            . "AND ciniki_customer_season_members.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_customer_seasons.start_date DESC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'seasons', 'fname'=>'id', 'name'=>'season',
                'fields'=>array('id', 'name', 'flags', 'season_member_id', 'status', 'date_paid')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['seasons']) ) {   
            $customer['seasons'] = $rc['seasons'];
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
                . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "') "
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

    //
    // Get the wineproduction appointments
    //
    if( isset($modules['ciniki.wineproduction']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wineproduction', 'hooks', 'appointments');
        $rc = ciniki_wineproduction_hooks_appointments($ciniki, $args['tnid'], array(
            'customer_id'=>$customer['id'],
            'status'=>'unbottled',
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['appointments']) ) {
            $customer['appointments'] = $rc['appointments'];
        } 

        //
        // Get the unbottled wineproduction orders
        //
        $strsql = "SELECT ciniki_wineproductions.id, "
            . "ciniki_wineproductions.invoice_number, "
            . "ciniki_products.name AS wine_name, "
            . "ciniki_wineproductions.status, "
            . "ciniki_wineproductions.status AS status_text, "
            . "DATE_FORMAT(ciniki_wineproductions.order_date, '%b %e, %Y') AS order_date, "
            . "DATE_FORMAT(ciniki_wineproductions.start_date, '%b %e, %Y') AS start_date, "
            . "DATE_FORMAT(ciniki_wineproductions.racking_date, '%b %e, %Y') AS racking_date, "
            . "DATE_FORMAT(ciniki_wineproductions.filtering_date, '%b %e, %Y') AS filtering_date, "
            . "DATE_FORMAT(ciniki_wineproductions.bottling_date, '%b %e, %Y') AS bottling_date, "
            . "DATE_FORMAT(IF(rack_date > 0, DATE_ADD(rack_date, INTERVAL (kit_length) DAY), "
                . "DATE_ADD(ciniki_wineproductions.start_date, INTERVAL kit_length WEEK)), '%b %e, %Y') AS approx_filtering_date "
            . "FROM ciniki_wineproductions "
            . "LEFT JOIN ciniki_products ON (ciniki_wineproductions.product_id = ciniki_products.id "
                . "AND ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_wineproductions.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND ciniki_wineproductions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_wineproductions.status < 60 "
            . "ORDER BY ciniki_wineproductions.order_date DESC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.wineproductions', array(
            array('container'=>'orders', 'fname'=>'id', 'name'=>'order',
                'fields'=>array('id', 'invoice_number', 'wine_name', 'status', 'status_text',
                    'order_date', 'start_date', 'racking_date', 'filtering_date', 'bottling_date',
                    'approx_filtering_date'),
                'maps'=>array('status_text'=>array(
                    '10'=>'Entered',
                    '20'=>'Started',
                    '30'=>'Racked',
                    '40'=>'Filtered',
                    '60'=>'Bottled',
                    ))),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['orders']) ) {
            $customer['currentwineproduction'] = $rc['orders'];
        }

        //
        // Get the bottled wineproduction orders
        //
        $strsql = "SELECT ciniki_wineproductions.id, "
            . "ciniki_wineproductions.invoice_number, "
            . "ciniki_products.name AS wine_name, "
            . "ciniki_wineproductions.status, "
            . "ciniki_wineproductions.status AS status_text, "
            . "DATE_FORMAT(ciniki_wineproductions.order_date, '%b %e, %Y') AS order_date, "
            . "DATE_FORMAT(ciniki_wineproductions.start_date, '%b %e, %Y') AS start_date, "
            . "DATE_FORMAT(ciniki_wineproductions.racking_date, '%b %e, %Y') AS racking_date, "
            . "DATE_FORMAT(ciniki_wineproductions.filtering_date, '%b %e, %Y') AS filtering_date, "
            . "DATE_FORMAT(ciniki_wineproductions.bottle_date, '%b %e, %Y') AS bottle_date, "
            . "DATE_FORMAT(IF(rack_date > 0, DATE_ADD(rack_date, INTERVAL (kit_length) DAY), "
                . "DATE_ADD(ciniki_wineproductions.start_date, INTERVAL kit_length WEEK)), '%b %e, %Y') AS approx_filtering_date "
            . "FROM ciniki_wineproductions "
            . "LEFT JOIN ciniki_products ON (ciniki_wineproductions.product_id = ciniki_products.id "
                . "AND ciniki_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_wineproductions.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND ciniki_wineproductions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_wineproductions.status = 60 "
            . "ORDER BY ciniki_wineproductions.order_date DESC "
            . "LIMIT 11 "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.wineproductions', array(
            array('container'=>'orders', 'fname'=>'id', 'name'=>'order',
                'fields'=>array('id', 'invoice_number', 'wine_name', 'status', 'status_text',
                    'order_date', 'start_date', 'racking_date', 'filtering_date', 'bottle_date',
                    'approx_filtering_date'),
                'maps'=>array('status_text'=>array(
                    '10'=>'Entered',
                    '20'=>'Started',
                    '30'=>'Racked',
                    '40'=>'Filtered',
                    '60'=>'Bottled',
                    ))),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['orders']) ) {
            $customer['pastwineproduction'] = $rc['orders'];
        }
    }

    //
    // Check for invoices for the customer
    //
    if( isset($modules['ciniki.sapos']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'customerInvoices');
        $rc = ciniki_sapos_hooks_customerInvoices($ciniki, $args['tnid'], array(
            'customer_id'=>$customer['id'], 
            'limit'=>11));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['types']) ) {
            foreach($rc['types'] as $tid => $type) {
                if( $type['type']['type'] == '10' ) {
                    $customer['invoices'] = $type['type']['invoices'];
                } elseif( $type['type']['type'] == '20' ) {
                    $customer['carts'] = $type['type']['invoices'];
                } elseif( $type['type']['type'] == '30' ) {
                    $customer['pos'] = $type['type']['invoices'];
                } elseif( $type['type']['type'] == '40' ) {
                    $customer['orders'] = $type['type']['invoices'];
                }
            }
        }
        //
        // Make sure at least a blank array exist for each type of invoice
        //
        if( ($modules['ciniki.sapos']['flags']&0x01) && !isset($customer['invoices']) ) {
            $customer['invoices'] = array();
        }
        if( ($modules['ciniki.sapos']['flags']&0x08) && !isset($customer['carts']) ) {
            $customer['carts'] = array();
        }
        if( ($modules['ciniki.sapos']['flags']&0x10) && !isset($customer['pos']) ) {
            $customer['pos'] = array();
        }
        if( ($modules['ciniki.sapos']['flags']&0x20) && !isset($customer['orders']) ) {
            $customer['orders'] = array();
        }
    }

    //
    // Check for First Add Certifications
    //
    if( isset($modules['ciniki.fatt']) && ($modules['ciniki.fatt']['flags']&0x10) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'hooks', 'customerCerts');
        $rc = ciniki_fatt_hooks_customerCerts($ciniki, $args['tnid'], array(
            'customer_id'=>$customer['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['curcerts']) ) {
            $customer['curcerts'] = $rc['curcerts'];
        } else {
            $customer['curcerts'] = array();
        }
        if( isset($rc['pastcerts']) ) {
            $customer['pastcerts'] = $rc['pastcerts'];
        } else {
            $customer['pastcerts'] = array();
        }
    }

    return array('stat'=>'ok', 'customer'=>$customer);
}
?>
