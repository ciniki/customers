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
        . "ORDER BY end_date DESC, type, sequence, end_date DESC "
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
    $now_dt = new DateTime('NOW', new DateTimezone($intl_timezone));
    $history = array();
    foreach($purchases as $purchase) {
        if( $purchase['type'] == 10 || $purchase['type'] == 20 ) {
            $end_dt = new DateTime($purchase['end_date'], new DateTimezone($intl_timezone));
            if( !isset($membership_details['type-' . $purchase['product_id']]) ) {
                $membership_details['type-' . $purchase['product_id']] = array(
                    'id' => $purchase['id'],
                    'product_id' => $purchase['product_id'],
                    'label' => 'Type', 
                    'type' => $purchase['type'],
                    'flags' => $purchase['flags'],
                    'value' => $purchase['short_name'],
                    'name' => $purchase['name'],
                    );
                if( $purchase['type'] == 20 && $purchase['end_date'] == '0000-00-00' ) {
                    $membership_details['type-' . $purchase['product_id']]['expires'] = 'future';
                    $membership_details['type-' . $purchase['product_id']]['expiry_display'] = 'Never';
                }
                elseif( $end_dt < $now_dt ) {
                    $membership_details['type-' . $purchase['product_id']]['expires'] = 'past';
                    $membership_details['type-' . $purchase['product_id']]['expiry_display'] = 'Expired ' . $end_dt->format('M j, Y');
                } else {
                    $membership_details['type-' . $purchase['product_id']]['expires'] = 'future';
                    $membership_details['type-' . $purchase['product_id']]['expiry_display'] = 'Expires ' . $end_dt->format('M j, Y');
                }
            } else {
                $history[] = array(
                    'id' => $purchase['id'],
                    'product_id' => $purchase['product_id'],
                    'type' => $purchase['type'],
                    'short_name' => $purchase['short_name'],
                    'name' => $purchase['name'],
                    'expired' => ($purchase['end_date'] != '0000-00-00' ? $end_dt->format('M j, Y') : ($purchase['type'] == '20' ? 'Never' : '')),
                    'end_dt' => clone $end_dt, 
                    );
            }
        }
/*        elseif( $purchase['type'] == 20 ) {
            $ptype = 'lifetime';
            if( $purchase['end_date'] != '0000-00-00' ) {
                $end_dt = new DateTime($purchase['end_date'], new DateTimezone($intl_timezone));
                if( $end_dt < $now_dt ) {
                    $history[] = array(
                        'id' => $purchase['id'],
                        'product_id' => $purchase['product_id'],
                        'type' => $purchase['type'],
                        'short_name' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'expired' => $end_dt->format('M j, Y'),
                        'end_dt' => clone $end_dt, 
                        );
                    continue;
                }
            }
            if( isset($membership_details['type']['expires']) && $membership_details['type']['expires'] == 'past' ) {
                $history[] = $membership_details['type'];
                $ptype = 'type';
            } else {
                if( isset($membership_details['lifetime']) ) {
                    $history[] = $membership_details['lifetime'];
                }
                $membership_details['lifetime'] = array(
                    'id' => $purchase['id'], 
                    'label' => 'Type', 
                    'value' => $purchase['name'], 
                    'name' => $purchase['name'], 
                    'type' => 20,
                    'expired' => 'future',
                    'expiry_display' => 'Never',
                    );
            }
        }  */
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
                        'id' => $purchase['id'],
                        'product_id' => $purchase['product_id'], 
                        'type' => $purchase['type'],
                        'label' => 'Add-on', 
                        'value' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'expiry_display' => 'Expired ' . $end_dt->format('M j, Y'),
                        'expires' => 'past',
                        );
                } else {
                    $history[] = array(
                        'id' => $purchase['id'],
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
                        'id' => $purchase['id'],
                        'product_id' => $purchase['product_id'],
                        'type' => $purchase['type'],
                        'label' => 'Add-on', 
                        'value' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'expiry_display' => 'Expiring ' . $end_dt->format('M j, Y'),
                        'expires' => 'soon',
                        );
                } else {
                    $history[] = array(
                        'id' => $purchase['id'],
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
                        'id' => $purchase['id'],
                        'product_id' => $purchase['product_id'],
                        'type' => $purchase['type'],
                        'label' => 'Add-on', 
                        'value' => $purchase['short_name'],
                        'name' => $purchase['name'],
                        'expiry_display' => 'Expires ' . $end_dt->format('M j, Y'),
                        'expires' => 'future',
                        );
                } else {
                    $history[] = array(
                        'id' => $purchase['id'],
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
