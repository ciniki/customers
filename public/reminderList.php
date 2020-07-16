<?php
//
// Description
// -----------
// This method will return the list of Reminderss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Reminders for.
//
// Returns
// -------
//
function ciniki_customers_reminderList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.reminderList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of reminders
    //
    $strsql = "SELECT ciniki_customer_reminders.id, "
        . "ciniki_customer_reminders.customer_id, "
        . "ciniki_customer_reminders.reminder_date, "
        . "ciniki_customer_reminders.flags, "
        . "ciniki_customer_reminders.repeat_type, "
        . "ciniki_customer_reminders.repeat_interval, "
        . "ciniki_customer_reminders.repeat_end, "
        . "ciniki_customer_reminders.description, "
        . "ciniki_customer_reminders.category, "
        . "ciniki_customer_reminders.email_time, "
        . "ciniki_customer_reminders.email_subject, "
        . "ciniki_customer_reminders.email_html "
        . "FROM ciniki_customer_reminders "
        . "WHERE ciniki_customer_reminders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'reminders', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'reminder_date', 'flags', 'repeat_type', 'repeat_interval', 'repeat_end', 'description', 'category', 'email_time', 'email_subject', 'email_html')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['reminders']) ) {
        $reminders = $rc['reminders'];
        $reminder_ids = array();
        foreach($reminders as $iid => $reminder) {
            $reminder_ids[] = $reminder['id'];
        }
    } else {
        $reminders = array();
        $reminder_ids = array();
    }

    return array('stat'=>'ok', 'reminders'=>$reminders, 'nplist'=>$reminder_ids);
}
?>
