<?php
/*
 * These functions are common functions used in Collection report.
 * They have been pulled out and placed in this file. This is done to prepare
 * the for building a report generator.
 *
 * Copyright (C) 2018 Tigpezeghe Rodrige <tigrodrige@gmail.com>
 *
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreHealth EHR
 * @author Tigpezeghe Rodrige <tigrodrige@gmail.com>
 * @link http://librehealth.io
 *
 * Please help the overall project by sending changes you make to the author and to the LibreHealth EHR community.
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;

require_once("../globals.php");
require_once("../../library/patient.inc");
require_once("../../library/invoice_summary.inc.php");
require_once("../../library/sl_eob.inc.php");
require_once("../../library/formatting.inc.php");
require_once("../../library/report_functions.php");
require_once "$srcdir/options.inc.php";
require_once "$srcdir/formdata.inc.php";
require_once("$srcdir/headers.inc.php");
$DateFormat = DateFormatRead();
$DateLocale = getLocaleCodeForDisplayLanguage($GLOBALS['language_default']);

$alertmsg = '';
$bgcolor = "#aaaaaa";
$export_patient_count = 0;
$export_dollars = 0;

$today = date("Y-m-d");

$from_date      = fixDate($_POST['form_from_date'], "");
$to_date        = fixDate($_POST['form_to_date'], "");
$form_facility  = $_POST['form_facility'];
$form_provider  = $_POST['form_provider'];
$is_ins_summary = $_POST['form_category'] == 'Ins Summary';
$is_due_ins     = ($_POST['form_category'] == 'Due Ins') || $is_ins_summary;
$is_due_pt      = $_POST['form_category'] == 'Due Pt';
$is_all         = $_POST['form_category'] == 'All';
$is_ageby_lad   = strpos($_POST['form_ageby'], 'Last') !== false;
$form_payer_id  = $_POST['form_payer_id'];

if ($_POST['form_refresh'] || $_POST['form_export'] || $_POST['form_csvexport']) {
  if ($is_ins_summary) {
    $form_cb_ssn      = false;
    $form_cb_dob      = false;
    $form_cb_pid      = false;
    $form_cb_adate    = false;
    $form_cb_policy   = false;
    $form_cb_phone    = false;
    $form_cb_city     = false;
    $form_cb_ins1     = false;
    $form_cb_referrer = false;
    $form_cb_idays    = false;
    $form_cb_err      = false;
  } else {
    $form_cb_ssn      = $_POST['form_cb_ssn']      ? true : false;
    $form_cb_dob      = $_POST['form_cb_dob']      ? true : false;
    $form_cb_pid      = $_POST['form_cb_pid']      ? true : false;
    $form_cb_adate    = $_POST['form_cb_adate']    ? true : false;
    $form_cb_policy   = $_POST['form_cb_policy']   ? true : false;
    $form_cb_phone    = $_POST['form_cb_phone']    ? true : false;
    $form_cb_city     = $_POST['form_cb_city']     ? true : false;
    $form_cb_ins1     = $_POST['form_cb_ins1']     ? true : false;
    $form_cb_referrer = $_POST['form_cb_referrer'] ? true : false;
    $form_cb_idays    = $_POST['form_cb_idays']    ? true : false;
    $form_cb_err      = $_POST['form_cb_err']      ? true : false;
  }
} else {
  $form_cb_ssn      = true;
  $form_cb_dob      = false;
  $form_cb_pid      = false;
  $form_cb_adate    = false;
  $form_cb_policy   = false;
  $form_cb_phone    = true;
  $form_cb_city     = false;
  $form_cb_ins1     = false;
  $form_cb_referrer = false;
  $form_cb_idays    = false;
  $form_cb_err      = false;
}
$form_age_cols = (int) $_POST['form_age_cols'];
$form_age_inc  = (int) $_POST['form_age_inc'];
if ($form_age_cols > 0 && $form_age_cols < 50) {
  if ($form_age_inc <= 0) $form_age_inc = 30;
} else {
  $form_age_cols = 0;
  $form_age_inc  = 0;
}

$initial_colspan = 1;
if ($is_due_ins      ) ++$initial_colspan;
if ($form_cb_ssn     ) ++$initial_colspan;
if ($form_cb_dob     ) ++$initial_colspan;
if ($form_cb_pid     ) ++$initial_colspan;
if ($form_cb_policy  ) ++$initial_colspan;
if ($form_cb_phone   ) ++$initial_colspan;
if ($form_cb_city    ) ++$initial_colspan;
if ($form_cb_ins1    ) ++$initial_colspan;
if ($form_cb_referrer) ++$initial_colspan;
if ($form_provider   ) ++$initial_colspan;
if ($form_payer_id   ) ++$initial_colspan;

$final_colspan = $form_cb_adate ? 6 : 5;

$grand_total_charges     = 0;
$grand_total_adjustments = 0;
$grand_total_paid        = 0;
$grand_total_agedbal = array();
for ($c = 0; $c < $form_age_cols; ++$c) $grand_total_agedbal[$c] = 0;

/*Attribution: 2010-2017 LibreHealth EHR Support LLC*/
function endPatient($ptrow) {
  global $export_patient_count, $export_dollars, $bgcolor;
  global $grand_total_charges, $grand_total_adjustments, $grand_total_paid;
  global $grand_total_agedbal, $is_due_ins, $form_age_cols;
  global $initial_colspan, $final_colspan, $form_cb_idays, $form_cb_err;

  if (!$ptrow['pid']) return;

  $pt_balance = $ptrow['amount'] - $ptrow['paid'];

  if ($_POST['form_export']) {
    // This is a fixed-length format used by Transworld Systems.  Your
    // needs will surely be different, so consider this just an example.
    //
    echo "1896H"; // client number goes here
    echo "000";   // filler
    echo sprintf("%-30s", substr($ptrow['ptname'], 0, 30));
    echo sprintf("%-30s", " ");
    echo sprintf("%-30s", substr($ptrow['address1'], 0, 30));
    echo sprintf("%-15s", substr($ptrow['city'], 0, 15));
    echo sprintf("%-2s", substr($ptrow['state'], 0, 2));
    echo sprintf("%-5s", $ptrow['zipcode'] ? substr($ptrow['zipcode'], 0, 5) : '00000');
    echo "1";                      // service code
    echo sprintf("%010.0f", $ptrow['pid']); // transmittal number = patient id
    echo " ";                      // filler
    echo sprintf("%-15s", substr($ptrow['ss'], 0, 15));
    echo substr($ptrow['dos'], 5, 2) . substr($ptrow['dos'], 8, 2) . substr($ptrow['dos'], 2, 2);
    echo sprintf("%08.0f", $pt_balance * 100);
    echo sprintf("%-9s\n", " ");

    if (!$_POST['form_without']) {
      sqlStatement("UPDATE patient_data SET " .
        "billing_note = CONCAT('IN COLLECTIONS " . date("Y-m-d") . "', billing_note) " .
        "WHERE pid = ? ", array($ptrow['pid']));
    }
    $export_patient_count += 1;
    $export_dollars += $pt_balance;
  }
  else if ($_POST['form_csvexport']) {
    $export_patient_count += 1;
    $export_dollars += $pt_balance;
  }
  else {
    if ($ptrow['count'] > 1) {
      echo " <tr bgcolor='$bgcolor'>\n";
      /***************************************************************
      echo "  <td class='detail' colspan='$initial_colspan'>";
      echo "&nbsp;</td>\n";
      echo "  <td class='detotal' colspan='$final_colspan'>&nbsp;Total Patient Balance:</td>\n";
      ***************************************************************/
      echo "  <td class='detotal' colspan='" . ($initial_colspan + $final_colspan) .
        "'>&nbsp;" . xlt('Total Patient Balance') . ":</td>\n";
      /**************************************************************/
      if ($form_age_cols) {
        for ($c = 0; $c < $form_age_cols; ++$c) {
          echo "  <td class='detotal' align='right'>&nbsp;" .
            oeFormatMoney($ptrow['agedbal'][$c]) . "&nbsp;</td>\n";
        }
      }
      else {
        echo "  <td class='detotal' align='right'>&nbsp;" .
          oeFormatMoney($pt_balance) . "&nbsp;</td>\n";
      }
      if ($form_cb_idays) echo "  <td class='detail'>&nbsp;</td>\n";
      echo "  <td class='detail' colspan='2'>&nbsp;</td>\n";
      if ($form_cb_err) echo "  <td class='detail'>&nbsp;</td>\n";
      echo " </tr>\n";
    }
  }
  $grand_total_charges     += $ptrow['charges'];
  $grand_total_adjustments += $ptrow['adjustments'];
  $grand_total_paid        += $ptrow['paid'];
  for ($c = 0; $c < $form_age_cols; ++$c) {
    $grand_total_agedbal[$c] += $ptrow['agedbal'][$c];
  }
}

