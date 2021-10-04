<?php
/***************************************************************************
 *
 *  Minimum Reputation plugin (/inc/plugins/minimumrep.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  This plugin adds restricts posting in certain forums for users with a minimum of X reputation.
 *
 ***************************************************************************/

/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// do NOT remove for security reasons!
if(!defined("IN_MYBB"))
{
	$secure = "-#77;-#121;-#66;-#66;-#45;-#80;-#108;-#117;-#103;-#105;-#110;-#115;";
	$secure = str_replace("-", "&", $secure);
	die("This file cannot be accessed directly.".$secure);
}

// add hooks
$plugins->add_hook('newreply_do_newreply_start', 'minimumrep_invalidate');
$plugins->add_hook('newreply_start', 'minimumrep_invalidate');
$plugins->add_hook('newthread_do_newthread_start', 'minimumrep_invalidate');
$plugins->add_hook('newthread_start', 'minimumrep_invalidate');

function minimumrep_info()
{
	return array(
		"name"			=> "Minimum Reputation",
		"description"	=> "This plugin adds restricts posting in certain forums for users with a minimum of X reputation.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.1",
		"guid" 			=> "61e08b0289c0b1375437ef6c2a545867",
		"compatibility"	=> "18*"
	);
}


function minimumrep_activate()
{
	global $db, $lang;

	// create settings group
	$insertarray = array(
		'name' => 'minimumrep',
		'title' => 'Minimum Reputation',
		'description' => "Settings for Minimum Reputation plugin.",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting = array(
		"sid"			=> NULL,
		"name"			=> "minimumrep_groups",
		"title"			=> "Excluded Groups",
		"description"	=> "Enter the group IDs that are excluded from the validation process.",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "minimumrep_forums",
		"title"			=> "Affected Forums",
		"description"	=> "Enter the forum IDs that are affected by this plugin. (separated by a comma, can be blank if you want to affect all forums)",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "minimumrep_rep",
		"title"			=> "Reputation Value",
		"description"	=> "Minimum required to post (replies and threads) in the affected forums (can be negative or positive or zero).",
		"optionscode"	=> "text",
		"value"			=> "0",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	rebuild_settings();
}


function minimumrep_deactivate()
{
	global $db, $mybb;

	// delete settings group
	$db->delete_query("settinggroups", "name = 'minimumrep'");

	// remove settings
	$db->delete_query('settings', 'name IN (\'minimumrep_groups\',\'minimumrep_forums\',\'minimumrep_rep\')');

	rebuild_settings();
}

// checks permissions for a certain user
function minimumrep_check_permissions($groups_comma)
{
	global $mybb;

	if ($groups_comma == '')
		return false;

	$groups = explode(",", $groups_comma);

	$ourgroups = explode(",", $mybb->user['additionalgroups']);
	$ourgroups[] = $mybb->user['usergroup'];

	if(count(array_intersect($ourgroups, $groups)) == 0)
		return false;
	else
		return true;
}

function minimumrep_is_forum($fid)
{
	$fid = (int)$fid;

	if ($fid <= 0) return 2;

	global $mybb;

	$fids = explode(',', $mybb->settings['minimumrep_forums']);
	if (!in_array($fid, $fids))
		return 3;

	return 0; // it's a forum we need to validate
}

function minimumrep_invalidate()
{
	global $mybb, $fid, $lang;

	$fid = (int)$fid;

	// affected forum?
	if (minimumrep_is_forum($fid)) return 2;

	// are we in the excluded groups?
	if (minimumrep_check_permissions($mybb->settings['minimumrep_groups'])) return 3;

	// if we're here it means the forum must be validated

	$mr = (int)$mybb->settings['minimumrep_rep'];

	$lang->load("minimumrep");

	if ($mybb->input['ajax'])
		$lang->minimumrep_not_enough_rep = $lang->minimumrep_not_enough_rep_ajax;

	$lang->minimumrep_not_enough_rep = $lang->sprintf($lang->minimumrep_not_enough_rep, $mr);

	// guest? No required rep for sure, unless it's 0 of course.
	// if guests can post, they should need to be checked as well. If they can't post, they're still validated but who cares.
	if (!$mybb->user['uid'] && $mr > 0)
		error($lang->minimumrep_not_enough_rep);

	// our rep is lower than the minimum needed? error out.
	if ($mybb->user['reputation'] < $mr)
		error($lang->minimumrep_not_enough_rep);

	return 0;
}

?>
