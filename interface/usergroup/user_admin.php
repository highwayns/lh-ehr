<?php
/*
 *  user_admin.php for the editing of the user information
 *
 *  This program is used to edit the users
 *
 * Copyright (C) 2016-2017
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-license.php.
 *
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreEHR
 *
 * @link http://librehealth.io
 *
 * Please help the overall project by sending changes you make to the author and to the LibreEHR community.
 *
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;


require_once("../globals.php");
require_once("../../library/acl.inc");
require_once("$srcdir/sql.inc");
require_once("$srcdir/formdata.inc.php");
require_once("$srcdir/calendar.inc");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/erx_javascript.inc.php");
require_once("$srcdir/role.php");

if (!$_GET["id"] || !acl_check('admin', 'users'))
  exit();

if ($_GET["mode"] == "update") {
  if ($_GET["username"]) {
    $tqvar = trim(formData('username','G'));
    $user_data = sqlFetchArray(sqlStatement("select * from users where id= ?", array($_GET["id"])));
    sqlStatement("update users set username='$tqvar' where id = ?", array($_GET["id"]));
    sqlStatement("update groups set user='$tqvar' where user = ?", array($user_data["username"]));
  }
  if ($_GET["taxid"]) {
    $tqvar = formData('taxid','G');
    sqlStatement("update users set federaltaxid='$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["drugid"]) {
    $tqvar = formData('drugid','G');
    sqlStatement("update users set federaldrugid='$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["upin"]) {
    $tqvar = formData('upin','G');
    sqlStatement("update users set upin='$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["npi"]) {
    $tqvar = formData('npi','G');
    sqlStatement("update users set npi='$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["taxonomy"]) {
    $tqvar = formData('taxonomy','G');
    sqlStatement("update users set taxonomy = '$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["lname"]) {
    $tqvar = formData('lname','G');
    sqlStatement("update users set lname='$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["suffix"]) {
    $tqvar = formData('suffix','G');
    sqlStatement("update users set suffix='$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["job"]) {
    $tqvar = formData('job','G');
    sqlStatement("update users set specialty='$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["mname"]) {
          $tqvar = formData('mname','G');
          sqlStatement("update users set mname='$tqvar' where id = ?", array($_GET["id"]));
  }
  if ($_GET["facility_id"]) {
          $tqvar = formData('facility_id','G');
          sqlStatement("update users set facility_id = '$tqvar' where id = ?", array($_GET["id"]));
          //(CHEMED) Update facility name when changing the id
          sqlStatement("UPDATE users, facility SET users.facility = facility.name WHERE facility.id = '$tqvar' AND users.id = ?", array($_GET["id"]));
          //END (CHEMED)
  }

  if ($GLOBALS['restrict_user_facility'] && $_GET["schedule_facility"]) {
      sqlStatement("delete from users_facility
        where tablename='users'
        and table_id=?
        and facility_id not in (" . implode(",", $_GET['schedule_facility']) . ")", array($_GET["id"]));
      foreach($_GET["schedule_facility"] as $tqvar) {
      sqlStatement("replace into users_facility set
            facility_id = '$tqvar',
            tablename='users',
            table_id = ?", array($_GET["id"]));
    }
  }
  if ($_GET["fname"]) {
          $tqvar = formData('fname','G');
          sqlStatement("update users set fname='$tqvar' where id = ?", array($_GET["id"]));
  }
  //(CHEMED) Calendar UI preference
  if ($_GET["cal_ui"]) {
          $tqvar = formData('cal_ui','G');
          sqlStatement("update users set cal_ui = '$tqvar' where id = ?", array($_GET["id"]));

          // added by bgm to set this session variable if the current user has edited
      //   their own settings
      if ($_SESSION['authId'] == $_GET["id"]) {
        $_SESSION['cal_ui'] = $tqvar;
      }
  }
  //END (CHEMED) Calendar UI preference

  if (isset($_GET['default_warehouse'])) {
    sqlStatement("UPDATE users SET default_warehouse = '" .
      formData('default_warehouse','G') .
      "' WHERE id = ?", array(formData('id','G')));
  }

  if (isset($_GET['irnpool'])) {
    sqlStatement("UPDATE users SET irnpool = '" .
      formData('irnpool','G') .
      "' WHERE id = ?", array(formData('id','G')));
  }

  if ($_GET["newauthPass"] && $_GET["newauthPass"] != "d41d8cd98f00b204e9800998ecf8427e") { // account for empty
    $tqvar = formData('newauthPass','G');
    sqlStatement("update users set password='$tqvar' where id = ?", array($_GET["id"]));
  }

  $tqvar  = $_GET["authorized"] ? 1 : 0;
  $actvar = $_GET["active"]     ? 1 : 0;
  $calvar = $_GET["calendar"]   ? 1 : 0;

  sqlStatement("UPDATE users SET authorized = $tqvar, active = $actvar, " .
    "calendar = $calvar, see_auth = '" . $_GET['see_auth'] . "' WHERE " .
    "id = ?", array($_GET["id"]));

  if ($_GET["comments"]) {
    $tqvar = formData('comments','G');
    sqlStatement("update users set info = '$tqvar' where id = ?", array($_GET["id"]));
  }

  if (isset($phpgacl_location) && acl_check('admin', 'acl')) {
    // Set the access control group of user
    $user_data = sqlFetchArray(sqlStatement("select username from users where id = ?", array($_GET["id"])));
    set_user_aro($_GET['access_group'], $user_data["username"],
      formData('fname','G'), formData('mname','G'), formData('lname','G'));
  }


  /*Dont move usergroup_admin (1).php just close window
  // On a successful update, return to the users list.
  include("usergroup_admin.php");
  exit(0);
  */    echo '
