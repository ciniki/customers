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
function ciniki_customers_web_accountProcessRequestChildren($ciniki, $settings, $business_id, $args) {

    $required = (isset($args['required'])?$args['required']:array());

    $page = array(
        'title'=>'Children',
        'container-class'=>'page-account-children',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );
    $page['breadcrumbs'][] = array('name'=>'Children', 'url'=>$args['base_url'] . '/account/chilren');

    $base_url = $args['base_url'] . '/children';
    if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'cartadd' ) {
        $base_url .= '/cartadd';
    }
    $errors = 'no';
    $error_msg = '';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Cancel' && isset($_POST['next']) && $_POST['next'] != '' ) {
        header("Location: " . $_POST['next']);
        exit;
    }

    //
    // Get the user if specified
    //
    if( isset($_POST['child_id']) && $_POST['child_id'] > 0 ) {
        $strsql = "SELECT id, type, display_name, sort_name, permalink, prefix, first, middle, last, suffix, company "
            . "FROM ciniki_customers "
            . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $_POST['child_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'child');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3531', 'msg'=>'We ran into a problem, please try again or contact us for help.'));
        }
        if( isset($rc['child']) ) {
            $child = $rc['child'];
        } else {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3532', 'msg'=>'We had a problem, please try again or contact us for help.'));
        }
    }

    //
    // Check if update to child
    //
    if( isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['child_id']) && $_POST['child_id'] > 0 
        && (!isset($_POST['submit']) || $_POST['submit'] != 'Cancel') 
        ) {
        $child_args = array();
        if( isset($_POST['first']) && $_POST['first'] != $child['first'] ) {
            $child_args['first'] = $_POST['first'];
            $child['first'] = $_POST['first'];
        }
        if( isset($_POST['last']) && $_POST['last'] != $child['last'] ) {
            $child_args['last'] = $_POST['last'];
            $child['last'] = $_POST['last'];
        }
        if( count($child_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateName');
            $rc = ciniki_customers_customerUpdateName($ciniki, $business_id, $child, $child['id'], $child_args);
            if( $rc['stat'] == 'ok' ) {
                if( isset($rc['display_name']) && $child['display_name'] != $rc['display_name'] ) {
                    $child_args['display_name'] = $rc['display_name'];
                }
                if( isset($rc['sort_name']) && $child['sort_name'] != $rc['sort_name'] ) {
                    $child_args['sort_name'] = $rc['sort_name'];
                }
                if( isset($rc['permalink']) && $child['permalink'] != $rc['permalink'] ) {
                    $child_args['permalink'] = $rc['permalink'];
                }
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.customer', $_POST['child_id'], $child_args);
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update.";
            } else {
                $updated = 'yes';
            }
        }
        if( $errors == 'no' && isset($_POST['next']) && $_POST['next'] != '' ) {
            header("Location: " . $_POST['next']);
            exit;
        }
        
    } elseif( isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['child_id']) && $_POST['child_id'] == 0 ) {
        $child = array('first'=>$_POST['first'], 'last'=>$_POST['last']);
        if( (!isset($_POST['first']) || trim($_POST['first']) == '') || (!isset($_POST['last']) || trim($_POST['last']) == '') ) {
            $_POST['action'] = 'edit';
            $errors = 'yes';
            $error_msg .= "You must specify a first and last name.";
        } else {
            $child_args = array('id'=>'', 'type'=>1, 'display_name'=>'', 'first'=>'', 'last'=>'', 'parent_id'=>$ciniki['session']['customer']['id']);
            $child_args['first'] = $_POST['first'];
            $child_args['last'] = $_POST['last'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerAdd');
            $rc = ciniki_customers_web_customerAdd($ciniki, $business_id, $child_args);
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update.";
            } else {
                $updated = 'yes';
            }
        }

        if( $errors == 'no' && isset($_POST['next']) && $_POST['next'] != '' ) {
            header("Location: " . $_POST['next']);
            exit;
        }
    }

    //
    // Get the current list of children
    //
    $strsql = "SELECT id, display_name, first, last "
        . "FROM ciniki_customers "
        . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'children', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'first', 'last')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['children']) ) {
        $children = $rc['children'];
    } else {
        $children = array();
    }

    //
    // Check if form to be displayed
    //
    if( (isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'cartadd')
        || (isset($_POST['action']) && isset($_POST['child_id']) 
            && ($_POST['action'] == 'edit' || (isset($errors) && $errors == 'yes' && ($_POST['action'] == 'update' || $_POST['action'] == 'add'))) 
            )
        ) {
        $page['title'] = 'Add a child';
        if( isset($error_msg) && $error_msg != '' ) {
            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>$error_msg);
        }
        $content = '';
