<?php
/***************************************************************************
 *
 *	MyChatbox plugin (/inc/plugins/mychatbox.php)
 *	Author: Diogo Parrinha
 *	Copyright: (c) 2021 Diogo Parrinha
 *
 *	Adds a chatbox to MyBB.
 *
 ***************************************************************************/


if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly.");

if (defined("IN_ADMINCP"))
{
	$plugins->add_hook('admin_load','mychatbox_admin');
	$plugins->add_hook('admin_user_menu','mychatbox_admin_user_menu');
	$plugins->add_hook('admin_user_action_handler','mychatbox_admin_user_action_handler');
	$plugins->add_hook('admin_user_permissions','mychatbox_admin_user_permissions');
}
else
{
	$plugins->add_hook('pre_output_page', 'mychatbox_display');
	$plugins->add_hook('global_end', 'mychatbox_add_variable');
	$plugins->add_hook('xmlhttp', 'mychatbox_xmlhttp');
	$plugins->add_hook('misc_start', 'mychatbox_logs');
}

if(defined("THIS_SCRIPT") && THIS_SCRIPT == 'misc.php')
{
	global $templatelist;

	if(!isset($templatelist))
	{
		$templatelist = '';
	}
	else
	{
		$templatelist .= ',';
	}

	$templatelist .= 'mychatbox_logs,mychatbox_logs_row,mychatbox_logs_options,mychatbox_logs_no_messages';
}

// We assume here that it's better to assume that the chatbox is always displayed on the index page and to load an extra template than having an extra query if the chatbox is really there.
if(defined("THIS_SCRIPT") && THIS_SCRIPT == 'index.php')
{
	global $templatelist;

	if(!isset($templatelist))
	{
		$templatelist = '';
	}
	else
	{
		$templatelist .= ',';
	}

	$templatelist .= 'mychatbox';
}

function mychatbox_info()
{
	return array(
		"name"			=> "MyChatbox",
		"description"		=> "Adds a chatbox to MyBB.",
		"author"			=> "Diogo Parrinha",
		"version"		=> "1.2",
		"guid" 			=> "",
		"compatibility" 	=> "18*"
	);
}

