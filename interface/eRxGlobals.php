<?php

/**
 * interface/eRxGlobals.php Functions for retrieving NewCrop global configurations.
 *
 * Copyright (C) 2015 Sam Likins <sam.likins@wsi-services.com>
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
 * @author     Sam Likins <sam.likins@wsi-services.com>
 * @link       http://librehealth.io
 */

class eRxGlobals {

    private $configuration;

    /**
     * Construct eRxGlobals optionally providing an array of configurations to use
     * @param array|null &$configuration [optional] Array of configurations to use
     */
    public function __construct(&$configuration = null) {
        if(is_array($configuration)) {
            $this->setGlobals($configuration);
        }
    }

    /**
     * Set the configuration array for use in eRxGlobals
     * @param array &$configuration Array of configurations to use
     */
    public function setGlobals(&$configuration) {
        $this->configuration =& $configuration;

        return $this;
    }

    /**
     * Return the value stored in configurations specified by the key
     * @param  string $key Configuration array key
     * @return mixed       Configuration specified by the key
     */
    public function getGlobalValue($key) {
        if(array_key_exists($key, $this->configuration)) {
            return $this->configuration[$key];
        }
    }

    /**
     * Return the version of LibreHealth EHR
     * @return string LibreHealth EHR version
     */
    public function getLibreEHRVersion() {
        return $this->getGlobalValue('libreehr_version');
    }

    /**
     * Return the LibreHealth EHR site directory
     * @return string LibreHealth EHR site directory
     */
    public function getLibreEHRSiteDirectory() {
        return $this->getGlobalValue('OE_SITE_DIR');
    }

    /**
     * Return enable state for NewCrop eRx service
     * @return boolean NewCrop eRx service enabled state
     */
    public function getEnabled() {
        return $this->getGlobalValue('erx_enable');
    }

    /**
     * Return the NewCrop eRx requests URL
     * @return string URL for NewCrop eRx requests
     */
    public function getPath() {
        return $this->getGlobalValue('erx_newcrop_path');
    }

    /**
     * Return the NewCrop eRx service URLs
     * @return array URLs for NewCrop eRx services: index [ 0 = Update, 1 = Patient ]
     */
    public function getSoapPaths() {
        return explode(';', $this->getGlobalValue('erx_newcrop_path_soap'));
    }

    /**
     * Return the NewCrop eRx allergies time-to-live
     * @return integer Time-to-live in seconds for NewCrop eRx allergies
     */
    public function getTTLSoapAllergies() {
        return $this->getGlobalValue('erx_soap_ttl_allergies');
    }

    /**
     * Return the NewCrop eRx medications time-to-live
     * @return integer Time-to-live in seconds for NewCrop eRx medications
     */
    public function getTTLSoapMedications() {
        return $this->getGlobalValue('erx_soap_ttl_medications');
    }

    /**
     * Return the NewCrop eRx partner name for credentials
     * @return string Partner name for credentials
     */
    public function getPartnerName() {
        return $this->getGlobalValue('erx_account_partner_name');
    }

    /**
     * Return the NewCrop eRx account name for credentials
     * @return string Account name for credentials
     */
    public function getAccountName() {
        return $this->getGlobalValue('erx_account_name');
    }

    /**
     * Return the NewCrop eRx password for credentials
     * @return string Password for credentials
     */
    public function getAccountPassword() {
        return $this->getGlobalValue('erx_account_password');
    }

    /**
     * Return the NewCrop eRx account Id for credentials
     * @return string Account Id for credentials
     */
    public function getAccountId() {
        return $this->getGlobalValue('erx_account_id');
    }

    /**
     * Return enable state for NewCrop eRx only upload prescriptions
     * @return boolean NewCrop eRx only upload prescriptions enabled state
     */
    public function getUploadActive() {
        return $this->getGlobalValue('erx_upload_active');
    }

    /**
     * Return enable state for NewCrop eRx import status message
     * @return boolean NewCrop eRx import status message enabled state
     */
    public function getImportStatusMessage() {
        return $this->getGlobalValue('erx_import_status_message');
    }

    /**
     * Return enable state for NewCrop eRx display medications uploaded
     * @return boolean NewCrop eRx display medications uploaded enabled state
     */
    public function getDisplayMedication() {
        return $this->getGlobalValue('erx_medication_display');
    }

    /**
     * Return enable state for NewCrop eRx display allergies uploaded
     * @return boolean NewCrop eRx display allergies uploaded enabled state
     */
    public function getDisplayAllergy() {
        return $this->getGlobalValue('erx_allergy_display');
    }

    /**
     * Return NewCrop eRx default patient country code
     * @return string NewCrop eRx default patient country code
     */
    public function getDefaultPatientCountry() {
        return $this->getGlobalValue('erx_default_patient_country');
    }

    /**
     * Return array containing NewCrop eRx credentials
     * @return array NewCrop eRx credentials: index [ 0 = Partner Name, 1 = Account Name, 2 = Password ]
     */
    public function getCredentials() {
        return array(
            $this->getPartnerName(),
            $this->getAccountName(),
            $this->getAccountPassword(),
        );
    }

    /**
     * Return Debug NewCrop eRx settings
     * @return integer Debug settings: flags [ 1 = XML, 2 = RESULT ]
     */
    public function getDebugSetting() {
        return $this->getGlobalValue('erx_debug_setting');
    }

}
