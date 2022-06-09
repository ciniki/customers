<?php
//
// Description
// ===========
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
// Returns
// -------
//
function ciniki_customers_reportCustomerCategories($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'), 
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'json', 'name'=>'Output'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.reportCustomerCategories'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Get the list of customer and the categories from sapos
    //
    $strsql = "SELECT customers.id, "
        . "customers.display_name, "
        . "IF(customers.member_status=10, 'yes', 'no') AS member_status, "
        . "IF(items.category='', 'Misc', items.category) AS category, "
        . "SUM(items.total_amount) AS amount " 
        . "FROM ciniki_customers AS customers "
        . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
            . "customers.id = invoices.customer_id "
            . "AND invoices.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "' "
            . "AND invoices.invoice_date <= '" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "' "
            . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_invoice_items AS items ON ("
            . "invoices.id = items.invoice_id "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY customers.id, category "
        . "ORDER BY customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'customers', 'fname'=>'id', 'fields'=>array('display_name', 'member_status')),
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('category', 'amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.545', 'msg'=>'Unable to load customers', 'err'=>$rc['err']));
    }
    $customers = isset($rc['customers']) ? $rc['customers'] : array();
    $categories = array();
    foreach($customers as $cid => $customer) {
        $customers[$cid]['total_amount'] = 0;
        if( isset($customer['categories']) ) {
            foreach($customer['categories'] as $cat) {  
                if( !in_array($cat['category'], $categories) ) {
                    $categories[] = $cat['category'];
                }
                $customers[$cid][$cat['category']] = $cat['amount'];
                $customers[$cid]['total_amount'] += $cat['amount'];
            }
        }
    }

    //
    // Output PDF version
    //
    if( $args['output'] == 'pdf' ) {
        //
        // FIXME: Add pdf output
        //
/*        $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'templates', 'offeringRegistrationsPDF');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $fn = $rc['function_call'];

        $rc = $fn($ciniki, $args['tnid'], $args['offering_id'], $tenant_details, $courses_settings);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        $title = $rc['offering']['code'] . '_' . $rc['offering']['course_name'] . '_' . $rc['offering']['course_name'];

        $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $title));
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($filename . '.pdf', 'D');
        } */
    }

    //
    // Output Excel version
    //
    elseif( $args['output'] == 'excel' ) {
        require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

        $col = 0;
        $row = 1;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Customer', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Member', false);
        foreach($categories as $cat) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $cat, false);
        }
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Total', false);
        $row++;
        foreach($customers as $customer) {
            $col = 0;
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['display_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['member_status'], false);
            foreach($categories as $cat) {
                if( isset($customer['categories'][$cat]['amount']) ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['categories'][$cat]['amount'], false);
                    $objPHPExcelWorksheet->getStyle(chr($col+64) . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                } else {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, '', false);
                }
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['total_amount'], false);
            $objPHPExcelWorksheet->getStyle(chr($col+64) . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

            $row++;
        }
        $objPHPExcelWorksheet->getStyle('A1:' . chr($col+65) . '1')->getFont()->setBold(true);
        $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
        $col=2;
        foreach($categories as $cat) {
            $objPHPExcelWorksheet->getColumnDimension(chr($col+65))->setAutoSize(true);
            $col++;
        }
        $objPHPExcelWorksheet->getColumnDimension(chr($col+65))->setAutoSize(true);
        $col++;
        $objPHPExcelWorksheet->freezePane(chr($col+65) . '2');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="customerspending.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        
        return array('stat'=>'exit');
    } 

    return array('stat'=>'ok', 'customers'=>$customers, 'categories'=>$categories);
}
?>
