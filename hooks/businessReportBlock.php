<?php
//
// Description
// -----------
// This function will return the report details for a requested report block.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_customers_hooks_businessReportBlock(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.customers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.221', 'msg'=>"That report is not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($args['block_ref']) || !isset($args['options']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.222', 'msg'=>"No block specified."));
    }

    //
    // The array to store the report data
    //

    //
    // Return the list of reports for the business
    //
    if( $args['block_ref'] == 'ciniki.customers.birthdays' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'reportUpcomingBirthdays');
        return ciniki_customers_reportUpcomingBirthdays($ciniki, $business_id, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.customers.newcustomers' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'reportNewCustomers');
        return ciniki_customers_reportNewCustomers($ciniki, $business_id, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.customers.newmembers' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'reportNewMembers');
        return ciniki_customers_reportNewMembers($ciniki, $business_id, $args['options']);
    }

    return array('stat'=>'ok');
}
?>
