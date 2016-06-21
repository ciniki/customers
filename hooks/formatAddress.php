<?php
//
// Description
// -----------
// This function formats the address.
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_hooks_formatAddress($ciniki, $business_id, $args) {

    $label = '';
    $flags = $args['address']['flags'];
    $comma = '';
    if( ($flags&0x01) == 0x01 ) { $label .= $comma . 'Shipping'; $comma = ', ';}
    if( ($flags&0x02) == 0x02 ) { $label .= $comma . 'Billing'; $comma = ', ';}
    if( ($flags&0x04) == 0x04 ) { $label .= $comma . 'Mailing'; $comma = ', ';}
    if( ($flags&0x08) == 0x08 ) { $label .= $comma . 'Public'; $comma = ', ';}
    if( $label == '' ) { 
        $label = 'Address'; 
    }
    $joined_address = '';
    if( isset($args['address']['address1']) && $args['address']['address1'] != '' ) {
        $joined_address .= $args['address']['address1'] . "\n";
    }
    if( isset($args['address']['address2']) && $args['address']['address2'] != '' ) {
        $joined_address .= $args['address']['address2'] . "\n";
    }
    $city = '';
    $comma = '';
    if( isset($args['address']['city']) && $args['address']['city'] != '' ) {
        $city = $args['address']['city'];
        $comma = ', ';
    }
    if( isset($args['address']['province']) && $args['address']['province'] != '' ) {
        $city .= $comma . $args['address']['province'];
        $comma = ', ';
    }
    if( isset($args['address']['postal']) && $args['address']['postal'] != '' ) {
        $city .= $comma . ' ' . $args['address']['postal'];
        $comma = ', ';
    }
    if( $city != '' ) {
        $joined_address .= $city;
    }
    if( isset($args['address']['country']) && $args['address']['country'] != '' ) {
        $joined_address .= "\n" . $args['address']['country'];
    }

    return array('stat'=>'ok', 'label'=>$label, 'address'=>$joined_address);
}
?>
