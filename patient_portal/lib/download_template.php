<?php
/**
 * Document Template Download Module.
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 * Copyright (C) 2013-2014 Rod Roark <rod@sunsetsystems.com>
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
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @author Rod Roark <rod@sunsetsystems.com>
 * @link http://librehealth.io
 *
 * Please help the overall project by sending changes you make to the authors and to the LibreHealth EHR community.
 *
 */

// This module downloads a specified document template to the browser after
// substituting relevant patient data into its variables.
require_once ( dirname( __file__ ) . "/../verify_session.php" );
require_once($GLOBALS['srcdir'] . '/acl.inc');
require_once($GLOBALS['srcdir'] . '/htmlspecialchars.inc.php');
require_once($GLOBALS['srcdir'] . '/formdata.inc.php');
require_once($GLOBALS['srcdir'] . '/formatting.inc.php');
require_once($GLOBALS['srcdir'] . '/appointments.inc.php');
require_once($GLOBALS['srcdir'] . '/options.inc.php');

$form_filename = $_POST['docid'];
$pid = $_POST['pid'];
//$user = strip_escape_custom($_POST['user']);

$nextLocation = 0;      // offset to resume scanning
$keyLocation  = false;  // offset of a potential {string} to replace
$keyLength    = 0;      // length of {string} to replace
$groupLevel   = 0;      // 0 if not in a {GRP} section
$groupCount   = 0;      // 0 if no items in the group yet
$itemSeparator = '; ';  // separator between group items
$tcnt=$grcnt=$ckcnt=0;
$html_flag = false;

// Flags to ignore new lines

// Check if the current location has the specified {string}.
function keySearch(&$s, $key)
{
  global $keyLocation, $keyLength;
  $keyLength = strlen($key);
    if ($keyLength == 0) {
        return false;
    }

  return $key == substr($s, $keyLocation, $keyLength);
}

// Replace the {string} at the current location with the specified data.
// Also update the location to resume scanning accordingly.
function keyReplace(&$s, $data)
{
  global $keyLocation, $keyLength, $nextLocation;
  $nextLocation = $keyLocation + strlen($data);
  return substr($s, 0, $keyLocation) . $data . substr($s, $keyLocation + $keyLength);
}

// Do some final processing of field data before it's put into the document.
function dataFixup($data, $title = '')
{
  global $groupLevel, $groupCount, $itemSeparator;
  if ($data !== '') {
    // Replace some characters that can mess up XML without assuming XML content type.
    $data = str_replace('&', '[and]'    , $data);
    $data = str_replace('<', '[less]'   , $data);
    $data = str_replace('>', '[greater]', $data);
    // If in a group, include labels and separators.
    if ($groupLevel) {
            if ($title !== '') {
                $data = $title . ': ' . $data;
            }

            if ($groupCount) {
                $data = $itemSeparator . $data;
            }

      ++$groupCount;
    }
  }
  return $data;
}

// Return a string naming all issues for the specified patient and issue type.
function getIssues($type)
{
  // global $itemSeparator;
  $tmp = '';
    $lres = sqlStatement("SELECT title, comments FROM lists WHERE " . "pid = ? AND type = ? AND enddate IS NULL " . "ORDER BY begdate", array(
        $GLOBALS['pid'],
        $type
    ));
  while ($lrow = sqlFetchArray($lres)) {
        if ($tmp) {
            $tmp .= '; ';
        }

    $tmp .= $lrow['title'];
        if ($lrow['comments']) {
            $tmp .= ' (' . $lrow['comments'] . ')';
        }
  }
  return $tmp;
}

