<?php
/*
 *  billing inc
 *
 *  billing.inc contains function for billing functions
 *
 *  The changes to this file as of November 16 2016 to include the exclusion of information from claims
 *  are covered under the terms of the Mozilla Public License, v. 2.0
 *
 * @copyright Copyright (C) 2016-2017 Terry Hill <teryhill@librehealth.io>
 *
 * No previous copyright in header. No header present. This is an original OpenEMR program.
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
 * LICENSE: This Source Code is subject to the terms of the Mozilla Public License, v. 2.0.
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreEHR
 * @author Terry Hill <teryhill@librehealth.io>
 * no other authors were present as there was no header file.
 * @link http://www.libreehr.org
 *
 * Please help the overall project by sending changes you make to the author and to the LibreEHR community.
 *
 */
// require_once("{$GLOBALS['srcdir']}/sql.inc");
require_once(dirname(__FILE__) . "/sql.inc");

function getBillingById ($id, $cols = "*")
{
    return sqlQuery("select $cols from billing where id='$id' and activity=1 order by date DESC limit 0,1");
}

function getBillingByPid ($pid, $cols = "*")
{
    return  sqlQuery("select $cols from billing where pid ='$pid' and activity=1 order by date DESC limit 0,1");
}

function getBillingByEncounter ($pid,$encounter, $cols = "code_type, code, code_text")
{
    $res = sqlStatement("select $cols from billing where encounter = ? and pid=? and activity=1 order by code_type, date ASC", array($encounter,$pid) );

    $all=array();
    for($iter=0; $row=sqlFetchArray($res); $iter++)
    {
        $all[$iter] = $row;
    }
    return $all;
}

function addBilling($encounter_id, $code_type, $code, $code_text, $pid,
  $authorized="0", $provider, $modifier="", $units="", $fee="0.00",
  $ndc_info='', $justify='', $billed=0, $notecodes='', $exclude = '0')
{
  $sql = "insert into billing (date, encounter, code_type, code, code_text, " .
    "pid, authorized, user, groupname, activity, billed, provider_id, " .
    "modifier, units, fee, ndc_info, justify, notecodes, exclude_from_insurance_billing) values (" .
    "NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  return sqlInsert($sql, array($encounter_id,$code_type,$code,$code_text,$pid,$authorized,$_SESSION['authId'],$_SESSION['authProvider'],$billed,$provider,$modifier,$units,$fee,$ndc_info,$justify,$notecodes, $exclude));
}

function authorizeBilling($id, $authorized = "1")
{
    sqlQuery("update billing set authorized = '$authorized' where id = '$id'");
}

function deleteBilling($id)
{
    sqlStatement("update billing set activity = 0 where id = ?", array($id));
}

function clearBilling($id)
{
    sqlStatement("update billing set justify = '' where id = ?", array($id));
}

// This function supports the Billing page (billing_process.php and initiation of secondary processing
// (sl_eob.inc.php).  It is called in the following situations:
//