/*Attribution: 2010-2017 LibreHealth EHR Support LLC*/
function endInsurance($insrow) {
  global $export_patient_count, $export_dollars, $bgcolor;
  global $grand_total_charges, $grand_total_adjustments, $grand_total_paid;
  global $grand_total_agedbal, $is_due_ins, $form_age_cols;
  global $initial_colspan, $form_cb_idays, $form_cb_err;
  if (!$insrow['pid']) return;
  $ins_balance = $insrow['amount'] - $insrow['paid'];
  if ($_POST['form_export'] || $_POST['form_csvexport']) {
    // No exporting of insurance summaries.
    $export_patient_count += 1;
    $export_dollars += $ins_balance;
  }
  else {
    echo " <tr bgcolor='$bgcolor'>\n";
    echo "  <td class='detail'>" . text($insrow['insname']) . "</td>\n";
    echo "  <td class='detotal' align='right'>&nbsp;" .
      oeFormatMoney($insrow['charges']) . "&nbsp;</td>\n";
    echo "  <td class='detotal' align='right'>&nbsp;" .
      oeFormatMoney($insrow['adjustments']) . "&nbsp;</td>\n";
    echo "  <td class='detotal' align='right'>&nbsp;" .
      oeFormatMoney($insrow['paid']) . "&nbsp;</td>\n";
    if ($form_age_cols) {
      for ($c = 0; $c < $form_age_cols; ++$c) {
        echo "  <td class='detotal' align='right'>&nbsp;" .
          oeFormatMoney($insrow['agedbal'][$c]) . "&nbsp;</td>\n";
      }
    }
    else {
      echo "  <td class='detotal' align='right'>&nbsp;" .
        oeFormatMoney($ins_balance) . "&nbsp;</td>\n";
    }
    echo " </tr>\n";
  }
  $grand_total_charges     += $insrow['charges'];
  $grand_total_adjustments += $insrow['adjustments'];
  $grand_total_paid        += $insrow['paid'];
  for ($c = 0; $c < $form_age_cols; ++$c) {
    $grand_total_agedbal[$c] += $insrow['agedbal'][$c];
  }
}

