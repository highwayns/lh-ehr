<?php
/*
* Forms generated from formsWiz
* script to save Review of Systems Checks Form
*
* Copyright (C) 2015 Roberto Vasquez <robertogagliotta@gmail.com>
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
* @author  Roberto Vasquez <robertogagliotta@gmail.com>
* @link    http://librehealth.io 
*/ 
include_once("../../globals.php");
include_once("$srcdir/api.inc");
include_once("$srcdir/forms.inc");
foreach ($_POST as $k => $var) {
$_POST[$k] = add_escape_custom($var);
echo attr($var);
echo "\n";
}
if ($encounter == "")
$encounter = date("Ymd");
if ($_GET["mode"] == "new"){
$newid = formSubmit("form_reviewofs", $_POST, $_GET["id"], $userauthorized);
addForm($encounter, "Review of Systems Checks", $newid, "reviewofs", $pid, $userauthorized);
}elseif ($_GET["mode"] == "update") {
sqlInsert("update form_reviewofs set pid = {$_SESSION["pid"]},groupname='".$_SESSION["authProvider"]."',user='".$_SESSION["authUser"]."',authorized=$userauthorized,activity=1, date = NOW(), fever='".$_POST["fever"]."', chills='".$_POST["chills"]."', night_sweats='".$_POST["night_sweats"]."', weight_loss='".$_POST["weight_loss"]."', poor_appetite='".$_POST["poor_appetite"]."', insomnia='".$_POST["insomnia"]."', fatigued='".$_POST["fatigued"]."', depressed='".$_POST["depressed"]."', hyperactive='".$_POST["hyperactive"]."', exposure_to_foreign_countries='".$_POST["exposure_to_foreign_countries"]."', cataracts='".$_POST["cataracts"]."', cataract_surgery='".$_POST["cataract_surgery"]."', glaucoma='".$_POST["glaucoma"]."', double_vision='".$_POST["double_vision"]."', blurred_vision='".$_POST["blurred_vision"]."', poor_hearing='".$_POST["poor_hearing"]."', headaches='".$_POST["headaches"]."', ringing_in_ears='".$_POST["ringing_in_ears"]."', bloody_nose='".$_POST["bloody_nose"]."', sinusitis='".$_POST["sinusitis"]."', sinus_surgery='".$_POST["sinus_surgery"]."', dry_mouth='".$_POST["dry_mouth"]."', strep_throat='".$_POST["strep_throat"]."', tonsillectomy='".$_POST["tonsillectomy"]."', swollen_lymph_nodes='".$_POST["swollen_lymph_nodes"]."', throat_cancer='".$_POST["throat_cancer"]."', throat_cancer_surgery='".$_POST["throat_cancer_surgery"]."', heart_attack='".$_POST["heart_attack"]."', irregular_heart_beat='".$_POST["irregular_heart_beat"]."', chest_pains='".$_POST["chest_pains"]."', shortness_of_breath='".$_POST["shortness_of_breath"]."', high_blood_pressure='".$_POST["high_blood_pressure"]."', heart_failure='".$_POST["heart_failure"]."', poor_circulation='".$_POST["poor_circulation"]."', vascular_surgery='".$_POST["vascular_surgery"]."', cardiac_catheterization='".$_POST["cardiac_catheterization"]."', coronary_artery_bypass='".$_POST["coronary_artery_bypass"]."', heart_transplant='".$_POST["heart_transplant"]."', stress_test='".$_POST["stress_test"]."', emphysema='".$_POST["emphysema"]."', chronic_bronchitis='".$_POST["chronic_bronchitis"]."', interstitial_lung_disease='".$_POST["interstitial_lung_disease"]."', shortness_of_breath_2='".$_POST["shortness_of_breath_2"]."', lung_cancer='".$_POST["lung_cancer"]."', lung_cancer_surgery='".$_POST["lung_cancer_surgery"]."', pheumothorax='".$_POST["pheumothorax"]."', stomach_pains='".$_POST["stomach_pains"]."', peptic_ulcer_disease='".$_POST["peptic_ulcer_disease"]."', gastritis='".$_POST["gastritis"]."', endoscopy='".$_POST["endoscopy"]."', polyps='".$_POST["polyps"]."', colonoscopy='".$_POST["colonoscopy"]."', colon_cancer='".$_POST["colon_cancer"]."', colon_cancer_surgery='".$_POST["colon_cancer_surgery"]."', ulcerative_colitis='".$_POST["ulcerative_colitis"]."', crohns_disease='".$_POST["crohns_disease"]."', appendectomy='".$_POST["appendectomy"]."', divirticulitis='".$_POST["divirticulitis"]."', divirticulitis_surgery='".$_POST["divirticulitis_surgery"]."', gall_stones='".$_POST["gall_stones"]."', cholecystectomy='".$_POST["cholecystectomy"]."', hepatitis='".$_POST["hepatitis"]."', cirrhosis_of_the_liver='".$_POST["cirrhosis_of_the_liver"]."', splenectomy='".$_POST["splenectomy"]."', kidney_failure='".$_POST["kidney_failure"]."', kidney_stones='".$_POST["kidney_stones"]."', kidney_cancer='".$_POST["kidney_cancer"]."', kidney_infections='".$_POST["kidney_infections"]."', bladder_infections='".$_POST["bladder_infections"]."', bladder_cancer='".$_POST["bladder_cancer"]."', prostate_problems='".$_POST["prostate_problems"]."', prostate_cancer='".$_POST["prostate_cancer"]."', kidney_transplant='".$_POST["kidney_transplant"]."', sexually_transmitted_disease='".$_POST["sexually_transmitted_disease"]."', burning_with_urination='".$_POST["burning_with_urination"]."', discharge_from_urethra='".$_POST["discharge_from_urethra"]."', rashes='".$_POST["rashes"]."', infections='".$_POST["infections"]."', ulcerations='".$_POST["ulcerations"]."', pemphigus='".$_POST["pemphigus"]."', herpes='".$_POST["herpes"]."', osetoarthritis='".$_POST["osetoarthritis"]."', rheumotoid_arthritis='".$_POST["rheum
otoid_arthritis"]."', lupus='".$_POST["lupus"]."', ankylosing_sondlilitis='".$_POST["ankylosing_sondlilitis"]."', swollen_joints='".$_POST["swollen_joints"]."', stiff_joints='".$_POST["stiff_joints"]."', broken_bones='".$_POST["broken_bones"]."', neck_problems='".$_POST["neck_problems"]."', back_problems='".$_POST["back_problems"]."', back_surgery='".$_POST["back_surgery"]."', scoliosis='".$_POST["scoliosis"]."', herniated_disc='".$_POST["herniated_disc"]."', shoulder_problems='".$_POST["shoulder_problems"]."', elbow_problems='".$_POST["elbow_problems"]."', wrist_problems='".$_POST["wrist_problems"]."', hand_problems='".$_POST["hand_problems"]."', hip_problems='".$_POST["hip_problems"]."', knee_problems='".$_POST["knee_problems"]."', ankle_problems='".$_POST["ankle_problems"]."', foot_problems='".$_POST["foot_problems"]."', insulin_dependent_diabetes='".$_POST["insulin_dependent_diabetes"]."', noninsulin_dependent_diabetes='".$_POST["noninsulin_dependent_diabetes"]."', hypothyroidism='".$_POST["hypothyroidism"]."', hyperthyroidism='".$_POST["hyperthyroidism"]."', cushing_syndrom='".$_POST["cushing_syndrom"]."', addison_syndrom='".$_POST["addison_syndrom"]."', additional_notes='".$_POST["additional_notes"]."' where id=$id");
}
$_SESSION["encounter"] = $encounter;
formHeader("Redirecting....");
formJump();
formFooter();
?>
