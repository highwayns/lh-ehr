<?php
/**
 *
 * EXPORT QRDA
 *
 * Copyright (C) 2015 Ensoftek, Inc
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
 * @package LibreHealth EHR
 * @author  Ensoftek
 * @link    http://librehealth.io
 */


 //SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//
//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//
require_once("../interface/globals.php");
require_once("../ccr/uuid.php");
require_once("../library/patient.inc");
require_once "../library/options.inc.php";
require_once("../library/clinical_rules.php");
require_once("../library/classes/QRDAXml.class.php");
require_once "$srcdir/report_database.inc";
require_once("$srcdir/sanitize.inc.php");
require_once "qrda_functions.php";

//Remove time limit, since script can take many minutes
set_time_limit(0);

//DENEXCEP NOT NEEDED rules
$denExcepNotNeedRules = array('0002', '0018', '0024', '0038', '0043', '0384', '0421');

//Predefined QRDA HQMF ID's for CQM rules
$preDefinedUniqIDRules = array();
$preDefPopIdArr = array();
//CMS139v3/0101 - Falls: Screening for Future Fall Risk: HQMF_ID: 40280381-4555-E1C1-0145-672613970D15 
$preDefinedUniqIDRules['0101'] = '40280381-4555-E1C1-0145-672613970D15';
$preDefPopIdArr['0101']['IPP'] = '2448B0C6-6848-4DCB-AA6D-F199337A2C5C';
$preDefPopIdArr['0101']['DENOM'] = 'EC400908-35BE-439B-92A9-231D99CEA9DF';
$preDefPopIdArr['0101']['NUMER'] = '663FB12B-0FF4-49AB-80A3-624C5E7DF892';
$preDefPopIdArr['0101']['DENEXCEP'] = 'FEC7251A-BF8D-4472-97D8-E2A9C0A42176';

//CMS127v3/0043 - Pneumonia Vaccination Status for Older Adults: HQMF_ID: 40280381-4555-E1C1-0145-762578A81C4C 
$preDefinedUniqIDRules['0043'] = '40280381-4555-E1C1-0145-762578A81C4C';
$preDefPopIdArr['0043']['IPP'] = '873AECC7-E15B-49E7-8391-D73A46201E2E';
$preDefPopIdArr['0043']['DENOM'] = 'FF7016E1-E8C7-43BA-9D56-2BEF649F36FA';
$preDefPopIdArr['0043']['NUMER'] = '201F5A6E-4DDE-43A2-BDFC-CE85A98560DA';

//CMS69v3/0421 - Preventive Care and Screening: Body Mass Index (BMI) Screening and Follow-Up Plan: HQMF_ID: 40280381-4555-E1C1-0145-D2B36DBB3FE6 
$preDefinedUniqIDRules['0421']['Numerator 2'] = '40280381-4555-E1C1-0145-D2B36DBB3FE6';
$preDefPopIdArr['0421']['Numerator 2']['IPP'] = '1C936855-E644-44C0-B264-49A28756FDB1';
$preDefPopIdArr['0421']['Numerator 2']['DENOM'] = '27F1591C-2060-462C-B5D7-7FE86A44B534';
$preDefPopIdArr['0421']['Numerator 2']['DENEX'] = '9B0C3C26-D621-4EA3-81FB-A839A3012044';
$preDefPopIdArr['0421']['Numerator 2']['NUMER'] = '3095531C-24D7-4AFB-9BCB-F1901FF0FF69';

$preDefinedUniqIDRules['0421']['Numerator 1'] = '40280381-4555-E1C1-0145-D2B36DBB3FE6';
$preDefPopIdArr['0421']['Numerator 1']['IPP'] = '6E701B1C-6CA5-4AD5-98C9-5F766745EA89';
$preDefPopIdArr['0421']['Numerator 1']['DENOM'] = 'E4DC29B8-EB26-4A01-ABB0-4F99FC03BA39';
$preDefPopIdArr['0421']['Numerator 1']['DENEX'] = 'BB1B4301-C275-4BAC-87C9-6E960B1601DA';
$preDefPopIdArr['0421']['Numerator 1']['NUMER'] = '7669026D-3683-44CC-A2C5-3D62EB2F8A33';

//CMS117v3/0038 - Childhood Immunization Status: HQMF_ID: 40280381-4555-E1C1-0145-D7C003364261
$preDefinedUniqIDRules['0038'] = '40280381-4555-E1C1-0145-D7C003364261';
$preDefPopIdArr['0038']['IPP'] = '6ED6A787-C871-49B9-825C-70A0DB898977';
$preDefPopIdArr['0038']['DENOM'] = '545DA813-89ED-4DCD-BDDF-4B33D93DCD84';
$preDefPopIdArr['0038']['NUMER'] = '00193FC7-AEE4-4507-A20F-D25A7BB214AD';

//CMS138v3/0028 - Preventive Care and Screening: Tobacco Use: Screening and Cessation Intervention: HQMF_ID: 40280381-4600-425F-0146-1F5867D40E82
$preDefinedUniqIDRules['0028'] = '40280381-4600-425F-0146-1F5867D40E82';
$preDefPopIdArr['0028']['IPP'] = '4E118B62-2AF8-4F51-9355-6FD3F2427D9F';
$preDefPopIdArr['0028']['DENOM'] = 'FA1B3953-AE58-4541-BF7B-84D0EB1B0713';
$preDefPopIdArr['0028']['NUMER'] = '35B1A6DF-1871-4633-A74B-BCAE371BC030';
$preDefPopIdArr['0028']['DENEXCEP'] = '3EE6DFF5-AB17-482F-A147-E6D1E46DBB79';

//CMS157v3/0384 - Oncology: Medical and Radiation – Pain Intensity Quantified: HQMF_ID: 40280381-4600-425F-0146-1F620BDF0EB0
$preDefinedUniqIDRules['0384'] = '40280381-4600-425F-0146-1F620BDF0EB0';
$preDefPopIdArr['0384']['IPP'] = 'C29B6555-3BC7-416F-B61A-FCACD637594F';
$preDefPopIdArr['0384']['DENOM'] = 'E5F80C25-6816-4992-92E2-A735B17F8D4F';
$preDefPopIdArr['0384']['NUMER'] = 'C948D0D2-D6E9-4099-9CD4-870F2F83A14C';

//CMS146v3/0002 - Appropriate Testing for Children with Pharyngitis: HQMF_ID: 40280381-4600-425F-0146-1F6E280C0F09
$preDefinedUniqIDRules['0002'] = '40280381-4600-425F-0146-1F6E280C0F09';
$preDefPopIdArr['0002']['IPP'] = '9D1135EA-BA90-45E7-8EED-F7335D1CC934';
$preDefPopIdArr['0002']['DENOM'] = 'D04EFECB-A901-4565-BDDB-826510499092';
$preDefPopIdArr['0002']['NUMER'] = '3F4CDE57-1C5C-4250-A338-55FED6775F57';
$preDefPopIdArr['0002']['DENEX'] = '0525FBA2-F068-4706-ADB5-E345852DC55B';

//CMS165v3/0018 - Controlling High Blood Pressure: HQMF_ID: 40280381-4600-425F-0146-1F6F722B0F17
$preDefinedUniqIDRules['0018'] = '40280381-4600-425F-0146-1F6F722B0F17';
$preDefPopIdArr['0018']['IPP'] = 'A72855CE-3C60-41F9-AEE2-64D4F584DDD4';
$preDefPopIdArr['0018']['DENOM'] = '26046A5C-E2CC-4A27-B480-FF7E3575691F';
$preDefPopIdArr['0018']['NUMER'] = '0899A359-0CD8-4977-AA29-666892AA3AD4';
$preDefPopIdArr['0018']['DENEX'] = '4327D845-6194-410D-A48D-D6E1802CAD55';