// Top level function for scanning and replacement of a file's contents.
function doSubs($s)
{
  global $ptrow, $hisrow, $enrow, $nextLocation, $keyLocation, $keyLength;
  global $groupLevel, $groupCount, $itemSeparator, $pid, $encounter;
  global $tcnt,$grcnt,$ckcnt;
  global $html_flag;

  $nextLocation = 0;
  $groupLevel   = 0;
  $groupCount   = 0;

  while (($keyLocation = strpos($s, '{', $nextLocation)) !== false) {
    $nextLocation = $keyLocation + 1;

    if (keySearch($s, '{PatientSignature}')) {
        $fn = $GLOBALS['web_root'] . '/patient_portal/sign/assets/signhere.png';
        $sigfld = '<span>';
        $sigfld .= '<img style="cursor:pointer;color:red" class="signature" type="patient-signature" id="patientSignature" onclick="getSignature(this)"' . 'alt="' . xla("Click in signature on file") . '" src="' . $fn . '">';
        $sigfld .= '</span>';
        $s = keyReplace($s,$sigfld);
    } else if (keySearch($s, '{AdminSignature}')) {
        $fn = $GLOBALS['web_root'] . '/patient_portal/sign/assets/signhere.png';
        $sigfld = '<span>';
        $sigfld .= '<img style="cursor:pointer;color:red" class="signature" type="admin-signature" id="adminSignature" onclick="getSignature(this)"' . 'alt="' . xla("Click in signature on file") . '" src="' . $fn . '">';
        $sigfld .= '</span>';
        $s = keyReplace($s,$sigfld);
   } else if (keySearch($s, '{ParseAsHTML}')) {
        $html_flag = true;
        $s = keyReplace($s, "");
   } else if (keySearch($s, '{TextInput}')) {
        $sigfld = '<span>';
        $sigfld .= '<input class="templateInput" type="text" style="color:black;" data-textvalue="" onblur="templateText(this);">';
        $sigfld .= '</span>';
        $s = keyReplace($s,$sigfld);
   } else if (keySearch($s, '{smTextInput}')) {
        $sigfld = '<span>';
        $sigfld .= '<input class="templateInput" type="text" style="color:black;max-width:50px;" data-textvalue="" onblur="templateText(this);">';
        $sigfld .= '</span>';
        $s = keyReplace($s,$sigfld);
   } else if (keySearch($s, '{CheckMark}')) {
        $ckcnt++;
        $sigfld = '<span class="checkMark" data-id="check'.$ckcnt.'">';
        $sigfld .= '<input type="checkbox"  id="check'.$ckcnt.'" data-value="" onclick="templateCheckMark(this);">';
        $sigfld .= '</span>';
        $s = keyReplace($s,$sigfld);
   } else if (keySearch($s, '{ynRadioGroup}')) {
        $grcnt++;
        $sigfld = '<span class="ynuGroup" data-value="N/A" data-id="'.$grcnt.'" id="rgrp'.$grcnt.'">';
        $sigfld .= '<label><input onclick="templateRadio(this)" type="radio" name="ynradio'.$grcnt.'" data-id="'.$grcnt.'" value="Yes">'.xlt("Yes").'</label>';
          $sigfld .= '<label><input onclick="templateRadio(this)" type="radio" name="ynradio'.$grcnt.'" data-id="'.$grcnt.'" value="No">'.xlt("No").'</label>';
        $sigfld .= '<label><input onclick="templateRadio(this)" type="radio" name="ynradio'.$grcnt.'" checked="checked" data-id="'.$grcnt.'" value="N/A">N/A</label>';
        $sigfld .= '</span>';
        $s = keyReplace($s,$sigfld);
   } else if (keySearch($s, '{PatientName}')) {
      $tmp = $ptrow['fname'];
      if ($ptrow['mname']) {
            if ($tmp) {
                $tmp .= ' ';
            }

        $tmp .= $ptrow['mname'];
      }
      if ($ptrow['lname']) {
          if ($tmp) {
              $tmp .= ' ';
          }

        $tmp .= $ptrow['lname'];
      }
      $s = keyReplace($s, dataFixup($tmp, xl('Name')));
    } else if (keySearch($s, '{PatientID}')) {
      $s = keyReplace($s, dataFixup($ptrow['pubpid'], xl('Chart ID')));
    } else if (keySearch($s, '{Address}')) {
      $s = keyReplace($s, dataFixup($ptrow['street'], xl('Street')));
    } else if (keySearch($s, '{City}')) {
      $s = keyReplace($s, dataFixup($ptrow['city'], xl('City')));
    } else if (keySearch($s, '{State}')) {
      $s = keyReplace($s, dataFixup(getListItemTitle('state', $ptrow['state']), xl('State')));
    } else if (keySearch($s, '{Zip}')) {
      $s = keyReplace($s, dataFixup($ptrow['postal_code'], xl('Postal Code')));
    } else if (keySearch($s, '{PatientPhone}')) {
      $ptphone = $ptrow['phone_contact'];
      if (empty($ptphone)) {
          $ptphone = $ptrow['phone_home'];
    }

      if (empty($ptphone)) {
          $ptphone = $ptrow['phone_cell'];
    }

      if (empty($ptphone)) {
          $ptphone = $ptrow['phone_biz'];
    }

      if (preg_match("/([2-9]\d\d)\D*(\d\d\d)\D*(\d\d\d\d)/", $ptphone, $tmp)) {
        $ptphone = '(' . $tmp[1] . ')' . $tmp[2] . '-' . $tmp[3];
      }
      $s = keyReplace($s, dataFixup($ptphone, xl('Phone')));
    } else if (keySearch($s, '{PatientDOB}')) {
      $s = keyReplace($s, dataFixup(oeFormatShortDate($ptrow['DOB']), xl('Birth Date')));
    } else if (keySearch($s, '{PatientSex}')) {
      $s = keyReplace($s, dataFixup(getListItemTitle('sex', $ptrow['sex']), xl('Sex')));
    } else if (keySearch($s, '{DOS}')) {
      //$s = @keyReplace($s, dataFixup(oeFormatShortDate(substr($enrow['date'], 0, 10)), xl('Service Date'))); // changed DOS to todays date- add future enc DOS
      $s = @keyReplace($s, dataFixup(oeFormatShortDate(substr(date("Y-m-d"), 0, 10)), xl('Service Date')));
    } else if (keySearch($s, '{ChiefComplaint}')) {
      $cc = $enrow['reason'];
      $patientid = $ptrow['pid'];
      $DOS = substr($enrow['date'], 0, 10);
      // Prefer appointment comment if one is present.
      $evlist = fetchEvents($DOS, $DOS, " AND pc_pid = '$patientid' ");
      foreach ($evlist as $tmp) {
        if ($tmp['pc_pid'] == $pid && !empty($tmp['pc_hometext'])) {
          $cc = $tmp['pc_hometext'];
        }
      }
      $s = keyReplace($s, dataFixup($cc, xl('Chief Complaint')));
    } else if (keySearch($s, '{ReferringDOC}')) {
      $tmp = empty($ptrow['ur_fname']) ? '' : $ptrow['ur_fname'];
      if (!empty($ptrow['ur_mname'])) {
          if ($tmp) {
              $tmp .= ' ';
          }

        $tmp .= $ptrow['ur_mname'];
      }
      if (!empty($ptrow['ur_lname'])) {
          if ($tmp) {
              $tmp .= ' ';
          }

        $tmp .= $ptrow['ur_lname'];
      }
      $s = keyReplace($s, dataFixup($tmp, xl('Referer')));
    } else if (keySearch($s, '{Allergies}')) {
      $tmp = generate_plaintext_field(array(
          'data_type' => '24',
          'list_id' => ''
      ), '');
      $s = keyReplace($s, dataFixup($tmp, xl('Allergies')));
    } else if (keySearch($s, '{Medications}')) {
      $s = keyReplace($s, dataFixup(getIssues('medication'), xl('Medications')));
    } else if (keySearch($s, '{ProblemList}')) {
      $s = keyReplace($s, dataFixup(getIssues('medical_problem'), xl('Problem List')));
    }

    // This tag indicates the fields from here until {/GRP} are a group of fields
    // separated by semicolons.  Fields with no data are omitted, and fields with
    // data are prepended with their field label from the form layout.
    else if (keySearch($s, '{GRP}')) {
      ++$groupLevel;
      $groupCount = 0;
      $s = keyReplace($s, '');
    } else if (keySearch($s, '{/GRP}')) {
        if ($groupLevel > 0) {
            -- $groupLevel;
    }

      $s = keyReplace($s, '');
    }

    // This is how we specify the separator between group items in a way that
    // is independent of the document format. Whatever is between {ITEMSEP} and
    // {/ITEMSEP} is the separator string.  Default is "; ".
    else if (preg_match('/^\{ITEMSEP\}(.*?)\{\/ITEMSEP\}/', substr($s, $keyLocation), $matches)) {
      $itemSeparator = $matches[1];
      $keyLength = strlen($matches[0]);
      $s = keyReplace($s, '');
    }

    // This handles keys like {LBFxxx:fieldid} for layout-based encounter forms.
    else if (preg_match('/^\{(LBF\w+):(\w+)\}/', substr($s, $keyLocation), $matches)) {
      $formname = $matches[1];
      $fieldid  = $matches[2];
      $keyLength = 3 + strlen($formname) + strlen($fieldid);
      $data = '';
      $currvalue = '';
      $title = '';
      $frow = sqlQuery("SELECT * FROM layout_options " .
        "WHERE form_id = ? AND field_id = ? LIMIT 1",
        array($formname, $fieldid));
      if (!empty($frow)) {
        $ldrow = sqlQuery("SELECT ld.field_value " .
          "FROM lbf_data AS ld, forms AS f WHERE " .
          "f.pid = ? AND f.encounter = ? AND f.formdir = ? AND f.deleted = 0 AND " .
          "ld.form_id = f.form_id AND ld.field_id = ? " .
          "ORDER BY f.form_id DESC LIMIT 1",
          array($pid, $encounter, $formname, $fieldid));
        if (!empty($ldrow)) {
          $currvalue = $ldrow['field_value'];
          $title = $frow['title'];
        }
        if ($currvalue !== '') {
          $data = generate_plaintext_field($frow, $currvalue);
        }
      }
      $s = keyReplace($s, dataFixup($data, $title));
    }

    // This handles keys like {DEM:fieldid} and {HIS:fieldid}.
    else if (preg_match('/^\{(DEM|HIS):(\w+)\}/', substr($s, $keyLocation), $matches)) {
      $formname = $matches[1];
      $fieldid  = $matches[2];
      $keyLength = 3 + strlen($formname) + strlen($fieldid);
      $data = '';
      $currvalue = '';
      $title = '';
      $frow = sqlQuery("SELECT * FROM layout_options " .
        "WHERE form_id = ? AND field_id = ? LIMIT 1",
        array($formname, $fieldid));
      if (!empty($frow)) {
        $tmprow = $formname == 'DEM' ? $ptrow : $hisrow;
        if (isset($tmprow[$fieldid])) {
          $currvalue = $tmprow[$fieldid];
          $title = $frow['title'];
        }
        if ($currvalue !== '') {
          $data = generate_plaintext_field($frow, $currvalue);
        }
      }
      $s = keyReplace($s, dataFixup($data, $title));
    }

  } // End if { character found.

  return $s;
}

