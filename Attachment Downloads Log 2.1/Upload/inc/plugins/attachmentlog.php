<?php
/***************************************************************************
 *
 *  Attachment Downloads Log (/inc/plugins/attachmentlog.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  Logs who downloads attachments.
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
$plugins->add_hook('admin_load', 'attachmentlog_admin');
$plugins->add_hook('admin_tools_menu', 'attachmentlog_admin_tools_menu');
$plugins->add_hook('admin_tools_action_handler', 'attachmentlog_admin_tools_action_handler');
$plugins->add_hook('admin_tools_permissions', 'attachmentlog_admin_permissions');

$plugins->add_hook('attachment_end', 'attachmentlog_log');
$plugins->add_hook('modcp_start', 'attachmentlog_modcp');

if(THIS_SCRIPT == 'modcp.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }

	if($GLOBALS['mybb']->input['action'] == 'attachmentlog')
		$templatelist .= 'attachmentlog,attachmentlog_row,attachmentlog_no_data,attachmentlog_nav';
	else
		$templatelist .= 'attachmentlog_nav';

}

function attachmentlog_info()
{
	return array(
		"name"			=> "Attachment Downloads Log",
		"description"	=> "Logs who downloads attachments.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "2.1",
		"guid" 			=> "ac6ebb542d35f0b99c2e3d6e8093e8b1",
		"compatibility"	=> "18*"
	);
}


function attachmentlog_install()
{
	global $db, $lang;
	// create settings group
	$insertarray = array(
		'name' => 'attachmentlog',
		'title' => 'Attachment Downloads Log',
		'description' => "Settings for Attachment Downloads Log plugin",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting = array(
		"sid"			=> NULL,
		"name"			=> "attachmentlog_forums",
		"title"			=> "Exempt Forums",
		"description"	=> "The ID\'s of the forums where attachment downloads are not logged. (blank if you want to log all forums)",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "attachmentlog_groups",
		"title"			=> "Exempt Groups",
		"description"	=> "The ID\'s of the user groups you do not want to log. (blank if you want to log all user groups)",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "attachmentlog_modcp_groups",
		"title"			=> "Allowed Groups",
		"description"	=> "The ID\'s of the user groups you want to be able to access the ModCP page.",
		"optionscode"	=> "text",
		"value"			=> "4",
		"disporder"		=> 3,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "attachmentlog_modcp_list",
		"title"			=> "Select box vs Text box",
		"description"	=> "Do you want to see a select box previously populated with all attachment names that were logged or do you want to be able to enter a search term? The former uses more processing power and more memory and it\'s definitely not recommended for boards with many attachments.",
		"optionscode"	=> "select
0=text
1=select",
		"value"			=> "0",
		"disporder"		=> 4,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "attachmentlog_modcp_exempt",
		"title"			=> "Groups Exempt from Showing",
		"description"	=> "The ID\'s of the user groups you do not want to show in the logs (e.g. a group that might have been logged in the past but you no longer want their logs to appear).",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 5,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "attachmentlog_aids_exempt",
		"title"			=> "Attachments Exempt from Showing",
		"description"	=> "The ID\'s of the attachments exempt from showing in the logs (including the attachements dropdown list).",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 6,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	rebuild_settings();

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."attachmentlog` (
	  `lid` bigint(30) UNSIGNED NOT NULL auto_increment,
	  `aid` bigint(30) UNSIGNED NOT NULL default '0',
	  `uid` bigint(30) UNSIGNED NOT NULL default '0',
	  `date` bigint(30) UNSIGNED NOT NULL default '0',
	  `atname` varchar(255) NOT NULL default '',
	  `username` varchar(100) NOT NULL default '',
	  PRIMARY KEY  (`lid`), KEY(`date`)
		) ENGINE=MyISAM");
}

function attachmentlog_activate()
{
	global $db;

	$template = array(
		"tid" => "NULL",
		"title" => "attachmentlog",
		"template" => $db->escape_string('<html>
	<head>
	<title>{$mybb->settings[\'bbname\']} - {$lang->attachmentlog}</title>
	{$headerinclude}
	</head>
	<body>
	{$header}
	<table width="100%" border="0" align="center">
		<tr>
			{$modcp_nav}
			<td valign="top">
				<form name="attachmentlog" action="modcp.php" method="GET">
					<input type="hidden" name="action" value="attachmentlog" />
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead" colspan="2">
								<strong>{$lang->attachmentlog_filters}</strong>
							</td>
						</tr>
						<tr>
							<td class="trow1" width="50%">
								<strong>{$lang->attachmentlog_attachmentid}:</strong>
							</td>
							<td class="trow1" width="50%">
								<input type="text" class="textbox" name="attachmentid" value="{$mybb->input[\'attachmentid\']}" />
							</td>
						</tr>
						<tr>
							<td class="trow1" width="50%">
								<strong>{$lang->attachmentlog_attachmentname}:</strong>
							</td>
							<td class="trow1" width="50%">
								{$searchinput}
							</td>
						</tr>
						<tr>
							<td class="trow1" width="50%">
								<strong>{$lang->attachmentlog_userid}:</strong>
							</td>
							<td class="trow1" width="50%">
								<input type="text" class="textbox" name="uid" value="{$mybb->input[\'uid\']}" />
							</td>
						</tr>
						<tr>
							<td class="trow1" width="50%">
								<strong>{$lang->attachmentlog_usergroups}:</strong>
							</td>
							<td class="trow1" width="50%">
								{$usergroups}
							</td>
						</tr>
						<tr>
							<td class="tfoot" colspan="2" align="center">
								<input type="submit" class="button" name="submit" value="{$lang->attachmentlog_filter}" />
							</td>
						</tr>
					</table>
				</form>
				<br />
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td class="thead" colspan="3">
						<strong>{$lang->attachmentlog}</strong></td>
					</tr>
					<tr>
						<td class="tcat" width="33%">
							<strong>{$lang->attachmentlog_downloads_user}</strong>
						</td>
						<td class="tcat" width="33%">
							<strong>{$lang->attachmentlog_downloads_attachment}</strong>
						</td>
						<td class="tcat" width="33%" align="center">
							<strong>{$lang->attachmentlog_downloads_date}</strong>
						</td>
					</tr>
					{$rows}
				</table>
				{$multipage}
			</td>
		</tr>
	</table>
	{$footer}
	</body>
	</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"tid" => "NULL",
		"title" => "attachmentlog_row",
		"template" => $db->escape_string('
				<tr>
					<td class="{$bgcolor}">
						{$log[\'username\']}
					</td>
					<td class="{$bgcolor}">
						{$log[\'attachment\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$log[\'date\']}
					</td>
				</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"tid" => "NULL",
		"title" => "attachmentlog_nav",
		"template" => $db->escape_string('
		<tr><td class="trow1 smalltext"><a href="modcp.php?action=attachmentlog" class="modcp_nav_item modcp_avattachmentlog">{$lang->attachmentlog}</td></tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"tid" => "NULL",
		"title" => "attachmentlog_no_data",
		"template" => $db->escape_string('
<tr>
	<td class="trow1" colspan="3">{$lang->attachmentlog_no_data}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"tid" => "NULL",
		"title" => "attachmentlog_list_dropdown",
		"template" => $db->escape_string('
<span class="smalltext">{$lang->attachmentlog_notice}</span><br />
{$attachlist}'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"tid" => "NULL",
		"title" => "attachmentlog_list_textbox",
		"template" => $db->escape_string('
<input type="text" class="textbox" name="attachmentname" value="{$mybb->input[\'attachmentname\']}" />'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('modcp_nav_forums_posts', '#'.preg_quote('{$nav_announcements}').'#', '{$nav_announcements}'.'{attachmentlog}');
}

function attachmentlog_is_installed()
{
	global $db;

	if ($db->table_exists('attachmentlog'))
		return true;
	else
		return false;
}

function attachmentlog_uninstall()
{
	global $db;

	// delete settings group
	$db->delete_query("settinggroups", "name = 'attachmentlog'");

	// remove settings
	$db->delete_query('settings', 'name IN ( \'attachmentlog_forums\',\'attachmentlog_groups\',\'attachmentlog_modcp_groups\',\'attachmentlog_aids_exempt\')');

	rebuild_settings();

	if ($db->table_exists('attachmentlog'))
		$db->drop_table('attachmentlog');
}

function attachmentlog_deactivate()
{
	global $db, $mybb;

	// delete templates
	$db->delete_query('templates', 'title LIKE \'%attachmentlog%\'');

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('modcp_nav_forums_posts', '#'.preg_quote('{attachmentlog}').'#', '', 0);
}

/* Checks if the primary or any of the additional groups of the current user are in the groups list passed as a parameter
 * @param String group ids seperated by a comma
 * @return true if the user has permissions, false if not
*/
function attachmentlog_check_permissions($groups_comma)
{
    global $mybb;

    if ($groups_comma == '')
        return false;

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

function attachmentlog_log()
{
	global $aid, $mybb, $db, $lang, $thread, $fid, $tid, $attachment;

	// thumbnails don't count
	if ($mybb->input['thumbnail'])
		return;

	$affected_fids = explode(',', $mybb->settings['attachmentlog_forums']);
	if (in_array($fid, $affected_fids))
		return;

	if (attachmentlog_check_permissions($mybb->settings['attachmentlog_groups']))
		return;

	if (!$mybb->user['username'])
		$mybb->user['username'] = 'Guest';

	// log download
	$insert_array = array(
		'aid' => intval($aid),
		'uid' => intval($mybb->user['uid']),
		'username' => $db->escape_string($mybb->user['username']),
		'date' => TIME_NOW,
		'atname' => $db->escape_string($attachment['filename'])
	);
	$db->insert_query('attachmentlog', $insert_array);
}

function attachmentlog_get_username($uid)
{
	global $db;
	$query = $db->simple_select('users', 'username', 'uid=\''.intval($uid).'\'', 1);
	return $db->fetch_field($query, 'username');
}

function attachmentlog_get_uid($username)
{
	global $db;
	$query = $db->simple_select('users', 'uid', 'username=\''.$db->escape_string($username).'\'', 1);
	return $db->fetch_field($query, 'uid');
}

function attachmentlog_modcp()
{
	global $mybb, $lang, $cache, $modcp_nav, $templates;

	$lang->load("attachmentlog");

	eval("\$attachmentlog = \"".$templates->get("attachmentlog_nav")."\";");
	$modcp_nav = str_replace('{attachmentlog}', $attachmentlog, $modcp_nav);

	if ($mybb->input['action'] != 'attachmentlog')
		return;

	if(!attachmentlog_modcp_check_permissions($mybb->settings['attachmentlog_modcp_groups']))
		error_no_permission();

	global $theme, $headerinclude, $header, $footer, $db;

	// Pagination
	$per_page = 15;
	$mybb->input['page'] = (int)$mybb->input['page'];
	if($mybb->input['page'] > 1)
	{
		$mybb->input['page'] = (int)$mybb->input['page'];
		$start = ($mybb->input['page']*$per_page)-$per_page;
	}
	else
	{
		$mybb->input['page'] = 1;
		$start = 0;
	}

	$url = '';
	$where = '';
	$selected_groups = array();
	$selected_aids = array();
	$and = '';

	if($mybb->settings['attachmentlog_modcp_exempt'] != '')
		$mybb->settings['attachmentlog_modcp_exempt'] = explode(',', $mybb->settings['attachmentlog_modcp_exempt']);
	else
		$mybb->settings['attachmentlog_modcp_exempt'] = array();

	if($mybb->settings['attachmentlog_aids_exempt'] != '')
		$mybb->settings['attachmentlog_aids_exempt'] = explode(',', $mybb->settings['attachmentlog_aids_exempt']);
	else
		$mybb->settings['attachmentlog_aids_exempt'] = array();

	// WHERE filters
	if(isset($mybb->input['uid']) && (int)$mybb->input['uid'] > 0)
	{
		$uid = (int)$mybb->input['uid'];
		$url .= '&amp;uid='.$uid;
		$where .= $and.'l.uid='.$uid.' ';
		$and = ' AND ';
	}

	if(isset($mybb->input['attachmentid']) && (int)$mybb->input['attachmentid'] > 0 && ($mybb->settings['attachmentlog_modcp_list'] == 0 || (int)$mybb->input['atids'] == 0))
	{
		$aid = (int)$mybb->input['attachmentid'];
		$url .= '&amp;attachmentid='.$aid;
		$where .= $and.'l.aid='.$aid.' ';
		$and = ' AND ';
	}
	elseif($mybb->settings['attachmentlog_modcp_list'] == 1 && isset($mybb->input['atids']) && is_array($mybb->input['atids']))
	{
		if(!empty($mybb->input['atids']))
		{
			$where .= $and.'l.aid IN (';

			$comma = '';

			foreach($mybb->input['atids'] as $aid)
			{
				if(in_array($gid, $mybb->settings['attachmentlog_aids_exempt']))
					continue;

				$aid = (int)$aid;
				$selected_aids[$aid] = $aid;
				$url .= '&amp;atids[]='.$aid;
				$where .= $comma.$aid;
				$comma = ',';
			}

			$where .= ')';

			$and = ' AND ';
		}
	}

	if(isset($mybb->input['attachmentname']) && $mybb->input['attachmentname'] != '' && $mybb->settings['attachmentlog_modcp_list'] == 0)
	{
		$atname = $mybb->input['attachmentname'];
		$url .= '&amp;attachmentname='.urlencode($aid);
		$where .= $and.'l.atname LIKE \'%'.$atname.'%\' ';
		$and = ' AND ';
	}
	if(isset($mybb->input['usergroups']) && is_array($mybb->input['usergroups']) && !$mybb->input['uid'])
	{
		if(!empty($mybb->input['usergroups']))
		{
			$where .= $and.'(u.usergroup IN (';
			$where2 = '';

			$or = '';
			$comma = '';

			foreach($mybb->input['usergroups'] as $gid)
			{
				if(in_array($gid, $mybb->settings['attachmentlog_modcp_exempt']))
					continue;

				$gid = (int)$gid;
				$selected_groups[$gid] = $gid;
				$url .= '&amp;usergroups[]='.$gid;
				$where .= $comma.$gid;
				$where2 .= $or."CONCAT(',',additionalgroups,',') LIKE '%,{$gid},%'";
				$comma = ',';
				$or = ' OR ';
			}

			$where .= ') OR ('.$where2.'))';

			$and = ' AND ';
		}
	}

	// Groups hide
	if(!empty($mybb->settings['attachmentlog_modcp_exempt']))
	{
		$where .= $and.'u.usergroup NOT IN (';
		$where2 = '';

		$comma = '';

		foreach($mybb->settings['attachmentlog_modcp_exempt'] as $gid)
		{
			$gid = (int)$gid;
			$where .= $comma.$gid;
			$where2 .= " AND CONCAT(',',additionalgroups,',') NOT LIKE '%,{$gid},%'";
			$comma = ',';
		}

		$where .= ')'.$where2;

		$and = ' AND ';
	}

	// Attachments hide
	if(!empty($mybb->settings['attachmentlog_aids_exempt']))
	{
		$where .= $and.'l.aid NOT IN (';
		$aids_not_in .= 'WHERE aid NOT IN (';

		$comma = '';

		foreach($mybb->settings['attachmentlog_aids_exempt'] as $aid)
		{
			$aid = (int)$aid;
			$aids_not_in .= $comma.$aid;
			$where .= $comma.$aid;
			$comma = ',';
		}

		$where .= ')';
		$aids_not_in .= ')';

		$and = ' AND ';
	}

	// Sort direction
	if(isset($mybb->input['sortusernamedir']))
	{
		// Show 'desc' in URL
		if($mybb->input['sortusernamedir'] == 'asc')
		{
			$sortusernamedir = 'desc';
			$sortdir = 'asc';
			$sortusername = $lang->attachmentlog_desc;
		}
		else
		{
			$sortusernamedir = 'asc';
			$sortdir = 'desc';
			$sortusername = $lang->attachmentlog_asc;
		}

		$lang->attachmentlog_downloads_user = "<a href=\"{$mybb->settings['bburl']}/modcp.php?action=attachmentlog&amp;sortusernamedir=asc\">{$lang->attachmentlog_downloads_user}</a> <span class=\"smalltext\">[<a href=\"{$mybb->settings['bburl']}/modcp.php?action=attachmentlog&amp;sortusernamedir={$sortusernamedir}\">{$sortusername}</a>]</span>";
		$lang->attachmentlog_downloads_date = "<a href=\"{$mybb->settings['bburl']}/modcp.php?action=attachmentlog&amp;sortdatedir=asc\">{$lang->attachmentlog_downloads_date}</a>";

		$sortby = 'u.username';
		$url .= '&amp;sortusernamedir='.$sortdir;
	}
	elseif(isset($mybb->input['sortdatedir']))
	{
		// Show 'desc' in URL
		if($mybb->input['sortdatedir'] == 'asc')
		{
			$sortdatedir = 'desc';
			$sortdir = 'asc';
			$sortdate = $lang->attachmentlog_desc;
		}
		else
		{
			$sortdatedir = 'asc';
			$sortdir = 'desc';
			$sortdate = $lang->attachmentlog_asc;
		}

		$lang->attachmentlog_downloads_date = "<a href=\"{$mybb->settings['bburl']}/modcp.php?action=attachmentlog&amp;sortdatedir=asc\">{$lang->attachmentlog_downloads_date}</a> <span class=\"smalltext\">[<a href=\"{$mybb->settings['bburl']}/modcp.php?action=attachmentlog&amp;sortdatedir={$sortdatedir}\">{$sortdate}</a>]</span>";
		$lang->attachmentlog_downloads_user = "<a href=\"{$mybb->settings['bburl']}/modcp.php?action=attachmentlog&amp;sortusernamedir=asc\">{$lang->attachmentlog_downloads_user}</a>";

		$sortby = 'l.date';
		$url .= '&amp;sortdatedir='.$sortdir;
	}
	else
	{
		// Default is date
		$sortby = 'l.date';
		$sortdir = 'desc';

		$sortdatedir = 'asc';
		$sortdate = $lang->attachmentlog_asc;

		$lang->attachmentlog_downloads_date = "<a href=\"{$mybb->settings['bburl']}/modcp.php?action=attachmentlog&amp;sortdatedir=asc\">{$lang->attachmentlog_downloads_date}</a>";
		$lang->attachmentlog_downloads_user = "<a href=\"{$mybb->settings['bburl']}/modcp.php?action=attachmentlog&amp;sortusernamedir=asc\">{$lang->attachmentlog_downloads_user}</a>";
	}

	if($where != '')
		$where = ' WHERE '.$where;

	// Total results
	$query = $db->query("
		SELECT COUNT(lid) as logs
		FROM ".TABLE_PREFIX."attachmentlog l
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		{$where}
	");
	$total = $db->fetch_field($query, "logs");

	// Build multipage
	if($total > 0)
	{
		$multipage = multipage($total, $per_page, $mybb->input['page'], "modcp.php?action=attachmentlog".$url);
	}

	$rows = '';
	$query = $db->query("
		SELECT u.*, u.username AS userusername, l.*
		FROM ".TABLE_PREFIX."attachmentlog l
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		{$where}
		ORDER BY {$sortby} {$sortdir} LIMIT {$start}, {$per_page}
	");
	while($log = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();

		$log['username'] = build_profile_link(htmlspecialchars_uni($log['username']), intval($log['uid'])); // user
		$log['attachment']= "<a href=\"".$mybb->settings['bburl']."/attachment.php?aid=".intval($log['aid'])."\">".htmlspecialchars_uni($log['atname'])."</a>"; // attachment
		$log['date'] = my_date($mybb->settings['dateformat'], intval($log['date']), '', false).", ".my_date($mybb->settings['timeformat'], intval($log['date'])); // date

		eval("\$rows .= \"".$templates->get("attachmentlog_row")."\";");
	}

	if($rows == '')
	{
		eval("\$rows = \"".$templates->get("attachmentlog_no_data")."\";");
	}

	global $cache;
	$groups = $cache->read("usergroups");
	uasort($groups, 'groupsort');

	// Build usergroups dropdown
	$usergroups = '<select name="usergroups[]" multiple>';
	foreach($groups as $group)
	{
		if(in_array($group['gid'], $mybb->settings['attachmentlog_modcp_exempt']))
			continue;

		if(empty($selected_groups))
		{
			$usergroups .= '<option value="'.(int)$group['gid'].'" selected="selected">'.htmlspecialchars_uni($group['title']).'</option>';
		}
		else
		{
			if(isset($selected_groups[$group['gid']]))
				$usergroups .= '<option value="'.(int)$group['gid'].'" selected="selected">'.htmlspecialchars_uni($group['title']).'</option>';
			else
				$usergroups .= '<option value="'.(int)$group['gid'].'">'.htmlspecialchars_uni($group['title']).'</option>';
		}
	}
	$usergroups .= '</select>';

	if($mybb->settings['attachmentlog_modcp_list'] == 1)
	{
		$attachlist = '<select name="atids[]" multiple>';
		$q = $db->query("
			SELECT *
			FROM `".TABLE_PREFIX."attachmentlog`
			{$aids_not_in}
			GROUP BY `atname`
			ORDER BY `atname` ASC
		");
		while($log = $db->fetch_array($q))
		{
			if(empty($selected_aids))
			{
				$attachlist .= '<option value="'.(int)$log['aid'].'">'.htmlspecialchars_uni($log['atname']).'</option>';
			}
			else
			{
				if(isset($selected_aids[$log['aid']]))
					$attachlist .= '<option value="'.(int)$log['aid'].'" selected="selected">'.htmlspecialchars_uni($log['atname']).'</option>';
				else
					$attachlist .= '<option value="'.(int)$log['aid'].'">'.htmlspecialchars_uni($log['atname']).'</option>';
			}
		}
		$attachlist .= '</select>';

		eval("\$searchinput = \"".$templates->get("attachmentlog_list_dropdown")."\";");
	}
	else
		eval("\$searchinput = \"".$templates->get("attachmentlog_list_textbox")."\";");

	if($mybb->input['uid'] == 0)
		$mybb->input['uid'] = '';

	eval("\$page = \"".$templates->get("attachmentlog")."\";");

	output_page($page);

	exit;
}
// MODCP END

function groupsort($a, $b)
{
    if (strnatcmp($a['title'], $b['title']) == 0) {
        return 0;
    }
    return (strnatcmp($a['title'], $b['title']) < 0) ? -1 : 1;
}

function attachmentlog_modcp_check_permissions($groups_comma)
{
	global $mybb;

	if ($groups_comma == 'all')
		return true;

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

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function attachmentlog_admin_tools_menu(&$sub_menu)
{
	global $lang;

	$lang->load('attachmentlog');
	$sub_menu[] = array('id' => 'attachmentlog', 'title' => $lang->attachmentlog_index, 'link' => 'index.php?module=tools-attachmentlog');
}

function attachmentlog_admin_tools_action_handler(&$actions)
{
	$actions['attachmentlog'] = array('active' => 'attachmentlog', 'file' => 'attachmentlog');
}

function attachmentlog_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	$lang->load("attachmentlog", false, true);
	$admin_permissions['attachmentlog'] = $lang->attachmentlog_canmanage;

}

function attachmentlog_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	$lang->load("attachmentlog", false, true);

	if($run_module == 'tools' && $action_file == 'attachmentlog')
	{
		if (!$mybb->input['action'])
		{
			$page->add_breadcrumb_item($lang->attachmentlog, 'index.php?module=tools-attachmentlog');

			$page->output_header($lang->attachmentlog);

			$sub_tabs['attachmentlog_downloads'] = array(
				'title'			=> $lang->attachmentlog_downloads,
				'link'			=> 'index.php?module=tools-attachmentlog',
				'description'	=> $lang->attachmentlog_downloads_desc
			);
		}

		if (!$mybb->input['action'])
		{
			$page->output_nav_tabs($sub_tabs, 'attachmentlog_downloads');

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

			if ((int)$mybb->input['uid'] > 0)
			{
				$uid = (int)$mybb->input['uid'];
				$url = '&amp;uid='.$uid;
				$where = 'uid='.$uid;
				$where2 = ' WHERE l.uid='.$uid.' ';
			}
			else
			{
				$url = '';
				$where = '';
			}

			$query = $db->simple_select("attachmentlog", "COUNT(lid) as logs", $where);
			$total_rows = $db->fetch_field($query, "logs");

			echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=tools-attachmentlog&amp;page={page}".$url);

			// table
			$table = new Table;
			$table->construct_header($lang->attachmentlog_downloads_user, array('width' => '30%'));
			$table->construct_header($lang->attachmentlog_downloads_attachment, array('width' => '30%'));
			$table->construct_header($lang->attachmentlog_downloads_date, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->attachmentlog_downloads_options, array('width' => '20%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT u.*, u.username AS userusername, l.*
				FROM ".TABLE_PREFIX."attachmentlog l
				LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
				{$where2}
				ORDER BY l.date DESC LIMIT {$start}, {$per_page}
			");

			while ($log = $db->fetch_array($query))
			{
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($log['username']), intval($log['uid']))); // user
				$table->construct_cell("<a href=\"".$mybb->settings['bburl']."/attachment.php?aid=".intval($log['aid'])."\">".htmlspecialchars_uni($log['atname'])."</a>"); // attachment
				$table->construct_cell(my_date($mybb->settings['dateformat'], intval($log['date']), '', false).", ".my_date($mybb->settings['timeformat'], intval($log['date'])), array('class' => 'align_center')); // date

				// options column
				$table->construct_cell("<a href=\"index.php?module=tools-attachmentlog&amp;action=delete_log&amp;lid=".intval($log['lid'])."\">".$lang->attachmentlog_delete."</a>", array('class' => 'align_center'));
				$table->construct_row();
			}

			if($table->num_rows() == 0)
			{
				$table->construct_cell($lang->attachmentlog_nodownloads, array('colspan' => 4));

				$table->construct_row();
			}

			$table->output($lang->attachmentlog_downloads);

			echo "<br />";

			$form = new Form("index.php?module=tools-attachmentlog&amp;action=prune", "post", "newpoints");

			echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

			$form_container = new FormContainer($lang->attachmentlog_prune);
			$form_container->output_row($lang->attachmentlog_prune_days, $lang->attachmentlog_prune_days_desc, $form->generate_text_box('days', 30, array('id' => 'days')), 'days');
			$form_container->end();

			$buttons = array();;
			$buttons[] = $form->generate_submit_button($lang->attachmentlog_submit);
			$buttons[] = $form->generate_reset_button($lang->attachmentlog_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();

		}
		elseif ($mybb->input['action'] == 'delete_log')
		{
			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=tools-attachmentlog");
			}

			if($mybb->request_method == "post")
			{
				if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
				{
					$mybb->request_method = "get";
					flash_message($lang->attachmentlog_error, 'error');
					admin_redirect("index.php?module=tools-attachmentlog");
				}

				if (!$db->fetch_field($db->simple_select('attachmentlog', 'aid', 'lid='.intval($mybb->input['lid']), array('limit' => 1)), 'aid'))
				{
					flash_message($lang->attachmentlog_log_invalid, 'error');
					admin_redirect('index.php?module=tools-attachmentlog');
				}
				else {
					$db->delete_query('attachmentlog', 'lid='.intval($mybb->input['lid']));
					flash_message($lang->attachmentlog_log_deleted, 'success');
					admin_redirect('index.php?module=tools-attachmentlog');
				}
			}
			else
			{
				$page->add_breadcrumb_item($lang->attachmentlog_downloads, 'index.php?module=tools-attachmentlog');

				$page->output_header($lang->attachmentlog_downloads);

				$mybb->input['lid'] = intval($mybb->input['lid']);
				$form = new Form("index.php?module=tools-attachmentlog&amp;action=delete_log&amp;lid={$mybb->input['lid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->attachmentlog_downloads_deleteconfirm}</p>\n";
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
				admin_redirect("index.php?module=tools-attachmentlog");
			}

			if($mybb->request_method == "post")
			{
				if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
				{
					$mybb->request_method = "get";
					flash_message($lang->attachmentlog_error, 'error');
					admin_redirect("index.php?module=tools-attachmentlog");
				}

				$db->delete_query('attachmentlog', 'date < '.(TIME_NOW - intval($mybb->input['days'])*60*60*24));
				flash_message($lang->attachmentlog_log_pruned, 'success');
				admin_redirect('index.php?module=tools-attachmentlog');
			}
			else
			{
				$page->add_breadcrumb_item($lang->attachmentlog_downloads, 'index.php?module=tools-attachmentlog');

				$page->output_header($lang->attachmentlog_downloads);

				$mybb->input['days'] = intval($mybb->input['days']);
				$form = new Form("index.php?module=tools-attachmentlog&amp;action=prune&amp;days={$mybb->input['days']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->attachmentlog_downloads_pruneconfirm}</p>\n";
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
		exit;
	}
}

?>
