<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_flags($ciniki) {
	$flags = array(
		array('flag'=>array('bit'=>'1', 'name'=>'Customers')),
		array('flag'=>array('bit'=>'2', 'name'=>'Members')),
		array('flag'=>array('bit'=>'3', 'name'=>'Member Categories')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
