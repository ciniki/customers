<?php
//
// Description
// -----------
// This function will add a log entry to customer logs for web logging.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
// type:            The type of the tag.
//
//
// Returns
// -------
//
function ciniki_customers_web_logAdd($ciniki, $settings, $tnid, $status, $action, $customer_id, $email, $code, $msg) {

    $dt = new DateTime('now', new DateTimeZone('UTC'));

    if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif( isset($_SERVER['REMOTE_ADDR']) ) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip_address = 'unknown';
    }

    $strsql = "INSERT INTO ciniki_customer_logs (uuid, tnid, "
        . "log_date, status, ip_address, action, customer_id, email, error_code, error_msg, "
        . "date_added, last_updated) VALUES ("
        . "UUID(), "
        . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
        . "UTC_TIMESTAMP(), "
        . "'" . ciniki_core_dbQuote($ciniki, $status) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ip_address) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $action) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $email) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $code) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $msg) . "', "
        . "UTC_TIMESTAMP(), "
        . "UTC_TIMESTAMP() "
        . ") ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.227', 'msg'=>'Unable to add log', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok');
}
?>
