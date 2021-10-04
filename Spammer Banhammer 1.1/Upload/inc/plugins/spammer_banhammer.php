<?php
/***************************************************************************
 *
 *  Spammer Banhammer plugin (/inc/plugins/spammer_banhammer.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  Adds a new page which suggests spammers to be banned quickly.",
 *
 ***************************************************************************/

// do NOT remove for security reasons!
if(!defined("IN_MYBB"))
{
	$secure = "-#77;-#121;-#66;-#66;-#45;-#80;-#108;-#117;-#103;-#105;-#110;-#115;";
	$secure = str_replace("-", "&", $secure);
	die("This file cannot be accessed directly.".$secure);
}

if (!defined("IN_ADMINCP"))
{
	$plugins->add_hook('modcp_start', 'spammer_banhammer_modcp');
}

if(THIS_SCRIPT == 'modcp.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }

	if($GLOBALS['mybb']->input['action'] == 'spammer_banhammer')
		$templatelist .= 'spammer_banhammer,spammer_banhammer_row,spammer_banhammer_no_data,spammer_banhammer_nav';
	else
		$templatelist .= 'spammer_banhammer_nav';

}

function spammer_banhammer_info()
{
	return array(
		"name"			=> "Spammer Banhammer",
		"description"	=> "Adds a new page which suggests spammers to be banned quickly.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.1",
		"guid" 			=> "",
		"compatibility"	=> "18*"
	);
}


