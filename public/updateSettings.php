<?php
//
// Description
// -----------
// This method will update one or more settings for the customers module.
//
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_updateSettings(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.updateSettings', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the current settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // The list of allowed fields for updating
    //
    $db_updated = 0;
    $changelog_fields = array(
        'display-name-tenant-format',
        'defaults-edit-form',
        'defaults-edit-person-hide-company',
        'membership-type-10-active',
        'membership-type-10-online',
        'membership-type-10-name',
        'membership-type-10-price',
        'membership-type-20-active',
        'membership-type-20-online',
        'membership-type-20-name',
        'membership-type-20-price',
        'membership-type-30-active',
        'membership-type-30-online',
        'membership-type-30-name',
        'membership-type-30-price',
        'membership-type-40-active',
        'membership-type-40-online',
        'membership-type-40-name',
        'membership-type-40-price',
        'membership-type-110-active',
        'membership-type-150-active',
        'membership-type-lifetime-active',
        'membership-type-lifetime-online',
        'membership-type-lifetime-name',
        'membership-type-lifetime-price',
        'ui-labels-parent',
        'ui-labels-parents',
        'ui-labels-child',
        'ui-labels-children',
        'ui-labels-customer',
        'ui-labels-customers',
        'ui-labels-member',
        'ui-labels-members',
        'ui-labels-dealer',
        'ui-labels-dealers',
        'ui-labels-distributor',
        'ui-labels-distributors',
        'ui-colours-customer-status-10',
        'ui-colours-customer-status-40',
        'ui-colours-customer-status-50',
        'ui-colours-customer-status-60',
        'dropbox-customerprofiles',
//      'use-cid',
//      'use-relationships',
//      'use-tax-number',
//      'use-tax-location-id',
//      'use-birthdate',
        );
    //
    // Check each valid setting and see if a new value was passed in the arguments for it.
    // Insert or update the entry in the ciniki_customer_settings table
    //
    foreach($changelog_fields as $field) {
        if( isset($ciniki['request']['args'][$field]) ) {
            $strsql = "INSERT INTO ciniki_customer_settings (tnid, detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $field) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
                . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'], 
                2, 'ciniki_customer_settings', $field, 'detail_value', $ciniki['request']['args'][$field]);
            $db_updated = 1;
            $ciniki['syncqueue'][] = array('push'=>'ciniki.customers.setting', 
                'args'=>array('id'=>$field));
        }
    }

    //
    // Check if changing 'display-name-tenant-format' and update display_name in database
    //
    if( isset($ciniki['request']['args']['display-name-tenant-format']) 
        && (!isset($settings['display-name-tenant-format']) 
        || $settings['display-name-tenant-format'] != $ciniki['request']['args']['display-name-tenant-format'])
    ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $format = $ciniki['request']['args']['display-name-tenant-format'];
        $strsql = "SELECT id, uuid, display_name, display_name_format, company, "
            . "REPLACE(TRIM(CONCAT_WS(' ', prefix, first, middle, last, suffix)),'  ', ' ') AS person_name, "
            . "TRIM(CONCAT_WS(', ', last, first)) AS sort_person_name "
            . "FROM ciniki_customers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND display_name_format = '' "   // Only select customer that don't have override set
            . "AND type = 2 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.153', 'msg'=>'Unable to update settings for tenant format', 'err'=>$rc['err']));
        
        }
        
        $customers = $rc['rows'];
        if( isset($rc['rows']) ) {
            $customers = $rc['rows'];
            foreach($customers AS $cid => $customer) {
                if( $format == 'company - person' ) {
                    $display_name = $customer['company'] . ' - ' . $customer['person_name'];
                    $sort_name = $customer['company'];
                } 
                elseif( $format == 'person - company' ) {
                    $display_name = $customer['person_name'] . ' - ' . $customer['company'];
                    $sort_name = $customer['sort_person_name'] . $customer['company'];
                } 
                elseif( $format == 'company [person]' ) {
                    $display_name = $customer['company'] . ' [' . $customer['person_name'] . ']';
                    $sort_name = $customer['company'];
                } 
                elseif( $format == 'person [company]' ) {
                    $display_name = $customer['person_name'] . ' [' . $customer['company'] . ']';
                    $sort_name = $customer['sort_person_name'] . $customer['company'];
                } 
                else {
                    $display_name = $customer['company'];
                    $sort_name = $customer['company'];
                }
                $customer_args = array('display_name'=>$display_name, 'sort_name'=>$sort_name);
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.customer', 
                    $customer['id'], $customer_args, 0x06);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    if( $db_updated > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'customers');
    }

    return array('stat'=>'ok');
}
?>
