<?php
  /*
   * Patient report
   *
   * Copyright (C) 2016-2017 Terry Hill <teryhill@librehealth.io>
   * No other information in the previous header
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
   * @author Terry Hill <teryhill@librehealth.io>
   * @link http://librehealth.io
   */

  require_once("../../globals.php");
  require_once("$srcdir/lists.inc");
  require_once("$srcdir/acl.inc");
  require_once("$srcdir/forms.inc");
  require_once("$srcdir/patient.inc");
  require_once("$srcdir/formatting.inc.php");
  require_once("$srcdir/headers.inc.php");

  $DateFormat = DateFormatRead();
  $DateLocale = getLocaleCodeForDisplayLanguage($GLOBALS['language_default']);

  // get various authorization levels
  $auth_notes_a  = acl_check('encounters', 'notes_a');
  $auth_notes    = acl_check('encounters', 'notes');
  $auth_coding_a = acl_check('encounters', 'coding_a');
  $auth_coding   = acl_check('encounters', 'coding');
  $auth_relaxed  = acl_check('encounters', 'relaxed');
  $auth_med      = acl_check('patients'  , 'med');
  $auth_demo     = acl_check('patients'  , 'demo');

  ?>
<html>
  <head>
    <?php
      html_header_show();
      call_required_libraries(array('jquery-min-3-1-1', 'datepicker','jquery-ui'));
    ?>

    <script language='JavaScript'>
      function checkAll(check) {
       var f = document.forms['report_form'];
       for (var i = 0; i < f.elements.length; ++i) {
        if (f.elements[i].type == 'checkbox') f.elements[i].checked = check;
       }
       return false;
      }

      function show_date_fun(){
        if(document.getElementById('show_date').checked == true){
          document.getElementById('date_div').style.display = '';
        }else{
          document.getElementById('date_div').style.display = 'none';
        }
        return;
      }
    </script>

  </head>
  <body class="body_top">
    <script>
      $(function() {
        $("#patient_reports").accordion({
          heightStyle: "content"
        });
      });
    </script>
    <div id="patient_reports">
      <!-- large outer DIV -->
      <?php if ( $GLOBALS['activate_ccr_ccd_report'] ) { // show CCR/CCD reporting options ?>
      <h3><?php xl('Continuity of Care Record & Document', 'e'); ?></h3>
      <div id="ccr_report">

        <form name='ccr_form' id='ccr_form' method='post' action='../../../ccr/createCCR.php'>
          <span class='title'><?php xl('Continuity of Care Record (CCR)','e'); ?></span>&nbsp;&nbsp;
          <br/>
          <span class='text'>(<?php xl('Pop ups need to be enabled to see these reports','e'); ?>)</span>
          <br/>
          <br/>
          <input type='hidden' name='ccrAction'>
          <input type='hidden' name='raw'>
          <input type="checkbox" name="show_date" id="show_date" onchange="show_date_fun();" ><span class='text'><?php xl('Use Date Range','e'); ?>
          <a href="../summary/demographics.php" class="css_button" onclick="top.restoreSession()">
            <span><?php echo htmlspecialchars(xl('Back To Patient'),ENT_NOQUOTES);?></span>
          </a>
          <br>
          <div id="date_div" style="display:none" >
            <br>
            <table border="0" cellpadding="0" cellspacing="0" >
              <tr>
                <td>
                  <span class='bold'><?php xl('Start Date','e');?>: </span>
                </td>
                <td>
                  <input type='text' size='10' name='Start' id='Start' />
                  <script>
                    $("#Start").datetimepicker({
                        timepicker: false,
                        format: "<?= $DateFormat; ?>"
                    });
                  </script>
                </td>
                <td>
                  &nbsp;
                  <span class='bold'><?php xl('End Date','e');?>: </span>
                </td>
                <td>
                  <input type='text' size='10' name='End' id='End'/>
                  <script>
                    $("#End").datetimepicker({
                        timepicker: false,
                        format: "<?= $DateFormat; ?>"
                    });
                    $.datetimepicker.setLocale('<?= $DateLocale;?>');
                  </script>
                </td>
              </tr>
            </table>
          </div>
          <br>
          <input type="button" class="generateCCR" value="<?php echo xla('Generate Report'); ?>" />
          <!-- <input type="button" class="generateCCR_download_h" value="<?php echo xl('Download')." (Hybrid)"; ?>" /> -->
          <input type="button" class="generateCCR_download_p" value="<?php echo xl('Download'); ?>" />
          <!-- <input type="button" class="generateCCR_raw" value="<?php xl('Raw Report','e'); ?>" /> -->
          <?php if ($GLOBALS['phimail_enable']==true && $GLOBALS['phimail_ccr_enable']==true) { ?>
          <input type="button" class="viewCCR_send_dialog" value="<?php echo htmlspecialchars( xl('Transmit', ENT_QUOTES)); ?>" />
          <br>
          <div id="ccr_send_dialog" style="display:none" >
            <br>
            <table border="0" cellpadding="0" cellspacing="0" >
              <tr>
                <td>
                  <span class='bold'><?php echo htmlspecialchars( xl('Enter Recipient\'s Direct Address'), ENT_NOQUOTES);?>: </span>
                  <input type="text" size="64" name="ccr_send_to" id="ccr_send_to" value="">
                  <input type="hidden" name="ccr_sent_by" id="ccr_sent_by" value="user">
                  <input type="button" class="viewCCR_transmit" value="<?php echo htmlspecialchars( xl('Send', ENT_QUOTES)); ?>" />
                  <div id="ccr_send_result" style="display:none" >
                    <span class="text" id="ccr_send_message"></span>
                  </div>
                </td>
              </tr>
            </table>
          </div>
          <?php } ?>
          <br><br><br>
          <span class='title'><?php xl('Continuity of Care Document (CCD)','e'); ?></span>&nbsp;&nbsp;
          <br/>
          <span class='text'>(<?php xl('Pop ups need to be enabled to see these reports','e'); ?>)</span>
          <br/>
          <br/>
          <input type="button" class="viewCCD" value="<?php echo xla('Generate Report'); ?>" />
          <input type="button" class="viewCCD_download" value="<?php echo htmlspecialchars( xl('Download', ENT_QUOTES)); ?>" />
          <!-- <input type="button" class="viewCCD_raw" value="<?php xl('Raw Report','e'); ?>" /> -->
          <?php if ($GLOBALS['phimail_enable']==true && $GLOBALS['phimail_ccd_enable']==true) { ?>
          <input type="button" class="viewCCD_send_dialog" value="<?php echo htmlspecialchars( xl('Transmit', ENT_QUOTES)); ?>" />
          <br>
          <div id="ccd_send_dialog" style="display:none" >
            <br>
            <table border="0" cellpadding="0" cellspacing="0" >
              <tr>
                <td>
                  <span class='bold'><?php echo htmlspecialchars( xl('Enter Recipient\'s Direct Address'), ENT_NOQUOTES);?>: </span>
                  <input type="text" size="64" name="ccd_send_to" id="ccd_send_to" value="">
                  <input type="hidden" name="ccd_sent_by" id="ccd_sent_by" value="user">
                  <input type="button" class="viewCCD_transmit" value="<?php echo htmlspecialchars( xl('Send', ENT_QUOTES)); ?>" />
                  <div id="ccd_send_result" style="display:none" >
                    <span class="text" id="ccd_send_message"></span>
                  </div>
                </td>
              </tr>
            </table>
          </div>
          <?php } ?>
        </form>
        
      </div>
      <?php } // end CCR/CCD reporting options ?>
      <h3><?php xl('Patient Report', 'e'); ?></h3>
      <div>
      <form name='report_form' id="report_form" method='post' action='custom_report.php'>
        <span class='title'><?php xl('Patient Report','e'); ?></span>&nbsp;&nbsp;
        <!--
          <a class="link_submit" href="full_report.php" onclick="top.restoreSession()">
          [<?php xl('View Comprehensive Patient Report','e'); ?>]</a>
          -->
        <a class="link_submit" href="#" onclick="return checkAll(true)"><?php xl('Check All','e'); ?></a>
        |
        <a class="link_submit" href="#" onclick="return checkAll(false)"><?php xl('Clear All','e'); ?></a>
            
          <!-- Checkboxes -->
        <p>
        <table class="includes">
          <tr>
            <td class='text'>
              <input type='checkbox' name='include_demographics' id='include_demographics' value="demographics" checked><?php xl('Demographics','e'); ?><br>
              <?php if (acl_check('patients', 'med')): ?>
              <input type='checkbox' name='include_history' id='include_history' value="history"><?php xl('History','e'); ?><br>
              <?php endif; ?>
              <!--
                <input type='checkbox' name='include_employer' id='include_employer' value="employer"><?php xl('Employer','e'); ?><br>
                -->
              <input type='checkbox' name='include_insurance' id='include_insurance' value="insurance"><?php xl('Insurance','e'); ?><br>
              <input type='checkbox' name='include_billing' id='include_billing' value="billing"
                <?php if (!$GLOBALS['simplified_demographics']) echo 'checked'; ?>><?php xl('Billing','e'); ?><br>
            </td>
            <td class='text'>
              <!--
                <input type='checkbox' name='include_allergies' id='include_allergies' value="allergies">Allergies<br>
                <input type='checkbox' name='include_medications' id='include_medications' value="medications">Medications<br>
                -->
              <input type='checkbox' name='include_immunizations' id='include_immunizations' value="immunizations"><?php xl('Immunizations','e'); ?><br>
              <!--
                <input type='checkbox' name='include_medical_problems' id='include_medical_problems' value="medical_problems">Medical Problems<br>
                -->
              <input type='checkbox' name='include_notes' id='include_notes' value="notes"><?php xl('Patient Notes','e'); ?><br>
              <input type='checkbox' name='include_transactions' id='include_transactions' value="transactions"><?php xl('Transactions','e'); ?><br>
              <input type='checkbox' name='include_batchcom' id='include_batchcom' value="batchcom"><?php xl('Communications','e'); ?><br>
            </td>
          </tr>
        </table>
          
        <br>
        <input type="button" class="genreport" value="<?php xl('Generate Report','e'); ?>" />&nbsp;
        <input type="button" class="genpdfrep" value="<?php xl('Download PDF','e'); ?>" />&nbsp;
        <input type='hidden' name='pdf' value='0'>

            <br><br>
        <!-- old ccr button position -->
          </p>
          
        <table class="issues_encounters_forms">
          <tr>
            <!-- Issues -->
            <td class='text'>
              <div class="issues">
                <span class='bold'><?php xl('Issues','e'); ?>:</span>
                <br>
                <br>
                <?php if (! acl_check('patients', 'med')): ?>
                <br>(Issues not authorized)
                <?php else: ?>
                <table>
                  <?php
                    // get issues
                    $pres = sqlStatement("SELECT * FROM lists WHERE pid = $pid " .
                                        "ORDER BY type, begdate");
                    $lasttype = "";
                    while ($prow = sqlFetchArray($pres)) {
                        if ($lasttype != $prow['type']) {
                            $lasttype = $prow['type'];

                       /****
                       $disptype = $lasttype;
                       switch ($lasttype) {
                        case "allergy"        : $disptype = "Allergies"       ; break;
                        case "problem"        :
                        case "medical_problem": $disptype = "Medical Problems"; break;
                        case "medication"     : $disptype = "Medications"     ; break;
                        case "surgery"        : $disptype = "Surgeries"       ; break;
                       }
                       ****/
                            $disptype = $ISSUE_TYPES[$lasttype][0];

                            echo " <tr>\n";
                            echo "  <td colspan='4' class='bold'><b>$disptype</b></td>\n";
                            echo " </tr>\n";
                        }
                        $rowid = $prow['id'];
                        $disptitle = trim($prow['title']) ? $prow['title'] : "[Missing Title]";

                        $ieres = sqlStatement("SELECT encounter FROM issue_encounter WHERE " .
                                            "pid = '$pid' AND list_id = '$rowid'");

                        echo "    <tr class='text'>\n";
                        echo "     <td>&nbsp;</td>\n";
                        echo "     <td>";
                        echo "<input type='checkbox' name='issue_$rowid' id='issue_$rowid' class='issuecheckbox' value='/";
                        while ($ierow = sqlFetchArray($ieres)) {
                            echo $ierow['encounter'] . "/";
                        }
                        echo "' />$disptitle</td>\n";
                        echo "     <td>" . htmlspecialchars(oeFormatShortDate($prow['begdate']));
                        if ($prow['enddate']) { echo " - " . htmlspecialchars(oeFormatShortDate($prow['enddate'])); }
                        else { echo " Active"; }

                        echo "</td>\n";
                        echo "</tr>\n";
                    }
                    ?>
                </table>
                <?php endif; // end of Issues output ?>
              </div>
              <!-- end issues DIV -->
            </td>
            <!-- Encounters and Forms -->
            <td class='text'>
              <div class='encounters'>
                <span class='bold'><?php xl('Encounters &amp; Forms','e'); ?>:</span>
                <br><br>
                <?php if (!($auth_notes_a || $auth_notes || $auth_coding_a || $auth_coding || $auth_med || $auth_relaxed)): ?>
                (Encounters not authorized)
                <?php else: ?>
                <?php
                  $isfirst = 1;
                  $res = sqlStatement("SELECT forms.encounter, forms.form_id, forms.form_name, " .
                                      "forms.formdir, forms.date AS fdate, form_encounter.date " .
                                      ",form_encounter.reason ".
                                      "FROM forms, form_encounter WHERE " .
                                      "forms.pid = '$pid' AND form_encounter.pid = '$pid' AND " .
                                      "form_encounter.encounter = forms.encounter " .
                                      " AND forms.deleted=0 ". // --JRM--
                                      "ORDER BY form_encounter.date DESC, fdate ASC");
                  $res2 = sqlStatement("SELECT name FROM registry ORDER BY priority");
                  $html_strings = array();
                  $registry_form_name = array();
                  while($result2 = sqlFetchArray($res2)) {
                      array_push($registry_form_name,trim($result2['name']));
                  }
                  while($result = sqlFetchArray($res)) {
                      if ($result{"form_name"} == "New Patient Encounter") {
                          if ($isfirst == 0) {
                              foreach($registry_form_name as $var) {
                                  if ($toprint = $html_strings[$var]) {
                                      foreach($toprint as $var) {print $var;}
                                  }
                              }
                              $html_strings = array();
                              echo "</div>\n"; // end DIV encounter_forms
                              echo "</div>\n\n";  //end DIV encounter_data
                              echo "<br>";
                          }
                          $isfirst = 0;
                          echo "<div class='encounter_data'>\n";
                          echo "<input type=checkbox ".
                                  " name='" . $result{"formdir"} . "_" .  $result{"form_id"} . "'".
                                  " id='" . $result{"formdir"} . "_" .  $result{"form_id"} . "'".
                                  " value='" . $result{"encounter"} . "'" .
                                  " class='encounter'".
                                  " >";

                          // show encounter reason, not just 'New Encounter'
                          // trim to a reasonable length for display purposes --cfapress
                          $maxReasonLength = 20;
                          if (strlen($result["reason"]) > $maxReasonLength) {
                              // The default encoding for this mb_substr() call is set near top of globals.php
                              $result['reason'] = mb_substr($result['reason'], 0, $maxReasonLength) . " ... ";
                          }

                          echo $result{"reason"}.
                                  " (" . htmlspecialchars(oeFormatShortDate(date("Y-m-d",strtotime($result{"date"})))) .
                                  ")\n";
                          echo "<div class='encounter_forms'>\n";
                      }
                      else {
                          $form_name = trim($result{"form_name"});
                          //if form name is not in registry, look for the closest match by
                          // finding a registry name which is  at the start of the form name.
                          //this is to allow for forms to put additional helpful information
                          //in the database in the same string as their form name after the name
                          $form_name_found_flag = 0;
                          foreach($registry_form_name as $var) {if ($var == $form_name) {$form_name_found_flag = 1;}}
                          // if the form does not match precisely with any names in the registry, now see if any front partial matches
                          // and change $form_name appropriately so it will print above in $toprint = $html_strings[$var]
                          if (!$form_name_found_flag) { foreach($registry_form_name as $var) {if (strpos($form_name,$var) == 0) {$form_name = $var;}}}

                          if (!is_array($html_strings[$form_name])) {$html_strings[$form_name] = array();}
                          array_push($html_strings[$form_name], "<input type='checkbox' ".
                                                                  " name='" . $result{"formdir"} . "_" . $result{"form_id"} . "'".
                                                                  " id='" . $result{"formdir"} . "_" . $result{"form_id"} . "'".
                                                                  " value='" . $result{"encounter"} . "'" .
                                                                  " class='encounter_form' ".
                                                                  ">" . xl_form_title($result{"form_name"}) . "<br>\n");
                      }
                  }
                  foreach($registry_form_name as $var) {
                      if ($toprint = $html_strings[$var]) {
                          foreach($toprint as $var) {print $var;}
                      }
                  }
                  ?>
                <?php endif; ?>
              </div>
              <!-- end encounters DIV -->
            </td>
          </tr>
        </table>
          <br>
        <input type="button" class="genreport" value="<?php xl('Generate Report','e'); ?>" />&nbsp;
        <input type="button" class="genpdfrep" value="<?php xl('Download PDF','e'); ?>" />&nbsp;
          <br><br><br>

        <!-- Procedure Orders -->
          
        <table border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td class='bold'><?php echo xlt('Procedures'); ?>&nbsp;&nbsp;</td>
            <td class='text'><?php echo xlt('Order Date'); ?>&nbsp;&nbsp;</td>
            <td class='text'><?php echo xlt('Encounter Date'); ?>&nbsp;&nbsp;</td>
            <td class='text'><?php echo xlt('Order Descriptions'); ?></td>
          </tr>
          <?php
            $res = sqlStatement("SELECT po.procedure_order_id, po.date_ordered, fe.date " .
              "FROM procedure_order AS po " .
              "LEFT JOIN forms AS f ON f.pid = po.patient_id AND f.formdir = 'procedure_order' AND " .
              "f.form_id = po.procedure_order_id AND f.deleted = 0 " .
              "LEFT JOIN form_encounter AS fe ON fe.pid = f.pid AND fe.encounter = f.encounter " .
              "WHERE po.patient_id = ? " .
              "ORDER BY po.date_ordered DESC, po.procedure_order_id DESC",
              array($pid));
            while($row = sqlFetchArray($res)) {
              $poid = $row['procedure_order_id'];
              echo " <tr>\n";
              echo "  <td align='center' class='text'>" .
                   "<input type='checkbox' name='procedures[]' value='$poid' />&nbsp;&nbsp;</td>\n";
              echo "  <td class='text'>" . oeFormatShortDate($row['date_ordered']) . "&nbsp;&nbsp;</td>\n";
              echo "  <td class='text'>" . oeFormatShortDate($row['date']) . "&nbsp;&nbsp;</td>\n";
              echo "  <td class='text'>";
              $opres = sqlStatement("SELECT procedure_code, procedure_name FROM procedure_order_code " .
                "WHERE procedure_order_id = ? ORDER BY procedure_order_seq",
                array($poid));
              while($oprow = sqlFetchArray($opres)) {
                $tmp = $oprow['procedure_name'];
                if (empty($tmp)) $tmp = $oprow['procedure_code'];
                echo text($tmp) . "<br />";
              }
              echo "</td>\n";
              echo " </tr>\n";
            }
            ?>
        </table>
          <br>
        <input type="button" class="genreport" value="<?php xl('Generate Report','e'); ?>" />&nbsp;
        <input type="button" class="genpdfrep" value="<?php xl('Download PDF','e'); ?>" />&nbsp;
          <br><br><br>

        <span class="bold"><?php xl('Documents','e'); ?></span>:<br>
        <ul>
          <?php
            // show available documents
            $db = $GLOBALS['adodb']['db'];
            $sql = "SELECT d.id, d.url, c.name FROM documents AS d " .
                    "LEFT JOIN categories_to_documents AS ctd ON d.id=ctd.document_id " .
                    "LEFT JOIN categories AS c ON c.id = ctd.category_id WHERE " .
                    "d.foreign_id = " . $db->qstr($pid);
            $result = $db->Execute($sql);
            if ($db->ErrorMsg()) echo $db->ErrorMsg();
            while ($result && !$result->EOF) {
                echo "<li class='bold'>";
                echo '<input type="checkbox" name="documents[]" value="' .
                    $result->fields['id'] . '">';
                echo '&nbsp;&nbsp;<i>' .  xl_document_category($result->fields['name']) . "</i>";
                echo '&nbsp;&nbsp;' . xl('Name') . ': <i>' . basename($result->fields['url']) . "</i>";
                echo '</li>';
                $result->MoveNext();
            }
            ?>
        </ul>
      </form>
      <input type="button" class="genreport" value="<?php xl('Generate Report','e'); ?>" />&nbsp;
      <input type="button" class="genpdfrep" value="<?php xl('Download PDF','e'); ?>" />&nbsp;
    </div>
    </div>
    <!-- close patient_reports DIV -->
  </body>
  <script language="javascript">
    // jQuery stuff to make the page a little easier to use
    $(document).ready(function(){
        $(".genreport").click(function() { top.restoreSession(); document.report_form.pdf.value = 0; $("#report_form").submit(); });
        $(".genpdfrep").click(function() { top.restoreSession(); document.report_form.pdf.value = 1; $("#report_form").submit(); });
        $("#genfullreport").click(function() { location.href='<?php echo "$rootdir/patient_file/encounter/$returnurl";?>'; });
        //$("#printform").click(function() { PrintForm(); });
        $(".issuecheckbox").click(function() { issueClick(this); });

        // check/uncheck all Forms of an encounter
        $(".encounter").click(function() { SelectForms($(this)); });

        $('.pdf_proto').click(function() {
          $('#report_form').attr('action', '../../../modules/pdf_generator/selector.php');
          $('#report_form').submit();
        });
        
        $(".generateCCR").click(
            function() {
                    if(document.getElementById('show_date').checked == true){
                            if(document.getElementById('Start').value == '' || document.getElementById('End').value == ''){
                                    alert('<?php echo addslashes( xl('Please select a start date and end date')) ?>');
                                    return false;
                            }
                    }
            var ccrAction = document.getElementsByName('ccrAction');
            ccrAction[0].value = 'generate';
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'no';
            top.restoreSession();
            ccr_form.setAttribute("target", "_blank");
            $("#ccr_form").submit();
                    ccr_form.setAttribute("target", "");
        });
            $(".generateCCR_raw").click(
            function() {
                    var ccrAction = document.getElementsByName('ccrAction');
                    ccrAction[0].value = 'generate';
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'yes';
                    top.restoreSession();
                    ccr_form.setAttribute("target", "_blank");
                    $("#ccr_form").submit();
                    ccr_form.setAttribute("target", "");
            });
            $(".generateCCR_download_h").click(
            function() {
                    var ccrAction = document.getElementsByName('ccrAction');
                    ccrAction[0].value = 'generate';
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'hybrid';
                    top.restoreSession();
                    $("#ccr_form").submit();
            });
            $(".generateCCR_download_p").click(
            function() {
                    if(document.getElementById('show_date').checked == true){
                            if(document.getElementById('Start').value == '' || document.getElementById('End').value == ''){
                                    alert('<?php echo addslashes( xl('Please select a start date and end date')) ?>');
                                    return false;
                            }
                    }
                    var ccrAction = document.getElementsByName('ccrAction');
                    ccrAction[0].value = 'generate';
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'pure';
                    top.restoreSession();
                    $("#ccr_form").submit();
            });
        $(".viewCCD").click(
        function() {
            var ccrAction = document.getElementsByName('ccrAction');
            ccrAction[0].value = 'viewccd';
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'no';
            top.restoreSession();
                    ccr_form.setAttribute("target", "_blank");
            $("#ccr_form").submit();
                    ccr_form.setAttribute("target", "");
        });
            $(".viewCCD_raw").click(
            function() {
                    var ccrAction = document.getElementsByName('ccrAction');
                    ccrAction[0].value = 'viewccd';
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'yes';
                    top.restoreSession();
                    ccr_form.setAttribute("target", "_blank");
                    $("#ccr_form").submit();
                    ccr_form.setAttribute("target", "");
            });
            $(".viewCCD_download").click(
            function() {
                    var ccrAction = document.getElementsByName('ccrAction');
                    ccrAction[0].value = 'viewccd';
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'pure';
                    $("#ccr_form").submit();
            });
    <?php if ($GLOBALS['phimail_enable']==true && $GLOBALS['phimail_ccr_enable']==true) { ?>
            $(".viewCCR_send_dialog").click(
            function() {
                    $("#ccr_send_dialog").toggle();
            });
            $(".viewCCR_transmit").click(
            function() {
                    $(".viewCCR_transmit").attr('disabled','disabled');
                    var ccrAction = document.getElementsByName('ccrAction');
                    ccrAction[0].value = 'generate';
                    var ccrRecipient = $("#ccr_send_to").val();
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'send '+ccrRecipient;
                    if(ccrRecipient=="") {
                      $("#ccr_send_message").html("<?php
      echo htmlspecialchars(xl('Please enter a valid Direct Address above.'), ENT_QUOTES);?>");
                      $("#ccr_send_result").show();
                    } else {
                      $(".viewCCR_transmit").attr('disabled','disabled');
                      $("#ccr_send_message").html("<?php
      echo htmlspecialchars(xl('Working... this may take a minute.'), ENT_QUOTES);?>");
                      $("#ccr_send_result").show();
                      var action=$("#ccr_form").attr('action');
                      $.post(action, {ccrAction:'generate',raw:'send '+ccrRecipient,requested_by:'user'},
                         function(data) {
                           if(data=="SUCCESS") {
                             $("#ccr_send_message").html("<?php
      echo htmlspecialchars(xl('Your message was submitted for delivery to'), ENT_QUOTES);
                          ?> "+ccrRecipient);
                             $("#ccr_send_to").val("");
                           } else {
                             $("#ccr_send_message").html(data);
                           }
                           $(".viewCCR_transmit").removeAttr('disabled');
                      });
                    }
            });
    <?php }
      if ($GLOBALS['phimail_enable']==true && $GLOBALS['phimail_ccd_enable']==true) { ?>
            $(".viewCCD_send_dialog").click(
            function() {
                    $("#ccd_send_dialog").toggle();
            });
            $(".viewCCD_transmit").click(
            function() {
                    $(".viewCCD_transmit").attr('disabled','disabled');
                    var ccrAction = document.getElementsByName('ccrAction');
                    ccrAction[0].value = 'viewccd';
                    var ccdRecipient = $("#ccd_send_to").val();
                    var raw = document.getElementsByName('raw');
                    raw[0].value = 'send '+ccdRecipient;
                    if(ccdRecipient=="") {
                      $("#ccd_send_message").html("<?php
      echo htmlspecialchars(xl('Please enter a valid Direct Address above.'), ENT_QUOTES);?>");
                      $("#ccd_send_result").show();
                    } else {
                      $(".viewCCD_transmit").attr('disabled','disabled');
                      $("#ccd_send_message").html("<?php
      echo htmlspecialchars(xl('Working... this may take a minute.'), ENT_QUOTES);?>");
                      $("#ccd_send_result").show();
                      var action=$("#ccr_form").attr('action');
                      $.post(action, {ccrAction:'viewccd',raw:'send '+ccdRecipient,requested_by:'user'},
                         function(data) {
                           if(data=="SUCCESS") {
                             $("#ccd_send_message").html("<?php
      echo htmlspecialchars(xl('Your message was submitted for delivery to'), ENT_QUOTES);
                          ?> "+ccdRecipient);
                             $("#ccd_send_to").val("");
                           } else {
                             $("#ccd_send_message").html(data);
                           }
                           $(".viewCCD_transmit").removeAttr('disabled');
                      });
                    }
            });
    <?php } ?>

    });

    // select/deselect the Forms related to the selected Encounter
    // (it ain't pretty code folks)
    var SelectForms = function (selectedEncounter) {
        if ($(selectedEncounter).attr("checked")) {
            $(selectedEncounter).parent().children().each(function(i, obj) {
                $(this).children().each(function(i, obj) {
                    $(this).attr("checked", "checked");
                });
            });
        }
        else {
            $(selectedEncounter).parent().children().each(function(i, obj) {
                $(this).children().each(function(i, obj) {
                    $(this).removeAttr("checked");
                });
            });
        }
    }

    // When an issue is checked, auto-check all the related encounters and forms
    function issueClick(issue) {
        // do nothing when unchecked
        if (! $(issue).attr("checked")) return;

        $("#report_form :checkbox").each(function(i, obj) {
            if ($(issue).val().indexOf('/' + $(this).val() + '/') >= 0) {
                $(this).attr("checked", "checked");
            }

        });
    }

  </script>
</html>
