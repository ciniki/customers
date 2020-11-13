<?php
//
// Description
// -----------
// This method will return the list of Membership Productss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Membership Products for.
//
// Returns
// -------
//
function ciniki_customers_productList($ciniki) {
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
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.productList');
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
    foreach($products as $pid => $product) {
        $products[$pid]['online_display'] = '';
        if( ($product['flags']&0x03) == 0x03 ) {
            $products[$pid]['online_display'] = 'For Sale Online';
        } elseif( ($product['flags']&0x01) == 0x01 )  {
            $products[$pid]['online_display'] = 'Visible';
        }
        $products[$pid]['amount_display'] = '$' . number_format($product['unit_amount'], 2);
    }

    return array('stat'=>'ok', 'products'=>$products);
}
?>
