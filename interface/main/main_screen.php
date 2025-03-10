<?php
/**
 * The outside frame that holds all of the LibreHealth EHR User Interface.
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package LibreHealth EHR
 * @author  Brady Miller <brady@sparmy.com>
 * @link    http://librehealth.io
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;

/* Include our required headers */
require_once('../globals.php');
require_once("$srcdir/formdata.inc.php");
require_once("../../library/CsrfToken.php");

// Creates a new session id when load this outer frame
// (allows creations of separate LibreHealth EHR frames to view patients concurrently
//  on different browser frame/windows)
// This session id is used below in the restoreSession.php include to create a
// session cookie for this specific LibreHealth EHR instance that is then maintained
// within the LibreHealth EHR instance by calling top.restoreSession() whenever
// refreshing or starting a new script.
if (isset($_POST['new_login_session_management'])) {
  // This is a new login, so create a new session id and remove the old session
  session_regenerate_id(true);
}
else {
  // This is not a new login, so create a new session id and do NOT remove the old session
  session_regenerate_id(false);
}

//generate csrf token
$_SESSION['token'] = CsrfToken::generateCsrfToken();

$_SESSION["encounter"] = '';

// Fetch the password expiration date
$is_expired=false;
if($GLOBALS['password_expiration_days'] != 0){
  $is_expired=false;
  $q= (isset($_POST['authUser'])) ? $_POST['authUser'] : '';
  $result = sqlStatement("select pwd_expiration_date from users where username = ?", array($q));
  $current_date = date('Y-m-d');
  $pwd_expires_date = $current_date;
  if($row = sqlFetchArray($result)) {
    $pwd_expires_date = $row['pwd_expiration_date'];
  }

  // Display the password expiration message (starting from 7 days before the password gets expired)
  $pwd_alert_date = date('Y-m-d', strtotime($pwd_expires_date . '-7 days'));

  if (strtotime($pwd_alert_date) != '' &&
      strtotime($current_date) >= strtotime($pwd_alert_date) &&
      (!isset($_SESSION['expiration_msg'])
      or $_SESSION['expiration_msg'] == 0)) {
    $is_expired = true;
    $_SESSION['expiration_msg'] = 1; // only show the expired message once
  }
}

if ($is_expired) {
  //display the php file containing the password expiration message.
  $frame1url = "pwd_expires_alert.php";
}
else if (!empty($_POST['patientID'])) {
  $patientID = 0 + $_POST['patientID'];
  if (empty($_POST['encounterID'])) {
    // Open patient summary screen (without a specific encounter)
    $frame1url = "../patient_file/summary/demographics.php?set_pid=".attr($patientID);
  }
  else {
    // Open patient summary screen with a specific encounter
    $encounterID = 0 + $_POST['encounterID'];
    $frame1url = "../patient_file/summary/demographics.php?set_pid=".attr($patientID)."&set_encounterid=".attr($encounterID);
  }
}

else if (isset($_GET['mode']) && $_GET['mode'] == "loadcalendar") {
  $frame1url = "../../modules/calendar/index.php?pid=" . attr($_GET['pid']);
  if (isset($_GET['date'])) $frame1url .= "&date=" . attr($_GET['date']);
}
else {
  $frame1url = "TAB_ONE_DEFAULT"; 
}


$username = (isset($_POST['authUser'])) ? $_POST['authUser'] : '';
$check_fullscreen = sqlStatement("select fullscreen_enable from users where username = ?", array($username));
$row = sqlFetchArray($check_fullscreen);
//echo $row["fullscreen_enable"];
if($row) {
  if($row["fullscreen_enable"] == 1) {
    $fullscreen_url = $web_root."/interface/main/tabs/fullscreen.php?url=TAB_ONE_DEFAULT";
    header('Location: '.$fullscreen_url);
    exit();
  } else {
    require_once("tabs/redirect.php");
  }
}
//require_once("tabs/redirect.php");
?>
<html>
<head>
<title>
<?php echo text($libreehr_name) ?>
</title>
<script type="text/javascript" src="../../library/js/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="../../library/topdialog.js"></script>

<script language='JavaScript'>
<?php require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>

// This counts the number of frames that have reported themselves as loaded.
// Currently only left_nav and Title do this, so the maximum will be 2.
// This is used to determine when those frames are all loaded.
var loadedFrameCount = 0;

function allFramesLoaded() {
 // Change this number if more frames participate in reporting.
 return loadedFrameCount >= 2;
}
</script>

</head>

<?php
/*
 * for RTL layout we need to change order of frames in framesets
 */
$lang_dir = $_SESSION['language_direction'];

$sidebar_tpl = "<frameset rows='*,0' frameborder='0' border='0' framespacing='0'>
   <frame src='left_nav.php' name='left_nav' />
   <frame src='daemon_frame.php' name='Daemon' scrolling='no' frameborder='0'
    border='0' framespacing='0' />
  </frameset>";
        
$main_tpl = "<frameset rows='60%,*' id='fsright' bordercolor='#999999' frameborder='1'>" ;
$main_tpl .= "<frame src='". $frame1url ."' name='RTop' scrolling='auto' />
   <frame src='messages/messages.php?form_active=1' name='RBot' scrolling='auto' /></frameset>";
?>
</html>
