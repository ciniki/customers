<?php
//
// Description
// -----------
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_update(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'parent_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Parent'), 
        'eid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'), 
		'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Type'), 
        'member_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member Status'), 
		'member_lastpaid'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Member Last Paid'),
		'membership_length'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Membership Length'),
		'membership_type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Membership Type'),
        'dealer_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Dealer Status'), 
        'distributor_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Distributor Status'), 
        'prefix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Prefix'), 
        'first'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Name'), 
        'middle'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Middle Name'), 
        'last'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Name'), 
        'suffix'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Suffix'), 
        'display_name_format'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Display Name Format'), 
        'company'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company'), 
        'department'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Department'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Company Title'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'primary_image_caption'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image Caption'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Webflags'), 
        'short_bio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Bio'), 
        'full_bio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Full Bio'), 
        'birthdate'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Birthday'), 
        'pricepoint_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Price Point'), 
        'salesrep_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sales Rep'), 
        'tax_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tax Number'), 
        'tax_location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tax Location'), 
        'reward_level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Reward Level'), 
        'sales_total'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sales Total'), 
        'sales_total_prev'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Previous Sales'), 
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'), 
		'subscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Subscriptions'),
		'unsubscriptions'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Unsubscriptions'),
		'customer_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Customer Categories'),
		'customer_tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Customer Tags'),
		'member_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Member Categories'),
		'dealer_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Dealer Categories'),
		'distributor_categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Distributor Categories'),
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.update', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];
	$perms = $rc['perms'];

	//
	// Get the current settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
	$rc = ciniki_customers_getSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Get the existing customer name
	//
	$strsql = "SELECT status, type, prefix, first, middle, last, suffix, "
		. "display_name, display_name_format, company "
		. "FROM ciniki_customers "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['customer']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1467', 'msg'=>'Customer does not exist'));
	}
	$customer = $rc['customer'];

	//
	// Only allow owners to change status of customer to/from suspend/delete
	//
	if( isset($args['status']) 
		&& ($args['status'] >= 50 || $customer['status'] >= 50) ) {
		if( !isset($perms) || ($perms&0x01) != 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2053', 'msg'=>'You do not have permissions to change the customer status.'));
		}
	}

	//
	// Check to make sure eid is unique if specified
	//
	if( isset($args['eid']) && $args['eid'] != '' ) {
		$strsql = "SELECT id "
			. "FROM ciniki_customers "
			. "WHERE eid = '" . ciniki_core_dbQuote($ciniki, $args['eid']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'eid');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1908', 'msg'=>'The customer ID already exists.'));
		}
	}

	//
	// Check if trying to make a child customer
	//
	if( isset($args['parent_id']) && $args['parent_id'] > 0 ) {
		//
		// Make sure parent_id is not customer id
		//
		if( $args['parent_id'] == $args['customer_id'] ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2328', 'msg'=>'Parent cannot be the same as the child.'));
		}

		// 
		// Check to make sure the parent is not a child
		//
		$strsql = "SELECT id, parent_id "
			. "FROM ciniki_customers "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'parent');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['parent']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1901', 'msg'=>'The parent does not exist.'));
		}
		if( isset($rc['parent']) && $rc['parent']['parent_id'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1902', 'msg'=>'The parent is already a child.'));
		}
		// 
		// Check to make sure the customer does not have any children
		//
		$strsql = "SELECT 'children', COUNT(*) AS num_children  "
			. "FROM ciniki_customers "
			. "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.customers', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['children']) && $rc['num']['children'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1904', 'msg'=>'This customer already has children and cannot become a parent.'));
		}
	}

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	if( isset($args['prefix']) || isset($args['first']) 
		|| isset($args['middle']) || isset($args['last']) || isset($args['suffix']) 
		|| isset($args['company']) || isset($args['type']) || isset($args['display_name_format']) ) {

		if( isset($args['display_name_format']) ) {
			$customer['display_name_format'] = $args['display_name_format'];
		}

		//
		// Build the persons name
		//
		$space = '';
		$person_name = '';
		$sort_person_name = '';
		if( isset($args['prefix']) && $args['prefix'] != '' ) {
			$person_name .= $args['prefix'];
		} elseif( !isset($args['prefix']) && $customer['prefix'] != '' ) {
			$person_name .= $customer['prefix'];
		}
		if( $space == '' && $person_name != '' ) { $space = ' '; }
		if( isset($args['first']) && $args['first'] != '' ) {
			$person_name .= $space . $args['first'];
		} elseif( !isset($args['first']) && $customer['first'] != '' ) {
			$person_name .= $space . $customer['first'];
		}
		if( $space == '' && $person_name != '' ) { $space = ' '; }
		if( isset($args['middle']) && $args['middle'] != '' ) {
			$person_name .= $space . $args['middle'];
		} elseif( !isset($args['middle']) && $customer['middle'] != '' ) {
			$person_name .= $space . $customer['middle'];
		}
		if( $space == '' && $person_name != '' ) { $space = ' '; }
		if( isset($args['last']) && $args['last'] != '' ) {
			$person_name .= $space . $args['last'];
			$sort_person_name = $args['last'];
		} elseif( !isset($args['last']) && $customer['last'] != '' ) {
			$person_name .= $space . $customer['last'];
			$sort_person_name = $customer['last'];
		}
		if( $space == '' && $person_name != '' ) { $space = ' '; }
		if( isset($args['suffix']) && $args['suffix'] != '' ) {
			$person_name .= $space . $args['suffix'];
		} elseif( !isset($args['suffix']) && $customer['suffix'] != '' ) {
			$person_name .= $space . $customer['suffix'];
		}

		if( isset($args['first']) && $args['first'] != '' ) {
			$sort_person_name .= ($sort_person_name!=''?', ':'') . $args['first'];
		} elseif( !isset($args['first']) && $customer['first'] != '' ) {
			$sort_person_name .= ($sort_person_name!=''?', ':'') . $customer['first'];
		}
		//
		// Build the display_name
		//
		$type = (isset($args['type']))?$args['type']:$customer['type'];
		$company = (isset($args['company']))?$args['company']:$customer['company'];
		if( $type == 2 && $company != '' ) {
			$format = 'company';
			if( isset($customer['display_name_format']) && $customer['display_name_format'] != '' ) {
				$format = $customer['display_name_format'];
			} elseif( !isset($settings['display-name-business-format']) 
				|| $settings['display-name-business-format'] == 'company' ) {
				$format = 'company';
			} elseif( $settings['display-name-business-format'] != '' ) {
				$format = $settings['display-name-business-format'];
			}
			// Format the display_name
			if( $format == 'company' ) {
				$args['display_name'] = $company;
				$args['sort_name'] = $company;
			} 
			elseif( $format == 'company - person' ) {
				$args['display_name'] = $company . ($person_name!=''?' - ' . $person_name:'');
				$args['sort_name'] = $company;
			} 
			elseif( $format == 'person - company' ) {
				$args['display_name'] = ($person_name!=''?$person_name . ' - ':'') . $company;
				$args['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $company;
			} 
			elseif( $format == 'company [person]' ) {
				$args['display_name'] = $company . ($person_name!=''?' [' . $person_name . ']':'');
				$args['sort_name'] = $company;
			} 
			elseif( $format == 'person [company]' ) {
				$args['display_name'] = ($person_name!=''?$person_name . ' [' . $company . ']':$company);
				$args['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $company;
			}
		} else {
			$args['display_name'] = $person_name;
			$args['sort_name'] = $sort_person_name;
		}
	}
   
   	if( isset($args['display_name']) && $args['display_name'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['display_name']);
	}

	//
	// Update the customer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.customer', 
		$args['customer_id'], $args, 0x06);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2009', 'msg'=>'Unable to updated customer', 'err'=>$rc['err']));
	}

	//
	// Hook into other modules when updating status incase orders or other items should be changed
	//
	if( isset($args['status']) && $args['status'] != '' ) {
		foreach($modules as $module => $m) {
			list($pkg, $mod) = explode('.', $module);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'customerStatusUpdate');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], array(
					'customer_id'=>$args['customer_id'], 
					'status'=>$args['status'],
					));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2007', 'msg'=>'Unable to update customer status.', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Hook into other modules when updating name incase orders or other items should be changed
	//
	if( isset($args['display_name']) && $args['display_name'] != '' ) {
		foreach($modules as $module => $m) {
			list($pkg, $mod) = explode('.', $module);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'customerNameUpdate');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], array(
					'customer_id'=>$args['customer_id'], 
					'display_name'=>$args['display_name'],
					));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2372', 'msg'=>'Unable to update customer name.', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Check for subscriptions
	//
	if( isset($args['subscriptions']) || isset($args['unsubscriptions']) ) {
		// incase one of the args isn't set, setup with blank arrays
		if( !isset($args['subscriptions']) ) { $args['subscriptions'] = array(); }
		if( !isset($args['unsubscriptions']) ) { $args['unsubscriptions'] = array(); }
		ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'updateCustomerSubscriptions');
		$rc = ciniki_subscriptions_updateCustomerSubscriptions($ciniki, $args['business_id'], 
			$args['customer_id'], $args['subscriptions'], $args['unsubscriptions']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Update the customer categories
	//
	if( isset($args['customer_categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['business_id'],
			'ciniki_customer_tags', 'ciniki_customer_history',
			'customer_id', $args['customer_id'], 10, $args['customer_categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}

	//
	// Update the customer tags
	//
	if( isset($args['customer_tags']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['business_id'],
			'ciniki_customer_tags', 'ciniki_customer_history',
			'customer_id', $args['customer_id'], 20, $args['customer_tags']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}

	//
	// Update the member categories
	//
	if( isset($args['member_categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['business_id'],
			'ciniki_customer_tags', 'ciniki_customer_history',
			'customer_id', $args['customer_id'], 40, $args['member_categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}

	//
	// Update the dealer categories
	//
	if( isset($args['dealer_categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['business_id'],
			'ciniki_customer_tags', 'ciniki_customer_history',
			'customer_id', $args['customer_id'], 60, $args['dealer_categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}

	//
	// Update the distributor categories
	//
	if( isset($args['distributor_categories']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
		$rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.customers', 'tag', $args['business_id'],
			'ciniki_customer_tags', 'ciniki_customer_history',
			'customer_id', $args['customer_id'], 80, $args['distributor_categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}

	//
	// Update the short_description
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
	$rc = ciniki_customers_customerUpdateShortDescription($ciniki, $args['business_id'], $args['customer_id'], 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
		return $rc;
	}

	//
	// Update the season membership
	//
	if( ($modules['ciniki.customers']['flags']&0x02000000) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateSeasons');
		$rc = ciniki_customers_customerUpdateSeasons($ciniki, $args['business_id'], $args['customer_id']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
			return $rc;
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$rsp = array('stat'=>'ok');

//
//	FIXME: Switch UI to use response for add/update to fill out details
//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
//	$rc = ciniki_customers__customerDetails($ciniki, $args['business_id'], $args['customer_id'], array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
//	if( $rc['stat'] == 'ok' && isset($rc['details']) ) {
//		$rsp['customer_details'] = $rc['details'];
//	}

	return $rsp;
}
?>
