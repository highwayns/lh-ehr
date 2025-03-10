<?php
/**
 * Message and Reminder Center UI
 *
 * 2013/02/08 Minor tweaks by EMR Direct to allow integration with Direct messaging
 * 2013-03-27 by sunsetsystems: Fixed some weirdness with assigning a message recipient,
 *   and allowing a message to be closed with a new note appended and no recipient.
 *
 * Copyright (c) 2010 LibreHealth EHR Support LLC
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
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package LibreHealth EHR
 * @author LibreHealth EHR Support LLC
 * @author Roberto Vasquez <robertogagliotta@gmail.com>
 * @author Rod Roark <rod@sunsetsystems.com>
 * @author Brady Miller <brady@sparmy.com>
 * @link http://librehealth.io
 */

//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;

require_once("../../globals.php");
require_once("$srcdir/pnotes.inc");
require_once("$srcdir/patient.inc");
require_once("$srcdir/acl.inc");
require_once("$srcdir/log.inc");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/formdata.inc.php");
require_once("$srcdir/classes/Document.class.php");
require_once("$srcdir/gprelations.inc.php");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/headers.inc.php");
//Include Bootstrap
call_required_libraries(array("jquery-min-3-1-1","bootstrap","font-awesome", "jquery-ui", "iziModalToast"));
?>
<html>
<head>
    <link rel='stylesheet' href='<?php echo $css_header ?>' type='text/css'>

    <?php html_header_show();?>
</head>

<body class="body_top">

<span class="title"><?php echo xlt('Message and Reminder Center'); ?></span>
<br /><br />
<span class="title"><?php echo xlt('Reminders'); ?></span>

<?php

// TajEmo Work by CB 2012/01/11 02:51:25 PM adding dated reminders
// I am asuming that at this point security checks have been performed
require_once '../dated_reminders/dated_reminders.php';

// Check to see if the user has Admin rights, and if so, allow access to See All.
$showall = isset($_GET['show_all']) ? $_GET['show_all'] : "" ;
if ($showall == "yes") {
    $show_all = $showall;
}
else
{
    $show_all= "no";
}

// Collect active variable and applicable html code for links
$form_active   = (isset($_REQUEST['form_active'])   ? $_REQUEST['form_active']   : FALSE);
$form_inactive = (isset($_REQUEST['form_inactive']) ? $_REQUEST['form_inactive'] : FALSE);
if ($form_active) {
    $active = '1';
    $activity_string_html = 'form_active=1';
}
else if ($form_inactive) {
    $active = '0';
    $activity_string_html = 'form_inactive=1';
}
else {
    $active = 'all';
    $activity_string_html = '';
}

//collect the task setting
$task= isset($_REQUEST['task']) ? $_REQUEST['task'] : "";

if (acl_check('admin', 'super'    )) {
    if ($show_all=='yes') {
        $showall = "yes";
        $lnkvar="<a class='more' href='messages.php?show_all=no&".attr($activity_string_html)."' name='Just Mine' onclick=\"top.restoreSession()\"> (".htmlspecialchars( xl('Just Mine'), ENT_NOQUOTES).")</a>";
    }
    else {
        $showall = "no";
        $lnkvar="<a class='more' href='messages.php?show_all=yes&".attr($activity_string_html)."' name='See All' onclick=\"top.restoreSession()\"> (".htmlspecialchars( xl('See All'), ENT_NOQUOTES).")</a>";
    }
}
?>
<br>
<span class="title"><?php echo htmlspecialchars( xl('Messages'), ENT_NOQUOTES); ?><?php echo $lnkvar; ?></span>
<br><br>
<?php
//show the activity links
if (empty($task) || $task=="add" || $task=="delete") { ?>
    <?php if ($active == "all") { ?>
        <span><?php echo xlt('Show All'); ?></span>
    <?php } else { ?>
        <a href="messages.php" class="link" onclick="top.restoreSession()"><span><?php echo xlt('Show All'); ?></span></a>
    <?php } ?>
    |
    <?php if ($active == '1') { ?>
        <span><?php echo xlt('Show Active'); ?></span>
    <?php } else { ?>
        <a href="messages.php?form_active=1" class="link" onclick="top.restoreSession()"><span><?php echo xlt('Show Active'); ?></span></a>
    <?php } ?>
    |
    <?php if ($active == '0') { ?>
        <span><?php echo xlt('Show Inactive'); ?></span>
    <?php } else { ?>
        <a href="messages.php?form_inactive=1" class="link" onclick="top.restoreSession()"><span><?php echo xlt('Show Inactive'); ?></span></a>
    <?php } ?>
<?php } ?>

