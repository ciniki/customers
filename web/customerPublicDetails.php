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
function ciniki_customers_web_customerPublicDetails($ciniki, $settings, $tnid, $args) {
    
    if( !isset($args['customer_id']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.217', 'msg'=>'We are sorry, but the customer you requested does not exist.'));
    }

    $strsql = "SELECT ciniki_customers.id, "
        . "ciniki_customers.display_name, "
        . "ciniki_customers.permalink, "
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
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'display_name', 'image_id'=>'primary_image_id', 'image_caption'=>'primary_image_caption', 'description')),
        array('container'=>'images', 'fname'=>'image_id', 
            'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
                'description'=>'image_description', 'sold', 'last_updated'=>'image_last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) || count($rc['customers']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.customers.216', 'msg'=>'We are sorry, but the customer you requested does not exist.'));
    }
    $customer = array_pop($rc['customers']);

    //
    // Check for any public addresses for the customer
    //
    $strsql = "SELECT id, address1, address2, city, province, postal "
        . "FROM ciniki_customer_addresses "
        . "WHERE ciniki_customer_addresses.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
        $customer['addresses'] = $rc['addresses'];
    } else {
        $customer['addresses'] = array();
    }

    //
    // Check for any public phone numbers for the customer
    //
    $strsql = "SELECT id, phone_label, phone_number "
        . "FROM ciniki_customer_phones "
        . "WHERE ciniki_customer_phones.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
        $customer['phones'] = $rc['phones'];
    } else {
        $customer['phones'] = array();
    }
        
    //
    // Check for any public email addresses for the customer
    //
    $strsql = "SELECT id, email  "
        . "FROM ciniki_customer_emails "
        . "WHERE ciniki_customer_emails.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
        $customer['emails'] = $rc['emails'];
    } else {
        $customer['emails'] = array();
    }
        
    //
    // Check for any links for the customer
    //
    $strsql = "SELECT id, name, url, description "
        . "FROM ciniki_customer_links "
        . "WHERE ciniki_customer_links.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
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
        $customer['links'] = $rc['links'];
    } else {
        $customer['links'] = array();
    }

    //
    // Process the description
    //
    $content = '';
    if( isset($customer['description']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
        $rc = ciniki_web_processContent($ciniki, $settings, $customer['description']);    
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $content .= $rc['content'];
    }

    //
    // Add contact_info
    //
    $cinfo = '';
    if( isset($customer['addresses']) ) {
        foreach($customer['addresses'] as $address) {
            $addr = '';
            if( $address['address1'] != '' ) {
                $addr .= ($addr!=''?'<br/>':'') . $address['address1'];
            }
            if( $address['address2'] != '' ) {
                $addr .= ($addr!=''?'<br/>':'') . $address['address2'];
            }
            if( $address['city'] != '' ) {
                $addr .= ($addr!=''?'<br/>':'') . $address['city'];
            }
            if( $address['province'] != '' ) {
                $addr .= ($addr!=''?', ':'') . $address['province'];
            }
            if( $address['postal'] != '' ) {
                $addr .= ($addr!=''?'  ':'') . $address['postal'];
            }
            if( $addr != '' ) {
                $cinfo .= ($cinfo!=''?'<br/>':'') . "$addr";
            }
        }
    }
    if( isset($customer['phones']) ) {
        foreach($customer['phones'] as $phone) {
            if( $phone['phone_label'] != '' && $phone['phone_number'] != '' ) {
                $cinfo .= ($cinfo!=''?'<br/>':'') . $phone['phone_label'] . ': ' . $phone['phone_number'];
            } elseif( $phone['phone_number'] != '' ) {
                $cinfo .= ($cinfo!=''?'<br/>':'') . $phone['phone_number'];
            }
        }
    }
    if( isset($customer['emails']) ) {
        foreach($customer['emails'] as $email) {
            if( $email['email'] != '' ) {
                $cinfo .= ($cinfo!=''?'<br/>':'') . '<a href="mailto:' . $email['email'] . '">' . $email['email'] . '</a>';
            }
        }
    }

    if( $cinfo != '' ) {
        $content .= "<h2>Contact Info</h2>\n";
        $content .= "<p>$cinfo</p>";
    }

    if( isset($customer['links']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');
        $links = '';
        foreach($customer['links'] as $link) {
            $rc = ciniki_web_processURL($ciniki, $link['url']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $url = $rc['url'];
            $display_url = $rc['display'];
            if( $link['name'] != '' ) {
                $display_url = $link['name'];
            }
            $links .= ($links!=''?'<br/>':'') 
                . "<a class='members-url' target='_blank' href='" . $url . "' "
                . "title='" . $display_url . "'>" . $display_url . "</a>";
        }
        if( $links != '' ) {
            $content .= "<h2>Links</h2>\n";
            $content .= "<p>" . $links . "</p>";
        }
    }
    $customer['processed_description'] = $content;
        
    return array('stat'=>'ok', 'customer'=>$customer);
}
?>
