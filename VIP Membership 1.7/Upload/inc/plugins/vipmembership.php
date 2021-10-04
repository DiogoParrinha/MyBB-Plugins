<?php
/***************************************************************************
 *
 *  VIP Membership plugin (/inc/plugins/vipmembership.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  Admins can move users to other groups, e.g. VIP group, and set how much time that user will stay there.
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

if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly.");

// add hooks
$plugins->add_hook('admin_load', 'vipmembership_admin');
$plugins->add_hook('admin_user_menu', 'vipmembership_admin_user_menu');
$plugins->add_hook('admin_user_action_handler', 'vipmembership_admin_user_action_handler');
$plugins->add_hook('admin_user_permissions', 'vipmembership_admin_permissions');

$plugins->add_hook('global_end', 'vipmembership_global');

if(THIS_SCRIPT == 'index.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'viewgroups_index';
}

function vipmembership_info()
{
	return array(
		"name"			=> "VIP Membership",
		"description"	=> "Admins can move users to other groups, e.g. VIP group, and set how much time that user will stay there.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.7",
		"guid" 			=> "2f0deda016f6d79722d0dbb34b6c67e8",
		"compatibility"	=> "18*"
	);
}


function vipmembership_activate()
{
	global $db, $lang;
	// create settings group
	$insertarray = array(
		'name' => 'vipmembership',
		'title' => 'VIP Membership',
		'description' => "Settings for VIP Membership plugin",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);
	// add settings

	$setting0 = array(
		"sid"			=> NULL,
		"name"			=> "vipmembership_uids",
		"title"			=> "User ID\'s",
		"description"	=> "The ID\'s of the users that get a PM when someone\'s membership expires or when an admin adds someone to a membership. (separated by a comma)",
		"optionscode"	=> "text",
		"value"			=> "1",
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting0);


	$setting1 = array(
		"sid"			=> NULL,
		"name"			=> "vipmembership_autoexpire",
		"title"			=> "Auto Expire Users?",
		"description"	=> "Check, each time a page is loaded, for users whose expiration date has passed and moves them to the end group. By setting to no, you will need to end the membership manually.",
		"optionscode"	=> "yesno",
		"value"			=> "0",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting1);

	$setting2 = array(
		"sid"			=> NULL,
		"name"			=> "vipmembership_runtask",
		"title"			=> "Check for expirations using MyBB\'s tasks system?",
		"description"	=> "If you set this to No, you will have to add a task for VIP Memebership from the tasks page(task file is vipmembership.php). (If set to No, a query will be run each time a page is loaded to check expired memberships)",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> 3,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting2);

	rebuild_settings();

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."vipmembership_memberships` (
	  `mid` bigint(30) UNSIGNED NOT NULL auto_increment,
	  `uid` bigint(30) UNSIGNED NOT NULL default '0',
	  `newgroup` int(10) UNSIGNED NOT NULL default '0',
	  `endgroup` int(10) UNSIGNED NOT NULL default '0',
	  `date` bigint(30) UNSIGNED NOT NULL default '0',
	  `wait` bigint(30) UNSIGNED NOT NULL default '0',
	  `years` bigint(30) UNSIGNED NOT NULL default '0',
	  `months` bigint(30) UNSIGNED NOT NULL default '0',
	  `days` bigint(30) UNSIGNED NOT NULL default '0',
	  `hours` bigint(30) UNSIGNED NOT NULL default '0',
	  `minutes` bigint(30) UNSIGNED NOT NULL default '0',
	  `seconds` bigint(30) UNSIGNED NOT NULL default '0',
	  `additional` smallint(1) UNSIGNED NOT NULL default '0',
	  `expired` smallint(1) UNSIGNED NOT NULL default '0',
	  `alerted` smallint(1) UNSIGNED NOT NULL default '0',
	  `note` text NOT NULL,
	  PRIMARY KEY  (`mid`), KEY(`date`, `expired`)
		) ENGINE=MyISAM");

	// create task
	$new_task = array(
		"title" => "VIP Membership",
		"description" => "Checks for members whose membership have expired.",
		"file" => "vipmembership",
		"minute" => '10,25,40,55',
		"hour" => '*',
		"day" => '*',
		"month" => '*',
		"weekday" => '*',
		"enabled" => '0',
		"logging" => '1'
	);

	$new_task['nextrun'] = 0; // once the task is enabled, it will generate a nextrun date
	$tid = $db->insert_query("tasks", $new_task);
}


function vipmembership_deactivate()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'vipmembership'");

	// remove settings
	$db->delete_query('settings', 'name IN ( \'vipmembership_users\',\'vipmembership_autoexpire\',\'vipmembership_runtask\')');

	$db->delete_query('tasks', 'file=\'vipmembership\''); // delete all tasks that use vipmembership task file

	rebuild_settings();

	if ($db->table_exists('vipmembership_memberships'))
		$db->drop_table('vipmembership_memberships');
}

/**
 * Sends a PM to a user, with Admin Override
 *
 * @param array: The PM to be sent; should have 'subject', 'message', 'touid'
 * @param int: from user id (0 if you want to use the uid the person that sends it. -1 to use MyBB Engine
 * @return bool: true if PM sent
 */
