<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_maps($ciniki) {

	$maps = array();
	$maps['customer'] = array(
		'status'=>array(
			'10'=>'Active',
			'40'=>'On Hold',
			'50'=>'Suspended',
			'60'=>'Deleted',
			),
		'member_status'=>array(
			'0'=>'',
			'10'=>'Active',
			'60'=>'Suspended Member',
			),
		'dealer_status'=>array(
			'0'=>'',
			'5'=>'Prospect',
			'10'=>'Dealer',
			'60'=>'Suspended Dealer',
			),
		'distributor_status'=>array(
			'0'=>'',
			'10'=>'Distributor',
			'60'=>'Suspended Distributor',
			),
		'membership_length'=>array(
			'0'=>'',
			'10'=>'Monthly',
			'20'=>'Yearly',
			'60'=>'Lifetime',
			),
		'membership_type'=>array(
			'0'=>'',
			'10'=>'Regular',
			'20'=>'Complimentary',
			'60'=>'Reciprocal',
			),
		);
	$maps['season_member'] = array(
		'status'=>array(
			'0'=>'Unknown',
			'10'=>'Active',
			'60'=>'Inactive',
			),
		);

	$maps['address'] = array(
		'flags'=>array(
			'0'=>'',
			0x01=>'Shipping',
			0x02=>'Billing',
			0x04=>'Mailing',
			0x08=>'Public',
			),
		'flags_shortcodes'=>array(
			'0'=>'',
			0x01=>'S',
			0x02=>'B',
			0x04=>'M',
			0x08=>'P',
			),
		);
	
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
