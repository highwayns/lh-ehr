<?php
/*
 * Immunizations
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
 * @author
 * @link http://librehealth.io 
 */

//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//

include_once("../../globals.php");
include_once("$srcdir/sql.inc");
include_once("$srcdir/options.inc.php");
include_once("$srcdir/immunization_helper.php");
require_once($GLOBALS['srcdir']."/formatting.inc.php");
$DateFormat = DateFormatRead();
$DateLocale = getLocaleCodeForDisplayLanguage($GLOBALS['language_default']);

if (isset($_GET['mode'])) {
    /*
     * THIS IS A BUG. IF NEW IMMUN IS ADDED AND USER PRINTS PDF, 
     * WHEN BACK IS CLICKED, ANOTHER ITEM GETS ADDED
     */
    
    if ($_GET['mode'] == "add") {       
        $sql = "REPLACE INTO immunizations set 
                      id = ?,
                      administered_date = if(?,?,NULL),  
                      immunization_id = ?,
                      cvx_code = ?, 
                      manufacturer = ?,
                      lot_number = ?,
                      administered_by_id = if(?,?,NULL),
                      administered_by = if(?,?,NULL),
                      education_date = if(?,?,NULL), 
                      vis_date = if(?,?,NULL), 
                      note   = ?,
                      patient_id   = ?,
                      created_by = ?,
                      updated_by = ?,
                      create_date = now(), 
                      amount_administered = ?,
                      amount_administered_unit = ?,
                      expiration_date = if(?,?,NULL),
                      route = ?,
                      administration_site = ? ,
                                          completion_status = ?";
    $sqlBindArray = array(
                 trim($_GET['id']),
             trim($_GET['administered_date']), trim($_GET['administered_date']),
             trim($_GET['form_immunization_id']),
             trim($_GET['cvx_code']),
             trim($_GET['manufacturer']),
             trim($_GET['lot_number']),
             trim($_GET['administered_by_id']), trim($_GET['administered_by_id']),
             trim($_GET['administered_by']), trim($_GET['administered_by']),
             trim($_GET['education_date']), trim($_GET['education_date']),
             trim($_GET['vis_date']), trim($_GET['vis_date']),
             trim($_GET['note']),
             $pid,
             $_SESSION['authId'],
             $_SESSION['authId'],
             trim($_GET['immuniz_amt_adminstrd']),
             trim($_GET['form_drug_units']),
             trim($_GET['immuniz_exp_date']), trim($_GET['immuniz_exp_date']),
             trim($_GET['immuniz_route']),
             trim($_GET['immuniz_admin_ste']),
                         trim($_GET['immuniz_completion_status'])
             );
        sqlStatement($sql,$sqlBindArray);
        $administered_date=date('Y-m-d H:i');
        $education_date=date('Y-m-d');
        $immunization_id=$cvx_code=$manufacturer=$lot_number=$administered_by_id=$note=$id="";
        $administered_by=$vis_date="";
        $newid = $_GET['id'] ? $_GET['id'] : $newid;
        if($GLOBALS['observation_results_immunization']) {
          saveImmunizationObservationResults($newid,$_GET);
        
    }
    }
    elseif ($_GET['mode'] == "delete" ) {
        // log the event
        newEvent("delete", $_SESSION['authUser'], $_SESSION['authProvider'], 1, "Immunization id ".$_GET['id']." deleted from pid ".$pid);
        // delete the immunization
        $sql="DELETE FROM immunizations WHERE id =? LIMIT 1";
        sqlStatement($sql, array($_GET['id']));
        
    }
    elseif ($_GET['mode'] == "added_error" ) {
        $sql = "UPDATE immunizations " .
               "SET added_erroneously=? "  .
               "WHERE id=?";
        $sql_arg_array = array(
                            ($_GET['isError'] === 'true'),
                            $_GET['id']
                         );
        sqlStatement($sql, $sql_arg_array);
    }
    elseif ($_GET['mode'] == "edit" ) {
        $sql = "select * from immunizations where id = ?";
        $result = sqlQuery($sql, array($_GET['id']));
        
        $administered_date = new DateTime($result['administered_date']);
        $administered_date = $administered_date->format('Y-m-d H:i');
        
        $immuniz_amt_adminstrd = $result['amount_administered'];
        $drugunitselecteditem = $result['amount_administered_unit'];
        $immunization_id = $result['immunization_id'];  
        $immuniz_exp_date = $result['expiration_date'];
        
        $cvx_code = $result['cvx_code'];
        $code_text = '';
        if ( !(empty($cvx_code)) ) {
            $query = "SELECT codes.code_text as `code_text`, codes.code as `code` " .
                     "FROM codes " .
                     "LEFT JOIN code_types on codes.code_type = code_types.ct_id " .
                     "WHERE code_types.ct_key = 'CVX' AND codes.code = ?";
            $result_code_text = sqlQuery($query, array($cvx_code));
            $code_text = $result_code_text['code_text'];
        }
        $manufacturer = $result['manufacturer'];
        $lot_number = $result['lot_number'];
        $administered_by_id = ($result['administered_by_id'] ? $result['administered_by_id'] : 0);

        $administered_by = "";
        if (!$result['administered_by'] && !$row['administered_by_id']) { 
            $stmt = "select concat(lname,', ',fname) as full_name ".
                    "from users where ".
                    "id=?";
            $user_result = sqlQuery($stmt, array($result['administered_by_id']));
            $administered_by = $user_result['full_name'];
        }
        
        $education_date = $result['education_date'];
        $vis_date = $result['vis_date'];
        $immuniz_route = $result['route'];
        $immuniz_admin_ste = $result['administration_site'];
        $note = $result['note'];
        $isAddedError = $result['added_erroneously'];
        
    $immuniz_completion_status = $result['completion_status'];  
    //set id for page
    $id = $_GET['id'];
    
    }
}