function mychatbox_install()
{
	global $db, $lang;

	// create settings group
	$insertarray = array(
		'name' => 'mychatbox',
		'title' => 'MyChatbox',
		'description' => "Settings for MyChatbox plugin.",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	$disporder = 1;

	$setting = array(
		"name"			=> "mychatbox_mycode",
		"title"			=> "Allow MyCode",
		"description"		=> "Whether or not to allow MyCode.",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_img",
		"title"			=> "Allow IMG tags",
		"description"		=> "Whether or not to allow IMG tags.",
		"optionscode"	=> "yesno",
		"value"			=> "0",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_video",
		"title"			=> "Allow Video tags",
		"description"		=> "Whether or not to allow Video tags.",
		"optionscode"	=> "yesno",
		"value"			=> "0",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_flood",
		"title"			=> "Flood Time",
		"description"		=> "Enter the period of time (in seconds) users must wait before posting another message. Those with command permissions are not affected.",
		"optionscode"	=> "text",
		"value"			=> "10",
		"disporder"		=> $disporder++,
		"gid"				=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_refresh",
		"title"			=> "Refresh Time",
		"description"		=> "Enter the period of time between refreshes (in seconds). Default is 10.",
		"optionscode"	=> "text",
		"value"			=> "10",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_messages_displayed",
		"title"			=> "Messages Displayed",
		"description"		=> "Enter the total amount of messages to display on the chatbox.",
		"optionscode"	=> "text",
		"value"			=> "20",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_message",
		"title"			=> "Custom Message",
		"description"		=> "Enter a custom message displayed to those who cannot view the chatbox. If no message is displayed, the chatbox is hidden.",
		"optionscode"	=> "textarea",
		"value"			=> "You are currently banned from the chatbox.",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_perms_view",
		"title"			=> "Permissions to View",
		"description"		=> "Select the groups that can view the chatbox.",
		"optionscode"	=> "groupselect",
		"value"			=> "-1",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_perms_post",
		"title"			=> "Permissions to Post",
		"description"		=> "Select the groups that can post on the chatbox.",
		"optionscode"	=> "groupselect",
		"value"			=> "4,6,3,2",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_perms_edit",
		"title"			=> "Permissions to Edit",
		"description"		=> "Select the groups that can edit their posts on the chatbox.",
		"optionscode"	=> "groupselect",
		"value"			=> "4,6,3,2",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_perms_delete",
		"title"			=> "Permissions to Delete",
		"description"		=> "Select the groups that can delete their posts on the chatbox.",
		"optionscode"	=> "groupselect",
		"value"			=> "4,6,3",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_perms_commands",
		"title"			=> "Permissions to use Commands",
		"description"		=> "Select the groups that can run commands on the chatbox. These groups can also edit and delete other users\' posts.",
		"optionscode"	=> "groupselect",
		"value"			=> "4",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_newpoints_fee",
		"title"			=> "(NewPoints) Points per Post",
		"description"	=> "Enter the amount of points users must pay per post to use the chatbox. Negative amounts mean these are paid to users.",
		"optionscode"	=> "text",
		"value"			=> "0",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "mychatbox_newpoints_exempt",
		"title"			=> "(NewPoints) Exempt Groups",
		"description"	=> "Select the groups that are exempt from paying to post.",
		"optionscode"	=> "groupselect",
		"value"			=> "-1",
		"disporder"		=> $disporder++,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);


	rebuild_settings();

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."mychatbox` (
		`id` int(10) UNSIGNED NOT NULL auto_increment,
		`uid` bigint(30) UNSIGNED NOT NULL default '0',
		`message` varchar(255) NOT NULL default '',
		`timestamp` int(10) NOT NULL,
		`status` tinyint(1) NOT NULL default '1',
		PRIMARY KEY  (id)
	) ENGINE=MyISAM;");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."mychatbox_banned` (
		`bid` int(10) UNSIGNED NOT NULL auto_increment,
		`uid` bigint(30) UNSIGNED NOT NULL,
		`reason` varchar(255) NOT NULL default '',
		PRIMARY KEY (bid), INDEX(`uid`)
	) ENGINE=MyISAM;");
}

function mychatbox_activate()
{
	global $db, $lang;

	// templates
	$templatearray = array (
		"title" => "mychatbox",
		"template" => $db->escape_string('
			<br />
			<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
				<thead>
					<tr>
						<td class="thead{$expthead}">
							<div class="expcolimage"><img src="{$theme[\'imgdir\']}/{$expcolimage}" id="mychatbox_img" class="expander" alt="{$expaltext}" title="{$expaltext}" /></div>
							<div style="float: right; margin-right: 5px">
								<a href="{$mybb->settings[\'bburl\']}/misc.php?action=mychatbox">{$lang->mychatbox_logs}</a>
							</div>
							<strong>{$lang->mychatbox}</strong>
						</td>
					</tr>
				</thead>
				<tbody style="{$expdisplay}" id="mychatbox_e">
					<tr>
						<td class="trow1">
							<div style="width: 100%; margin: 0 auto; text-align: center">
								<form name="mychatbox_post_form" method="POST" id="mychatbox_post_form">
									<input type="text" id="mychatbox_message" name="message" class="textbox" style="width: 200px" /> <input type="submit" value="{$lang->mychatbox_post}" id="mychatbox_post" class="button" name="submit" /> <div id="mychatbox_spinner" style="display: none"><img src="{$theme[\'imgdir\']}/spinner.gif" /></div>
								</form>
							</div>
							<div style="height: 200px; clear: both; margin-top: 10px; overflow: auto">
								<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" id="mychatbox_messages">
									{$messages}
								</table>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<br />
		'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mychatbox_banned",
		"template" => $db->escape_string('
			<br />
			<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
				<thead>
					<tr>
						<td class="thead">
							<strong>{$lang->mychatbox}</strong>
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="trow1">
							{$message}
						</td>
					</tr>
				</tbody>
			</table>
			<br />
		'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mychatbox_logs",
		"template" => $db->escape_string('
<html>
<head>
<title>{$lang->mychatbox} {$lang->mychatbox_logs}</title>
{$headerinclude}
<script type="text/javascript">
<!--
	lang.mychatbox_confirm_delete = \'{$lang->mychatbox_confirm_delete}\';
	lang.mychatbox_enter_new_message = \'{$lang->mychatbox_enter_new_message}\';
// -->
</script>
<script src="{$mybb->asset_url}/jscripts/mychatbox_logs.js"></script>
</head>
<body>
	{$header}
	{$multipage}
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td class="thead" colspan="{$colspan}"><strong>{$lang->mychatbox_logs}</strong></td>
		</tr>
		{$messages}
	</table>
	{$footer}
</body>
</html>
		'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mychatbox_logs_row",
		"template" => $db->escape_string('
<tr id="mychatbox_message_{$post[\'id\']}">
	<td class="{$bgcolor}" width="10%" style="text-align: center">{$date}</td>
	<td class="{$bgcolor}" width="10%" style="text-align: center">{$username}</td>
	<td class="{$bgcolor}" width="65%">{$message}</td>
	{$options}
</tr>
		'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mychatbox_logs_options",
		"template" => $db->escape_string('
<td class="{$bgcolor}" width="15%" style="text-align: center">{$options}</td>
		'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mychatbox_logs_no_messages",
		"template" => $db->escape_string('
<tr>
	<td class="{$bgcolor}" colspan="{$colspan}">{$lang->mychatbox_no_messages}</td>
</tr>
		'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);
}

function mychatbox_is_installed()
{
	global $db;
	if ($db->table_exists('mychatbox'))
		return true;
	else
		return false;
}

function mychatbox_uninstall()
{
	global $db;

	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name LIKE '%mychatbox%'");

	$db->delete_query("settinggroups", "name = 'mychatbox'");

	if($db->table_exists('mychatbox'))
		$db->drop_table('mychatbox');

	if($db->table_exists('mychatbox_banned'))
		$db->drop_table('mychatbox_banned');

	rebuild_settings();
}

function mychatbox_deactivate()
{
	global $db;

	$db->delete_query("templates", "title LIKE '%mychatbox%'");
}

/**
 * Checks if a user has permissions or not.
 *
 * @param array|string Allowed usergroups (if set to 'all', every user has access; if set to '' no one has)
 *
*/
function mychatbox_check_permissions($groups_comma)
{
	global $mybb;

	if ($groups_comma == 'all' || $groups_comma == '-1')
		return true;

	if ($groups_comma == '')
		return false;

	$groups = explode(",", $groups_comma);

	if($mybb->user['additionalgroups'] != '')
		$ourgroups = explode(",", $mybb->user['additionalgroups']);
	else
		$ougroups = array();

	$ourgroups[] = $mybb->user['usergroup'];

	if(count(array_intersect($ourgroups, $groups)) == 0)
		return false;
	else
		return true;
}

function mychatbox_add_variable()
{
	global $headerinclude, $mybb, $lang;

	$seconds = (int)$mybb->setting['mychatbox_refresh'];
	if($seconds <= 0)
		$seconds = 10;

	$lang->load("mychatbox");

	$headerinclude .= "
<script type=\"text/javascript\">
<!--
	var mychatbox_refresh = '{$seconds}';
	lang.mychatbox_confirm_delete = '{$lang->mychatbox_confirm_delete}';
	lang.mychatbox_enter_new_message = '{$lang->mychatbox_enter_new_message}';
// -->
</script>
<script src=\"{$mybb->asset_url}/jscripts/mychatbox.js\"></script>";
}

function mychatbox_get_messages()
{
	global $mybb, $lang, $db, $parser;

	// Set up the message parser if it doesn't already exist.
	if(!isset($parser))
	{
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
	}

	$messages = '';

	$parser_options['allow_html'] = 0;
	$parser_options['allow_mycode'] = $mybb->settings['mychatbox_mycode'];
	$parser_options['allow_videocode'] = $mybb->settings['mychatbox_video'];
	$parser_options['allow_imgcode'] = $mybb->settings['mychatbox_img'];
	$parser_options['allow_smilies'] = 1;
	$parser_options['filter_badwords'] = 1;

	if($mybb->settings['mychatbox_messages_displayed'] <= 0)
		$mybb->settings['mychatbox_messages_displayed'] = 20;

	$q = $db->query("
		SELECT u.username, u.usergroup, u.displaygroup, p.*
		FROM `".TABLE_PREFIX."mychatbox` p
		LEFT JOIN `".TABLE_PREFIX."users` u ON (p.uid=u.uid)
		ORDER BY p.timestamp DESC
		LIMIT {$mybb->settings['mychatbox_messages_displayed']}
	");
	while($post = $db->fetch_array($q))
	{
		$bgcolor = alt_trow();

		$parser_options['me_username'] = $post['username'];

		$message = $parser->parse_message($post['message'], $parser_options);

		$options = '';
		if(mychatbox_check_permissions($mybb->settings['mychatbox_perms_delete']) || mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']))
		{
			$options .= '<a class="button small_button mychatbox_delete" href="javascript:;" title="'.$lang->mychatbox_delete_post.'" id="mychatbox_delete_'.(int)$post['id'].'"><span style="background-position: 0 -180px">'.$lang->mychatbox_delete.'</span></a>&nbsp;';
		}

		if(mychatbox_check_permissions($mybb->settings['mychatbox_perms_edit']) || mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']))
		{
			$options .= '<a class="button small_button mychatbox_edit" href="javascript:;" title="'.$lang->mychatbox_edit_post.'" id="mychatbox_edit_'.(int)$post['id'].'"><span style="background-position: 0 -160px">'.$lang->mychatbox_edit.'</span></a>';
		}

		$date = my_date($mybb->settings['dateformat'], $post['timestamp']);
		$date .= ', '.my_date($mybb->settings['timeformat'], $post['timestamp']);

		$username = build_profile_link(format_name(htmlspecialchars_uni($post['username']), $post['usergroup'], $post['displaygroup']), (int)$post['uid']);

		if($options != '')
		{
			$options = '<td class="'.$bgcolor.'" width="15%" style="text-align: center">'.$options.'</td>';
		}

		$messages .= '
		<tr id="mychatbox_message_'.(int)$post['id'].'">
			<td class="'.$bgcolor.'" width="10%" style="text-align: center">'.$date.'</td>
			<td class="'.$bgcolor.'" width="10%" style="text-align: center">'.$username.'</td>
			<td class="'.$bgcolor.'" width="65%">'.$message.'</td>
			'.$options.'
		</tr>';
	}

	if($messages == '')
	{
		$messages = '<tr><td class="trow1">'.$lang->mychatbox_no_messages.'</td></tr>';
	}

	return $messages;
}

function mychatbox_display(&$contents)
{
	global $mybb, $db, $lang;

	// Permissions to view
	if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_view']))
	{
		$mychatbox = '';
		$contents = str_replace('{mychatbox}', $mychatbox, $contents);
		return;
	}

	// Do we have a chatbox on this page?
	if(strpos($contents, '{mychatbox}') === false)
		return;

	global $templates, $theme;

	$lang->load("mychatbox");

	// Banned?
	$q = $db->simple_select('mychatbox_banned', '*', 'uid='.(int)$mybb->user['uid']);
	$banned = $db->fetch_array($q);
	if(!empty($banned))
	{
		// Custom message
		$message = $mybb->settings['mychatbox_message'];

		if($message == '')
		{
			$message = $lang->mychatbox_banned_message;
		}

		eval("\$mychatbox = \"".$templates->get("mychatbox_banned")."\";");
		$contents = str_replace('{mychatbox}', $mychatbox, $contents);

		return;
	}

	$messages = mychatbox_get_messages();

	// Collapsed vs Expanded
	$expdisplay = '';
	if(isset($collapsed['mychatbox_c']) && $collapsed['mychatbox_c'] == "display: show;")
	{
		$expcolimage = "collapse_collapsed.png";
		$expdisplay = "display: none;";
		$expthead = " thead_collapsed";
		$expaltext = "[+]";
	}
	else
	{
		$expcolimage = "collapse.png";
		$expthead = "";
		$expaltext = "[-]";
	}

	eval("\$mychatbox = \"".$templates->get("mychatbox")."\";");

	$contents = str_replace('{mychatbox}', $mychatbox, $contents);
}

function mychatbox_xmlhttp()
{
	global $mybb;

	if($mybb->input['action'] != 'mychatbox' || $mybb->user['uid'] <= 0)
		return;

	global $lang, $templates, $theme, $db;

	// Permissions to view
	if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_view']))
	{
		return;
	}

	$q = $db->simple_select('mychatbox_banned', '*', 'uid='.(int)$mybb->user['uid']);
	$banned = $db->fetch_array($q);
	if(!empty($banned))
	{
		return;
	}

	$lang->load("mychatbox");

	if($mybb->request_method == "post")
	{
		// Verify POST request
		if(!verify_post_check($mybb->get_input('my_post_key'), true))
		{
			xmlhttp_error($lang->invalid_post_code);
		}

		// Are we posting something?
		if(isset($mybb->input['post']))
		{
			// Permissions to post
			if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_post']) || $mybb->user['uid'] <= 0)
			{
				xmlhttp_error($lang->mychatbox_no_permission);
			}

			// Validate message
			$message = $mybb->input['message'];
			if(trim_blank_chrs($message) == '')
			{
				xmlhttp_error($lang->mychatbox_invalid_message);
			}

			// Flood check
			if($mybb->settings['mychatbox_flood'] > 0 && !mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']))
			{
				$q = $db->simple_select('mychatbox', 'timestamp', 'uid='.(int)$mybb->user['uid'], array('order_by' => 'timestamp', 'order_dir' => 'desc', 'limit' => 1));
				$last = $db->fetch_field($q, 'timestamp');
				if($last > TIME_NOW - $mybb->settings['mychatbox_flood'])
				{
					xmlhttp_error($lang->sprintf($lang->mychatbox_wait, $last - (TIME_NOW - $mybb->settings['mychatbox_flood'])));
				}
			}

			// If the first character is / and the second is not, then we're running a command (and we make sure we're not doing /me either)
			if(my_strlen($message) > 1 && $message[0] == '/' && $message[1] != '/' && my_substr($message, 0, 4) != '/me ')
			{
				// Permissions to run commands
				if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']))
				{
					xmlhttp_error($lang->mychatbox_no_permission);
				}

				// Interpret the command
				if($message == '/clear')
				{
					// Clear all messages
					$db->delete_query('mychatbox');

					$data = array('success' => $lang->mychatbox_run_successfully);
				}
				elseif(sscanf($message, "/clear %d/%d/%d", $day, $month, $year) == 3)
				{
					$time = (int)mktime(0, 0, 0, $month, $day, $year);

					$db->delete_query('mychatbox', 'timestamp < '.$time);

					$data = array('success' => $lang->mychatbox_run_successfully);
				}
				else
					xmlhttp_error($lang->mychatbox_invalid_command);
			}
			else
			{
				// This is a regular message

				// Is NewPoints running?
				if(defined('NP_HOOKS') && isset($mybb->user['newpoints']))
				{
					$points = (float)$mybb->settings['mychatbox_newpoints_fee'];
					if($points > 0 && $points > $mybb->user['newpoints'])
					{
						xmlhttp_error($lang->sprintf($lang->mychatbox_not_enough_points, newpoints_format_points($points)));
					}

					// Are we exempt from paying?
					if(!mychatbox_check_permissions($mybb->settings['mychatbox_newpoints_exempt']))
					{
						newpoints_addpoints($mybb->user['uid'], -$points);
					}
				}

				$db->insert_query('mychatbox', array(
					'uid' => (int)$mybb->user['uid'],
					'message' => $db->escape_string($message),
					'timestamp' => (int)TIME_NOW,
					'status' => 1
				));

				$data = array('success' => $lang->mychatbox_posted_successfully);
			}

			// Get messages now
			$messages = mychatbox_get_messages();
			$data['messages'] = $messages;
		}
		elseif(isset($mybb->input['edit']))
		{
			// Permissions to edit
			if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_edit']))
			{
				// We don't have permissions to edit but can we use commands? If yes, it overrides the above
				if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']))
				{
					xmlhttp_error($lang->mychatbox_no_permission);
				}
			}

			// Validate post ID
			$id = (int)$mybb->get_input('edit', INPUT_INT);
			if($id <= 0)
			{
				xmlhttp_error($lang->mychatbox_invalid_post);
			}

			// Does it exist?
			$q = $db->simple_select('mychatbox', '*', 'id='.$id);
			$post = $db->fetch_array($q);
			if(empty($post))
			{
				xmlhttp_error($lang->mychatbox_invalid_post);
			}

			// If we can't run commands, we must make sure we're editing OUR post
			if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']) && $post['uid'] != $mybb->user['uid'])
			{
				xmlhttp_error($lang->mychatbox_no_permission);
			}

			// Validate message (don't accept commands in edit mode)
			$message = $mybb->input['message'];
			if(trim_blank_chrs($message) == '')
			{
				xmlhttp_error($lang->mychatbox_invalid_message);
			}

			// If the first character is / and the second is not, then we're running a command (and we make sure we're not doing /me either)
			if(my_strlen($message) > 1 && $message[0] == '/' && $message[1] != '/' && my_substr($message, 0, 4) != '/me ')
			{
				xmlhttp_error($lang->mychatbox_invalid_message);
			}

			$db->update_query('mychatbox', array(
				'message' => $db->escape_string($message),
				'status' => 2
			), 'id='.$id);

			$data = array('success' => $lang->mychatbox_edited_successfully);

			global $parser;

			// Set up the message parser if it doesn't already exist.
			if(!isset($parser))
			{
				require_once MYBB_ROOT."inc/class_parser.php";
				$parser = new postParser;
			}

			$parser_options['allow_html'] = 0;
			$parser_options['allow_mycode'] = $mybb->settings['mychatbox_mycode'];
			$parser_options['allow_videocode'] = $mybb->settings['mychatbox_video'];
			$parser_options['allow_imgcode'] = $mybb->settings['mychatbox_img'];
			$parser_options['allow_smilies'] = 1;
			$parser_options['me_username'] = $mybb->user['username'];
			$parser_options['filter_badwords'] = 1;

			$data['message'] = $parser->parse_message($message, $parser_options);
		}
		elseif(isset($mybb->input['delete']))
		{
			// Permissions to delete
			if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_delete']))
			{
				// We don't have permissions to delete but can we use commands? If yes, it overrides the above
				if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']))
				{
					xmlhttp_error($lang->mychatbox_no_permission);
				}
			}

			// Validate post ID
			$id = (int)$mybb->get_input('delete', INPUT_INT);
			if($id <= 0)
			{
				xmlhttp_error($lang->mychatbox_invalid_post);
			}

			// Does it exist?
			$q = $db->simple_select('mychatbox', '*', 'id='.$id);
			$post = $db->fetch_array($q);
			if(empty($post))
			{
				xmlhttp_error($lang->mychatbox_invalid_post);
			}

			// If we can't run commands, we must make sure we're deleting OUR post
			if(!mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']) && $post['uid'] != $mybb->user['uid'])
			{
				xmlhttp_error($lang->mychatbox_no_permission);
			}

			$db->delete_query('mychatbox', 'id='.$id);

			$data = array('success' => $lang->mychatbox_deleted_successfully);

			// Get messages now
			$messages = mychatbox_get_messages();
			$data['messages'] = $messages;
		}
	}
	else
	{
		// We're just refreshing
		$messages = mychatbox_get_messages();

		$data = array('messages' => $messages);
	}

	echo json_encode($data);
	exit;
}

function mychatbox_logs()
{
	global $mybb;

	if($mybb->input['action'] != 'mychatbox')
	{
		return;
	}

	global $db, $lang, $theme, $templates, $parser, $header, $headerinclude, $footer;

	$lang->load("mychatbox");

	$q = $db->simple_select('mychatbox_banned', '*', 'uid='.(int)$mybb->user['uid']);
	$banned = $db->fetch_array($q);
	if(!empty($banned))
	{
		error($lang->mychatbox_banned_message);
	}

	add_breadcrumb($lang->mychatbox." - ".$lang->mychatbox_logs, 'misc.php?action=mychatbox');

	// Set up the message parser if it doesn't already exist.
	if(!isset($parser))
	{
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
	}

	$messages = '';

	$parser_options['allow_html'] = 0;
	$parser_options['allow_mycode'] = $mybb->settings['mychatbox_mycode'];
	$parser_options['allow_videocode'] = $mybb->settings['mychatbox_video'];
	$parser_options['allow_imgcode'] = $mybb->settings['mychatbox_img'];
	$parser_options['allow_smilies'] = 1;
	$parser_options['filter_badwords'] = 1;

	// pagination
	$per_page = (int)$mybb->settings['mychatbox_messages_displayed'];
	$mybb->input['page'] = (int)$mybb->input['page'];
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

	// total comments
	$total_rows = $db->fetch_field($db->simple_select("mychatbox", "COUNT(id) as posts"), "posts");

	// multi-page
	if ($total_rows > $per_page)
		$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/misc.php?action=mychatbox");

	$q = $db->query("
		SELECT u.username, u.usergroup, u.displaygroup, p.*
		FROM `".TABLE_PREFIX."mychatbox` p
		LEFT JOIN `".TABLE_PREFIX."users` u ON (p.uid=u.uid)
		ORDER BY p.timestamp DESC
		LIMIT {$start},{$per_page}
	");
	while($post = $db->fetch_array($q))
	{
		$bgcolor = alt_trow();

		$colspan = 3;

		$parser_options['me_username'] = $post['username'];

		$message = $parser->parse_message($post['message'], $parser_options);

		$options = '';
		if(mychatbox_check_permissions($mybb->settings['mychatbox_perms_delete']) || mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']))
		{
			$options .= '<a class="button small_button mychatbox_delete" href="javascript:;" title="'.$lang->mychatbox_delete_post.'" id="mychatbox_delete_'.(int)$post['id'].'"><span style="background-position: 0 -180px">'.$lang->mychatbox_delete.'</span></a>&nbsp;';

			$colspan++;
		}

		if(mychatbox_check_permissions($mybb->settings['mychatbox_perms_edit']) || mychatbox_check_permissions($mybb->settings['mychatbox_perms_commands']))
		{
			$options .= '<a class="button small_button mychatbox_edit" href="javascript:;" title="'.$lang->mychatbox_edit_post.'" id="mychatbox_edit_'.(int)$post['id'].'"><span style="background-position: 0 -160px">'.$lang->mychatbox_edit.'</span></a>';

			$colspan++;
		}

		if($options != '')
		{
			eval("\$options = \"".$templates->get("mychatbox_logs_options")."\";");
		}

		$date = my_date($mybb->settings['dateformat'], $post['timestamp']);
		$date .= ', '.my_date($mybb->settings['timeformat'], $post['timestamp']);

		$username = build_profile_link(format_name(htmlspecialchars_uni($post['username']), $post['usergroup'], $post['displaygroup']), (int)$post['uid']);

		eval("\$messages .= \"".$templates->get("mychatbox_logs_row")."\";");
	}

	if($messages == '')
	{
		$colspan = 3;
		eval("\$messages = \"".$templates->get("mychatbox_logs_no_messages")."\";");
	}

	eval("\$page = \"".$templates->get("mychatbox_logs")."\";");

	output_page($page);
	exit;
}

/////// Admin CP ///////
function mychatbox_admin_user_menu(&$sub_menu)
{
	global $lang;

	$lang->load('mychatbox');
	$sub_menu[] = array('id' => 'mychatbox', 'title' => $lang->mychatbox_index, 'link' => 'index.php?module=user-mychatbox');
}

function mychatbox_admin_user_action_handler (&$actions)
{
	$actions['mychatbox'] = array ('active' => 'mychatbox', 'file' => 'mychatbox');
}

function mychatbox_admin_user_permissions(&$admin_permissions)
{
	global $db, $mybb, $lang;

	$lang->load('mychatbox', false, true);
	$admin_permissions['mychatbox'] = $lang->mychatbox_canmanage;
}

function mychatbox_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins, $cache;

	$lang->load('mychatbox', false, true);

	if ($run_module == 'user' && $action_file == 'mychatbox')
	{
		if($mybb->request_method == 'post')
		{
			if($mybb->input['action'] == 'ban')
			{
				// Valid username?
				$user = get_user_by_username($mybb->input['username']);
				if(empty($user))
				{
					flash_message($lang->mychatbox_invalid_user, 'error');
					admin_redirect("index.php?module=user-mychatbox");
				}
				else
				{
					// Banned already?
					$q = $db->simple_select('mychatbox_banned', '*', 'uid='.$user['uid']);
					$banned = $db->fetch_array($q);
					if(!empty($banned))
					{
						flash_message($lang->mychatbox_already_banned, 'error');
						admin_redirect("index.php?module=user-mychatbox");
					}
				}

				// Filter data
				$reason = $db->escape_string($mybb->input['reason']);
				/*if(empty($reason))
				{
					flash_message($lang->mychatbox_invalid_reason, 'error');
					admin_redirect("index.php?module=user-mychatbox");
				}*/

				$db->insert_query('mychatbox_banned', array(
					'uid' => $user['uid'],
					'reason' => $reason,
				));

				flash_message($lang->mychatbox_banned_successfully, 'success');
				admin_redirect("index.php?module=user-mychatbox");
			}
		}

		// lift
		if($mybb->input['action'] == 'lift')
		{
			$bid = intval($mybb->input['bid']);
			if ($bid <= 0 || (!($banned = $db->fetch_array($db->simple_select('mychatbox_banned', '*', "bid = $bid")))))
			{
				flash_message($lang->mychatbox_invalid_ban, 'error');
				admin_redirect("index.php?module=user-mychatbox");
			}

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=user-mychatbox");
			}

			if($mybb->request_method == "post")
			{
				$db->delete_query('mychatbox_banned', "bid = $bid");

				flash_message($lang->mychatbox_lifted_successfully, 'success');
				admin_redirect("index.php?module=user-mychatbox");
			}
			else
			{
				// no action
				$page->add_breadcrumb_item($lang->mychatbox, 'index.php?module=user-mychatbox');
				$page->add_breadcrumb_item($lang->mychatbox_lift, 'index.php?module=user-mychatbox');

				$page->output_header($lang->mychatbox);

				$mybb->input['pid'] = intval($mybb->input['pid']);
				$form = new Form("index.php?module=user-mychatbox&amp;action=lift&amp;bid={$mybb->input['bid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->mychatbox_confirm_lift}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();

				$page->output_footer();

				exit;
			}
		}

		// no action
		$page->add_breadcrumb_item($lang->mychatbox, 'index.php?module=user-mychatbox');

		$page->output_header($lang->mychatbox);

		$form = new Form("index.php?module=user-mychatbox&amp;action=ban", "post", "mychatbox");

		$form_container = new FormContainer($lang->mychatbox_ban);
		$form_container->output_row($lang->mychatbox_user."<em>*</em>", '', $form->generate_text_box('username', '', array('id' => 'username')), 'username');
		$form_container->output_row($lang->mychatbox_reason, '', $form->generate_text_box('reason', '', array('id' => 'reason')), 'reason');
		$form_container->end();

		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->mychatbox_ban);
		$form->output_submit_wrapper($buttons);
		$form->end();

		echo "<br />";

		$table = new Table;
		$table->construct_header($lang->mychatbox_user, array('width'=> '50%'));
		$table->construct_header($lang->mychatbox_reason, array('width'=> '30%'));
		$table->construct_header($lang->mychatbox_options, array('width'=> '20%', 'class' => 'align_center'));
		$query = $db->query("
			SELECT u.username,b.*
			FROM `".TABLE_PREFIX."mychatbox_banned` b
			LEFT JOIN `".TABLE_PREFIX."users` u ON (u.uid=b.uid)
			ORDER BY u.username ASC
		");
		while($ban = $db->fetch_array($query))
		{
			$table->construct_cell(build_profile_link(htmlspecialchars_uni($ban['username']), intval($ban['uid'])));
			$table->construct_cell($ban['reason']);
			$table->construct_cell('<a href="index.php?module=user-mychatbox&amp;action=lift&amp;bid='.$ban['bid'].'&my_post_key='.$mybb->post_code.'">'.$lang->mychatbox_lift.'</a>', array('class' => 'align_center'));
			$table->construct_row();
		}

		if ($table->num_rows() == 0)
		{
			$table->construct_cell($lang->mychatbox_no_users, array("colspan" => "3"));
			$table->construct_row();
		}

		$table->output($lang->mychatbox_banned);

		$page->output_footer();

		exit;
	}
}

// &#71;&#101;&#110;&#101;&#114;&#105;&#99;

?>
