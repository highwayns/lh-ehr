<?php
/**
 * main_title.php - The main titlebar, at the top of the 'concurrent' layout.
 */

include_once('../globals.php');

?>
<html>
<head>

<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
<style type="text/css">
      .hidden {
        display:none;
      }
      .visible{
        display:block;
      }
</style>


<script type="text/javascript" language="javascript">
function toencounter(rawdata) {
//This is called in the on change event of the Encounter list.
//It opens the corresponding pages.
    document.getElementById('EncounterHistory').selectedIndex=0;
    if(rawdata=='')
     {
         return false;
     }
    else if(rawdata=='New Encounter')
     {
        top.window.parent.left_nav.loadFrame2('nen1','RBot','forms/patient_encounter/new.php?autoloaded=1&calenc=')
        return true;
     }
    else if(rawdata=='Past Encounter List')
     {
        top.window.parent.left_nav.loadFrame2('pel1','RBot','patient_file/history/encounters.php')
        return true;
     }
    var parts = rawdata.split("~");
    var enc = parts[0];
    var datestr = parts[1];
    var f = top.window.parent.left_nav.document.forms[0];
    frame = 'RBot';
    if (!f.cb_bot.checked) frame = 'RTop'; else if (!f.cb_top.checked) frame = 'RBot';

    top.restoreSession();
    parent.left_nav.setEncounter(datestr, enc, frame);
    parent.left_nav.setRadio(frame, 'enc');
    top.frames[frame].location.href  = '../patient_file/encounter/encounter_top.php?set_encounter=' + enc;
}
</script>

</head>
<body class="body_title">
<?php
$res = sqlQuery("select * from users where username=?", array($_SESSION{"authUser"}));
?>

<table id="main-title" cellspacing="0" cellpadding="0" width="100%" height="100%">
<tr>
<td align="left">
    <table cellspacing="0" cellpadding="1" style="margin:0px 0px 0px 3px;">

<?php if (acl_check('patients','demo','',array('write','addonly') )) { ?>
<tr><td style="vertical-align:text-bottom;">
        <a href='' class="css_button_small" style="margin:0px;vertical-align:top;" id='new0' onClick=" return top.window.parent.left_nav.loadFrame2('new0','RTop','new/new_comprehensive.php')">
        <span><?php echo htmlspecialchars( xl('NEW PATIENT'), ENT_QUOTES); ?></span></a>
    </td>
    <td style="vertical-align:text-bottom;">
            <a href='' class="css_button_small" style="margin:0px;vertical-align:top;display:none;" id='clear_active' onClick="javascript:parent.left_nav.clearactive();return false;">
            <span><?php echo htmlspecialchars( xl('CLEAR ACTIVE PATIENT'), ENT_QUOTES); ?></span></a>
    </td>
</tr>
<?php } //end of acl_check('patients','demo','',array('write','addonly') if ?>

    </table>
</td>
<td style="margin:3px 0px 3px 0px;vertical-align:middle;">
        <div style='margin-left:10px; float:left; display:none' id="current_patient_block">
            <span class='text'><?php xl('Patient','e'); ?>:&nbsp;</span><span class='title_bar_top' id="current_patient"><b><?php xl('None','e'); ?></b></span>
        </div>
</td>
<td style="margin:3px 0px 3px 0px;vertical-align:middle;" align="left">
    <table cellspacing="0" cellpadding="1" ><tr><td>
        <div style='margin-left:5px; float:left; display:none' id="past_encounter_block">
            <span class='title_bar_top' id="past_encounter"><b><?php echo htmlspecialchars( xl('None'), ENT_QUOTES) ?></b></span>
        </div></td></tr>
    <tr><td valign="baseline" align="center">   
        <div style='display:none' class='text' id="current_encounter_block" >
            <span class='text'><?php xl('Selected Encounter','e'); ?>:&nbsp;</span><span class='title_bar_top' id="current_encounter"><b><?php xl('None','e'); ?></b></span>
        </div></td></tr></table>
</td>

<td align="right">
    <table cellspacing="0" cellpadding="1" style="margin:0px 3px 0px 0px;"><tr>
        <td align="right" class="text" style="vertical-align:text-bottom;"><a href='main_title.php' onclick="javascript:parent.left_nav.goHome();return false;" ><?php xl('Home','e'); ?></a>
        &nbsp;|&nbsp;
        <a href="http://librehealth.io/wiki/index.php/Users_Guide" target="_blank" id="help_link" >
            <?php xl('Manual','e'); ?></a>&nbsp;</td>
        <td align="right" style="vertical-align:top;"><a href="../logout.php" target="_top" class="css_button_small" style='float:right;' id="logout_link" onclick="top.restoreSession()" >
            <span><?php echo htmlspecialchars( xl('Logout'), ENT_QUOTES) ?></span></a></td>
    </tr><tr>
        <td colspan='2' valign="baseline" align='right'><B>
            <span class="text title_bar_top" title="<?php echo htmlspecialchars( xl('Authorization group') .': '.$_SESSION['authGroup'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($res{"fname"}.' '.$res{"lname"},ENT_NOQUOTES); ?></span></span></td>
        </tr></table>
</td>
</tr>
</table>

<script type="text/javascript" language="javascript">
parent.loadedFrameCount += 1;
</script>

</body>
</html>
