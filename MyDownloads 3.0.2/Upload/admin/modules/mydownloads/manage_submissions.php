<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/admin/modules/mydownloads/manage_submissions.php)
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

// check if NewPoints is installed
$plugins_cache = $cache->read("plugins");
if(isset($plugins_cache['active']['newpoints']) && $mybb->settings['mydownloads_bridge_newpoints'] == 1)
	$newpoints_installed = true;
else
	$newpoints_installed = false;

if ($mybb->settings['mydownloads_paypal_enabled'] == 1)
	$paypal_enabled = true;
else
	$paypal_enabled = false;

if ($mybb->input['action'] == 'view')
{
	if ((!($sid = intval($mybb->input['sid']))) || (!($submission = $db->fetch_array($db->simple_select('mydownloads_submissions', '*', 'sid='.intval($mybb->input['sid']), array('limit' => 1))))))
	{
		flash_message($lang->mydownloads_invalid_sid, 'error');
		admin_redirect("index.php?module=mydownloads-manage_submissions");
	}

	?>
	<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;">
	<?php

	if($submission['preview'] != '')
	{
		$submission['preview'] = unserialize($submission['preview']);

		// Take the first image as cover
		$submission['preview'] = $submission['preview'][0];
	}
	else
	{
		$submission['preview'] = 'nopreview.png';
	}

	if (!$submission['license'])
		$submission['license'] = $lang->mydownloads_no_license_set;

	if (!$submission['version'])
		$submission['version'] = $lang->mydownloads_no_version_set;

	$table = new Table();

	$table->construct_cell($lang->mydownloads_submission_dl_name.':');
	$table->construct_cell(htmlspecialchars_uni($submission['name']));
	$table->construct_row();

	if (empty($submission['description']))
		$submission['description'] = $lang->mydownloads_no_desc_set;
	$table->construct_cell($lang->mydownloads_submission_dl_desc.':');
	$table->construct_cell(htmlspecialchars_uni($submission['description']));
	$table->construct_row();

	$table->construct_cell($lang->mydownloads_submission_dl_preview.':');
	$table->construct_cell('<a href="'.$mybb->settings['bburl'].'/'.$mybb->settings['mydownloads_previews_dir'].'/'.$submission['preview'].'" target="_blank"><img src="'.$mybb->settings['bburl'].'/'.$mybb->settings['mydownloads_previews_dir'].'/'.$submission['preview'].'" width="50" height="50"></a>');
	$table->construct_row();

	if($submission['url'] == '')
	{
		$table->construct_cell($lang->mydownloads_submission_dl_download.':');
		$table->construct_cell('<a href="'.$mybb->settings['bburl'].'/'.$mybb->settings['mydownloads_downloads_dir'].'/'.$submission['download'].'" target="_blank">'.$lang->mydownloads_download_file.'</a>');
		$table->construct_row();
	}

	if($submission['url'] != '')
	{
		$table->construct_cell($lang->mydownloads_submission_dl_download.':');
		$table->construct_cell(nl2br(htmlspecialchars_uni($submission['url'])));
		$table->construct_row();
	}

	$table->construct_cell($lang->mydownloads_submission_dl_license.':');
	$table->construct_cell(htmlspecialchars_uni($submission['license']));
	$table->construct_row();

	$table->construct_cell($lang->mydownloads_submission_dl_version.':');
	$table->construct_cell(htmlspecialchars_uni($submission['version']));
	$table->construct_row();

	if($submission['url'] == '')
	{
		$table->construct_cell($lang->mydownloads_submission_dl_filetype.':');
		$table->construct_cell(htmlspecialchars_uni($submission['filetype']));
		$table->construct_row();

		$table->construct_cell($lang->mydownloads_submission_dl_filesize.':');
		$table->construct_cell(get_friendly_size($submission['filesize']));
		$table->construct_row();
	}

	$table->construct_cell($lang->mydownloads_submission_dl_cat.':');
	$table->construct_cell(htmlspecialchars_uni($db->fetch_field($db->simple_select('mydownloads_categories', 'name', 'cid='.intval($submission['cid'])), 'name')));
	$table->construct_row();

	if ($paypal_enabled) {
		$table->construct_cell($lang->mydownloads_submission_dl_price.':');
		$table->construct_cell($submission['price']." ".$mybb->settings['mydownloads_paypal_currency']);
		$table->construct_row();

		if($submission['receiver_email'] == '')
			$submission['receiver_email'] = $lang->mydownloads_default;

		$table->construct_cell($lang->mydownloads_submission_dl_email.':');
		$table->construct_cell(htmlspecialchars_uni($submission['receiver_email']));
		$table->construct_row();
	}


	if ($newpoints_installed) {
		$table->construct_cell($lang->mydownloads_submission_dl_points.':');
		$table->construct_cell(newpoints_format_points($submission['points']));
		$table->construct_row();
	}

	$table->output($lang->sprintf($lang->mydownloads_viewing_download, $submission['name']));

	?>
	</div>
	</div>
	<?php

	exit;
}
elseif ($mybb->input['action'] == 'modify')
{
	if ((!($sid = intval($mybb->input['sid']))) || (!($submission = $db->fetch_array($db->simple_select('mydownloads_submissions', '*', 'sid='.intval($mybb->input['sid']), array('limit' => 1))))))
	{
		flash_message($lang->mydownloads_invalid_sid, 'error');
		admin_redirect("index.php?module=mydownloads-manage_submissions");
	}

	?>
	<div class="modal" style="width: 500px">
	<div style="overflow-y: auto; max-height: 400px;">
	<?php

	if($mybb->request_method == 'post')
	{
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', '*', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_no_cid, 'error');
			admin_redirect("index.php?module=mydownloads-manage_submissions");
		}

		if (empty($mybb->input['name']))
		{
			flash_message($lang->mydownloads_no_dl_name, 'error');
			admin_redirect("index.php?module=mydownloads-manage_submissions");
		}

		if (!$newpoints_installed)
			$mybb->input['points'] = 0;

		if ($paypal_enabled)
			$mybb->input['price'] = 0;

		// everything was uploaded, insert new download into the database
		$update_array = array(
			"name"			=> $db->escape_string($mybb->input['name']),
			"cid"			=> $cid,
			"description"	=> $db->escape_string($mybb->input['description']),
			"points"		=> floatval($mybb->input['points']),
			"price"			=> floatval($mybb->input['price']),
			"license"		=> $db->escape_string($mybb->input['license']),
			"version"		=> $db->escape_string($mybb->input['version']),
		);
		$db->update_query("mydownloads_submissions", $update_array, "sid = $sid");

		flash_message($lang->mydownloads_download_successfully_edited, 'success');
		admin_redirect("index.php?module=mydownloads-manage_submissions");
	}

	$form = new Form("index.php?module=mydownloads-manage_submissions&amp;action=modify", "post", "mydownloads", 1);

	$catcache = array();
	$foundparents = array();

	// fetch categories
	$cat_query = $db->simple_select('mydownloads_categories', 'cid,name,disporder,parent', '', array('order_by' => 'name', 'order_dir' => 'asc'));
	while($cat = $db->fetch_array($cat_query))
	{
		$catcache[$cat['cid']] = $cat;
	}
	$db->free_result($cat_query);

	$categories = array();

	// Build tree list
	$categories[0] = $lang->mydownloads_no_parent;
	mydownloads_build_tree($categories);

	unset($catcache);

	echo $form->generate_hidden_field('sid', intval($mybb->input['sid']));

	$form_container = new FormContainer($lang->mydownloads_edit_download);
	$form_container->output_row($lang->mydownloads_edit_download_name, '', $form->generate_text_box('name', htmlspecialchars_uni($submission['name']), array('id' => 'name')), 'name');
	$form_container->output_row($lang->mydownloads_edit_download_description, '', $form->generate_text_area('description', htmlspecialchars_uni($submission['description']), array('id' => 'description', 'style' => 'width: 90%')), 'description');
	if ($newpoints_installed)
		$form_container->output_row($lang->mydownloads_edit_download_points, $lang->mydownloads_edit_download_points_description, $form->generate_text_box('points', floatval($submission['points']), array('id' => 'points')), 'points');
	if ($paypal_enabled)
		$form_container->output_row($lang->mydownloads_edit_download_price, $lang->mydownloads_edit_download_price_description, $form->generate_text_box('price', floatval($submission['price']), array('id' => 'price')), 'price');
	$form_container->output_row($lang->mydownloads_edit_download_category, "", $form->generate_select_box('cid', $categories, $submission['cid'], array('id' => 'category')), 'category');
	$form_container->output_row($lang->mydownloads_edit_download_license, $lang->mydownloads_edit_download_license_desc, $form->generate_text_area('license', htmlspecialchars_uni($submission['license']), array('id' => 'license')), 'license');
	$form_container->output_row($lang->mydownloads_edit_download_version, $lang->mydownloads_edit_download_version_desc, $form->generate_text_box('version', htmlspecialchars_uni($submission['version']), array('id' => 'version')), 'version');
	$form_container->end();

	$buttons = array();
	$buttons[] = $form->generate_submit_button($lang->mydownloads_submit_changes);
	$buttons[] = $form->generate_reset_button($lang->mydownloads_reset_button);
	$form->output_submit_wrapper($buttons);
	$form->end();

	?>
	</div>
	</div>
	<?php

	exit;
}
elseif ($mybb->input['action'] == 'reject')
{
	if ((!($sid = intval($mybb->input['sid']))) || (!($submission = $db->fetch_array($db->simple_select('mydownloads_submissions', '*', 'sid='.intval($mybb->input['sid']), array('limit' => 1))))))
	{
		flash_message($lang->mydownloads_invalid_sid, 'error');
		admin_redirect("index.php?module=mydownloads-manage_submissions");
	}

	?>
	<div class="modal" style="width: 500px">
	<div style="overflow-y: auto; max-height: 400px;">
	<?php

	if($mybb->request_method == 'post')
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->mydownloads_error, 'error');
			admin_redirect("index.php?module=mydownloads-manage_submissions");
		}

		// Validate reason
		$mybb->input['reason'] = (int)$mybb->input['reason'];
		if($mybb->input['reason'] < 0 || $mybb->input['reason'] > 5)
		{
			flash_message($lang->mydownloads_invalid_reason, 'error');
			admin_redirect("index.php?module=mydownloads-manage_submissions");
		}

		if($mybb->input['reason'] == 0 && $mybb->input['custom'] == '')
		{
			flash_message($lang->mydownloads_invalid_custom_reason, 'error');
			admin_redirect("index.php?module=mydownloads-manage_submissions");
		}

		if($mybb->input['reason'] == 0)
		{
			$reason = $mybb->input['custom'];
		}
		else
		{
			$var = 'mydownloads_reject_reason'.(int)$mybb->input['reason'];
			$reason = $lang->$var;
		}

		// delete submission
		$db->delete_query('mydownloads_submissions', 'sid='.$sid, 1);

		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$submission['preview']);
		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".'thumbnail_'.$submission['preview']);
		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$submission['download']);

		if ($submission['update_did'])
			$db->update_query('mydownloads_downloads', array('hidden' => 0), 'did='.intval($submission['update_did']));

		$lang->mydownloads_log_rejected = $lang->sprintf($lang->mydownloads_log_rejected, htmlspecialchars_uni($submission['submitter']), htmlspecialchars_uni($submission['name']));

		log_admin_action($lang->mydownloads_log_rejected);

		if ($mybb->settings['mydownloads_pm_on_managing'] == 1) {

			// send pm
			require_once(MYBB_ROOT."/inc/datahandlers/pm.php");

			$pmhandler = new PMDataHandler();

			$message = $lang->sprintf($lang->mydownloads_pm_message_rejected, htmlspecialchars_uni($submission['name']), $reason);
			send_pm(array(
				'subject' => $lang->mydownloads_pm_subject_rejected,
				'message' => $message,
				'touid' => (int)$submission['submitter_uid'],
				'receivepms' => 1
			), 0, true);
		}

		flash_message($lang->mydownloads_rejected, 'success');
		admin_redirect("index.php?module=mydownloads-manage_submissions");
	}

	$form = new Form("index.php?module=mydownloads-manage_submissions&amp;action=reject", "post", "mydownloads", 1);

	unset($catcache);

	echo $form->generate_hidden_field('sid', intval($mybb->input['sid']));

	$reasons = array(
		0 => $lang->mydownloads_reject_reason0,
		1 => $lang->mydownloads_reject_reason1,
		2 => $lang->mydownloads_reject_reason2,
		3 => $lang->mydownloads_reject_reason3,
		4 => $lang->mydownloads_reject_reason4,
		5 => $lang->mydownloads_reject_reason5
	);

	$form_container = new FormContainer($lang->mydownloads_reject_submission);
	$form_container->output_row($lang->mydownloads_reject_reason, "", $form->generate_select_box('reason', $reasons, 0), 'reason');
	$form_container->output_row($lang->mydownloads_reject_custom_reason, $lang->mydownloads_reject_custom_reason_desc, $form->generate_text_area('custom', ''), 'custom');
	$form_container->end();

	$buttons = array();
	$buttons[] = $form->generate_submit_button($lang->mydownloads_submission_dl_reject);
	$form->output_submit_wrapper($buttons);
	$form->end();

	?>
	</div>
	</div>
	<?php

	exit;
}

