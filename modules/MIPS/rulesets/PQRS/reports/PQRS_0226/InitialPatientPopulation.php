<?php
/**
 * PQRS Measure 0226 -- Initial Patient Population
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
 
class PQRS_0226_InitialPatientPopulation extends PQRSFilter
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
" INNER JOIN pqrs_poph AS codelist_a ON (b1.code = codelist_a.code)".
" WHERE b1.pid = ? ".
" AND fe.provider_id = '".$this->_reportOptions['provider']."'".
" AND fe.date BETWEEN '".$beginDate."' AND '".$endDate."' ".
" AND TIMESTAMPDIFF(YEAR,p.DOB,fe.date) >= '18' ".
" AND (b1.code = codelist_a.code AND codelist_a.type = 'pqrs_0226_b' AND b1.modifier NOT IN('GQ','GT','95')); ";

$result = sqlFetchArray(sqlStatementNoLog($query, array($patient->id)));
if ($result['count']> 0){ return true;} else {
    $query =
"SELECT COUNT(b1.code) as count ".  
" FROM billing AS b1". 
" JOIN form_encounter AS fe ON (b1.encounter = fe.encounter)".
" JOIN patient_data AS p ON (p.pid = b1.pid)".
" INNER JOIN pqrs_poph AS codelist_a ON (b1.code = codelist_a.code)".
" WHERE b1.pid = ? ".
" AND fe.provider_id = '".$this->_reportOptions['provider']."'".
" AND fe.date BETWEEN '".$beginDate."' AND '".$endDate."' ".
" AND TIMESTAMPDIFF(YEAR,p.DOB,fe.date) >= '18' ".
" AND (b1.code = codelist_a.code AND codelist_a.type = 'pqrs_0226_a' AND b1.modifier NOT IN('GQ','GT','95')); ";

$result = sqlFetchArray(sqlStatementNoLog($query, array($patient->id)));
if ($result['count']> 1){ return true;} else {return false;}  
}
    }
}

?>
