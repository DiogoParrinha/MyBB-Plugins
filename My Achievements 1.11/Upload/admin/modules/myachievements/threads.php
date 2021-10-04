<?php
/***************************************************************************
 *
 *  My Achievements plugin (/admin/modules/myachievements-threads.php)
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

$page->add_breadcrumb_item($lang->myachievements_threads, 'index.php?module=myachievements-threads');

$page->output_header($lang->myachievements_threads);


$sub_tabs['myachievements_view'] = array(
	'title'			=> $lang->myachievements_view,
	'link'			=> 'index.php?module=myachievements-threads',
	'description'	=> $lang->sprintf($lang->myachievements_view_desc, "thread count achievements")
);

$sub_tabs['myachievements_add'] = array(
	'title'			=> $lang->myachievements_add,
	'link'			=> 'index.php?module=myachievements-threads&amp;action=add',
	'description'	=> $lang->sprintf($lang->myachievements_add_desc, "thread count achievements")
);

$sub_tabs['myachievements_edit'] = array(
	'title'			=> $lang->myachievements_edit,
	'link'			=> 'index.php?module=myachievements-threads&amp;action=edit',
	'description'	=> $lang->sprintf($lang->myachievements_edit_desc, "thread count achievements")
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

	$query = $db->simple_select("myachievements_numthreads", "COUNT(atid) as threads");
	$total_rows = $db->fetch_field($query, "threads");

	if ($total_rows > $per_page)
		echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=myachievements-threads&amp;page={page}");

	// table
	$table = new Table;
	$table->construct_header($lang->myachievements_icon, array('width' => '1%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_name, array('width' => '25%'));
	$table->construct_header($lang->myachievements_description, array('width' => '30%'));
	$table->construct_header($lang->myachievements_numthreads, array('width' => '15%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_options, array('width' => '20%', 'class' => 'align_center'));

	// fetch all ranks
	$query = $db->simple_select('myachievements_numthreads', '*', '', array('order_by' => 'numthreads', 'order_dir' => 'desc', 'limit' => "{$start}, {$per_page}"));
	while($achievement = $db->fetch_array($query)) {

		$achievement['image'] = htmlspecialchars_uni($achievement['image']);
		$achievement['name'] = htmlspecialchars_uni($achievement['name']);
		$achievement['description'] = nl2br(htmlspecialchars_uni($achievement['description']));
		$achievement['numthreads'] = intval($achievement['numthreads']);

		$table->construct_cell("<img src=\"".$mybb->settings['bburl']."/".$achievement['image']."\" />", array('class' => 'align_center'));
		$table->construct_cell($achievement['name']);
		$table->construct_cell($achievement['description']);
		$table->construct_cell($achievement['numthreads'], array('class' => 'align_center'));

		$table->construct_cell("<a href=\"index.php?module=myachievements-threads&amp;action=delete&amp;atid={$achievement['atid']}\" target=\"_self\">{$lang->myachievements_delete}</a> - <a href=\"index.php?module=myachievements-threads&amp;action=edit&amp;atid={$achievement['atid']}\" target=\"_self\">{$lang->myachievements_edit}</a>", array('class' => 'align_center'));

		$table->construct_row();
	}

	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->myachievements_no_data, array('colspan' => 5));

		$table->construct_row();
	}

	$table->output($lang->myachievements_threads);
}
elseif ($mybb->input['action'] == 'add') // Add entry
{
	if ($mybb->request_method == "post") // submit
	{
		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements/custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}

		if (empty($mybb->input['numthreads']))
		{
			flash_message($lang->myachievements_no_numthreads, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}


		$insert_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
			'numthreads' => intval($mybb->input['numthreads']),
		);

		$db->insert_query('myachievements_numthreads', $insert_array);

		flash_message($lang->myachievements_achievement_added, 'success');
		admin_redirect("index.php?module=myachievements-threads");
	}
	else {

		$form = new Form("index.php?module=myachievements-threads&amp;action=add", "post", "myachievements");

		$form_container = new FormContainer($lang->myachievements_add_achievement);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "achievement"), $form->generate_text_box('name', '', array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "achievement"), $form->generate_text_area('description', '', array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_numthreads."<em>*</em>", $lang->sprintf($lang->myachievements_numthreads_desc, "achievement"), $form->generate_text_box('numthreads', '', array('id' => 'numthreads')), 'numthreads');

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
		$query = $db->simple_select('myachievements_numthreads', '*', 'atid=\''.intval($mybb->input['atid']).'\'');
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}

		if (empty($mybb->input['name']))
		{
			flash_message($lang->myachievements_no_name, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}

		if (strpos($mybb->input['name'], "'") !== false || strpos($mybb->input['name'], '"') !== false)
		{
			flash_message($lang->myachievements_name_invalid_characters, 'error');
			admin_redirect("index.php?module=myachievements/custom");
		}

		if (empty($mybb->input['icon']))
		{
			flash_message($lang->myachievements_no_icon, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}

		if (empty($mybb->input['numthreads']))
		{
			flash_message($lang->myachievements_no_numthreads, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}


		$update_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'image' => $db->escape_string($mybb->input['icon']),
			'numthreads' => intval($mybb->input['numthreads']),
		);

		$db->update_query('myachievements_numthreads', $update_array, 'atid='.$achievement['atid']);

		flash_message($lang->myachievements_achievement_edited, 'success');
		admin_redirect("index.php?module=myachievements-threads");
	}
	else {

		$query = $db->simple_select('myachievements_numthreads', '*', 'atid=\''.intval($mybb->input['atid']).'\'');
		$achievement = $db->fetch_array($query);
		if (!$achievement)
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}

		$form = new Form("index.php?module=myachievements-threads&amp;action=edit", "post", "myachievements");

		echo $form->generate_hidden_field("atid", $achievement['atid']);

		$form_container = new FormContainer($lang->myachievements_add_achievement);

		$form_container->output_row($lang->myachievements_name."<em>*</em>", $lang->sprintf($lang->myachievements_name_desc, "achievement"), $form->generate_text_box('name', htmlspecialchars_uni($achievement['name']), array('id' => 'name')), 'name');

		$form_container->output_row($lang->myachievements_description, $lang->sprintf($lang->myachievements_description_desc, "achievement"), $form->generate_text_area('description', htmlspecialchars_uni($achievement['description']), array('id' => 'description')), 'description');

		$form_container->output_row($lang->myachievements_numthreads."<em>*</em>", $lang->sprintf($lang->myachievements_numthreads_desc, "achievement"), $form->generate_text_box('numthreads', intval($achievement['numthreads']), array('id' => 'numthreads')), 'numthreads');

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
		admin_redirect("index.php?module=myachievements-threads");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=myachievements-threads");
		}

		if (!$db->fetch_field($db->simple_select('myachievements_numthreads', 'name', 'atid='.intval($mybb->input['atid']), array('limit' => 1)), 'name'))
		{
			flash_message($lang->myachievements_achievement_invalid, 'error');
			admin_redirect('index.php?module=myachievements-threads');
		}
		else {
			$db->delete_query('myachievements_numthreads', 'atid='.intval($mybb->input['atid']));
			flash_message($lang->myachievements_achievement_deleted, 'success');
			admin_redirect('index.php?module=myachievements-threads');
		}
	}
	else
	{
		$mybb->input['atid'] = intval($mybb->input['atid']);
		$form = new Form("index.php?module=myachievements-threads&amp;action=delete&amp;atid={$mybb->input['atid']}&amp;my_post_key={$mybb->post_code}", 'post');
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