<script type="text/javascript">
<!--
parent.$.fn.fancybox.close();
//-->
</script>

    ';
}

$res = sqlStatement("select * from users where id=?",array($_GET["id"]));
for ($iter = 0;$row = sqlFetchArray($res);$iter++)
                $result[$iter] = $row;
$iter = $result[0];

///
if (isset($_POST["mode"])) {
    echo '
<script type="text/javascript">
<!--
parent.$.fn.fancybox.close();
//-->
</script>

    ';
}
///

?>

<html>
<head>

<link rel="stylesheet" href="<?php echo $css_header; ?>" type="text/css">
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/js/jquery.1.3.2.js"></script>
<script type="text/javascript" src="../../library/js/common.js"></script>

<script src="checkpwd_validation.js" type="text/javascript"></script>

<script language="JavaScript">
function checkChange()
{
  alert("<?php echo addslashes(xl('If you change e-RX Role for ePrescription, it may affect the ePrescription workflow. If you face any difficulty, contact your ePrescription vendor.'));?>");
}
function submitform() {
    top.restoreSession();
    var flag=0;
    function trimAll(sString)
    {
        while (sString.substring(0,1) == ' ')
        {
            sString = sString.substring(1, sString.length);
        }
        while (sString.substring(sString.length-1, sString.length) == ' ')
        {
            sString = sString.substring(0,sString.length-1);
        }
        return sString;
    }
    if(trimAll(document.getElementById('fname').value) == ""){
        alert("<?php xl('Required field missing: Please enter the First name','e');?>");
        document.getElementById('fname').style.backgroundColor="red";
        document.getElementById('fname').focus();
        return false;
    }
    if(trimAll(document.getElementById('lname').value) == ""){
        alert("<?php xl('Required field missing: Please enter the Last name','e');?>");
        document.getElementById('lname').style.backgroundColor="red";
        document.getElementById('lname').focus();
        return false;
    }
    if(document.forms[0].clearPass.value!="")
    {
        //Checking for the strong password if the 'secure password' feature is enabled
        if(document.forms[0].secure_pwd.value == 1)
        {
                    var pwdresult = passwordvalidate(document.forms[0].clearPass.value);
                    if(pwdresult == 0) {
                            flag=1;
                            alert("<?php echo xl('The pass phrase must be at least eight characters, and should'); echo '\n'; echo xl('contain at least three of the four following items:'); echo '\n'; echo xl('A number'); echo '\n'; echo xl('A lowercase letter'); echo '\n'; echo xl('An uppercase letter'); echo '\n'; echo xl('A special character');echo '('; echo xl('not a letter or number'); echo ').'; echo '\n'; echo xl('For example:'); echo ' healthCare@09'; ?>");
                            return false;
                    }
        }

    }//If pwd null ends here
    //Request to reset the user password if the user was deactived once the password expired.
    if((document.forms[0].pwd_expires.value != 0) && (document.forms[0].clearPass.value == "")) {
        if((document.forms[0].user_type.value != "Emergency Login") && (document.forms[0].pre_active.value == 0) && (document.forms[0].active.checked == 1) && (document.forms[0].grace_time.value != "") && (document.forms[0].current_date.value) > (document.forms[0].grace_time.value))
        {
            flag=1;
            document.getElementById('error_message').innerHTML="<?php xl('Please reset the pass phrase.','e') ?>";
        }
    }

  if (document.forms[0].access_group_id) {
    var sel = getSelected(document.forms[0].access_group_id.options);
    for (var item in sel) {
      if (sel[item].value == "Emergency Login") {
        document.forms[0].check_acl.value = 1;
      }
    }
  }

      <?php if($GLOBALS['erx_enable']){ ?>
    alertMsg='';
    f=document.forms[0];
    for(i=0;i<f.length;i++){
      if(f[i].type=='text' && f[i].value)
      {
        if(f[i].name == 'fname' || f[i].name == 'mname' || f[i].name == 'lname')
        {
          alertMsg += checkLength(f[i].name,f[i].value,35);
          alertMsg += checkUsername(f[i].name,f[i].value);
        }
        else if(f[i].name == 'taxid')
        {
          alertMsg += checkLength(f[i].name,f[i].value,10);
          alertMsg += checkFederalEin(f[i].name,f[i].value);
        }
        else if(f[i].name == 'state_license_number')
        {
          alertMsg += checkLength(f[i].name,f[i].value,10);
          alertMsg += checkStateLicenseNumber(f[i].name,f[i].value);
        }
        else if(f[i].name == 'npi')
        {
          alertMsg += checkLength(f[i].name,f[i].value,10);
          alertMsg += checkTaxNpiDea(f[i].name,f[i].value);
        }
        else if(f[i].name == 'drugid')
        {
          alertMsg += checkLength(f[i].name,f[i].value,30);
          alertMsg += checkAlphaNumeric(f[i].name,f[i].value);
        }
      }
    }
    if(alertMsg)
    {
      alert(alertMsg);
      return false;
    }
    <?php } ?>
    if(flag == 0){
                    document.forms[0].submit();
                    parent.$.fn.fancybox.close();
    }
}
//Getting the list of selected item in ACL
function getSelected(opt) {
         var selected = new Array();
            var index = 0;
            for (var intLoop = 0; intLoop < opt.length; intLoop++) {
               if ((opt[intLoop].selected) ||
                   (opt[intLoop].checked)) {
                  index = selected.length;
                  selected[index] = new Object;
                  selected[index].value = opt[intLoop].value;
                  selected[index].index = intLoop;
               }
            }
            return selected;
         }

