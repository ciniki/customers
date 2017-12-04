<?php
//
// Description
// -----------
// This function will return the list of locations and number of customers in each location.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers__locationStats($ciniki, $tnid, $args) {
    
    //
    // Get the settings for customer module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $tnid); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $settings = $rc['settings'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $date_format = ciniki_users_dateFormat($ciniki);

    $level = $args['start_level'];

    $rsp = array('stat'=>'ok');

    //
    // Load the stats based on country
    //
    if( $level == 'country' ) {
        $strsql = "SELECT IFNULL(country, 'No Address') AS _country, "
            . "IF(ISNULL(country) OR country='', 1, 0) AS sorta, "
            . "COUNT(DISTINCT ciniki_customers.id) AS num "
            . "FROM ciniki_customers "
            . "LEFT JOIN ciniki_customer_addresses USE INDEX FOR JOIN (uuid, city) ON ("
                . "ciniki_customers.id = ciniki_customer_addresses.customer_id "
                . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_customers.status = 10 "
            . "";
        if( ($ciniki['tenant']['user']['perms']&0x04) > 0 ) {
            $strsql .= "AND ciniki_customers.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
        }
        $strsql .= "GROUP BY _country "
            . "ORDER BY sorta, num DESC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'places', 'fname'=>'_country', 'name'=>'place',
                'fields'=>array('country'=>'_country', 'num_customers'=>'num')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['places']) ) {
            // No customers setup yet
            return array('stat'=>'ok');
        }
        if( count($rc['places']) == 1 ) {
            // Switch to province stats if 0 or 1 countries returned
            $level = 'province';
            $args['country'] = $rc['places'][0]['place']['country'];
        } elseif( count($rc['places']) == 2 
            && ($rc['places'][0]['place']['country'] == '' || $rc['places'][1]['place']['country'] == '') ) {
            $level = 'province';
        } else {
            $rsp['places'] = $rc['places'];
            $rsp['place_level'] = 'country';
        }
    }

    //
    // Load the stats based on province for the specified country
    //
    if( $level == 'province' ) {
        $strsql = "SELECT IFNULL(province, 'No Address') AS _province, "
            . "IFNULL(country, 'No Address') AS country, "
            . "IF(ISNULL(province) OR province='', 1, 0) AS sorta, "
            . "COUNT(DISTINCT ciniki_customers.id) AS num "
            . "FROM ciniki_customers "
            . "LEFT JOIN ciniki_customer_addresses ON ("
                . "ciniki_customers.id = ciniki_customer_addresses.customer_id ";
        if( isset($args['country']) ) {
            if( $args['country'] == 'No Address') {
                $strsql .= "AND ISNULL(ciniki_customer_addresses.country) ";
            } else {
                $strsql .= "AND ciniki_customer_addresses.country = '" . ciniki_core_dbQuote($ciniki, $args['country']) . "' ";
            }
        }
        $strsql .= "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_customers.status = 10 ";
        if( ($ciniki['tenant']['user']['perms']&0x04) > 0 ) {
            $strsql .= "AND ciniki_customers.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
        }
        if( !isset($args['country']) || $args['country'] != 'No Address' ) {
            $strsql .= "AND NOT ISNULL(ciniki_customer_addresses.province) ";
        }
        $strsql .= "GROUP BY country, _province "
            . "ORDER BY sorta, num DESC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'places', 'fname'=>'_province', 'name'=>'place',
                'fields'=>array('country', 'province'=>'_province', 'num_customers'=>'num')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['places']) ) {
            // No customers setup yet
            return array('stat'=>'ok');
        }
        if( count($rc['places']) == 1 ) {
            // Switch to city stats if 0 or 1 provinces returned
            $level = 'city';
            $args['province'] = $rc['places'][0]['place']['province'];
        } elseif( count($rc['places']) == 2 
            && ($rc['places'][0]['place']['province'] == '' || $rc['places'][1]['place']['province'] == '') ) {
            $level = 'city';
        } else {
            $rsp['places'] = $rc['places'];
            $rsp['place_level'] = 'province';
        }
    }

    //
    // Load the stats based on city
    //
    if( $level == 'city' ) {
        $strsql = "SELECT city AS _city, "
            . "country, "
            . "province, "
            . "COUNT(DISTINCT ciniki_customers.id) AS num "
            . "FROM ciniki_customers "
            . "LEFT JOIN ciniki_customer_addresses ON ("
                . "ciniki_customers.id = ciniki_customer_addresses.customer_id ";
        if( isset($args['country']) ) {
            if( $args['country'] == 'No Address') {
                $strsql .= "AND ISNULL(ciniki_customer_addresses.country) ";
            } else {
                $strsql .= "AND ciniki_customer_addresses.country = '" . ciniki_core_dbQuote($ciniki, $args['country']) . "' ";
            }
        }
        if( isset($args['province']) ) {
//          if( $args['province'] == 'No Province/State' ) {
//              $strsql .= "AND ISNULL(ciniki_customer_addresses.province) ";
//          } else {
                $strsql .= "AND ciniki_customer_addresses.province = '" . ciniki_core_dbQuote($ciniki, $args['province']) . "' ";
//          }
        }
        $strsql .= "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_customers.status = 10 ";
        if( ($ciniki['tenant']['user']['perms']&0x04) > 0 ) {
            $strsql .= "AND ciniki_customers.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
        }
        $strsql .= "AND NOT ISNULL(city) "
            . "GROUP BY country, province, _city "
            . "ORDER BY country, province, city, num DESC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'places', 'fname'=>'_city', 'name'=>'place',
                'fields'=>array('country', 'province', 'city'=>'_city', 'num_customers'=>'num')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['places']) ) {
            // No customers setup yet
            return array('stat'=>'ok');
        }
        $rsp['places'] = $rc['places'];
        $rsp['place_level'] = 'city';
    }

    return $rsp;
}
?>