// Decide whether using the CVX list or the custom list in list_options
if ($GLOBALS['use_custom_immun_list']) {
  // user forces the use of the custom list
  $useCVX = false;
}
else {
  if ($_GET['mode'] == "edit") {
    //depends on if a cvx code is enterer already
    if (empty($cvx_code)) {
      $useCVX = false;
    }
    else {
      $useCVX = true;
    }
  }
  else { // $_GET['mode'] == "add"
    $useCVX = true;
  }
}

// set the default sort method for the list of past immunizations
$sortby = $_GET['sortby'];
if (!$sortby) { $sortby = 'vacc'; }

// set the default value of 'administered_by'
if (!$administered_by && !$administered_by_id) { 
    $stmt = "select concat(lname,', ',fname) as full_name ".
            " from users where ".
            " id=?";
    $row = sqlQuery($stmt, array($_SESSION['authId']));
    $administered_by = $row['full_name'];
}

?>
<html>
<head>
<?php html_header_show();?>

<!-- supporting javascript code -->
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>


<!-- page styles -->
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">

<style>
.highlight {
  color: green;
}
tr.selected {
  background-color: white;
}   
</style>
</head>

<body class="body_top">

    <span class="title"><?php echo htmlspecialchars( xl('Immunizations'), ENT_NOQUOTES); ?></span>
    <span>
        <a href="demographics.php" class="css_button" onclick="top.restoreSession()">
            <span><?php echo htmlspecialchars( xl('Back to Patient'), ENT_NOQUOTES);?></span>
        </a>
    </span>

    <form action="immunizations.php" name="add_immunization" id="add_immunization">
    <input type="hidden" name="mode" id="mode" value="add">
    <input type="hidden" name="id" id="id" value="<?php echo htmlspecialchars( $id, ENT_QUOTES); ?>"> 
    <input type="hidden" name="pid" id="pid" value="<?php echo htmlspecialchars( $pid, ENT_QUOTES); ?>"> 
    <br>
      <table border="0" cellpadding="1" cellspacing="1">
      <?php
        if ($isAddedError) {
            echo "<tr><font color='red'><b>" . xlt("Entered in Error") . "</b></font></tr>";
        }
      ?> 

      <?php if (!($useCVX)) { ?>
        <tr>
          <td align="right">
            <span class="text">
              <?php echo htmlspecialchars( xl('Immunization'), ENT_NOQUOTES); ?>
            </span>          
          </td>
          <td>
              <?php
                // Modified 7/2009 by BM to incorporate the immunization items into the list_options listings
                generate_form_field(array('data_type'=>1,'field_id'=>'immunization_id','list_id'=>'immunizations','empty_title'=>'SKIP'), $immunization_id);
              ?>          
           </td>
        </tr>
      <?php } else { ?>
        <tr>
          <td align="right" valign="top" style="padding-top:4px;">
            <span class="text">
              <?php echo htmlspecialchars( xl('Immunization'), ENT_NOQUOTES); ?> (<?php echo htmlspecialchars( xl('CVX Code'), ENT_NOQUOTES); ?>)            
            </span>          
          </td>
          <td>
           <input type='text' size='10' name='cvx_code' id='cvx_code'
            value='<?php echo htmlspecialchars($cvx_code,ENT_QUOTES); ?>' onclick='sel_cvxcode(this)'
            title='<?php echo htmlspecialchars( xl('Click to select or change CVX code'), ENT_QUOTES); ?>'
            />
            <div id='cvx_description' style='display:inline; float:right; padding:3px; margin-left:3px; width:400px'>
                <?php echo htmlspecialchars( xl( $code_text ), ENT_QUOTES); ?>          
            </div>        
          </td>
        </tr>
      <?php } ?>
        
        <tr>
          <td align="right">
            <span class="text">
              <?php echo htmlspecialchars( xl('Date & Time Administered'), ENT_NOQUOTES); ?>
            </span>          
          </td>
          <td>
            <table border="0">
                <tr>
                  <td><input type='text' size='14' name="administered_date" id="administered_date"
                       value='<?php echo $administered_date ? date(DateFormatRead(true) . ' H:i', strtotime(text($administered_date))) : date(str_replace('%','',$DateFormat.' H:i')); ?>'
                       title='<?php echo htmlspecialchars( xl('yyyy-mm-dd Hours(24):minutes'), ENT_QUOTES); ?>'
                       />
                   </td>
                </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td align="right"><span class="text"><?php echo htmlspecialchars( xl('Amount Administered'), ENT_NOQUOTES); ?></span></td>
          <td class='text'>
            <input class='text' type='text' name="immuniz_amt_adminstrd" size="25" value="<?php echo htmlspecialchars( $immuniz_amt_adminstrd, ENT_QUOTES); ?>">
            <?php echo generate_select_list("form_drug_units", "drug_units", $drugunitselecteditem,'Select Drug Unit',''); ?>
          </td>
        </tr>
        <tr>
          <td align="right"><span class="text"><?php echo htmlspecialchars( xl('Immunization Expiration Date'), ENT_NOQUOTES); ?></span></td>
          <td class='text'><input type='text' size='10' name="immuniz_exp_date" id="immuniz_exp_date"
             value='<?php echo $immuniz_exp_date ? htmlspecialchars( oeFormatShortDate($immuniz_exp_date), ENT_QUOTES) : ''; ?>'
                title='<?php echo htmlspecialchars( xl('yyyy-mm-dd'), ENT_QUOTES); ?>'/>
          </td>
        </tr>       
        <tr>
          <td align="right">
            <span class=text>
              <?php echo htmlspecialchars( xl('Immunization Manufacturer'), ENT_NOQUOTES); ?>            
            </span>          
          </td>
          <td>
              <?php echo generate_select_list('manufacturer', 'Immunization_Manufacturer', $manufacturer, 'Select Manufacturer', '');?>
        </tr>
        <tr>
          <td align="right">
            <span class=text>
              <?php echo htmlspecialchars( xl('Immunization Lot Number'), ENT_NOQUOTES); ?>            
            </span>          </td>
          <td>
            <input class='text' type='text' name="lot_number" size="25" value="<?php echo htmlspecialchars( $lot_number, ENT_QUOTES); ?>">          
          </td>
        </tr>
        <tr>
          <td align="right">
            <span class='text'>
              <?php echo htmlspecialchars( xl('Name and Title of Immunization Administrator'), ENT_NOQUOTES); ?>            
            </span>          
          </td>
          <td class='text'>
            <input type="text" name="administered_by" id="administered_by" size="25" value="<?php echo htmlspecialchars( $administered_by, ENT_QUOTES); ?>">
            <?php echo htmlspecialchars( xl('or choose'), ENT_NOQUOTES); ?>
