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
		// 0x0001
		array('flag'=>array('bit'=>'1', 'name'=>'Customers')),
		array('flag'=>array('bit'=>'2', 'name'=>'Members')),
		array('flag'=>array('bit'=>'3', 'name'=>'Member Categories')),
		array('flag'=>array('bit'=>'4', 'name'=>'Memberships')),
		// 0x0010
		array('flag'=>array('bit'=>'5', 'name'=>'Dealers')),
		array('flag'=>array('bit'=>'6', 'name'=>'Dealer Categories')),
//		array('flag'=>array('bit'=>'7', 'name'=>'Dealer Tags')),
//		array('flag'=>array('bit'=>'8', 'name'=>'Member Tags')),
		// 0x0100
		array('flag'=>array('bit'=>'9', 'name'=>'Distributors')),
		array('flag'=>array('bit'=>'10', 'name'=>'Distributor Categories')),
//		array('flag'=>array('bit'=>'11', 'name'=>'Distributor Tags')),
//		array('flag'=>array('bit'=>'12', 'name'=>'')),
		// 0x1000
		array('flag'=>array('bit'=>'13', 'name'=>'Price Points')),
		array('flag'=>array('bit'=>'14', 'name'=>'Sales Reps')),
//		array('flag'=>array('bit'=>'15', 'name'=>'Relationships')),
		array('flag'=>array('bit'=>'16', 'name'=>'Birthdate')),
		// 0x010000
		array('flag'=>array('bit'=>'17', 'name'=>'External ID')),
		array('flag'=>array('bit'=>'18', 'name'=>'Tax Number')),
		array('flag'=>array('bit'=>'19', 'name'=>'Tax Locations')),
		array('flag'=>array('bit'=>'20', 'name'=>'Reward Levels')),
		// 0x100000
		array('flag'=>array('bit'=>'21', 'name'=>'Sales Total')),
		array('flag'=>array('bit'=>'22', 'name'=>'Children')),
		array('flag'=>array('bit'=>'23', 'name'=>'Customer Categories')),
		array('flag'=>array('bit'=>'24', 'name'=>'Customer Tags')),
		// 0x01000000
		array('flag'=>array('bit'=>'25', 'name'=>'Address Phone Numbers')),
//		array('flag'=>array('bit'=>'26', 'name'=>'')),
//		array('flag'=>array('bit'=>'27', 'name'=>'')),
//		array('flag'=>array('bit'=>'28', 'name'=>'')),
		// 0x10000000
//		array('flag'=>array('bit'=>'29', 'name'=>'')),
//		array('flag'=>array('bit'=>'30', 'name'=>'')),
//		array('flag'=>array('bit'=>'31', 'name'=>'')),
//		array('flag'=>array('bit'=>'32', 'name'=>'')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
