<?php
/*
Plugin Name: Yourls Abusedesk
Plugin URI: http://blog.tty.nu/tag/yourls-abusedesk/
Description: Additional handlers to block malware and spammers
Version: 1.0
Author: Florian Overkamp
Author URI: http://blog.tty.nu/
*/
// Abusedesk plugin for Yourls - URL Shortener
// Copyright (c) 2010, Florian Overkamp <florian@tty.nu>

require_once("config.php");

// Create tables for this plugin when activated
yourls_add_action( 'activated_yourls-abusedesk/plugin.php', 'abusedesk_activated' );
function abusedesk_activated() {
	global $ydb;

	$table_banned  = "CREATE TABLE IF NOT EXISTS " . YOURLS_DB_PREFIX . "banned (";
	$table_banned .= "ban varchar(255) NOT NULL, ";
	$table_banned .= "bantype enum('src','dst') NOT NULL, ";
	$table_banned .= "timestamp timestamp NOT NULL default CURRENT_TIMESTAMP, ";
	$table_banned .= "reason text NOT NULL, ";
	$table_banned .= "clicks int(10) NOT NULL default '0', ";
	$table_banned .= "PRIMARY KEY (ban) ";
	$table_banned .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1;";
	$tables = $ydb->query($table_banned);

	$table_blocked  = "CREATE TABLE IF NOT EXISTS " . YOURLS_DB_PREFIX . "blocked (";
	$table_blocked .= "keyword varchar(200) NOT NULL, ";
	$table_blocked .= "timestamp timestamp NOT NULL default CURRENT_TIMESTAMP, ";
	$table_blocked .= "ip varchar(41) default NULL, ";
	$table_blocked .= "reason text, ";
	$table_blocked .= "addr varchar(200) default NULL, ";
	$table_blocked .= "PRIMARY KEY (keyword) ";
	$table_blocked .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1;";
	$tables = $ydb->query($table_blocked);
}

// Add forms to work with blacklisted sources or destinations
yourls_add_action( 'plugins_loaded', 'abusedesk_add_pages' );
function abusedesk_add_pages() {
        yourls_register_plugin_page( 'banlist', 'Ban-list', 'banlist_do_page' );
}

// Display page
function banlist_do_page() {
	echo "<h2>Ban list</h2>\n";

        if( isset( $_GET['action'] ) && $_GET['action'] == 'deleteban' ) {
                ban_del();
	} else if( isset( $_POST['action'] ) && $_POST['action'] == 'ban' ) {
                ban_add();
        } else {
                ban_list();
        }
}

function ban_list() {
	global $ydb;

	echo "<form method=\"post\">\n";
	echo "<table id=\"main_table\" class=\"tblSorter\" cellpadding=\"2\" cellspacing=\"1\">\n";
	echo "<thead><tr><th>Ban</th><th>Type</th><th>Reason</th><th>Date</th><th>Clicks</th><th>&nbsp;</th></tr></thead>\n";
	echo "<tbody>\n";
	echo "<tr><td><input type=\"text\" name=\"ban\" size=20></td>";
	echo "<td><input type=\"radio\" name=\"type\" id=\"ban_type\" value=\"src\"/>Source<br>";
	echo "<input type=\"radio\" name=\"type\" id=\"ban_type\" value=\"dst\"/>Destination</td>";
	echo "<td><input type=\"text\" name=\"reason\" size=30></td>";
	echo "<td colspan=3 align=right><input type=submit name=\"submit\" value=\"Ban this!\"><input type=\"hidden\" name=\"action\" value=\"ban\"></td></tr>";

	$table = YOURLS_DB_TABLE_BANNED;
	$banned_list = $ydb->get_results("SELECT * FROM `$table` ORDER BY timestamp DESC");
	$found_rows = false;
	if($banned_list) {
		$found_rows = true;
		foreach( $banned_list as $ban ) {
			$ban_key = $ban->ban;
			$ban_type = $ban->bantype;
			$timestamp = strtotime($ban->timestamp);
			$reason = $ban->reason;
			$date = date( 'M d, Y H:i', $timestamp+( YOURLS_HOURS_OFFSET * 3600) );
			$clicks = $ban->clicks;
			if($ban_type == 'src') $ban_type='Source';
			if($ban_type == 'dst') $ban_type='Destination';
			echo "<tr><td>$ban_key</td><td>$ban_type</td>";
			echo "<td>$reason</td>";
			echo "<td>$date</td>";
			echo "<td>$clicks</td>";
			echo "<td><a href=\"".$PHP_SELF."?page=banlist&action=deleteban&key=$ban_key\"><img src=\"/images/delete.png\" title=\"Delete\" align=right border=0></a></td></tr>\n";
		}
	}
	echo "</tbody>\n";
	echo "</table>\n";
	echo "</form>\n";
}

