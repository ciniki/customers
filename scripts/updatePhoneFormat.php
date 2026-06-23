<?php
//
// Description
// -----------
// This script will setup the timeslot_id for each registration even
// when NOT a split class.
//

//
// Initialize Ciniki by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'phoneFormat');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleListKeywordsMake');

//
// Get the titles
//
$strsql = "SELECT phones.id, "
    . "phones.tnid, "
    . "phones.phone_number "
    . "FROM ciniki_customer_phones AS phones "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
    array('container'=>'phones', 'fname'=>'id', 'fields'=>array('id', 'tnid', 'phone_number')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.577', 'msg'=>'Unable to load customer phones', 'err'=>$rc['err']));
}
$phones = isset($rc['phones']) ? $rc['phones'] : array();

$num_good = 0;
$num_reformat = 0;
$num_bad = 0;
foreach($phones as $phone) {
  
    $update_args = [];
    if( $phone['phone_number'] != '' ) {
        $rc = ciniki_tenants_hooks_phoneFormat($ciniki, $phone['tnid'], ['number'=>$phone['phone_number']]);
        if( $rc['stat'] != 'ok' ) {
            print_r($rc);
            exit;
        }
        if( $rc['formatted_number'] != $phone['phone_number'] ) {
            $update_args['phone_number'] = $rc['formatted_number'];
            print "Reformat: {$phone['phone_number']} -> {$rc['formatted_number']}\n";
            $num_reformat++;
        } elseif( !preg_match("/[0-9][0-9][0-9]-[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]/", $rc['formatted_number']) ) {
            print "Invalid format: {$rc['formatted_number']}\n";
            $num_bad++;
        } else {
            $num_good++;
        }
    }
    if( count($update_args) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $phone['tnid'], 'ciniki.customers.phone', $phone['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.customers.578', 'msg'=>'Unable to update the phone', 'err'=>$rc['err']));
        }
    }
}

print "Good: {$num_good}\n";
print "Bad: {$num_bad}\n";
print "Reformat: {$num_reformat}\n";
?>
