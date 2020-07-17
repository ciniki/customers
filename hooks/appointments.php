<?php
//
// Description
// -----------
// This function will return the customer reminders to be shown in the calendar.
//
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the appointments for.
// args:         The args passed from the calling function.
//
// Returns
// -------
//
function ciniki_customers_hooks_appointments($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'reminderRepeatNextDate');
    $appointments = array();

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
    // Setup the dates if not properly setup
    //
    if( isset($args['date']) && ($args['date'] == '' || $args['date'] == 'today') ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $args['date'] = $dt->format('Y-m-d');
    }

    if( !isset($args['start_date']) || $args['start_date'] == '' ) {
        if( isset($args['date']) ) {
            $args['start_date'] = $args['date'];
        } else {
            $dt = new DateTime('now', new DateTimezone($intl_timezone));
            $args['start_date'] = $dt->format('Y-m-d') . ' 00:00:00';
        }
    }

    if( !isset($args['end_date']) || $args['end_date'] == '' ) {
        $args['end_date'] = $args['start_date'] . ' 23:59:59';
    }

    $start_dt = new DateTime($args['start_date'], new DateTimezone($intl_timezone));
    $end_dt = new DateTime($args['end_date'], new DateTimezone($intl_timezone));

    //
    // Get the list of reminders between the 2 dates
    //
    $strsql = "SELECT reminders.id, "
        . "reminders.customer_id, "
        . "customers.display_name, "
        . "reminders.reminder_date, "
        . "reminders.flags, "
        . "reminders.repeat_type, "
        . "reminders.repeat_interval, "
        . "reminders.repeat_end, "
        . "reminders.description, "
        . "reminders.category, "
        . "reminders.email_time, "
        . "reminders.email_subject, "
        . "reminders.email_html "
        . "FROM ciniki_customer_reminders AS reminders "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "reminders.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE reminders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (("
                . "reminders.reminder_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
                . "AND reminders.reminder_date <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
                . ") "
            . "OR ("
                . "reminders.reminder_date < '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
                . "AND reminders.repeat_type > 0 "
                . "AND reminders.repeat_end >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
                . ") "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'reminders', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'reminder_date', 'flags', 
                'repeat_type', 'repeat_interval', 'repeat_end', 'description', 'category', 
                'email_time', 'email_subject', 'email_html')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $reminders = isset($rc['reminders']) ? $rc['reminders'] : array();

    //
    // Format reminders for the calendar
    //
    $appointments = array();
    foreach($reminders as $rid => $reminder) {
        $reminder['colour'] = '#fdbb6c';
        $reminder['module'] = 'ciniki.customers';
        $reminder['app'] = 'ciniki.customers.reminders';
        $dt = new DateTime($reminder['reminder_date'] . ' 00:00:00', new DateTimezone($intl_timezone));
        $reminder['start_ts'] = $dt->format('U');
        $reminder['date'] = $dt->format('Y-m-d');
        $reminder['time'] = '00:00:00';
        $reminder['allday'] = 'yes';
        $reminder['subject'] = $reminder['display_name'] . ' - ' . $reminder['description'];
        if( $dt >= $start_dt && $dt <= $end_dt ) {
            $appointments[] = $reminder;
        }
        // Setup end of repeat
        $repeat_end_dt = null;
        if( $reminder['repeat_end'] != '0000-00-00' ) {
            $repeat_end_dt = new DateTime($reminder['repeat_end'] . ' 23:59:59', new DateTimezone($intl_timezone));
        }

        if( $reminder['repeat_type'] > 0 ) {
            while($dt <= $end_dt) {
                $rc = ciniki_customers_reminderRepeatNextDate($ciniki, $tnid, $reminder, $dt);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.410', 'msg'=>'Unable to calculate next reminder date', 'err'=>$rc['err']));
                }
                $dt = $rc['next_dt'];

                if( $dt >= $start_dt && $dt <= $end_dt && ($repeat_end_dt == null || $dt <= $repeat_end_dt) ) {
                    $reminder['start_ts'] = $dt->format('U');
                    $reminder['date'] = $dt->format('Y-m-d');
                    $appointments[] = $reminder;
                }
            }
        }
    }

    return array('stat'=>'ok', 'appointments'=>$appointments);
}
?>
