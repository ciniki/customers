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
function ciniki_customers_web_accountProcessRequestMembership($ciniki, $settings, $tnid, $args) {


    $page = array(
        'title'=>'Membership',
        'container-class'=>'page-account-membership',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );
    $page['breadcrumbs'][] = array('name'=>'Membership', 'url'=>$args['base_url'] . '/membership');
    $base_url = $args['base_url'] . '/membership';

    //
    // Double check the account is logged in, should never reach this spot
    //
    if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.359', 'msg'=>'Not logged in'));
    }

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
//        . "AND products.type IN (10, 20) "
        . "AND (products.flags&0x03) > 0  "
        . "ORDER BY type, sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'products', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'short_name', 'type', 'flags', 'sequence', 'unit_amount', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.434', 'msg'=>'Unable to load products', 'err'=>$rc['err']));
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
        if( $product['type'] > 20 ) {
            $addons[] = $products[$pid];
            unset($product[$pid]);
        }
    }

/*    //
    // Load the list of membership products
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
        . "AND products.type IN (40) "
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
    $addons = isset($rc['products']) ? $rc['products'] : array();
*/
    //
    // Check if already a member, show current products and renewal date
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'productsPurchased');
    $rc = ciniki_customers_productsPurchased($ciniki, $tnid, array(
        'customer_id' => $ciniki['session']['customer']['id'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.430', 'msg'=>'Unable to load purchased products.', 'err'=>$rc['err']));
    }

    //
    // Show list of membership products/add-ons
    // show memberships/lifetime as checkbox, add-ons as checkboxes
    //
    if( isset($rc['membership_details']) && count($rc['membership_details']) > 0 ) {
        $details = $rc['membership_details'];
        foreach($details AS $did => $detail) {
            if( isset($products[$detail['product_id']]) ) {
                $details[$did]['renewbutton'] = '<div class="cart"><form action="' . $ciniki['request']['ssl_domain_base_url'] . '/cart" method="POST">'
                    . '<input type="hidden" name="action" value="add">'
                    . '<input type="hidden" name="object" value="ciniki.customers.product">'
                    . '<input type="hidden" name="object_id" value="' . $detail['product_id'] . '">'
                    . '<input type="hidden" name="final_price" value="' . $products[$detail['product_id']]['unit_amount'] . '">'
                    . '<input type="hidden" name="quantity" value="1">'
                    . '<input class="cart-submit" type="submit" value="Renew Now"/>'
                    . '</form></div>';
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
        $page['blocks'][] = array('type'=>'table', 
            'title' => '',
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Purchase', 'field' => 'name', 'class' => 'alignleft'),
                array('label' => 'Expiry', 'field' => 'expiry_display', 'class' => 'aligncenter'),
                array('label' => 'Renew', 'field' => 'renewbutton', 'class' => 'alignright'),
                ),
            'rows' => $details,
            );

        //
        // Show membership addon's available
        //
        if( count($addons) > 0 ) {

        }
    } 
    //
    // Show list of products and add-ons
    //
    else {
        $page['blocks'][] = array('type'=>'content', 'wide'=>'yes', 'content'=>'You do not currently have a membership. '
            . 'To become a member, choose one of the options below.');
        $page['blocks'][] = array('type'=>'prices', 'class'=>'wide', 'prices'=>$products);
    }

    //
    // Show history
    //
    if( isset($rc['history']) && count($rc['history']) > 0 ) {
        $page['blocks'][] = array('type'=>'table', 
            'title' => 'History',
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Purchase', 'field' => 'name'),
                array('label' => 'Expired', 'field' => 'expired', 'class' => 'alignright'),
                ),
            'rows' => $rc['history'],
            );
    }

    

//    $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($rc, true) . '</pre>');




    //
    // Check if individual account and go directly to contact form edit
    //
    /*
        if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'remove' ) {
            //
            // Display the confirmation screen to remove child/parent
            //
            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'warning', 'message'=>"Are you sure you want to remove {$customer_name}?");
            $form = "<form action='' method='POST'>"
                . "<input type='hidden' name='action' value='remove'>"
                . "<input type='hidden' name='confirm' value='yes'>"
                . "";
            if( $ciniki['session']['account']['type'] == 20 && $customer_type == 'parent' ) {
                $form .= "<div class='submit'>"
                    . "<a class='cancel button' href='{$base_url}'>Cancel</a>"
                    . "&nbsp;&nbsp;<input name='submit' type='submit' class='submit submit-delete' value=' Remove Parent/Guardian '>"
                    . "</div>";
            } elseif( $ciniki['session']['account']['type'] == 20 && $customer_type == 'child' ) {
                $form .= "<div class='submit'>"
                    . "<a class='cancel button' href='{$base_url}'>Cancel</a>"
                    . "&nbsp;&nbsp;<input name='submit' type='submit' class='submit submit-delete' value=' Remove Child '>"
                    . "</div>";
            } elseif( $ciniki['session']['account']['type'] == 30 && $customer_type == 'parent' ) {
                $form .= "<div class='submit'>"
                    . "<a class='cancel button' href='{$base_url}'>Cancel</a>"
                    . "&nbsp;&nbsp;<input name='submit' type='submit' class='submit submit-delete' value=' Remove Admin '>"
                    . "</div>";
            } elseif( $ciniki['session']['account']['type'] == 30 && $customer_type == 'child' ) {
                $form .= "<div class='submit aligncenter'>"
                    . "<a class='cancel button' href='{$base_url}'>Cancel</a>"
                    . "&nbsp;&nbsp;<input name='submit' type='submit' class='submit submit-delete' value=' Remove Employee '>"
                    . "</div>";
            }
            $form .= "</form>";
            $page['blocks'][] = array('type'=>'content', 'html'=>$form);
        } else if( isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][1] == 'admin' && $ciniki['request']['uri_split'][2] == 'add' ) {
            $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>Make Admin</pre>');
            $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($ciniki['session'], true) . "</pre>");
        } else if( isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][1] == 'admin' && $ciniki['request']['uri_split'][2] == 'remove' ) {
            $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>Remove Admin</pre>');
            $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($ciniki['session'], true) . "</pre>");
            
        } else {
            $args['form_url'] = $base_url . '/' . $ciniki['request']['uri_split'][0];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountProcessRequestContactForm');
            $rc = ciniki_customers_web_accountProcessRequestContactForm($ciniki, $settings, $tnid, $args);
            if( $rc['stat'] == 'updated' ) {
                if( isset($_POST['redirect']) && $_POST['redirect'] != '' ) {
                    Header("Location: " . $_POST['redirect']);
                } else {
                    Header("Location: " . $base_url);
                }
                exit;
            }
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.337', 'msg'=>'Unable to process request', 'err'=>$rc['err']));
            }
            foreach($rc['blocks'] as $block) {
                $page['blocks'][] = $block;
            }
            //$page['blocks'] = array_merge($page['blocks'], $rc['blocks']);
        }
    } 
    
    elseif( $ciniki['session']['account']['type'] == 20 || $ciniki['session']['account']['type'] == 30 ) {
        //
        // Get the list of parents/children/admins/employees
        //
        $page['container-class'] = 'page-account-children';
        if( $ciniki['session']['account']['type'] == 20 ) {
            if( count($ciniki['session']['account']['parents']) > 0 ) {
                $page['blocks'][] = array('type'=>'table',
                    'title' => 'Parents/Guardians',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Name', 'field' => 'display_name'),
                        array('label' => '<a href="' . $base_url . '/add/parent">Add</a>', 'strsub' => '<a href="' . $base_url . '/{_uuid_}">Edit</a><a href="' . $base_url . '/{_uuid_}/remove">Remove</a>'),
                    ),
                    'rows' => $ciniki['session']['account']['parents'],
                );
            }
            $page['blocks'][] = array('type'=>'table',
                'title' => 'Children',
                'headers' => 'yes',
                'empty' => 'No children',
                'columns' => array(
                    array('label' => 'Name', 'field' => 'display_name'),
                    array('label' => '<a href="' . $base_url . '/add">Add</a>', 'strsub' => '<a href="' . $base_url . '/{_uuid_}">Edit</a><a href="' . $base_url . '/{_uuid_}/remove">Remove</a>'),
                ),
                'rows' => $ciniki['session']['account']['children'],
            );

        } elseif( $ciniki['session']['account']['type'] == 30 ) {
            if( count($ciniki['session']['account']['parents']) > 0 ) {
                $page['blocks'][] = array('type'=>'table',
                    'title' => 'Administrators',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Name', 'field' => 'display_name'),
                        array('label' => '<a href="' . $base_url . '/add/admin">Add</a>', 'strsub' => '<a href="' . $base_url . '/{_uuid_}">Edit</a><a href="' . $base_url . '/{_uuid_}/remove">Remove</a>'),
                    ),
                    'rows' => $ciniki['session']['account']['parents'],
                );
            }
            $page['blocks'][] = array('type'=>'table',
                'title' => 'Employees',
                'headers' => 'yes',
                'empty' => 'No employees',
                'columns' => array(
                    array('label' => 'Name', 'field' => 'display_name'),
                    array('label' => '<a href="' . $base_url . '/add">Add</a>', 
                        'strsub' => '<a href="' . $base_url . '/{_uuid_}">Edit</a><a href="' . $base_url . '/{_uuid_}/remove">Remove</a>'),
                ),
                'rows' => $ciniki['session']['account']['children'],
            );
        }
    } else {
    }
*/
//        $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Invalid request, please try again or contact us for help.');

    return array('stat'=>'ok', 'page'=>$page);
}
?>
