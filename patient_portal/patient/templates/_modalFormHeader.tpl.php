<?php
/**
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 *
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 *
 * @package LibreHealth EHR
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @link http://librehealth.io
 */
?>
<!DOCTYPE html>
<html>

<head>
<meta charset="utf-8">

<title><?php $this->eprint($this->title); ?></title>
<meta content="width=device-width, initial-scale=1, user-scalable=no"   name="viewport">

<meta name="description" content="Patient Profile" />
<meta name="author" content="Form | sjpadgett@gmail.com" />

<script src="<?php echo $GLOBALS['standard_js_path']; ?>jquery-min-3-1-1/index.js" type="text/javascript"></script>

<link href="<?php echo $GLOBALS['fonts_path']; ?>font-awesome-4-6-3/css/font-awesome.min.css" rel="stylesheet" />
<link href="<?php echo $GLOBALS['standard_js_path']; ?>jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.min.css"  rel="stylesheet" />

<?php if ($this->register) {?>
    <link href="<?php echo $GLOBALS['standard_js_path']; ?>bootstrap-3-3-4/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo $GLOBALS['standard_js_path']; ?>bootstrap-3-3-4/dist/js/bootstrap.min.js" type="text/javascript"></script>
<?php } ?>
<link href="<?php echo $GLOBALS['web_root']; ?>/patient_portal/patient/styles/style.css" rel="stylesheet" />

<script type="text/javascript" src="<?php echo $GLOBALS['standard_js_path']; ?>moment-2-13-0/moment.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['standard_js_path']; ?>jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.full.min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['standard_js_path']; ?>underscore-1-8-3/underscore-min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['standard_js_path']; ?>backbone-1-3-3/backbone-min.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['web_root']; ?>/patient_portal/patient/scripts/app.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['web_root']; ?>/patient_portal/patient/scripts/model.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['web_root']; ?>/patient_portal/patient/scripts/view.js"></script>
<base href="<?php $this->eprint($this->ROOT_URL); ?>" />
<script type="text/javascript" src="<?php echo $GLOBALS['web_root']; ?>/patient_portal/patient/scripts/libs/LAB.min.js"></script>
<script type="text/javascript">
$LAB.setGlobalDefaults({BasePath: "<?php $this->eprint($this->ROOT_URL); ?>"});
</script>

</head>
<body>
