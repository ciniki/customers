<?php
//
// Description
// -----------
// Return the list of customer who have been recently updated
//
// Arguments
// ---------
// user_id:         The user making the request
// search_str:      The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_customers_placeDetails($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Country'), 
        'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Province'), 
        'city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'City'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.placeDetails', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $rsp = array('stat'=>'ok');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // Get the places and customer counts
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'locationStats');
    if( isset($args['city']) || (isset($args['country']) && $args['country'] == 'No Address') ) {
        $strsql = "SELECT ciniki_customers.id, "
            . "ciniki_customers.eid, "
            . "ciniki_customers.display_name, "
            . "ciniki_customers.status, "
            . "ciniki_customers.type, "
            . "ciniki_customers.company "
            . ", ciniki_customer_addresses.city AS _city "
            . "FROM ciniki_customers "
            . "LEFT JOIN ciniki_customer_addresses ON ("
                . "ciniki_customers.id = ciniki_customer_addresses.customer_id ";
        if( isset($args['country']) && $args['country'] != 'No Address' ) {
            $strsql .= "AND ciniki_customer_addresses.country = '" . ciniki_core_dbQuote($ciniki, $args['country']) . "' ";
        }
        if( isset($args['province']) ) {
            $strsql .= "AND ciniki_customer_addresses.province = '" . ciniki_core_dbQuote($ciniki, $args['province']) . "' ";
        }
        if( isset($args['city']) ) {
            $strsql .= "AND ciniki_customer_addresses.city = '" . ciniki_core_dbQuote($ciniki, $args['city']) . "' ";
        }
            $strsql .= "AND ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_customers.status < 50 ";
        if( ($ciniki['business']['user']['perms']&0x04) > 0 ) {
            $strsql .= "AND ciniki_customers.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
        }
        if( !isset($args['country']) || $args['country'] != 'No Address' ) {
            $strsql .= "AND NOT ISNULL(city) ";
        } else {
            $strsql .= "AND ISNULL(city) ";
        }
        $strsql .= "ORDER BY ciniki_customers.sort_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
                'fields'=>array('id', 'display_name', 'status', 'type', 'company', 'eid', '_city')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) ) { 
            $rsp['customers'] = $rc['customers'];
        }
    }

    elseif( isset($args['province']) ) {
        $rc = ciniki_customers__locationStats($ciniki, $args['business_id'], array(
            'start_level'=>'city',
            'country'=>(isset($args['country'])?$args['country']:NULL),
            'province'=>$args['province'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['places']) ) {
            $rsp['places'] = $rc['places'];
            $rsp['place_level'] = $rc['place_level'];
        }
    }
    
    elseif( isset($args['country']) ) {
        $rc = ciniki_customers__locationStats($ciniki, $args['business_id'], array(
            'start_level'=>'province',
            'country'=>$args['country'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['places']) ) {
            $rsp['places'] = $rc['places'];
            $rsp['place_level'] = $rc['place_level'];
        }
    }
    
    else {
        $rc = ciniki_customers__locationStats($ciniki, $args['business_id'], array('start_level'=>'country'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['places']) ) {
            $rsp['places'] = $rc['places'];
            $rsp['place_level'] = $rc['place_level'];
        }
    }
    
    return $rsp;
}
?>
