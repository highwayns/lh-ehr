<?php

require_once("{$GLOBALS['srcdir']}/sql.inc");

$GLOBALS['form_exit_url'] = "$rootdir/patient_file/encounter/encounter_top.php";

function getFormById ($id, $cols = "*")
{
    $sql = "select $cols from forms where id=? order by date DESC limit 0,1";
    //echo $sql . "<br />";
    return sqlQuery($sql, array($id));
}

function getFormInfoById ($id, $cols = "*")
{
    $sql = "select $cols from forms where id=? order by date DESC limit 0,1";
    //echo $sql . "<br />";
    $result =  sqlQuery($sql, array($id));
    if ($result['formdir'] == "patient_encounter") {
        $result['formdir'] = "encounter";
    }
    $sql = "select * from form_" . $result['formdir'] . " where id=?";
    //echo $sql . "<br />";
    $result =  sqlQuery($sql, array($result['form_id']));
    //print_r($result);
    return $result;
}

function getFormsByPid ($pid, $cols = "*")
{
    return sqlQuery("select $cols from forms where pid =?", array($pid));
}

function getFormByEncounter($pid,$encounter, $cols="form_id, form_name", $name="")
{
    $arraySqlBind = array();
    $sql = "select $cols from forms where encounter = ? and pid=? ";
    array_push($arraySqlBind,$encounter,$pid);
    if(!empty($name)){
        $sql .= "and form_name=? ";
        array_push($arraySqlBind,$name);
    }
  // This puts vitals first in the list, and patient_encounter last:
  $sql .= "ORDER BY FIND_IN_SET(formdir,'vitals') DESC, date DESC";

    $res = sqlStatement($sql,$arraySqlBind);

    for($iter=0; $row=sqlFetchArray($res); $iter++)
    {
        $all[$iter] = $row;
    }
    return $all;
}

function addForm($encounter, $form_name, $form_id, $formdir, $pid,
 $authorized = "0", $date="NOW()", $user="", $group="")
{
    if (!$user) $user = $_SESSION['authUser'];
    if (!$group) $group = $_SESSION['authProvider'];

        $arraySqlBind = array();
    $sql = "insert into forms (date, encounter, form_name, form_id, pid, " .
        "user, groupname, authorized, formdir) values (";
    if($date == "NOW()") {
        $sql .= "$date";
        }
    else {
        $sql .= "?";
                array_push($arraySqlBind,$date);
        }
    $sql .= ", ?, ?, ?, ?, ?, ?, ?, ?)";
        array_push($arraySqlBind,$encounter,$form_name,$form_id,$pid,$user,$group,$authorized,$formdir);
    return sqlInsert($sql,$arraySqlBind);
}

function authorizeForm($id, $authorized = "1")
{
    sqlQuery("update forms set authorized = '$authorized' where id = '$id'");
}

function getEncounters($pid,$dateStart='',$dateEnd='',$encounterRuleType='')
{

        $arraySqlBind = array();
        
        if ($encounterRuleType) {
                // Only collect certain type of encounters (list_options item from the rule_enc_types list that is mapped via enc_category_map table)
                $from = "form_encounter LEFT JOIN enc_category_map ON (form_encounter.pc_catid = enc_category_map.main_cat_id)";
                $where = "enc_category_map.rule_enc_id = ? and ";
                array_push($arraySqlBind,$encounterRuleType);
        }
        else {
                // Collect all encounters
                $from = "form_encounter";
        }

        if ($dateStart && $dateEnd) {
                $where .= "form_encounter.pid = ? and form_encounter.date >= ? and form_encounter.date <= ?";
                array_push($arraySqlBind,$pid,$dateStart,$dateEnd);
        }
        else if ($dateStart && !$dateEnd) {
                $where .= "form_encounter.pid = ? and form_encounter.date >= ?";
                array_push($arraySqlBind,$pid,$dateStart);
        }
        else if (!$dateStart && $dateEnd) {
                $where .= "form_encounter.pid = ? and form_encounter.date <= ?";
                array_push($arraySqlBind,$pid,$dateEnd);
        }
        else {
                $where .= "form_encounter.pid = ?";
                array_push($arraySqlBind,$pid);
        }

        $res = sqlStatement("SELECT distinct encounter FROM $from WHERE $where ORDER by date desc;", $arraySqlBind);

        for($iter=0; $row=sqlFetchArray($res); $iter++)
        {
                $all[$iter] = $row;
        }
        return $all;
}

function getEncounterDateByEncounter($encounter)
{
    // $sql = "select date from forms where encounter='$encounter' order by date";
    $sql = "select date from form_encounter where encounter=? order by date";
    return sqlQuery($sql, array($encounter));
}

function getProviderIdOfEncounter($encounter)
{
        $sql = "select provider_id from form_encounter where encounter = ? order by date";
        $res = sqlQuery($sql, array($encounter));
        return $res['provider_id'];
}

function getFormNameByFormdirAndFormid ($formdir,$form_id)
{
        return sqlQuery("select form_name from forms where formdir=? and form_id=?", array($formdir, $form_id));
}

function getFormIdByFormdirAndFormid ($formdir,$form_id)
{
    $result = sqlQuery("select id from forms where formdir = ? and form_id =? ", array( $formdir, $form_id ) );
    return $result['id'];
}

function getFormNameByFormdir ($formdir)
{
    return sqlQuery("select form_name from forms where formdir=?", array($formdir));
}

function getDocumentsByEncounter($patientID = null,$encounterID = null) {
    $allDocuments = null;
    $currentEncounter = ( $encounterID ) ? $encounterID : $_SESSION['encounter'];
    $currentPatient = ( $patientID ) ? $patientID : $_SESSION['pid'];
    
    if($currentPatient != "" && $currentEncounter != "") {
        $sql = "SELECT d.id, d.type, d.url, d.docdate, d.list_id, c.name,d.encounter_id FROM documents AS d, categories_to_documents AS cd, 
            categories AS c WHERE d.foreign_id = ? AND d.encounter_id=? AND cd.document_id = d.id AND c.id = cd.category_id ORDER BY d.docdate DESC, d.id DESC";
        $res = sqlStatement($sql,array($currentPatient,$currentEncounter));
    
        while ( $row = sqlFetchArray($res) ) {
            $allDocuments[] = $row;
        }
    }
    return $allDocuments;
}
?>
