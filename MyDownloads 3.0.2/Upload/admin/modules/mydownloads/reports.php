<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/admin/modules/mydownloads-reports.php) - MyDownloads MyPlaza Turbo module development has been abandoned.
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

if ($mybb->input['action'] != 'reason')
{
	$page->add_breadcrumb_item($lang->mydownloads_reports, 'index.php?module=mydownloads-reports');

	$page->output_header($lang->mydownloads_reports);

	$sub_tabs['mydownloads_reports'] = array(
		'title'			=> $lang->mydownloads_reports,
		'link'			=> 'index.php?module=mydownloads-reports',
		'description'	=> $lang->mydownloads_reports_description
	);

	$page->output_nav_tabs($sub_tabs, 'mydownloads_reports');
}

if (!$mybb->input['action']) // view reportss
{
	// table
	$table = new Table;
	$table->construct_header($lang->mydownloads_reports_did, array('width' => '10%'));
	$table->construct_header($lang->mydownloads_reports_reported_by);
	$table->construct_header($lang->mydownloads_reports_reason);
	$table->construct_header($lang->mydownloads_reports_date, array('width' => '15%'));
	$table->construct_header($lang->mydownloads_reports_download);
	$table->construct_header($lang->mydownloads_reports_delete);
	$table->construct_header($lang->mydownloads_reports_mark);

	// pagination
	$per_page = 15;
	if($mybb->input['page'] && intval($mybb->input['page']) > 1)
	{
		$mybb->input['page'] = intval($mybb->input['page']);
		$start = ($mybb->input['page']*$per_page)-$per_page;
	}
	else
	{
		$mybb->input['page'] = 1;
		$start = 0;
	}

	$query = $db->simple_select("mydownloads_reports", "COUNT(rid) as reports");
	$total_rows = $db->fetch_field($query, "reports");

	echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=mydownloads-reports&amp;page={page}");

	$query = $db->simple_select('mydownloads_reports', '*', '', array('sort_by' => 'date', 'sort_dir' => 'desc', 'limit' => "{$start},{$per_page}"));
	while($r = $db->fetch_array($query)) {

		if ($r['marked'] == 0)
		{
			$styles = 'background-color: #FFD7D7';
		}
		else
			$styles = '';

		$table->construct_cell(htmlspecialchars_uni($r['did']), array('style' => $styles));
		$table->construct_cell(build_profile_link($r['username'], $r['uid']), array('style' => $styles));

		$form = new Form("", "post", 'mydownloads" onsubmit="MyBB.popupWindow(\'index.php?module=mydownloads-reports&amp;action=reason&amp;rid='.$r['rid'].'\', null, true); return false;', 0, "", true);
		$html_data = $form->construct_return;
		$html_data .= $form->generate_hidden_field("rid", $r['rid']);
		$html_data .= "<input type=\"submit\" class=\"submit_button\" value=\"{$lang->mydownloads_view}\" />";
		$html_data .= $form->end();

		$table->construct_cell($html_data, array('style' => $styles));

		$table->construct_cell(my_date($mybb->settings['dateformat'], $r['date'], '', false).", ".my_date($mybb->settings['timeformat'], $r['date']), array('style' => $styles));

		$table->construct_cell(mydownloads_build_download_link($r['name'], $r['did'])."<br /><small><a href=\"index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=edit_download&amp;did={$r['did']}\">".$lang->mydownloads_edit."</a> ".$lang->mydownloads_or." <a href=\"index.php?module=mydownloads-downloads_categories&amp;action=delete_download&amp;type=downloads&amp;did={$r['did']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">".$lang->mydownloads_delete."</a></small>", array('style' => $styles));

		$form = new Form("index.php?module=mydownloads-reports&amp;action=delete", "post", 'mydownloads" onsubmit="return confirm(\''.mydownloads_jsspecialchars($lang->mydownloads_reports_delete_confirm).'\');', 0, "", true);
		$html_data = $form->construct_return;
		$html_data .= $form->generate_hidden_field("rid", $r['rid']);
		$html_data .= "<input type=\"submit\" class=\"submit_button\" value=\"{$lang->mydownloads_delete}\" />";
		$html_data .= $form->end();

		$table->construct_cell($html_data, array('style' => $styles));

		if ($r['marked'])
		{
			$form = new Form("index.php?module=mydownloads-reports&amp;action=unmark", "post", 'mydownloads" onsubmit="return confirm(\''.mydownloads_jsspecialchars($lang->mydownloads_reports_unmark_confirm).'\');', 0, "", true);
			$html_data = $form->construct_return;
			$html_data .= $form->generate_hidden_field("rid", $r['rid']);
			$html_data .= "<input type=\"submit\" class=\"submit_button\" value=\"{$lang->mydownloads_unmark}\" />";
			$html_data .= $form->end();

			$table->construct_cell($html_data, array('style' => $styles));
		}
		else {
			$form = new Form("index.php?module=mydownloads-reports&amp;action=mark", "post", 'mydownloads" onsubmit="return confirm(\''.mydownloads_jsspecialchars($lang->mydownloads_reports_mark_confirm).'\');', 0, "", true);
			$html_data = $form->construct_return;
			$html_data .= $form->generate_hidden_field("rid", $r['rid']);
			$html_data .= "<input type=\"submit\" class=\"submit_button\" value=\"{$lang->mydownloads_mark}\" />";
			$html_data .= $form->end();

			$table->construct_cell($html_data, array('style' => $styles));
		}


		$table->construct_row();
		$found = true;
	}

	if (!$found)
	{
		$table->construct_cell($lang->mydownloads_no_reports, array('colspan' => 7));
		$table->construct_row();
	}

	$table->output($lang->mydownloads_marked_downloads);

	$page->output_footer();
}
elseif ($mybb->input['action'] == 'delete')
{
	if ($mybb->request_method == "post")
	{
		$mybb->input['rid'] = (int)$mybb->input['rid'];
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'] || !$mybb->input['rid'])
		{
			$mybb->request_method = "get";
			flash_message($lang->mydownloads_error, 'error');
			admin_redirect("index.php?module=mydownloads-reports");
		}

		$r = mydownloads_get_report($mybb->input['rid']);
		if (empty($r))
		{
			flash_message($lang->mydownloads_invalid_report, 'error');
			admin_redirect("index.php?module=mydownloads-reports");
		}

		$db->delete_query('mydownloads_reports', 'rid='.$r['rid'], 1);

		log_admin_action($lang->mydownloads_log_deleted_report);

		flash_message($lang->mydownloads_report_deleted, 'success');
		admin_redirect("index.php?module=mydownloads-reports");
	}
}
elseif ($mybb->input['action'] == 'mark')
{
	if ($mybb->request_method == "post")
	{
		$mybb->input['rid'] = (int)$mybb->input['rid'];
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'] || !$mybb->input['rid'])
		{
			$mybb->request_method = "get";
			flash_message($lang->mydownloads_error, 'error');
			admin_redirect("index.php?module=mydownloads-reports");
		}

		$r = mydownloads_get_report($mybb->input['rid']);
		if (empty($r))
		{
			flash_message($lang->mydownloads_invalid_report, 'error');
			admin_redirect("index.php?module=mydownloads-reports");
		}

		$db->update_query('mydownloads_reports', array('marked' => 1), 'rid=\''.intval($mybb->input['rid']).'\'', 1);

		log_admin_action($lang->mydownloads_log_marked_report);

		flash_message($lang->mydownloads_report_marked, 'success');
		admin_redirect("index.php?module=mydownloads-reports");
	}
}
elseif ($mybb->input['action'] == 'unmark')
{
	if ($mybb->request_method == "post")
	{
		$mybb->input['rid'] = (int)$mybb->input['rid'];
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'] || !$mybb->input['rid'])
		{
			$mybb->request_method = "get";
			flash_message($lang->mydownloads_error, 'error');
			admin_redirect("index.php?module=mydownloads-reports");
		}

		$r = mydownloads_get_report($mybb->input['rid']);
		if (empty($r))
		{
			flash_message($lang->mydownloads_invalid_report, 'error');
			admin_redirect("index.php?module=mydownloads-reports");
		}

		$db->update_query('mydownloads_reports', array('marked' => 0), 'rid=\''.intval($mybb->input['rid']).'\'', 1);

		log_admin_action($lang->mydownloads_log_marked_report);

		flash_message($lang->mydownloads_report_marked, 'success');
		admin_redirect("index.php?module=mydownloads-reports");
	}
}
elseif ($mybb->input['action'] == 'reason')
{
	$mybb->input['rid'] = (int)$mybb->input['rid'];

	$r = mydownloads_get_report($mybb->input['rid']);
	if (empty($r))
	{
		flash_message($lang->mydownloads_invalid_report, 'error');
		admin_redirect("index.php?module=mydownloads-reports");
	}

	?>
	<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;">
	<?php

	$table = new Table();

	$table->construct_cell($lang->mydownloads_report_reason.':');
	$table->construct_cell(nl2br(htmlspecialchars_uni($r['reason'])));
	$table->construct_row();

	$table->output($lang->sprintf($lang->mydownloads_viewing_download_report, $r['name']));

	?>
	</div>
	</div>
	<?php

	exit;
}

?>
