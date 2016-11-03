<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get options for.
//
// args:            The possible arguments for profiles
//
//
// Returns
// -------
//
function ciniki_customers_hooks_webOptions(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.31', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'business_id', $business_id, 'ciniki.web', 'settings', 'page');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }

    $pages['ciniki.customers.dealers'] = array('name'=>'Dealers', 'options'=>array(
        array('label'=>'Expand Short Name',
            'setting'=>'page-dealers-locations-map-names', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-dealers-locations-map-names'])?$settings['page-dealers-locations-map-names']:'no'),
            'toggles'=>array(
                array('value'=>'no', 'label'=>'No'),
                array('value'=>'yes', 'label'=>'Yes'),
                ),
            ),
        array('label'=>'Display Dealer Locations',
            'setting'=>'page-dealers-locations-display', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-dealers-locations-display'])?$settings['page-dealers-locations-display']:'no'),
            'toggles'=>array(
                array('value'=>'no', 'label'=>'No'),
                array('value'=>'wordlist', 'label'=>'List'),
                array('value'=>'wordcloud', 'label'=>'Cloud'),
                ),
            ),
        array('label'=>'Listing Content',
            'setting'=>'page-dealers-list-format', 
            'type'=>'select',
            'value'=>(isset($settings['page-dealers-list-format'])?$settings['page-dealers-list-format']:'no'),
            'options'=>array(
                array('value'=>'shortbio', 'label'=>'Short Bio'),
                array('value'=>'shortbio-blank-addressesnl-phones-emails-links', 'label'=>'Short Bio, Addresses, Phones, Emails, Links'),
                array('value'=>'addressesnl-blank-shortbio-phones-emails-links', 'label'=>'Addresses, Short Bio, Phones, Emails, Links'),
                array('value'=>'shortbio-blank-addressesnl-phones-links', 'label'=>'Short Bio, Addresses, Phones, Links'),
                array('value'=>'addressesnl-phones-emails-links', 'label'=>'Short Bio, Addresses, Phones, Emails, Links'),
                ),
            ),
        ));

    $pages['ciniki.customers.distributors'] = array('name'=>'Distributors', 'options'=>array(
        array('label'=>'Expand Short Name',
            'setting'=>'page-distributors-locations-map-names', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-distributors-locations-map-names'])?$settings['page-distributors-locations-map-names']:'no'),
            'toggles'=>array(
                array('value'=>'no', 'label'=>'No'),
                array('value'=>'yes', 'label'=>'Yes'),
                ),
            ),
        array('label'=>'Display Dealer Locations',
            'setting'=>'page-distributors-locations-display', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-distributors-locations-display'])?$settings['page-distributors-locations-display']:'no'),
            'toggles'=>array(
                array('value'=>'no', 'label'=>'No'),
                array('value'=>'wordlist', 'label'=>'List'),
                array('value'=>'wordcloud', 'label'=>'Cloud'),
                ),
            ),
        array('label'=>'Listing Content',
            'setting'=>'page-distributors-list-format', 
            'type'=>'select',
            'value'=>(isset($settings['page-distributors-list-format'])?$settings['page-distributors-list-format']:'no'),
            'options'=>array(
                array('value'=>'shortbio', 'label'=>'Short Bio'),
                array('value'=>'shortbio-blank-addressesnl-phones-emails-links', 'label'=>'Short Bio, Addresses, Phones, Emails, Links'),
                array('value'=>'addressesnl-blank-shortbio-phones-emails-links', 'label'=>'Addresses, Short Bio, Phones, Emails, Links'),
                array('value'=>'shortbio-blank-addressesnl-phones-links', 'label'=>'Short Bio, Addresses, Phones, Links'),
                array('value'=>'addressesnl-phones-emails-links', 'label'=>'Short Bio, Addresses, Phones, Emails, Links'),
                ),
            ),
        ));

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
