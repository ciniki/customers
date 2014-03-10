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
function ciniki_customers_memberDownloadDirectory(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'heading1_size'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Heading 1 Size'), 
        'heading2_size'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Heading 2 Size'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.memberDownloadDirectory', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	require($ciniki['config']['core']['lib_dir'] . '/PHPWord/Classes/PHPWord.php');

	$PHPWord = new PHPWord();
	$h1_size = 20;
	if( isset($args['heading1_size']) && $args['heading1_size'] != '' ) {
		$h1_size = $args['heading1_size'];
	}
	// 120 = 6pt
	$PHPWord->addTitleStyle(1, array('size'=>$h1_size, 'color'=>'000000', 'bold'=>true), 
		array('align'=>'center', 'spaceAfter'=>120));
	$h2_size = 16;
	if( isset($args['heading2_size']) && $args['heading2_size'] != '' ) {
		$h2_size = $args['heading1_size'];
	}
	$PHPWord->addTitleStyle(2, array('size'=>$h2_size, 'color'=>'000000', 'bold'=>true), 
		array('spaceBefore'=>240, 'spaceAfter'=>120));


	if( ($modules['ciniki.customers']['flags']&0x04) > 0 ) {
		$strsql = "SELECT ciniki_customers.id, "
			. "ciniki_customer_tags.tag_name AS category, "
			. "ciniki_customers.display_name AS title, "
			. "ciniki_customers.permalink, "
			. "ciniki_customers.short_description "
			. "FROM ciniki_customer_tags, ciniki_customers "
			. "WHERE ciniki_customer_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customer_tags.tag_type = '40' "
			. "AND ciniki_customer_tags.customer_id = ciniki_customers.id "
			// Check the member is visible on the website
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customers.member_status = 10 "
			. "AND (ciniki_customers.webflags&0x01) = 1 "
			. "ORDER BY ciniki_customer_tags.tag_name, ciniki_customers.display_name ";
	} else {
		$strsql = "SELECT ciniki_customers.id, "
			. "'Members' AS category, "
			. "ciniki_customers.display_name AS title, "
			. "ciniki_customers.permalink, "
			. "ciniki_customers.short_description "
			. "FROM ciniki_customers "
			. "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customers.member_status = 10 "
			. "AND (ciniki_customers.webflags&0x01) = 1 "
			. "ORDER BY ciniki_customers.display_name ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'categories', 'fname'=>'category',
			'fields'=>array('id', 'name'=>'category')),
		array('container'=>'members', 'fname'=>'id', 
			'fields'=>array('id', 'title', 'description'=>'short_description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$categories = $rc['categories'];
	$s_count = 0;
	$section = $PHPWord->createSection(array(
		'marginLeft'=>720, 
		'marginRight'=>720, 
		'marginTop'=>720, 
		'marginBottom'=>720, 
		'pageSizeW'=>12240, 
		'pageSizeH'=>15840
		));
	foreach($categories as $category) {
		if( $s_count > 0 ) {
			$section->addPageBreak();
		}
		$section->addTitle($category['name'], 1);
		foreach($category['members'] as $member) {
			$section->addTitle($member['title'], 2);
			$desc = $member['description'];
			$desc = preg_replace('/\n/', "\r\n", $desc);
			$desc = preg_replace('/\<a.*href=\'(http:\/\/|)([^\']*)\'.*\>.*\<\/a\>/', '$2', $desc);
			$blocks = explode("\n", $desc);
			foreach($blocks as $block) {
				$section->addText($block);
			}
		}
		$s_count++;
	}

	//
	// Redirect output to a client’s web browser (Word)
	//
	header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
	header('Content-Disposition: attachment;filename="directory.docx"');
	header('Cache-Control: max-age=0');

	$objWriter = PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
	$objWriter->save('/tmp/directory.docx');
	$objWriter->save('php://output');

	return array('stat'=>'exit');
}
?>
