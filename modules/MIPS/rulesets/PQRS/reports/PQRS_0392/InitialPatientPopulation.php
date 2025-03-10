<?php
/**
 * PQRS Measure 0392 -- Initial Patient Population
 *
 * Copyright (C) 2015 - 2017      Suncoast Connection
  * 
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details. 
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 * 
 * @author  Art Eaton <art@suncoastconnection.com>
 * @author  Bryan lee <bryan@suncoastconnection.com>
 * @package LibreHealthEHR 
 * @link    http://suncoastconnection.com
 * @link    http://librehealth.io
 *
 * Please support this product by sharing your changes with the LibreHealth.io community.
 */
 ///////This measure has multiple reporting criteria, and might need to be 
 ///////run as multiple measures or multiple denominators.  Check, otherwise 
 ///////you must run this report and manually separate the age populations.
class PQRS_0392_InitialPatientPopulation extends PQRSFilter
{
    public function getTitle() 
    {
        return "Initial Patient Population";
    }
    
    public function test( PQRSPatient $patient, $beginDate, $endDate )
    {
$query =
"SELECT COUNT(b1.code) as count ".  
" FROM billing AS b1". 
" JOIN form_encounter AS fe ON (b1.encounter = fe.encounter)".
" JOIN patient_data AS p ON (p.pid = b1.pid)".
" INNER JOIN billing AS b2 ON (b2.pid = b1.pid)".
" INNER JOIN pqrs_ptsf AS codelist_a ON (b1.code = codelist_a.code)".
" INNER JOIN pqrs_ptsf AS codelist_b ON (b2.code = codelist_b.code)".
" WHERE b1.pid = ? ".
//" AND p.sex = 'Female'". //reporting rate 1 and 3
//" AND p.sex = 'Male'".  //reporting rate 2 and 4
" AND fe.provider_id = '".$this->_reportOptions['provider']."'".
" AND fe.date >= '".$beginDate."' ".
" AND fe.date <= DATE_SUB('".$endDate."', INTERVAL 1 MONTH)".
//" AND TIMESTAMPDIFF(YEAR,p.DOB,fe.date) BETWEEN '18' AND '65' ". //reporting rate 1 and 2
" AND TIMESTAMPDIFF(YEAR,p.DOB,fe.date) >='18'".
" AND (b1.code = codelist_a.code AND codelist_a.type = 'pqrs_0392_a') ".
" AND (b2.code = codelist_b.code AND codelist_b.type = 'pqrs_0392_b'); ";

$result = sqlFetchArray(sqlStatementNoLog($query, array($patient->id)));
if ($result['count']> 0){ return true;} else {return false;}  
    }
}