<!-- NEEDS WORK -->
            <select name="administered_by_id" id='administered_by_id'>
            <option value=""></option>
              <?php
                $sql = "select id, concat(lname,', ',fname) as full_name " .
                       "from users where username != '' " .
                       "order by concat(lname,', ',fname)";

                $result = sqlStatement($sql);
                while($row = sqlFetchArray($result)){
                  echo '<OPTION VALUE=' . htmlspecialchars( $row{'id'}, ENT_QUOTES);
                  echo (isset($administered_by_id) && $administered_by_id != "" ? $administered_by_id : $_SESSION['authId']) == $row{'id'} ? ' selected>' : '>';
                  echo htmlspecialchars( $row{'full_name'}, ENT_NOQUOTES) . '</OPTION>';
                }
              ?>
            </select>          
          </td>
        </tr>
        <tr>
          <td align="right" class="text">
              <?php echo htmlspecialchars( xl('Date Immunization Information Statements Given'), ENT_NOQUOTES); ?>          
          </td>
          <td>
            <input type='text' size='10' name="education_date" id="education_date"
                    value='<?php echo $education_date? htmlspecialchars( $education_date, ENT_QUOTES) : oeFormatShortDate(date('Y-m-d')); ?>'
                    title='<?php echo htmlspecialchars( xl('yyyy-mm-dd'), ENT_QUOTES); ?>'/>
          </td>
        </tr>
        <tr>
          <td align="right" class="text">
              <?php echo htmlspecialchars( xl('Date of VIS Statement'), ENT_NOQUOTES); ?>
              (<a href="http://www.cdc.gov/vaccines/pubs/vis/default.htm" title="<?php echo htmlspecialchars( xl('Help'), ENT_QUOTES); ?>" target="_blank">?</a>)          
          </td>
          <td>
            <input type='text' size='10' name="vis_date" id="vis_date"
                    value='<?php echo $vis_date ? date(DateFormatRead(true), strtotime(text($vis_date))) : oeFormatShortDate(date('Y-m-d')); ?>'
                    title='<?php echo htmlspecialchars( xl('yyyy-mm-dd'), ENT_QUOTES); ?>'
            />
          </td>
        </tr>
        <tr>
          <td align="right" class='text'><?php echo htmlspecialchars( xl('Route'), ENT_NOQUOTES); ?></td>
          <td>
            <?php echo generate_select_list('immuniz_route', 'drug_route', $immuniz_route, 'Select Route', '');?>         
          </td>
        </tr>
        <tr>
          <td align="right" class='text'><?php echo htmlspecialchars( xl('Administration Site'), ENT_NOQUOTES); ?>
          </td>
          <td>
            <?php echo generate_select_list('immuniz_admin_ste', 'proc_body_site', $immuniz_admin_ste, 'Select Administration Site', ' ');?>
          </td>
        </tr>
        <tr>
          <td align="right" class='text'>
              <?php echo htmlspecialchars( xl('Notes'), ENT_NOQUOTES); ?>          
          </td>
          <td>
            <textarea class='text' name="note" id="note" rows=5 cols=25><?php echo htmlspecialchars( $note, ENT_NOQUOTES); ?></textarea>          
          </td>
        </tr>
        <tr>
          <td align="right" class='text'>
              <?php echo htmlspecialchars( xl('Completion Status'), ENT_NOQUOTES); ?>          
          </td>
          <td>
            <?php echo generate_select_list('immuniz_completion_status', 'Immunization_Completion_Status', $immuniz_completion_status, 'Select Completion Status', ' ');?>          
          </td>
        </tr>
        <tr>
          <td colspan="3" align="center">
            <input type="button" name="save" id="save" value="<?php echo htmlspecialchars( xl('Save Immunization'), ENT_QUOTES); ?>">
            <input type="button" name="print" id="print" value="<?php echo htmlspecialchars( xl('Print Record') . xl('PDF','',' (',')'), ENT_QUOTES); ?>">
            <input type="button" name="printHtml" id="printHtml" value="<?php echo htmlspecialchars( xl('Print Record') . xl('HTML','',' (',')'), ENT_QUOTES); ?>">
            <input type="reset" name="clear" id="clear" value="<?php echo htmlspecialchars( xl('Clear'), ENT_QUOTES); ?>">                    
          </td>
        </tr>
      </table>
