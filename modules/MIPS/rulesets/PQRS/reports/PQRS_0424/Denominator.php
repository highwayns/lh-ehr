<?php
/**
 * PQRS Measure 0424 -- Denominator 
 *
 * Copyright (C) 2016      Suncoast Connection
 *
 * @link    http://suncoastconnection.com
 * @author  Bryan lee <leebc 11 at acm dot org>
 * @author  Suncoast Connection
 */
 
class PQRS_0424_Denominator extends PQRSFilter
{
    public function getTitle() 
    {
        return "Denominator";
    }
    
    public function test( PQRSPatient $patient, $beginDate, $endDate )
    {
$query =
" SELECT COUNT(b1.code) AS count".  
" FROM billing AS b1".
" JOIN form_encounter AS fe ON (b1.encounter = fe.encounter)".
" WHERE b1.pid = ? ".
" AND fe.date BETWEEN '".$beginDate."' AND '".$endDate."' ".
" AND b1.code IN ('G9654','G9770') ; ";
//Rule-out
$result = sqlFetchArray(sqlStatementNoLog($query, array($patient->id))); 
if ($result['count']> 0){ return false;} 


$query =
" SELECT COUNT(b1.code) AS count".  
" FROM billing AS b1".
" JOIN form_encounter AS fe ON (b1.encounter = fe.encounter)".
" WHERE b1.pid = ? ".
" AND fe.date BETWEEN '".$beginDate."' AND '".$endDate."' ".
" AND b1.code = '4255F' ; ";
//Requires Pre-Measure
$result = sqlFetchArray(sqlStatementNoLog($query, array($patient->id))); 
if ($result['count']> 0){ return true;} else {return false;}
    }
}

?>
