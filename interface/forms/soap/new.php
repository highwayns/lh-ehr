<?php
include_once("../../globals.php");
include_once("$srcdir/api.inc");
include_once("$srcdir/headers.inc.php");

require ("C_FormSOAP.class.php");

$c = new C_FormSOAP();
echo $c->default_action();
?>
