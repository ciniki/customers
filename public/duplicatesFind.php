<?php
//
// Description
// -----------
// This method will return a list of potential duplicates
// 
// Returns
// -------
//
function ciniki_customers_duplicatesFind($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'checkAccess');
    $ac = ciniki_customers_checkAccess($ciniki, $args['tnid'], 'ciniki.customers.duplicatesFind', 0);
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Search for any potential duplicate customers
    //
    $strsql = "SELECT CONCAT_WS('-', c1.id, c2.id) AS match_id, c1.id AS c1_id, "
        . "c1.display_name AS c1_display_name, "
        . "c2.id AS c2_id, c2.display_name AS c2_display_name "
        . "FROM ciniki_customers AS c1, ciniki_customers AS c2 "
        . "WHERE c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND c1.id < c2.id "
//      . "AND ((c1.first = c2.first and c1.last = c2.last) OR (c1.first = c2.last and c1.last = c2.first)) "
        . "AND ("
            . "((c1.type = 1 OR c1.type = 10 OR c1.type = 21 OR c1.type = 22 OR c1.type = 31 OR c1.type = 32) "
                . "AND (c2.type = 1 OR c2.type = 10 OR c1.type = 21 OR c1.type = 22 OR c1.type = 31 OR c1.type = 32)"
                . "AND ("
                    . "(c1.first <> '' AND c1.last <> '' AND soundex(c1.first) = soundex(c2.first) AND soundex(c1.last) = soundex(c2.last)) "
                    . "OR (c1.first <> '' AND c1.last <> '' AND soundex(c1.first) = soundex(c2.last) AND soundex(c1.last) = soundex(c2.first)) "
                . ")) "
            . "OR ((c1.type = 2 OR c1.type = 20 OR c1.type = 30) AND (c2.type = 2 OR c2.type = 20 OR c2.type = 30) AND soundex(c1.company) = soundex(c2.company)) "
            . ") "
//        . "AND ((c1.type = 1 AND soundex(c1.first) = soundex(c2.first) and soundex(c1.last) = soundex(c2.last)) "
//            . "OR (c1.type = 1 AND soundex(c1.first) = soundex(c2.last) and soundex(c1.last) = soundex(c2.first)) "
//            . "OR (c1.type = 2 AND soundex(c1.company) = soundex(c2.company)) "
//            . "OR (soundex(c1.display_name) = soundex(c2.display_name)) "
//            . ") "
        . "ORDER BY c1_display_name, c1.id "
        . "";
        error_log($strsql);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'matches', 'fname'=>'match_id', 'name'=>'match',
            'fields'=>array('c1_id', 'c1_display_name', 'c2_id', 'c2_display_name'),
            ),
//      array('container'=>'duplicates', 'fname'=>'c2_id', 'name'=>'customer',
//          'fields'=>array('id'=>'c2_id', 'first'=>'c2_first', 'last'=>'c2_last'),
//          ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // When customers match multiple records, the second and third matches will appear in the
    // list later on by themselves, and should be removed
    //
//  foreach($rc['customers'] as $cnum => $customer) {
//      foreach($customer['customer']['duplicates'] as $dnum => $dup) {
            // because the list is sorted, we need to only start from where we are and carry forward
//          for($i=$cnum;$i<count($rc['customers']);$i++) {
//              if( $dup['customer']['id'] == $rc['customers'][$i]['customer']['id'] ) {
//                  unset($rc['customers'][$i]);
//              }
//          }
//      }
//  }

    return $rc;
}
?>
