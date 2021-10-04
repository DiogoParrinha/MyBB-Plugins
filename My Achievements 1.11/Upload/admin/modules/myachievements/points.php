<?php
/***************************************************************************
 *
 *  My Achievements plugin (/admin/modules/myachievements-points.php)
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

$page->add_breadcrumb_item($lang->myachievements_points, 'index.php?module=myachievements-points');

$page->output_header($lang->myachievements_points);


$sub_tabs['myachievements_view'] = array(
	'title'			=> $lang->myachievements_view,
	'link'			=> 'index.php?module=myachievements-points',
	'description'	=> $lang->sprintf($lang->myachievements_view_desc, "points amount achievements")
);

$sub_tabs['myachievements_add'] = array(
	'title'			=> $lang->myachievements_add,
	'link'			=> 'index.php?module=myachievements-points&amp;action=add',
	'description'	=> $lang->sprintf($lang->myachievements_add_desc, "points amount achievements")
);

$sub_tabs['myachievements_edit'] = array(
	'title'			=> $lang->myachievements_edit,
	'link'			=> 'index.php?module=myachievements-points&amp;action=edit',
	'description'	=> $lang->sprintf($lang->myachievements_edit_desc, "points amount achievements")
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

	if ($mybb->settings['myachievements_points_enabled'] == 0)
		echo "<p class=\"notice\">{$lang->newpoints_points_disabled}</p>";
	else {
		// check if NewPoints is installed
		$plugins_cache = $cache->read("plugins");
		if(!isset($plugins_cache['active']['newpoints']))
			echo "<p class=\"notice\">{$lang->newpoints_newpoints_not_installed}</p>";
	}

	$query = $db->simple_select("myachievements_points", "COUNT(apoid) as points");
	$total_rows = $db->fetch_field($query, "points");

	if ($total_rows > $per_page)
		echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=myachievements-points&amp;page={page}");

	// table
	$table = new Table;
	$table->construct_header($lang->myachievements_icon, array('width' => '1%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_name, array('width' => '25%'));
	$table->construct_header($lang->myachievements_description, array('width' => '30%'));
	$table->construct_header($lang->myachievements_numpoints, array('width' => '15%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_options, array('width' => '20%', 'class' => 'align_center'));

	// fetch all ranks
	$query = $db->simple_select('myachievements_points', '*', '', array('order_by' => 'points', 'order_dir' => 'desc', 'limit' => "{$start}, {$per_page}"));
	while($achievement = $db->fetch_array($query)) {

		$achievement['image'] = htmlspecialchars_uni($achievement['image']);
		$achievement['name'] = htmlspecialchars_uni($achievement['name']);
		$achievement['description'] = nl2br(htmlspecialchars_uni($achievement['description']));
		$achievement['points'] = intval($achievement['points']);

		$table->construct_cell("<img src=\"".$mybb->settings['bburl']."/".$achievement['image']."\" />", array('class' => 'align_center'));
		$table->construct_cell($achievement['name']);
		$table->construct_cell($achievement['description']);
		$table->construct_cell($achievement['points'], array('class' => 'align_center'));

		$table->construct_cell("<a href=\"index.php?module=myachievements-points&amp;action=delete&amp;apoid={$achievement['apoid']}\" target=\"_self\">{$lang->myachievements_delete}</a> - <a href=\"index.php?module=myachievements-points&amp;action=edit&amp;apoid={$achievement['apoid']}\" target=\"_self\">{$lang->myachievements_edit}</a>", array('class' => 'align_center'));

		$table->construct_row();
	}

	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->myachievements_no_data, array('colspan' => 5));

		$table->construct_row();
	}

	$table->output($lang->myachievements_points);
}
elseif ($mybb->input['action'] == 'add') // Add entry
{
	if ($mybb->request_method == "post") // submit
	{
		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements/custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}

		if (empty($mybb->input['points']))
		{
			flash_message($lang->myachievements_no_points, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}


		$insert_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
			'points' => intval($mybb->input['points']),
		);

		$db->insert_query('myachievements_points', $insert_array);

		flash_message($lang->myachievements_achievement_added, 'success');
		admin_redirect("index.php?module=myachievements-points");
	}
	else {

		$form = new Form("index.php?module=myachievements-points&amp;action=add", "post", "myachievements");

		$form_container = new FormContainer($lang->myachievements_add_achievement);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "achievement"), $form->generate_text_box('name', '', array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "achievement"), $form->generate_text_area('description', '', array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_numpoints."<em>*</em>", $lang->sprintf($lang->myachievements_numpoints_desc, "achievement"), $form->generate_text_box('points', '', array('id' => 'points')), 'points');

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
		$query = $db->simple_select('myachievements_points', '*', 'apoid=\''.intval($mybb->input['apoid']).'\'');
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}

		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements/custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}

		if (empty($mybb->input['points']))
		{
			flash_message($lang->myachievements_no_points, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}


		$update_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
			'points' => intval($mybb->input['points']),
		);

		$db->update_query('myachievements_points', $update_array, 'apoid='.$achievement['apoid']);

		flash_message($lang->myachievements_achievement_edited, 'success');
		admin_redirect("index.php?module=myachievements-points");
	}
	else {

		$query = $db->simple_select('myachievements_points', '*', 'apoid=\''.intval($mybb->input['apoid']).'\'');
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}

		$form = new Form("index.php?module=myachievements-points&amp;action=edit", "post", "myachievements");

		echo $form->generate_hidden_field("apoid", $achievement['apoid']);

		$form_container = new FormContainer($lang->myachievements_add_achievement);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "achievement"), $form->generate_text_box('name', htmlspecialchars_uni($achievement['name']), array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "achievement"), $form->generate_text_area('description', htmlspecialchars_uni($achievement['description']), array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_numpoints."<em>*</em>", $lang->sprintf($lang->myachievements_numpoints_desc, "achievement"), $form->generate_text_box('points', intval($achievement['points']), array('id' => 'points')), 'points');

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
		admin_redirect("index.php?module=myachievements-points");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=myachievements-points");
		}

		if (!$db->fetch_field($db->simple_select('myachievements_points', 'name', 'apoid='.intval($mybb->input['apoid']), array('limit' => 1)), 'name'))
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect('index.php?module=myachievements-points');
		}
		else {
			$db->delete_query('myachievements_points', 'apoid='.intval($mybb->input['apoid']));
			flash_message($lang->myachievements_achievement_deleted, 'success');
			admin_redirect('index.php?module=myachievements-points');
		}
	}
	else
	{
		$mybb->input['apoid'] = intval($mybb->input['apoid']);
		$form = new Form("index.php?module=myachievements-points&amp;action=delete&amp;apoid={$mybb->input['apoid']}&amp;my_post_key={$mybb->post_code}", 'post');
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

$page->output_footer();

exit;

?>
