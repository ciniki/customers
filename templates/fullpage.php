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
function ciniki_customers_templates_fullpage($ciniki, $business_id, $categories, $args, $size='full') {

	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');

	//
	// Load business details
	//
	$rc = ciniki_businesses_businessDetails($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['details']) && is_array($rc['details']) ) {	
		$business_details = $rc['details'];
	} else {
		$business_details = array();
	}

	//
	// Load INTL settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	//
	// Create a custom class for this document
	//
	class MYPDF extends TCPDF {
		public $business_name = '';
		public $title = '';
		public $pagenumbers = 'yes';
		public $coverpage = 'no';
		public $toc = 'no';
		public $toc_categories = 'yes';
		public $footer_height = 0;
		public $header_height = 0;
		public $footer_text = '';
		public $usable_width = 180;
		public $fresh_page = 'yes';		// Flag to track if on a fresh page and if title should be at top or bumped down.
		public $section_title_font_size = 18;
		public $member_title_font_size = 14;
		public $member_font_size = 12;

		public function Header() {
//			$this->SetFont('helvetica', 'B', 20);
//			$this->SetLineWidth(0.25);
//			$this->SetDrawColor(125);
//			$this->setCellPaddings(5,1,5,2);
//			if( $this->title != '' ) {
//				$this->Cell(0, 22, $this->title, 'B', false, 'C', 0, '', 0, false, 'M', 'B');
//			}
//			$this->setCellPaddings(0,0,0,0);
		}

		// Page footer
		public function Footer() {
			// Position at 15 mm from bottom
			// Set font
			if( $this->pagenumbers == 'yes' ) {
				$this->SetY(-15);
				$this->SetFont('helvetica', '', 10);
				$this->Cell(0, 8, $this->getAliasNumPage(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
//				if( $this->toc == 'yes' ) {
//					$this->Cell(0, 8, $this->footer_text . '  --  Page ' . $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 
//						0, false, 'C', 0, '', 0, false, 'T', 'M');
//				} else {
//					$this->Cell(0, 8, $this->footer_text . '  --  Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
//						0, false, 'C', 0, '', 0, false, 'T', 'M');
//				}
			}
		}

		public function AddMember($member, $section_title) {
			//
			// Build the complex details array, deciding how much can fit on a line.
			//
			$member_details = array();
			$ln = 0;
			$member_details[$ln] = '';
			$this->SetFont('helvetica', '', $this->member_font_size);
			if( isset($member['addresses']) && count($member['addresses']) > 0 ) {
				foreach($member['addresses'] as $addr) {
					if( isset($addr['address1']) && $addr['address1'] != '' ) {
						$member_details[$ln] = $addr['address1'];
					}
					if( isset($addr['address2']) && $addr['address2'] != '' ) {
						if( $this->getStringWidth($member_details[$ln] . ($member_details[$ln]!=''?', ':'') . $addr['address2']) > $this->usable_width ) {	
							$ln++;
							$member_details[$ln] = $addr['address2'];
						} else {
							$member_details[$ln] .= ($member_details[$ln]!=''?', ':'') . $addr['address2'];
						}
					}
					$city = '';
					if( isset($addr['city']) && $addr['city'] != '' ) {
						$city .= $addr['city'];
					}
					if( isset($addr['province']) && $addr['province'] != '' ) {
						$city .= ($city!=''?', ':'') . $addr['province'];
					}
					if( isset($addr['postal']) && $addr['postal'] != '' && $city != '' ) {
						// Don't add postal if no city/province
						$city .= ($city!=''?'  ':'') . $addr['postal'];
					}
					if( $city != '' ) {
						if( $this->getStringWidth($member_details[$ln] . ($member_details[$ln]!=''?', ':'') . $city) > $this->usable_width ) {	
							$ln++;
							$member_details[$ln] = $city;
						} else {
							$member_details[$ln] .= ($member_details[$ln]!=''?', ':'') . $city;
						}
					}
				}
			}
			if( isset($member['phones']) && count($member['phones']) > 0 ) {
				foreach($member['phones'] as $phone) {
					if( count($member['phones']) > 1 ) {
						$phone_text = $phone['phone_label'] . ': ' . $phone['phone_number'];
					} else {
						$phone_text = $phone['phone_number'];
					}
					if( $phone_text != '' ) {
						if( $this->getStringWidth($member_details[$ln] . ($member_details[$ln]!=''?' - ':'') . $phone_text) > $this->usable_width ) {	
							$ln++;
							$member_details[$ln] = $phone_text;
						} else {
							$member_details[$ln] .= ($member_details[$ln]!=''?' - ':'') . $phone_text;
						}
					}
				}
			}
			if( isset($member['emails']) && count($member['emails']) > 0 ) {
				foreach($member['emails'] as $email) {
					if( $email['email'] != '' ) {
						if( $this->getStringWidth($member_details[$ln] . ($member_details[$ln]!=''?' - ':'') . $email['email']) > $this->usable_width ) {	
							$ln++;
							$member_details[$ln] = $email['email'];
						} else {
							$member_details[$ln] .= ($member_details[$ln]!=''?' - ':'') . $email['email'];
						}
					}
				}
			}
			if( isset($member['links']) && count($member['links']) > 0 ) {
				foreach($member['links'] as $link) {
					if( $link['url'] != '' ) {
						if( $this->getStringWidth($member_details[$ln] . ($member_details[$ln]!=''?' - ':'') . $link['url']) > $this->usable_width ) {	
							$ln++;
							$member_details[$ln] = $link['url'];
						} else {
							$member_details[$ln] .= ($member_details[$ln]!=''?' - ':'') . $link['url'];
						}
					}
				}
			}

			//
			// Calculate how much room the member information will take up, decide if we need a new page
			//
			$this->SetFont('helvetica');
			$cnt_height = ($this->getPageHeight() - $this->top_margin - $this->header_height - $this->footer_height);
			$member_height = 0;
			if( $section_title != '' ) {
				$this->SetFont('', 'B', $this->section_title_font_size);
				$member_height += $this->getStringHeight($this->usable_width, $section_title);
				$member_height += 8;
			}
			if( isset($member['title']) && $member['title'] != '' ) {
				$this->SetFont('', 'B', $this->member_title_font_size);
				$member_height += $this->getStringHeight($this->usable_width, $member['title']);
				$member_height += 1;
			}
			$this->SetFont('', '', $this->member_font_size);
			if( isset($member['short_bio']) && $member['short_bio'] != '' ) {
				$member_height += $this->getStringHeight($this->usable_width, $member['short_bio']);
				$member_height += 3;
			}
			foreach($member_details as $det) {
				if( $det != '' ) {
					$member_height += $this->getStringHeight($this->usable_width, $det);
				}
			}
			$member_height += 3;
			$member_height += 2;
	
			//
			// Check if we need a new page
			//
			if( ($this->getY() + $member_height - $this->top_margin - $this->header_height) > ($cnt_height) ) {
				// Add a page
				$this->AddPage('P');
				$this->SetFillColor(255);
				$this->SetTextColor(0);
				$this->SetDrawColor(51);
				$this->SetLineWidth(0.15);
			} elseif( $section_title != '' && $this->fresh_page == 'no' ) {
				$this->Ln(6);
			}

			if( $section_title != '' ) {
				$this->SetFont('', 'B', $this->section_title_font_size);
				$this->MultiCell($this->usable_width, 8, $section_title, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
				$this->Ln(5);
				// Add a table of contents bookmarks
				if( $this->toc == 'yes' && $this->toc_categories == 'yes' ) {
					$this->SetFont('', '');
					$this->Bookmark($section_title, 0, 0, '', '');
				}
			}

			if( isset($member['title']) && $member['title'] != '' ) {
				$this->SetFont('', 'B', $this->member_title_font_size);
				$this->MultiCell($this->usable_width, 5, $member['title'], 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
				$this->Ln(1);
				if( $this->toc == 'yes' && $this->toc_categories == 'no' ) {
					$this->Bookmark($member['title'], 0, 0, '', '');
				}
			}

			$this->SetFont('', '', $this->member_font_size);
			if( isset($member['short_bio']) && $member['short_bio'] != '' ) {
				$this->MultiCell($this->usable_width, 5, $member['short_bio'], 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
				$this->Ln(3);
			}
			if( count($member_details) > 0 ) {
				foreach($member_details as $det) {
					if( $det != '' ) {
						$this->MultiCell($this->usable_width, 5, $det, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
						$this->Ln(0);
					}
				}
				$this->Ln(3);
			}
			$this->Ln(2);
		}
	}

	//
	// Start a new document
	//
	if( $size == 'half' ) {
		$pdf = new MYPDF('P', PDF_UNIT, 'STATEMENT', true, 'UTF-8', false);
		// set margins
		$pdf->header_height = 0;
		$pdf->footer_height = 12;
		$pdf->top_margin = 12;
		$pdf->left_margin = 13;
		$pdf->right_margin = 13;
		$pdf->middle_margin = 6;
		$pdf->SetMargins($pdf->left_margin, $pdf->top_margin, $pdf->right_margin);
		$pdf->SetHeaderMargin($pdf->header_height);
		$pdf->setPageOrientation('P', false);
		$pdf->SetFooterMargin(0);
		$pdf->usable_width = 114;
		$pdf->section_title_font_size = 18;
		$pdf->member_title_font_size = 12;
		$pdf->member_font_size = 12;
	} else {
		$pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
		// set margins
		$pdf->header_height = 0;
		$pdf->footer_height = 12;
		$pdf->top_margin = 15;
		$pdf->left_margin = 15;
		$pdf->right_margin = 15;
		$pdf->SetMargins($pdf->left_margin, $pdf->top_margin, $pdf->right_margin);
		$pdf->SetHeaderMargin($pdf->header_height);
		$pdf->setPageOrientation('P', false);
		$pdf->SetFooterMargin(0);
		$pdf->usable_width = 180;
		$pdf->section_title_font_size = 18;
		$pdf->member_title_font_size = 14;
		$pdf->member_font_size = 12;
	}
	
	if( isset($args['title']) ) {
		$pdf->title = $args['title'];
	} else {
		$pdf->title = 'Member Directory';
	}

	// Set PDF basics
	$pdf->SetCreator('Ciniki');
	$pdf->SetAuthor($business_details['name']);
	$pdf->footer_text = $business_details['name'];
	$pdf->SetTitle($args['title']);
	$pdf->SetSubject('');
	$pdf->SetKeywords('');

	// Set font
	$pdf->SetFont('times', 'BI', 10);
	$pdf->SetCellPadding(0);

	//
	// Check if coverpage is to be outputed
	//
	if( isset($args['coverpage']) && $args['coverpage'] == 'yes' ) {
		$pdf->coverpage = 'yes';
		$pdf->title = '';
		if( isset($args['title']) && $args['title'] != '' ) {
			$title = $args['title'];
		} else {
			$title = "Members";
		}
		$pdf->pagenumbers = 'no';
		$pdf->AddPage('P');
		
		if( isset($args['coverpage-image']) && $args['coverpage-image'] > 0 ) {
			$img_box_width = $pdf->usable_width;
			if( $pdf->usable_width == 180 ) {
				$img_box_height = 150;
			} else {
				$img_box_height = 100;
			}
			$rc = ciniki_images_loadCacheOriginal($ciniki, $business_id, $args['coverpage-image'], 2000, 2000);
			if( $rc['stat'] == 'ok' ) {
				$image = $rc['image'];
				$pdf->SetLineWidth(0.25);
				$pdf->SetDrawColor(50);
				$img = $pdf->Image('@'.$image, '', '', $img_box_width, $img_box_height, 'JPEG', '', '', false, 300, '', false, false, 0, 'CT');
			}
			$pdf->SetY(-50);
		} else {
			$pdf->SetY(-100);
		}

		$pdf->SetFont('times', 'B', '30');
		$pdf->MultiCell($pdf->usable_width, 5, $title, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
		$pdf->endPage();
	}
	$pdf->pagenumbers = 'yes';

	//
	// Add the member items
	//
	$page_num = 1;
	$pdf->toc_categories = 'no';
	if( count($categories) > 1 ) {
		$pdf->toc_categories = 'yes';
	}
	if( isset($args['toc']) && $args['toc'] == 'yes' ) {
		$pdf->toc = 'yes';
	}
	if( !isset($args['section-pagebreak']) || $args['section-pagebreak'] != 'yes' ) {
		// Start a new page
		$pdf->AddPage('P');
		$pdf->SetFillColor(255);
		$pdf->SetTextColor(0);
		$pdf->SetDrawColor(51);
		$pdf->SetLineWidth(0.15);
	}
	$pdf->fresh_page = 'yes';
	foreach($categories as $cid => $category) {
		$member_num = 1;
		if( isset($args['section-pagebreak']) && $args['section-pagebreak'] == 'yes' ) {
			// Start a new page
			$pdf->AddPage('P');
			$pdf->SetFillColor(255);
			$pdf->SetTextColor(0);
			$pdf->SetDrawColor(51);
			$pdf->SetLineWidth(0.15);
			$pdf->fresh_page = 'yes';		// Reset
		}
		
		foreach($category['members'] as $mid => $member) {
			if( $member_num == 1 ) {
				$section_title = $category['name'];
			} else {
				$section_title = '';
			}

			$pdf->AddMember($member, $section_title);
			$member_num++;
			$pdf->fresh_page = 'no';
		}
	}
	$pdf->endPage();

	if( isset($args['toc']) && $args['toc'] == 'yes' ) {
		$pdf->addTOCPage();
		$pdf->SetFont('helvetica', 'B', 18);
		$pdf->SetTextColor(0);
		$pdf->SetLineWidth(0.15);
		$pdf->SetDrawColor(51);
		$pdf->setCellPaddings(5,1,5,2);
		$pdf->MultiCell($pdf->usable_width, 5, 'Table of Contents', 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
		$pdf->setCellPaddings(0,0,0,0);
		$pdf->Ln(8);
		$pdf->SetFont('helvetica', '', $pdf->member_font_size);
		$pdf->pagenumbers = 'no';
		$pdf->addTOC(($pdf->coverpage=='yes'?2:0), 'courier', '.', 'INDEX', '');
		$pdf->pagenumbers = 'yes';
		$pdf->endTOCPage();
	}

	return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
