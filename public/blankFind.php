<?php
//
// Description
// -----------
// This method will return a list of customers for the tenant which have blank first or last name.
// 
// Returns
// -------
//
function ciniki_customers_blankFind($ciniki) {
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
    // Check access to tnid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.blankFind', 0);
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Search for any potential blank customers
    //
    $strsql = "SELECT id, first, middle, last, display_name, company "
        . "FROM ciniki_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (first = '' OR last = '') "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'first', 'middle', 'last', 'display_name', 'company'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.62', 'msg'=>'Unable to locate any blank customers', 'err'=>$rc['err']));
    }

    return $rc;
}
?>
