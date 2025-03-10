<?php
/*
*
* Fee Sheet Program used to create charges, copays and add diagnosis codes to the encounter
*
* The changes to this file as of November 16 2016 to include the exclusion of information from claims
* are covered under the terms of the Mozilla Public License, v. 2.0
*
* @copyright Copyright (C) 2016-2017 Terry Hill <teryhill@librehealth.io>
* Copyright (C) 2005-2015 Rod Roark <rod@sunsetsystems.com>
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
* LICENSE: This Source Code is subject to the terms of the Mozilla Public License, v. 2.0.
* See the Mozilla Public License for more details.
* If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
*
* @package LibreHealth EHR
* @author Rod Roark <rod@sunsetsystems.com>
* @author Terry Hill <teryhill@librehealth.io>
* @link http://librehealth.io
*
* Please help the overall project by sending changes you make to the authors and to the LibreHealth EHR community.
*
*/

$fake_register_globals=false;
$sanitize_all_escapes=true;

require_once("../../globals.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/api.inc");
require_once("codes.php");
require_once("../../../custom/code_types.inc.php");
require_once("../../drugs/drugs.inc.php");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/formdata.inc.php");
require_once("$srcdir/log.inc");
require_once("$srcdir/headers.inc.php");

// For logging checksums set this to true.
define('CHECKSUM_LOGGING', true);

// Some table cells will not be displayed unless insurance billing is used.
$usbillstyle = $GLOBALS['ippf_specific'] ? " style='display:none'" : "";

// This may be an error message or warning that pops up when the form is loaded.
$alertmsg = '';

function alphaCodeType($id) {
  global $code_types;
  foreach ($code_types as $key => $value) {
    if ($value['id'] == $id) return $key;
  }
  return '';
}

// Helper function for creating drop-lists.
function endFSCategory() {
  global $i, $last_category, $FEE_SHEET_COLUMNS;
  if (! $last_category) return;
  echo "   </select>\n";
  echo "  </td>\n";
  if ($i >= $FEE_SHEET_COLUMNS) {
    echo " </tr>\n";
    $i = 0;
  }
}

// Generate JavaScript to build the array of diagnoses.
function genDiagJS($code_type, $code) {
  global $code_types;
  if ($code_types[$code_type]['diag']) {
    echo "diags.push('" . attr($code_type) . "|" . attr($code) . "');\n";
  }
}

// For IPPF only.  Returns 0 = none, 1 = nonsurgical, 2 = surgical.
//
function contraceptionClass($code_type, $code) {
  global $code_types;
  if (!$GLOBALS['ippf_specific']) return 0;
  $contra = 0;
  // Get the related service codes.
  $codesrow = sqlQuery("SELECT related_code FROM codes WHERE " .
    "code_type = ? " .
    " AND code = ? LIMIT 1", array($code_types[$code_type]['id'],$code) );
  if (!empty($codesrow['related_code']) && $code_type == 'MA') {
    $relcodes = explode(';', $codesrow['related_code']);
    foreach ($relcodes as $relstring) {
      if ($relstring === '') continue;
      list($reltype, $relcode) = explode(':', $relstring);
      if ($reltype !== 'IPPF') continue;
      if      (preg_match('/^11....110/'    , $relcode)) $contra |= 1;
      else if (preg_match('/^11....999/'    , $relcode)) $contra |= 1;
      else if (preg_match('/^112152010/'    , $relcode)) $contra |= 1;
      else if (preg_match('/^11317[1-2]111/', $relcode)) $contra |= 1;
      else if (preg_match('/^12118[1-2].13/', $relcode)) $contra |= 2;
      else if (preg_match('/^12118[1-2]999/', $relcode)) $contra |= 2;
    }
  }
  return $contra;
}
# gets the provider from the encounter file , or from the logged on user or from the patient file
function findProvider() {
  global $encounter, $pid;
  $find_provider = sqlQuery("SELECT provider_id FROM form_encounter " .
        "WHERE pid = ? AND encounter = ? " .
        "ORDER BY id DESC LIMIT 1", array($pid,$encounter) );
  $providerid = $find_provider['provider_id'];
  if($providerid == 0) {
   $get_authorized = $_SESSION['userauthorized'];
   if($get_authorized ==1) {
      $providerid = $_SESSION[authUserID];
   }
  }
  if($providerid == 0) {
    $find_provider = sqlQuery("SELECT providerID FROM patient_data " .
        "WHERE pid = ? ", array($pid) );
    $providerid = $find_provider['providerID'];
  }
  return $providerid;
}

