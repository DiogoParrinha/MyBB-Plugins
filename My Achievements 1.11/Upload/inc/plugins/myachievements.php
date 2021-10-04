<?php
/***************************************************************************
 *
 *  My Achievements plugin (/inc/plugins/myachievements.php)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  Adds an achievements system to MyBB.
 *
 ***************************************************************************/

// do NOT remove for security reasons!
if(!defined("IN_MYBB"))
{
	$secure = "-#77;-#121;-#66;-#66;-#45;-#80;-#108;-#117;-#103;-#105;-#110;-#115;";
	$secure = str_replace("-", "&", $secure);
	die("This file cannot be accessed directly.".$secure);
}

if(defined('THIS_SCRIPT') && THIS_SCRIPT == 'showthread.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'myachievements_postbit,myachievements_postbit';
}
elseif(defined('THIS_SCRIPT') && THIS_SCRIPT == 'member.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'myachievements_profile,myachievements_ranks_profile';
}
elseif(defined('THIS_SCRIPT') && THIS_SCRIPT == 'usercp.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'myachievements_nav,myachievements_usercp_achievement,myachievements_usercp';
}

$plugins->add_hook("datahandler_post_insert_thread", "myachievements_addthread");
$plugins->add_hook("class_moderation_delete_thread", "myachievements_deletethread");
$plugins->add_hook('member_profile_end', 'myachievements_profile');
$plugins->add_hook('postbit', 'myachievements_postbit');
$plugins->add_hook("build_friendly_wol_location_end", "myachievements_online");
$plugins->add_hook('usercp_start', 'myachievements_usercp');
$plugins->add_hook('usercp_menu_built', 'myachievements_usercp_nav');

function myachievements_info()
{
	return array(
		"name"			=> "My Achievements",
		"description"	=> "Adds an achievements system to MyBB.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.11",
		"guid" 			=> "",
		"compatibility"	=> "18*"
	);
}


