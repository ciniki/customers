<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// key:                 The detail key to get the history for.
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_customers_customerHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.customerHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'birthdate' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.customers', 'ciniki_customer_history',
            $args['tnid'], 'ciniki_customers', $args['customer_id'], $args['field'], 'date');
    }
    if( $args['field'] == 'primary_email' || $args['field'] == 'secondary_email' ) {
        $strsql = "SELECT DISTINCT h1.table_key "
            . "FROM ciniki_customer_history AS h1 "
/*            . "INNER JOIN ciniki_customer_history AS h2 ON ("
                . "h1.table_name = h2.table_name "
                . "AND h1.table_key = h2.table_key "
                . "AND h2.table_field = 'email' "
                . "AND h2.new_value = '" . ciniki_core_dbQuote($ciniki, 'email') . "' "
                . ") " */
            . "WHERE h1.table_name = 'ciniki_customer_emails' "
            . "AND h1.table_field = 'customer_id' "
            . "AND h1.new_value = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND h1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.customers', 'keys', 'table_key');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.319', 'msg'=>'Unable to get history', 'err'=>$rc['err']));
        }
        if( isset($rc['keys']) && count($rc['keys']) > 0 ) {
            error_log(print_r($rc, true));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
            return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'], 'ciniki_customer_emails', $rc['keys'], 'email');
        }
        
    }
    if( $args['field'] == 'phone_home' 
        || $args['field'] == 'phone_work' 
        || $args['field'] == 'phone_cell' 
        || $args['field'] == 'phone_fax' 
        ) {
        $new_value = '';
        switch($args['field']) {
            case 'phone_home': $new_value = 'Home'; break;
            case 'phone_work': $new_value = 'Work'; break;
            case 'phone_cell': $new_value = 'Cell'; break;
            case 'phone_fax': $new_value = 'Fax'; break;
        }

        $strsql = "SELECT DISTINCT h2.table_key "
            . "FROM ciniki_customer_history AS h1 "
            . "INNER JOIN ciniki_customer_history AS h2 ON ("
                . "h1.table_name = h2.table_name "
                . "AND h1.table_key = h2.table_key "
                . "AND h2.table_field = 'phone_label' "
                . "AND h2.new_value = '" . ciniki_core_dbQuote($ciniki, $new_value) . "' "
                . ") "
            . "WHERE h1.table_name = 'ciniki_customer_phones' "
            . "AND h1.table_field = 'customer_id' "
            . "AND h1.new_value = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND h1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.customers', 'keys', 'table_key');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.312', 'msg'=>'Unable to get history', 'err'=>$rc['err']));
        }
        if( isset($rc['keys']) && count($rc['keys']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
            return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'], 'ciniki_customer_phones', $rc['keys'], 'phone_number');
        }
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'], 'ciniki_customers', $args['customer_id'], $args['field']);
}
?>