// Get patient demographic info.
$ptrow = sqlQuery("SELECT pd.*, " .
  "ur.fname AS ur_fname, ur.mname AS ur_mname, ur.lname AS ur_lname, ur.title AS ur_title, ur.specialty AS ur_specialty " .
  "FROM patient_data AS pd " .
  "LEFT JOIN users AS ur ON ur.id = pd.ref_providerID " .
  "WHERE pd.pid = ?", array($pid));

$hisrow = sqlQuery("SELECT * FROM history_data WHERE pid = ? " .
  "ORDER BY date DESC LIMIT 1", array($pid));

$enrow = array();

// Get some info for the currently selected encounter.
if ($encounter) {
  $enrow = sqlQuery("SELECT * FROM form_encounter WHERE pid = ? AND " .
    "encounter = ?", array($pid, $encounter));
}

$templatedir   = $GLOBALS['OE_SITE_DIR'] .  '/documents/onsite_portal_documents/templates';
$templatepath  = "$templatedir/$form_filename";
// test if this is folder with template, if not, must be for a specific patient
 if( ! file_exists($templatepath) ){
    $templatepath  = "$templatedir/" . $pid . "/$form_filename";
 }

// Create a temporary file to hold the output.
$fname = tempnam($GLOBALS['temporary_files_dir'], 'OED');

