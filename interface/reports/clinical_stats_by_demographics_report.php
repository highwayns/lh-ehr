<?php
/**
 * interface/reports/clinical_stats_by_demographic.php: Lists prodcedures by demographics,
 *integrates dataTables in report.  Ability to download in .pdf, .xls, or .csv.
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/mpl-license.php>;.
 * Copyright (c) 2018 Growlingflea Software <daniel@growlingflea.com>
 * File adapted for user activity log.
 * @package LibreEHR
 * @author  Daniel Pflieger daniel@growlingflea.com daniel@mi-squared.com
 */
 $fake_register_globals=false;
 $sanitize_all_escapes=true;

require_once("../globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/vendor/libreehr/Framework/DataTable/DataTable.php");
require_once("reports_controllers/ClinicalController.php");
require_once($GLOBALS['srcdir'].'/headers.inc.php');
$library_array = array('jquery-min-3-1-1', 'iziModalToast', 'datatables');
$DateFormat = DateFormatRead();
//make sure to get the dates
if ( ! isset($_POST['form_from_date'])) {

    $from_date = fixDate(date($DateFormat));

} else {
    $from_date = fixDate($_POST['form_from_date']);
}

if ( !isset($_POST['form_to_date'])) {
    // If a specific patient, default to 2 years ago.
    $to_date = fixDate(date($DateFormat));


} else{

    $to_date = fixDate($_POST['form_to_date']);
}

$to_date = new DateTime($to_date);
$to_date->modify('+1 day');
$to_date = $to_date->format('Y-m-d');
$age_from = $_POST['age_from'];
$age_to = $_POST['age_to'];


?>
<head>
<?php html_header_show();?>
<title><?php xl('Clinical Reports: Demographics vs Diagnosis','e'); ?></title>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
<?php call_required_libraries($library_array) ?>
<script type="text/javascript" src="../../library/report_validation.js"></script>

<script>
$(document).ready(function() {

    if($('#show_diags_details_selector').val()) {
        $('.session_table').hide();
        $('#show_diags_details_table').show();

        show_all_diags();
    }

});

$(document).ready(function() {
    $(".numeric_only").keydown(function(event) {
        //alert(event.keyCode);
        // Allow only backspace and delete
        if ( event.keyCode == 46 || event.keyCode == 8 ) {
            // let it happen, don't do anything
        }
        else {
            if (!((event.keyCode >= 96 && event.keyCode <= 105) || (event.keyCode >= 48 && event.keyCode <= 57))) {
                event.preventDefault();
            }
        }
    });
});

function validateInput() {
    return validateAgeRange();
}

var oTable;
// This is for callback by the find-code popup.
// Appends to or erases the current list of diagnoses.
function set_related(codetype, code, selector, codedesc) {
    var f = document.forms[0][current_sel_name];
    var s = f.value;
    if (code) {
        if (s.length > 0) s += ';';
        s += codetype + ':' + code;
    } else {
        s = '';
    }
    f.value = s;
}

//This invokes the find-code popup.
function sel_diagnosis(e) {
    current_sel_name = e.name;
    dlgopen('../patient_file/encounter/find_code_popup.php?codetype=<?php echo collect_codetypes("diagnosis","csv"); ?>', '_blank', 500, 400);
}

//This invokes the find-code popup.
function sel_procedure(e) {
    current_sel_name = e.name;
    dlgopen('../patient_file/encounter/find_code_popup.php?codetype=<?php echo collect_codetypes("procedure","csv"); ?>', '_blank', 500, 400);
}
$("#form_from_date").val();
//Function to initiate datatables plugin


function refreshPage(){

    window.location.reload();

}

function show_all_diags(){

    $('#image').show();

    oTable=$('#show_diags_details_table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'excel', 'pdf', 'csv'
        ],
        ajax:{
            type: "POST",
            url: "../../library/ajax/clinical_stats_and_lab_stats_by_demographics_report_ajax.php",
            data: {

                func:"get_all_diags_data",
                diag:"<?php  echo $_POST['form_diagnosis'];   ?>",
                ethnicity:"<?php echo $_POST['ethnicity']; ?>",
                age_from:"<?php echo $_POST['age_from']  ; ?>",
                age_to:"<?php echo $_POST['age_to']  ; ?>",
                token: "<?php echo $_SESSION['token'];?>"

            }, complete: function(){
                $('#image').hide();
            }},
        columns:[
            { 'data': 'pid'         },
            { 'data': 'sex'      },
            { 'data': 'dob'         },
            { 'data': 'ethnicity'   },
            { 'data': 'diagnosis' },
            { 'data': 'title'      }

        ],
        "iDisplayLength": 100,
        "select":true,
        "searching":true,
        "retrieve" : true
    });

    $('#column0_search_show_diags_details_table').on( 'keyup', function () {
        oTable
            .columns( 0 )
            .search( this.value )
            .draw();
    } );

    $('#column1_search_show_diags_details_table').on( 'keyup', function () {
        oTable
            .columns( 1 )
            .search( this.value)
            .draw();
    } );

    $('#column2_search_show_diags_details_table').on( 'keyup', function () {
        oTable
            .columns( 2 )
            .search( this.value )
            .draw();
    } );

    $('#column3_search_show_diags_details_table').on( 'keyup', function () {
        oTable
            .columns( 3 )
            .search( this.value )
            .draw();
    } );

    $('#column4_search_show_diags_details_table').on( 'keyup', function () {
        oTable
            .columns( 4 )
            .search( this.value )
            .draw();
    } );

    $('#column5_search_show_diags_details_table').on( 'keyup', function () {
        oTable
            .columns( 5 )
            .search( this.value )
            .draw();
    } );



}


