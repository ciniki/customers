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
function ciniki_customers_web_auth(&$ciniki, $settings, $tnid, $email, $password) {

    //
    // Check if account flag set, then redirect to authAccount
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'authAccount');
        return ciniki_customers_web_authAccount($ciniki, $settings, $tnid, $email, $password);
    }

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'logAdd');

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
    // Get customer information
    //
    $strsql = "SELECT ciniki_customers.id, parent_id, "
        . "ciniki_customers.first, ciniki_customers.last, ciniki_customers.display_name, "
        . "ciniki_customer_emails.email, ciniki_customers.status, ciniki_customers.member_status, ciniki_customers.membership_type, "
        . "ciniki_customers.dealer_status, ciniki_customers.distributor_status, "
        . "ciniki_customer_emails.id AS email_id, "
        . "ciniki_customer_emails.failed_logins, "
        . "ciniki_customer_emails.flags, "
        . "TIMESTAMPDIFF(HOUR, UTC_TIMESTAMP(), ciniki_customer_emails.date_locked) AS lock_age "
        . "FROM ciniki_customer_emails, ciniki_customers "
        . "WHERE ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.type IN (1, 2, 10, 21, 31) "
        . "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "";
    if( isset($settings['page-account-allowed-attempts']) && $settings['page-account-allowed-attempts'] > 0 
        && isset($settings['page-account-lock-hours']) && $settings['page-account-lock-hours'] > 0 
        ) {
        $lock_hour_dt = new DateTime('now', new DateTimeZone('UTC'));
        $lock_hour_dt->sub(new DateInterval('PT' . $settings['page-account-lock-hours'] . 'H'));
        $strsql .= "AND ("
            . "(ciniki_customer_emails.flags&0x80) = 0 "
            . "OR "
            . "ciniki_customer_emails.date_locked < '" . ciniki_core_dbQuote($ciniki, $lock_hour_dt->format('Y-m-d H:i:s')) . "' "
            . ") ";
    } else {
        $strsql .= "AND (ciniki_customer_emails.flags&0x80) = 0 ";
    }
    $strsql .= "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
        . "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
        . "";
    //
    // Don't allow children and employees to login
    // Only allow person, business, individual, parent, admin
    // 
    if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) && isset($settings['page-account-child-logins']) && $settings['page-account-child-logins'] == 'no' ) {
        $strsql .= "AND ciniki_customers.parent_id = 0 ";
    }
    $strsql .= "ORDER BY parent_id ASC "    // List parent accounts first
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', 0, $email, 'ciniki.customers.180', 'Unable to authenticate');
        error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (2601)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.180', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
    }

    //
    // Allow for email address to be attached to multiple accounts
    //
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $children = array();
        if( count($rc['rows']) == 1 ) {
            $customer = $rc['rows'][0];
            $customers = array($rc['rows'][0]['id']=>$rc['rows'][0]);
        } else {
            $customer = $rc['rows'][0];
            $customers = array();
            foreach($rc['rows'] as $cust) {
                $customers[$cust['id']] = $cust;
            }
        } 
    } else {
        //
        // Check if autolock is enabled, lookup email
        // FIXME: This is not yet in the authAccount file
        //
        $account_locked = 'no';
        if( isset($settings['page-account-allowed-attempts']) && $settings['page-account-allowed-attempts'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'authFailedLogin');
            $rc = ciniki_customers_web_authFailedLogin($ciniki, $settings, $tnid, $email);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.351', 'msg'=>'Unable to load email', 'err'=>$rc['err']));
            }
            if( isset($rc['locked']) ) {
                $account_locked = $rc['locked'];
            }
        }

        //
        // Return error
        //
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', 0, $email, 'ciniki.customers.182', 'Email address does not exist');
        error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (736)");
        if( $account_locked == 'yes' ) {
            return array('stat'=>'locked', 'err'=>array('code'=>'ciniki.customers.392', 'msg'=>'Unable to authenticate, account locked.'));
        } 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.182', 'msg'=>'Unable to authenticate.'));
    }

    //
    // Check the customer status
    //
    if( !isset($customer['status']) || $customer['status'] == 0 || $customer['status'] >= 40 ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.183', 'Login disabled');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.183', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
    }
    if( isset($settings['page-account-dealers-only']) && $settings['page-account-dealers-only'] == 'yes'
        && $customer['dealer_status'] != 10 ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.218', 'Not a dealer');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.218', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
    }

    //
    // Check for account locked, and if lock-hours specified, then still within the lock-hours
    // 
    if( isset($settings['page-account-lock-hours']) && $settings['page-account-lock-hours'] > 0 ) {
        $lock_hours = $settings['page-account-lock-hours'];
    } else {
        $lock_hours = 0;
    }
    if( ($customer['flags']&0x80) == 0x80 && ($lock_hours == 0 || $lock_hours < $customer['lock_age']) ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.390', 'Not a dealer');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.390', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
    }

    //
    // Check for other accounts with the same email/password or child accounts
    //
    if( isset($ciniki['tenant']['modules']['ciniki.customers']['flags']) 
        && ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x200000) 
        ) {
        //
        // Get all the parent customer_ids
        //
        $customer_ids = array($customer['id']);
        if( isset($customers) ) {
            foreach($customers as $cust) {
                if( $cust['parent_id'] == 0 ) {
                    $customer_ids[] = $cust['id'];
                }
            }
        } elseif( $customer['parent_id'] == 0 ) {
            $customer_ids = array($customer['id']);
        }

        //
        // Get the child accounts
        //
        if( count($customer_ids) > 0 ) {
            $strsql = "SELECT ciniki_customers.id, parent_id, "
                . "ciniki_customers.first, ciniki_customers.last, ciniki_customers.display_name, "
                . "ciniki_customers.status, ciniki_customers.member_status, "
                . "ciniki_customers.dealer_status, ciniki_customers.distributor_status "
                . "FROM ciniki_customers "
                . "WHERE ciniki_customers.parent_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//              . "AND ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//              . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//              . "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
//              . "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
//              . "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
                . "ORDER BY parent_id ASC "     // List parent accounts first
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
            if( $rc['stat'] != 'ok' ) {
                ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.184', 'Unable to load child accounts');
                error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (2602)");
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.184', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $cust) {
                    //
                    // If the children are unable to login, add them to the children list
                    //
                    if( isset($settings['page-account-child-logins']) && $settings['page-account-child-logins'] == 'yes' ) {
                        if( !isset($customers[$cust['id']]) ) {
                            $customers[$cust['id']] = $cust;
                        }
                    } else {
                        $children[$cust['id']] = $cust;
                    }
                }
            }
        }
    }

    //
    // Reset the failed_logins back to zero
    //
    if( $customer['failed_logins'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.email', $customer['email_id'], array(
            'failed_logins' => 0,
            'flags' => $customer['flags'] & 0xFF7F,
            'date_locked' => '',
            ), 0x07);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.181', 'msg'=>'Unable to update the email'));
        }
    }

    //
    // Create a session for the customer
    //
