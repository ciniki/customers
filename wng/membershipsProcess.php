<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_wng_membershipsProcess($ciniki, $tnid, &$request, $section) {

    $s = isset($section['settings']) ? $section['settings'] : array();

    $blocks = array();

    //
    // Load the list of membership products
    //
    $strsql = "SELECT products.id, "
        . "products.name, "
        . "products.short_name, "
        . "products.type, "
        . "products.flags, "
        . "products.sequence, "
        . "products.unit_amount, "
        . "products.synopsis AS description "
        . "FROM ciniki_customer_products AS products "
        . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (products.flags&0x03) > 0  "
        . "ORDER BY type, sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'short_name', 'type', 'flags', 'sequence', 'unit_amount', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.491', 'msg'=>'Unable to load products', 'err'=>$rc['err']));
    }
    $products = isset($rc['products']) ? $rc['products'] : array();

    $addons = array();
    foreach($products as $pid => $product) {
        if( ($product['flags']&0x03) == 0x03 ) {
            $products[$pid]['cart'] = 'yes';
        } else {
            $products[$pid]['cart'] = 'no';
        }
        $products[$pid]['object'] = 'ciniki.customers.product';
        $products[$pid]['object_id'] = $product['id'];
        $products[$pid]['price_id'] = 0;
        $products[$pid]['units_available'] = 1;
        $products[$pid]['limited_units'] = 'yes';
        $products[$pid]['no-cart-msg'] = '&nbsp;';
        if( $product['type'] > 20 ) {
            $addons[] = $products[$pid];
            unset($product[$pid]);
        }
    }

    //
    // Check if already a member, show current products and renewal date
    //
    if( isset($request['session']['customer']['id']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'productsPurchased');
        $rc = ciniki_customers_productsPurchased($ciniki, $tnid, array(
            'customer_id' => $request['session']['customer']['id'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.515', 'msg'=>'Unable to load purchased products.', 'err'=>$rc['err']));
        }

        //
        // Show list of membership products/add-ons
        // show memberships/lifetime as checkbox, add-ons as checkboxes
        //
        if( isset($rc['membership_details']) && count($rc['membership_details']) > 0 ) {
            $details = $rc['membership_details'];
            $object_ids = array();
            $final_price = 0;
            foreach($details AS $did => $detail) {
                if( isset($products[$detail['product_id']]) ) {
                    $details[$did]['renewbutton'] = '<div class="cart"><form action="' . $request['ssl_domain_base_url'] . '/cart" method="POST">'
                        . '<input type="hidden" name="action" value="add">'
                        . '<input type="hidden" name="object" value="ciniki.customers.product">'
                        . '<input type="hidden" name="object_id" value="' . $detail['product_id'] . '">'
                        . '<input type="hidden" name="final_price" value="' . $products[$detail['product_id']]['unit_amount'] . '">'
                        . '<input type="hidden" name="quantity" value="1">'
                        . '<input class="button" type="submit" value="Renew Now"/>'
                        . '</form></div>';
                    $object_ids[] = $detail['product_id'];
                    $final_price += $products[$detail['product_id']]['unit_amount'];
                } else {
                    $details[$did]['renewbutton'] = 'Contact Us to Renew';
                }
                if( isset($products[$detail['product_id']]) && $detail['type'] > 20 ) {
                    unset($products[$detail['product_id']]);
                }
                if( isset($addons[$detail['product_id']]) && $detail['type'] > 20 ) {
                    unset($addons[$detail['product_id']]);
                }
            }
            $blocks[] = array(
                'title' => isset($s['title']) ? $s['title'] : '',
                'type' => 'table', 
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Purchase', 'field' => 'name', 'class' => 'alignleft'),
                    array('label' => 'Expiry', 'field' => 'expiry_display', 'class' => 'aligncenter'),
                    array('label' => 'Renew', 'field' => 'renewbutton', 'class' => 'alignright'),
                    ),
                'rows' => $details,
                );

            //
            // FIXME: Show membership addon's available
            //
            if( count($addons) > 0 ) {

            }

            //
            // Check if there should be a renew all button
            //
            if( count($object_ids) > 1 ) {
                $blocks[] = array(
                    'type' => 'html', 
                    'html' => '<div class="wide alignright">'
                        . '<form class="wide" action="' . $request['ssl_domain_base_url'] . '/cart" method="POST">'
                        . '<input type="hidden" name="action" value="addobjectids">'
                        . '<input type="hidden" name="object" value="ciniki.customers.product">'
                        . '<input type="hidden" name="object_ids" value="' . implode(',', $object_ids) . '">'
                        . '<input type="hidden" name="final_price" value="' . $final_price . '">'
                        . '<input type="hidden" name="quantity" value="1">'
                        . '<input class="submit" type="submit" value="Renew All Now"/>&nbsp;'
                        . '</form></div>',
                    );
            }
        } 
        //
        // Show list of products and add-ons
        //
        else {
            $blocks[] = array(
                'title' => isset($s['title']) ? $s['title'] : '',
                'type' => 'pricelist', 
                'prices' => $products, 
                'descriptions' => 'yes',
                );
        }
    }

    //
    // Show list of products and add-ons
    //
    else {
        $blocks[] = array(
            'title' => isset($s['title']) ? $s['title'] : '',
            'type' => 'pricelist', 
            'prices' => $products, 
            'descriptions' => 'yes',
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
