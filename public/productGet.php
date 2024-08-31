<?php
//
// Description
// ===========
// This method will return all the information about an membership products.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the membership products is attached to.
// product_id:          The ID of the membership products to get the details for.
//
// Returns
// -------
//
function ciniki_customers_productGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'product_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Membership Products'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.productGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Membership Products
    //
    if( $args['product_id'] == 0 ) {
        $product = array('id'=>0,
            'name'=>'',
            'short_name'=>'',
            'code'=>'',
            'permalink'=>'',
            'type'=>'10',
            'status'=>'10',
            'flags'=>'0',
            'months'=>12,
            'sequence'=>'1',
            'primary_image_id'=>'',
            'synopsis'=>'',
            'description'=>'',
            'unit_amount'=>'',
        );
    }

    //
    // Get the details for an existing Membership Products
    //
    else {
        $strsql = "SELECT ciniki_customer_products.id, "
            . "ciniki_customer_products.name, "
            . "ciniki_customer_products.short_name, "
            . "ciniki_customer_products.code, "
            . "ciniki_customer_products.permalink, "
            . "ciniki_customer_products.type, "
            . "ciniki_customer_products.status, "
            . "ciniki_customer_products.flags, "
            . "ciniki_customer_products.months, "
            . "ciniki_customer_products.sequence, "
            . "ciniki_customer_products.primary_image_id, "
            . "ciniki_customer_products.synopsis, "
            . "ciniki_customer_products.description, "
            . "ciniki_customer_products.unit_amount "
            . "FROM ciniki_customer_products "
            . "WHERE ciniki_customer_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customer_products.id = '" . ciniki_core_dbQuote($ciniki, $args['product_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'products', 'fname'=>'id', 
                'fields'=>array('name', 'short_name', 'code', 'permalink', 'type', 'status', 'flags', 'months', 'sequence',
                    'primary_image_id', 'synopsis', 'description', 'unit_amount',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.403', 'msg'=>'Membership Products not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['products'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.418', 'msg'=>'Unable to find Membership Products'));
        }
        $product = $rc['products'][0];
        $product['unit_amount'] = '$' . number_format($product['unit_amount'], 2);
    }

    return array('stat'=>'ok', 'product'=>$product);
}
?>