// This writes a billing line item to the output page.
//
function echoLine($lino, $codetype, $code, $modifier, $ndc_info='',
  $auth = TRUE, $del = FALSE, $units = NULL, $fee = NULL, $id = NULL,
  $billed = FALSE, $code_text = NULL, $justify = NULL, $provider_id = 0, $notecodes='', $exclude ="0")
{
  global $code_types, $ndc_applies, $ndc_uom_choices, $justinit, $pid;
  global $contraception, $usbillstyle, $hasCharges;

  // If using line item billing and user wishes to default to a selected provider, then do so.
  if($GLOBALS['default_fee_sheet_line_item_provider'] == 1 && $GLOBALS['support_fee_sheet_line_item_provider'] ==1 ) {
    if ($provider_id == 0) {
      $provider_id = 0 + findProvider();
    }
  }

  if ($codetype == 'COPAY') {
    if (!$code_text) $code_text = 'Cash';
    if ($fee > 0) $fee = 0 - $fee;
  }
  if (! $code_text) {
    $sqlArray = array();
    $query = "select id, units, exclude_from_insurance_billing, code_text from codes where code_type = ? " .
      " and " .
      "code = ? and ";
    array_push($sqlArray,$code_types[$codetype]['id'],$code);
    if ($modifier) {
      $query .= "modifier = ?";
      array_push($sqlArray,$modifier);
    } else {
      $query .= "(modifier is null or modifier = '')";
    }
    $result = sqlQuery($query, $sqlArray);
    $code_text = $result['code_text'];
    $exclude = $result['exclude_from_insurance_billing'];

    if (empty($units)) $units = max(1, intval($result['units']));
    if (!isset($fee)) {
      // Fees come from the prices table now.
      $query = "SELECT prices.pr_price " .
        "FROM patient_data, prices WHERE " .
        "patient_data.pid = ? AND " .
        "prices.pr_id = ? AND " .
        "prices.pr_selector = '' AND " .
        "prices.pr_level = patient_data.pricelevel " .
        "LIMIT 1";
      echo "\n<!-- $query -->\n"; // debugging
      $prrow = sqlQuery($query, array($pid,$result['id']) );
      $fee = empty($prrow) ? 0 : $prrow['pr_price'];
    }
  }
  $fee = sprintf('%01.2f', $fee);
  if (empty($units)) $units = 1;
  $units = max(1, intval($units));
  // We put unit price on the screen, not the total line item fee.
  $price = $fee / $units;
  $strike1 = ($id && $del) ? "<strike>" : "";
  $strike2 = ($id && $del) ? "</strike>" : "";
  echo " <tr>\n";
  echo "  <td class='billcell'>$strike1" .
    ($codetype == 'COPAY' ? xl($codetype) : $codetype) . $strike2;
  //if the line to ouput is copay, show the date here passed as $ndc_info,
  //since this variable is not applicable in the case of copay.
  if($codetype == 'COPAY'){
    echo "(".htmlspecialchars($ndc_info).")";
    $ndc_info = '';
  }
  if ($id) {
    echo "<input type='hidden' name='bill[".attr($lino)."][id]' value='$id'>";
  }
  echo "<input type='hidden' name='bill[".attr($lino)."][code_type]' value='".attr($codetype)."'>";
  echo "<input type='hidden' name='bill[".attr($lino)."][code]' value='".attr($code)."'>";
  echo "<input type='hidden' name='bill[".attr($lino)."][billed]' value='".attr($billed)."'>";
  echo "</td>\n";
  if ($codetype != 'COPAY') {
    echo "  <td class='billcell'>$strike1" . text($code) . "$strike2</td>\n";
  } else {
    echo "  <td class='billcell'>&nbsp;</td>\n";
  }
  if ($billed) {
    if (modifiers_are_used(true)) {
      echo "  <td class='billcell'>$strike1" . text($modifier) . "$strike2" .
        "<input type='hidden' name='bill[".attr($lino)."][mod]' value='".attr($modifier)."'></td>\n";
    }
    if (fees_are_used()) {
      echo "  <td class='billcell' align='right'>" . text(oeFormatMoney($price)) . "</td>\n";
      if ($codetype != 'COPAY') {
        echo "  <td class='billcell' align='center'>" . text($units) . "</td>\n";
      } else {
        echo "  <td class='billcell'>&nbsp;</td>\n";
      }
    }
    if (justifiers_are_used()) {
      echo "  <td class='billcell' align='center'$usbillstyle>" . text($justify) . "</td>\n";
    }

    // Show provider for this line (if using line item billing).
    if($GLOBALS['support_fee_sheet_line_item_provider'] ==1) {
      echo "  <td class='billcell' align='center'>";
    }
    else
    {
      echo "  <td class='billcell' align='center' style='display: none'>";
    }
      genProviderSelect('', '-- '.xl("Default").' --', $provider_id, true);
      echo "</td>\n";

    if ($code_types[$codetype]['claim'] && !$code_types[$codetype]['diag']) {
      echo "  <td class='billcell' align='center'$usbillstyle>" .
        htmlspecialchars($notecodes, ENT_NOQUOTES) . "</td>\n";
    }
    else {
      echo "  <td class='billcell' align='center'$usbillstyle></td>\n";
    }
    echo "  <td class='billcell' align='center'$usbillstyle><input type='checkbox'" .
      ($auth ? " checked" : "") . " disabled /></td>\n";
    echo "  <td class='billcell' align='center'><input type='checkbox'" .
      " disabled /></td>\n";
    if($GLOBALS['bill_to_patient'] ==1) {
    echo "  <td class='billcell' align='center'$usbillstyle><input type='checkbox'" .
      ($exclude ? " checked" : "") . " disabled /></td>\n";
  }
  }
  else { // not billed
    if (modifiers_are_used(true)) {
      if ($codetype != 'COPAY' && ($code_types[$codetype]['mod'] || $modifier)) {
        echo "  <td class='billcell'><input type='text' name='bill[".attr($lino)."][mod]' " .
             "value='" . attr($modifier) . "' " .
             "title='" . xla("Multiple modifiers can be separated by colons or spaces, maximum of 4 (M1:M2:M3:M4)") . "' " .
             "value='" . attr($modifier) . "' size='" . attr($code_types[$codetype]['mod']) . "'></td>\n";
      } else {
        echo "  <td class='billcell'>&nbsp;</td>\n";
      }
    }
    if (fees_are_used()) {
      if ($codetype == 'COPAY' || $code_types[$codetype]['fee'] || $fee != 0) {
        echo "  <td class='billcell' align='right'>" .
          "<input type='text' name='bill[".attr($lino)."][price]' " .
          "value='" . attr($price) . "' size='6'";
        if (acl_check('acct','disc'))
          echo " style='text-align:right'";
        else
          echo " style='text-align:right;background-color:transparent' readonly";
        echo "></td>\n";
        echo "  <td class='billcell' align='center'>";
        if ($codetype != 'COPAY') {
          echo "<input type='text' name='bill[".attr($lino)."][units]' " .
          "value='" . attr($units) . "' size='2' style='text-align:right'>";
        } else {
          echo "<input type='hidden' name='bill[".attr($lino)."][units]' value='" . attr($units) . "'>";
        }
        echo "</td>\n";
      } else {
        echo "  <td class='billcell'>&nbsp;</td>\n";
        echo "  <td class='billcell'>&nbsp;</td>\n";
      }
    }
    if (justifiers_are_used()) {
      if ($code_types[$codetype]['just'] || $justify) {
        echo "  <td class='billcell' align='center'$usbillstyle ";
        echo "title='" . xla("Select one or more diagnosis codes to justify the service") . "' >";
        echo "<select name='bill[".attr($lino)."][justify]' onchange='setJustify(this)'>";
        echo "<option value='" . attr($justify) . "'>" . text($justify) . "</option></select>";
        echo "</td>\n";
        $justinit .= "setJustify(f['bill[".attr($lino)."][justify]']);\n";
      } else {
        echo "  <td class='billcell'$usbillstyle>&nbsp;</td>\n";
      }
    }

    // Show provider for this line (if using line item billing)
    if($GLOBALS['support_fee_sheet_line_item_provider'] ==1) {
      echo "  <td class='billcell' align='center'>";
    }
    else
    {
      echo "  <td class='billcell' align='center' style='display: none'>";
    }
      genProviderSelect("bill[$lino][provid]", '-- '.xl("Default").' --', $provider_id);
      echo "</td>\n";

    if ($code_types[$codetype]['claim'] && !$code_types[$codetype]['diag']) {
      echo "  <td class='billcell' align='center'$usbillstyle><input type='text' name='bill[".attr($lino)."][notecodes]' " .
        "value='" . htmlspecialchars($notecodes, ENT_QUOTES) . "' maxlength='50' size='8' /></td>\n";
    }
    else {
      echo "  <td class='billcell' align='center'$usbillstyle></td>\n";
    }
    echo "  <td class='billcell' align='center'$usbillstyle><input type='checkbox' name='bill[".attr($lino)."][auth]' " .
      "value='1'" . ($auth ? " checked" : "") . " /></td>\n";
    echo "  <td class='billcell' align='center'><input type='checkbox' name='bill[".attr($lino)."][del]' " .
      "value='1'" . ($del ? " checked" : "") . " /></td>\n";
        //ADD THE NEW CHECKBOX "Bill to Patient EXCLUDE"
    if($GLOBALS['bill_to_patient'] ==1) {
      echo "  <td class='billcell' align='center'><input type='checkbox' name='bill[".attr($lino)."][exclude_from_insurance_billing]' " .
        "value='1'" . ($exclude ? " checked" : "") . " /></td>\n";
    }
  }

  echo "  <td class='billcell'>$strike1" . text($code_text) . "$strike2</td>\n";
  echo " </tr>\n";

  // If NDC info exists or may be required, add a line for it.
  if ($codetype == 'HCPCS' && $ndc_applies && !$billed) {
    $ndcnum = ''; $ndcuom = ''; $ndcqty = '';
    if (preg_match('/^N4(\S+)\s+(\S\S)(.*)/', $ndc_info, $tmp)) {
      $ndcnum = $tmp[1]; $ndcuom = $tmp[2]; $ndcqty = $tmp[3];
    }
    echo " <tr>\n";
    echo "  <td class='billcell' colspan='2'>&nbsp;</td>\n";
    echo "  <td class='billcell' colspan='6'>&nbsp;NDC:&nbsp;";
    echo "<input type='text' name='bill[".attr($lino)."][ndcnum]' value='" . attr($ndcnum) . "' " .
      "size='11' style='background-color:transparent'>";
    echo " &nbsp;Qty:&nbsp;";
    echo "<input type='text' name='bill[".attr($lino)."][ndcqty]' value='" . attr($ndcqty) . "' " .
      "size='3' style='background-color:transparent;text-align:right'>";
    echo " ";
    echo "<select name='bill[".attr($lino)."][ndcuom]' style='background-color:transparent'>";
    foreach ($ndc_uom_choices as $key => $value) {
      echo "<option value='" . attr($key) . "'";
      if ($key == $ndcuom) echo " selected";
      echo ">" . text($value) . "</option>";
    }
    echo "</select>";
    echo "</td>\n";
    echo " </tr>\n";
  }
  else if ($ndc_info) {
    echo " <tr>\n";
    echo "  <td class='billcell' colspan='2'>&nbsp;</td>\n";
    echo "  <td class='billcell' colspan='6'>&nbsp;" . xlt("NDC Data") . ": " . text($ndc_info) . "</td>\n";
    echo " </tr>\n";
  }

  // For IPPF.  Track contraceptive services.
  if (!$del) $contraception |= contraceptionClass($codetype, $code);

  if ($fee != 0) $hasCharges = true;
}

