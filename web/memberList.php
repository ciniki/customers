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
function ciniki_customers_web_memberList($ciniki, $settings, $tnid, $args) {


    $tag_name = '';
    if( isset($args['category']) && $args['category'] != '' ) {
        $strsql = "SELECT tag_name FROM ciniki_customer_tags "
            . "WHERE permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'tag');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows'][0]['tag_name']) ) {
            $tag_name = $rc['rows'][0]['tag_name'];
        }

        $strsql = "SELECT ciniki_customers.id, "
            . "ciniki_customers.display_name AS title, "
//          . "IF(type=2,CONCAT_WS(', ', company, last, first),CONCAT_WS(', ', last, first)) AS sname, "
//          . "IF(company<>'',CONCAT_WS(', ', company, last, first),CONCAT_WS(', ', last, first)) AS sname, "
            . "ciniki_customers.permalink, "
            . "ciniki_customers.short_description, "
            . "ciniki_customers.primary_image_id, "
            . "IF(full_bio<>'', 'yes', 'no') AS is_details "
            . "FROM ciniki_customer_tags, ciniki_customers "
            . "WHERE ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_customer_tags.tag_type = '40' "
            . "AND ciniki_customer_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND ciniki_customer_tags.customer_id = ciniki_customers.id "
            // Check the member is visible on the website
            . "AND ciniki_customers.member_status = 10 "
            . "AND (ciniki_customers.webflags&0x01) = 1 "
//          . "ORDER BY ciniki_customers.display_name ";
            . "ORDER BY ciniki_customers.sort_name ";
//          . "ORDER BY ciniki_customers.company, ciniki_customers.last, ciniki_customers.first ";
    } else {
        $strsql = "SELECT ciniki_customers.id, "
            . "ciniki_customers.display_name AS title, "
//          . "IF(type=2,CONCAT_WS(', ', company, last, first),CONCAT_WS(', ', last, first)) AS sname, "
//          . "IF(company<>'',CONCAT_WS(', ', company, last, first),CONCAT_WS(', ', last, first)) AS sname, "
            . "ciniki_customers.permalink, "
            . "ciniki_customers.short_description, "
            . "ciniki_customers.primary_image_id, "
            . "IF(full_bio<>'', 'yes', 'no') AS is_details "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            // Check the member is visible on the website
            . "AND ciniki_customers.member_status = 10 "
            . "AND (ciniki_customers.webflags&0x01) = 1 "
            . "ORDER BY ciniki_customers.sort_name ";
//          . "ORDER BY ciniki_customers.display_name, ciniki_customers.last, ciniki_customers.first ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    if( isset($args['format']) && $args['format'] == '2dlist' ) {
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'members', 'fname'=>'id',
                'fields'=>array('id', 'name'=>'title')),
            array('container'=>'list', 'fname'=>'id', 
                'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id',
                    'description'=>'short_description', 'is_details')),
            ));
    } else {
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'members', 'fname'=>'id', 
                'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id',
                    'description'=>'short_description', 'is_details')),
            ));
    }
//  $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
//      array('container'=>'members', 'fname'=>'id', 'name'=>'member',
//          'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 
//              'permalink', 'description'=>'short_bio')),
//      ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['members']) ) {
        return array('stat'=>'ok', 'tag_name'=>$tag_name, 'members'=>array());
    }
    return array('stat'=>'ok', 'tag_name'=>$tag_name, 'members'=>$rc['members']);
}
?>
