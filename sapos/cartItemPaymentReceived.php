<?php
//
// Description
// ===========
// This function completes the course registration when the customer has submitted a payment and checkout cart.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_customers_sapos_cartItemPaymentReceived($ciniki, $business_id, $customer, $args) {

	if( !isset($args['object']) || $args['object'] == '' 
		|| !isset($args['object_id']) || $args['object_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3225', 'msg'=>'No item specified.'));
	}

	if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3226', 'msg'=>'No item specified.'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

	//
	// Get the business settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));

    if( $args['object'] == 'ciniki.customers.membership' ) {
        //
        // Get the latest season marked current
        //
        $strsql = "SELECT id, name "
            . "FROM ciniki_customer_seasons "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (flags&0x01) = 0x01 "
            . "ORDER BY end_date DESC "
            . "LIMIT 1 ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'season');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3232', 'msg'=>'Unable to setup the membership', 'err'=>$rc['err']));
        }
        if( !isset($rc['season']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3233', 'msg'=>'Unable to setup the membership'));
        }
        $season = $rc['season']; 

        //
        // Check the customer is not already attached to the season
        //
        $strsql = "SELECT id, season_id, customer_id, status, date_paid, notes "
            . "FROM ciniki_customer_season_members "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND season_id = '" . ciniki_core_dbQuote($ciniki, $season['id']) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customerseason');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3234', 'msg'=>'Unable to setup the membership', 'err'=>$rc['err']));
        }
        if( isset($rc['customerseason']) ) {
            //
            // Update the current information
            //
            $customerseason = $rc['customerseason'];
            
            $update_args = array(
                'date_paid'=>$dt->format('Y-m-d'),
                );
            if( $customerseason['status'] != 10 ) {
                $update_args['status'] = 10;
            }
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.season_member', $customerseason['id'], $update_args, 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3236', 'msg'=>'Unable to setup the membership', 'err'=>$rc['err']));
            }
        }

        elseif( count($rc['rows']) > 0 ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3235', 'msg'=>'Unable to setup the membership.'));
        }

        else {
            //
            // Setup the customer with a membership in the current season
            //
            $update_args = array(
                'season_id'=>$season['id'],
                'customer_id'=>$customer['id'],
                'status'=>10,
                'date_paid'=>$dt->format('Y-m-d'),
                'notes'=>'',
                );
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.customers.season_member', $update_args, 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3237', 'msg'=>'Unable to setup the membership', 'err'=>$rc['err']));
            }
        }

        //
        // Check to make sure the customer is member_status = 10
        //
        $strsql = "SELECT id, member_status, membership_length, membership_type "
            . "FROM ciniki_customers "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $customer['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3238', 'msg'=>'Customer does not exist.', 'err'=>$rc['err']));
        }
        if( !isset($rc['customer']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3239', 'msg'=>'Customer does not exist.'));
        }
        $customer = $rc['customer'];
        $update_args = array();
        if( $customer['member_status'] != '10' ) {
            $update_args['member_status'] = 10;
        }
        if( $customer['membership_length'] != '20' && $customer['membership_length'] != '60' ) {
            $update_args['membership_length'] = 20;
        }
        if( $args['object_id'] == 10 && $customer['membership_type'] != $args['object_id'] ) {
            $update_args['membership_type'] = 10;
        } elseif( $args['object_id'] == 20 && $customer['membership_type'] != $args['object_id'] ) {
            $update_args['membership_type'] = 20;
        } elseif( $args['object_id'] == 30 && $customer['membership_type'] != $args['object_id'] ) {
            $update_args['membership_type'] = 30;
        } elseif( $args['object_id'] == 40 && $customer['membership_type'] != $args['object_id'] ) {
            $update_args['membership_type'] = 40;
        } elseif( $args['object_id'] == 'lifetime' && $customer['membership_type'] != 30 ) {
            $update_args['membership_type'] = 30;
        }
        if( $args['object_id'] == 'lifetime' && $customer['membership_length'] != 60 ) {
            $update_args['membership_length'] = 60;
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.customer', $customer['id'], $update_args, 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3240', 'msg'=>'Unable to update the member.', 'err'=>$rc['err']));
            }
        }
    }

	return array('stat'=>'ok');
}
?>