// This writes a product (drug_sales) line item to the output page.
//
function echoProdLine($lino, $drug_id, $del = FALSE, $units = NULL,
  $fee = NULL, $sale_id = 0, $billed = FALSE)
{
  global $code_types, $ndc_applies, $pid, $usbillstyle, $hasCharges;

  $drow = sqlQuery("SELECT name FROM drugs WHERE drug_id = ?", array($drug_id) );
  $code_text = $drow['name'];

  $fee = sprintf('%01.2f', $fee);
  if (empty($units)) $units = 1;
  $units = max(1, intval($units));
  // We put unit price on the screen, not the total line item fee.
  $price = $fee / $units;
  $strike1 = ($sale_id && $del) ? "<strike>" : "";
  $strike2 = ($sale_id && $del) ? "</strike>" : "";
  echo " <tr>\n";
  echo "  <td class='billcell'>{$strike1}" . xlt("Product") . "$strike2";
  echo "<input type='hidden' name='prod[".attr($lino)."][sale_id]' value='" . attr($sale_id) . "'>";
  echo "<input type='hidden' name='prod[".attr($lino)."][drug_id]' value='" . attr($drug_id) . "'>";
  echo "<input type='hidden' name='prod[".attr($lino)."][billed]' value='" . attr($billed) . "'>";
  echo "</td>\n";
  echo "  <td class='billcell'>$strike1" . text($drug_id) . "$strike2</td>\n";
  if (modifiers_are_used(true)) {
    echo "  <td class='billcell'>&nbsp;</td>\n";
  }
  if ($billed) {
    if (fees_are_used()) {
      echo "  <td class='billcell' align='right'>" . text(oeFormatMoney($price)) . "</td>\n";
      echo "  <td class='billcell' align='center'>" . text($units) . "</td>\n";
    }
    if (justifiers_are_used()) {
      echo "  <td class='billcell' align='center'$usbillstyle>&nbsp;</td>\n"; // justify
    }
    echo "  <td class='billcell' align='center'>&nbsp;</td>\n";             // provider
    echo "  <td class='billcell' align='center'$usbillstyle>&nbsp;</td>\n"; // note codes
    echo "  <td class='billcell' align='center'$usbillstyle>&nbsp;</td>\n"; // auth
    echo "  <td class='billcell' align='center'><input type='checkbox'" .   // del
      " disabled /></td>\n";
  } else {
    if (fees_are_used()) {
      echo "  <td class='billcell' align='right'>" .
        "<input type='text' name='prod[".attr($lino)."][price]' " .
        "value='" . attr($price) . "' size='6'";
      if (acl_check('acct','disc'))
        echo " style='text-align:right'";
      else
        echo " style='text-align:right;background-color:transparent' readonly";
      echo "></td>\n";
      echo "  <td class='billcell' align='center'>";
      echo "<input type='text' name='prod[".attr($lino)."][units]' " .
        "value='" . attr($units) . "' size='2' style='text-align:right'>";
      echo "</td>\n";
    }
    if (justifiers_are_used()) {
      echo "  <td class='billcell'$usbillstyle>&nbsp;</td>\n"; // justify
    }
    echo "  <td class='billcell' align='center'>&nbsp;</td>\n"; // provider
    echo "  <td class='billcell' align='center'$usbillstyle>&nbsp;</td>\n"; // note codes
    echo "  <td class='billcell' align='center'$usbillstyle>&nbsp;</td>\n"; // auth
    echo "  <td class='billcell' align='center'><input type='checkbox' name='prod[".attr($lino)."][del]' " .
      "value='1'" . ($del ? " checked" : "") . " /></td>\n";
    if($GLOBALS['bill_to_patient'] ==1) {
      echo "  <td class='billcell' align='center'><input type='checkbox' name='bill[".attr($lino)."][exclude_from_insurance_billing]' " .
        "value='1'" . ($del ? " checked" : "") . " /></td>\n";
    }
  }

  echo "  <td class='billcell'>$strike1" . text($code_text) . "$strike2</td>\n";
  echo " </tr>\n";

  if ($fee != 0) $hasCharges = true;
}


// Compute a current checksum of Fee Sheet data from the database.
//
function visitChecksum($pid, $encounter, $saved=false) {
  $rowb = sqlQuery("SELECT BIT_XOR(CRC32(CONCAT_WS(',', " .
    "id, code, modifier, units, fee, authorized, provider_id, ndc_info, justify, billed" .
    "))) AS checksum FROM billing WHERE " .
    "pid = ? AND encounter = ? AND activity = 1",
    array($pid, $encounter));
  $rowp = sqlQuery("SELECT BIT_XOR(CRC32(CONCAT_WS(',', " .
    "sale_id, inventory_id, prescription_id, quantity, fee, sale_date, billed" .
    "))) AS checksum FROM drug_sales WHERE " .
    "pid = ? AND encounter = ?",
    array($pid, $encounter));
  $ret = intval($rowb['checksum']) ^ intval($rowp['checksum']);
  if (CHECKSUM_LOGGING) {
    $comment = "Checksum = '$ret'";
    $comment .= ", AJAX = " . (empty($_POST['running_as_ajax']) ? "false" : "true");
    $comment .= ", Save = " . (empty($_POST['bn_save']) ? "false" : "true");
    $comment .= ", Saved = " . ($saved ? "true" : "false");
    newEvent("checksum", $_SESSION['authUser'], $_SESSION['authProvider'], 1, $comment, $pid);
  }
  return $ret;
}

// This is just for IPPF, to indicate if the visit includes contraceptive services.
$contraception = 0;

// Possible units of measure for NDC drug quantities.
//
$ndc_uom_choices = array(
  'ML' => 'ML',
  'GR' => 'Grams',
  'ME' => 'Milligrams',
  'F2' => 'I.U.',
  'UN' => 'Units'
);

// $FEE_SHEET_COLUMNS should be defined in codes.php.
if (empty($FEE_SHEET_COLUMNS)) $FEE_SHEET_COLUMNS = 2;

$returnurl = 'encounter_top.php';

// Update price level in patient demographics.
if (!empty($_POST['pricelevel'])) {
  sqlStatement("UPDATE patient_data SET pricelevel = ? WHERE pid = ?", array($_POST['pricelevel'],$pid) );
}

// Get some info about this visit.
$visit_row = sqlQuery("SELECT fe.date, opc.pc_catname " .
  "FROM form_encounter AS fe " .
  "LEFT JOIN libreehr_postcalendar_categories AS opc ON opc.pc_catid = fe.pc_catid " .
  "WHERE fe.pid = ? AND fe.encounter = ? LIMIT 1", array($pid,$encounter) );
$visit_date = substr($visit_row['date'], 0, 10);

$current_checksum = visitChecksum($pid, $encounter);
// It's important to look for a checksum mismatch even if we're just refreshing
// the display, otherwise the error goes undetected on a refresh-then-save.
if (isset($_POST['form_checksum'])) {
  if ($_POST['form_checksum'] != $current_checksum) {
    $alertmsg = xl('Someone else has just changed this visit. Please cancel this page and try again.');
    if (CHECKSUM_LOGGING) {
      $comment = "CHECKSUM ERROR, expecting '{$_POST['form_checksum']}'";
      newEvent("checksum", $_SESSION['authUser'], $_SESSION['authProvider'], 1, $comment, $pid);
    }
  }
}

