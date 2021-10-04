<?php
/***************************************************************************
 *
 *  My Achievements plugin (/admin/modules/myachievements-activity.php)
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

$page->add_breadcrumb_item($lang->myachievements_activity, 'index.php?module=myachievements-activity');

$page->output_header($lang->myachievements_activity);


$sub_tabs['myachievements_view'] = array(
	'title'			=> $lang->myachievements_view,
	'link'			=> 'index.php?module=myachievements-activity',
	'description'	=> $lang->sprintf($lang->myachievements_view_desc, "activity achievements")
);

$sub_tabs['myachievements_add'] = array(
	'title'			=> $lang->myachievements_add,
	'link'			=> 'index.php?module=myachievements-activity&amp;action=add',
	'description'	=> $lang->sprintf($lang->myachievements_add_desc, "activity achievements")
);

$sub_tabs['myachievements_edit'] = array(
	'title'			=> $lang->myachievements_edit,
	'link'			=> 'index.php?module=myachievements-activity&amp;action=edit',
	'description'	=> $lang->sprintf($lang->myachievements_edit_desc, "activity achievements")
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

	$query = $db->simple_select("myachievements_activity", "COUNT(aaid) as activity");
	$total_rows = $db->fetch_field($query, "activity");

	if ($total_rows > $per_page)
		echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=myachievements-activity&amp;page={page}");

	// table
	$table = new Table;
	$table->construct_header($lang->myachievements_icon, array('width' => '1%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_name, array('width' => '25%'));
	$table->construct_header($lang->myachievements_description, array('width' => '30%'));
	$table->construct_header($lang->myachievements_timespent, array('width' => '15%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_options, array('width' => '20%', 'class' => 'align_center'));

	// fetch all ranks
	$query = $db->simple_select('myachievements_activity', '*', '', array('order_by' => 'aaid', 'order_dir' => 'asc', 'limit' => "{$start}, {$per_page}"));
	while($achievement = $db->fetch_array($query)) {

		$achievement['image'] = htmlspecialchars_uni($achievement['image']);
		$achievement['name'] = htmlspecialchars_uni($achievement['name']);
		$achievement['description'] = nl2br(htmlspecialchars_uni($achievement['description']));

		$years = $months = $days = "";

		if (intval($achievement['years']) > 0)
		{
			if (intval($achievement['years']) == 1)
				$years = "1 ".$lang->myachievements_year;
			else
				$years = intval($achievement['years'])." ".$lang->myachievements_years;
		}

		if (intval($achievement['months']) > 0)
		{
			if (intval($achievement['months']) == 1)
				$months = "1 ".$lang->myachievements_month;
			else
				$months = intval($achievement['months'])." ".$lang->myachievements_months;

			if ($years)
				$months = ", ".$months;
		}

		if (intval($achievement['days']) > 0)
		{
			if (intval($achievement['days']) == 1)
				$days = "1 ".$lang->myachievements_day;
			else
				$days = intval($achievement['days'])." ".$lang->myachievements_days;

			if ($years || $months)
				$days = ", ".$days;
		}

		$achievement['time'] = $years.$months.$days;

		$table->construct_cell("<img src=\"".$mybb->settings['bburl']."/".$achievement['image']."\" />", array('class' => 'align_center'));
		$table->construct_cell($achievement['name']);
		$table->construct_cell($achievement['description']);
		$table->construct_cell($achievement['time'], array('class' => 'align_center'));

		$table->construct_cell("<a href=\"index.php?module=myachievements-activity&amp;action=delete&amp;aaid={$achievement['aaid']}\" target=\"_self\">{$lang->myachievements_delete}</a> - <a href=\"index.php?module=myachievements-activity&amp;action=edit&amp;aaid={$achievement['aaid']}\" target=\"_self\">{$lang->myachievements_edit}</a>", array('class' => 'align_center'));

		$table->construct_row();
	}

	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->myachievements_no_data, array('colspan' => 5));

		$table->construct_row();
	}

	$table->output($lang->myachievements_activity);
}
elseif ($mybb->input['action'] == 'add') // Add entry
{
	if ($mybb->request_method == "post") // submit
	{
		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements/custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		$years = intval($mybb->input['years']);
		$months = intval($mybb->input['months']);
		$days = intval($mybb->input['days']);

		// convert time to seconds
		$time = ($years * 31556952) + ($months * 2551443) + ($days * 86400);

		if ($time <= 0)
		{
			flash_message($lang->myachievements_no_time, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		$insert_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
			'years' => intval($mybb->input['years']),
			'months' => intval($mybb->input['months']),
			'days' => intval($mybb->input['days']),
			'time' => $time
		);

		$db->insert_query('myachievements_activity', $insert_array);

		flash_message($lang->myachievements_achievement_added, 'success');
		admin_redirect("index.php?module=myachievements-activity");
	}
	else {

		$form = new Form("index.php?module=myachievements-activity&amp;action=add", "post", "myachievements");

		$form_container = new FormContainer($lang->myachievements_add_achievement);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "achievement"), $form->generate_text_box('name', '', array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "achievement"), $form->generate_text_area('description', '', array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_years."<em>*</em>", $lang->sprintf($lang->myachievements_years_desc, "achievement"), $form->generate_text_box('years', '', array('id' => 'years')), 'years');

		$form_container->output_row($lang->myachievements_months."<em>*</em>", $lang->sprintf($lang->myachievements_months_desc, "achievement"), $form->generate_text_box('months', '', array('id' => 'months')), 'months');

		$form_container->output_row($lang->myachievements_days."<em>*</em>", $lang->sprintf($lang->myachievements_days_desc, "achievement"), $form->generate_text_box('days', '', array('id' => 'days')), 'days');

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
		$query = $db->simple_select('myachievements_activity', '*', 'aaid=\''.intval($mybb->input['aaid']).'\'');
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements/custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		$years = intval($mybb->input['years']);
		$months = intval($mybb->input['months']);
		$days = intval($mybb->input['days']);

		// convert time to seconds
		$time = ($years * 31556952) + ($months * 2551443) + ($days * 86400);

		if ($time <= 0)
		{
			flash_message($lang->myachievements_no_time, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		$update_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
			'years' => intval($mybb->input['years']),
			'months' => intval($mybb->input['months']),
			'days' => intval($mybb->input['days']),
			'time' => $time
		);

		$db->update_query('myachievements_activity', $update_array, 'aaid='.$achievement['aaid']);

		flash_message($lang->myachievements_achievement_edited, 'success');
		admin_redirect("index.php?module=myachievements-activity");
	}
	else {

		$query = $db->simple_select('myachievements_activity', '*', 'aaid=\''.intval($mybb->input['aaid']).'\'');
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		$form = new Form("index.php?module=myachievements-activity&amp;action=edit", "post", "myachievements");

		echo $form->generate_hidden_field("aaid", $achievement['aaid']);

		$form_container = new FormContainer($lang->myachievements_edit_achievement);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "achievement"), $form->generate_text_box('name', htmlspecialchars_uni($achievement['name']), array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "achievement"), $form->generate_text_area('description', htmlspecialchars_uni($achievement['description']), array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_years."<em>*</em>", $lang->sprintf($lang->myachievements_years_desc, "achievement"), $form->generate_text_box('years', intval($achievement['years']), array('id' => 'years')), 'years');

		$form_container->output_row($lang->myachievements_months."<em>*</em>", $lang->sprintf($lang->myachievements_months_desc, "achievement"), $form->generate_text_box('months', intval($achievement['months']), array('id' => 'months')), 'months');

		$form_container->output_row($lang->myachievements_days."<em>*</em>", $lang->sprintf($lang->myachievements_days_desc, "achievement"), $form->generate_text_box('days', intval($achievement['days']), array('id' => 'days')), 'days');

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
		admin_redirect("index.php?module=myachievements-activity");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=myachievements-activity");
		}

		if (!$db->fetch_field($db->simple_select('myachievements_activity', 'name', 'aaid='.intval($mybb->input['aaid']), array('limit' => 1)), 'name'))
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect('index.php?module=myachievements-activity');
		}
		else {
			$db->delete_query('myachievements_activity', 'aaid='.intval($mybb->input['aaid']));
			flash_message($lang->myachievements_achievement_deleted, 'success');
			admin_redirect('index.php?module=myachievements-activity');
		}
	}
	else
	{
		$mybb->input['aaid'] = intval($mybb->input['aaid']);
		$form = new Form("index.php?module=myachievements-activity&amp;action=delete&amp;aaid={$mybb->input['aaid']}&amp;my_post_key={$mybb->post_code}", 'post');
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
