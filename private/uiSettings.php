<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_customers_uiSettings($ciniki, $modules, $business_id) {

    $settings = array();

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'business_id', 
        $business_id, 'ciniki.customers', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }


    //
    // Get the list of price points
    //
    if( isset($modules['ciniki.customers']['flags']) && ($modules['ciniki.customers']['flags']&0x1000) > 0 ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_customer_pricepoints "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "ORDER BY sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'pricepoints', 'fname'=>'id', 'name'=>'pricepoint',
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] == 'ok' && isset($rc['pricepoints']) ) {
            $settings['pricepoints'] = $rc['pricepoints'];
        }
    }

    //
    // Get the membership seasons
    //
    if( isset($modules['ciniki.customers']['flags']) && ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
        $strsql = "SELECT id, name, if((flags&0x02)=2,'yes','no') AS open "
            . "FROM ciniki_customer_seasons "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (flags&0x02) = 2 "
            . "ORDER BY start_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'seasons', 'fname'=>'id', 'name'=>'season',
                'fields'=>array('id', 'name', 'open')),
            ));
        if( $rc['stat'] == 'ok' && isset($rc['seasons']) ) {
            $settings['seasons'] = $rc['seasons'];
        }
    }

    return array('stat'=>'ok', 'settings'=>$settings);  
}
?>
