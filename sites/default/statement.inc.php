<?php

// Copyright (C) 2005-2006 Rod Roark <rod@sunsetsystems.com>
// Copyright (C) 2016       Raymond Magauran <magauran@medfetch.com>
// Copyright (C) 2015-2018  Terry Hill <teryhill@librehealth.io>
//
// Windows compatibility mods 2009 Bill Cernansky [mi-squared.com]
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// Updated by Medical Information Integration, LLC to support download
//  and multi OS use - tony@mi-squared..com 12-2009

//////////////////////////////////////////////////////////////////////
// This is a template for printing patient statements and collection
// letters.  You must customize it to suit your practice.  If your
// needs are simple then you do not need programming experience to do
// this - just read the comments and make appropriate substitutions.
// All you really need to do is replace the [strings in brackets].
//////////////////////////////////////////////////////////////////////

// The location/name of a temporary file to hold printable statements.
// May want to alter these names to allow multi-site installs out-of-the-box
$STMT_TEMP_FILE = $GLOBALS['temporary_files_dir'] . "/libreehr_statements.txt";
$STMT_TEMP_FILE_PDF = $GLOBALS['temporary_files_dir'] . "/libreehr_statements.pdf";
$STMT_PRINT_CMD = $GLOBALS['print_command'];
/** There are two options to print a batch of PDF statements:
 *  1.  The original statement, a text based statement, using CezPDF
 *      Altering this statement is labor intensive, but capable of being altered any way desired...
 *
 *  2.  Branded Statement, whose core is build from 1., the original statement, using HTML2PDF.
 *
 *      To customize 2., add your practice location/images/practice_logo.gif
 *      In the base/default install this is located at '/libreehr/sites/default/images/practice_logo.gif',
 *      Adjust directory paths per your installation.
 *      Further customize 2. manually in functions report_2() and create_HTML_statement(), below.
 *
 */

//for getPatientName function
require_once("../../library/patient.inc");

function make_statement($stmt) {
  if ($GLOBALS['statement_appearance'] == "1") {
    if(is_auth_portal($stmt['pid']) && $_POST['form_portalnotify'])
        return osp_create_HTML_statement($stmt);
    else
    return create_HTML_statement($stmt);
  } else {
    return create_statement($stmt);
  }
}
/**
 * This prints a header for documents.  Keeps the brand uniform...
 *  @param string $pid patient_id
 *  @param string $direction, options "web" or anything else.  Web provides apache-friendly url links.
 *  @return outputs to be displayed however requested
 */
