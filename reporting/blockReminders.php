<?php
//
// Description
// -----------
// Return the report of upcoming customer reminders
//
// Arguments
// ---------
// ciniki:
// tnid:         
// args:        
//
// Additional Arguments
// --------------------
// days:       
// 
// Returns
// -------
//
function ciniki_customers_reporting_blockReminders(&$ciniki, $tnid, $args) {
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

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 && $args['days'] < 365 ) {
        $days = $args['days'];
    } else {
        $days = 14;
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $start_dt = $start_dt->setTime(0, 0, 0);
    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval('P' . $days . 'D'));
    $end_dt->setTime(23,59,59);

    //
    // Store the report block chunks
    //
    $chunks = array();

    //
    // Get the list of reminders for the next 14 days
    //
    $strsql = "SELECT reminders.id, "
        . "reminders.customer_id, "
        . "reminders.reminder_date, "
        . "customers.display_name, "
        . "DATE_FORMAT(reminders.reminder_date, '%b %e, %Y') AS reminder_date_display, "
        . "reminders.flags, "
        . "reminders.repeat_type, "
        . "reminders.repeat_interval, "
        . "reminders.repeat_end, "
        . "reminders.description, "
        . "reminders.category, "
        . "IF((reminders.flags&0x01)=0x01, TIME_FORMAT(reminders.email_time, '%l:%i %p'), '') AS auto_email, "
        . "reminders.email_time, "
        . "reminders.email_subject, "
        . "reminders.email_html "
        . "FROM ciniki_customer_reminders AS reminders "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "reminders.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE reminders.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND reminders.reminder_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
        . "AND reminders.reminder_date <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
        . "ORDER BY reminders.reminder_date, reminders.email_time "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'reminders', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'reminder_date', 'reminder_date_display', 'flags', 
                'repeat_type', 'repeat_interval', 'repeat_end', 
                'description', 'category', 'auto_email', 'email_time', 'email_subject', 'email_html')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $reminders = isset($rc['reminders']) ? $rc['reminders'] : array();
    
    $customer_ids = array();
    foreach($reminders as $reminder) {
        $customer_ids[] = $reminder['customer_id'];
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
        // Get the phones
        //
        $strsql = "SELECT id, customer_id, phone_label, phone_number "
            . "FROM ciniki_customer_phones "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'phones', 'fname'=>'id', 'fields'=>array('label'=>'phone_label', 'number'=>'phone_number')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $phones = isset($rc['customers']) ? $rc['customers'] : array();

        //
        // Create the report blocks
        //
        $chunk = array(
            'type'=>'table',
            'columns'=>array(
                array('label'=>'Date', 'pdfwidth'=>'15%', 'field'=>'reminder_date_display'),
                array('label'=>'Name', 'pdfwidth'=>'30%', 'field'=>'customer_details'),
                array('label'=>'Description', 'pdfwidth'=>'40%', 'field'=>'description'),
                array('label'=>'Auto Email', 'pdfwidth'=>'15%', 'field'=>'auto_email'),
                ),
            'data'=>array(),
            'textlist'=>'',
            );
        foreach($reminders as $cid => $reminder) {
            //
            // Add emails to customer
            //
            $reminder['emails'] = '';
            $reminder['phones'] = '';
            $chunk['textlist'] .= $reminder['reminder_date_display'] . ' - ' . $reminder['display_name'] . "\n";
            $chunk['textlist'] .= $reminder['description'] . "\n";
            if( $reminder['auto_email'] != '' ) {
                $chunk['textlist'] .= 'Emailed at: ' . $reminder['auto_email'] . "\n";
            }
            if( isset($emails[$reminder['customer_id']]['emails']) ) {
                foreach($emails[$reminder['customer_id']]['emails'] as $email) {
                    $chunk['textlist'] .= $email['email'] . "\n";
                    $reminder['emails'] .= ($reminder['emails'] != '' ? "\n":'') . $email['email'];
                }
            }
            if( isset($phones[$reminder['customer_id']]['phones']) ) {
                foreach($phones[$reminder['customer_id']]['phones'] as $phone) {
                    $chunk['textlist'] .= ($phone['label'] != '' ? $phone['label'] . ': ' : '') . $phone['number'] . "\n";
                    $reminder['phones'] .= 
                        ($reminder['phones'] != '' ? "\n" : '') 
                        .  ($phone['label'] != '' ? $phone['label'] . ': ' : '') . $phone['number'];
                }
            }
            $reminder['customer_details'] = $reminder['display_name'];
            if( $reminder['emails'] != '' ) {
                $reminder['customer_details'] .= "\n" . $reminder['emails'];
            }
            if( $reminder['phones'] != '' ) {
                $reminder['customer_details'] .= "\n" . $reminder['phones'];
            }

            $chunk['textlist'] .= "\n";
            $chunk['data'][] = $reminder;
        }
        $chunks[] = $chunk;
    } 
    //
    // No customers 
    //
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No reminders in the next ' . ($days == 1 ? 'day' : $days . ' days') . '.');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
