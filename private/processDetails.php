<?php
//
// Description
// -----------
// This function will process the details for a customer into a list to be used in the UI.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_processDetails($ciniki, $tnid, $customer, $args) {
   
    //
    // Build the details array
    //
    $details = array();
    $details[] = array('label'=>'Name', 'value'=>$customer['display_name']);
    if( isset($args['phones']) && $args['phones'] == 'yes' && isset($customer['phones']) ) {
        foreach($customer['phones'] as $phone) {
            $details[] = array('label'=>$phone['phone_label'], 'value'=>$phone['phone_number']);
        }
    }
    if( isset($args['emails']) && $args['emails'] == 'yes' && isset($customer['emails']) ) {
        $emails = '';
        $comma = '';
        foreach($customer['emails'] as $e => $email) {
            $emails .= $comma . $email['address'];
            $comma = ', ';
        }
        if( count($customer['emails']) > 1 ) {
            $details[] = array('label'=>'Emails', 'value'=>$emails);
        } elseif( $emails != '' ) {
            $details[] = array('label'=>'Email', 'value'=>$emails);
        }
    }
    if( isset($args['addresses']) && $args['addresses'] == 'yes' && isset($customer['addresses']) ) {
        foreach($customer['addresses'] as $a => $address) {
            $label = '';
            if( count($customer['addresses']) > 1 ) {
                $flags = $address['flags'];
                $comma = '';
                if( ($flags&0x01) == 0x01 ) { $label .= $comma . 'Shipping'; $comma = ', ';}
                if( ($flags&0x02) == 0x02 ) { $label .= $comma . 'Billing'; $comma = ', ';}
                if( ($flags&0x04) == 0x04 ) { $label .= $comma . 'Mailing'; $comma = ', ';}
                if( ($flags&0x08) == 0x08 ) { $label .= $comma . 'Public'; $comma = ', ';}
            }
            if( $label == '' ) {
                $label = 'Address';
            }
            $joined_address = '';
            if( isset($address['address1']) && $address['address1'] != '' ) {
                $joined_address .= $address['address1'] . "\n";
            }
            if( isset($address['address2']) && $address['address2'] != '' ) {
                $joined_address .= $address['address2'] . "\n";
            }
            $city = '';
            $comma = '';
            if( isset($address['city']) && $address['city'] != '' ) {
                $city = $address['city'];
                $comma = ', ';
            }
            if( isset($address['province']) && $address['province'] != '' ) {
                $city .= $comma . $address['province'];
                $comma = ', ';
            }
            if( isset($address['postal']) && $address['postal'] != '' ) {
                $city .= $comma . ' ' . $address['postal'];
                $comma = ', ';
            }
            if( $city != '' ) {
                $joined_address .= $city . "\n";
            }
            $customer['addresses'][$a]['joined'] = $joined_address;
            $details[] = array('label'=>$label, 'value'=>$joined_address);
        }
    }
    if( isset($customer['subscriptions']) ) {
        $subscriptions = '';
        $comma = '';
        foreach($customer['subscriptions'] as $sub => $subdetails) {
            $subscriptions .= $comma . $subdetails['name'];
            $comma = ', ';
        }
        if( $subscriptions != '' ) {
            $details[] = array('label'=>'Subscriptions', 'value'=>$subscriptions);
        }
    }

    return array('stat'=>'ok', 'customer'=>$customer, 'details'=>$details);
}
?>
