<?php
//
// Description
// -----------
// This function will update the customers short description for the website listing.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_customerUpdateSeasons(&$ciniki, $tnid, $customer_id) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Load the list of seasons
    //
    $strsql = "SELECT ciniki_customer_seasons.id, "
        . "ciniki_customer_seasons.name, "
        . "ciniki_customer_seasons.flags, "
        . "IFNULL(ciniki_customer_season_members.id, 0) AS season_member_id, "
        . "IFNULL(ciniki_customer_season_members.status, '') AS status, "
        . "IFNULL(ciniki_customer_season_members.date_paid, '') AS date_paid "
        . "FROM ciniki_customer_seasons "
        . "LEFT JOIN ciniki_customer_season_members ON ("
            . "ciniki_customer_seasons.id = ciniki_customer_season_members.season_id "
            . "AND ciniki_customer_season_members.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
            . "AND ciniki_customer_season_members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_customer_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (ciniki_customer_seasons.flags&0x02) > 0 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'seasons', 'fname'=>'id',
            'fields'=>array('id', 'name', 'flags', 'season_member_id', 'status', 'date_paid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['seasons']) ) {  
        return array('stat'=>'ok');
    }
    $seasons = $rc['seasons'];

    //
    // Check the args for season status
    //
    foreach($seasons as $season_id => $season) {
        $args = array();
        if( isset($ciniki['request']['args']['season-' . $season_id . '-status']) ) {
            if( $ciniki['request']['args']['season-' . $season_id . '-status'] != $season['status'] ) {
                $args['status'] = $ciniki['request']['args']['season-' . $season_id . '-status'];
            }
        }
        if( isset($ciniki['request']['args']['season-' . $season_id . '-date_paid']) ) {
            $ts = strtotime($ciniki['request']['args']['season-' . $season_id . '-date_paid']);
            if( $ts === FALSE || $ts < 1 ) {
                $date_paid = '';
            } else {
                $date_paid = strftime("%Y-%m-%d", $ts);
            }
            if( $date_paid != $season['date_paid'] ) {
                $args['date_paid'] = $date_paid;
            }
        }
        if( count($args) > 0 ) {
            //
            // Update the season member
            //
            $args['season_id'] = $season_id;
            $args['customer_id'] = $customer_id;
            if( $season['season_member_id'] > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.season_member', $season['season_member_id'], $args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
            
            if( $season['season_member_id'] == 0 ) {
                if( !isset($args['date_paid']) ) {
                    $args['date_paid'] = '';
                }
                if( !isset($args['status']) ) {
                    $args['status'] = '60';
                }
                if( !isset($args['notes']) ) {
                    $args['notes'] = '';
                }
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.season_member', $args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }
    
    return array('stat'=>'ok');
}
?>
