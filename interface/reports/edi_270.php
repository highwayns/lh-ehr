<?php
/*
 *  main file for the 270 batch creation.
 *  This report is the batch report required for batch eligibility verification.
 *
 *  This program creates the batch for the x12 270 eligibility file
 *  The changes to this file as of November 16 2016  to bring it to the 5010 standard
 *  are covered under the terms of the Mozilla Public License, v. 2.0
 *
 * @copyright Copyright (C) 2016-2017 Terry Hill <teryhill@librehealth.io>
 *
 * Copyright (C) 2010 MMF Systems, Inc>
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
 * along with this program. If not, see http://opensource.org/licenses/gpl-license.php.
 *
 * LICENSE: This Source Code is subject to the terms of the Mozilla Public License, v. 2.0.
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreHealth EHR
 * @author Terry Hill <teryhill@librehealth.io>
 * No other authors mentioned in the previous header file.
 * @link http://librehealth.io
 *
 * Please help the overall project by sending changes you make to the author and to the LibreHealth EHR community.
 *
 */

require_once "reports_controllers/Edi270Controller.php";

?>

<html>

    <head>

        <?php html_header_show();?>

        <title><?php echo htmlspecialchars( xl('Eligibility 270 Inquiry Batch'), ENT_NOQUOTES); ?></title>

        <link rel="stylesheet" href="../../library/css/jquery.datetimepicker.css">

        <link rel=stylesheet href="<?php echo $css_header;?>" type="text/css">

        <style type="text/css">

            /* specifically include & exclude from printing */
            @media print {
                #report_parameters {
                    visibility: hidden;
                    display: none;
                }
                #report_parameters_daterange {
                    visibility: visible;
                    display: inline;
                }
                #report_results table {
                   margin-top: 0px;
                }
            }

            /* specifically exclude some from the screen */
            @media screen {
                #report_parameters_daterange {
                    visibility: hidden;
                    display: none;
                }
            }

        </style>

        <script type="text/javascript" src="../../library/textformat.js"></script>
        <script type="text/javascript" src="../../library/dialog.js"></script>
        <script type="text/javascript" src="../../library/report_validation.js"></script>

        <?php
            call_required_libraries(array("jquery-min-3-1-1", "iziModalToast"));
        ?>

        <script type="text/javascript">

            var stringDelete = "<?php echo htmlspecialchars( xl('Do you want to remove this record?'), ENT_QUOTES); ?>?";
            var stringBatch  = "<?php echo htmlspecialchars( xl('Please select X12 partner, required to create the 270 batch'), ENT_QUOTES); ?>";

            // for form refresh
            function refreshme() {
                document.forms[0].submit();
            }

            // validate form input before submission
            function validateInput() {
                return top.restoreSession() && validateFromAndToDates();
            }

            //  To delete the row from the reports section
            function deletetherow(id){
                var suredelete = confirm(stringDelete);
                if(suredelete == true){
                    document.getElementById('PR'+id).style.display="none";
                    if(document.getElementById('removedrows').value == ""){
                        document.getElementById('removedrows').value = "'" + id + "'";
                    }else{
                        document.getElementById('removedrows').value = document.getElementById('removedrows').value + ",'" + id + "'";

                    }
                }

            }

            //  To validate the batch file generation - for the required field [clearing house/x12 partner]
            function validate_batch()
            {
                if(document.getElementById('form_x12').value=='')
                {
                    alert(stringBatch);
                    return false;
                }
                else
                {
                    document.getElementById('form_savefile').value = "true";
                    document.theform.submit();

                }

            }

            // To Clear the hidden input field
            function validate_policy()
            {
                document.getElementById('removedrows').value = "";
                document.getElementById('form_savefile').value = "";
                return true;
            }

            // To toggle the clearing house empty validation message
            function toggleMessage(id,x12){

                var spanstyle = new String();

                spanstyle       = document.getElementById(id).style.visibility;
                selectoption    = document.getElementById(x12).value;

                if(selectoption != '')
                {
                    document.getElementById(id).style.visibility = "hidden";
                }
                else
                {
                    document.getElementById(id).style.visibility = "visible";
                    document.getElementById(id).style.display = "inline";
                }
                return true;

            }

        </script>

    </head>
    <body class="body_top">

        <!-- Required for the popup date selectors -->
        <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

        <span class='title'><?php echo htmlspecialchars( xl('Report'), ENT_NOQUOTES); ?> - <?php echo htmlspecialchars( xl('Eligibility 270 Inquiry Batch'), ENT_NOQUOTES); ?></span>

        <?php reportParametersDaterange(); #TRK ?>

        <form method='post' name='theform' id='theform' action='edi_270.php' onsubmit='return validateInput()'>
            <input type="hidden" name="removedrows" id="removedrows" value="">
            <div id="report_parameters">
                <table>
                    <tr>
                        <td width='550px'>
                            <div style='float:left'>
                                <table class='text'>
                                    <tr>
                                        <?php // Show From and To dates fields. (TRK)
                                            showFromAndToDates(); ?>
                                        <td>&nbsp;</td>
                                    </tr>

                                    <tr>
                                        <td class='label'>
                                            <?php echo htmlspecialchars( xl('Facility'), ENT_NOQUOTES); ?>:
                                        </td>
                                        <td>
                                            <?php dropdown_facility($form_facility,'form_facility',false);  ?>
                                        </td>
                                        <td class='label'>
                                           <?php echo htmlspecialchars( xl('Provider'), ENT_NOQUOTES); ?>:
                                        </td>
                                        <td>
                                            <select name='form_users' onchange='form.submit();'>
                                                <option value=''>-- <?php echo htmlspecialchars( xl('All'), ENT_NOQUOTES); ?> --</option>
                                                <?php foreach($providers as $user): ?>
                                                    <option value='<?php echo htmlspecialchars( $user['id'], ENT_QUOTES); ?>'
                                                        <?php echo $form_provider == $user['id'] ? " selected " : null; ?>
                                                    ><?php echo htmlspecialchars( $user['fname']." ".$user['lname'], ENT_NOQUOTES); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>&nbsp;
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class='label'>
                                            <?php echo htmlspecialchars( xl('X12 Partner'), ENT_NOQUOTES); ?>:
                                        </td>
                                        <td colspan='5'>
                                            <select name='form_x12' id='form_x12' onchange='return toggleMessage("emptyVald","form_x12");' >
                                                        <option value=''>--<?php echo htmlspecialchars( xl('select'), ENT_NOQUOTES); ?>--</option>
                                                        <?php
                                                            if(isset($clearinghouses) && !empty($clearinghouses))
                                                            {
                                                                foreach($clearinghouses as $clearinghouse): ?>
                                                                    <option value='<?php echo htmlspecialchars( $clearinghouse['id']."|".$clearinghouse['id_number']."|".$clearinghouse['x12_sender_id']."|".$clearinghouse['x12_receiver_id']."|".$clearinghouse['x12_version']."|".$clearinghouse['processing_format'], ENT_QUOTES); ?>'
                                                                        <?php echo $clearinghouse['id'] == $X12info[0] ? " selected " : null; ?>
                                                                    ><?php echo htmlspecialchars( $clearinghouse['name'], ENT_NOQUOTES); ?></option>
                                                        <?php   endforeach;
                                                            }

                                                        ?>
                                                </select>
                                                <span id='emptyVald' style='color:red;font-size:12px;'> * <?php echo htmlspecialchars( xl('Clearing house info required for EDI 270 batch creation.'), ENT_NOQUOTES); ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                        <td align='left' valign='middle' height="100%">
                            <table style='border-left:1px solid; width:80%; height:100%; margin-left: 3%' >
                                <tr>
                                    <td>
                                        <div style='margin-left:15px'>
                                            <a href='#' class='css_button cp-misc' onclick='validate_policy(); $("#theform").submit();'>
                                            <span>
                                                <?php echo htmlspecialchars( xl('Refresh'), ENT_NOQUOTES); ?>
                                            </span>
                                            </a>

                                            <a href='#' class='css_button cp-misc' onclick='return validate_batch();'>
                                                <span>
                                                    <?php echo htmlspecialchars( xl('Create batch'), ENT_NOQUOTES); ?>
                                                    <input type='hidden' name='form_savefile' id='form_savefile' value=''></input>
                                                </span>
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            <div class='text'>
                <?php echo htmlspecialchars( xl('Please choose date range criteria above, and click Refresh to view results.'), ENT_NOQUOTES); ?>
            </div>

        </form>

        <?php
            if ($res){
                show_elig($res,$X12info,$segTer,$compEleSep);
            }
        ?>
    </body>

    <script language='JavaScript'>
        <?php if ($alertmsg) { echo " alert('$alertmsg');\n"; } ?>
    </script>
<script type="text/javascript" src="../../library/js/jquery.datetimepicker.full.min.js"></script>
<script>
    $(function() {
        $("#form_from_date").datetimepicker({
            timepicker: false,
            format: "<?= $DateFormat; ?>"
        });
        $("#form_to_date").datetimepicker({
            timepicker: false,
            format: "<?= $DateFormat; ?>"
        });
        $.datetimepicker.setLocale('<?= $DateLocale; ?>');
    });
</script>

</html>
