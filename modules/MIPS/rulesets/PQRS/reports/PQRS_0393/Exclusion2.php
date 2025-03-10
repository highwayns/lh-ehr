<?php
/**
 * PQRS Measure 0393 -- Exclusion 2
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

class PQRS_0393_Exclusion2 extends PQRSFilter
{
    public function getTitle() 
    {
        return "Exclusion 2";
    }
    
    public function test( PQRSPatient $patient, $beginDate, $endDate )
    {
return false;    

    }
}

?>
