<?php
/**
 * Portal Registration Wizard
 *
 * @package LibreHealth EHR
 * @link    http://librehealth.io
 * @author  Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2017 Jerry Padgett <sjpadgett@gmail.com>
 * @license https://www.gnu.org/licenses/agpl-3.0.en.html GNU Affero General Public License 3
 */

session_start();
session_regenerate_id(true);

unset($_SESSION['itsme']);

$_SESSION['authUser'] = 'portal-user';
$_SESSION['pid'] = true;
$_SESSION['register'] = true;

$_SESSION['site_id'] = isset($_SESSION['site_id']) ? $_SESSION['site_id'] : 'default';

$landingpage = "index.php?site=" . $_SESSION['site_id'];

$ignoreAuth_onsite_portal = true;

require_once("../../interface/globals.php");

$res2 = sqlStatement("select * from lang_languages where lang_description = ?", array(
    $GLOBALS['language_default']
));
for ($iter = 0; $row = sqlFetchArray($res2); $iter ++) {
    $result2[$iter] = $row;
}

if (count($result2) == 1) {
    $defaultLangID = $result2[0]{"lang_id"};
    $defaultLangName = $result2[0]{"lang_description"};
} else {
    // default to english if any problems
    $defaultLangID = 1;
    $defaultLangName = "English";
}

if (! isset($_SESSION['language_choice'])) {
    $_SESSION['language_choice'] = $defaultLangID;
}
// collect languages if showing language menu
if ($GLOBALS['language_menu_login']) {
    // sorting order of language titles depends on language translation options.
    $mainLangID = empty($_SESSION['language_choice']) ? '1' : $_SESSION['language_choice'];
    if ($mainLangID == '1' && ! empty($GLOBALS['skip_english_translation'])) {
        $sql = "SELECT * FROM lang_languages ORDER BY lang_description, lang_id";
        $res3 = SqlStatement($sql);
    } else {
        // Use and sort by the translated language name.
        $sql = "SELECT ll.lang_id, " . "IF(LENGTH(ld.definition),ld.definition,ll.lang_description) AS trans_lang_description, " . "ll.lang_description " .
             "FROM lang_languages AS ll " . "LEFT JOIN lang_constants AS lc ON lc.constant_name = ll.lang_description " .
             "LEFT JOIN lang_definitions AS ld ON ld.cons_id = lc.cons_id AND " . "ld.lang_id = ? " .
             "ORDER BY IF(LENGTH(ld.definition),ld.definition,ll.lang_description), ll.lang_id";
        $res3 = SqlStatement($sql, array(
            $mainLangID
        ));
    }

    for ($iter = 0; $row = sqlFetchArray($res3); $iter ++) {
        $result3[$iter] = $row;
    }

    if (count($result3) == 1) {
        // default to english if only return one language
        $hiddenLanguageField = "<input type='hidden' name='languageChoice' value='1' />\n";
    }
} else {
    $hiddenLanguageField = "<input type='hidden' name='languageChoice' value='" . htmlspecialchars($defaultLangID, ENT_QUOTES) . "' />\n";
}

?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo xlt('New Patient'); ?> | <?php echo xlt('Register'); ?></title>
<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
<meta name="description" content="Developed By sjpadgett@gmail.com">

<link href="<?php echo $GLOBALS['fonts_path']; ?>font-awesome-4-6-3/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="<?php echo $GLOBALS['standard_js_path']; ?>jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.min.css">
<link href="<?php echo $GLOBALS['standard_js_path']; ?>bootstrap-3-3-4/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="./../assets/css/register.css" rel="stylesheet" type="text/css" />

<script src="<?php echo $GLOBALS['standard_js_path']; ?>jquery-min-3-1-1/index.js" type="text/javascript"></script>

<script src="<?php echo $GLOBALS['standard_js_path']; ?>bootstrap-3-3-4/dist/js/bootstrap.min.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['standard_js_path']; ?>jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.full.min.js"></script>

<script type="text/javascript" src="<?php echo $GLOBALS['standard_js_path']; ?>emodal-1-2-65/dist/eModal.js"></script>


<script>
var newPid = 0;
var curPid = 0;
var provider = 0;

