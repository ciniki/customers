<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_customers_hooks_uiSettings($ciniki, $tnid, $args) {

    $rsp = array('stat'=>'ok', 'settings'=>array(), 'menu_items'=>array(), 'settings_menu_items'=>array());  

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'tnid', $tnid, 'ciniki.customers', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $rsp['settings'] = $rc['settings'];
    }


    //
    // Get the list of price points
    //
    if( isset($args['modules']['ciniki.customers']['flags']) && ($args['modules']['ciniki.customers']['flags']&0x1000) > 0 ) {
        $strsql = "SELECT id, sequence, name "
            . "FROM ciniki_customer_pricepoints "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'pricepoints', 'fname'=>'id', 'name'=>'pricepoint',
                'fields'=>array('id', 'sequence', 'name')),
            ));
        if( $rc['stat'] == 'ok' && isset($rc['pricepoints']) ) {
            $rsp['settings']['pricepoints'] = $rc['pricepoints'];
        }
    }

    //
    // Get the membership seasons
    //
    if( isset($args['modules']['ciniki.customers']['flags']) && ($args['modules']['ciniki.customers']['flags']&0x02000000) > 0 ) {
        $strsql = "SELECT id, name, if((flags&0x02)=2,'yes','no') AS open "
            . "FROM ciniki_customer_seasons "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x02) = 2 "
            . "ORDER BY start_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'seasons', 'fname'=>'id', 'name'=>'season',
                'fields'=>array('id', 'name', 'open')),
            ));
        if( $rc['stat'] == 'ok' && isset($rc['seasons']) ) {
            $rsp['settings']['seasons'] = $rc['seasons'];
        }
    }

    //
    // Check if IFB or customers flag is set, and if the user has permissions
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5600,
            'label'=>'Customers', 
            'edit'=>array('app'=>'ciniki.customers.ifb'),
            'add'=>array('app'=>'ciniki.customers.ifb', 'args'=>array('edit_id'=>0)),
            'search'=>array(
                'method'=>'ciniki.customers.searchQuick',
                'args'=>array(),
                'container'=>'customers',
                'cols'=>3,
                'headerValues'=>array('Customers', 'Type'),
                'cellValues'=>array(
                    '0' => 'd.display_name;',
                    '1' => 'd.status_text;',
                    ),
                'noData'=>'No customers found',
                'edit'=>array('method'=>'ciniki.customers.ifb', 'args'=>array('customer_id'=>'d.id;')),
                'submit'=>array('method'=>'ciniki.customers.ifb', 'args'=>array('search'=>'search_str')),
                ),
            );
        if( isset($rsp['settings']['ui-labels-customers']) && $rsp['settings']['ui-labels-customers'] != '' ) {
            $menu_item['label'] = $rsp['settings']['ui-labels-customers'];
            $menu_item['search']['noData'] = 'No ' . $rsp['settings']['ui-labels-customers'] . ' found';
        }
        $rsp['menu_items'][] = $menu_item;
        //
        // Setup the ui app override for ifb UI
        //
        $rsp['uiAppOverrides'] = array(
            'ciniki.customers.edit' => array('method'=>'ciniki.customers.ifb'),
            );
    } 
    elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x01) 
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
                    '0'=>'d.display_name + (d.parent_name != null && d.parent_name != "" ? " <span class=\'subdue\'>(" + d.parent_name + ")</span>" : "");',
                    '1'=>'d.status_text;',
                    ),
                'rowStyle'=>'if( M.curTenant.customers.settings[\'ui-colours-customer-status-\' + d.status] != null ) {'
                        . '\'background: \' + M.curTenant.customers.settings[\'ui-colours-customer-status-\' + d.status];'
                    . '}',
                'noData'=>'No customers found',
                'edit'=>array('method'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.id;')),
                'submit'=>array('method'=>'ciniki.customers.main', 'args'=>array('search'=>'search_str')),
                ),
            );
        if( isset($rsp['settings']['ui-labels-customers']) && $rsp['settings']['ui-labels-customers'] != '' ) {
            $menu_item['label'] = $rsp['settings']['ui-labels-customers'];
            $menu_item['search']['noData'] = 'No ' . $rsp['settings']['ui-labels-customers'] . ' found';
        }
        $rsp['menu_items'][] = $menu_item;
    } 


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
                    '0' => 'd.display_name + (d.parent_name != null && d.parent_name != "" ? " <span class=\'subdue\'>(" + d.parent_name + "</span>" : "");',
                    '1' => 'd.status_text;',
                    ),
                'rowStyle'=>'if( M.curTenant.customers.settings[\'ui-colours-customer-status-\' + d.status] != null ) {'
                        . '\'background: \' + M.curTenant.customers.settings[\'ui-colours-customer-status-\' + d.status];'
                    . '}',
                'noData'=>'No customers found',
                'edit'=>array('method'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.id;')),
                'submit'=>array('method'=>'ciniki.customers.main', 'args'=>array('search'=>'search_str')),
                ),
            );
        if( isset($rsp['settings']['ui-labels-customers']) && $rsp['settings']['ui-labels-customers'] != '' ) {
            $menu_item['label'] = $rsp['settings']['ui-labels-customers'];
            $menu_item['search']['noData'] = 'No ' . $rsp['settings']['ui-labels-customers'] . ' found';
        }
        $rsp['menu_items'][] = $menu_item;
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
            'add'=>array('app'=>'ciniki.customers.edit', 'args'=>array('customer_id'=>0, 'member'=>"'\"yes\"'")),
            'search'=>array(
                'method'=>'ciniki.customers.searchQuick',
                'args'=>array('member_status'=>10),
                'container'=>'customers',
                'cols'=>1,
                'cellValues'=>array(
                    '0'=>'d.display_name;',
                    ),
                'noData'=>'No customers found',
                'edit'=>array('method'=>'ciniki.customers.main', 'args'=>array('customer_id'=>'d.id;')),
                'submit'=>array('method'=>'ciniki.customers.main', 'args'=>array('search'=>'search_str', 'type'=>'"members"')),
                ),
            );
        if( isset($rsp['settings']['ui-labels-members']) && $rsp['settings']['ui-labels-members'] != '' ) {
            $menu_item['label'] = $rsp['settings']['ui-labels-members'];
            $menu_item['search']['noData'] = 'No ' . $rsp['settings']['ui-labels-members'] . ' found';
        }
        $rsp['menu_items'][] = $menu_item;

    } 

    if( isset($ciniki['tenant']['modules']['ciniki.customers']) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>5600, 'label'=>'Customers/Members', 'edit'=>array('app'=>'ciniki.customers.settings'));
    }

    return $rsp;
}
?>
