<?php
//
// Description
// -----------
// This method will setup a temporary password for a user, and email 
// the temporary password to them.
//
// API Arguments
// ---------
// email:           The email address of the user to reset.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_customers_wng_passwordRequestReset(&$ciniki, $tnid, $request, $email, $url) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'logAdd');

    //  
    // Create a random password for the user
    //  
    $password = ''; 
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    for($i=0;$i<32;$i++) {
        $password .= substr($chars, rand(0, strlen($chars)-1), 1); 
    }

    //
    // Get the username for the account
    //
    $strsql = "SELECT ciniki_customer_emails.id, "
        . "email, "
        . "ciniki_customer_emails.flags, "
        . "ciniki_customers.uuid, "
        . "ciniki_customers.display_name "
        . "FROM ciniki_customer_emails, ciniki_customers "
        . "WHERE ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
        . "AND ciniki_customers.status < 40 "
        . "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Request Password Reset', 0, $email, 'ciniki.customers.477', 'Internal Error');
        error_log("WEB [" . $ciniki['tenant']['name'] . "]: changeTempPassword $email fail (725)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.478', 'msg'=>'Unable to reset password.', 'err'=>$rc['err']));
    }
    if( $rc['num_rows'] > 1 ) {
        $customers = $rc['rows'];
        $customer = $customers[0];
    } elseif( !isset($rc['customer']) || !isset($rc['customer']['email']) ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Request Password Reset', 0, $email, 'ciniki.customers.479', 'Email not found');
        error_log("WEB [" . $ciniki['tenant']['name'] . "]: changeTempPassword $email fail (726)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.480', 'msg'=>'Unable to reset password.'));
    } else {
        $customer = $rc['customer'];
        $customers = $rc['rows'];
    }

    //
    // If required to blocked locked accounts from resetting passwords, this will need to be an option
    // added for the tenant to decide if this should be an option.
    //
//    if( ($customer['flags']&0x80) == 0x80 ) {
//        return array('stat'=>'locked');
//    }

    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Set the new temporary password
    //
    foreach($customers as $cust) {
        $strsql = "UPDATE ciniki_customer_emails "
            . "SET temp_password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "'), "
            . "temp_password_date = UTC_TIMESTAMP(), "
            . "last_updated = UTC_TIMESTAMP() "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $cust['id']) . "' "
            . "AND (flags&0x01) = 0x01 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Request Password Reset', 0, $email, 'ciniki.customers.481', 'Internal Error');
            error_log("WEB [" . $ciniki['tenant']['name'] . "]: changeTempPassword $email fail (727)");
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.482', 'msg'=>'Unable to reset password.'));
        }

        if( $rc['num_affected_rows'] < 1 ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Request Password Reset', 0, $email, 'ciniki.customers.483', 'Error commiting changes');
            error_log("WEB [" . $ciniki['tenant']['name'] . "]: changeTempPassword $email fail (728)");
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.484', 'msg'=>'Unable to reset password.'));
        }
    }

    //
    // FIXME: Add log entry to track password changes
    //

    //
    // Email the customer with the new password
    //
    if( $customer['email'] != '' 
        && isset($ciniki['config']['ciniki.core']['system.email']) && $ciniki['config']['ciniki.core']['system.email'] != '' ) {
        //
        // Load the tenant mail template
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadTenantTemplate');
        $rc = ciniki_mail_loadTenantTemplate($ciniki, $tnid, array('title'=>'Password reset'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $template = $rc['template'];
        $theme = $rc['theme'];

        //
        // Prepare Messages
        //
        $subject = "Password reset";
        if( strstr($url, '?') !== false ) {
            $url .= '&email=' . urlencode($customer['email']) . "&pwd=$password";
        } else {
            $url .= '?email=' . urlencode($customer['email']) . "&pwd=$password";
        }
        $html_message = $template['html_header']
            . "<tr><td style='" . $theme['td_body'] . "'>"
            . "<p style='" . $theme['p'] . "'>You have requested a new password.  Please click on the following link to set a new password.  This link will only be valid for 2 hours.</p>"
            . "<p style='" . $theme['p'] . "'><a style='" . $theme['a'] . "' href='$url'>$url</a></p>"
            . "</td></tr>"
            . $template['html_footer']
            . "";
        $text_message = $template['text_header']
            . "Hi, \n\n"
            . "You have requested a new password.  "
            . "Please click the following link to reset your password.  This link will only be valid for 2 hours.\n"
            . "\n"
            . $url . "\n"
            . "\n"
            . "\n"
            . $template['text_footer']
            . "";

        //
        // The from address can be set in the config file.
        //
        $ciniki['emailqueue'][] = array('to'=>$customer['email'],
            'to_name'=>$customer['display_name'],
            'tnid'=>$tnid,
            'subject'=>$subject,
            'textmsg'=>$text_message,
            'htmlmsg'=>$html_message,
            );
    }

    //
    // Commit the changes and return
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Request Password Reset', 0, $email, 'ciniki.customers.485', 'Error commiting changes');
        error_log("WEB [" . $ciniki['tenant']['name'] . "]: changeTempPassword $email fail (729)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.486', 'msg'=>'Unable to reset password.'));
    }

    //
    // Email sysadmins to let them know of a password reset request
    //
    if( isset($ciniki['config']['ciniki.customers']['password.forgot.notify']) && $ciniki['config']['ciniki.customers']['password.forgot.notify'] != '' ) {
        date_default_timezone_set('America/Eastern');
        $subject = $customer['display_name'] . " forgot password";
        $msg = "Customer password request: \n\n"
            . "email: " . $customer['email'] . "\n"
            . "time: " . date("D M j, Y H:i e") . "\n"
            . "" . "\n";
        $ciniki['emailqueue'][] = array('to'=>$ciniki['config']['ciniki.customers']['password.forgot.notify'],
            'subject'=>$subject,
            'textmsg'=>$msg,
            );
    }
    ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 10, 'Request Password Reset', 0, $email, '', 'Reset password email sent');
    error_log("WEB [" . $ciniki['tenant']['name'] . "]: passwordRequestReset $email success");

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'customers');

    return array('stat'=>'ok');
}
?>