$(document).ready(function () {

    /* // test data
    $("#emailInput").val("me@me.com");
    $("#fname").val("Jerry");
    $("#lname").val("Padgett");
    $("#dob").val("1919-03-03");
    // ---------- */
    var navListItems = $('div.setup-panel div a'),
              allWells = $('.setup-content'),
              allNextBtn = $('.nextBtn'),
              allPrevBtn = $('.prevBtn');

      allWells.hide();

      navListItems.click(function (e) {
          e.preventDefault();
        var $target = $($(this).attr('href')),
        $item = $(this);

          if (!$item.hasClass('disabled')) {
              navListItems.removeClass('btn-primary').addClass('btn-default');
              $item.addClass('btn-primary');
              allWells.hide();
              $target.show();
              $target.find('input:eq(0)').focus();
          }
      });

      allPrevBtn.click(function(){
          var curStep = $(this).closest(".setup-content"),
              curStepBtn = curStep.attr("id"),
              prevstepwiz = $('div.setup-panel div a[href="#' + curStepBtn + '"]').parent().prev().children("a");
              prevstepwiz.removeAttr('disabled').trigger('click');
      });

      allNextBtn.click(function(){
          var profile = $("#profileFrame").contents();

        /* // test data
        profile.find("input#street").val("123 Some St.");
        profile.find("input#city").val("Brandon");
        //--------------------- */

          var curStep = $(this).closest(".setup-content"),
              curStepBtn = curStep.attr("id"),
              nextstepwiz = $('div.setup-panel div a[href="#' + curStepBtn + '"]').parent().next().children("a"),
              curInputs = curStep.find("input[type='text'],input[type='email'],select"),
              isValid = true;

          $(".form-group").removeClass("has-error");
          for(var i=0; i<curInputs.length; i++){
              if (!curInputs[i].validity.valid){
                  isValid = false;
                  $(curInputs[i]).closest(".form-group").addClass("has-error");
              }
          }
          if (isValid){
             if(curStepBtn == 'step-1'){ // leaving step 1 setup profile frame. Prob not nec but in case
               profile.find('input#fname').val($("#fname").val());
               profile.find('input#mname').val($("#mname").val());
               profile.find('input#lname').val($("#lname").val());
               profile.find('input#dob').val($("#dob").val());
               profile.find('input#email').val($("#emailInput").val());
               profile.find('input[name=allowPatientPortal]').val(['YES']);
               // need these for validation.
               profile.find('select#providerid option:contains("Unassigned")').val('');
               profile.find('select#providerid').attr('required', true);
               profile.find('select#sex option:contains("Unassigned")').val('');
               profile.find('select#sex').attr('required', true);

               var pid = profile.find('input#pid').val();
               if( pid < 1){ // form pid set in promise
                   callServer('get_newpid','',$("#dob").val(),$("#lname").val(),$("#fname").val()); // @TODO escape these
               }
            }
              nextstepwiz.removeAttr('disabled').trigger('click');
          }
      });

      $("#profileNext").click(function(){
          var profile = $("#profileFrame").contents();
          var curStep = $(this).closest(".setup-content"),
              curStepBtn = curStep.attr("id"),
              nextstepwiz = $('div.setup-panel div a[href="#' + curStepBtn + '"]').parent().next().children("a"),
              curInputs = $("#profileFrame").contents().find("input[type='text'],input[type='email'],select"),
              isValid = true;
          $(".form-group").removeClass("has-error");
          var flg = 0;
          for(var i=0; i < curInputs.length; i++){
              if (!curInputs[i].validity.valid){
                  isValid = false;
                  if( !flg ){
                    curInputs[i].scrollIntoView();
                    curInputs[i].focus();
                    flg = 1;
                  }
                  $(curInputs[i]).closest(".form-group").addClass("has-error");
              }
          }
        if (isValid) {
            provider = profile.find('select#providerid').val();
              nextstepwiz.removeAttr('disabled').trigger('click');
        }
      });

      $("#submitPatient").click(function(){
          var profile = $("#profileFrame").contents();
          var pid = profile.find('input#pid').val();

          if( pid < 1){ // Just in case. Can never have too many pid checks!
              callServer('get_newpid','');
          }

          var isOk = checkRegistration(newPid);

          if(isOk){
              // Use portals rest api. flag 1 is write to chart. flag 0 writes an audit record for review in dashboard.
            // rest update will determine if new or existing pid for save. In register step-1 we catch existing pid but,
              // we can still use update here if we want to allow changing passwords.
              //
              document.getElementById('profileFrame').contentWindow.page.updateModel(1);
              $("#insuranceForm").submit();
            //  cleanup is in callServer done promise. This starts end session.

          }
      });

    $('div.setup-panel div a.btn-primary').trigger('click');

    $('.datepicker').datetimepicker({
        <?php $datetimepicker_timepicker = false; ?>
        <?php $datetimepicker_showseconds = false; ?>
        <?php $datetimepicker_formatInput = false; ?>
        <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
    });

    $("#insuranceForm").submit(function(e){
      e.preventDefault();
        var url = "account.php?action=new_insurance&pid=" + newPid;
      $.ajax({
            url: url,
            type: 'post',
            data: $("#insuranceForm").serialize(),
            success: function(serverResponse) {
               doCredentials(newPid) // this is the end for session.
              return false;
            }
        });
    });

    $('#selLanguage').on('change', function() {
        callServer("set_lang", this.value);
    });

    $(document.body).on('hidden.bs.modal', function (){ //@TODO maybe make a promise for wiz exit
        callServer('cleanup');
    });
    $('#inscompany').on('change', function () {
        if ($('#inscompany').val().toUpperCase() === 'SELF') {
            $("#insuranceForm input").removeAttr("required");
            let message = "<?php echo xls('You have chosen to be self insured or currently do not have insurance. Click next to continue registration.'); ?>";
            alert(message);
        }

});
}); // ready end

