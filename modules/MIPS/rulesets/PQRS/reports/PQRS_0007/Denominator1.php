<?php
/**
 * PQRS Measure 0007 -- Denominator 1
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
 
class PQRS_0007_Denominator1 extends PQRSFilter
{
    public function getTitle() 
    {
        return "Denominator";
    }
    
    public function test( PQRSPatient $patient, $beginDate, $endDate )
    {
		$query =
		"SELECT COUNT(b1.code) as count ". 
		" FROM billing AS b1". 
		" JOIN form_encounter AS fe ON (b1.encounter = fe.encounter)".  
		" JOIN patient_data AS p ON (b1.pid = p.pid)".
		" INNER JOIN pqrs_efcc1 AS codelist_c ON (b1.code = codelist_c.code)".		
		" WHERE b1.pid = ? ".
		" AND b1.code = 'G8694'";  
		
		$result = sqlFetchArray(sqlStatementNoLog($query, array($patient->id)));
if ($result['count']> 0){ return true;}else{return false;}        

    }
}

?>
