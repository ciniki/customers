<?php
//
// Description
// -----------
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_customerUpdateName(&$ciniki, $tnid, $customer, $customer_id, $args) {

    //
    // Get the settings for customer module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getSettings');
    $rc = ciniki_customers_getSettings($ciniki, $tnid); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $settings = $rc['settings'];

    if( isset($args['display_name_format']) ) {
        $customer['display_name_format'] = $args['display_name_format'];
    }

    $rsp = array('stat'=>'ok');

    //
    // Build the persons name
    //
    $space = '';
    $person_name = '';
    $sort_person_name = '';
    if( isset($settings['display-name-callsign-format']) && $settings['display-name-callsign-format'] == 'beforedash' ) {
        if( isset($args['callsign']) && $args['callsign'] != '' ) {
            $person_name .= $args['callsign'] . ' -';
            $sort_person_name .= $args['callsign'];
        } elseif( !isset($args['callsign']) && $customer['callsign'] != '' ) {
            $person_name .= $customer['callsign'] . ' -';
            $sort_person_name .= $customer['callsign'];
        }
    }
    if( $space == '' && $person_name != '' ) { $space = ' '; }
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

    if( !isset($settings['display-name-callsign-format']) || $settings['display-name-callsign-format'] == 'afterdash' ) {
        if( isset($args['callsign']) && $args['callsign'] != '' ) {
            $person_name .= ($person_name != '' ? ' - ' : '') . $args['callsign'];
            $sort_person_name .= ($sort_person_name != '' ? ', ' : '') . $args['callsign'];
        } elseif( !isset($args['callsign']) ) {
            $person_name .= ($person_name != '' ? ' - ' : '') . $customer['callsign'];
            $sort_person_name .= ($sort_person_name != '' ? ', ' : '') . $customer['callsign'];
        }
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
            $rsp['display_name'] = $company;
            $rsp['sort_name'] = $company;
        } 
        elseif( $format == 'company - person' ) {
            $rsp['display_name'] = $company . ($person_name!=''?' - ' . $person_name:'');
            $rsp['sort_name'] = $company;
        } 
        elseif( $format == 'person - company' ) {
            $rsp['display_name'] = ($person_name!=''?$person_name . ' - ':'') . $company;
            $rsp['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $company;
        } 
        elseif( $format == 'company [person]' ) {
            $rsp['display_name'] = $company . ($person_name!=''?' [' . $person_name . ']':'');
            $rsp['sort_name'] = $company;
        } 
        elseif( $format == 'person [company]' ) {
            $rsp['display_name'] = ($person_name!=''?$person_name . ' [' . $company . ']':$company);
            $rsp['sort_name'] = ($sort_person_name!=''?$sort_person_name.', ':'') . $company;
        }
    } 
    //
    // Override formatting options for IFB customers
    //
    elseif( $type == 10 || $type == 21 || $type == 22 || $type == 31 || $type == 32 ) {
        $rsp['display_name'] = $person_name;
        $rsp['sort_name'] = $sort_person_name;
    }
    elseif( $type == 20 || $type == 30 ) {
        $rsp['display_name'] = $company;
        $rsp['sort_name'] = $company;
    } 
    else {
        $rsp['display_name'] = $person_name;
        $rsp['sort_name'] = $sort_person_name;
    }
   
    if( isset($args['callsign']) && $args['callsign'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $rsp['permalink'] = ciniki_core_makePermalink($ciniki, $args['callsign']);
    } elseif( !isset($args['callsign']) && isset($customer['callsign']) && $customer['callsign'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $rsp['permalink'] = ciniki_core_makePermalink($ciniki, $customer['callsign']);
    } elseif( isset($rsp['display_name']) && $rsp['display_name'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $rsp['permalink'] = ciniki_core_makePermalink($ciniki, $rsp['display_name']);
    } else {
        $rsp['permalink'] = '';
    }

    return $rsp;
}
?>
