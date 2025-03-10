<?php
/*
 * Patient List report
 *
 * This report lists patients that were seen within a given date
 * range, or all patients if no date range is entered.
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

require_once "reports_controllers/PatientListController.php";

// In the case of CSV export only, a download will be forced.
if ($_POST['form_csvexport'] && !$_POST['form_refresh']) {
    csvexport('patient_list'); // CSV headers. (TRK)
} else {
     $_POST['form_csvexport']=false;
?>
<html>
<head>
<?php html_header_show();?>
<title><?php echo xlt('Patient List'); ?></title>
<script type="text/javascript" src="../../library/overlib_mini.js"></script>
<script type="text/javascript" src="../../library/textformat.js"></script>
<script type="text/javascript" src="../../library/dialog.js"></script>
<script type="text/javascript" src="../../library/report_validation.js"></script>
<link rel="stylesheet" href="../../library/css/jquery.datetimepicker.css">

<!-- Including jquery plugin to handle uncaught ReferenceError $  -->
<?php
  call_required_libraries(array("jquery-min-3-1-1", "iziModalToast"));
?>

<script language="JavaScript">
	$(document).ready(function() {
 		top.printLogSetup(document.getElementById('printbutton'));
  });
  
  function validateInput() {
    return validateFromAndToDates();
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

<!-- Required for the popup date selectors -->
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

<span class='title'><?php echo xlt('Report'); ?> - <?php echo xlt('Patient List'); ?></span>



<form name='theform' id='theform' method='post' onsubmit='return validateInput()'>
  <div id="report_parameters">
    <input type='hidden' name='form_refresh' id='form_refresh' value=''/>
    <input type='hidden' name='form_csvexport' id='form_csvexport' value=''/>
    <table>
      <tr>
        <td width='60%'>
          <div style='float:left'>
            <table class='text'>
              <tr>
                <td class='label'>
                  <?php echo xlt('Provider'); ?>:
                </td>
                <td>
                  <?php
                    generate_form_field(array('data_type' => 10, 'field_id' => 'provider','empty_title' => '-- All Providers --'), $_POST['form_provider']);
                  ?>
                </td>
                <?php // Show From and To dates fields. (TRK)
                  showFromAndToDates(); ?>
              </tr>
            </table>
          </div>
        </td>
        <?php // Show print, submit and export buttons. (TRk)
          showSubmitPrintButtons('form_csvexport'); ?>
      </tr>
    </table>
  </div> <!-- end of parameters -->

<?php
} // end not form_csvexport

if ($_POST['form_refresh'] || $_POST['form_csvexport']) {
  if ($_POST['form_csvexport']) {
    // CSV headers:
    echo '"' . xl('Last Visit') . '",';
    echo '"' . xl('First') . '",';
    echo '"' . xl('Last') . '",';
    echo '"' . xl('Middle') . '",';
    echo '"' . xl('ID') . '",';
    echo '"' . xl('Street') . '",';
    echo '"' . xl('City') . '",';
    echo '"' . xl('State') . '",';
    echo '"' . xl('Zip') . '",';
    echo '"' . xl('Home Phone') . '",';
    echo '"' . xl('Work Phone') . '"' . "\n";
  } else {
      ?>
      <div id="report_results">
        <table>
          <thead>
            <th> <?php echo xlt('Last Visit'); ?> </th>
            <th> <?php echo xlt('Patient'); ?> </th>
            <th> <?php echo xlt('ID'); ?> </th>
            <th> <?php echo xlt('Street'); ?> </th>
            <th> <?php echo xlt('City'); ?> </th>
            <th> <?php echo xlt('State'); ?> </th>
            <th> <?php echo xlt('Zip'); ?> </th>
            <th> <?php echo xlt('Primary Insurance'); ?> </th>
            <th> <?php echo xlt('Secondary Insurance'); ?> </th>
          </thead>
          <tbody>
        <?php
    } // end not export
    $totalpts = prepareAndShowResults(); // Prepare and show results. (TRK)

  if (!$_POST['form_csvexport']) {
    ?>
    <tr class="report_totals">
        <td colspan="9">
          <?php echo xlt('Total Number of Patients');
          echo ':';
          echo total($totalpts); ?>
        </td>
    </tr>

          </tbody>
        </table>
      </div> <!-- end of results -->
  <?php
    } // end not export
} // end if refresh or export

if (!$_POST['form_refresh'] && !$_POST['form_csvexport']) {
  ?>
  <div class='text'>
      <?php echo xlt('Please input search criteria above, and click Submit to view results.'); ?>
  </div>
  <?php
}

if (!$_POST['form_csvexport']) {
  ?>

  </form>
  </body>

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
} // end not export
?>