//CMS155v3/0024 - Weight Assessment and Counseling for Nutrition and Physical Activity for Children and Adolescents: HQMF_ID: 40280381-4555-E1C1-0145-85C7311720F5  
$preDefinedUniqIDRules['0024']['Population Criteria 1']['Numerator 1'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 1']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 1']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 1']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 1']['NUMER'] = 'FD4649BD-B962-4CBE-BF4A-C2F95F3EA08E';

$preDefinedUniqIDRules['0024']['Population Criteria 1']['Numerator 2'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 2']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 2']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 2']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 2']['NUMER'] = '52FBD726-4DD1-48F5-98B9-7175754923E1';

$preDefinedUniqIDRules['0024']['Population Criteria 1']['Numerator 3'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 3']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 3']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 3']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 1']['Numerator 3']['NUMER'] = 'C43CE779-C5EE-4C15-A4CC-AF1C446CFB09';

$preDefinedUniqIDRules['0024']['Population Criteria 2']['Numerator 1'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 1']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 1']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 1']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 1']['NUMER'] = 'FD4649BD-B962-4CBE-BF4A-C2F95F3EA08E';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 1']['STRAT'] = '40280381-3D61-56A7-013E-5D53298E6DA3';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 1']['DISPLAY_TEXT'] = 'BMI Recorded, RS1: 3-11';

$preDefinedUniqIDRules['0024']['Population Criteria 2']['Numerator 2'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 2']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 2']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 2']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 2']['NUMER'] = '52FBD726-4DD1-48F5-98B9-7175754923E1';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 2']['STRAT'] = '40280381-3D61-56A7-013E-5D53298E6DA3';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 2']['DISPLAY_TEXT'] = 'BMI Recorded, RS2: 12-17';

$preDefinedUniqIDRules['0024']['Population Criteria 2']['Numerator 3'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 3']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 3']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 3']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 3']['NUMER'] = 'C43CE779-C5EE-4C15-A4CC-AF1C446CFB09';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 3']['STRAT'] = '40280381-3D61-56A7-013E-5D53298E6DA3';
$preDefPopIdArr['0024']['Population Criteria 2']['Numerator 3']['DISPLAY_TEXT'] = 'Nutrition Counseling, RS1: 3-11';

$preDefinedUniqIDRules['0024']['Population Criteria 3']['Numerator 1'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 1']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 1']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 1']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 1']['NUMER'] = 'FD4649BD-B962-4CBE-BF4A-C2F95F3EA08E';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 1']['STRAT'] = '40280381-3D61-56A7-013E-5D532AF06DA5';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 1']['DISPLAY_TEXT'] = 'Nutrition Counseling, RS2: 12-17';

$preDefinedUniqIDRules['0024']['Population Criteria 3']['Numerator 2'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 2']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 2']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 2']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 2']['NUMER'] = '52FBD726-4DD1-48F5-98B9-7175754923E1';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 2']['STRAT'] = '40280381-3D61-56A7-013E-5D532AF06DA5';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 2']['DISPLAY_TEXT'] = 'Physical Activity Counseling, RS1: 3-11';

$preDefinedUniqIDRules['0024']['Population Criteria 3']['Numerator 3'] = '40280381-4555-E1C1-0145-85C7311720F5';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 3']['IPP'] = '10127790-AE94-4070-9DD3-1D3776D08D7C';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 3']['DENOM'] = '3B3C1568-F875-49B1-9090-E2F494EECBB6';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 3']['DENEX'] = 'B288D5A4-D573-4FAA-92D6-0B01E1B35C7A';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 3']['NUMER'] = 'C43CE779-C5EE-4C15-A4CC-AF1C446CFB09';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 3']['STRAT'] = '40280381-3D61-56A7-013E-5D532AF06DA5';
$preDefPopIdArr['0024']['Population Criteria 3']['Numerator 3']['DISPLAY_TEXT'] = 'Physical Activity Counseling, RS2: 12-17';

$preDefPopIdArr['0024']['STRAT1'] = '40280381-3D61-56A7-013E-5D53298E6DA3';
$preDefPopIdArr['0024']['STRAT2'] = '40280381-3D61-56A7-013E-5D532AF06DA5';

//Multiple Numerator NQF# Array declaration
$multNumNQFArr = array('0421', '0024');
$countNumNQFArr = array();
$countNumNQFArr['0421'] = 2;//two Numerators
$countNumNQFArr['0024'] = 9;//Nine Numerators

//Initiation of all QRDA needed elements
$CQMeausesArr = array();
$CQMeausesArr['init_patients'] = "Initial Patient Population";
$CQMeausesArr['exclude_patients'] = "Denominator Exclusions";
$CQMeausesArr['denom_patients'] = "Denominator";
$CQMeausesArr['numer_patients'] = "Numerator";
$CQMeausesArr['exception_patients'] = "Denominator Exceptions";

$cqmItemizedArr = array();
$cqmItemizedArr['init_patients'] = "init_patients";
$cqmItemizedArr['exclude_patients'] = "exclude";
$cqmItemizedArr['denom_patients'] = "all";
$cqmItemizedArr['numer_patients'] = "pass";
$cqmItemizedArr['exception_patients'] = "exception";

//QRDA Needed Ethnicity
$mainEthiArr = array(0=>'Not Hispanic or Latino', 1=>'Hispanic or Latino');
$mainEthiCodeArr =  array(0=>'2186-5', 1=>'2135-2');

//QRDA Needed Race
$mainQrdaRaceArr = array(0=>'American Indian or Alaska Native', 1=>'Asian', 2=>'Black or African American', 3=>'Native Hawaiian or Other Pacific Islander', 4=>'White', 5=>'Other');
$mainQrdaRaceCodeArr =  array(0=>'1002-5', 1=>'2028-9', 2=>'2054-5', 3=>'2076-8', 4=>'2106-3', 5=>'2131-1');

$mainQrdaPopulationIncArr = array();
$mainQrdaPopulationIncArr['init_patients'] = "IPP";
$mainQrdaPopulationIncArr['exclude_patients'] = "DENEX";
$mainQrdaPopulationIncArr['denom_patients'] = "DENOM";
$mainQrdaPopulationIncArr['numer_patients'] = "NUMER";
$mainQrdaPopulationIncArr['exception_patients'] = "DENEXCEP";
$mainQrdaPopulationIncArr['measure_population'] = "MSRPOPL";
$mainQrdaPopulationIncArr['numer_exclusion'] = "NUMEX";

//QRDA Needed Gender
$mainQrdaGenderCodeArr = array();
$mainQrdaGenderCodeArr['F'] = "Female";
$mainQrdaGenderCodeArr['M'] = "Male";
$mainQrdaGenderCodeArr['UN'] = "Unknown";

//QRDA Needed Payer Info
$mainQrdaPayerCodeArr = array();
$mainQrdaPayerCodeArr['A'] = "Medicare";
$mainQrdaPayerCodeArr['B'] = "Medicaid";
$mainQrdaPayerCodeArr['C'] = "Private Health Insurance";
$mainQrdaPayerCodeArr['D'] = "Other";

//Payer Codes According to Cypress Codes
$mainQrdaPayerCodeSendArr = array();
$mainQrdaPayerCodeSendArr['A'] = "1";
$mainQrdaPayerCodeSendArr['B'] = "2";
$mainQrdaPayerCodeSendArr['C'] = "5";
$mainQrdaPayerCodeSendArr['D'] = "349";

//Provider selection
$form_provider = $_GET['form_provider'];

//Get Report Information
$report_id = $_GET['report_id'];
$report_view = collectReportDatabase($report_id);
$target_date = $report_view['date_target'];
$dataSheet = json_decode($report_view['data'],TRUE);

