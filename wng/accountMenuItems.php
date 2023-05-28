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
function ciniki_customers_wng_accountMenuItems($ciniki, $tnid, $request, $args) {

    $items = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';

    //
    // Setup different sub menu when accounts flag is set
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) ) {
/*        if( isset($ciniki['session']['account']['type']) && $ciniki['session']['account']['type'] == 10 ) {
            $submenu[] = array('name'=>'Contact Info', 'priority'=>250, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'contactinfo')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/contactinfo');
        } elseif( isset($ciniki['session']['account']['type']) && $ciniki['session']['account']['type'] == 20 ) {
            $submenu[] = array('name'=>'Children', 'priority'=>250, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'contactinfo')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/contactinfo');
        } elseif( isset($ciniki['session']['account']['type']) && $ciniki['session']['account']['type'] == 30 ) {
            $submenu[] = array('name'=>'Employees', 'priority'=>250, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'contactinfo')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/contactinfo');
        }
        if( !isset($settings['page-account-password-change']) || $settings['page-account-password-change'] == 'yes' ) {
            $submenu[] = array('name'=>'Change Password', 'priority'=>250, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'changepassword')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/changepassword');
        } */
    } else {

/*        if( isset($settings['page-account-registrations-update']) && $settings['page-account-registrations-update'] == 'yes' ) {
            $submenu[] = array('name'=>'Registrations', 'priority'=>300, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'registrations')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/registrations');
        }

        if( isset($ciniki['session']['customers']) && count($ciniki['session']['customers']) > 1 
            ) {
            $submenu[] = array('name'=>'Switch Account', 'priority'=>310, 'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'accounts')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/accounts');
        }

        if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' 
            && isset($settings['page-account-email-update']) && $settings['page-account-email-update'] == 'yes' 
            && isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' 
            ) {
            $submenu[] = array('name'=>'Contact Details', 'priority'=>300, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'contactdetails')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/contactdetails');
        }

        if( isset($settings['page-account-children-update']) && $settings['page-account-children-update'] == 'yes' 
            && isset($ciniki['session']['customer']['children-allowed']) && $ciniki['session']['customer']['children-allowed'] == 'yes'
            ) {
            $submenu[] = array('name'=>'Children', 'priority'=>300, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'children')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/children');
        }

        if( !isset($settings['page-account-password-change']) || $settings['page-account-password-change'] == 'yes' ) {
            $submenu[] = array('name'=>'Change Password', 'priority'=>250, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'account' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'changepassword')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/changepassword');
        }

        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08)
            && (!isset($settings['page-account-membership-update']) || $settings['page-account-membership-update'] == 'yes')
            ) {
            $submenu[] = array('name'=>'Membership', 'priority'=>220, 
                'package'=>'ciniki', 'module'=>'customers', 
                'selected'=>($ciniki['request']['page'] == 'membership' 
                    && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'membership')?'yes':'no',
                'url'=>$ciniki['request']['base_url'] . '/account/membership');
        }

        $submenu[] = array('name'=>'Logout', 'priority'=>100, 
            'package'=>'ciniki', 'module'=>'customers', 
            'selected'=>'no',
            'url'=>$ciniki['request']['base_url'] . '/account/logout');

        */


    }



    //
    // FIXME: Add items for editing contact details, mailing lists, past orders, children
    //
    $options = array();
    $options[] = array(
        'title' => 'Contact Info', 
        'priority' => 350, 
        'ref' => 'ciniki.customers.contact',
        'selected' => isset($args['selected']) && $args['selected'] == 'contact' ? 'yes' : 'no',
        'url' => $base_url . '/contact',
        );
    if( isset($settings['account-children-update']) && $settings['account-children-update'] == 'yes' 
        && isset($request['session']['customer']['children-allowed']) && $request['session']['customer']['children-allowed'] == 'yes'
        ) {
        $options[] = array(
            'title' => 'Children', 
            'priority' => 350, 
            'ref' => 'ciniki.customers.children',
            'selected' => isset($args['selected']) && $args['selected'] == 'children' ? 'yes' : 'no',
            'url' => $base_url . '/children',
            );
    }

    //
    // Rolling Membership Product Renewals
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08)
        && (!isset($settings['account-membership-change']) || $settings['account-membership-change'] == 'yes') 
        ) {
        $options[] = array(
            'title' => 'Membership', 
            'priority' => 300, 
            'ref' => 'ciniki.customers.membership',
            'selected' => isset($args['selected']) && $args['selected'] == 'membership' ? 'yes' : 'no',
            'url' => $base_url . '/membership',
            );
    }

    //
    // Change Password
    //
    if( isset($settings['account-password-change']) && $settings['account-password-change'] == 'yes' ) {
        $options[] = array(
            'title' => 'Change Password', 
            'priority' => 250, 
            'ref' => 'ciniki.customers.changepassword',
            'selected' => isset($args['selected']) && $args['selected'] == 'changepassword' ? 'yes' : 'no',
            'url' => $base_url . '/changepassword',
            );
    }

    if( count($options) <= 2 ) {
        foreach($options as $option) {
            $items[] = $option;
        }

    } else {
        $items[] = array(
            'title' => 'Profile',
            'priority' => 250,
            'ref' => 'ciniki.customers.profile',
            'selected' => isset($args['selected']) && $args['selected'] == 'profile' ? 'yes' : 'no',
            'url' => $base_url . '/profile',
            'items' => count($options) > 1 ? $options : null,
            );
    }

    //
    // Add Logout - This request is handled by the wng modules accountLogoutProcess directly
    //
    $items[] = array(
        'title' => 'Logout', 
        'priority' => 100, 
        'ref' => 'ciniki.customers.logout',
        'selected' => 'no',
        'url' => $base_url . '/logout',
        );

    return array('stat'=>'ok', 'items'=>$items);
}
?>
