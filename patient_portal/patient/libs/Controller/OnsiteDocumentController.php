<?php
/** @package    Patient Portal::Controller */

/**
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 *
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreHealth EHR
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @link http://librehealth.io
 */

/** import supporting libraries */
require_once("AppBaseController.php");
require_once("Model/OnsiteDocument.php");

/**
 * OnsiteDocumentController is the controller class for the OnsiteDocument object.  The
 * controller is responsible for processing input from the user, reading/updating
 * the model as necessary and displaying the appropriate view.
 *
 * @package Patient Portal::Controller
 * @author ClassBuilder
 * @version 1.0
 */
class OnsiteDocumentController extends AppBaseController
{

    /**
     * Override here for any controller-specific functionality
     *
     * @inheritdocs
     */
    protected function Init()
    {
        parent::Init();

        // $this->RequirePermission(ExampleUser::$PERMISSION_USER,'SecureExample.LoginForm');
    }

    /**
     * Displays a list view of OnsiteDocument objects
     */
    public function ListView()
    {
        $recid = $pid = $user = $encounter =  0;
        $docid = "";

        if (isset($_GET['pid'])) {
            $pid = ( int ) $_GET['pid'];
        }

        if (isset($_GET['user'])) {
            $user = $_GET['user'];
        }

        if (isset($_GET['docid'])) {
            $docid = $_GET['docid'];
        }

        if (isset($_GET['enc'])) {
            $encounter = ( int ) $_GET['enc'];
        }

        if (isset($_GET['recid'])) {
            $recid = ( int ) $_GET['recid'];
        }

        $this->Assign( 'recid', $recid );
        $this->Assign( 'cpid', $pid );
        $this->Assign( 'cuser', $user );
        $this->Assign( 'encounter', $encounter );
        $this->Assign( 'docid', $docid );

        $this->Render();
    }

