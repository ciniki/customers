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
function ciniki_customers_sync_objects($ciniki, &$sync, $business_id, $args) {
	//
	// Note: Pass the standard set of arguments in, they may be required in the future
	//

	return array('stat'=>'ok', 'objects'=>array(
		'setting'=>array(),
		'customer'=>array(),
		'email'=>array(),
		'relationship'=>array(),
		'history'=>array(),
	));
}
?>
