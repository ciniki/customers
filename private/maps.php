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
			'20'=>'On Hold',
			'50'=>'Suspended',
			'60'=>'Deleted',
			),
		);
	
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
