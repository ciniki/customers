<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get distributors for.
// type:            The type of participants to get.  Refer to participantAdd for 
//                  more information on types.
//
// Returns
// -------
//
function ciniki_customers_distributorList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.distributorList', 0);
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the list of distributors for a tenant
    //
    if( isset($args['category']) && $args['category'] != '' ) {
        $strsql = "SELECT ciniki_customers.id, "
            . "ciniki_customers.first, "
            . "ciniki_customers.last, "
            . "ciniki_customers.display_name, "
            . "ciniki_customers.distributor_status AS distributor_status_text, "
            . "ciniki_customers.company "
            . "FROM ciniki_customer_tags, ciniki_customers "
            . "WHERE ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customer_tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND ciniki_customer_tags.tag_type = '80' "
            . "AND ciniki_customer_tags.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.distributor_status = 10 "
            . "ORDER BY last, first, company";
    } elseif( isset($args['category']) && $args['category'] == '' ) {
        $strsql = "SELECT ciniki_customers.id, "
            . "ciniki_customers.first, "
            . "ciniki_customers.last, "
            . "ciniki_customers.display_name, "
            . "ciniki_customers.distributor_status AS distributor_status_text, "
            . "ciniki_customers.company "
            . "FROM ciniki_customers "
            . "LEFT JOIN ciniki_customer_tags ON ("
                . "ciniki_customers.id = ciniki_customer_tags.customer_id "
                . "AND ciniki_customer_tags.tag_type = '80' "
                . "AND ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.distributor_status = 10 "
            . "AND ISNULL(ciniki_customer_tags.tag_name) "
            . "ORDER BY last, first, company";
    } else {
        $strsql = "SELECT ciniki_customers.id, "
            . "ciniki_customers.first, "
            . "ciniki_customers.last, "
            . "ciniki_customers.display_name, "
            . "ciniki_customers.distributor_status AS distributor_status_text, "
            . "ciniki_customers.company "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.distributor_status = 10 "
            . "ORDER BY last, first, company";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artclub', array(
        array('container'=>'distributors', 'fname'=>'id', 'name'=>'distributor',
            'fields'=>array('id', 'first', 'last', 'display_name', 'company', 'distributor_status_text'),
            'maps'=>array(
                'distributor_status_text'=>array('0'=>'Non-Distributor', '10'=>'Active', '60'=>'Suspended'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['distributors']) ) {
        return array('stat'=>'ok', 'distributors'=>$rc['distributors']);
    } 

    return array('stat'=>'ok', 'distributors'=>array());
}
?>