function report_header_2($stmt,$direction='',$providerID='1') {
  $titleres = getPatientData($stmt['pid'], "fname,lname,DOB");
  if ($_SESSION['pc_facility']) {
    $sql = "select * from facility where id=?";
    $facility = sqlQuery($sql,array($_SESSION['pc_facility']));
  } else {
    $sql = "SELECT * FROM facility ORDER BY billing_location DESC LIMIT 1";
    $facility = sqlQuery($sql);
  }
  $DOB = oeFormatShortDate($titleres['DOB']);
  /******************************************************************/
  ob_start();
  // Use logo if it exists as 'practice_logo.gif' in the site dir
  // old code used the global custom dir which is no longer a valid
  ?>
  <table style="width:7in;">
    <tr>
      <td style='width:100px;text-align:top;'>
        <?php
          $practice_logo = $GLOBALS['OE_SITE_DIR']."/images/practice_logo.gif";
          if (file_exists($practice_logo)) {
            echo "<img src='$practice_logo' align='left' style='width:125px;margin:0px;'><br />\n";
          }
        ?>
      </td>
      <td style='width:40%;'>
        <em style="font-weight:bold;font-size:1.4em;"><?php echo text($facility['name']); ?></em><br />
        <?php echo text($facility['street']); ?><br />
        <?php echo text($facility['city']); ?>, <?php echo text($facility['state']); ?> <?php echo text($facility['postal_code']); ?><br />
        <?php echo xlt('Phone').': ' .text($facility['phone']); ?><br />
        <?php echo xlt('Fax').': ' .text($facility['fax']); ?><br />
        <br clear='all' />
      </td>
      <td>
        <em style="font-weight:bold;font-size:1.4em;"><?php echo text($titleres['fname']) . " " . text($titleres['lname']); ?></em><br />
        <b style="font-weight:bold;"><?php echo xlt('Chart Number'); ?>:</b> <?php echo text($stmt['pid']); ?><br />
        <b style="font-weight:bold;"><?php echo xlt('Generated on'); ?>:</b> <?php echo oeFormatShortDate(); ?><br />
        <b><?php echo xlt('Provider') . ':</b>  '; ?><?php echo text(getProviderName($providerID)); ?> <br />
      </td>
    </tr>
  </table>
  <?php
  $output = ob_get_contents();
  ob_end_clean();
  return $output;
}
function create_HTML_statement($stmt) {
  if (! $stmt['pid']) return ""; // get out if no data

  #minimum_amount_due_to _print
  if ($stmt['amount'] <= ($GLOBALS['minimum_amount_to_print']) && $GLOBALS['use_statement_print_exclusion']) {
    //increment whenever amount value of statement is less than minimum amount value,
    //to keep count of such ignored statements in final print
    $GLOBALS['stmts_below_minimum_amount']++;
    //also add corresponding patient name
    array_push($GLOBALS['pat_below_minimum_amount'], getPatientName($stmt['pid']));
    return "";
  }

  if ($stmt['statement_print'] == "NO" && $GLOBALS['use_statement_print_exclusion']) {
    //increment whenever the patient is set to no statement,
    //to keep count of such ignored statements in final print
    $GLOBALS['stmts_set_no_patient']++;
    //also add corresponding patient name
    array_push($GLOBALS['pat_set_no_stmt'], getPatientName($stmt['pid']));
    return "";
  }
  if ($GLOBALS['use_statement_print_exclusion'] && $GLOBALS['insurance_statement_exclude'] !=4 ){
    if ($GLOBALS['insurance_statement_exclude'] ==0 && $stmt['insurance_no_statement_print_pri']) {
      //increment whenever the insurance company does not allow statements,
      //to keep count of such ignored statements in final print
      $GLOBALS['stmts_not_allowed_insurance_company']++;
      //also add corresponding patient name
      array_push($GLOBALS['pat_not_allowed_insurance_company'], getPatientName($stmt['pid']));
      return "";
    }
    if ($GLOBALS['insurance_statement_exclude'] ==1 && $stmt['insurance_no_statement_print_sec']) {
      //increment whenever the insurance company does not allow statements,
      //to keep count of such ignored statements in final print
      $GLOBALS['stmts_not_allowed_insurance_company']++;
      //also add corresponding patient name
      array_push($GLOBALS['pat_not_allowed_insurance_company'], getPatientName($stmt['pid']));
      return "";
    }
    if ($GLOBALS['insurance_statement_exclude'] ==2 && $stmt['insurance_no_statement_print_tri']) {
      //increment whenever the insurance company does not allow statements,
      //to keep count of such ignored statements in final print
      $GLOBALS['stmts_not_allowed_insurance_company']++;
      //also add corresponding patient name
      array_push($GLOBALS['pat_not_allowed_insurance_company'], getPatientName($stmt['pid']));
      return "";
    }
    if ($GLOBALS['insurance_statement_exclude'] ==3) return "";
  }

  // Don't print if decreased
  if ($GLOBALS['disallow_print_deceased']) return "";

  // Facility (service location)
  $atres = sqlStatement("select f.name,f.street,f.city,f.state,f.postal_code,f.attn,f.phone from facility f " .
    " left join users u on f.id=u.facility_id " .
    " left join  billing b on b.provider_id=u.id and b.pid = ? ".
    " where  service_location=1",array($stmt['pid']));
  $row = sqlFetchArray($atres);
  $clinic_name = "{$row['name']}";
  $clinic_addr = "{$row['street']}";
  $clinic_csz = "{$row['city']}, {$row['state']}, {$row['postal_code']}";
  // Contacts
  $billing_contact = "{$row['attn']}";
  $billing_phone = "{$row['phone']}";
  // Billing location
  $remit_name = $clinic_name;
  $remit_addr = $clinic_addr;
  $remit_csz = $clinic_csz;
  ob_start();
  ?><div style="padding-left:25px;">
  <?php
  $find_provider = sqlQuery("SELECT * FROM form_encounter " .
        "WHERE pid = ? AND encounter = ? " .
        "ORDER BY id DESC LIMIT 1", array($stmt['pid'],$stmt['encounter']) );
  $providerID = $find_provider['provider_id'];
  echo report_header_2($stmt,$direction,$providerID);
  // dunning message setup
  // insurance has paid something
  // $stmt['age'] how old is the invoice
  // $stmt['dun_count'] number of statements run
  // $stmt['level_closed'] <= 3 insurance 4 = patient
  if ($GLOBALS['use_dunning_message']) {
    if ($stmt['ins_paid'] != 0 || $stmt['level_closed'] == 4)  {
      // do collection messages
      switch ($stmt{'age'}) {
        case $stmt{'age'} <= $GLOBALS['first_dun_msg_set']:
        $dun_message = $GLOBALS['first_dun_msg_text'];
        break;
        case $stmt{'age'} <= $GLOBALS['second_dun_msg_set']:
        $dun_message = $GLOBALS['second_dun_msg_text'];
        break;
        case $stmt{'age'} <= $GLOBALS['third_dun_msg_set']:
        $dun_message = $GLOBALS['third_dun_msg_text'];
        break;
        case $stmt{'age'} <= $GLOBALS['fourth_dun_msg_set']:
        $dun_message = $GLOBALS['fourth_dun_msg_text'];
        break;
        case $stmt{'age'} >= $GLOBALS['fifth_dun_msg_set']:
        $dun_message = $GLOBALS['fifth_dun_msg_text'];
        break;
      }
    }
  }
  // Text only labels
  $label_addressee = xl('ADDRESSEE');
  $label_remitto = xl('REMIT TO');
  $label_chartnum = xl('Chart Number');
  if ($GLOBALS['show_insurance_name_on_custom_statement']) {
   if (strlen($stmt['insconum2']) !=0){
      $label_insinfo = xl('Insurance Companies '). $stmt['insconum1'] . ', '. $stmt['insconum2'];
   }
   else
   {
     $label_insinfo = xl('Insurance Company '). $stmt['insconum1'] ;
   }
  }else{
  $label_insinfo = xl('Insurance information on file');
  }
  $label_totaldue = xl('Total amount due');
  $label_payby = xl('If paying by');
  $label_cards = xl('VISA/MC/Discovery/HSA');
  $label_cardnum = xl('Card');
  $label_expiry = xl('Exp');
  $label_sign = xl('Signature');
  $label_retpay = xl('Please return this bottom part with your payment');
  $label_pgbrk = xl('STATEMENT SUMMARY');
  $label_visit = xl('Visit Date');
  $label_desc = xl('Description');
  $label_amt = xl('Amount');
  // This is the text for the top part of the page, up to but not
  // including the detail lines.  Some examples of variable fields are:
  //  %s    = string with no minimum width
  //  %9s   = right-justified string of 9 characters padded with spaces
  //  %-25s = left-justified string of 25 characters padded with spaces
  // Note that "\n" is a line feed (new line) character.
  // reformatted to handle i8n by tony
  $out  = "<div style='margin-left:60px;margin-top:20px;'><pre>";
  $out .= "\n";
  $out .= sprintf("_______________________ %s _______________________\n",$label_pgbrk);
  $out .= "\n";
  $out .= sprintf("%-11s %-46s %s\n",$label_visit,$label_desc,$label_amt);
  $out .= "\n";
  // This must be set to the number of lines generated above.
//

  $count = 6;
  $num_ages = 4;
  $aging = array();
  for ($age_index = 0; $age_index < $num_ages; ++$age_index) {
    $aging[$age_index] = 0.00;
  }
  $todays_time = strtotime(date('Y-m-d'));
  // This generates the detail lines.  Again, note that the values must be specified in the order used.
  foreach ($stmt['lines'] as $line) {
    if ($GLOBALS['use_custom_statement']) {
      $description = substr($line['desc'],0,30);
    } else {
      $description = $line['desc'];
    }
    $tmp = substr($description, 0, 14);
    if ($tmp == 'Procedure 9920' || $tmp == 'Procedure 9921' || $tmp == 'Procedure 9200' || $tmp == 'Procedure 9201')
      $description = str_replace("Procedure",xl('Office Visit').":",$description);
    //92002-14 are Eye Office Visit Codes
    $dos = $line['dos'];
    ksort($line['detail']);
      # Compute the aging bucket index and accumulate into that bucket.
    $age_in_days = (int) (($todays_time - strtotime($dos)) / (60 * 60 * 24));
    $age_index = (int) (($age_in_days - 1) / 30);
    $age_index = max(0, min($num_ages - 1, $age_index));
    $aging[$age_index] += $line['amount'] - $line['paid'];
    foreach ($line['detail'] as $dkey => $ddata) {
      $ddate = substr($dkey, 0, 10);
      if (preg_match('/^(\d\d\d\d)(\d\d)(\d\d)\s*$/', $ddate, $matches)) {
        $ddate = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
      }
      $amount = '';
      if ($ddata['pmt']) {
        $amount = sprintf("%.2f", 0 - $ddata['pmt']);
        $desc = xl('Paid') .' '. oeFormatShortDate($ddate) .': '. $ddata['src'].' '. $ddata['pmt_method'].' '. $ddata['insurance_company'];
      } else if ($ddata['rsn']) {
        if ($ddata['chg']) {
          $amount = sprintf("%.2f", $ddata['chg']);
          $desc = xl('Adj') .' '.  oeFormatShortDate($ddate) .': ' . $ddata['rsn'].' '.$ddata['pmt_method'].' '. $ddata['insurance_company'];
        } else {
          $desc = xl('Note') .' '. oeFormatShortDate($ddate) .': '. $ddata['rsn'].' '.$ddata['pmt_method'].' '. $ddata['insurance_company'];
        }
      } else if ($ddata['chg'] < 0) {
        $amount = sprintf("%.2f", $ddata['chg']);
        $desc = xl('Patient Payment');
      } else {
        $amount = sprintf("%.2f", $ddata['chg']);
        $desc = $description;
      }
      $out .= sprintf("%-10s  %-45s%8s\n", oeFormatShortDate($dos), $desc, $amount);
      $dos = '';
      ++$count;
    }
  }
  // This generates blank lines until we are at line 20.
  //  At line 20 we start middle third.
  while ($count++ < 16) $out .= "\n";
    # Generate the string of aging text.  This will look like:
    # Current xxx.xx / 31-60 x.xx / 61-90 x.xx / Over-90 xxx.xx
    # ....+....1....+....2....+....3....+....4....+....5....+....6....+
    #
  $ageline = xl('Current') .': ' . sprintf("%.2f", $aging[0]);
  for ($age_index = 1; $age_index < ($num_ages - 1); ++$age_index) {
    $ageline .= ' | ' . ($age_index * 30 + 1) . '-' . ($age_index * 30 + 30) . ':' .
                sprintf(" %.2f", $GLOBALS['gbl_currency_symbol'].''.$aging[$age_index]);
  }
  // Fixed text labels
  $label_ptname = xl('Name');
  $label_today = xl('Date');
  $label_due = xl('Due');
  $label_thanks = xl('Thank you for choosing');
  $label_call = xl('Please call if any of the above information is incorrect.');
  $label_prompt = xl('We appreciate prompt payment of balances due.');
  $label_dept = xl('Billing Department');
  $label_bill_phone = (!empty($GLOBALS['billing_phone_number']) ? $GLOBALS['billing_phone_number'] : $billing_phone );
  $label_appointments = xl('Future Appointments').':';
  // This is the top portion of the page.
  $out .= "\n\n\n";
  if(strlen($stmt['bill_note']) !=0 && $GLOBALS['statement_bill_note_print']) {
    $out .= sprintf("%-46s\n",$stmt['bill_note']);
    $count++;
  }
  if ($GLOBALS['use_dunning_message']) {
      $out .= sprintf("%-46s\n",$dun_message);
      $count++;
  }
  $out .= "\n";
  $out .= sprintf("%-s: %-25s %-s: %-14s %-s: %8s\n",$label_ptname,$stmt['patient'],
  $label_today,oeFormatShortDate($stmt['today']),$label_due,$stmt['amount']);
  $out .= sprintf("__________________________________________________________________\n");
  $out .= "\n";
  $out .= sprintf("%-s\n",$label_call);
  $out .= sprintf("%-s\n",$label_prompt);
  $out .= "\n";
  $out .= sprintf("%-s\n",$billing_contact);
  $out .= sprintf("  %-s %-25s\n",$label_dept,$label_bill_phone);
  if($GLOBALS['statement_message_to_patient']) {
    $out .= "\n";
    $statement_message = $GLOBALS['statement_msg_text'];
    $out .= sprintf("%-40s\n",$statement_message);
    $count++;
  }
  if($GLOBALS['show_aging_on_custom_statement']) {
    # code for ageing
    $ageline .= ' | ' . xl('Over') . ' ' . ($age_index * 30) .':'.
    sprintf(" %.2f", $aging[$age_index]);
    $out .= "\n" . $ageline . "\n\n";
    $count++;
  }
  if($GLOBALS['number_appointments_on_statement']!=0) {
    $out .= "\n";
    $num_appts = $GLOBALS['number_appointments_on_statement'];
    $next_day = mktime(0,0,0,date('m'),date('d')+1,date('Y'));
    # add one day to date so it will not get todays appointment
    $current_date2 = date('Y-m-d', $next_day);
    $events = fetchNextXAppts($current_date2,$stmt['pid'],$num_appts);
    $j=0;
    $out .= sprintf("%-s\n",$label_appointments);
    #loop to add the appointments
    for ($x = 1; $x <= $num_appts; $x++) {
      $next_appoint_date = oeFormatShortDate($events[$j]['pc_eventDate']);
      $next_appoint_time = substr($events[$j]['pc_startTime'],0,5);
      if(strlen(umname) != 0 ) {
        $next_appoint_provider = $events[$j]['ufname'] . ' ' . $events[$j]['umname'] . ' ' .  $events[$j]['ulname'];
      }
      else
      {
        $next_appoint_provider = $events[$j]['ufname'] . ' ' .  $events[$j]['ulname'];
      }
      if(strlen($next_appoint_time) != 0) {
        $label_plsnote[$j] = xlt('Date') . ': ' . text($next_appoint_date) . ' ' . xlt('Time') . ' ' . text($next_appoint_time) . ' ' . xlt('Provider') . ' ' . text($next_appoint_provider);
        $out .= sprintf("%-s\n",$label_plsnote[$j]);
      }
      $j++;
      $count++;
    }
  }
  while ($count++ < 29) $out .= "\n";
  $out .= sprintf("%-10s %s\n",null,$label_retpay);
  $out .= '</pre></div>';
  $out .= '<div style="width:7.0in;border-top:1pt dotted black;font-size:12px;margin:0px;"><br /><br />
      <table style="width:7in;margin-left:20px;"><tr><td style="width:4.5in;"><br />
 ';
  $out .= $label_payby.' '.$label_cards;
  $out .= "<br /><br />";
  $out .= $label_cardnum .': __________________________________  '.$label_expiry.': ___ / _____ <br /><br />';
  $out .= $label_sign .'  ____________________________<br />';
  $out .="      </td><td style=width:2.0in;vertical-align:middle;'>";
          $practice_cards = $GLOBALS['webroot']."/sites/" . $_SESSION['site_id'] . "/images/visa_mc_disc_credit_card_logos_176x35.gif";
          if (file_exists($GLOBALS['OE_SITE_DIR']."/images/visa_mc_disc_credit_card_logos_176x35.gif")) {
            $out .= "<img src='$practice_cards' style='width:100%;margin:4px auto;'><br /><p>\n".$label_totaldue.": ".$stmt['amount']."</p>";
          }
  $out .="</td></tr></table>";
    $out .= '</div><br />
   <pre>';
  if($stmt['to'][3]!='')//to avoid double blank lines the if condition is put.
  $out .= sprintf("   %-32s\n",$stmt['to'][3]);
  $out .= ' </pre>
  <div style="width:7in;border-top:1pt solid black;"><br />';
  $out .= " <table style='width:6.0in;margin-left:40px;'><tr>";
  $out .= '<td style="width:3.0in;"><b>'
      .$label_addressee.'</b><br />'
      .$stmt['to'][0].'<br />'
      .$stmt['to'][1].'<br />'
      .$stmt['to'][2].'
      </td>
      <td style="width:3.0in;"><b>'.$label_remitto.'</b><br />'
      .$remit_name.'<br />'
      .$remit_addr.'<br />'
      .$remit_csz.'
      </td>
      </tr></table>';
  $out .= "      </div></div>";
  $out .= "\014
  <br /><br />"; // this is a form feed
  echo $out;
  $output = ob_get_clean();
  return $output;
}


