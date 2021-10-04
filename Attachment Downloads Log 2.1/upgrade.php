<?php

define("IN_MYBB", 1);

require_once "./global.php";

$query = $db->simple_select('settinggroups', 'gid', 'name=\'attachmentlog\'');
$gid = $db->fetch_field($query, 'gid');

// add settings
$setting = array(
	"sid"			=> NULL,
	"name"			=> "attachmentlog_modcp_groups",
	"title"			=> "Allowed Groups",
	"description"	=> "The ID\'s of the user groups you want to be able to access the ModCP page.",
	"optionscode"	=> "text",
	"value"			=> "4",
	"disporder"		=> 3,
	"gid"			=> $gid
);

$db->insert_query("settings", $setting);

$setting = array(
	"sid"			=> NULL,
	"name"			=> "attachmentlog_modcp_list",
	"title"			=> "Select box vs Text box",
	"description"	=> "Do you want to see a select box previously populated with all attachment names that were logged or do you want to be able to enter a search term? The former uses more processing power and more memory and it\'s definitely not recommended for boards with many attachments.",
	"optionscode"	=> "select
0=text
1=select",
	"value"			=> "0",
	"disporder"		=> 4,
	"gid"			=> $gid
);

$db->insert_query("settings", $setting);

$setting = array(
	"sid"			=> NULL,
	"name"			=> "attachmentlog_modcp_exempt",
	"title"			=> "Groups Exempt from Showing",
	"description"	=> "The ID\'s of the user groups you do not want to show in the logs (e.g. a group that might have been logged in the past but you no longer want their logs to appear).",
	"optionscode"	=> "text",
	"value"			=> "",
	"disporder"		=> 5,
	"gid"			=> $gid
);

$db->insert_query("settings", $setting);

$setting = array(
	"sid"			=> NULL,
	"name"			=> "attachmentlog_aids_exempt",
	"title"			=> "Attachments Exempt from Showing",
	"description"	=> "The ID\'s of the attachments exempt from showing in the logs (including the attachements dropdown list).",
	"optionscode"	=> "text",
	"value"			=> "",
	"disporder"		=> 6,
	"gid"			=> $gid
);

$db->insert_query("settings", $setting);

rebuild_settings();

die("Upgraded to 2.0 successfully.");

?>