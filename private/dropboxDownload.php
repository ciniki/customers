<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/dropbox/lib/Dropbox/autoload.php');
use \Dropbox as dbx;

function ciniki_customers_dropboxDownload(&$ciniki, $business_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkModuleFlags');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxParseRTFToText');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxOpenTXT');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'insertFromDropbox');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'dropboxDownloadImages');

    //
    // Check to make sure the dropbox flag is enabled for this business
    //
    if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x0800000000) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3551', 'msg'=>'Dropbox integration not enabled'));
    }

    //
    // Get the settings for customers
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'business_id', $business_id, 'ciniki.customers', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']['dropbox-customerprofiles']) || $rc['settings']['dropbox-customerprofiles'] == '') {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3552', 'msg'=>'Dropbox customers not setup.'));
    }
    $customers_dir = $rc['settings']['dropbox-customerprofiles'];
    if( $customers_dir[0] != '/' ) {
        $customers_dir = '/' . $customers_dir;
    }
    rtrim($customers_dir, '/');
    $dropbox_cursor = null;
    if( isset($rc['settings']['dropbox-cursor']) && $rc['settings']['dropbox-cursor'] != '') {
        $dropbox_cursor = $rc['settings']['dropbox-cursor'];
    }

    //
    // Check if we should ignore the old cursor and start from scratch
    //
    if( isset($ciniki['config']['ciniki.customers']['ignore.cursor']) 
        && ($ciniki['config']['ciniki.customers']['ignore.cursor'] == 1 || $ciniki['config']['ciniki.customers']['ignore.cursor'] == 'yes') 
        ) {
        $dropbox_cursor = null;
    }

    //
    // Get the settings for dropbox
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_business_details', 'business_id', $business_id, 'ciniki.businesses', 'settings', 'apis');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']['apis-dropbox-access-token']) || $rc['settings']['apis-dropbox-access-token'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3553', 'msg'=>'Dropbox not configured.'));
    }
    $access_token = $rc['settings']['apis-dropbox-access-token'];

    $client = new dbx\Client($access_token, 'Ciniki');

    //
    // Get the latest changes from Dropbox
    //
    error_log("checking: " . $customers_dir);
    $rc = $client->getDelta($dropbox_cursor, $customers_dir);
    if( !isset($rc['entries']) ) {
        // Nothing to update, return
        error_log("nothing\n");
        return array('stat'=>'ok');
    }
    // If there is more
    $dropbox_cursor = $rc['cursor'];
    if( count($rc['entries']) == 0 && $rc['has_more'] == 1 ) {
        error_log('delta again');
        $rc = $client->getDelta($dropbox_cursor, $customers_dir);
        if( !isset($rc['entries']) ) {
            // Nothing to update, return
            error_log("nothing\n");
            return array('stat'=>'ok');
        }
    }
    $updates = array();
    $new_dropbox_cursor = $rc['cursor'];
    $entries = $rc['entries'];
    foreach($entries as $entry) {
        if( !isset($entry[1]) || count($entry[1]) == 0 ) {
            print "skipping";
            continue;
        }
        //
        // Entries look like:
        //      [0] => /website/dealers/rivett-andrew/primary_image/img_0610.jpg
        //      [1] => Array
        //          (
        //              [rev] => 230d1f249e
        //              [thumb_exists] => 1
        //              [path] => /website/dealers/rivett-andrew/primary_image/IMG_0610.jpg
        //              [is_dir] =>
        //              [client_mtime] => Wed, 15 Jan 2014 13:37:06 +0000
        //              [icon] => page_white_picture
        //              [read_only] =>
        //              [modifier] =>
        //              [bytes] => 114219
        //              [modified] => Sat, 14 Mar 2015 19:23:45 +0000
        //              [size] => 111.5 KB
        //              [root] => dropbox
        //              [mime_type] => image/jpeg
        //              [revision] => 35
        //          )
        //
        // Check for a match in the specified directory and path matches valid path list information
        //
        
        if( preg_match("#^($customers_dir)/([^/]+)/(primary_image|primary-image|images|description)/(.*)$#", $entry[0], $matches) ) {
            $customer_eid = $matches[2];
            if( !isset($updates[$customer_eid]) ) {
                $updates[$customer_eid] = array();
            }
            if( isset($matches[3]) ) {
                switch($matches[3]) {
                    case 'primary-image':
                    case 'primary_image': 
                        if( $entry[1]['mime_type'] == 'image/jpeg' || $matches[4] == 'caption.rtf' || $matches[4] == 'caption.txt' ) {
                            $updates[$customer_eid][$matches[3]] = array(
                                'path'=>$entry[1]['path'], 
                                'modified'=>$entry[1]['modified'], 
                                'mime_type'=>$entry[1]['mime_type'],
                                ); 
                            break;
                        }
                    case 'description': 
                        $updates[$customer_eid][$matches[3]] = array(
                            'path'=>$entry[1]['path'], 
                            'modified'=>$entry[1]['modified'], 
                            'mime_type'=>$entry[1]['mime_type'],
                            ); 
                        break;
                    case 'images': 
                        if( !isset($updates[$customer_eid][$matches[3]]) ) {
                            $updates[$customer_eid][$matches[3]] = array();
                        }
                        $updates[$customer_eid][$matches[3]][] = array(
                            'path'=>$entry[0], 
                            'filename'=>$entry[1]['path'],
                            'modified'=>$entry[1]['modified'], 
                            'mime_type'=>$entry[1]['mime_type'],
                            ); 
                        break;
                }
            }
        }
    }

    //
    // Update Ciniki
    //
    foreach($updates as $customer_eid => $customer) {
        error_log("Updating: " . $customer_eid);

        //  
        // Turn off autocommit
        //  
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
        
        //
        // Lookup the customer in the customers
        //
        $strsql = "SELECT id, primary_image_id, primary_image_caption, full_bio "
            . "FROM ciniki_customers "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND eid = '" . ciniki_core_dbQuote($ciniki, $customer_eid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
            return $rc;
        }

        //
        // Add customer
        //
        if( !isset($rc['customer']) && $rc['num_rows'] == 0 ) {
            error_log("Customer $customer_eid does not exist");
            continue;
            //
            // If customer need to be added from dropbox, then add setting variable to control if adds are allowed
            //
        } 
    
        //
        // Load the full customer
        //
        else {
            $customer_id = $rc['customer']['id'];

            $ciniki_customer = $rc['customer'];

            //
            // Get the list of images
            //
            $strsql = "SELECT id, "
                . "permalink, name, webflags, image_id, description "
                . "FROM ciniki_customer_images "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_customer_images.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
                array('container'=>'images', 'fname'=>'id', 'fields'=>array('id', 'permalink', 'name', 'webflags', 'image_id', 'description')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['images']) ) {
                $ciniki_customer['images'] = $rc['images'];
            } else {
                $ciniki_customer['images'] = array();
            }
        }

        //
        // Decide what needs to be updated
        //
        $update_args = array();

        //
        // Go through the updated items
        //
        foreach($customer as $field => $details) {
            if( ($field == 'primary_image' || $field == 'primary-image') && $details['mime_type'] == 'image/jpeg' ) {
                $rc = ciniki_images_insertFromDropbox($ciniki, $business_id, $ciniki['session']['user']['id'], $client, $details['path'], 1, '', '', 'no');
                if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return $rc;
                }
                if( $rc['id'] != $ciniki_customer['primary_image_id'] ) {
                    $update_args['primary_image_id'] = $rc['id'];
                }
            }
            elseif( ($field == 'primary_image' || $field == 'primary-image') && $details['mime_type'] == 'application/rtf' ) {
                $rc = ciniki_core_dropboxParseRTFToText($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_customer['primary_image_caption'] ) {
                    $update_args['primary_image_caption'] = $rc['content'];
                }
            }
            elseif( ($field == 'primary_image' || $field == 'primary-image') && $details['mime_type'] == 'text/plain' ) {
                $rc = ciniki_core_dropboxOpenTXT($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_customer['primary_image_caption'] ) {
                    $update_args['primary_image_caption'] = $rc['content'];
                }
            }
            elseif( $field == 'description' && $details['mime_type'] == 'application/rtf' ) {
                $rc = ciniki_core_dropboxParseRTFToText($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_customer['full_bio'] ) {
                    $update_args['full_bio'] = $rc['content'];
                }
            }
            elseif( $field == 'description' && $details['mime_type'] == 'text/plain' ) {
                $rc = ciniki_core_dropboxOpenTXT($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_customer['full_bio'] ) {
                    $update_args['full_bio'] = $rc['content'];
                }
            }
            elseif( $field == 'images' ) {
                $rc = ciniki_customers_dropboxDownloadImages($ciniki, $business_id, $client, $ciniki_customer, $details);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                    return $rc;
                }
            }
        }

        //
        // Update the customer
        //
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.customers.customer', $customer_id, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
                return $rc;
            }
        }

        //
        // Update the web index if enabled
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
        ciniki_core_hookExec($ciniki, $business_id, 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.customer', 'object_id'=>$customer_id));
        ciniki_core_hookExec($ciniki, $business_id, 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.members', 'object_id'=>$customer_id));
        ciniki_core_hookExec($ciniki, $business_id, 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.dealers', 'object_id'=>$customer_id));
        ciniki_core_hookExec($ciniki, $business_id, 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.customers.distributors', 'object_id'=>$customer_id));

        //  
        // Commit the changes
        //  
        $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.customers');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
    }

    //
    // Update the dropbox cursor
    //
    $strsql = "INSERT INTO ciniki_customer_settings (business_id, detail_key, detail_value, date_added, last_updated) "
        . "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "'"
        . ", '" . ciniki_core_dbQuote($ciniki, 'dropbox-cursor') . "'"
        . ", '" . ciniki_core_dbQuote($ciniki, $new_dropbox_cursor) . "'"
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
        . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $new_dropbox_cursor) . "' "
        . ", last_updated = UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.customers');
        return $rc;
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.customers', 'ciniki_customer_history', $business_id, 
        2, 'ciniki_customer_settings', 'dropbox-cursor', 'detail_value', $new_dropbox_cursor);
    $ciniki['syncqueue'][] = array('push'=>'ciniki.customers.setting', 'args'=>array('id'=>'dropbox-cursor'));

    return array('stat'=>'ok');
}
?>