//        $content .= "<div class='cart'>";
        $content .= "<form action='$base_url' method='POST'>"
            . "<input type='hidden' name='child_id' value='" . (isset($_POST['child_id']) ? $_POST['child_id'] : 0) . "' />"
            . "<input type='hidden' name='action' value='update' />"
            . "";
        if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'cartadd' ) {
            $content .= "<input type='hidden' name='next' value='" . $ciniki['request']['ssl_domain_base_url'] . "/cart' />";
        }
        $content .= "<div class='input first'>"
            . "<label for='first'>First Name" . (in_array('first', $required)?' *':'') . "</label>"
            . "<input type='text' class='text' name='first' value='" . (isset($child['first']) ? $child['first'] : '') . "'>"
            . "</div>";
        $content .= "<div class='input last'>"
            . "<label for='last'>Last Name" . (in_array('last', $required)?' *':'') . "</label>"
            . "<input type='text' class='text' name='last' value='" . (isset($child['last']) ? $child['last'] : '') . "'>"
            . "</div>";
        $content .= "<div class='submit'>"
            . "<input class='submit' type='submit' name='submit' value='Cancel' />&nbsp;"
            . "<input class='submit' type='submit' name='submit' value='Save' />"
            . "</div>";
        $content .= "</form>";
//        $content .= "</div>";
        $page['blocks'][] = array('type'=>'content', 'html'=>$content);
    }
    
    //
    // Display the list of children
    //
    else {
        $content = '';
        $content .= "<div class='cart'>";
        if( count($children) > 0 ) {
            $content .= "<div class='cart-items'>";
            $content .= "<table class='cart-items'>"
//                . "<thead><tr><th>Name</th><th>Action</th></tr></thead>"
                . "<tbody>";
            $count = 0;
            foreach($children as $child_id => $child) {
                $content .= "<tr class='" . (($count%2)==0 ? 'item-even' : 'item-odd') . "'><td>" . $child['display_name'] . "</td><td class='alignright'>"
                    . "<span class='cart-submit'>"
                    . "<form action='$base_url' method='POST'>"
                    . "<input type='hidden' name='child_id' value='$child_id' />"
                    . "<input type='hidden' name='action' value='edit' />"
                    . "<input class='cart-submit' type='submit' name='submit' value='Edit'>"
                    . "</form>"
                    . "</span>"
                    . "</td></tr>";
                $count++;
            }
            $content .= "</table>";
            $content .= "</div>";
        } else {
//            $page['formmessage'] = array('type'=>'formmessage', 'level'=>'success', 'content'=>'');
        }
        $content .= "<form action='$base_url' class='wide' method='POST'>"
            . "<input type='hidden' name='child_id' value='0' />"
            . "<input type='hidden' name='action' value='edit' />"
            . "<div class='cart-buttons'>"
                . "<span class='cart-submit'>"
                    . "<input class='cart-submit' type='submit' name='submit' value='Add'>"
                . "</span>"
            . "</div>"
            . "</form>";
        $content .= "</div>";
        $page['blocks'][] = array('type'=>'content', 'html'=>$content);
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