// This function builds a printable statement or collection letter from
// an associative array having the following keys:
//
//  today   = statement date yyyy-mm-dd
//  pid     = patient ID
//  patient = patient name
//  amount  = total amount due
//  to      = array of addressee name/address lines
//  lines   = array of lines, each with the following keys:
//    dos     = date of service yyyy-mm-dd
//    desc    = description
//    amount  = charge less adjustments
//    paid    = amount paid
//    notice  = 1 for first notice, 2 for second, etc.
//    detail  = associative array of details
//
// Each detail array is keyed on a string beginning with a date in
// yyyy-mm-dd format, or blanks in the case of the original charge
// items.  Its values are associative arrays like this:
//
//  pmt - payment amount as a positive number, only for payments
//  src - check number or other source, only for payments
//  chg - invoice line item amount amount, only for charges or
//        adjustments (adjustments may be zero)
//  rsn - adjustment reason, only for adjustments
//
// The returned value is a string that can be sent to a printer.
// This example is plain text, but if you are a hotshot programmer
// then you could make a PDF or PostScript or whatever peels your
// banana.  These strings are sent in succession, so append a form
// feed if that is appropriate.
//

// A sample of the text based format follows:

//[Your Clinic Name]             Patient Name          2009-12-29
//[Your Clinic Address]          Chart Number: 1848
//[City, State Zip]              Insurance information on file
//
//
//ADDRESSEE                      REMIT TO
//Patient Name                     [Your Clinic Name]
//patient address                  [Your Clinic Address]
//city, state zipcode              [City, State Zip]
//                                 If paying by VISA/MC/AMEX/Dis
//
//Card_____________________  Exp______ Signature___________________
//                     Return above part with your payment
//-----------------------------------------------------------------
//
//_______________________ STATEMENT SUMMARY _______________________
//
//Visit Date  Description                                    Amount
//
//2009-08-20  Procedure 99345                                198.90
//            Paid 2009-12-15:                               -51.50
//... more details ...
//...
//...
// skipping blanks in example
//
//
//Name: Patient Name              Date: 2009-12-29     Due:   147.40
//_________________________________________________________________
//
//Please call if any of the above information is incorrect
//We appreciate prompt payment of balances due
//
//[Your billing contact name]
//  Billing Department
//  [Your billing dept phone]

