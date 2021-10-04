<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/admin/modules/mydownloads/log.php) - MyDownloads MyPlaza Turbo module development has been abandoned.
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$lang->load('mydownloads');

$page->add_breadcrumb_item($lang->mydownloads_log, 'index.php?module=mydownloads-log');

$page->output_header($lang->mydownloads_log);

$sub_tabs['mydownloads_log'] = array(
	'title'			=> $lang->mydownloads_log,
	'link'			=> 'index.php?module=mydownloads-log',
	'description'	=> $lang->mydownloads_log_description
);

$page->output_nav_tabs($sub_tabs, 'mydownloads_log');
if (!$mybb->input['action']) // view logs
{
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

	$query = $db->simple_select("mydownloads_log", "COUNT(lid) as log_entries");
	$total_rows = $db->fetch_field($query, "log_entries");

	echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=mydownloads-log&amp;page={page}");

	// table
	$table = new Table;
	$table->construct_header($lang->mydownloads_log_action, array('width' => '60%'));
	$table->construct_header($lang->mydownloads_log_date, array('width' => '20%'));
	$table->construct_header($lang->mydownloads_admin_options, array('width' => '20%'));

	$query = $db->query("
		SELECT d.*, l.*
		FROM ".TABLE_PREFIX."mydownloads_log l
		LEFT JOIN ".TABLE_PREFIX."mydownloads_downloads d ON (d.did=l.did)
		ORDER BY l.date DESC LIMIT {$start}, {$per_page}
	");
	while($r = $db->fetch_array($query)) {

		switch($r['type'])
		{
			case 1: // download purchased
				$table->construct_cell($lang->sprintf($lang->mydownloads_log_download_purchased, $mybb->settings['bburl'], $r['uid'], htmlspecialchars_uni($r['username']), $r['did'], htmlspecialchars_uni($r['name'])));
			break;

			case 2: // download rated
				$table->construct_cell($lang->sprintf($lang->mydownloads_log_download_rated, $mybb->settings['bburl'], $r['uid'], htmlspecialchars_uni($r['username']), $r['did'], htmlspecialchars_uni($r['name']), $r['rating']));
			break;

			case 3: // download commented
				$table->construct_cell($lang->sprintf($lang->mydownloads_log_download_commented, $mybb->settings['bburl'], $r['uid'], htmlspecialchars_uni($r['username']), $r['did'], htmlspecialchars_uni($r['name'])));
			break;

			case 4: // comment deleted
				$table->construct_cell($lang->sprintf($lang->mydownloads_log_download_comment_deleted, $mybb->settings['bburl'], $r['uid'], htmlspecialchars_uni($r['username']), $r['did'], htmlspecialchars_uni($r['name'])));
			break;

			case 5: // download purchased
				$table->construct_cell($lang->sprintf($lang->mydownloads_log_download_downloaded, $mybb->settings['bburl'], $r['uid'], htmlspecialchars_uni($r['username']), $r['did'], htmlspecialchars_uni($r['name'])));
			break;
		}

		$table->construct_cell(my_date($mybb->settings['dateformat'], intval($r['date']), '', false).", ".my_date($mybb->settings['timeformat'], intval($r['date'])));
		$table->construct_cell("<a href=\"index.php?module=mydownloads-log&amp;action=delete_log&amp;lid={$r['lid']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">{$lang->mydownloads_delete}</a>"); // delete button

		$table->construct_row();
	}
	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->mydownloads_no_log_entries, array('colspan' => 3));

		$table->construct_row();
	}

	$table->output($lang->mydownloads_log_entries);

	echo "<br />";

	$form = new Form("index.php?module=mydownloads-log&amp;action=prune", "post", "mydownloads");

	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

	$form_container = new FormContainer($lang->mydownloads_log_prune);
	$form_container->output_row($lang->mydownloads_older_than, $lang->mydownloads_older_than_desc, $form->generate_text_box('days', 30, array('id' => 'days')), 'days');
	$form_container->end();

	$buttons = array();;
	$buttons[] = $form->generate_submit_button($lang->mydownloads_submit);
	$buttons[] = $form->generate_reset_button($lang->mydownloads_reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif ($mybb->input['action'] == 'delete_log')
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=mydownloads-log");
	}

	if($mybb->request_method == "post")
	{
		if (!$db->fetch_field($db->simple_select('mydownloads_log', 'uid', 'lid='.intval($mybb->input['lid']), array('limit' => 1)), 'uid'))
		{
			flash_message($lang->mydownloads_log_error, 'error');
			admin_redirect('index.php?module=mydownloads-log');
		}
		else {
			$db->delete_query('mydownloads_log', 'lid='.intval($mybb->input['lid']));
			flash_message($lang->mydownloads_log_deleted, 'success');
			admin_redirect('index.php?module=mydownloads-log');
		}
	}
	else
	{
		$mybb->input['lid'] = intval($mybb->input['lid']);
		$form = new Form("index.php?module=mydownloads-log&amp;action=delete_log&amp;lid={$mybb->input['lid']}&amp;my_post_key={$mybb->post_code}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->mydownloads_log_deleteconfirm}</p>\n";
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
		admin_redirect("index.php?module=mydownloads-log");
	}

	if($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->mydownloads_error, 'error');
			admin_redirect("index.php?module=mydownloads-log");
		}

		$db->delete_query('mydownloads_log', 'date < '.(TIME_NOW - intval($mybb->input['days'])*60*60*24));
		flash_message($lang->mydownloads_log_pruned, 'success');
		admin_redirect('index.php?module=mydownloads-log');
	}
	else
	{
		$page->add_breadcrumb_item($lang->mydownloads_log, 'index.php?module=mydownloads-log');

		$page->output_header($lang->mydownloads_log);

		$mybb->input['days'] = intval($mybb->input['days']);
		$form = new Form("index.php?module=mydownloads-log&amp;action=prune&amp;days={$mybb->input['days']}&amp;my_post_key={$mybb->post_code}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->mydownloads_log_pruneconfirm}</p>\n";
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