// If Save or Save-and-Close was clicked, save the new and modified billing
// lines; then if no error, redirect to $returnurl.
//
if (!$alertmsg && ($_POST['bn_save'] || $_POST['bn_save_close'])) {
  $main_provid = 0 + $_POST['ProviderID'];
  $main_supid  = 0 + $_POST['SupervisorID'];
  $main_order  = 0 + $_POST['OrderingID'];
  $main_referr  = 0 + $_POST['ReferringID'];
  $main_contract  = 0 + $_POST['ContractID'];
  if ($main_supid == $main_provid) $main_supid = 0;
  $default_warehouse = $_POST['default_warehouse'];

  $bill = $_POST['bill'];
  $copay_update = FALSE;
  $update_session_id = '';
  $ct0 = '';//takes the code type of the first fee type code type entry from the fee sheet, against which the copay is posted
  $cod0 = '';//takes the code of the first fee type code type entry from the fee sheet, against which the copay is posted
  $mod0 = '';//takes the modifier of the first fee type code type entry from the fee sheet, against which the copay is posted
  for ($lino = 1; $bill["$lino"]['code_type']; ++$lino) {
    $iter = $bill["$lino"];
    $code_type = $iter['code_type'];
    $code      = $iter['code'];
    $del       = $iter['del'];
    $exclude   = $iter['exclude_from_insurance_billing'];

    // Skip disabled (billed) line items.
    if ($iter['billed']) continue;

    $id        = $iter['id'];
    $modifier  = trim($iter['mod']);
    if( !($cod0) && ($code_types[$code_type]['fee'] == 1) ){
      $mod0 = $modifier;
      $cod0 = $code;
      $ct0 = $code_type;
    }
    $units     = max(1, intval(trim($iter['units'])));
    $fee       = sprintf('%01.2f',(0 + trim($iter['price'])) * $units);

    if($code_type == 'COPAY'){
      if($id == ''){
        //adding new copay from fee sheet into ar_session and ar_activity tables
        if($fee < 0){
          $fee = $fee * -1;
        }
        $session_id = idSqlStatement("INSERT INTO ar_session(payer_id,user_id,pay_total,payment_type,description,".
          "patient_id,payment_method,adjustment_code,post_to_date) VALUES('0',?,?,'patient','COPAY',?,'','patient_payment',now())",
          array($_SESSION['authId'],$fee,$pid));
        SqlStatement("INSERT INTO ar_activity (pid,encounter,code_type,code,modifier,payer_type,post_time,post_user,session_id,".
          "pay_amount,account_code) VALUES (?,?,?,?,?,0,now(),?,?,?,'PCP')",
          array($pid,$encounter,$ct0,$cod0,$mod0,$_SESSION['authId'],$session_id,$fee));
      }else{
        //editing copay saved to ar_session and ar_activity
        if($fee < 0){
          $fee = $fee * -1;
        }
        $session_id = $id;
        $res_amount = sqlQuery("SELECT pay_amount FROM ar_activity WHERE pid=? AND encounter=? AND session_id=?",
          array($pid,$encounter,$session_id));
        if($fee != $res_amount['pay_amount']){
          sqlStatement("UPDATE ar_session SET user_id=?,pay_total=?,modified_time=now(),post_to_date=now() WHERE session_id=?",
            array($_SESSION['authId'],$fee,$session_id));
          sqlStatement("UPDATE ar_activity SET code_type=?, code=?, modifier=?, post_user=?, post_time=now(),".
            "pay_amount=?, modified_time=now() WHERE pid=? AND encounter=? AND account_code='PCP' AND session_id=?",
            array($ct0,$cod0,$mod0,$_SESSION['authId'],$fee,$pid,$encounter,$session_id));
        }
      }
      if(!$cod0){
        $copay_update = TRUE;
        $update_session_id = $session_id;
      }
      continue;
    }
    $justify   = trim($iter['justify']);
    # Code to create justification for all codes based on first justification
    if ($GLOBALS['replicate_justification']=='1' ) {
      if ($justify !='') {
         $autojustify =  $justify;
      }
    }
    if ( ($GLOBALS['replicate_justification']=='1') && ($justify == '') && check_is_code_type_justify($code_type) ) {
        $justify =  $autojustify;
    }

    $notecodes = trim($iter['notecodes']);
    if ($justify) $justify = str_replace(',', ':', $justify) . ':';
    // $auth      = $iter['auth'] ? "1" : "0";
    $auth      = "1";
    $exclude   = $iter['exclude_from_insurance_billing'] == 1 ? 1 : 0;
    $provid    = 0 + $iter['provid'];

    $ndc_info = '';
    if ($iter['ndcnum']) {
    $ndc_info = 'N4' . trim($iter['ndcnum']) . '   ' . $iter['ndcuom'] .
      trim($iter['ndcqty']);
    }

    // If the item is already in the database...
    if ($id) {
      if ($del) {
        deleteBilling($id);
      }
      else {
        // authorizeBilling($id, $auth);
        sqlQuery("UPDATE billing SET code = ?, " .
          "units = ?, fee = ?, modifier = ?, " .
          "authorized = ?, provider_id = ?, " .
          "ndc_info = ?, justify = ?, notecodes = ?, exclude_from_insurance_billing = ? " .
          "WHERE " .
          "id = ? AND billed = 0 AND activity = 1", array($code,$units,$fee,$modifier,$auth,$provid,$ndc_info,$justify,$notecodes, $exclude, $id) );
      }
    }

    // Otherwise it's a new item...
    else if (! $del) {
      $code_text = lookup_code_descriptions($code_type.":".$code);
      addBilling($encounter, $code_type, $code, $code_text, $pid, $auth,
        $provid, $modifier, $units, $fee, $ndc_info, $justify, 0, $notecodes, $exclude);
    }
  } // end for

  //if modifier is not inserted during loop update the record using the first
  //non-empty modifier and code
  if($copay_update == TRUE && $update_session_id != '' && $mod0 != ''){
    sqlStatement("UPDATE ar_activity SET code_type=?, code=?, modifier=?".
      " WHERE pid=? AND encounter=? AND account_code='PCP' AND session_id=?",
      array($ct0,$cod0,$mod0,$pid,$encounter,$update_session_id));
  }

  // Doing similarly to the above but for products.
  $prod = $_POST['prod'];
  for ($lino = 1; $prod["$lino"]['drug_id']; ++$lino) {
    $iter = $prod["$lino"];

    if (!empty($iter['billed'])) continue;

    $drug_id   = $iter['drug_id'];
    $sale_id   = $iter['sale_id']; // present only if already saved
    $units     = max(1, intval(trim($iter['units'])));
    $fee       = sprintf('%01.2f',(0 + trim($iter['price'])) * $units);
    $del       = $iter['del'];

    // If the item is already in the database...
    if ($sale_id) {
      if ($del) {
        // Zero out this sale and reverse its inventory update.  We bring in
        // drug_sales twice so that the original quantity can be referenced
        // unambiguously.
        sqlStatement("UPDATE drug_sales AS dsr, drug_sales AS ds, " .
          "drug_inventory AS di " .
          "SET di.on_hand = di.on_hand + dsr.quantity, " .
          "ds.quantity = 0, ds.fee = 0 WHERE " .
          "dsr.sale_id = ? AND ds.sale_id = dsr.sale_id AND " .
          "di.inventory_id = ds.inventory_id", array($sale_id) );
        // And delete the sale for good measure.
        sqlStatement("DELETE FROM drug_sales WHERE sale_id = ?", array($sale_id) );
      }
      else {
        // Modify the sale and adjust inventory accordingly.
        $query = "UPDATE drug_sales AS dsr, drug_sales AS ds, " .
          "drug_inventory AS di " .
          "SET di.on_hand = di.on_hand + dsr.quantity - " . add_escape_custom($units) . ", " .
          "ds.quantity = ?, ds.fee = ?, " .
          "ds.sale_date = ? WHERE " .
          "dsr.sale_id = ? AND ds.sale_id = dsr.sale_id AND " .
          "di.inventory_id = ds.inventory_id";
        sqlStatement($query, array($units,$fee,$visit_date,$sale_id) );
      }
    }

    // Otherwise it's a new item...
    else if (! $del) {
      $sale_id = sellDrug($drug_id, $units, $fee, $pid, $encounter, 0,
        $visit_date, '', $default_warehouse);
      if (!$sale_id) die(xlt("Insufficient inventory for product ID") . " \"" . text($drug_id) . "\".");
    }
  } // end for

  // Set the main/default service provider in the new-encounter form.
  /*******************************************************************
  sqlStatement("UPDATE forms, users SET forms.user = users.username WHERE " .
    "forms.pid = '$pid' AND forms.encounter = '$encounter' AND " .
    "forms.formdir = 'patient_encounter' AND users.id = '$provid'");
  *******************************************************************/
  sqlStatement("UPDATE form_encounter SET provider_id = ?, " .
    "supervisor_id = ?, ordering_physician = ?, referring_physician = ?, contract_physician = ? WHERE " .
    "pid = ? AND encounter = ?", array($main_provid,$main_supid,$main_order,$main_referr,$main_contract,$pid,$encounter) );

  // Save-and-Close is currently IPPF-specific but might be more generally
  // useful.  It provides the ability to mark an encounter as billed
  // directly from the Fee Sheet, if there are no charges.
  if ($_POST['bn_save_close']) {
    $tmp1 = sqlQuery("SELECT SUM(ABS(fee)) AS sum FROM drug_sales WHERE " .
      "pid = ? AND encounter = ?", array($pid,$encounter) );
    $tmp2 = sqlQuery("SELECT SUM(ABS(fee)) AS sum FROM billing WHERE " .
      "pid = ? AND encounter = ? AND billed = 0 AND " .
      "activity = 1", array($pid,$encounter) );
    if ($tmp1['sum'] + $tmp2['sum'] == 0) {
      sqlStatement("update drug_sales SET billed = 1 WHERE " .
        "pid = ? AND encounter = ? AND billed = 0", array($pid,$encounter));
      sqlStatement("UPDATE billing SET billed = 1, bill_date = NOW() WHERE " .
        "pid = ? AND encounter = ? AND billed = 0 AND " .
        "activity = 1", array($pid,$encounter));
    }
    else {
      // Would be good to display an error message here... they clicked
      // Save and Close but the close could not be done.  However the
      // framework does not provide an easy way to do that.
    }
  }

  // More IPPF stuff.
  if (!empty($_POST['contrastart'])) {
    $contrastart = $_POST['contrastart'];
    sqlStatement("UPDATE patient_data SET contrastart = ?" .
      " WHERE pid = ?", array($contrastart,$pid) );
  }

  // Note: Taxes are computed at checkout time (in pos_checkout.php which
  // also posts to SL).  Currently taxes with insurance claims make no sense,
  // so for now we'll ignore tax computation in the insurance billing logic.

  if ($_POST['running_as_ajax']) {
    // In the case of running as an AJAX handler, we need to return this same
    // form with an updated checksum to properly support the invoking logic.
    // See review/js/fee_sheet_core.js for that logic.
    $current_checksum = visitChecksum($pid, $encounter, true);
    // Also remove form data for the newly entered lines so they are not
    // duplicated from the database.
    unset($_POST['bill']);
    unset($_POST['prod']);
  }
  else {
    formHeader("Redirecting....");
    formJump();
    formFooter();
    exit;
  }
}

