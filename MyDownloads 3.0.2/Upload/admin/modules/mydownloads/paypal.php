<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/admin/modules/mydownloads/paypal.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
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

$page->add_breadcrumb_item($lang->mydownloads_log, 'index.php?module=mydownloads-paypal');

$page->output_header($lang->mydownloads_paypal);

$sub_tabs['mydownloads_paypal_logs'] = array(
	'title'			=> $lang->mydownloads_paypal_logs,
	'link'			=> 'index.php?module=mydownloads-paypal',
	'description'	=> $lang->mydownloads_paypal_logs_description
);

$page->output_nav_tabs($sub_tabs, 'mydownloads_paypal_logs');
if (!$mybb->input['action']) // view logs
{
	$tabs = array(
		'search_username' => $lang->mydownloads_paypal_logs_search_username,
		'search_uid' => $lang->mydownloads_paypal_logs_search_uid
	);

	$page->output_tab_control($tabs);

	// quick ban user form
	echo "<div id=\"tab_search_username\">\n";
	$form = new Form("index.php?module=mydownloads-paypal&amp;action=search&amp;search_for=username", "post", "mydownloads");

	$form_container = new FormContainer($lang->mydownloads_paypal_logs_search_by_username);
	$form_container->output_row($lang->mydownloads_paypal_logs_username, "", $form->generate_text_box('search_data', '', array('id' => 'search_data')), 'search_data');

	$form_container->end();

	$buttons = array();
	$buttons[] = $form->generate_submit_button($lang->mydownloads_submit);
	$buttons[] = $form->generate_reset_button($lang->mydownloads_reset);
	$form->output_submit_wrapper($buttons);
	$form->end();

	echo "</div>\n";

	// quick unban user form
	echo "<div id=\"tab_search_uid\">\n";
	$form = new Form("index.php?module=mydownloads-paypal&amp;action=search&amp;search_for=uid", "post", "mydownloads");

	$form_container = new FormContainer($lang->mydownloads_paypal_logs_search_by_uid);
	$form_container->output_row($lang->mydownloads_paypal_logs_uid, "", $form->generate_text_box('search_data', '', array('id' => 'search_data')), 'search_data');

	$form_container->end();

	$buttons = array();
	$buttons[] = $form->generate_submit_button($lang->mydownloads_submit);
	$buttons[] = $form->generate_reset_button($lang->mydownloads_reset);
	$form->output_submit_wrapper($buttons);
	$form->end();

	echo "</div>\n";

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

	$found = false;

	$query = $db->simple_select("mydownloads_paypal_logs", "COUNT(lid) as log_entries");
	$total_rows = $db->fetch_field($query, "log_entries");

	echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=mydownloads-paypal&amp;page={page}");

	// table
	$table = new Table;
	$table->construct_header($lang->mydownloads_paypal_logs_id, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_username, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_download, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_uid, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_date, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_amount, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_type, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_downloaded, array('width' => '10%', 'class' => 'align_center'));

	$query = $db->simple_select('mydownloads_paypal_logs', '*', '', array('order_by' => 'lid', 'order_dir' => 'ASC', 'limit' => "{$start}, {$per_page}"));
	while($log = $db->fetch_array($query)) {
		$table->construct_cell("<strong>".intval($log['lid'])."</strong>", array('class' => 'align_center'));
		$table->construct_cell("<a href=\"".$mybb->settings['bburl']."/member.php?action=profile&amp;uid=".intval($log['uid'])."\" />".htmlspecialchars_uni($log['custom'])."</a>", array("class" => "align_center"));
		$table->construct_cell("<a href=\"".$mybb->settings['bburl']."/mydownloads.php?action=view_down&amp;did=".intval($log['item_number'])."\" />".htmlspecialchars_uni($log['item_name'])."</a>", array("class" => "align_center"));
		$table->construct_cell(intval($log['uid']), array("class" => "align_center"));
		$table->construct_cell(htmlspecialchars_uni($log['payment_date']), array("class" => "align_center"));
		$table->construct_cell(floatval($log['mc_gross']), array("class" => "align_center"));
		$table->construct_cell(htmlspecialchars_uni($log['txn_type']), array("class" => "align_center"));
		$table->construct_cell(intval($log['downloaded']) == 1 ? "Yes" : "No", array("class" => "align_center"));
		$table->construct_row();

		$found = true;
	}

	if (!$found)
	{
		$table->construct_cell($lang->mydownloads_paypal_logs_no_log_entries, array('colspan' => 8));
		$table->construct_row();
	}

	$table->output($lang->mydownloads_log_entries);
}
elseif($mybb->input['action'] == "search")
{
	if ($mybb->input['search_for'] == "username")
		$search_for = "username";
	elseif ($mybb->input['search_for'] == "uid")
		$search_for = "uid";
	else {
		flash_message($lang->mydownloads_search_criteria_incorrect, 'error');
		admin_redirect("index.php?module=mydownloads-paypal");
	}

	$tabs = array(
		'search_username' => $lang->mydownloads_paypal_logs_search_username,
		'search_uid' => $lang->mydownloads_paypal_logs_search_uid
	);

	$page->output_tab_control($tabs);

	// quick ban user form
	echo "<div id=\"tab_search_username\">\n";
	$form = new Form("index.php?module=mydownloads-paypal&amp;action=search&amp;search_for=username", "post", "mydownloads");

	$form_container = new FormContainer($lang->mydownloads_paypal_logs_search_by_username);
	$form_container->output_row($lang->mydownloads_paypal_logs_username, "", $form->generate_text_box('search_data', '', array('id' => 'search_data')), 'search_data');

	$form_container->end();

	$buttons = array();
	$buttons[] = $form->generate_submit_button($lang->mydownloads_submit);
	$buttons[] = $form->generate_reset_button($lang->mydownloads_reset);
	$form->output_submit_wrapper($buttons);
	$form->end();

	echo "</div>\n";

	// quick unban user form
	echo "<div id=\"tab_search_uid\">\n";
	$form = new Form("index.php?module=mydownloads-paypal&amp;action=search&amp;search_for=uid", "post", "mydownloads");

	$form_container = new FormContainer($lang->mydownloads_paypal_logs_search_by_uid);
	$form_container->output_row($lang->mydownloads_paypal_logs_uid, "", $form->generate_text_box('search_data', '', array('id' => 'search_data')), 'search_data');

	$form_container->end();

	$buttons = array();
	$buttons[] = $form->generate_submit_button($lang->mydownloads_submit);
	$buttons[] = $form->generate_reset_button($lang->mydownloads_reset);
	$form->output_submit_wrapper($buttons);
	$form->end();

	echo "</div>\n";


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

	$found = false;

	$query = $db->simple_select("mydownloads_paypal_logs", "COUNT(lid) as log_entries");
	$total_rows = $db->fetch_field($query, "log_entries");

	echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=mydownloads-paypal&amp;action=search&amp;search_for=".$search_for."&amp;page={page}");

	// table
	$table = new Table;
	$table->construct_header($lang->mydownloads_paypal_logs_id, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_username, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_download, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_uid, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_date, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_amount, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_type, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_paypal_logs_downloaded, array('width' => '10%', 'class' => 'align_center'));

	switch ($search_for)
	{
		case "username":
			$query = $db->simple_select('mydownloads_paypal_logs', '*', 'custom=\''.$db->escape_string($mybb->input['search_data']).'\'', array('order_by' => 'lid', 'order_dir' => 'ASC', 'limit' => "{$start}, {$per_page}"));
		break;
		case "uid":
			$query = $db->simple_select('mydownloads_paypal_logs', '*', 'uid='.intval($mybb->input['search_data']), array('order_by' => 'lid', 'order_dir' => 'ASC', 'limit' => "{$start}, {$per_page}"));
		break;
	}

	while($log = $db->fetch_array($query)) {
		$table->construct_cell("<strong>".intval($log['lid'])."</strong>", array('class' => 'align_center'));
		$table->construct_cell("<a href=\"".$mybb->settings['bburl']."/member.php?action=profile&amp;uid=".intval($log['uid'])."\" />".htmlspecialchars_uni($log['custom'])."</a>", array("class" => "align_center"));
		$table->construct_cell("<a href=\"".$mybb->settings['bburl']."/mydownloads.php?action=view_down&amp;did=".intval($log['item_number'])."\" />".htmlspecialchars_uni($log['item_name'])."</a>", array("class" => "align_center"));
		$table->construct_cell(intval($log['uid']), array("class" => "align_center"));
		$table->construct_cell(htmlspecialchars_uni($log['payment_date']), array("class" => "align_center"));
		$table->construct_cell(floatval($log['mc_gross']), array("class" => "align_center"));
		$table->construct_cell(htmlspecialchars_uni($log['txn_type']), array("class" => "align_center"));
		$table->construct_cell(intval($log['downloaded']) == 1 ? "Yes" : "No", array("class" => "align_center"));
		$table->construct_row();

		$found = true;
	}

	if (!$found)
	{
		$table->construct_cell($lang->mydownloads_paypal_logs_wrong_criteria, array('colspan' => 8));
		$table->construct_row();
	}

	$table->output($lang->mydownloads_log_entries);
}

$page->output_footer();

?>
