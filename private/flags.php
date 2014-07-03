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
function ciniki_customers_flags($ciniki, $modules) {
	$flags = array(
		array('flag'=>array('bit'=>'1', 'name'=>'Customers')),
		array('flag'=>array('bit'=>'2', 'name'=>'Members')),
		array('flag'=>array('bit'=>'3', 'name'=>'Member Categories')),
		array('flag'=>array('bit'=>'5', 'name'=>'Dealers')),
		array('flag'=>array('bit'=>'6', 'name'=>'Dealer Categories')),
		array('flag'=>array('bit'=>'9', 'name'=>'Distributors')),
		array('flag'=>array('bit'=>'10', 'name'=>'Distributor Categories')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
