<?php
//
// Description
// -----------
// This method searchs for a Membership Productss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Membership Products for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_customers_productSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.productSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
        . "ciniki_customer_products.status, "
        . "ciniki_customer_products.flags, "
        . "ciniki_customer_products.months, "
        . "ciniki_customer_products.unit_amount "
        . "FROM ciniki_customer_products "
        . "WHERE ciniki_customer_products.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'short_name', 'code', 'permalink', 'type', 'status', 'flags', 'months', 'unit_amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['products']) ) {
        $products = $rc['products'];
        $product_ids = array();
        foreach($products as $iid => $product) {
            $product_ids[] = $product['id'];
        }
    } else {
        $products = array();
        $product_ids = array();
    }

    return array('stat'=>'ok', 'products'=>$products, 'nplist'=>$product_ids);
}
?>