function doCredentials(pid) {
    callServer('do_signup', pid);
}


function checkRegistration(pid){
    var profile = $("#profileFrame").contents();
    var curStep = $("#step-2"),
    curStepBtn = curStep.attr("id"),
    nextstepwiz = $('div.setup-panel div a[href="#' + curStepBtn + '"]').parent().next().children("a"),
    curInputs = $("#profileFrame").contents().find("input[type='text'],input[type='email'],select"),
    isValid = true;
    $(".form-group").removeClass("has-error");
    var flg = 0;
    for(var i=0; i < curInputs.length; i++){
        if (!curInputs[i].validity.valid){
            isValid = false;
            if( !flg ){
              curInputs[i].scrollIntoView();
              curInputs[i].focus();
              flg = 1;
            }
            $(curInputs[i]).closest(".form-group").addClass("has-error");
        }
    }

    if (!isValid){
      return false;
    }

    return true;
}

function callServer(action, value, value2, last, first) {
    var data = {
        'action' : action,
        'value' : value,
        'dob' : value2,
        'last' : last,
        'first' : first
    }
       if(action == 'do_signup'){
        data = {
            'action': action,
            'pid': value
        };
    }
    else if (action == 'notify_admin') {
        data = {
            'action': action,
            'pid': value,
            'provider': value2
        };
    }
    else if (action == 'cleanup') {
        data = {
            'action': action
        };
       }
    // The magic that is jquery ajax.
    $.ajax({
        type: 'GET',
        url : 'account.php',
        data: data
    }).done(function (rtn) {
        if (action == "cleanup") {
            window.location.href = "./../index.php" // Goto landing page.
        }
        else if (action == "set_lang") {
                window.location.href=window.location.href;
            }
        else if (action == "get_newpid") {
            if (parseInt(rtn) > 0) {
                    newPid = rtn;
                    $("#profileFrame").contents().find('input#pubpid').val(newPid);
                    $("#profileFrame").contents().find('input#pid').val(newPid);
                }
                else{
                // After error alert app exit to landing page.
                // Existing user error. Error message is translated in account.lib.php.
                eModal.alert(rtn);
                }
            }
        else if (action == 'do_signup') {
                if(rtn == ""){
                let message = "<?php echo xls('Unable to either create credentials or send email.'); ?>";
                alert(message);
                    return false;
                    }
            // For production. Here we're finished so do signup closing alert and then cleanup.
            callServer('notify_admin', newPid, provider); // pnote notify to selected provider
            // alert below for ease of testing.
             alert(rtn); // sync alert.. rtn holds username and password for testing.

            let message = "<?php echo xls("Your new credentials have been sent. Check your email inbox and also possibly your spam folder. Once you log into your patient portal feel free to make an appointment or send us a secure message. We look forward to seeing you soon."); ?>"
            eModal.alert(message); // This is an async call. The modal close event exits us to portal landing page after cleanup.


            }
     }).fail(function (err){
        let message = "<?php echo xls('Something went wrong.') ?>";
        alert(message);
        });
}
</script>

