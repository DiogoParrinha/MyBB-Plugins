<?php
/***************************************************************************
 *
 *  View Groups plugin (/inc/plugins/viewgroups.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  Displays the list of user groups on index page.
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
$plugins->add_hook("index_start", "viewgroups_index");

if(THIS_SCRIPT == 'index.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'viewgroups_index';
}

function viewgroups_info()
{
	return array(
		"name"			=> "View Groups",
		"description"	=> "Displays the list of user groups on index page.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.6",
		"guid" 			=> "8bacd9894791da8dac2ccc6c89f590ce",
		"compatibility"	=> "18*"
	);
}


function viewgroups_activate()
{
	global $db, $lang;
	// create settings group
	$insertarray = array(
		'name' => 'viewgroups',
		'title' => 'View Groups',
		'description' => "Settings for View Groups",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);
	// add settings

	$setting0 = array(
		"sid"			=> NULL,
		"name"			=> "viewgroups_groups",
		"title"			=> "Hidden User Groups",
		"description"	=> "Enter the group id\'s (seperated by a comma) of the groups that you don\'t want to be displayed on the index page. (leave blank to disable this feature)",
		"optionscode"	=> "text",
		"value"			=> "1,5,7",
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting0);

	$setting1 = array(
		"sid"			=> NULL,
		"name"			=> "viewgroups_perpage",
		"title"			=> "Per Page",
		"description"	=> "Display how many users per page?",
		"optionscode"	=> "text",
		"value"			=> "15",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting1);

	$setting2 = array(
		"sid"			=> NULL,
		"name"			=> "viewgroups_clickable",
		"title"			=> "Clickable User Groups",
		"description"	=> "Set to Yes if you want group names to be clickable, opening a new page which shows the users of the clicked group.",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting2);

	rebuild_settings();

	// add templates
	$template0 = array(
		"tid" => "NULL",
		"title" => "viewgroups_index",
		"template" => $db->escape_string('
<tr>
	<td class="tcat"><strong>{$lang->viewgroups_groups}</strong></td>
</tr>
<tr>
	<td class="trow1"><span class="smalltext">{$usergroups}</span></td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template0);

	$template1 = array(
		"tid" => "NULL",
		"title" => "viewgroups_user",
		"template" => $db->escape_string('
<tr>
<td class="{$bgcolor}" align="center">{$user[\'avatar\']}</td>
<td class="{$bgcolor}">{$user[\'username\']}</td>
<td class="{$bgcolor}" align="center">{$user[\'regdate\']}</td>
<td class="{$bgcolor}" align="center">{$user[\'lastactive\']}</td>
<td class="{$bgcolor}" align="center">{$user[\'postnum\']}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template1);

	$template2 = array(
		"tid" => "NULL",
		"title" => "viewgroups",
		"template" => $db->escape_string('
<html>
	<head>
	<title>{$title}</title>
	{$headerinclude}
	</head>
	<body>
	{$header}
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<tbody>
				<tr class="thead">
					<td colspan="5">
						{$lang->viewgroups_groups_group}
					</td>
				</tr>
				<tr class="tcat">
					<td width="1%" align="center"><strong>{$lang->viewgroups_groups_avatar}</strong></td>
					<td><strong>{$lang->viewgroups_groups_username}</strong></td>
					<td width="15%" align="center"><strong>{$lang->viewgroups_groups_regdate}</strong></td>
					<td width="15%" align="center"><strong>{$lang->viewgroups_groups_lastactive}</strong></td>
					<td width="10%" align="center"><strong>{$lang->viewgroups_groups_postcount}</strong></td>
				</tr>
				{$users}
			</tbody>
		</table>
		{$multipage}
		{$footer}
	</body>
</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template2);

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

	find_replace_templatesets('index_boardstats', '#'.preg_quote('{$birthdays}').'#', '{$birthdays}'."\n".'{$viewgroups}');

}


function viewgroups_deactivate()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'viewgroups'");

	// remove settings
	$db->delete_query('settings', 'name IN ( \'viewgroups_groups\',\'viewgroups_perpage\',\'viewgroups_clickable\')');

	rebuild_settings();

	// delete templates
	$db->delete_query('templates', 'title IN ( \'viewgroups_index\',\'viewgroups\',\'viewgroups_user\')');

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('index_boardstats', '#'.preg_quote("\n".'{$viewgroups}').'#', "", 0);

}

function viewgroups_index()
{
	global $mybb, $lang, $db, $viewgroups, $usergroups, $templates, $header, $footer, $headerinclude, $title, $theme;

	$gid = intval($mybb->input['gid']);

	if ($mybb->input['action'] != "viewgroups" || ($mybb->input['action'] != "viewgroups" && $gid <= 0))
	{
		$lang->load("viewgroups");

		$usergroups = $comma = '';

		$query = $db->simple_select("usergroups", "gid,title,namestyle", "gid NOT IN ('".str_replace(',', '\',\'', $mybb->settings['viewgroups_groups'])."')");
		while ($group = $db->fetch_array($query))
		{
			if ($mybb->settings['viewgroups_clickable'] != 1)
				$usergroups .= $comma.str_replace('{username}', htmlspecialchars_uni($group['title']), $group['namestyle']);
			else
				$usergroups .= $comma.'<a href="'.$mybb->settings['bburl'].'/index.php?action=viewgroups&amp;gid='.$group['gid'].'">'.str_replace('{username}', htmlspecialchars_uni($group['title']), $group['namestyle']).'</a>';

			$comma = ', ';
		}

		eval("\$viewgroups = \"".$templates->get("viewgroups_index")."\";");
	}
	else {

		if ($mybb->settings['viewgroups_clickable'] != 1)
			error_no_permission();

		if (in_array($gid, explode(',', $mybb->settings['viewgroups_groups'])))
			error_no_permission();

		global $users, $user, $bgcolor, $multipage;

		$users = '';

		$lang->load("viewgroups");

		$query = $db->simple_select("usergroups", "title", "gid=".$gid);
		$group_title = $db->fetch_field($query, 'title');
		$title = $lang->sprintf($lang->viewgroups_groups_group, htmlspecialchars_uni($group_title));
		add_breadcrumb($lang->viewgroups_groups_nav, 'index.php');
		add_breadcrumb(htmlspecialchars_uni($group_title), 'index.php?action=viewgroups&gid='.$gid);
		$lang->viewgroups_groups_group = '<strong>'.$lang->sprintf($lang->viewgroups_groups_group, htmlspecialchars_uni($group_title)).'</strong>';

		// pagination
		$per_page = $mybb->settings['viewgroups_perpage'];
		$mybb->input['page'] = intval($mybb->input['page']);
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

		$total_rows = 0;

		$shownleaderssep = $shownregularsep = false;

		switch($db->type)
		{
			case "pgsql":
			case "sqlite3":
			case "sqlite2":
				$additional_sql .= " OR ','||additionalgroups||',' LIKE '%,{$gid},%'";
				break;
			default:
				$additional_sql .= "OR CONCAT(',',additionalgroups,',') LIKE '%,{$gid},%'";
		}
		$search_sql .= " (usergroup='{$gid}' {$additional_sql})";

		// total users
		$total_rows = $db->fetch_field($db->simple_select("users", "COUNT(uid) as users", $search_sql), "users");

		$users = array();

		// get group members
		$query = $db->simple_select("users", "*",$search_sql, array('limit' => "{$start}, {$per_page}"));
		while ($user = $db->fetch_array($query))
		{
			// make sure group we're viewing belongs to additional groups if the primary group is not the group we're viewing
			/*if ($user['additionalgroups'] != '' && $user['usergroup'] != $gid)
			{
				if (!in_array($gid, explode(',', $user['additionalgroups'])))
				{
					$total_rows--; // maintain a proper count
					continue;
				}
			}*/

			$users[$user['uid']] = $user;
			$users[$user['uid']]['isleader'] = 0;
		}

		$leaders = array();

		// get leaders
		$query = $db->simple_select("groupleaders", "*", 'gid='.$gid);
		while ($leader = $db->fetch_array($query))
		{
			$leaders[$leader['uid']] = $leader;

			// leader is member of the group so we can just use the data we got from the users query
			if ($users[$leader['uid']])
				$leaders[$leader['uid']] = $users[$leader['uid']];
			else // leader is not member of the group so we have to get the data here
				$leaders[$leader['uid']] = get_user($leader['uid']);

			$leaders[$leader['uid']]['isleader'] = 1;
		}

		if (!$users && !$leaders)
		{
			// no members and and no leaders found
			$users = '<tr><td colspan="5" class="trow1">'.$lang->viewgroups_usersnotfound.'</td></tr>';
		}
		else {
			$members = array();

			// leaders come first
			if ($leaders)
			{
				foreach ($leaders as $leader)
				{
					$members[] = $leader;
				}

				$leadersep = '<tr><td colspan="5" class="trow_sep"><strong>'.$lang->viewgroups_leaders.'</strong></td></tr>';
			}
			else
				$leadersep = '';

			if ($users)
			{
				foreach ($users as $user)
				{
					if ($leaders[$user['uid']]) // remove group leaders from the regular members list
						continue;
					$members[] = $user;
				}

				$regularsep = '<tr><td colspan="5" class="trow_sep"><strong>'.$lang->viewgroups_members.'</strong></td></tr>';
			}
			else
				$regularsep = '';

			$users = '';

			if ($members)
			{
				foreach ($members as $user)
				{
					// show group leaders seperator if this is the first leader and if we have any leaders
					if ($user['isleader'] == 1 && $shownleaderssep === false)
					{
						$users .= $leadersep;
						$shownleaderssep = true;
					}
					// show regular members seperator if this is the first member and if there is group leader seperator
					elseif ($user['isleader'] == 0 && $shownregularsep === false && $shownleaderssep === true)
					{
						$users .= $regularsep;
						$shownregularsep = true;
					}

					$bgcolor = alt_trow();

					if ($user['avatar'])
						$user['avatar'] = '<img src="'.htmlspecialchars_uni($user['avatar']).'" width="70" height="70" />';
					else
						$user['avatar'] = '';

					$user['username'] = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']);
					$user['regdate'] = my_date($mybb->settings['regdateformat'], $user['regdate']);
					$user['postnum'] = intval($user['postnum']);

					if($user['lastvisit'])
					{
						$user['lastactive'] = my_date($mybb->settings['dateformat'], $user['lastvisit']);
						$user['lastactive'] .= ', ';
						$user['lastactive'] .= my_date($mybb->settings['timeformat'], $user['lastvisit']);
					}
					else
					{
						$user['lastactive'] = $lang->lastvisit_never;
					}

					eval("\$users .= \"".$templates->get("viewgroups_user")."\";");
				}
			}
			else
				$users = '<tr><td colspan="5" class="trow1">'.$lang->viewgroups_usersnotfound.'</td></tr>';
		}

		// multi-page
		if ($total_rows > $per_page)
			$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/index.php?action=viewgroups&amp;gid={$gid}");

		eval("\$group_page = \"".$templates->get("viewgroups")."\";");

		output_page($group_page);
		exit;
	}
}

?>
