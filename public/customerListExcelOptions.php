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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerListExcelOptions', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $rsp = array('stat'=>'ok');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

/*    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $args['tnid'], 'ciniki.customers', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.557', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $rsp['settings'] = isset($rc['settings']) ? $rc['settings'] : array();
 */   
    //
    // Check if subscriptions module enabled
    //
    if( isset($ciniki['tenant']['modules']['ciniki.subscriptions']) ) {
        $strsql = "SELECT ciniki_subscriptions.id, "
            . "ciniki_subscriptions.name, "
            . "ciniki_subscriptions.description, "
            . "ciniki_subscriptions.flags "
            . "FROM ciniki_subscriptions "
            . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_subscriptions.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.subscriptions', array(
            array('container'=>'subscriptions', 'fname'=>'id', 'fields'=>array('id', 'name', 'description', 'flags')),
            ));
        if( $rc['stat'] != 'ok' ) { 
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.219', 'msg'=>'Unable to retrieve subscriptions', 'err'=>$rc['err']));
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
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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

    //
    // Get the customer products
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) ) {
        $strsql = "SELECT DISTINCT id, name "
            . "FROM ciniki_customer_products "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY type, name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        $rsp['products'] = isset($rc['products']) ? $rc['products'] : array();
    }

    return $rsp;
}
?>