<?php
switch($task) {
    case "add" :
        {
            // Add a new message for a specific patient; the message is documented in Patient Notes.
            // Add a new message; it's treated as a new note in Patient Notes.
            $note = $_POST['note'];
            $noteid = $_POST['noteid'];
            $form_note_type = $_POST['form_note_type'];
            $form_message_status = $_POST['form_message_status'];
            $reply_to = $_POST['reply_to'];
            $assigned_to_list = explode(';', $_POST['assigned_to']);
            foreach($assigned_to_list as $assigned_to){
                if ($noteid && $assigned_to != '-patient-') {
                    updatePnote($noteid, $note, $form_note_type, $assigned_to, $form_message_status);
                    $noteid = '';
                }
                else {
                    if($noteid && $assigned_to == '-patient-'){
                        // When $assigned_to == '-patient-' we don't update the current note, but
                        // instead create a new one with the current note's body prepended and
                        // attributed to the patient.  This seems to be all for the patient portal.
                        $row = getPnoteById($noteid);
                        if (! $row) die("getPnoteById() did not find id '".text($noteid)."'");
                        $pres = sqlQuery("SELECT lname, fname " .
                            "FROM patient_data WHERE pid = ?", array($reply_to) );
                        $patientname = $pres['lname'] . ", " . $pres['fname'];
                        $note .= "\n\n$patientname on ".$row['date']." wrote:\n\n";
                        $note .= $row['body'];
                    }
                    // There's no note ID, and/or it's assigned to the patient.
                    // In these cases a new note is created.
                    addPnote($reply_to, $note, $userauthorized, '1', $form_note_type, $assigned_to, '', $form_message_status);
                }
            }
            echo ('
        <script type="text/javascript">
             iziToast.success({
                position : "bottomRight",
                icon : "fa fa-envelope",
                message : "Message sent"
            });
        </script>
        ');
        } break;
    case "savePatient":
    case "save" : {
        // Update alert.
        $noteid = $_POST['noteid'];
        $form_message_status = $_POST['form_message_status'];
        $reply_to = $_POST['reply_to'];
        if ($task=="save")
            updatePnoteMessageStatus($noteid,$form_message_status);
        else
            updatePnotePatient($noteid,$reply_to);
        $task = "edit";
        $note = $_POST['note'];
        $title = $_POST['form_note_type'];
        $reply_to = $_POST['reply_to'];
    }
    case "edit" : {
        if ($noteid == "") {
            $noteid = $_GET['noteid'];
        }
        // Update the message if it already exists; it's appended to an existing note in Patient Notes.
        $result = getPnoteById($noteid);
        if ($result) {
            if ($title == ""){
                $title = $result['title'];
            }
            $body = $result['body'];
            if ($reply_to == ""){
                $reply_to = $result['pid'];
            }
            $form_message_status = $result['message_status'];
        }
    } break;
    case "delete" : {
        // Delete selected message(s) from the Messages box (only).
        $delete_id = $_POST['delete_id'];
        for($i = 0; $i < count($delete_id); $i++) {
            deletePnote($delete_id[$i]);
            newEvent("delete", $_SESSION['authUser'], $_SESSION['authProvider'], 1, "pnotes: id ".$delete_id[$i]);
        }
    } break;
}

if($task == "addnew" or $task == "edit") {
    // Display the Messages page layout.
    echo "
<form name=new_note id=new_note action=\"messages.php?showall=".attr($showall)."&sortby=".attr($sortby)."&sortorder=".attr($sortorder)."&begin=".attr($begin)."&".attr($activity_string_html)."\" method=post>
<input type=hidden name=noteid id=noteid value='".attr($noteid)."'>
<input type=hidden name=task id=task value=add>";
    ?>
    <div id="pnotes"><center>
            <table class="table">
                <tr>
                    <td class='text'>
                        <b><?php echo htmlspecialchars( xl('Type'), ENT_NOQUOTES); ?>:</b>
                        <?php
                        if ($title == "") {
                            $title = "Unassigned";
                        }
                        // Added 6/2009 by BM to incorporate the patient notes into the list_options listings.
                        generate_form_field(array('data_type'=>1,'field_id'=>'note_type','list_id'=>'note_type','empty_title'=>'SKIP','order_by'=>'title'), $title);
                        ?>
                        &nbsp; &nbsp;
                        <?php if ($task != "addnew" && $result['pid'] != 0) { ?>
                            <a class="patLink" onclick="goPid('<?php echo attr($result['pid']);?>')"><?php echo htmlspecialchars( xl('Patient'), ENT_NOQUOTES); ?>:</a>
                        <?php } else { ?>
                            <b class='<?php echo ($task=="addnew"? attr("required"):""); ?>'><?php echo htmlspecialchars( xl('Patient'), ENT_NOQUOTES); ?>:</b>
                            <?php
                        }
                        if ($reply_to) {
                            $prow = sqlQuery("SELECT lname, fname " .
                                "FROM patient_data WHERE pid = ?", array($reply_to) );
                            $patientname = $prow['lname'] . ", " . $prow['fname'];
                        }
                        if ($patientname == '') {
                            $patientname = xl('Click to select');
                        } ?>
                        <input type='text' size='10' name='form_patient' style='<?php
                        echo ($task=="addnew"? attr("cursor:pointer;cursor:hand;"):"") ?>' value='<?php
                        echo htmlspecialchars($patientname, ENT_QUOTES); ?>' <?php
                        echo (($task=="addnew" || $result['pid']==0) ? "onclick='sel_patient()' readonly":"disabled") ?> title='<?php
                        echo ($task=="addnew"?(htmlspecialchars( xl('Click to select patient'), ENT_QUOTES)):"") ?>'  />
                        <input type='hidden' name='reply_to' id='reply_to' value='<?php echo htmlspecialchars( $reply_to, ENT_QUOTES) ?>' />
                        &nbsp; &nbsp;
                        <b><?php echo htmlspecialchars( xl('Status'), ENT_NOQUOTES); ?>:</b>
                        <?php
                        if ($form_message_status == "") {
                            $form_message_status = 'New';
                        }
                        generate_form_field(array('data_type'=>1,'field_id'=>'message_status','list_id'=>'message_status','empty_title'=>'SKIP','order_by'=>'title'), $form_message_status); ?>
                    </td>
                </tr>
                <tr>
                    <td class='text'>
                        <b><?php echo htmlspecialchars( xl('To'), ENT_QUOTES); ?>:</b>
                        <input type='textbox' name='assigned_to_text' id='assigned_to_text' size='40' readonly='readonly'
                               value='<?php echo htmlspecialchars(xl("Select Users From The Dropdown List"), ENT_QUOTES)?>' >
                        <input type='hidden' name='assigned_to' id='assigned_to' >
                        <select name='users' id='users' onchange='addtolist(this);' >
                            <?php
                            echo "<option value='" . htmlspecialchars( '--', ENT_QUOTES) . "'";
                            echo ">" . htmlspecialchars( xl('Select User'), ENT_NOQUOTES);
                            echo "</option>\n";
                            $ures = sqlStatement("SELECT username, fname, lname FROM users " .
                                "WHERE username != '' AND active = 1 AND " .
                                "( info IS NULL OR info NOT LIKE '%Inactive%' ) " .
                                "ORDER BY lname, fname");
                            while ($urow = sqlFetchArray($ures)) {
                                echo "    <option value='" . htmlspecialchars( $urow['username'], ENT_QUOTES) . "'";
                                echo ">" . htmlspecialchars( $urow['lname'], ENT_NOQUOTES);
                                if ($urow['fname']) echo ", " . htmlspecialchars( $urow['fname'], ENT_NOQUOTES);
                                echo "</option>\n";
                            }
                            echo "<option value='" . htmlspecialchars( '-patient-', ENT_QUOTES) . "'";
                            echo ">" . htmlspecialchars( '-Patient-', ENT_NOQUOTES);
                            echo "</option>\n";
                            ?>
                        </select>
                    </td>
                </tr>

                <?php
                if ($noteid) {
                    // Get the related document IDs if any.
                    $tmp = sqlStatement("SELECT id1 FROM gprelations WHERE " .
                        "type1 = ? AND type2 = ? AND id2 = ?",
                        array('1', '6', $noteid));
                    if (sqlNumRows($tmp)) {
                        echo " <tr>\n";
                        echo "  <td class='text'><b>";
                        echo xlt('Linked document') . ":</b>\n";
                        while ($gprow = sqlFetchArray($tmp)) {
                            $d = new Document($gprow['id1']);
                            echo "   <a href='";
                            echo $GLOBALS['webroot'] . "/controller.php?document&retrieve";
                            echo "&patient_id="  . attr($d->get_foreign_id());
                            echo "&document_id=" . attr($d->get_id());
                            echo "&as_file=true' target='_blank' onclick='top.restoreSession()'>";
                            echo text($d->get_url_file());
                            echo "</a>\n";
                        }
                        echo "  </td>\n";
                        echo " </tr>\n";
                    }
                    // Get the related procedure order IDs if any.
                    $tmp = sqlStatement("SELECT id1 FROM gprelations WHERE " .
                        "type1 = ? AND type2 = ? AND id2 = ?",
                        array('2', '6', $noteid));
                    if (sqlNumRows($tmp)) {
                        echo " <tr>\n";
                        echo "  <td class='text'><b>";
                        echo xlt('Linked procedure order') . ":</b>\n";
                        while ($gprow = sqlFetchArray($tmp)) {
                            echo "   <a href='";
                            echo $GLOBALS['webroot'] . "/interface/orders/single_order_results.php?orderid=";
                            echo attr($gprow['id1']);
                            echo "' target='_blank' onclick='top.restoreSession()'>";
                            echo text($gprow['id1']);
                            echo "</a>\n";
                        }
                        echo "  </td>\n";
                        echo " </tr>\n";
                    }
                }
                ?>

                <tr>
                    <td>

                        <?php

                        if ($noteid) {
                            $body = preg_replace('/(:\d{2}\s\()'.$result['pid'].'(\sto\s)/','${1}'.$patientname.'${2}',$body);
                            $body = nl2br(htmlspecialchars( $body, ENT_NOQUOTES));
                            echo "<div class='text' style='background-color:white; color: gray; border:1px solid #999; padding: 5px; width: 640px;'>".$body."</div>";
                        }

                        ?>
                        <textarea name='note' id='note' rows='8' style="width: 660px; "><?php echo htmlspecialchars( $note, ENT_NOQUOTES) ?></textarea>
                    </td>
                </tr>
            </table>

            <?php if ($noteid) { ?>
                <!-- This is for displaying an existing note. -->
                <input type="button" id="newnote" class="cp-submit"  value="<?php echo htmlspecialchars( xl('Send message'), ENT_QUOTES); ?>">
                <input type="button" id="printnote" class="cp-output" value="<?php echo htmlspecialchars( xl('Print message'), ENT_QUOTES); ?>">
                <input type="button" id="cancel" class="cp-negative" value="<?php echo htmlspecialchars( xl('Cancel'), ENT_QUOTES); ?>">
            <?php } else { ?>
                <!-- This is for displaying a new note. -->
                <input type="button" id="newnote" class="cp-submit" value="<?php echo htmlspecialchars( xl('Send message'), ENT_QUOTES); ?>">
                <input type="button" id="cancel"  class="cp-negative" value="<?php echo htmlspecialchars( xl('Cancel'), ENT_QUOTES); ?>">
            <?php }
            ?>

            <br>
            </form></center></div>
    <script language="javascript">

        // jQuery stuff to make the page a little easier to use

        $(document).ready(function(){
            $("#newnote").click(function() { NewNote(); });
            $("#printnote").click(function() { PrintNote(); });
            obj = document.getElementById("form_message_status");
            obj.onchange = function(){SaveNote();};
            $("#cancel").click(function() { CancelNote(); });
            $("#note").focus();

            var NewNote = function () {
                top.restoreSession();

                if($("#note").val() === null || $("#note").val() == ""){
                    <?php
                    $msg = 'Message field cannot be empty';
                    echo ("var alertMsg ="."'".htmlspecialchars( xl($msg), ENT_QUOTES)."'".";\n");
                    ?>
                    iziToast.warning({
                        position : "bottomRight",
                        icon : "fa fa-warning",
                        message : alertMsg
                    });
                }
                else {
                    if (document.forms[0].reply_to.value.length == 0 || document.forms[0].reply_to.value == '0') {
                        <?php
                        $msg = 'Please choose a patient';
                        echo ("var alertMsg ="."'".htmlspecialchars( xl($msg), ENT_QUOTES)."'".";\n");
                        ?>
                        iziToast.warning({
                            position : "bottomRight",
                            icon : "fa fa-warning",
                            message : alertMsg
                        });
                    }
                    else if (document.forms[0].assigned_to.value.length == 0 &&
                        document.getElementById("form_message_status").value != 'Done')
                    {
                        <?php
                        $msg = 'Recipient required unless status is Done';
                        echo ("var alertMsg ="."'".addslashes(xl($msg))."'".";\n");
                        ?>
                        iziToast.warning({
                            position : "bottomRight",
                            icon : "fa fa-warning",
                            message : alertMsg
                        });
                    }
                    else
                    {
                        $("#new_note").submit();
                    }
                }
            };

            var PrintNote = function () {
                top.restoreSession();
                window.open('../../patient_file/summary/pnotes_print.php?noteid=<?php echo htmlspecialchars( $noteid, ENT_QUOTES); ?>', '_blank', 'resizable=1,scrollbars=1,width=600,height=500');
            };

            var SaveNote = function () {
                <?php if ($noteid) { ?>
                top.restoreSession();
                $("#task").val("save");
                $("#new_note").submit();
                <?php } ?>
            };

            var CancelNote = function () {
                top.restoreSession();
                $("#task").val("");
                $("#new_note").submit();
            };
        });
        // This is for callback by the find-patient popup.
        function setpatient(pid, lname, fname, dob) {
            var f = document.forms[0];
            f.form_patient.value = lname + ', ' + fname;
            f.reply_to.value = pid;
            <?php if ($noteid) { ?>
            //used when direct messaging service inserts a pnote with indeterminate patient
            //to allow the user to assign the message to a patient.
            top.restoreSession();
            $("#task").val("savePatient");
            $("#new_note").submit();
            <?php } ?>
        }

        // This invokes the find-patient popup.
        function sel_patient() {
            dlgopen('<?php echo $GLOBALS["web_root"]; ?>/modules/calendar/find_patient_popup.php', '_blank', 500, 400);
        }

        function addtolist(sel){
            var itemtext = document.getElementById('assigned_to_text');
            var item = document.getElementById('assigned_to');
            if(sel.value !== '--'){
                if(item.value){
                    if(item.value.indexOf(sel.value) === -1){
                        itemtext.value = itemtext.value +' ; '+ sel.options[sel.selectedIndex].text;
                        item.value = item.value +';'+ sel.value;
                    }
                }else{
                    itemtext.value = sel.options[sel.selectedIndex].text;
                    item.value = sel.value;
                }
            }
        }

    </script><?php
}
else {

// This is for sorting the records.
$sort = array("users.lname", "patient_data.lname", "pnotes.title", "pnotes.date", "pnotes.message_status");
$sortby = (isset($_REQUEST['sortby']) && ($_REQUEST['sortby']!="")) ? $_REQUEST['sortby'] : $sort[0];
$sortorder = (isset($_REQUEST['sortorder'])  && ($_REQUEST['sortorder']!="")) ? $_REQUEST['sortorder']  : "asc";
$begin = isset($_REQUEST['begin']) ? $_REQUEST['begin'] : 0;

for($i = 0; $i < count($sort); $i++) {
    $sortlink[$i] = "<a href=\"messages.php?show_all=".attr($showall)."&sortby=".attr($sort[$i])."&sortorder=asc&".attr($activity_string_html)."\" onclick=\"top.restoreSession()\"><img src=\"../../../images/sortdown.gif\" border=0 alt=\"".htmlspecialchars( xl('Sort Up'), ENT_QUOTES)."\"></a>";
}
for($i = 0; $i < count($sort); $i++) {
    if($sortby == $sort[$i]) {
        switch($sortorder) {
            case "asc"      : $sortlink[$i] = "<a href=\"messages.php?show_all=".attr($showall)."&sortby=".attr($sortby)."&sortorder=desc&".attr($activity_string_html)."\" onclick=\"top.restoreSession()\"><img src=\"../../../images/sortup.gif\" border=0 alt=\"".htmlspecialchars( xl('Sort Up'), ENT_QUOTES)."\"></a>"; break;
            case "desc"     : $sortlink[$i] = "<a href=\"messages.php?show_all=".attr($showall)."&sortby=".attr($sortby)."&sortorder=asc&".attr($activity_string_html)."\" onclick=\"top.restoreSession()\"><img src=\"../../../images/sortdown.gif\" border=0 alt=\"".htmlspecialchars( xl('Sort Down'), ENT_QUOTES)."\"></a>"; break;
        } break;
    }
}
// Manage page numbering and display beneath the Messages table.
$listnumber = 25;
$total = getPnotesByUser($active,$show_all,$_SESSION['authUser'],true);
if($begin == "" or $begin == 0) {
    $begin = 0;
}
$prev = $begin - $listnumber;
$next = $begin + $listnumber;
$start = $begin + 1;
$end = $listnumber + $start - 1;
if($end >= $total) {
    $end = $total;
}
if($end < $start) {
    $start = 0;
}
if($prev >= 0) {
    $prevlink = "<a href=\"messages.php?show_all=".attr($showall)."&sortby=".attr($sortby)."&sortorder=".attr($sortorder)."&begin=".attr($prev)."&".attr($activity_string_html)."\" onclick=\"top.restoreSession()\"><<</a>";
}
else {
    $prevlink = "<<";
}

if($next < $total) {
    $nextlink = "<a href=\"messages.php?show_all=".attr($showall)."&sortby=".attr($sortby)."&sortorder=".attr($sortorder)."&begin=".attr($next)."&".attr($activity_string_html)."\" onclick=\"top.restoreSession()\">>></a>";
}
else {
    $nextlink = ">>";
}
// Display the Messages table header.
echo "
    <table class='table well'><tr><td><table class='table table-bordered table-hover'>
    <form name=MessageList action=\"messages.php?showall=".attr($showall)."&sortby=".attr($sortby)."&sortorder=".attr($sortorder)."&begin=".attr($begin)."&".attr($activity_string_html)."\" method=post>
    <input type=hidden name=task value=delete>
        <tr height=\"24\">
            <td align=\"center\" width=\"25\"><input type=checkbox id=\"checkAll\" onclick=\"selectAll()\"></td>
            <td width=\"20%\">&nbsp;<b>" .
    htmlspecialchars( xl('From'), ENT_NOQUOTES) . "</b> $sortlink[0]</td>
            <td width=\"20%\">&nbsp;<b>" .
    htmlspecialchars( xl('Patient'), ENT_NOQUOTES) . "</b> $sortlink[1]</td>
            <td>&nbsp;<b>" .
    htmlspecialchars( xl('Type'), ENT_NOQUOTES) . "</b> $sortlink[2]</td>
            <td width=\"15%\">&nbsp;<b>" .
    htmlspecialchars( xl('Date'), ENT_NOQUOTES) . "</b> $sortlink[3]</td>
            <td width=\"15%\">&nbsp;<b>" .
    htmlspecialchars( xl('Status'), ENT_NOQUOTES) . "</b> $sortlink[4]</td>
        </tr>";
// Display the Messages table body.
$count = 0;
$result = getPnotesByUser($active,$show_all,$_SESSION['authUser'],false,$sortby,$sortorder,$begin,$listnumber);
while ($myrow = sqlFetchArray($result)) {
    $name = $myrow['user'];
    $name = $myrow['users_lname'];
    if ($myrow['users_fname']) {
        $name .= ", " . $myrow['users_fname'];
    }
    $patient = $myrow['pid'];
    if ($patient>0) {
        $patient = $myrow['patient_data_lname'];
        if ($myrow['patient_data_fname']) {
            $patient .= ", " . $myrow['patient_data_fname'];
        }
    } else {
        $patient = "* Patient must be set manually *";
    }
    $count++;
    echo "
            <tr id=\"row".attr($count)."\" style=\"background:white\" height=\"24\">
                <td align=\"center\" style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><input type=checkbox id=\"check".attr($count)."\" name=\"delete_id[]\" value=\"" .
        htmlspecialchars( $myrow['id'], ENT_QUOTES) . "\" onclick=\"if(this.checked==true){ selectRow('row".attr($count)."'); }else{ deselectRow('row".attr($count)."'); }\"></td>
                <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
        htmlspecialchars( $name, ENT_NOQUOTES) . "</td><td width=5></td></tr></table></td>
                <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\"><a href=\"messages.php?showall=".attr($showall)."&sortby=".attr($sortby)."&sortorder=".attr($sortorder)."&begin=".attr($begin)."&task=edit&noteid=" .
        htmlspecialchars( $myrow['id'], ENT_QUOTES) . "&".attr($activity_string_html)."\" onclick=\"top.restoreSession()\">" .
        htmlspecialchars( $patient, ENT_NOQUOTES) . "</a></td><td width=5></td></tr></table></td>
                <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
        htmlspecialchars( $myrow['title'], ENT_NOQUOTES) . "</td><td width=5></td></tr></table></td>
                <td style=\"border-bottom: 1px #000000 solid; border-right: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
        htmlspecialchars( oeFormatShortDate(substr($myrow['date'], 0, strpos($myrow['date'], " "))), ENT_NOQUOTES) . "</td><td width=5></td></tr></table></td>
                <td style=\"border-bottom: 1px #000000 solid;\"><table cellspacing=0 cellpadding=0 width=100%><tr><td width=5></td><td class=\"text\">" .
        htmlspecialchars( $myrow['message_status'], ENT_NOQUOTES) . "</td><td width=5></td></tr></table></td>
            </tr>";
}
// Display the Messages table footer.
echo "
    </form></table>
    <table class='table well'>
        <tr>
            <td class=\"text\"><a role='button' class='cp-positive' href=\"messages.php?showall=".attr($showall)."&sortby=".attr($sortby)."&sortorder=".attr($sortorder)."&begin=".attr($begin)."&task=addnew&".attr($activity_string_html)."\" onclick=\"top.restoreSession()\">" .
    htmlspecialchars( xl('Add New'), ENT_NOQUOTES) . "</a> &nbsp; <a role='button' class='deleter cp-negative' href=\"javascript:confirmDeleteSelected()\" onclick=\"top.restoreSession()\">" .
    htmlspecialchars( xl('Delete'), ENT_NOQUOTES) . "</a></td>
            <td align=right class=\"text amount-msg\">$prevlink &nbsp; ".text($end)." of ".text($total)." &nbsp; $nextlink</td>
        </tr>
    </table></td></tr></table><br>";
?>
    <script language="javascript">
        // This is to confirm delete action.
        function confirmDeleteSelected() {
            if(confirm("<?php echo htmlspecialchars( xl('Do you really want to delete the selection?'), ENT_QUOTES); ?>")) {
                document.MessageList.submit();
            }
        }
        // This is to allow selection of all items in Messages table for deletion.
        function selectAll() {
            if(document.getElementById("checkAll").checked==true) {
                document.getElementById("checkAll").checked=true;<?php
                for($i = 1; $i <= $count; $i++) {
                    echo "document.getElementById(\"check".attr($i)."\").checked=true; document.getElementById(\"row".attr($i)."\").style.background='#E7E7E7';  ";
                } ?>
            }
            else {
                document.getElementById("checkAll").checked=false;<?php
                for($i = 1; $i <= $count; $i++) {
                    echo "document.getElementById(\"check".attr($i)."\").checked=false; document.getElementById(\"row".attr($i)."\").style.background='#F7F7F7';  ";
                } ?>
            }
        }
        // The two functions below are for managing row styles in Messages table.
        function selectRow(row) {
            document.getElementById(row).style.background = "#E7E7E7";
        }
        function deselectRow(row) {
            document.getElementById(row).style.background = "#F7F7F7";
        }
    </script><?php
}
?>

</body>
</html>
