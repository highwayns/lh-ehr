<?php
/**
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 * Copyright (C) 2015-2017 Terry Hill <teryhill@librehealth.io>
 * Copyright (C) 2005-2006, 2013 Rod Roark <rod@sunsetsystems.com>
 *
 * This program is used to find un-used appointments in the Patient Portal, 
 * allowing the patient to select there own appointment.
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreHealth EHR
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @author Rod Roark <rod@sunsetsystems.com>
 * @author Terry Hill <teryhill@librehealth.io>
 * @link http://librehealth.io
 *
 * Please help the overall project by sending changes you make to the authors and to the OpenEMR community.
 *
 */

//continue session
session_start();
//

//landing page definition -- where to go if something goes wrong
$landingpage = "index.php?site=".$_SESSION['site_id'];
//

// kick out if patient not authenticated
if ( isset($_SESSION['pid']) && isset($_SESSION['patient_portal_onsite']) ) {
  $pid = $_SESSION['pid'];
} else {
  session_destroy();
  header('Location: '.$landingpage.'&w');
  exit;
}
//

$ignoreAuth = 1;

 include_once("../interface/globals.php");
 include_once("$srcdir/patient.inc");

 // Exit if the modify calendar for portal flag is not set
  if (!($GLOBALS['portal_onsite_appt_modify'])) {
   echo htmlspecialchars( xl('You are not authorized to schedule appointments.'),ENT_NOQUOTES);
   exit;
 } 

 $input_catid = $_REQUEST['catid'];

 // Record an event into the slots array for a specified day.
function doOneDay($catid, $udate, $starttime, $duration, $prefcatid)
{
  global $slots, $slotsecs, $slotstime, $slotbase, $slotcount, $input_catid;
  $udate = strtotime($starttime, $udate);
    if ($udate < $slotstime) {
        return;
    }

  $i = (int) ($udate / $slotsecs) - $slotbase;
  $iend = (int) (($duration + $slotsecs - 1) / $slotsecs) + $i;
    if ($iend > $slotcount) {
        $iend = $slotcount;
    }

    if ($iend <= $i) {
        $iend = $i + 1;
    }

  for (; $i < $iend; ++$i) {
   if ($catid == 2) {        // in office
    // If a category ID was specified when this popup was invoked, then select
    // only IN events with a matching preferred category or with no preferred
    // category; other IN events are to be treated as OUT events.
    if ($input_catid) {
                if ($prefcatid == $input_catid || !$prefcatid) {
      $slots[$i] |= 1;
                } else {
      $slots[$i] |= 2;
                }
    } else {
     $slots[$i] |= 1;
    }
    break; // ignore any positive duration for IN
   } else if ($catid == 3) { // out of office
    $slots[$i] |= 2;
    break; // ignore any positive duration for OUT
   } else { // all other events reserve time
    $slots[$i] |= 4;
   }
  }
 }

 // seconds per time slot
 $slotsecs = $GLOBALS['calendar_interval'] * 60;

 $catslots = 1;
 if ($input_catid) {
  $srow = sqlQuery("SELECT pc_duration FROM libreehr_postcalendar_categories WHERE pc_catid = ?", array($input_catid));
  if ($srow['pc_duration']) {
      $catslots = ceil($srow['pc_duration'] / $slotsecs);
 }
 }

 $info_msg = "";

 $searchdays = 7; // default to a 1-week lookahead
if ($_REQUEST['searchdays']) {
    $searchdays = $_REQUEST['searchdays'];
}

 // Get a start date.