$billresult = getBillingByEncounter($pid, $encounter, "*");
?>
<html>
<head>
<?php
  html_header_show();
  // Include Bootstrap
  call_required_libraries(array("jquery-min-3-1-1","bootstrap"));
?>

<style>
.billcell { font-family: sans-serif; font-size: 10pt }
</style>
<script language="JavaScript">

var diags = new Array();

<?php
if ($billresult) {
  foreach ($billresult as $iter) {
    genDiagJS($iter["code_type"], trim($iter["code"]));
  }
}
if ($_POST['bill']) {
  foreach ($_POST['bill'] as $iter) {
    if ($iter["del"]) continue; // skip if Delete was checked
    if ($iter["id"])  continue; // skip if it came from the database
    genDiagJS($iter["code_type"], $iter["code"]);
  }
}
if ($_POST['newcodes']) {
  $arrcodes = explode('~', $_POST['newcodes']);
  foreach ($arrcodes as $codestring) {
    if ($codestring === '') continue;
    $arrcode = explode('|', $codestring);
    list($code, $modifier) = explode(":", $arrcode[1]);
    genDiagJS($arrcode[0], $code);
  }
}
?>

// This is invoked by <select onchange> for the various dropdowns,
// including search results.
function codeselect(selobj) {
 var i = selobj.selectedIndex;
 if (i > 0) {
  top.restoreSession();
  var f = document.forms[0];
  f.newcodes.value = selobj.options[i].value;
  f.submit();
 }
}

function copayselect() {
 top.restoreSession();
 var f = document.forms[0];
 f.newcodes.value = 'COPAY||';
 f.submit();
}

function validate(f) {
 for (var lino = 1; f['bill['+lino+'][code_type]']; ++lino) {
  var pfx = 'bill['+lino+']';
  if (f[pfx+'[ndcnum]'] && f[pfx+'[ndcnum]'].value) {
   // Check NDC number format.
   var ndcok = true;
   var ndc = f[pfx+'[ndcnum]'].value;
   var a = ndc.split('-');
   if (a.length != 3) {
    ndcok = false;
   }
   else if (a[0].length < 1 || a[1].length < 1 || a[2].length < 1 ||
    a[0].length > 5 || a[1].length > 4 || a[2].length > 2) {
    ndcok = false;
   }
   else {
    for (var i = 0; i < 3; ++i) {
     for (var j = 0; j < a[i].length; ++j) {
      var c = a[i].charAt(j);
      if (c < '0' || c > '9') ndcok = false;
     }
    }
   }
   if (!ndcok) {
    alert('<?php echo addslashes(xl('Format incorrect for NDC')) ?> "' + ndc +
     '", <?php echo addslashes(xl('should be like nnnnn-nnnn-nn')) ?>');
    if (f[pfx+'[ndcnum]'].focus) f[pfx+'[ndcnum]'].focus();
    return false;
   }
   // Check for valid quantity.
   var qty = f[pfx+'[ndcqty]'].value - 0;
   if (isNaN(qty) || qty <= 0) {
    alert('<?php echo addslashes(xl('Quantity for NDC')) ?> "' + ndc +
     '" <?php echo addslashes(xl('is not valid (decimal fractions are OK).')) ?>');
    if (f[pfx+'[ndcqty]'].focus) f[pfx+'[ndcqty]'].focus();
    return false;
   }
  }
 }
 top.restoreSession();
 return true;
}

// When a justify selection is made, apply it to the current list for
// this procedure and then rebuild its selection list.
//
function setJustify(seljust) {
 var theopts = seljust.options;
 var jdisplay = theopts[0].text;
 // Compute revised justification string.  Note this does nothing if
 // the first entry is still selected, which is handy at startup.
 if (seljust.selectedIndex > 0) {
  var newdiag = seljust.value;
  if (newdiag.length == 0) {
   jdisplay = '';
  }
  else {
   if (jdisplay.length) jdisplay += ',';
   jdisplay += newdiag;
  }
 }
 // Rebuild selection list.
 var jhaystack = ',' + jdisplay + ',';
 var j = 0;
 theopts.length = 0;
 theopts[j++] = new Option(jdisplay,jdisplay,true,true);
 for (var i = 0; i < diags.length; ++i) {
  if (jhaystack.indexOf(',' + diags[i] + ',') < 0) {
   theopts[j++] = new Option(diags[i],diags[i],false,false);
  }
 }
 theopts[j++] = new Option('Clear','',false,false);
}
// Open the add-event dialog.
function newEvt() {
 var f = document.forms[0];
 var url = '<?php echo $GLOBALS["web_root"]; ?>/modules/calendar/add_edit_event.php?patientid=<?php echo attr($pid); ?>';
 if (f.ProviderID && f.ProviderID.value) {
  url += '&userid=' + parseInt(f.ProviderID.value);
 }
 dlgopen(url, '_blank', 600, 300);
 return false;
}

</script>
</head>

<body class="body_top">
<form method="post" action="<?php echo $rootdir; ?>/forms/fee_sheet/new.php"
 onsubmit="return validate(this)">
