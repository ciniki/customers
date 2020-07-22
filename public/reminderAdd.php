<?php
//
// Description
// -----------
// This method will add a new reminders for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Reminders to.
//
// Returns
// -------
//
function ciniki_customers_reminderAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'reminder_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Date'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'repeat_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Repeat Type'),
        'repeat_interval'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Repeat Interval'),
        'repeat_end'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Repeat End Date'),
        'description'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Description'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        'email_time'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email Time'),
        'email_subject'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email Subject'),
        'email_html'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email Content'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.reminderAdd');
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
    // Make sure the email_time is specified when email flag set
    //
    if( isset($args['flags']) && ($args['flags']&0x01) == 0x01 ) {
        if( !isset($args['email_time']) || $args['email_time'] == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.customers.127', 'msg'=>'Email Time must be specified'));
        }
        //
        // Setup email_next_dt 
        //
        $dt = new DateTime($args['reminder_date'] . ' ' . $args['email_time'], new DateTimezone($intl_timezone));
        $dt->setTimezone(new DateTimezone('UTC'));
        $args['email_next_dt'] = $dt->format('Y-m-d H:i:s');
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
    // Add the reminders to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.reminder', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    $reminder_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.reminder', 'object_id'=>$reminder_id));

    return array('stat'=>'ok', 'id'=>$reminder_id);
}
?>
