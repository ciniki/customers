<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_wng_memberListProcess($ciniki, $tnid, &$request, $section) {

    $s = isset($section['settings']) ? $section['settings'] : array();

    $blocks = array();

    //
    // Make sure the request has the customer logged in and a member
    //
    if( !isset($request['session']['customer']['id']) 
        || !isset($request['session']['customer']['status']) 
        || !isset($request['session']['customer']['member_status']) 
        || $request['session']['customer']['status'] != 10
        || $request['session']['customer']['member_status'] != 10
        ) {
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'error',
            'content' => 'You must be a member and logged in to view content',
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Get the settings for customer module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $tnid); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // Get the list of members and emails
    //
    $strsql = "SELECT customers.id, "
        . "customers.permalink, "
        . "customers.last, "
        . "customers.first, "
        . "customers.display_name, "
        . "IFNULL(DATE_FORMAT(customers.birthdate, '%b %e, %Y'), '') AS birthdate, "
        . "IFNULL(DATE_FORMAT(customers.start_date, '%b %e, %Y'), '') AS start_date, "
        . "customers.other1, "
        . "customers.other2, "
        . "customers.other3, "
        . "customers.other4, "
        . "customers.other5, "
        . "customers.other6, "
        . "customers.other7, "
        . "customers.other8, "
        . "customers.other9, "
        . "customers.other10, "
        . "customers.other11, "
        . "customers.other12, "
        . "emails.id AS email_id, "
        . "emails.email "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customers.status = 10 "
        . "AND customers.member_status = 10 "
        . "ORDER BY customers.last, customers.first  "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'id-permalink'=>'permalink', 'first', 'last', 'display_name', 
                'birthdate', 'start_date',
                'other1', 'other2', 'other3', 'other4', 'other5', 'other6', 'other7', 'other8',
                'other9', 'other10', 'other11', 'other12',
                ),
            ),
        array('container'=>'emails', 'fname'=>'email_id', 
            'fields'=>array('id'=>'email_id', 'email'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.559', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();

    //
    // Load phones
    //
    $strsql = "SELECT customers.id, "
        . "phones.id AS phone_id, "
        . "phones.flags, "
        . "phones.phone_label, "
        . "phones.phone_number "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_phones AS phones ON ("
            . "customers.id = phones.customer_id "
            . "AND phones.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customers.status = 10 "
        . "AND customers.member_status = 10 "
        . "ORDER BY customers.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id'),
            ),
        array('container'=>'phones', 'fname'=>'phone_id', 
            'fields'=>array('id'=>'phone_id', 'flags', 'label'=>'phone_label', 'number'=>'phone_number'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.559', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $phones = isset($rc['members']) ? $rc['members'] : array();

    //
    // Load addresses
    //
    $strsql = "SELECT customers.id, "
        . "addresses.id AS address_id, "
        . "addresses.flags, "
        . "addresses.address1, "
        . "addresses.address2, "
        . "addresses.city, "
        . "addresses.province, "
        . "addresses.postal, "
        . "addresses.country "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_addresses AS addresses ON ("
            . "customers.id = addresses.customer_id "
            . "AND addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customers.status = 10 "
        . "AND customers.member_status = 10 "
        . "ORDER BY customers.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id'),
            ),
        array('container'=>'addresses', 'fname'=>'address_id', 
            'fields'=>array('id'=>'address_id', 'flags', 'address1', 'address2', 'city', 'province', 'postal', 'country'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.559', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $addresses = isset($rc['members']) ? $rc['members'] : array();

    //
    // Load links
    //
    $strsql = "SELECT customers.id, "
        . "links.id AS link_id, "
        . "links.name, "
        . "links.url "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_links AS links ON ("
            . "customers.id = links.customer_id "
            . "AND links.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customers.status = 10 "
        . "AND customers.member_status = 10 "
        . "ORDER BY customers.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id'),
            ),
        array('container'=>'links', 'fname'=>'link_id', 
            'fields'=>array('id'=>'link_id', 'name', 'url'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.559', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $links = isset($rc['members']) ? $rc['members'] : array();

    //
    // Process the members list
    //
    $members_list = '';
    foreach($members as $mid => $member) {
        $details = '';
        $members_list .= ($members_list != '' ? "\n" : '') 
            . "<a href='{$request['ssl_domain_base_url']}{$request['page']['path']}/{$member['permalink']}'>{$member['last']}, {$member['first']}</a>";

        if( $member['birthdate'] != '' ) {
            $details .= ($details != '' ? "\n" : '') . "<b>Birthday</b>: {$member['birthdate']}";
        }
        if( $member['start_date'] != '' ) {
            $details .= ($details != '' ? "\n" : '') . "<b>Start Date</b>: {$member['start_date']}";
        }
        if( isset($phones[$member['id']]['phones']) ) {
            foreach($phones[$member['id']]['phones'] as $phone) {
                $details .= ($details != '' ? "\n" : '') . "<b>{$phone['label']}</b>: {$phone['number']}";
            }
        }
        if( isset($member['emails']) ) {
            foreach($member['emails'] as $email) {
                $details .= ($details != '' ? "\n" : '') . "<b>Email</b>: {$email['email']}";
            }
        }
        if( isset($addresses[$member['id']]['addresses']) ) {
            foreach($addresses[$member['id']]['addresses'] as $address) {
                $label = '';
                $comma = '';
                if( ($address['flags']&0x01) == 0x01 ) { $label .= $comma . 'Shipping'; $comma = ', ';}
                if( ($address['flags']&0x02) == 0x02 ) { $label .= $comma . 'Billing'; $comma = ', ';}
                if( ($address['flags']&0x04) == 0x04 ) { $label .= $comma . 'Mailing'; $comma = ', ';}
                if( ($address['flags']&0x08) == 0x08 ) { $label .= $comma . 'Public'; $comma = ', ';}
                if( $label == '' ) { 
                    $label = 'Address'; 
                }
                $addr = '';
                if( $address['address1'] != '' ) {
                    $addr .= $address['address1'];
                }
                if( $address['address2'] != '' ) {
                    $addr .= ($addr != '' ? "\n" : '') . $address['address2'];
                }
                $city = $address['city'];
                if( $address['province'] != '' ) {
                    $city .= ($city != '' ? ', ' : '') . $address['province'];
                }
                if( $address['postal'] != '' ) {
                    $city .= ($city != '' ? '  ' : '') . $address['postal'];
                }
                if( $city != '' ) {
                    $addr .= ($addr != '' ? "\n" : '') . $city;
                }

                $details .= ($details != '' ? "\n" : '') . "<b>{$label}</b>: {$addr}";
            }
        }
        if( isset($links[$member['id']]['links']) ) {
            foreach($links[$member['id']]['links'] as $link) {
                $details .= ($details != '' ? "\n" : '') . "<b>{$link['label']}</b>: {$link['url']}";
            }
        }

        for($i = 1; $i <= 12; $i++) {
            if( isset($settings["other-{$i}-label"]) && $settings["other-{$i}-label"] != '' 
                && $member["other{$i}"] != '' 
                ) {
                $details .= ($details != '' ? "\n" : '') . '<b>' . $settings["other-{$i}-label"] . '</b>: ' . $member["other{$i}"];
            }
        }

        $members[$mid]['title'] = "{$member['last']}, {$member['first']}";
        $members[$mid]['synopsis'] = $details;
    }


    
    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'text',
            'title' => $s['title'],
            'content' => $s['content'],
            );
    } elseif( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title',
            'level' => 1,
            'title' => $s['title'],
            );
    }

    //
    // Display the list of members
    //
    $blocks[] = array(
        'type' => 'textcards',
        'collapsible' => 'yes',
        'collapsed' => 'yes',
        'level' => 3,
        'items' => $members,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
