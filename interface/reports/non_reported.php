<?php
/*
 * Non Reported Diagnosis report
 * This report lists non reported patient diagnoses for a given date range.
 * Ensoftek: Jul-2015: Modified HL7 generation to 2.5.1 spec and MU2 compliant.
 * This implementation is only for the A01 profile which will suffice for MU2 certification.
 *
 * Copyright (C) 2016 <dan@mi-squared.com>
 * Copyright (C) 2015 Ensoftek <rammohan@ensoftek.com>
 * Copyright (C) 2010 Tomasz Wyderka <wyderkat@cofoh.com>
 * Copyright (C) 2008 Rod Roark <rod@sunsetsystems.com>
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
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreHealth EHR
 * @author MI-Squared <dan@mi-squared.com>
 * @author Ensoftek <rammohan@ensoftek.com>
 * @author Tomasz Wyderka <wyderkat@cofoh.com>
 * @author Rod Roark <rod@sunsetsystems.com>
 * @link http://librehealth.io
 */

require_once "reports_controllers/NonReportedController.php";

?>

<html>
<head>
<?php html_header_show();?>
<title><?php xl('Syndromic Surveillance - Non Reported Issues','e'); ?></title>
<link rel="stylesheet" href="../../library/css/jquery.datetimepicker.css">
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/textformat.js"></script>
<script type="text/javascript" src="../../library/report_validation.js"></script>

<?php
  call_required_libraries(array("jquery-min-3-1-1", "iziModalToast"));
?>

<script language="JavaScript">

 <?php require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>

 $(document).ready(function() {
  var win = top.printLogSetup ? top : opener.top;
  win.printLogSetup(document.getElementById('printbutton'));
 });

 function validateInput() {
    return top.restoreSession() && validateFromAndToDates();
  }

</script>

<link rel='stylesheet' href='<?php echo $css_header ?>' type='text/css'>
<style type="text/css">
/* specifically include & exclude from printing */
@media print {
    #report_parameters {
        visibility: hidden;
        display: none;
    }
    #report_parameters_daterange {
        visibility: visible;
        display: inline;
        margin-bottom: 10px;
    }
    #report_results table {
       margin-top: 0px;
    }
}
/* specifically exclude some from the screen */
@media screen {
    #report_parameters_daterange {
        visibility: hidden;
        display: none;
    }
    #report_results {
        width: 100%;
    }
}
</style>
</head>

<body class="body_top">

<span class='title'><?php xl('Report', 'e'); ?>
    - <?php xl('Syndromic Surveillance - Non Reported Issues', 'e'); ?></span>

<?php reportParametersDaterange(); #TRK ?>

<form name='theform' id='theform' method='post' action='non_reported.php' onsubmit='return validateInput()'>
<div id="report_parameters">
<input type='hidden' name='form_refresh' id='form_refresh' value=''/>
<input type='hidden' name='form_get_hl7' id='form_get_hl7' value=''/>
<table>
 <tr>
  <td width='410px'>
    <div style='float:left'>
      <table class='text'>
        <tr>
          <td class='label'>
            <?php xl('Diagnosis','e'); ?>:
          </td>
          <td>
            <?php // Build a drop-down list of codes. (TRK)
              dropDownCodes(); ?>
          </td>
          <?php // Show From and To dates fields. (TRK)
            showFromAndToDates(); ?>
        </tr>
      </table>
    </div>
  </td>
  <td align='left' valign='middle' height="100%">
    <table style='border-left:1px solid; width:80%; height:100%' >
      <tr>
        <td>
          <div style='margin-left:15px'>
            <a href='#' class='css_button cp-misc'
            onclick='
            $("#form_refresh").attr("value","true");
            $("#form_get_hl7").attr("value","false");
            $("#theform").submit();
            '>
            <span>
              <?php xl('Refresh','e'); ?>
            </spain>
            </a>
            <?php if ($_POST['form_refresh']) { ?>
              <a href='#' class='css_button cp-output' id='printbutton'>
                <span>
                  <?php echo xlt('Print'); ?>
                </span>
              </a>
              <a href='#' class='css_button cp-output' onclick=
              "if(confirm('<?php xl('This step will generate a file which you have to save for future use. The file cannot be generated again. Do you want to proceed?','e'); ?>')) {
                     $('#form_get_hl7').attr('value','true');
                     $('#theform').submit();
              }">
                <span>
                  <?php xl('Get HL7','e'); ?>
                </span>
              </a>
            <?php } ?>
          </div>
        </td>
      </tr>
    </table>
  </td>
 </tr>
</table>
</div> <!-- end of parameters -->


<?php
 if ($_POST['form_refresh']) {
?>
<div id="report_results">
<table>
 <thead align="left">
  <th> <?php xl('Patient ID','e'); ?> </th>
  <th> <?php xl('Patient Name','e'); ?> </th>
  <th> <?php xl('Diagnosis','e'); ?> </th>
  <th> <?php xl('Issue ID','e'); ?> </th>
  <th> <?php xl('Issue Title','e'); ?> </th>
  <th> <?php xl('Issue Date','e'); ?> </th>
 </thead>
 <tbody>
<?php
  $total = 0;
  //echo "<p> DEBUG query: $query </p>\n"; // debugging
  $res = sqlStatement($query);

  while ($row = sqlFetchArray($res)) {
?>
 <tr>
  <td>
  <?php echo htmlspecialchars($row['patientid']) ?>
  </td>
  <td>
   <?php echo htmlspecialchars($row['patientname']) ?>
  </td>
  <td>
   <?php echo htmlspecialchars($row['diagnosis']) ?>
  </td>
  <td>
   <?php echo htmlspecialchars($row['issueid']) ?>
  </td>
  <td>
   <?php echo htmlspecialchars($row['issuetitle']) ?>
  </td>
  <td>
   <?php date(DateFormatRead(true) . ' H:i:s', strtotime($row['issuedate'])); ?>
  </td>
 </tr>
<?php
   ++$total;
  }
?>
 <tr class="report_totals">
  <td colspan='9'>
   <?php xl('Total Number of Issues','e'); ?>
   :
   <?php echo $total ?>
  </td>
 </tr>

</tbody>
</table>
</div> <!-- end of results -->
<?php } else { ?>
<div class='text'>
  <?php echo xlt('Click Refresh to view all results, or please input search criteria above to view specific results.'); ?>
  <br>
  (<?php echo xlt('This report currently only works for ICD9 codes.'); ?>)
</div>
<?php } ?>
</form>

<script type="text/javascript" src="../../library/js/jquery.datetimepicker.full.min.js"></script>
<script>
    $(function() {
        $("#form_from_date").datetimepicker({
            timepicker: false,
            format: "<?= $DateFormat; ?>"
        });
        $("#form_to_date").datetimepicker({
            timepicker: false,
            format: "<?= $DateFormat; ?>"
        });
        $.datetimepicker.setLocale('<?= $DateLocale; ?>');
    });
</script>

</body>
</html>
