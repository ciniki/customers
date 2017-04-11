<?php
//
// Description
// -----------
//
// Arguments
// ---------
// 
// 
// Returns
// -------
//
function ciniki_customers_customerListExcelOptions($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.customerListExcelOptions', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $rsp = array('stat'=>'ok');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Check if subscriptions module enabled
    //
    if( isset($ciniki['business']['modules']['ciniki.subscriptions']) ) {
        $strsql = "SELECT ciniki_subscriptions.id, "
            . "ciniki_subscriptions.name, "
            . "ciniki_subscriptions.description, "
            . "ciniki_subscriptions.flags "
            . "FROM ciniki_subscriptions "
            . "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_subscriptions.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.subscriptions', array(
            array('container'=>'subscriptions', 'fname'=>'id', 'fields'=>array('id', 'name', 'description', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.10', 'msg'=>'Unable to retrieve subscriptions', 'err'=>$rc['err']));
        }
        if( isset($rc['subscriptions']) ) {
            $rsp['subscriptions'] = $rc['subscriptions'];
        }
    }

    //
    // Check for customer categories
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0xC00000) ) {
        $strsql = "SELECT DISTINCT tag_type, tag_name "
            . "FROM ciniki_customer_tags "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'types', 'fname'=>'tag_type', 'fields'=>array('tag_type')),
            array('container'=>'tags', 'fname'=>'tag_name', 'fields'=>array('name'=>'tag_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['types']) ) {
            foreach($rc['types'] as $tid => $tag_type) {
                if( $tag_type['tag_type'] == 10 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x400000) ) { 
                    $rsp['customer_categories'] = $tag_type['tags'];
                } elseif( $tag_type['tag_type'] == 20 && ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x800000) ) { 
                    $rsp['customer_tags'] = $tag_type['tags'];
                }
            }
        }
    }

    return $rsp;
}
?>