//Needed array for Rule NQF#0024 Stratification
$stratumCheckArr = array();
if(count($dataSheet) > 0){
    //Inner Data Loop
    foreach ($dataSheet as $row) {
        $itemized_test_id = $row['itemized_test_id'];
        $numerator_label = $row['numerator_label'];
        if($row['cqm_nqf_code'] == "0024"){
            if( $row['population_label'] == "Population Criteria 2" ){
                if($row['numerator_label'] == "Numerator 1"){
                    $firstElement = count($row['init_patients']);
                    $thirdElement = count($row['exclude_patients']);
                    $fifthElement = count($row['denom_patients']);
                    $seventhElement = count($row['numer_patients']);
                }else if($row['numerator_label'] == "Numerator 2"){
                    $fifteenElement = count($row['numer_patients']);
                }else if($row['numerator_label'] == "Numerator 3"){
                    $twentyThreeElement = count($row['numer_patients']);
                }
            }else if( $row['population_label'] == "Population Criteria 3" ){
                if($row['numerator_label'] == "Numerator 1"){
                    $secondElement = count($row['init_patients']);
                    $sixElement = count($row['exclude_patients']);
                    $fouthElement = count($row['denom_patients']);
                    $eigthElement = count($row['numer_patients']);
                }else if($row['numerator_label'] == "Numerator 2"){
                    $sixteenElement = count($row['numer_patients']);
                }else if($row['numerator_label'] == "Numerator 3"){
                    $twentyFourElement = count($row['numer_patients']);
                }
            }
        }
        
        //Stratum Array for Cypress Version 2.6.0 acceptable format for NQF 0024 rule
        $stratumCheckArr[1] = array('1'=> $firstElement, '2' => $secondElement);
        $stratumCheckArr[2] = array('1'=> $thirdElement, '2' => $fouthElement);
        $stratumCheckArr[3] = array('1'=> $fifthElement, '2' => $sixElement);
        $stratumCheckArr[4] = array('1'=> $seventhElement, '2' => $eigthElement);
        $stratumCheckArr[5] = array('1'=>'0', '2' =>'0');//Always 0
        $stratumCheckArr[6] = array('1'=>'0', '2' =>'0');//Always 0
        $stratumCheckArr[7] = array('1'=>'0', '2' =>'0');//Always 0
        $stratumCheckArr[8] = array('1'=> $fifteenElement, '2' => $sixteenElement);
        $stratumCheckArr[9] = array('1'=>'0', '2' =>'0');//Always 0
        $stratumCheckArr[10] = array('1'=>'0', '2' =>'0');//Always 0
        $stratumCheckArr[11] = array('1'=>'0', '2' =>'0');//Always 0
        $stratumCheckArr[12] = array('1'=> $twentyThreeElement, '2' => $twentyFourElement);
    }
}

$from_date = date('Y', strtotime($target_date ))."-01-01"; 
$to_date =  date('Y', strtotime($target_date ))."-12-31";
$xml = new QRDAXml();

#################################################################################################
####################### HEADER ELEMENTS START #####################################################
#################################################################################################
//Open Main Clinical Document
$xml->open_clinicaldocument();

$xml->self_realmcode();

$xml->self_typeid();

$tempId = '2.16.840.1.113883.10.20.27.1.1';
$xml->self_templateid($tempId);

$xml->unique_id = getUuid();
$xml->self_id();
$xml->self_code();

//Main Title Display to XML
$main_title = "QRDA Calculated Summary Report";
$xml->add_title($main_title);

//Effective date and time
$eff_datetime = date('Ymdhis', strtotime($target_date));
$xml->self_efftime($eff_datetime);

$xml->self_confidentcode();

//Language
$xml->self_lang();

$setidVal =getUuid();
$xml->self_setid($setidVal);

//Version
$xml->self_version();

//Record Target Elements
$xml->open_recordTarget();
$xml->add_patientRole();
$xml->close_recordTarget();

############### Author Info #######################
$xml->open_author();
//Author time
$auth_dtime = date('Ymdhis', strtotime(date('Y-m-d H:i:s')));
$xml->self_authorTime($auth_dtime);
//Assigned Author
$xml->open_assignAuthor();
$authorsetid = getUuid();
$xml->self_customId($authorsetid);
if($form_provider != ""){
    $userRow = sqlQuery("SELECT facility, facility_id, federaltaxid, npi, phone,fname, lname FROM users WHERE id=?", array($form_provider));
    $facility_name = $userRow['facility'];
    $facility_id = $userRow['facility_id'];
}
//$xml->self_customTag('telecom', array('value' => $userRow['phone'], 'use'=>'WP'));

//assignedAuthoringDevice Start
$xml->open_customTag('assignedAuthoringDevice');

$xml->element('softwareName', 'CYPRESS'); 

//assignedAuthoringDevice Close
$xml->close_customTag();

//Facility Address
$facilResRow = sqlQuery("SELECT name, street,city,state,postal_code, country_code, phone from facility WHERE id = ?", array($facility_id));
$xml->add_authReprestOrginisation($facilResRow);
//$xml->add_facilAddress($facilResRow);
$xml->close_assignAuthor();
$xml->close_author();

############### Custodian Info #######################
$xml->open_custodian();
$xml->open_assgnCustodian();
$xml->add_represtCustodianOrginisation($facilResRow);
$xml->close_assgnCustodian();
$xml->close_custodian();

/*
############### Information Recipient #######################
$xml->open_infoRecipient();
$xml->add_indententRecipient();
$xml->close_infoRecipient();
*/

############### Legal Authenticator #######################
$xml->open_legalAuthenticator();
$auth_dtime = date('Ymdhis', strtotime(date('Y-m-d H:i:s')));
$xml->self_authorTime($auth_dtime);
$xml->self_legalSignCode();

$xml->open_assignedEntity();
$assignedEntityId = getUuid();
$xml->self_customId($assignedEntityId);

$xml->open_customTag('assignedPerson');

//Provider Name
$userNameArr = array('fname' =>$userRow['fname'], 'lname'=>$userRow['lname']);
$xml->add_providerName($userNameArr);

//assignedPerson Close
$xml->close_customTag();

//Represent Origination Name
$xml->add_authReprestOrginisation($facilResRow);
$xml->close_assignedEntity();

$xml->close_legalAuthenticator();

/*
############### Participant is Device(optional)  #######################
$participentDevArr = array();
$xml->open_participant_data('DEV');//DEV -- Device
$xml->open_assocEntityData('RGPR');//RGPR -- Regulated Product
$participentDevArr['root'] = '2.16.840.1.113883.3.2074.1';
$participentDevArr['extension'] = '1a2b3c';
$xml->self_particpantIdInfo($participentDevArr);
$xml->self_participantCodeDevice();
$xml->close_assocEntityData();
$xml->close_participant_data();

############### Participant is Location(optional)  #######################
$participentLocArr = array();
$xml->open_participant_data('LOC');//LOC -- Location
$xml->open_assocEntityData('SDLOC');//SDLOC -- Service Delivery Location
$participentLocArr['root'] = '2.16.840.1.113883.3.249.5.1';
$participentLocArr['extension'] = 'OK666333';
$xml->self_particpantIdInfo($participentLocArr);
$xml->self_participantCodeLocation();

$xml->add_facilAddress($facilResRow);
$xml->close_assocEntityData();
$xml->close_participant_data();
*/

############### documentationOf  START  #######################
$xml->open_customTag('documentationOf');

$xml->open_customTag('serviceEvent', array('classCode'=>'PCPR'));

$timeArr = array('low'=>date('Ymd', strtotime($from_date)), 'high'=>date('Ymd', strtotime($to_date)));
$xml->add_entryEffectTime($timeArr);

$xml->open_customTag('performer', array('typeCode'=>'PRF'));

$xml->open_customTag('assignedEntity');

$npi_provider = $userRow['npi'];
$xml->self_customTag('id', array('root' => '2.16.840.1.113883.4.6', 'extension' =>$npi_provider));

