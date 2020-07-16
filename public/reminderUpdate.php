<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_reminderUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'reminder_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reminders'),
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'),
        'reminder_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Date'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'repeat_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Repeat Type'),
        'repeat_interval'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Repeat Interval'),
        'repeat_end'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Repeat End Date'),
        'description'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Description'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        'email_time'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'Email Time'),
        'email_subject'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email Subject'),
        'email_html'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email Content'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.reminderUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Get the current reminder
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
        . "ciniki_customer_reminders.notes, "
        . "ciniki_customer_reminders.email_time,"
        . "ciniki_customer_reminders.email_next_dt,"
        . "ciniki_customer_reminders.email_subject, "
        . "ciniki_customer_reminders.email_html "
        . "FROM ciniki_customer_reminders "
        . "WHERE ciniki_customer_reminders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customer_reminders.id = '" . ciniki_core_dbQuote($ciniki, $args['reminder_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'reminder');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.128', 'msg'=>'Unable to load reminder', 'err'=>$rc['err']));
    }
    if( !isset($rc['reminder']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.129', 'msg'=>'Unable to find reminder'));
    }
    $reminder = $rc['reminder'];

    //
    // Setup the latest variables from updates or database
    //
    $flags = isset($args['flags']) ? $args['flags'] : $reminder['flags'];
    $reminder_date = isset($args['reminder_date']) ? $args['reminder_date'] : $reminder['reminder_date'];
    $email_time = isset($args['email_time']) ? $args['email_time'] : $reminder['email_time'];

    //
    // Make sure the email_time is specified when email flag set
    //
    if( ($flags&0x01) == 0x01 ) {
        if( $email_time == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.customers.128', 'msg'=>'Email Time must be specified'));
        }

        //
        // Setup email_next_dt 
        //
        $dt = new DateTime($reminder_date . ' ' . $email_time, new DateTimezone($intl_timezone));
        $dt->setTimezone(new DateTimezone('UTC'));
        if( $dt->format('Y-m-d H:i:s') != $reminder['email_next_dt'] ) {
            $args['email_next_dt'] = $dt->format('Y-m-d H:i:s');
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Reminders in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.reminder', $args['reminder_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }

    //
    // Commit the transaction
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.reminder', 'object_id'=>$args['reminder_id']));

    return array('stat'=>'ok');
}
?>
