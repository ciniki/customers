<?php
//
// Description
// -----------
// Calculate the next reminder date for a reminder.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_customers_reminderRepeatNextDate(&$ciniki, $tnid, $reminder, $dt) {

    if( $reminder['repeat_type'] == 10 ) {
        $dt->add(new DateInterval('P' . $reminder['repeat_interval'] . 'D'));
    } elseif( $reminder['repeat_type'] == 20 ) {
        $dt->add(new DateInterval('P' . $reminder['repeat_interval'] . 'W'));
    } elseif( $reminder['repeat_type'] == 30 ) {
        $year = $dt->format('Y');
        $month = $dt->format('m') + $reminder['repeat_interval'];
        $day = $dt->format('d');
        // Make sure the month hasn't rolled over into new year
        if( $month > 12 ) {
            $year += floor($month/12);
            $month = ($month % 12);
        }
        // Setup new date as first of month and noon to get the number of days in month
        $dt = new DateTime("$year-$month-01 12:00:00", new DateTimezone($intl_timezone));
        if( $dt->format('t') < $day ) {
            $day = $dt->format('t');
        }
        // Setup new UTC date and time to send next email reminder
        $dt = new DateTime("$year-$month-$day " .$reminder['email_time'], new DateTimezone($intl_timezone));
    } elseif( $reminder['repeat_type'] == 31 ) {
        $dayofweek = $dt->format('w');
        $weekofmonth = ceil($dt->format('d')/7);
        $year = $dt->format('Y');
        $month = $dt->format('m') + $reminder['repeat_interval'];
        // Make sure the month hasn't rolled over into new year
        if( $month > 12 ) {
            $year += floor($month/12);
            $month = ($month % 12);
        }
        // Setup new date as first of month
        $dt = new DateTime("$year-$month-01 12:00:00", new DateTimezone($intl_timezone));
        // Adjust to proper day of week
        if( $dt->format('w') < $dayofweek ) {
            $dt->add(new DateInterval('P' . ($dayofweek - $dt->format('w')) . 'D'));
        } elseif( $dt->format('w') > $dayofweek ) {
            $dt->add(new DateInterval('P' . (7 + $dayofweek - $dt->format('w')) . 'D'));
        }
        // Adjust to proper week of month
        if( ceil($dt->format('d')/7) < $weekofmonth ) {
            $dt->add(new DateInterval('P' . (($weekofmonth - ceil($dt->format('d')/7))*7) . 'W'));
        }
    } elseif( $reminder['repeat_type'] == 40 ) {
        $dt->add(new DateInterval('P' . $reminder['repeat_interval'] . 'Y'));
    }

    return array('stat'=>'ok', 'next_dt'=>$dt);
}
?>
