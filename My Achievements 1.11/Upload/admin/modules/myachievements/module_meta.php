<?php
/***************************************************************************
 *
 *  My Achievements plugin (/admin/modules/myachievements/module_meta.php)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  Adds an achievements system to MyBB.
 *
 ***************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function myachievements_meta()
{
	global $page, $lang, $plugins;

	$sub_menu = array();
	$sub_menu['5'] = array("id" => "ranks", "title" => $lang->nav_achievements_ranks, "link" => "index.php?module=myachievements-ranks");
	$sub_menu['10'] = array("id" => "posts", "title" => $lang->nav_achievements_posts, "link" => "index.php?module=myachievements-posts");
	$sub_menu['15'] = array("id" => "threads", "title" => $lang->nav_achievements_threads, "link" => "index.php?module=myachievements-threads");
	$sub_menu['20'] = array("id" => "activity", "title" => $lang->nav_achievements_activity, "link" => "index.php?module=myachievements-activity");
	$sub_menu['25'] = array("id" => "points", "title" => $lang->nav_achievements_points, "link" => "index.php?module=myachievements-points");
	$sub_menu['30'] = array("id" => "custom", "title" => $lang->nav_achievements_custom, "link" => "index.php?module=myachievements-custom");
	$sub_menu['35'] = array("id" => "log", "title" => $lang->nav_log, "link" => "index.php?module=myachievements-log");
	$sub_menu['40'] = array("id" => "rebuild", "title" => $lang->nav_rebuild, "link" => "index.php?module=myachievements-rebuild");

	$sub_menu = $plugins->run_hooks("admin_myachievements_menu", $sub_menu);

	$page->add_menu_item($lang->myachievements, "myachievements", "index.php?module=myachievements", 60, $sub_menu);

	return true;
}

function myachievements_action_handler($action)
{
	global $page, $lang, $plugins;

	$page->active_module = "myachievements";

	$actions = array(
		'ranks' => array('active' => 'ranks', 'file' => 'ranks.php'),
		'posts' => array('active' => 'posts', 'file' => 'posts.php'),
		'threads' => array('active' => 'threads', 'file' => 'threads.php'),
		'activity' => array('active' => 'activity', 'file' => 'activity.php'),
		'points' => array('active' => 'points', 'file' => 'points.php'),
		'custom' => array('active' => 'custom', 'file' => 'custom.php'),
		'log' => array('active' => 'log', 'file' => 'log.php'),
		'rebuild' => array('active' => 'rebuild', 'file' => 'rebuild.php')
	);

	$actions = $plugins->run_hooks("admin_tools_action_handler", $actions);

	if(!isset($actions[$action]))
	{
		$page->active_action = "ranks";
		return "ranks.php";
	}
	else
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
}

function myachievements_admin_permissions()
{
	global $lang, $plugins;

	$admin_permissions = array(
		"myachievements"	=> $lang->can_manage_myachievements,
		"posts"				=> $lang->can_manage_posts,
		"threads"			=> $lang->can_manage_threads,
		"activity"			=> $lang->can_manage_activity,
		"points"			=> $lang->can_manage_points,
		"custom"			=> $lang->can_manage_custom,
		"ranks"				=> $lang->can_manage_ranks,
		"log"				=> $lang->can_manage_log,
		"rebuild"			=> $lang->can_manage_rebuild
	);

	$admin_permissions = $plugins->run_hooks("admin_myachievements_permissions", $admin_permissions);

	return array("name" => $lang->myachievements, "permissions" => $admin_permissions, "disporder" => 60);
}
?>
