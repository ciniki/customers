<?php
//
// Description
// -----------
// This function will go through the history of the ciniki.customers module and add missing history elements.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_revertFromSeasons($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.dbIntegrityCheck', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');


    $strsql = "SELECT customers.id, "
        . "customers.member_lastpaid, "
        . "MAX(seasons.date_paid) AS lastpaid "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_season_members AS seasons ON ("
            . "customers.id = seasons.customer_id "
            . "AND seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customers.member_lastpaid < seasons.date_paid "
        . "GROUP BY customers.id ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'lastpaid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.395', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    if( isset($rc['members']) ) {
        foreach($rc['members'] as $member) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', $member['id'], array(
                'member_lastpaid' => $member['lastpaid'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.396', 'msg'=>'Unable to update the customer'));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
