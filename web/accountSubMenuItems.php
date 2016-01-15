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
function ciniki_customers_web_accountSubMenuItems($ciniki, $settings, $business_id) {

    $submenu = array();

    if( isset($ciniki['session']['customers']) && count($ciniki['session']['customers']) > 1 ) {
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

    $submenu[] = array('name'=>'Change Password', 'priority'=>250, 
        'package'=>'ciniki', 'module'=>'customers', 
        'selected'=>($ciniki['request']['page'] == 'account' 
            && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'changepassword')?'yes':'no',
        'url'=>$ciniki['request']['base_url'] . '/account/changepassword');

    $submenu[] = array('name'=>'Logout', 'priority'=>100, 
        'package'=>'ciniki', 'module'=>'customers', 
        'selected'=>'no',
        'url'=>$ciniki['request']['base_url'] . '/account/logout');

	return array('stat'=>'ok', 'submenu'=>$submenu);
}
?>