//  session_start();
    $_SESSION['change_log_id'] = 'web.' . date('ymd.His');
    $_SESSION['tnid'] = $ciniki['request']['tnid'];
    $customer['price_flags'] = 0x01;
    if( $customer['status'] < 50 ) {
        // they can see prices if not suspended/deleted
        $customer['price_flags'] |= 0x10;
    }

    //
    // If the account holder is allowed to add children to the account, option also has to be enabled in web/account
    //
    $customer['children-allowed'] = 'no';

    //
    // Check if memberships enabled and if customer is part of current season
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02000000) ) {   // Check if membership seasons is active
        //
        // Check for Latest date the members price is valid to
        //
        $strsql = "SELECT MAX(ciniki_customer_seasons.end_date) AS membership_expiration "
            . "FROM ciniki_customer_season_members, ciniki_customer_seasons "
            . "WHERE ciniki_customer_season_members.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "AND ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['tnid']) . "' "
            . "AND ciniki_customer_season_members.status = 10 " // Active for the season
            . "AND ciniki_customer_season_members.season_id = ciniki_customer_seasons.id "
            . "AND ciniki_customer_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.185', 'Unable to check membership');
            error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: unable to check member season $email fail (3231)");
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.185', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
        }
        if( isset($rc['customer']['membership_expiration']) ) {
            $dt = new DateTime($rc['customer']['membership_expiration'], new DateTimeZone($intl_timezone));
            $customer['membership_expiration'] = $dt->format('U');
            $dt = new DateTime('now', new DateTimeZone($intl_timezone));
            //
            // Check the membership hasn't expired yet
            //
            if( $customer['membership_expiration'] > $dt->format('U') ) {
                $customer['price_flags'] |= 0x20;
            }
        }
        //
        // Check if children should be allowed
        //
        if( isset($settings['page-account-children-update']) && $settings['page-account-children-update'] == 'yes' 
            && $customer['membership_type'] > 0
            && isset($settings['page-account-children-member-' . $customer['membership_type'] . '-update']) 
            && $settings['page-account-children-member-' . $customer['membership_type'] . '-update'] == 'yes'
            ) {
            $customer['children-allowed'] = 'yes';
        }
        if( isset($settings['page-account-children-update']) && $settings['page-account-children-update'] == 'yes' 
            && $customer['membership_type'] == 0
            ) {
            $customer['children-allowed'] = 'yes';
        }
    } 
    elseif( $customer['member_status'] == 10 ) {
        $customer['price_flags'] |= 0x20;
        if( $customer['membership_type'] > 0 
            && isset($settings['page-account-children-member-' . $customer['membership_type'] . '-update']) 
            && $settings['page-account-children-member-' . $customer['membership_type'] . '-update'] == 'yes'
            ) {
            $customer['children-allowed'] = 'yes';
        }
    }
    elseif( isset($settings['page-account-children-update']) && $settings['page-account-children-update'] == 'yes'
        && isset($settings['page-account-children-member-non-update']) && $settings['page-account-children-member-non-update'] == 'yes'
        ) {
        $customer['children-allowed'] = 'yes';
    }
    if( $customer['dealer_status'] == 10 ) {
        $customer['price_flags'] |= 0x40;
    }
    if( $customer['distributor_status'] == 10 ) {
        $customer['price_flags'] |= 0x80;
    }
    foreach($customers as $cid => $cust) {
        $customers[$cid]['price_flags'] = 0x01;
        if( $cust['status'] < 50 ) {
            $customers[$cid]['price_flags'] |= 0x10;
        }
        if( $cust['member_status'] == 10 ) {
            $customers[$cid]['price_flags'] |= 0x20;
        }
        if( $cust['dealer_status'] == 10 ) {
            $customers[$cid]['price_flags'] |= 0x40;
        }
        if( $cust['distributor_status'] == 10 ) {
            $customers[$cid]['price_flags'] |= 0x80;
        }
    }
    $login = array('email'=>$email);
    $_SESSION['login'] = $login;
    $_SESSION['customer'] = $customer;
    $_SESSION['customers'] = $customers;
    $_SESSION['children'] = $children;
    $ciniki['session']['login'] = $login;
    $ciniki['session']['customer'] = $customer;
    $ciniki['session']['customers'] = $customers;
    $ciniki['session']['children'] = $children;
    $ciniki['session']['tnid'] = $ciniki['request']['tnid'];
    $ciniki['session']['change_log_id'] = $_SESSION['change_log_id'];
    $ciniki['session']['user'] = array('id'=>'-2');

    ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 10, 'Login', $customer['id'], $email, '', 'Success');
    error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email success");

    return array('stat'=>'ok');
}
?>