function vipmembership_send_pm($pm, $fromid = 0)
{
	global $lang, $mybb, $db;
	if($mybb->settings['enablepms'] == 0) return false;
	if (!is_array($pm))	return false;
	if (!$pm['subject'] ||!$pm['message'] || !$pm['touid'] || !$pm['receivepms']) return false;

	require_once MYBB_ROOT."inc/datahandlers/pm.php";

	$pmhandler = new PMDataHandler();

	$subject = $pm['subject'];
	$message = $pm['message'];
	$toid = $pm['touid'];

	require_once MYBB_ROOT."inc/datahandlers/pm.php";

	$pmhandler = new PMDataHandler();

	if (is_array($toid))
		$recipients_to = $toid;
	else
		$recipients_to = array($toid);
	$recipients_bcc = array();

	if (intval($fromid) == 0)
		$fromid = intval($mybb->user['uid']);
	elseif (intval($fromid) < 0)
		$fromid = 0;

	$pm = array(
		"subject" => $subject,
		"message" => $message,
		"icon" => -1,
		"fromid" => 0,
		"toid" => $recipients_to,
		"bccid" => $recipients_bcc,
		"do" => '',
		"pmid" => ''
	);

	$pm['options'] = array(
		"signature" => 0,
		"disablesmilies" => 0,
		"savecopy" => 0,
		"readreceipt" => 0
	);
	$pm['saveasdraft'] = 0;
	$pmhandler->admin_override = 1;
	$pmhandler->set_data($pm);
	if($pmhandler->validate_pm())
	{
		$pmhandler->insert_pm();
	}
	else
	{
		return false;
	}

	return true;
}

function vipmembership_global()
{
	global $mybb, $db, $lang;

	// running off task, so just exit from this function
	if ($mybb->settings['vipmembership_runtask'] == 1)
		return;

	$lang->load("vipmembership");

	// get memberships
	$query = $db->simple_select('vipmembership_memberships', '*', 'expired=0 AND (years != 0 OR months != 0 OR days != 0 OR hours != 0 OR minutes != 0 OR seconds != 0)');
	while ($member = $db->fetch_array($query))
	{
		// calculate date on the fly
		$member['date'] += $member['wait'];

		// expiration date has already passed
		if ($member['date'] < TIME_NOW)
		{
			// if auto expire setting is set to Yes
			if ($mybb->settings['vipmembership_autoexpire'] == 1)
			{
				// terminate membership
				$db->update_query('vipmembership_memberships', array('expired' => 1), 'mid='.$member['mid']);

				$uid = $member['uid'];

				// if additional, leave user group
				if ($member['additional'] == 1)
				{
					leave_usergroup($uid, $member['newgroup']);
				}
				// else just change the primary group
				else {
					$db->update_query('users', array('usergroup' => $member['endgroup']), 'uid='.$uid);
				}

				// pm user
				$to = $member['uid'];
				vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_global_yourended_title, 'message' => $lang->sprintf($lang->vipmembership_pm_global_yourended, vipmembership_get_username($member['uid'])), 'touid' => $to, 'receivepms' => 1), 0);

				// send pm to users set in settings
				$to = explode(',', $mybb->settings['vipmembership_uids']);
				vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_global_endmember_title, 'message' => $lang->sprintf($lang->vipmembership_pm_global_endmember, vipmembership_get_username($member['uid'])), 'touid' => $to, 'receivepms' => 1), 0);
			}
			else {
				if ($member['alerted'] == 0)
				{
					// send pm to users set in settings
					$to = explode(',', $mybb->settings['vipmembership_uids']);
					vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_global_man_endmember_title, 'message' => $lang->sprintf($lang->vipmembership_pm_global_man_endmember, vipmembership_get_username($member['uid'])), 'touid' => $to, 'receivepms' => 1), 0);

					$db->update_query('vipmembership_memberships', array('alerted' => 1), 'mid='.$member['mid']);
				}
			}
		}
	}
}

