<?php
//
// Description
// -----------
// This function will output a pdf document as a series of thumbnails.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_customers_templates_halfpage($ciniki, $business_id, $categories, $args) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'templates', 'fullpage');
    return ciniki_customers_templates_fullpage($ciniki, $business_id, $categories, $args, 'half');  
}
?>
