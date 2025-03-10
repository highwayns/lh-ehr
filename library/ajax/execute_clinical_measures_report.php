<?php
/**
 * Run a Measures Engine Report.
 *
 * Copyright (C) 2012      Brady Miller <brady@sparmy.com>
 * Copyright (C) 2016      Suncoast Connection
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @link    http://SuncoastConnection.com
 * @author  Brady Miller <brady@sparmy.com>
 * @author  Bryan lee <leebc 11 at acm dot org>
 * @author  Sam Likins <sam.likins@wsi-services.com>
 */

// SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;

// STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;

require_once '../../interface/globals.php';
require_once $srcdir.'/clinical_rules.php';
require_once("$srcdir/CsrfToken.php");

if (!empty($_POST)) {
  if (!isset($_POST['token'])) {
    CsrfToken::noTokenFoundError();
  } else if (!(CsrfToken::verifyCsrfToken($_POST['token']))) {
      die('Authentication failed.');
  }
}
// To improve performance and not freeze the session when running this
// report, turn off session writing. Note that php session variables
// can not be modified after the line below. So, if need to do any php
// session work in the future, then will need to remove this line.
session_write_close();

// Remove time limit, since script can take many minutes
set_time_limit(0);

// Set the "nice" level of the process for these reports. When the "nice" level
// is increased, these cpu intensive reports will have less affect on the performance
// of other server activities, albeit it may negatively impact the performance
// of this report (note this is only applicable for linux).
if(!empty($GLOBALS['clinical_measures_report_nice'])) {
  proc_nice($GLOBALS['clinical_measures_report_nice']);
}

// Start a report, which will be stored in the report_results sql table..
if(!empty($_POST['execute_report_id'])) {

  $target_date = (!empty($_POST['date_target'])) ? $_POST['date_target'] : date('Y-m-d H:i:s');
  $rule_filter = (!empty($_POST['type'])) ? $_POST['type'] : '';
  $plan_filter = (!empty($_POST['plan'])) ? $_POST['plan'] : '';
  $organize_method = (empty($plan_filter)) ? 'default' : 'plans';
  $provider = $_POST['provider'];
  $pat_prov_rel = (empty($_POST['pat_prov_rel'])) ? 'primary' : $_POST['pat_prov_rel'];

  // Process a new report and collect results
  $options = array();
  $array_date = array();

  if(in_array($rule_filter, array('pqrs', 'pqrs_individual_2015', 'pqrs_groups_2015', 'pqrs_individual_2016', 'pqrs_groups_2016', 'amc', 'amc_2011', 'amc_2014', 'amc_2014_stage1'))) {
    // For AMC:
    //   need to make $target_date an array with two elements ('dateBegin' and 'dateTarget')
    //   need to send a manual data entry option (number of labs)
    $array_date['dateBegin'] = $_POST['date_begin'];
    $array_date['dateTarget'] = $target_date;
    $options = array('labs_manual' => $_POST['labs'], 'provider' => $provider);

  } else {
    // If it turns out we ony need one date, use the unmodified target date array and send an empty options array
    // For others, use the unmodified target date array and send an empty options array
    $array_date = $target_date;
  }

  test_rules_clinic_batch_method($provider, $rule_filter, $array_date, 'report', $plan_filter, $organize_method, $options, $pat_prov_rel, '', $_POST['execute_report_id']);
} else {
  echo 'ERROR';
}

?>