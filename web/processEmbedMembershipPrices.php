<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_web_processEmbedMembershipPrices(&$ciniki, $settings, $tnid, $args) {

    $prices = array();

    //
    // Load the membership products
    //
    $strsql = "SELECT products.id, "
        . "products.name, "
        . "products.short_name, "
        . "products.type, "
        . "products.flags, "
        . "products.sequence, "
        . "products.unit_amount, "
        . "products.synopsis "
        . "FROM ciniki_customer_products AS products "
        . "WHERE products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND type IN (10, 20) "
        . "ORDER BY type, sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'short_name', 'type', 'flags', 'sequence', 'unit_amount', 'synopsis')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.434', 'msg'=>'Unable to load products', 'err'=>$rc['err']));
    }
    $products = isset($rc['products']) ? $rc['products'] : array();

    //
    // Check if customer logged in, then load their current membership
    //
    $blocks = array();
    $cart = 'yes';
    if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'productsPurchased');
        $rc = ciniki_customers_productsPurchased($ciniki, $tnid, array('customer_id' => $ciniki['session']['customer']['id']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.433', 'msg'=>'Unable to load purchases', 'err'=>$rc['err']));
        }
        if( isset($rc['membership_details']['type']['type']) && $rc['membership_details']['type']['type'] == 20 ) {
            $blocks[] = array('type'=>'content', 'content'=>"You are currently a " . $rc['membership_details']['type']['short_name'] . " Member."); 
            $cart = 'no';
        }
        if( isset($rc['membership_details']['type']['type']) && $rc['membership_details']['type']['type'] == 10 ) {
            //
            // Invisible online, unable to renew online (Complimentary, Student, etc)
            //
            if( ($rc['membership_details']['type']['flags']&0x01) == 0 ) {
                $blocks[] = array('type'=>'content', 'html'=>"<p class='cart-pricelist-renew-contact'><b>Please contact us to renew your " . $rc['membership_details']['type']['name'] . ".</b></p>");
                $cart = 'no';
            } 
            //
            // Visible but not for sale online, unable to renew online (Complimentary, Student, etc)
            //
            elseif( ($rc['membership_details']['type']['flags']&0x03) == 0x01 ) {
                $blocks[] = array('type'=>'content', 'html'=>"<p class='cart-pricelist-renew-contact'><b>Please contact us to renew your " . $rc['membership_details']['type']['name'] . ".</b></p>");
                $cart = 'no';
            } 
            //
            // Renew Online
            //
            elseif( ($rc['membership_details']['type']['flags']&0x03) == 0x03 
                && isset($products[$rc['membership_details']['type']['product_id']]) 
                ) {

                $cart = 'yes';
                if( $rc['membership_details']['type']['expires'] == 'future' ) {
                    $blocks[] = array('type'=>'content', 'content'=>"<p class='cart-pricelist-renew-contact'><b>Your " . $rc['membership_details']['type']['name'] . ' has expires on ' . $rc['membership_details']['type']['expiry_display'] . '.</b></p>'); 
                } else {
                    $blocks[] = array('type'=>'content', 'content'=>"<p class='cart-pricelist-renew-contact'><b>Your " . $rc['membership_details']['type']['name'] . ' expired on ' . $rc['membership_details']['type']['expiry_display'] . ', renew today.</b></p>'); 
                }
                
/*                $cart = 'yes';
                $product = $products[$rc['membership_details']['type']['product_id']];
                $prices = array(
                    array(
                        'name' => $product['name'],
                        'cart' => 'yes',
                        'object' => 'ciniki.customers.product',
                        'object_id' => $product['id'],
                        'price_id' => 0,
                        'unit_amount' => $product['unit_amount'],
                        'unit_available' => 1,
                        'limited_units' => 'yes',
                        'add_text' => 'Renew Today',
                        'description' => $product['synopsis'],
                        ),
                    );

                $blocks = array(
                    array('type'=>'content', 'content'=>'Your ' . $rc['membership_details']['type']['name'] . ''),
                    array('type'=>'prices', 'prices'=>$prices)
                    );
                return array('stat'=>'ok', 'blocks'=>$blocks);  */
            }
        }
    }

    //
    // Create the list of prices
    //
    $prices = array();
    foreach($products as $product) {
        if( ($product['flags']&0x03) == 0x03 ) {
            $prices[] = array(
                'name' => $product['name'],
                'description' => $product['synopsis'],
                'cart' => $cart,
                'object' => 'ciniki.customers.product',
                'object_id' => $product['id'],
                'price_id' => 0,
                'unit_amount' => $product['unit_amount'],
                'units_available' => 1,
                'limited_units' => 'yes',
                );
        } elseif( ($product['flags']&0x03) == 0x01 ) {
            $prices[] = array(
                'name' => $product['name'],
                'description' => $product['synopsis'],
                'object' => 'ciniki.customers.product',
                'object_id' => $product['id'],
                'price_id' => 0,
                'unit_amount' => $product['unit_amount'],
                'units_available' => 1,
                'limited_units' => 'yes',
                );
        }
    }

    $blocks[] = array('type'=>'prices', 'prices'=>$prices, 'descriptions'=>'yes');
    
    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
