<?php
/***************************************************************************
 *
 *  My Achievements plugin (/admin/modules/myachievements-rebuild.php)
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

require_once MYBB_ROOT."inc/functions_task.php";

$lang->load('myachievements');

if (!$mybb->input['action']) // view rebuild page
{
	$page->add_breadcrumb_item($lang->myachievements_rebuild, 'index.php?module=myachievements-rebuild');

	$page->output_header($lang->myachievements_rebuild);

	$sub_tabs['myachievements_rebuild'] = array(
		'title'			=> $lang->myachievements_rebuild,
		'link'			=> 'index.php?module=myachievements-rebuild',
		'description'	=> $lang->myachievements_rebuild_description
	);

	$page->output_nav_tabs($sub_tabs, 'myachievements_rebuild');

	$form = new Form("index.php?module=myachievements-rebuild&amp;action=do_rebuild", "post", "myachievements");

	$form_container = new FormContainer($lang->myachievements_rebuild);
	$form_container->output_row($lang->myachievements_rebuild_ranks_achievements, $lang->myachievements_rebuild_ranks_achievements_desc, '', 'rebuild');
	$form_container->output_row($lang->myachievements_per_page, $lang->myachievements_per_page_desc, $form->generate_text_box('per_page', 100, array('id' => 'per_page')), 'per_page');
	$form_container->output_row($lang->myachievements_full, $lang->myachievements_full_desc, $form->generate_yes_no_radio('full', 0, true), 'full');
	$form_container->output_row($lang->myachievements_ignore_custom, $lang->myachievements_ignore_custom_desc, $form->generate_yes_no_radio('ignore_custom', 1, true), 'ignore_custom');
	$form_container->end();

	$buttons = array();;
	$buttons[] = $form->generate_submit_button($lang->myachievements_submit);
	$buttons[] = $form->generate_reset_button($lang->myachievements_reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif ($mybb->input['action'] == 'do_rebuild')
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=myachievements-rebuild");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=myachievements-rebuild");
		}

		// reset achievements, rank and level
		if (intval($mybb->input['per_page']) > 0)
			$per_page = intval($mybb->input['per_page']);
		else
			$per_page = 100;

		$start = intval($mybb->input['page']);
		if (empty($start) || $start <= 0) $start = 1;
		$start = ($start-1) * $per_page;
		$end = $start + $per_page;

		// we dont want it to stop
		ignore_user_abort(true);
		@set_time_limit(0);

		$query = $db->simple_select("users", "COUNT(*) as users", "myachievements!='' OR myachievements_level!='' OR myachievements_rank!=''");
		$total_users = $db->fetch_field($query, 'users');

		if ($total_users > 0)
		{
			$uids = array();
			$query = $db->simple_select('users', 'uid,myachievements', "myachievements!='' OR myachievements_level!='' OR myachievements_rank!=''", array('order_by' => 'uid', 'order_dir' => 'ASC', 'limit_start' => $start, 'limit' => $per_page));
			while($user = $db->fetch_array($query)) {
				$uids[] = (int)$user['uid'];

				if($mybb->input['ignore_custom'] == 1)
				{
					$user_achievements = unserialize($user['myachievements']);

					if(!isset($user_achievements['apid']) && !isset($user_achievements['atid']) && !isset($user_achievements['aaid']) && !isset($user_achievements['acid']))
					{
						// If so, the actual array we want is different
						$chosen_achs = $user_achievements[1];
						$user_achievements = $user_achievements[0];
					}

					// Erase all but custom achievements
					unset($user_achievements['apid']);
					unset($user_achievements['atid']);
					unset($user_achievements['aaid']);
					unset($user_achievements['apoid']);

					$db->update_query('users', array('myachievements' => $db->escape_string(serialize(array($user_achievements, array())))), 'uid='.(int)$user['uid']);
				}
			}

			if($mybb->input['ignore_custom'] == 1)
				$db->update_query('users', array('myachievements_level' => 0, 'myachievements_rank' => ''), "uid IN ('".implode("','", $uids)."')");
			else
				$db->update_query('users', array('myachievements' => '', 'myachievements_level' => 0, 'myachievements_rank' => ''), "uid IN ('".implode("','", $uids)."')");

			if ($end < $total_users)
			{
				global $form, $page;
				$lang->load("myachievements");
				$page->output_header($lang->myachievements);

				$form = new Form("index.php?module=myachievements-rebuild&amp;action=do_rebuild", "post", "myachievements");

				echo $form->generate_hidden_field("per_page", intval($mybb->input['per_page']));
				echo $form->generate_hidden_field("page", intval($mybb->input['page'])+1);
				echo "<div class=\"confirm_action\">\n";
				echo "<p>".$lang->myachievements_click_continue."</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->myachievements_continue, array('class' => 'button_yes'));
				echo "</p>\n";
				echo "</div>\n";

				$form->end();

				$page->output_footer();
				exit;
			}
		}

		if ($mybb->input['full'] == 1) // it's a full reset so set last run to 0
		{
			$db->update_query('tasks', array('lastrun' => '0', 'locked' => '0'), 'file=\'myachievements\'');
		}

		$query = $db->simple_select("tasks", "*", "file='myachievements'");
		$task = $db->fetch_array($query);

		// Does the task not exist?
		if(!$task['tid'])
		{
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=tools/tasks");
		}

		global $achievement_uid;
		$achievement_uid = 0;

		// Run task
		run_task($task['tid']);

		flash_message($lang->myachievements_rebuilt, 'success');
		admin_redirect('index.php?module=myachievements-rebuild');
	}
	else
	{
		$page->add_breadcrumb_item($lang->myachievements_rebuild, 'index.php?module=myachievements-rebuild');

		$page->output_header($lang->myachievements_rebuild);

		$form = new Form("index.php?module=myachievements-rebuild&amp;action=rebuild", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->myachievements_rebuild_confirm}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
		echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
		echo "</p>\n";
		echo "</div>\n";
		$form->end();
	}
}

$page->output_footer();

?>