// * billing_process.php sets claim status to 2, and payer, on marking a
//   claim as billed without actually generating any billing.  Create a
//   claims row.  In this case bill_process will remain at 0 and process_time
//   and process_file will not be set.
// * billing_process.php sets bill_process, payer, target and x12 partner
//   before calling gen_x12_837.  Create a claims row.
// * billing_process.php sets claim status to 2 (billed), bill_process to 2,
//   process_time and process_file after calling gen_x12_837.  Claims row
//   already exists.
// * process_bills.php sets bill_process to 2, process_time.  Claims row already exists.
// * EOB posting updates claim status to mark a payer as done.  Claims row
//   already exists.
// * EOB posting reopens an encounter for billing a secondary payer.  Create
//   a claims row.
//
// $newversion should be passed to us to indicate if a new claims row
// is to be generated, otherwise one must already exist.  The payer, if
// passed in for the latter case, must match the existing claim.
//
// Currently on the billing page the user can select any of the patient's
// payers.  That logic will tailor the payer choices to the encounter date.
//
function updateClaim($newversion, $patient_id, $encounter_id, $payer_id=-1, $payer_type=-1,
  $status=-1, $bill_process=-1, $process_file='', $target='', $partner_id=-1,$crossover=0)
{
  if (!$newversion) {
    $sql = "SELECT * FROM claims WHERE patient_id = '$patient_id' AND " .
      "encounter_id = '$encounter_id' AND status > 0 AND status < 4 ";
    if ($payer_id >= 0) $sql .= "AND payer_id = '$payer_id' ";
    $sql .= "ORDER BY version DESC LIMIT 1";
    $row = sqlQuery($sql);
    if (!$row) return 0;
    if ($payer_id     < 0) $payer_id     = $row['payer_id'];
    if ($status       < 0) $status       = $row['status'];
    if ($bill_process < 0) $bill_process = $row['bill_process'];
    if ($partner_id   < 0) $partner_id   = $row['x12_partner_id'];
    if (!$process_file   ) $process_file = $row['process_file'];
    if (!$target         ) $target       = $row['target'];
  }

  $claimset = "";
  $billset = "";
  if (empty($payer_id) || $payer_id < 0) $payer_id = 0;

  if ($status==7) {//$status==7 is the claim denial case.
    $claimset .= ", status = '$status'";
  }
  elseif ($status >= 0) {
    $claimset .= ", status = '$status'";
    if ($status > 1) {
      $billset .= ", billed = 1";
      if ($status == 2) $billset  .= ", bill_date = NOW()";
    } else {
      $billset .= ", billed = 0";
    }
  }
  if ($status==7) {//$status==7 is the claim denial case.
    $billset  .= ", bill_process = '$status'";
  }
  elseif ($bill_process >= 0) {
    $claimset .= ", bill_process = '$bill_process'";
    $billset  .= ", bill_process = '$bill_process'";
  }
  if ($status==7) {//$status==7 is the claim denial case.
    $claimset  .= ", process_file = '$process_file'";//Denial reason code is stored here
  }
  elseif ($process_file) {
    $claimset .= ", process_file = '$process_file', process_time = NOW()";
    $billset  .= ", process_file = '$process_file', process_date = NOW()";
  }
  if ($target) {
    $claimset .= ", target = '$target'";
    $billset  .= ", target = '$target'";
  }
  if ($payer_id >= 0) {
    $claimset .= ", payer_id = '$payer_id', payer_type = '$payer_type'";
    $billset  .= ", payer_id = '$payer_id'";
  }
  if ($partner_id >= 0) {
    $claimset .= ", x12_partner_id = '$partner_id'";
    $billset  .= ", x12_partner_id = '$partner_id'";
  }

  if ($billset) {
    $billset = substr($billset, 2);
    sqlStatement("UPDATE billing SET ? WHERE " .
      "encounter = ? AND pid=? AND activity = 1", array($billset, $encounter_id, $patient_id));
  }

  // If a new claim version is requested, insert its row.
  //
  if ($newversion) {
    /****
    $payer_id = ($payer_id < 0) ? $row['payer_id'] : $payer_id;
    $bill_process = ($bill_process < 0) ? $row['bill_process'] : $bill_process;
    $process_file = ($process_file) ? $row['process_file'] : $process_file;
    $target = ($target) ? $row['target'] : $target;
    $partner_id = ($partner_id < 0) ? $row['x12_partner_id'] : $partner_id;
    $sql = "INSERT INTO claims SET " .
      "patient_id = '$patient_id', " .
      "encounter_id = '$encounter_id', " .
      "bill_time = UNIX_TIMESTAMP(NOW()), " .
      "payer_id = '$payer_id', " .
      "status = '$status', " .
      "payer_type = '" . $row['payer_type'] . "', " .
      "bill_process = '$bill_process', " .
      "process_time = '" . $row['process_time'] . "', " .
      "process_file = '$process_file', " .
      "target = '$target', " .
      "x12_partner_id = '$partner_id'";
    ****/
    sqlBeginTrans();
    $version = sqlQuery( "SELECT IFNULL(MAX(version),0) + 1 AS increment FROM claims WHERE patient_id = ? AND encounter_id = ?", array( $patient_id, $encounter_id ) );
    if($crossover<>1)
    {
      $sqlBindArray = array();
      $sql .= "INSERT INTO claims SET " .
          "patient_id = ?, " .
          "encounter_id = ?, " .
          "bill_time = NOW() ? ," .
          "version = ?";

      array_push($sqlBindArray, $patient_id, $encounter_id, $claimset, $version['increment']);
      }
     else
     {//Claim automatic forward case.
      $sqlBindArray = array();
     $sql = "INSERT INTO claims SET " .
          "patient_id = ?, " .
          "encounter_id = ?, " .
          "bill_time = NOW(), status=? ," .
          "version = ?";

      array_push($sqlBindArray, $patient_id, $encounter_id, $status, $version['increment']); 
     }
    
    sqlStatement($sql, $sqlBindArray);
    sqlCommitTrans();
  }

  // Otherwise update the existing claim row.
  //
  else if ($claimset) {
    $claimset = substr($claimset, 2);
    sqlStatement("UPDATE claims SET ? WHERE " .
      "patient_id = ? AND encounter_id = ? AND " .
      // "payer_id = '" . $row['payer_id'] . "' AND " .
      "version = ?", array($claimset, $patient_id, $encounter_id, $row['version']));
  }

  // Whenever a claim is marked billed, update A/R accordingly.
  //
  if ($status == 2) {
      if ($payer_type > 0) {
        sqlStatement("UPDATE form_encounter SET " .
          "last_level_billed = ? WHERE " .
          "pid = ? AND encounter = ?", array($payer_type, $patient_id, $encounter_id));
      }
  }

  return 1;
}

// Determine if anything in a visit has been billed.
//
function isEncounterBilled($pid, $encounter) {
  $row = sqlQuery("SELECT count(*) AS count FROM billing WHERE " .
    "pid = ? AND encounter = ? AND activity = 1 AND " .
    "billed = 1", array($pid, $encounter_id));
  $count = $row['count'];
  if (!$count) {
    $row = sqlQuery("SELECT count(*) AS count FROM drug_sales WHERE " .
      "pid = ? AND encounter = ? AND billed = 1", array($pid, $encounter_id));
    $count = $row['count'];
  }
  return $count ? true : false;
}

// Get the co-pay amount that is effective on the given date.
// Or if no insurance on that date, return -1.
//
function getCopay($patient_id, $encdate) {
 $tmp = sqlQuery("SELECT provider, copay FROM insurance_data " .
   "WHERE pid = ? AND type = 'primary' " .
   "AND date <= ? ORDER BY date DESC LIMIT 1", array($patient_id, $encdate));
 if ($tmp['provider']) return sprintf('%01.2f', 0 + $tmp['copay']);
 return 0;
}

// Get the total co-pay amount paid by the patient for an encounter
function getPatientCopay($patient_id, $encounter) {
        $resMoneyGot = sqlStatement("SELECT sum(pay_amount) as PatientPay FROM ar_activity where ".
          "pid = ? and encounter = ? and payer_type=0 and account_code='PCP'",
          array($patient_id,$encounter));
         //new fees screen copay gives account_code='PCP'
        $rowMoneyGot = sqlFetchArray($resMoneyGot);
        $Copay=$rowMoneyGot['PatientPay'];
        return $Copay*-1;
}
?>
