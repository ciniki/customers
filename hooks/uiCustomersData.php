<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get customers for.
//
// Returns
// -------
//
function ciniki_customers_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'mysql');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    
    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Get any reminders for a customer
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x080000) && isset($args['customer_id']) ) {
        //
        // Get the list of reminders
        //
        $strsql = "SELECT ciniki_customer_reminders.id, "
            . "ciniki_customer_reminders.customer_id, "
            . "ciniki_customer_reminders.reminder_date, "
            . "DATE_FORMAT(ciniki_customer_reminders.reminder_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS reminder_date_display, "
            . "ciniki_customer_reminders.flags, "
            . "ciniki_customer_reminders.repeat_type, "
            . "ciniki_customer_reminders.repeat_interval, "
            . "ciniki_customer_reminders.repeat_end, "
            . "ciniki_customer_reminders.description, "
            . "ciniki_customer_reminders.category, "
            . "ciniki_customer_reminders.email_time, "
            . "IF((flags&0x01)=0, 'none', TIME_FORMAT(email_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "')) AS email_dt_display, "
            . "ciniki_customer_reminders.email_subject, "
            . "ciniki_customer_reminders.email_html "
            . "FROM ciniki_customer_reminders "
            . "WHERE ciniki_customer_reminders.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_customer_reminders.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "ORDER BY reminder_date ASC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'reminders', 'fname'=>'id', 
                'fields'=>array('id', 'customer_id', 'reminder_date', 'reminder_date_display', 'flags', 
                    'repeat_type', 'repeat_interval', 'repeat_end', 'description', 'category', 
                    'email_time', 'email_dt_display', 'email_subject', 'email_html'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['reminders']) && count($rc['reminders']) > 0 ) {
            $sections['ciniki.customers.reminders']['data'] = $rc['reminders'];
        }
        $rsp['tabs'][] = array(
            'id' => 'ciniki.customers.reminders',
            'label' => 'Reminders',
            'priority' => 2000,
            'sections' => array(
                'ciniki.customers.reminders' => array(
                    'label' => 'Reminders',
                    'type' => 'simplegrid', 
                    'priority' => 5000,
                    'num_cols' => 3,
                    'headerValues' => array('Date', 'Description', 'Email'),
                    'cellClasses' => array('multiline', '', ''),
                    'noData' => 'No reminders setup',
                    'addTxt' => 'Add Reminder',
                    'addApp' => array('app'=>'ciniki.customers.reminders', 'args'=>array('customer_id'=>$args['customer_id'], 'source'=>'\'customer\'')),
                    'editApp' => array('app'=>'ciniki.customers.reminders', 'args'=>array('reminder_id'=>'d.id;', 'source'=>'\'customer\'')),
                    'cellValues' => array(
                        '0' => "M.multiline(d.reminder_date_display, d.category)",
                        '1' => "d.description",
                        '2' => "d.email_dt_display",
                        ),
                    'data' => (isset($rc['reminders']) ? $rc['reminders'] : array()),
                    ),
                ),
            );
    }

    //
    // Check for membership information
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08) && isset($args['customer_id']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'productsPurchased');
        $rc = ciniki_customers_productsPurchased($ciniki, $tnid, array('customer_id'=>$args['customer_id']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.435', 'msg'=>'Unable to get membership info.', 'err'=>$rc['err']));
        }
        $rsp['tabs'][] = array(
            'id' => 'ciniki.customers.products',
            'label' => 'Membership',
            'priority' => 2000,
            'sections' => array(
                'ciniki.customers.productscurrent' => array(
                    'label' => 'Membership',
                    'type' => 'simplegrid', 
                    'priority' => 3000,
                    'num_cols' => 2,
                    'headerValues' => array('Product', 'Expiry'),
                    'cellClasses' => array('', '', ''),
                    'noData' => 'No products purchased',
                    'addTxt' => 'Add Membership',
                    'addApp' => array('app'=>'ciniki.customers.products', 'args'=>array('purchase_id'=>'0', 'customer_id'=>$args['customer_id'], 'source'=>'\'customer\'')),
                    'editApp' => array('app'=>'ciniki.customers.products', 'args'=>array('purchase_id'=>'d.id;', 'source'=>'\'customer\'')),
                    'cellValues' => array(
                        '0' => "d.name",
                        '1' => "d.expiry_display",
                        ),
                    'data' => (isset($rc['membership_details']) ? $rc['membership_details'] : array()),
                    
                    ),
                'ciniki.customers.productshistory' => array(
                    'label' => 'History',
                    'type' => 'simplegrid', 
                    'priority' => 2000,
                    'num_cols' => 2,
                    'headerValues' => array('Product', 'Expired'),
                    'cellClasses' => array('', '', ''),
                    'noData' => 'No products purchased',
//                    'addTxt' => 'Add Membership Product',
//                    'addApp' => array('app'=>'ciniki.customers.reminders', 'args'=>array('customer_id'=>$args['customer_id'], 'source'=>'\'customer\'')),
//                    'editApp' => array('app'=>'ciniki.customers.reminders', 'args'=>array('reminder_id'=>'d.id;', 'source'=>'\'customer\'')),
                    'editApp' => array('app'=>'ciniki.customers.products', 'args'=>array('purchase_id'=>'d.id;', 'source'=>'\'customer\'')),
                    'cellValues' => array(
                        '0' => "d.name",
                        '1' => "d.expired",
                        ),
                    'data' => (isset($rc['history']) ? $rc['history'] : array()),
                    
                    ),
                ),
            );
    }

    return $rsp;
}
?>
