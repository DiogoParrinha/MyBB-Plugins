<?php
/***************************************************************************
 *
 *  HTML in Posts plugin (/inc/plugins/htmlposts.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  This plugin adds the possibility to use HTML in posts.
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
$plugins->add_hook('parse_message_start', 'htmlposts_parse');

function htmlposts_info()
{
	return array(
		"name"			=> "HTML in Posts",
		"description"	=> "This plugin adds the possibility to use HTML in posts.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.7",
		"guid" 			=> "1e7c24cc5352de0fbc1e7be40ef1ad60",
		"compatibility"	=> "18*"
	);
}


function htmlposts_activate()
{
	global $db, $lang;

	// create settings group
	$insertarray = array(
		'name' => 'htmlposts',
		'title' => 'HTML in Posts',
		'description' => "Settings for HTML in Posts plugin.",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting = array(
		"sid"			=> NULL,
		"name"			=> "htmlposts_groups",
		"title"			=> "Allowed Groups",
		"description"	=> "Enter the group IDs that can use HTML in posts. (separated by a comma, can be blank to allow all)",
		"optionscode"	=> "text",
		"value"			=> '4',
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "htmlposts_uids",
		"title"			=> "Allowed Users",
		"description"	=> "Enter the user IDs of the users that can use HTML in posts. (separated by a comma, leave blank to disable this feature)<br />Note: overrides groups setting.",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "htmlposts_forums",
		"title"			=> "Affected Forums",
		"description"	=> "Enter the forum IDs that are affected by this plugin. (separated by a comma, can be blank if you want to affect all forums)",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 3,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	rebuild_settings();
}


function htmlposts_deactivate()
{
	global $db, $mybb;

	// delete settings group
	$db->delete_query("settinggroups", "name = 'htmlposts'");

	// remove settings
	$db->delete_query('settings', 'name IN (\'htmlposts_groups\',\'htmlposts_uids\',\'htmlposts_forums\')');

	rebuild_settings();
}

// checks permissions for a certain user
function htmlposts_check_permissions($groups_comma, $user)
{
	if ($groups_comma == '' || empty($user))
		return false;

	$groups = explode(",", $groups_comma);
	$add_groups = explode(",", $user['additionalgroups']);

	if (!in_array($user['usergroup'], $groups)) { // primary user group not allowed
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

function htmlposts_parse(&$message)
{
	global $mybb, $db;

	if(THIS_SCRIPT == 'portal.php')
	{
		global $announcement;
		$mypost =& $announcement;
	}
	else
	{
		global $post;
		$mypost =& $post;
	}

	if (empty($mypost))
		return; // we're not in postbit so get out of here

	$previewpost = false;

	// we're previewing a post
	if ($mybb->input['previewpost'] && (THIS_SCRIPT == "newthread.php" || THIS_SCRIPT == "newreply.php" || THIS_SCRIPT == "editpost.php"))
	{
		if (THIS_SCRIPT != "editpost.php")
		{
			global $fid;
			$mypost['fid'] = $fid; // no fid is set in $mypost['fid'] when previewing
			$mypost['usergroup'] = $mybb->user['usergroup'];
			$mypost['additionalgroups'] = $mybb->user['additionalgroups'];
			$previewpost = true;
		}
		else
		{
			global $fid;
			$mypost['fid'] = $fid; // no fid is set in $mypost['fid'] when previewing
		}

		$previewpost = true;
	}

	// if not blank, check if we're in a forum that's affected
	if ($mybb->settings['htmlposts_forums'] != '')
	{
		$forums = explode(",", trim($mybb->settings['htmlposts_forums']));
		if (!in_array($mypost['fid'], $forums))
			return;
	}

	global $parser, $control_html;

	if (!is_object($parser))
	{
		return; // unfortunately we cannot proceed without a $parser object created
	}

	// Create a new class to control the parser options easily
	if (!class_exists("control_html"))
	{
		class control_html
		{
			public $html_enabled;

			function control_html()
			{
				// Is it enabled already? Save it in a var to later disallow disabling
				$this->html_enabled = $parser->options['allow_html'];
			}

			function set_html($status)
			{
				$status = (int)$status;
				if ($status != 0 && $status != 1) return false;

				// if we're trying to disable it but it's enabled by default, disallow the action
				if ($status == 0 && $this->html_enabled == 1)
					return false;

				global $parser;

				// Set to desired status
				$parser->options['allow_html'] = $status;
				// for previewing posts
				global $parser_options;
				if (!empty($parser_options))
					$parser_options['allow_html'] = $status;

				return true;
			}
		}
	}

	// Create object if it doesn't exist
	if (!is_object($control_html))
		$control_html = new control_html();

	$override = false;
	// is the post author allowed to have HTML in posts?
	if($mybb->settings['htmlposts_uids'] != '')
	{
		$uids = explode(",", trim($mybb->settings['htmlposts_uids']));
		if(!in_array($mypost['uid'], $uids))
		{
			// Disable HTML, or at least we'll try to, the function might refuse it
			$control_html->set_html(0);
		}
		else
			$override = true;
	}

	// is the post author in a group allowed to post HTML?
	if($override === false && $mybb->settings['htmlposts_groups'] != '' && THIS_SCRIPT != 'xmlhttp.php') // groups are not affected when editing the post via XMLHTTP (because it doesn't get user data and we are not going to run an extra query)
	{
		// Portal and Thread Review in New Reply don't have usergroup,additionalgroups in query
		if(THIS_SCRIPT == 'portal.php' || (THIS_SCRIPT == 'newreply.php' && !isset($mypost['usergroup'])))
		{
			// Get usergroup and additionalgroups if we're in portal.php
			$q = $db->simple_select('users', 'usergroup,additionalgroups', 'uid='.$mypost['uid']);
			$data = $db->fetch_array($q);
			$mypost['usergroup'] = $data['usergroup'];
			$mypost['additionalgroups'] = $data['additionalgroups'];
		}

		if(!htmlposts_check_permissions($mybb->settings['htmlposts_groups'], $mypost))
		{
			// Disable HTML, or at least we'll try to, the function might refuse it
			$control_html->set_html(0);
			return;
		}
	}

	if(!isset($parser->options['filter_badwords']) && !$previewpost) // we're probably parsing a signature, this is not defined there
	{
		// Disable HTML, or at least we'll try to, the function might refuse it
		$control_html->set_html(0);
		return;
	}

	// Enable HTML for allowed users :)
	$control_html->set_html(1);
}

?>
