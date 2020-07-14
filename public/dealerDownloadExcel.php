<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the dealers belong to.
//
// Returns
// -------
// A word document
//
function ciniki_customers_dealerDownloadExcel(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'columns'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Columns'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.dealerDownloadExcel', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];


    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    //
    // Add headers
    //
    $row = 1;
    $col = 0;
    $tax_code = 'no';
    foreach($args['columns'] as $column) {
        $value = '';
        switch($column) {
            case 'eid': $value = 'ID'; break;
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
            case 'dealer_status': $value = 'Status'; break;
            case 'dealer_categories': $value = 'Categories'; break;
            case 'tax_number': $value = 'Tax Number'; break;
            case 'tax_location_name': $value = 'Tax'; $tax_code = 'yes'; break;
            case 'tax_location_code': $value = 'Tax Code'; $tax_code = 'yes'; break;
            case 'start_date': $value = 'Start Date'; break;
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

    if( $col > 26 ) {
        $objPHPExcelWorksheet->getStyle('A1:A' . chr(65+$col-27) . '1')->getFont()->setBold(true);
    } else {
        $objPHPExcelWorksheet->getStyle('A1:' . chr(65+$col-1) . '1')->getFont()->setBold(true);
    }
    $objPHPExcelWorksheet->freezePane('A2');

    $row++;


    $strsql = "SELECT ciniki_customers.id, ciniki_customers.eid, prefix, first, middle, last, suffix, "
        . "ciniki_customers.company, ciniki_customers.department, ciniki_customers.title, "
        . "ciniki_customers.display_name, "
        . "ciniki_customers.type, "
        . "ciniki_customers.status, "
        . "ciniki_customers.dealer_status, ";
    if( $tax_code == 'yes' ) {
        $strsql .= "ciniki_tax_locations.name AS tax_location_name, ";
        $strsql .= "ciniki_tax_locations.code AS tax_location_code, ";
    } else {
        $strsql .= "'' AS tax_location_name, ";
        $strsql .= "'' AS tax_location_code, ";
    }
    $strsql .= "ciniki_customers.tax_number, "
        . "ciniki_customers.start_date, "
        . "IF(ciniki_customers.primary_image_id>0,'yes','no') AS primary_image, "
        . "ciniki_customers.primary_image_caption, "
        . "ciniki_customers.short_description, "
        . "ciniki_customers.full_bio, "
        . "IF((ciniki_customers.webflags&0x02)=0x02,'Visible','Hidden') AS visible, "
        . "ciniki_customer_tags.tag_name AS dealer_categories, "
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
        . "";
    if( $tax_code == 'yes' ) {
        $strsql .= "LEFT JOIN ciniki_tax_locations ON ("
            . "ciniki_customers.tax_location_id = ciniki_tax_locations.id "
            . "AND ciniki_tax_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $strsql .= "LEFT JOIN ciniki_customer_tags ON ("
            . "ciniki_customers.id = ciniki_customer_tags.customer_id "
            . "AND ciniki_customer_tags.tag_type = 40 "
            . "AND ciniki_customer_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_phones ON (ciniki_customers.id = ciniki_customer_phones.customer_id "
            . "AND ciniki_customer_phones.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id "
            . "AND ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_addresses ON (ciniki_customers.id = ciniki_customer_addresses.customer_id "
            . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_links ON (ciniki_customers.id = ciniki_customer_links.customer_id "
            . "AND ciniki_customer_links.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_customers.dealer_status > 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
            'fields'=>array('id', 'eid', 'prefix', 'first', 'middle', 'last', 'suffix',
                'company', 'display_name', 'type', 'status', 'visible', 
                'dealer_status', 'dealer_categories',
                'tax_number', 'tax_location_name', 'tax_location_code',
                'start_date',
                'phones', 'emails', 'addresses', 'links',
                'primary_image', 'primary_image_caption', 'short_description', 'full_bio'),
            'maps'=>array(
                'type'=>array('1'=>'Individual', '2'=>'Tenant'),
                'dealer_status'=>array('5'=>'Prospect', '10'=>'Active', '60'=>'Inactive'),
                ),
            'dlists'=>array('phones'=>', ', 'emails'=>', ', 'addresses'=>' - ', 'links'=>', ', 'dealer_categories'=>', ')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
        if( $col > 25 ) {
            $objPHPExcelWorksheet->getColumnDimension('A'.chr(65+$col-26))->setAutoSize(true);
        } else {
            $objPHPExcelWorksheet->getColumnDimension(chr(65+$col))->setAutoSize(true);
        }
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