function ban_add() {
	global $ydb;

	if( isset($_POST['type']) && isset($_POST['ban']) ) {
		$table = YOURLS_DB_TABLE_BANNED;
		$ban = mysql_real_escape_string($_POST['ban']);
		$type = mysql_real_escape_string($_POST['type']);
		$reason = mysql_real_escape_string($_POST['reason']);
        	echo("REPLACE INTO `$table` (ban, bantype, reason) VALUES('$ban', '$type', '$reason')");
        	$insert = $ydb->query("REPLACE INTO `$table` (ban, bantype, reason) VALUES('$ban', '$type', '$reason')");
	}

	ban_list();
}

function ban_del() {
	global $ydb;

	if( isset($_GET['key']) ) {
		$table = YOURLS_DB_TABLE_BANNED;
		// @@@FIXME@@@ needs securing against SQL injection !
		$key = $_GET['key'];
        	$delete = $ydb->query("DELETE FROM `$table` WHERE ban='$key'");
	}

	// @@@FIXME@@@ This should probably be rewritten to do a redirect to avoid confusion between GET/POST forms
	ban_list();
}

// Now start using the ban-list
yourls_add_action( 'pre_add_new_link', 'check_banlist' );

function check_banlist( $args ) {
	global $ydb;

        $url = $args[0]; // Target URL to insert
        $keyword = $args[1]; // Keyword for this request
	$ip = yourls_get_IP();
	$table = YOURLS_DB_TABLE_BANNED;

	$srcflag = $ydb->get_row("SELECT * FROM `$table` WHERE bantype='src' AND ban='$ip'");
	if($srcflag) {
		// Result found, block entry because of blacklisting
		$reason = $srcflag->reason;
		$clicks = $srcflag->clicks+1;
		$ydb->query("UPDATE `$table` SET clicks=$clicks WHERE bantype='src' AND ban='$ip'");
		display_banpage("<p>Your IP has been blocked</p><p>$reason");
	}
	$dstflag = $ydb->get_row("SELECT * FROM `$table` WHERE bantype='dst' AND '$url' LIKE CONCAT('%', ban, '%')");
	if($dstflag) {
		// Result found, block entry because of blacklisting
		$reason = $dstflag->reason;
		$clicks = $dstflag->clicks+1;
		$ban=$dstflag->ban;
		$ydb->query("UPDATE `$table` SET clicks=$clicks WHERE bantype='dst' AND ban='$ban'");
		display_banpage("<p>Destination URL has been blocked</p><p>$reason");
	}
}

// Hook the 'redirect_shorturl' event
yourls_add_action( 'redirect_shorturl', 'check_safe_redirection' );

// Our custom function that will be triggered when the event occurs
function check_safe_redirection( $args ) {
	global $ydb;

        $url = $args[0]; // Target URL to scan
        $keyword = $args[1]; // Keyword for this request
       
	$resultset = check_blockpage($url, $keyword);  

	if($resultset !== false) {
		display_blockpage($resultset['displayreason']);
        }
}

// Also hook into the delete action to clean up if needed
yourls_add_action( 'delete_link', 'delete_blocked_link_by_keyword' );

function delete_blocked_link_by_keyword( $args ) {
	global $ydb;

        $keyword = $args[0]; // Keyword to delete

	// Delete the blocking data, no need for it anymore
	$table = YOURLS_DB_TABLE_BLOCKED;
	$ydb->query("DELETE FROM `$table` WHERE `keyword` = '$keyword';");

	// No need for log-entries to deleted URL's either, really
	$table = YOURLS_DB_TABLE_LOG;
	$ydb->query("DELETE FROM `$table` WHERE `shorturl` = '$keyword';");
}

// Hook into a few filters to make the offending entry visible for the administrator
yourls_add_filter( 'table_add_row', 'show_blocked_tablerow' );

