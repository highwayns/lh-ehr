<?php
/*
 * Unique Patients Seen report
 *
 * This report lists patients that were seen within a given date
 * range.
 *
 * Copyright (C) 2016-2017 Terry Hill <teryhill@librehealth.io>
 * Copyright (C) 2006-2015 Rod Roark <rod@sunsetsystems.com>
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
 * @author Rod Roark <rod@sunsetsystems.com>
 * @link http://librehealth.io
 */
require_once "reports_controllers/UniqueSeenPatientsController.php";

 if ($form_action == 'labels') {
    csvexport('label'); // Export to csv. (TRK) 
 }
 else {
?>
<html>
<head>
<?php html_header_show();?>
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
    }
    #report_results {
       margin-top: 30px;
    }
}

/* specifically exclude some from the screen */
@media screen {
    #report_parameters_daterange {
        visibility: hidden;
        display: none;
    }
}
</style>
<title><?php echo xlt('Front Office Receipts'); ?></title>

<script type="text/javascript" src="../../library/overlib_mini.js"></script>
<script type="text/javascript" src="../../library/textformat.js"></script>
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/report_validation.js"></script>

<?php
  call_required_libraries(array("jquery-min-3-1-1", "iziModalToast"));
?>

<script language="JavaScript">
 $(document).ready(function() {
  var win = top.printLogSetup ? top : opener.top;
  win.printLogSetup(document.getElementById('printbutton'));
 });

 function validateInput() {
  if (validateFromAndToDates()) mysubmit("submit");
 }

 function mysubmit(action) {
  var f = document.forms[0];
  f.form_action.value = action;
  top.restoreSession();
  f.submit();
 }
</script>

<link rel=stylesheet href="<?php echo $css_header;?>" type="text/css">
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
    }
}

/* specifically exclude some from the screen */
@media screen {
    #report_parameters_daterange {
        visibility: hidden;
        display: none;
    }
}

</style>
</head>

<body class="body_top">

<!-- Required for the popup date selectors -->
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

<span class='title'><?php echo xlt('Report'); ?> - <?php echo xlt('Unique Seen Patients'); ?></span>

<?php reportParametersDaterange(); #TRK ?>

<form name='theform' method='post' action='unique_seen_patients_report.php' id='theform' onsubmit='return validateInput()'>
<div id="report_parameters">
<!-- form_action is set to "submit" or "labels" at form submit time -->
<input type='hidden' name='form_action' value='' />

<table>
 <tr>
  <td width='410px'>
    <div style='float:left'>

    <table class='text'>
        <tr>
          <?php // Show From and To dates fields. (TRK)
            showFromAndToDates(); ?>
        </tr>
    </table>

    </div>

  </td>
 <td align='left' valign='middle'>
   <table style='border-left:1px solid; width:100%; height:100%'>
    <tr>
     <td valign='middle'>
      <a href='#' class='css_button cp-submit' onclick='validateInput()' style='margin-left:1em'>
       <span><?php echo htmlspecialchars(xl('Submit')); ?></span>
      </a>
<?php if ($form_action) { ?>
      <a href='#' class='css_button cp-output' id='printbutton' style='margin-left:1em'>
       <span><?php echo htmlspecialchars(xl('Print')); ?></span>
      </a>
      <a href='#' class='css_button cp-ouput' onclick='mysubmit("labels")' style='margin-left:1em'>
       <span><?php echo htmlspecialchars(xl('Labels')); ?></span>
      </a>
<?php } ?>
     </td>
    </tr>
   </table>
  </td>
 </tr>
</table>
</div> <!-- end of parameters -->

<div id="report_results">
<table>

 <thead>
  <th> <?php echo xlt('Last Visit'); ?> </th>
  <th> <?php echo xlt('Patient'); ?> </th>
  <th align='right'> <?php echo xlt('Visits'); ?> </th>
  <th align='right'> <?php echo xlt('Age'); ?> </th>
  <th> <?php echo xlt('Sex'); ?> </th>
  <th> <?php echo xlt('Race'); ?> </th>
  <th> <?php echo xlt('Primary Insurance'); ?> </th>
  <th> <?php echo xlt('Secondary Insurance'); ?> </th>
 </thead>
 <tbody>
<?php
 } // end not generating labels

 if ($form_action) {
   prepareAndShowResults(); // Prepare and show results. (TRK)
 } // end refresh or labels

 if ($form_action != 'labels') {
  if ($form_action) {  
?>
</tbody>
</table>
</div>
<?php
  } // end if ($form_action)
?>
</form>
</body>
<link rel="stylesheet" href="../../library/css/jquery.datetimepicker.css">
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
</html>
<?php
 } // end not labels
?>