function myachievements_install()
{
	global $db, $lang;

	// create settings group
	$insertarray = array(
		'name' => 'myachievements',
		'title' => 'My Achievements',
		'description' => "Settings for My Achievements plugin.",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting = array(
		"name"			=> "myachievements_max_profile",
		"title"			=> "Max Achievements on Profile",
		"description"	=> "Enter the the maximum number of achievements that are shown on profile. (leave blank to not show anything)",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	// add settings
	$setting = array(
		"name"			=> "myachievements_max_postbit",
		"title"			=> "Max Achievements on Postbit",
		"description"	=> "Enter the the maximum number of achievements that are shown on postbit. (leave blank to not show anything)",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	// add settings
	$setting = array(
		"name"			=> "myachievements_points_enabled",
		"title"			=> "Achievements Points",
		"description"	=> "Do you want achivements based on points enabled? You must have NewPoints installed.",
		"optionscode"	=> "yesno",
		"value"			=> '0',
		"disporder"		=> 4,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	// add settings
	$setting = array(
		"name"			=> "myachievements_topranks",
		"title"			=> "Top Ranks",
		"description"	=> "Enter how many users can be in the top.",
		"optionscode"	=> "text",
		"value"			=> '10',
		"disporder"		=> 5,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	// add settings
	$setting = array(
		"name"			=> "myachievements_custom_usercp",
		"title"			=> "Custom Achievements only",
		"description"	=> "Show only custom achievements in User CP. Users can\'t pick other achievements.",
		"optionscode"	=> "text",
		"value"			=> '0',
		"disporder"		=> 6,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	rebuild_settings();

	$collation = $db->build_create_table_collation();

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myachievements_numposts` (
	  `apid` int(10) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(50) NOT NULL DEFAULT '',
	  `description` varchar(200) NOT NULL DEFAULT '',
	  `numposts` int(10) NOT NULL DEFAULT 0,
	  `image` varchar(250) NOT NULL DEFAULT '',
	  PRIMARY KEY  (`apid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myachievements_numthreads` (
	  `atid` int(10) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(50) NOT NULL DEFAULT '',
	  `description` varchar(200) NOT NULL DEFAULT '',
	  `numthreads` int(10) NOT NULL DEFAULT 0,
	  `image` varchar(250) NOT NULL DEFAULT '',
	  PRIMARY KEY  (`atid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myachievements_activity` (
	  `aaid` int(10) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(50) NOT NULL DEFAULT '',
	  `description` varchar(200) NOT NULL DEFAULT '',
	  `time` int(10) NOT NULL DEFAULT 0,
	  `years` int(5) NOT NULL DEFAULT 0,
	  `months` int(5) NOT NULL DEFAULT 0,
	  `days` int(5) NOT NULL DEFAULT 0,
	  `image` varchar(250) NOT NULL DEFAULT '',
	  PRIMARY KEY  (`aaid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myachievements_custom` (
	  `acid` int(10) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(50) NOT NULL DEFAULT '',
	  `description` varchar(200) NOT NULL DEFAULT '',
	  `image` varchar(250) NOT NULL DEFAULT '',
	  PRIMARY KEY  (`acid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myachievements_points` (
	  `apoid` int(10) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(50) NOT NULL DEFAULT '',
	  `description` varchar(200) NOT NULL DEFAULT '',
	  `image` varchar(250) NOT NULL DEFAULT '',
	  `points` int(10) NOT NULL DEFAULT 0,
	  PRIMARY KEY  (`apoid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myachievements_log` (
	  `lid` bigint(30) UNSIGNED NOT NULL auto_increment,
	  `type` varchar(50) NOT NULL DEFAULT '',
	  `uid` bigint(30) NOT NULL DEFAULT 0,
	  `username` varchar(50) NOT NULL DEFAULT '',
	  `date` int(10) NOT NULL DEFAULT 0,
	  `data` varchar(255) NOT NULL DEFAULT '',
	  PRIMARY KEY  (`lid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myachievements_ranks` (
	  `rid` int(10) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(50) NOT NULL DEFAULT '',
	  `description` varchar(200) NOT NULL DEFAULT '',
	  `image` varchar(250) NOT NULL DEFAULT '',
	  `achievements_apid` int(10) NOT NULL DEFAULT 0,
	  `achievements_atid` int(10) NOT NULL DEFAULT 0,
	  `achievements_aaid` int(10) NOT NULL DEFAULT 0,
	  `achievements_acid` int(10) NOT NULL DEFAULT 0,
	  `achievements_apoid` int(10) NOT NULL DEFAULT 0,
	  `level` int(10) NOT NULL DEFAULT 0,
	  PRIMARY KEY  (`rid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `myachievements` TEXT NOT NULL;");
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `myachievements_rank` TEXT NOT NULL;");
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `myachievements_level` int(5) NOT NULL;");
	// for number of threads
	$db->query("ALTER TABLE `".TABLE_PREFIX."users` ADD `myachievements_threads` int(10) unsigned NOT NULL default '0'");

	// make sure the script doesn't timeout due to the queries
	@set_time_limit(0);

	// load all users into an array
	$query = $db->simple_select("users", "uid");
	while($user = $db->fetch_array($query))
	{
		$users[$user['uid']] = $user;
	}

	// now for each user in the array, count the number of threads and update the database
	// heavy process for big boards
	foreach($users as $user)
	{
		$query = $db->simple_select("threads", "COUNT(tid) AS threads", "uid = '".$user['uid']."'");

		// get total number of threads
		$numthreads = intval($db->fetch_field($query, "threads"));

		$db->update_query("users", array("myachievements_threads" => $numthreads), "uid = '".$user['uid']."'");
	}

	// add a new task
	$new_task = array(
		"title" => "My Achievements",
		"description" => "Adds achivements and updates ranks.",
		"file" => "myachievements",
		"minute" => '0',
		"hour" => '0,6,12,18',
		"day" => '*',
		"month" => '*',
		"weekday" => '*',
		"enabled" => '0',
		"logging" => '1'
	);

	$new_task['nextrun'] = 0; // once the task is enabled, it will generate a nextrun date
	$tid = $db->insert_query("tasks", $new_task);
}

function myachievements_is_installed()
{
	global $db;

	if ($db->table_exists('myachievements_numposts'))
		return true;
	else
		false;
}

function myachievements_uninstall()
{
	global $db, $mybb;

	// delete settings group
	$db->delete_query("settinggroups", "name = 'myachievements'");

	// remove settings
	$db->delete_query('settings', 'name IN (\'myachievements_max_profile\',\'myachievements_max_postbit\',\'myachievements_points_enabled\',\'myachievements_topranks\',\'myachievements_custom_usercp\')');

	rebuild_settings();

	// delete templates
	$db->delete_query('templates', 'title IN (\'myachievements_achievements\',\'myachievements_achievement\',\'myachievements_ranks\'\'myachievements_ranks_rank\',\'myachievements_profile\',\'myachievements_ranks_profile\',\'myachievements_no_data\')');

	if ($db->table_exists('myachievements_numposts'))
		$db->drop_table('myachievements_numposts');

	if ($db->table_exists('myachievements_numthreads'))
		$db->drop_table('myachievements_numthreads');

	if ($db->table_exists('myachievements_activity'))
		$db->drop_table('myachievements_activity');

	if ($db->table_exists('myachievements_custom'))
		$db->drop_table('myachievements_custom');

	if ($db->table_exists('myachievements_points'))
		$db->drop_table('myachievements_points');

	if ($db->table_exists('myachievements_log'))
		$db->drop_table('myachievements_log');

	if ($db->table_exists('myachievements_ranks'))
		$db->drop_table('myachievements_ranks');

	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `myachievements`;");
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `myachievements_rank`;");
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `myachievements_threads`;");
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `myachievements_level`;");

	// delete task
	$db->delete_query('tasks', 'file=\'myachievements\''); // delete all tasks that use myachievements task file
}

function myachievements_activate()
{
	global $db, $lang;

		$template = array(
		"title" => "myachievements_achievements",
		"template" => $db->escape_string('
<html>
<head>
<title>{$lang->myachievements_achievements}</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4"><strong>{$lang->myachievements_nav}</strong></td>
	</tr>
	<tr>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?uid={$mybb->user[\'uid\']}">{$lang->myachievements_earned}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php">{$lang->myachievements_list}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?action=ranks">{$lang->myachievements_ranks_list}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?action=topranks">{$lang->myachievements_topranks}</a></td>
	</tr>
</table>
<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4"><strong>{$lang->myachievements_list}</strong></td>
	</tr>
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_posts_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$posts_achievements}
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_threads_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$threads_achievements}
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_activity_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$activity_achievements}
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_custom_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$custom_achievements}
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_points_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$points_achievements}
</table>
{$footer}
</body>
</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_achievement",
		"template" => $db->escape_string('
<tr>
	<td class="{$bgcolor}" width="1%"><img src="{$achievement[\'image\']}" /></td>
	<td class="{$bgcolor}" width="30%">{$achievement[\'name\']}</td>
	<td class="{$bgcolor}">{$achievement[\'description\']}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_userachievements",
		"template" => $db->escape_string('
<html>
<head>
<title>{$lang->myachievements_userachievements}</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4">{$lang->myachievements_nav}</td>
	</tr>
	<tr>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?uid={$mybb->user[\'uid\']}">{$lang->myachievements_earned}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php">{$lang->myachievements_list}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?action=ranks">{$lang->myachievements_ranks_list}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?action=topranks">{$lang->myachievements_topranks}</a></td>
	</tr>
</table>
<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4"><strong>{$lang->myachievements_userachievements}</strong></td>
	</tr>
	<tr>
		<td class="trow1" colspan="4">{$lang->myachievements_userrank}</td>
	</tr>
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_posts_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$posts_achievements}
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_threads_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$threads_achievements}
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_activity_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$activity_achievements}
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_custom_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$custom_achievements}
	<tr>
		<td class="tcat" colspan="4"><strong>{$lang->myachievements_points_achievements}</strong></td>
	</tr>
	<tr>
		<td class="tcat">{$lang->myachievements_achievements_icon}</td>
		<td class="tcat">{$lang->myachievements_achievements_name}</td>
		<td class="tcat">{$lang->myachievements_achievements_description}</td>
	</tr>
	{$points_achievements}
</table>
{$footer}
</body>
</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_userachievement",
		"template" => $db->escape_string('
<tr>
	<td class="{$bgcolor}" width="1%"><img src="{$achievement[\'image\']}" /></td>
	<td class="{$bgcolor}" width="30%">{$achievement[\'name\']}</td>
	<td class="{$bgcolor}">{$achievement[\'description\']}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_ranks",
		"template" => $db->escape_string('
<html>
<head>
<title>{$lang->myachievements_ranks}</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4">{$lang->myachievements_nav}</td>
	</tr>
	<tr>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?uid={$mybb->user[\'uid\']}">{$lang->myachievements_earned}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php">{$lang->myachievements_list}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?action=ranks">{$lang->myachievements_ranks_list}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?action=topranks">{$lang->myachievements_topranks}</a></td>
	</tr>
</table>
<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4"><strong>{$lang->myachievements_ranks_list}</strong></td>
	</tr>
	<tr>
		<td class="tcat" width="1%" align="center"><strong>{$lang->myachievements_ranks_icon}</strong></td>
		<td class="tcat" width="30%"><strong>{$lang->myachievements_ranks_name}</strong></td>
		<td class="tcat"><strong>{$lang->myachievements_ranks_description}</strong></td>
		<td class="tcat" width="15%" align="center"><strong>{$lang->myachievements_ranks_level}</strong></td>
	</tr>
	{$ranks}
</table>
{$footer}
</body>
</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_ranks_rank",
		"template" => $db->escape_string('
<tr>
	<td class="{$bgcolor}" width="1%" align="center"><img src="{$mybb->settings[\'bburl\']}/{$rank[\'image\']}" /></td>
	<td class="{$bgcolor}" width="30%">{$rank[\'name\']}</td>
	<td class="{$bgcolor}">{$rank[\'description\']}</td>
	<td class="{$bgcolor}" width="15%" align="center">{$rank[\'level\']}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_topranks",
		"template" => $db->escape_string('
<html>
<head>
<title>{$lang->myachievements_topranks}</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4">{$lang->myachievements_nav}</td>
	</tr>
	<tr>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?uid={$mybb->user[\'uid\']}">{$lang->myachievements_earned}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php">{$lang->myachievements_list}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?action=ranks">{$lang->myachievements_ranks_list}</a></td>
		<td class="trow1" valign="middle" align="center" width="25%"><a href="{$mybb->settings[\'bburl\']}/myachievements.php?action=topranks">{$lang->myachievements_topranks}</a></td>
	</tr>
</table>
<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4"><strong>{$lang->myachievements_topranks}</strong></td>
	</tr>
	<tr>
		<td class="tcat" width="1%" align="center"><strong>{$lang->myachievements_rank}</strong></td>
		<td class="tcat" width="35%"><strong>{$lang->myachievements_ranks_username}</strong></td>
		<td class="tcat" width="30%"><strong>{$lang->myachievements_rank}</strong></td>
		<td class="tcat" width="15%" align="center"><strong>{$lang->myachievements_ranks_level}</strong></td>
	</tr>
	{$ranks}
</table>
{$footer}
</body>
</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_topranks_rank",
		"template" => $db->escape_string('
<tr>
	<td class="{$bgcolor}" align="center"><img src="{$mybb->settings[\'bburl\']}/{$rank[\'image\']}" title="{$rank[\'name\']}" /></td>
	<td class="{$bgcolor}">{$rank[\'username\']} - {$rank[\'view\']}</td>
	<td class="{$bgcolor}">{$rank[\'name\']}</td>
	<td class="{$bgcolor}" align="center">{$rank[\'level\']}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_no_data",
		"template" => $db->escape_string('
<tr>
	<td class="trow1" colspan="{$colspan}">{$lang->myachievements_no_data}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_profile",
		"template" => $db->escape_string('
<tr>
	<td class="trow2"><strong>{$lang->myachievements_achievements}:</strong></td>
	<td class="trow2">{$achievements} <span class="smalltext">(<a href="{$mybb->settings[\'bburl\']}/myachievements.php?uid={$memprofile[\'uid\']}">{$lang->myachievements_view_all_achievements}</a>)</span></td>
</tr>
{$rank}'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_ranks_profile",
		"template" => $db->escape_string('
<tr>
	<td class="trow1"><strong>{$lang->myachievements_rank}:</strong></td>
	<td class="trow1">{$rank}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_postbit",
		"template" => $db->escape_string('
<br />{$lang->myachievements_achievements}: {$achievements}<br /><span class="smalltext">(<a href="{$mybb->settings[\'bburl\']}/myachievements.php?uid={$post[\'uid\']}">{$lang->myachievements_view_all_achievements}</a>)</span>
<br />{$lang->myachievements_rank}: <span class="smalltext">{$rank}</span>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_nav",
		"template" => $db->escape_string('
<tr><td class="trow1 smalltext"><a href="usercp.php?action=myachievements" class="usercp_nav_item usercp_nav_fsubscriptions">{$lang->myachievements}</a></td></tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_usercp",
		"template" => $db->escape_string('
		<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->myachievements}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="usercp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
{$errors}
<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
	<thead>
		<tr>
			<td class="thead" colspan="3"><strong>{$lang->myachievements}</strong></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="trow1" colspan="3">{$lang->myachievements_select_achievements}</td>
		</tr>
		<tr>
			<td class="tcat" colspan="3" align="center"><strong>{$lang->myachievements_list}</strong></td>
		</tr>
		{$list}
	</tbody>
</table>
<br />
<div align="center">
<input type="hidden" name="action" value="myachievements" />
<input type="submit" class="button" name="change_myachievements" value="{$lang->myachievements_save}" />
</div>
</td>
</tr>
</table>
</form>
{$footer}
</body>
</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "myachievements_usercp_achievement",
		"template" => $db->escape_string('
<tr><td class="{$bgcolor} smalltext" width="1" align="center"><img src="{$ach[\'image\']}" title="{$ach[\'name\']}" /></td><td class="{$bgcolor} smalltext">{$ach[\'name\']} - {$ach[\'description\']}</td><td class="{$bgcolor} smalltext" width="1" align="center"><input type="checkbox" name="myachievements[]" value="{$ach[\'type\']}_{$ach[\'id\']}" {$ach[\'checked\']} /></td></tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	//Change admin permissions
	change_admin_permission("myachievements", false, 1);
	change_admin_permission("myachievements", "posts", 1);
	change_admin_permission("myachievements", "threads", 1);
	change_admin_permission("myachievements", "activity", 1);
	change_admin_permission("myachievements", "points", 1);
	change_admin_permission("myachievements", "custom", 1);
	change_admin_permission("myachievements", "ranks", 1);
	change_admin_permission("myachievements", "log", 1);

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('member_profile', '#'.preg_quote('{$warning_level}').'#', '{$warning_level}'.'{$myachievements}');
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}'.'{$post[\'myachievements_postbit\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}'.'{$post[\'myachievements_postbit\']}');
	find_replace_templatesets('usercp_nav_misc', '#'.preg_quote('id="usercpmisc_e">').'#', 'id="usercpmisc_e">'.'{myachievements_nav}');
}

function myachievements_deactivate()
{
	global $db, $mybb;

	// delete templates
	$db->delete_query('templates', 'title IN (\'myachievements_achievements\',\'myachievements_achievement\',\'myachievements_userachievements\',\'myachievements_userachievement\',\'myachievements_topranks\',\'myachievements_ranks\',\'myachievements_ranks_rank\',\'myachievements_profile\',\'myachievements_ranks_profile\',\'myachievements_postbit\',\'myachievements_no_data\',\'myachievements_topranks_rank\',\'myachievements_usercp\',\'myachievements_usercp_achievement\',\'myachievements_nav\')');

	//Change admin permissions
	change_admin_permission("myachievements", false, -1);
	change_admin_permission("myachievements", "posts", -1);
	change_admin_permission("myachievements", "threads", -1);
	change_admin_permission("myachievements", "activity", -1);
	change_admin_permission("myachievements", "points", -1);
	change_admin_permission("myachievements", "custom", -1);
	change_admin_permission("myachievements", "ranks", -1);
	change_admin_permission("myachievements", "log", -1);

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('member_profile', '#'.preg_quote('{$myachievements}').'#', '', 0);
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'myachievements_postbit\']}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'myachievements_postbit\']}').'#', '', 0);
	find_replace_templatesets('usercp_nav_misc', '#'.preg_quote('{myachievements_nav}').'#', '', 0);
}

/**
 * Sends a PM to a user
 *
 * @param array: The PM to be sent; should have 'subject', 'message', 'touid' and 'receivepms'
 * (receivepms is for admin override in case the user has disabled pm's)
 * @param int: from user id (0 if you want to use the uid of the person that sends it. -1 to use MyBB Engine
 * @return bool: true if PM sent
 */
function myachievements_send_pm($pm, $fromid = 0)
{
    global $lang, $mybb, $db;
    if($mybb->settings['enablepms'] == 0) return false;
    if (!is_array($pm))    return false;
    if (!$pm['subject'] ||!$pm['message'] || !$pm['touid'] || !$pm['receivepms']) return false;

    $lang->load('messages');

    require_once MYBB_ROOT."inc/datahandlers/pm.php";

    $pmhandler = new PMDataHandler();

    $subject = $pm['subject'];
    $message = $pm['message'];
    $toid = $pm['touid'];

    if (is_array($toid))
        $recipients_to = $toid;
    else
        $recipients_to = array($toid);
    $recipients_bcc = array();

    if (intval($fromid) == 0)
        $fromid = intval($mybb->user['uid']);
    elseif (intval($fromid) < 0)
        $fromid = 0;

    $pm = array(
        "subject" => $subject,
        "message" => $message,
        "icon" => -1,
        "fromid" => $fromid,
        "toid" => $recipients_to,
        "bccid" => $recipients_bcc,
        "do" => '',
        "pmid" => ''
    );

    $pm['options'] = array(
        "signature" => 0,
        "disablesmilies" => 0,
        "savecopy" => 0,
        "readreceipt" => 0
    );
    $pm['saveasdraft'] = 0;
    $pmhandler->admin_override = 1;
    $pmhandler->set_data($pm);
    if($pmhandler->validate_pm())
    {
        $pmhandler->insert_pm();
	}
    else
    {
        return false;
	}

    return true;
}

// remove 1 from the total amount of threads
function myachievements_deletethread()
{
	global $db, $thread;
	if(isset($thread['uid']))
	{
		$db->write_query('UPDATE '.TABLE_PREFIX.'users SET myachievements_threads=myachievements_threads-1 WHERE uid='.$thread['uid']);
	}
}

// add 1 to the total amount of threads
function myachievements_addthread(&$posthandler)
{
	global $db, $mybb;
	if (!isset($posthandler->post_insert_data)) // DRAFT!
		$uid = (int)$post['uid'];
	else
		$uid = (int)$posthandler->thread_insert_data['uid'];

	$db->write_query('UPDATE '.TABLE_PREFIX.'users SET myachievements_threads=myachievements_threads+1 WHERE uid='.$uid);
}

function myachievements_getachievement($id, $type)
{
	global $db;

	$idtypes = array('apid' => 'numposts', 'atid' => 'numthreads', 'aaid' => 'activity', 'acid' => 'custom', 'apoid' => 'points');
	$types = array('apid', 'atid', 'aaid', 'acid', 'apoid');

	if (!in_array($type, $types))
		return;

	$query = $db->simple_select('myachievements_'.$db->escape_string($idtypes[$type]), '*', $type.' = \''.intval($id).'\'');
	$achievement = $db->fetch_array($query);

	return $achievement;
}

function myachievements_profile()
{
	global $mybb, $memprofile, $lang, $theme, $myachievements, $db, $templates;

	$lang->load("myachievements");

	if ($mybb->settings['myachievements_max_profile'] != '')
	{
		$memprofile['myachievements'] = unserialize($memprofile['myachievements']);

		if (empty($memprofile['myachievements']))
			return;

		if(!isset($achievements_cache) || !is_array($achievements_cache))
		{
			$idtypes = array('numposts' => 'apid', 'numthreads' => 'atid', 'activity' => 'aaid', 'custom' => 'acid', 'points' => 'apoid');
			$types = array('numposts', 'numthreads', 'activity', 'custom', 'points');

			// query all types of achievements
			// this is done to run less queries
			foreach ($types as $type)
			{
				if ($type == 'points' && $mybb->settings['myachievements_points_enabled'] == 0)
					continue;

				switch ($type)
				{
					case 'numposts':
						$order_by = array('order_by' => 'numposts', 'order_dir' => 'desc');
					break;

					case 'numthreads':
						$order_by = array('order_by' => 'numthreads', 'order_dir' => 'desc');
					break;

					case 'activity':
						$order_by = array('order_by' => 'time', 'order_dir' => 'desc');
					break;

					case 'custom':
						$order_by = array('order_by' => 'acid', 'order_dir' => 'asc');
					break;

					case 'points':
						$order_by = array('order_by' => 'points', 'order_dir' => 'desc');
					break;
				}

				$query = $db->simple_select('myachievements_'.$type, '*', '', $order_by);
				while ($achievement = $db->fetch_array($query))
				{
					$achievements_cache[$idtypes[$type]][$achievement[$idtypes[$type]]] = $achievement;
				}
			}
		}

		$achievements = array();

		$new = false;

		// We're using the new method if these elements do not exist
		if (!isset($memprofile['myachievements']['apid']) && !isset($memprofile['myachievements']['atid']) && !isset($memprofile['myachievements']['aaid']) && !isset($memprofile['myachievements']['acid']))
		{
			if (!empty($memprofile['myachievements'][1]))
			{
				$memprofile['myachievements'] = $memprofile['myachievements'][1];
				$new = true;
			}
			elseif (!empty($memprofile['myachievements'][0]))
			{
				$memprofile['myachievements'] = $memprofile['myachievements'][0];
				$new = false;
			}
		}
		else {
			$new = false;
		}

		if ($new === true)
		{
			$achievements = '';
			$count = 1;
			foreach($memprofile['myachievements'] as $achievement)
			{
				$achievement = explode('_', $achievement);
				$ach = $achievements_cache[$achievement[0]][$achievement[1]];

				$achievements .= "<img src=\"".htmlspecialchars_uni($ach['image'])."\" title=\"".htmlspecialchars_uni($ach['name'])."\" /> ";

				$count++;

				if ($count > $mybb->settings['myachievements_max_profile'])
					break;
			}
		}
		else { // we're still using the old method
			foreach ($memprofile['myachievements'] as $ach_type => $achievement)
			{
				if (!empty($achievement))
				{
					foreach ($achievement as $ach)
					{
						$achievements[$ach_type][] = $ach[$ach_type];
					}
				}
			}

			$achs_array = $achievements;
			$achievements = '';
			$count = 1;

			foreach($achs_array as $ach_type => $achievement)
			{
				foreach($achievement as $ach)
				{
					$ach = $achievements_cache[$ach_type][$ach];
					$achievements .= "<img src=\"".htmlspecialchars_uni($ach['image'])."\" title=\"".htmlspecialchars_uni($ach['name'])."\" /> ";

					$count++;

					if ($count > $mybb->settings['myachievements_max_profile'])
						break;
				}

				if ($count > $mybb->settings['myachievements_max_profile'])
					break;
			}
		}

		eval("\$myachievements = \"".$templates->get("myachievements_profile")."\";");
	}

	$memprofile['myachievements_rank'] = unserialize($memprofile['myachievements_rank']);

	if (empty($memprofile['myachievements_rank']))
		return;

	$rank = "<img src=\"".htmlspecialchars_uni($memprofile['myachievements_rank']['image'])."\" title=\"".htmlspecialchars_uni($memprofile['myachievements_rank']['name'])."\" />";

	eval("\$myachievements .= \"".$templates->get("myachievements_ranks_profile")."\";");
}

function myachievements_postbit(&$post)
{
	global $mybb, $lang, $theme, $db, $templates, $post_type;

	if ($post_type)
	{
		$post['myachievements_postbit'] = '';
		return;
	}

	if ($mybb->settings['myachievements_max_postbit'] == '')
	{
		$post['myachievements_postbit'] = '';
		return;
	}

	$lang->load("myachievements");

	$post['myachievements'] = unserialize($post['myachievements']);

	// if amount of achievements to show is not 0 or empty, show achievements
	if ($mybb->settings['myachievements_max_postbit'] > 0 && !empty($post['myachievements']))
	{
		// CACHE ACHIEVEMENTS - ADDED IN 1.2
		static $achievements_cache; // we need to cache all achievements to use less queries

		if(!isset($achievements_cache) || !is_array($achievements_cache))
		{
			$idtypes = array('numposts' => 'apid', 'numthreads' => 'atid', 'activity' => 'aaid', 'custom' => 'acid', 'points' => 'apoid');
			$types = array('numposts', 'numthreads', 'activity', 'custom', 'points');

			// query all types of achievements
			// this is done to run less queries
			foreach ($types as $type)
			{
				if ($type == 'points' && $mybb->settings['myachievements_points_enabled'] == 0)
					continue;

				switch ($type)
				{
					case 'numposts':
						$order_by = array('order_by' => 'numposts', 'order_dir' => 'desc');
					break;

					case 'numthreads':
						$order_by = array('order_by' => 'numthreads', 'order_dir' => 'desc');
					break;

					case 'activity':
						$order_by = array('order_by' => 'time', 'order_dir' => 'desc');
					break;

					case 'custom':
						$order_by = array('order_by' => 'acid', 'order_dir' => 'asc');
					break;

					case 'points':
						$order_by = array('order_by' => 'points', 'order_dir' => 'desc');
					break;
				}

				$query = $db->simple_select('myachievements_'.$type, '*', '', $order_by);
				while ($achievement = $db->fetch_array($query))
				{
					$achievements_cache[$idtypes[$type]][$achievement[$idtypes[$type]]] = $achievement;
				}
			}
		}

		$achievements = array();

		$new = false;

		// We're using the new method if these elements do not exist
		if (!isset($post['myachievements']['apid']) && !isset($post['myachievements']['atid']) && !isset($post['myachievements']['aaid']) && !isset($post['myachievements']['acid']))
		{
			if (!empty($post['myachievements'][1]))
			{
				$post['myachievements'] = $post['myachievements'][1];
				$new = true;
			}
			elseif(!empty($post['myachievements'][0]))
			{
				$post['myachievements'] = $post['myachievements'][0];
				$new = false;
			}
		}
		else {
			$new = false;
		}

		if ($new === true)
		{
			$achievements = '';
				$count = 1;
			foreach($post['myachievements'] as $achievement)
			{
				$achievement = explode('_', $achievement);
				// use the cache array
				$ach = $achievements_cache[$achievement[0]][$achievement[1]];
				$achievements .= "<img src=\"".htmlspecialchars_uni($ach['image'])."\" title=\"".htmlspecialchars_uni($ach['name'])."\" /> ";

				$count++;

				if ($count > $mybb->settings['myachievements_max_postbit'])
					break;
			}
		}
		else {  // Who the hell uses the old method these days?

			foreach ($post['myachievements'] as $ach_type => $achievement)
			{
				if (!empty($achievement))
				{
					foreach ($achievement as $ach)
					{
						$achievements[$ach_type][] = $ach[$ach_type];
					}
				}
			}

			$achs_array = $achievements;
			$achievements = '';
			$count = 1;

			foreach($achs_array as $ach_type => $achievement)
			{
				foreach($achievement as $ach)
				{
					// use the cache array
					$ach = $achievements_cache[$ach_type][$ach];
					$achievements .= "<img src=\"".htmlspecialchars_uni($ach['image'])."\" title=\"".htmlspecialchars_uni($ach['name'])."\" /> ";

					$count++;

					if ($count > $mybb->settings['myachievements_max_postbit'])
						break;
				}

				if ($count > $mybb->settings['myachievements_max_postbit'])
					break;
			}
		}
	}

	if (empty($achievements))
		return;

	// show rank
	$post['myachievements_rank'] = unserialize($post['myachievements_rank']);

	if (empty($post['myachievements_rank']))
	{
		eval("\$post['myachievements_postbit'] = \"".$templates->get("myachievements_postbit")."\";");
		return;
	}

	$rank = "<img src=\"".htmlspecialchars_uni($post['myachievements_rank']['image'])."\" title=\"".htmlspecialchars_uni($post['myachievements_rank']['name'])."\" />";

	eval("\$post['myachievements_postbit'] = \"".$templates->get("myachievements_postbit")."\";");
}

// Logs something
function myachievements_log($type, $uid, $username, $data)
{
	global $mybb, $db;

	$type = $db->escape_string($type);
	$uid = (int)$uid; // hopefully it's a valid UID, but let's not confirm to not lose time
	$username = $db->escape_string($username);
	$data = $db->escape_string($data);

	$db->insert_query('myachievements_log', array('type' => $type, 'uid' => $uid, 'username' => $username, 'date' => TIME_NOW, 'data' => $data));
}

function myachievements_online(&$plugin_array)
{
	if (preg_match('/myachievements\.php/',$plugin_array['user_activity']['location']))
	{
		global $lang, $mybb;
		$lang->load("myachievements");

		$plugin_array['location_name'] = "Viewing <a href=\"".$mybb->settings['bburl']."/myachievements.php\">".$lang->myachievements."</a>";
	}

	return $plugin_array;
}

function myachievements_usercp_nav()
{
	global $lang, $usercpnav, $templates;
	$lang->load("myachievements");

	eval("\$myachievements = \"".$templates->get("myachievements_nav")."\";");
	$usercpnav = str_replace('{myachievements_nav}', $myachievements, $usercpnav);
}

function myachievements_usercp()
{
	global $headerinclude, $errors, $header, $footer, $theme, $templates, $mybb, $db, $lang, $usercpnav;

	if ($mybb->input['action'] != 'myachievements') return;

	$lang->load("myachievements");

	if ($mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		// Get list of achievements we have chosen
		$achievements = $mybb->input['myachievements'];

		if (!is_array($achievements)) error(); // something wrong happened?

		// Calculate what's the max number of achievements we can choose
		if ($mybb->settings['myachievements_max_postbit'] == $mybb->settings['myachievements_max_profile'])
			$max = $mybb->settings['myachievements_max_postbit'];
		elseif ($mybb->settings['myachievements_max_postbit'] > $mybb->settings['myachievements_max_profile'])
			$max = $mybb->settings['myachievements_max_postbit'];
		else
			$max = $mybb->settings['myachievements_max_profile'];

		// If we selected more than the max achievements permitted
		if (count($achievements) > $max)
		{
			error($lang->myachievements_max_error);
		}

		// Now we must verify that we actually earned the achievements we have selected
		// Get list of achievements available
		$myachievements = unserialize($mybb->user['myachievements']);

		$chosen_achs = array();
		$error = false;
		if (!empty($myachievements))
		{
			foreach ($myachievements[0] as $ach_type => $achievement)
			{
				if ($mybb->settings['myachievements_custom_usercp'] == 1 && $ach_type != 'acid')
					continue;

				foreach ($achievement as $ach)
				{
					$earned_achs[] = $ach_type.'_'.$ach[$ach_type];
				}
			}

			foreach ($achievements as $achievement)
			{
				if (!in_array($achievement, $earned_achs))
				{
					$error = true;
					break;
				}

				$chosen_achs[] = $achievement;
			}
		}
		else $error = true;

		// we didn't earn any...
		if ($error === true)
			error($lang->myachievements_invalid_achievements);

		$ourachievements = array($myachievements[0], $chosen_achs);

		$db->update_query('users', array('myachievements' => $db->escape_string(serialize($ourachievements))), 'uid=\''.intval($mybb->user['uid']).'\'');

		redirect("usercp.php", $lang->myachievements_updated_message, $lang->myachievements_updated_title);
	}

	add_breadcrumb($lang->myachievements);

	// Get list of achievements available
	$myachievements = unserialize($mybb->user['myachievements']);

	// If one of these elements exist then we're still using the old structure
	if (isset($myachievements['apid']) || isset($myachievements['atid']) || isset($myachievements['aaid']) || isset($myachievements['acid']))
	{
		$newachievements[0] = $myachievements; // contains our achievements list
		$newachievements[1] = array(); // contains selected achievements
		$myachievements = $newachievements;

		$db->update_query('users', array('myachievements' => $db->escape_string(serialize($newachievements))), 'uid=\''.intval($mybb->user['uid']).'\'');
	}

	if (!empty($myachievements[0]))
	{
		$achievements = array();

		foreach ($myachievements[0] as $ach_type => $achievement)
		{
			foreach ($achievement as $ach)
			{
				$achievements[$ach_type][] = $ach[$ach_type];
			}
		}

		$list = '';

		if (!empty($achievements))
		{
			foreach($achievements as $ach_type => $achievement)
			{
				if ($mybb->settings['myachievements_custom_usercp'] == 1 && $ach_type != 'acid')
					continue;

				foreach($achievement as $ach)
				{
					$bgcolor = alt_trow();
					$ach = myachievements_getachievement($ach, $ach_type);
					$ach['image'] = htmlspecialchars_uni($ach['image']);
					$ach['name'] = htmlspecialchars_uni($ach['name']);
					$ach['description'] = htmlspecialchars_uni($ach['description']);
					$ach['type'] = htmlspecialchars_uni($ach_type);
					$ach['id'] = intval($ach[$ach_type]);

					if (!empty($myachievements[1]) && in_array($ach_type.'_'.$ach['id'], $myachievements[1]))
					{
						$ach['checked'] = 'checked';
					}
					else
						$ach['checked'] = '';

					eval("\$list .= \"".$templates->get("myachievements_usercp_achievement")."\";");
				}
			}
		}
	}

	$lang->myachievements_select_achievements = $lang->sprintf($lang->myachievements_select_achievements, $mybb->settings['myachievements_max_postbit'], $mybb->settings['myachievements_max_profile']);

	eval("\$page = \"".$templates->get("myachievements_usercp")."\";");
	output_page($page);
}

// &#71;&#101;&#110;&#101;&#114;&#105;&#99;

?>