if($userRow['phone'] != ""){
    $xml->self_customTag('telecom', array('value' => $userRow['phone'], 'use'=>'WP'));
}

$xml->open_customTag('assignedPerson');

//Provider Name
$userNameArr = array('fname' =>$userRow['fname'], 'lname'=>$userRow['lname']);
$xml->add_providerName($userNameArr);

//assignedPerson Close
$xml->close_customTag();

$xml->open_customTag('representedOrganization');

$tin_provider = $userRow['federaltaxid'];
if($tin_provider != ""){
    $xml->self_customTag('id', array('root' => '2.16.840.1.113883.4.2', 'extension' =>$tin_provider));
}

$xml->add_facilName($facility_name);

$xml->add_facilAddress($facilResRow);

//representedOrganization Close
$xml->close_customTag();

//assignedEntity Close
$xml->close_customTag();

//performer Close
$xml->close_customTag();

//serviceEvent Close
$xml->close_customTag();

//documentationOf Close
$xml->close_customTag();
############### documentationOf  END  #######################


############### authorization (optional)  #########################

#################################################################################################
####################### HEADER ELEMENTS END #####################################################
#################################################################################################



#################################################################################################
######################### Main Component Open ###################################################
$xml->open_mainComponent();

############### Structure Body Open #######################
$xml->open_structuredBody();

##################### LOOP Component(s) START ########################

###################### Report Parameters Open #####################
//QRDA Category III Reporting Parameters Section (CMS EP) [section: templateId 2.16.840.1.113883.10.20.27.2.6
$xml->open_loopComponent();

$xml->open_section();

$tempID = '2.16.840.1.113883.10.20.17.2.1';
$xml->self_templateid($tempID);

$tempID = '2.16.840.1.113883.10.20.27.2.2';
$xml->self_templateid($tempID);

$tempID = '2.16.840.1.113883.10.20.27.2.6';
$xml->self_templateid($tempID);
$arr = array('code'=>'55187-9', 'codeSystem'=>'2.16.840.1.113883.6.1');
$xml->self_codeCustom($arr);
$title = "Reporting Parameters";
$xml->add_title($title);

$xml->open_text();
$xml->open_list();
$item_title = "Reporting period: ".date('d M Y', strtotime($from_date))." - ".date('d M Y', strtotime($to_date));
$xml->add_item($item_title);
$xml->close_list();
$xml->close_text();

$typeCode = 'DRIV';
$xml->open_entry($typeCode);
$arr = array('classCode'=>'ACT', 'moodCode'=>'EVN');
$xml->open_act($arr);

$tempID = '2.16.840.1.113883.10.20.17.3.8';
$xml->self_templateid($tempID);

$tempID = '2.16.840.1.113883.10.20.27.3.23';
$xml->self_templateid($tempID);

$actId = getUuid();
$xml->self_customId($actId);

$arr = array('code'=>'252116004', 'codeSystem'=>'2.16.840.1.113883.6.96', 'displayName'=>'Observation Parameters');
$xml->self_codeCustom($arr);

$timeArr = array('low'=>date('Ymd', strtotime($from_date)), 'high'=>date('Ymd', strtotime($to_date)));
$xml->add_entryEffectTime($timeArr);

$xml->close_act();
$xml->close_entry();

$xml->close_section();

$xml->close_loopComponent();
###################### Report Parameters Close #####################

###################### Measure Section Open #####################
$xml->open_loopComponent();

$xml->open_section();

$tempID = '2.16.840.1.113883.10.20.27.2.1';
$xml->self_templateid($tempID);

$tempID = '2.16.840.1.113883.10.20.24.2.2';
$xml->self_templateid($tempID);

$tempID = '2.16.840.1.113883.10.20.27.2.3';
$xml->self_templateid($tempID);

$arr = array('code'=>'55186-1', 'codeSystem'=>'2.16.840.1.113883.6.1');
$xml->self_codeCustom($arr);
$title = "Measure Section";
$xml->add_title($title);

$xml->open_text();
$cnt=1;

$tabArr = array('border'=>1, 'width'=>'100%');
if(count($dataSheet) > 0){
    $uniqIdArr = array();
    
    //Inner Data Loop
    foreach ($dataSheet as $row) {
        $itemized_test_id = $row['itemized_test_id'];
        $numerator_label = $row['numerator_label'];
        
        //CQM Rules 2014 set, 0013 is 0018
        if($row['cqm_nqf_code'] == "0013") $row['cqm_nqf_code'] = "0018";
        
        //Table Start
        $xml->open_customTag('table', $tabArr);
        //THEAD Start
        $xml->open_customTag('thead');
        //TR Start
        $xml->open_customTag('tr');

        $xml->add_trElementsTitles();

        //TR close
        $xml->close_customTag();

        //THEAD close
        $xml->close_customTag();
        //TBOBY START
        $xml->open_customTag('tbody');
        $xml->open_customTag('tr');
        
        $tdTitle = generate_display_field(array('data_type'=>'1','list_id'=>'clinical_rules'),$row['id']);
        
        if (!empty($row['cqm_pqri_code'])) {
            $tdTitle .= " " . htmlspecialchars( xl('PQRI') . ":" . $row['cqm_pqri_code'], ENT_NOQUOTES) . " ";
        }
        if (!empty($row['cqm_nqf_code'])) {
            $tdTitle .= " " . htmlspecialchars( xl('NQF') . ":" . $row['cqm_nqf_code'], ENT_NOQUOTES) . " ";
        }
        
        if ( !(empty($row['concatenated_label'])) ) {
            $tdTitle .= ", " . htmlspecialchars( xl( $row['concatenated_label'] ), ENT_NOQUOTES) . " ";
        }
        
        $tdVersionNeutral = getUuid();
        
        if($preDefinedUniqIDRules[$row['cqm_nqf_code']] != ""){
            if( ($row['cqm_nqf_code'] == "0421" ) )
                $tdVersionSpecific = $preDefinedUniqIDRules[$row['cqm_nqf_code']][$row['numerator_label']];
            else if($row['cqm_nqf_code'] == "0024")
                $tdVersionSpecific = $preDefinedUniqIDRules[$row['cqm_nqf_code']][$row['population_label']][$row['numerator_label']];
            else
                $tdVersionSpecific = $preDefinedUniqIDRules[$row['cqm_nqf_code']];
            
            $uniqIdArr[] = $tdVersionSpecific;
        }else{
            $tdVersionSpecific = getUuid();
            $uniqIdArr[] = $tdVersionSpecific;
        }
        
        $dataArr = array(0=>$tdTitle, 1=>$tdVersionNeutral, 2=>$tdVersionSpecific);
        $xml->add_trElementsValues($dataArr);

        //TR close
        $xml->close_customTag();
        //TBODY close
        $xml->close_customTag();
        //Table Close
        $xml->close_customTag();
        
        //Open List Item Wise
        $xml->open_list();
        
        //Performance Rate
        $xml->open_customTag('item');
        $arrContent = array('name'=>'Performance Rate', 'value'=>$row['percentage']);
        $xml->innerContent($arrContent);
        $xml->close_customTag();
        
        
        //All CQM Measures taken here
        foreach($CQMeausesArr as $cqmKey => $cqmVal){
            
            //DENEXCEP(Denominator Exception not needed for some rules are skipping here)
            if( (in_array($row['cqm_nqf_code'], $denExcepNotNeedRules) ) && ($cqmKey == "exception_patients") ) continue;
            
            //get Itemized Data
            if($cqmKey == "init_patients")
                $itemPatArr = collectItemizedPatientsCdrReport($report_id,$itemized_test_id,$cqmItemizedArr[$cqmKey]);
            else 
                $itemPatArr = collectItemizedPatientsCdrReport($report_id,$itemized_test_id,$cqmItemizedArr[$cqmKey], $numerator_label);
            $fullPatArr = array();
            foreach($itemPatArr as $itemPatInfo){
                $fullPatArr[] = $itemPatInfo['pid'];
            }
            
            //Initial Patient Population
            $xml->open_customTag('item');
            $arrContent = array('name'=>$cqmVal, 'value'=>count($fullPatArr));
            $xml->innerContent($arrContent);
            
            $detailsArr = getQRDAPatientNeedInfo($fullPatArr);
            
            //Open Sub List 
            $xml->open_list();
            
            //Gender Section Display
            foreach($mainQrdaGenderCodeArr as $GKey => $GVal){
                $xml->open_customTag('item');
                $genderInfo = $detailsArr['gender'][$GVal];
                $arrContent = array('name'=>$GVal, 'value'=>$genderInfo);
                $xml->innerContent($arrContent);
                $xml->close_customTag();
            }
            
            //Ethnicity Section Display
            foreach($mainEthiArr as $ethKey => $ethVal){
                $ethnicity_data = $detailsArr['ethnicity'][$ethVal];
                $xml->open_customTag('item');
                $arrContent = array('name'=>'Ethnicity - '.$ethVal, 'value'=>$ethnicity_data);
                $xml->innerContent($arrContent);
                $xml->close_customTag();
            }
            
            //Race Section Display
            foreach($mainQrdaRaceArr as $RKey => $RVal){
                $race_data = $detailsArr['race'][$RVal];
                $xml->open_customTag('item');
                $arrContent = array('name'=>'Race - '.$RVal, 'value'=>$race_data);
                $xml->innerContent($arrContent);
                $xml->close_customTag();
            }
            
            //Payer Type Section Display
            $payerCheckArr = getQRDAPayerInfo($fullPatArr);
            foreach($mainQrdaPayerCodeArr as $PKey => $PVal){
                $xml->open_customTag('item');
                $arrContent = array('name'=>'Payer - '.$PVal, 'value'=>$payerCheckArr[$PVal]);
                $xml->innerContent($arrContent);
                $xml->close_customTag();
            }
                
            //close Sub List
            $xml->close_list();
            $xml->close_customTag();
        }
        $xml->close_list();
    }
}

