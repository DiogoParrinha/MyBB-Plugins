<?php
/***************************************************************************
 *
 *  First Post Only plugin (/inc/plugins/firstpostonly.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2009-2012 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  Certain users groups can only view the first post of threads in certain forums.
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
$plugins->add_hook("postbit", "firstpostonly_post");
$plugins->add_hook("printthread_post", "firstpostonly_print");
$plugins->add_hook("archive_thread_start", "firstpostonly_archive");

/* TODO: Archive, Print Thread, New Reply */

function firstpostonly_info()
{
	return array(
		"name"			=> "First Post Only",
		"description"	=> "Certain users groups can only view the first post of threads in certain forums.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.2",
		"guid" 			=> "719e378f19d93d9513d7616b7b5ae867",
		"compatibility"	=> "18*"
	);
}


function firstpostonly_activate()
{
	global $db, $lang;
	// create settings group
	$insertarray = array(
		'name' => 'firstpostonly',
		'title' => 'First Post Only',
		'description' => "Settings for First Post Only plugin.",
		'disporder' => 150,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting0 = array(
		"sid"			=> NULL,
		"name"			=> "firstpostonly_groups",
		"title"			=> "User Groups",
		"description"	=> "Enter the group id\'s of the user groups that you want to be affected by this plugin. (set to \'all\' (without quotes) if you want all user groups to be affected)",
		"optionscode"	=> "text",
		"value"			=> "all",
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting0);

	// add settings
	$setting1 = array(
		"sid"			=> NULL,
		"name"			=> "firstpostonly_forums",
		"title"			=> "Forums",
		"description"	=> "Enter the forum id\'s of the forums that you want to be affected by this plugin (set to \'all\' (without quotes) if you want to affect all forums)",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting1);

	rebuild_settings();

	$templatearray = array(
		"tid" => "NULL",
		"title" => "firstpostonly",
		"template" => $db->escape_string('
<br />
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<thead>
			<tr>
				<td class="thead" colspan="2">
					<div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse{$collapsedimg[\'firstpostonly\']}.png" id="firstpostonly_img" class="expander" alt="[-]" title="[-]" /></div>
					<div><strong>{$lang->firstpostonly_nopermission}</strong></div>
				</td>
			</tr>
		</thead>
		<tbody style="{$collapsed[\'firstpostonly_e\']}" id="firstpostonly_e">
			<tr>
				<td class="trow1">
					{$lang->firstpostonly_message}
				</td>
			</tr>
		</tbody>
	</table><br />'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	$templatearray = array(
		"tid" => "NULL",
		"title" => "firstpostonly_print",
		"template" => $db->escape_string('
<strong>{$lang->firstpostonly_nopermission}</strong>
<br />
<br />
{$lang->firstpostonly_message}
<br />
<br />
<hr size="1" />'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);
}


function firstpostonly_deactivate()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'firstpostonly'");

	// remove settings
	$db->delete_query('settings', 'name IN ( \'firstpostonly_forums\',\'firstpostonly_groups\')');
	rebuild_settings();

	$db->delete_query('templates', 'title IN ( \'firstpostonly\',\'firstpostonly_print\')');
}

function firstpostonly_check_permissions($groups_comma)
{
	global $mybb;

	if ($mybb->settings['firstpostonly_groups'] == 'all')
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

function firstpostonly_post(&$post)
{
	global $lang, $templates, $mybb, $theme, $fid, $thread;

	if (!firstpostonly_check_permissions($mybb->settings['firstpostonly_groups']))
		return;

	if($mybb->settings['firstpostonly_forums'] == '')
		return;

	if($mybb->settings['firstpostonly_forums'] != 'all')
	{
		$forums = explode(',', $mybb->settings['firstpostonly_forums']);
		if(!in_array($fid, $forums))
		{
			return;
		}
	}

	if($thread['firstpost'] == $post['pid'])
		return;

	$lang->load("firstpostonly");

	// Alter cached templates to our own

	static $firstpostonly;

	if(isset($firstpostonly))
	{
		$templates->cache['postbit_classic'] = '';
		$templates->cache['postbit'] = '';
		return;
	}

	eval("\$firstpostonly = \"".$templates->get("firstpostonly")."\";");

	$templates->cache['postbit_classic'] = $firstpostonly;
	$templates->cache['postbit'] = $firstpostonly;
}

function firstpostonly_print()
{
	global $lang, $templates, $mybb, $theme, $fid, $thread, $postrow;

	if (!firstpostonly_check_permissions($mybb->settings['firstpostonly_groups']))
		return;

	if($mybb->settings['firstpostonly_forums'] == '')
		return;

	if($mybb->settings['firstpostonly_forums'] != 'all')
	{
		$forums = explode(',', $mybb->settings['firstpostonly_forums']);
		if(!in_array($fid, $forums))
		{
			return;
		}
	}

	if($thread['firstpost'] == $postrow['pid'])
		return;

	$lang->load("firstpostonly");

	// Alter cached templates to our own

	static $firstpostonly;

	if(isset($firstpostonly))
	{
		$templates->cache['printthread_post'] = '';
		return;
	}

	eval("\$firstpostonly = \"".$templates->get("firstpostonly_print")."\";");

	$templates->cache['printthread_post'] = $firstpostonly;
}

function firstpostonly_archive()
{
	global $lang, $db, $mybb, $plugins, $forum, $thread;

	if (!firstpostonly_check_permissions($mybb->settings['firstpostonly_groups']))
		return;

	if($mybb->settings['firstpostonly_forums'] == '')
		return;

	if($mybb->settings['firstpostonly_forums'] != 'all')
	{
		$forums = explode(',', $mybb->settings['firstpostonly_forums']);
		if(!in_array($forum['fid'], $forums))
		{
			return;
		}
	}

	$lang->load("firstpostonly");

	$pids = array();
	// Try to grab the post. We will only use this for validation purposes
	$query = $db->simple_select("posts", "pid", "visible='1' AND pid='{$thread['firstpost']}'");
	$post = $db->fetch_array($query);
	$pids[$post['pid']] = $post['pid'];

	if(empty($pids))
	{
		archive_error($lang->error_invalidthread);
	}

	global $acache, $parser;

	$acache = array();

	// Build attachments cache
	$query = $db->simple_select("attachments", "*", "pid = '{$thread['firstpost']}'");
	while($attachment = $db->fetch_array($query))
	{
		$acache[$attachment['pid']][$attachment['aid']] = $attachment;
	}

	// Start fetching the posts
	$query = $db->query("
		SELECT u.*, u.username AS userusername, p.*
		FROM ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
		WHERE p.pid = '{$thread['firstpost']}'
		ORDER BY p.dateline
	");
	$post = $db->fetch_array($query);

	$post['date'] = my_date($mybb->settings['dateformat'].", ".$mybb->settings['timeformat'], $post['dateline'], "", 0);
	if($post['userusername'])
	{
		$post['username'] = $post['userusername'];
	}

	// Parse the message
	$parser_options = array(
		"allow_html" => $forum['allowhtml'],
		"allow_mycode" => $forum['allowmycode'],
		"allow_smilies" => $forum['allowsmilies'],
		"allow_imgcode" => $forum['allowimgcode'],
		"allow_videocode" => $forum['allowvideocode'],
		"me_username" => $post['username'],
		"filter_badwords" => 1
	);
	if($post['smilieoff'] == 1)
	{
		$parser_options['allow_smilies'] = 0;
	}

	$post['message'] = $parser->parse_message($post['message'], $parser_options);

	// Is there an attachment in this post?
	if(is_array($acache[$post['pid']]))
	{
		foreach($acache[$post['pid']] as $aid => $attachment)
		{
			$post['message'] = str_replace("[attachment={$attachment['aid']}]", "[<a href=\"".$mybb->settings['bburl']."/attachment.php?aid={$attachment['aid']}\">attachment={$attachment['aid']}</a>]", $post['message']);
		}
	}

	// Damn thats a lot of parsing, now to determine which username to show..
	if($post['userusername'])
	{
		$post['username'] = $post['userusername'];
	}
	$post['username'] = build_profile_link($post['username'], $post['uid']);

	$plugins->run_hooks("archive_thread_post");

	// Finally show the post
	echo "<div class=\"post\">\n<div class=\"header\">\n<div class=\"author\"><h2>{$post['username']}</h2></div>";
	echo "<div class=\"dateline\">{$post['date']}</div>\n</div>\n<div class=\"message\">{$post['message']}</div>\n</div>\n";

	echo "<div class=\"post\">\n<div class=\"header\">\n<div class=\"author\"><h2>{$lang->firstpostonly_nopermission}</h2></div>";
	echo "<div class=\"message\">{$lang->firstpostonly_message}</div>\n</div>\n";

	$plugins->run_hooks("archive_thread_end");

	archive_footer();

	$plugins->run_hooks("archive_end");

	exit;
}

?>
