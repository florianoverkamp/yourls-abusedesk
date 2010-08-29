<?php
// Configuration settings for Abusedesk plugin for Yourls - URL Shortener
// Copyright (c) 2010, Florian Overkamp <florian@tty.nu>

global $ydb;
?>
<p><h2>Report abuse</h2>
<p>If you feel any short URL's is posted to support abusive purposes such as spam, you can report the URL as Abusive using the form below.
Just enter the last part of the short URL, add your comments and a contact address. 
<p><b>The offending URL will be disabled automatically.</b>

<?
$alias = "";
$reporttext = "";
$reportemail = "";
if(isset($_POST['alias'])) $alias=mysql_escape_string($_POST['alias']);
if(isset($_POST['report'])) $reporttext=mysql_escape_string($_POST['report']);
if(isset($_POST['contact'])) $reportemail=mysql_escape_string($_POST['contact']);

if (!empty($alias)) {
    $table = YOURLS_DB_TABLE_BLOCKED;
    $insert = $ydb->query("REPLACE INTO `$table` (keyword, ip, reason, addr) VALUES ('$alias', '".$_SERVER['REMOTE_ADDR']."', '$reporttext', '$reportemail')");
    echo "<font color=red>Short URL <b>$alias</b> has been marked as bad.</font><br/>\n";
}
?>

<form method="post" action="report.php">
<table border=0 cellspacing=0 cellpadding=0>
<tr><td valign=top>Short URL:</td><td valign=top><input type="text" name="alias" size="10" /></td></tr>
<tr><td valign=top>Reason to disable:<br/><b>(public info)</b></td><td valign=top><textarea rows=6 cols=60 name="report"><? echo $reporttext ?></textarea></td></tr>
<tr><td valign=top>Your email:<br/><b>(private info)</b></td><td valign=top><input type="text" name="contact" size="40" value="<? echo $reportemail ?>"/></td></tr>
<tr><td colspan=2><input type="submit" value="Report Abuse" /></td></tr>
</table>
</form>

<p><i>Please be advised: <b>Your report will be visible to visitors</b> of the shortlink (Reason to disable) but your contact details will remain confidential. We will only use your contact details if exchange of additional information is required.</i>

