<?php
//
// Description
// -----------
// This function will return a list of posts organized by date
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
// type:            The type of the tag.
//
//
// Returns
// -------
//
function ciniki_customers_web_tagCloud($ciniki, $settings, $tnid, $type) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Build the query to get the tags
    //
    $strsql = "SELECT ciniki_customer_tags.tag_name, "
        . "ciniki_customer_tags.permalink, "
        . "COUNT(ciniki_customers.id) AS num_tags "
        . "FROM ciniki_customer_tags, ciniki_customers "
        . "WHERE ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customer_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $type) . "' "
        . "AND ciniki_customer_tags.customer_id = ciniki_customers.id "
        . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_customers.status < 40 "
        . "AND ciniki_customers.member_status = 10 "
        . "AND (ciniki_customers.webflags&0x01) = 1 "
        . "GROUP BY tag_name "
        . "ORDER BY tag_name "
        . "";
    //
    // Get the list of posts, sorted by publish_date for use in the web CI List Categories
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'tags', 'fname'=>'permalink', 
            'fields'=>array('name'=>'tag_name', 'permalink', 'num_tags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        $tags = $rc['tags'];
    } else {
        $tags = array();
    }

    return array('stat'=>'ok', 'tags'=>$tags);
}
?>
