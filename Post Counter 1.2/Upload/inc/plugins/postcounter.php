<?php
/***************************************************************************
 *
 *  Post Counter plugin (/inc/plugins/postcounter.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  Website: http://consoleaddicted.com
 *  License: license.txt
 *
 *  Count someone's posts since a certain date
 *
 ***************************************************************************/

/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly.");

// add hooks
$plugins->add_hook('admin_load', 'postcounter_admin');
$plugins->add_hook('admin_tools_menu', 'postcounter_admin_tools_menu');
$plugins->add_hook('admin_tools_action_handler', 'postcounter_admin_tools_action_handler');
$plugins->add_hook('admin_tools_permissions', 'postcounter_admin_permissions');

function postcounter_info()
{
	return array(
		"name"			=> "Post Counter",
		"description"	=> "Count someone's posts since a certain date",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.2",
		"guid" 			=> "c17312633a12d09643be0dc63da12a91",
		"compatibility"	=> "18*"
	);
}


function postcounter_activate()
{
	global $db, $lang;
}


function postcounter_deactivate()
{
	global $db, $mybb;
}

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function postcounter_admin_tools_menu(&$sub_menu)
{
	global $lang;

	$lang->load('postcounter');
	$sub_menu[] = array('id' => 'postcounter', 'title' => $lang->postcounter_index, 'link' => 'index.php?module=tools-postcounter');
}

function postcounter_admin_tools_action_handler(&$actions)
{
	$actions['postcounter'] = array('active' => 'postcounter', 'file' => 'postcounter');
}

function postcounter_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	$lang->load("postcounter", false, true);
	$admin_permissions['postcounter'] = $lang->postcounter_canmanage;

}

function postcounter_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	$lang->load("postcounter", false, true);

	if($run_module == 'tools' && $action_file == 'postcounter')
	{
		$page->add_breadcrumb_item($lang->postcounter, 'index.php?module=tools-postcounter');
		$page->output_header($lang->postcounter);

		$sub_tabs['postcounter_count'] = array(
			'title'			=> $lang->postcounter_count,
			'link'			=> 'index.php?module=tools-postcounter',
			'description'	=> $lang->postcounter_count_desc
		);

		$page->output_nav_tabs($sub_tabs, 'postcounter_count');

		if (!$mybb->input['action'])
		{
			$form = new Form("index.php?module=tools-postcounter&amp;action=count", "post", "postcounter");

			$form_container = new FormContainer($lang->postcounter_count);
			$form_container->output_row($lang->postcounter_user, $lang->postcounter_user_desc, $form->generate_text_box('user', '', array('id' => 'user')), 'user');
			$form_container->output_row($lang->postcounter_exemptforums, $lang->postcounter_exemptforums_desc, $form->generate_text_box('exempt', '', array('id' => 'exempt')), 'exempt');
			$form_container->output_row($lang->postcounter_date, $lang->postcounter_date_desc, $form->generate_text_box('date', '', array('id' => 'date')), 'date');
			$form_container->output_row($lang->postcounter_threads, $lang->postcounter_threads_desc, $form->generate_yes_no_radio('countthreads', 0, true, "", ""), 'countthreads');

			$form_container->end();

			$buttons = "";
			$buttons[] = $form->generate_submit_button($lang->postcounter_submit);
			$buttons[] = $form->generate_reset_button($lang->postcounter_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'count')
		{
			$posts = $threads = 0;

			$user = trim($mybb->input['user']);
			if (!$user || !$db->fetch_field($db->simple_select('users', 'uid', 'username=\''.$db->escape_string($user).'\''), 'uid'))
			{
				flash_message($lang->postcounter_invalid_user, 'error');
				admin_redirect("index.php?module=tools-postcounter");
			}

			$forums = $mybb->input['exempt'];
			if ($forums != '')
			{
				$forums = explode(',', $forums);
			}
			else
				$forums = array();

			$date = $mybb->input['date'];
			if (!$mybb->input['date'])
			{
				flash_message($lang->postcounter_invalid_date, 'error');
				admin_redirect("index.php?module=tools-postcounter");
			}
			$date = strtotime($date);

			$countthreads = intval($mybb->input['countthreads']);

			$query = $db->simple_select("posts", "pid", "username='".$db->escape_string($user)."' AND dateline > ".$date." AND fid NOT IN ('".implode('\',\'', $forums)."')");
			while ($post = $db->fetch_array($query))
			{
				$posts++;
			}

			if ($countthreads == 1)
			{
				$query = $db->simple_select("threads", "tid", "username='".$db->escape_string($user)."' AND dateline > ".$date." AND fid NOT IN ('".implode('\',\'', $forums)."')");
				while ($thread = $db->fetch_array($query))
				{
					$threads++;
				}

				flash_message($lang->sprintf($lang->postcounter_total_count_both, $posts, $threads), 'success');
				admin_redirect("index.php?module=tools-postcounter");
			}
			else {
				flash_message($lang->sprintf($lang->postcounter_total_count, $posts, $threads), 'success');
				admin_redirect("index.php?module=tools-postcounter");
			}
		}

		$page->output_footer();
		exit;
	}
}

?>
