<?php
//
// Description
// -----------
// Find the customers with birthdays
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to search for the customers.
// start_needle:        The search string to use.
// limit:               (optional) The maximum number of results to return.  If not
//                      specified, the maximum results will be 25.
// 
// Returns
// -------
//
function ciniki_customers_birthdays($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'query'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Query'),
        'days'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Days'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.birthdays', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    if( !isset($args['days']) || $args['days'] == '' ) {
        $args['days'] = 7;
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval('P' . $args['days'] . 'D'));

    //
    // Get the customers with upcoming birthdays
    //
    if( $args['query'] == 'upcoming' ) {
        $strsql = "SELECT c.id, "
            . "c.parent_id, "
            . "c.status, "
            . "c.status AS status_text, "
            . "c.display_name, "
            . "DATE_FORMAT(c.birthdate, '%b %e, %Y') AS birthdate "
            . "FROM ciniki_customers AS c "
            . "WHERE c.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND c.birthdate <> '0000-00-00' "
            . "AND c.birthdate < '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
            . "AND c.status = 10 "
            . "AND c.type = 1 "
            . "";
        //
        // When the start and end date span end of year
        //
        if( $start_dt->format('Y') < $end_dt->format('Y') ) {
            $strsql .= "AND ( "
                    // birthdate is same month as start month
                    . "(MONTH(c.birthdate) = '" . ciniki_core_dbQuote($ciniki, $start_dt->format('m')) . "' "
                        . "AND DAY(c.birthdate) >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('d')) . "') "
                    // From start to end of year
                    . "OR MONTH(c.birthdate) > '" . ciniki_core_dbQuote($ciniki, $start_dt->format('m')) . "' "
                    // From start of year to end date
                    . "OR MONTH(c.birthdate) < '" . ciniki_core_dbQuote($ciniki, $end_dt->format('m')) . "' "
                    // Birthdate is on same month as end month
                    . "OR (MONTH(c.birthdate) = '" . ciniki_core_dbQuote($ciniki, $end_dt->format('m')) . "' "
                        . "AND DAY(c.birthdate) <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('d')) . "') "
                    . ") ";
        } 
        //
        // When the start and end date are in the same month
        //
        elseif( $start_dt->format('Y-m') == $end_dt->format('Y-m') ) {
            $strsql .= "AND ( "
                    . "MONTH(c.birthdate) = '" . ciniki_core_dbQuote($ciniki, $start_dt->format('m')) . "' "
                    . "AND DAY(c.birthdate) >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('d')) . "' "
                    . "AND DAY(c.birthdate) <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('d')) . "' "
                . ") ";
        } 
        //
        // The same year but start and end month are different
        //
        else {
            $strsql .= "AND ( "
                    // Birthdate is same month as start month
                    . "(MONTH(c.birthdate) = '" . ciniki_core_dbQuote($ciniki, $start_dt->format('m')) . "' "
                        . "AND DAY(c.birthdate) >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('d')) . "') "
                    // Month is between start and end month
                    . "OR (MONTH(c.birthdate) > '" . ciniki_core_dbQuote($ciniki, $start_dt->format('m')) . "' "
                        . "AND MONTH(c.birthdate) < '" . ciniki_core_dbQuote($ciniki, $end_dt->format('m')) . "') "
                    // Birthdate is on same month as end month
                    . "OR (MONTH(c.birthdate) = '" . ciniki_core_dbQuote($ciniki, $end_dt->format('m')) . "' "
                        . "AND DAY(c.birthdate) <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('d')) . "') "
                    . ") ";
        }
    }
    //
    // Get the customers with missing birthdays
    //
    elseif( $args['query'] == 'missing' ) {
        $strsql = "SELECT c.id, "
            . "c.parent_id, "
            . "c.status, "
            . "c.status AS status_text, "
            . "c.display_name, "
            . "DATE_FORMAT(c.birthdate, '%b %e, %Y') AS birthdate "
            . "FROM ciniki_customers AS c "
            . "WHERE c.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND c.birthdate = '0000-00-00' "
            . "AND c.status = 10 "
            . "AND c.type = 1 "
            . "";
    }
    //
    // Get the customers with incorrect birthdays
    //
    elseif( $args['query'] == 'incorrect' ) {
        $strsql = "SELECT c.id, "
            . "c.parent_id, "
            . "c.status, "
            . "c.status AS status_text, "
            . "c.display_name, "
            . "DATE_FORMAT(c.birthdate, '%b %e, %Y') AS birthdate "
            . "FROM ciniki_customers AS c "
            . "WHERE c.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND c.birthdate > '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
            . "AND c.status = 10 "
            . "AND c.type = 1 "
            . "";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'parent_id', 'display_name', 'status', 'status_text', 'birthdate'),
            'maps'=>array('status_text'=>$maps['customer']['status'])),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customers = $rc['customers'];
   
    $customer_ids = array();
    foreach($customers as $customer) {
        $customer_ids[] = $customer['id'];
    }

    //
    // Get the emails and addresses
    //
    if( count($customer_ids) > 0 ) {
        //
        // Get the emails
        //
        $strsql = "SELECT id, customer_id, email "
            . "FROM ciniki_customer_emails "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'emails', 'fname'=>'id', 'fields'=>array('email')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $emails = $rc['customers'];

        //
        // Get the addresses
        //
        $strsql = "SELECT id, customer_id, address1, address2, city, province, postal, country "
            . "FROM ciniki_customer_addresses "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'addresses', 'fname'=>'id', 'fields'=>array('address1', 'address2', 'city', 'province', 'postal', 'country')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $addresses = $rc['customers'];

        foreach($customers as $cid => $customer) {
            //
            // Add emails to customer
            //
            if( isset($emails[$customer['id']]['emails']) ) {
                foreach($emails[$customer['id']]['emails'] as $email) {
                    if( !isset($customers[$cid]['email']) ) {
                        $customers[$cid]['email'] = $email['email'];
                    } else {
                        $customers[$cid]['email'] .= ', ' . $email['email'];
                    }
                }
            }
            //
            // Add addresses to customer
            //
            if( isset($addresses[$customer['id']]['addresses']) ) {
                foreach($addresses[$customer['id']]['addresses'] as $address) {
                    $addr = '';
                    if( isset($address['address1']) && $address['address1'] != '' ) {
                        $addr .= $address['address1'] . '<br/>';
                    }
                    if( isset($address['address2']) && $address['address2'] != '' ) {
                        $addr .= $address['address2'] . '<br/>';
                    }
                    $city = '';
                    if( isset($address['city']) && $address['city'] != '' ) {
                        $city .= $address['city'];
                    }
                    if( isset($address['province']) && $address['province'] != '' ) {
                        $city .= ($city != '' ? ', ' : '') . $address['province'];
                    }
                    if( isset($address['postal']) && $address['postal'] != '' ) {
                        $city .= ($city != '' ? '  ' : '') . $address['postal'];
                    }
                    if( $city != '' ) {
                        $addr .= $city . '<br/>';
                    }
                    if( isset($address['country']) && $address['country'] != '' ) {
                        $addr .= $address['country'] . '<br/>';
                    }
                    if( $addr != '' ) {
                        if( !isset($customers[$cid]['address']) ) {
                            $customers[$cid]['address'] = $addr;
                        } else {
                            $customers[$cid]['address'] .= '<br/>' . $addr;
                        }
                    }
                }
            }
        }
    }
    
    return array('stat'=>'ok', 'customers'=>$customers);
}
?>
