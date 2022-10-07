<?php
//
// Description
// -----------
// Update the tags
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_tagsUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'tag'=>array('required'=>'yes', 'blank'=>'yes', 'type'=>'text', 'name'=>'Tag'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.tagsUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the existing tags
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsByType');
    $rc = ciniki_core_tagsByType($ciniki, 'ciniki.customers', $args['tnid'], 'ciniki_customer_tags', array($args['tag']));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tags = isset($rc['types'][0]['type']['tags']) ? $rc['types'][0]['type']['tags'] : array();

    //
    // Check each tag for a new name
    //
    foreach($tags as $tag) {
        
        $tag = $tag['tag'];
        
        if( isset($ciniki['request']['args'][$tag['permalink']]) 
            && $ciniki['request']['args'][$tag['permalink']] != $tag['name']
            ) {
            $tag_name = $ciniki['request']['args'][$tag['permalink']];
            $tag_permalink = ciniki_core_makePermalink($ciniki, $tag_name);

            //
            // Get the list of tags that need updating
            //
            $strsql = "SELECT id, uuid, tag_name, permalink "
                . "FROM ciniki_customer_tags "
                . "WHERE tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag']) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $tag['permalink']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.548', 'msg'=>'Unable to load tag', 'err'=>$rc['err']));
            }
            $rows = isset($rc['rows']) ? $rc['rows'] : array();
                
            foreach($rows as $row) {

                if( $tag_permalink == '' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.customers.tag', $row['id'], $row['uuid'], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.551', 'msg'=>'Unable to update the tag', 'err'=>$rc['err']));
                    }

                } else {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.tag', $row['id'], array(
                        'tag_name' => $tag_name,
                        'tag_permalink' => $tag_permalink,
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.549', 'msg'=>'Unable to update the tag', 'err'=>$rc['err']));
                    }
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
