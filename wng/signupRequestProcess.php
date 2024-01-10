<?php
//
// Description
// -----------
//
// API Arguments
// ---------
// email:           The email address of the user to reset.
//
function ciniki_customers_wng_signupRequestProcess(&$ciniki, $tnid, &$request, $args) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'logAdd');

    //
    // Check the required variables are present
    //
    if( !isset($args['first']) || !isset($args['last']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.523', 'msg'=>'You must specify a first and last name'));
    }
    if( !isset($args['email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.536', 'msg'=>'You must enter an email address'));
    }

    //
    // Check if email already exists
    //
    $strsql = "SELECT emails.id, emails.customer_id, customers.status, emails.email "
        . "FROM ciniki_customer_emails AS emails "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "emails.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE emails.email = '" . ciniki_core_dbQuote($ciniki, $args['email']) . "' "
        . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY customers.status "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.525', 'msg'=>'Unable to load email', 'err'=>$rc['err']));
    }
    //
    // If multiple returned, pick the first one
    //
    if( isset($rc['rows'][0]) ) {
        $email = $rc['rows'][0];
        //
        // Check customers status
        //
        if( $email['status'] != 10 ) {
            //
            // Anybody deleted, on hold, suspended are unable to reactivate or re-signup.
            //
            return array('stat'=>'notactive', 'err'=>array('code'=>'ciniki.customers.537', 'msg'=>'Unable to load email', 'err'=>$rc['err']));
        }
        return array('stat'=>'accountexists', 'err'=>array('code'=>'ciniki.customers.538', 'msg'=>'An account with that email address already exists.'));
    }
   
    
    //
    // Email does not exist, safe to issue signup
    //

    //  
    // Create a random password/key for the user
    //  
    $signupkey = ''; 
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    for($i=0;$i<32;$i++) {
        // Pick a random character from the $chars string
        $signupkey .= substr($chars, rand(0, strlen($chars)-1), 1); 
    }

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
    // Setup request in signuprequests table
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.signup', array(
        'signupkey' => $signupkey,
        'first' => $args['first'],
        'last' => $args['last'],
        'email' => $args['email'],
        'password' => sha1($args['password']),
        'details' => serialize($args['details']),
        ), 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.527', 'msg'=>'Unable to add the signup', 'err'=>$rc['err']));
    }
    
    //
    // Load the tenant mail template
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadTenantTemplate');
    $rc = ciniki_mail_loadTenantTemplate($ciniki, $tnid, array('title'=>'Email Verification'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $template = $rc['template'];
    $theme = $rc['theme'];

    //
    // Prepare Messages
    //
    $subject = "Please verify your email address";
    $url = $args['url'];
    if( strstr($url, '?') !== false ) {
        $url .= "&k={$signupkey}";
    } else {
        $url .= "?k={$signupkey}";
    }
/*    $html_message = $template['html_header']
        . "<tr><td style='" . $theme['td_body'] . "'>"
        . "<p style='" . $theme['p'] . "'>Please click on the following link to verify your email.  This link will expire in 2 hours.</p>"
        . "<p style='" . $theme['p'] . "'><a style='" . $theme['a'] . "' href='$url'>$url</a></p>"
        . "</td></tr>"
        . $template['html_footer']
        . "";
    $text_message = $template['text_header']
        . "Hi, \n\n"
        . "Please click the following link to verify your email.  This link will expire in 2 hours.\n"
        . "\n"
        . $url . "\n"
        . "\n"
        . "\n"
        . $template['text_footer']
        . ""; */

    $html_message = "Please click on the following link to verify your email. This link will expire in 2 hours.\n\n"
        . $url 
        . "\n\n";
    $text_message = $html_message;

    //
    // The from address can be set in the config file.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
    $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
        'customer_id' => 0,
        'customer_email' => $args['email'],
        'customer_name' => $args['first'] . ' ' . $args['last'],
        'subject'=>$subject,
        'text_content'=>$text_message,
        'html_content'=>$html_message,
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.553', 'msg'=>'Unable to add verification email', 'err'=>$rc['err']));
    }
    $ciniki['emailqueue'][] = array('tnid'=>$tnid, 'mail_id'=>$rc['id']);
    
/*    $ciniki['emailqueue'][] = array('to'=>$args['email'],
        'to_name'=>$args['first'] . ' ' . $args['last'],
        'tnid'=>$tnid,
        'subject'=>$subject,
        'textmsg'=>$text_message,
        'htmlmsg'=>$html_message,
        ); */
 
    //
    // Commit the changes and return
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.543', 'msg'=>'Unable to reset password.'));
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'customers');

    return array('stat'=>'ok');
}
?>