</form>

<div id="immunization_list">

    <table border=0 cellpadding=3 cellspacing=0>

    <!-- some columns are sortable -->
    <tr class='text bold'>
    <th>
        <a href="javascript:top.restoreSession();location.href='immunizations.php?sortby=vacc';" title='<?php echo htmlspecialchars( xl('Sort by vaccine'), ENT_QUOTES); ?>'>
          <?php echo htmlspecialchars( xl('Vaccine'), ENT_NOQUOTES); ?></a>
        <span class='small' style='font-family:arial'><?php if ($sortby == 'vacc') { echo 'v'; } ?></span>
    </th>
    <th>
        <a href="javascript:top.restoreSession();location.href='immunizations.php?sortby=date';" title='<?php echo htmlspecialchars( xl('Sort by date'), ENT_QUOTES); ?>'>
          <?php echo htmlspecialchars( xl('Date'), ENT_NOQUOTES); ?></a>
        <span class='small' style='font-family:arial'><?php if ($sortby == 'date') { echo 'v'; } ?></span>
    </th>
    <th><?php echo htmlspecialchars( xl('Amount'), ENT_NOQUOTES); ?></th>
    <th><?php echo xlt('Expiration'); ?></th>
    <th><?php echo htmlspecialchars( xl('Manufacturer'), ENT_NOQUOTES); ?></th>
    <th><?php echo htmlspecialchars( xl('Lot Number'), ENT_NOQUOTES); ?></th>
    <th><?php echo htmlspecialchars( xl('Administered By'), ENT_NOQUOTES); ?></th>
    <th><?php echo htmlspecialchars( xl('Education Date'), ENT_NOQUOTES); ?></th>
    <th><?php echo htmlspecialchars( xl('Route'), ENT_NOQUOTES); ?></th>
    <th><?php echo htmlspecialchars( xl('Administered Site'), ENT_NOQUOTES); ?></th>
    <th><?php echo htmlspecialchars( xl('Notes'), ENT_NOQUOTES); ?></th>
    <th><?php echo htmlspecialchars( xl('Completion Status'), ENT_NOQUOTES); ?></th>
    <th><?php echo htmlspecialchars( xl('Error'), ENT_NOQUOTES); ?></th>
    <th>&nbsp;</th>
    </tr>
    
