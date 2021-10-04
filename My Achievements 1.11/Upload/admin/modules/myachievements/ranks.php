<?php
/***************************************************************************
 *
 *  My Achievements plugin (/admin/modules/myachievements-ranks.php)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
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

$page->add_breadcrumb_item($lang->myachievements_ranks, 'index.php?module=myachievements-ranks');

$page->output_header($lang->myachievements_ranks);


$sub_tabs['myachievements_view'] = array(
	'title'			=> $lang->myachievements_view,
	'link'			=> 'index.php?module=myachievements-ranks',
	'description'	=> $lang->sprintf($lang->myachievements_view_desc, "ranks")
);

$sub_tabs['myachievements_add'] = array(
	'title'			=> $lang->myachievements_add,
	'link'			=> 'index.php?module=myachievements-ranks&amp;action=add',
	'description'	=> $lang->sprintf($lang->myachievements_add_desc, "ranks")
);

$sub_tabs['myachievements_edit'] = array(
	'title'			=> $lang->myachievements_edit,
	'link'			=> 'index.php?module=myachievements-ranks&amp;action=edit',
	'description'	=> $lang->sprintf($lang->myachievements_edit_desc, "ranks")
);

switch ($mybb->input['action'])
{
	case 'add':
		$page->output_nav_tabs($sub_tabs, 'myachievements_add');
	break;
	case 'edit':
		$page->output_nav_tabs($sub_tabs, 'myachievements_edit');
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

	$query = $db->simple_select("myachievements_ranks", "COUNT(rid) as ranks");
	$total_rows = $db->fetch_field($query, "ranks");

	if ($total_rows > $per_page)
		echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=myachievements-ranks&amp;page={page}");

	// table
	$table = new Table;
	$table->construct_header($lang->myachievements_icon, array('width' => '1%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_name, array('width' => '25%'));
	$table->construct_header($lang->myachievements_description, array('width' => '30%'));
	$table->construct_header($lang->myachievements_level, array('width' => '15%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_options, array('width' => '20%', 'class' => 'align_center'));

	// fetch all ranks
	$query = $db->simple_select('myachievements_ranks', '*', '', array('order_by' => 'level', 'order_dir' => 'desc', 'limit' => "{$start}, {$per_page}"));
	while($rank = $db->fetch_array($query)) {

		$rank['image'] = htmlspecialchars_uni($rank['image']);
		$rank['name'] = htmlspecialchars_uni($rank['name']);
		$rank['description'] = nl2br(htmlspecialchars_uni($rank['description']));
		$rank['level'] = intval($rank['level']);

		$table->construct_cell("<img src=\"".$mybb->settings['bburl']."/".$rank['image']."\" />", array('class' => 'align_center'));
		$table->construct_cell($rank['name']);
		$table->construct_cell($rank['description']);
		$table->construct_cell($rank['level'], array('class' => 'align_center'));

		$table->construct_cell("<a href=\"index.php?module=myachievements-ranks&amp;action=delete&amp;rid={$rank['rid']}\" target=\"_self\">{$lang->myachievements_delete}</a> - <a href=\"index.php?module=myachievements-ranks&amp;action=edit&amp;rid={$rank['rid']}\" target=\"_self\">{$lang->myachievements_edit}</a>", array('class' => 'align_center'));

		$table->construct_row();
	}

	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->myachievements_no_data, array('colspan' => 5));

		$table->construct_row();
	}

	$table->output($lang->myachievements_ranks);
}
elseif ($mybb->input['action'] == 'add') // Add entry
{
	if ($mybb->request_method == "post") // submit
	{
		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements/custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}

		if (intval($mybb->input['level']) < 0)
		{
			flash_message($lang->myachievements_no_level, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}


		$insert_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
			'achievements_apid' => intval($mybb->input['achievements_apid']),
			'achievements_atid' => intval($mybb->input['achievements_atid']),
			'achievements_aaid' => intval($mybb->input['achievements_aaid']),
			'achievements_acid' => intval($mybb->input['achievements_acid']),
			'achievements_apoid' => intval($mybb->input['achievements_apoid']),
			'level' => intval($mybb->input['level']),
		);

		$db->insert_query('myachievements_ranks', $insert_array);

		flash_message($lang->myachievements_rank_added, 'success');
		admin_redirect("index.php?module=myachievements-ranks");
	}
	else {

		$achievements_apid[0] = $lang->myachievements_select_achievement;
		$achievements_atid[0] = $lang->myachievements_select_achievement;
		$achievements_aaid[0] = $lang->myachievements_select_achievement;
		$achievements_acid[0] = $lang->myachievements_select_achievement;
		$achievements_apoid[0] = $lang->myachievements_select_achievement;

		$types = array('numposts', 'numthreads', 'activity', 'custom', 'points');

		// query all types of achievements
		foreach ($types as $type)
		{
			if ($type == 'points' && $mybb->settings['myachievements_points_enabled'] != 1)
				continue;

			$query = $db->simple_select('myachievements_'.$type);
			while ($achievement = $db->fetch_array($query))
			{
				switch ($type)
				{
					case 'numposts':
						$achievements_apid[$achievement['apid']] = htmlspecialchars_uni($achievement['name']);
					break;

					case 'numthreads':
						$achievements_atid[$achievement['atid']] = htmlspecialchars_uni($achievement['name']);
					break;

					case 'activity':
						$achievements_aaid[$achievement['aaid']] = htmlspecialchars_uni($achievement['name']);
					break;

					case 'custom':
						$achievements_acid[$achievement['acid']] = htmlspecialchars_uni($achievement['name']);
					break;

					case 'points':
						$achievements_apoid[$achievement['apoid']] = htmlspecialchars_uni($achievement['name']);
					break;
				}
			}
			$db->free_result($query);
		}

		$form = new Form("index.php?module=myachievements-ranks&amp;action=add", "post", "myachievements");

		$form_container = new FormContainer($lang->myachievements_add_rank);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "rank"), $form->generate_text_box('name', '', array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "rank"), $form->generate_text_area('description', '', array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_level."<em>*</em>", $lang->sprintf($lang->myachievements_level_desc, "rank"), $form->generate_text_box('level', '', array('id' => 'level')), 'level');

		$form_container->output_row($lang->myachievements_icon."<em>*</em>", $lang->sprintf($lang->myachievements_icon_desc, "rank"), $form->generate_text_box('icon', '', array('id' => 'icon')), 'icon');

		$form_container->output_row($lang->myachievements_achievements_apid, $lang->myachievements_achievements_apid_desc, $form->generate_select_box('achievements_apid', $achievements_apid, 0, array('id' => 'achievements_apid')), 'achievements_apid');

		$form_container->output_row($lang->myachievements_achievements_atid, $lang->myachievements_achievements_atid_desc, $form->generate_select_box('achievements_atid', $achievements_atid, 0, array('id' => 'achievements_atid')), 'achievements_atid');

		$form_container->output_row($lang->myachievements_achievements_aaid, $lang->myachievements_achievements_aaid_desc, $form->generate_select_box('achievements_aaid', $achievements_aaid, 0, array('id' => 'achievements_aaid')), 'achievements_aaid');

		$form_container->output_row($lang->myachievements_achievements_acid, $lang->myachievements_achievements_acid_desc, $form->generate_select_box('achievements_acid', $achievements_acid, 0, array('id' => 'achievements_acid')), 'achievements_acid');

		$form_container->output_row($lang->myachievements_achievements_apoid, $lang->myachievements_achievements_apoid_desc, $form->generate_select_box('achievements_apoid', $achievements_apoid, 0, array('id' => 'achievements_apoid')), 'achievements_apoid');

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
		$query = $db->simple_select('myachievements_ranks', '*', 'rid=\''.intval($mybb->input['rid']).'\'');
		$rank = $db->fetch_array($query);
		if (!$rank)
		{
			flash_message($lang->myachievements_rank_invalid, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}

		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements/custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}

		if (intval($mybb->input['level']) < 0)
		{
			flash_message($lang->myachievements_no_level, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}


		$update_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
			'achievements_apid' => intval($mybb->input['achievements_apid']),
			'achievements_atid' => intval($mybb->input['achievements_atid']),
			'achievements_aaid' => intval($mybb->input['achievements_aaid']),
			'achievements_acid' => intval($mybb->input['achievements_acid']),
			'achievements_apoid' => intval($mybb->input['achievements_apoid']),
			'level' => intval($mybb->input['level']),
		);

		$db->update_query('myachievements_ranks', $update_array, 'rid='.$rank['rid']);

		flash_message($lang->myachievements_rank_edited, 'success');
		admin_redirect("index.php?module=myachievements-ranks");
	}
	else {

		$achievements_apid[0] = $lang->myachievements_select_achievement;
		$achievements_atid[0] = $lang->myachievements_select_achievement;
		$achievements_aaid[0] = $lang->myachievements_select_achievement;
		$achievements_acid[0] = $lang->myachievements_select_achievement;
		$achievements_apoid[0] = $lang->myachievements_select_achievement;

		$types = array('numposts', 'numthreads', 'activity', 'custom', 'points');

		// query all types of achievements
		foreach ($types as $type)
		{
			if ($type == 'points' && $mybb->settings['myachievements_points_enabled'] != 1)
				continue;

			$query = $db->simple_select('myachievements_'.$type);
			while ($achievement = $db->fetch_array($query))
			{
				switch ($type)
				{
					case 'numposts':
						$achievements_apid[$achievement['apid']] = htmlspecialchars_uni($achievement['name']);
					break;

					case 'numthreads':
						$achievements_atid[$achievement['atid']] = htmlspecialchars_uni($achievement['name']);
					break;

					case 'activity':
						$achievements_aaid[$achievement['aaid']] = htmlspecialchars_uni($achievement['name']);
					break;

					case 'custom':
						$achievements_acid[$achievement['acid']] = htmlspecialchars_uni($achievement['name']);
					break;

					case 'points':
						$achievements_apoid[$achievement['apoid']] = htmlspecialchars_uni($achievement['name']);
					break;
				}
			}
			$db->free_result($query);
		}

		$query = $db->simple_select('myachievements_ranks', '*', 'rid=\''.intval($mybb->input['rid']).'\'');
		$rank = $db->fetch_array($query);
		if (!$rank)
		{
			flash_message($lang->myachievements_rank_invalid, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}

		$form = new Form("index.php?module=myachievements-ranks&amp;action=edit", "post", "myachievements");

		echo $form->generate_hidden_field("rid", $rank['rid']);

		$form_container = new FormContainer($lang->myachievements_edit_rank);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "rank"), $form->generate_text_box('name', htmlspecialchars_uni($rank['name']), array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "rank"), $form->generate_text_area('description', htmlspecialchars_uni($rank['description']), array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_level."<em>*</em>", $lang->sprintf($lang->myachievements_level_desc, "rank"), $form->generate_text_box('level', intval($rank['level']), array('id' => 'level')), 'level');

		$form_container->output_row($lang->myachievements_icon."<em>*</em>", $lang->sprintf($lang->myachievements_icon_desc, "rank"), $form->generate_text_box('icon', htmlspecialchars_uni($rank['image']), array('id' => 'icon')), 'icon');

		$form_container->output_row($lang->myachievements_achievements_apid, $lang->myachievements_achievements_apid_desc, $form->generate_select_box('achievements_apid', $achievements_apid, intval($rank['achievements_apid']), array('id' => 'achievements_apid')), 'achievements_apid');

		$form_container->output_row($lang->myachievements_achievements_atid, $lang->myachievements_achievements_atid_desc, $form->generate_select_box('achievements_atid', $achievements_atid, intval($rank['achievements_atid']), array('id' => 'achievements_atid')), 'achievements_atid');

		$form_container->output_row($lang->myachievements_achievements_aaid, $lang->myachievements_achievements_aaid_desc, $form->generate_select_box('achievements_aaid', $achievements_aaid, intval($rank['achievements_aaid']), array('id' => 'achievements_aaid')), 'achievements_aaid');

		$form_container->output_row($lang->myachievements_achievements_acid, $lang->myachievements_achievements_acid_desc, $form->generate_select_box('achievements_acid', $achievements_acid, intval($rank['achievements_acid']), array('id' => 'achievements_acid')), 'achievements_acid');

		$form_container->output_row($lang->myachievements_achievements_apoid, $lang->myachievements_achievements_apoid_desc, $form->generate_select_box('achievements_apoid', $achievements_apoid, intval($rank['achievements_apoid']), array('id' => 'achievements_apoid')), 'achievements_apoid');

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
		admin_redirect("index.php?module=myachievements-ranks");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=myachievements-ranks");
		}

		if (!$db->fetch_field($db->simple_select('myachievements_ranks', 'name', 'rid='.intval($mybb->input['rid']), array('limit' => 1)), 'name'))
		{
			flash_message($lang->myachievements_rank_invalid, 'error');
			admin_redirect('index.php?module=myachievements-ranks');
		}
		else {
			$db->delete_query('myachievements_ranks', 'rid='.intval($mybb->input['rid']));
			flash_message($lang->myachievements_rank_deleted, 'success');
			admin_redirect('index.php?module=myachievements-ranks');
		}
	}
	else
	{
		$mybb->input['rid'] = intval($mybb->input['rid']);
		$form = new Form("index.php?module=myachievements-ranks&amp;action=delete&amp;rid={$mybb->input['rid']}&amp;my_post_key={$mybb->post_code}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->myachievements_ranks_deleteconfirm}</p>\n";
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

exit;

?>