<span class="title"><?php echo xlt('Fee Sheet'); ?></span><br>
<input type='hidden' name='newcodes' value=''>

<center>

<?php
$isBilled = isEncounterBilled($pid, $encounter);
if ($isBilled) {
  echo "<p><font color='green'>" . xlt("This encounter has been billed. If you need to change it, it must be re-opened.") . "</font></p>\n";
}
else { // the encounter is not yet billed
?>

<table width='95%'>
<?php
$i = 0;
$last_category = '';

// Create drop-lists based on the fee_sheet_options table.
$res = sqlStatement("SELECT * FROM fee_sheet_options " .
  "ORDER BY fs_category, fs_option");
while ($row = sqlFetchArray($res)) {
  $fs_category = $row['fs_category'];
  $fs_option   = $row['fs_option'];
  $fs_codes    = $row['fs_codes'];
  if($fs_category !== $last_category) {
    endFSCategory();
    $last_category = $fs_category;
    ++$i;
    echo ($i <= 1) ? " <tr>\n" : "";
    echo "  <td width='50%' align='center' nowrap>\n";
    echo "   <select style='width:96%' onchange='codeselect(this)'>\n";
    echo "    <option value=''> " . text(substr($fs_category, 1)) . "</option>\n";
  }
  echo "    <option value='" . attr($fs_codes) . "'>" . text(substr($fs_option, 1)) . "</option>\n";
}
endFSCategory();

// Create drop-lists based on categories defined within the codes.
$pres = sqlStatement("SELECT option_id, title FROM list_options " .
  "WHERE list_id = 'superbill' ORDER BY seq");
while ($prow = sqlFetchArray($pres)) {
  global $code_types;
  ++$i;
  echo ($i <= 1) ? " <tr>\n" : "";
  echo "  <td width='50%' align='center' nowrap>\n";
  echo "   <select style='width:96%' onchange='codeselect(this)'>\n";
  echo "    <option value=''> " . text($prow['title']) . "\n";
  $res = sqlStatement("SELECT code_type, code, code_text,modifier FROM codes " .
    "WHERE superbill = ? AND active = 1 " .
    "ORDER BY code_text", array($prow['option_id']) );
  while ($row = sqlFetchArray($res)) {
    $ctkey = alphaCodeType($row['code_type']);
    if ($code_types[$ctkey]['nofs']) continue;
    echo "    <option value='" . attr($ctkey) . "|" .
      attr($row['code']) . ':'. attr($row['modifier']) . "|'>" . text($row['code_text']) . "</option>\n";
  }
  echo "   </select>\n";
  echo "  </td>\n";
  if ($i >= $FEE_SHEET_COLUMNS) {
    echo " </tr>\n";
    $i = 0;
  }
}

// Create one more drop-list, for Products.
if ($GLOBALS['sell_non_drug_products']) {
  ++$i;
  echo ($i <= 1) ? " <tr>\n" : "";
  echo "  <td width='50%' align='center' nowrap>\n";
  echo "   <select name='Products' style='width:96%' onchange='codeselect(this)'>\n";
  echo "    <option value=''> " . xlt('Products') . "\n";
  $tres = sqlStatement("SELECT dt.drug_id, dt.selector, d.name " .
    "FROM drug_templates AS dt, drugs AS d WHERE " .
    "d.drug_id = dt.drug_id AND d.active = 1 " .
    "ORDER BY d.name, dt.selector, dt.drug_id");
  while ($trow = sqlFetchArray($tres)) {
    echo "    <option value='PROD|" . attr($trow['drug_id']) . '|' . attr($trow['selector']) . "'>" .
      text($trow['drug_id']) . ':' . text($trow['selector']);
    if ($trow['name'] !== $trow['selector']) echo ' ' . text($trow['name']);
    echo "</option>\n";
  }
  echo "   </select>\n";
  echo "  </td>\n";
  if ($i >= $FEE_SHEET_COLUMNS) {
    echo " </tr>\n";
    $i = 0;
  }
}

$search_type = $default_search_type;
if ($_POST['search_type']) $search_type = $_POST['search_type'];

$ndc_applies = true; // Assume all payers require NDC info.

echo $i ? "  <td></td>\n </tr>\n" : "";
echo " <tr>\n";
echo "  <td colspan='" . attr($FEE_SHEET_COLUMNS) . "' align='center' nowrap>\n";

// If Search was clicked, do it and write the list of results here.
// There's no limit on the number of results!
//
$numrows = 0;
if ($_POST['bn_search'] && $_POST['search_term']) {
  $res = main_code_set_search($search_type,$_POST['search_term']);
  if (!empty($res)) {
    $numrows = sqlNumRows($res);
  }
}

echo "   <select name='Search Results' style='width:98%' " .
  "onchange='codeselect(this)'";
if (! $numrows) echo ' disabled';
echo ">\n";
echo "    <option value=''> " . xlt("Search Results") . " ($numrows " . xlt("items") . ")\n";

if ($numrows) {
  while ($row = sqlFetchArray($res)) {
    $code = $row['code'];
    if ($row['modifier']) $code .= ":" . $row['modifier'];
    echo "    <option value='" . attr($search_type) . "|" . attr($code) . "|'>" . text($code) . " " .
      text($row['code_text']) . "</option>\n";
  }
}

echo "   </select>\n";
echo "  </td>\n";
echo " </tr>\n";
?>

</table>

<p style='margin-top:8px;margin-bottom:8px'>
<table>
 <tr>
  <td>
   <input type='button' class="cp-positive" value='<?php echo xla('Add Copay');?>'
    onclick="copayselect()" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
  </td>
  <td>
   <?php echo xlt('Search'); ?>&nbsp;
  </td>
  <td>
<?php
  $nofs_code_types = array();
  foreach ($code_types as $key => $value) {
    if (!empty($value['nofs'])) continue;
    $nofs_code_types[$key] = $value;
  }
  $size_select = (count($nofs_code_types) < 5) ? count($nofs_code_types) : 5;
?>
  <select name='search_type' size='<?php echo attr($size_select) ?>'>
<?php
  foreach ($nofs_code_types as $key => $value) {
    echo "   <option value='" . attr($key) . "'";
    if ($key == $search_type) echo " selected";
    echo " />" . xlt($value['label']) . "</option>";
  }
?>
  </select>
  </td>
  <td>
   <?php echo xlt('for'); ?>&nbsp;
  </td>
  <td>
   <input type='text' name='search_term' value=''> &nbsp;
  </td>
  <td>
   <input type='submit' class="cp-submit" name='bn_search' value='<?php echo xla('Search');?>'>
  </td>
 </tr>
</table>
</p>

<p style='margin-top:16px;margin-bottom:8px'>

<?php } // end encounter not billed ?>

<table cellspacing='5'>
 <tr>
  <td class='billcell'><b><?php echo xlt('Type');?></b></td>
  <td class='billcell'><b><?php echo xlt('Code');?></b></td>
<?php if (modifiers_are_used(true)) { ?>
  <td class='billcell'><b><?php echo xlt('Modifiers');?></b></td>
<?php } ?>
<?php if (fees_are_used()) { ?>
  <td class='billcell' align='right'><b><?php echo xlt('Price');?></b>&nbsp;</td>
  <td class='billcell' align='center'><b><?php echo xlt('Units');?></b></td>
<?php } ?>
<?php if (justifiers_are_used()) { ?>
  <td class='billcell' align='center'<?php echo $usbillstyle; ?>><b><?php echo xlt('Justify');?></b></td>
<?php } ?>

  <?php // Show provider (only if using line item billing) ?>
  <?php if($GLOBALS['support_fee_sheet_line_item_provider'] ==1) { ?>
    <td class='billcell' align='center'>
  <?php } else { ?>
    <td class='billcell' align='center' style='display: none'>
  <?php } ?>
  <b><?php echo xlt('Provider');?></b></td>

  <td class='billcell' align='center'<?php echo $usbillstyle; ?>><b><?php echo xlt('Note Codes');?></b></td>
  <td class='billcell' align='center'<?php echo $usbillstyle; ?>><b><?php echo xlt('Auth');?></b></td>
  <td class='billcell' align='center'><b><?php echo xlt('Delete');?></b></td>
  <?php if($GLOBALS['bill_to_patient'] ==1) { ?>
    <td class='billcell' align='center'>
  <?php } else { ?>
    <td class='billcell' align='center' style='display: none'>
  <?php } ?>
  <b><?php echo xlt('Exclude from Billing');?></b></td>
  <td class='billcell'><b><?php echo xlt('Description');?></b></td>
 </tr>