function vipmembership_get_grouptitle($gid)
{
	global $db;
	$query = $db->simple_select('usergroups', 'title', 'gid=\''.intval($gid).'\'', 1);
	return $db->fetch_field($query, 'title');
}

function vipmembership_get_usergroup($uid)
{
	global $db;
	$query = $db->simple_select('users', 'usergroup', 'uid=\''.intval($uid).'\'', 1);
	return $db->fetch_field($query, 'usergroup');
}

function vipmembership_get_username($uid)
{
	global $db;
	$query = $db->simple_select('users', 'username', 'uid=\''.intval($uid).'\'', 1);
	return $db->fetch_field($query, 'username');
}

function vipmembership_get_uid($username)
{
	global $db;
	$query = $db->simple_select('users', 'uid', 'username=\''.$db->escape_string($username).'\'', 1);
	return $db->fetch_field($query, 'uid');
}

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function vipmembership_admin_user_menu(&$sub_menu)
{
	global $lang;

	$lang->load('vipmembership');
	$sub_menu[] = array('id' => 'vipmembership', 'title' => $lang->vipmembership_index, 'link' => 'index.php?module=user-vipmembership');
}

function vipmembership_admin_user_action_handler(&$actions)
{
	$actions['vipmembership'] = array('active' => 'vipmembership', 'file' => 'vipmembership');
}

function vipmembership_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	$lang->load("vipmembership", false, true);
	$admin_permissions['vipmembership'] = $lang->vipmembership_canmanage;

}