<?php
        $result = getImmunizationList($pid, $_GET['sortby'], true);
            
        while($row = sqlFetchArray($result)) {
            $isError = $row['added_erroneously'];
            
            if ($isError) {
                $tr_title = 'title="' . xla("Entered in Error") . '"';
            } else {
                $tr_title = "";
            }

            if ($row["id"] == $id) {
                echo "<tr " . $tr_title . " class='immrow text selected' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."'>";
            }
            else {
                echo "<tr " . $tr_title . " class='immrow text' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."'>";
            }

            // Figure out which name to use (ie. from cvx list or from the custom list)
            if ($GLOBALS['use_custom_immun_list']) {
                $vaccine_display = generate_display_field(array('data_type'=>'1','list_id'=>'immunizations'), $row['immunization_id']);
            }
            else {
                if (!empty($row['code_text_short'])) {
                    $vaccine_display = htmlspecialchars( xl($row['code_text_short']), ENT_NOQUOTES);
                }
                else {
                    $vaccine_display = generate_display_field(array('data_type'=>'1','list_id'=>'immunizations'), $row['immunization_id']);
                }
            } 
            
            if ($isError) {
                $del_tag_open = "<del>";
                $del_tag_close = "</del>";
            } else {
                $del_tag_open = "";
                $del_tag_close = "";                
            }           
            
            echo "<td>" . $del_tag_open . $vaccine_display . $del_tag_close . "</td>";
            
            if ($row["administered_date"]) {
                $administered_date_summary = new DateTime($row['administered_date']);
                $administered_date_summary = $administered_date_summary->format('Y-m-d H:i');
            } else {
                $administered_date_summary = "";
            }           
            echo "<td>" . $del_tag_open .text(date(DateFormatRead(true) . ' H:i:s', strtotime($administered_date_summary))) . $del_tag_close . "</td>";
                        if ($row["amount_administered"] > 0) {
                    echo "<td>" . $del_tag_open . htmlspecialchars( $row["amount_administered"] . " " . generate_display_field(array('data_type'=>'1','list_id'=>'drug_units'), $row['amount_administered_unit']) , ENT_NOQUOTES) . $del_tag_close . "</td>";
                        }
                        else {
                               echo "<td>&nbsp</td>";
                        }
            echo "<td>" . $del_tag_open . text(date(DateFormatRead(true) . ' H:i:s', strtotime($row["expiration_date"]))) . $del_tag_close . "</td>";
                        echo "<td>" . $del_tag_open . htmlspecialchars( $row["manufacturer"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["lot_number"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["administered_by"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["education_date"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . generate_display_field(array('data_type'=>'1','list_id'=>'drug_route'), $row['route']) . $del_tag_close . "</td>";            
            echo "<td>" . $del_tag_open . generate_display_field(array('data_type'=>'1','list_id'=>'proc_body_site'), $row['administration_site']) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["note"], ENT_NOQUOTES) . $del_tag_close . "</td>";
                        echo "<td>" . $del_tag_open . generate_display_field(array('data_type'=>'1','list_id'=>'Immunization_Completion_Status'), $row['completion_status']) . $del_tag_close . "</td>";
            
            if ($isError) {
                $checkbox = "checked";
            } else {
                $checkbox = "";
            }
            
            echo "<td><input type='checkbox' class='error' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."' value='" . htmlspecialchars( xl('Error'), ENT_QUOTES) . "' " . $checkbox . "></td>";
            
            echo "<td><input type='button' class='delete' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."' value='" . htmlspecialchars( xl('Delete'), ENT_QUOTES) . "'></td>";
            echo "</tr>";
        }

?>

    </table>
</div> <!-- end immunizations -->

  </body>
<link rel="stylesheet" href="../../../library/css/jquery.datetimepicker.css">
<script type="text/javascript" src="../../../library/js/jquery.datetimepicker.full.min.js"></script>

<script language="javascript">
/* required for popup calendar */
$(function() {
    $("#administered_date").datetimepicker({
        timepicker: true,
        format: "<?= DateFormatRead(true); ?>"
    });
    $("#immuniz_exp_date, #education_date, #vis_date").datetimepicker({
        timepicker: false,
        format: "<?= $DateFormat; ?>"
    });
    $.datetimepicker.setLocale('<?= $DateLocale;?>');
});

// jQuery stuff to make the page a little easier to use

$(document).ready(function(){
    <?php if (!($useCVX)) { ?>
      $("#save").click(function() { SaveForm(); });
    <?php } else { ?>
      $("#save").click(function() { 
        if (validate_cvx()) {
          SaveForm();
        }
        else {
          return;
        }
      });
    <?php } ?>
    $("#print").click(function() { PrintForm("pdf"); });
    $("#printHtml").click(function() { PrintForm("html"); });
    $(".immrow").click(function() { EditImm(this); });
    $(".error").click(function(event) { ErrorImm(this); event.stopPropagation(); });
    $(".delete").click(function(event) { DeleteImm(this); event.stopPropagation(); });

    $(".immrow").mouseover(function() { $(this).toggleClass("highlight"); });
    $(".immrow").mouseout(function() { $(this).toggleClass("highlight"); });

    $("#administered_by_id").change(function() { $("#administered_by").val($("#administered_by_id :selected").text()); });

    $("#form_immunization_id").change( function() {
        if ( $(this).val() != "" ) {
            $("#cvx_code").val( "" );
            $("#cvx_description").text( "" );
            $("#cvx_code").change();
        }
    });
});

var PrintForm = function(typ) {
    top.restoreSession();
    newURL='shot_record.php?output='+typ+'&sortby=<?php echo $sortby; ?>';
    window.open(newURL, '_blank', "menubar=1,toolbar=1,scrollbars=1,resizable=1,width=600,height=450");
}

var SaveForm = function() {
    top.restoreSession();
    $("#add_immunization").submit();
}

var EditImm = function(imm) {
    top.restoreSession();
    location.href='immunizations.php?mode=edit&id='+imm.id;
}

var DeleteImm = function(imm) {
    if (confirm("<?php echo htmlspecialchars( xl('This action cannot be undone.'), ENT_QUOTES); ?>" + "\n" +"<?php echo htmlspecialchars( xl('Do you wish to PERMANENTLY delete this immunization record?'), ENT_QUOTES); ?>")) {
        top.restoreSession();
        location.href='immunizations.php?mode=delete&id='+imm.id;
    }
}

var ErrorImm = function(imm) {
    top.restoreSession();
    location.href='immunizations.php?mode=added_error&id='+imm.id+'&isError='+imm.checked;
}

//This is for callback by the find-code popup.
//Appends to or erases the current list of diagnoses.
function set_related(codetype, code, selector, codedesc) {
    var f = document.forms[0][current_sel_name];
    var s = f.value;
    
    if (code) {
        s = code;
    }
    else {
        s = '';
    }
    
    f.value = s;
    $("#cvx_description").text( codedesc );
    $("#form_immunization_id").attr( "value", "" );
    $("#form_immunization_id").change();
}


// This invokes the find-code popup.
function sel_cvxcode(e) {
 current_sel_name = e.name;
 dlgopen('../encounter/find_code_popup.php?codetype=CVX', '_blank', 500, 400);
}

// This ensures the cvx centric entry is filled.
function validate_cvx() {
 if (document.add_immunization.cvx_code.value>0) {
  return true;
 }
 else {
  document.add_immunization.cvx_code.style.backgroundColor="red";
  document.add_immunization.cvx_code.focus();
  return false;
 }   
}

</script>

</html>
