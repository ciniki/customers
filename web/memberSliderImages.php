<?php
//
// Description
// -----------
// This funciton will return a list of the random added items in the art catalog. 
// These are used on the homepage of the business website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get images for.
// limit:           The maximum number of images to return.
//
// Returns
// -------
// <images>
//      [title="Slow River" permalink="slow-river" image_id="431" 
//          caption="Based on a photograph taken near Slow River, Ontario, Pastel, size: 8x10" sold="yes"
//          last_updated="1342653769"],
//      [title="Open Field" permalink="open-field" image_id="217" 
//          caption="An open field in Ontario, Oil, size: 8x10" sold="yes"
//          last_updated="1342653769"],
//      ...
// </images>
//
function ciniki_customers_web_memberSliderImages($ciniki, $settings, $business_id, $list, $limit) {


    if( $list == 'random' ) {
        $strsql = "SELECT ciniki_customer_images.id, "
            . "ciniki_customer_images.image_id, "
            . "ciniki_customer_images.name AS title, "
            . "ciniki_customers.permalink AS member_permalink, "
            . "ciniki_customer_images.permalink AS image_permalink, "
            . "IF(ciniki_images.last_updated > ciniki_customer_images.last_updated, UNIX_TIMESTAMP(ciniki_images.last_updated), UNIX_TIMESTAMP(ciniki_customer_images.last_updated)) AS last_updated "
            . "FROM ciniki_customer_images "
            . "INNER JOIN ciniki_customers ON ("
                . "ciniki_customer_images.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.member_status = 10 "
                . "AND (ciniki_customers.webflags&0x01) = 0x01 "
                . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "INNER JOIN ciniki_images ON ( "
                . "ciniki_customer_images.image_id = ciniki_images.id "
                . "AND ciniki_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE ciniki_customer_images.business_id = '". ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_customer_images.webflags&0x01) = 0x01 "
            . "AND ciniki_customer_images.image_id > 0 "
            . "";
        if( $limit != '' && $limit > 0 && is_int($limit) ) {
            $strsql .= "ORDER BY RAND() "
                . "LIMIT $limit ";
        } else {
            $strsql .= "ORDER BY RAND() "
                . "LIMIT 15 ";
        }
    } else {
        $strsql = "SELECT ciniki_customer_images.id, "
            . "ciniki_customer_images.image_id, "
            . "ciniki_customer_images.name AS title, "
            . "ciniki_customers.permalink AS member_permalink, "
            . "ciniki_customer_images.permalink AS image_permalink, "
            . "IF(ciniki_images.last_updated > ciniki_customer_images.last_updated, UNIX_TIMESTAMP(ciniki_images.last_updated), UNIX_TIMESTAMP(ciniki_customer_images.last_updated)) AS last_updated "
            . "FROM ciniki_customer_images "
            . "INNER JOIN ciniki_customers ON ("
                . "ciniki_customer_images.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.member_status = 10 "
                . "AND (ciniki_customers.webflags&0x01) = 0x01 "
                . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "INNER JOIN ciniki_images ON ( "
                . "ciniki_customer_images.image_id = ciniki_images.id "
                . "AND ciniki_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE ciniki_customer_images.business_id = '". ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_customer_images.webflags&0x01) = 0x01 "
            . "AND ciniki_customer_images.image_id > 0 "
            . "ORDER BY ciniki_customer_images.date_added DESC "
            . "";
        if( $limit != '' && $limit > 0 && is_int($limit) ) {
            $strsql .= "LIMIT $limit ";
        } else {
            $strsql .= "LIMIT 15";
        }
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'images', 'fname'=>'id',
            'fields'=>array('id', 'image_id', 'title', 'member_permalink', 'image_permalink', 'last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['images']) ) {
        return array('stat'=>'ok', 'images'=>$rc['images']);
    }

    return array('stat'=>'ok', 'images'=>array());
}
?>
