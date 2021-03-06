<?php
//
// Description
// -----------
// This method will return the customers module settings for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the settings for.
// 
// Returns
// -------
//
function ciniki_customers_getSettings($ciniki) {
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
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.getSettings', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];
    
    //
    // Grab the settings for the tenant from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $args['tnid'], 'ciniki.customers', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp = $rc; 

    //
    // Get the membership seasons
    //
    if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
        $strsql = "SELECT ciniki_customer_seasons.id, "
            . "ciniki_customer_seasons.name, "
            . "ciniki_customer_seasons.flags "
            . "FROM  ciniki_customer_seasons "
            . "WHERE ciniki_customer_seasons.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_customer_seasons.start_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'seasons', 'fname'=>'id', 'name'=>'season',
                'fields'=>array('id', 'name', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['seasons']) ) {   
            $rsp['seasons'] = $rc['seasons'];
        }
    }

    //
    // Return the response, including colour arrays and todays date
    //
    return $rsp;
}
?>
