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
		);
	
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
