<?php
//
// Description
// -----------
// This function returns the list of objects and object_ids that should be indexed on the website.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_customers_hooks_webIndexList($ciniki, $tnid, $args) {

    $objects = array();

    //
    // Get the list of members that should be in the index
    //
    $strsql = "SELECT CONCAT('ciniki.customers.members.', id) AS oid, 'ciniki.customers.members' AS object, id AS object_id "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND member_status = 10 "
        . "AND (webflags&0x01) = 0x01 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'objects', 'fname'=>'oid', 'fields'=>array('object', 'object_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['objects']) ) {
        $objects = $rc['objects'];
    }

    //
    // Get the list of dealers that should be in the index
    //
    $strsql = "SELECT CONCAT('ciniki.customers.dealers.', id) AS oid, 'ciniki.customers.dealers' AS object, id AS object_id "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (webflags&0x02) = 0x02 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'objects', 'fname'=>'oid', 'fields'=>array('object', 'object_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['objects']) ) {
        $objects = array_replace($objects, $rc['objects']);
    }

    //
    // Get the list of distributors that should be in the index
    //
    $strsql = "SELECT CONCAT('ciniki.customers.distributors.', id) AS oid, 'ciniki.customers.distributors' AS object, id AS object_id "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (webflags&0x04) = 0x04 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'objects', 'fname'=>'oid', 'fields'=>array('object', 'object_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['objects']) ) {
        $objects = array_replace($objects, $rc['objects']);
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