/*Attribution: 2010-2017 LibreHealth EHR Support LLC*/
function getInsName($payerid) {
  $tmp = sqlQuery("SELECT name FROM insurance_companies WHERE id = ? ", array($payerid));
  return $tmp['name'];
}

/* This function prepares the report from form inputs, and displays them.
 * @params: None
 * @return: None
 * @author: Tigpezeghe Rodrige K. <tigrodrige@gmail.com>
 * */
function prepareAndShowResults() {
  global $form_cb_ssn, $form_cb_dob, $form_cb_pid, $form_cb_adate, $form_cb_policy, $form_cb_phone, $form_cb_city, $form_cb_ins1, $form_cb_referrer, $form_cb_idays, $form_cb_err;
  global $from_date, $to_date, $form_facility, $form_provider, $is_ins_summary, $is_due_ins, $is_due_pt, $is_all,$is_ageby_lad, $form_payer_id;
  global $is_ins_summary, $today, $rows, $row, $where, $sqlArray;
  global $grand_total_charges, $grand_total_adjustments, $grand_total_paid;
  global $final_colspan, $initial_colspan;
  $rows = array();
  $where = "";
  $sqlArray = array();

    if ($_POST['form_export'] || $_POST['form_csvexport']) {
      $where = "( 1 = 2";
      foreach ($_POST['form_cb'] as $key => $value) {
        list($key_newval['pid'], $key_newval['encounter']) = explode(".", $key);
        $newkey = $key_newval['pid'];
        $newencounter =  $key_newval['encounter'];
        # added this condition to handle the downloading of individual invoices (TLH)
        if($_POST['form_individual'] ==1){
          $where .= " OR f.encounter = ? ";
          array_push($sqlArray, $newencounter);
        } else {
          $where .= " OR f.pid = ? ";
          array_push($sqlArray, $newkey);
        }
      }
      $where .= ' )';
    }

    if ($from_date) {
      if ($where) $where .= " AND ";
      if ($to_date) {
        $where .= "f.date >= ? AND f.date <= ? ";
        array_push($sqlArray, $from_date.' 00:00:00', $to_date.' 23:59:59');
      } else {
        $where .= "f.date >= ? AND f.date <= ? ";
        array_push($sqlArray, $from_date.' 00:00:00', $from_date.' 23:59:59');
      }
    }

    if ($form_facility) {
      if ($where) $where .= " AND ";
      $where .= "f.facility_id = ? ";
      array_push($sqlArray, $form_facility);
    }

    # added for filtering by provider (TLH)
    if ($form_provider) {
      if ($where) $where .= " AND ";
      $where .= "f.provider_id = ? ";
      array_push($sqlArray, $form_provider);
    }

    if (! $where) {
      $where = "1 = 1";
    }

    # added provider from encounter to the query (TLH)
    $query = "SELECT f.id, f.date, f.pid, CONCAT(w.lname, ', ', w.fname) AS provider_id, f.encounter, f.last_level_billed, " .
      "f.last_level_closed, f.last_stmt_date, f.stmt_count, f.invoice_refno, " .
      "p.fname, p.mname, p.lname, p.street, p.city, p.state, " .
      "p.postal_code, p.phone_home, p.ss, p.billing_note, " .
      "p.pid, p.DOB, CONCAT(u.lname, ', ', u.fname) AS referrer, " .
      "( SELECT SUM(b.fee) FROM billing AS b WHERE " .
      "b.pid = f.pid AND b.encounter = f.encounter AND " .
      "b.activity = 1 AND b.code_type != 'COPAY' ) AS charges, " .
      "( SELECT SUM(b.fee) FROM billing AS b WHERE " .
      "b.pid = f.pid AND b.encounter = f.encounter AND " .
      "b.activity = 1 AND b.code_type = 'COPAY' ) AS copays, " .
      "( SELECT SUM(s.fee) FROM drug_sales AS s WHERE " .
      "s.pid = f.pid AND s.encounter = f.encounter ) AS sales, " .
      "( SELECT SUM(a.pay_amount) FROM ar_activity AS a WHERE " .
      "a.pid = f.pid AND a.encounter = f.encounter ) AS payments, " .
      "( SELECT SUM(a.adj_amount) FROM ar_activity AS a WHERE " .
      "a.pid = f.pid AND a.encounter = f.encounter ) AS adjustments " .
      "FROM form_encounter AS f " .
      "JOIN patient_data AS p ON p.pid = f.pid " .
      "LEFT OUTER JOIN users AS u ON u.id = p.ref_providerID " .
      "LEFT OUTER JOIN users AS w ON w.id = f.provider_id " .
      "WHERE $where " .
      "ORDER BY f.pid, f.encounter";

    $eres = sqlStatement($query, $sqlArray);

    while ($erow = sqlFetchArray($eres)) {
      $patient_id = $erow['pid'];
      $encounter_id = $erow['encounter'];
      $pt_balance = $erow['charges'] + $erow['sales'] + $erow['copays'] - $erow['payments'] - $erow['adjustments'];
      $pt_balance = 0 + sprintf("%.2f", $pt_balance); // yes this seems to be necessary
      $svcdate = substr($erow['date'], 0, 10);

      if ($_POST['form_refresh'] && ! $is_all) {
        if ($pt_balance == 0) continue;
      }

      if ($_POST['form_category'] == 'Credits') {
        if ($pt_balance > 0) continue;
      }

      // If we have not yet billed the patient, then compute $duncount as a
      // negative count of the number of insurance plans for which we have not
      // yet closed out insurance.  Here we also compute $insname as the name of
      // the insurance plan from which we are awaiting payment, and its sequence
      // number $insposition (1-3).
      $last_level_closed = $erow['last_level_closed'];
      $duncount = $erow['stmt_count'];
      $payerids = array();
      $insposition = 0;
      $insname = '';

      if (! $duncount) {
        for ($i = 1; $i <= 3; ++$i) {
          $tmp = arGetPayerID($patient_id, $svcdate, $i);
          if (empty($tmp)) break;
          $payerids[] = $tmp;
        }
        $duncount = $last_level_closed - count($payerids);
        if ($duncount < 0) {
          if (!empty($payerids[$last_level_closed])) {
            $insname = getInsName($payerids[$last_level_closed]);
            $insposition = $last_level_closed + 1;
          }
        }
      }

      // Skip invoices not in the desired "Due..." category.
      //
      if ($is_due_ins && $duncount >= 0) continue;
      if ($is_due_pt  && $duncount <  0) continue;

      // echo "<!-- " . $erow['encounter'] . ': ' . $erow['charges'] . ' + ' . $erow['sales'] . ' + ' . $erow['copays'] . ' - ' . $erow['payments'] . ' - ' . $erow['adjustments'] . "  -->\n"; // debugging

      // An invoice is due from the patient if money is owed and we are
      // not waiting for insurance to pay.
      $isduept = ($duncount >= 0) ? " checked" : "";
      $row = array();

      $row['id']        = $erow['id'];
      $row['invnumber'] = "$patient_id.$encounter_id";
      $row['custid']    = $patient_id;
      $row['name']      = $erow['fname'] . ' ' . $erow['lname'];
      $row['address1']  = $erow['street'];
      $row['city']      = $erow['city'];
      $row['state']     = $erow['state'];
      $row['zipcode']   = $erow['postal_code'];
      $row['phone']     = $erow['phone_home'];
      $row['duncount']  = $duncount;
      $row['dos']       = $svcdate;
      $row['ss']        = $erow['ss'];
      $row['DOB']       = $erow['DOB'];
      $row['pid']       = $erow['pid'];
      $row['billnote']  = $erow['billing_note'];
      $row['referrer']  = $erow['referrer'];
      $row['provider']  = $erow['provider_id'];
      $row['irnumber']  = $erow['invoice_refno'];

      // Also get the primary insurance company name whenever there is one.
      $row['ins1'] = '';

      if ($insposition == 1) {
        $row['ins1'] = $insname;
      } else {
        if (empty($payerids)) {
          $tmp = arGetPayerID($patient_id, $svcdate, 1);
          if (!empty($tmp)) $payerids[] = $tmp;
        }
        if (!empty($payerids)) {
          $row['ins1'] = getInsName($payerids[0]);
        }
      }

      // This computes the invoice's total original charges and adjustments,
      // date of last activity, and determines if insurance has responded to
      // all billing items.
      $invlines = ar_get_invoice_summary($patient_id, $encounter_id, true);

      // if ($encounter_id == 185) { // debugging
      //   echo "\n<!--\n";
      //   print_r($invlines);
      //   echo "\n-->\n";
      // }

      $row['charges'] = 0;
      $row['adjustments'] = 0;
      $row['paid'] = 0;
      $ins_seems_done = true;
      $ladate = $svcdate;

      foreach ($invlines as $key => $value) {
        $row['charges'] += $value['chg'] + $value['adj'];
        $row['adjustments'] += 0 - $value['adj'];
        $row['paid'] += $value['chg'] - $value['bal'];
        foreach ($value['dtl'] as $dkey => $dvalue) {
          $dtldate = trim(substr($dkey, 0, 10));
          if ($dtldate && $dtldate > $ladate) $ladate = $dtldate;
        }
        $lckey = strtolower($key);
        if ($lckey == 'co-pay' || $lckey == 'claim') continue;
        if (count($value['dtl']) <= 1) $ins_seems_done = false;
      }

      // Simulating ar.amount in SQL-Ledger which is charges with adjustments:
      $row['amount'] = $row['charges'] + $row['adjustments'];

      $row['billing_errmsg'] = '';
      if ($is_due_ins && $last_level_closed < 1 && $ins_seems_done)
        $row['billing_errmsg'] = 'Ins1 seems done';
      else if ($last_level_closed >= 1 && !$ins_seems_done)
        $row['billing_errmsg'] = 'Ins1 seems not done';

      $row['ladate'] = $ladate;

      // Compute number of days since last activity.
      $latime = mktime(0, 0, 0, substr($ladate, 5, 2),
        substr($ladate, 8, 2), substr($ladate, 0, 4));
      $row['inactive_days'] = floor((time() - $latime) / (60 * 60 * 24));

      // Look up insurance policy number if we need it.
      if ($form_cb_policy) {
        $instype = ($insposition == 2) ? 'secondary' : (($insposition == 3) ? 'tertiary' : 'primary');
        $insrow = sqlQuery("SELECT policy_number FROM insurance_data WHERE " .
          "pid = ? AND type = ? AND date <= ? " .
          "ORDER BY date DESC LIMIT 1", array($patient_id, $instype, $svcdate));
        $row['policy'] = $insrow['policy_number'];
      }

      $ptname = $erow['lname'] . ", " . $erow['fname'];
      if ($erow['mname']) $ptname .= " " . substr($erow['mname'], 0, 1);

      if (!$is_due_ins ) $insname = '';
      $rows[$insname . '|' . $ptname . '|' . $encounter_id] = $row;
    } // end while

  ksort($rows);

  if ($_POST['form_export']) {
    echo "<textarea rows='35' cols='100' readonly>";
  } else if ($_POST['form_csvexport']) {
    # CSV headers added conditions if they are checked to display then export them (TLH)
    if (true) {
      echo '"' . xl('Insurance') . '",';
      echo '"' . xl('Name') . '",';
      if ($form_cb_ssn) {
        echo '"' . xl('SSN') . '",';
      }
      if ($form_cb_dob) {
        echo '"' . xl('DOB') . '",';
      }
      if ($form_cb_pubid) {
        echo '"' . xl('Pubid') . '",';
      }
      if ($form_cb_policy) {
        echo '"' . xl('Policy') . '",';
      }
      if ($form_cb_phone) {
        echo '"' . xl('Phone') . '",';
      }
      if ($form_cb_city) {
        echo '"' . xl('City') . '",';
      }
      echo '"' . xl('Invoice') . '",';
      echo '"' . xl('DOS') . '",';
      echo '"' . xl('Referrer') . '",';
      echo '"' . xl('Provider') . '",';
      echo '"' . xl('Charge') . '",';
      echo '"' . xl('Adjust') . '",';
      echo '"' . xl('Paid') . '",';
      echo '"' . xl('Balance') . '",';
      echo '"' . xl('IDays') . '",';
      if ($form_cb_err) {
        echo '"' . xl('LADate') . '",';
        echo '"' . xl('Error') . '"' . "\n";
      } else {
        echo '"' . xl('LADate') . '"' . "\n";
      }
    }
  } else {
      echo '<div id="report_results">
      <table>

        <thead>';
          if ($is_due_ins) {
            echo '<th>&nbsp;'; echo xlt('Insurance') . '</th>';
          }
          if (!$is_ins_summary) {
            echo '<th>&nbsp;'; echo xlt('Name') . '</th>';
          }
          if ($form_cb_ssn) {
            echo '<th>&nbsp;'; echo xlt('SSN') . '</th>';
          }
          if ($form_cb_dob) {
            echo '<th>&nbsp;'; echo xlt('DOB') . '</th>';
          }
          if ($form_cb_pid) {
            echo '<th>&nbsp;'; echo xlt('ID') . '</th>';
          }
          if ($form_cb_policy) {
            echo '<th>&nbsp;'; echo xlt('Policy') . '</th>';
          }
          if ($form_cb_phone) {
            echo '<th>&nbsp;'; echo xlt('Phone') . '</th>';
          }
          if ($form_cb_city) {
            echo '<th>&nbsp;'; echo xlt('City') . '</th>';
          }
          if ($form_cb_ins1 || $form_payer_id) {
            echo '<th>&nbsp;'; echo xlt('Primary Ins') . '</th>';
          }
          if ($form_provider) {
            echo '<th>&nbsp;'; echo xlt('Provider') . '</th>';
          }
          if ($form_cb_referrer) {
            echo '<th>&nbsp;'; echo xlt('Referrer') . '</th>';
          }
          if (!$is_ins_summary) {
            echo '<th>&nbsp;'; echo xlt('Invoice') . '</th>';
            echo '<th>&nbsp;'; echo xlt('Svc Date') . '</th>';
            if ($form_cb_adate) {
              echo '<th>&nbsp;'; echo xlt('Act Date') . '</th>';
            }
          }
          echo '<th align="right">'; echo xlt('Charge') . '&nbsp;</th>';
          echo '<th align="right">'; echo xlt('Adjust') . '&nbsp;</th>';
          echo '<th align="right">'; echo xlt('Paid') . '&nbsp;</th>';

          // Generate aging headers if appropriate, else balance header.
          if ($form_age_cols) {
              for ($c = 0; $c < $form_age_cols;) {
                echo "  <th class='dehead' align='right'>";
                echo $form_age_inc * $c;
                if (++$c < $form_age_cols) {
                  echo "-" . ($form_age_inc * $c - 1);
                } else {
                  echo "+";
                }
                echo "</th>\n";
              }
          } else {
            echo '<th align="right">'; echo xlt('Balance') . '&nbsp;</th>';
          }

          if ($form_cb_idays) {
            echo '<th align="right">'; echo xlt('IDays') . '&nbsp;</th>';
          }
          if (!$is_ins_summary) {
            echo '<th align="center">'; echo xlt('Prv') . '</th>';
            echo '<th align="center">'; echo xlt('Sel') . '</th>';
          }
          if ($form_cb_err) {
            echo '<th>&nbsp;'; echo xlt('Error') . '</th>';
          }
        echo '</thead>';
  } // end not export

  $ptrow = array('insname' => '', 'pid' => 0);
  $orow = -1;

  foreach ($rows as $key => $row) {
    list($insname, $ptname, $trash) = explode('|', $key);
    list($pid, $encounter) = explode(".", $row['invnumber']);

    if ($form_payer_id) {
      if ($ins_co_name <> $row['ins1']) continue;
    }

    if ($is_ins_summary && $insname != $ptrow['insname']) {
      endInsurance($ptrow);
      $bgcolor = ((++$orow & 1) ? "#ffdddd" : "#ddddff");
      $ptrow = array('insname' => $insname, 'ptname' => $ptname, 'pid' => $pid, 'count' => 1);
      foreach ($row as $key => $value) $ptrow[$key] = $value;
      $ptrow['agedbal'] = array();
    } else if (!$is_ins_summary && ($insname != $ptrow['insname'] || $pid != $ptrow['pid'])) {
      // For the report, this will write the patient totals.  For the
      // collections export this writes everything for the patient:
      endPatient($ptrow);
      $bgcolor = ((++$orow & 1) ? "#ffdddd" : "#ddddff");
      $ptrow = array('insname' => $insname, 'ptname' => $ptname, 'pid' => $pid, 'count' => 1);
      foreach ($row as $key => $value) $ptrow[$key] = $value;
      $ptrow['agedbal'] = array();
    } else {
      $ptrow['amount']      += $row['amount'];
      $ptrow['paid']        += $row['paid'];
      $ptrow['charges']     += $row['charges'];
      $ptrow['adjustments'] += $row['adjustments'];
      ++$ptrow['count'];
    }

    // Compute invoice balance and aging column number, and accumulate aging.
    $balance = $row['charges'] + $row['adjustments'] - $row['paid'];
    if ($form_age_cols) {
      $agedate = $is_ageby_lad ? $row['ladate'] : $row['dos'];
      $agetime = mktime(0, 0, 0, substr($agedate, 5, 2),
        substr($agedate, 8, 2), substr($agedate, 0, 4));
      $days = floor((time() - $agetime) / (60 * 60 * 24));
      $agecolno = min($form_age_cols - 1, max(0, floor($days / $form_age_inc)));
      $ptrow['agedbal'][$agecolno] += $balance;
    }

    if (!$is_ins_summary && !$_POST['form_export'] && !$_POST['form_csvexport']) {
      $in_collections = stristr($row['billnote'], 'IN COLLECTIONS') !== false;
      echo '<tr bgcolor="'; echo attr($bgcolor) . '">';
      if ($ptrow['count'] == 1) {
        if ($is_due_ins) {
          echo "  <td class='detail'>&nbsp;" . attr($insname) ."</td>\n";
        }
        echo "  <td class='detail'>&nbsp;" . attr($ptname) ."</td>\n";
        if ($form_cb_ssn) {
          echo "  <td class='detail'>&nbsp;" . attr($row['ss']) . "</td>\n";
        }
        if ($form_cb_dob) {
          echo "  <td class='detail'>&nbsp;" . attr(date(DateFormatRead(true), strtotime($row['DOB']))) ."</td>\n";
        }
        if ($form_cb_pid) {
          echo "  <td class='detail'>&nbsp;" . attr($row['pid']) . "</td>\n";
        }
        if ($form_cb_policy) {
          echo "  <td class='detail'>&nbsp;" . attr($row['policy']) . "</td>\n";
        }
        if ($form_cb_phone) {
          echo "  <td class='detail'>&nbsp;" . attr($row['phone']) . "</td>\n";
        }
        if ($form_cb_city) {
          echo "  <td class='detail'>&nbsp;" . attr($row['city']) . "</td>\n";
        }
        if ($form_cb_ins1 || $form_payer_id ) {
          echo "  <td class='detail'>&nbsp;" . attr($row['ins1']) . "</td>\n";
        }
        if ($form_provider) {
          echo "  <td class='detail'>&nbsp;" . attr($provider_name) . "</td>\n";
        }
        if ($form_cb_referrer) {
          echo "  <td class='detail'>&nbsp;" . attr($row['referrer']) . "</td>\n";
        }
      } else {
        echo "  <td class='detail' colspan='$initial_colspan'>";
        echo "&nbsp;</td>\n";
      }

      echo '<td class="detail">
         &nbsp;<a href="../billing/sl_eob_invoice.php?id='; echo attr($row['id']) . '"
          target="_blank">'; echo empty($row['irnumber']) ? $row['invnumber'] : $row['irnumber']; echo '</a>
        </td>
        <td class="detail">
         &nbsp;'; echo attr(oeFormatShortDate($row['dos']));
        echo '</td>';
      if ($form_cb_adate) {
        echo '<td class="detail">
         &nbsp;'; echo attr(oeFormatShortDate($row['ladate']));
        echo '</td>';
      }
      //print_r($row['charges']);die();
      //print_r($row['adjustments']);die();
      //var_dump($fman);
        echo '<td class="detail" align="right">';
          attr(bucks($row['charges'])); echo '&nbsp;
        </td>
        <td class="detail" align="right">';
          attr(bucks($row['adjustments'])); echo '&nbsp;
        </td>
        <td class="detail" align="right">';
          attr(bucks($row['paid'])); echo '&nbsp;
        </td>';
      if ($form_age_cols) {
        for ($c = 0; $c < $form_age_cols; ++$c) {
          echo "  <td class='detail' align='right'>";
          if ($c == $agecolno) {
            bucks($balance);
          }
            echo "&nbsp;</td>\n";
        }
      } else {
        echo '<td class="detail" align="right">'; bucks($balance); echo '&nbsp;</td>';
      } // end else

      if ($form_cb_idays) {
        echo "  <td class='detail' align='right'>";
        echo attr($row['inactive_days']); echo "&nbsp;</td>\n";
      }

      echo '<td class="detail" align="center">';
        echo $row['duncount'] ? $row['duncount'] : "&nbsp;";
      echo '</td>
        <td class="detail" align="center">';
          if ($in_collections) {
            echo "   <b><font color='red'>IC</font></b>\n";
          } else {
            echo "   <input type='checkbox' name='form_cb[" .  attr($row['invnumber'])  . "]' />\n";
          }
        echo '</td>';

        if ($form_cb_err) {
              echo "  <td class='detail'>&nbsp;";
              echo text($row['billing_errmsg']) . "</td>\n";
        }
      echo '</tr>';
    } // end not export and not insurance summary
    else if ($_POST['form_csvexport']) {
      # The CSV detail line is written here added conditions for checked items (TLH).
      $balance = $row['charges'] + $row['adjustments'] - $row['paid'];
      if($balance >0) {
        // echo '"' . $insname                             . '",';
        echo '"' . $row['ins1']                         . '",';
        echo '"' . $ptname                              . '",';
        if ($form_cb_ssn) {
          echo '"' . $row['ss']                          . '",';
        }
        if ($form_cb_dob) {
          echo '"' . oeFormatShortDate($row['DOB'])       . '",';
        }
        if ($form_cb_pubid) {
              echo '"' . $row['pid']                       . '",';
        }
        if ($form_cb_policy) {
          echo '"' . $row['policy']                       . '",';
        }
        if ($form_cb_phone) {
          echo '"' . $row['phone']                       . '",';
        }
        if ($form_cb_city) {
          echo '"' . $row['city']                       . '",';
        }
        echo '"' . (empty($row['irnumber']) ? $row['invnumber'] : $row['irnumber']) . '",';
        echo '"' . oeFormatShortDate($row['dos'])       . '",';
        echo '"' . $row['referrer']                     . '",';
        echo '"' . $row['provider']                     . '",';
        echo '"' . oeFormatMoney($row['charges'])       . '",';
        echo '"' . oeFormatMoney($row['adjustments'])   . '",';
        echo '"' . oeFormatMoney($row['paid'])          . '",';
        echo '"' . oeFormatMoney($balance)              . '",';
        echo '"' . $row['inactive_days']                . '",';
        if ($form_cb_err) {
          echo '"' . oeFormatShortDate($row['ladate'])    . '",';
          echo '"' . $row['billing_errmsg']               . '"' . "\n";
        } else {
          echo '"' . oeFormatShortDate($row['ladate'])    . '"' . "\n";
        }
      }
    } // end $form_csvexport
  }

  if ($is_ins_summary)
    endInsurance($ptrow);
  else
    endPatient($ptrow);

  if ($_POST['form_export']) {
    echo "</textarea>\n";
    $alertmsg .= "$export_patient_count patients with total of " .
      oeFormatMoney($export_dollars) . " have been exported ";
    if ($_POST['form_without']) {
      $alertmsg .= "but NOT flagged as in collections.";
    } else {
      $alertmsg .= "AND flagged as in collections.";
    }
  } else if ($_POST['form_csvexport']) {
    // echo "</textarea>\n";
    // $alertmsg .= "$export_patient_count patients representing $" .
    //   sprintf("%.2f", $export_dollars) . " have been exported.";
  } else {
    echo " <tr bgcolor='#ffffff'>\n";
    if ($is_ins_summary) {
      echo "  <td class='dehead'>&nbsp;" . xlt('Report Totals'); echo ":</td>\n";
    } else {
      echo "  <td class='detail' colspan='" . attr($initial_colspan); echo "'>\n";
      echo "   &nbsp;</td>\n";
      echo "  <td class='dehead' colspan='" . attr($final_colspan - 3); echo "'>&nbsp;" . xlt('Report Totals'); echo ":</td>\n";
    }
    echo "  <td class='dehead' align='right'>&nbsp;" .
      oeFormatMoney($grand_total_charges); echo "&nbsp;</td>\n";
    echo "  <td class='dehead' align='right'>&nbsp;" .
      oeFormatMoney($grand_total_adjustments); echo "&nbsp;</td>\n";
    echo "  <td class='dehead' align='right'>&nbsp;" .
      oeFormatMoney($grand_total_paid); echo "&nbsp;</td>\n";
    if ($form_age_cols) {
      for ($c = 0; $c < $form_age_cols; ++$c) {
        echo "  <td class='dehead' align='right'>" .
          oeFormatMoney($grand_total_agedbal[$c]); echo "&nbsp;</td>\n";
      }
    } else {
      echo "  <td class='dehead' align='right'>" .
        oeFormatMoney($grand_total_charges +
        $grand_total_adjustments - $grand_total_paid); echo "&nbsp;</td>\n";
    }
    if ($form_cb_idays) echo "  <td class='detail'>&nbsp;</td>\n";
    if (!$is_ins_summary) echo "  <td class='detail' colspan='2'>&nbsp;</td>\n";
    if ($form_cb_err) echo "  <td class='detail'>&nbsp;</td>\n";
    echo " </tr>\n";
    echo "</table>\n";
    echo "</div>\n";
  }
}

?>
