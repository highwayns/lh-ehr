<?php
/*
 *  Billing Report Program
 *
 *  This program displays the main search and select screen for claims generation
 *
 *  The changes to this file as of November 16 2016 to add the 1500 pre-printed form
 *  are covered under the terms of the Mozilla Public License, v. 2.0
 *
 * @copyright Copyright (C) 2016-2017 Terry Hill <teryhill@librehealth.io>
 * No previous copyright listed in file. This was an original OpenEMR program.
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
 * @package LibreHealth EHR
 * @author Terry Hill <teryhill@librehealth.io>
 * @link http://librehealth.io
 *
 * Please help the overall project by sending changes you make to the author and to the LibreHealth EHR community.
 * Added hooks for UB04 and End of day reporting Terry Hill 2014 teryhill@librehealth.io
 *
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;

require_once("../globals.php");
require_once("../../library/acl.inc");
require_once("../../custom/code_types.inc.php");
require_once("$srcdir/patient.inc");
include_once("$srcdir/../interface/reports/report.inc.php");//Criteria Section common php page
require_once("$srcdir/billrep.inc");
require_once(dirname(__FILE__) . "/../../library/classes/OFX.class.php");
require_once(dirname(__FILE__) . "/../../library/classes/X12Partner.class.php");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/headers.inc.php");
require_once("$srcdir/options.inc.php");
require_once("adjustment_reason_codes.php");

$EXPORT_INC = "$webserver_root/custom/BillingExport.php";
//echo $GLOBALS['daysheet_provider_totals'];

$daysheet = false;
$daysheet_total = false;
$provider_run = false;
$DateFormat = DateFormatRead();

if ($GLOBALS['use_custom_daysheet'] != 0) {
  $daysheet = true;
  if ($GLOBALS['daysheet_provider_totals'] == 1) {
   $daysheet_total = true;
   $provider_run =  false;
  }
  if ($GLOBALS['daysheet_provider_totals'] == 0) {
   $daysheet_total = false;
   $provider_run =  true;
  }
}

$alertmsg = '';

if (isset($_POST['mode'])) {
  if ($_POST['mode'] == 'export') {
    $sql = ReturnOFXSql();
    $db = get_db();
    $results = $db->Execute($sql);
    $billings = array();
    if ($results->RecordCount() == 0) {
      echo xlt("No Bills Found to Include in OFX Export")."<br>";
    } else {
      while(!$results->EOF) {
        $billings[] = $results->fields;
        $results->MoveNext();
      }
      $ofx = new OFX($billings);
      header("Pragma: public");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Content-Disposition: attachment; filename=libreehr_ofx.ofx");
      header("Content-Type: text/xml");
      echo $ofx->get_OFX();
      exit;
    }
  }


}

//global variables:
$from_date     = isset($_POST['from_date'])  ? $_POST['from_date']  : date('Y-m-d');
$to_date       = isset($_POST['to_date'  ])  ? $_POST['to_date'  ]  : '';
$code_type     = isset($_POST['code_type'])  ? $_POST['code_type']  : 'all';
$unbilled      = isset($_POST['unbilled' ])  ? $_POST['unbilled' ]  : 'on';
$my_authorized = isset($_POST["authorized"]) ? $_POST["authorized"] : '';


// This tells us if only encounters that appear to be missing a "25" modifier
// are to be reported.
$missing_mods_only = (isset($_POST['missing_mods_only']) && !empty($_POST['missing_mods_only']));

$left_margin = isset($_POST["left_margin"]) ? $_POST["left_margin"] : 24;
$top_margin  = isset($_POST["top_margin"] ) ? $_POST["top_margin" ] : 20;
// if this
$ubleft_margin = isset($_POST["ubleft_margin"]) ? $_POST["ubleft_margin"] : $GLOBALS['ubleft_margin_default'];
$ubtop_margin  = isset($_POST["ubtop_margin"] ) ? $_POST["ubtop_margin" ] : $GLOBALS['ubtop_margin_default'];
//}

$ofrom_date  = $from_date;
$oto_date    = $to_date;
$ocode_type  = $code_type;
$ounbilled   = $unbilled;
$oauthorized = $my_authorized;
?>

<html>
<head>
<?php if (function_exists('html_header_show')) html_header_show(); ?>
<link rel="stylesheet" href="<?php echo $css_header; ?>" type="text/css">
<style>
.subbtn { margin-top:3px; margin-bottom:3px; margin-left:2px; margin-right:2px }
</style>
<script>

function select_all() {
  for($i=0;$i < document.update_form.length;$i++) {
    $name = document.update_form[$i].name;
    if ($name.substring(0,7) == "claims[" && $name.substring($name.length -6) == "[bill]") {
      document.update_form[$i].checked = true;
    }
  }
  set_button_states();
}

function set_button_states() {
  var f = document.update_form;
  var count0 = 0; // selected and not billed or queued
  var count1 = 0; // selected and queued
  var count2 = 0; // selected and billed
  for($i = 0; $i < f.length; ++$i) {
    $name = f[$i].name;
    if ($name.substring(0, 7) == "claims[" && $name.substring($name.length -6) == "[bill]" && f[$i].checked == true) {
      if      (f[$i].value == '0') ++count0;
      else if (f[$i].value == '1' || f[$i].value == '5') ++count1;
      else ++count2;
    }
  }

  var can_generate = (count0 > 0 || count1 > 0 || count2 > 0);
  var can_mark     = (count1 > 0 || count0 > 0 || count2 > 0);
  var can_bill     = (count0 == 0 && count1 == 0 && count2 > 0);

<?php if (file_exists($EXPORT_INC)) { ?>
  f.bn_external.disabled        = !can_generate;
<?php } else { ?>
  // f.bn_hcfa_print.disabled      = !can_generate;
  // f.bn_hcfa.disabled            = !can_generate;
  // f.bn_ub92_print.disabled      = !can_generate;
  // f.bn_ub92.disabled            = !can_generate;
<?php if ($GLOBALS['claim_type'] =='0' || $GLOBALS['claim_type'] =='2' ) { ?>
  f.bn_x12.disabled             = !can_generate;
<?php } ?>
<?php if ($GLOBALS['support_encounter_claims']) { ?>
  f.bn_x12_encounter.disabled   = !can_generate;
<?php } ?>
<?php if ($GLOBALS['claim_type'] =='0' || $GLOBALS['claim_type'] =='2' ) { ?>
  f.bn_process_hcfa.disabled    = !can_generate;
<?php if ($GLOBALS['preprinted_cms_1500']) { ?>
  f.bn_process_hcfa_form.disabled    = !can_generate;
<?php } ?>
  f.bn_hcfa_txt_file.disabled   = !can_generate;
 <?php } ?>
<?php if ($GLOBALS['claim_type'] =='1' || $GLOBALS['claim_type'] =='2' ) { ?>
  f.bn_process_ub04.disabled    = !can_generate;
  f.bn_ub04_txt_file.disabled   = !can_generate;
  f.bn_837I.disabled            = !can_generate;
 <?php } ?>
  // f.bn_electronic_file.disabled = !can_bill;
  f.bn_reopen.disabled          = !can_bill;
<?php } ?>
  f.bn_mark.disabled            = !can_mark;
}

// Process a click to go to an encounter.
function toencounter(pid, pubpid, pname, enc, datestr, dobstr) {
 top.restoreSession();
     encurl = 'patient_file/encounter/encounter_top.php?set_encounter=' + enc + '&pid=' + pid;
 parent.left_nav.setPatient(pname,pid,pubpid,'',dobstr);
     parent.left_nav.setEncounter(datestr, enc, 'enc');
     parent.left_nav.loadFrame('enc2', 'enc', encurl);
}
// Process a click to go to an patient.
function topatient(pid, pubpid, pname, enc, datestr, dobstr) {
 top.restoreSession();
    paturl = 'patient_file/summary/demographics_full.php?pid=' + pid;
 parent.left_nav.setPatient(pname,pid,pubpid,'',dobstr);
    parent.left_nav.loadFrame('dem1', 'pat1', paturl);
}
</script>
<script language="javascript" type="text/javascript">
EncounterDateArray=new Array;
CalendarCategoryArray=new Array;
EncounterIdArray=new Array;
function SubmitTheScreen()
 {//Action on Update List link
  if(!ProcessBeforeSubmitting())
   return false;
  top.restoreSession();
  document.the_form.mode.value='change';
  document.the_form.target='_self';
  document.the_form.action='billing_report.php';
  document.the_form.submit();
  return true;
 }
function SubmitTheScreenPrint()
 {//Action on View Printable Report link
  if(!ProcessBeforeSubmitting())
   return false;
  top.restoreSession();
  document.the_form.target='new';
  document.the_form.action='print_billing_report.php';
  document.the_form.submit();
  return true;
 }
  function SubmitTheEndDayPrint()
 {//Action on View End of Day Report link
  if(!ProcessBeforeSubmitting())
   return false;
  top.restoreSession();
  document.the_form.target='new';
<?php if ($GLOBALS['use_custom_daysheet'] == 1) { ?>
  document.the_form.action='print_daysheet_report_num1.php';
<?php } ?>
<?php if ($GLOBALS['use_custom_daysheet'] == 2) { ?>
  document.the_form.action='print_daysheet_report_num2.php';
<?php } ?>
<?php if ($GLOBALS['use_custom_daysheet'] == 3) { ?>
  document.the_form.action='print_daysheet_report_num3.php';
<?php } ?>
<?php if ($GLOBALS['use_custom_daysheet'] == 4) { ?>
  document.the_form.action='print_daysheet_report_num4.php';
<?php } ?>
  document.the_form.submit();
  return true;
 }
function SubmitTheScreenExportOFX()
 {//Action on Export OFX link
  if(!ProcessBeforeSubmitting())
   return false;
  top.restoreSession();
  document.the_form.mode.value='export';
  document.the_form.target='_self';
  document.the_form.action='billing_report.php';
  document.the_form.submit();
  return true;
 }
function TestExpandCollapse()
 {//Checks whether the Expand All, Collapse All labels need to be placed.If any result set is there these will be placed.
    var set=-1;
    for(i=1;i<=document.getElementById("divnos").value;i++)
    {
        var ele = document.getElementById("divid_"+i);
        if(ele)
        {
        set=1;
        break;
        }
    }
    if(set==-1)
         {
         if(document.getElementById("ExpandAll"))
          {
             document.getElementById("ExpandAll").innerHTML='';
             document.getElementById("CollapseAll").innerHTML='';
          }
         }
 }
function expandcollapse(atr){
    if(atr == "expand") {//Called in the Expand All, Collapse All links(All items will be expanded or collapsed)
        for(i=1;i<=document.getElementById("divnos").value;i++){
            var mydivid="divid_"+i;var myspanid="spanid_"+i;
                var ele = document.getElementById(mydivid);    var text = document.getElementById(myspanid);
                if(ele)
                 {
                    ele.style.display = "inline";text.innerHTML = "<?php echo xla('Collapse'); ?>";
                 }
        }
      }
    else {
        for(i=1;i<=document.getElementById("divnos").value;i++){
            var mydivid="divid_"+i;var myspanid="spanid_"+i;
                var ele = document.getElementById(mydivid);    var text = document.getElementById(myspanid);
                if(ele)
                 {
                    ele.style.display = "none";    text.innerHTML = "<?php echo xla('Expand'); ?>";
                 }
        }
    }

}
function divtoggle(spanid, divid) {//Called in the Expand, Collapse links(This is for a single item)
    var ele = document.getElementById(divid);
    if(ele)
     {
        var text = document.getElementById(spanid);
        if(ele.style.display == "inline") {
            ele.style.display = "none";
            text.innerHTML = "<?php echo xla('Expand'); ?>";
        }
        else {
            ele.style.display = "inline";
            text.innerHTML = "<?php echo xla('Collapse'); ?>";
        }
     }
}
function MarkAsCleared(Type)
 {
  CheckBoxBillingCount=0;
  for (var CheckBoxBillingIndex =0; ; CheckBoxBillingIndex++)
   {
    CheckBoxBillingObject=document.getElementById('CheckBoxBilling'+CheckBoxBillingIndex);
    if(!CheckBoxBillingObject)
     break;
    if(CheckBoxBillingObject.checked)
     {
       ++CheckBoxBillingCount;
     }
   }
   if(Type==1)
    {
     Message='<?php echo xla('After saving your batch, click [View Log] to check for errors.'); ?>';
    }
   if(Type==2)
    {
     Message='<?php echo xla('After saving the PDF, click [View Log] to check for errors.'); ?>';
    }
   if(Type==3)
    {
     Message='<?php echo xla('After saving the TEXT file(s), click [View Log] to check for errors.'); ?>';
    }
  if(confirm(Message + "\n\n\n<?php echo addslashes( xl('Total') ); ?>" + ' ' + CheckBoxBillingCount + ' ' +  "<?php echo addslashes( xl('Selected') ); ?>\n" +
  "<?php echo addslashes( xl('Would You Like them to be Marked as Cleared.') ); ?>"))
   {
    document.getElementById('HiddenMarkAsCleared').value='yes';
  }
  else
   {
    document.getElementById('HiddenMarkAsCleared').value='';
   }
 }
</script>
<?php include_once("$srcdir/../interface/reports/report.script.php"); ?><!-- Criteria Section common javascript page-->
<!-- ================================================== -->
<!-- =============Included for Insurance ajax criteria==== -->
<!-- ================================================== -->
<link rel="stylesheet" href="../../library/css/jquery.datetimepicker.css">
<script type="text/javascript" src="../../library/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../../library/js/jquery.datetimepicker.full.min.js"></script>
<?php include_once("{$GLOBALS['srcdir']}/ajax/payment_ajax_jav.inc.php"); ?>
<script type="text/javascript" src="../../library/js/blink/jquery.modern-blink.js"></script>
<script type="text/javascript" src="../../library/js/common.js"></script>
<style>
#ajax_div_insurance {
    position: absolute;
    z-index:10;
    background-color: #FBFDD0;
    border: 1px solid #ccc;
    padding: 10px;
}
</style>
<script language="javascript" type="text/javascript">
document.onclick=TakeActionOnHide;
</script>
<!-- ================================================== -->
<!-- =============Included for Insurance ajax criteria==== -->
<!-- ================================================== -->
</head>
<body class="body_top" onLoad="TestExpandCollapse()">

<p style='margin-top:5px;margin-bottom:5px;margin-left:5px'>
<font class='title'><?php echo xlt('Billing Manager') ?></font>
</p>

<form name='the_form' method='post' action='billing_report.php' onsubmit='return top.restoreSession()' style="display:inline">

<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/textformat.js"></script>
<script language='JavaScript'>
 var mypcc = '1';
</script>

<input type='hidden' name='mode' value='change'>
<!-- ============================================================================================================================================= -->
                                                        <!-- Criteria section Starts -->
<!-- ============================================================================================================================================= -->
<?php
//The following are the search criteria per page.All the following variable which ends with 'Master' need to be filled properly.
//Each item is seperated by a comma(,).
//$ThisPageSearchCriteriaDisplayMaster ==>It is the display on screen for the set of criteria.
//$ThisPageSearchCriteriaKeyMaster ==>Corresponding database fields in the same order.
//$ThisPageSearchCriteriaDataTypeMaster ==>Corresponding data type in the same order.
$ThisPageSearchCriteriaDisplayRadioMaster=array();
$ThisPageSearchCriteriaRadioKeyMaster=array();
$ThisPageSearchCriteriaQueryDropDownMaster=array();
$ThisPageSearchCriteriaQueryDropDownMasterDefault=array();
$ThisPageSearchCriteriaQueryDropDownMasterDefaultKey=array();
$ThisPageSearchCriteriaIncludeMaster=array();

if ($daysheet) {
$ThisPageSearchCriteriaDisplayMaster= array( xl("Date of Service"),xl("Date of Entry"),xl("Date of Billing"),xl("Patient Name"),xl("Patient Id"),xl("Provider"),xl("Referring Provider"),xl("Insurance Company"),xl("Claim Type"),xl("Encounter"),xl("Whether Insured"),xl("Charge Coded"),xl("Billing Status"),xl("Authorization Status"),xl("Last Level Billed"),xl("X12 Partner"),xl("User") );
$ThisPageSearchCriteriaKeyMaster="form_encounter.date,billing.date,claims.process_time,patient_data.fname,".
                                 "form_encounter.pid,form_encounter.provider_id,form_encounter.referring_physician,claims.payer_id,claims.target,form_encounter.encounter,insurance_data.provider,billing.id,billing.billed,".
                                 "billing.authorized,form_encounter.last_level_billed,billing.x12_partner_id,billing.user";
$ThisPageSearchCriteriaDataTypeMaster="datetime,datetime,datetime,text_like,".
                                      "text,query_drop_down,query_drop_down,include,radio,text,radio,radio,radio,".
                                      "radio_like,radio,query_drop_down,text";
}
else
{

$ThisPageSearchCriteriaDisplayMaster= array( xl("Date of Service"),xl("Date of Entry"),xl("Date of Billing"),xl("Patient Name"),xl("Patient Id"),xl("Provider"),xl("Referring Provider"),xl("Insurance Company"),xl("Claim Type"),xl("Encounter"),xl("Whether Insured"),xl("Charge Coded"),xl("Billing Status"),xl("Authorization Status"),xl("Last Level Billed"),xl("X12 Partner") );
$ThisPageSearchCriteriaKeyMaster="form_encounter.date,billing.date,claims.process_time,patient_data.fname,".
                                 "form_encounter.pid,form_encounter.provider_id,form_encounter.referring_physician,claims.payer_id,claims.target,form_encounter.encounter,insurance_data.provider,billing.id,billing.billed,".
                                 "billing.authorized,form_encounter.last_level_billed,billing.x12_partner_id";
$ThisPageSearchCriteriaDataTypeMaster="datetime,datetime,datetime,text_like,".
                                      "text,query_drop_down,query_drop_down,include,radio,text,radio,radio,radio,".
                                      "radio_like,radio,query_drop_down";



}
//The below section is needed if there is any 'radio' or 'radio_like' type in the $ThisPageSearchCriteriaDataTypeMaster
//$ThisPageSearchCriteriaDisplayRadioMaster,$ThisPageSearchCriteriaRadioKeyMaster ==>For each radio data type this pair comes.
//The key value 'all' indicates that no action need to be taken based on this.For that the key must be 'all'.Display value can be any thing.
$ThisPageSearchCriteriaDisplayRadioMaster[1] = array( xl("All"),xl("eClaims"),xl("Paper") );//Display Value
$ThisPageSearchCriteriaRadioKeyMaster[1]="all,standard,hcfa";//Key
$ThisPageSearchCriteriaDisplayRadioMaster[2]= array( xl("All"),xl("Insured"),xl("Non-Insured") );//Display Value
$ThisPageSearchCriteriaRadioKeyMaster[2]="all,1,0";//Key
$ThisPageSearchCriteriaDisplayRadioMaster[3]= array( xl("All"),xl("Coded"),xl("Not Coded") );//Display Value
$ThisPageSearchCriteriaRadioKeyMaster[3]="all,not null,null";//Key
$ThisPageSearchCriteriaDisplayRadioMaster[4]= array( xl("All"),xl("Unbilled"),xl("Billed"),xl("Denied") );//Display Value
$ThisPageSearchCriteriaRadioKeyMaster[4]="all,0,1,7";//Key
$ThisPageSearchCriteriaDisplayRadioMaster[5]= array( xl("All"),xl("Authorized"),xl("Unauthorized") );
$ThisPageSearchCriteriaRadioKeyMaster[5]="%,1,0";
$ThisPageSearchCriteriaDisplayRadioMaster[6]= array( xl("All"),xl("None"),xl("Ins 1"),xl("Ins 2 or Ins 3") );
$ThisPageSearchCriteriaRadioKeyMaster[6]="all,0,1,2";
//The below section is needed if there is any 'query_drop_down' type in the $ThisPageSearchCriteriaDataTypeMaster
$ThisPageSearchCriteriaQueryDropDownMaster[1]="SELECT id, CONCAT(lname, ', ', fname) AS name FROM users WHERE authorized = 1 AND username != '' ORDER BY name ;";
$ThisPageSearchCriteriaQueryDropDownMasterDefault[1]= xl("All");//Only one item will be here
$ThisPageSearchCriteriaQueryDropDownMasterDefaultKey[1]="all";//Only one item will be here
$ThisPageSearchCriteriaQueryDropDownMaster[2]="SELECT id, CONCAT(lname, ', ', fname) AS name FROM users WHERE authorized = 1 OR npi != '' ORDER BY name ;";
$ThisPageSearchCriteriaQueryDropDownMasterDefault[2]= xl("All");//Only one item will be here
$ThisPageSearchCriteriaQueryDropDownMasterDefaultKey[2]="all";//Only one item will be here
$ThisPageSearchCriteriaQueryDropDownMaster[3]="SELECT name,id FROM x12_partners;";
$ThisPageSearchCriteriaQueryDropDownMasterDefault[3]= xl("All");//Only one item will be here
$ThisPageSearchCriteriaQueryDropDownMasterDefaultKey[3]="all";//Only one item will be here
//The below section is needed if there is any 'include' type in the $ThisPageSearchCriteriaDataTypeMaster
//Function name is added here.Corresponding include files need to be included in the respective pages as done in this page.
//It is labled(Included for Insurance ajax criteria)(Line:-279-299).
$ThisPageSearchCriteriaIncludeMaster[1]="InsuranceCompanyDisplay";//This is php function defined in the file 'report.inc.php'

if(!isset($_REQUEST['mode']))//default case
 {
  $_REQUEST['final_this_page_criteria'][0]="(form_encounter.date between '".date("Y-m-d 00:00:00")."' and '".date("Y-m-d 23:59:59")."')";
  $_REQUEST['final_this_page_criteria'][1]="billing.billed = '0'";

  $_REQUEST['final_this_page_criteria_text'][0]=xl("Date of Service = Today");
  $_REQUEST['final_this_page_criteria_text'][1]=xl("Billing Status = Unbilled");

  $_REQUEST['date_master_criteria_form_encounter_date']="today";
  $_REQUEST['master_from_date_form_encounter_date']=date($DateFormat);
  $_REQUEST['master_to_date_form_encounter_date']=date($DateFormat);

  $_REQUEST['radio_billing_billed']=0;

 }
?>
<table width='100%' border="0" cellspacing="0" cellpadding="0">
 <tr>
      <td width="25%">&nbsp;</td>
      <td width="50%">
            <?php include_once("$srcdir/../interface/reports/criteria.tab.php"); ?>
      </td>
      <td width="25%">
<?php
// ============================================================================================================================================= -->
                                                        // Criteria section Ends -->
// ============================================================================================================================================= -->
?>

      <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="15%">&nbsp;</td>
            <td width="85%"><span class='text'><a onClick="javascript:return SubmitTheScreen();" href="#" class=link_submit>[<?php echo xlt('Update List') ?>]</a>
   or
   <a onClick="javascript:return SubmitTheScreenExportOFX();" href="#"  class='link_submit'><?php echo '[' . xlt('Export OFX') .']' ?></a></span>               </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><a onClick="javascript:return SubmitTheScreenPrint();" href="#"
    class='link_submit'  ><?php echo '['. xlt('View Printable Report').']' ?></a></td>
          </tr>

     <?php if ($daysheet) { ?>
          <tr>
            <td>&nbsp;</td>
            <td><a onClick="javascript:return SubmitTheEndDayPrint();" href="#"
    class='link_submit'  ><?php echo '['.xlt('End Of Day Report').']' ?></a>
    <?php if ($daysheet_total) { ?>
    <span class=text><?php echo xlt('Totals'); ?> </span>
    <input type=checkbox  name="end_of_day_totals_only" value="1" <?php if ($obj['end_of_day_totals_only'] === '1') echo "checked";?>>
    <?php } ?>
    <?php if ($provider_run) { ?>
    <span class=text><?php echo xlt('Provider'); ?> </span>
    <input type=checkbox  name="end_of_day_provider_only" value="1" <?php if ($obj['end_of_day_provider_only'] === '1') echo "checked";?>>
    <?php } ?>
    </td>
          </tr>
        <?php } ?>

          <tr>
            <td>&nbsp;</td>
            <td>
            <?php if (! file_exists($EXPORT_INC)) { ?>
               <!--
               <a href="javascript:top.restoreSession();document.the_form.mode.value='process';document.the_form.submit()" class="link_submit"
                title="Process all queued bills to create electronic data (and print if requested)"><?php echo '['. xlt('Start Batch Processing') .']' ?></a>
               &nbsp;
               -->
               <a href='#' id="view-log-link" class='link_submit'
                title='<?php xla('See messages from the last set of generated claims'); ?>'><?php echo '['. xlt('View Log') .']'?></a>
            <?php } ?>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><a href="javascript:select_all()" class="link_submit"><?php  echo '['. xlt('Select All') .']'?></a></td>
          </tr>
      </table>


      </td>
 </tr>
</table>
<table width='100%' border="0" cellspacing="0" cellpadding="0" >
    <tr>
        <td>
            <hr color="#000000">
        </td>
    </tr>
</table>
</form>
<form name='update_form' method='post' action='billing_process.php' onsubmit='return top.restoreSession()' style="display:inline">
<center>
<span class='text' style="display:inline">
<?php if (file_exists($EXPORT_INC)) { ?>
<input type="submit" data-open-popup="true" class="subbtn" name="bn_external" value="Export Billing" title="<?php echo xla('Export to external billing system') ?>">
<input type="submit" data-open-popup="true" class="subbtn" name="bn_mark" value="Mark as Cleared" title="<?php echo xla('Mark as billed but skip billing') ?>">
<?php } else { ?>
<!--
<input type="submit" class="subbtn" name="bn_hcfa_print" value="Queue HCFA &amp; Print" title="<?php echo xla('Queue for HCFA batch processing and printing') ?>">
<input type="submit" class="subbtn" name="bn_hcfa" value="Queue HCFA" title="<?php echo xla('Queue for HCFA batch processing')?>">
<input type="submit" class="subbtn" name="bn_ub92_print" value="Queue UB92 &amp; Print" title="<?php echo xla('Queue for UB-92 batch processing and printing')?>">
<input type="submit" class="subbtn" name="bn_ub92" value="Queue UB92" title="<?php echo xla('Queue for UB-92 batch processing')?>">
-->
<?php if ($GLOBALS['claim_type'] =='0' || $GLOBALS['claim_type'] =='2' ) { ?>
<input type="submit" class="subbtn cp-output" name="bn_x12" value="<?php echo xla('Generate X12')?>"
 title="<?php echo xla('Generate and download X12 batch')?>"
 onclick="MarkAsCleared(1)">
<?php if ($GLOBALS['support_encounter_claims']) { ?>
<input type="submit" class="subbtn cp-output" name="bn_x12_encounter" value="<?php echo xla('Generate X12 Encounter')?>"
 title="<?php echo xla('Generate and download X12 encounter claim batch')?>"
 onclick="MarkAsCleared(1)">
<?php } ?>
<input type="submit" class="subbtn cp-output" name="bn_process_hcfa" value="<?php echo xla('CMS 1500 PDF')?>"
 title="<?php echo xla('Generate and download CMS 1500 paper claims')?>"
 onclick="MarkAsCleared(2)">
 <?php if ($GLOBALS['preprinted_cms_1500']) { ?>
<input type="submit" class="subbtn cp-output" name="bn_process_hcfa_form" value="<?php echo xla('CMS 1500 PREPRINTED FORM')?>"
 title="<?php echo xla('Generate and download CMS 1500 paper claims on Preprinted form')?>"
 onclick="MarkAsCleared(2)">
 <?php } ?>
<input type="submit" class="subbtn cp-output" name="bn_hcfa_txt_file" value="<?php echo xla('CMS 1500 TEXT')?>"
 title="<?php echo xla('Making batch text files for uploading to Clearing House and will mark as billed')?>"
 onclick="MarkAsCleared(3)">
<input type="submit" data-open-popup="true" class="subbtn cp-misc" name="bn_mark" value="<?php echo xla('Mark as Cleared')?>" title="<?php echo xla('Post to accounting and mark as billed')?>">
<input type="submit" data-open-popup="true" class="subbtn cp-misc" name="bn_reopen" value="<?php echo xla('Re-Open')?>" title="<?php echo xla('Mark as not billed')?>">
<!--
<input type="submit" class="subbtn" name="bn_electronic_file" value="Make Electronic Batch &amp; Clear" title="<?php echo xla('Download billing file, post to accounting and mark as billed')?>">
-->
&nbsp;&nbsp;&nbsp;
<?php echo xlt('CMS 1500 Margins'); ?>:
&nbsp;<?php echo xlt('Left'); ?>:
<input type='text' size='2' name='left_margin'
 value='<?php echo attr($left_margin); ?>'
 title='<?php echo xla('HCFA left margin in points'); ?>' />
&nbsp;<?php echo xlt('Top'); ?>:
<input type='text' size='2' name='top_margin'
 value='<?php echo attr($top_margin); ?>'
 title='<?php echo xla('HCFA top margin in points'); ?>' /><br></br>

 <?php } ?>

<?php if ($GLOBALS['claim_type'] =='1' || $GLOBALS['claim_type'] =='2') { ?>

<input type="submit" class="subbtn" name="bn_837I" value="<?php echo xla('Generate 837I')?>"
 title="<?php echo xla('Generate and download 837I file')?>"
 onclick="MarkAsCleared(1)">

<input type="submit" class="subbtn" style="width:175px;" name="bn_process_ub04" value="<?php echo xla('Generate CMS 1450 PDF')?>"
 title="<?php echo xla('Generate and download CMS 1450 paper claims')?>"
 onclick="MarkAsCleared(2)">

<input type="submit" class="subbtn" style="width:175px;" name="bn_ub04_txt_file" value="<?php echo xla('Generate CMS 1450 TEXT')?>"
 title="<?php echo xla('Making batch text files for uploading to Clearing House and will mark as billed')?>"
 onclick="MarkAsCleared(3)">

<?php if ($GLOBALS['claim_type'] =='1') { ?>

<input type="submit" class="subbtn" name="bn_mark" value="<?php echo xla('Mark as Cleared')?>" title="<?php echo xla('Post to accounting and mark as billed')?>">
<input type="submit" class="subbtn" name="bn_reopen" value="<?php echo xla('Re-Open')?>" title="<?php echo xla('Mark as not billed')?>">

<?php } ?>

<?php if ($GLOBALS['claim_type'] =='2') { ?>

 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

<?php } ?>

 &nbsp;&nbsp;&nbsp;

<?php echo xlt('CMS 1450 Margins'); ?>:
&nbsp;<?php echo xlt('Left'); ?>:
<input type='text' size='2' name='ubleft_margin'
 value='<?php echo attr($ubleft_margin); ?>'
 title='<?php echo xla('UB04 left margin in points'); ?>' />
&nbsp;<?php echo xlt('Top'); ?>:
<input type='text' size='2' name='ubtop_margin'
 value='<?php echo attr($ubtop_margin); ?>'
 title='<?php echo xla('UB04 top margin in points'); ?>' />

<?php } ?>
<?php } ?>
</span>

</center>
<input type='hidden' name='HiddenMarkAsCleared'  id='HiddenMarkAsCleared' value="" />
<input type='hidden' name='mode' value="bill" />
<input type='hidden' name='authorized' value="<?php echo attr($my_authorized); ?>" />
<input type='hidden' name='unbilled' value="<?php echo attr($unbilled); ?>" />
<input type='hidden' name='code_type' value="%" />
<input type='hidden' name='to_date' value="<?php echo attr($to_date); ?>" />
<input type='hidden' name='from_date' value="<?php echo attr($from_date); ?>" />

<?php
if ($my_authorized == "on" ) {
  $my_authorized = "1";
} else {
  $my_authorized = "%";
}
if ($unbilled == "on") {
  $unbilled = "0";
} else {
  $unbilled = "%";
}
$list = getBillsListBetween("%");
?>

<input type='hidden' name='bill_list' value="<?php echo attr($list); ?>" />

<!-- new form for uploading -->

<?php
if (!isset($_POST["mode"])) {
  if (!isset($_POST["from_date"])) {
    $from_date = date("Y-m-d");
  } else {
    $from_date = $_POST["from_date"];
  }
  if (empty($_POST["to_date"])) {
    $to_date = '';
  } else {
    $to_date = $_POST["to_date"];
  }
  if (!isset($_POST["code_type"])) {
    $code_type="all";
  } else {
    $code_type = $_POST["code_type"];
  }
  if (!isset($_POST["unbilled"])) {
    $unbilled = "on";
  } else {
    $unbilled = $_POST["unbilled"];
  }
  if (!isset($_POST["authorized"])) {
    $my_authorized = "on";
  } else {
    $my_authorized = $_POST["authorized"];
  }
} else {
  $from_date = $_POST["from_date"];
  $to_date = $_POST["to_date"];
  $code_type = $_POST["code_type"];
  $unbilled = $_POST["unbilled"];
  $my_authorized = $_POST["authorized"];
}

if ($my_authorized == "on" ) {
  $my_authorized = "1";
} else {
  $my_authorized = "%";
}

if ($unbilled == "on") {
  $unbilled = "0";
} else {
  $unbilled = "%";
}

if (isset($_POST["mode"]) && $_POST["mode"] == "bill") {
  billCodesList($list);
}
?>

<table border="0" cellspacing="0" cellpadding="0" width="100%">

<?php
$divnos=0;
if ($ret = getBillsBetween("%"))
{
if(is_array($ret))
 {
?>
<tr ><td colspan='9' align="right" ><table width="250" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100" id='ExpandAll'><a  onclick="expandcollapse('expand');" class='small'  href="JavaScript:void(0);"><?php echo '('.xla('Expand All').')' ?></a></td>
    <td width="100" id='CollapseAll'><a  onclick="expandcollapse('collapse');" class='small'  href="JavaScript:void(0);"><?php echo '('.xla('Collapse All').')' ?></a></td>
    <td width="50">&nbsp;</td>
  </tr>
</table>
</td></tr>
<?php
}
  $loop = 0;
  $oldcode = "";
  $last_encounter_id = "";
  $lhtml = "";
  $rhtml = "";
  $lcount = 0;
  $rcount = 0;
  $bgcolor = "";
  $skipping = FALSE;

  $mmo_empty_mod = false;
  $mmo_num_charges = 0;

  foreach ($ret as $iter) {

    // We include encounters here that have never been billed.  However
    // if it had no selected billing items but does have non-selected
    // billing items, then it is not of interest.
    if (!$iter['id']) {
      $res = sqlQuery("SELECT count(*) AS count FROM billing WHERE " .
        "encounter = ? AND " .
        "pid=? AND " .
        "activity = 1", array($iter['enc_encounter'],$iter['enc_pid']) );
      if ($res['count'] > 0) continue;
    }

    $this_encounter_id = $iter['enc_pid'] . "-" . $iter['enc_encounter'];

    if ($last_encounter_id != $this_encounter_id) {

      // This dumps all HTML for the previous encounter.
      //
      if ($lhtml) {
        while ($rcount < $lcount) {
          $rhtml .= "<tr bgcolor='$bgcolor'><td colspan='8'></td></tr>";
          ++$rcount;
        }
        // This test handles the case where we are only listing encounters
        // that appear to have a missing "25" modifier.
        if (!$missing_mods_only || ($mmo_empty_mod && $mmo_num_charges > 1)) {
          if($DivPut=='yes')
           {
             $lhtml.='</div>';
            $DivPut='no';
           }
          echo "<tr bgcolor='$bgcolor'>\n<td rowspan='$rcount' valign='top'>\n$lhtml</td>$rhtml\n";
          echo "<tr bgcolor='$bgcolor'><td colspan='9' height='5'></td></tr>\n\n";
          ++$encount;
        }
      }

      $lhtml = "";
      $rhtml = "";
      $mmo_empty_mod = false;
      $mmo_num_charges = 0;

      // If there are ANY unauthorized items in this encounter and this is
      // the normal case of viewing only authorized billing, then skip the
      // entire encounter.
      //
      $skipping = FALSE;
      if ($my_authorized == '1') {
        $res = sqlQuery("select count(*) as count from billing where " .
          "encounter = ? and " .
          "pid=? and " .
          "activity = 1 and authorized = 0", array($iter['enc_encounter'],$iter['enc_pid']) );
        if ($res['count'] > 0) {
          $skipping = TRUE;
          $last_encounter_id = $this_encounter_id;
          continue;
        }
      }

      $name = getPatientData($iter['enc_pid'], "fname, mname, lname, pid, billing_note, DATE_FORMAT(DOB,'%Y-%m-%d') as DOB_YMD");

      # Check if patient has primary insurance and a subscriber exists for it.
      # If not we will highlight their name in red.
      # TBD: more checking here.
      #
      $res = sqlQuery("select count(*) as count from insurance_data where " .
        "pid = ? and " .
        "type='primary' and " .
        "subscriber_relationship != '' and " .
        "subscriber_relationship is not null and " .
        "subscriber_street != '' and " .
        "subscriber_street is not null and " .
        "subscriber_city != '' and " .
        "subscriber_city is not null and " .
        "subscriber_state != '' and " .
        "subscriber_state is not null and " .
        "subscriber_sex != '' and " .
        "subscriber_sex is not null and " .
        "subscriber_DOB != '0000-00-00' and " .
        "subscriber_DOB is not null and " .
        "subscriber_DOB != '' and " .
        "subscriber_fname is not null and " .
        "subscriber_fname != '' and " .
        "subscriber_lname is not null and " .
        "subscriber_lname != '' limit 1", array($iter['enc_pid']) );
      $namecolor = ($res['count'] > 0) ? "black" : "#ff7777";

      $bgcolor = "#" . (($encount & 1) ? "ddddff" : "ffdddd");
      echo "<tr bgcolor='$bgcolor'><td colspan='9' height='5'></td></tr>\n";
      $lcount = 1;
      $rcount = 0;
      $oldcode = "";

      $ptname = $name['fname'] . " " . $name['lname'];
      $raw_encounter_date = date("Y-m-d", strtotime($iter['enc_date']));
      $billing_note = $name['billing_note'];
            //  Add Encounter Date to display with "To Encounter" button 2/17/09  JCH

      if ($namecolor != 'black') {
      #error_log("Color: ".$namecolor, 0);
        $lhtml .= "&nbsp;<span class=js-blink-infinite><font color='$namecolor'>". text($ptname) .
          "</font></span><span class=small>&nbsp;(" . text($iter['enc_pid']) . "-" .
          text($iter['enc_encounter']) . ")</span>";
      }else{
      $lhtml .= "&nbsp;<span class=bold><font color='$namecolor'>". text($ptname) .
        "</font></span><span class=small>&nbsp;(" . text($iter['enc_pid']) . "-" .
        text($iter['enc_encounter']) . ")</span>";
      }

         //Encounter details are stored to javacript as array.
        $result4 = sqlStatement("SELECT fe.encounter,fe.date,fe.billing_note,libreehr_postcalendar_categories.pc_catname FROM form_encounter AS fe ".
            " left join libreehr_postcalendar_categories on fe.pc_catid=libreehr_postcalendar_categories.pc_catid  WHERE fe.pid = ? order by fe.date desc", array($iter['enc_pid']) );
           if(sqlNumRows($result4)>0)
            ?>
            <script language='JavaScript'>
            Count=0;
            EncounterDateArray[<?php echo attr($iter['enc_pid']); ?>]=new Array;
            CalendarCategoryArray[<?php echo attr($iter['enc_pid']); ?>]=new Array;
            EncounterIdArray[<?php echo attr($iter['enc_pid']); ?>]=new Array;
            <?php
            while($rowresult4 = sqlFetchArray($result4))
             {
            ?>
                EncounterIdArray[<?php echo attr($iter['enc_pid']); ?>][Count]='<?php echo attr($rowresult4['encounter']); ?>';
                EncounterDateArray[<?php echo attr($iter['enc_pid']); ?>][Count]='<?php echo attr(oeFormatShortDate(date("Y-m-d", strtotime($rowresult4['date'])))); ?>';
                CalendarCategoryArray[<?php echo attr($iter['enc_pid']); ?>][Count]='<?php echo attr(xl_appt_category($rowresult4['pc_catname'])); ?>';
                Count++;
         <?php
             }
         ?>
        </script>
        <?php

            //  Not sure why the next section seems to do nothing except post "To Encounter" button 2/17/09  JCH
      $lhtml .= "&nbsp;&nbsp;&nbsp;<a class=\"link_submit\" " .
        "href=\"javascript:window.toencounter(" . $iter['enc_pid'] .
        ",'" . addslashes($name['pid']) .
        "','" . addslashes($ptname) . "'," . $iter['enc_encounter'] .
        ",'" . oeFormatShortDate($raw_encounter_date) . "',' " .
        xl('DOB') . ": " . oeFormatShortDate($name['DOB_YMD']) . " " . xl('Age') . ": " . getPatientAge($name['DOB_YMD']) . "');
                 top.window.parent.left_nav.setPatientEncounter(EncounterIdArray[" . $iter['enc_pid'] . "],EncounterDateArray[" . $iter['enc_pid'] .
                 "], CalendarCategoryArray[" . $iter['enc_pid'] . "])\">[" .
        xlt('To Enctr') . " " . text(oeFormatShortDate($raw_encounter_date)) . "]</a>";

            //  Changed "To xxx" buttons to allow room for encounter date display 2/17/09  JCH
      $lhtml .= "&nbsp;&nbsp;&nbsp;<a class=\"link_submit\" " .
        "href=\"javascript:window.topatient(" . $iter['enc_pid'] .
        ",'" . addslashes($name['pid']) .
        "','" . addslashes($ptname) . "'," . $iter['enc_encounter'] .
        ",'" . oeFormatShortDate($raw_encounter_date) . "',' " .
        xl('DOB') . ": " . oeFormatShortDate($name['DOB_YMD']) . " " . xl('Age') . ": " . getPatientAge($name['DOB_YMD']) . "');
                 top.window.parent.left_nav.setPatientEncounter(EncounterIdArray[" . $iter['enc_pid'] . "],EncounterDateArray[" . $iter['enc_pid'] .
                 "], CalendarCategoryArray[" . $iter['enc_pid'] . "])\">[" . xlt('To Dems') . "]</a>";
        $divnos=$divnos+1;
      $lhtml .= "&nbsp;&nbsp;&nbsp;<a  onclick='divtoggle(\"spanid_$divnos\",\"divid_$divnos\");' class='small' id='aid_$divnos' href=\"JavaScript:void(0);".
        "\">(<span id=spanid_$divnos class=\"indicator\">" . xla('Expand') . '</span>)<br></a>';
      if($GLOBALS['notes_to_display_in_Billing'] == 2 || $GLOBALS['notes_to_display_in_Billing'] == 3){
      $lhtml .= '<span style="margin-left: 20px; font-weight bold; color: red">'.text($billing_note).'</span>';
      }

      if ($iter['id']) {

        $lcount += 2;
        $lhtml .= "<br />\n";
        $lhtml .= "&nbsp;<span class=text>Bill: ";
        $lhtml .= "<select name='claims[" . attr($this_encounter_id) . "][payer]' style='background-color:$bgcolor'>";

        $query = "SELECT id.provider AS id, id.type, id.date, " .
          "ic.x12_default_partner_id AS ic_x12id, ic.name AS provider " .
          "FROM insurance_data AS id, insurance_companies AS ic WHERE " .
          "ic.id = id.provider AND " .
          "id.pid = ? AND " .
          "id.date <= ? " .
          "ORDER BY id.type ASC, id.date DESC";

        $result = sqlStatement($query, array($iter['enc_pid'],$raw_encounter_date) );
        $count = 0;
        $default_x12_partner = $iter['ic_x12id'];
        $prevtype = '';

        while ($row = sqlFetchArray($result)) {
          if (strcmp($row['type'], $prevtype) == 0) continue;
          $prevtype = $row['type'];
          if (strlen($row['provider']) > 0) {
            // This preserves any existing insurance company selection, which is
            // important when EOB posting has re-queued for secondary billing.
            $lhtml .= "<option value=\"" . attr(substr($row['type'],0,1).$row['id']) . "\"";
            if (($count == 0 && !$iter['payer_id']) || $row['id'] == $iter['payer_id']) {
              $lhtml .= " selected";
              if (!is_numeric($default_x12_partner)) $default_x12_partner = $row['ic_x12id'];
            }
            $lhtml .= ">" . text($row['type']) . ": " . text($row['provider']) . "</option>";
          }
          $count++;
        }

        $lhtml .= "<option value='-1'>" . xlt("Unassigned") . "</option>\n";
        $lhtml .= "</select>&nbsp;&nbsp;\n";
        $lhtml .= "<select name='claims[" . attr($this_encounter_id) . "][partner]' style='background-color:$bgcolor'>";
        $x = new X12Partner();
        $partners = $x->_utility_array($x->x12_partner_factory());
        foreach ($partners as $xid => $xname) {
          $lhtml .= '<option label="' . attr($xname) . '" value="' . attr($xid) .'"';
          if ($xid == $default_x12_partner) {
            $lhtml .= "selected";
          }
          $lhtml .= '>' . text($xname) . '</option>';
        }
        $lhtml .= "</select>";
        $DivPut='yes';

        if($GLOBALS['notes_to_display_in_Billing'] == 1 || $GLOBALS['notes_to_display_in_Billing'] == 3) {
          $lhtml .= "<br><span style='margin-left: 20px; font-weight bold; color: green'>".text($iter['enc_billing_note'])."</span>";
        }
          $lhtml .= "<br>\n&nbsp;<div   id='divid_$divnos' style='display:none'>" . text(oeFormatShortDate(substr($iter['date'], 0, 10)))
          . text(substr($iter['date'], 10, 6)) . " " . xlt("Encounter was coded");

        $query = "SELECT * FROM claims WHERE " .
          "patient_id = ? AND " .
          "encounter_id = ? " .
          "ORDER BY version";
        $cres = sqlStatement($query, array($iter['enc_pid'],$iter['enc_encounter']) );

        $lastcrow = false;

        while ($crow = sqlFetchArray($cres)) {
          $query = "SELECT id.type, ic.name " .
            "FROM insurance_data AS id, insurance_companies AS ic WHERE " .
            "id.pid = ? AND " .
            "id.provider = ? AND " .
            "id.date <= ? AND " .
            "ic.id = id.provider " .
            "ORDER BY id.type ASC, id.date DESC";

          $irow= sqlQuery($query, array($iter['enc_pid'],$crow['payer_id'],$raw_encounter_date) );

          if ($crow['bill_process']) {
            $lhtml .= "<br>\n&nbsp;" .
              text(oeFormatShortDate(substr($crow['bill_time'], 0, 10))) .
              text(substr($crow['bill_time'], 10, 6)) . " " .
              xlt("Queued for") . " " . text($irow['type']) . " " . text($crow['target']) . " " .
              xlt("billing to ") . text($irow['name']);
            ++$lcount;
          }
          else if ($crow['status'] < 6) {
              if ($crow['status'] > 1) {
                $lhtml .= "<br>\n&nbsp;" .
                  text(oeFormatShortDate(substr($crow['bill_time'], 0, 10))) .
                  text(substr($crow['bill_time'], 10, 6)) . " " .
                  xla("Marked as cleared");
                ++$lcount;
              }
              else {
                $lhtml .= "<br>\n&nbsp;" .
                  text(oeFormatShortDate(substr($crow['bill_time'], 0, 10))) .
                  text(substr($crow['bill_time'], 10, 6)) . " " .
                  xla("Re-opened");
                ++$lcount;
              }
          }
          else if ($crow['status'] == 6) {
            $lhtml .= "<br>\n&nbsp;" .
              text(oeFormatShortDate(substr($crow['bill_time'], 0, 10))) .
              text(substr($crow['bill_time'], 10, 6)) . " " .
              xla("This claim has been forwarded to next level.");
            ++$lcount;
          }
          else if ($crow['status'] == 7) {
            $lhtml .= "<br>\n&nbsp;" .
              text(oeFormatShortDate(substr($crow['bill_time'], 0, 10))) .
              text(substr($crow['bill_time'], 10, 6)) . " " .
              xla("This claim has been denied.Reason:-");
              if($crow['process_file'])
               {
                $code_array=explode(',',$crow['process_file']);
                foreach($code_array as $code_key => $code_value)
                 {
                    $lhtml .= "<br>\n&nbsp;&nbsp;&nbsp;";
                    $reason_array=explode('_',$code_value);
                    if(!isset($adjustment_reasons[$reason_array[3]]))
                     {
                        $lhtml .=xla("For code").' ['.text($reason_array[0]).'] '.xla("and modifier").' ['.text($reason_array[1]).'] '.xla("the Denial code is").' ['.text($reason_array[2]).' '.text($reason_array[3]).']';
                     }
                    else
                     {
                        $lhtml .=xla("For code").' ['.text($reason_array[0]).'] '.xla("and modifier").' ['.text($reason_array[1]).'] '.xla("the Denial Group code is").' ['.text($reason_array[2]).'] '.xla("and the Reason is").':- '.text($adjustment_reasons[$reason_array[3]]);
                     }
                 }
               }
              else
               {
                $lhtml .=xla("Not Specified.");
               }
            ++$lcount;
          }

          if ($crow['process_time']) {
            $lhtml .= "<br>\n&nbsp;" .
              text(oeFormatShortDate(substr($crow['process_time'], 0, 10))) .
              text(substr($crow['process_time'], 10, 6)) . " " .
              xlt("Claim was generated to file") . " " .
              "<a href='get_claim_file.php?key=" . attr($crow['process_file']) .
              "' onclick='top.restoreSession()'>" .
              text($crow['process_file']) . "</a>";
            ++$lcount;
          }

          $lastcrow = $crow;
        } // end while ($crow = sqlFetchArray($cres))

        if ($lastcrow && $lastcrow['status'] == 4) {
          $lhtml .= "<br>\n&nbsp;" . xlt("This claim has been closed.");
          ++$lcount;
        }

        if ($lastcrow && $lastcrow['status'] == 5) {
          $lhtml .= "<br>\n&nbsp;" . xlt("This claim has been canceled.");
          ++$lcount;
        }
      } // end if ($iter['id'])

    } // end if ($last_encounter_id != $this_encounter_id)

    if ($skipping) continue;

    // Collect info related to the missing modifiers test.
    if ($iter['fee'] > 0) {
      ++$mmo_num_charges;
      $tmp = substr($iter['code'], 0, 3);
      if (($tmp == '992' || $tmp == '993') && empty($iter['modifier']))
        $mmo_empty_mod = true;
    }

    ++$rcount;

    if ($rhtml) {
        $rhtml .= "<tr bgcolor='$bgcolor'>\n";
    }
    $rhtml .= "<td width='50'>";
    if ($iter['id'] && $oldcode != $iter['code_type']) {
        $rhtml .= "<span class=text>" . text($iter['code_type']) . ": </span>";
    }

    $oldcode = $iter['code_type'];
    $rhtml .= "</td>\n";
    $justify = "";

    if ($iter['id'] && $code_types[$iter['code_type']]['just']) {
      $js = explode(":",$iter['justify']);
      $counter = 0;
      foreach ($js as $j) {
        if(!empty($j)) {
          if ($counter == 0) {
            $justify .= " (<b>" . text($j) . "</b>)";
          }
          else {
            $justify .= " (" . text($j) . ")";
          }
          $counter++;
        }
      }
    }

    $rhtml .= "<td><span class='text'>" .
      ($iter['code_type'] == 'COPAY' ? text(oeFormatMoney($iter['code'])) : text($iter['code']));
    if ($iter['modifier']) $rhtml .= ":" . text($iter['modifier']);
    $rhtml .= "</span><span style='font-size:8pt;'>$justify</span></td>\n";

    $rhtml .= '<td align="right"><span style="font-size:8pt;">&nbsp;&nbsp;&nbsp;';
    if ($iter['id'] && $iter['fee'] > 0) {
      $rhtml .= text(oeFormatMoney($iter['fee']));
    }
    $rhtml .= "</span></td>\n";
    $rhtml .= '<td><span style="font-size:8pt;">&nbsp;&nbsp;&nbsp;';
    if ($iter['id']) $rhtml .= getProviderName(empty($iter['provider_id']) ? text($iter['enc_provider_id']) : text($iter['provider_id']));
    $rhtml .= "</span></td>\n";
    $rhtml .= '<td><span style="font-size:8pt;">&nbsp;&nbsp;&nbsp;';
    if($GLOBALS['display_units_in_billing'] != 0) {
      if ($iter['id']) $rhtml .= xlt("Units") . ":" . text($iter{"units"});
    }
    $rhtml .= "</span></td>\n";
    $rhtml .= '<td width=100>&nbsp;&nbsp;&nbsp;<span style="font-size:8pt;">';
    if ($iter['id']) $rhtml .= text(oeFormatSDFT(strtotime($iter{"date"})));
    $rhtml .= "</span></td>\n";
    # This error message is generated if the authorized check box is not checked
    if ($iter['id'] && $iter['authorized'] != 1) {
      $rhtml .= "<td><span class=alert>".xlt("Note: This code has not been authorized.")."</span></td>\n";
    }
    # This will check if an item is excluded and will tell the user if it is the case.
    else if ($iter['id'] && $iter['authorized'] == 1 && $iter['exclude_from_insurance_billing'] == 1) {
      $rhtml .= "<td><span class=alert>".xlt("Note: Excluded from X12 and CMS1500.")."</span></td>\n";
    }
    else {
      $rhtml .= "<td></td>\n";
    }
    if ($iter['id'] && $last_encounter_id != $this_encounter_id) {
      $tmpbpr = $iter['bill_process'];
      if ($tmpbpr == '0' && $iter['billed']) $tmpbpr = '2';
      $rhtml .= "<td><input type='checkbox' value='" . attr($tmpbpr) . "' name='claims[" . attr($this_encounter_id) . "][bill]' onclick='set_button_states()' id='CheckBoxBilling" . attr($CheckBoxBilling*1) . "'>&nbsp;</td>\n";
      $CheckBoxBilling++;
    }
    else {
      $rhtml .= "<td></td>\n";
    }
    if($last_encounter_id != $this_encounter_id){
      $rhtml2 = "";
      $rowcnt = 0;
      $resMoneyGot = sqlStatement("SELECT pay_amount as PatientPay,date(post_time) as date FROM ar_activity where ".
        "pid = ? and encounter = ? and payer_type=0 and account_code='PCP'",
        array($iter['enc_pid'],$iter['enc_encounter']));
        //new fees screen copay gives account_code='PCP'
      if(sqlNumRows($resMoneyGot) > 0){
        $lcount += 2;
        $rcount++;
      }
      //checks whether a copay exists for the encounter and if exists displays it.
      while($rowMoneyGot = sqlFetchArray($resMoneyGot)){
        $rowcnt++;
        $PatientPay=$rowMoneyGot['PatientPay'];
        $date=$rowMoneyGot['date'];
        if($PatientPay > 0){
          if($rhtml){
            $rhtml2 .= "<tr bgcolor='$bgcolor'>\n";
          }
          $rhtml2 .= "<td width='50'>";
          $rhtml2 .= "<span class='text'>".xlt('COPAY').": </span>";
          $rhtml2 .= "</td>\n";
          $rhtml2 .= "<td><span class='text'>".text(oeFormatMoney($PatientPay))."</span><span style='font-size:8pt;'>&nbsp;</span></td>\n";
          $rhtml2 .= '<td align="right"><span style="font-size:8pt;">&nbsp;&nbsp;&nbsp;';
          $rhtml2 .= "</span></td>\n";
          $rhtml2 .= '<td><span style="font-size:8pt;">&nbsp;&nbsp;&nbsp;';
          $rhtml2 .= "</span></td>\n";
          $rhtml2 .= '<td><span style="font-size:8pt;">&nbsp;&nbsp;&nbsp;';
          $rhtml2 .= "</span></td>\n";
          $rhtml2 .= '<td width=100>&nbsp;&nbsp;&nbsp;<span style="font-size:8pt;">';
          $rhtml2 .= text(oeFormatSDFT(strtotime($date)));
          $rhtml2 .= "</span></td>\n";
          if ($iter['id'] && $iter['authorized'] != 1) {
            $rhtml2 .= "<td><span class=alert>".xlt("Note: This copay was entered against billing that has not been authorized. Please review status.")."</span></td>\n";
          }else{
            $rhtml2 .= "<td></td>\n";
          }
          if(!$iter['id'] && $rowcnt == 1){
            $rhtml2 .= "<td><input type='checkbox' value='0' name='claims[" . attr($this_encounter_id) . "][bill]' onclick='set_button_states()' id='CheckBoxBilling" . attr($CheckBoxBilling*1) . "'>&nbsp;</td>\n";
            $CheckBoxBilling++;
          }else{
            $rhtml2 .= "<td></td>\n";
          }
        }
      }
      $rhtml .= $rhtml2;
    }
    $rhtml .= "</tr>\n";
    $last_encounter_id = $this_encounter_id;

  } // end foreach

  if ($lhtml) {
    while ($rcount < $lcount) {
      $rhtml .= "<tr bgcolor='$bgcolor'><td colspan='8'></td></tr>";
      ++$rcount;
    }
    if (!$missing_mods_only || ($mmo_empty_mod && $mmo_num_charges > 1)) {
      if($DivPut=='yes')
       {
        $lhtml.='</div>';
        $DivPut='no';
       }
      echo "<tr bgcolor='$bgcolor'>\n<td rowspan='$rcount' valign='top'>\n$lhtml</td>$rhtml\n";
      echo "<tr bgcolor='$bgcolor'><td colspan='9' height='5'></td></tr>\n";
    }
  }

}

?>

</table>
</form>

<script>
set_button_states();
<?php
if ($alertmsg) {
  echo "alert('".addslashes($alertmsg)."');\n";
}
?>
$(document).ready(function() {
    $("#view-log-link").click( function() {
        top.restoreSession();
        dlgopen('customize_log.php', '_blank', 500, 400);
    });

    $('input[type="submit"]').click( function() {
        top.restoreSession();
        $(this).attr('data-clicked', true);
    });

    $('form[name="update_form"]').submit( function(e) {
        var clickedButton = $("input[type=submit][data-clicked='true'")[0];

        // clear clicked button indicator
        $('input[type="submit"]').attr('data-clicked', false);

        if ( !clickedButton || $(clickedButton).attr("data-open-popup") !== "true" ) {
            $(this).removeAttr("target");
            return top.restoreSession();
        } else {
            top.restoreSession();
            var w = window.open('about:blank','Popup_Window','toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=400,height=300,left = 312,top = 234');
            this.target = 'Popup_Window';
        }
    });
          $('.js-blink-infinite').modernBlink();
});
</script>
<input type="hidden" name="divnos"  id="divnos" value="<?php echo attr($divnos) ?>"/>
<input type='hidden' name='ajax_mode' id='ajax_mode' value='' />
</body>
</html>
