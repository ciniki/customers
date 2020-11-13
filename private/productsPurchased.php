<?php
//
// Description
// -----------
// This function returns the products purchases by a customer. 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// args:            customer_id
// 
// Returns
// ---------
// 
function ciniki_customers_productsPurchased(&$ciniki, $tnid, $args) {

    if( !isset($args['customer_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.428', 'msg'=>'No customer specified'));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the products purchased
    //
    $strsql = "SELECT purchases.id, "
        . "purchases.end_date, "
        . "products.id AS product_id, "
        . "products.name, "
        . "products.short_name, "
        . "products.type, "
        . "products.flags, "
        . "products.sequence "
        . "FROM ciniki_customer_product_purchases AS purchases "
        . "INNER JOIN ciniki_customer_products AS products ON ("
            . "purchases.product_id = products.id "
            . "AND products.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE purchases.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND purchases.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY type, sequence, end_date DESC "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'purchase');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.425', 'msg'=>'Unable to load purchase', 'err'=>$rc['err']));
    }
    $purchases = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Go through the purchases, and setup what is currently active and expired
    //
    $active = array();
    $expired = array();
    $membership_details = array();
    //
    // Determine the membership type
    //
    $history = array();
    foreach($purchases as $purchase) {
        if( $purchase['type'] == 10 ) {
            $end_dt = new DateTime($purchase['end_date'], new DateTimezone($intl_timezone));
            if( !isset($membership_details['type']) ) {
                $now_dt = new DateTime('NOW', new DateTimezone($intl_timezone));
                $membership_details['type'] = array(
                    'id' => $purchase['id'],
                    'product_id' => $purchase['product_id'],
                    'label' => 'Type', 
                    'type' => $purchase['type'],
                    'flags' => $purchase['flags'],
                    'value' => $purchase['short_name'],
                    'name' => $purchase['name'],
                    );
                if( $end_dt < $now_dt ) {
                    $membership_details['type']['expires'] = 'past';
                    $membership_details['type']['expiry_display'] = 'Expired ' . $end_dt->format('M j, Y');
                } else {
                    $membership_details['type']['expires'] = 'future';
                    $membership_details['type']['expiry_display'] = 'Expires ' . $end_dt->format('M j, Y');
                }
            } else {
                $history[] = array(
                    'product_id' => $purchase['product_id'],
                    'type' => $purchase['type'],
                    'short_name' => $purchase['short_name'],
                    'name' => $purchase['name'],
                    'expired' => $end_dt->format('M j, Y'),
                    'end_dt' => clone $end_dt, 
                    );
            }
        }
        if( $purchase['type'] == 20 ) {
            $membership_details['type'] = array('id' => $purchase['id'], 'label'=>'Type', 'value'=>$purchase['name'], 'type'=>20);
            $membership_details['expires'] = array('label'=>'Expires', 'value'=>'Never');
        }
    }
    //
    // Determine the add-on's that are active and expired
    //
    foreach($purchases as $purchase) {
        if( $purchase['type'] == 40 ) {
            $end_dt = new DateTime($purchase['end_date'], new DateTimezone($intl_timezone));
            $now_dt = new DateTime('NOW', new DateTimezone($intl_timezone));
            $soon_dt = new DateTime('NOW', new DateTimezone($intl_timezone));
            $soon_dt->add(new DateInterval('P30D'));
            $detail_idx = $purchase['sequence'] . '-' . $purchase['id'];
            if( $end_dt < $now_dt ) {
                // Expired, only add if not a current active add-on
                if( !isset($membership_details[$detail_idx]) ) {
                    $membership_details[$detail_idx] = array(
                        'product_id' => $purchase['product_id'],
                        'label' => 'Add-on', 
                        'value' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'expiry_display' => 'Expired ' . $end_dt->format('M j, Y'),
                        'expires' => 'past',
                        );
                } else {
                    $history[] = array(
                        'product_id' => $purchase['product_id'],
                        'type' => $purchase['type'],
                        'short_name' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'end_dt' => clone $end_dt, 
                        'expired' => $end_dt->format('M j, Y'),
                        );
                }
            } elseif( $end_dt < $soon_dt ) {
                // Expired, only add if not a current active add-on
                if( !isset($membership_details[$detail_idx]) ) {
                    $membership_details[$detail_idx] = array(
                        'product_id' => $purchase['product_id'],
                        'label' => 'Add-on', 
                        'value' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'expiry_display' => 'Expiring ' . $end_dt->format('M j, Y'),
                        'expires' => 'soon',
                        );
                } else {
                    $history[] = array(
                        'product_id' => $purchase['product_id'],
                        'type' => $purchase['type'],
                        'short_name' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'end_dt' => clone $end_dt, 
                        'expired' => $end_dt->format('M j, Y'),
                        );
                }
            } else {
                // Active
                if( !isset($membership_details[$detail_idx]) ) {
                    $membership_details[$detail_idx] = array(
                        'product_id' => $purchase['product_id'],
                        'label' => 'Add-on', 
                        'value' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'expiry_display' => 'Expires ' . $end_dt->format('M j, Y'),
                        'expires' => 'future',
                        );
                } else {
                    $history[] = array(
                        'product_id' => $purchase['product_id'],
                        'type' => $purchase['type'],
                        'short_name' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'end_dt' => clone $end_dt, 
                        'expired' => $end_dt->format('M j, Y'),
                        );
                }
            }
        }
    }

    uasort($history, function($a, $b) {
        if( $a['end_dt'] == $b['end_dt'] ) {
            if( $a['type'] == $b['type'] ) {
                return 0;
            } 
            return $a['type'] < $b['type'] ? -1 : 1;
        }
        return $a['end_dt'] > $b['end_dt'] ? -1 : 1;
        });

    return array('stat'=>'ok', 'membership_details'=>$membership_details, 'history'=>$history);
}
?>
