<?php

define("IN_MYBB", 1);

require_once "./global.php";

$query = $db->simple_select('settinggroups', 'gid', 'name=\'myadvertisements\'');
$gid = $db->fetch_field($query, 'gid');

// add settings
$setting3 = array(
	"sid"			=> NULL,
	"name"			=> "myadvertisements_email_days",
	"title"			=> "E-mail Notices Days",
	"description"	=> "How many days before the ad expires, should the system send e-mail notices? Set to 0 disable the e-mail notices feature globally.",
	"optionscode"	=> "text",
	"value"			=> 5,
	"disporder"		=> 3,
	"gid"			=> $gid
);
$db->insert_query("settings", $setting3);

rebuild_settings();

if(!$db->field_exists("created", "myadvertisements_advertisements"))
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."myadvertisements_advertisements` ADD `created` bigint(30) UNSIGNED NOT NULL default '0';");

if(!$db->field_exists("email_subject", "myadvertisements_advertisements"))
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."myadvertisements_advertisements` ADD `email_subject` varchar(50) NOT NULL default '';");

if(!$db->field_exists("email_message", "myadvertisements_advertisements"))
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."myadvertisements_advertisements` ADD `email_message` text NOT NULL;");

if(!$db->field_exists("emails", "myadvertisements_advertisements"))
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."myadvertisements_advertisements` ADD `emails` text NOT NULL;");

if(!$db->field_exists("email", "myadvertisements_advertisements"))
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."myadvertisements_advertisements` ADD `email` tinyint(1) UNSIGNED NOT NULL default '0';");

die("Upgraded to 2.2 successfully.");

?>