if ($_REQUEST['startdate'] && preg_match(
    "/(\d\d\d\d)\D*(\d\d)\D*(\d\d)/",
    $_REQUEST['startdate'],
    $matches
)) {
  $sdate = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
 } else {
  $sdate = date("Y-m-d");
 }
 $first_dow = $GLOBALS['portal_first_dow'];
 $last_dow = $GLOBALS['portal_last_dow'];
 $start_days = $GLOBALS['portal_start_days'];
 $sdate = date('Y-m-d' , strtotime( " +" . $start_days ." days"));
 $chck_sdate = date('Ymd' , strtotime( " +" . $start_days ." days"));

 // Get an end date - actually the date after the end date.
 preg_match("/(\d\d\d\d)\D*(\d\d)\D*(\d\d)/", $sdate, $matches);
    $edate = date(
        "Y-m-d",
        mktime(0, 0, 0, $matches[2], $matches[3] + $searchdays, $matches[1])
    );

 // compute starting time slot number and number of slots.
 $slotstime = strtotime("$sdate 00:00:00");
 $slotetime = strtotime("$edate 00:00:00");
 $slotbase  = (int) ($slotstime / $slotsecs);
 $slotcount = (int) ($slotetime / $slotsecs) - $slotbase;

    if ($slotcount <= 0 || $slotcount > 100000) {
        die("Invalid date range.");
    }

 $slotsperday = (int) (60 * 60 * 24 / $slotsecs);

 // If we have a provider, search.
 //
 if ($_REQUEST['providerid']) {
  $providerid = $_REQUEST['providerid'];

  // Create and initialize the slot array. Values are bit-mapped:
  //   bit 0 = in-office occurs here
  //   bit 1 = out-of-office occurs here
  //   bit 2 = reserved
  // So, values may range from 0 to 7.
  //
  $slots = array_pad(array(), $slotcount, 0);

  // Note there is no need to sort the query results.
  $query = "SELECT pc_eventDate, pc_endDate, pc_startTime, pc_duration, " .
   "pc_recurrtype, pc_recurrspec, pc_alldayevent, pc_catid, pc_prefcatid, pc_title " .
   "FROM libreehr_postcalendar_events " .
   "WHERE pc_aid = ? AND " .
   "((pc_endDate >= ? AND pc_eventDate < ?) OR " .
   "(pc_endDate = '0000-00-00' AND pc_eventDate >= ? AND pc_eventDate < ?))";
  $res = sqlStatement($query, array($providerid, $sdate, $edate, $sdate, $edate));
//  print_r($res);

  while ($row = sqlFetchArray($res)) {
   $thistime = strtotime($row['pc_eventDate'] . " 00:00:00");
   if ($row['pc_recurrtype']) {

    preg_match('/"event_repeat_freq_type";s:1:"(\d)"/', $row['pc_recurrspec'], $matches);
    $repeattype = $matches[1];

    preg_match('/"event_repeat_freq";s:1:"(\d)"/', $row['pc_recurrspec'], $matches);
    $repeatfreq = $matches[1];
    if ($row['pc_recurrtype'] == 2) {
     // Repeat type is 2 so frequency comes from event_repeat_on_freq.
     preg_match('/"event_repeat_on_freq";s:1:"(\d)"/', $row['pc_recurrspec'], $matches);
     $repeatfreq = $matches[1];
    }

                if (! $repeatfreq) {
                    $repeatfreq = 1;
                }

    preg_match('/"event_repeat_on_num";s:1:"(\d)"/', $row['pc_recurrspec'], $matches);
    $my_repeat_on_num = $matches[1];

    preg_match('/"event_repeat_on_day";s:1:"(\d)"/', $row['pc_recurrspec'], $matches);
    $my_repeat_on_day = $matches[1];

    $endtime = strtotime($row['pc_endDate'] . " 00:00:00") + (24 * 60 * 60);
                if ($endtime > $slotetime) {
                    $endtime = $slotetime;
                }
    
    $repeatix = 0;
    while ($thistime < $endtime) {

     // Skip the event if a repeat frequency > 1 was specified and this is
     // not the desired occurrence.
     if (! $repeatix) {
                        doOneDay(
                            $row['pc_catid'],
                            $thistime,
                            $row['pc_startTime'],
                            $row['pc_duration'],
                            $row['pc_prefcatid']
                        );
                    }

                    if (++$repeatix >= $repeatfreq) {
                        $repeatix = 0;
     }

     $adate = getdate($thistime);

     if ($row['pc_recurrtype'] == 2) {
      // Need to skip to nth or last weekday of the next month.
      $adate['mon'] += 1;
      if ($adate['mon'] > 12) {
       $adate['year'] += 1;
       $adate['mon'] -= 12;
      }
      if ($my_repeat_on_num < 5) { // not last
       $adate['mday'] = 1;
       $dow = jddayofweek(cal_to_jd(CAL_GREGORIAN, $adate['mon'], $adate['mday'], $adate['year']));
        if ($dow > $my_repeat_on_day) {
            $dow -= 7;
        }

       $adate['mday'] += ($my_repeat_on_num - 1) * 7 + $my_repeat_on_day - $dow;
      } else { // last weekday of month
       $adate['mday'] = cal_days_in_month(CAL_GREGORIAN, $adate['mon'], $adate['year']);
       $dow = jddayofweek(cal_to_jd(CAL_GREGORIAN, $adate['mon'], $adate['mday'], $adate['year']));
        if ($dow < $my_repeat_on_day) {
            $dow += 7;
        }

       $adate['mday'] += $my_repeat_on_day - $dow;
      }
     } // end recurrtype 2

     else { // recurrtype 1

     if ($repeattype == 0)        { // daily
      $adate['mday'] += 1;
     } else if ($repeattype == 1) { // weekly
      $adate['mday'] += 7;
     } else if ($repeattype == 2) { // monthly
      $adate['mon'] += 1;
     } else if ($repeattype == 3) { // yearly
      $adate['year'] += 1;
     } else if ($repeattype == 4) { // work days
      if ($adate['wday'] == 5) {      // if friday, skip to monday
       $adate['mday'] += 3;
      } else if ($adate['wday'] == 6) { // saturday should not happen
       $adate['mday'] += 2;
      } else {
       $adate['mday'] += 1;
      }
     } else if ($repeattype == 5) { // monday
      $adate['mday'] += 7;
     } else if ($repeattype == 6) { // tuesday
      $adate['mday'] += 7;
     } else if ($repeattype == 7) { // wednesday
      $adate['mday'] += 7;
     } else if ($repeattype == 8) { // thursday
      $adate['mday'] += 7;
     } else if ($repeattype == 9) { // friday
      $adate['mday'] += 7;
     } else {
       die("Invalid repeat type '$repeattype'");
     }

     } // end recurrtype 1

     $thistime = mktime(0, 0, 0, $adate['mon'], $adate['mday'], $adate['year']);
    }
   } else {
                doOneDay(
                    $row['pc_catid'],
                    $thistime,
                    $row['pc_startTime'],
                    $row['pc_duration'],
                    $row['pc_prefcatid']
                );
   }
  }

  // Mark all slots reserved where the provider is not in-office.
  // Actually we could do this in the display loop instead.
  $inoffice = false;
  for ($i = 0; $i < $slotcount; ++$i) {
            if (($i % $slotsperday) == 0) {
                $inoffice = false;
            }

            if ($slots[$i] & 1) {
                $inoffice = true;
  }

            if ($slots[$i] & 2) {
                $inoffice = false;
            }

            if (! $inoffice) {
                $slots[$i] |= 4;
            }
 }
}
?>
<html>
<head>
<?php html_header_show(); ?>
<title><?php echo xlt('Find Available Appointments'); ?></title>
<link rel="stylesheet" href='<?php echo $css_header ?>' type='text/css'>

