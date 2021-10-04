<?php
/***************************************************************************
 *
 *  My Achievements plugin (/admin/modules/myachievements-log.php)
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

if (!$mybb->input['action']) // view logs
{
	$page->add_breadcrumb_item($lang->myachievements_log, 'index.php?module=myachievements-log');

	$page->output_header($lang->myachievements_log);

	$sub_tabs['myachievements_log'] = array(
		'title'			=> $lang->myachievements_log,
		'link'			=> 'index.php?module=myachievements-log',
		'description'	=> $lang->myachievements_log_description
	);

	$page->output_nav_tabs($sub_tabs, 'myachievements_log');

	$per_page = 10;
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

	// Filters
	if($mybb->input['filter'] != '')
	{
		echo "<p class=\"notice\">";

		$sql_filter = 'type=\''.$db->escape_string($mybb->input['filter']).'\'';

		$filter = htmlspecialchars_uni($mybb->input['filter']);

		$filter_url = '&amp;filter='.filter;

		echo "{$lang->newpoints_current_filter}: {$filter} - {$lang->newpoints_no_filters}</p>";
	}
	else
	{
		$sql_filter = '';
		$filter_url = '';
	}

	$query = $db->simple_select("myachievements_log", "COUNT(lid) as log_entries", $sql_filter);
	$total_rows = $db->fetch_field($query, "log_entries");
	if ($total_rows > $per_page)
		echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=myachievements-log&amp;page={page}".$filter_url);

	// table
	$table = new Table;
	$table->construct_header($lang->myachievements_log_type, array('width' => '15%'));
	$table->construct_header($lang->myachievements_log_data, array('width' => '30%'));
	$table->construct_header($lang->myachievements_log_user, array('width' => '20%'));
	$table->construct_header($lang->myachievements_log_date, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->myachievements_log_options, array('width' => '15%', 'class' => 'align_center'));

	$query = $db->simple_select('myachievements_log', '*', $sql_filter, array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => "{$start}, {$per_page}"));
	while($log = $db->fetch_array($query)) {

		$table->construct_cell('<a href="index.php?module=myachievements-log&amp;page='.$mybb->input['page'].'&amp;filter='.htmlspecialchars_uni($log['type']).'">'.htmlspecialchars_uni($log['type']).'</a>');
		$table->construct_cell(htmlspecialchars_uni($log['data']));
		$link = build_profile_link(htmlspecialchars_uni($log['username']), intval($log['uid']));
		$table->construct_cell($link);
		$table->construct_cell(my_date($mybb->settings['dateformat'], intval($log['date']), '', false).", ".my_date($mybb->settings['timeformat'], intval($log['date'])), array('class' => 'align_center'));
		$table->construct_cell("<a href=\"index.php?module=myachievements-log&amp;action=delete_log&amp;lid={$log['lid']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">{$lang->myachievements_delete}</a>", array('class' => 'align_center')); // delete button

		$table->construct_row();
	}

	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->myachievements_no_log_entries, array('colspan' => 5));
		$table->construct_row();
	}

	$table->output($lang->myachievements_log_entries);

	echo "<br />";

	$form = new Form("index.php?module=myachievements-log&amp;action=prune", "post", "myachievements");

	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

	$form_container = new FormContainer($lang->myachievements_log_prune);
	$form_container->output_row($lang->myachievements_older_than, $lang->myachievements_older_than_desc, $form->generate_text_box('days', 30, array('id' => 'days')), 'days');
	$form_container->end();

	$buttons = array();;
	$buttons[] = $form->generate_submit_button($lang->myachievements_submit);
	$buttons[] = $form->generate_reset_button($lang->myachievements_reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif ($mybb->input['action'] == 'delete_log')
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=myachievements-log");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=myachievements-log");
		}

		if (!$db->fetch_field($db->simple_select('myachievements_log', 'type', 'lid='.intval($mybb->input['lid']), array('limit' => 1)), 'type'))
		{
			flash_message($lang->myachievements_log_invalid, 'error');
			admin_redirect('index.php?module=myachievements-log');
		}
		else {
			$db->delete_query('myachievements_log', 'lid='.intval($mybb->input['lid']));
			flash_message($lang->myachievements_log_deleted, 'success');
			admin_redirect('index.php?module=myachievements-log');
		}
	}
	else
	{
		$page->add_breadcrumb_item($lang->myachievements_log, 'index.php?module=myachievements-log');

		$page->output_header($lang->myachievements_log);

		$mybb->input['lid'] = intval($mybb->input['lid']);
		$form = new Form("index.php?module=myachievements-log&amp;action=delete_log&amp;lid={$mybb->input['lid']}&amp;my_post_key={$mybb->post_code}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->myachievements_log_deleteconfirm}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
		echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
		echo "</p>\n";
		echo "</div>\n";
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'prune')
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=myachievements-log");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->myachievements_error, 'error');
			admin_redirect("index.php?module=myachievements-log");
		}

		$db->delete_query('myachievements_log', 'date < '.(TIME_NOW - intval($mybb->input['days'])*60*60*24));
		flash_message($lang->myachievements_log_pruned, 'success');
		admin_redirect('index.php?module=myachievements-log');
	}
	else
	{
		$page->add_breadcrumb_item($lang->myachievements_log, 'index.php?module=myachievements-log');

		$page->output_header($lang->myachievements_log);

		$mybb->input['days'] = intval($mybb->input['days']);
		$form = new Form("index.php?module=myachievements-log&amp;action=prune&amp;days={$mybb->input['days']}&amp;my_post_key={$mybb->post_code}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->myachievements_log_pruneconfirm}</p>\n";
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
