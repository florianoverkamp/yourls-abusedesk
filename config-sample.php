<?
//
// Configuration settings for Abusedesk plugin for Yourls - URL Shortener
// Copyright (c) 2010, Florian Overkamp <florian@tty.nu>
// 

// Define the BLOCKED table to mark URL's disabled.
//
if( !defined('YOURLS_DB_TABLE_BLOCKED') )
	define('YOURLS_DB_TABLE_BLOCKED', YOURLS_DB_PREFIX.'blocked');
if( !defined('YOURLS_DB_TABLE_BANNED') )
	define('YOURLS_DB_TABLE_BANNED', YOURLS_DB_PREFIX.'banned');

// Settings for using Google Safe Browsing with phpgsb
//
if (file_exists(dirname(__FILE__).'/phpgsb/phpgsb.class.php')) {
//	define('GSB_APIKEY', 'YOUR GOOGLE SAFEBROWSING API KEY HERE');
}

// No user serviceable parts below
//

// Enable phpgsb if a key is defined
if (defined('GSB_APIKEY') ) {
	require_once("phpgsb/phpgsb.class.php");
}

