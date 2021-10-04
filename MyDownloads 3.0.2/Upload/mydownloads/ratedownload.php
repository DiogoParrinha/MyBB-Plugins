<?php

/***************************************************************************
 *
 *   MyDownloads plugin (/mydownloads/ratedownload.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *
 *
 *   MyDownloads adds a downloads system to MyBB.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'mydownloads/ratedownload.php');

$templatelist = '';
require_once "../global.php";

// Verify incoming POST request
verify_post_check($mybb->input['my_post_key']);

if ($mybb->user['uid'] <= 0)
	error_no_permission();

$lang->load('mydownloads');

$did = intval($mybb->input['did']);
$query = $db->simple_select("mydownloads_downloads", "*", "did='{$did}'");
$download = $db->fetch_array($query);
if(!$download['did'])
{
	error($lang->mydownloads_no_did);
}

if ($download['hidden'] == 1)
	error($lang->mydownloads_no_permissions);

if($download['submitter_uid'] == $mybb->user['uid'])
{
	error($lang->mydownloads_cannot_rate_own);
}

// check if category exists
$cid = intval($download['cid']);
if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'hidden,usergroups', "cid = $cid")))))
	error($lang->mydownloads_no_cid);

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

$mybb->input['rating'] = intval($mybb->input['rating']);
if($mybb->input['rating'] < 1 || $mybb->input['rating'] > 5)
{
	error($lang->mydownloads_error_invalidrating);
}
$plugins->run_hooks("ratedownload_start");

if($mybb->user['uid'] != 0)
{
	$whereclause = "uid='{$mybb->user['uid']}'";
}
else
{
	$whereclause = "ipaddress='".$db->escape_string($session->ipaddress)."'";
}
$query = $db->simple_select("mydownloads_ratings", "*", "{$whereclause} AND did='{$did}'");
$ratecheck = $db->fetch_array($query);

if($ratecheck['rid'] || $mybb->cookies['mybbratedownload'][$did])
{
	error($lang->mydownloads_already_rated);
}
else
{
	$plugins->run_hooks("ratedownload_process");

	$db->write_query("
		UPDATE ".TABLE_PREFIX."mydownloads_downloads
		SET numratings=numratings+1, totalratings=totalratings+'{$mybb->input['rating']}'
		WHERE did='{$did}'
	");
	if($mybb->user['uid'] != 0)
	{
		$insertarray = array(
			'did' => $did,
			'uid' => $mybb->user['uid'],
			'rating' => $mybb->input['rating'],
			'ipaddress' => $db->escape_string($session->ipaddress)
		);
		$db->insert_query("mydownloads_ratings", $insertarray);
	}
	else
	{
		error_no_permission();
	}
}

// log rating
$insert_array = array(
	'uid' => intval($mybb->user['uid']),
	'did' => $did,
	'date' => time(),
	'type' => 2,
	'rating' => $mybb->input['rating'],
	'username' => $db->escape_string($mybb->user['username'])
);
$db->insert_query('mydownloads_log', $insert_array);

$plugins->run_hooks("ratedownload_end");

if($mybb->input['ajax'])
{
	$data = array();
	$data['success'] = $lang->mydownloads_download_rated;
	$query = $db->simple_select("mydownloads_downloads", "totalratings, numratings", "did='$did'", array('limit' => 1));
	$fetch = $db->fetch_array($query);
	$width = 0;
	if($fetch['numratings'] >= 0)
	{
		$averagerating = floatval(round($fetch['totalratings']/$fetch['numratings'], 2));
		$width = intval(round($averagerating))*20;
		$fetch['numratings'] = intval($fetch['numratings']);

		//$ratingvotesav = $lang->sprintf($lang->rating_votes_average, $fetch['numratings'], $averagerating);
		//echo "<average>{$ratingvotesav}</average>\n";
		$data['average'] = "{$lang->mydownloads_total_rate}: {$averagerating}";
		$data['user'] = "{$lang->mydownloads_your_rate}: {$mybb->input['rating']}";
	}
	$data['width'] = "{$width}";

	echo json_encode($data);
	exit;
}

redirect($mybb->settings['bburl']."/mydownloads.php?action=view_down&amp;did={$download['did']}", $lang->mydownloads_download_rated, $lang->mydownloads_download_rated_title, 1);

?>
