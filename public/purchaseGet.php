<?php
//
// Description
// ===========
// This method will return all the information about an membership product purchases.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the membership product purchases is attached to.
// purchase_id:          The ID of the membership product purchases to get the details for.
//
// Returns
// -------
//
function ciniki_customers_purchaseGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'purchase_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Membership Product Purchases'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.purchaseGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
        
    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Membership Product Purchases
    //
    if( $args['purchase_id'] == 0 ) {
        $purchase = array('id'=>0,
            'product_id'=>'',
            'customer_id'=>'',
            'flags'=>'0',
            'purchase_date'=>'',
            'invoice_id'=>'',
            'start_date'=>'',
            'end_date'=>'',
            'stripe_customer_id'=>'',
            'stripe_subscription_id'=>'',
        );
    }

    //
    // Get the details for an existing Membership Product Purchases
    //
    else {
        $strsql = "SELECT ciniki_customer_product_purchases.id, "
            . "ciniki_customer_product_purchases.product_id, "
            . "ciniki_customer_product_purchases.customer_id, "
            . "ciniki_customer_product_purchases.flags, "
            . "ciniki_customer_product_purchases.purchase_date, "
            . "ciniki_customer_product_purchases.invoice_id, "
            . "ciniki_customer_product_purchases.start_date, "
            . "ciniki_customer_product_purchases.end_date, "
            . "ciniki_customer_product_purchases.stripe_customer_id, "
            . "ciniki_customer_product_purchases.stripe_subscription_id "
            . "FROM ciniki_customer_product_purchases "
            . "WHERE ciniki_customer_product_purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_customer_product_purchases.id = '" . ciniki_core_dbQuote($ciniki, $args['purchase_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'purchases', 'fname'=>'id', 
                'fields'=>array('product_id', 'customer_id', 'flags', 'purchase_date', 'invoice_id', 'start_date', 'end_date', 'stripe_customer_id', 'stripe_subscription_id'),
                'utctotz'=>array('purchase_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.439', 'msg'=>'Membership Product Purchases not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['purchases'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.440', 'msg'=>'Unable to find Membership Product Purchases'));
        }
        $purchase = $rc['purchases'][0];
    }

    //
    // Get the list of products
    //
    $strsql = "SELECT ciniki_customer_products.id, "
        . "ciniki_customer_products.name, "
        . "ciniki_customer_products.short_name, "
        . "ciniki_customer_products.code, "
        . "ciniki_customer_products.permalink, "
        . "ciniki_customer_products.type, "
        . "ciniki_customer_products.type AS type_display, "
        . "ciniki_customer_products.status, "
        . "ciniki_customer_products.status AS status_display, "
        . "ciniki_customer_products.flags, "
        . "ciniki_customer_products.months, "
        . "ciniki_customer_products.unit_amount "
        . "FROM ciniki_customer_products "
        . "WHERE ciniki_customer_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY type, sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'short_name', 'code', 'permalink', 
                'type', 'type_display', 'status', 'status_display', 'flags', 
                'unit_amount',
                ),
            'maps'=>array(
                'type_display'=>$maps['product']['type'],
                'status_display'=>$maps['product']['status'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $products = isset($rc['products']) ? $rc['products'] : array();

    return array('stat'=>'ok', 'purchase'=>$purchase, 'products'=>$products);
}
?>
