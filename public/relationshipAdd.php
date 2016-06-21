<?php
//
// Description
// -----------
// This method will add a new relationship between customers to the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the customer belongs to.
// customer_id:         The ID of the customer to add the relationship to.
// relationship_type:   The type of relationship between the customer_id and
//                      the related_id.  
//
//                      If the type is passed as a negative number, the 
//                      relationship is reversed before storing in the database.
//
//                      10 - business owner (related_id is the business owned)
//                      -10 - owned by
//                      11 - business partner
//                      30 - friend
//                      40 - relative
//                      41 - parent
//                      -41 - child
//                      42 - step-parent
//                      -42 - step-child
//                      43 - parent-in-law
//                      -43 - child-in-law
//                      44 - spouse
//                      45 - sibling
//                      46 - step-sibling
//                      47 - sibling-in-law
//
// related_id:          The ID of the related customer.
//
// date_started:        (optional) The date the relationship started.
// date_ended:          (optional) The date the relationship ended.
// notes:               (optional) Any notes about the relationship.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_customers_relationshipAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'relationship_type'=>array('required'=>'yes', 'blank'=>'no', 
            'validlist'=>array('10','-10','11','30', '40', '41', '-41', '42', '-42', '43', '-43', '44', '45', '46', '47'), 
            'name'=>'Relationship Type'), 
        'related_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Related Customer'), 
        'date_started'=>array('required'=>'no', 'type'=>'date', 'default'=>'', 'blank'=>'yes', 'name'=>'Date Started'), 
        'date_ended'=>array('required'=>'no', 'type'=>'date', 'default'=>'', 'blank'=>'yes', 'name'=>'Date Ended'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.relationshipAdd', $args['customer_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check if relationship should be reversed
    //
    if( $args['relationship_type'] < 0 ) {
        $args['relationship_type'] = abs($args['relationship_type']);
        $id = $args['customer_id'];
        $args['customer_id'] = $args['related_id'];
        $args['related_id'] = $id;
    }

    //
    // Add the relationship
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.customers.relationship', $args, 0x07);
}
?>