function create_statement($stmt) {
 if (! $stmt['pid']) return ""; // get out if no data

 #minimum_amount_to _print
 if ($stmt[amount] <= ($GLOBALS['minimum_amount_to_print']) && $GLOBALS['use_statement_print_exclusion']) {
    //increment whenever amount value of statement is less than minimum amount value,
    //to keep count of such ignored statements in final print
    $GLOBALS['stmts_below_minimum_amount']++;
    //also add corresponding patient name
    array_push($GLOBALS['pat_below_minimum_amount'], getPatientName($stmt['pid']));
    return "";
  }

 if ($stmt['statement_print'] == "NO" && $GLOBALS['use_statement_print_exclusion']) {
    //increment whenever the patient is set to no statement,
    //to keep count of such ignored statements in final print
    $GLOBALS['stmts_set_no_patient']++;
    //also add corresponding patient name
    array_push($GLOBALS['pat_set_no_stmt'], getPatientName($stmt['pid']));
    return "";
  };
 if ($GLOBALS['use_statement_print_exclusion'] && $GLOBALS['insurance_statement_exclude'] !=4 ){
    if ($GLOBALS['insurance_statement_exclude'] ==0 && $stmt['insurance_no_statement_print_pri']) {
      //increment whenever the insurance company does not allow statements,
      //to keep count of such ignored statements in final print
      $GLOBALS['stmts_not_allowed_insurance_company']++;
      //also add corresponding patient name
      array_push($GLOBALS['pat_not_allowed_insurance_company'], getPatientName($stmt['pid']));
      return "";
    }
    if ($GLOBALS['insurance_statement_exclude'] ==1 && $stmt['insurance_no_statement_print_sec']) {
      //increment whenever the insurance company does not allow statements,
      //to keep count of such ignored statements in final print
      $GLOBALS['stmts_not_allowed_insurance_company']++;
      //also add corresponding patient name
      array_push($GLOBALS['pat_not_allowed_insurance_company'], getPatientName($stmt['pid']));
      return "";
    }
    if ($GLOBALS['insurance_statement_exclude'] ==2 && $stmt['insurance_no_statement_print_tri']) {
      //increment whenever the insurance company does not allow statements,
      //to keep count of such ignored statements in final print
      $GLOBALS['stmts_not_allowed_insurance_company']++;
      //also add corresponding patient name
      array_push($GLOBALS['pat_not_allowed_insurance_company'], getPatientName($stmt['pid']));
      return "";
    }
    if ($GLOBALS['insurance_statement_exclude'] ==3) return "";
  }

 // Don't print if decreased
 if ($GLOBALS['disallow_print_deceased']) return "";

 // These are your clinics return address, contact etc.  Edit them.
 // TBD: read this from the facility table

 // Facility (service location)
  $atres = sqlStatement("select f.name,f.street,f.city,f.state,f.postal_code from facility f " .
    " left join users u on f.id=u.facility_id " .
    " left join  billing b on b.provider_id=u.id and b.pid = '".$stmt['pid']."' " .
    " where  service_location=1");
  $row = sqlFetchArray($atres);

 // Facility (service location)

 $clinic_name = "{$row['name']}";
 $clinic_addr = "{$row['street']}";
 $clinic_csz = "{$row['city']}, {$row['state']}, {$row['postal_code']}";


 // Billing location
 $remit_name = $clinic_name;
 $remit_addr = $clinic_addr;
 $remit_csz = $clinic_csz;

 // Contacts
  $atres = sqlStatement("select f.attn,f.phone from facility f " .
    " left join users u on f.id=u.facility_id " .
    " left join  billing b on b.provider_id=u.id and b.pid = '".$stmt['pid']."'  " .
    " where billing_location=1");
  $row = sqlFetchArray($atres);
 $billing_contact = "{$row['attn']}";
 $billing_phone = "{$row['phone']}";

 // dunning message setup

 // insurance has paid something
 // $stmt['age'] how old is the invoice
 // $stmt['dun_count'] number of statements run
 // $stmt['level_closed'] <= 3 insurance 4 = patient

if ($GLOBALS['use_dunning_message']) {

   if ($stmt['ins_paid'] != 0 || $stmt['level_closed'] == 4)  {

    // do collection messages
    switch ($stmt{'age'}) {
       case $stmt{'age'} <= $GLOBALS['first_dun_msg_set']:
          $dun_message = $GLOBALS['first_dun_msg_text'];
          break;
       case $stmt{'age'} <= $GLOBALS['second_dun_msg_set']:
          $dun_message = $GLOBALS['second_dun_msg_text'];
          break;
       case $stmt{'age'} <= $GLOBALS['third_dun_msg_set']:
          $dun_message = $GLOBALS['third_dun_msg_text'];
          break;
       case $stmt{'age'} <= $GLOBALS['fourth_dun_msg_set']:
          $dun_message = $GLOBALS['fourth_dun_msg_text'];
          break;
       case $stmt{'age'} >= $GLOBALS['fifth_dun_msg_set']:
          $dun_message = $GLOBALS['fifth_dun_msg_text'];
          break;
     }
    }
 }
 // Text only labels

 $label_addressee = xl('ADDRESSEE');
 $label_remitto = xl('REMIT TO');
 $label_chartnum = xl('Chart Number');
 if ($GLOBALS['show_insurance_name_on_custom_statement']) {
   if (strlen($stmt['insconum2']) !=0){
      $label_insinfo = xl('Insurance Companies '). $stmt['insconum1'] . ', '. $stmt['insconum2'];
   }
   else
   {
     $label_insinfo = xl('Insurance Company '). $stmt['insconum1'] ;
   }
 }else{
 $label_insinfo = xl('Insurance information on file');
 }

 $label_totaldue = xl('Total amount due');
 $label_payby = xl('If paying by');
 $label_cards = xl('VISA/MC/AMEX/Dis');
 $label_cardnum = xl('Card');
 $label_expiry = xl('Exp');
 $label_sign = xl('Signature');
 $label_retpay = xl('Return above part with your payment');
 $label_pgbrk = xl('STATEMENT SUMMARY');
 $label_visit = xl('Visit Date');
 $label_desc = xl('Description');
 $label_amt = xl('Amount');

 // This is the text for the top part of the page, up to but not
 // including the detail lines.  Some examples of variable fields are:
 //  %s    = string with no minimum width
 //  %9s   = right-justified string of 9 characters padded with spaces
 //  %-25s = left-justified string of 25 characters padded with spaces
 // Note that "\n" is a line feed (new line) character.
 // reformatted to handle i8n by tony

  $out = "\n\n";
  $providerNAME = getProviderName($stmt['providerID']);
  $out .= sprintf("%-30s %s %-s\n",$clinic_name,$stmt['patient'],$stmt['today']);
  $out .= sprintf("%-30s %s: %-s\n",$providerNAME,$label_chartnum,$stmt['pid']);
  $out .= sprintf("%-30s %s\n",$clinic_addr,$label_insinfo);
  $out .= sprintf("%-30s %-s: %-s\n",$clinic_csz,$label_totaldue,$stmt['amount']);
  $out .= "\n";
$out .= sprintf("       %-30s %-s\n",$label_addressee,$label_remitto);
$out .= sprintf("       %-30s %s\n",$stmt['to'][0],$remit_name);
$out .= sprintf("       %-30s %s\n",$stmt['to'][1],$remit_addr);
$out .= sprintf("       %-30s %s\n",$stmt['to'][2],$remit_csz);

if($stmt['to'][3]!='')//to avoid double blank lines the if condition is put.
    $out .= sprintf("   %-32s\n",$stmt['to'][3]);
$out .= sprintf("_________________________________________________________________\n");
$out .= "\n";
$out .= sprintf("%-32s\n",$label_payby.' '.$label_cards);
$out .= "\n";
$out .= sprintf("%s_____________________  %s______ %s___________________\n",
                $label_cardnum,$label_expiry,$label_sign);
  $out .= sprintf("-----------------------------------------------------------------\n");
$out .= sprintf("%-20s %s\n",null,$label_retpay);
$out .= "\n";
$out .= sprintf("_______________________ %s _______________________\n",$label_pgbrk);
$out .= "\n";
$out .= sprintf("%-11s %-46s %s\n",$label_visit,$label_desc,$label_amt);
$out .= "\n";

 // This must be set to the number of lines generated above.
 //
  $count = 25;
 $num_ages = 4;
 $aging = array();
 for ($age_index = 0; $age_index < $num_ages; ++$age_index) {
  $aging[$age_index] = 0.00;
 }
 $todays_time = strtotime(date('Y-m-d'));

 // This generates the detail lines.  Again, note that the values must
 // be specified in the order used.
 //


 foreach ($stmt['lines'] as $line) {
 if ($GLOBALS['use_custom_statement']) {
   $description = substr($line['desc'],0,30);

    }
else {
     $description = $line['desc'];

      }

  $tmp = substr($description, 0, 14);
  if ($tmp == 'Procedure 9920' || $tmp == 'Procedure 9921')
   $description = xl('Office Visit');


  $dos = $line['dos'];
  ksort($line['detail']);
  # Compute the aging bucket index and accumulate into that bucket.
  #
  $age_in_days = (int) (($todays_time - strtotime($dos)) / (60 * 60 * 24));
  $age_index = (int) (($age_in_days - 1) / 30);
  $age_index = max(0, min($num_ages - 1, $age_index));
  $aging[$age_index] += $line['amount'] - $line['paid'];

  foreach ($line['detail'] as $dkey => $ddata) {
   $ddate = substr($dkey, 0, 10);
   if (preg_match('/^(\d\d\d\d)(\d\d)(\d\d)\s*$/', $ddate, $matches)) {
    $ddate = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
   }
   $amount = '';

   if ($ddata['pmt']) {
    $amount = sprintf("%.2f", 0 - $ddata['pmt']);
    $desc = xl('Paid') .' '. oeFormatShortDate($ddate) .': '. $ddata['src'].' '. $ddata['pmt_method'].' '. $ddata['insurance_company'];
   } else if ($ddata['rsn']) {
    if ($ddata['chg']) {
     $amount = sprintf("%.2f", $ddata['chg']);
     $desc = xl('Adj') .' '.  oeFormatShortDate($ddate) .': ' . $ddata['rsn'].' '.$ddata['pmt_method'].' '. $ddata['insurance_company'];
    } else {
     $desc = xl('Note') .' '. oeFormatShortDate($ddate) .': '. $ddata['rsn'].' '.$ddata['pmt_method'].' '. $ddata['insurance_company'];
    }
   } else if ($ddata['chg'] < 0) {
    $amount = sprintf("%.2f", $ddata['chg']);
    $desc = xl('Patient Payment');
   } else {
    $amount = sprintf("%.2f", $ddata['chg']);
    $desc = $description;

   }

   $out .= sprintf("%-10s  %-45s%8s\n", oeFormatShortDate($dos), $desc, $amount);
   $dos = '';
   ++$count;
  }
 }

 // This generates blank lines until we are at line 42.
 //
  while ($count++ < 42) $out .= "\n";

 # Generate the string of aging text.  This will look like:
 # Current xxx.xx / 31-60 x.xx / 61-90 x.xx / Over-90 xxx.xx
 # ....+....1....+....2....+....3....+....4....+....5....+....6....+
 #
 $ageline = xl('Current') . ' ' . sprintf("%.2f", $aging[0]);
 for ($age_index = 1; $age_index < ($num_ages - 1); ++$age_index) {
  $ageline .= ' / ' . ($age_index * 30 + 1) . '-' . ($age_index * 30 + 30) .
   sprintf(" %.2f", $aging[$age_index]);
}

 // Fixed text labels
 $label_ptname = xl('Name');
 $label_today = xl('Date');
  $label_due = xl('Amount Due');
 $label_thanks = xl('Thank you for choosing');
  $label_call = xl('Please call if any of the above information is incorrect.');
  $label_prompt = xl('We appreciate prompt payment of balances due.');
 $label_dept = xl('Billing Department');
 $label_bill_phone = (!empty($GLOBALS['billing_phone_number']) ? $GLOBALS['billing_phone_number'] : $billing_phone );
 $label_appointments = xl('Future Appointments') .':';

 // This is the bottom portion of the page.
  $out .= "\n";
 if(strlen($stmt['bill_note']) !=0 && $GLOBALS['statement_bill_note_print']) {
   $out .= sprintf("%-46s\n",$stmt['bill_note']);
}
 if ($GLOBALS['use_dunning_message']) {
   $out .= sprintf("%-46s\n",$dun_message);
 }
 $out .= "\n";
 $out .= sprintf("%-s: %-25s %-s: %-14s %-s: %8s\n",$label_ptname,$stmt['patient'],
                 $label_today,oeFormatShortDate($stmt['today']),$label_due,$stmt['amount']);
 $out .= sprintf("__________________________________________________________________\n");
 $out .= "\n";
 $out .= sprintf("%-s\n",$label_call);
 $out .= sprintf("%-s\n",$label_prompt);
 $out .= "\n";
 $out .= sprintf("%-s\n",$billing_contact);
  $out .= sprintf("  %-s %-25s\n",$label_dept,$label_bill_phone);
if($GLOBALS['statement_message_to_patient']) {
 $out .= "\n";
 $statement_message = $GLOBALS['statement_msg_text'];
 $out .= sprintf("%-40s\n",$statement_message);
}
if($GLOBALS['show_aging_on_custom_statement']) {
# code for ageing
 $ageline .= ' / ' . xl('Over') . '-' . ($age_index * 30) .
  sprintf(" %.2f", $aging[$age_index]);
 $out .= "\n" . $ageline . "\n\n";

}
 if($GLOBALS['number_appointments_on_statement']!=0) {
  $out .= "\n";
  $num_appts = $GLOBALS['number_appointments_on_statement'];
  $next_day = mktime(0,0,0,date('m'),date('d')+1,date('Y'));
  # add one day to date so it will not get todays appointment
  $current_date2 = date('Y-m-d', $next_day);
  $events = fetchNextXAppts($current_date2,$stmt['pid'],$num_appts);
  $j=0;
  $out .= sprintf("%-s\n",$label_appointments);
  #loop to add the appointments
  for ($x = 1; $x <= $num_appts; $x++) {
   $next_appoint_date = oeFormatShortDate($events[$j]['pc_eventDate']);
   $next_appoint_time = substr($events[$j]['pc_startTime'],0,5);
   if(strlen(umname) != 0 ) {
      $next_appoint_provider = $events[$j]['ufname'] . ' ' . $events[$j]['umname'] . ' ' .  $events[$j]['ulname'];
   }
   else
   {
      $next_appoint_provider = $events[$j]['ufname'] . ' ' .  $events[$j]['ulname'];
   }
   if(strlen($next_appoint_time) != 0) {
      $label_plsnote[$j] = xlt('Date') . ': ' . text($next_appoint_date) . ' ' . xlt('Time') . ' ' . text($next_appoint_time) . ' ' . xlt('Provider') . ' ' . text($next_appoint_provider);
      $out .= sprintf("%-s\n",$label_plsnote[$j]);
   }
   $j++;
  }
 }
 $out .= "\014"; // this is a form feed

 return $out;
}
function osp_create_HTML_statement($stmt) {
    if (! $stmt['pid']) return ""; // get out if no data
    #minimum_amount_due_to _print
    if ($stmt['amount'] <= ($GLOBALS['minimum_amount_to_print']) && $GLOBALS['use_statement_print_exclusion']) return "";
    // Facility (service location)
    $atres = sqlStatement("select f.name,f.street,f.city,f.state,f.postal_code,f.attn,f.phone from facility f " .
            " left join users u on f.id=u.facility_id " .
            " left join  billing b on b.provider_id=u.id and b.pid = ? ".
            " where  service_location=1",array($stmt['pid']));
    $row = sqlFetchArray($atres);
    $clinic_name = "{$row['name']}";
    $clinic_addr = "{$row['street']}";
    $clinic_csz = "{$row['city']}, {$row['state']}, {$row['postal_code']}";
    // Contacts
    $billing_contact = "{$row['attn']}";
    $billing_phone = "{$row['phone']}";
    // Billing location
    $remit_name = $clinic_name;
    $remit_addr = $clinic_addr;
    $remit_csz = $clinic_csz;
    ob_start();
    ?><div style="padding-left:25px;">
  <?php
  $find_provider = sqlQuery("SELECT * FROM form_encounter " .
        "WHERE pid = ? AND encounter = ? " .
        "ORDER BY id DESC LIMIT 1", array($stmt['pid'],$stmt['encounter']) );
  $providerID = $find_provider['provider_id'];
  echo report_header_2($stmt,$direction,$providerID);
  // dunning message setup
  // insurance has paid something
  // $stmt['age'] how old is the invoice
  // $stmt['dun_count'] number of statements run
  // $stmt['level_closed'] <= 3 insurance 4 = patient
  if ($GLOBALS['use_dunning_message']) {
    if ($stmt['ins_paid'] != 0 || $stmt['level_closed'] == 4)  {
      // do collection messages
      switch ($stmt{'age'}) {
        case $stmt{'age'} <= $GLOBALS['first_dun_msg_set']:
        $dun_message = $GLOBALS['first_dun_msg_text'];
        break;
        case $stmt{'age'} <= $GLOBALS['second_dun_msg_set']:
        $dun_message = $GLOBALS['second_dun_msg_text'];
        break;
        case $stmt{'age'} <= $GLOBALS['third_dun_msg_set']:
        $dun_message = $GLOBALS['third_dun_msg_text'];
        break;
        case $stmt{'age'} <= $GLOBALS['fourth_dun_msg_set']:
        $dun_message = $GLOBALS['fourth_dun_msg_text'];
        break;
        case $stmt{'age'} >= $GLOBALS['fifth_dun_msg_set']:
        $dun_message = $GLOBALS['fifth_dun_msg_text'];
        break;
      }
    }
  }
  // Text only labels
  $label_addressee = xl('ADDRESSEE');
  $label_remitto = xl('REMIT TO');
  $label_chartnum = xl('Chart Number');
 if ($GLOBALS['show_insurance_name_on_custom_statement']) {
   if (strlen($stmt['insconum2']) !=0){
      $label_insinfo = xl('Insurance Companies '). $stmt['insconum1'] . ', '. $stmt['insconum2'];
   }
   else
   {
     $label_insinfo = xl('Insurance Company '). $stmt['insconum1'] ;
   }
 }else{
    $label_insinfo = xl('Insurance information on file');
 }
  $label_totaldue = xl('Total amount due');
  $label_payby = xl('If paying by');
  $label_cards = xl('VISA/MC/Discovery/HSA');
  $label_cardnum = xl('Card');
  $label_expiry = xl('Exp');
  $label_sign = xl('Signature');
  $label_retpay = xl('Please fill in credit information and send for review.');
  $label_pgbrk = xl('STATEMENT SUMMARY');
  $label_visit = xl('Visit Date');
  $label_desc = xl('Description');
  $label_amt = xl('Amount');
  // This is the text for the top part of the page, up to but not
  // including the detail lines.  Some examples of variable fields are:
  //  %s    = string with no minimum width
  //  %9s   = right-justified string of 9 characters padded with spaces
  //  %-25s = left-justified string of 25 characters padded with spaces
  // Note that "\n" is a line feed (new line) character.
  // reformatted to handle i8n by tony
  $out  = "<div style='margin-left:60px;margin-top:0px;'>";
  $out .= "\n";
  $out .= sprintf("_______________________ %s _______________________\n",$label_pgbrk);
  $out .= "\n";
  $out .= sprintf("%-11s %-46s %s\n",$label_visit,$label_desc,$label_amt);
  $out .= "\n";
  // This must be set to the number of lines generated above.
  //
  $count = 6;
  $num_ages = 4;
  $aging = array();
  for ($age_index = 0; $age_index < $num_ages; ++$age_index) {
    $aging[$age_index] = 0.00;
  }
  $todays_time = strtotime(date('Y-m-d'));
  // This generates the detail lines.  Again, note that the values must be specified in the order used.
  foreach ($stmt['lines'] as $line) {
    if ($GLOBALS['use_custom_statement']) {
      $description = substr($line['desc'],0,30);
    } else {
      $description = $line['desc'];
    }
    $tmp = substr($description, 0, 14);
    if ($tmp == 'Procedure 9920' || $tmp == 'Procedure 9921' || $tmp == 'Procedure 9200' || $tmp == 'Procedure 9201')
      $description = str_replace("Procedure",xl('Office Visit').":",$description);
    //92002-14 are Eye Office Visit Codes
    $dos = $line['dos'];
    ksort($line['detail']);
      # Compute the aging bucket index and accumulate into that bucket.
    $age_in_days = (int) (($todays_time - strtotime($dos)) / (60 * 60 * 24));
    $age_index = (int) (($age_in_days - 1) / 30);
    $age_index = max(0, min($num_ages - 1, $age_index));
    $aging[$age_index] += $line['amount'] - $line['paid'];
    foreach ($line['detail'] as $dkey => $ddata) {
      $ddate = substr($dkey, 0, 10);
      if (preg_match('/^(\d\d\d\d)(\d\d)(\d\d)\s*$/', $ddate, $matches)) {
        $ddate = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
      }
      $amount = '';
      if ($ddata['pmt']) {
        $amount = sprintf("%.2f", 0 - $ddata['pmt']);
        $desc = xl('Paid') .' '. oeFormatShortDate($ddate) .': '. $ddata['src'].' '. $ddata['pmt_method'].' '. $ddata['insurance_company'];
      } else if ($ddata['rsn']) {
        if ($ddata['chg']) {
          $amount = sprintf("%.2f", $ddata['chg']);
          $desc = xl('Adj') .' '.  oeFormatShortDate($ddate) .': ' . $ddata['rsn'].' '.$ddata['pmt_method'].' '. $ddata['insurance_company'];
        } else {
          $desc = xl('Note') .' '. oeFormatShortDate($ddate) .': '. $ddata['rsn'].' '.$ddata['pmt_method'].' '. $ddata['insurance_company'];
        }
      } else if ($ddata['chg'] < 0) {
        $amount = sprintf("%.2f", $ddata['chg']);
        $desc = xl('Patient Payment');
      } else {
        $amount = sprintf("%.2f", $ddata['chg']);
        $desc = $description;
      }
      $out .= sprintf("%-10s  %-45s%8s\n", oeFormatShortDate($dos), $desc, $amount);
      $dos = '';
      ++$count;
    }
  }
  // This generates blank lines until we are at line 20.
  //  At line 20 we start middle third.
  //while ($count++ < 16) $out .= "\n";
    # Generate the string of aging text.  This will look like:
    # Current xxx.xx / 31-60 x.xx / 61-90 x.xx / Over-90 xxx.xx
    # ....+....1....+....2....+....3....+....4....+....5....+....6....+
    #
  $ageline = xl('Current') .': ' . sprintf("%.2f", $aging[0]);
  for ($age_index = 1; $age_index < ($num_ages - 1); ++$age_index) {
    $ageline .= ' | ' . ($age_index * 30 + 1) . '-' . ($age_index * 30 + 30) . ':' .
                sprintf(" %.2f", $GLOBALS['gbl_currency_symbol'].''.$aging[$age_index]);
  }
  // Fixed text labels
  $label_ptname = xl('Name');
  $label_today = xl('Date');
  $label_due = xl('Due');
  $label_thanks = xl('Thank you for choosing');
  $label_call = xl('Please call or message if any of the above information is incorrect.');
  $label_prompt = xl('We appreciate prompt payment of balances due.');
  $label_dept = xl('Billing Department');
  $label_bill_phone = (!empty($GLOBALS['billing_phone_number']) ? $GLOBALS['billing_phone_number'] : $billing_phone );
  $label_appointments = xl('Future Appointments').':';
  // This is the top portion of the page.
  $out .= "\n";
  if(strlen($stmt['bill_note']) !=0 && $GLOBALS['statement_bill_note_print']) {
    $out .= sprintf("%-46s\n",$stmt['bill_note']);
    $count++;
  }
  if ($GLOBALS['use_dunning_message']) {
      $out .= sprintf("%-46s\n",$dun_message);
      $count++;
  }
  $out .= "\n";
  $out .= sprintf("%-s: %-25s %-s: %-14s %-s: %8s\n",$label_ptname,$stmt['patient'],
  $label_today,oeFormatShortDate($stmt['today']),$label_due,$stmt['amount']);
  $out .= sprintf("__________________________________________________________________\n");
  $out .= "\n";
  $out .= sprintf("%-s\n",$label_call);
  $out .= sprintf("%-s\n",$label_prompt);
  $out .= "\n";
  $out .= sprintf("%-s\n",$billing_contact);
  $out .= sprintf("  %-s %-25s\n",$label_dept,$label_bill_phone);
  if($GLOBALS['statement_message_to_patient']) {
    $out .= "\n";
    $statement_message = $GLOBALS['statement_msg_text'];
    $out .= sprintf("%-40s\n",$statement_message);
    $count++;
  }
  if($GLOBALS['show_aging_on_custom_statement']) {
    # code for ageing
    $ageline .= ' | ' . xl('Over') . ' ' . ($age_index * 30) .':'.
    sprintf(" %.2f", $aging[$age_index]);
    $out .= "\n" . $ageline . "\n\n";
    $count++;
  }
  if($GLOBALS['number_appointments_on_statement']!=0) {
    $out .= "\n";
    $num_appts = $GLOBALS['number_appointments_on_statement'];
    $next_day = mktime(0,0,0,date('m'),date('d')+1,date('Y'));
    # add one day to date so it will not get todays appointment
    $current_date2 = date('Y-m-d', $next_day);
    $events = fetchNextXAppts($current_date2,$stmt['pid'],$num_appts);
    $j=0;
    $out .= sprintf("%-s\n",$label_appointments);
    #loop to add the appointments
    for ($x = 1; $x <= $num_appts; $x++) {
      $next_appoint_date = oeFormatShortDate($events[$j]['pc_eventDate']);
      $next_appoint_time = substr($events[$j]['pc_startTime'],0,5);
      if(strlen(umname) != 0 ) {
        $next_appoint_provider = $events[$j]['ufname'] . ' ' . $events[$j]['umname'] . ' ' .  $events[$j]['ulname'];
      }
      else
      {
        $next_appoint_provider = $events[$j]['ufname'] . ' ' .  $events[$j]['ulname'];
      }
      if(strlen($next_appoint_time) != 0) {
        $label_plsnote[$j] = xlt('Date') . ': ' . text($next_appoint_date) . ' ' . xlt('Time') . ' ' . text($next_appoint_time) . ' ' . xlt('Provider') . ' ' . text($next_appoint_provider);
        $out .= sprintf("%-s\n",$label_plsnote[$j]);
      }
      $j++;
      $count++;
    }
  }
 // while ($count++ < 29) $out .= "\n";
  $out .= sprintf("%-10s %s\n",null,$label_retpay);
  $out .= '</pre></div>';
  $out .= '<div style="width:7.0in;border-top:1pt dotted black;font-size:12px;margin:0px;"><br /><br />
      <table style="width:8in;margin-left:20px;"><tr><td style="width:4.5in;"><br />
 ';
  $out .= $label_payby.' '.$label_cards;
  $out .= "<br /><br />";
  $out .= $label_cardnum .': {TextInput}  '.$label_expiry.': {smTextInput} / {smTextInput} <br /><br />';
  $out .= $label_sign .'  {PatientSignature}<br />';
  $out .="      </td><td style=width:2.0in;vertical-align:middle;'>";
          $practice_cards = $GLOBALS['webroot']."/sites/" . $_SESSION['site_id'] . "/images/visa_mc_disc_credit_card_logos_176x35.gif";
          if (file_exists($GLOBALS['OE_SITE_DIR']."/images/visa_mc_disc_credit_card_logos_176x35.gif")) {
            //$out .= "<img onclick='getPayment()' src='$practice_cards' style='width:100%;margin:4px auto;'><br /><p>\n".$label_totaldue.": ".$stmt['amount']."</p>";
            $out .= "<br /><p>".$label_totaldue.": ".$stmt['amount']."</p>";
          }
  $out .="</td></tr></table>";
    $out .= '</div><br />';
  if($stmt['to'][3]!='')//to avoid double blank lines the if condition is put.
  $out .= sprintf("   %-32s\n",$stmt['to'][3]);
  $out .= ' </pre>
  <div style="width:8in;border-top:1pt solid black;"><br />';
  $out .= " <table style='width:6.0in;margin-left:40px;'><tr>";
  $out .= '<td style="width:3.0in;"><b>'
      .$label_addressee.'</b><br />'
      .$stmt['to'][0].'<br />'
      .$stmt['to'][1].'<br />'
      .$stmt['to'][2].'
      </td>
      <td style="width:3.0in;"><b>'.$label_remitto.'</b><br />'
      .$remit_name.'<br />'
      .$remit_addr.'<br />'
      .$remit_csz.'
      </td>
      </tr></table>';
  $out .= "      </div></div>";
  $out .= "\014
  <br /><br />"; // this is a form feed
  echo $out;
  $output = ob_get_clean();
  return $output;
}
?>
