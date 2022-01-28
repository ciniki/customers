<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_memberDetails($ciniki, $settings, $tnid, $permalink) {

    $strsql = "SELECT ciniki_customers.id, "
        . "ciniki_customers.display_name AS member, "
        . "ciniki_customers.company, "
        . "ciniki_customers.permalink, "
        . "ciniki_customers.full_bio AS description, "
        . "ciniki_customers.primary_image_id, "
        . "ciniki_customers.primary_image_caption, "
        . "ciniki_customers.intro_image_id, "
        . "ciniki_customers.intro_image_caption, "
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
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        // Check the member is visible on the website
        . "AND ciniki_customers.member_status = 10 "
        . "AND (ciniki_customers.webflags&0x01) = 1 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'member', 'company', 
                'image_id'=>'primary_image_id', 'image_caption'=>'primary_image_caption', 
                'intro_image_id', 'intro_image_caption', 
                'description')),
        array('container'=>'images', 'fname'=>'image_id', 
            'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
                'description'=>'image_description', 'sold', 'last_updated'=>'image_last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['members']) || count($rc['members']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.203', 'msg'=>'We are sorry, but the member you requested does not exist.'));
    }
    $member = array_pop($rc['members']);

//  if( isset($member['company']) && $member['company'] != '' ) {
//      $member['name'] = $member['company'];
//  } else {
        $member['name'] = $member['member'];
//  }

    //
    // Check for any public addresses for the member
    //
    $strsql = "SELECT id, address1, address2, city, province, postal "
        . "FROM ciniki_customer_addresses "
        . "WHERE ciniki_customer_addresses.customer_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
        . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (ciniki_customer_addresses.flags&0x08) > 0 " // Visible on website
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'addresses', 'fname'=>'id', 
            'fields'=>array('address1', 'address2', 'city', 'province', 'postal')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['addresses']) ) {
        $member['addresses'] = $rc['addresses'];
    } else {
        $member['addresses'] = array();
    }

    //
    // Check for any public phone numbers for the member
    //
    $strsql = "SELECT id, phone_label, phone_number "
        . "FROM ciniki_customer_phones "
        . "WHERE ciniki_customer_phones.customer_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
        . "AND ciniki_customer_phones.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        $member['phones'] = $rc['phones'];
    } else {
        $member['phones'] = array();
    }
        
    //
    // Check for any public email addresses for the member
    //
    $strsql = "SELECT id, email  "
        . "FROM ciniki_customer_emails "
        . "WHERE ciniki_customer_emails.customer_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
        . "AND ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        $member['emails'] = $rc['emails'];
    } else {
        $member['emails'] = array();
    }
        
    //
    // Check for any links for the member
    //
    $strsql = "SELECT id, name, url, description "
        . "FROM ciniki_customer_links "
        . "WHERE ciniki_customer_links.customer_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
        . "AND ciniki_customer_links.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        $member['links'] = $rc['links'];
    } else {
        $member['links'] = array();
    }

    //
    // Get the exhibitions and items that are available online
    //
    if( isset($ciniki['tenant']['modules']['ciniki.ags']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'ags', 'web', 'customerExhibits');
        $rc = ciniki_ags_web_customerExhibits($ciniki, $tnid, array('customer_id'=>$member['id']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.398', 'msg'=>'Unable to load exhibits', 'err'=>$rc['err']));
        }
        $member['exhibits'] = isset($rc['exhibits']) ? $rc['exhibits'] : array();
    }
        
    return array('stat'=>'ok', 'member'=>$member);
}
?>