function spammer_banhammer_install()
{
	global $db, $lang;

	// create settings group
	$insertarray = array(
		'name' => 'spammer_banhammer',
		'title' => 'Spammer Banhammer',
		'description' => "Settings for Spammer Banhammer plugin.",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting = array(
		"name"			=> "spammer_banhammer_groups",
		"title"			=> "Allowed Groups",
		"description"	=> "Select the groups that can use the Spammer Banhammer.",
		"optionscode"	=> "groupselect",
		"value"			=> '4,6,3',
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "spammer_banhammer_maxposts",
		"title"			=> "Maximum Posts",
		"description"	=> "Enter the maximum posts after which users no longer appear on the Spammer Banhammer page.",
		"optionscode"	=> "text",
		"value"			=> '1',
		"disporder"		=> 2,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "spammer_banhammer_banip",
		"title"			=> "Ban IPs",
		"description"	=> "Select whether or not you want to ban IPs when purging spammers. Note that banning IPs might cause other users not to be able to login if their IPs are dynamic and they end up using a previously banned IP.",
		"optionscode"	=> "yesno",
		"value"			=> '0',
		"disporder"		=> 3,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "spammer_banhammer_biography",
		"title"			=> "Biography Profile Field",
		"description"	=> "Enter the profile field ID of the biography field. Leave empty to disable this. Default is 2. (fid2)",
		"optionscode"	=> "text",
		"value"			=> '2',
		"disporder"		=> 4,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	$setting = array(
		"name"			=> "spammer_banhammer_maxpurge",
		"title"			=> "Maximum Users to Purge",
		"description"	=> "Enter how many users the Purge All feature should purge when it\'s run. A big number may cause a 500 Internal Server error if there are many spammers matching the selected criteria.",
		"optionscode"	=> "text",
		"value"			=> '100',
		"disporder"		=> 5,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting);

	rebuild_settings();
}

function spammer_banhammer_is_installed()
{
	global $db;

	$q = $db->simple_select('settings', '*', 'name=\'spammer_banhammer_maxposts\'');
	$setting = $db->fetch_array($q);
	if (!empty($setting))
		return true;
	else
		false;
}

function spammer_banhammer_uninstall()
{
	global $db, $mybb;

	// delete settings group
	$db->delete_query("settinggroups", "name = 'spammer_banhammer'");

	// remove settings
	$db->delete_query('settings', 'name LIKE \'%spammer_banhammer%\'');

	rebuild_settings();
}

function spammer_banhammer_activate()
{
	global $db, $lang;

	$template = array(
		"tid" => "NULL",
		"title" => "spammer_banhammer",
		"template" => $db->escape_string('<html>
	<head>
	<title>{$mybb->settings[\'bbname\']} - {$lang->spammer_banhammer}</title>
	{$headerinclude}
	</head>
	<body>
	{$header}
	<table width="100%" border="0" align="center">
		<tr>
			{$modcp_nav}
			<td valign="top">
				{$multipage}
				<form name="spammer_banhammer" action="modcp.php" method="POST">
					<input type="hidden" name="action" value="spammer_banhammer" />
					<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead" colspan="7">
								<strong>{$lang->spammer_banhammer_suggestions} - {$total}</strong>
							</td>
						</tr>
						<tr>
							<td class="tcat" width="1"><input type="checkbox" name="check" class="checkbox check" /></td>
							<td class="tcat" width="20%"><strong>{$lang->spammer_banhammer_username}</strong></td>
							<td class="tcat" width="20%"><strong>{$lang->spammer_banhammer_homepage}</strong></td>
							<td class="tcat" width="30%"><strong>{$lang->spammer_banhammer_biography}</strong></td>
							<td class="tcat" width="30%"><strong>{$lang->spammer_banhammer_signature}</strong></td>
						</tr>
						{$rows}
						<tr>
							<td class="tfoot" colspan="7" align="center">
								<input type="submit" class="button" name="submit" value="{$lang->spammer_banhammer_purgeselected}" onclick="return confirm(\'{$lang->spammer_banhammer_purgeselected_confirm}\');" />
								<br />
								<br />
								<input type="submit" class="button" name="purgeall" value="{$lang->spammer_banhammer_purgeall}" onclick="return confirm(\'{$lang->spammer_banhammer_purgeselected_confirm_all}\');" />
								&nbsp;
								{$lang->spammer_banhammer_with}
								&nbsp;
								<select name="condition">
									<option value="0">{$lang->spammer_banhammer_condition0}</option>
									<option value="1">{$lang->spammer_banhammer_condition1}</option>
									<option value="2">{$lang->spammer_banhammer_condition2}</option>
									<option value="3">{$lang->spammer_banhammer_condition3}</option>
									<option value="4">{$lang->spammer_banhammer_condition4}</option>
									<option value="5">{$lang->spammer_banhammer_condition5}</option>
									<option value="6">{$lang->spammer_banhammer_condition6}</option>
								</select>
							</td>
						</tr>
					</table>
				</form>
				{$multipage}
				<script type="text/javascript">
					$(document).ready(function(){
					    $(\'.check\').click(function(){
						   $(\'.bancheck\').not(this).prop(\'checked\', this.checked);
					    })
					})
				</script>
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
		"title" => "spammer_banhammer_row",
		"template" => $db->escape_string('
				<tr>
					<td class="{$bgcolor}" valign="top">
						<input type="checkbox" name="ban[]" value="{$user[\'uid\']}" class="checkbox bancheck" />
					</td>
					<td class="{$bgcolor}" valign="top">
						{$user[\'username\']}
						<br />
						<strong>{$lang->spammer_banhammer_postcount}:</strong> {$user[\'postnum\']}
						<br />
						<strong>{$lang->spammer_banhammer_usergroup}:</strong> {$user[\'usergroup\']}
					</td>
					<td class="{$bgcolor}" valign="top">
						{$user[\'website\']}
					</td>
					<td class="{$bgcolor}" valign="top">
						{$user[\'biography\']}
					</td>
					<td class="{$bgcolor}" valign="top">
						{$user[\'signature\']}
					</td>
				</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"tid" => "NULL",
		"title" => "spammer_banhammer_nav",
		"template" => $db->escape_string('
		<tr><td class="trow1 smalltext"><a href="modcp.php?action=spammer_banhammer" class="modcp_nav_item modcp_avspammer_banhammer">{$lang->spammer_banhammer}</td></tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"tid" => "NULL",
		"title" => "spammer_banhammer_no_data",
		"template" => $db->escape_string('
<tr>
	<td class="trow1" colspan="7">{$lang->spammer_banhammer_no_data}</td>
</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('modcp_nav_forums_posts', '#'.preg_quote('{$nav_announcements}').'#', '{$nav_announcements}'.'{spammer_banhammer}');
}

function spammer_banhammer_deactivate()
{
	global $db, $mybb;

	// delete templates
	$db->delete_query('templates', 'title LIKE \'%spammer_banhammer%\'');

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('modcp_nav_forums_posts', '#'.preg_quote('{spammer_banhammer}').'#', '', 0);
}

// Used with groupselect setting type
function spammer_banhammer_check_permissions($groups_comma)
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

function spammer_banhammer_modcp()
{
	global $mybb, $lang, $db, $cache, $modcp_nav, $templates;

	$lang->load("spammer_banhammer");

	eval("\$spammer_banhammer = \"".$templates->get("spammer_banhammer_nav")."\";");
	$modcp_nav = str_replace('{spammer_banhammer}', $spammer_banhammer, $modcp_nav);

	if ($mybb->input['action'] != 'spammer_banhammer')
		return;

	if(!spammer_banhammer_check_permissions($mybb->settings['spammer_banhammer_groups']))
		error_no_permission();

	$maxposts = abs((int)$mybb->settings['spammer_banhammer_maxposts']);

	if($mybb->request_method == "post")
	{
		// Post key verification
		verify_post_check($mybb->input['my_post_key']);

		require_once MYBB_ROOT.'inc/datahandlers/user.php';
		$userhandler = new UserDataHandler('delete');

		// We want to purge all
		if(isset($mybb->input['purgeall']) && $mybb->input['purgeall'] != '')
		{
			if($mybb->input['condition'] < 0 || $mybb->input['condition'] > 6)
				error();

			$sql_query = "u.postnum<={$maxposts} AND (u.usergroup=2 OR u.usergroup=5)";

			$biography_sql = '';
			if($mybb->settings['spammer_banhammer_biography'] != '')
			{
				$biography_sql = '(uf.fid'.(int)$mybb->settings['spammer_banhammer_biography'].' LIKE \'%http://%\' OR uf.fid'.(int)$mybb->settings['spammer_banhammer_biography'].' LIKE \'%https://%\')';
			}

			// Links in signature, website and biography
			if($mybb->input['condition'] == 0)
			{
				$sql_query .= " AND (u.signature LIKE '%http://%' OR u.signature LIKE '%https://%')";
				$sql_query .= " AND u.website!=''";

				if($mybb->settings['spammer_banhammer_biography'] != '')
				{
					$sql_query .= ' AND '.$biography_sql;
				}
			}

			// Links in signature and biography
			if($mybb->input['condition'] == 1)
			{
				$sql_query .= " AND (u.signature LIKE '%http://%' OR u.signature LIKE '%https://%')";

				if($mybb->settings['spammer_banhammer_biography'] != '')
				{
					$sql_query .= ' AND '.$biography_sql;
				}
			}

			// Links in signature and website
			if($mybb->input['condition'] == 2)
			{
				$sql_query .= " AND (u.signature LIKE '%http://%' OR u.signature LIKE '%https://%')";
				$sql_query .= " AND u.website!=''";
			}

			// Links in website and biography
			if($mybb->input['condition'] == 3)
			{
				$sql_query .= " AND u.website!=''";

				if($mybb->settings['spammer_banhammer_biography'] != '')
				{
					$sql_query .= ' AND '.$biography_sql;
				}
			}

			// Links in signature
			if($mybb->input['condition'] == 4)
			{
				$sql_query .= " AND (u.signature LIKE '%http://%' OR u.signature LIKE '%https://%')";
			}

			// Links in website
			if($mybb->input['condition'] == 5)
			{
				$sql_query .= " AND u.website!=''";
			}

			// Links in biography
			if($mybb->input['condition'] == 6)
			{
				if($mybb->settings['spammer_banhammer_biography'] != '')
				{
					$sql_query .= ' AND '.$biography_sql;
				}
			}

			@set_time_limit(0);

			if($mybb->settings['spammer_banhammer_maxpurge'] <= 0)
				$mybb->settings['spammer_banhammer_maxpurge'] = 100;

			$uids = array();
			$rows = '';
			$q = $db->query("
				SELECT u.uid,u.username,u.lastip,u.regip
				FROM `".TABLE_PREFIX."users` u
				LEFT JOIN `".TABLE_PREFIX."userfields` uf ON (uf.ufid=u.uid)
				WHERE {$sql_query}
				ORDER BY u.username ASC
				LIMIT {$mybb->settings['spammer_banhammer_maxpurge']}
			");
			while($user = $db->fetch_array($q))
			{
				$uid = (int)$user['uid'];

				$uids[] = $uid;

				// First delete everything
				$userhandler->delete_content($uid);
				$userhandler->delete_posts($uid);

				// Next ban him (or update the banned reason, shouldn't happen)
				$query = $db->simple_select("banned", "uid", "uid = '{$uid}'");
				if($db->num_rows($query) > 0)
				{
					$banupdate = array(
						"reason" => $db->escape_string($mybb->settings['purgespammerbanreason'])
					);
					$db->update_query('banned', $banupdate, "uid = '{$uid}'");
				}
				else
				{
					$insert = array(
						"uid" => $uid,
						"gid" => (int)$mybb->settings['purgespammerbangroup'],
						"oldgroup" => 2,
						"oldadditionalgroups" => "",
						"olddisplaygroup" => 0,
						"admin" => (int)$mybb->user['uid'],
						"dateline" => TIME_NOW,
						"bantime" => "---",
						"lifted" => 0,
						"reason" => $db->escape_string($mybb->settings['purgespammerbanreason'])
					);
					$db->insert_query('banned', $insert);
				}

				if($mybb->settings['spammer_banhammer_banip'])
				{
					// Add the IP's to the banfilters
					foreach(array($user['regip'], $user['lastip']) as $ip)
					{
						$ip = my_inet_ntop($db->unescape_binary($ip));
						$query = $db->simple_select("banfilters", "type", "type = 1 AND filter = '".$db->escape_string($ip)."'");
						if($db->num_rows($query) == 0)
						{
							$insert = array(
								"filter" => $db->escape_string($ip),
								"type" => 1,
								"dateline" => TIME_NOW
							);
							$db->insert_query("banfilters", $insert);
						}
					}
				}

				// Clear the profile
				$userhandler->clear_profile($uid, $mybb->settings['purgespammerbangroup']);

				$cache->update_banned();
				$cache->update_bannedips();
				$cache->update_awaitingactivation();

				// Update reports cache
				$cache->update_reportedcontent();

				log_moderator_action(array('uid' => $uid, 'username' => $user['username']), $lang->spammer_banhammer_modlog);
			}

			redirect('modcp.php?action=spammer_banhammer', $lang->sprintf($lang->spammer_banhammer_purge_successful2, count($uids)));

			/*echo "<pre>
				SELECT u.uid,u.username,u.usergroup,u.postnum,u.signature,u.website{$sql_field}
				FROM `".TABLE_PREFIX."users` u
				LEFT JOIN `".TABLE_PREFIX."userfields` uf ON (uf.ufid=u.uid)
				WHERE {$sql_query}
				ORDER BY u.username ASC
			</pre><br />";

			echo "<pre>";
			print_r($uids);
			echo "</pre>";
			exit;*/
		}

		// Get selected users
		if(!is_array($mybb->input['ban']) || empty($mybb->input['ban']))
		{
			error($lang->spammer_banhammer_invalid_selected);
		}

		$selected = array_map('intval', $mybb->input['ban']);

		// Now must check that each user was actually disaplyed in the banhammer page
		// (we don't want someone tricking the system into banning an admin or a valid user!)
		$biography_field = '';
		$biography_sql = '';
		if($mybb->settings['spammer_banhammer_biography'] != '')
		{
			$biography_field = ',uf.fid'.(int)$mybb->settings['spammer_banhammer_biography'].' as biography';
			$biography_sql = 'OR uf.fid'.(int)$mybb->settings['spammer_banhammer_biography'].' LIKE \'%http://%\' OR uf.fid'.(int)$mybb->settings['spammer_banhammer_biography'].' LIKE \'%https://%\'';
		}

		$rows = '';
		$q = $db->query("
			SELECT u.uid,u.username,u.usergroup,u.postnum,u.signature,u.lastip,u.regip,u.website{$biography_field}
			FROM `".TABLE_PREFIX."users` u
			LEFT JOIN `".TABLE_PREFIX."userfields` uf ON (uf.ufid=u.uid)
			WHERE
				u.postnum<={$maxposts}
				AND
				(u.usergroup=2 OR u.usergroup=5)
				AND
				(
					u.website!=''
					OR
					u.signature LIKE '%http://%' OR u.signature LIKE '%https://%'
					{$biography_sql}
				)
				AND u.uid IN (".implode(',', $selected).")
			ORDER BY u.username ASC
		");
		while($user = $db->fetch_array($q))
		{
			// Banhammer!!
			$uid = (int)$user['uid'];

			// First delete everything
			$userhandler->delete_content($uid);
			$userhandler->delete_posts($uid);

			// Next ban him (or update the banned reason, shouldn't happen)
			$query = $db->simple_select("banned", "uid", "uid = '{$uid}'");
			if($db->num_rows($query) > 0)
			{
				$banupdate = array(
					"reason" => $db->escape_string($mybb->settings['purgespammerbanreason'])
				);
				$db->update_query('banned', $banupdate, "uid = '{$uid}'");
			}
			else
			{
				$insert = array(
					"uid" => $uid,
					"gid" => (int)$mybb->settings['purgespammerbangroup'],
					"oldgroup" => 2,
					"oldadditionalgroups" => "",
					"olddisplaygroup" => 0,
					"admin" => (int)$mybb->user['uid'],
					"dateline" => TIME_NOW,
					"bantime" => "---",
					"lifted" => 0,
					"reason" => $db->escape_string($mybb->settings['purgespammerbanreason'])
				);
				$db->insert_query('banned', $insert);
			}

			if($mybb->settings['spammer_banhammer_banip'])
			{
				// Add the IP's to the banfilters
				foreach(array($user['regip'], $user['lastip']) as $ip)
				{
					$ip = my_inet_ntop($db->unescape_binary($ip));
					$query = $db->simple_select("banfilters", "type", "type = 1 AND filter = '".$db->escape_string($ip)."'");
					if($db->num_rows($query) == 0)
					{
						$insert = array(
							"filter" => $db->escape_string($ip),
							"type" => 1,
							"dateline" => TIME_NOW
						);
						$db->insert_query("banfilters", $insert);
					}
				}
			}

			// Clear the profile
			$userhandler->clear_profile($uid, $mybb->settings['purgespammerbangroup']);

			$cache->update_banned();
			$cache->update_bannedips();
			$cache->update_awaitingactivation();

			// Update reports cache
			$cache->update_reportedcontent();

			log_moderator_action(array('uid' => $uid, 'username' => $user['username']), $lang->spammer_banhammer_modlog);
		}

		redirect('modcp.php?action=spammer_banhammer', $lang->spammer_banhammer_purge_successful);
	}

	global $theme, $headerinclude, $header, $footer;

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

	require_once MYBB_ROOT."inc/class_parser.php";
	$parser = new postParser;

	$sig_parser = array(
		"allow_html" => $mybb->settings['sightml'],
		"allow_mycode" => $mybb->settings['sigmycode'],
		"allow_smilies" => $mybb->settings['sigsmilies'],
		"allow_imgcode" => $mybb->settings['sigimgcode'],
		"filter_badwords" => 1,
		"allow_imgcode" => 0,
		"nofollow_on" => 1
	);

	$usergroups = $cache->read("usergroups");

	$biography_field = '';
	$biography_sql = '';
	if($mybb->settings['spammer_banhammer_biography'] != '')
	{
		$biography_field = ',uf.fid'.(int)$mybb->settings['spammer_banhammer_biography'].' as biography';
		$biography_sql = 'OR uf.fid'.(int)$mybb->settings['spammer_banhammer_biography'].' LIKE \'%http://%\' OR uf.fid'.(int)$mybb->settings['spammer_banhammer_biography'].' LIKE \'%https://%\'';
	}

	// Build multipage
	$q = $db->query("
		SELECT COUNT(u.uid) as totalusers
		FROM `".TABLE_PREFIX."users` u
		LEFT JOIN `".TABLE_PREFIX."userfields` uf ON (uf.ufid=u.uid)
		WHERE
			u.postnum<={$maxposts}
			AND
			(u.usergroup=2 OR u.usergroup=5)
			AND
			(
				u.website!=''
				OR
				u.signature LIKE '%http://%' OR u.signature LIKE '%https://%'
				{$biography_sql}
			)
		ORDER BY u.username ASC
	");
	$total = $db->fetch_field($q, 'totalusers');
	if($total > 0)
	{
		$multipage = multipage($total, $per_page, $mybb->input['page'], "modcp.php?action=spammer_banhammer");
	}

	$rows = '';
	$q = $db->query("
		SELECT u.uid,u.username,u.usergroup,u.postnum,u.signature,u.website{$biography_field}
		FROM `".TABLE_PREFIX."users` u
		LEFT JOIN `".TABLE_PREFIX."userfields` uf ON (uf.ufid=u.uid)
		WHERE
			u.postnum<={$maxposts}
			AND
			(u.usergroup=2 OR u.usergroup=5)
			AND
			(
				u.website!=''
				OR
				u.signature LIKE '%http://%' OR u.signature LIKE '%https://%'
				{$biography_sql}
			)
		ORDER BY u.username ASC
		LIMIT {$start},{$per_page}
	");
	while($user = $db->fetch_array($q))
	{
		$bgcolor = alt_trow();

		$user['username'] = build_profile_link(htmlspecialchars_uni($user['username']), intval($user['uid']));
		$user['postnum'] = (int)$user['postnum'];
		$user['website'] = htmlspecialchars_uni($user['website']);

		// use sig parser for biography as well (no need to get the parser options for the biography custom field)
		if(isset($user['biography']) && $user['biography'] != '')
		{
			$user['biography'] = $parser->parse_message($user['biography'], $sig_parser);
		}
		else
			$user['biography'] = '';

		$user['signature'] = $parser->parse_message($user['signature'], $sig_parser);

		$user['usergroup'] = htmlspecialchars_uni($usergroups[$user['usergroup']]['title']);

		eval("\$rows .= \"".$templates->get("spammer_banhammer_row")."\";");
	}

	if($rows == '')
	{
		eval("\$rows = \"".$templates->get("spammer_banhammer_no_data")."\";");
	}

	if($mybb->settings['spammer_banhammer_maxpurge'] <= 0)
		$mybb->settings['spammer_banhammer_maxpurge'] = 100;
	$lang->spammer_banhammer_purgeall = $lang->sprintf($lang->spammer_banhammer_purgeall, (int)$mybb->settings['spammer_banhammer_maxpurge']);
	$lang->spammer_banhammer_purgeselected_confirm_all = $lang->sprintf($lang->spammer_banhammer_purgeselected_confirm_all, (int)$mybb->settings['spammer_banhammer_maxpurge']);

	$lang->spammer_banhammer_condition0 = $lang->sprintf($lang->spammer_banhammer_condition0, $maxposts);
	$lang->spammer_banhammer_condition1 = $lang->sprintf($lang->spammer_banhammer_condition1, $maxposts);
	$lang->spammer_banhammer_condition2 = $lang->sprintf($lang->spammer_banhammer_condition2, $maxposts);
	$lang->spammer_banhammer_condition3 = $lang->sprintf($lang->spammer_banhammer_condition3, $maxposts);
	$lang->spammer_banhammer_condition4 = $lang->sprintf($lang->spammer_banhammer_condition4, $maxposts);
	$lang->spammer_banhammer_condition5 = $lang->sprintf($lang->spammer_banhammer_condition5, $maxposts);
	$lang->spammer_banhammer_condition6 = $lang->sprintf($lang->spammer_banhammer_condition6, $maxposts);

	eval("\$page = \"".$templates->get("spammer_banhammer")."\";");

	output_page($page);

	exit;
}
// MODCP END

// &#71;&#101;&#110;&#101;&#114;&#105;&#99;

?>