function authorized_clicked() {
 var f = document.forms[0];
 f.calendar.disabled = !f.authorized.checked;
 f.calendar.checked  =  f.authorized.checked;
}

</script>
<style type="text/css">
  .physician_type_class{
    width: 150px !important;
  }
</style>
</head>
<body class="body_top">
<table><tr><td>
<span class="title"><?php echo xlt('Edit User'); ?></span>&nbsp;
</td><td>
    <a class="css_button" name='form_save' id='form_save' href='#' onclick='return submitform()'> <span><?php echo xlt('Save');?></span> </a>
    <a class="css_button" id='cancel' href='#'><span><?php echo xlt('Cancel');?></span></a>
</td></tr>
</table>
<br>
<form name="user_form" method="POST" action="usergroup_admin.php" enctype="multipart/form-data" target="_parent" onsubmit='return top.restoreSession()'>

<input type=hidden name="pwd_expires" value="<?php echo $GLOBALS['password_expiration_days']; ?>" >
<input type=hidden name="pre_active" value="<?php echo $iter["active"]; ?>" >
<input type=hidden name="exp_date" value="<?php echo $iter["pwd_expiration_date"]; ?>" >
<input type=hidden name="get_admin_id" value="<?php echo $GLOBALS['Emergency_Login_email']; ?>" >
<input type=hidden name="admin_id" value="<?php echo $GLOBALS['Emergency_Login_email_id']; ?>" >
<input type=hidden name="check_acl" value="">
<?php
//Calculating the grace time
$current_date = date("Y-m-d");
$password_exp=$iter["pwd_expiration_date"];
if($password_exp != "0000-00-00")
  {
    $grace_time1 = date("Y-m-d", strtotime($password_exp . "+".$GLOBALS['password_grace_time'] ."days"));
  }
?>
<input type=hidden name="current_date" value="<?php echo strtotime($current_date); ?>" >
<input type=hidden name="grace_time" value="<?php echo strtotime($grace_time1); ?>" >
<!--  Get the list ACL for the user -->
<?php
$acl_name=acl_get_group_titles($iter["username"]);
$bg_name='';
$bg_count=count($acl_name);
   for($i=0;$i<$bg_count;$i++){
      if($acl_name[$i] == "Emergency Login")
       $bg_name=$acl_name[$i];
      }
