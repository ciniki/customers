<?php
//
// Description
// -----------
// Return the report of new members
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the birthdays for.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// days:                The number of days past to look for new members.
// 
// Returns
// -------
//
function ciniki_customers_reporting_blockRenewedMembers(&$ciniki, $tnid, $args) {
    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $date_text = '';
    if( isset($args['months']) && $args['months'] != '' && $args['months'] > 0 && $args['months'] < 366 ) {
        $months = $args['months'];
        $date_text .= $months . ' month' . ($months > 1 ? 's' : '');
    } else {
        $months = 0;
    }
    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 && $args['days'] < 366 ) {
        $days = $args['days'];
    } else {
        // Default to 0 when months specified, otherwise default to 7 days
        $days = ($months > 0 ? 0 : 7);
    }
    if( $days > 0 ) {
        $date_text .= $days . ' day' . ($days > 1 ? 's' : '');
    }

//    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
//    $end_dt = clone $start_dt;
//    if( $days != 0 ) {
//        $end_dt->sub(new DateInterval('P' . $days . 'D'));
//    }
//    if( $months != 0 ) {
//        $end_dt->sub(new DateInterval('P' . $months . 'M'));
//    }
    if( isset($args['start_date']) && $args['start_date'] != '' 
        && isset($args['end_date']) && $args['end_date'] != '' 
        ) {
        $start_dt = new DateTime($args['start_date'] . ' 00:00:00' , new DateTimezone($intl_timezone));
        $end_dt = new DateTime($args['end_date'] . ' 23:59:59', new DateTimezone($intl_timezone));
        if( $start_dt > $end_dt ) {
            $interval = $end_dt->diff($start_dt);
            $days = $interval->format("%a");
            $start_dt = clone $end_dt;
            $start_dt->sub(new DateInterval('P1D'));
        }
        elseif( $start_dt < $end_dt ) {
            $interval = $start_dt->diff($end_dt);
            $days = $interval->format("%a") + 1;
            $months = 0;
        } elseif( $start_dt == $end_dt ) {
            $days = 1;
        } else {
            $days = 1;
        }
        $start_dt->setTimezone(new DateTimezone('UTC'));
    }
    elseif( isset($args['start_date']) && $args['start_date'] != '' 
        && (!isset($args['end_date']) || $args['end_date'] == '') 
        ) {
        $start_dt = new DateTime($args['start_date'] . ' 00:00:00' , new DateTimezone($intl_timezone));
        $start_dt->setTimezone(new DateTimezone('UTC'));
    }
    else {
        $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
        $start_dt->setTime(0,0,0);
        if( $months > 0 ) {
            $start_dt->sub(new DateInterval('P' . $months . 'M'));
        } elseif( $days > 1 ) {
            $start_dt->sub(new DateInterval('P' . $days . 'D'));
        }
        $start_dt->setTimezone(new DateTimezone('UTC'));
    }
    $end_dt = clone $start_dt;
    if( $months > 0 ) {
        $end_dt->add(new DateInterval('P' . $months .  'M'));
    } else {
        $end_dt->add(new DateInterval('P' . (isset($days) ? $days : 1) .  'D'));
    }

    //
    // Store the report block chunks
    //
    $chunks = array();

    //
    // Get the renewed members
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) ) {
        $strsql = "SELECT c.id, "
            . "c.parent_id, "
            . "c.status, "
            . "c.status AS status_text, "
            . "c.display_name, "
//            . "MIN(purchases.start_date) AS sdate, "
            . "c.member_lastpaid AS mlp, "
            . "DATE_FORMAT(c.member_lastpaid, '%b %e, %Y') AS member_lastpaid, "
            . "MIN(purchases.start_date) AS min_start_date "
            . "FROM ciniki_customers AS c "
            . "INNER JOIN ciniki_customer_product_purchases AS purchases ON ("
                . "c.id = purchases.customer_id "
                . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//            . "AND c.member_status = 10 "
    //        . "AND c.start_date >= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
//            . "HAVING sdate >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
//            . "AND sdate < '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
            . "AND c.member_lastpaid >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
            . "GROUP BY c.id "
            . "HAVING min_start_date < mlp "
            . "ORDER BY c.member_lastpaid "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 
                'fields'=>array('id', 'parent_id', 'display_name', 'status', 'status_text', 'start_date'=>'min_start_date', 'member_lastpaid'),
                'utctotz'=>array(
                    'start_date'=>array('timezone'=>'UTC', 'format'=>$php_date_format),
                    ),
                'maps'=>array('status_text'=>$maps['customer']['status'])),
                ));
    } else {
        $strsql = "SELECT c.id, "
            . "c.parent_id, "
            . "c.status, "
            . "c.status AS status_text, "
            . "c.display_name, "
            . "DATE_FORMAT(c.member_lastpaid, '%b %e, %Y') AS member_lastpaid, "
            . "DATE_FORMAT(c.start_date, '%b %e, %Y') AS start_date "
            . "FROM ciniki_customers AS c "
            . "WHERE c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND c.member_status = 10 "
            . "AND c.member_lastpaid >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
            . "AND c.member_lastpaid < NOW() "
            . "AND c.start_date < c.member_lastpaid "
            . "ORDER BY c.member_lastpaid "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 
                'fields'=>array('id', 'parent_id', 'display_name', 'status', 'status_text', 'start_date', 'member_lastpaid'),
                'utctotz'=>array(
                    'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$php_date_format),
                    ),
                'maps'=>array('status_text'=>$maps['customer']['status'])),
                ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }
    $customer_ids = array();
    if( isset($rc['customers']) ) {
        $customers = $rc['customers'];
       
        foreach($customers as $customer) {
            $customer_ids[] = $customer['id'];
        }
    }

    //
    // Get the emails and addresses
    //
    if( count($customer_ids) > 0 ) {
        //
        // Get the emails
        //
        $strsql = "SELECT id, customer_id, email "
            . "FROM ciniki_customer_emails "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x10) = 0 " // Only get emails that want to receive emails
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'emails', 'fname'=>'id', 'fields'=>array('email')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $emails = $rc['customers'];

        //
        // Get the addresses
        //
        $strsql = "SELECT id, customer_id, address1, address2, city, province, postal, country "
            . "FROM ciniki_customer_addresses "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x04) = 0x04 " // Only get mailing addresses
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'addresses', 'fname'=>'id', 'fields'=>array('address1', 'address2', 'city', 'province', 'postal', 'country')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $addresses = $rc['customers'];

        //
        // Create the report blocks
        //
        $chunk = array(
            'type'=>'table',
            'columns'=>array(
                array('label'=>'Name', 'pdfwidth'=>'20%', 'field'=>'display_name'),
                array('label'=>'Renewed Date', 'pdfwidth'=>'20%', 'field'=>'member_lastpaid'),
                array('label'=>'Email', 'pdfwidth'=>'30%', 'field'=>'email'),
                array('label'=>'Address', 'pdfwidth'=>'30%', 'field'=>'address'),
                ),
            'data'=>array(),
            'editApp'=>array('app'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.id')),
            'textlist'=>'',
            );
        foreach($customers as $cid => $customer) {
            //
            // Add emails to customer
            //
            $chunk['textlist'] .= $customer['display_name'] . "\n";
            $chunk['textlist'] .= $customer['start_date'] . "\n";
            if( isset($emails[$customer['id']]['emails']) ) {
                foreach($emails[$customer['id']]['emails'] as $email) {
                    $chunk['textlist'] .= $email['email'] . "\n";
                    if( !isset($customer['email']) ) {
                        $customer['email'] = $email['email'];
                    } else {
                        $customer['email'] .= ', ' . $email['email'];
                    }
                }
            }
            //
            // Add addresses to customer
            //
            if( isset($addresses[$customer['id']]['addresses']) ) {
                foreach($addresses[$customer['id']]['addresses'] as $address) {
                    $addr = '';
                    if( isset($address['address1']) && $address['address1'] != '' ) {
                        $addr .= $address['address1'];
                    }
                    if( isset($address['address2']) && $address['address2'] != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $address['address2'];
                    }
                    $city = '';
                    if( isset($address['city']) && $address['city'] != '' ) {
                        $city .= $address['city'];
                    }
                    if( isset($address['province']) && $address['province'] != '' ) {
                        $city .= ($city != '' ? ', ' : '') . $address['province'];
                    }
                    if( isset($address['postal']) && $address['postal'] != '' ) {
                        $city .= ($city != '' ? '  ' : '') . $address['postal'];
                    }
                    if( $city != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $city;
                    }
                    if( isset($address['country']) && $address['country'] != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $address['country'];
                    }
                    if( $addr != '' ) {
                        $chunk['textlist'] .= $addr . "\n";
                        if( !isset($customers[$cid]['address']) ) {
                            $customer['address'] = $addr;
                        } else {
                            $customer['address'] .= "\n" . $addr;
                        }
                    }
                }
            }
            $chunk['textlist'] .= "\n";
            $chunk['data'][] = $customer;
        }
        $chunks[] = $chunk;
    } 
    //
    // No customers 
    //
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No renewed members in the last ' . $date_text . '.');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
