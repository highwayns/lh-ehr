<?php

require_once ($GLOBALS['fileroot'] . "/library/classes/Controller.class.php");
require_once ($GLOBALS['fileroot'] . "/library/forms.inc");
require_once ($GLOBALS['fileroot'] . "/library/patient.inc");
require_once("FormVitals.class.php");

class C_FormVitals extends Controller {

    var $template_dir;
    var $form_action;
    var $dont_save_link;
    var $style;
    var $units_of_measurement;
    var $gbl_vitals_options;
    var $vitals;
    var $results;
    var $patient_age;
    var $patient_dob;
    var $view;

    function __construct($template_mod = "general") {
        parent::__construct();
        $returnurl = 'encounter_top.php';
        $this->template_mod = $template_mod;
        $this->template_dir = dirname(__FILE__) . "/templates/vitals/";
        //$this->assign("FORM_ACTION", $GLOBALS['web_root']);
        //$this->assign("DONT_SAVE_LINK",$GLOBALS['webroot'] . "/interface/patient_file/encounter/$returnurl");
        //$this->assign("STYLE", $GLOBALS['style']);
        $this->form_action = $GLOBALS['web_root'];
        $this->style = $GLOBALS['style'];
        $this->dont_save_link = $GLOBALS['webroot'] . "/interface/patient_file/encounter/$returnurl";


        // Options for units of measurement and things to omit.
        // $this->assign("units_of_measurement",$GLOBALS['units_of_measurement']);
        //$this->assign("gbl_vitals_options",$GLOBALS['gbl_vitals_options']);
        $this->units_of_measurement = $GLOBALS['units_of_measurement'];
        $this->gbl_vitals_options = $GLOBALS['gbl_vitals_options'];
    }

    function default_action_old() {
        //$vitals = array();
        //array_push($vitals, new FormVitals());
        $vitals = new FormVitals();
        //$this->assign("vitals",$vitals);
        //$this->assign("results", $results);
        //return $this->fetch($this->template_dir . $this->template_mod . "_new.html");

        $this->vitals = $vitals;
        $this->results = $results;

        ob_start();
        require_once($this->template_dir . $this->template_mod . "_new.php");
        $echoed_content = ob_get_clean(); // gets content, discards buffer
        return $echoed_content;
    }

    function default_action($form_id) {

        if (is_numeric($form_id)) {
            $vitals = new FormVitals($form_id);
        } else {
            $vitals = new FormVitals();
        }

        $dbconn = $GLOBALS['adodb']['db'];
        //Combined query for retrieval of vital information which is not deleted
        $sql = "SELECT fv.*, fe.date AS encdate " .
                "FROM form_vitals AS fv, forms AS f, form_encounter AS fe WHERE " .
                "fv.id != $form_id and fv.pid = " . $GLOBALS['pid'] . " AND " .
                "f.formdir = 'vitals' AND f.deleted = 0 AND f.form_id = fv.id AND " .
                "fe.pid = f.pid AND fe.encounter = f.encounter " .
                "ORDER BY encdate DESC, fv.date DESC";
        $result = $dbconn->Execute($sql);

        // get the patient's current age
        $patient_data = getPatientData($GLOBALS['pid']);
        $patient_dob = $patient_data['DOB'];
        $patient_age = getPatientAge($patient_dob);
        //$this->assign("patient_age", $patient_age);
        //$this->assign("patient_dob",$patient_dob);
        $this->patient_age = $patient_age;
        $this->patient_dob = $patient_dob;

        $i = 1;
        while ($result && !$result->EOF) {
            $results[$i]['id'] = $result->fields['id'];
            $results[$i]['encdate'] = substr($result->fields['encdate'], 0, 10);
            $results[$i]['date'] = $result->fields['date'];
            $results[$i]['activity'] = $result->fields['activity'];
            $results[$i]['bps'] = $result->fields['bps'];
            $results[$i]['bpd'] = $result->fields['bpd'];
            $results[$i]['weight'] = $result->fields['weight'];
            $results[$i]['height'] = $result->fields['height'];
            $results[$i]['temperature'] = $result->fields['temperature'];
            $results[$i]['temp_method'] = $result->fields['temp_method'];
            $results[$i]['pulse'] = $result->fields['pulse'];
            $results[$i]['respiration'] = $result->fields['respiration'];
            $results[$i]['BMI'] = $result->fields['BMI'];
            $results[$i]['BMI_status'] = $result->fields['BMI_status'];
            $results[$i]['note'] = $result->fields['note'];
            $results[$i]['waist_circ'] = $result->fields['waist_circ'];
            $results[$i]['head_circ'] = $result->fields['head_circ'];
            $results[$i++]['oxygen_saturation'] = $result->fields['oxygen_saturation'];
            $result->MoveNext();
        }

        //$this->assign("vitals",$vitals);
        //$this->assign("results", $results);
        //$this->assign("VIEW",true);
        //return $this->fetch($this->template_dir . $this->template_mod . "_new.html");

        $this->vitals = $vitals;
        $this->results = $results;
        $this->view = true;

        ob_start();
        require_once($this->template_dir . $this->template_mod . "_new.php");
        $echoed_content = ob_get_clean(); // gets content, discards buffer
        return $echoed_content;
    }

    function default_action_process() {
        if ($_POST['process'] != "true")
            return;

        $weight = $_POST["weight"];
        $height = $_POST["height"];
        if ($weight > 0 && $height > 0) {
            $_POST["BMI"] = ($weight / $height / $height) * 703;
        }
        if ($_POST["BMI"] > 42)
            $_POST["BMI_status"] = 'Obesity III';
        elseif ($_POST["BMI"] > 34)
            $_POST["BMI_status"] = 'Obesity II';
        elseif ($_POST["BMI"] > 30)
            $_POST["BMI_status"] = 'Obesity I';
        elseif ($_POST["BMI"] > 27)
            $_POST["BMI_status"] = 'Overweight';
        elseif ($_POST["BMI"] > 25)
            $_POST["BMI_status"] = 'Normal BL';
        elseif ($_POST["BMI"] > 18.5)
            $_POST["BMI_status"] = 'Normal';
        elseif ($_POST["BMI"] > 10)
            $_POST["BMI_status"] = 'Underweight';
        $temperature = $_POST["temperature"];
        if ($temperature == '0' || $temperature == '') {
            $_POST["temp_method"] = "";
        }

        $this->vitals = new FormVitals($_POST['id']);

        parent::populate_object($this->vitals);

        $this->vitals->persist();
        if ($GLOBALS['encounter'] < 1) {
            $GLOBALS['encounter'] = date("Ymd");
        }
        if (empty($_POST['id'])) {
            addForm($GLOBALS['encounter'], "Vitals", $this->vitals->id, "vitals", $GLOBALS['pid'], $_SESSION['userauthorized']);
            $_POST['process'] = "";
        }
        return;
    }

}

?>
