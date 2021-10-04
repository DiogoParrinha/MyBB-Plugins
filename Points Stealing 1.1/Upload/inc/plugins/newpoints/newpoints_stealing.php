<?php
/***************************************************************************
 *
 *   NewPoints Points Stealing plugin (/inc/plugins/newpoints/languages/english/newpoints_stealing.php)
 *	Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   License: licence.txt
 *
 *   Adds a points stealing system to NewPoints.
 *
 ***************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if (!defined("IN_ADMINCP"))
{
	$plugins->add_hook("newpoints_start", "newpoints_stealing_page");
	$plugins->add_hook("newpoints_default_menu", "newpoints_stealing_menu");
	$plugins->add_hook("newpoints_stats_start", "newpoints_stealing_stats");
}

function newpoints_stealing_info()
{
	return array(
		"name"			=> "Points Stealing",
		"description"	=> "Adds a points stealing system to NewPoints.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.1",
		"compatibility" 	=> "2*"
	);
}

function newpoints_stealing_install()
{
	global $db;

	// add settings
	$disporder = 0;
	newpoints_add_setting('newpoints_stealing_cost', 'newpoints_stealing', 'Cost', 'How many points do users have to spend to steal from another user?', 'text', '100', ++$disporder);
	newpoints_add_setting('newpoints_stealing_chance', 'newpoints_stealing', 'Chance', 'What is the chance, out of a 100, that a stealing try is successful?', 'text', '10', ++$disporder);
	newpoints_add_setting('newpoints_stealing_blocker', 'newpoints_stealing', 'Blocker Item', 'Enter the item ID of the Shop item that allows users to block other users from blocking them.', 'text', '', ++$disporder);
	newpoints_add_setting('newpoints_stealing_sendpm', 'newpoints_stealing', 'Send PM Alerts', 'Select whether or not PM alerts are sent. The content of the PMs can be changed in the language files.', 'yesno', '1', ++$disporder);
	newpoints_add_setting('newpoints_stealing_laststealers', 'newpoints_stealing', 'Last Stealers', 'Enter how many last stealers are displayed in the statistics.', 'text', '10', ++$disporder);
	newpoints_add_setting('newpoints_stealing_flood', 'newpoints_stealing', 'Flood Check', 'Enter how many seconds must pass before a user can steal again.', 'text', '15', ++$disporder);
	newpoints_add_setting('newpoints_stealing_maxpoints', 'newpoints_stealing', 'Maximum Points', 'Enter how many points users can try to steal from other users. Leave empty to disable the maximum.', 'text', '500', ++$disporder);

	rebuild_settings();
}

function newpoints_stealing_is_installed()
{
	global $db;

	$q = $db->simple_select('newpoints_settings', '*', 'name=\'newpoints_stealing_cost\'');
	$s = $db->fetch_array($q);
	if(!empty($s))
	{
		return true;
	}
	return false;
}

function newpoints_stealing_uninstall()
{
	global $db;

	// delete settings
	newpoints_remove_settings("'newpoints_stealing_cost','newpoints_stealing_chance','newpoints_stealing_blocker','newpoints_stealing_sendpm','newpoints_stealing_laststealers','newpoints_stealing_maxpoints'");
	rebuild_settings();

	newpoints_remove_log(array('stealing_stole'));
}

function newpoints_stealing_activate()
{
	global $db, $mybb;

	newpoints_add_template('newpoints_stealing', '
<html>
	<head>
		<title>{$lang->newpoints} - {$lang->newpoints_stealing}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				<td valign="top" width="180">
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead"><strong>{$lang->newpoints_menu}</strong></td>
						</tr>
						{$options}
					</table>
				</td>
				<td valign="top">
					{$inline_errors}
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead"><strong>{$lang->newpoints_stealing}</strong></td>
						</tr>
						<tr>
							<td class="trow1">{$lang->newpoints_stealing_info}</td>
						</tr>
						<tr>
							<td class="trow1" align="center">
								<form action="newpoints.php?action=stealing" method="post">
									<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
									<strong>{$lang->newpoints_stealing_points}:</strong><br />
									<input type="text" class="textbox" name="points" /><br />
									<br />
									<strong>{$lang->newpoints_stealing_victim}:</strong><br />
									<input type="text" class="textbox" name="username" id="username" /><br />
									<br />
									<input type="submit" name="submit" class="button" value="{$lang->newpoints_stealing_steal}" />
								</form>
							</td>
						</tr>
						{$stealing}
					</table>
				</td>
			</tr>
		</table>
		<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css">
		<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1804"></script>
		<script type="text/javascript">
		<!--
		if(use_xmlhttprequest == "1")
		{
			MyBB.select2();
			$("#username").select2({
				placeholder: "{$lang->search_user}",
				minimumInputLength: 3,
				maximumSelectionSize: 3,
				multiple: false,
				ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
					url: "xmlhttp.php?action=get_users",
					dataType: \'json\',
					data: function (term, page) {
						return {
							query: term, // search term
						};
					},
					results: function (data, page) { // parse the results into the format expected by Select2.
						// since we are using custom formatting functions we do not need to alter remote JSON data
						return {results: data};
					}
				},
				initSelection: function(element, callback) {
					var value = $(element).val();
					if (value !== "") {
						callback({
							id: value,
							text: value
						});
					}
				},
			});
		}
		// -->
		</script>
		{$footer}
	</body>
</html>');

	newpoints_add_template('newpoints_stealing_stats', '
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="4"><strong>{$lang->newpoints_stealing_laststealers}</strong></td>
</tr>
<tr>
<td class="tcat" width="30%"><strong>{$lang->newpoints_stealing_stealer}</strong></td>
<td class="tcat" width="30%"><strong>{$lang->newpoints_stealing_victim}</strong></td>
<td class="tcat" width="20%" align="center"><strong>{$lang->newpoints_stealing_amount}</strong></td>
<td class="tcat" width="20%" align="center"><strong>{$lang->newpoints_stealing_date}</strong></td>
</tr>
{$rows}
</table><br />');

	newpoints_add_template('newpoints_stealing_stats_row', '
<tr>
<td class="{$bgcolor}">{$row[\'user\']}</td>
<td class="{$bgcolor}">{$row[\'victim\']}</td>
<td class="{$bgcolor}" align="center">{$row[\'amount\']}</td>
<td class="{$bgcolor}" align="center">{$row[\'date\']}</td>
</tr>');

	newpoints_add_template('newpoints_stealing_stats_nodata', '
<tr>
<td class="trow1" width="100%" colspan="4">{$lang->newpoints_stealing_no_data}</td>
</tr>');

	// edit templates
	newpoints_find_replace_templatesets('newpoints_statistics', '#'.preg_quote('width="60%">').'#', 'width="60%">{$newpoints_stealing_stats}');
}

function newpoints_stealing_deactivate()
{
	global $db, $mybb;

	newpoints_remove_templates("'newpoints_stealing','newpoints_stealing_stats','newpoints_stealing_stats_row','newpoints_stealing_stats_nodata'");

	// edit templates
	newpoints_find_replace_templatesets('newpoints_statistics', '#'.preg_quote('{$newpoints_stealing_stats}').'#', '');
}

// show stealing in the list
function newpoints_stealing_menu(&$menu)
{
	global $mybb, $lang;
	newpoints_lang_load("newpoints_stealing");

	if ($mybb->input['action'] == 'stealing')
		$menu[] = "&raquo; <a href=\"{$mybb->settings['bburl']}/newpoints.php?action=stealing\">".$lang->newpoints_stealing."</a>";
	else
		$menu[] = "<a href=\"{$mybb->settings['bburl']}/newpoints.php?action=stealing\">".$lang->newpoints_stealing."</a>";
}

function newpoints_stealing_page()
{
	global $mybb, $db, $lang, $cache, $theme, $header, $templates, $plugins, $headerinclude, $footer, $options, $inline_errors;

	if($mybb->input['action'] != 'stealing')
		return;

	if (!$mybb->user['uid'])
		error_no_permission();

	newpoints_lang_load("newpoints_stealing");

	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);

		// Check points
		$mybb->input['points'] = (float)$mybb->input['points'];
		/*if($mybb->input['points'] > (float)$mybb->user['newpoints'])
		{
			error($lang->newpoints_stealing_not_enough_points);
		}*/

		// Check flood
		$q = $db->simple_select('newpoints_log', '*', '(action=\'stealing_stole\' OR action=\'stealing_failed\' OR action=\'stealing_blocked\') AND uid='.(int)$mybb->user['uid'], array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => 1));
		$log = $db->fetch_array($q);
		if($log['date'] > (TIME_NOW-(int)$mybb->settings['newpoints_stealing_flood']))
		{
			error($lang->sprintf($lang->newpoints_stealing_flood, ($log['date']-(TIME_NOW-(int)$mybb->settings['newpoints_stealing_flood']))));
		}

		// Validate points maximum
		if((int)$mybb->settings['newpoints_stealing_maxpoints'] != 0)
		{
			if((float)$mybb->input['points'] > (float)$mybb->settings['newpoints_stealing_maxpoints'])
			{
				error($lang->newpoints_stealing_over_maxpoints);
			}
		}

		if((float)$mybb->input['points'] <= 0)
		{
			error($lang->newpoints_stealing_invalid_points);
		}

		// Validate user
		$fields = array('uid','username','newpoints');
		if(function_exists('newpoints_shop_get_item'))
		{
			$fields[] = 'newpoints_items';
		}
		$user = get_user_by_username($mybb->get_input('username'), array('fields' => $fields));
		if(empty($user))
		{
			error($lang->newpoints_stealing_invalid_user);
		}

		if($user['uid'] == $mybb->user['uid'])
			error($lang->newpoints_stealing_self);

		// Do we have enough points?
		if((float)$mybb->settings['newpoints_stealing_cost'] > (float)$mybb->user['newpoints'])
		{
			error($lang->newpoints_stealing_own_points);
		}

		// Does the victim have enough points?
		if((float)$mybb->input['points'] > (float)$user['newpoints'])
		{
			error($lang->newpoints_stealing_victim_points);
		}

		// Check if user has blocker item
		if(function_exists('newpoints_shop_get_item') && (int)$mybb->settings['newpoints_stealing_blocker'] > 0)
		{
			$useritems = @unserialize($user['newpoints_items']);
			if(!empty($useritems))
			{
				// make sure we own the item
				$key = array_search((int)$mybb->settings['newpoints_stealing_blocker'], $useritems);
				if ($key !== false)
				{
					// Remove item from user
					unset($useritems[$key]);
					sort($useritems);
					$db->update_query('users', array('newpoints_items' => $db->escape_string(serialize($useritems))), 'uid=\''.(int)$user['uid'].'\'');

					// Send PM to victim
					send_pm(array(
						'subject' => $lang->newpoints_stealing_pm_blocked_subject,
						'message' => $lang->sprintf($lang->newpoints_stealing_pm_blocked_message, $mybb->user['username'], newpoints_format_points($mybb->input['points'])),
						'touid' => (int)$user['uid'],
						'receivepms' => 1
					), 0, true);

					// Log
					newpoints_log('stealing_blocked', $lang->sprintf($lang->newpoints_stealing_blocked_log, $user['uid'], $mybb->input['points'], $mybb->settings['newpoints_stealing_cost']));

					error($lang->newpoints_stealing_blocked);
				}
			}
		}

		// Get money from user
		newpoints_addpoints($mybb->user['uid'], -(floatval($mybb->settings['newpoints_stealing_cost'])));

		// Successful? Get points from victim
		$r = mt_rand(1, 100);
		if((float)$r > (float)$mybb->settings['newpoints_stealing_chance'])
		{
			send_pm(array(
				'subject' => $lang->newpoints_stealing_pm_failed_subject,
				'message' => $lang->sprintf($lang->newpoints_stealing_pm_failed_message, $mybb->user['username'], newpoints_format_points($mybb->input['points'])),
				'touid' => (int)$user['uid'],
				'receivepms' => 1
			), 0, true);

			// Log
			newpoints_log('stealing_failed', $lang->sprintf($lang->newpoints_stealing_failed_log, $user['uid'], $mybb->input['points'], $mybb->settings['newpoints_stealing_cost']));

			error($lang->newpoints_stealing_failed);
		}

		// Success
		newpoints_addpoints($user['uid'], -(floatval($mybb->input['points'])));
		newpoints_addpoints($mybb->user['uid'], (floatval($mybb->input['points'])));

		// Send PM
		send_pm(array(
			'subject' => $lang->newpoints_stealing_pm_stolen_subject,
			'message' => $lang->sprintf($lang->newpoints_stealing_pm_stolen_message, $mybb->user['username'], newpoints_format_points($mybb->input['points'])),
			'touid' => (int)$user['uid'],
			'receivepms' => 1
		), 0, true);

		// Log
		newpoints_log('stealing_stole', $lang->sprintf($lang->newpoints_stealing_stole_log, $user['uid'], $mybb->input['points'], $mybb->settings['newpoints_stealing_cost']));

		//redirect($mybb->settings['bburl']."/newpoints.php?action=stealing", $lang->newpoints_stealing_redirect);
		error($lang->sprintf($lang->newpoints_stealing_success, newpoints_format_points((float)$mybb->input['points']), htmlspecialchars_uni($user['username'])), $lang->newpoints_stealing_success_title);
	}

	$lang->newpoints_stealing_info = $lang->sprintf($lang->newpoints_stealing_info, newpoints_format_points($mybb->settings['newpoints_stealing_cost']), number_format($mybb->settings['newpoints_stealing_chance'], 2), newpoints_format_points($mybb->settings['newpoints_stealing_maxpoints']));

	eval("\$page = \"".$templates->get('newpoints_stealing')."\";");

	// output page
	output_page($page);
}


