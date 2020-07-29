<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_reminderEmailSend(&$ciniki, $tnid, $reminder_id) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the reminder
    //
    $strsql = "SELECT reminders.id, "
        . "reminders.customer_id, "
        . "reminders.reminder_date, "
        . "reminders.flags, "
        . "reminders.repeat_type, "
        . "reminders.repeat_interval, "
        . "reminders.repeat_end, "
        . "reminders.description, "
        . "reminders.category, "
        . "reminders.notes, "
        . "reminders.email_time, "
        . "reminders.email_next_dt, "
        . "reminders.email_subject, "
        . "reminders.email_html, "
        . "customers.display_name, "
        . "emails.id AS email_id, "
        . "emails.email AS email_address "
        . "FROM ciniki_customer_reminders AS reminders "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "reminders.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "reminders.customer_id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE reminders.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND reminders.id = '" . ciniki_core_dbQuote($ciniki, $reminder_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'reminder', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'reminder_date', 'flags', 'repeat_type', 'repeat_interval', 
                'repeat_end', 'description', 'category', 'notes', 'email_time', 'email_next_dt', 
                'email_subject', 'email_html', 'display_name'),
            ),
        array('container'=>'emails', 'fname'=>'email_id', 'fields'=>array('email_address')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.408', 'msg'=>'Unable to load reminders', 'err'=>$rc['err']));
    }
    if( !isset($rc['reminder'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.407', 'msg'=>'Unable to find requested reminder'));
    }
    $reminder = $rc['reminder'][0];
    
    //
    // Send the email
    //
    if( isset($reminder['emails']) && count($reminder['emails']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        foreach($reminder['emails'] as $email) {
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'customer_id'=>$reminder['customer_id'],
                'customer_email'=>$email['email_address'],
                'customer_name'=>$reminder['display_name'],
                'subject'=>$reminder['email_subject'],
                'html_content'=>$reminder['email_html'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    } else {
        error_log("CUSTOMER REMINDERS ERROR -- No emails specified for reminder " . $reminder_id);
    }

    //
    // Update the email_next_dt
    //
    if( $reminder['repeat_type'] > 0 ) {
        $dt = new DateTime($reminder['reminder_date'] . ' 12:00:00', new DateTimezone($intl_timezone));
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'reminderRepeatNextDate');
        $rc = ciniki_customers_reminderRepeatNextDate($ciniki, $tnid, $reminder, $dt);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.409', 'msg'=>'Unable to calculate next reminder date', 'err'=>$rc['err']));
        }
        if( $reminder['repeat_end'] != '0000-00-00' ) {
            $end_dt = new DateTime($reminder['repeat_end'] . ' 23:59:59', new DateTimezone($intl_timezone));
        }
        //
        // Mark the reminder as sent, don't advance the date
        //
        if( isset($end_dt) && $end_dt < $rc['next_dt'] ) {
            $update_args['flags'] = ($reminder['flags'] | 0x02); // Mark as sent
        }
        //
        // Only advance the date if no end date or end date is in future and next date is different
        //
        elseif( $rc['next_dt']->format('Y-m-d') != $reminder['reminder_date'] ) {
            $update_args['reminder_date'] = $rc['next_dt']->format('Y-m-d');
        }

        //
        // Setup next email date time
        //
        if( isset($update_args['reminder_date']) ) {
            $dt = new DateTime($update_args['reminder_date'] . ' ' . $reminder['email_time'], new DateTimezone($intl_timezone));
            $dt->setTimezone(new DateTimezone('UTC'));
            $update_args['email_next_dt'] = $dt->format('Y-m-d H:i:s');
        }
    } else {
        $update_args = array('flags' => ($reminder['flags'] | 0x02));
    }

    if( isset($update_args) && count($update_args) > 0 ) {
        //
        // Update the reminder
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.reminder', $reminder['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.406', 'msg'=>'Unable to update the reminder'));
        }
    }

    return array('stat'=>'ok');
}
?>