$xml->close_text();

#######################################################################
######################### QUALITY MEASURES START ######################
#######################################################################
if(count($dataSheet) > 0){
    $innrCnt = 0;
    $skipMultNumArr = array();
    $dataChkArr = array();
    foreach($multNumNQFArr as $multNumVal){
        $skipMultNumArr[$multNumVal] = false; 
        $dataChkArr[$multNumVal] = 0;
    }
    
    //Inner Data Loop
    foreach ($dataSheet as $row) {
        $itemized_test_id = $row['itemized_test_id'];
        $numerator_label = $row['numerator_label'];
        //Skip section
        //if($row['cqm_nqf_code'] == "0028a") continue;
        
        //if($row['cqm_nqf_code'] == "0038"){
        //  if(in_array($row['numerator_label'], $NQF38NumArr)) continue;
        //}
        
        if(in_array($row['cqm_nqf_code'], $multNumNQFArr)){
            $dataChkArr[$row['cqm_nqf_code']]++;
        }
        
        //CQM Rules 2014 set, 0013 is 0018
        if($row['cqm_nqf_code'] == "0013") $row['cqm_nqf_code'] = "0018";
        
        $tdTitle = generate_display_field(array('data_type'=>'1','list_id'=>'clinical_rules'),$row['id']);      
        if (!empty($row['cqm_pqri_code'])) {
            $tdTitle .= " " . text( xl('PQRI') . ":" . $row['cqm_pqri_code']) . " ";
        }
        if (!empty($row['cqm_nqf_code'])) {
            $tdTitle .= " " . text( xl('NQF') . ":" . $row['cqm_nqf_code']) . " ";
        }
        
        if ( !(empty($row['concatenated_label'])) ) {
            $tdTitle .= ", " . text( xl( $row['concatenated_label'] )) . " ";
        }
        
        ###########################################################
        if( ( !isset($skipMultNumArr[$row['cqm_nqf_code']]) ) || ($skipMultNumArr[$row['cqm_nqf_code']] == false) ){
            
            //Entry open
            $xml->open_entry();

            //Organizer Start
            $arr = array('classCode'=>'CLUSTER', 'moodCode'=>'EVN');
            $xml->open_customTag('organizer', $arr);

            $tempID = "2.16.840.1.113883.10.20.24.3.98";
            $xml->self_templateid($tempID);

            $tempID = "2.16.840.1.113883.10.20.27.3.1";
            $xml->self_templateid($tempID);

            //$tempID = "2.16.840.1.113883.10.20.27.3.17";
            //$xml->self_templateid($tempID);
            $actId = getUuid();
            $xml->self_customId($actId);

            $arr = array('code'=>'completed');
            $xml->self_customTag('statusCode', $arr);

            //reference Start
            $arr = array('typeCode'=>'REFR');
            $xml->open_customTag('reference', $arr);

            //externalDocument Start
            $arr = array('classCode'=>'DOC', 'moodCode'=>'EVN');
            $xml->open_customTag('externalDocument', $arr);

            //$exDocID = getUuid();
            $exDocID = $uniqIdArr[$innrCnt];
            //$xml->self_customId($exDocID);
            $xml->self_customTag('id', array('root' => '2.16.840.1.113883.4.738', 'extension' =>$exDocID));

            $arr = array('code'=>'57024-2', 'displayName'=>'Health Quality Measure Document', 'codeSystem'=>'2.16.840.1.113883.6.1', 'codeSystemName'=>'LOINC');
            $xml->self_codeCustom($arr);

            $dispContntTitle = str_replace("&", '', $tdTitle);
            $xml->textDispContent($dispContntTitle);

            //externalDocument Close
            $xml->close_customTag();

            //reference Close
            $xml->close_customTag();

            
            ############### Performance Rate for Proportion Measure template START###################
            $xml->open_loopComponent();
            
            //observation Open
            $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));

            $tempID = "2.16.840.1.113883.10.20.27.3.14";
            $xml->self_templateid($tempID);

            $tempID = "2.16.840.1.113883.10.20.27.3.25";
            $xml->self_templateid($tempID);

            $arr = array('code'=>'72510-1', 'displayName'=>'Performance Rate', 'codeSystem'=>'2.16.840.1.113883.6.1', 'codeSystemName'=>'SNOMED-CT');
            $xml->self_codeCustom($arr);

            $arr = array('code'=>'completed');
            $xml->self_customTag('statusCode', $arr);
            
            $percentage = str_replace("%", '', $row['percentage']);
            $arr = array('xsi:type'=>'REAL', 'value'=>$percentag/100);
            $xml->self_customTag('value', $arr);

            //reference Start
            $arr = array('typeCode'=>'REFR');
            $xml->open_customTag('reference', $arr);

            //externalObservation Start
            $arr = array('classCode'=>'OBS', 'moodCode'=>'EVN');
            $xml->open_customTag('externalObservation', $arr);
            
            //Modified HQMF_ID
            //$exDocID = getUuid();
            
            
            if( ($row['cqm_nqf_code'] == "0421" )){
                $exDocID = $preDefPopIdArr[$row['cqm_nqf_code']][$row['numerator_label']]["NUMER"];
            }else if(($row['cqm_nqf_code'] == "0024")){
                $exDocID = $preDefPopIdArr[$row['cqm_nqf_code']][$row['population_label']][$row['numerator_label']]["NUMER"];
            }else{
                if($preDefPopIdArr[$row['cqm_nqf_code']]["NUMER"] != "")
                    $exDocID = $preDefPopIdArr[$row['cqm_nqf_code']]["NUMER"];
                else    
                    $exDocID = getUuid();
            }
        
            $xml->self_customId($exDocID);

            $arr = array('code'=>'NUMER', 'displayName'=>'Numerator', 'codeSystem'=>'2.16.840.1.113883.5.1063', 'codeSystemName'=>'ObservationValue');
            $xml->self_codeCustom($arr);

            //externalObservation Close
            $xml->close_customTag();

            //reference Close
            $xml->close_customTag();

            //observation Close
            $xml->close_customTag();

            $xml->close_loopComponent();
            ############### Performance Rate for Proportion Measure template END ###################
        
        }
        
        //All CQM Measures taken here
        foreach($CQMeausesArr as $cqmKey => $cqmVal){
            
            //DENEXCEP(Denominator Exception not needed for some rules are skipping here)
            if( (in_array($row['cqm_nqf_code'], $denExcepNotNeedRules) ) && ($cqmKey == "exception_patients") ) continue;
            
            //get Itemized Data
            if($cqmKey == "init_patients")
                $itemPatArr = collectItemizedPatientsCdrReport($report_id,$itemized_test_id,$cqmItemizedArr[$cqmKey]);
            else
                $itemPatArr = collectItemizedPatientsCdrReport($report_id,$itemized_test_id,$cqmItemizedArr[$cqmKey], $numerator_label);
            
            $fullPatArr = array();
            foreach($itemPatArr as $itemPatInfo){
                $fullPatArr[] = $itemPatInfo['pid'];
            }
            
            $detailsArr = getQRDAPatientNeedInfo($fullPatArr);
            ############### Initial patient population template START###################
            $xml->open_loopComponent();

            //observation Open
            $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));

            $tempID = "2.16.840.1.113883.10.20.27.3.5";
            $xml->self_templateid($tempID);

            $tempID = "2.16.840.1.113883.10.20.27.3.16";
            $xml->self_templateid($tempID);

            $arr = array('code'=>'ASSERTION', 'displayName'=>'Assertion', 'codeSystem'=>'2.16.840.1.113883.5.4', 'codeSystemName'=>'ActCode');
            $xml->self_codeCustom($arr);

            $arr = array('code'=>'completed');
            $xml->self_customTag('statusCode', $arr);

            $arr = array('xsi:type'=>'CD', 'code'=>$mainQrdaPopulationIncArr[$cqmKey], 'displayName'=>$cqmVal, 'codeSystem' =>'2.16.840.1.113883.5.1063', 'codeSystemName'=>'ObservationValue');
            $xml->self_customTag('value', $arr);
            
            //entryRelationship Open
            $xml->open_customTag('entryRelationship', array('typeCode'=>'SUBJ', 'inversionInd'=>'true'));
            
            //observation Open
            $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
            
            $tempID = "2.16.840.1.113883.10.20.27.3.3";
            $xml->self_templateid($tempID);

            $tempID = "2.16.840.1.113883.10.20.27.3.24";
            $xml->self_templateid($tempID);
            
            $arr = array('code'=>'MSRAGG', 'displayName'=>'rate aggregation', 'codeSystem'=>'2.16.840.1.113883.5.4', 'codeSystemName'=>'ActCode');
            $xml->self_codeCustom($arr);
            
            //$arr = array('code'=>'completed');
            //$xml->self_customTag('statusCode', $arr);
            
            $arr = array('xsi:type'=>'INT', 'value'=>count($fullPatArr));
            $xml->self_customTag('value', $arr);
            
            $arr = array('code'=>'COUNT', 'displayName'=>'Count', 'codeSystem'=>'2.16.840.1.113883.5.84', 'codeSystemName'=>'ObservationMethod');
            $xml->self_customTag('methodCode', $arr);
            
            //observation Close
            $xml->close_customTag();
            
            //entryRelationship Close
            $xml->close_customTag();
            
            #### Stratum Start (Stratification)#####
            if( ($row['cqm_nqf_code'] == "0024") && ( $row['population_label'] == "Population Criteria 1" || $row['population_label'] == "Population Criteria 2" || $row['population_label'] == "Population Criteria 3") ){
                
                if($cnt <= 12){
                    $stratumCheckArrSub = $stratumCheckArr[$cnt];
                    for($i=1;$i<=2;$i++){
                        //entryRelationship Open
                        $xml->open_customTag('entryRelationship', array('typeCode'=>'COMP'));
                        
                        //observation Open
                        $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                        
                        $tempID = "2.16.840.1.113883.10.20.27.3.4";
                        $xml->self_templateid($tempID);
                        
                        $tempID = "2.16.840.1.113883.10.20.27.3.20";
                        $xml->self_templateid($tempID);
                        
                        $arr = array('code'=>'ASSERTION', 'displayName'=>'Assertion', 'codeSystem'=>'2.16.840.1.113883.5.4', 'codeSystemName'=>'ActCode');
                        $xml->self_codeCustom($arr);
                        
                        $arr = array('code'=>'completed');
                        $xml->self_customTag('statusCode', $arr);
                        
                        //value open
                        $xml->open_customTag('value', array('xsi:type'=>'CD', 'nullFlavor'=>'OTH'));
                        
                        $stratumText = $preDefPopIdArr[$row['cqm_nqf_code']][$row['population_label']][$row['numerator_label']]['DISPLAY_TEXT'];
                        $xml->element('originalText', "Stratum".$i); 
                        
                        //value Close
                        $xml->close_customTag();
                        
                        //entryRelationship Open
                        $xml->open_customTag('entryRelationship', array('typeCode'=>'SUBJ', 'inversionInd' => 'true'));
                        
                        //observation Open
                        $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                        
                        $tempID = "2.16.840.1.113883.10.20.27.3.3";
                        $xml->self_templateid($tempID);
                        
                        $arr = array('code'=>'MSRAGG', 'displayName'=>'rate aggregation', 'codeSystem'=>'2.16.840.1.113883.5.4', 'codeSystemName'=>'ActCode');
                        $xml->self_codeCustom($arr);
                        
                        $arr = array('xsi:type'=>'INT', 'value'=>$stratumCheckArrSub[$i]);
                        $xml->self_customTag('value', $arr);
                        
                        $arr = array('code'=>'COUNT', 'displayName'=>'Count', 'codeSystem'=>'2.16.840.1.113883.5.84', 'codeSystemName'=>'ObservationMethod');
                        $xml->self_customTag('methodCode', $arr);
                        
                        //observation Close
                        $xml->close_customTag();
                    
                        //entryRelationship Close
                        $xml->close_customTag();
                        
                        //reference Start
                        $arr = array('typeCode'=>'REFR');
                        $xml->open_customTag('reference', $arr);
                        
                        //externalObservation Start
                        $arr = array('classCode'=>'OBS', 'moodCode'=>'EVN');
                        $xml->open_customTag('externalObservation', $arr);
                        
                        //Modified HQMF_ID for CQM IDS
                        //$refID = $preDefPopIdArr[$row['cqm_nqf_code']][$row['population_label']][$row['numerator_label']]['STRAT'];
                        $refID = $preDefPopIdArr[$row['cqm_nqf_code']]['STRAT'.$i];
                        
                        $xml->self_customId($refID);
                        
                        //externalObservation Close
                        $xml->close_customTag();
                        
                        //reference Close
                        $xml->close_customTag();
                        
                        //observation Close
                        $xml->close_customTag();
                        
                        //entryRelationship Close
                        $xml->close_customTag();
                    }
                }
                $cnt++;
            }
            #### Stratum END #####
            
            ####################################################
            ####################################################
            //Sex Supplemental Data Element START
            ####################################################
            ####################################################
            
            foreach($mainQrdaGenderCodeArr as $GKey => $GVal){
                //entryRelationship Open
                $xml->open_customTag('entryRelationship', array('typeCode'=>'COMP'));
                
                //observation Open
                $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                
                $tempID = "2.16.840.1.113883.10.20.27.3.6";
                $xml->self_templateid($tempID);

                $tempID = "2.16.840.1.113883.10.20.27.3.21";
                $xml->self_templateid($tempID);
                
                $arr = array('code'=>'184100006', 'displayName'=>'patient sex', 'codeSystem'=>'2.16.840.1.113883.6.96', 'codeSystemName'=>'SNOMED-CT');
                $xml->self_codeCustom($arr);
                
                $arr = array('code'=>'completed');
                $xml->self_customTag('statusCode', $arr);
                
                $arr = array('xsi:type'=>'CD', 'code'=>$GKey, 'codeSystem' =>'2.16.840.1.113883.5.1', 'codeSystemName'=>'AdministrativeGenderCode');
                $xml->self_customTag('value', $arr);
                
                //entryRelationship Open
                $xml->open_customTag('entryRelationship', array('typeCode'=>'SUBJ', 'inversionInd'=>'true'));
                
                //observation Open
                $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                
                $tempID = "2.16.840.1.113883.10.20.27.3.3";
                $xml->self_templateid($tempID);

                $tempID = "2.16.840.1.113883.10.20.27.3.24";
                $xml->self_templateid($tempID);
                
                $arr = array('code'=>'MSRAGG', 'displayName'=>'rate aggregation', 'codeSystem'=>'2.16.840.1.113883.5.4', 'codeSystemName'=>'ActCode');
                $xml->self_codeCustom($arr);
                
                //$arr = array('code'=>'completed');
                //$xml->self_customTag('statusCode', $arr);
                
                $arr = array('xsi:type'=>'INT', 'value'=>$detailsArr['gender'][$GVal]);
                $xml->self_customTag('value', $arr);
                
                $arr = array('code'=>'COUNT', 'displayName'=>'Count', 'codeSystem'=>'2.16.840.1.113883.5.84', 'codeSystemName'=>'ObservationMethod');
                $xml->self_customTag('methodCode', $arr);
                
                //observation Close
                $xml->close_customTag();
                
                //entryRelationship Close
                $xml->close_customTag();
                
                
                //observation Close
                $xml->close_customTag();
                
                //entryRelationship Close
                $xml->close_customTag();
            }
            ####################################################
            ####################################################
            //Sex Supplemental Data Element END
            ####################################################
            ####################################################
            
            ####################################################
            ####################################################
            //Ethnicity Supplemental Data Element (CMS EP) START
            ####################################################
            ####################################################
            
            foreach($mainEthiArr as $ethKey => $ethVal){
                //entryRelationship Open
                $xml->open_customTag('entryRelationship', array('typeCode'=>'COMP'));
                
                //observation Open
                $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                
                $tempID = "2.16.840.1.113883.10.20.27.3.7";
                $xml->self_templateid($tempID);

                $tempID = "2.16.840.1.113883.10.20.27.3.22";
                $xml->self_templateid($tempID);
                
                $arr = array('code'=>'364699009', 'displayName'=>'Ethnic Group', 'codeSystem'=>'2.16.840.1.113883.6.96', 'codeSystemName'=>'SNOMED-CT');
                $xml->self_codeCustom($arr);
                
                $arr = array('code'=>'completed');
                $xml->self_customTag('statusCode', $arr);
                
                $arr = array('xsi:type'=>'CD', 'code'=>$mainEthiCodeArr[$ethKey], 'displayName'=>$ethVal, 'codeSystem' =>'2.16.840.1.113883.6.238', 'codeSystemName'=>'Race &amp; Ethnicity - CDC');
                $xml->self_customTag('value', $arr);
                
                //entryRelationship Open
                $xml->open_customTag('entryRelationship', array('typeCode'=>'SUBJ', 'inversionInd'=>'true'));
                
                //observation Open
                $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                
                $tempID = "2.16.840.1.113883.10.20.27.3.3";
                $xml->self_templateid($tempID);

                $tempID = "2.16.840.1.113883.10.20.27.3.24";
                $xml->self_templateid($tempID);
                
                $arr = array('code'=>'MSRAGG', 'displayName'=>'rate aggregation', 'codeSystem'=>'2.16.840.1.113883.5.4', 'codeSystemName'=>'ActCode');
                $xml->self_codeCustom($arr);
                
                //$arr = array('code'=>'completed');
                //$xml->self_customTag('statusCode', $arr);
                
                $arr = array('xsi:type'=>'INT', 'value'=>$detailsArr['ethnicity'][$ethVal]);
                $xml->self_customTag('value', $arr);
                
                $arr = array('code'=>'COUNT', 'displayName'=>'Count', 'codeSystem'=>'2.16.840.1.113883.5.84', 'codeSystemName'=>'ObservationMethod');
                $xml->self_customTag('methodCode', $arr);
                
                //observation Close
                $xml->close_customTag();
                
                //entryRelationship Close
                $xml->close_customTag();
                
                
                //observation Close
                $xml->close_customTag();
                
                //entryRelationship Close
                $xml->close_customTag();
                
            }
            ####################################################
            ####################################################
            //Ethnicity Supplemental Data Element (CMS EP) END
            ####################################################
            ####################################################
            
            
            ####################################################
            ####################################################
            //Race Supplemental Data Element (CMS EP) START
            ####################################################
            ####################################################
            
            foreach($mainQrdaRaceArr as $RKey => $RVal){
                //entryRelationship Open
                $xml->open_customTag('entryRelationship', array('typeCode'=>'COMP'));
                
                //observation Open
                $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                
                $tempID = "2.16.840.1.113883.10.20.27.3.8";
                $xml->self_templateid($tempID);

                $tempID = "2.16.840.1.113883.10.20.27.3.19";
                $xml->self_templateid($tempID);
                
                $arr = array('code'=>'103579009', 'displayName'=>'Race', 'codeSystem'=>'2.16.840.1.113883.6.96', 'codeSystemName'=>'SNOMED-CT');
                $xml->self_codeCustom($arr);
                
                $arr = array('code'=>'completed');
                $xml->self_customTag('statusCode', $arr);
                
                $arr = array('xsi:type'=>'CD', 'code'=>$mainQrdaRaceCodeArr[$RKey], 'displayName'=>$RVal, 'codeSystem' =>'2.16.840.1.113883.6.238', 'codeSystemName'=>'Race &amp; Ethnicity - CDC');
                $xml->self_customTag('value', $arr);
                
                //entryRelationship Open
                $xml->open_customTag('entryRelationship', array('typeCode'=>'SUBJ', 'inversionInd'=>'true'));
                
                //observation Open
                $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                
                $tempID = "2.16.840.1.113883.10.20.27.3.3";
                $xml->self_templateid($tempID);

                $tempID = "2.16.840.1.113883.10.20.27.3.24";
                $xml->self_templateid($tempID);
                
                $arr = array('code'=>'MSRAGG', 'displayName'=>'rate aggregation', 'codeSystem'=>'2.16.840.1.113883.5.4', 'codeSystemName'=>'ActCode');
                $xml->self_codeCustom($arr);
                
                //$arr = array('code'=>'completed');
                //$xml->self_customTag('statusCode', $arr);
                
                $arr = array('xsi:type'=>'INT', 'value'=>$detailsArr['race'][$RVal]);
                $xml->self_customTag('value', $arr);
                
                $arr = array('code'=>'COUNT', 'displayName'=>'Count', 'codeSystem'=>'2.16.840.1.113883.5.84', 'codeSystemName'=>'ObservationMethod');
                $xml->self_customTag('methodCode', $arr);
                
                //observation Close
                $xml->close_customTag();
                
                //entryRelationship Close
                $xml->close_customTag();
                
                
                //observation Close
                $xml->close_customTag();
                
                //entryRelationship Close
                $xml->close_customTag();
            }
            
            ####################################################
            ####################################################
            //Race Supplemental Data Element (CMS EP) END
            ####################################################
            ####################################################
            
            
            ####################################################
            ####################################################
            //Payer Supplemental Data Element (CMS EP) START
            ####################################################
            ####################################################
            $payerCheckArr = getQRDAPayerInfo($fullPatArr);
            foreach($mainQrdaPayerCodeArr as $PKey => $PVal){
                //entryRelationship Open
                $xml->open_customTag('entryRelationship', array('typeCode'=>'COMP'));
                
                //observation Open
                $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                
                $tempID = "2.16.840.1.113883.10.20.27.3.9";
                $xml->self_templateid($tempID);

                $tempID = "2.16.840.1.113883.10.20.24.3.55";
                $xml->self_templateid($tempID);
                
                $tempID = "2.16.840.1.113883.10.20.27.3.18";
                $xml->self_templateid($tempID);
                
                $xml->self_setpatientRoleid();
                
                $arr = array('code'=>'48768-6', 'displayName'=>'Payment source', 'codeSystem'=>'2.16.840.1.113883.6.1', 'codeSystemName'=>'SNOMED-CT');
                $xml->self_codeCustom($arr);
                
                $arr = array('code'=>'completed');
                $xml->self_customTag('statusCode', $arr);
                
                $timeArr = array('low'=>date('Ymd', strtotime($from_date)));
                $xml->add_entryEffectTime($timeArr);
                
                /*
                //Value Tag Open
                $xml->open_customTag('value', array('xsi:type'=>'CD', 'nullFlavor'=>'OTH'));
                
                $xml->self_customTag('translation', array('code'=>$PKey, 'displayName'=>$PVal, 'codeSystem'=>'2.16.840.1.113883.3.249.12', 'codeSystemName'=>'CMS Clinical Codes'));
                
                //Value Tag Close
                $xml->close_customTag();
                */
                
                //Value Tag 
                $xml->self_customTag('value', array('xsi:type'=>'CD', 'code'=>$mainQrdaPayerCodeSendArr[$PKey], 'codeSystem'=>'2.16.840.1.113883.3.221.5' , 'codeSystemName'=>'SOP', 'displayName'=>$PVal));
                
                //entryRelationship Open
                $xml->open_customTag('entryRelationship', array('typeCode'=>'SUBJ', 'inversionInd'=>'true'));
                
                //observation Open
                $xml->open_customTag('observation', array('classCode'=>'OBS', 'moodCode'=>'EVN'));
                
                $tempID = "2.16.840.1.113883.10.20.27.3.3";
                $xml->self_templateid($tempID);

                $tempID = "2.16.840.1.113883.10.20.27.3.24";
                $xml->self_templateid($tempID);
                
                $arr = array('code'=>'MSRAGG', 'displayName'=>'rate aggregation', 'codeSystem'=>'2.16.840.1.113883.5.4', 'codeSystemName'=>'ActCode');
                $xml->self_codeCustom($arr);
                
                //$arr = array('code'=>'completed');
                //$xml->self_customTag('statusCode', $arr);
                
                $arr = array('xsi:type'=>'INT', 'value'=>$payerCheckArr[$PVal]);
                $xml->self_customTag('value', $arr);
                
                $arr = array('code'=>'COUNT', 'displayName'=>'Count', 'codeSystem'=>'2.16.840.1.113883.5.84', 'codeSystemName'=>'ObservationMethod');
                $xml->self_customTag('methodCode', $arr);
                
                //observation Close
                $xml->close_customTag();
                
                //entryRelationship Close
                $xml->close_customTag();
                
                
                //observation Close
                $xml->close_customTag();
                
                //entryRelationship Close
                $xml->close_customTag();
                
            }
            ####################################################
            ####################################################
            //Payer Supplemental Data Element (CMS EP) END
            ####################################################
            ####################################################
            
            ######################################################################
            //reference Start
            $arr = array('typeCode'=>'REFR');
            $xml->open_customTag('reference', $arr);
            
            //externalObservation Start
            $arr = array('classCode'=>'OBS', 'moodCode'=>'EVN');
            $xml->open_customTag('externalObservation', $arr);
            
            //Modified HQMF_ID for CQM IDS
            if( ($row['cqm_nqf_code'] == "0421" ) ){
                $refID = $preDefPopIdArr[$row['cqm_nqf_code']][$row['numerator_label']][$mainQrdaPopulationIncArr[$cqmKey]];
            }else if( ($row['cqm_nqf_code'] == "0024") ){
                $refID = $preDefPopIdArr[$row['cqm_nqf_code']][$row['population_label']][$row['numerator_label']][$mainQrdaPopulationIncArr[$cqmKey]];
            }else{
                $refID = $preDefPopIdArr[$row['cqm_nqf_code']][$mainQrdaPopulationIncArr[$cqmKey]];
            }
            if($refID == ""){
                $refID = getUuid();
            }
            $xml->self_customId($refID);
            
            //externalObservation Close
            $xml->close_customTag();
            
            //reference Close
            $xml->close_customTag();
            ########################################################################
            
            //observation Close
            $xml->close_customTag();

            $xml->close_loopComponent();
            ############### Initial patient population template END#####################
        }
        
        //Multiple Numerator Handling
        if(in_array($row['cqm_nqf_code'], $multNumNQFArr)){
            //Skipping Multiple Numerator(s)
            if(in_array($row['cqm_nqf_code'], $multNumNQFArr)){
                $skipMultNumArr[$row['cqm_nqf_code']] = true; 
            }
            if($dataChkArr[$row['cqm_nqf_code']] == $countNumNQFArr[$row['cqm_nqf_code']]){
                //Organizer Close
                $xml->close_customTag();
                $xml->close_entry();
            }
        }else{
            //Organizer Close
            $xml->close_customTag();
            $xml->close_entry();
        }
        
        ###########################################################
        
        $innrCnt++;
    }
}

