<?php
//
// Description
// -----------
// Update members lastpaid based on membership purchases
//
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_phonesReformat(&$ciniki) {

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
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.phonesReformat');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the list of purchases and customer details
    //
    $strsql = "SELECT phones.id, "
        . "phones.phone_number "
        . "FROM ciniki_customer_phones AS phones "
        . "WHERE phones.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'phones', 'fname'=>'id', 'fields'=>array('id', 'phone_number')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.577', 'msg'=>'Unable to load customer phones', 'err'=>$rc['err']));
    }
    $phones = isset($rc['phones']) ? $rc['phones'] : array();

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'phoneFormat');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $num_good = 0;
    $num_reformat = 0;
    $num_bad = 0;
    foreach($phones as $phone) {
        $update_args = [];
        if( $phone['phone_number'] != '' ) {
            $rc = ciniki_tenants_hooks_phoneFormat($ciniki, $args['tnid'], ['number'=>$phone['phone_number']]);
            if( $rc['stat'] != 'ok' ) {
                print_r($rc);
                exit;
            }
            if( $rc['formatted_number'] != $phone['phone_number'] ) {
                $update_args['phone_number'] = $rc['formatted_number'];
                $num_reformat++;
            } elseif( !preg_match("/[0-9][0-9][0-9]-[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]/", $rc['formatted_number']) ) {
//                error_log("Invalid format: {$rc['formatted_number']}");
                $num_bad++;
            } else {
                $num_good++;
            }
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.customers.phone', $phone['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.578', 'msg'=>'Unable to update the phone', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'customers');

    return array('stat'=>'ok');
}
?>
