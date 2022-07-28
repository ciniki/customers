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
    $display = 'list';
    if( isset($_GET['add']) && $_GET['add'] == 'yes' ) {
        $display = 'form';
    }
    $errors = 'no';
    $error_msg = '';

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
    $children = isset($rc['children']) ? $rc['children'] : array();

    //
    // Remove leading f- from next field
    //
    if( isset($_POST['f-next']) ) {
        $_POST['next'] = $_POST['f-next'];
    }
    //
    // Check for delete confirmation
    //
    if( isset($_GET['delete']) && $_GET['delete'] > 0 ) {
        $_POST['f-child_id'] = $_GET['delete'];
        $_POST['f-action'] = 'edit';
        $_POST['delete'] = 'Delete';
    }
    
    //
    // Trim fields
    //
    if( isset($_POST['f-first']) ) {
        $_POST['f-first'] = trim($_POST['f-first']);
    }
    if( isset($_POST['f-last']) ) {
        $_POST['f-last'] = trim($_POST['f-last']);
    }
    //
    // Check if next should go back to cart
    //
    if( isset($_GET['next']) && $_GET['next'] == 'regreview' ) {
        $_POST['next'] = $request['ssl_domain_base_url'] . '/cart?regreview';
    } elseif( isset($_GET['next']) && $_GET['next'] == 'cart' ) {
        $_POST['next'] = $request['ssl_domain_base_url'] . '/cart';
    }

    if( isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel' && isset($_POST['next']) && $_POST['next'] != '' ) {
        header("Location: " . $_POST['next']);
        return array('stat'=>'exit');
    } elseif( isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel' ) {
        header("Location: " . $request['base_url'] . '/account/children');
        return array('stat'=>'exit');
    }

    if( isset($_POST['f-child_id']) && $_POST['f-child_id'] > 0 && !isset($children[$_POST['f-child_id']]) ) {
        // Invalid child
        error_log('BAD REQUEST: account child update for: ' . $_POST['f-child_id']);
        unset($_POST['f-child_id']);
        $display = 'list';
    }

    //
    // Get the user if specified
    //
    if( isset($_POST['f-child_id']) && $_POST['f-child_id'] > 0 ) {
        $strsql = "SELECT id, type, display_name, sort_name, permalink, prefix, first, middle, last, suffix, company "
            . "FROM ciniki_customers "
            . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $_POST['f-child_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'child');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.176', 'msg'=>'We ran into a problem, please try again or contact us for help.'));
        }
        if( isset($rc['child']) ) {
            $child = $rc['child'];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.488', 'msg'=>'We had a problem, please try again or contact us for help.'));
        }
        $display = 'form';
    }

    //
    // Check if delete child
    //
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'edit' 
        && isset($_POST['f-child_id']) && $_POST['f-child_id'] > 0 
        && isset($_POST['delete']) 
        && isset($child)
        ) {
        //
        // Check if child account used for any registrations
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
        $rc = ciniki_core_objectCheckUsed($ciniki, $tnid, 'ciniki.customers.customer', $_POST['f-child_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.546', 'msg'=>'Unable to check child usage', 'err'=>$rc['err']));
        }
        if( $rc['used'] == 'yes' ) {
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error', 
                'content' => 'Registrations exist for ' . $child['first'] . ' ' . $child['last'] . ' and they cannot be removed.',
                );
        } elseif( isset($_GET['confirm']) && $_GET['confirm'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDelete');
            $rc = ciniki_customers_customerDelete($ciniki, $tnid, $_POST['f-child_id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.547', 'msg'=>'Unable to delete child', 'err'=>$rc['err']));
            }

            if( isset($_POST['next']) && $_POST['next'] != '' ) {
                header("Location: " . $_POST['next']);
                return array('stat'=>'exit');
            } else {
                header("Location: " . $request['base_url'] . '/account/children');
                return array('stat'=>'exit');
            }
            
        } else {
            $blocks[] = array(
                'type' => 'msg', 
                'class' => 'limit-width limit-width-40',
                'level' => 'warning', 
                'content' => 'Please confirm you wish to remove the child account ' . $child['first'] . ' ' . $child['last'],
                );
            $blocks[] = array(
                'type' => 'buttons', 
                'class' => 'limit-width limit-width-40 alignright',
                'list' => array(
                    array(
                        'text' => 'Cancel',
                        'url' => '/account/children',
                        ),
                    array(
                        'text' => 'Remove Child',
                        'url' => '/account/children?delete=' . $_POST['f-child_id'] . '&confirm=yes',
                        ),
                    ),
                ); 
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }

        $display = 'list';
    }
    //
    // Check if update to child
    //
    elseif( isset($_POST['f-action']) && $_POST['f-action'] == 'update' 
        && isset($_POST['f-child_id']) && $_POST['f-child_id'] > 0 
        && isset($_POST['submit']) 
        && isset($child)
        ) {
        $child_args = array();
        if( isset($_POST['f-first']) && $_POST['f-first'] != $child['first'] ) {
            if( $_POST['f-first'] == '' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg != '' ? "\n" : '') . "You must specify a first name.";
                $display = 'form';
            } else {
                $child_args['first'] = $_POST['f-first'];
                $child['first'] = $_POST['f-first'];
                $children[$_POST['f-child_id']]['first'] = $_POST['f-first'];
            }
        }
        if( isset($_POST['f-last']) && $_POST['f-last'] != $child['last'] ) {
            if( $_POST['f-last'] == '' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg != '' ? "\n" : '') . "You must specify a last name.";
                $display = 'form';
            } else {
                $child_args['last'] = $_POST['f-last'];
                $child['last'] = $_POST['f-last'];
                $children[$_POST['f-child_id']]['last'] = $_POST['f-last'];
            }
        }
        if( $errors == 'no' && count($child_args) > 0 ) {
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
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $_POST['f-child_id'], $child_args);
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update.";
                $display = 'form';
            } else {
                $updated = 'yes';
                $display = 'list';
            }
        } elseif( $errors == 'no' ) {
            $display = 'list';
        }
        if( $errors == 'no' ) {
            if( isset($_POST['next']) && $_POST['next'] != '' ) {
                header("Location: " . $_POST['next']);
                return array('stat'=>'exit');
            } else {
                header("Location: " . $request['base_url'] . '/account/children');
                return array('stat'=>'exit');
            }
        }
        
    } elseif( isset($_POST['f-action']) && $_POST['f-action'] == 'add' 
        && isset($_POST['f-child_id']) && $_POST['f-child_id'] == 0 
        && isset($_POST['submit']) 
        ) {
        $child = array('first'=>$_POST['f-first'], 'last'=>$_POST['f-last']);
        if( (!isset($_POST['f-first']) || trim($_POST['f-first']) == '') || (!isset($_POST['f-last']) || trim($_POST['f-last']) == '') ) {
            $errors = 'yes';
            $error_msg .= "You must specify a first and last name.";
            $display = 'form';
        } else {
            $child_args = array('id'=>'', 'type'=>1, 'display_name'=>'', 'first'=>'', 'last'=>'', 'parent_id'=>$request['session']['customer']['id']);
            $child_args['first'] = $_POST['f-first'];
            $child_args['last'] = $_POST['f-last'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerAdd');
            $rc = ciniki_customers_web_customerAdd($ciniki, $tnid, $child_args);
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                $error_msg .= ($error_msg!=''?"\n":'') . "Unable to update.";
                $display = 'form';
            } else {
                $updated = 'yes';
                $display = 'list';
            }
        }

        if( $errors == 'no' && isset($_POST['next']) && $_POST['next'] != '' ) {
            header("Location: " . $_POST['next']);
            return array('stat'=>'exit');
        }
    }

    //
    // Check if form to be displayed
    //
    if( $display == 'form' ) {
        if( isset($error_msg) && $error_msg != '' ) {
            $blocks[] = array(
                'type' => 'msg', 
                'class' => 'limit-width limit-width-30',
                'level' => 'error', 
                'content' => $error_msg,
                );
        }
        $blocks[] = array(
            'type' => 'form',
            'title' => (isset($_POST['f-child_id']) && $_POST['f-child_id'] > 0 ? 'Update Child Name' : 'Add Child'),
            'class' => 'limit-width limit-width-30',
            'cancel-label' => 'Cancel',
            'fields' => array(
                'child_id' => array(
                    'id' => 'child_id',
                    'ftype' => 'hidden',
                    'value' => isset($_POST['f-child_id']) ? $_POST['f-child_id'] : 0,
                    ),
                'action' => array(
                    'id' => 'action',
                    'ftype' => 'hidden',
                    'value' => isset($_POST['f-child_id']) && $_POST['f-child_id'] > 0 ? 'update' : 'add',
                    ),
                'next' => array(
                    'id' => 'next',
                    'ftype' => 'hidden',
                    'value' => isset($_POST['next']) ? $_POST['next'] : '',
                    ),
                'first' => array(
                    'id' => 'first',
                    'label' => 'First Name', 
                    'ftype' => 'text',
                    'size' => 'large',
                    'required' => 'yes', 
                    'value' => isset($child['first']) ? $child['first'] : '',
                    ),
                'last' => array(
                    'id' => 'last',
                    'label' => 'Last Name', 
                    'ftype' => 'text',
                    'size' => 'large',
                    'required' => 'yes', 
                    'value' => isset($child['last']) ? $child['last'] : '',
                    ),
                ),
            );
    }
    
    //
    // Display the list of children
    //
    else {
        if( count($children) > 0 ) {
            foreach($children as $cid => $child) {
                $children[$cid]['editbutton'] = "<form action='{$base_url}' method='POST'>"
                    . "<input type='hidden' name='f-child_id' value='{$cid}' />"
                    . "<input type='hidden' name='f-action' value='edit' />"
                    . "<input class='button' type='submit' name='submit' value='Edit'>"
                    . "<input class='button' type='submit' name='delete' value='Remove'>"
                    . "</form>";
            }
            $blocks[] = array(
                'type' => 'table', 
                'title' => 'Account Children',
                'class' => 'limit-width limit-width-40',
                'headers' => 'no',
                'columns' => array(
                    array('label' => 'Name', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => '', 'field' => 'editbutton', 'class' => 'buttons alignright'),
                    ),
                'rows' => $children,
                );
        } else {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-40',
                'content' => 'You have not added any children to your account.',
                );
        }

        $blocks[] = array(
            'type' => 'buttons', 
            'class' => 'limit-width limit-width-40 alignright',
            'list' => array(array(
                'text' => 'Add Child',
                'url' => '/account/children?add=yes',
                )),
            ); 
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