function newpoints_stealing_stats()
{
	global $mybb, $db, $templates, $cache, $theme, $newpoints_stealing_stats, $rows, $lang;

	// load language
	newpoints_lang_load("newpoints_stealing");
	$rows = '';

	// build stats table
	$query = $db->simple_select('newpoints_log', '*', 'action=\'stealing_stole\'', array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => intval($mybb->settings['newpoints_stealing_laststealers'])));
	while($row = $db->fetch_array($query)) {
		$bgcolor = alt_trow();
		$data = explode('-', $row['data']);

		// Stealer
		$link = build_profile_link(htmlspecialchars_uni($row['username']), intval($row['uid']));
		$row['user'] = $link;

		// Victim
		$q = $db->simple_select('users', 'username', 'uid='.(int)$data[0]);
		$victim = $db->fetch_field($q, 'username');
		$row['victim'] = build_profile_link(htmlspecialchars_uni($victim), intval($data[0]));

		// Amount
		$row['amount'] = newpoints_format_points($data[1]);

		// Date
		$row['date'] = my_date($mybb->settings['dateformat'], intval($row['date']), '', false);

		eval("\$rows .= \"".$templates->get('newpoints_stealing_stats_row')."\";");
	}

	if (!$rows)
		eval("\$rows = \"".$templates->get('newpoints_stealing_stats_nodata')."\";");

	eval("\$newpoints_stealing_stats = \"".$templates->get('newpoints_stealing_stats')."\";");
}


?>
