<?php
//
// Description
// -----------
// Return the list of available field refs for ciniki.forms module.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_hooks_formFieldRefs(&$ciniki, $tnid, $args) {
    
    $module = 'Customers';
    $refs = array(
        'ciniki.customers.customer.first' => array('module'=>$module, 'type'=>'text', 'name'=>'First Name'),
        'ciniki.customers.customer.middle' => array('module'=>$module, 'type'=>'text', 'name'=>'Middle Name'),
        'ciniki.customers.customer.last' => array('module'=>$module, 'type'=>'text', 'name'=>'Last Name'),
        'ciniki.customers.customer.company' => array('module'=>$module, 'type'=>'text', 'name'=>'Business'),
        'ciniki.customers.customer.phone_cell' => array('module'=>$module, 'type'=>'phone', 'name'=>'Cell Phone'),
        'ciniki.customers.customer.phone_home' => array('module'=>$module, 'type'=>'phone', 'name'=>'Home Phone'),
        'ciniki.customers.customer.phone_work' => array('module'=>$module, 'type'=>'phone', 'name'=>'Work Phone'),
        'ciniki.customers.customer.phone_fax' => array('module'=>$module, 'type'=>'phone', 'name'=>'Fax'),
        'ciniki.customers.customer.primary_email' => array('module'=>$module, 'type'=>'email', 'name'=>'Primary Email'),
        'ciniki.customers.customer.secondary_email' => array('module'=>$module, 'type'=>'email', 'name'=>'Secondary Email'),
        'ciniki.customers.customer.mailing_address' => array('module'=>$module, 'type'=>'address', 'name'=>'Mailing Address'),
        'ciniki.customers.customer.shipping_address' => array('module'=>$module, 'type'=>'address', 'name'=>'Shipping Address'),

/*        'ciniki.customers.customer.mailing_address1' => array('module'=>$module, 'type'=>'text', 'name'=>'Mailing Address Line 1'),
        'ciniki.customers.customer.mailing_address2' => array('module'=>$module, 'type'=>'text', 'name'=>'Mailing Address Line 2'),
        'ciniki.customers.customer.mailing_city' => array('module'=>$module, 'type'=>'text', 'name'=>'Mailing City'),
        'ciniki.customers.customer.mailing_province' => array('module'=>$module, 'type'=>'text', 'name'=>'Mailing Province'),
        'ciniki.customers.customer.mailing_postal' => array('module'=>$module, 'type'=>'text', 'name'=>'Mailing Postal'),
        'ciniki.customers.customer.mailing_country' => array('module'=>$module, 'type'=>'text', 'name'=>'Mailing Country'),
        'ciniki.customers.customer.shipping_address1' => array('module'=>$module, 'type'=>'text', 'name'=>'Shipping Address Line 1'),
        'ciniki.customers.customer.shipping_address2' => array('module'=>$module, 'type'=>'text', 'name'=>'Shipping Address Line 2'),
        'ciniki.customers.customer.shipping_city' => array('module'=>$module, 'type'=>'text', 'name'=>'Shipping City'),
        'ciniki.customers.customer.shipping_province' => array('module'=>$module, 'type'=>'text', 'name'=>'Shipping Province'),
        'ciniki.customers.customer.shipping_postal' => array('module'=>$module, 'type'=>'text', 'name'=>'Shipping Postal'),
        'ciniki.customers.customer.shipping_country' => array('module'=>$module, 'type'=>'text', 'name'=>'Shipping Country'),
        */
        'ciniki.customers.customer.website' => array('module'=>$module, 'type'=>'url', 'name'=>'Website'),
        'ciniki.customers.customer.facebook' => array('module'=>$module, 'type'=>'url', 'name'=>'Facebook'),
        'ciniki.customers.customer.instagram' => array('module'=>$module, 'type'=>'url', 'name'=>'Instagram'),
        'ciniki.customers.customer.twitter' => array('module'=>$module, 'type'=>'url', 'name'=>'Twitter'),
        );

    return array('stat'=>'ok', 'refs'=>$refs);
}
?>
