<?php
//
// Description
// -----------
// This function will search the customer reminders.
//
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the details for.
// args:                The args passed through the API.
//
// Returns
// -------
//
function ciniki_customers_hooks_appointmentSearch($ciniki, $tnid, $args) {

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
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "reminders.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE reminders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "reminders.description LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR reminders.description LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.first LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.last LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.company LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customers.company LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "";
    if( isset($args['date']) ) {
        $strsql .= "ORDER BY ABS(DATEDIFF(DATE(reminders.reminder_date), DATE('" . ciniki_core_dbQuote($ciniki, $args['date']) . "'))) ";
    } else {
        $strsql .= "ORDER BY ABS(DATEDIFF(DATE(reminders.reminder_date), DATE('" . ciniki_core_dbQuote($ciniki, $args['date']) . "'))) ";
    }
    error_log($strsql);
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
    foreach($reminders as $rid => $reminder) {
        $reminders[$rid]['colour'] = '#fdbb6c';
        $reminders[$rid]['module'] = 'ciniki.customers';
        $reminders[$rid]['app'] = 'ciniki.customers.reminders';
        $dt = new DateTime($reminder['reminder_date'] . ' 00:00:00', new DateTimezone($intl_timezone));
        $reminders[$rid]['start_ts'] = $dt->format('U');
        $reminders[$rid]['start_date'] = $dt->format('M j, Y H:i');
        $reminders[$rid]['date'] = $dt->format('Y-m-d');
        $reminders[$rid]['time'] = '00:00:00';
        $reminders[$rid]['allday'] = 'yes';
        $reminders[$rid]['subject'] = $reminder['display_name'] . ' - ' . $reminder['description'];
    }

    return array('stat'=>'ok', 'appointments'=>$reminders);;
}
?>
