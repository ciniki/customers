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
        $submenu[] = array('name'=>'Accounts', 'priority'=>310, 'package'=>'ciniki', 'module'=>'customers', 'url'=>$ciniki['request']['base_url'] . '/account/accounts');
    }

    if( isset($settings['page-account-phone-update']) && $settings['page-account-phone-update'] == 'yes' 
        && isset($settings['page-account-email-update']) && $settings['page-account-email-update'] == 'yes' 
        && isset($settings['page-account-address-update']) && $settings['page-account-address-update'] == 'yes' 
        ) {
        $submenu[] = array('name'=>'Contact Details', 'priority'=>300, 
            'package'=>'ciniki', 'module'=>'customers', 
            'url'=>$ciniki['request']['base_url'] . '/account/contactdetails');
    }

    $submenu[] = array('name'=>'Change Password', 'priority'=>250, 
        'package'=>'ciniki', 'module'=>'customers', 
        'url'=>$ciniki['request']['base_url'] . '/account/changepassword');

	return array('stat'=>'ok', 'submenu'=>$submenu);
}
?>
