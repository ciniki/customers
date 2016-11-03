<?php
//
// Description
// -----------
// This function will lookup the details for a dealer
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_dealerDetails($ciniki, $settings, $business_id, $permalink) {

    $strsql = "SELECT ciniki_customers.id, "
        . "ciniki_customers.display_name AS dealer, "
        . "ciniki_customers.company, "
        . "ciniki_customers.permalink, "
        . "ciniki_customers.short_description AS synopsis, "
        . "ciniki_customers.full_bio AS description, "
        . "ciniki_customers.primary_image_id, "
        . "ciniki_customers.primary_image_caption, "
        . "ciniki_customer_images.image_id, "
        . "ciniki_customer_images.name AS image_name, "
        . "ciniki_customer_images.permalink AS image_permalink, "
        . "ciniki_customer_images.description AS image_description, "
        . "IF((ciniki_customer_images.webflags&0x02)=0x02, 'yes', 'no') AS sold, "
        . "UNIX_TIMESTAMP(ciniki_customer_images.last_updated) AS image_last_updated "
        . "FROM ciniki_customers "
        . "LEFT JOIN ciniki_customer_images ON ("
            . "ciniki_customers.id = ciniki_customer_images.customer_id "
            . "AND ciniki_customer_images.image_id > 0 "
            . "AND (ciniki_customer_images.webflags&0x01) = 1 "
            . ") "
        . "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_customers.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        // Check the dealer is visible on the website
        . "AND (ciniki_customers.webflags&0x02) = 0x02 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'dealers', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'dealer', 'company', 
                'image_id'=>'primary_image_id', 'image_caption'=>'primary_image_caption', 'synopsis', 'description')),
        array('container'=>'images', 'fname'=>'image_id', 
            'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
                'description'=>'image_description', 'sold', 'last_updated'=>'image_last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['dealers']) || count($rc['dealers']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.202', 'msg'=>'We are sorry, but the dealer you requested does not exist.'));
    }
    $dealer = array_pop($rc['dealers']);

    $dealer['name'] = $dealer['dealer'];

    //
    // Check for any public addresses for the dealer
    //
    $strsql = "SELECT id, address1, address2, city, province, postal, latitude, longitude "
        . "FROM ciniki_customer_addresses "
        . "WHERE ciniki_customer_addresses.customer_id = '" . ciniki_core_dbQuote($ciniki, $dealer['id']) . "' "
        . "AND ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (ciniki_customer_addresses.flags&0x08) > 0 " // Visible on website
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'addresses', 'fname'=>'id', 
            'fields'=>array('address1', 'address2', 'city', 'province', 'postal', 'latitude', 'longitude')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['addresses']) && count($rc['addresses']) > 0 ) {
        $dealer['addresses'] = $rc['addresses'];
        $address = array_pop($rc['addresses']);
        $dealer['latitude'] = $address['latitude'];
        $dealer['longitude'] = $address['longitude'];
    } else {
        $dealer['addresses'] = array();
    }

    //
    // Check for any public phone numbers for the dealer
    //
    $strsql = "SELECT id, phone_label, phone_number "
        . "FROM ciniki_customer_phones "
        . "WHERE ciniki_customer_phones.customer_id = '" . ciniki_core_dbQuote($ciniki, $dealer['id']) . "' "
        . "AND ciniki_customer_phones.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (ciniki_customer_phones.flags&0x08) > 0 "    // Visible on website
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'phones', 'fname'=>'id', 
            'fields'=>array('phone_label', 'phone_number')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['phones']) ) {
        $dealer['phones'] = $rc['phones'];
    } else {
        $dealer['phones'] = array();
    }
        
    //
    // Check for any public email addresses for the dealer
    //
    $strsql = "SELECT id, email  "
        . "FROM ciniki_customer_emails "
        . "WHERE ciniki_customer_emails.customer_id = '" . ciniki_core_dbQuote($ciniki, $dealer['id']) . "' "
        . "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (ciniki_customer_emails.flags&0x08) > 0 "    // Visible on website
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'emails', 'fname'=>'id', 
            'fields'=>array('email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['emails']) ) {
        $dealer['emails'] = $rc['emails'];
    } else {
        $dealer['emails'] = array();
    }
        
    //
    // Check for any links for the dealer
    //
    $strsql = "SELECT id, name, url, description "
        . "FROM ciniki_customer_links "
        . "WHERE ciniki_customer_links.customer_id = '" . ciniki_core_dbQuote($ciniki, $dealer['id']) . "' "
        . "AND ciniki_customer_links.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (ciniki_customer_links.webflags&0x01) = 1 "  // Visible on website
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'links', 'fname'=>'id', 
            'fields'=>array('name', 'url', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['links']) ) {
        $dealer['links'] = $rc['links'];
    } else {
        $dealer['links'] = array();
    }
        
    return array('stat'=>'ok', 'dealer'=>$dealer);
}
?>
