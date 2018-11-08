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
function ciniki_customers_web_accountProcessRequestContactInfo($ciniki, $settings, $tnid, $args) {


    $page = array(
        'title'=>'Contact Info',
        'container-class'=>'page-account-contactinfo',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );
    $page['breadcrumbs'][] = array('name'=>'Contact Info', 'url'=>$args['base_url'] . '/contactinfo');
    $base_url = $args['base_url'] . '/contactinfo';

    //
    // Double check the account is logged in, should never reach this spot
    //
    if( !isset($ciniki['session']['account']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.164', 'msg'=>'Not logged in'));
    }

    //
    // Check if individual account and go directly to contact form edit
    //
    if( $ciniki['session']['account']['type'] == 10 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountProcessRequestContactForm');
        $rc = ciniki_customers_web_accountProcessRequestContactForm($ciniki, $settings, $tnid, $args);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.337', 'msg'=>'Unable to process request', 'err'=>$rc['err']));
        }
        foreach($rc['blocks'] as $block) {
            $page['blocks'][] = $blocks;
        }
    }
    elseif( ($ciniki['session']['account']['type'] == 20 || $ciniki['session']['account']['type'] == 30) 
        && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'add' 
        ) {
        $args['form_url'] = $base_url . '/' . $ciniki['request']['uri_split'][0];
        $args['customer_id'] = 0;
        if( $ciniki['session']['account']['type'] == 20 ) { 
            if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'parent' ) {
                $args['type'] = 21;
            } else {
                $args['type'] = 22;
            }
        } elseif( $ciniki['session']['account']['type'] == 30 ) { 
            if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'admin' ) {
                $args['type'] = 31;
            } else {
                $args['type'] = 32;
            }
        }
            
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'accountProcessRequestContactForm');
        $rc = ciniki_customers_web_accountProcessRequestContactForm($ciniki, $settings, $tnid, $args);
        if( $rc['stat'] == 'updated' ) {
            Header("Location: " . $base_url);
            exit;
        }
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.337', 'msg'=>'Unable to process request', 'err'=>$rc['err']));
        }
        foreach($rc['blocks'] as $block) {
            $page['blocks'][] = $block;
        }
    }
    elseif( ($ciniki['session']['account']['type'] == 20 || $ciniki['session']['account']['type'] == 30) 
        && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' 
        ) {
        //
        // Find the account
        //
        $customer_id = 0;
        $customer_name = '';
        foreach($ciniki['session']['account']['parents'] as $parent) {
            if( $parent['uuid'] == $ciniki['request']['uri_split'][0] ) {
                $customer_type = 'parent';
                $customer_id = $parent['id'];
                $customer_name = $parent['display_name'];
                break;
            }
        }
        foreach($ciniki['session']['account']['children'] as $child) {
            if( $child['uuid'] == $ciniki['request']['uri_split'][0] ) {
                $customer_type = 'child';
                $customer_id = $child['id'];
                $customer_name = $child['display_name'];
                break;
            }
        }
        if( $customer_id == 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.164', 'msg'=>'Invalid account'));
        }
        $args['customer_id'] = $customer_id;
        //
        // Check if remove requested
        //
        if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'remove' 
            && isset($_POST['action']) && $_POST['action'] == 'remove'
            && isset($_POST['confirm']) && $_POST['confirm'] == 'yes'
            ) {
//            $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($ciniki['session'], true) . "</pre>");
            foreach(['parents', 'children'] as $type) {
                foreach($ciniki['session']['account'][$type] as $cid => $child) {
                    if( $child['uuid'] == $ciniki['request']['uri_split'][0] ) {
                        if( $child['id'] == $ciniki['session']['customer']['id'] ) {
                            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>"You cannot remove yourself.");
                        } else {
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $customer_id, array('type'=>10, 'parent_id'=>0), 0x07);
                            if( $rc['stat'] != 'ok' ) {
                                $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>"We were unable to remove {$customer_name}. Please try again or contact us for help.");
                            } else {
                                unset($_SESSION['account'][$type][$cid]);
                                unset($ciniki['session']['account'][$type][$cid]);
                                $page['blocks'][] = array('type'=>'formmessage', 'level'=>'', 'message'=>"{$customer_name} was removed.");
                                $page['blocks'][] = array('type'=>'content', 'html'=>"<form><div class='submit'><a class='submit button' href='{$base_url}'>Continue</a></div></form>");
                            }
                        } 
                        break;
                    }
                }
            }
        } else if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'remove' ) {
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
                Header("Location: " . $base_url);
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
            if( count($ciniki['session']['account']['children']) > 0 ) {
                $page['blocks'][] = array('type'=>'table',
                    'title' => 'Children',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Name', 'field' => 'display_name'),
                        array('label' => '<a href="' . $base_url . '/add">Add</a>', 'strsub' => '<a href="' . $base_url . '/{_uuid_}">Edit</a><a href="' . $base_url . '/{_uuid_}/remove">Remove</a>'),
                    ),
                    'rows' => $ciniki['session']['account']['children'],
                );
            }

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
            if( count($ciniki['session']['account']['children']) > 0 ) {
                $page['blocks'][] = array('type'=>'table',
                    'title' => 'Employees',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Name', 'field' => 'display_name'),
                        array('label' => '<a href="' . $base_url . '/add">Add</a>', 
                            'strsub' => '<a href="' . $base_url . '/{_uuid_}">Edit</a><a href="' . $base_url . '/{_uuid_}/remove">Remove</a>'),
                    ),
                    'rows' => $ciniki['session']['account']['children'],
                );
            }
        }
    } else {
        $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Invalid request, please try again or contact us for help.');
    }


    return array('stat'=>'ok', 'page'=>$page);
}
?>