</head>
<body>
    <div class="container">
        <div class="stepwiz col-md-offset-3">
            <div class="stepwiz-row setup-panel">
                <div class="stepwiz-step">
                    <a href="#step-1" type="button" class="btn btn-primary btn-circle">1</a>
                    <p><?php echo xlt('Get Started') ?></p>
                </div>
                <div class="stepwiz-step">
                    <a href="#step-2" type="button" class="btn btn-default btn-circle" disabled="disabled">2</a>
                    <p><?php echo xlt('Profile') ?></p>
                </div>
                <div class="stepwiz-step">
                    <a href="#step-3" type="button" class="btn btn-default btn-circle" disabled="disabled">3</a>
                    <p><?php echo xlt('Insurance') ?></p>
                </div>
                <div class="stepwiz-step">
                    <a href="#step-4" type="button" class="btn btn-default btn-circle" disabled="disabled"><?php echo xlt('Done') ?></a>
                    <p><?php echo xlt('Register') ?></p>
                </div>
            </div>
        </div>
<!-- // Start Forms // -->
        <form class="form-inline" id="startForm" role="form" action="" method="post" onsubmit="">
            <div class="row setup-content" id="step-1">
                <div class="col-xs-7 col-md-offset-3 text-center">
                    <fieldset>
                        <legend class='bg-primary'><?php echo xlt('Contact') ?></legend>
                        <div class="well">
                        <?php if ($GLOBALS['language_menu_login']) { ?>
                        <?php if (count($result3) != 1) { ?>
                        <div class="form-group row">
                            <label for="selLanguage"><?php echo xlt('Language'); ?></label>
                            <select class="form-control" id="selLanguage" name="languageChoice">
                            <?php
                                echo "<option selected='selected' value='" . htmlspecialchars($defaultLangID, ENT_QUOTES) . "'>" .
                                     htmlspecialchars(xl('Default') . " - " . xl($defaultLangName), ENT_NOQUOTES) . "</option>\n";
                            foreach ($result3 as $iter) {
                                if ($GLOBALS['language_menu_showall']) {
                                    if (! $GLOBALS['allow_debug_language'] && $iter['lang_description'] == 'dummy') {
                                        continue; // skip the dummy language
                                    }
                                    echo "<option value='" . htmlspecialchars($iter['lang_id'], ENT_QUOTES) . "'>" .
                                         htmlspecialchars($iter['trans_lang_description'], ENT_NOQUOTES) . "</option>\n";
                                } else {
                                    if (in_array($iter['lang_description'], $GLOBALS['language_menu_show'])) {
                                        if (! $GLOBALS['allow_debug_language'] && $iter['lang_description'] == 'dummy') {
                                            continue; // skip the dummy language
                                        }
                                        echo "<option value='" . htmlspecialchars($iter['lang_id'], ENT_QUOTES) . "'>" .
                                             htmlspecialchars($iter['trans_lang_description'], ENT_NOQUOTES) . "</option>\n";
                                    }
                                }
                            }
                                ?>
                              </select>
                            </div>
                        <?php } } ?>
                        <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group inline">
                                        <label class="control-label" for="fname"><?php echo xlt('First')?></label>
                                        <div class="controls inline-inputs">
                                            <input type="text" class="form-control" id="fname" required placeholder="<?php echo xla('First Name'); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group inline">
                                        <label class="control-label" for="mname"><?php echo xlt('Middle')?></label>
                                        <div class="controls inline-inputs">
                                            <input type="text" class="form-control" id="mname" placeholder="<?php echo xla('Full or Initial'); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group inline">
                                        <label class="control-label" for="lname"><?php echo xlt('Last Name')?></label>
                                        <div class="controls inline-inputs">
                                            <input type="text" class="form-control" id="lname" required placeholder="<?php echo xla('Enter Last'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group inline">
                                <label class="control-label" for="dob"><?php echo xlt('Birth Date')?></label>
                                <div class="controls inline-inputs">
                                    <div class="input-group">
                                        <input id="dob" type="text" required class="form-control datepicker" placeholder="<?php echo xla('YYYY-MM-DD'); ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 form-group">
                                    <label class="control-label" for="email"><?php echo xlt('Enter E-Mail Address')?></label>
                                    <div class="controls inline-inputs">
                                        <input id="emailInput" type="email" class="form-control" style="width: 100%" required
                                            placeholder="<?php echo xla('Enter email address to receive registration info.'); ?>" maxlength="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary nextBtn btn-sm pull-right" type="button"><?php echo xlt('Next') ?></button>
                    </fieldset>
                </div>
            </div>
        </form>
