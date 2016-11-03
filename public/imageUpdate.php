<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to add the customers to.
// name:                The name of the customers.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_customers_imageUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer Image'), 
        'image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Website Flags'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
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
    $rc = ciniki_customers_checkAccess($ciniki, $args['business_id'], 'ciniki.customers.imageUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Check the permalink
    //
    if( isset($args['name']) ) {
        if( $args['name'] == '' ) {
            //
            // Get the existing image details
            //
            $strsql = "SELECT uuid FROM ciniki_customer_images "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['customer_image_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['item']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.98', 'msg'=>'Member image not found'));
            }
            $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($rc['item']['uuid'])));
        } else {
            $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($args['name'])));
        }
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink FROM ciniki_customer_images "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['customer_image_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'image');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.99', 'msg'=>'You already have an image with this name, please choose another name'));
        }
    }

    //
    // Update the customer image
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.customers.image', $args['customer_image_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the short_description
    //
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerUpdateShortDescription');
//  $rc = ciniki_customers_customerUpdateShortDescription($ciniki, $args['business_id'], $args['customer_id'], 0x07);
//  if( $rc['stat'] != 'ok' ) {
//      return $rc;
//  }

    return array('stat'=>'ok');
}
?>