    /**
     * API Method queries for OnsiteDocument records and render as JSON
     */
    public function Query()
    {
        try
        {
            $criteria = new OnsiteDocumentCriteria();
            $pid = RequestUtil::Get( 'patientId' );
            $criteria->Pid_Equals = $pid;
            $recid = RequestUtil::Get( 'recid' );
            if( $recid > 0 ){
                $criteria->Id_Equals = $recid;
            }
            $filter = RequestUtil::Get('filter');
            if ($filter) {
                $criteria->AddFilter(
                new CriteriaFilter('Id,Pid,Facility,Provider,Encounter,CreateDate,DocType,PatientSignedStatus,PatientSignedTime,AuthorizeSignedTime,
                        AcceptSignedStatus,AuthorizingSignator,ReviewDate,DenialReason,AuthorizedSignature,PatientSignature,FullDocument,FileName,FilePath', '%'.$filter.'%')
            );
            }

            // TODO: this is generic query filtering based only on criteria properties
            foreach (array_keys($_REQUEST) as $prop)
            {
                $prop_normal = ucfirst($prop);
                $prop_equals = $prop_normal.'_Equals';

                if (property_exists($criteria, $prop_normal))
                {
                    $criteria->$prop_normal = RequestUtil::Get($prop);
                }
                elseif (property_exists($criteria, $prop_equals))
                {
                    // this is a convenience so that the _Equals suffix is not needed
                    $criteria->$prop_equals = RequestUtil::Get($prop);
                }
            }

            $output = new stdClass();

            // if a sort order was specified then specify in the criteria
            $output->orderBy = RequestUtil::Get('orderBy');
            $output->orderDesc = RequestUtil::Get('orderDesc') != '';
            if ($output->orderBy) {
                $criteria->SetOrder($output->orderBy, $output->orderDesc);
            }

            $page = RequestUtil::Get('page');

            if ($page != '')
            {
                // if page is specified, use this instead (at the expense of one extra count query)
                $pagesize = $this->GetDefaultPageSize();

                $onsitedocuments = $this->Phreezer->Query('OnsiteDocument',$criteria)->GetDataPage($page, $pagesize);
                $output->rows = $onsitedocuments->ToObjectArray(true,$this->SimpleObjectParams());
                $output->totalResults = $onsitedocuments->TotalResults;
                $output->totalPages = $onsitedocuments->TotalPages;
                $output->pageSize = $onsitedocuments->PageSize;
                $output->currentPage = $onsitedocuments->CurrentPage;
            } else {
                // return all results
                $onsitedocuments = $this->Phreezer->Query('OnsiteDocument',$criteria);
                $output->rows = $onsitedocuments->ToObjectArray(true, $this->SimpleObjectParams());
                $output->totalResults = count($output->rows);
                $output->totalPages = 1;
                $output->pageSize = $output->totalResults;
                $output->currentPage = 1;
            }


            $this->RenderJSON($output, $this->JSONPCallback());
        }
        catch (Exception $ex)
        {
            $this->RenderExceptionJSON($ex);
        }
    }
    public function SingleView()
    {
        $rid = $pid = $user = $encounter = 0;
        if (isset($_GET['id'])) {
            $rid = ( int ) $_GET['id'];
        }

        if (isset($_GET['pid'])) {
            $pid = ( int ) $_GET['pid'];
        }

        if (isset($_GET['user'])) {
            $user = $_GET['user'];
        }

        if (isset($_GET['enc'])) {
            $encounter = $_GET['enc'];
        }

        $this->Assign( 'recid', $rid );
        $this->Assign( 'cpid', $pid );
        $this->Assign( 'cuser', $user );
        $this->Assign( 'encounter', $encounter );
        $this->Render();
    }
    /**
     * API Method retrieves a single OnsiteDocument record and render as JSON
     */
    public function Read()
    {
        try
        {
            $pk = $this->GetRouter()->GetUrlParam('id');
            $onsitedocument = $this->Phreezer->Get('OnsiteDocument',$pk);
            $this->RenderJSON($onsitedocument, $this->JSONPCallback(), true, $this->SimpleObjectParams());
        }
        catch (Exception $ex)
        {
            $this->RenderExceptionJSON($ex);
        }
    }

    /**
     * API Method inserts a new OnsiteDocument record and render response as JSON
     */
    public function Create()
    {
        try
        {

            $json = json_decode(RequestUtil::GetBody());

            if (!$json)
            {
                throw new Exception('The request body does not contain valid JSON');
            }

            $onsitedocument = new OnsiteDocument($this->Phreezer);

            // TODO: any fields that should not be inserted by the user should be commented out

            // this is an auto-increment.  uncomment if updating is allowed
            // $onsitedocument->Id = $this->SafeGetVal($json, 'id');

            $onsitedocument->Pid = $this->SafeGetVal($json, 'pid');
            $onsitedocument->Facility = $this->SafeGetVal($json, 'facility');
            $onsitedocument->Provider = $this->SafeGetVal($json, 'provider');
            $onsitedocument->Encounter = $this->SafeGetVal($json, 'encounter');
            $onsitedocument->CreateDate = date('Y-m-d H:i:s',strtotime($this->SafeGetVal($json, 'createDate')));
            $onsitedocument->DocType = $this->SafeGetVal($json, 'docType');
            $onsitedocument->PatientSignedStatus = $this->SafeGetVal($json, 'patientSignedStatus');
            $onsitedocument->PatientSignedTime = date('Y-m-d H:i:s',strtotime($this->SafeGetVal($json, 'patientSignedTime')));
            $onsitedocument->AuthorizeSignedTime = date('Y-m-d H:i:s',strtotime($this->SafeGetVal($json, 'authorizeSignedTime')));
            $onsitedocument->AcceptSignedStatus = $this->SafeGetVal($json, 'acceptSignedStatus');
            $onsitedocument->AuthorizingSignator = $this->SafeGetVal($json, 'authorizingSignator');
            $onsitedocument->ReviewDate = date('Y-m-d H:i:s',strtotime($this->SafeGetVal($json, 'reviewDate')));
            $onsitedocument->DenialReason = $this->SafeGetVal($json, 'denialReason');
            $onsitedocument->AuthorizedSignature = $this->SafeGetVal($json, 'authorizedSignature');
            $onsitedocument->PatientSignature = $this->SafeGetVal($json, 'patientSignature');
            $onsitedocument->FullDocument = $this->SafeGetVal($json, 'fullDocument');
            $onsitedocument->FileName = $this->SafeGetVal($json, 'fileName');
            $onsitedocument->FilePath = $this->SafeGetVal($json, 'filePath');

            $onsitedocument->Validate();
            $errors = $onsitedocument->GetValidationErrors();

            if (count($errors) > 0)
            {
                $this->RenderErrorJSON('Please check the form for errors',$errors);
            } else {
                $onsitedocument->Save();
                $this->RenderJSON($onsitedocument, $this->JSONPCallback(), true, $this->SimpleObjectParams());
            }

        }
        catch (Exception $ex)
        {
            $this->RenderExceptionJSON($ex);
        }
    }

    /**
     * API Method updates an existing OnsiteDocument record and render response as JSON
     */
    public function Update()
    {
        try
        {

            $json = json_decode(RequestUtil::GetBody());

            if (!$json)
            {
                throw new Exception('The request body does not contain valid JSON');
            }

            $pk = $this->GetRouter()->GetUrlParam('id');
            $onsitedocument = $this->Phreezer->Get('OnsiteDocument',$pk);

            // TODO: any fields that should not be updated by the user should be commented out

            // this is a primary key.  uncomment if updating is allowed
            // $onsitedocument->Id = $this->SafeGetVal($json, 'id', $onsitedocument->Id);

            $onsitedocument->Pid = $this->SafeGetVal($json, 'pid', $onsitedocument->Pid);
            $onsitedocument->Facility = $this->SafeGetVal($json, 'facility', $onsitedocument->Facility);
            $onsitedocument->Provider = $this->SafeGetVal($json, 'provider', $onsitedocument->Provider);
            $onsitedocument->Encounter = $this->SafeGetVal($json, 'encounter', $onsitedocument->Encounter);
            $onsitedocument->CreateDate = date('Y-m-d H:i:s',strtotime($this->SafeGetVal($json, 'createDate', $onsitedocument->CreateDate)));
            $onsitedocument->DocType = $this->SafeGetVal($json, 'docType', $onsitedocument->DocType);
            $onsitedocument->PatientSignedStatus = $this->SafeGetVal($json, 'patientSignedStatus', $onsitedocument->PatientSignedStatus);
            $onsitedocument->PatientSignedTime = date('Y-m-d H:i:s',strtotime($this->SafeGetVal($json, 'patientSignedTime', $onsitedocument->PatientSignedTime)));
            $onsitedocument->AuthorizeSignedTime = date('Y-m-d H:i:s',strtotime($this->SafeGetVal($json, 'authorizeSignedTime', $onsitedocument->AuthorizeSignedTime)));
            $onsitedocument->AcceptSignedStatus = $this->SafeGetVal($json, 'acceptSignedStatus', $onsitedocument->AcceptSignedStatus);
            $onsitedocument->AuthorizingSignator = $this->SafeGetVal($json, 'authorizingSignator', $onsitedocument->AuthorizingSignator);
            $onsitedocument->ReviewDate = date('Y-m-d H:i:s',strtotime($this->SafeGetVal($json, 'reviewDate', $onsitedocument->ReviewDate)));
            $onsitedocument->DenialReason = $this->SafeGetVal($json, 'denialReason', $onsitedocument->DenialReason);
            $onsitedocument->AuthorizedSignature = $this->SafeGetVal($json, 'authorizedSignature', $onsitedocument->AuthorizedSignature);
            $onsitedocument->PatientSignature = $this->SafeGetVal($json, 'patientSignature', $onsitedocument->PatientSignature);
            $onsitedocument->FullDocument = $this->SafeGetVal($json, 'fullDocument', $onsitedocument->FullDocument);
            $onsitedocument->FileName = $this->SafeGetVal($json, 'fileName', $onsitedocument->FileName);
            $onsitedocument->FilePath = $this->SafeGetVal($json, 'filePath', $onsitedocument->FilePath);

            $onsitedocument->Validate();
            $errors = $onsitedocument->GetValidationErrors();

            if (count($errors) > 0)
            {
                $this->RenderErrorJSON('Please check the form for errors',$errors);
            } else {
                $onsitedocument->Save();
                $this->RenderJSON($onsitedocument, $this->JSONPCallback(), true, $this->SimpleObjectParams());
            }


        }
        catch (Exception $ex)
        {


            $this->RenderExceptionJSON($ex);
        }
    }

    /**
     * API Method deletes an existing OnsiteDocument record and render response as JSON
     */
    public function Delete()
    {
        try
        {

            // TODO: if a soft delete is prefered, change this to update the deleted flag instead of hard-deleting

            $pk = $this->GetRouter()->GetUrlParam('id');
            $onsitedocument = $this->Phreezer->Get('OnsiteDocument',$pk);

            $onsitedocument->Delete();

            $output = new stdClass();

            $this->RenderJSON($output, $this->JSONPCallback());

        }
        catch (Exception $ex)
        {
            $this->RenderExceptionJSON($ex);
        }
    }
}

?>