#######################################################################
######################### QUALITY MEASURES END ########################
#######################################################################


$xml->close_section();

$xml->close_loopComponent();

##################### LOOP Component(s) END ########################

$xml->close_structuredBody();
############### Structure Body Close #######################

$xml->close_mainComponent();
############### Main Component Close #######################

//Close Main Clinical Document
$xml->close_clinicaldocument();


//QRDA File Download Folder in site/cqm_qrda folder
$qrda_fname = "QRDA_III_".date("YmdHis").".xml";
$qrda_file_path = $GLOBALS['OE_SITE_DIR'] . "/documents/cqm_qrda/";
if(!file_exists($qrda_file_path)){
    mkdir($qrda_file_path, 0777, true);
}
$qrda_file_name = $qrda_file_path.$qrda_fname;
$fileQRDAOPen = fopen($qrda_file_name, "w");
fwrite($fileQRDAOPen, trim($xml->getXml()));
fclose($fileQRDAOPen);
?>

<html>
<head>
<?php html_header_show();?>
<script type="text/javascript" src="<?php echo $webroot ?>/interface/main/tabs/js/include_opener.js"></script>        
<link rel=stylesheet href="<?php echo $css_header;?>" type="text/css">
<title><?php echo xlt('Export QRDA Report'); ?></title>

<script type="text/javascript"> 
    //Close Me function
    function closeme() {      
      window.close();
    }
</script>
</head>
<body>

<p class="text"><?php echo xlt('The exported data appears in the text area below. You can copy and paste this into an email or to any other desired destination (or) download the below link.'); ?></p>

<center>
<form>
<p class="text">
    <a href="qrda_download.php?qrda_fname=<?php echo attr($qrda_fname);?>"><?php echo xlt("Download QRDA Category III File");?></a>
</p>
<textarea rows='50' cols='500' style='width:95%' readonly>
<?php echo trim($xml->getXml()); ?>
</textarea>

<p><input type='button' value='<?php echo xla('Close'); ?>' onclick='closeme();' /></p>
</form>
</center>


</body>
</html>