</script>
</head>
<body class="body_top formtable">&nbsp;&nbsp;
<form action="./clinical_stats_by_demographics_report.php" method="post" name='theform' id='theform' onsubmit='return validateInput()'>
<table>
<tr>
<td><label><input value="Refresh Query" type="submit" id="show_diags_details_selector" name="show_diags_details" ><?php ?></label></td>

</tr>


    <tr>

    <td class='label'><?php echo htmlspecialchars(xl('Problem DX'),ENT_NOQUOTES); ?>:</td>
    <td><input type='text' name='form_diagnosis' size='10' maxlength='250'
               value='<?php echo htmlspecialchars($form_diagnosis, ENT_QUOTES); ?>'
               onclick='sel_diagnosis(this)' title='<?php echo htmlspecialchars(xl('Click to select or change diagnoses'), ENT_QUOTES); ?>' readonly/>
    </td>
    <td>&nbsp;</td>

    </tr>

    <tr>

        <td class='label'><?php echo htmlspecialchars(xl('Age Min'),ENT_NOQUOTES); ?>:</td>
        <td><input type='text' class='numeric_only' name='age_from' size='10' maxlength='250' id='age_from' value='<?php echo htmlspecialchars($age_from, ENT_QUOTES); ?>' > </td>
        <td></td>
        <td class='label'><?php echo htmlspecialchars(xl('Age Max'),ENT_NOQUOTES); ?>:</td>
        <td><input type='text' class='numeric_only' name='age_to' size='10' maxlength='250' id='age_to' value='<?php echo htmlspecialchars($age_to, ENT_QUOTES); ?>' > </td>

    </tr>
    <tr>
        <td class='label'><?php echo xlt('Ethnicity'); ?>:</td>
        <td><?php

            // Build a drop-down list of providers.
            //

            $query = "SELECT DISTINCT(ethnicity) as ethnicity, list_options.title, option_id FROM patient_data " .
                    " JOIN list_options on option_id = ethnicity and list_id = 'ethnicity'";

            $ures = sqlStatement($query);

            echo "   <select name='ethnicity'>\n";
            echo "    <option value=''>-- " . xlt('All') . " --\n";

            while ($urow = sqlFetchArray($ures)) {
                $ethnicity = $urow['ethnicity'];
                echo "    <option value='" . attr($ethnicity) . "'";
                if ($ethnicity == $_POST['ethnicity']) echo " selected";
                echo ">" . text(xl($ethnicity)) . "\n";
            }

            echo "   </select>\n";
            ?>
        </td>


    </tr>



    <tr><td>

            <input hidden id = 'show_diags_details_button' value = '<?php echo isset($_POST['show_diags_details']) ? $_POST['show_diags_details'] : null  ?>'>
    </td></tr>
</table>
</form>



&nbsp;&nbsp;

<img hidden id="image" src="../../images/loading.gif" width="100" height="100">



<table cellpadding="0" cellspacing="0" border="0" class="display formtable session_table" id="show_diags_details_table">
	<thead>

        <tr>
            <th><input  id = 'column0_search_show_diags_details_table'></th>
            <th><input  id = 'column1_search_show_diags_details_table'></th>
            <th><input  id = 'column2_search_show_diags_details_table'></th>
            <th><input  id = 'column3_search_show_diags_details_table'></th>
            <th><input  id = 'column4_search_show_diags_details_table'></th>
            <th align="left"><input  id = 'column5_search_show_diags_details_table'></th>
        </tr>

		<tr>
			<th><?php echo xla('PID'); ?></th>
			<th><?php echo xla('Gender'); ?></th>
			<th><?php echo xla('DOB'); ?></th>
			<th><?php echo xla('ETHNICITY'); ?></th>
			<th><?php echo xla('ICD10'); ?></th>
            <th align="left"><?php echo xla('ICD10 Text'); ?></th>
		</tr>

	</thead>
	<tbody id="users_list">
	</tbody>
</table>
</body>
<link rel="stylesheet" href="../../library/css/jquery.datetimepicker.css">
<script type="text/javascript" src="../../library/js/jquery.datetimepicker.full.min.js"></script>

<script>
    $(function() {
        $("#form_from_date").datetimepicker({
            timepicker: false,
            format: "Y-m-d"
        });
        $("#form_to_date").datetimepicker({
            timepicker: false,
            format: "Y-m-d"
        });

    });
</script>