?>
<input type=hidden name="user_type" value="<?php echo $bg_name; ?>" >

<table border=0 cellpadding=0 cellspacing=0>
<tr>
  <?php
    if($iter["picture_url"]){
      echo '<td style="width:180px;"><img id="prof_img" src="../../sites/'.$_SESSION['site_id'].'/profile_pictures/'.$iter["picture_url"].'"';
      echo ' style="display: block; border-radius: 40px; width: 64px; height: 64px; border: 8px solid #888;"></td>';
    }
    else{
      echo '<td style="width:180px;"><img id="prof_img" style="display: none; border-radius: 40px; border: 8px solid #888;"></td>';
    }
  ?>
  <td style="width:270px;"></td>
  <td style="width:200px;"></td>
  <?php
    if($iter["picture_url"]){
      echo '<td class="text" style="width:280px;"><label for="files" class="css_button cp-positive" id="file_input_button">'.xlt("Change Profile Picture").'</label></td>';
    }
    else{
      echo '<td class="text" style="width:280px;"><label for="files" class="css_button cp-positive" id="file_input_button">'.xlt("Add Profile Picture").'</label></td>';
    }
  ?>
<td style="padding-left: 350px;">
<input type="file" name="profile_picture" id="files"  class="hidden" style="display: none;" onchange="readURL(this);" />
</td>
</tr>
<tr>
    <td style="width:180px;"><span class=text><?php echo xlt('Username'); ?>: </span></td>
    <td style="width:270px;"><input type=entry name=username style="width:150px;" value="<?php echo $iter["username"]; ?>" disabled></td>
    <td style="width:200px;"><span class=text><?php echo xlt('Your Pass Phrase'); ?>: </span></td>
    <td class='text' style="width:280px;"><input type='password' name=adminPass style="width:150px;"  value="" autocomplete='off'><font class="mandatory">*</font></td>
</tr>
<tr>
    <td style="width:180px;"><span class=text></span></td>
    <td style="width:270px;"></td>
    <td style="width:200px;"><span class=text><?php echo xlt('User\'s New Pass Phrase'); ?>: </span></td>
    <td class='text' style="width:280px;">    <input type=entry name=clearPass style="width:150px;"  value=""><font class="mandatory">*</font></td>
</tr>


<tr height="30" style="valign:middle;">
<td><span class="text">&nbsp;</span></td><td>&nbsp;</td>
<td colspan="2"><span class=text><?php echo xlt('Provider'); ?>:
 <input type="checkbox" name="authorized" onclick="authorized_clicked()"<?php
  if ($iter["authorized"]) echo " checked"; ?> />
 &nbsp;&nbsp;<span class='text'><?php echo xlt('Calendar'); ?>:
 <input type="checkbox" name="calendar"<?php
  if ($iter["calendar"]) echo " checked";
  if (!$iter["authorized"]) echo " disabled"; ?> />
 &nbsp;&nbsp;<span class='text'><?php echo xlt('Active'); ?>:
 <input type="checkbox" name="active"<?php if ($iter["active"]) echo " checked"; ?> />
</td>
</tr>

<tr>
<td><span class=text><?php echo xlt('First Name'); ?>: </span></td>
<td><input type=entry name=fname id=fname style="width:150px;" value="<?php echo $iter["fname"]; ?>"><span class="mandatory">&nbsp;*</span></td>
<td><span class=text><?php echo xlt('Middle Name'); ?>: </span></td><td><input type=entry name=mname style="width:150px;"  value="<?php echo $iter["mname"]; ?>"></td>
</tr>

<tr>
<td><span class=text><?php echo xlt('Last Name'); ?>: </span></td><td><input type=entry name=lname id=lname style="width:150px;"  value="<?php echo $iter["lname"]; ?>"><span class="mandatory">&nbsp;*</span></td>
<td><span class=text><?php echo xlt('Default Facility'); ?>: </span></td><td><select name=facility_id style="width:150px;" >
<?php
$fres = sqlStatement("select * from facility where service_location != 0 order by name");
if ($fres) {
for ($iter2 = 0; $frow = sqlFetchArray($fres); $iter2++)
                $result[$iter2] = $frow;
foreach($result as $iter2) {
?>
  <option value="<?php echo $iter2['id']; ?>" <?php if ($iter['facility_id'] == $iter2['id']) echo "selected"; ?>><?php echo htmlspecialchars($iter2['name']); ?></option>
<?php
}
}
?>
</select></td>
</tr>

