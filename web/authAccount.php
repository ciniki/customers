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
function ciniki_customers_web_authAccount(&$ciniki, $settings, $tnid, $email, $password) {

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
    // Get the customer account.
    // Only individuals, parents and admins are allowed to login
    //
    $strsql = "SELECT customers.id, "
        . "customers.parent_id, "
        . "customers.type, "
        . "customers.status, "
        . "customers.first, "
        . "customers.last, "
        . "customers.display_name, "
        . "emails.email, "
        . "IFNULL(parents.type, 0) AS parent_type, "
        . "IFNULL(parents.status, 0) AS parent_status, "
        . "IFNULL(parents.first, '') AS parent_first, "
        . "IFNULL(parents.last, '') As parent_last, "
        . "IFNULL(parents.display_name, '') AS parent_name "
        . "FROM ciniki_customer_emails AS emails "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "emails.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND customers.type IN (10, 21, 31) "
            . ") "
        . "LEFT JOIN ciniki_customers AS parents ON ("
            . "customers.parent_id = parents.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND emails.email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "AND emails.password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', 0, $email, 'ciniki.customers.353', 'Unable to authenticate');
        error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (3401)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.320', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
    }

    if( isset($rc['rows'][0]) ) {
        $customer = $rc['rows'][0];
    } else {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', 0, $email, 'ciniki.customers.354', 'Email address does not exist');
        error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (3403)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.321', 'msg'=>'Unable to authenticate.'));
    }

    //
    // Check the customer status
    //
    if( !isset($customer['status']) || $customer['status'] == 0 || $customer['status'] >= 40 ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.355', 'Login disabled');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.322', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
    }

    if( $customer['type'] == 10 ) {
        $account = $customer;
        $account['parent_child_ids'] = $customer['id'];
    } else {
        if( !isset($customer['parent_id']) || $customer['parent_id'] == 0 ) {
            ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', 0, $email, 'ciniki.customers.182', 'Account does not exist');
            error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (3402)");
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.323', 'msg'=>'Unable to authenticate.'));
        }
        if( $customer['parent_status'] == 0 || $customer['parent_status'] >= 40 ) {
            ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.336', 'Login disabled');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.336', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
        }
        $account = array(
            'id' => $customer['parent_id'],
            'type' => $customer['parent_type'],
            'status' => $customer['parent_status'],
            'display_name' => $customer['parent_name'],
            'parents' => array(),
            'children' => array(),
            'parent_child_ids' => array(),
            );
        //
        // Get the parents/children/admins/employees
        //
        $strsql = "SELECT customers.id, "
            . "customers.uuid, "
            . "customers.type, "
            . "customers.display_name, "
            . "customers.status "
            . "FROM ciniki_customers AS customers "
            . "WHERE customers.parent_id = '" . ciniki_core_dbQuote($ciniki, $account['id']) . "' "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY customers.display_name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.326', 'msg'=>'Unable to get account details', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                $account['parent_child_ids'][] = $row['id'];
                if( $row['type'] == 21 || $row['type'] == 31 ) {
                    $account['parents'][] = $row;
                } else {
                    $account['children'][] = $row;
                }
            }
        }
    }


    $_SESSION['change_log_id'] = 'web.' . date('ymd.His');
    $_SESSION['tnid'] = $ciniki['request']['tnid'];
    $_SESSION['customer'] = $customer;
    $_SESSION['account'] = $account;
    $ciniki['session']['customer'] = $customer;
    $ciniki['session']['account'] = $account;
    $ciniki['session']['tnid'] = $ciniki['request']['tnid'];
    $ciniki['session']['change_log_id'] = $_SESSION['change_log_id'];
    $ciniki['session']['user'] = array('id'=>'-2');

    ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 10, 'Login', $customer['id'], $email, '', 'Success');
    error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email success");

    return array('stat'=>'ok');
    















    //
    // Get customer information
    //
    $strsql = "SELECT ciniki_customers.id, parent_id, "
        . "ciniki_customers.first, ciniki_customers.last, ciniki_customers.display_name, "
        . "ciniki_customer_emails.email, ciniki_customers.status, ciniki_customers.member_status, ciniki_customers.membership_type, "
        . "ciniki_customers.dealer_status, ciniki_customers.distributor_status, "
        . "ciniki_customers.pricepoint_id "
        . "FROM ciniki_customer_emails, ciniki_customers "
        . "WHERE ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        //
        // Don't allow children and employees to login
        // Only allow person, business, individual, parent, admin
        // 
        . "AND ciniki_customers.type IN (1, 2, 10, 21, 31) "
        . "AND email = '" . ciniki_core_dbQuote($ciniki, $email) . "' "
        . "AND ciniki_customer_emails.customer_id = ciniki_customers.id "
        . "AND password = SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "') "
        . "";
    if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) && isset($settings['page-account-child-logins']) && $settings['page-account-child-logins'] == 'no' ) {
        $strsql .= "AND ciniki_customers.parent_id = 0 ";
    }
    $strsql .= "ORDER BY parent_id ASC "    // List parent accounts first
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', 0, $email, 'ciniki.customers.360', 'Unable to authenticate');
        error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (2601)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.360', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
    }

    //
    // Allow for email address to be attached to multiple accounts
    //
    if( isset($rc['rows']) ) {
        $children = array();
        if( count($rc['rows']) > 1 ) {
            $customer = $rc['rows'][0];
            $customers = array();
            foreach($rc['rows'] as $cust) {
                $customers[$cust['id']] = $cust;
            }
        } elseif( count($rc['rows']) == 1 ) {
            $customer = $rc['rows'][0];
            $customers = array($rc['rows'][0]['id']=>$rc['rows'][0]);
        } else {
            ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', 0, $email, 'ciniki.customers.361', 'Email address does not exist or password incorrect');
            error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (2059)");
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.361', 'msg'=>'Unable to authenticate.'));
        }
    } else {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', 0, $email, 'ciniki.customers.362', 'Email address does not exist');
        error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (736)");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.362', 'msg'=>'Unable to authenticate.'));
    }

    //
    // Check the customer status
    //
    if( !isset($customer['status']) || $customer['status'] == 0 || $customer['status'] >= 40 ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.363', 'Login disabled');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.363', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
    }
    if( isset($settings['page-account-dealers-only']) && $settings['page-account-dealers-only'] == 'yes'
        && $customer['dealer_status'] != 10 ) {
        ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.357', 'Not a dealer');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.378', 'msg'=>'Login disabled, please contact us to have the problem fixed.'));
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
                . "ciniki_customers.dealer_status, ciniki_customers.distributor_status, "
                . "ciniki_customers.pricepoint_id "
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
                ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.356', 'Unable to load child accounts');
                error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: auth $email fail (2602)");
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.356', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
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
    // Get the sequence for the customers pricepoint if set
    //
    if( ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x1000) ) {
        $strsql = "SELECT id, sequence, flags "
            . "FROM ciniki_customer_pricepoints "
//          . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $customer['pricepoint_id']) . "' "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'pricepoints', 'fname'=>'id', 
                'fields'=>array('id', 'sequence', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, $rc['err']['code'], 'Unable to load prices');
            error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: $email pricepoints not found");
            return $rc;
        }
        if( !isset($rc['pricepoints']) ) {
            $pricepoints = array();
        } else {
            $pricepoints = $rc['pricepoints'];
        }
        if( $customer['pricepoint_id'] > 0 ) {
            if( isset($pricepoints[$customer['pricepoint_id']]) ) {
                $customer['pricepoint'] = $pricepoints[$customer['pricepoint_id']];
            } else {
                error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: $email pricepoints not found");
                if( isset($customer['pricepoint']) ) {
                    unset($customer['pricepoint']);
                }
            }
        }
        if( isset($customers) && count($customers) > 0 ) {
            foreach($customers as $cid => $cust) {
                if( isset($cust['pricepoint_id']) 
                    && $cust['pricepoint_id'] > 0 
                    && isset($pricepoints[$cust['pricepoint_id']])
                    ) {
                    $customers[$cid]['pricepoint'] = $pricepoints[$cust['pricepoint_id']];
                }
            }
        }
//      if( !isset($rc['pricepoint']) ) {
//          error_log("WEB: $email pricepoint not found");
//          $customer['pricepoint_id'] = 0;
//          if( isset($customer['pricepoint']) ) { unset($customer['pricepoint']); }
//      } else {
//          $customer['pricepoint'] = array('id'=>$customer['pricepoint_id'],
//              'sequence'=>$rc['pricepoint']['sequence'],
//              'flags'=>$rc['pricepoint']['flags'],
//              );
//      }
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
            ciniki_customers_web_logAdd($ciniki, $settings, $tnid, 50, 'Login', $customer['id'], $email, 'ciniki.customers.365', 'Unable to check membership');
            error_log("WEB [" . $ciniki['tenant']['details']['name'] . "]: unable to check member season $email fail (3231)");
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.365', 'msg'=>'Unable to authenticate.', 'err'=>$rc['err']));
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
