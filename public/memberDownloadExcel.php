<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the members belong to.
//
// Returns
// -------
// A word document
//
function ciniki_customers_memberDownloadExcel(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'columns'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Columns'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.memberDownloadExcel', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];


	require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
	$objPHPExcel = new PHPExcel();

	$strsql = "SELECT ciniki_customers.id, prefix, first, middle, last, suffix, "
		. "company, department, title, display_name, "
		. "ciniki_customers.type, "
		. "ciniki_customers.status, "
		. "ciniki_customers.member_status, "
		. "ciniki_customers.member_lastpaid, "
		. "ciniki_customers.membership_length, "
		. "ciniki_customers.membership_type, "
		. "IF(ciniki_customers.primary_image_id>0,'yes','no') AS primary_image, "
		. "ciniki_customers.primary_image_caption, "
		. "ciniki_customers.short_description, "
		. "ciniki_customers.full_bio, "
		. "IF((ciniki_customers.webflags&0x01)=1,'Visible','Hidden') AS visible, "
		. "ciniki_customer_tags.tag_name AS member_categories, "
		. "CONCAT_WS(': ', ciniki_customer_phones.phone_label, "
			. "ciniki_customer_phones.phone_number) AS phones, "
		. "ciniki_customer_emails.email AS emails, "
		. "CONCAT_WS(', ', ciniki_customer_addresses.address1, "
			. "ciniki_customer_addresses.address2, "
			. "ciniki_customer_addresses.city, "
			. "ciniki_customer_addresses.province, "
			. "ciniki_customer_addresses.postal) AS addresses, "
		. "ciniki_customer_links.url AS links "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_tags ON ("
			. "ciniki_customers.id = ciniki_customer_tags.customer_id "
			. "AND ciniki_customer_tags.tag_type = 40 "
			. "AND ciniki_customer_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customer_phones ON (ciniki_customers.id = ciniki_customer_phones.customer_id "
			. "AND ciniki_customer_phones.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id "
			. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customer_addresses ON (ciniki_customers.id = ciniki_customer_addresses.customer_id "
			. "AND ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customer_links ON (ciniki_customers.id = ciniki_customer_links.customer_id "
			. "AND ciniki_customer_links.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.member_status = 10 "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'prefix', 'first', 'middle', 'last', 'suffix',
				'company', 'display_name', 'type', 'status', 'visible', 
				'member_status', 'member_lastpaid', 'membership_length', 'membership_type',
				'member_categories',
				'phones', 'emails', 'addresses', 'links',
				'primary_image', 'primary_image_caption', 'short_description', 'full_bio'),
			'maps'=>array(
				'type'=>array('1'=>'Individual', '2'=>'Business'),
				'member_status'=>array('10'=>'Active', '60'=>'Former'),
				'membership_length'=>array('10'=>'Monthly', '20'=>'Yearly', '60'=>'Lifetime'),
				'membership_type'=>array('10'=>'Regular', '20'=>'Complimentary', '30'=>'Reciprocal'),
				),
			'dlists'=>array('phones'=>', ', 'emails'=>', ', 'addresses'=>' - ', 'links'=>', ', 'member_categories'=>', ')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

	//
	// Add headers
	//
	$row = 1;
	$col = 0;
	foreach($args['columns'] as $column) {
		$value = '';
		switch($column) {
			case 'prefix': $value = 'Prefix'; break;
			case 'first': $value = 'First'; break;
			case 'middle': $value = 'Middle'; break;
			case 'last': $value = 'Last'; break;
			case 'suffix': $value = 'Suffix'; break;
			case 'company': $value = 'Company'; break;
			case 'department': $value = 'Department'; break;
			case 'title': $value = 'Title'; break;
			case 'display_name': $value = 'Name'; break;
			case 'type': $value = 'Type'; break;
			case 'visible': $value = 'Visible'; break;
			case 'member_status': $value = 'Status'; break;
			case 'member_lastpaid': $value = 'Last Paid'; break;
			case 'membership_length': $value = 'Length'; break;
			case 'membership_type': $value = 'Type'; break;
			case 'member_categories': $value = 'Categories'; break;
			case 'phones': $value = 'Phones'; break;
			case 'emails': $value = 'Emails'; break;
			case 'addresses': $value = 'Addresses'; break;
			case 'links': $value = 'Websites'; break;
			case 'notes': $value = 'Notes'; break;
			case 'primary_image': $value = 'Image'; break;
			case 'primary_image_caption': $value = 'Image Caption'; break;
			case 'short_description': $value = 'Short Bio'; break;
			case 'full_bio': $value = 'Full Bio'; break;
		}
		$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $value, false);
		$col++;
	}
	$objPHPExcelWorksheet->getStyle('A1:' . chr(65+$col-1) . '1')->getFont()->setBold(true);
	$objPHPExcelWorksheet->freezePane('A2');

	$row++;

	foreach($rc['customers'] as $customer) {
		$customer = $customer['customer'];

		$col = 0;
		foreach($args['columns'] as $column) {
			if( !isset($customer[$column]) ) {
				$col++;
				continue;
			}
			if( $column == 'addresses' ) {
				$customer[$column] = preg_replace('/, ,/', ',', $customer[$column]);
			}
			$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $customer[$column], false);
			$col++;
		}
		$row++;
	}

	$col = 0;
	PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
	foreach($args['columns'] as $column) {
		$objPHPExcelWorksheet->getColumnDimension(chr(65+$col))->setAutoSize(true);
		$col++;
	}

	//
	// Redirect output to a client’s web browser (Excel)
	//
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="export.xls"');
	header('Cache-Control: max-age=0');

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');

	return array('stat'=>'exit');
}
?>
