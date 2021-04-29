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
function ciniki_customers_wng_accountChildrenProcess($ciniki, $tnid, &$request, $item) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    $required = array();        // List of required fields

    $blocks = array();

    $request['breadcrumbs'][] = array(
        'title' => 'Children',
        'page-class' => 'page-account-children',
        'url' => $request['base_url'] . '/account/children',
        );

    $base_url = $request['base_url'] . '/account/children';
    if( isset($request['uri_split'][2]) && $request['uri_split'][2] == 'cartadd' ) {
        $base_url .= '/cartadd';
    }
    $errors = 'no';
    $error_msg = '';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Cancel' && isset($_POST['next']) && $_POST['next'] != '' ) {
        header("Location: " . $_POST['next']);
        return array('stat'=>'exit');
    }

    //
    // Get the user if specified
    //
    if( isset($_POST['child_id']) && $_POST['child_id'] > 0 ) {
        $strsql = "SELECT id, type, display_name, sort_name, permalink, prefix, first, middle, last, suffix, company "
            . "FROM ciniki_customers "
            . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $_POST['child_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'child');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.176', 'msg'=>'We ran into a problem, please try again or contact us for help.'));
        }
        if( isset($rc['child']) ) {
            $child = $rc['child'];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.177', 'msg'=>'We had a problem, please try again or contact us for help.'));
        }
    }

    //
    // Check if update to child
    //
    if( isset($_POST['action']) && $_POST['action'] == 'update' 
        && isset($_POST['child_id']) && $_POST['child_id'] > 0 
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
            $rc = ciniki_customers_customerUpdateName($ciniki, $tnid, $child, $child['id'], $child_args);
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
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $_POST['child_id'], $child_args);
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update.";
            } else {
                $updated = 'yes';
            }
        }
        if( $errors == 'no' && isset($_POST['next']) && $_POST['next'] != '' ) {
            header("Location: " . $_POST['next']);
            return array('stat'=>'exit');
        }
        
    } elseif( isset($_POST['action']) && $_POST['action'] == 'update' 
        && isset($_POST['child_id']) && $_POST['child_id'] == 0 
        && (!isset($_POST['submit']) || $_POST['submit'] != 'Cancel') 
        ) {
        $child = array('first'=>$_POST['first'], 'last'=>$_POST['last']);
        if( (!isset($_POST['first']) || trim($_POST['first']) == '') || (!isset($_POST['last']) || trim($_POST['last']) == '') ) {
            $_POST['action'] = 'edit';
            $errors = 'yes';
            $error_msg .= "You must specify a first and last name.";
        } else {
            $child_args = array('id'=>'', 'type'=>1, 'display_name'=>'', 'first'=>'', 'last'=>'', 'parent_id'=>$request['session']['customer']['id']);
            $child_args['first'] = $_POST['first'];
            $child_args['last'] = $_POST['last'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerAdd');
            $rc = ciniki_customers_web_customerAdd($ciniki, $tnid, $child_args);
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update.";
            } else {
                $updated = 'yes';
            }
        }

        if( $errors == 'no' && isset($_POST['next']) && $_POST['next'] != '' ) {
            header("Location: " . $_POST['next']);
            return array('stat'=>'exit');
        }
    }

    //
    // Get the current list of children
    //
    $strsql = "SELECT id, display_name, first, last "
        . "FROM ciniki_customers "
        . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
    if( (isset($request['uri_split'][2]) && $request['uri_split'][2] == 'cartadd')
        || (isset($_GET['add']) && $_GET['add'] == 'yes')
        || (isset($_POST['action']) && isset($_POST['child_id']) 
            && ($_POST['action'] == 'edit' || (isset($errors) && $errors == 'yes' && ($_POST['action'] == 'update' || $_POST['action'] == 'add'))) 
            )
        ) {
        if( isset($error_msg) && $error_msg != '' ) {
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error', 
                'content' => $error_msg,
                );
        }
        $content = '';
        $content .= "<div class='block-account-children'>";
        $content .= "<div class='wrap'>";
        $content .= "<div class='content'>";

        $content .= "<form action='$base_url' method='POST'>"
            . "<input type='hidden' name='child_id' value='" . (isset($_POST['child_id']) ? $_POST['child_id'] : 0) . "' />"
            . "<input type='hidden' name='action' value='update' />"
            . "";
        if( isset($request['uri_split'][2]) && $request['uri_split'][2] == 'cartadd' ) {
            $content .= "<input type='hidden' name='next' value='" . $request['ssl_domain_base_url'] . "/cart' />";
        }
        $content .= "<div class='input first'>"
            . "<label for='first'>First Name" . (in_array('first', $required)?' *':'') . "</label>"
            . "<input type='text' class='text' name='first' value='" . (isset($child['first']) ? $child['first'] : '') . "'>"
            . "</div>";
        $content .= "<div class='input last'>" . "<label for='last'>Last Name" . (in_array('last', $required)?' *':'') . "</label>"
            . "<input type='text' class='text' name='last' value='" . (isset($child['last']) ? $child['last'] : '') . "'>"
            . "</div>";
        $content .= "<div class='submit'>"
            . "<input class='button' type='submit' name='submit' value='Cancel' />&nbsp;"
            . "<input class='button' type='submit' name='submit' value='Save' />"
            . "</div>";
        $content .= "</form>";

        $content .= "</div>";
        $content .= "</div>";
        $content .= "</div>";

        $blocks[] = array(
            'type' => 'html', 
            'html' => $content,
            );
    }
    
    //
    // Display the list of children
    //
    else {
        if( count($children) > 0 ) {
            foreach($children as $cid => $child) {
                $children[$cid]['editbutton'] = "<form action='{$base_url}' method='POST'>"
                    . "<input type='hidden' name='child_id' value='{$cid}' />"
                    . "<input type='hidden' name='action' value='edit' />"
                    . "<input class='button' type='submit' name='submit' value='Edit'>"
                    . "</form>";
            }
            $blocks[] = array(
                'type' => 'table', 
                'title' => 'Account Children',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Name', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => '', 'field' => 'editbutton', 'class' => 'alignright'),
                    ),
                'rows' => $children,
                );
        } else {
            $blocks[] = array(
                'type' => 'text',
                'content' => 'You have not added any children to your account.',
                );
        }

        $blocks[] = array(
            'type' => 'buttons', 
            'list' => array(array(
                'text' => 'Add Child',
                'url' => '/account/children?add=yes',
                )),
            ); 
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
