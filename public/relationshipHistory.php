<?php
//
// Description
// -----------
// This method will return the history for a field that is part of a relationship.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the history for.
// relationship_id:     The ID of the relationship to get the history for.
// field:               The field to get the history for.
//
//                      relationship_type
//                      related_id
//                      date_started
//                      date_ended
//                      notes
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_customers_relationshipHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'relationship_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Relationship'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $rc = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.relationshipHistory', $args['relationship_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'date_started'
        || $args['field'] == 'date_ended' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'], 
            'ciniki_customer_relationships', $args['relationship_id'], $args['field'], 'date');
    }

    //
    // The related_id field requires it's own special query, because the history should come
    // from either the customer_id or related_id depending on which the customer_id is set
    // to.  This means any responses where the customer_id is the history are filtered out,
    // and only the other customer_id is returned
    //
    if( $args['field'] == 'related_id' ) {
        //
        // Check if different customer types have been enabled
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'getCustomerTypes');
        $rc = ciniki_customers_getCustomerTypes($ciniki, $args['tnid']); 
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        $types = $rc['types'];

        //
        // Get the history log from ciniki_core_change_logs table.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
        $datetime_format = ciniki_users_datetimeFormat($ciniki);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

        $strsql = "SELECT user_id, DATE_FORMAT(log_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as date, "
            . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(log_date) as DECIMAL(12,0)) as age, "
            . "new_value as value, "
            . "";
        if( count($types) > 0 ) {
            // If there are customer types defined, choose the right name for the customer
            // This is required here to be able to sort properly
            $strsql .= "CASE ciniki_customers.type ";
            foreach($types as $tid => $type) {
                $strsql .= "WHEN " . ciniki_core_dbQuote($ciniki, $tid) . " THEN ";
                if( $type['detail_value'] == 'tenant' ) {
                    $strsql .= " ciniki_customers.company ";
                } else {
                    $strsql .= "CONCAT_WS(' ', first, last) ";
                }
            }
            $strsql .= "ELSE CONCAT_WS(' ', first, last) END AS fkidstr_value ";
        } else {
            // Default to a person
            $strsql .= "CONCAT_WS(' ', first, last) AS fkidstr_value ";
        }
        $strsql .= " FROM ciniki_customer_history "
            . "LEFT JOIN ciniki_customers ON (ciniki_customer_history.new_value = ciniki_customers.id "
                . " AND ciniki_customers.tnid ='" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "') "
            . " WHERE ciniki_customer_history.tnid ='" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . " AND table_name = 'ciniki_customer_relationships' "
            . " AND table_key = '" . ciniki_core_dbQuote($ciniki, $args['relationship_id']) . "' "
            . " AND ((table_field = 'related_id' AND new_value != '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "') "
                . "OR (table_field = 'customer_id' AND new_value != '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "')) "
            . " ORDER BY log_date DESC "
            . " ";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQueryPlusDisplayNames');
        $rc = ciniki_core_dbRspQueryPlusDisplayNames($ciniki, $strsql, 'ciniki.customers', 'history', 'action', array('stat'=>'ok', 'history'=>array(), 'users'=>array()));
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $args['tnid'], 'ciniki_customer_relationships', $args['relationship_id'], $args['field']);
}
?>
