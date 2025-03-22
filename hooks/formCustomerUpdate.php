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
function ciniki_customers_hooks_formCustomerUpdate(&$ciniki, $tnid, $args) {
   
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

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
                    $customer['website_id'] = $link['id'];
                    $customer['website'] = $link['url'];
                } 
                elseif( $link['name'] == 'Facebook' ) {
                    $customer['facebook_id'] = $link['id'];
                    $customer['facebook'] = $link['url'];
                }
                elseif( $link['name'] == 'Instagram' ) {
                    $customer['instagram_id'] = $link['id'];
                    $customer['instagram'] = $link['url'];
                }
                elseif( $link['name'] == 'Twitter' ) {
                    $customer['twitter_id'] = $link['id'];
                    $customer['twitter'] = $link['url'];
                }
            }
        }
   
        $form_addresses = 0;
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
                            if( $m[1] == 'mailing_address' ) {
                                $form_addresses |= 0x04;
                            }
                            if( $m[1] == 'shipping_address' ) {
                                $form_addresses |= 0x01;
                            }
                            if( !isset($customer[$m[1]]) || $customer[$m[1]] != $field['value'] ) {
                                $customer["{$m[1]}_new"] = $field['value'];
                            }
                        }
                    }
                }
            }
        } 

        //
        // Update the customer fields
        //
        $update_args = [];
        foreach(['first', 'middle', 'last', 'company'] as $t) {
            if( isset($customer["{$t}_new"]) && $customer[$t] != $customer["{$t}_new"] ) {
                $update_args[$t] = $customer["{$t}_new"];
            }
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $customer['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.567', 'msg'=>'Unable to update customer', 'err'=>$rc['err']));
            }
        }

        //
        // Update the customer emails
        //
        foreach(['primary'=>'Primary', 'secondary'=>'Secondary'] as $t => $label) {
            if( isset($customer["{$t}_email_new"]) ) {
                // Update existing email record
                if( isset($customer["{$t}_email_id"]) ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.email', $customer["{$t}_email_id"], [
                        'email' => $customer["{$t}_email_new"],
                        ], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.565', 'msg'=>'Unable to update the email', 'err'=>$rc['err']));
                    }
                } 
                // Create new email record
                elseif( $customer["{$t}_email_new"] != '' ) {
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.email', [
                        'customer_id' => $form['customer_id'],
                        'email' => $customer["{$t}_email_new"],
                        ], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.570', 'msg'=>'Unable to update the email', 'err'=>$rc['err']));
                    }
                }
            }
        }
        //
        // Update the customer phones
        //
        foreach(['cell'=>'Cell', 'home'=>'Home', 'work'=>'Work', 'fax'=>'Fax'] as $t => $label) {
            if( isset($customer["phone_{$t}_new"]) ) {
                // Update existing phone record
                if( isset($customer["phone_{$t}_id"]) ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.phone', $customer["phone_{$t}_id"], [
                        'phone_number' => $customer["phone_{$t}_new"],
                        ], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.568', 'msg'=>'Unable to update the phone', 'err'=>$rc['err']));
                    }
                } 
                // Create new phone record
                elseif( $customer["phone_{$t}_new"] != '' ) {
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.phone', [
                        'customer_id' => $form['customer_id'],
                        'phone_label' => $label,
                        'phone_number' => $customer["phone_{$t}_new"],
                        'flags' => 0,
                        ], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.571', 'msg'=>'Unable to update the phone', 'err'=>$rc['err']));
                    }
                }
            }
        }

        if( isset($customer['mailing_address_new']) 
            && $customer['mailing_address_new']['address1'] == ''
            && $customer['mailing_address_new']['city'] == ''
            && $customer['mailing_address_new']['province'] == ''
            && $customer['mailing_address_new']['postal'] == ''
            && $customer['mailing_address_new']['country'] == ''
            ) {
            $customer['mailing_address_new']['action'] = 'empty';
        } else {
            $customer['mailing_address_new']['action'] = '';
        }
        if( isset($customer['shipping_address_new']) 
            && $customer['shipping_address_new']['address1'] == ''
            && $customer['shipping_address_new']['city'] == ''
            && $customer['shipping_address_new']['province'] == ''
            && $customer['shipping_address_new']['postal'] == ''
            && $customer['shipping_address_new']['country'] == ''
            ) {
            $customer['shipping_address_new']['action'] = 'empty';
        } else {
            $customer['shipping_address_new']['action'] = '';
        }

        //
        // Update addresses
        //
        if( $form_addresses == 0x05 ) { // Mailing and Shipping address exist on the form
            //
            // Check if both addresses new and the same
            //
            if( isset($customer['mailing_address_new']) 
                && isset($customer['shipping_address_new']) 
                && $customer['mailing_address_new'] == $customer['shipping_address_new']
                ) {
                // Old mail == old ship
                if( isset($customer['mailing_address']['id'])
                    && isset($customer['shipping_address']['id'])
                    && $customer['mailing_address']['id'] == $customer['shipping_address']['id']
                    ) {
                    $customer['mailing_address_new']['action'] = 'update';
                    unset($customer['shipping_address_new']);
                } 
                // No old mail or old ship
                elseif( !isset($customer['mailing_address']['id'])
                    && !isset($customer['shipping_address']['id'])
                    ) {
                    $customer['mailing_address_new']['action'] = 'add';
                    $customer['mailing_address_new']['flags'] |= 0x05;
                }
                // Maybe old mail, maybe old ship
                else {
                    if( isset($customer['mailing_address']['id']) ) {
                        $customer['mailing_address_new']['action'] = 'update';
                    } else {
                        $customer['mailing_address_new']['action'] = 'add';
                    }
                    if( isset($customer['shipping_address']['id']) ) {
                        $customer['shipping_address_new']['action'] = 'update';
                    } else {
                        $customer['shipping_address_new']['action'] = 'add';
                    }
                }
            }
            //
            // Both addresses specified and different
            //
            else {
                if( isset($customer['mailing_address_new']) ) {
                    if( isset($customer['mailing_address_new']['action'])
                        && $customer['mailing_address_new']['action'] == 'empty' 
                        ) {
                        if( isset($customer['mailing_address']['id']) ) {
                            $customer['mailing_address']['action'] = 'delete';
                        }
                    } elseif( isset($customer['mailing_address']['id']) ) {
                        $customer['mailing_address_new']['action'] = 'update';
                    } else {
                        $customer['mailing_address_new']['action'] = 'add';
                        $customer['mailing_address_new']['flags'] = 0x04;
                    }
                }
                if( isset($customer['shipping_address_new']) ) {
                    if( isset($customer['shipping_address_new']['action'])
                        && $customer['shipping_address_new']['action'] == 'empty' 
                        ) {
                        if( isset($customer['shipping_address']['id']) ) {
                            $customer['shipping_address']['action'] = 'delete';
                        }
                    } elseif( isset($customer['shipping_address']['id']) ) {
                        $customer['shipping_address_new']['action'] = 'update';
                    } else {
                        $customer['shipping_address_new']['action'] = 'add';
                        $customer['shipping_address_new']['flags'] = 0x01;
                    }
                }
            }
        } 
        // Mailing address has changed, and it's the only address on the form
        elseif( isset($customer["mailing_address_new"]) && $form_addresses == 0x04 
            && $customer['mailing_address_new']['action'] != 'empty' 
            ) {
            if( isset($customer['mailing_address']['id']) ) {
                $customer['mailing_address_new']['action'] = 'update';
            } elseif( !isset($customer['mailing_address']['id']) ) {
                if( isset($customer['shipping_address']['id']) ) {
                    $customer['mailing_address_new']['flags'] = 0x04;
                }
                $customer['mailing_address_new']['action'] = 'add';
            }
        }
        // Shipping address has changed, and it's the only address on the form
        elseif( isset($customer["shipping_address_new"]) && $form_addresses == 0x01 
            && $customer['shipping_address_new']['action'] != 'empty' 
            ) {
            if( isset($customer['shipping_address']['id']) ) {
                $customer['shipping_address_new']['action'] = 'update';
            } elseif( !isset($customer['shipping_address']['id']) ) {
                if( isset($customer['mailing_address']['id']) ) {
                    $customer['shipping_address_new']['flags'] = 0x01;
                }
                $customer['shipping_address_new']['action'] = 'add';
            }
        }

        //
        // Update mailing address
        //
        if( isset($customer['mailing_address_new']['action']) ) {
            if( $customer['mailing_address_new']['action'] == 'update' ) {
                $update_args = [];
                foreach(['address1', 'address2', 'city', 'province', 'postal', 'country'] as $field) {
                    if( !isset($customer['mailing_address'][$field]) 
                        || $customer['mailing_address'][$field] != $customer['mailing_address_new'][$field] 
                        ) {
                        $update_args[$field] = $customer['mailing_address_new'][$field];
                    }
                }
                if( count($update_args) > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $customer["mailing_address"]["id"], $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.568', 'msg'=>'Unable to update the phone', 'err'=>$rc['err']));
                    }
                }
            } elseif( $customer['mailing_address_new']['action'] == 'add' ) {
                $customer['mailing_address_new']['customer_id'] = $form['customer_id'];
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $customer['mailing_address_new'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.572', 'msg'=>'Unable to add mailing address', 'err'=>$rc['err']));
                }
            }
        }
        //
        // Update shipping address
        //
        if( isset($customer['shipping_address_new']['action']) ) {
            if( $customer['shipping_address_new']['action'] == 'update' ) {
                $update_args = [];
                foreach(['address1', 'address2', 'city', 'province', 'postal', 'country'] as $field) {
                    if( !isset($customer['shipping_address'][$field]) 
                        || $customer['shipping_address'][$field] != $customer['shipping_address_new'][$field] 
                        ) {
                        $update_args[$field] = $customer['shipping_address_new'][$field];
                    }
                }
                if( count($update_args) > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.address', $customer["shipping_address"]["id"], $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.568', 'msg'=>'Unable to update the phone', 'err'=>$rc['err']));
                    }
                }
            } elseif( $customer['shipping_address_new']['action'] == 'add' ) {
                $customer['shipping_address_new']['customer_id'] = $form['customer_id'];
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', $customer['shipping_address_new'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.572', 'msg'=>'Unable to add shipping address', 'err'=>$rc['err']));
                }
            }
        }

        //
        // Update links
        //
        foreach(['website'=>'Website', 'facebook'=>'Facebook', 'instagram'=>'Instagram', 'twitter'=>'Twitter'] as $t => $label) {
            if( isset($customer["{$t}_new"]) ) {
                // Update existing link record
                if( isset($customer["{$t}_id"]) ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.link', $customer["{$t}_id"], [
                        'url' => $customer["{$t}_new"],
                        ], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.569', 'msg'=>'Unable to update the link', 'err'=>$rc['err']));
                    }
                } 
                // Create new link record
                elseif( $customer["{$t}_new"] != '' ) {
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.link', [
                        'customer_id' => $form['customer_id'],
                        'name' => $label,
                        'url' => $customer["{$t}_new"],
                        'flags' => 0,
                        ], 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.566', 'msg'=>'Unable to update the link', 'err'=>$rc['err']));
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
