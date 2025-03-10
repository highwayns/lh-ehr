<?php
// +-----------------------------------------------------------------------------+
// Copyright (C) 2012 NP Clinics <info@npclinics.com.au>
//
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
//
// A copy of the GNU General Public License is included along with this program:
// libreehr/interface/login/GnuGPL.html
// For more information write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
// Author:   Scott Wakefield <scott@npclinics.com.au>
//
// +------------------------------------------------------------------------------+

//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;


//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;


require_once("../globals.php");
require_once("$srcdir/headers.inc.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/formdata.inc.php");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/headers.inc.php");
require_once("$srcdir/acl.inc");

// Ensure authorized
if (!acl_check('admin', 'users')) {
  die(xlt("Unauthorized"));
}

$alertmsg = '';

if ( isset($_POST["mode"]) && $_POST["mode"] == "facility_user_id" && isset($_POST["user_id"]) && isset($_POST["fac_id"]) ) {
  // Inserting/Updating new facility specific user information
  $fres = sqlStatement("SELECT * FROM `layout_options` " .
                       "WHERE `form_id` = 'FACUSR' AND `uor` > 0 AND `field_id` != '' " .
                       "ORDER BY `group_name`, `seq`");
  while ($frow = sqlFetchArray($fres)) {
    $value = get_layout_form_value($frow);
    $entry_id = sqlQuery("SELECT `id` FROM `facility_user_ids` WHERE `uid` = ? AND `facility_id` = ? AND `field_id` =?", array($_POST["user_id"],$_POST["fac_id"],$frow['field_id']) );
    if (empty($entry_id)) {
      // Insert new entry
      sqlInsert("INSERT INTO `facility_user_ids` (`uid`, `facility_id`, `field_id`, `field_value`) VALUES (?,?,?,?)", array($_POST["user_id"],$_POST["fac_id"],$frow['field_id'], $value) );
    }
    else {
      // Update existing entry
      sqlStatement("UPDATE `facility_user_ids` SET `field_value` = ? WHERE `id` = ?", array($value,$entry_id['id']) );
    }
  }
}

?>
<html>
<head>
    <?php call_required_libraries(array("jquery-min-3-1-1","bootstrap","font-awesome" , "iziModalToast")); ?>
    <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/common.js"></script>
    <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $(".facitityUser").click(function () {
            var iter1 = $(this).attr("data-text1");
            var iter2 = $(this).attr("data-text2");
            var title = $(this).children('span').text();
            initIziLink(iter1 , iter2, title);
        });

        function initIziLink(iter1, iter2, title) {
            $("#facilityUser-iframe").iziModal({
                title: 'User - <b style="color: white">'+title+'</b>',
                subtitle: '',
                headerColor: '#88A0B9',
                closeOnEscape: true,
                fullscreen:true,
                overlayClose: false,
                closeButton: true,
                theme: 'light',  // light
                iframe: true,
                width:500,
                focusInput: true,
                padding:5,
                iframeHeight: 250,
                iframeURL:'facility_user_admin.php?user_id='+iter1 + '&fac_id='+iter2,
                onClosed:function () {
                    location.reload();
                }
            });

            setTimeout(function () {
                call_izi();
            },200);
        }

        function call_izi() {
            $("#facilityUser-iframe").iziModal('open');
        }

    });
</script>

</head>
<body class="body_top">
<div id="facilityUser-iframe"></div>

<?php
// Collect all users
$u_res = sqlStatement("select * from `users` WHERE `username` != '' AND `active` = 1 order by `username`");

// Collect all facilities and store them in an array
$f_res = sqlStatement("select * from `facility` order by `name`");
$f_arr = array();
for($i=0; $row=sqlFetchArray($f_res); $i++) {
  $f_arr[$i]=$row;
}

// Collect layout information and store them in an array
$l_res = sqlStatement("SELECT * FROM layout_options " .
                      "WHERE form_id = 'FACUSR' AND uor > 0 AND field_id != '' " .
                      "ORDER BY group_name, seq");
$l_arr = array();
for($i=0; $row=sqlFetchArray($l_res); $i++) {
  $l_arr[$i]=$row;
}

?>

<div>
    <div>
       <table>
      <tr >
        <td><b><?php echo xlt('Facility Specific User Information'); ?></b></td>
        <td><a href="usergroup_admin.php" class="css_button cp-misc" onclick="top.restoreSession()"><span><?php echo xlt('Back to Users'); ?></span></a>
        </td>
     </tr>
    </table>
    </div>

    <div style="width:400px;">
        <div>

            <table class="table table-hover" cellpadding="1" cellspacing="0" class="showborder">
                <tbody><tr height="22" class="showborder_head">
                    <th><b><?php echo xlt('Username'); ?></b></th>
                    <th><b><?php echo xlt('Full Name'); ?></b></th>
                    <th><b><span class="bold"><?php echo xlt('Facility'); ?></span></b></th>
                                        <?php
                                        foreach ($l_arr as $layout_entry) {
                                          echo "<th width='100px'><b><span class='bold'>" . text(xl_layout_label($layout_entry['title'])) . "&nbsp;</span></b></th>";
                                        }
                                        ?>
                </tr>
                    <?php
                    while ($user = sqlFetchArray($u_res)) {
                        foreach ($f_arr as $facility) {
                    ?>
                <tr height="20"  class="text" style="border-bottom: 1px dashed;">
                    <td class="text"><b><a data-text1="<?php echo attr($user['id']);?>" data-text2="<?php echo attr($facility['id']);?>" href="#" class="facitityUser" onclick="top.restoreSession()"><span><?php echo text($user['username']);?></span></a></b>&nbsp;</td>
                    <td><span class="text"><?php echo text($user['fname'] . " " . $user['lname']);?></span>&nbsp;</td>
                    <td><span class="text"><?php echo text($facility['name']);?>&nbsp;</td>
                                   <?php
                                   foreach ($l_arr as $layout_entry) {
                                     $entry_data = sqlQuery("SELECT `field_value` FROM `facility_user_ids` " .
                                                            "WHERE `uid` = ? AND `facility_id` = ? AND `field_id` = ?", array($user['id'],$facility['id'],$layout_entry['field_id']) );
                                     echo "<td><span class='text'>" . generate_display_field($layout_entry,$entry_data['field_value']) . "&nbsp;</td>";
                                   }
                                   ?>
                </tr>
                <?php
                }}
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