// Get mime type in a way that works with old and new PHP releases.
$mimetype = 'application/octet-stream';
$ext = strtolower(array_pop((explode('.', $fname))));
if ('dotx' == $ext) {
  // PHP does not seem to recognize this type.
  $mimetype = 'application/msword';
} else if (function_exists('finfo_open')) {
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimetype = finfo_file($finfo, $templatepath);
  finfo_close($finfo);
} else if (function_exists('mime_content_type')) {
  $mimetype = mime_content_type($templatepath);
} else {
    if ('doc' == $ext) {
        $mimetype = 'application/msword';
    } else if ('dot' == $ext) {
        $mimetype = 'application/msword';
    } else if ('htm' == $ext) {
        $mimetype = 'text/html';
    } else if ('html' == $ext) {
        $mimetype = 'text/html';
    } else if ('odt' == $ext) {
        $mimetype = 'application/vnd.oasis.opendocument.text';
    } else if ('ods' == $ext) {
        $mimetype = 'application/vnd.oasis.opendocument.spreadsheet';
    } else if ('ott' == $ext) {
        $mimetype = 'application/vnd.oasis.opendocument.text';
    } else if ('pdf' == $ext) {
        $mimetype = 'application/pdf';
    } else if ('ppt' == $ext) {
        $mimetype = 'application/vnd.ms-powerpoint';
    } elseif ('ps' == $ext) {
        $mimetype = 'application/postscript';
    } else if ('rtf' == $ext) {
        $mimetype = 'application/rtf';
    } else if ('txt' == $ext) {
        $mimetype = 'text/plain';
    } else if ('xls' == $ext) {
        $mimetype = 'application/vnd.ms-excel';
}
}

$zipin = new ZipArchive();
if ($zipin->open($templatepath) === true) {
    $xml = '';
  // Must be a zip archive.
    $zipout = new ZipArchive();
  $zipout->open($fname, ZipArchive::OVERWRITE);
  for ($i = 0; $i < $zipin->numFiles; ++$i) {
    $ename = $zipin->getNameIndex($i);
    $edata = $zipin->getFromIndex($i);
    $edata = doSubs($edata);
    $xml .= $edata;
    $zipout->addFromString($ename, $edata);
  }
  $zipout->close();
  $zipin->close();
  $html = nl2br($xml);
} else {
  // Not a zip archive.
  $edata = file_get_contents($templatepath);
  $edata = doSubs($edata);
    if ($html_flag) { // return raw html template
        $html = $edata;
    } else { // add br for lf in text template

  $html = nl2br($edata);
    }
}
echo  $html;
?>
