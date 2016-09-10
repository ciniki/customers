<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_customers_hooks_uiSettings($ciniki, $business_id, $args) {

    $settings = array();

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'business_id', 
        $business_id, 'ciniki.customers', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }


    //
    // Get the list of price points
    //
    if( isset($args['modules']['ciniki.customers']['flags']) && ($args['modules']['ciniki.customers']['flags']&0x1000) > 0 ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_customer_pricepoints "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "ORDER BY sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'pricepoints', 'fname'=>'id', 'name'=>'pricepoint',
                'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] == 'ok' && isset($rc['pricepoints']) ) {
            $settings['pricepoints'] = $rc['pricepoints'];
        }
    }

    //
    // Get the membership seasons
    //
    if( isset($args['modules']['ciniki.customers']['flags']) && ($args['modules']['ciniki.customers']['flags']&0x02000000) > 0 ) {
        $strsql = "SELECT id, name, if((flags&0x02)=2,'yes','no') AS open "
            . "FROM ciniki_customer_seasons "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (flags&0x02) = 2 "
            . "ORDER BY start_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'seasons', 'fname'=>'id', 'name'=>'season',
                'fields'=>array('id', 'name', 'open')),
            ));
        if( $rc['stat'] == 'ok' && isset($rc['seasons']) ) {
            $settings['seasons'] = $rc['seasons'];
        }
    }

    //
    // Setup the menu items
    //
    $menu = array();

    //
    // Check if customers flag is set, and if the user has permissions
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x01) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5600,
            'label'=>'Customers', 
            'edit'=>array('app'=>'ciniki.customers.main'),
            'add'=>array('app'=>'ciniki.customers.edit', 'args'=>array('customer_id'=>0)),
            'search'=>array(
                'method'=>'ciniki.customers.searchQuick',
                'args'=>array(),
                'container'=>'customers',
                'cols'=>2,
                'headerValues'=>array('Customers', 'Status'),
                'cellValues'=>array(
                    '0'=>'d.customer.display_name;',
                    '1'=>'d.customer.status_text;',
                    ),
                'noData'=>'No customers found',
                'edit'=>array('method'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.customer.id;')),
                'submit'=>array('method'=>'ciniki.customers.main', 'args'=>array('search'=>'search_str')),
                ),
            );
        if( isset($settings['ui-labels-customers']) && $settings['ui-labels-customers'] != '' ) {
            $menu_item['label'] = $settings['ui-labels-customers'];
            $menu_item['search']['noData'] = 'No ' . $settings['ui-labels-customers'] . ' found';
        }
        $menu[] = $menu_item;
    } 


    //
    // A salesrep only see's a reduced customer interface
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x01) 
        && !isset($args['permissions']['owners'])
        && !isset($args['permissions']['employees'])
        && !isset($args['permissions']['resellers'])
        && isset($args['permissions']['salesreps'])
        ) {
        $menu_item = array(
            'priority'=>5600,
            'label'=>'Customers', 
            'edit'=>array('app'=>'ciniki.customers.main'),
            'search'=>array(
                'method'=>'ciniki.customers.searchQuick',
                'args'=>array(),
                'container'=>'customers',
                'cols'=>2,
                'headerValues'=>array('Customers', 'Status'),
                'cellValues'=>array(
                    '0'=>'d.customer.display_name;',
                    '1'=>'d.customer.status_text;',
                    ),
                'noData'=>'No customers found',
                'edit'=>array('method'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.customer.id;')),
                'submit'=>array('method'=>'ciniki.customers.main', 'args'=>array('search'=>'search_str')),
                ),
            );
        if( isset($settings['ui-labels-customers']) && $settings['ui-labels-customers'] != '' ) {
            $menu_item['label'] = $settings['ui-labels-customers'];
            $menu_item['search']['noData'] = 'No ' . $settings['ui-labels-customers'] . ' found';
        }
        $menu[] = $menu_item;
    } 

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x02)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5590,
            'label'=>'Members', 
            'edit'=>array('app'=>'ciniki.customers.members'),
            'add'=>array('app'=>'ciniki.customers.edit', 'args'=>array('customer_id'=>0, 'member'=>'"yes"')),
            'search'=>array(
                'method'=>'ciniki.customers.searchQuick',
                'args'=>array('member_status'=>10),
                'container'=>'customers',
                'cols'=>1,
                'cellValues'=>array(
                    '0'=>'d.customer.display_name;',
                    ),
                'noData'=>'No customers found',
                'edit'=>array('method'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.customer.id;')),
                'submit'=>array('method'=>'ciniki.customers.main', 'args'=>array('search'=>'search_str', 'type'=>'"members"')),
                ),
            );
        if( isset($settings['ui-labels-members']) && $settings['ui-labels-members'] != '' ) {
            $menu_item['label'] = $settings['ui-labels-members'];
            $menu_item['search']['noData'] = 'No ' . $settings['ui-labels-members'] . ' found';
        }
        $menu[] = $menu_item;
    } 

    return array('stat'=>'ok', 'settings'=>$settings, 'menu_items'=>$menu);  
}
?>
