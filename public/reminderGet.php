<?php
//
// Description
// ===========
// This method will return all the information about an reminders.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the reminders is attached to.
// reminder_id:          The ID of the reminders to get the details for.
//
// Returns
// -------
//
function ciniki_customers_reminderGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'reminder_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reminders'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.reminderGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'mysql');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    

    //
    // Return default for new Reminders
    //
    if( $args['reminder_id'] == 0 ) {
        $reminder = array('id'=>0,
            'customer_id'=>(isset($args['customer_id']) ? $args['customer_id'] : 0),
            'reminder_date'=>'',
            'flags'=>'0',
            'repeat_type'=>'0',
            'repeat_interval'=>'1',
            'repeat_end'=>'',
            'description'=>'',
            'category'=>'',
            'notes'=>'',
            'email_time'=>'',
            'email_subject'=>'',
            'email_html'=>'',
        );
    }

    //
    // Get the details for an existing Reminders
    //
    else {
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
            . "TIME_FORMAT(ciniki_customer_reminders.email_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "') AS email_time, "
            . "ciniki_customer_reminders.email_subject, "
            . "ciniki_customer_reminders.email_html "
            . "FROM ciniki_customer_reminders "
            . "WHERE ciniki_customer_reminders.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customer_reminders.id = '" . ciniki_core_dbQuote($ciniki, $args['reminder_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'reminders', 'fname'=>'id', 
                'fields'=>array(
                    'customer_id', 'reminder_date', 'flags', 
                    'repeat_type', 'repeat_interval', 'repeat_end', 'description', 'category', 'notes', 
                    'email_time', 'email_subject', 'email_html',
                    ),
                'utctotz'=>array(
                    'reminder_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'repeat_end'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.459', 'msg'=>'Reminders not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['reminders'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.461', 'msg'=>'Unable to find Reminders'));
        }
        $reminder = $rc['reminders'][0];
    }

    //
    // Load the customer details
    //
    if( $reminder['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], $reminder);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.464', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        $reminder['customer_details'] = $rc['details'];
    }

    return array('stat'=>'ok', 'reminder'=>$reminder);
}
?>