function show_blocked_tablerow($row, $keyword, $url, $title, $ip, $clicks, $timestamp) {
	// If the row is malware, make the URL show in red;
	$WEBPATH=substr(dirname(__FILE__), strlen(YOURLS_ABSPATH));
	$resultset = check_blockpage($url, $keyword);
	if($resultset !== false) {
		// Split up the current table row
		$tablestart = strpos($row, "<td id=\"url-");
		$urlstart = strpos($row, "class=\"url\">", $tablestart) + 12;
		$urlend = strpos($row, "</td>", $urlstart);
		$rowbegin = substr($row, 0, $urlstart);
		$rowpart = substr($row, $urlstart, $urlend-$urlstart);
		$rowend = substr($row, $urlend);
		// Modify the look for this entry
		$rowpart = "<font color=red>" . strip_tags($rowpart, "<br><small>") . "</font>";
		$blockedreason = $resultset['shortreason'];
		$newrow = $rowbegin . "<img src=\"/images/error.png\" title=\"$blockedreason\" align=right>" . $rowpart . $rowend . "\n";
	} else {
		$newrow = $row;
	}
	
	return $newrow;
}

yourls_add_filter( 'action_links', 'show_abuse_buttons' );

function show_abuse_buttons($actions, $keyword, $url, $ip, $clicks, $timestamp) {
	return $actions;
}

// Other usefull functions
function check_blockpage($url, $keyword='') {
	global $ydb;

	$resultset = false;

	// Safety check 1: Was the url reported bad?
	$table = YOURLS_DB_TABLE_BLOCKED;
	$blocked = $ydb->get_row("SELECT * FROM `$table` WHERE `keyword` = '$keyword'");
	if( $blocked ) {
		$blocked = (array)$blocked;
		$resultset['displayreason'] = "Target URL was blocked. ".$blocked['reason'];
		$resultset['shortreason'] = $blocked['reason'];
		if(strlen($resultset['shortreason']) > 20) $resultset['shortreason'] = substr($resultset['shortreason'], 0, 20) . "...";
	}

	if(defined('GSB_APIKEY')) {
		// Safety check 2: Is any part of this request tagged as unsafe by Google Safe Browsing
		$phpgsb = new phpGSB(YOURLS_DB_NAME, YOURLS_DB_USER, YOURLS_DB_PASS, YOURLS_DB_HOST, false);
		$phpgsb->apikey = GSB_APIKEY;
		$phpgsb->usinglists = array('googpub-phish-shavar');
		if($phpgsb->doLookup($url)) {
			$resultset['displayreason'] = "<p><b>Suspected phishing page</b> <p>The page you requested may be a forgery or imitation 
				of another website, designed to trick users into sharing personal or financial information. 
				Entering any personal information on this page may result in identity theft or other abuse. 
				You can find out more about phishing from <a href=\"www.antiphising.org\">www.antiphishing.org</a>.
				<p>If you really want to visit the page, you may click the link below: 
				<p><code><a href=\"$url\">$url</a></code>";
			$resultset['shortreason'] = "Phishing site";
		}
		$phpgsb->usinglists = array('goog-malware-shavar');
		if($phpgsb->doLookup($url)) {
			$resultset['displayreason'] = "<p><b>Visiting this web site may harm your computer.</b> <p>This page appears to 
				contain malicious code that could be downloaded to your computer without your consent. You 
				can learn more about harmful web content including viruses and other malicious code and how 
				to protect your computer at <a href=\"http://www.stopbadware.org/\">StopBadware.org</a>. 
				<p>If you really want to visit the page, you may click the link below: 
				<p><code><a href=\"$url\">$url</a></code>";
			$resultset['shortreason'] = "Malware site";
		}
	}

	return $resultset;
}

function display_blockpage($reason) {
	global $hide_top, $hide_bottom;
	$hide_top = true;
	$hide_bottom = true;
	yourls_do_action( 'pre_page', 'blocked' );
	echo "<p><b>This link has been disabled</b>";
	echo "<p>Sorry, but the link you have clicked has been disabled, for the following reason:";
	echo "<div style=\"padding:6px; border: 1px solid black; background-color: #ffdddd;\">";
	echo $reason;
	echo "</div>";
	yourls_do_action( 'post_page', 'blocked' );
	die();
}

function display_banpage($reason) {
	global $hide_top, $hide_bottom;
	$hide_top = true;
	$hide_bottom = true;
	echo "<p><h2>Short URL has NOT been created!</h2>";
	echo "<p>Sorry, but your short-link request could not be honored:";
	echo "<div style=\"padding:6px; border: 1px solid black; background-color: #ffdddd;\">";
	echo $reason;
	echo "</div>";
	yourls_do_action( 'post_page', 'blocked' );
	die();
}