<link href="<?php echo $GLOBALS['standard_js_path']; ?>/bootstrap-3-3-4/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<?php if ($_SESSION['language_direction'] == 'rtl') { ?>
    <link href="<?php echo $GLOBALS['standard_js_path']; ?>/bootstrap-rtl-3-3-4/dist/css/bootstrap-rtl.min.css" rel="stylesheet" type="text/css" />
<?php } ?>

<!-- for the pop up calendar -->
<style type="text/css">@import url(../library/dynarch_calendar.css);</style>
<script src="<?php echo $GLOBALS['standard_js_path']; ?>/jquery-min-1-11-3/index.js" type="text/javascript"></script>
<script type="text/javascript" src="../library/dynarch_calendar.js"></script>
<script type="text/javascript" src="../library/dynarch_calendar_en.js"></script>
<script type="text/javascript" src="../library/dynarch_calendar_setup.js"></script>

<script src="<?php echo $GLOBALS['standard_js_path']; ?>/bootstrap-3-3-4/dist/js/bootstrap.min.js" type="text/javascript"></script>
<!-- for ajax-y stuff
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.2.2.min.js"></script> -->

<script>

 function setappt(year,mon,mday,hours,minutes) {
  if (opener.closed || ! opener.setappt)
   alert('<?php xl('The destination form was closed; I cannot act on your selection.','e'); ?>');
  else
   opener.setappt(year,mon,mday,hours,minutes);
  window.close();
  return false;
 }

</script>


<style>
form {
    /* this eliminates the padding normally around a FORM tag */
    padding: 0px;
    margin: 0px;
}
#searchCriteria {
    text-align: center;
    width: 100%;
   /* font-size: 0.8em; */
    background-color: #bfe6ff;
    font-weight: bold;
    padding: 3px;
}
#searchResultsHeader { 
    width: 100%;
    background-color: lightgrey;
}
#searchResultsHeader table { 
    width: 96%;  /* not 100% because the 'searchResults' table has a scrollbar */
    border-collapse: collapse;
}
#searchResultsHeader th {
   /* font-size: 0.7em; */
}
#searchResults {
    width: 100%;
    height: 100%;
    overflow: auto;
}

.srDate { width: 20%; }
.srTimes { width: 80%; }

