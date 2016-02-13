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
			'10'=>'Active',
			'40'=>'Previous',
			'60'=>'Closed',
			),
		'distributor_status'=>array(
			'0'=>'',
			'10'=>'Active',
			'60'=>'Closed',
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
			'20'=>'Student',
			'30'=>'Individual',
			'40'=>'Family',
			'110'=>'Complimentary',
			'150'=>'Reciprocal',
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
