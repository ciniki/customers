<?php
//
// Description
// -----------
// This function will automerge a row of customer information from an import.
//
// Info
// ----
// Status: defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
//
//
function ciniki_customers_autoMerge($ciniki, $business_id, $row) {

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/checkAccess.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');

	//
	// Check user has access to update business information the business (is a business owner)
	//
	$ac = ciniki_users_checkAccess($ciniki, $business_id);
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}




	//
	// NOTE: Count the number of matching fields.  If all fields match, and customer_id matches, then same record (duplicate)
	//

	//print "autoMerge Row: \n";
	//print_r($row);

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'99', 'msg'=>'Function not complete'));



	//
	// *note* If all information is already in database, the record is duplicate.
	//

	//
	// The header and row information need to be transformed into 
	// a useable structure for the autoMerge.
	// 
	// The structure should look like:
	// $customer
	//		_ ['first'] - The first name for the customer.
	//			__ ['col'] - The column the piece of data is from in the $row variable
	//			__ ['data'] - The value of the field
	// $addresses
	//		_ ['home'] - The index to the addresses list
	//      	__ ['address1'] - The first address line for the address
	//				___ ['col'] - The column the data came from
	//				___ ['data'] - The value of the field.
	//
	$customer = array();
	$num_addresses = 0;
	$addresses = array();
	$num_emails = 0;
	$emails = array();
	$num_notes = 0;
	$notes = array();
	$num_phones = 0;
	$phones = array();
	foreach($header as $col => $header_col) {
		print "Analyzing: $col: " . $header_col['data_txt'] . "\n";
		//
		// Check for any customer.tablename, and setup the $customer array
		//
		if( preg_match('/^customers\.([^\.]+)$/', $header_col['data_txt'], $matches) ) {
			if( isset($row[$col]) && isset($row[$col]['data_txt']) && $row[$col]['data_txt'] != '' ) {
				$customer[$matches[1]] = array('col'=>$col, 'data'=>$row[$col]['data_txt']);
			} else {
				// No data
				$customer[$matches[1]] = array('col'=>$col, 'data'=>'');
			}
		}

		//
		// Check for address information.  It should be in the format
		// customer_addresses.(home|work).(address1|address2|city|province|postal)
		// eg: customer_addresses.home.address1
		//
		if( preg_match('/^customer_addresses\.(.*)\.(.*)$/', $header_col['data_txt'], $matches) ) {
			// Create a record for the address type if it does not exist
			if( !isset($addresses[$matches[1]]) ) {
				$addresses[$matches[1]] = array('flags'=>array('col'=>'0', 'data'=>0));
				$num_addresses++;
				// Setup flags field
				if( $matches[1] == 'home' ) {
					$addresses[$matches[1]]['flags']['data'] |= 0x10;
				} elseif( $matches[1] == 'work' ) {
					$addresses[$matches[1]]['flags']['data'] |= 0x20;
				}
			}
			// Create a record for the field if it does not exist.
			if( !isset($addresses[$matches[1]][$matches[2]]) ) {
				$addresses[$matches[1]][$matches[2]] = array('col'=>$col, 'data'=>'');
			}
			if( isset($row[$col]) && isset($row[$col]['data_txt']) && $row[$col]['data_txt'] != '' ) {
				$addresses[$matches[1]][$matches[2]]['data'] = $row[$col]['data_txt'];
			}
		}

		//
		// Look for customer phone numbers
		//
		if( preg_match('/^customer_phones\.(.*)\.(.*)$/', $header_col['data_txt'], $matches) ) {
			// Create a record for the address type if it does not exist
			if( !isset($phones[$matches[1]]) ) {
				$phones[$matches[1]] = array(
					'flags'=>array('col'=>'0', 'data'=>'0'),
					'number'=>array('col'=>'0', 'data'=>''),
					'country'=>array('col'=>'0', 'data'=>''),
					'area'=>array('col'=>'0', 'data'=>''),
					'exchange'=>array('col'=>'0', 'data'=>''),
					'line'=>array('col'=>'0', 'data'=>''),
					'extension'=>array('col'=>'0', 'data'=>'')
					);
				$num_phones++;
				if( $matches[1] == 'home' ) {
					$phones[$matches[1]]['flags'] |= 0x01;
				} elseif( $matches[1] == 'work' ) {
					$phones[$matches[1]]['flags'] |= 0x02;
				} elseif( $matches[1] == 'mobile' ) {
					$phones[$matches[1]]['flags'] |= 0x04;
				} elseif( $matches[1] == 'fax' ) {
					$phones[$matches[1]]['flags'] |= 0x08;
				}
				$phones[$matches[1]]['flags'] |= 0x10;	// Set to active phone
			}
			// If recognize a number format, split into pieces
			if( $matches[2] == 'number' && isset($row[$col]) && isset($row[$col]['data_txt']) && $row[$col]['data_txt'] != '' ) {
				if( preg_match('/(1|)(-|\()([0-9]{1-4})(\)|\s*-\s*|\s+)([0-9]{2,3})(\s*-\s*|_)([0-9]{4,7})(|x.*|\s.*)$/', 
					$row[$col]['data_txt'], $phone_number) ) {
					$phones[$matches[1]]['number']['data'] = $row[$col]['data_txt'];
					$phones[$matches[1]]['number']['col'] = $col;
					$phones[$matches[1]]['country']['data'] = $matches[1];
					$phones[$matches[1]]['area']['data'] = $matches[3];
					$phones[$matches[1]]['exchange']['data'] = $matches[5];
					$phones[$matches[1]]['lines']['data'] = $matches[7];
					// If there was something for extension
					if( $matches[8] != '' ) {
						$phones[$matches[1]]['extension']['data'] = $matches[8];
						$phones[$matches[1]]['extension']['col'] = $col;
					}
				}
			} elseif( isset($row[$col]) && isset($row[$col]['data_txt']) && $row[$col]['data_txt'] != '' ) {
				$phones[$matches[1]][$matches[2]]['data'] = $row[$col]['data_txt'];
				$phones[$matches[1]][$matches[2]]['col'] = $col;
			} else {
				$phones[$matches[1]][$matches[2]]['col'] = $col;
				$phones[$matches[1]][$matches[2]]['data'] = '';
			}
		}


		//
		// Look for any customer emails
		//
		if( preg_match('/^customer_emails\.([^\.]+)$/', $header_col['data_txt'], $matches) ) {
			if( isset($row[$col]) && isset($row[$col]['data_txt']) && $row[$col]['data_txt'] != '' ) {
				$emails[$matches[1]] = array('col'=>$col, 'type'=>$matches[1], 'data'=>$row[$col]['data_txt']);
				$num_emails++;
			} else {
				$emails[$matches[1]] = array('col'=>$col, 'type'=>$matches[1], 'data'=>'');
			}
		}

		//
		// Look for any notes
		//
		if( preg_match('/^customer_notes\.(.*)$/', $header_col['data_txt'], $matches) ) {
			if( isset($row[$col]) && isset($row[$col]['data_txt']) && $row[$col]['data_txt'] != '' ) {
				$notes[$matches[1]] = array('col'=>$col, 'data'=>$row[$col]['data_txt']);
				$num_notes++;
			} else {
				$notes[$matches[1]] = array('note'=>array('col'=>$col, 'data'=>''));
			}
		}

	}

	//
	// Row must have a first or last name be merged.  Without that information,
	// the row cannot be added.
	//
	if( $customer['first'] == '' && $customer['last'] == '' ) {
		//
		// Loop through fields, and update status to ignored, no identifiable info
		//
		$strsql = "UPDATE import_data "
			. "SET status = 3, import_result = 0 "
			. "WHERE import_id = '" . ciniki_core_dbQuote($ciniki, $import_id) . "' "
			. "AND row = '" . ciniki_core_dbQuote($ciniki, $row[0]['row']) . "' "
			. "AND type = 3 AND status = 3";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'imports');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		//
		// Return OK, because the function performed, 
		// FIXME: Add in autoMerge return codes
		//
		return array('stat'=>'ok', 'status'=>'No ID found', 'customer_id'=>'0');
	}


	//
	// Check to see if the customer exists in the database
	//
	$strsql = "SELECT id, prefix, first, middle, last, suffix, company, department, title "
		. "FROM customers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND first = '" . ciniki_core_dbQuote($ciniki, $customer['first']['data']) . "' "
		. "AND last = '" . ciniki_core_dbQuote($ciniki, $customer['last']['data']) . "' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$db_customers = ciniki_core_dbHashQuery($ciniki, $strsql, 'customers', 'customer');
	if( $db_customers['stat'] != 'ok' ) {
		return $db_customers;
	}


	//
	// Check to see if emails exist 
	//
	if( $num_emails > 0 ) {
		$strsql = "SELECT customer.id, customer.business_id, customer_emails.id, customer.business_id, flags, email "
			. "FROM customer_emails, customers "
			. "WHERE (";
		$separator = "";
		foreach($emails as $email_key => $email_obj) {
			if( $email_obj['data'] != '' ) {
				$strsql .= "$separator customer_emails.email = '" . ciniki_core_dbQuote($ciniki, $email_obj['data']) . "' ";
				$separator = "OR";
			}
		}
		$strsql .= ")"
			. " AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. " AND customer_emails.customer_id = customers.id ";
		if( $separator == '' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'82', 'msg'=>'Internal Error', 'pmsg'=>'Mismatch in number of emails found'));
		}
		$db_emails = ciniki_core_dbHashQuery($ciniki, $strsql, 'customers', 'customer');
		if( $db_emails['stat'] != 'ok' ) {
			return $db_emails;
		}
	}


	//
	// If first, last and email do not exist, then add the user
	//
	if( $db_customers['num_rows'] == 0 && 
		($num_emails == 0 || ($num_emails > 1 && $db_emails['num_rows'] == 0)) ) {
		//
		// Add the customer record
		//
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsertAutoMerge.php');
		$rc = ciniki_core_dbInsertAutoMerge($ciniki, 
			array('prefix', 'first', 'middle', 'last', 'suffix', 'company', 'department', 'title'),
			&$customer,
			'INSERT INTO customers (business_id, status, ',
			"date_added, last_updated) VALUES ('$business_id', 1, ",
			'UTC_TIMESTAMP(), UTC_TIMESTAMP())', &$row);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'79', 'msg'=>'Internal Error', 'pmsg'=>'Unable to get new id for new customer'));
		} 
		$customer_id = $rc['insert_id'];


		//
		// Add the customer emails if they exist
		//
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
		foreach($emails as $email_key => $email_obj) {
			if( $email_obj['data'] != '' ) {
				$flags = 0;
				// FIXME: Add all types of emails here
				if( $email_obj['type'] == 'home' ) {
					$flags |= 0x10;
				} elseif( $email_obj['type'] == 'work' ) {
					$flags |= 0x20;
				} elseif( $email_obj['type'] == 'other' ) {
					$flags |= 0x80;
				}
				$strsql = "INSERT INTO customer_emails (customer_id, flags, email, date_added, last_updated) "
					. "VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', $flags, '" . ciniki_core_dbQuote($ciniki, $email_obj['data']) . "' "
					. ", UTC_TIMESTAMP(), UTC_TIMESTAMP())";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'customers');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($email_obj['col']) && $email_obj['col'] > 0 ) {
					$row[$email_obj['col']]['new_status'] = 2;
					$row[$email_obj['col']]['new_import_result'] = 10;
				}
			} else {
				if( isset($email_obj['col']) && $email_obj['col'] > 0 ) {
					$row[$email_obj['col']]['new_status'] = 2;
					$row[$email_obj['col']]['new_import_result'] = 12;
				}
			}
		}

		//
		// Add the customer phones
		//
		foreach($phones as $phone_key => $phone_obj) {
			if( $phone_obj['exchange']['data'] != '' && $phone_obj['line']['data'] != '' ) {
				$rc = ciniki_core_dbInsertAutoMerge($ciniki, 
					array('flags', 'country', 'area', 'exchange', 'line', 'extension'),
					&$phones[$phone_key],
					'INSERT INTO customer_phones (customer_id, ',
					"date_added, last_updated) VALUES ($customer_id, ",
					'UTC_TIMESTAMP(), UTC_TIMESTAMP())', &$row);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			} else {
				// If phone number is blank, then mark as ignored.
				print "PHONE: "; print_r($phone_obj);
				if( isset($phone_obj['number']['col']) && $phone_obj['number']['col'] > 0 ) {
					print "SETTING: " . $phone_obj['number']['col'] . "\n";
					$row[$phone_obj['number']['col']]['new_status'] = 2;
					$row[$phone_obj['number']['col']]['new_import_result'] = 12;
				}
				if( isset($phone_obj['extension']['col']) && $phone_obj['extension']['col'] > 0 ) {
					$row[$phone_obj['extension']['col']]['new_status'] = 2;
					$row[$phone_obj['extension']['col']]['new_import_result'] = 12;
				}
			}

		}


		//
		// Add the customer addresses
		//
		foreach($addresses as $addr_key => $addr_obj) {
			$rc = ciniki_core_dbInsertAutoMerge($ciniki, 
				array('flags', 'address1', 'address2', 'city', 'province', 'postal', 'country'),
				&$addresses[$addr_key],
				'INSERT INTO customer_addresses (customer_id, ',
				"date_added, last_updated) VALUES ($customer_id, ",
				'UTC_TIMESTAMP(), UTC_TIMESTAMP())', &$row);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}

		//
		// Add the notes
		//
		foreach($notes as $note_key => $note_obj) {
			if( $note_obj['note']['data'] != '' ) {
				$rc = ciniki_core_dbInsertAutoMerge($ciniki, 
					array('note'),
					&$notes[$note_key],
					'INSERT INTO customer_notes (customer_id, user_id, ',
					"date_added, last_updated) VALUES ($customer_id, " . $ciniki['session']['user']['id'] . ", ",
					'UTC_TIMESTAMP(), UTC_TIMESTAMP())', &$row);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			} else {
				if( isset($note_obj['note']['col']) && $note_obj['note']['col'] > 0 ) {
					$row[$note_obj['note']['col']]['new_status'] = 2;
					$row[$note_obj['note']['col']]['new_import_result'] = 12;
				}
			}
		}

		print "Row: "; print_r($row);
	}

//	print "Customer: "; print_r($customer);		
//	print "Addresses: "; print_r($addresses);		
	print "Phones: "; print_r($phones);		
//	print "Emails: "; print_r($emails);
//	print "Notes: "; print_r($notes);


	//
	// Check if addresses exist
	//

	//
	// If all fields from import match the same customer in the database, then
	// mark all the fields as duplicate, and be done.


	//
	// Now try to find the edge cases
	//


	return array('stat'=>'ok');
}
?>
