<?php
//
// Description
// -----------
// This function is called whenever a setting is changes in ciniki.web
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_settingChange($ciniki, $tnid, $field, $field_value) {

    if( $field == 'page-members-list-format' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
        $strsql = "SELECT id "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND member_status > 0 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $members = $rc['rows'];

        foreach($members as $member) {
            $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $tnid, $member['id'], 0x04, $field_value);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    if( $field == 'page-dealers-list-format' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
        $strsql = "SELECT id "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND dealer_status > 0 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $dealers = $rc['rows'];

        foreach($dealers as $dealer) {
            $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $tnid, $dealer['id'], 0x04, $field_value);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    if( $field == 'page-distributors-list-format' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
        $strsql = "SELECT id "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND distributor_status > 0 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $distributors = $rc['rows'];

        foreach($distributors as $distributor) {
            $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $tnid, $distributor['id'], 0x04, $field_value);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
