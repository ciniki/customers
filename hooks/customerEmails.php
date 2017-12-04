<?php
//
// Description
// -----------
// This function will get the customer information and the emails that are used for mailing lists.
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
function ciniki_customers_hooks_customerEmails($ciniki, $tnid, $args) {

    if( !isset($args['customer_id']) || $args['customer_id'] == '' || $args['customer_id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.15', 'msg'=>'No customer specified'));
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the customer details and emails
    //
    $strsql = "SELECT ciniki_customers.id, ciniki_customers.parent_id, eid, type, prefix, first, middle, last, suffix, "
        . "display_name, company, department, title, salesrep_id, "
        . "status, dealer_status, distributor_status, "
        . "ciniki_customer_emails.id AS email_id, ciniki_customer_emails.email, "
        . "IFNULL(DATE_FORMAT(birthdate, '" . ciniki_core_dbQuote($ciniki, '%M %d, %Y') . "'), '') AS birthdate, "
        . "pricepoint_id, notes "
        . "FROM ciniki_customers "
        . "LEFT JOIN ciniki_customer_emails ON ("
            . "ciniki_customers.id = ciniki_customer_emails.customer_id "
            . "AND ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_customer_emails.flags&0x10) = 0 " // Not flagged as "NO NOT EMAIL"
            . ") "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' ";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'parent_id', 'eid', 'type', 
                'prefix', 'first', 'middle', 'last', 'suffix', 'display_name',
                'status', 'dealer_status', 'distributor_status',
                'company', 'department', 'title', 'salesrep_id', 'pricepoint_id',
                'notes', 'birthdate')),
        array('container'=>'emails', 'fname'=>'email_id', 'name'=>'email',
            'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'address'=>'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.16', 'msg'=>'Invalid customer'));
    }
    $customer = array_pop($rc['customers']);

    return array('stat'=>'ok', 'customer'=>$customer);
}
?>