$page->add_breadcrumb_item($lang->mydownloads_manage_submissions, 'index.php?module=mydownloads-manage_submissions');

$page->output_header($lang->mydownloads_manage_submissions);

if (!$mybb->input['action']) {

	$per_page = 10;
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

	$query = $db->simple_select("mydownloads_submissions", "COUNT(sid) as submissions");
	$total_rows = $db->fetch_field($query, "submissions");

	echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=mydownloads-manage_submissions&amp;page={page}");

	$field = array();

	// table
	$table = new Table;
	$table->construct_header($lang->mydownloads_submission_username, array('width' => '20%'));
	$table->construct_header($lang->mydownloads_submission_dl_name, array('width' => '20%'));
	$table->construct_header($lang->mydownloads_submission_dl_cat, array('width' => '20%'));
	$table->construct_header($lang->mydownloads_submission_dl_approve, array('width' => '10%'));
	$table->construct_header($lang->mydownloads_submission_dl_reject, array('width' => '10%'));
	$table->construct_header($lang->mydownloads_submission_dl_view, array('width' => '10%'));
	$table->construct_header($lang->mydownloads_submission_dl_modify, array('width' => '10%'));

	$query = $db->simple_select('mydownloads_submissions', '*', '', array('order_by' => 'sid', 'order_dir' => 'DESC', 'limit' => "{$start}, {$per_page}"));
	while($sub = $db->fetch_array($query)) {

		$submission = $sub;

		$table->construct_cell('<a href="'.$mybb->settings['bburl'].'/member.php?action=profile&amp;uid='.intval($submission['submitter_uid']).'" target="_blank">'.htmlspecialchars_uni($submission['submitter']).'</a>');
		$table->construct_cell(htmlspecialchars_uni($submission['name']));

		$category = $db->fetch_field($db->simple_select('mydownloads_categories', 'name', 'cid='.intval($submission['cid'])), 'name');
		$table->construct_cell(htmlspecialchars_uni($category));

		$form = new Form("index.php?module=mydownloads-manage_submissions&amp;action=approve", "post", 'mydownloads" onsubmit="return confirm(\''.mydownloads_jsspecialchars($lang->mydownloads_submission_dl_approve_confirm).'\');', 0, "", true);
		$html_data = $form->construct_return;
		$html_data .= $form->generate_hidden_field("sid", $submission['sid']);
		$html_data .= "<input type=\"submit\" class=\"submit_button\" value=\"{$lang->mydownloads_submission_dl_approve}\" />";
		$html_data .= $form->end();

		$table->construct_cell($html_data);

		$form = new Form("", "get", 'mydownloads" onsubmit="MyBB.popupWindow(\'index.php?module=mydownloads-manage_submissions&amp;action=reject&amp;sid='.$submission['sid'].'\', null, true); return false;', 0, "", true);
		$html_data = $form->construct_return;
		$html_data .= $form->generate_hidden_field("sid", $submission['sid']);
		$html_data .= "<input type=\"submit\" class=\"submit_button\" value=\"{$lang->mydownloads_submission_dl_reject}\" />";
		$html_data .= $form->end();

		$table->construct_cell($html_data);

		$form = new Form("", "post", 'mydownloads" onsubmit="MyBB.popupWindow(\'index.php?module=mydownloads-manage_submissions&amp;action=view&amp;sid='.$submission['sid'].'\', null, true); return false;', 0, "", true);
		$html_data = $form->construct_return;
		$html_data .= $form->generate_hidden_field("sid", $submission['sid']);
		$html_data .= "<input type=\"submit\" class=\"submit_button\" value=\"{$lang->mydownloads_submission_dl_view}\" />";
		$html_data .= $form->end();

		$table->construct_cell($html_data);

		$form = new Form("", "get", 'mydownloads" onsubmit="MyBB.popupWindow(\'index.php?module=mydownloads-manage_submissions&amp;action=modify&amp;sid='.$submission['sid'].'\', null, true); return false;', 0, "", true);
		$html_data = $form->construct_return;
		$html_data .= $form->generate_hidden_field("sid", $submission['sid']);
		$html_data .= "<input type=\"submit\" class=\"submit_button\" value=\"{$lang->mydownloads_submission_dl_modify}\" />";
		$html_data .= $form->end();

		$table->construct_cell($html_data);

		$table->construct_row();
	}

	if (!$submission)
	{
		$table->construct_cell($lang->mydownloads_sumbissions_not_found, array('colspan' => 8));
		$table->construct_row();
	}

	$table->output($lang->mydownloads_downloads_submissions);

	$page->output_footer();
}
elseif ($mybb->input['action'] == 'approve') {

	if ((!($sid = intval($mybb->input['sid']))) || (!($submission = $db->fetch_array($db->simple_select('mydownloads_submissions', '*', 'sid='.intval($mybb->input['sid']), array('limit' => 1))))))
	{
		flash_message($lang->mydownloads_invalid_sid, 'error');
		admin_redirect("index.php?module=mydownloads-manage_submissions");
	}
	if ($mybb->request_method == "post")
	{
		if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
		{
			$mybb->request_method = "get";
			flash_message($lang->mydownloads_error, 'error');
			admin_redirect("index.php?module=mydownloads-manage_submissions");
		}

		$cid = intval($submission['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', '*', "cid = $cid")))))
			error($lang->mydownloads_no_cid_dl);

		$lang->mydownloads_log_approved = $lang->sprintf($lang->mydownloads_log_approved, htmlspecialchars_uni($submission['submitter']), htmlspecialchars_uni($submission['name']));

		log_admin_action($lang->mydownloads_log_approved);

		if ($submission['update_did'] > 0)
		{
			$download = mydownloads_get_download(intval($submission['update_did']), 'preview,thumbnail,download');
			$submission['old_download'] = $download['download'];
			unset($download);
		}

		if($submission['preview'] != '')
		{
			$submission['preview'] = unserialize($submission['preview']);
		}

		mydownloads_approve_submission($submission, $cat, $sid);

		if ($mybb->settings['mydownloads_pm_on_managing'] == 1) {
			// send pm
			require_once(MYBB_ROOT."/inc/datahandlers/pm.php");

			$pmhandler = new PMDataHandler();

			$message = $lang->sprintf($lang->mydownloads_pm_message_approved, htmlspecialchars_uni($submission['name']));
			send_pm(array(
				'subject' => $lang->mydownloads_pm_subject_approved,
				'message' => $message,
				'touid' => (int)$submission['submitter_uid'],
				'receivepms' => 1
			), 0, true);
		}

		flash_message($lang->mydownloads_approved, 'success');
		admin_redirect("index.php?module=mydownloads-manage_submissions");
	}
	else {

		$form = new Form("index.php?module=mydownloads-manage_submissions&amp;action=approve", 'post');

		echo $form->generate_hidden_field("sid", intval($mybb->input['sid']));

		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->mydownloads_submission_dl_approve_confirm}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
		echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
		echo "</p>\n";
		echo "</div>\n";
		$form->end();

		$page->output_footer();
	}
}

$page->output_footer();

?>
