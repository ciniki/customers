<?php
//
// Description
// -----------
// Authenticate the customer, and setup a session.
//
// Returns
// -------
// <stat='ok' />
//
function ciniki_customers_web_authFailedLogin(&$ciniki, $settings, $tnid, $email) {

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
    // Lookup the failed email address
    //
    $strsql = "SELECT emails.id, "
        . "emails.email, "
        . "emails.customer_id, "
        . "emails.flags, "
        . "emails.failed_logins, "
        . "customers.status, "
        . "customers.display_name, "
        . "customers.prefix, "
        . "customers.first, "
        . "customers.last "
        . "FROM ciniki_customer_emails AS emails, ciniki_customers AS customers "
        . "WHERE emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND emails.email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "AND emails.customer_id = customers.id "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'email');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.351', 'msg'=>'Unable to load email', 'err'=>$rc['err']));
    }
    //
    // Multiple emails can be in same business, process them all
    //
    if( isset($rc['rows']) && $rc['rows'] > 0 ) {
        $emails = $rc['rows'];
        //
        // Load email message
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'private', 'core', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $tnid, 'ciniki.customers', 'settings', '');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $customer_settings = isset($rc['settings']) ? $rc['settings'] : array();

        $dt = new DateTime('now', new DateTimezone('UTC'));
        foreach($emails as $email) { 
            // Check if already locked
            if( ($email['flags']&0x80) == 0x80 ) {
                return array('stat'=>'ok', 'locked'=>'yes');
            }
            $failed_logins = $email['failed_logins'] + 1;
            if( $failed_logins >= $settings['page-account-allowed-attempts'] ) {
                $update_args = array(
                    'failed_logins' => $failed_logins, 
                    'flags' => ($email['flags'] | 0x80),
                    'date_locked' => $dt->format('Y-m-d H:i:s'),
                    );
                $account_locked = 'yes';
            } else {
                $update_args = array('failed_logins' => $failed_logins);
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.email', $email['id'], $update_args, 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.409', 'msg'=>'Unable to update the email'));
            }

            //
            // If account was locked, send out the email
            //
            if( isset($account_locked) && $account_locked == 'yes' ) {
                if( isset($customer_settings['weblogin-locked-email-subject']) 
                    && $customer_settings['weblogin-locked-email-subject'] != ''
                    && isset($customer_settings['weblogin-locked-email-message']) 
                    && $customer_settings['weblogin-locked-email-message'] != ''
                    ) {
                    //
                    // Setup the email
                    //
                    $subject = $customer_settings['weblogin-locked-email-subject'];
                    $content = $customer_settings['weblogin-locked-email-message'];
                    $content = preg_replace("/{_name_}/", $email['display_name'], $content);
                    $content = preg_replace("/{_firstname_}/", $email['first'], $content);
                    $content = preg_replace("/{_lastname_}/", $email['last'], $content);
                    $content = preg_replace("/{_email_}/", $email['email'], $content);
                    $content = preg_replace("/{_attempts_}/", $email['failed_logins'], $content);
                    if( isset($settings['page-account-lock-hours']) ) {
                        $content = preg_replace("/{_hours_}/", $settings['page-account-lock-hours'], $content);
                    } else {
                        $content = preg_replace("/{_hours_}/", 'unlimited', $content);
                    }

                    //
                    // Queue the email
                    //
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
                    $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                        'customer_id'=>$email['customer_id'],
                        'customer_email'=>$email['email'],
                        'customer_name'=>$email['display_name'],
                        'subject'=>$subject,
                        'text_content'=>$content,
                        'html_content'=>$content,
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }

                    //
                    // Send the message
                    //
                    $ciniki['emailqueue'][] = array('tnid'=>$tnid, 'mail_id'=>$rc['id']);
                }
                if( isset($customer_settings['weblogin-locked-admin-emails']) 
                    && $customer_settings['weblogin-locked-admin-emails'] != ''
                    ) {
                    $dt->setTimezone(new DateTimezone($intl_timezone));
                    $addrs = explode(',', $customer_settings['weblogin-locked-admin-emails']);
                    foreach($addrs as $addr) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
                        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                            'customer_email'=>$email['email'],
                            'customer_name'=>$email['display_name'],
                            'subject'=>$email['display_name'] . "'s account is now locked",
                            'text_content'=>"The account for " . $email['display_name'] . " was locked at " . $dt->format('M d, Y h:i:s a') . " due to too many failed login attempts.",
                            ));
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $ciniki['emailqueue'][] = array('tnid'=>$tnid, 'mail_id'=>$rc['id']);
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'locked'=>(isset($account_locked) ? $account_locked : 'no'));
}
?>
