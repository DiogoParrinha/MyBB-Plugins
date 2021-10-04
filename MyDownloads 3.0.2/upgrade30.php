<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/upgrade30.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *
 *
 *   MyDownloads adds a downloads system to MyBB.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
require_once "./inc/init.php";

$gid = $db->fetch_field($db->simple_select('settinggroups', 'gid', 'name=\'mydownloads\''), 'gid');

echo "Adding new settings...";
$setting46 = array(
	"name" => "mydownloads_points_available",
	"title" => "Points Available",
	"description" => "Enter the options available for the price of the items. If empty, users will be able to enter their desired value in a textbox. Example: 10,20,30,40,50",
	"optionscode" => "text",
	"value" => "",
	"disporder" => 46,
	"gid" => intval($gid),
);

$db->insert_query("settings", $setting46);

$setting47 = array(
	"name"			=> "mydownloads_newpoints_pay_each",
	"title"			=> "[NewPoints] Pay each time",
	"description"	=> "If the \"Work together with NewPoints\" setting is set to yes, do you want users to pay everytime they want to download the SAME download? E.g. user X has paid to download file Y but for some reason the user deleted the download from the hard drive and wants to re-download. Does user X have to pay again?",
	"optionscode"	=> "yesno",
	"value"			=> 0,
	"disporder"		=> 47,
	"gid"			=> $gid
);
$db->insert_query("settings", $setting47);

rebuild_settings();

echo "Done!<br />";

echo "Adding fields...";
$db->write_query("ALTER TABLE `".TABLE_PREFIX."mydownloads_tags` ADD `color` varchar(7) NOT NULL DEFAULT '';");
$db->write_query("ALTER TABLE `".TABLE_PREFIX."mydownloads_downloads` ADD `banner` varchar(255) NOT NULL default '';");
$db->write_query("ALTER TABLE `".TABLE_PREFIX."mydownloads_submissions` ADD `banner` varchar(255) NOT NULL default '';");
echo "Done!<br />";

echo "Upgrade finished!<br />";
exit;

?>
