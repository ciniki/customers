<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the members belong to.
//
// Returns
// -------
// A word document
//
function ciniki_customers_memberPDFDirectory(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'layout'=>array('required'=>'no', 'blank'=>'no', 'default'=>'fullpage', 'name'=>'Layout',
            'validlist'=>array('fullpage', 'halfpage')), 
        'coverpage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Cover Page'),
        'toc'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Table of Contents'),
        'title'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'Member Directory', 'name'=>'Title'),
        'categories'=>array('required'=>'no', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Categories'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.memberPDFDirectory', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];


    if( ($modules['ciniki.customers']['flags']&0x04) > 0 ) {
        if( isset($args['categories']) && count($args['categories']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
            $strsql = "SELECT permalink "
                . "FROM ciniki_customer_tags "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['categories']) . ") "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
            $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.customers', 'categories', 'permalink');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['categories']) ) {
                $permalinks = $rc['categories'];
            }
        }

        $strsql = "SELECT ciniki_customers.id, "
            . "ciniki_customer_tags.tag_name AS category, "
            . "ciniki_customers.display_name AS title, "
            . "IF(type=2,CONCAT_WS(', ', company, last, first),CONCAT_WS(', ', last, first)) AS sname, "
            . "ciniki_customers.permalink, "
            . "ciniki_customers.short_bio "
            . "FROM ciniki_customer_tags, ciniki_customers "
            . "WHERE ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customer_tags.tag_type = '40' "
            . "AND ciniki_customer_tags.customer_id = ciniki_customers.id "
            // Check the member is visible on the website
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "AND (ciniki_customers.webflags&0x01) = 1 "
            . "";
        if( isset($permalinks) && count($permalinks) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
            $strsql .= "AND ciniki_customer_tags.permalink IN (" . ciniki_core_dbQuoteList($ciniki, $permalinks) . ") ";
        }
        $strsql .= "ORDER BY ciniki_customer_tags.tag_name, sname ";
    } else {
        $strsql = "SELECT ciniki_customers.id, "
            . "'Members' AS category, "
            . "IF(type=2,CONCAT_WS(', ', company, last, first),CONCAT_WS(', ', last, first)) AS sname, "
            . "ciniki_customers.display_name AS title, "
            . "ciniki_customers.permalink, "
            . "ciniki_customers.short_bio "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customers.member_status = 10 "
            . "AND (ciniki_customers.webflags&0x01) = 1 "
            . "ORDER BY sname ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('id', 'name'=>'category')),
        array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'title', 'short_bio')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = $rc['categories'];

    //
    // Get the public addresses
    //
    $strsql = "SELECT id, customer_id, address1, address2, city, province, postal "
        . "FROM ciniki_customer_addresses "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (flags&0x08) > 0 "
        . "ORDER BY customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('customer_id')),
        array('container'=>'addresses', 'fname'=>'id', 'fields'=>array('id', 'address1', 'address2', 'city', 'province', 'postal')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $addresses = $rc['customers'];
    }

    //
    // Get the public email addresses
    //
    $strsql = "SELECT id, customer_id, email "
        . "FROM ciniki_customer_emails "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (flags&0x08) > 0 "
        . "ORDER BY customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('customer_id')),
        array('container'=>'emails', 'fname'=>'id', 'fields'=>array('id', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $emails = $rc['customers'];
    } else {
        $emails = array();
    }

    //
    // Get the phone numbers for the customer
    //
    $strsql = "SELECT id, customer_id, phone_label, phone_number, flags "
        . "FROM ciniki_customer_phones "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (flags&0x08) > 0 "
        . "ORDER BY customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('customer_id')),
        array('container'=>'phones', 'fname'=>'id', 'fields'=>array('id', 'phone_label', 'phone_number', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $phones = $rc['customers'];
    } else {
        $phones = array();
    }

    //
    // Get the websites for the customer
    //
    $strsql = "SELECT id, customer_id, name, url, webflags "
        . "FROM ciniki_customer_links "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (webflags&0x01) > 0 "
        . "ORDER BY customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('customer_id')),
        array('container'=>'links', 'fname'=>'id', 'fields'=>array('id', 'name', 'url', 'webflags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $links = $rc['customers'];
    } else {
        $links = array();
    }

    //
    // Merge with members
    //
    foreach($categories as $cid => $category) {
        foreach($category['members'] as $mid => $member) {
            if( isset($addresses[$mid]) ) {
                $categories[$cid]['members'][$mid]['addresses'] = $addresses[$mid]['addresses'];
            }
            if( isset($phones[$mid]) ) {
                $categories[$cid]['members'][$mid]['phones'] = $phones[$mid]['phones'];
            }
            if( isset($emails[$mid]) ) {
                $categories[$cid]['members'][$mid]['emails'] = $emails[$mid]['emails'];
            }
            if( isset($links[$mid]) ) {
                $categories[$cid]['members'][$mid]['links'] = $links[$mid]['links'];
            }
        }
    }

    //
    // Check for coverpage settings
    //
    if( isset($args['coverpage']) && $args['coverpage'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $args['tnid'], 'ciniki.customers', 'settings', 'members-coverpage');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['settings']['members-coverpage-image']) ) {
            $args['coverpage-image'] = $rc['settings']['coverpage-image'];
        }
    }

    //
    // Load the PDF template and generate
    //
    $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'templates', $args['layout']);
    $fn = $rc['function_call'];
    $rc = $fn($ciniki, $args['tnid'], $categories, $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($args['title']) && $args['title'] != '' ) {
        $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $args['title']));
    } else {
        foreach($categories as $cat) {
            foreach($cat['members'] as $member) {
                $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $member['title']));
                break;
            }
            break;
        }
    }
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($filename . '.pdf', 'D');
    }

    return array('stat'=>'exit');
}
?>
