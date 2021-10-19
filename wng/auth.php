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
function ciniki_customers_wng_auth(&$ciniki, $tnid, &$request, $email, $hashed_password) {

    //
    // Check if account flag set, then redirect to authAccount
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'authAccount');
        return ciniki_customers_wng_authAccount($ciniki, $tnid, $request, $email, $hashed_password);
    }

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'logAdd');

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
    $strsql = "SELECT customers.id, "
        . "customers.parent_id, "
        . "customers.first, "
        . "customers.last, "
        . "customers.display_name, "
        . "emails.email, "
        . "customers.status, "
        . "customers.member_status, "
        . "customers.membership_type, "
        . "emails.id AS email_id, "
        . "emails.failed_logins, "
        . "emails.flags, "
        . "TIMESTAMPDIFF(HOUR, UTC_TIMESTAMP(), emails.date_locked) AS lock_age "
        . "FROM ciniki_customer_emails AS emails, ciniki_customers AS customers "
        . "WHERE emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customers.parent_id = 0 "    // Don't allow children to login
        . "AND customers.type IN (1, 2, 10, 21, 31) "
        . "AND emails.email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "AND emails.customer_id = customers.id "
        . "AND emails.password = '" . ciniki_core_dbQuote($ciniki, $hashed_password) . "' "
        . "";
    if( isset($settings['account-allowed-attempts']) && $settings['account-allowed-attempts'] > 0 
        && isset($settings['account-lock-hours']) && $settings['account-lock-hours'] > 0 
        ) {
        $lock_hour_dt = new DateTime('now', new DateTimeZone('UTC'));
        $lock_hour_dt->sub(new DateInterval('PT' . $settings['account-lock-hours'] . 'H'));
        $strsql .= "AND ("
            . "(emails.flags&0x80) = 0 "
            . "OR "
            . "emails.date_locked < '" . ciniki_core_dbQuote($ciniki, $lock_hour_dt->format('Y-m-d H:i:s')) . "' "
            . ") ";
    } else {
        $strsql .= "AND (emails.flags&0x80) = 0 ";
    }
    $strsql .= "ORDER BY parent_id ASC "    // List parent accounts first
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Login', 0, $email, 'ciniki.customers.451', 'Unable to authenticate');
        //error_log("WEB [" . $ciniki['tenant']['name'] . "]: auth $email fail (2601)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.452', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
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
        if( isset($settings['account-allowed-attempts']) && $settings['account-allowed-attempts'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'authFailedLogin');
            $rc = ciniki_customers_wng_authFailedLogin($ciniki, $tnid, $request, $email);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.453', 'msg'=>'Unable to load email', 'err'=>$rc['err']));
            }
            if( isset($rc['locked']) ) {
                $account_locked = $rc['locked'];
            }
        }

        //
        // Return error
        //
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Login', 0, $email, 'ciniki.customers.454', 'Email address does not exist');
        //error_log("WEB [" . $ciniki['tenant']['name'] . "]: auth $email fail (736)");
        if( $account_locked == 'yes' ) {
            return array('stat'=>'locked', 'err'=>array('code'=>'ciniki.customers.455', 'msg'=>'Unable to authenticate, account locked.'));
        } 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.456', 'msg'=>'Unable to authenticate.'));
    }

    //
    // Check the customer status
    //
    if( !isset($customer['status']) || $customer['status'] == 0 || $customer['status'] >= 40 ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Login', $customer['id'], $email, 'ciniki.customers.457', 'Login disabled');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.458', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
    }

    //
    // Check for account locked, and if lock-hours specified, then still within the lock-hours
    // 
    if( isset($settings['account-lock-hours']) && $settings['account-lock-hours'] > 0 ) {
        $lock_hours = $settings['account-lock-hours'];
    } else {
        $lock_hours = 0;
    }
    if( ($customer['flags']&0x80) == 0x80 && ($lock_hours == 0 || $lock_hours < $customer['lock_age']) ) {
        ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Login', $customer['id'], $email, 'ciniki.customers.459', 'Not a dealer');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.460', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
    }

    //
    // Check for other accounts with the same email/password or child accounts
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x200000) ) {
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
            $strsql = "SELECT customers.id, "
                . "customers.parent_id, "
                . "customers.first, "
                . "customers.last, "
                . "customers.display_name, "
                . "customers.status, "
                . "customers.member_status, "
                . "customers.dealer_status, "
                . "customers.distributor_status "
                . "FROM ciniki_customers AS customers "
                . "WHERE customers.parent_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY customers.parent_id ASC "     // List parent accounts first
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
            if( $rc['stat'] != 'ok' ) {
                ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 50, 'Login', $customer['id'], $email, 'ciniki.customers.461', 'Unable to load child accounts');
                //error_log("WEB [" . $ciniki['tenant']['name'] . "]: auth $email fail (2602)");
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.462', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $cust) {
                    $children[$cust['id']] = $cust;
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.463', 'msg'=>'Unable to update the email'));
        }
    }

    //
    // Create a session for the customer
    //
    $customer['price_flags'] = 0x01;
    if( $customer['status'] < 50 ) {
        // they can see prices if not suspended/deleted
        $customer['price_flags'] |= 0x10;
    }

    //
    // If the account holder is allowed to add children to the account, option also has to be enabled in web/account
    //
    $customer['children-allowed'] = 'no';
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x200000) ) {
        // Children are enabled for tenant, allow parents to manage children
        // Children not allowed to login
        $customer['children-allowed'] = 'yes';
    }

    //
    // Check if memberships enabled
    //
    if( $customer['member_status'] == 10 ) {
        $customer['price_flags'] |= 0x20;
/*        if( $customer['membership_type'] > 0 
            && isset($settings['account-children-member-' . $customer['membership_type'] . '-update']) 
            && $settings['account-children-member-' . $customer['membership_type'] . '-update'] == 'yes'
            ) {
            $customer['children-allowed'] = 'yes';
        } */
    }
/*    elseif( isset($settings['account-children-update']) && $settings['account-children-update'] == 'yes'
        && isset($settings['account-children-member-non-update']) && $settings['account-children-member-non-update'] == 'yes'
        ) {
        $customer['children-allowed'] = 'yes';
    } */
/*    if( $customer['dealer_status'] == 10 ) {
        $customer['price_flags'] |= 0x40;
    }
    if( $customer['distributor_status'] == 10 ) {
        $customer['price_flags'] |= 0x80;
    } */
    foreach($customers as $cid => $cust) {
        $customers[$cid]['price_flags'] = 0x01;
        if( $cust['status'] < 50 ) {
            $customers[$cid]['price_flags'] |= 0x10;
        }
        if( $cust['member_status'] == 10 ) {
            $customers[$cid]['price_flags'] |= 0x20;
        }
/*        if( $cust['dealer_status'] == 10 ) {
            $customers[$cid]['price_flags'] |= 0x40;
        }
        if( $cust['distributor_status'] == 10 ) {
            $customers[$cid]['price_flags'] |= 0x80;
        } */
    }
    $login = array('email'=>$email);
    $request['session']['login'] = $login;
    $request['session']['customer'] = $customer;
    $request['session']['customers'] = $customers;
    $request['session']['children'] = $children;

    ciniki_customers_wng_logAdd($ciniki, $tnid, $request, 10, 'Login', $customer['id'], $email, '', 'Success');
    //error_log("WEB [" . $ciniki['tenant']['name'] . "]: auth $email success");

    return array('stat'=>'ok');
}
?>