#searchResults table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
}
#searchResults td {
   /* font-size: 0.7em; */
    border-bottom: 1px solid gray;
    padding: 1px 5px 1px 5px;
}
.highlight { background-color: #ff9; }
.blue_highlight { background-color: #BBCCDD; color: white; }
#am {
    border-bottom: 1px solid lightgrey;
    color: #00c;
}
#pm { color: #c00; }
#pm a { color: #c00; }
</style>

</head>

<body class="body_top">

<div id="searchCriteria">
<form method='post' name='theform' action='./find_appt_popup_user.php?providerid=<?php echo attr($providerid) ?>&catid=<?php echo attr($input_catid) ?>'>
   <input type="hidden" name='bypatient' />

   <?php echo xlt('Start date:'); ?>


   <input type='text' name='startdate' id='startdate' size='10' value='<?php echo $sdate ?> ' readonly='readonly'
    title='<?php echo xla('This Date is set by the Clinic and cannot be Changed'); ?>'/>

  <!-- <img src='../interface/pic/show_calendar.gif' align='absbottom' width='24' height='22'
    id='img_date' border='0' alt='[?]' style='cursor:pointer'
    title='<?php //xl('Click here to choose a date','e'); ?>'>-->


   <?php echo xlt('for'); ?>
   <input type='text' name='searchdays' size='3' value='<?php echo attr($searchdays) ?>'
    title='<?php echo xla('Number of days to search from the start date'); ?>' />
   <?php echo xlt('days'); ?>&nbsp;
   <input type='submit' value='<?php echo xla('Search'); ?>'>
</div>

<?php if (!empty($slots)) : ?>

<div id="searchResultsHeader">
<table class='table table-bordered'>
 <tr>
  <th class="srDate"><?php echo xlt('Day'); ?></th>
  <th class="srTimes"><?php echo xlt('Available Times'); ?></th>
 </tr>
</table>
</div>

<div id="searchResults">
<table class='table table-condensed table-inversed table-bordered'>
<?php
    $lastdate = "";
    $ampmFlag = "am"; // establish an AM-PM line break flag
    for ($i = 0; $i < $slotcount; ++$i) {

        $available = true;
        for ($j = $i; $j < $i + $catslots; ++$j) {
        if ($slots[$j] >= 4) {
            $available = false;
        }
        }

    if (!$available) {
        continue; // skip reserved slots
    }

        $utime = ($slotbase + $i) * $slotsecs;
        $thisdate = date("Y-m-d", $utime);
        if ($thisdate != $lastdate) { 
            // if a new day, start a new row
            if ($lastdate) {
                echo "</div>";
                echo "</td>\n";
                echo " </tr>\n";
            }
            $lastdate = $thisdate;
            echo " <tr class='oneresult'>\n";
            echo "  <td class='srDate'>" . date("l", $utime)."<br>".date("Y-m-d", $utime) . "</td>\n";
            echo "  <td class='srTimes'>";
            echo "<div id='am'>AM ";
            $ampmFlag = "am";  // reset the AMPM flag
        }
        
        $ampm = date('a', $utime);
    if ($ampmFlag != $ampm) {
        echo "</div><div id='pm'>PM ";
    }

        $ampmFlag = $ampm;

        $atitle = "Choose ".date("h:i a", $utime);
        $adate = getdate($utime);
        $anchor = "<a href='' onclick='return setappt(" .
            $adate['year'] . "," .
            $adate['mon'] . "," .
            $adate['mday'] . "," .
            $adate['hours'] . "," .
            $adate['minutes'] . ")'".
            " title='$atitle' alt='$atitle'".
            ">";
        echo (strlen(date('g',$utime)) < 2 ? "<span style='visibility:hidden'>0</span>" : "") .
            $anchor . date("g:i", $utime) . "</a> ";

        // If category duration is more than 1 slot, increment $i appropriately.
        // This is to avoid reporting available times on undesirable boundaries.
        $i += $catslots - 1;
    }
    if ($lastdate) {
        echo "</td>\n";
        echo " </tr>\n";
    } else {
        echo " <tr><td colspan='2'> " . xl('No openings were found for this period.','e') . "</td></tr>\n";
    }
?>
</table>
</div>
</div>
<?php endif; ?>

</form>
</body>

<!-- for the pop up calendar -->
<script language='JavaScript'>
 <!--Calendar.setup({inputField:"startdate", ifFormat:"%Y-%m-%d", button:"img_date"});-->

// jQuery stuff to make the page a little easier to use

$(document).ready(function(){
    $(".oneresult").mouseover(function() { $(this).toggleClass("highlight"); });
    $(".oneresult").mouseout(function() { $(this).toggleClass("highlight"); });
    $(".oneresult a").mouseover(function () { $(this).toggleClass("blue_highlight"); $(this).children().toggleClass("blue_highlight"); });
    $(".oneresult a").mouseout(function() { $(this).toggleClass("blue_highlight"); $(this).children().toggleClass("blue_highlight"); });
    //$(".event").dblclick(function() { EditEvent(this); });
});

</script>

</html>