<tr>
<td><span class=text><?php echo xlt('Suffix'); ?>: </span></td><td><input type=entry name=suffix id=suffix style="width:150px;"  title ="<?php echo xla("Please Change this Information in the Address Book"); ?>" value="<?php echo $iter["suffix"]; ?>" readonly> </td>

 <td><span class=text><?php echo xlt('Schedule Facilities:');?></td>
 <td>
  <select name="schedule_facility[]" multiple style="width:150px;" >
<?php
  $userFacilities = getUserFacilities($_GET['id']);
  $ufid = array();
  //ufid is an array of id of schedule facilities of selected user
  foreach($userFacilities as $uf)
    $ufid[] = $uf['id'];
  $fres = sqlStatement("select * from facility where service_location != 0 order by name");
  if ($fres) {
    while($frow = sqlFetchArray($fres)):
?>
   <option <?php echo in_array($frow['id'], $ufid) || $frow['id'] == $iter['facility_id'] ? "selected" : null ?>
      value="<?php echo $frow['id'] ?>"><?php echo htmlspecialchars($frow['name']) ?></option>
<?php
  //$iter['facility_id'] is id of default facility of selected user
  endwhile;
}
?>
  </select>
 </td>
</tr>

<tr>
<td><span class=text><?php echo xlt('Federal Tax ID'); ?>: </span></td><td><input type=entry name=taxid style="width:150px;"  value="<?php echo $iter["federaltaxid"]?>"></td>
<td><span class=text><?php echo xlt('DEA Number'); ?>: </span></td><td><input type=entry name=drugid style="width:150px;"  value="<?php echo $iter["federaldrugid"]?>"></td>
</tr>

<tr>
  <td><span class="text"><?php echo xlt('Provider Type'); ?>: </span></td>
  <td><?php echo generate_select_list("physician_type", "physician_type", $iter['physician_type'],'',xl('Select Type'),'physician_type_class','','',''); ?></td>
<td class='text'><?php echo xlt('See Authorizations'); ?>: </td>
<td><select name="see_auth" style="width:150px;" >
<?php
 foreach (array(1 => xl('None'), 2 => xl('Only Mine'), 3 => xl('All')) as $key => $value)
 {
  echo " <option value='$key'";
  if ($key == $iter['see_auth']) echo " selected";
  echo ">$value</option>\n";
 }
?>
</select></td>
</tr>

<tr>
<td><span class="text"><?php echo xlt('NPI'); ?>: </span></td><td><input type="entry" name="npi" style="width:150px;"  value="<?php echo $iter["npi"]?>"></td>
<td><span class="text"><?php echo xlt('Job Description'); ?>: </span></td><td><input type="entry" name="job" style="width:150px;"  value="<?php echo $iter["specialty"]?>"></td>
</tr>

<!-- (CHEMED) Calendar UI preference -->
<tr>
<td><span class="text"><?php echo xlt('Taxonomy'); ?>: </span></td>
<td><input type="entry" name="taxonomy" style="width:150px;"  value="<?php echo $iter["taxonomy"]?>"></td>
<td><span class="text"><?php echo xlt('Calendar UI'); ?>: </span></td><td><select name="cal_ui" style="width:150px;" >
<?php
 foreach (array(3 => xl('Outlook'), 1 => xl('Original'), 2 => xl('Fancy')) as $key => $value)
 {
  echo " <option value='$key'";
  if ($key == $iter['cal_ui']) echo " selected";
  echo ">$value</option>\n";
 }
?>
</select></td>
</tr>
<!-- END (CHEMED) Calendar UI preference -->

<tr>
<td><span class="text"><?php echo xlt('State License Number'); ?>: </span></td>
<td><input type="entry" name="state_license_number" style="width:150px;"  value="<?php echo $iter["state_license_number"]?>"></td>
<td class='text'><?php echo xlt('NewCrop eRX Role'); ?>:</td>
<td>
  <?php echo generate_select_list("erxrole", "newcrop_erx_role", $iter['newcrop_user_role'],'',xl('Select Role'),'','','',array('style'=>'width:150px')); ?>
</td>
</tr>

