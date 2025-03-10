<?php
/**
 *
 * Copyright (C) 2012-2013 Naina Mohamed <naina@capminds.com> CapMinds Technologies
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
 * @author  Naina Mohamed <naina@capminds.com>
 * @link    http://librehealth.io
 */

//SANITIZE ALL ESCAPES
 $sanitize_all_escapes=true;

 //STOP FAKE REGISTER GLOBALS
 $fake_register_globals=false;
 
include_once("../../globals.php");
include_once("$srcdir/api.inc");
require_once("$srcdir/patient.inc");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/formsoptions.inc.php");
formHeader("Form:Transfer Summary");
$returnurl = 'encounter_top.php';
$form_name = 'form_transfer_summary';
$formid = 0 + (isset($_GET['id']) ? $_GET['id'] : '');
if (empty($formid)) {
        $formid = checkFormIsActive($form_name,$encounter);
}
$obj = $formid ? formFetch("form_transfer_summary", $formid) : array();
require_once($GLOBALS['srcdir']."/formatting.inc.php");
$DateFormat = DateFormatRead();
$DateLocale = getLocaleCodeForDisplayLanguage($GLOBALS['language_default']);

?>
<html>
<head>
<?php html_header_show();?>
<script type="text/javascript" src="../../../library/dialog.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">

<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.9.1.min.js"></script>
<script language="JavaScript">
 $(document).ready(function() {
  var win = top.printLogSetup ? top : opener.top;
  win.printLogSetup(document.getElementById('printbutton'));
 });
</script>

</head>

<body class="body_top">
<p><span class="forms-title"><?php echo xlt('Transfer Summary'); ?></span></p>
</br>
<?php
echo "<form method='post' name='my_form' " .
  "action='$rootdir/forms/transfer_summary/save.php?id=" . attr($formid) ."'>\n";
?>
<table  border="0">
<tr>
<td align="left" class="forms" class="forms"><?php echo xlt('Client Name' ); ?>:</td>
        <td class="forms">
            <label class="forms-data"> <?php if (is_numeric($pid)) {
    
    $result = getPatientData($pid, "fname,lname,squad");
   echo text($result['fname'])." ".text($result['lname']);}
   $patient_name=($result['fname'])." ".($result['lname']);
   ?>
   </label>
   <input type="hidden" name="client_name" value="<?php echo attr($patient_name);?>">
        </td>
        <td align="left"  class="forms"><?php echo xlt('DOB'); ?>:</td>
        <td class="forms">
        <label class="forms-data"> <?php if (is_numeric($pid)) {
    
    $result = getPatientData($pid, "*");
   echo text($result['DOB']);}
   $dob=($result['DOB']);
   ?>
   </label>
     <input type="hidden" name="DOB" value="<?php echo attr($dob);?>">
        </td>
        </tr>
    <tr>
    
    <td align="left" class="forms"><?php echo xlt('Transfer to'); ?>:</td>
    
     <td class="forms">
         <input type="text" name="transfer_to" id="transfer_to" value="<?php echo text($obj{"transfer_to"});?>"></td>
         
        <td align="left" class="forms"><?php echo xlt('Transfer date'); ?>:</td>
        <td class="forms">
               <input type='text' size='10' name='transfer_date' id='transfer_date' <?php echo attr ($disabled)?>;
               value='<?php echo htmlspecialchars(oeFormatShortDate(attr($obj{"transfer_date"}))); ?>'
               title='<?php echo xla('yyyy-mm-dd Date of service'); ?>'/>
        </td>

    </tr>
        
    <tr>
        <td align="left colspan="3" style="padding-bottom:7px;"></td>
    </tr>
    
    <tr>
        <td align="left" class="forms"><b><?php echo xlt('Status Of Admission'); ?>:</b></td>
        <td colspan="3"><textarea name="status_of_admission" rows="3" cols="60" wrap="virtual name"><?php echo text($obj{"status_of_admission"});?></textarea></td>
        </tr>
        <tr>
        <td align="left colspan="3" style="padding-bottom:7px;"></td>
    </tr>
    <tr>
        <td align="left" class="forms"><b><?php echo xlt('Diagnosis'); ?>:</b></td>
        <td colspan="3"><textarea name="diagnosis" rows="3" cols="60" wrap="virtual name"><?php echo text($obj{"diagnosis"});?></textarea></td>
            </tr>
            <tr>
        <td align="left colspan="3" style="padding-bottom:7px;"></td>
    </tr>
    <tr>
        <td align="left" class="forms"><b><?php echo xlt('Intervention Provided'); ?>:</b></td>
        <td colspan="3"><textarea name="intervention_provided" rows="3" cols="60" wrap="virtual name"><?php echo text($obj{"intervention_provided"});?></textarea></td>
    </tr>
    <tr>
        <td align="left colspan="3" style="padding-bottom:7px;"></td>
    </tr>
    <tr>
        <td align="left" class="forms"><b><?php echo xlt('Overall Status Of Discharge'); ?>:</b></td>
        <td colspan="3"><textarea name="overall_status_of_discharge" rows="3" cols="60" wrap="virtual name"><?php echo text($obj{"overall_status_of_discharge"});?></textarea></td>
    </tr>
    
<tr>
        <td align="left colspan="3" style="padding-bottom:7px;"></td>
    </tr>
    
    <tr>
        <td align="left colspan="3" style="padding-bottom:7px;"></td>
    </tr>
    <tr>
        <td></td>
    <td><input type='submit'  value='<?php echo xlt('Save');?>' class="button-css">&nbsp;
    <input type='button' value='<?php echo xla('Print'); ?>' id='printbutton' />&nbsp;
    <input type='button' class="button-css" value='<?php echo xla('Cancel'); ?>'
 onclick="top.restoreSession();location='<?php echo "$rootdir/patient_file/encounter/$returnurl" ?>'" />

 </td>
    </tr>
</table>
</form>

<link rel="stylesheet" href="../../../library/css/jquery.datetimepicker.css">
<script type="text/javascript" src="../../../library/js/jquery.datetimepicker.full.min.js"></script>
<script>
    $(function() {
        $("#transfer_date").datetimepicker({
            timepicker: false,
            format: "<?= $DateFormat; ?>"
        });
        $.datetimepicker.setLocale('<?= $DateLocale;?>');
    });

</script>
<?php
formFooter();
?>
