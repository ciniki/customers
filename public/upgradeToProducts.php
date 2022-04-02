<?php
//
// Description
// -----------
// This method will convert memberships to customer products for the tenant.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_upgradeToProducts(&$ciniki) {
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
    // Make sure only sysadmin can run
    //
    if( ($ciniki['session']['user']['perms']&0x01) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.539', 'msg'=>'Unauthorized'));
    }

    //
    // Load the products
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_customer_products "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'products', 'fname'=>'id', 'fields'=>array('id', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.540', 'msg'=>'Unable to load products', 'err'=>$rc['err']));
    }
    $products = isset($rc['products']) ? $rc['products'] : array();

    if( count($products) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.544', 'msg'=>'No products specified'));
    }

    $individual_id = 0;
    $student_id = 0;
    $family_id = 0;
    $complimentary_id = 0;
    $reciprocal_id = 0;
    foreach($products as $product) {
        if( preg_match('/Individual/', $product['name']) ) {
            $individual_id = $product['id'];
        }
        if( preg_match('/Student/', $product['name']) ) {
            $student_id = $product['id'];
        }
        if( preg_match('/Family/', $product['name']) ) {
            $family_id = $product['id'];
        }
        if( preg_match('/Complimentary/', $product['name']) ) {
            $complimentary_id = $product['id'];
        }
        if( preg_match('/Reciprocal/', $product['name']) ) {
            $reciprocal_id = $product['id'];
        }
    }
   
    //
    // Load the members
    //
    $strsql = "SELECT customers.id, "
        . "customers.display_name, "
        . "customers.member_status, "
        . "customers.member_lastpaid, "
        . "customers.member_expires, "
        . "customers.membership_length, "
        . "customers.membership_type, "
        . "customers.date_added, "
        . "purchases.id AS purchase_id, "
        . "purchases.product_id, "
        . "purchases.start_date, "
        . "purchases.end_date "
        . "FROM ciniki_customers AS customers "
        . "LEFT JOIN ciniki_customer_product_purchases AS purchases ON ("
            . "customers.id = purchases.customer_id "
            . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND customers.membership_type > 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'members', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'member_status', 'member_lastpaid', 'member_expires', 'membership_length', 
                'membership_type', 'date_added'),
            ),
        array('container'=>'purchases', 'fname'=>'purchase_id', 
            'fields'=>array('id'=>'purchase_id', 'product_id', 'start_date', 'end_date'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.541', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    $members = isset($rc['members']) ? $rc['members'] : array();

    //
    // Create the purchases
    //
    foreach($members as $member) {
        if( isset($member['purchases']) ) {
            continue;
        }
        $product = array(
            'product_id' => $individual_id,
            'customer_id' => $member['id'],
            'flags' => 0,
            'purchase_date' => $member['member_lastpaid'],
            'invoice_id' => 0,
            'start_date' => $member['member_lastpaid'],
            'end_date' => $member['member_expires'],
            'stripe_customer_id' => '',
            'stripe_subscription_id' => '',
            );
        if( $member['membership_type'] == 20 && $student_id > 0 ) {
            $product['product_id'] = $student_id;
        }
        if( $member['membership_type'] == 40 && $family_id > 0 ) {
            $product['product_id'] = $family_id;
        }
        if( $member['membership_type'] == 110 && $complimentary_id > 0 ) {
            $product['product_id'] = $complimentary_id;
        }
        if( $member['membership_type'] == 150 && $reciprocal_id > 0 ) {
            $product['product_id'] = $reciprocal_id;
        }
        if( $member['member_lastpaid'] == '0000-00-00' ) {
            $product['purchase_date'] = $member['date_added'];
        }
        if( $member['member_expires'] != '0000-00-00' ) {
            $dt = new DateTime($member['member_expires']);
            $dt->sub(new DateInterval('P1Y'));
            $product['start_date'] = $dt->format('Y-m-d');
        }
        
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.customers.product_purchase', $product, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.542', 'msg'=>'Unable to add the product_purchase', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
