<?php
//
// Description
// -----------
// This function will return the details for a customer without the required middle array for rest.
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
function ciniki_customers_hooks_customerDetails2($ciniki, $tnid, $args) {

    if( !isset($args['customer_id']) || $args['customer_id'] == '' || $args['customer_id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.353', 'msg'=>'No customer specified'));
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Get the customer details and emails
    //
    $strsql = "SELECT ciniki_customers.id, ciniki_customers.uuid, eid, type, prefix, first, middle, last, suffix, "
        . "display_name, company, department, title, "
        . "phone_home, phone_cell, phone_work, phone_fax, "
        . "primary_email, alternate_email, "
        . (isset($args['full_bio']) && $args['full_bio'] == 'yes' ? "full_bio, " : '')
        . "status, dealer_status, distributor_status, "
        . "member_status, member_status AS member_status_text, member_lastpaid, member_expires, "
        . "ciniki_customer_emails.id AS email_id, "
        . "ciniki_customer_emails.email, "
        . "ciniki_customer_emails.flags AS email_flags, "
        . "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, '%M %d, %Y') . "'), '') AS birthdate, "
        . "notes "
        . "FROM ciniki_customers "
        . "LEFT JOIN ciniki_customer_emails ON ("
            . "ciniki_customers.id = ciniki_customer_emails.customer_id "
            . "AND ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "";
    $fields = array('id', 'uuid', 'eid', 'type', 'prefix', 'first', 'middle', 'last', 'suffix', 'display_name',
        'phone_home', 'phone_work', 'phone_cell', 'phone_fax',
        'primary_email', 'alternate_email',
        'status', 'dealer_status', 'distributor_status',
        'member_status', 'member_status_text', 'member_lastpaid', 'member_expires',
        'company', 'department', 'title', 
        'notes', 'birthdate');
    if( isset($args['full_bio']) && $args['full_bio'] == 'yes' ) {
        $fields[] = 'full_bio';
    }
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id',
            'fields'=>$fields,
            'utctotz'=>array(
                'member_lastpaid'=>array('format'=>'M d, Y', 'timezone'=>'UTC'),
                'member_expires'=>array('format'=>'M d, Y', 'timezone'=>'UTC'),
                ),
            'maps'=>array('member_status_text'=>$maps['customer']['member_status'],)
            ),
        array('container'=>'emails', 'fname'=>'email_id',
            'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'address'=>'email', 'flags'=>'email_flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.354', 'msg'=>'Invalid customer'));
    }
    $customer = $rc['customers'][0];

    //
    // Get the customer addresses
    //
    if( isset($args['addresses']) && ($args['addresses'] == 'yes' || $args['addresses'] == 'billing') ) {
        $strsql = "SELECT id, customer_id, "
            . "address1, address2, city, province, postal, country, flags, phone "
            . "FROM ciniki_customer_addresses "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
            . "";
        if( $args['addresses'] == 'billing' ) {
            $strsql .= "AND (flags&0x02) = 0x02 ";
        }
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'addresses', 'fname'=>'id',
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
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'subscriptions', 'fname'=>'id',
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
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
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
        $details[] = array('label'=>'Name', 'value'=>$customer['display_name'], 'type'=>'name');
    }
    if( !isset($args['companydetails']) || $args['companydetails'] == 'yes' ) {
        
        if( $customer['company'] != '' && stristr($customer['display_name'], $customer['company']) === false ) {
            $details[] = array('label'=>'Company', 'value'=>$customer['company'], 'type'=>'name');
        } elseif( $customer['company'] != '' && $customer['company'] == $customer['display_name'] ) {
            $name = $customer['prefix'];
            $name .= ($customer['first'] != '' ? ' ' : '') . $customer['first'];
            $name .= ($customer['middle'] != '' ? ' ' : '') . $customer['middle'];
            $name .= ($customer['last'] != '' ? ' ' : '') . $customer['last'];
            $name .= ($customer['suffix'] != '' ? ' ' : '') . $customer['suffix'];
            
            $details[] = array('label'=>'Contact', 'value'=>$name, 'type'=>'name');
        }
        if( $customer['department'] != '' ) {
            $details[] = array('label'=>'Department', 'value'=>$customer['department'], 'type'=>'title');
        }
        if( $customer['title'] != '' ) {
            $details[] = array('label'=>'Title', 'value'=>$customer['title'], 'type'=>'title');
        }
    }
    if( isset($customer['phones']) ) {
        foreach($customer['phones'] as $phone) {
            $details[] = array('label'=>$phone['phone_label'], 'value'=>$phone['phone_number'], 'type'=>'phone');
        }
    }
    if( isset($customer['emails']) ) {
        $emails = '';
        $comma = '';
        foreach($customer['emails'] as $e => $email) {
            $emails .= $comma . $email['address'];
            $comma = ', ';
        }
        if( count($customer['emails']) > 1 ) {
            $details[] = array('label'=>'Emails', 'value'=>$emails, 'type'=>'email');
        } else {
            $details[] = array('label'=>'Email', 'value'=>$emails, 'type'=>'email');
        }
    }
    if( isset($customer['addresses']) ) {
        foreach($customer['addresses'] as $a => $address) {
            $label = '';
            if( count($customer['addresses']) > 1 ) {
                $flags = $address['flags'];
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
            if( isset($address['address1']) && $address['address1'] != '' ) {
                $joined_address .= $address['address1'] . "\n";
            }
            if( isset($address['address2']) && $address['address2'] != '' ) {
                $joined_address .= $address['address2'] . "\n";
            }
            $city = '';
            $comma = '';
            if( isset($address['city']) && $address['city'] != '' ) {
                $city = $address['city'];
                $comma = ', ';
            }
            if( isset($address['province']) && $address['province'] != '' ) {
                $city .= $comma . $address['province'];
                $comma = ', ';
            }
            if( isset($address['postal']) && $address['postal'] != '' ) {
                $city .= $comma . ' ' . $address['postal'];
                $comma = ', ';
            }
            if( $city != '' ) {
                $joined_address .= $city . "\n";
            }
            $customer['addresses'][$a]['label'] = $label;
            $customer['addresses'][$a]['joined'] = $joined_address;
            $details[] = array('label'=>$label, 'value'=>$joined_address, 'type'=>'address');
        }
    }
    if( isset($customer['subscriptions']) ) {
        $subscriptions = '';
        $comma = '';
        foreach($customer['subscriptions'] as $sub => $subdetails) {
            $subscriptions .= $comma . $subdetails['name'];
            $comma = ', ';
        }
        if( $subscriptions != '' ) {
            $details[] = array('label'=>'Subscriptions', 'value'=>$subscriptions, 'type'=>'subscription');
        }
    }

    if( isset($args['membership']) && $args['membership'] == 'yes' ) {
        $details[] = array('label'=>'Member', 'value'=>$customer['member_status_text'], 'type'=>'membership');
        if( isset($customer['member_status']) && $customer['member_status'] > 0 ) {
            $details[] = array('label'=>'Last Paid', 'value'=>$customer['member_lastpaid'], 'type'=>'membership');
            $details[] = array('label'=>'Expires', 'value'=>$customer['member_expires'], 'type'=>'membership');
        }
    }

    return array('stat'=>'ok', 'customer'=>$customer, 'details'=>$details);
}
?>