<!-- Profile Form -->
        <form class="form-inline" id="profileForm" role="form" action="account.php" method="post">
            <div class="row setup-content" id="step-2" style="display: none">
                <div class="col-md-9 col-md-offset-2 text-center">
                    <fieldset>
                        <legend class='bg-primary'><?php echo xlt('Profile') ?></legend>
                        <div class="well">
                            <div class="embed-responsive embed-responsive-16by9">
                                <iframe class="embed-responsive-item" src="../patient/patientdata?pid=0&register=true" id="profileFrame" name="demo"></iframe>
                            </div>
                        </div>
                        <button class="btn btn-primary prevBtn btn-sm pull-left" type="button"><?php echo xlt('Previous') ?></button>
                        <button class="btn btn-primary btn-sm pull-right" type="button" id="profileNext"><?php echo xlt('Next') ?></button>
                    </fieldset>
                </div>
            </div>
        </form>
<!-- Insurance Form -->
        <form class="form-inline" id="insuranceForm" role="form" action="" method="post">
            <div class="row setup-content" id="step-3" style="display: none">
                <div class="col-xs-6 col-md-offset-3 text-center">
                    <fieldset>
                        <legend class='bg-primary'><?php echo xlt('Insurance') ?></legend>
                        <div class="well">
                            <div class="form-group inline">
                                <label class="control-label" for="provider"><?php echo xlt('Insurance Company')?></label>
                                <div class="controls inline-inputs">
                                    <input type="text" class="form-control" name="provider" id="inscompany" required placeholder="<?php echo xla('Enter Self if None'); ?>">
                                </div>
                            </div>
                            <div class="form-group inline">
                                <label class="control-label" for=""><?php echo xlt('Plan Name')?></label>
                                <div class="controls inline-inputs">
                                    <input type="text" class="form-control" name="plan_name" required placeholder="<?php echo xla('Required'); ?>">
                                </div>
                            </div>
                            <div class="form-group inline">
                                <label class="control-label" for=""><?php echo xlt('Policy Number')?></label>
                                <div class="controls inline-inputs">
                                    <input type="text" class="form-control" name="policy_number" required placeholder="<?php echo xla('Required'); ?>">
                                </div>
                            </div>
                            <div class="form-group inline">
                                <label class="control-label" for=""><?php echo xlt('Group Number')?></label>
                                <div class="controls inline-inputs">
                                    <input type="text" class="form-control" name="group_number" required placeholder="<?php echo xla('Required'); ?>">
                                </div>
                            </div>
                            <div class="form-group inline">
                                <label class="control-label" for=""><?php echo xlt('Policy Begin Date')?></label>
                                <div class="controls inline-inputs">
                                    <input type="text" class="form-control datepicker" name="date" placeholder="<?php echo xla('Policy effective date'); ?>">
                                </div>
                            </div>
                            <div class="form-group inline">
                                <label class="control-label" for=""><?php echo xlt('Co-Payment')?></label>
                                <div class="controls inline-inputs">
                                    <input type="number" class="form-control" name="copay" placeholder="<?php echo xla('Plan copay if known'); ?>">
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary prevBtn btn-sm pull-left" type="button"><?php echo xlt('Previous') ?></button>
                        <button class="btn btn-primary nextBtn btn-sm pull-right" type="button"><?php echo xlt('Next') ?></button>
                    </fieldset>
                </div>
            </div>
        </form>
        <!-- End Insurance. Next what we've been striving towards..the end-->
        <div class="row setup-content" id="step-4" style="display: none">
            <div class="col-xs-6 col-md-offset-3 text-center">
                <div class="col-md-12">
                    <fieldset>
                        <legend class='bg-success'><?php echo xlt('Register') ?></legend>
                        <div class="well" style="text-align: center">
                            <h4 class='bg-success'><?php echo xlt("All set. Click Send Request below to finish registration") ?></h4>
                            <hr>
                            <p>
                            <?php echo xlt("An e-mail with your new account credentials will be sent to the e-mail address supplied earlier. You may still review or edit any part of your information by using the top step buttons to go to the appropriate panels. Note to be sure you have given your correct e-mail address. If after receiving credentials and you have trouble with access to the portal, please contact administration.") ?>
                            </p>
                        </div>
                        <button class="btn btn-primary prevBtn btn-sm pull-left" type="button"><?php echo xlt('Previous') ?></button>
                        <hr>
                        <button class="btn btn-success btn-sm pull-right" type="button" id="submitPatient"><?php echo xlt('Send Request') ?></button>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
