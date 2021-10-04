<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/mydownloads/comment_download.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'mydownloads/comment_download.php');

$templatelist = '';
require_once "../global.php";

// Verify incoming POST request
verify_post_check($mybb->input['my_post_key']);

// load language
$lang->load("mydownloads");

if (!$mybb->user['uid'])
	error_no_permission();

$plugins->run_hooks("comment_download_start");

$delete_comment = false;

if (intval($mybb->input['cid']) > 0) // in this case 'cid' is the comment id
{
	if($mybb->input['action'] == 'edit_comment')
	{
		if($mybb->settings['mydownloads_time_edit'] == -1 && $mybb->usergroup['canmodcp'] != 1)
			error_no_permission();

		// Get comment
		$comment = $db->fetch_array($db->simple_select("mydownloads_comments", "*", "cid=".intval($mybb->input['cid'])));

		if($mybb->settings['mydownloads_time_edit'] != -1 && (TIME_NOW-$comment['date'] > $mybb->settings['mydownloads_time_edit']) && $mybb->usergroup['canmodcp'] != 1)
		{
			error($lang->mydownloads_cant_edit_comment);
		}

		if($comment['uid'] != $mybb->user['uid'] && $mybb->usergroup['canmodcp'] != 1)
			error_no_permission();

		if($mybb->request_method == 'post')
		{
			if ($mybb->input['message'] == "")
				error($lang->mydownloads_enter_a_comment);

			$db->update_query('mydownloads_comments', array('comment' => $db->escape_string($mybb->input['message'])), 'cid='.$comment['cid']);

			redirect($mybb->settings['bburl']."/mydownloads.php?action=view_down&amp;did={$comment['did']}", $lang->mydownloads_download_comment_edited);
		}

		$comment['message'] = htmlspecialchars_uni($comment['comment']);

		$codebuttons = build_mycode_inserter("message");

		eval("\$page = \"".$templates->get("mydownloads_edit_comment")."\";");
		output_page($page);
		exit;
	}
	elseif ($mybb->input['action'] == 'delete_comment' && $mybb->request_method == 'post')
	{
		if ($mybb->usergroup['canmodcp'] != 1)
		{
			error_no_permission();
		}

		$comment = $db->fetch_array($db->simple_select("mydownloads_comments", "did,cid", "cid=".intval($mybb->input['cid'])));
		// check if did exists
		if($comment['did'])
		{
			$delete_comment = true;
			$did = intval($comment['did']);
		}
		else
			error($lang->mydownloads_no_comid);
	}
	else
		error($lang->sprintf($lang->mydownloads_confirm_delete, $mybb->settings['bburl'], $mybb->post_code, intval($mybb->input['cid']))); // cid is greater than 0 but action is not 'delete_comment' or request method is not POST? Something's not right, display an error page.
}
else
	$did = intval($mybb->input['did']);

$download = $db->fetch_array($db->simple_select("mydownloads_downloads", "*", "did='{$did}'"));
if(!$download['did']) // valid did?
	error($lang->mydownloads_no_did);

if ($download['hidden'] == 1) // download is hidden, we can't view it or delete comments which have been made to it
	error($lang->mydownloads_no_permissions);

// check if category exists
$catid = intval($download['cid']);
if ($catid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'hidden,usergroups', "cid = $catid")))))
	error($lang->mydownloads_no_cid); // it's not a valid category

// verify permissions first
if ($cat['hidden'] == 1)
	error($lang->mydownloads_no_permissions);

if ($cat['usergroups'] != 'all') {
	$groups = explode(",", $cat['usergroups']);
	$add_groups = "";

	if ($cat['usergroups'])
		$add_groups = explode(",", $mybb->user['additionalgroups']);

	if (!in_array($mybb->user['usergroup'], $groups)) { // is the user allowed to view the category?
		// check additional groups

		if ($add_groups) {
			if (count(array_intersect($add_groups, $groups)) == 0)
				error($lang->mydownloads_no_permissions);
		}
		else
			error($lang->mydownloads_no_permissions);
	}
}

if ($delete_comment === true)
{
	// delete comment
	$db->delete_query('mydownloads_comments', 'cid='.intval($mybb->input['cid']));
	$db->update_query('mydownloads_downloads', array('comments' => --$download['comments']), 'did='.intval($did), '', true);

	// log delete comment action
	$insert_array = array(
		'uid' => intval($mybb->user['uid']),
		'did' => $did, // id of the download which was commented
		'date' => time(), // date of the comment
		'type' => 4,
		'username' => $db->escape_string($mybb->user['username'])
	);
	$db->insert_query('mydownloads_log', $insert_array);

	redirect($mybb->settings['bburl']."/mydownloads.php?action=view_down&amp;did={$download['did']}", $lang->mydownloads_download_comment_deleted, $lang->mydownloads_download_comment_deleted_title);
}

if ($mybb->input['message'] == "")
	error($lang->mydownloads_enter_a_comment);

$mybb->input['comment'] = $db->escape_string($mybb->input['message']);

$plugins->run_hooks("comment_download_process");

// flood check
if (intval($mybb->settings['mydownloads_flood']) && !is_moderator()) {
	$lastComment = $db->fetch_field($db->simple_select('mydownloads_comments', 'MAX(date) as lastComment', 'uid = '.intval($mybb->user['uid'])), 'lastComment');
	$interval = time()-$lastComment;

	if ($interval <= intval($mybb->settings['mydownloads_flood']))
		error($lang->sprintf($lang->mydownloads_flood_check, $mybb->settings['mydownloads_flood'] - $interval));
}

$insertarray = array(
	'did' => $did,
	'uid' => intval($mybb->user['uid']),
	'comment' => $mybb->input['comment'],
	'ipaddress' => $db->escape_string($session->ipaddress),
	'username' => $db->escape_string($mybb->user['username']),
	'date' => time()
);
$cid = $db->insert_query("mydownloads_comments", $insertarray);

// MyAlerts integration
if (class_exists('MybbStuff_MyAlerts_AlertTypeManager') && $mybb->user['uid'] != $download['submitter_uid'])
{
	/** @var MybbStuff_MyAlerts_Entity_AlertType $alertType */
	$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('mydownloads_new_comment');

	if ($alertType != null && $alertType->getEnabled()) {
		/**
		 * Initialise a new Alert instance.
		 *
		 * @param int|array                                      $user     The ID of the user this alert is for.
		 * @param int|MybbSTuff_MyAlerts_Entity_AlertType|string $type     The ID of the object this alert is linked to.
		 *                                                                 Optionally pass in an AlertType object or the
		 *                                                                 short code name of the alert type.
		 * @param int                                            $objectId The ID of the object this alert is linked to. (eg: thread ID, post ID, etc.)
		 */
		$alert = new MybbStuff_MyAlerts_Entity_Alert($download['submitter_uid'], $alertType, $download['did']);
		$alert = $alert->setExtraDetails(array('cid' => $cid));

		MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
	}
}

// log commenting action
$insert_array = array(
	'uid' => intval($mybb->user['uid']),
	'did' => $did, // id of the download which was commented
	'date' => time(), // date of the comment
	'type' => 3,
	'username' => $db->escape_string($mybb->user['username'])
);
$db->insert_query('mydownloads_log', $insert_array);

$plugins->run_hooks("comment_download_end");

$db->update_query('mydownloads_downloads', array('comments' => $download['comments']+1), 'did='.intval($did), '', true); // increase comments counter

redirect($mybb->settings['bburl']."/mydownloads.php?action=view_down&amp;did={$download['did']}", $lang->mydownloads_download_commented, $lang->mydownloads_download_commented_title);

?>