<tr>
</tr>
<?php if ($GLOBALS['inhouse_pharmacy']) { ?>
<tr>
 <td class="text"><?php echo xlt('Default Warehouse'); ?>: </td>
 <td class='text'>
<?php
echo generate_select_list('default_warehouse', 'warehouse',
  $iter['default_warehouse'], '');
?>
 </td>
 <td class="text"><?php echo xlt('Invoice Refno Pool'); ?>: </td>
 <td class='text'>
<?php
echo generate_select_list('irnpool', 'irnpool', $iter['irnpool'],
  xl('Invoice reference number pool, if used'));
?>
 </td>
</tr>
<?php } ?>

<?php
 // Collect the access control group of user
 if (isset($phpgacl_location) && acl_check('admin', 'acl')) {
?>
  <tr>
  <td class='text'><?php echo xlt('Access Control'); ?>:</td>
  <td><select id="access_group_id" name="access_group[]" multiple style="width:150px;" >
  <?php
   $list_acl_groups = acl_get_group_title_list();
   $username_acl_groups = acl_get_group_titles($iter["username"]);
   foreach ($list_acl_groups as $value) {
    if (($username_acl_groups) && in_array($value,$username_acl_groups)) {
     // Modified 6-2009 by BM - Translate group name if applicable
     echo " <option value='$value' selected>" . xl_gacl_group($value) . "</option>\n";
    }
    else {
     // Modified 6-2009 by BM - Translate group name if applicable
     echo " <option value='$value'>" . xl_gacl_group($value) . "</option>\n";
    }
   }
  ?>
  </select></td>
  <td><span class=text><?php echo xlt('Additional Info'); ?>:</span></td>
  <td><textarea style="width:150px;" name="comments" wrap=auto rows=4 cols=25><?php echo $iter["info"];?></textarea></td>
  </tr>
  <tr>
  <td><span class="text"><?php echo xlt('Menu role'); ?>:</span></td>
  <td>
  <select style="width:120px;" name="menu_role" id="menu_role">
      <?php
         $role = new Role();
         $role_list = $role->getRoleList();
         foreach($role_list as $role_title) {
           ?>  <option value="<?php echo $role_title; ?>" <?php if ($iter["menu_role"] == $role_title) echo "selected"; ?>><?php echo xlt($role_title); ?></option>
          <?php
         }
      ?>
      </select>
  </td>
  <td><span class="text"> <?php echo xlt('Full screen page'); ?>:</span></td>
  <td>
      <select style="width:120px;" name="fullscreen_page" id="fullscreen_page">
                <option value="Calendar|/interface/main/main_info.php">Calendar</option>
                <option value="Flow Board|/interface/patient_tracker/patient_tracker.php">Flow Board</option>
      </select>


  </td>
  </tr>
  <tr>
  <td>
      <span class="text"> <?php echo xlt('Full screen page enabled'); ?>: </span>
  </td>
  <td>
      <input type="checkbox" name="fullscreen_enable" <?php if($iter['fullscreen_enable'] == 1) echo "checked"; ?>/>
  </td>
  <?php do_action( 'usergroup_admin_edit', $iter ); ?>

  </tr>
  <tr height="20" valign="bottom">
  <td colspan="4" class="text">
  <font class="mandatory">*</font> <?php echo xlt('You must enter your own pass phrase to change user pass phrases. Leave this blank to keep it unchanged.'); ?>
<!--
Display red alert if entered password matched one of last three passwords/Display red alert if user password was expired and the user was inactivated previously
-->
  <div class="redtext" id="error_message">&nbsp;</div>
  </td>
  </tr>
<?php
 }
?>
</table>

<input TYPE="HIDDEN" NAME="id" VALUE="<?php echo attr($_GET["id"]); ?>">
<input TYPE="HIDDEN" NAME="mode" VALUE="update">
<input TYPE="HIDDEN" NAME="privatemode" VALUE="user_admin">

<input TYPE="HIDDEN" NAME="secure_pwd" VALUE="<?php echo $GLOBALS['secure_password']; ?>">
</form>
<script language="JavaScript">
  $(document).ready(function(){
    $("#cancel").click(function() {
      parent.$.fn.fancybox.close();
    });

  });

  function readURL(input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();

      reader.onload = function (e) {
          $('#prof_img')
              .attr('src', e.target.result)
              .width(64)
              .height(64);
          $('#prof_img').css("display", "block"); 
          $('#file_input_button').text("Edit Profile Picture");
      };

      reader.readAsDataURL(input.files[0]);
    }
  }
</script>
</BODY>

</HTML>

<?php
//  d41d8cd98f00b204e9800998ecf8427e == blank
?>