<?php
$justinit = "var f = document.forms[0];\n";

// $encounter_provid = -1;

$hasCharges = false;

// Generate lines for items already in the billing table for this encounter,
// and also set the rendering provider if we come across one.
//
$bill_lino = 0;
if ($billresult) {
  foreach ($billresult as $iter) {
    ++$bill_lino;
    $bline = $_POST['bill']["$bill_lino"];
    $del = $bline['del']; // preserve Delete if checked

    $modifier   = trim($iter["modifier"]);
    $units      = $iter["units"];
    $fee        = $iter["fee"];
    $authorized = $iter["authorized"];
    $exclude    = $iter["exclude_from_insurance_billing"];
    $ndc_info   = $iter["ndc_info"];
    $justify    = trim($iter['justify']);
    $notecodes  = trim($iter['notecodes']);
    if ($justify) $justify = substr(str_replace(':', ',', $justify), 0, strlen($justify) - 1);
    $provider_id = $iter['provider_id'];

    // Also preserve other items from the form, if present.
    if ($bline['id'] && !$iter["billed"]) {
      $modifier   = trim($bline['mod']);
      $units      = max(1, intval(trim($bline['units'])));
      $fee        = sprintf('%01.2f',(0 + trim($bline['price'])) * $units);
      $authorized = $bline['auth'];
      $exclude    = $bline['exclude_from_insurance_billing'];
      $ndc_info   = '';
      if ($bline['ndcnum']) {
        $ndc_info = 'N4' . trim($bline['ndcnum']) . '   ' . $bline['ndcuom'] .
        trim($bline['ndcqty']);
      }
      $justify    = $bline['justify'];
      $notecodes  = trim($bline['notecodes']);
      $provider_id = 0 + $bline['provid'];
    }

    if($iter['code_type'] == 'COPAY'){//moved copay display to below
      --$bill_lino;
      continue;
    }

    // list($code, $modifier) = explode("-", $iter["code"]);
    echoLine($bill_lino, $iter["code_type"], trim($iter["code"]),
      $modifier, $ndc_info,  $authorized,
      $del, $units, $fee, $iter["id"], $iter["billed"],
      $iter["code_text"], $justify, $provider_id, $notecodes, $exclude);
  }
}

$resMoneyGot = sqlStatement("SELECT pay_amount as PatientPay,session_id as id,date(post_time) as date ".
  "FROM ar_activity where pid =? and encounter =? and payer_type=0 and account_code='PCP'",
  array($pid,$encounter));//new fees screen copay gives account_code='PCP'
while($rowMoneyGot = sqlFetchArray($resMoneyGot)){
  $PatientPay=$rowMoneyGot['PatientPay']*-1;
  $id=$rowMoneyGot['id'];
  echoLine(++$bill_lino,'COPAY','','',$rowMoneyGot['date'],'1','','',$PatientPay,$id);
}

// Echo new billing items from this form here, but omit any line
// whose Delete checkbox is checked.
//
if ($_POST['bill']) {
  foreach ($_POST['bill'] as $key => $iter) {
    if ($iter["id"])  continue; // skip if it came from the database
    if ($iter["del"]) continue; // skip if Delete was checked
    $ndc_info = '';
    if ($iter['ndcnum']) {
      $ndc_info = 'N4' . trim($iter['ndcnum']) . '   ' . $iter['ndcuom'] .
      trim($iter['ndcqty']);
    }
    // $fee = 0 + trim($iter['fee']);
    $units = max(1, intval(trim($iter['units'])));
    $fee = sprintf('%01.2f',(0 + trim($iter['price'])) * $units);
    //the date is passed as $ndc_info, since this variable is not applicable in the case of copay.
    $ndc_info = '';
    if ($iter['code_type'] == 'COPAY'){
      $ndc_info = date("Y-m-d");
      if($fee > 0)
      $fee = 0 - $fee;
    }
    echoLine(++$bill_lino, $iter["code_type"], $iter["code"], trim($iter["mod"]),
      $ndc_info, $iter["auth"], $iter["del"], $units,
      $fee, NULL, FALSE, NULL, $iter["justify"], 0 + $iter['provid'],
      $iter['notecodes']);
  }
}

// Generate lines for items already in the drug_sales table for this encounter.
//
$query = "SELECT * FROM drug_sales WHERE " .
  "pid = ? AND encounter = ? " .
  "ORDER BY sale_id";
$sres = sqlStatement($query, array($pid,$encounter) );
$prod_lino = 0;
while ($srow = sqlFetchArray($sres)) {
  ++$prod_lino;
  $pline = $_POST['prod']["$prod_lino"];
  $del   = $pline['del']; // preserve Delete if checked
  $sale_id = $srow['sale_id'];
  $drug_id = $srow['drug_id'];
  $units   = $srow['quantity'];
  $fee     = $srow['fee'];
  $billed  = $srow['billed'];
  // Also preserve other items from the form, if present and unbilled.
  if ($pline['sale_id'] && !$srow['billed']) {
    // $units      = trim($pline['units']);
    // $fee        = trim($pline['fee']);
    $units = max(1, intval(trim($pline['units'])));
    $fee   = sprintf('%01.2f',(0 + trim($pline['price'])) * $units);
  }
  echoProdLine($prod_lino, $drug_id, $del, $units, $fee, $sale_id, $billed);
}

// Echo new product items from this form here, but omit any line
// whose Delete checkbox is checked.
//
if ($_POST['prod']) {
  foreach ($_POST['prod'] as $key => $iter) {
    if ($iter["sale_id"])  continue; // skip if it came from the database
    if ($iter["del"]) continue; // skip if Delete was checked
    // $fee = 0 + trim($iter['fee']);
    $units = max(1, intval(trim($iter['units'])));
    $fee   = sprintf('%01.2f',(0 + trim($iter['price'])) * $units);
    echoProdLine(++$prod_lino, $iter['drug_id'], FALSE, $units, $fee);
  }
}

// If new billing code(s) were <select>ed, add their line(s) here.
//
if ($_POST['newcodes']) {
  $arrcodes = explode('~', $_POST['newcodes']);
  foreach ($arrcodes as $codestring) {
    if ($codestring === '') continue;
    $arrcode = explode('|', $codestring);
    $newtype = $arrcode[0];
    $newcode = $arrcode[1];
    $newsel  = $arrcode[2];
    if ($newtype == 'COPAY') {
      $tmp = sqlQuery("SELECT copay FROM insurance_data WHERE pid = ? " .
        "AND type = 'primary' ORDER BY date DESC LIMIT 1", array($pid) );
      $code = sprintf('%01.2f', 0 + $tmp['copay']);
      echoLine(++$bill_lino, $newtype, $code, '', date("Y-m-d"), '1', '0', '1',
        sprintf('%01.2f', 0 - $code));
    }
    else if ($newtype == 'PROD') {
      $result = sqlQuery("SELECT * FROM drug_templates WHERE " .
        "drug_id = ? AND selector = ?", array($newcode,$newsel) );
      $units = max(1, intval($result['quantity']));
      $prrow = sqlQuery("SELECT prices.pr_price " .
        "FROM patient_data, prices WHERE " .
        "patient_data.pid = ? AND " .
        "prices.pr_id = ? AND " .
        "prices.pr_selector = ? AND " .
        "prices.pr_level = patient_data.pricelevel " .
        "LIMIT 1", array($pid,$newcode,$newsel) );
      $fee = empty($prrow) ? 0 : $prrow['pr_price'];
      echoProdLine(++$prod_lino, $newcode, FALSE, $units, $fee);
    }
    else {
      list($code, $modifier) = explode(":", $newcode);
      $ndc_info = '';
      // If HCPCS, find last NDC string used for this code.
      if ($newtype == 'HCPCS' && $ndc_applies) {
        $tmp = sqlQuery("SELECT ndc_info FROM billing WHERE " .
          "code_type = ? AND code = ? AND ndc_info LIKE 'N4%' " .
          "ORDER BY date DESC LIMIT 1", array($newtype,$code) );
        if (!empty($tmp)) $ndc_info = $tmp['ndc_info'];
      }
      echoLine(++$bill_lino, $newtype, $code, trim($modifier), $ndc_info);
    }
  }
}