function vipmembership_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	$lang->load("vipmembership", false, true);

	if($run_module == 'user' && $action_file == 'vipmembership')
	{
		if ($mybb->request_method == "post")
		{
			switch ($mybb->input['action'])
			{
				case 'do_adduser':
					if (!$mybb->input['username'] || !$mybb->input['newgroup'])
					{
						flash_message($lang->vipmembership_missing, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					// unlimited
					/*if (!$mybb->input['years'] && !$mybb->input['months'] && !$mybb->input['days'] && !$mybb->input['hours'] && !$mybb->input['minutes'] && !$mybb->input['seconds'])
					{
						flash_message($lang->vipmembership_missing_date, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}*/

					if ($mybb->input['note'] != "")
					{
						$note = $db->escape_string($mybb->input['note']);
					}
					else
						$note = "";

					$username = $db->escape_string(trim($mybb->input['username']));
					if (!($uid = vipmembership_get_uid($username)))
					{
						// invalid user
						flash_message($lang->vipmembership_invalid_user, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					$query = $db->simple_select('vipmembership_memberships', '*', 'uid='.$uid.' AND expired=0');
					$member = $db->fetch_array($query);
					if ($member)
					{
						flash_message($lang->vipmembership_alreadyadded, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					$newgroup = intval($mybb->input['newgroup']);
					if (!vipmembership_get_grouptitle($newgroup))
					{

						// invalid new group
						flash_message($lang->vipmembership_invalid_group, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					$endgroup = intval($mybb->input['endgroup']);
					if ($endgroup <= 0)
					{
						// no end group set, leave it as current group
						$endgroup = vipmembership_get_usergroup($uid);
					}

					$years = intval($mybb->input['years']);
					$months = intval($mybb->input['months']);
					$days = intval($mybb->input['days']);
					$hours = intval($mybb->input['hours']);
					$minutes = intval($mybb->input['minutes']);
					$seconds = intval($mybb->input['seconds']);

					// calculate date
					$date = 0;
					if ($years > 0)
					{
						$date = (31536000 * $years) + $date;
					}
					if ($months > 0)
					{
						$date = (2628000 * $months) + $date;
					}
					if ($days > 0)
					{
						$date = (86400 * $days) + $date;
					}
					if ($hours > 0)
					{
						$date = (3600 * $hours) + $date;
					}
					if ($minutes > 0)
					{
						$date = (60 * $minutes) + $date;
					}
					if ($seconds > 0)
					{
						$date = (1 * $seconds) + $date;
					}

					$additional = intval($mybb->input['additional']);
					if ($additional >= 1)
						$additional = 1;
					else
						$additional = 0;

					$curgroup = vipmembership_get_usergroup($uid);

					$insert_array = array('uid' => $uid,
										  'newgroup' => $newgroup,
										  'endgroup' => $endgroup,
										  'date' => TIME_NOW,
										  'wait' => $date,
										  'additional' => $additional,
										  'expired' => 0,
										  'note' => $note,
										  'years' => $years,
										  'months' => $months,
										  'days' => $days,
										  'hours' => $hours,
										  'minutes' => $minutes,
										  'seconds' => $seconds);

					$db->insert_query('vipmembership_memberships', $insert_array);

					// if additional, join user group to the user's additional groups list
					if ($additional == 1)
					{
						join_usergroup($uid, $newgroup);
					}
					// else just change the primary group
					else {
						$db->update_query('users', array('usergroup' => $newgroup), 'uid='.$uid);
					}

					// send pm
					$to = $uid;
					if ($additional == 1)
					{
						$mybb->input['pm'] = $lang->vipmembership_pm_welcome_additional;

						vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_welcome_title, 'message' => $lang->sprintf($mybb->input['pm'], $mybb->user['username'], vipmembership_get_grouptitle($newgroup)), 'touid' => $to, 'receivepms' => 1), 0);
					}
					else {
						vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_welcome_title, 'message' => $lang->sprintf($mybb->input['pm'], $mybb->user['username'], vipmembership_get_grouptitle($curgroup), vipmembership_get_grouptitle($newgroup)), 'touid' => $to, 'receivepms' => 1), 0);
					}

					$to = explode(',', $mybb->settings['vipmembership_uids']);
					vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_newmember_title, 'message' => $lang->sprintf($lang->vipmembership_pm_newmember, $mybb->user['username'], $username), 'touid' => $to, 'receivepms' => 1), 0);

					flash_message($lang->vipmembership_added_user, 'success');
					admin_redirect("index.php?module=user-vipmembership");

				break;
			case 'do_edituser':

					$mid = intval($mybb->input['mid']);
					$query = $db->simple_select('vipmembership_memberships', '*', 'mid='.$mid);
					$member = $db->fetch_array($query);
					if (!$member)
					{
						flash_message($lang->vipmembership_invalid_member, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					if ($member['expired'])
					{
						flash_message($lang->vipmembership_invalid_member, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					if (!$mybb->input['newgroup'])
					{
						flash_message($lang->vipmembership_missing, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					// unlimited
					/*if (!$mybb->input['years'] && !$mybb->input['months'] && !$mybb->input['days'] && !$mybb->input['hours'] && !$mybb->input['minutes'] && !$mybb->input['seconds'])
					{
						flash_message($lang->vipmembership_missing_date, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}*/

					if ($mybb->input['note'] != "")
					{
						$note = $db->escape_string($mybb->input['note']);
					}
					else
						$note = "";

					$newgroup = intval($mybb->input['newgroup']);
					if (!vipmembership_get_grouptitle($newgroup))
					{
						// invalid new group
						flash_message($lang->vipmembership_invalid_group, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					$endgroup = intval($mybb->input['endgroup']);
					if ($endgroup <= 0)
					{
						// no end group set, leave it as current group
						$endgroup = vipmembership_get_usergroup($member['uid']);
					}
					if (!vipmembership_get_grouptitle($endgroup) && $endgroup > 0)
					{
						// invalid new group
						flash_message($lang->vipmembership_invalid_group, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					$years = intval($mybb->input['years']);
					$months = intval($mybb->input['months']);
					$days = intval($mybb->input['days']);
					$hours = intval($mybb->input['hours']);
					$minutes = intval($mybb->input['minutes']);
					$seconds = intval($mybb->input['seconds']);

					// calculate date
					$date = 0;
					if ($years > 0)
					{
						$date = (31536000 * $years) + $date;
					}
					if ($months > 0)
					{
						$date = (2628000 * $months) + $date;
					}
					if ($days > 0)
					{
						$date = (86400 * $days) + $date;
					}
					if ($hours > 0)
					{
						$date = (3600 * $hours) + $date;
					}
					if ($minutes > 0)
					{
						$date = (60 * $minutes) + $date;
					}
					if ($seconds > 0)
					{
						$date = (1 * $seconds) + $date;
					}

					$additional = intval($mybb->input['additional']);
					if ($additional >= 1)
						$additional = 1;
					else
						$additional = 0;

					$update_array = array('newgroup' => $newgroup,
										  'endgroup' => $endgroup,
										  'date' => TIME_NOW,
										  'wait' => $date,
										  'additional' => $additional,
										  'expired' => 0,
										  'note' => $note,
										  'years' => $years,
										  'months' => $months,
										  'days' => $days,
										  'hours' => $hours,
										  'minutes' => $minutes,
										  'seconds' => $seconds);

					$db->update_query('vipmembership_memberships', $update_array, 'mid='.$mid);

					$uid = $member['uid'];

					// if additional, join user group to the user's additional groups list
					if ($additional == 1)
					{
						join_usergroup($uid, $newgroup);
					}
					// else just change the primary group
					else {
						$db->update_query('users', array('usergroup' => $newgroup), 'uid='.$uid);
					}

					flash_message($lang->vipmembership_edited_user, 'success');
					admin_redirect("index.php?module=user-vipmembership");

				break;
				case 'do_enduser':

					$mid = intval($mybb->input['mid']);
					$query = $db->simple_select('vipmembership_memberships', '*', 'mid='.$mid);
					$member = $db->fetch_array($query);
					if (!$member)
					{
						flash_message($lang->vipmembership_invalid_member, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					$endgroup = intval($mybb->input['endgroup']);
					if ($endgroup <= 0)
					{
						// no end group set, leave it as current group
						$endgroup = vipmembership_get_usergroup($member['uid']);
					}
					if (!vipmembership_get_grouptitle($endgroup) && $endgroup > 0)
					{
						// invalid new group
						flash_message($lang->vipmembership_invalid_group, 'error');
						admin_redirect("index.php?module=user-vipmembership");
					}

					//$db->delete_query('vipmembership_memberships', 'mid='.$mid, 1);
					$db->update_query('vipmembership_memberships', array('expired' => 1), 'mid='.$mid);

					$uid = $member['uid'];

					// if additional, leave the new user group
					if ($member['additional'] == 1)
					{
						leave_usergroup($uid, $newgroup);
					}
					// else just change the primary group
					else {
						$db->update_query('users', array('usergroup' => $endgroup), 'uid='.$uid);
					}

					// check if send PM is set to yes
					if ($mybb->input['sendpm'] == 1)
					{
						// send pm
						$to = $uid;
						vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_yourended_title, 'message' => $lang->sprintf($mybb->input['pm'], $mybb->user['username']), 'touid' => $to, 'receivepms' => 1), 0);
					}

					$username = vipmembership_get_username($uid);

					// send pm to users set in settings
					$to = explode(',', $mybb->settings['vipmembership_uids']);
					vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_endmember_title, 'message' => $lang->sprintf($lang->vipmembership_pm_endmember, $mybb->user['username'], $username), 'touid' => $to, 'receivepms' => 1), 0);

					flash_message($lang->vipmembership_ended_user, 'success');
					admin_redirect("index.php?module=user-vipmembership");

				break;
			}
		}

		if (!$mybb->input['action'] || $mybb->input['action'] == 'adduser' || $mybb->input['action'] == 'expired' || $mybb->input['action'] == 'enduser' || $mybb->input['action'] == 'edituser' || $mybb->input['action'] == 'deleteuser')
		{
			$page->add_breadcrumb_item($lang->vipmembership, 'index.php?module=user-vipmembership');

			$page->output_header($lang->vipmembership);

			$sub_tabs['vipmembership_members'] = array(
				'title'			=> $lang->vipmembership_members,
				'link'			=> 'index.php?module=user-vipmembership',
				'description'	=> $lang->vipmembership_members_desc
			);
			$sub_tabs['vipmembership_expired'] = array(
				'title'			=> $lang->vipmembership_expired,
				'link'			=> 'index.php?module=user-vipmembership&amp;action=expired',
				'description'	=> $lang->vipmembership_expired_desc
			);
			$sub_tabs['vipmembership_add'] = array(
				'title'			=> $lang->vipmembership_add,
				'link'			=> 'index.php?module=user-vipmembership&amp;action=adduser',
				'description'	=> $lang->vipmembership_add_desc
			);
			$sub_tabs['vipmembership_end'] = array(
				'title'			=> $lang->vipmembership_end,
				'link'			=> 'index.php?module=user-vipmembership&amp;action=enduser',
				'description'	=> $lang->vipmembership_end_desc
			);
			$sub_tabs['vipmembership_edit'] = array(
				'title'			=> $lang->vipmembership_edit,
				'link'			=> 'index.php?module=user-vipmembership&amp;action=edituser',
				'description'	=> $lang->vipmembership_edit_desc
			);
		}

		if (!$mybb->input['action'])
		{
			$page->output_nav_tabs($sub_tabs, 'vipmembership_members');

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

			$query = $db->simple_select("vipmembership_memberships", "COUNT(mid) as members", "expired=0");
			$total_rows = $db->fetch_field($query, "members");

			echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=user-vipmembership&amp;page={page}");

			// table
			$table = new Table;
			$table->construct_header($lang->vipmembership_mid, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->vipmembership_username, array('width' => '25%'));
			$table->construct_header($lang->vipmembership_newgroup, array('width' => '15%'));
			$table->construct_header($lang->vipmembership_endgroup, array('width' => '15%'));
			$table->construct_header($lang->vipmembership_additional, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->vipmembership_expiredate, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->vipmembership_action, array('width' => '10%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT u.*, u.username AS userusername, m.*
				FROM ".TABLE_PREFIX."vipmembership_memberships m
				LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=m.uid)
				WHERE m.expired = 0
				ORDER BY m.date DESC LIMIT {$start}, {$per_page}
			");

			while ($mem = $db->fetch_array($query))
			{
				$member = $mem;

				if ($member['years'] != 0 || $member['months'] != 0 || $member['days'] != 0 || $member['hours'] != 0 || $member['minutes'] != 0 || $member['seconds'] != 0)
				{
					$lifetime = false;
				}
				else
					$lifetime = true;

				if (intval($member['date'])+intval($member['wait']) < TIME_NOW && !$lifetime)
					$styles = 'background-color: #FFD7D7';
				else
					$styles = '';

				$table->construct_cell(intval($member['mid']), array('class' => 'align_center', 'style' => $styles)); // member id
				$table->construct_cell(htmlspecialchars_uni($member['username']), array('style' => $styles)); // member name
				$table->construct_cell(htmlspecialchars_uni(vipmembership_get_grouptitle(intval($member['newgroup']))), array('style' => $styles)); // member new group
				$table->construct_cell(htmlspecialchars_uni(vipmembership_get_grouptitle(intval($member['endgroup']))), array('style' => $styles)); // member end group
				$table->construct_cell($member['additional'] ? "Yes" : "No", array('class' => 'align_center', 'style' => $styles));
				$table->construct_cell($lifetime ? $lang->vipmembership_lifetime : my_date($mybb->settings['dateformat'], intval($member['date'])+intval($member['wait']), '', false).", ".my_date($mybb->settings['timeformat'], intval($member['date'])+intval($member['wait'])), array('class' => 'align_center', 'style' => $styles)); // expiration date

				// actions column
				$table->construct_cell("<a href=\"index.php?module=user-vipmembership&amp;action=enduser&amp;mid=".intval($member['mid'])."\">".$lang->vipmembership_end."</a> - <a href=\"index.php?module=user-vipmembership&amp;action=edituser&amp;mid=".intval($member['mid'])."\">".$lang->vipmembership_edit."</a>", array('class' => 'align_center', 'style' => $styles));

				$table->construct_row();
			}

			if (!$member)
			{
				$table->construct_cell($lang->vipmembership_nomembers, array('colspan' => 7));

				$table->construct_row();
			}

			$table->output($lang->vipmembership_members);

		}
		elseif ($mybb->input['action'] == 'expired')
		{
			$page->output_nav_tabs($sub_tabs, 'vipmembership_expired');

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

			$query = $db->simple_select("vipmembership_memberships", "COUNT(mid) as members","expired=1");
			$total_rows = $db->fetch_field($query, "members");

			echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=user-vipmembership&amp;page={page}&amp;action=expired");

			// table
			$table = new Table;
			$table->construct_header($lang->vipmembership_mid, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->vipmembership_username, array('width' => '25%'));
			$table->construct_header($lang->vipmembership_newgroup, array('width' => '15%'));
			$table->construct_header($lang->vipmembership_endgroup, array('width' => '15%'));
			$table->construct_header($lang->vipmembership_additional, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->vipmembership_expiredate, array('width' => '15%'));
			$table->construct_header($lang->vipmembership_action, array('width' => '10%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT u.*, u.username AS userusername, m.*
				FROM ".TABLE_PREFIX."vipmembership_memberships m
				LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=m.uid)
				WHERE m.expired = 1
				ORDER BY m.date DESC LIMIT {$start}, {$per_page}
			");

			while ($mem = $db->fetch_array($query))
			{
				$member = $mem;
				$table->construct_cell(intval($member['mid']), array('class' => 'align_center')); // member id
				$table->construct_cell(htmlspecialchars_uni($member['username'])); // member name
				$table->construct_cell(htmlspecialchars_uni(vipmembership_get_grouptitle(intval($member['newgroup'])))); // member new group
				$table->construct_cell(htmlspecialchars_uni(vipmembership_get_grouptitle(intval($member['endgroup'])))); // member end group
				$table->construct_cell($member['additional'] ? "Yes" : "No", array('class' => 'align_center'));
				$table->construct_cell(my_date($mybb->settings['dateformat'], intval($member['date'])+intval($member['wait']), '', false).", ".my_date($mybb->settings['timeformat'], intval($member['date'])+intval($member['wait'])), array('class' => 'align_center')); // expiration date

				// actions column
				$table->construct_cell("<a href=\"index.php?module=user-vipmembership&amp;action=edituser&amp;mid=".intval($member['mid'])."\">".$lang->vipmembership_edit."</a> - <a href=\"index.php?module=user-vipmembership&amp;action=deleteuser&amp;mid=".intval($member['mid'])."\">".$lang->vipmembership_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if (!$member)
			{
				$table->construct_cell($lang->vipmembership_nomembers, array('colspan' => 7));

				$table->construct_row();
			}

			$table->output($lang->vipmembership_expired_memberships);

		}
		elseif ($mybb->input['action'] == "adduser")
		{
			$page->output_nav_tabs($sub_tabs, 'vipmembership_add');

			$newgroups[0] = $lang->vipmembership_nogroup;
			$endgroups[0] = $lang->vipmembership_curgroup;

			// get groups
			$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
			while($group = $db->fetch_array($query, 'title, gid'))
			{
				$endgroups[$group['gid']] = $group['title'];
				$newgroups[$group['gid']] = $group['title'];
			}

			$form = new Form("index.php?module=user-vipmembership&amp;action=do_adduser", "post", "vipmembership");

			$form_container = new FormContainer($lang->vipmembership_addmember);
			$form_container->output_row($lang->vipmembership_user_username, $lang->vipmembership_user_username_desc, $form->generate_text_box('username', '', array('id' => 'username')), 'username');
			$form_container->output_row($lang->vipmembership_user_newgroup, $lang->vipmembership_user_newgroup_desc, $form->generate_select_box('newgroup', $newgroups, 0, array('id' => 'newgroup')), 'newgroup');
			$form_container->output_row($lang->vipmembership_user_endgroup, $lang->vipmembership_user_endgroup_desc, $form->generate_select_box('endgroup', $endgroups, 0, array('id' => 'endgroup')), 'endgroup');
			$form_container->output_row($lang->vipmembership_user_years, $lang->vipmembership_user_years_desc, $form->generate_text_box('years', '0', array('id' => 'years')), 'years');
			$form_container->output_row($lang->vipmembership_user_months, $lang->vipmembership_user_months_desc, $form->generate_text_box('months', '0', array('id' => 'months')), 'months');
			$form_container->output_row($lang->vipmembership_user_days, $lang->vipmembership_user_days_desc, $form->generate_text_box('days', '0', array('id' => 'days')), 'days');
			$form_container->output_row($lang->vipmembership_user_hours, $lang->vipmembership_user_hours_desc, $form->generate_text_box('hours', '0', array('id' => 'hours')), 'hours');
			$form_container->output_row($lang->vipmembership_user_minutes, $lang->vipmembership_user_minutes_desc, $form->generate_text_box('minutes', '0', array('id' => 'minutes')), 'minutes');
			$form_container->output_row($lang->vipmembership_user_seconds, $lang->vipmembership_user_seconds_desc, $form->generate_text_box('seconds', '0', array('id' => 'seconds')), 'seconds');
			$form_container->output_row($lang->vipmembership_user_additionalgroup, $lang->vipmembership_user_additionalgroup_desc, $form->generate_yes_no_radio('additional', 0, true, "", ""), 'additional');
			$form_container->output_row($lang->vipmembership_user_note, $lang->vipmembership_user_note_desc, $form->generate_text_area('note', '', array('id' => 'note')), 'note');
			$form_container->output_row($lang->vipmembership_privatemessage, $lang->vipmembership_privatemessage_desc, $form->generate_text_area('pm', $lang->vipmembership_pm_welcome, array('id' => 'pm')), 'pm');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->vipmembership_submit);
			$buttons[] = $form->generate_reset_button($lang->vipmembership_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == "enduser")
		{
			$page->output_nav_tabs($sub_tabs, 'vipmembership_end');

			$mid = intval($mybb->input['mid']);
			$query = $db->simple_select('vipmembership_memberships', '*', 'mid='.$mid);
			$member = $db->fetch_array($query);
			if (!$member)
			{
				flash_message($lang->vipmembership_invalid_member, 'error');
				admin_redirect("index.php?module=user-vipmembership");
			}

			$member['username'] = vipmembership_get_username($member['uid']);

			$endgroups[0] = $lang->vipmembership_nogroup;

			// get groups
			$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
			while($group = $db->fetch_array($query, 'title, gid'))
			{
				$endgroups[$group['gid']] = $group['title'];
			}

			$form = new Form("index.php?module=user-vipmembership&amp;action=do_enduser", "post", "vipmembership");

			echo $form->generate_hidden_field('mid', $mid);

			$form_container = new FormContainer($lang->vipmembership_endmember);
			$form_container->output_row($lang->vipmembership_user_endgroup, $lang->vipmembership_user_end_group_desc, $form->generate_select_box('endgroup', $endgroups, $member['endgroup'], array('id' => 'endgroup')), 'endgroup');
			$form_container->output_row($lang->vipmembership_user_sendpm, $lang->vipmembership_user_sendpm_desc, $form->generate_yes_no_radio('sendpm', 1, true, "", ""), 'sendpm');
			$form_container->output_row($lang->vipmembership_sendpm_privatemessage, $lang->vipmembership_sendpm_privatemessage_desc, $form->generate_text_area('pm', $lang->vipmembership_pm_yourended, array('id' => 'pm')), 'pm');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->vipmembership_submit);
			$buttons[] = $form->generate_reset_button($lang->vipmembership_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == "edituser")
		{
			$page->output_nav_tabs($sub_tabs, 'vipmembership_edit');

			$mid = intval($mybb->input['mid']);
			$query = $db->simple_select('vipmembership_memberships', '*', 'mid='.$mid);
			$member = $db->fetch_array($query);
			if (!$member)
			{
				flash_message($lang->vipmembership_invalid_member, 'error');
				admin_redirect("index.php?module=user-vipmembership");
			}

			$member['username'] = vipmembership_get_username($member['uid']);

			$newgroups[0] = $lang->vipmembership_nogroup;
			$endgroups[0] = $lang->vipmembership_curgroup;

			// get groups
			$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
			while($group = $db->fetch_array($query, 'title, gid'))
			{
				$endgroups[$group['gid']] = $group['title'];
				$newgroups[$group['gid']] = $group['title'];
			}

			$form = new Form("index.php?module=user-vipmembership&amp;action=do_edituser", "post", "vipmembership");

			echo $form->generate_hidden_field('mid', $mid);

			$form_container = new FormContainer($lang->vipmembership_editmember);
			$form_container->output_row($lang->vipmembership_user_newgroup, $lang->vipmembership_user_newgroup_desc, $form->generate_select_box('newgroup', $newgroups, $member['newgroup'], array('id' => 'newgroup')), 'newgroup');
			$form_container->output_row($lang->vipmembership_user_endgroup, $lang->vipmembership_user_endgroup_desc, $form->generate_select_box('endgroup', $endgroups, $member['endgroup'], array('id' => 'endgroup')), 'endgroup');
			$form_container->output_row($lang->vipmembership_user_years, $lang->vipmembership_user_years_desc, $form->generate_text_box('years', $member['years'], array('id' => 'years')), 'years');
			$form_container->output_row($lang->vipmembership_user_months, $lang->vipmembership_user_months_desc, $form->generate_text_box('months', $member['months'], array('id' => 'months')), 'months');
			$form_container->output_row($lang->vipmembership_user_days, $lang->vipmembership_user_days_desc, $form->generate_text_box('days', $member['days'], array('id' => 'days')), 'days');
			$form_container->output_row($lang->vipmembership_user_hours, $lang->vipmembership_user_hours_desc, $form->generate_text_box('hours', $member['hours'], array('id' => 'hours')), 'hours');
			$form_container->output_row($lang->vipmembership_user_minutes, $lang->vipmembership_user_minutes_desc, $form->generate_text_box('minutes', $member['minutes'], array('id' => 'minutes')), 'minutes');
			$form_container->output_row($lang->vipmembership_user_seconds, $lang->vipmembership_user_seconds_desc, $form->generate_text_box('seconds', $member['seconds'], array('id' => 'seconds')), 'seconds');
			$form_container->output_row($lang->vipmembership_user_additionalgroup, $lang->vipmembership_user_additionalgroup_desc, $form->generate_yes_no_radio('additional', $member['additional'], true, "", ""), 'additional');
			$form_container->output_row($lang->vipmembership_user_note, $lang->vipmembership_user_note_desc, $form->generate_text_area('note', htmlspecialchars_uni($member['note']), array('id' => 'note')), 'note');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->vipmembership_submit);
			$buttons[] = $form->generate_reset_button($lang->vipmembership_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == "deleteuser")
		{
			$mid = intval($mybb->input['mid']);
			$query = $db->simple_select('vipmembership_memberships', '*', 'mid='.$mid);
			$member = $db->fetch_array($query);
			if (!$member)
			{
				flash_message($lang->vipmembership_invalid_member, 'error');
				admin_redirect("index.php?module=user-vipmembership");
			}

			$db->delete_query('vipmembership_memberships', 'mid='.$mid, 1);

			flash_message($lang->vipmembership_ended_user, 'success');
			admin_redirect("index.php?module=user-vipmembership");
		}

		$page->output_footer();
		exit;
	}
}

?>
