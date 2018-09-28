<?php
//
// Description
// -----------
// This function will return the status of a customer.
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_hooks_customerDetails($ciniki, $tnid, $args) {

    if( !isset($args['customer_id']) || $args['customer_id'] == '' || $args['customer_id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.13', 'msg'=>'No customer specified'));
    }
    $customer_id = $args['customer_id'];

    //
    // Get the types of customers available for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
    $rc = ciniki_customers_getCustomerTypes($ciniki, $tnid); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $types = $rc['types'];

    //
    // Get the settings for customer module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $tnid); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // Get the maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki, $tnid); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Get the customer details and emails
    //
    $strsql = "SELECT ciniki_customers.id, eid, type, prefix, first, middle, last, suffix, "
        . "display_name, company, department, title, salesrep_id, "
        . "phone_home, phone_cell, phone_work, phone_fax, "
        . "primary_email, alternate_email, "
        . (isset($args['full_bio']) && $args['full_bio'] == 'yes' ? "full_bio, " : '')
        . "status, dealer_status, distributor_status, "
        . "member_status, member_status AS member_status_text, member_lastpaid, "
        . "ciniki_customer_emails.id AS email_id, ciniki_customer_emails.email, "
        . "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, '%M %d, %Y') . "'), '') AS birthdate, "
        . "pricepoint_id, notes "
        . "FROM ciniki_customers "
        . "LEFT JOIN ciniki_customer_emails ON ("
            . "ciniki_customers.id = ciniki_customer_emails.customer_id "
            . "AND ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "";
    $fields = array('id', 'eid', 'type', 'prefix', 'first', 'middle', 'last', 'suffix', 'display_name',
        'phone_home', 'phone_work', 'phone_cell', 'phone_fax',
        'primary_email', 'alternate_email',
        'status', 'dealer_status', 'distributor_status',
        'member_status', 'member_status_text', 'member_lastpaid',
        'company', 'department', 'title', 'salesrep_id', 'pricepoint_id',
        'notes', 'birthdate');
    if( isset($args['full_bio']) && $args['full_bio'] == 'yes' ) {
        $fields[] = 'full_bio';
    }
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>$fields,
            'utctotz'=>array('member_lastpaid'=>array('format'=>'M Y', 'timezone'=>'UTC')),
            'maps'=>array('member_status_text'=>$maps['customer']['member_status'],)
            ),
        array('container'=>'emails', 'fname'=>'email_id', 'name'=>'email',
            'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'address'=>'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.14', 'msg'=>'Invalid customer'));
    }
    $customer = $rc['customers'][0]['customer'];

    //
    // Get the customer addresses
    //
    if( isset($args['addresses']) && $args['addresses'] == 'yes' ) {
        $strsql = "SELECT id, customer_id, "
            . "address1, address2, city, province, postal, country, flags, phone "
            . "FROM ciniki_customer_addresses "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'addresses', 'fname'=>'id', 'name'=>'address',
                'fields'=>array('id', 'customer_id', 'address1', 'address2', 'city', 'province', 'postal', 
                    'country', 'flags', 'phone')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['addresses']) ) {
            $customer['addresses'] = $rc['addresses'];
        }
    }

    // 
    // Get customer subscriptions if module is enabled
    //
    if( isset($modules['ciniki.subscriptions']) && isset($args['subscriptions']) && $args['subscriptions'] == 'yes' ) {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, "
            . "ciniki_subscription_customers.id AS customer_subscription_id, "
            . "ciniki_subscriptions.description, ciniki_subscription_customers.status "
            . "FROM ciniki_subscriptions "
            . "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
                . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "') "
            . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 10 "
            . "ORDER BY ciniki_subscriptions.name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
                'fields'=>array('id', 'name', 'description', 'customer_subscription_id', 'status')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['subscriptions']) ) {
            $customer['subscriptions'] = $rc['subscriptions'];
        }
    }

    //
    // Get the phone numbers for the customer
    //
    if( isset($args['phones']) && $args['phones'] == 'yes' ) {
        $strsql = "SELECT id, phone_label, phone_number, flags "
            . "FROM ciniki_customer_phones "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'phones', 'fname'=>'id',
                'fields'=>array('id', 'phone_label', 'phone_number', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['phones']) ) {
            $customer['phones'] = $rc['phones'];
        }
    }

    //
    // Build the details array
    //
    $details = array();
    if( !isset($args['name']) || $args['name'] == 'yes' ) {
        $details[] = array('detail'=>array('label'=>'Name', 'value'=>$customer['display_name']));
    }
    if( isset($customer['phones']) ) {
        foreach($customer['phones'] as $phone) {
            $details[] = array('detail'=>array('label'=>$phone['phone_label'], 'value'=>$phone['phone_number']));
        }
    }
    if( isset($customer['emails']) ) {
        $emails = '';
        $comma = '';
        foreach($customer['emails'] as $e => $email) {
            $emails .= $comma . $email['email']['address'];
            $comma = ', ';
        }
        if( count($customer['emails']) > 1 ) {
            $details[] = array('detail'=>array('label'=>'Emails', 'value'=>$emails));
        } else {
            $details[] = array('detail'=>array('label'=>'Email', 'value'=>$emails));
        }
    }
    if( isset($customer['addresses']) ) {
        foreach($customer['addresses'] as $a => $address) {
            $label = '';
            if( count($customer['addresses']) > 1 ) {
                $flags = $address['address']['flags'];
                $comma = '';
                if( ($flags&0x01) == 0x01 ) { $label .= $comma . 'Shipping'; $comma = ', ';}
                if( ($flags&0x02) == 0x02 ) { $label .= $comma . 'Billing'; $comma = ', ';}
                if( ($flags&0x04) == 0x04 ) { $label .= $comma . 'Mailing'; $comma = ', ';}
                if( ($flags&0x08) == 0x08 ) { $label .= $comma . 'Public'; $comma = ', ';}
            }
            if( $label == '' ) { 
                $label = 'Address'; 
            }
            $joined_address = '';
            if( isset($address['address']['address1']) && $address['address']['address1'] != '' ) {
                $joined_address .= $address['address']['address1'] . "\n";
            }
            if( isset($address['address']['address2']) && $address['address']['address2'] != '' ) {
                $joined_address .= $address['address']['address2'] . "\n";
            }
            $city = '';
            $comma = '';
            if( isset($address['address']['city']) && $address['address']['city'] != '' ) {
                $city = $address['address']['city'];
                $comma = ', ';
            }
            if( isset($address['address']['province']) && $address['address']['province'] != '' ) {
                $city .= $comma . $address['address']['province'];
                $comma = ', ';
            }
            if( isset($address['address']['postal']) && $address['address']['postal'] != '' ) {
                $city .= $comma . ' ' . $address['address']['postal'];
                $comma = ', ';
            }
            if( $city != '' ) {
                $joined_address .= $city . "\n";
            }
            $customer['addresses'][$a]['address']['joined'] = $joined_address;
            $details[] = array('detail'=>array('label'=>$label, 'value'=>$joined_address));
        }
    }
    if( isset($customer['subscriptions']) ) {
        $subscriptions = '';
        $comma = '';
        foreach($customer['subscriptions'] as $sub => $subdetails) {
            $subscriptions .= $comma . $subdetails['subscription']['name'];
            $comma = ', ';
        }
        if( $subscriptions != '' ) {
            $details[] = array('detail'=>array('label'=>'Subscriptions', 'value'=>$subscriptions));
        }
    }

    return array('stat'=>'ok', 'customer'=>$customer, 'details'=>$details);
}
?>
