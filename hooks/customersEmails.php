<?php
//
// Description
// -----------
// This function will get the customer information and the emails that are used for mailing lists.
//
// Arguments
// ---------
// ciniki:
// tnid:                The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_hooks_customersEmails($ciniki, $tnid, $args) {

    if( !isset($args['customer_ids']) || !is_array($args['customer_ids']) || count($args['customer_ids']) < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.397', 'msg'=>'No customers specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Get the customer details and emails
    //
    $strsql = "SELECT customers.id, "
        . "customers.parent_id, "
        . "customers.eid, "
        . "customers.type, "
        . "customers.prefix, "
        . "customers.first, "
        . "customers.middle, "
        . "customers.last, "
        . "customers.suffix, "
        . "customers.display_name, "
        . "customers.company, "
        . "customers.department, "
        . "customers.title, "
        . "customers.status, "
        . "customers.dealer_status, "
        . "customers.distributor_status, "
        . "emails.id AS email_id, "
        . "emails.email, "
        . "IFNULL(DATE_FORMAT(customers.birthdate, '" . ciniki_core_dbQuote($ciniki, '%M %d, %Y') . "'), '') AS birthdate, "
        . "customers.notes "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (emails.flags&0x10) = 0 " // Not flagged as "NO NOT EMAIL"
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customers.id IN (" . ciniki_core_dbQuoteIDs($ciniki, array_unique($args['customer_ids'])) . ") "
        . "ORDER BY customers.id, email "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'parent_id', 'eid', 'type', 
                'prefix', 'first', 'middle', 'last', 'suffix', 'display_name',
                'status', 'dealer_status', 'distributor_status',
                'company', 'department', 'title',
                'notes', 'birthdate')),
        array('container'=>'emails', 'fname'=>'email_id', 
            'fields'=>array('id'=>'email_id', 'customer_id'=>'id', 'customer_name'=>'display_name', 'email')),
        ));
    return $rc;
}
?>
