<?php
//
// Description
// -----------
// Return the list of available field refs for ciniki.forms module.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_hooks_formDefaultsLoad(&$ciniki, $tnid, $args) {
   
    if( !isset($args['form']) ) {
        return array('stat'=>'ok');
    }
    $form = $args['form'];
  
    //
    // Process the customer if specified
    //
    if( isset($form['customer_id']) && $form['customer_id'] != '' && $form['customer_id'] > 0 ) {
        //
        // Load the customer
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerLoad');
        $rc = ciniki_customers_customerLoad($ciniki, $tnid, $form['customer_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.521', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        $customer = $rc['customer'];

        //
        // Load the refs
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'formFieldRefs');
        $rc = ciniki_customers_hooks_formFieldRefs($ciniki, $tnid, array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $refs = $rc['refs'];        

        //
        // Process customer data into refs
        //
        if( isset($customer['emails']) ) {
            if( isset($customer['emails'][0]['id']) ) {
                $customer['primary_email_id'] = $customer['emails'][0]['id'];
                $customer['primary_email'] = $customer['emails'][0]['address'];
            }
            if( isset($customer['emails'][1]['id']) ) {
                $customer['secondary_email_id'] = $customer['emails'][1]['id'];
                $customer['secondary_email'] = $customer['emails'][1]['address'];
            }
            // Ignore other emails
        }
        if( isset($customer['phones']) ) {
            foreach($customer['phones'] as $phone) {
                if( $phone['phone_label'] == 'Cell' && $phone['phone_number'] != '' ) {
                    $customer['phone_cell_id'] = $phone['id'];
                    $customer['phone_cell'] = $phone['phone_number'];
                } elseif( $phone['phone_label'] == 'Home' && $phone['phone_number'] != '' ) {
                    $customer['phone_home_id'] = $phone['id'];
                    $customer['phone_home'] = $phone['phone_number'];
                } elseif( $phone['phone_label'] == 'Work' && $phone['phone_number'] != '' ) {
                    $customer['phone_work_id'] = $phone['id'];
                    $customer['phone_work'] = $phone['phone_number'];
                } elseif( $phone['phone_label'] == 'Fax' && $phone['phone_number'] != '' ) {
                    $customer['phone_fax_id'] = $phone['id'];
                    $customer['phone_fax'] = $phone['phone_number'];
                }
                // Ignore other phones
            }
        }
        if( isset($customer['addresses']) ) {
            foreach($customer['addresses'] as $address) {
                if( ($address['flags']&0x01) == 0x01 && !isset($customer['shipping_address']) ) {
                     $customer['shipping_address'] = $address;
                } 
                if( ($address['flags']&0x02) == 0x02 && !isset($customer['billing_address']) ) {
                     $customer['billing_address'] = $address;
                } 
                if( ($address['flags']&0x04) == 0x04 && !isset($customer['mailing_address']) ) {
                     $customer['mailing_address'] = $address;
                }
            }
        }
        if( isset($customer['links']) ) {
            foreach($customer['links'] as $link) {
                if( $link['name'] == 'Website' ) {
                    $customer['website'] = $link['url'];
                } 
                elseif( $link['name'] == 'Facebook' ) {
                    $customer['facebook'] = $link['url'];
                }
                elseif( $link['name'] == 'Instagram' ) {
                    $customer['instagram'] = $link['url'];
                }
                elseif( $link['name'] == 'Twitter' ) {
                    $customer['twitter'] = $link['url'];
                }
            }
        }
    
        if( isset($form['sections']) ) {
            foreach($form['sections'] as $sid => $section) {
                if( isset($section['fields']) ) {
                    foreach($section['fields'] as $fid => $field) {
                        //
                        // Check if field ref is for customer and if the reference exists
                        //
                        if( isset($field['field_ref']) && $field['field_ref'] != '' 
                            && preg_match("/^ciniki\.customers\.customer\.([^\.]+)/", $field['field_ref'], $m)
                            && isset($refs[$field['field_ref']])
                            ) {

                            if( isset($customer[$m[1]]) ) {
                                $form['sections'][$sid]['fields'][$fid]['default'] = $customer[$m[1]];
                            }
                        }
                    }
                }
            }
        } 
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
