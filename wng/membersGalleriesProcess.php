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
function ciniki_customers_wng_membersGalleriesProcess($ciniki, $tnid, &$request, $section) {

    $s = isset($section['settings']) ? $section['settings'] : array();

    $blocks = array();

    //
    // Get the list of members with galleries
    //
    $strsql = "SELECT customers.id, "
        . "customers.permalink, "
        . "customers.last, "
        . "customers.first, "
        . "customers.display_name, "
        . "customers.primary_image_id, "
        . "customers.full_bio, "
        . "images.image_id, "
        . "images.permalink AS image_permalink, "
        . "images.name AS title, "
        . "images.description "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_images AS images ON ("
            . "customers.id = images.customer_id "
            . "AND (images.webflags&0x01) = 0x01 "
            . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customers.status = 10 "
        . "AND customers.member_status = 10 "
        . "AND (customers.webflags&0x01) = 0x01 "
        . "ORDER BY customers.display_name, images.date_added "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'title'=>'display_name', 'first', 'last', 'image-id'=>'primary_image_id', 'full_bio'),
            ),
        array('container'=>'images', 'fname'=>'image_id', 
            'fields'=>array('image-id'=>'image_id', 'permalink'=>'image_permalink', 'title', 'description'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.559', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();

    //
    // Process the members
    //
    $list_content = '';
    foreach($members as $mid => $member) {
        $members[$mid]['url'] = $request['page']['path'] . '/' . $member['permalink'];

        if( isset($s['list-format']) && $s['list-format'] == 'last-first' ) {
            $list_content .= ($list_content != '' ? "\n" : '')
                . "<a href='{$request['ssl_domain_base_url']}{$members[$mid]['url']}'>{$member['last']}, {$member['first']}</a>";
        } elseif( isset($s['list-format']) && $s['list-format'] == 'first-last' ) {
            $list_content .= ($list_content != '' ? "\n" : '')
                . "<a href='{$request['ssl_domain_base_url']}{$members[$mid]['url']}'>{$member['title']}</a>";
        }
        //
        // Check if member requested
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) 
            && $request['uri_split'][($request['cur_uri_pos']+1)] == $member['permalink']
            ) {
            $blocks = [];
            $blocks[] = array(
                'type' => 'title',
                'level' => 1,
                'title' => ($s['title'] != '' ? $s['title'] . ' - ' : '') . $member['title'],
//                'content' => $member['full_bio'],
//                'image-id' => $member['image-id'],
                );
            if( isset($member['full_bio']) && $member['full_bio'] != '' ) {
                $blocks[] = array(
                    'type' => 'text',
                    'content' => $member['full_bio'],
                    );
            }

            //
            // Process the images
            //
            $images = [];
            foreach($member['images'] as $iid => $image) {
                $image['url'] = $request['page']['path'] . '/' . $member['permalink'] . '/' . $image['permalink'];
                $image['title-position'] = 'below';
                $images[$image['permalink']] = $image;
            }
            //
            // Check if image selected
            //
            if( isset($request['uri_split'][($request['cur_uri_pos']+2)]) 
                && $images[$request['uri_split'][($request['cur_uri_pos']+2)]]
                ) {
                $image = $images[$request['uri_split'][($request['cur_uri_pos']+2)]];
                $blocks = array();
                $blocks[] = array(
                    'type' => 'title',
                    'level' => 1,
                    'title' => ($s['title'] != '' ? $s['title'] . ' - ' : '') . $member['title'],
                    );
                $blocks[] = array(
                    'type' => 'image',
                    'image-permalink' => $image['permalink'],
                    'image-id' => $image['image-id'],
                    'title' => $image['title'],
                    'base-url' => $request['page']['path'] . '/' . $member['permalink'],
                    'image-list' => $images,
                    );
                return array('stat'=>'ok', 'blocks'=>$blocks, 'clear'=>'yes');
            }
            $blocks[] = array(
                'type' => 'imagebuttons',
                'title-position' => 'below',
                'base-url' => $request['page']['path'] . '/' . $member['permalink'],
                'items' => $images,
                );
            return array('stat'=>'ok', 'blocks'=>$blocks, 'clear'=>'yes');
        }
    }

    //
    // Show the list of members
    //
    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'text',
            'class' => 'member-galleries',
            'title' => $s['title'],
            'content' => $s['content'],
            );
    } elseif( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title',
            'class' => 'member-galleries',
            'level' => 1,
            'title' => $s['title'],
            );
    }

    if( isset($s['list-format']) && $s['list-format'] == 'last-first' ) {
        $blocks[] = array(
            'type' => 'text',
            'columns' => 'medium',
            'content' => $list_content,
            );
    } elseif( isset($s['list-format']) && $s['list-format'] == 'first-last' ) {
        $blocks[] = array(
            'type' => 'text',
            'columns' => 'medium',
            'content' => $list_content,
            );
    } else {
        $blocks[] = array(
            'type' => 'imagebuttons',
            'title-position' => 'below',
            'base-url' => $request['page']['path'],
            'items' => $members,
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
