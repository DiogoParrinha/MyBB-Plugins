<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/admin/modules/mydownloads/module_meta.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function mydownloads_meta()
{
	global $page, $lang, $plugins;

	if(!function_exists('mydownloads_build_tree'))
		return;

	$sub_menu = array();
	$sub_menu['10'] = array("id" => "downloads_categories", "title" => $lang->nav_downloads_categories, "link" => "index.php?module=mydownloads-downloads_categories");
	$sub_menu['15'] = array("id" => "log", "title" => $lang->nav_log, "link" => "index.php?module=mydownloads-log");
	$sub_menu['20'] = array("id" => "manage_submissions", "title" => $lang->nav_manage_submissions, "link" => "index.php?module=mydownloads-manage_submissions");
	$sub_menu['25'] = array("id" => "paypal", "title" => $lang->nav_paypal, "link" => "index.php?module=mydownloads-paypal");
	$sub_menu['30'] = array("id" => "reports", "title" => $lang->nav_reports, "link" => "index.php?module=mydownloads-reports");
	$sub_menu['35'] = array("id" => "tags", "title" => $lang->nav_tags, "link" => "index.php?module=mydownloads-tags");

	$sub_menu = $plugins->run_hooks("admin_mydownloads_menu", $sub_menu);

	$lang->load('mydownloads');

	$page->add_menu_item($lang->mydownloads, "mydownloads", "index.php?module=mydownloads", 60, $sub_menu);

	return true;
}

function mydownloads_action_handler($action)
{
	global $page, $lang, $plugins;

	$page->active_module = "mydownloads";

	$actions = array(
		'downloads_categories' => array('active' => 'downloads_categories', 'file' => 'downloads_categories.php'),
		'log' => array('active' => 'log', 'file' => 'log.php'),
		'manage_submissions' => array('active' => 'manage_submissions', 'file' => 'manage_submissions.php'),
		'paypal' => array('active' => 'paypal', 'file' => 'paypal.php'),
		'reports' => array('active' => 'reports', 'file' => 'reports.php'),
		'tags' => array('active' => 'tags', 'file' => 'tags.php')
	);

	$actions = $plugins->run_hooks("admin_tools_action_handler", $actions);

	if(!isset($actions[$action]))
	{
		$page->active_action = "downloads_categories";
		return "downloads_categories.php";
	}
	else
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
}

function mydownloads_admin_permissions()
{
	global $lang, $plugins;

	$admin_permissions = array(
		"mydownloads"			=> $lang->can_manage_mydownloads,
		"downloads_categories"	=> $lang->can_manage_downloads_categories,
		"log"					=> $lang->can_manage_log,
		"manage_submissions"	=> $lang->can_manage_manage_submissions,
		"paypal"				=> $lang->can_manage_paypal,
		"reports"				=> $lang->can_manage_reports,
		"tags"					=> $lang->can_manage_tags
	);

	$admin_permissions = $plugins->run_hooks("admin_mydownloads_permissions", $admin_permissions);

	return array("name" => $lang->mydownloads, "permissions" => $admin_permissions, "disporder" => 60);
}
?>