$tmp = sqlQuery("SELECT provider_id, supervisor_id, ordering_physician, referring_physician, contract_physician FROM form_encounter " .
  "WHERE pid = ? AND encounter = ? " .
  "ORDER BY id DESC LIMIT 1", array($pid,$encounter) );
$encounter_provid = 0 + findProvider();
$encounter_supid  = 0 + $tmp['supervisor_id'];
$encounter_order  = 0 + $tmp['ordering_physician'];
$encounter_referr  = 0 + $tmp['referring_physician'];
$encounter_contract  = 0 + $tmp['contract_physician'];
?>
</table>
</p>

<br />
&nbsp;

<?php
// Choose rendering and supervising providers.
echo "<span class='billcell'><b>\n";
echo xlt('Providers') . ": &nbsp;";

echo "&nbsp;&nbsp;" . xlt('Rendering') . "\n";
genProviderSelect('ProviderID', '-- '.xl("Please Select").' --', $encounter_provid, $isBilled);

if ($GLOBALS['supervising_physician_in_feesheet']) {
  echo "&nbsp;&nbsp;" . xlt('Supervising') . "\n";
  genProviderSelect('SupervisorID', '-- '.xl("N/A").' --', $encounter_supid, $isBilled);
}

if ($GLOBALS['ordering_physician_in_feesheet']) {
  echo "&nbsp;&nbsp;" . xlt('Ordering') . "\n";
  genProviderSelect('OrderingID', '-- '.xl("N/A").' --', $encounter_order, $isBilled, true);
}
if ($GLOBALS['ordering_physician_in_feesheet'] || $GLOBALS['supervising_physician_in_feesheet'] && ($GLOBALS['referring_physician_in_feesheet'] || $GLOBALS['contract_physician_in_feesheet'])) {
 echo "<br></br>";
}
if ($GLOBALS['referring_physician_in_feesheet']) {
  echo "&nbsp;&nbsp;" . xlt('Referring') . "\n";
  genProviderSelect('ReferringID', '-- '.xl("N/A").' --', $encounter_referr, $isBilled, true);
}

if ($GLOBALS['contract_physician_in_feesheet']) {
  echo "&nbsp;&nbsp;" . xlt($GLOBALS['contract_physician_in_feesheet_name']) . "\n";
  if (trim($GLOBALS['contract_physician_in_feesheet_name']) == 'Users') {
      genUserSelect('ContractID', '-- '.xl("N/A").' --', $encounter_contract, $isBilled, true);
  }else{
  genProviderSelect('ContractID', '-- '.xl("N/A").' --', $encounter_contract, $isBilled, true);
}
}

if ($GLOBALS['allow_appointments_in_feesheet']) {
  echo "<input type='button' class='cp-misc' value='" . xla('New Appointment') . "' onclick='newEvt()' />\n";
}

echo "</b></span>\n";
?>

<p>
&nbsp;

<?php
// If applicable, ask for the contraceptive services start date.
$trow = sqlQuery("SELECT count(*) AS count FROM layout_options WHERE " .
  "form_id = 'DEM' AND field_id = 'contrastart' AND uor > 0");
if ($trow['count'] && $contraception && !$isBilled) {
  $date1 = substr($visit_row['date'], 0, 10);
  // If admission or surgical, then force contrastart.
  if ($contraception > 1 ||
    strpos(strtolower($visit_row['pc_catname']), 'admission') !== false)
  {
    echo "   <input type='hidden' name='contrastart' value='" . attr($date1) . "' />\n";
  }
  else {
    // echo "<!-- contraception = $contraception -->\n"; // debugging
    $trow = sqlQuery("SELECT contrastart " .
      "FROM patient_data WHERE " .
      "pid = ? LIMIT 1", array($pid) );
    if (empty($trow['contrastart']) || substr($trow['contrastart'], 0, 4) == '0000') {
      $date0 = date('Y-m-d', strtotime($date1) - (60 * 60 * 24));
      echo "   <select name='contrastart'>\n";
      echo "    <option value='" . attr($date1) . "'>" . xlt('This visit begins new contraceptive use') . "</option>\n";
      echo "    <option value='" . attr($date0) . "'>" . xlt('Contraceptive services previously started') . "</option>\n";
      echo "    <option value=''>" . xlt('None of the above') . "</option>\n";
      echo "   </select>\n";
      echo "&nbsp; &nbsp; &nbsp;\n";
    }
  }
}

// If there is a choice of warehouses, allow override of user default.
if ($prod_lino > 0) { // if any products are in this form
  $trow = sqlQuery("SELECT count(*) AS count FROM list_options WHERE list_id = 'warehouse'");
  if ($trow['count'] > 1) {
    $trow = sqlQuery("SELECT default_warehouse FROM users WHERE username = ?", array($_SESSION['authUser']) );
    echo "   <span class='billcell'><b>" . xlt('Warehouse') . ":</b></span>\n";
    echo generate_select_list('default_warehouse', 'warehouse',
      $trow['default_warehouse'], '');
    echo "&nbsp; &nbsp; &nbsp;\n";
  }
}

// Allow the patient price level to be fixed here.
$plres = sqlStatement("SELECT option_id, title FROM list_options " .
  "WHERE list_id = 'pricelevel' ORDER BY seq");
if (true) {
  $trow = sqlQuery("SELECT pricelevel FROM patient_data WHERE " .
    "pid = ? LIMIT 1", array($pid) );
  $pricelevel = $trow['pricelevel'];
  echo "   <span class='billcell'><b>" . xlt('Price Level') . ":</b></span>\n";
  echo "   <select class='form-control' style='width: auto; display: inline-block;' name='pricelevel'";
  if ($isBilled) echo " disabled";
  echo ">\n";
  while ($plrow = sqlFetchArray($plres)) {
    $key = $plrow['option_id'];
    $val = $plrow['title'];
    echo "    <option value='" . attr($key) . "'";
    if ($key == $pricelevel) echo ' selected';
    echo ">" . text($val) . "</option>\n";
  }
  echo "   </select>\n";
}
?>
</p>

&nbsp; &nbsp; &nbsp;

<?php if (!$isBilled) { ?>
<input type='submit' class="cp-submit" name='bn_save' value='<?php echo xla('Save');?>' />
&nbsp;
<?php if (!$hasCharges) { ?>
<input type='submit' class="cp-misc" name='bn_save_close' value='<?php echo xla('Mark as Billed');?>' />
&nbsp;
<?php } ?>
<input type='submit' class="cp-misc" name='bn_refresh' value='<?php echo xla('Refresh');?>'>
&nbsp;
<?php } ?>

<input type='hidden' name='form_checksum' value='<?php echo $current_checksum; ?>' />
<input type='hidden' name='form_alertmsg' value='<?php echo attr($alertmsg); ?>' />

<!-- Class='deleter' makes button -->
<input type='button' class='deleter cp-negative' value='<?php echo xla('Cancel');?>'
 onclick="top.restoreSession();location='<?php echo "$rootdir/patient_file/encounter/$returnurl" ?>'" />


</center>

</form>
<script language='JavaScript'>
<?php
echo $justinit;
if ($alertmsg) {
  echo "alert('" . addslashes($alertmsg) . "');\n";
}
?>
</script>
</body>
</html>
<?php require_once("review/initialize_review.php"); ?>
