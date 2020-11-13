<?php
//
// Description
// -----------
// This function will return the report details for a requested report block.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_customers_reporting_block(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.221', 'msg'=>"That report is not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($args['block_ref']) || !isset($args['options']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.224', 'msg'=>"No block specified."));
    }

    //
    // The array to store the report data
    //

    //
    // Return the list of reports for the tenant
    //
    if( $args['block_ref'] == 'ciniki.customers.birthdays' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'reporting', 'blockBirthdays');
        return ciniki_customers_reporting_blockBirthdays($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.customers.newcustomers' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'reporting', 'blockNewCustomers');
        return ciniki_customers_reporting_blockNewCustomers($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.customers.newmembers' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'reporting', 'blockNewMembers');
        return ciniki_customers_reporting_blockNewMembers($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.customers.renewedmembers' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'reporting', 'blockRenewedMembers');
        return ciniki_customers_reporting_blockRenewedMembers($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.customers.expiringmembers' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'reporting', 'blockExpMembers');
        $args['options']['direction'] = 'future';
        return ciniki_customers_reporting_blockExpMembers($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.customers.expiredmembers' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'reporting', 'blockExpMembers');
        $args['options']['direction'] = 'past';
        return ciniki_customers_reporting_blockExpMembers($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.customers.reminders' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'reporting', 'blockReminders');
        return ciniki_customers_reporting_blockReminders($ciniki, $tnid, $args['options']);
    }

    return array('stat'=>'ok');
}
?>
