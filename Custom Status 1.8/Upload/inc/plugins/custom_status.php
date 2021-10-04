<?php
/***************************************************************************
 *
 *  Custom Status plugin (/inc/plugins/custom_status.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  
 *  License: license.txt
 *
 *  This plugin allows users to set a custom status which appears on index, profile and posts.
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

// cache templates
if(THIS_SCRIPT == 'index.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'index_custom_status,index_custom_status_latest,member_profile_custom_status_change';
}
elseif(THIS_SCRIPT == 'usercp.php' && $mybb->input['action'] == 'profile')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'usercp_profile_custom_status';
}
elseif(THIS_SCRIPT == 'member.php' && $mybb->input['action'] == 'profile')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'member_profile_custom_status,member_profile_custom_status_change';
}

// add hooks
$plugins->add_hook('global_start', 'custom_status_start');
$plugins->add_hook('xmlhttp', 'custom_status_xmlhttp');
$plugins->add_hook('member_profile_end', 'custom_status_profile');
$plugins->add_hook('usercp_profile_start', 'custom_status_usercp_profile');
$plugins->add_hook('usercp_do_profile_end', 'custom_status_usercp_do_profile');
$plugins->add_hook("postbit", "custom_status_postbit");
$plugins->add_hook("index_start", "custom_status_index");

function custom_status_info()
{
	return array(
		"name"			=> "Custom Status",
		"description"	=> "This plugin allows users to set a custom status which appears on index, profile and posts.",
		"website"		=> "",
		"author"		=> "Diogo Parrinha",
		"authorsite"	=> "",
		"version"		=> "1.8",
		"guid" 			=> "81307cf4fc98bb7e9ab3aa597b864cb0",
		"compatibility"	=> "18*"
	);
}


function custom_status_install()
{
	global $db, $lang;
	// create settings group
	$insertarray = array(
		'name' => 'custom_status',
		'title' => 'Custom Status',
		'description' => "Settings for Custom Status plugin.",
		'disporder' => 150,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting0 = array(
		"sid"			=> NULL,
		"name"			=> "custom_status_groups",
		"title"			=> "User Groups",
		"description"	=> "Enter the group id\'s of the user groups that can set a custom status. (set to \'all\' (without quotes) if you want to allow all user groups to use this feature.)",
		"optionscode"	=> "text",
		"value"			=> "4",
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting0);

	// add settings
	$setting1 = array(
		"sid"			=> NULL,
		"name"			=> "custom_status_latest",
		"title"			=> "Latest Status Changes",
		"description"	=> "How many status updates do you want to show on index? (leave empty to disable this feature)",
		"optionscode"	=> "text",
		"value"			=> "5",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting1);

	rebuild_settings();

	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `custom_status` VARCHAR(300) NOT NULL DEFAULT '';");
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `custom_status_date` VARCHAR(300) NOT NULL DEFAULT '';");
}

function custom_status_is_installed()
{
	global $db;

	if ($db->field_exists('custom_status', 'users')) return true;

	return false;
}

function custom_status_activate()
{
	global $db, $lang, $mybb;

	$templatearray = array(
		"tid" => "NULL",
		"title" => "usercp_profile_custom_status",
		"template" => $db->escape_string('
<br />
<fieldset class="trow2">
<legend><strong>{$lang->custom_status}</strong></legend>
<table cellspacing="0" cellpadding="{$theme[\'tablespace\']}" width="100%">
<tr>
<td colspan="2"><span class="smalltext"><a style="text-decoration: none;" title="{$lang->custom_status_option_desc}">{$lang->custom_status_option}</a></span></td>
</tr>
<tr>
<td colspan="2">
<input name="custom_status" type="text" class="textbox" value="{$custom_status_user}">
</td>
</tr>
</table>
</fieldset>'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	$templatearray = array(
		"tid" => "NULL",
		"title" => "member_profile_custom_status",
		"template" => $db->escape_string('<strong>{$lang->custom_status_doing}</strong> <span id="custom_status">{$memprofile[\'custom_status\']}</span>{$change}'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	$templatearray = array(
		"tid" => "NULL",
		"title" => "member_profile_custom_status_change",
		"template" => $db->escape_string('(<a onclick="Custom_Status.get_new();" href="javascript: void(0);">{$lang->custom_status_change}</a>) <div id="customstatus_spinner" class="modal-spinner" style="display: none"></div><span id="custom_status_changed_success" style="color: #00b200; font-weight: bold; font-size: 10px; margin-bottom: 10px;">&nbsp;</div>'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	/*$templatearray = array(
		"tid" => "NULL",
		"title" => "index_custom_status",
		"template" => $db->escape_string('<div style="background: #F8FAFC; text-align: center; margin-left: 5px; margin-right: 5px; padding: 13px 20px 13px 45px; border-top: 2px solid #B5D4FE; border-bottom: 2px solid #B5D4FE; line-height: 150%; margin-top: 5px; margin-bottom: 5px;"><strong>{$lang->custom_status_doing2}</strong> <span id="custom_status">{$mybb->user[\'custom_status\']}</span>{$change}</div>'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);*/

	$templatearray = array(
		"tid" => "NULL",
		"title" => "index_custom_status",
		"template" => $db->escape_string('
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->custom_status_latest_status}</strong></td>
</tr>
<tr>
<td class="tcat"><strong>{$lang->custom_status_doing2}</strong> <span id="custom_status">{$mybb->user[\'custom_status\']}</span>{$change}</td>
</tr>
{$lateststatus}
</table><br />
		'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	$templatearray = array(
		"tid" => "NULL",
		"title" => "index_custom_status_latest",
		"template" => $db->escape_string('
<tr>
<td class="{$bgcolor}">{$userstatus[\'username\']} {$lang->custom_status_updated_status_on} {$userstatus[\'date\']}<br /><span class="smalltext"><strong>{$lang->custom_status_new_status}:</strong> {$userstatus[\'custom_status\']}</span></td>
</tr>
		'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

	find_replace_templatesets('usercp_profile', '#'.preg_quote('{$customfields}').'#', '{$customfields}{$custom_status}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$online_status}').'#', '{$online_status}'."\n".'{$custom_status}');
	find_replace_templatesets('headerinclude', '#'.preg_quote('{$stylesheets}').'#', '{$stylesheets}'."\n".'<script language="javascript" type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/custom_status.js"></script>');
	find_replace_templatesets('index', '#'.preg_quote('{$forums}').'#', '{$custom_status_index}'."\n".'{$forums}');
}


function custom_status_uninstall()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'custom_status'");

	// remove settings
	$db->delete_query('settings', 'name IN ( \'custom_status_groups\',\'custom_status_latest\')');
	rebuild_settings();

	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `custom_status`;");
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `custom_status_date`;");
}

function custom_status_deactivate()
{
	global $db, $mybb;

	$db->delete_query('templates', 'title IN ( \'usercp_profile_custom_status\',\'member_profile_custom_status\',\'member_profile_custom_status_change\',\'index_custom_status\',\'index_custom_status_latest\')');

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('usercp_profile', '#'.preg_quote('{$custom_status}').'#', "", 0);
	find_replace_templatesets('member_profile', '#'.preg_quote("\n".'{$custom_status}').'#', '', 0);
	find_replace_templatesets('headerinclude', '#'.preg_quote("\n".'<script language="javascript" type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/custom_status.js"></script>').'#', '', 0);
	find_replace_templatesets('index', '#'.preg_quote("\n".'{$custom_status_index}').'#', '', 0);
}

function custom_status_check_permissions($groups_comma)
{
	global $mybb;

	if ($mybb->settings['custom_status_groups'] == 'all')
		return true;

	$groups = explode(",", $groups_comma);
	$add_groups = explode(",", $mybb->user['additionalgroups']);

	if (!in_array($mybb->user['usergroup'], $groups)) { // primary user group not allowed
		// check additional groups
		if ($add_groups) {
			if (count(array_intersect($add_groups, $groups)) == 0)
				return false;
			else
				return true;
		}
		else
			return false;
	}
	else
		return true;
}

function custom_status_profile()
{
	global $db, $mybb, $online_status, $memprofile, $session, $lang, $templates, $custom_status_user, $custom_status, $change;

	$lang->load("custom_status");

	if ($memprofile['uid'] == $mybb->user['uid'])
	{
		if (!custom_status_check_permissions($mybb->settings['custom_status_groups']) && $mybb->settings['custom_status_groups'] != 'all')
			$change = '&nbsp;';
		else
			eval("\$change = \"".$templates->get('member_profile_custom_status_change')."\";");
	}
	else
		$change = '&nbsp;';

	if ($memprofile['custom_status'] == '')
		$memprofile['custom_status'] = $lang->custom_status_not_set;

	$memprofile['custom_status'] = htmlspecialchars_uni($memprofile['custom_status']);
	eval("\$custom_status = \"".$templates->get("member_profile_custom_status")."\";");
}

function custom_status_usercp_profile()
{
	global $mybb, $db, $lang, $custom_status, $templates;

	if (!custom_status_check_permissions($mybb->settings['custom_status_groups']))
	{
		$custom_status = '';
		return;
	}
	else
	{
		$custom_status_user = htmlspecialchars_uni($mybb->user['custom_status']);
		$lang->load("custom_status");

		$lang->custom_status_option = $lang->custom_status_option2;
		$lang->custom_status_option_desc = $lang->custom_status_option2_desc;

		eval("\$custom_status = \"".$templates->get("usercp_profile_custom_status")."\";");
	}
}

function custom_status_usercp_do_profile()
{
	global $mybb, $db, $lang, $custom_status, $templates;

	if (!custom_status_check_permissions($mybb->settings['custom_status_groups']) && $mybb->settings['custom_status_groups'] != 'all')
		return;
	else
		$db->update_query("users", array('custom_status' => $db->escape_string($mybb->input['custom_status']), 'custom_status_date' => TIME_NOW), 'uid='.$mybb->user['uid']);
}

function custom_status_xmlhttp()
{
	global $mybb, $db, $lang;

	if ($mybb->input['action'] != 'change_custom_status')
		return;

	// Verify incoming POST request
	verify_post_check($mybb->input['my_post_key']);

	if ($mybb->user['uid'] <= 0)
		error_no_permission();

	if (!custom_status_check_permissions($mybb->settings['custom_status_groups']) && $mybb->settings['custom_status_groups'] != 'all')
		error_no_permission();

	$lang->load("custom_status");

	if ($mybb->input['status'] == "" || !isset($mybb->input['status']))
	{
		echo json_encode(array('error' => $lang->custom_status_error1));
	}
	else {
		$db->update_query("users", array('custom_status' => $db->escape_string($mybb->input['status']), 'custom_status_date' => TIME_NOW), 'uid='.$mybb->user['uid']);

		echo json_encode(array('success' => $lang->custom_status_success, 'status' => htmlspecialchars_uni($mybb->input['status'])));
	}

	exit;
}

function custom_status_start()
{
	global $mybb;

	if ($mybb->user['uid'] <= 0)
		return;

	// sanitize HTML as admins may want to place the status somewhere
	$mybb->user['custom_status'] = htmlspecialchars_uni($mybb->user['custom_status']);
}

function custom_status_postbit(&$post)
{
	global $lang, $change, $templates, $change;
	if ($post['custom_status'] == '')
	{
		$lang->load('custom_status');
		$post['custom_status'] = $lang->custom_status_not_set;
	}
	else {
		$lang->load('custom_status');
		$post['custom_status'] = htmlspecialchars_uni($post['custom_status']);
		// build a shortned one as well if it has more than 10 characters
		$post['custom_status_shortened'] = (strlen($post['custom_status']) > 13) ? substr($post['custom_status'],0,10).'...' : $post['custom_status'];
	}
}

function custom_status_index()
{
	global $mybb, $templates, $lang, $custom_status_index, $db, $theme;

	$custom_status_index = '';

	if ($mybb->user['uid'] <= 0)
		return;

	if (!custom_status_check_permissions($mybb->settings['custom_status_groups']) && $mybb->settings['custom_status_groups'] != 'all')
		return;

	if ($mybb->settings['custom_status_latest'] == '') return;

	$lang->load('custom_status');

	// sanitize HTML as admins may want to place the status somewhere
	// nevermind, no need to it here since we do it everywhere
	// $mybb->user['custom_status'] = htmlspecialchars_uni($mybb->user['custom_status']);

	if ($mybb->user['custom_status'] == '')
		$mybb->user['custom_status'] = $lang->custom_status_not_set;

	$change = '';
	eval("\$change = \"".$templates->get('member_profile_custom_status_change')."\";");

	$lateststatus = '';
	$query = $db->simple_select('users', 'uid,username,custom_status,custom_status_date', 'custom_status != \'\' AND custom_status_date > 0', array('order_by' => 'custom_status_date', 'order_dir' => 'DESC', 'limit' => $mybb->settings['custom_status_latest']));
	while ($userstatus = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();
		$userstatus['username'] = build_profile_link(htmlspecialchars_uni($userstatus['username']), $userstatus['uid']);
		$userstatus['date'] = my_date($mybb->settings['dateformat'], $userstatus['custom_status_date'], '', false).", ".my_date($mybb->settings['timeformat'], $userstatus['custom_status_date']);
		$userstatus['status'] = htmlspecialchars_uni($userstatus['custom_status']);
		$userstatus['custom_status'] = htmlspecialchars_uni($userstatus['custom_status']);

		eval("\$lateststatus .= \"".$templates->get('index_custom_status_latest')."\";");
	}

	eval("\$custom_status_index = \"".$templates->get('index_custom_status')."\";");
}

?>
