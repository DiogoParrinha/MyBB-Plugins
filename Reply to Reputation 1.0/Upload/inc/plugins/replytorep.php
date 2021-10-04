<?php
/***************************************************************************
 *
 *  Reply to Reputation plugin (/inc/plugins/replytorep.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  Certain groups can leave a reply to reputation given to them.
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

$plugins->add_hook('reputation_vote', 'replytorep_showreply');
$plugins->add_hook('reputation_start', 'replytorep_modals');

function replytorep_info()
{
	return array(
		"name"			=> "Reply to Reputation",
		"description"	=> "Certain groups can leave a reply to reputation given to them.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility"	=> "18*"
	);
}


function replytorep_install()
{
	global $db;
	// create settings group
	$insertarray = array(
		'name' => 'replytorep',
		'title' => 'Reply to Reputation',
		'description' => "Settings for Reply to Reputation",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);
	// add settings

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "replytorep_groups",
		"title"			=> "Allowed Groups",
		"description"	=> "Select the groups allowed to reply to reputation.",
		"optionscode"	=> "groupselect",
		"value"			=> '',
		"disporder"		=> 1,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "replytorep_reptype",
		"title"			=> "Allowed Groups",
		"description"	=> "Select the groups allowed to reply to reputation.",
		"optionscode"	=> "select
all=All
positive=Positive
negative=Negative
neutral=Neutral
posneg=Positive and Negative
posneu=Positive and Neutral
negneu=Negative and Neutral",
		"value"			=> 'all',
		"disporder"		=> 2,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	rebuild_settings();

	// create table
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."replytorep` (
	  `rid` bigint(30) UNSIGNED NOT NULL,
	  `uid` bigint(30) UNSIGNED NOT NULL,
	  `message` varchar(255) NOT NULL default '',
	  `time` int(10) UNSIGNED NOT NULL,
	  PRIMARY KEY (`rid`)
		) ENGINE=MyISAM");
}

function replytorep_activate()
{
	global $db, $lang;

	$template = array(
		"title" => "replytorep_edit_button",
		"template" => $db->escape_string('
		<div class="float_right postbit_buttons">
			<a href="javascript:Replytorep.edit({$reply[\'rid\']});" class="postbit_edit"><span>{$lang->replytorep_edit}</span></a>
		</div>
		'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "replytorep_reply_button",
		"template" => $db->escape_string('
		<div class="float_right postbit_buttons">
			<a href="javascript:Replytorep.reply({$reputation_vote[\'rid\']});" class="postbit_quote"><span>{$lang->replytorep_reply}</span></a>
		</div>
		'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "replytorep_comment",
		"template" => $db->escape_string('
		<div style="padding: 10px">
			<div style="float: left; height: 50px; padding-right: 5px; padding-top: 2px"><img src="images/nav_bit.png" style=""></div>
			<div>
				<strong>{$lang->replytorep_replied}</strong><br />
				{$comment}
			</div>
		</div>
		'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "replytorep_reply_modal",
		"template" => $db->escape_string('
<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;" class="modal_replytorep_{$reputation_vote[\'rid\']}">
      <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="trow1" style="padding: 20px">
			<strong>{$lang->replytorep_reply_info}</strong><br /><br />

			<form action="reputation.php" method="post" class="replytorep_{$reputation_vote[\'rid\']}" onsubmit="javascript: return Replytorep.submitMessage({$reputation_vote[\'rid\']});">
				<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
				<input type="hidden" name="action" value="replytorep_reply" />
				<input type="hidden" name="rid" value="{$reputation_vote[\'rid\']}" />
				<input type="hidden" name="nomodal" value="1" />
				<input type="text" class="textbox" name="message" size="35" maxlength="250" value="{$message}" style="width: 95%" />
				<br /><br />
				<div style="text-align: center;">
					<input type="submit" class="button" value="{$lang->replytorep_reply}" />
				</div>
			</form>
		</td>
	</tr>
</table>
  </div>
</div>
		'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('reputation', '#'.preg_quote('{$headerinclude}').'#', '{$headerinclude}<script type="text/javascript" src="{$mybb->asset_url}/jscripts/replytorep.js"></script>');
	find_replace_templatesets('reputation_vote', '#'.preg_quote('{$delete_link}').'#', '{$delete_link}{$reply_link}');
	find_replace_templatesets('reputation_vote', '#'.preg_quote('{$reputation_vote[\'comments\']}').'#', '{$reputation_vote[\'comments\']}{$reply_comment}');
}

function replytorep_is_installed()
{
	global $db;

	if($db->table_exists('replytorep'))
		return true;

	return false;
}

function replytorep_uninstall()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'replytorep'");

	// remove settings
	$db->delete_query('settings', 'name IN ( \'replytorep_groups\',\'replytorep_reptype\')');

	rebuild_settings();

	if ($db->table_exists('replytorep'))
		$db->drop_table('replytorep');
}

function replytorep_deactivate()
{
	global $db, $mybb;

	$db->delete_query('templates', 'title LIKE \'%replytorep%\'');

	// remove templates edits
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('reputation', '#'.preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/replytorep.js"></script>').'#', '', 0);
	find_replace_templatesets('reputation_vote', '#'.preg_quote('{$reply_link}').'#', '', 0);
	find_replace_templatesets('reputation_vote', '#'.preg_quote('{$reply_comment}').'#', '', 0);
}

// Used with groupselect setting type
function replytorep_check_permissions($groups_comma)
{
	global $mybb;

	if ($groups_comma == '')
		return false;

	if ($groups_comma == '-1') // All
		return true;

	$groups = explode(",", $groups_comma);

	$ourgroups = explode(",", $mybb->user['additionalgroups']);
	$ourgroups[] = $mybb->user['usergroup'];

	if(count(array_intersect($ourgroups, $groups)) == 0)
		return false;
	else
		return true;
}

function replytorep_showreply()
{
	global $db, $mybb, $user;

	global $reply_link, $reply_comment;
	$reply_link = $reply_comment = '';

	global $theme, $templates, $lang;
	$lang->load("replytorep");

	// Alright get all replies we made
	static $replies;
	if(!isset($replies))
	{
		$q = $db->simple_select('replytorep', '*', 'uid='.(int)$user['uid']);
		while($reply = $db->fetch_array($q))
			$replies[$reply['rid']] = $reply;
	}

	// Do we have a reply for this vote?
	global $reputation_vote;
	if(isset($replies[$reputation_vote['rid']]))
	{
		// Reply link turns into Edit Reply
		$reply = $replies[$reputation_vote['rid']];

		if($mybb->user['uid'] == $user['uid'] && replytorep_check_permissions($mybb->settings['replytorep_groups']))
			eval("\$reply_link = \"".$templates->get("replytorep_edit_button")."\";");

		// Show comment
		$date = my_date('relative', $reply['time']);
		$comment = htmlspecialchars_uni($reply['message']);

		$lang->replytorep_replied = $lang->sprintf($lang->replytorep_replied, htmlspecialchars_uni($user['username']), $date);

		eval("\$reply_comment = \"".$templates->get("replytorep_comment")."\";");
	}
	else
	{
		if($mybb->settings['replytorep_reptype'] != 'all')
		{
			switch($mybb->settings['replytorep_reptype'])
			{
				case 'positive':
					if($reputation_vote['reputation'] <= 0)
						return;
				break;
				case 'negative':
					if($reputation_vote['reputation'] >= 0)
						return;
				break;
				case 'neutral':
					if($reputation_vote['reputation'] < 0 || $reputation_vote['reputation'] > 0)
						return;
				break;
				case 'posneg':
					if($reputation_vote['reputation'] == 0)
						return;
				break;
				case 'posneu':
					if($reputation_vote['reputation'] < 0)
						return;
				break;
				case 'negneu':
					if($reputation_vote['reputation'] > 0)
						return;
				break;
			}
		}

		// We haven't replied
		if($mybb->user['uid'] == $user['uid'] && replytorep_check_permissions($mybb->settings['replytorep_groups']))
			eval("\$reply_link = \"".$templates->get("replytorep_reply_button")."\";");
	}
}

function replytorep_modals()
{
	global $mybb;

	if($mybb->input['action'] != 'replytorep_reply' && $mybb->input['action'] != 'replytorep_edit')
		return;

	global $templates, $theme, $lang;
	$lang->load("replytorep");

	if($mybb->request_method == "post")
	{
		// Verify if it's our reputation page
		global $db;
		$q = $db->simple_select('reputation', '*', 'rid='.(int)$mybb->input['rid'].' AND uid='.(int)$mybb->user['uid']);
		$reputation_vote = $db->fetch_array($q);
		if(empty($reputation_vote))
		{
			$message = $lang->replytorep_no_permissions;
			eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
			echo $error;
			exit;
		}

		// Verify message
		if(trim($mybb->input['message']) == '')
		{
			$message = $lang->replytorep_empty_message;
			eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
			echo $error;
			exit;
		}

		$q = $db->simple_select('replytorep', '*', 'rid='.(int)$mybb->input['rid'].' AND uid='.(int)$mybb->user['uid']);
		$reply = $db->fetch_array($q);
		if(!empty($reply))
		{
			// Update existing
			$db->update_query('replytorep', array('message' => $db->escape_string($mybb->input['message'])), 'rid='.(int)$reply['rid']);

			$message = $lang->replytorep_reply_updated;
		}
		else
		{
			if($mybb->settings['replytorep_reptype'] != 'all')
			{
				switch($mybb->settings['replytorep_reptype'])
				{
					case 'positive':
						if($reputation_vote['reputation'] <= 0)
						{
							$message = $lang->replytorep_no_permissions;
							eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
							echo $error;
							exit;
						}
					break;
					case 'negative':
						if($reputation_vote['reputation'] >= 0)
						{
							$message = $lang->replytorep_no_permissions;
							eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
							echo $error;
							exit;
						}
					break;
					case 'neutral':
						if($reputation_vote['reputation'] < 0 || $reputation_vote['reputation'] > 0)
						{
							$message = $lang->replytorep_no_permissions;
							eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
							echo $error;
							exit;
						}
					break;
					case 'posneg':
						if($reputation_vote['reputation'] == 0)
						{
							$message = $lang->replytorep_no_permissions;
							eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
							echo $error;
							exit;
						}
					break;
					case 'posneu':
						if($reputation_vote['reputation'] < 0)
						{
							$message = $lang->replytorep_no_permissions;
							eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
							echo $error;
							exit;
						}
					break;
					case 'negneu':
						if($reputation_vote['reputation'] > 0)
						{
							$message = $lang->replytorep_no_permissions;
							eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
							echo $error;
							exit;
						}
					break;
				}
			}

			// Insert new
			$db->insert_query('replytorep', array('time' => TIME_NOW, 'rid' => (int)$mybb->input['rid'], 'uid' => (int)$mybb->user['uid'], 'message' => $db->escape_string($mybb->input['message'])));

			$message = $lang->replytorep_reply_added;
		}

		$lang->error = $lang->replytorep_success;
		eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
		echo $error;
		exit;
	}
	else
	{
		global $db;
		$q = $db->simple_select('reputation', '*', 'rid='.(int)$mybb->input['rid'].' AND uid='.(int)$mybb->user['uid']);
		$reputation_vote = $db->fetch_array($q);

		if($mybb->input['action'] == 'replytorep_reply')
		{
			eval("\$page = \"".$templates->get("replytorep_reply_modal", 1, 0)."\";");
			echo $page;
			exit;
		}
		else
		{
			// Editing: search for our existing reply
			global $db;
			$q = $db->simple_select('replytorep', '*', 'rid='.(int)$mybb->input['rid'].' AND uid='.(int)$mybb->user['uid']);
			$reply = $db->fetch_array($q);
			if(empty($reply))
			{
				$message = $lang->replytorep_no_reply;
				eval("\$error = \"".$templates->get("reputation_add_error", 1, 0)."\";");
				echo $error;
				exit;
			}
			else
			{
				$message = htmlspecialchars_uni($reply['message']);
				$lang->replytorep_reply = $lang->replytorep_edit;
				eval("\$page = \"".$templates->get("replytorep_reply_modal", 1, 0)."\";");
				echo $page;
				exit;
			}
		}
	}
}
