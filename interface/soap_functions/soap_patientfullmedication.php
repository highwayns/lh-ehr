<?php

/**
 * interface/soap_functions/soap_patientfullmedication.php Check current
 * patients Medications TTL status and perform a SOAP call to NewCrop if
 * elapsed.
 *
 * Copyright (C) 2011 ZMG LLC <sam@zhservices.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 3 of the License, or (at your option) any
 * later version.  This program is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General
 * Public License for more details.  You should have received a copy of the GNU
 * General Public License along with this program.
 * If not, see <http://opensource.org/licenses/gpl-license.php>.
 *
 * @package    LibreHealth EHR
 * @subpackage NewCrop
 * @author     Eldho Chacko <eldho@zhservices.com>
 * @author     Vinish K <vinish@zhservices.com>
 * @author     Sam Likins <sam.likins@wsi-services.com>
 * @link       http://librehealth.io
 */

$sanitize_all_escapes = true;       // SANITIZE ALL ESCAPES

$fake_register_globals = false;     // STOP FAKE REGISTER GLOBALS

require_once(__DIR__.'/../globals.php');
require_once($GLOBALS['fileroot'].'/interface/eRxGlobals.php');
require_once($GLOBALS['fileroot'].'/interface/eRxStore.php');
require_once($GLOBALS['srcdir'].'/xmltoarray_parser_htmlfix.php');
require_once($GLOBALS['srcdir'].'/lists.inc');
require_once($GLOBALS['srcdir'].'/amc.php');
require_once($GLOBALS['fileroot'].'/interface/eRxSOAP.php');
require_once($GLOBALS['fileroot'].'/interface/eRx_xml.php');

set_time_limit(0);

$eRxSOAP = new eRxSOAP;
$eRxSOAP->setGlobals(new eRxGlobals($GLOBALS))
    ->setStore(new eRxStore)
    ->setAuthUserId($_SESSION['authUserID']);

if(array_key_exists('patient', $_REQUEST)) {
    $eRxSOAP->setPatientId($_REQUEST['patient']);
} elseif(array_key_exists('pid', $GLOBALS)) {
    $eRxSOAP->setPatientId($GLOBALS['pid']);
}

if((array_key_exists('refresh', $_REQUEST)
        && $_REQUEST['refresh'] == 'true')
    || $eRxSOAP->elapsedTTL(eRxSOAP::ACTION_MEDICATIONS)
    || $eRxSOAP->checkPatientImportStatus(eRxSOAP::FLAG_PRESCRIPTION_PRESS)
) {
    $insertedRows = $eRxSOAP->insertUpdateMedications();

    $message = $insertedRows
        ? xl('Prescription History import successfully completed')
        : xl('Nothing to import for Prescription');

    $eRxSOAP->updatePatientImportStatus(eRxSOAP::FLAG_PRESCRIPTION_IMPORT)
        ->updateTTL(eRxSOAP::ACTION_MEDICATIONS);
} else {
    $message = xl('Import deferred for time-to-live');
}

echo text($message);
