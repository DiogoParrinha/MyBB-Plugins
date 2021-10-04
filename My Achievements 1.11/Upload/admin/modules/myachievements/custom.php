<?php
/***************************************************************************
 *
 *  My Achievements plugin (/admin/modules/myachievements-custom.php)
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

$lang->load('myachievements');

$page->add_breadcrumb_item($lang->myachievements_custom, 'index.php?module=myachievements-custom');

$page->output_header($lang->myachievements_custom);


$sub_tabs['myachievements_view'] = array(
	'title'			=> $lang->myachievements_view,
	'link'			=> 'index.php?module=myachievements-custom',
	'description'	=> $lang->sprintf($lang->myachievements_view_desc, "custom achievements")
);

$sub_tabs['myachievements_add'] = array(
	'title'			=> $lang->myachievements_add,
	'link'			=> 'index.php?module=myachievements-custom&amp;action=add',
	'description'	=> $lang->sprintf($lang->myachievements_add_desc, "custom achievements")
);

$sub_tabs['myachievements_edit'] = array(
	'title'			=> $lang->myachievements_edit,
	'link'			=> 'index.php?module=myachievements-custom&amp;action=edit',
	'description'	=> $lang->sprintf($lang->myachievements_edit_desc, "custom achievements")
);

$sub_tabs['myachievements_give'] = array(
	'title'			=> $lang->myachievements_give,
	'link'			=> 'index.php?module=myachievements-custom&amp;action=give',
	'description'	=> $lang->sprintf($lang->myachievements_give_desc, "custom achievements")
);

$sub_tabs['myachievements_revoke'] = array(
	'title'			=> $lang->myachievements_revoke,
	'link'			=> 'index.php?module=myachievements-custom&amp;action=revoke',
	'description'	=> $lang->sprintf($lang->myachievements_revoke_desc, "custom achievements")
);

switch ($mybb->input['action'])
{
	case 'add':
		$page->output_nav_tabs($sub_tabs, 'myachievements_add');
	break;
	case 'edit':
		$page->output_nav_tabs($sub_tabs, 'myachievements_edit');
	break;
	case 'give':
		$page->output_nav_tabs($sub_tabs, 'myachievements_give');
	break;
	case 'revoke':
		$page->output_nav_tabs($sub_tabs, 'myachievements_revoke');
	break;
	default:
		$page->output_nav_tabs($sub_tabs, 'myachievements_view');
}

if (!$mybb->input['action']) // No action, view entries
{
	$per_page = 25;
	if($mybb->input['page'] && $mybb->input['page'] > 1)
	{
		$mybb->input['page'] = intval($mybb->input['page']);
		$start = ($mybb->input['page']*$per_page)-$per_page;
	}
	else
	{
		$mybb->input['page'] = 1;
		$start = 0;
	}

	$query = $db->simple_select("myachievements_custom", "COUNT(acid) as custom");
	$total_rows = $db->fetch_field($query, "custom");

	if ($total_rows > $per_page)
		echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=myachievements-custom&amp;page={page}");

	// table
	$table = new Table;
	$table->construct_header($lang->myachievements_icon, array('width' => '1%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_name, array('width' => '25%'));
	$table->construct_header($lang->myachievements_description, array('width' => '30%'));
	$table->construct_header($lang->myachievements_options, array('width' => '20%', 'class' => 'align_center'));

	// fetch all achievements
	$query = $db->simple_select('myachievements_custom', '*', '', array('order_by' => 'acid', 'order_dir' => 'asc', 'limit' => "{$start}, {$per_page}"));
	while($achievement = $db->fetch_array($query)) {

		$achievement['image'] = htmlspecialchars_uni($achievement['image']);
		$achievement['name'] = htmlspecialchars_uni($achievement['name']);
		$achievement['description'] = nl2br(htmlspecialchars_uni($achievement['description']));

		$table->construct_cell("<img src=\"".$mybb->settings['bburl']."/".$achievement['image']."\" />", array('class' => 'align_center'));
		$table->construct_cell($achievement['name']);
		$table->construct_cell($achievement['description']);

		$table->construct_cell("<a href=\"index.php?module=myachievements-custom&amp;action=delete&amp;acid={$achievement['acid']}\" target=\"_self\">{$lang->myachievements_delete}</a> - <a href=\"index.php?module=myachievements-custom&amp;action=edit&amp;acid={$achievement['acid']}\" target=\"_self\">{$lang->myachievements_edit}</a>", array('class' => 'align_center'));

		$table->construct_row();
	}

	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->myachievements_no_data, array('colspan' => 5));

		$table->construct_row();
	}

	$table->output($lang->myachievements_custom);
}
elseif ($mybb->input['action'] == 'add') // Add entry
{
	if ($mybb->request_method == "post") // submit
	{
		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		$insert_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
		);

		$db->insert_query('myachievements_custom', $insert_array);

		flash_message($lang->myachievements_achievement_added, 'success');
		admin_redirect("index.php?module=myachievements-custom");
	}
	else {

		$form = new Form("index.php?module=myachievements-custom&amp;action=add", "post", "myachievements");

		$form_container = new FormContainer($lang->myachievements_add_achievement);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "achievement"), $form->generate_text_box('name', '', array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "achievement"), $form->generate_text_area('description', '', array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_icon."<em>*</em>", $lang->sprintf($lang->myachievements_icon_desc, "achievement"), $form->generate_text_box('icon', '', array('id' => 'icon')), 'icon');

		$form_container->end();

		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->myachievements_submit);
		$buttons[] = $form->generate_reset_button($lang->myachievements_reset);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'edit') // Edit entry
{
	if ($mybb->request_method == "post") // submit
	{
		$query = $db->simple_select('myachievements_custom', '*', 'acid=\''.intval($mybb->input['acid']).'\'');
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		$update_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
		);

		$db->update_query('myachievements_custom', $update_array, 'acid='.$achievement['acid']);

		flash_message($lang->myachievements_achievement_edited, 'success');
		admin_redirect("index.php?module=myachievements-custom");
	}
	else {

		$query = $db->simple_select('myachievements_custom', '*', 'acid=\''.intval($mybb->input['acid']).'\'');
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		$form = new Form("index.php?module=myachievements-custom&amp;action=edit", "post", "myachievements");

		echo $form->generate_hidden_field("acid", $achievement['acid']);

		$form_container = new FormContainer($lang->myachievements_add_achievement);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "achievement"), $form->generate_text_box('name', htmlspecialchars_uni($achievement['name']), array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "achievement"), $form->generate_text_area('description', htmlspecialchars_uni($achievement['description']), array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_icon."<em>*</em>", $lang->sprintf($lang->myachievements_icon_desc, "achievement"), $form->generate_text_box('icon', htmlspecialchars_uni($achievement['image']), array('id' => 'icon')), 'icon');

		$form_container->end();

		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->myachievements_submit);
		$buttons[] = $form->generate_reset_button($lang->myachievements_reset);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'delete')
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=myachievements-custom");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		if (!$db->fetch_field($db->simple_select('myachievements_custom', 'name', 'acid='.intval($mybb->input['acid']), array('limit' => 1)), 'name'))
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect('index.php?module=myachievements-custom');
		}
		else {
			$db->delete_query('myachievements_custom', 'acid='.intval($mybb->input['acid']));
			flash_message($lang->myachievements_achievement_deleted, 'success');
			admin_redirect('index.php?module=myachievements-custom');
		}
	}
	else
	{
		$mybb->input['acid'] = intval($mybb->input['acid']);
		$form = new Form("index.php?module=myachievements-custom&amp;action=delete&amp;acid={$mybb->input['acid']}&amp;my_post_key={$mybb->post_code}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->myachievements_achievement_deleteconfirm}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
		echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
		echo "</p>\n";
		echo "</div>\n";
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'give') // Give achievement
{
	if ($mybb->request_method == "post") // submit
	{
		if (empty($mybb->input['username']))
		{
			flash_message($lang->myachievements_no_user, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		if (strpos($mybb->input['reason'], "'") !== false || strpos($mybb->input['reason'], '"') !== false)
		{
			flash_message($lang->myachievements_reason_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		$query = $db->simple_select('myachievements_custom', '*', 'acid='.intval($mybb->input['acid']), array('limit' => 1));
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect('index.php?module=myachievements-custom');
		}

		// get user id, user achievements and user rank
		$query = $db->simple_select('users', 'username,uid,myachievements,myachievements_rank,myachievements_level', 'username=\''.$db->escape_string(trim($mybb->input['username'])).'\'', array('limit' => 1));
		$user = $db->fetch_array($query);
		if (!$user)
		{
			flash_message($lang->myachievements_no_user, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		// add achievement first
		$user_achievements = unserialize($user['myachievements']);

		// we're using the new method
		if (!isset($user_achievements['apid']) && !isset($user_achievements['atid']) && !isset($user_achievements['aaid']) && !isset($user_achievements['acid']))
		{
			// let's make sure the user does not have this achievement already
			if (isset($user_achievements[0]['acid'][$achievement['acid']]))
			{
				flash_message($lang->myachievements_user_already_acid, 'error');
				admin_redirect("index.php?module=myachievements-custom");
			}
			else
				$user_achievements[0]['acid'][$achievement['acid']] = array('acid' => intval($achievement['acid']), 'name' => $achievement['name'], 'reason' => trim($mybb->input['reason']));
		}
		else {
			// let's make sure the user does not have this achievement already
			if (isset($user_achievements['acid'][$achievement['acid']]))
			{
				flash_message($lang->myachievements_user_already_acid, 'error');
				admin_redirect("index.php?module=myachievements-custom");
			}
			else
				$user_achievements['acid'][$achievement['acid']] = array('acid' => intval($achievement['acid']), 'name' => $achievement['name'], 'reason' => trim($mybb->input['reason']));
		}

		$user_achievements = $db->escape_string(serialize($user_achievements));

		// update achievements list
		$db->update_query('users', array('myachievements' => $user_achievements), 'uid=\''.$user['uid'].'\'');

		// log that the user has been updated with a achievement - don't check for a rank update since this will be done the next time the task is run
		myachievements_log('updated_via_custom', $user['uid'], $user['username'], 'New custom achievement: '.$achievement['name']);

		flash_message($lang->myachievements_achievement_given, 'success');
		admin_redirect("index.php?module=myachievements-custom");
	}
	else {
		$achievements_acid[0] = $lang->myachievements_select_achievement;

		// get all custom achievements
		$query = $db->simple_select('myachievements_custom');
		while ($achievement = $db->fetch_array($query))
		{
			$achievements_acid[$achievement['acid']] = htmlspecialchars_uni($achievement['name']);
		}
		$db->free_result($query);

		$form = new Form("index.php?module=myachievements-custom&amp;action=give", "post", "myachievements");

		$form_container = new FormContainer($lang->myachievements_give_achievement);

		$form_container->output_row($lang->myachievements_username."<em>*</em>", $lang->sprintf($lang->myachievements_username_desc, "achievement"), $form->generate_text_box('username', '', array('id' => 'username')), 'username');

		$form_container->output_row($lang->myachievements_reason, $lang->sprintf($lang->myachievements_reason_desc, "achievement"), $form->generate_text_area('reason', '', array('id' => 'reason')), 'reason');

		$form_container->output_row($lang->myachievements_achievements_acid, $lang->myachievements_achievements_acid_give_desc, $form->generate_select_box('acid', $achievements_acid, '0', array('id' => 'acid')), 'acid');

		$form_container->end();

		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->myachievements_submit);
		$buttons[] = $form->generate_reset_button($lang->myachievements_reset);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'revoke') // Revoke achievement
{
	if ($mybb->request_method == "post") // submit
	{
		if (empty($mybb->input['username']))
		{
			flash_message($lang->myachievements_no_user, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		if (empty($mybb->input['acid']))
		{
			flash_message($lang->myachievements_no_achievement, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		$query = $db->simple_select('myachievements_custom', '*', 'acid='.intval($mybb->input['acid']), array('limit' => 1));
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect('index.php?module=myachievements-custom');
		}

		// get user id, and user achievements to check if we have given this achievement to this user
		$query = $db->simple_select('users', 'username,uid,myachievements', 'username=\''.$db->escape_string(trim($mybb->input['username'])).'\'', array('limit' => 1));
		$user = $db->fetch_array($query);
		if (!$user)
		{
			flash_message($lang->myachievements_no_user, 'error');
			admin_redirect("index.php?module=myachievements-custom");
		}

		// add achievement first
		$user_achievements = unserialize($user['myachievements']);

		// we're using the new method
		if (!isset($user_achievements['apid']) && !isset($user_achievements['atid']) && !isset($user_achievements['aaid']) && !isset($user_achievements['acid']))
		{
			// output error if the user does not have this achievement
			if (!isset($user_achievements[0]['acid'][$achievement['acid']]))
			{
				flash_message($lang->myachievements_user_no_acid, 'error');
				admin_redirect("index.php?module=myachievements-custom");
			}
			// revoke achievement
			unset($user_achievements[0]['acid'][$achievement['acid']]);
		}
		else {
			// output error if the user does not have this achievement
			if (!isset($user_achievements['acid'][$achievement['acid']]))
			{
				flash_message($lang->myachievements_user_no_acid, 'error');
				admin_redirect("index.php?module=myachievements-custom");
			}
			// revoke achievement
			unset($user_achievements['acid'][$achievement['acid']]);
		}

		$user_achievements = serialize($user_achievements);

		// update achievements list
		$db->update_query('users', array('myachievements' => $user_achievements), 'uid=\''.$user['uid'].'\'');

		// log that the user has been revoked an achievement
		myachievements_log('updated_via_custom', $user['uid'], $user['username'], 'Revoked achievement: '.$achievement['name']);

		// Rebuild this user's achievements and rank
		require_once MYBB_ROOT."inc/functions_task.php";
		ignore_user_abort(true);
		@set_time_limit(0);

		$db->update_query('tasks', array('lastrun' => '0'), 'file=\'myachievements\'');

		$query = $db->simple_select("tasks", "*", "file='myachievements'");
		$task = $db->fetch_array($query);

		// Does the task not exist?
		if(!$task['tid'])
		{
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=tools/tasks");
		}

		global $achievement_uid;
		$achievement_uid = $user['uid'];

		// Run task
		run_task($task['tid']);

		flash_message($lang->myachievements_achievement_revoked, 'success');
		admin_redirect("index.php?module=myachievements-custom");
	}
	else {
		$achievements_acid[0] = $lang->myachievements_select_achievement;

		// get all custom achievements
		$query = $db->simple_select('myachievements_custom');
		while ($achievement = $db->fetch_array($query))
		{
			$achievements_acid[$achievement['acid']] = htmlspecialchars_uni($achievement['name']);
		}
		$db->free_result($query);

		$form = new Form("index.php?module=myachievements-custom&amp;action=revoke", "post", "myachievements");

		$form_container = new FormContainer($lang->myachievements_revoke_achievement);

		$form_container->output_row($lang->myachievements_username."<em>*</em>", $lang->sprintf($lang->myachievements_username_desc, "achievement"), $form->generate_text_box('username', '', array('id' => 'username')), 'username');

		$form_container->output_row($lang->myachievements_achievements_acid, $lang->myachievements_achievements_acid_revoke_desc, $form->generate_select_box('acid', $achievements_acid, '0', array('id' => 'acid')), 'acid');

		$form_container->end();

		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->myachievements_submit);
		$buttons[] = $form->generate_reset_button($lang->myachievements_reset);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}

$page->output_footer();

exit;

?>
