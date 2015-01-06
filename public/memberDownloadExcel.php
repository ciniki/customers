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

	//
	// Load maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
	$rc = ciniki_customers_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// If seasons is enabled and requested, get the requested season names
	//
	$season_ids = array();
	$seasons = array();
	if( ($ciniki['business']['modules']['ciniki.customers']['flags']&0x02000000) > 0 ) {
		foreach($args['columns'] as $column) {
			if( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
				$season_ids[] = $matches[1];
			}
		}
		if( count($season_ids) > 0 ) {
			$strsql = "SELECT id, name "
				. "FROM ciniki_customer_seasons "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND id IN (" . ciniki_core_dbQuoteIDs($ciniki, $season_ids) . ") "
				. "";
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
				array('container'=>'seasons', 'fname'=>'id', 
					'fields'=>array('id', 'name')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['seasons']) ) {
				$seasons = $rc['seasons'];
			}
			$strsql = "SELECT season_id, customer_id, status "
				. "FROM ciniki_customer_season_members "
				. "WHERE ciniki_customer_season_members.season_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $season_ids) . ") "
				. "AND ciniki_customer_season_members.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "ORDER BY season_id, customer_id "
				. "";
			$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
				array('container'=>'seasons', 'fname'=>'season_id', 'fields'=>array('season_id')),
				array('container'=>'customers', 'fname'=>'customer_id', 
					'fields'=>array('id'=>'customer_id', 'status')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['seasons']) ) {
				foreach($seasons as $sid => $season) {
					if( isset($rc['seasons'][$sid]['customers']) ) {
						$seasons[$sid]['customers'] = $rc['seasons'][$sid]['customers'];
					}
				}
			}
		}
	}

	//
	// Load the categories
	//
	$member_categories = array();
	$strsql = "SELECT customer_id, "
		. "ciniki_customer_tags.tag_name AS member_categories "
		. "FROM ciniki_customer_tags "
		. "WHERE ciniki_customer_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customer_tags.tag_type = 40 "
		. "ORDER BY ciniki_customer_tags.customer_id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('member_categories'),
			'dlists'=>array('member_categories'=>', ')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$member_categories = $rc['customers'];
	}

	//
	// Load the phones
	//
	$phones = array();
	$strsql = "SELECT customer_id, "
		. "CONCAT_WS(': ', ciniki_customer_phones.phone_label, "
			. "ciniki_customer_phones.phone_number) AS phones "
		. "FROM ciniki_customer_phones "
		. "WHERE ciniki_customer_phones.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_customer_phones.customer_id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('phones'),
			'dlists'=>array('phones'=>', ')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$phones = $rc['customers'];
	} 

	//
	// Load the addresses
	//
	$addresses = array();
	$strsql = "SELECT customer_id, "
		. "CONCAT_WS(', ', ciniki_customer_addresses.address1, "
			. "ciniki_customer_addresses.address2, "
			. "ciniki_customer_addresses.city, "
			. "ciniki_customer_addresses.province, "
			. "ciniki_customer_addresses.postal) AS addresses "
		. "FROM ciniki_customer_addresses "
		. "WHERE ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_customer_addresses.customer_id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('addresses'),
			'dlists'=>array('addresses'=>'/')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$addresses = $rc['customers'];
	}

	//
	// Load the links
	//
	$links = array();
	$strsql = "SELECT customer_id, "
		. "ciniki_customer_links.url AS links "
		. "FROM ciniki_customer_links "
		. "WHERE ciniki_customer_links.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_customer_links.customer_id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'customer_id', 
			'fields'=>array('links'),
			'dlists'=>array('links'=>', ')),
		));	
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['customers']) ) {
		$links = $rc['customers'];
	}

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
		. "'' AS member_categories, "
		. "'' AS phones, "
		. "'' AS addresses, "
		. "'' AS links, "
		. "ciniki_customer_emails.email AS emails "
		. "FROM ciniki_customers "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id "
			. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "";
			
	$strsql .= "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_customers.member_status = 10 "
		. "ORDER BY ciniki_customers.sort_name "
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
				'member_status'=>$maps['customer']['member_status'], //array('10'=>'Active', '60'=>'Former'),
				'membership_length'=>$maps['customer']['membership_length'], // array('10'=>'Monthly', '20'=>'Yearly', '60'=>'Lifetime'),
				'membership_type'=>$maps['customer']['membership_type'], // array('10'=>'Regular', '20'=>'Complimentary', '30'=>'Reciprocal'),
				),
			'dlists'=>array('emails'=>', ')),
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
		if( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
			if( isset($seasons[$matches[1]]) ) {
				$value = $seasons[$matches[1]]['name'];
			}
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
			if( preg_match("/^season-([0-9]+)$/", $column, $matches) ) {
				$value = '';
				if( isset($seasons[$matches[1]]['customers'][$customer['id']]['status'])
					&& $seasons[$matches[1]]['customers'][$customer['id']]['status'] > 0 
					&& isset($maps['season_member']['status'][$seasons[$matches[1]]['customers'][$customer['id']]['status']]) 
					) {
					$value = $maps['season_member']['status'][$seasons[$matches[1]]['customers'][$customer['id']]['status']];
				} else {
					$col++;
					continue;
				}
			} elseif( $column == 'member_categories' && isset($member_categories[$customer['id']]['member_categories']) ) {
				$value = $member_categories[$customer['id']]['member_categories'];
			} elseif( $column == 'phones' && isset($phones[$customer['id']]['phones']) ) {
				$value = $phones[$customer['id']]['phones'];
			} elseif( $column == 'addresses' && isset($addresses[$customer['id']]['addresses']) ) {
				$value = preg_replace('/, ,/', ',', $addresses[$customer['id']]['addresses']);
			} elseif( $column == 'links' && isset($links[$customer['id']]['links']) ) {
				$value = $links[$customer['id']]['links'];
			} elseif( !isset($customer[$column]) ) {
				$col++;
				continue;
			} else {
				$value = $customer[$column];
			}
			$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $value, false);
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
