<?php
/***************************************************************************
 *
 *   NewPoints Subscriptions plugin (/inc/plugins/newpoints/newpoints_subscriptions.php)
 *	 Author: Pirata Nervo
 *   Copyright: � 2014 Pirata Nervo
 *
 *   Website: http://www.mybb-plugins.com
 *
 *   Integrates a subscriptions system with NewPoints.
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

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if (defined("IN_ADMINCP"))
{
	// Subscriptions ACP page (Add/Edit/Delete subscription plans)
	$plugins->add_hook('newpoints_admin_load', 'newpoints_subscriptions_admin');
	$plugins->add_hook('newpoints_admin_newpoints_menu', 'newpoints_subscriptions_admin_newpoints_menu');
	$plugins->add_hook('newpoints_admin_newpoints_action_handler', 'newpoints_subscriptions_admin_newpoints_action_handler');
	$plugins->add_hook('newpoints_admin_newpoints_permissions', 'newpoints_subscriptions_admin_permissions');
}
else
{
	// show Subscriptions in the menu
	$plugins->add_hook("newpoints_default_menu", "newpoints_subscriptions_menu");
	$plugins->add_hook("newpoints_start", "newpoints_subscriptions_page");
}

// backup subscriptions fields too
$plugins->add_hook("newpoints_task_backup_tables", "newpoints_subscriptions_backup");

function newpoints_subscriptions_info()
{
	return array(
		"name"			=> "Subscriptions",
		"description"	=> "An advanced subscriptions system for NewPoints.",
		"website"		=> "http://www.mybb-plugins.com",
		"author"		=> "Pirata Nervo",
		"authorsite"	=> "http://www.mybb-plugins.com",
		"version"		=> "1.2.2",
		"guid" 			=> "",
		"compatibility" => "2*"
	);
}

function newpoints_subscriptions_install()
{
	global $db;

	$collation = $db->build_create_table_collation();

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_subscriptions` (
	  `sid` bigint(30) UNSIGNED NOT NULL auto_increment,
	  `title` varchar(100) NOT NULL default '',
	  `description` text NOT NULL,
	  `time` bigint(11) UNSIGNED NOT NULL default '0',
	  `years` tinyint(2) UNSIGNED NOT NULL default '0',
	  `months` tinyint(2) UNSIGNED NOT NULL default '0',
	  `days` int(10) UNSIGNED NOT NULL default '0',
	  `hours` int(10) UNSIGNED NOT NULL default '0',
	  `price` decimal(16,2) UNSIGNED NOT NULL default '0',
	  `group` tinyint(3) UNSIGNED NOT NULL default '0',
	  `additional` tinyint(1) UNSIGNED NOT NULL default '0',
	  `enabled` tinyint(1) UNSIGNED NOT NULL default '1',
	  PRIMARY KEY  (`sid`)
		) ENGINE=MyISAM{$collation}");

	// create task
	$new_task = array(
		"title" => "NewPoints Subscriptions",
		"description" => "Checks for members whose subscriptions have expired (each half hour).",
		"file" => "newpoints_subscriptions",
		"minute" => '0,30',
		"hour" => '*',
		"day" => '*',
		"month" => '*',
		"weekday" => '*',
		"enabled" => '0',
		"logging" => '1'
	);

	$new_task['nextrun'] = 0; // once the task is enabled, it will generate a nextrun date
	$tid = $db->insert_query("tasks", $new_task);

	// add settings
	newpoints_add_setting('newpoints_subscriptions_renew', 'newpoints_subscriptions', 'Auto Renewal', 'Is auto renewal enabled? This affects all subscriptions.', 'yesno', '', 1);
	rebuild_settings();
}

function newpoints_subscriptions_is_installed()
{
	global $db;
	if($db->table_exists('newpoints_subscriptions'))
	{
		return true;
	}
	return false;
}

function newpoints_subscriptions_uninstall()
{
	global $db;

	if($db->table_exists('newpoints_subscriptions'))
	{
		$db->drop_table('newpoints_subscriptions');
	}

	newpoints_remove_log(array('subscriptions'));

	$db->delete_query('tasks', 'file=\'newpoints_subscriptions\''); // delete all tasks that use newpoints_subscriptions task file

	// delete settings
	newpoints_remove_settings("'newpoints_subscriptions_renew'");
	rebuild_settings();
}

function newpoints_subscriptions_activate()
{
	global $db, $mybb;

	newpoints_add_template('newpoints_subscriptions', '
<html>
<head>
<title>{$lang->newpoints_subscriptions} - {$lang->newpoints}</title>
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
<td class="thead" colspan="5"><div style="float: right; font-weight: bold; border-bottom: 2px solid #00BBEC;"><a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=mysubscriptions">{$lang->newpoints_subscriptions_mysubscriptions}</a></div><strong>{$lang->newpoints_subscriptions_plans_primary}</strong></td>
</tr>
<tr>
<td class="tcat" width="40%"><strong>{$lang->newpoints_subscriptions_title}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_usergroup}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_period}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_price}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_subscribe}</strong></td>
</tr>
{$subplans_primary}
</table>
<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="5"><strong>{$lang->newpoints_subscriptions_plans_sub}</strong></td>
</tr>
<tr>
<td class="tcat" width="40%"><strong>{$lang->newpoints_subscriptions_title}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_usergroup}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_period}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_price}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_subscribe}</strong></td>
</tr>
{$subplans_sub}
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>');

	newpoints_add_template('newpoints_subscriptions_mysubscriptions', '
<html>
<head>
<title>{$lang->newpoints_subscriptions_mysubscriptions} - {$lang->newpoints_subscriptions} - {$lang->newpoints}</title>
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
<td class="thead" colspan="5"><div style="float: right; font-weight: bold; border-bottom: 2px solid #00BBEC;"><a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=subscriptions">{$lang->newpoints_subscriptions}</a></div><strong>{$lang->newpoints_subscriptions_mysubscriptions}</strong></td>
</tr>
<tr>
<td class="tcat" width="40%"><strong>{$lang->newpoints_subscriptions_title}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_usergroup}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_expires}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_price}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_subscriptions_auto_renewal}</strong></td>
</tr>
{$subplans}
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>');

newpoints_add_template('newpoints_subscriptions_row', '
<tr>
<td class="{$bgcolor}" width="40%">{$sub[\'title\']}<br /><span class="smalltext">{$sub[\'description\']}<span></td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'usergroup\']}</td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'period\']}</td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'price\']}</td>
<td class="{$bgcolor}" width="15%" align="center"><form action="newpoints.php" method="POST" name="subscribe"><input type="hidden" name="action" value="subscribe" /><input type="hidden" name="my_post_key" value="{$mybb->post_code}" /><input type="hidden" name="sid" value="{$sub[\'sid\']}" /><input type="submit" name="submit" value="{$lang->newpoints_subscriptions_subscribe}" /></form></td>
</tr>');

newpoints_add_template('newpoints_subscriptions_mysubscriptions_row', '
<tr>
<td class="{$bgcolor}" width="40%">{$sub[\'title\']}<br /><span class="smalltext">{$sub[\'description\']}<span></td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'usergroup\']}</td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'expires\']}</td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'price\']}</td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'renew\']}</td>
</tr>');

newpoints_add_template('newpoints_subscriptions_empty', '
<tr>
<td class="trow1" width="100%" colspan="5">{$lang->newpoints_subscriptions_empty}</td>
</tr>');

	newpoints_add_template('newpoints_subscriptions_subscribe', '
<html>
<head>
<title>{$lang->newpoints_subscriptions_subscribe} - {$lang->newpoints_subscriptions} - {$lang->newpoints}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="newpoints.php" method="POST">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}">
<input type="hidden" name="action" value="subscribe" />
<input type="hidden" name="sid" value="{$sub[\'sid\']}" />
<input type="hidden" name="subscribe_confirm" value="1" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->newpoints_subscriptions_subscribe_plan} "{$sub[\'title\']}"</strong></td>
</tr>
<tr>
<td class="trow1">
{$lang->newpoints_subscriptions_subscribe_confirm}
</td>
</tr>
<tr>
<td class="tfoot" width="100%" align="center" colspan="{$colspan}"><input type="submit" name="submit" value="{$lang->newpoints_subscriptions_subscribe}"></td>
</tr>
</table>
</form>
{$footer}
</body>
</html>');
}

function newpoints_subscriptions_deactivate()
{
	global $db, $mybb;

	newpoints_remove_templates("'newpoints_subscriptions','newpoints_subscriptions_subscribe','newpoints_subscriptions_row','newpoints_subscriptions_empty','newpoints_subscriptions_mysubscriptions','newpoints_subscriptions_mysubscriptions_row'");
}

// show subscriptions in the list
function newpoints_subscriptions_menu(&$menu)
{
	global $mybb, $lang;
	newpoints_lang_load("newpoints_subscriptions");

	if ($mybb->input['action'] == 'subscriptions')
		$menu[] = "&raquo; <a href=\"{$mybb->settings['bburl']}/newpoints.php?action=subscriptions\">".$lang->newpoints_subscriptions."</a>";
	else
		$menu[] = "<a href=\"{$mybb->settings['bburl']}/newpoints.php?action=subscriptions\">".$lang->newpoints_subscriptions."</a>";
}

function newpoints_subscriptions_page()
{
	global $mybb, $db, $lang, $cache, $theme, $header, $templates, $plugins, $headerinclude, $footer, $options, $inline_errors;

	if (!$mybb->user['uid'])
		return;

	if ($mybb->input['action'] != 'subscribe' && $mybb->input['action'] != 'subscriptions' && $mybb->input['action'] != 'mysubscriptions'
		 && $mybb->input['action'] != 'enable_renewal'  && $mybb->input['action'] != 'disable_renewal') return;

	newpoints_lang_load("newpoints_subscriptions");

	$plugins->run_hooks("newpoints_subscriptions_start");

	if ($mybb->input['action'] == 'subscribe')
	{
		verify_post_check($mybb->input['my_post_key']);

		$plugins->run_hooks("newpoints_subscriptions_subscribe");

		$sid = (int)$mybb->input['sid'];
		if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('newpoints_subscriptions', '*', "sid = $sid")))))
		{
			error($lang->newpoints_subscriptions_invalid_sub);
		}

		if (floatval($sub['price']) > floatval($mybb->user['newpoints']))
		{
			error($lang->newpoints_subscriptions_not_enough);
		}

		if ($mybb->user['usergroup'] == $sub['group']) // we're already in the group
		{
			error($lang->newpoints_subscriptions_already_group);
		}

		// check if we're in this additional group already
		if ($sub['additional'] == 1)
		{
			if (count(array_intersect(explode(",", $mybb->user['additionalgroups']), array($sub['group']))) != 0) // we're already in the group
			{
				error($lang->newpoints_subscriptions_already_group);
			}
		}
		// else check if we've subscribed to another plan: we cannot change our primary group and have two plans running
		else
		{
			$error = false;

			// let's check if we have a "running" plan
			$query = $db->simple_select('newpoints_log', 'data', 'action=\'subscriptions\' AND uid=\''.$mybb->user['uid'].'\'');
			while ($log = $db->fetch_array($query))
			{
				$data = explode('-', $log['data']);

				if ($data[6] == 1) break;; // if it has already expired, return

				$error = true;
			}

			if ($error === true)
				error($lang->newpoints_subscriptions_already_subscribed);
		}

		// if we've confirmed the subscription, then we can subscribe
		if ((int)$mybb->input['subscribe_confirm'] == 1)
		{
			$plugins->run_hooks("newpoints_subscriptions_confirmed");

			// get points from user
			newpoints_addpoints($mybb->user['uid'], -(floatval($sub['price'])));

			//if an additional group, join it
			if ($sub['additional'] == 1)
			{
				join_usergroup($mybb->user['uid'], intval($sub['group']));
			}
			else
			{
				// change primary group to end group
				$db->update_query('users', array('usergroup' => intval($sub['group'])), 'uid='.intval($mybb->user['uid']));
			}

			$log_message = $lang->sprintf($lang->newpoints_subscriptions_subscribed_log,
			(int)$sub['sid'], // subscription sid
			(int)$sub['group'], // our new group (primary or additional, doesn't matter)
			(int)$mybb->user['usergroup'], // current primary group - useless if this is an additional group plan type
			(float)$sub['price'], // price in points
			(int)$sub['additional'], // additional group?
			(int)$sub['time'], // period of subscription
			0, // expired
			$db->escape_string($sub['title']),
			0 // by default, it doens't have auto renewal enabled
			);

			// log purchase
			newpoints_log('subscriptions', $log_message);

			redirect($mybb->settings['bburl']."/newpoints.php?action=subscriptions", $lang->newpoints_subscriptions_subscribed, $lang->newpoints_subscriptions_subscribed_title);
		}
		// else show the confirmation page
		else
		{
			$plugins->run_hooks("newpoints_subscriptions_confirm");

			$sub['sid'] = (int)$sub['sid'];
			//$sub['title'] = htmlspecialchars_uni($sub['title']);
			$sub['title'] = $sub['title'];

			eval("\$page = \"".$templates->get('newpoints_subscriptions_subscribe')."\";");
		}
	}
	elseif ($mybb->input['action'] == 'enable_renewal')
	{
		verify_post_check($mybb->input['my_post_key']);

		$plugins->run_hooks("newpoints_subscriptions_enable_renewal");

		// Can we find the log entry?
		$lid = (int)$mybb->input['lid'];
		if ($lid <= 0 || (!($log = $db->fetch_array($db->simple_select('newpoints_log', '*', "lid = $lid AND uid=".(int)$mybb->user['uid'])))))
		{
			error($lang->newpoints_subscriptions_invalid_sub);
		}

		$data = explode('-', $log['data']);

		// Expired already?
		if($data[6] == 1)
		{
			error($lang->newpoints_subscriptions_invalid_sub);
		}

		$data[8] = 1; // 8 is for auto renewal

		$data = implode('-', $data);

		$db->update_query('newpoints_log', array('data' => $db->escape_string($data)), 'lid='.$lid);

		redirect($mybb->settings['bburl']."/newpoints.php?action=mysubscriptions", $lang->newpoints_subscriptions_auto_renewal_enabled);
	}
	elseif ($mybb->input['action'] == 'disable_renewal')
	{
		verify_post_check($mybb->input['my_post_key']);

		$plugins->run_hooks("newpoints_subscriptions_disable_renewal");

		// Can we find the log entry?
		$lid = (int)$mybb->input['lid'];
		if ($lid <= 0 || (!($log = $db->fetch_array($db->simple_select('newpoints_log', '*', "lid = $lid AND uid=".(int)$mybb->user['uid'])))))
		{
			error($lang->newpoints_subscriptions_invalid_sub);
		}

		$data = explode('-', $log['data']);

		// Expired already?
		if($data[6] == 1)
		{
			error($lang->newpoints_subscriptions_invalid_sub);
		}

		$data[8] = 1; // 8 is for auto renewal

		$data = implode('-', $data);

		$db->update_query('newpoints_log', array('data' => $db->escape_string($data)), 'lid='.$lid);

		redirect($mybb->settings['bburl']."/newpoints.php?action=mysubscriptions", $lang->newpoints_subscriptions_auto_renewal_disabled);
	}
	elseif ($mybb->input['action'] == 'mysubscriptions')
	{
		// get all groups first
		$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
		while($group = $db->fetch_array($query, 'title, gid'))
		{
			$groups[$group['gid']] = $group['title'];
		}

		// grab our subscriptions from the log
		$active = array();
		$sids = array();
		$query = $db->simple_select('newpoints_log', 'lid,data,date', 'action=\'subscriptions\' AND uid=\''.$mybb->user['uid'].'\'');
		while ($log = $db->fetch_array($query))
		{
			$data = explode('-', $log['data']);

			if ($data[6] == 1) break; // if it has already expired, return

			// overwrite period with expiration date
			$data[5] = $log['date'] + $data[5];

			// 9 is for lid
			$data[9] = $log['lid'];

			$active[$data[0]] = $data;
			$sids[] = $data[0];
		}

		$subplans = '';
		if(!empty($sids))
		{
			$where = 'sid IN('.implode(',', $sids).')';

			$query = $db->simple_select('newpoints_subscriptions', '*', $where, array('order_by' => 'price', 'order_dir' => 'ASC'));
			while ($sub = $db->fetch_array($query))
			{
				$bgcolor = alt_trow();

				$sub['sid'] = (int)$sub['sid'];
				//$sub['title'] = htmlspecialchars_uni($sub['title']);
				//$sub['description'] = nl2br(htmlspecialchars_uni($sub['description']));
				if ($sub['additional'])
				{
					$sub['description'] .= "<br /><strong>".$lang->newpoints_subscriptions_additional_notice."</strong>";
				}

				$sub['price'] = newpoints_format_points($sub['price']);
				if (!isset($groups[$sub['group']]))
				{
					// invalid new group
					$sub['usergroup'] = 'INVALID';
				}
				else
					$sub['usergroup'] = htmlspecialchars_uni($groups[$sub['group']]);

				$sub['expires'] = my_date($mybb->settings['dateformat'], $active[$sub['sid']][5], '', false).', '.my_date($mybb->settings['timeformat'], $active[$sub['sid']][5]);

				if($mybb->settings['newpoints_subscriptions_renew'] == 0)
				{
					$sub['renew'] = $lang->newpoints_subscriptions_notavailable;
				}
				else
				{
					if(!isset($data[8]) || (isset($data[8]) && $data[8] == 0))
					{
						$sub['renew'] =$lang->sprintf($lang->newpoints_subscriptions_disabled, $active[$sub['sid']][9], $mybb->post_code);
					}
					else
						$sub['renew'] = $lang->sprintf($lang->newpoints_subscriptions_enabled, $active[$sub['sid']][9], $mybb->post_code);
				}

				eval("\$subplans .= \"".$templates->get('newpoints_subscriptions_mysubscriptions_row')."\";");
			}
		}

		if (empty($subplans))
		{
			eval("\$subplans = \"".$templates->get('newpoints_subscriptions_empty')."\";");
		}

		eval("\$page = \"".$templates->get('newpoints_subscriptions_mysubscriptions')."\";");
	}
	else // show the subscription plans page
	{
		// get all groups first
		$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
		while($group = $db->fetch_array($query, 'title, gid'))
		{
			$groups[$group['gid']] = $group['title'];
		}

		$subplans_primary = '';

		$query = $db->simple_select('newpoints_subscriptions', '*', 'additional=0', array('order_by' => 'price', 'order_dir' => 'ASC'));
		while ($sub = $db->fetch_array($query))
		{
			$bgcolor = alt_trow();

			$sub['sid'] = (int)$sub['sid'];
			//$sub['title'] = htmlspecialchars_uni($sub['title']);
			//$sub['description'] = nl2br(htmlspecialchars_uni($sub['description']));
			if ($sub['additional'])
			{
				$sub['description'] .= "<br /><strong>".$lang->newpoints_subscriptions_additional_notice."</strong>";
			}

			$sub['price'] = newpoints_format_points($sub['price']);
			if (!isset($groups[$sub['group']]))
			{
				// invalid new group
				$sub['usergroup'] = 'INVALID';
			}
			else
				$sub['usergroup'] = htmlspecialchars_uni($groups[$sub['group']]);

			$sub['period'] = intval($sub['years'])."Y ".intval($sub['months'])."M ".intval($sub['days'])."D ".intval($sub['hours'])."H";

			eval("\$subplans_primary .= \"".$templates->get('newpoints_subscriptions_row')."\";");
		}

		if (empty($subplans_primary))
		{
			eval("\$subplans_primary = \"".$templates->get('newpoints_subscriptions_empty')."\";");
		}

		$subplans_sub = '';

		$query = $db->simple_select('newpoints_subscriptions', '*', 'additional=1', array('order_by' => 'price', 'order_dir' => 'ASC'));
		while ($sub = $db->fetch_array($query))
		{
			$bgcolor = alt_trow();

			$sub['sid'] = (int)$sub['sid'];
			//$sub['title'] = htmlspecialchars_uni($sub['title']);
			//$sub['description'] = nl2br(htmlspecialchars_uni($sub['description']));
			if ($sub['additional'])
			{
				$sub['description'] .= "<br /><strong>".$lang->newpoints_subscriptions_additional_notice."</strong>";
			}

			$sub['price'] = newpoints_format_points($sub['price']);
			if (!isset($groups[$sub['group']]))
			{
				// invalid new group
				$sub['usergroup'] = 'INVALID';
			}
			else
				$sub['usergroup'] = htmlspecialchars_uni($groups[$sub['group']]);

			$sub['period'] = intval($sub['years'])."Y ".intval($sub['months'])."M ".intval($sub['days'])."D ".intval($sub['hours'])."H";

			eval("\$subplans_sub .= \"".$templates->get('newpoints_subscriptions_row')."\";");
		}

		if (empty($subplans_sub))
		{
			eval("\$subplans_sub = \"".$templates->get('newpoints_subscriptions_empty')."\";");
		}

		eval("\$page = \"".$templates->get('newpoints_subscriptions')."\";");
	}

	$plugins->run_hooks("newpoints_subscriptions_end");

	// output page
	output_page($page);
}

// backup the subscriptions table too
function newpoints_subscriptions_backup(&$backup_fields)
{
	global $db, $table, $tables;

	$tables[] = TABLE_PREFIX.'newpoints_subscriptions';
}

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function newpoints_subscriptions_admin_newpoints_menu(&$sub_menu)
{
	global $lang;

	newpoints_lang_load('newpoints_subscriptions');
	$sub_menu[] = array('id' => 'subscriptions', 'title' => $lang->newpoints_subscriptions, 'link' => 'index.php?module=newpoints-subscriptions');
}

function newpoints_subscriptions_admin_newpoints_action_handler(&$actions)
{
	$actions['subscriptions'] = array('active' => 'subscriptions', 'file' => 'newpoints_subscriptions');
}

function newpoints_subscriptions_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	newpoints_lang_load('newpoints_subscriptions');
	$admin_permissions['subscriptions'] = $lang->newpoints_subscriptions_canmanage;

}

function newpoints_subscriptions_messageredirect($message, $error=0, $action='')
{
  	global $db, $mybb, $lang;

	if (!$message)
		return;

	if ($action)
		$parameters = '&amp;action='.$action;

	if ($error)
	{
		flash_message($message, 'error');
		admin_redirect("index.php?module=newpoints-subscriptions".$parameters);
	}
	else {
		flash_message($message, 'success');
		admin_redirect("index.php?module=newpoints-subscriptions".$parameters);
	}
}

function newpoints_subscriptions_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	newpoints_lang_load('newpoints_subscriptions');

	if($run_module == 'newpoints' && $action_file == 'newpoints_subscriptions')
	{
		if ($mybb->request_method == "post")
		{
			switch ($mybb->input['action'])
			{
				case 'do_addsubscription':
					if ($mybb->input['title'] == '' || $mybb->input['description'] == '' || intval($mybb->input['years']) < 0 || intval($mybb->input['months']) < 0 || intval($mybb->input['days']) < 0 || intval($mybb->input['hours']) < 0 || intval($mybb->input['group']) <= 0)
					{
						newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_missing_field, 1);
					}

					$title = $db->escape_string($mybb->input['title']);
					$description = $db->escape_string($mybb->input['description']);

					$years = intval($mybb->input['years']);
					$months = intval($mybb->input['months']);
					$days = intval($mybb->input['days']);
					$hours = intval($mybb->input['hours']);

					// calculate time
					$time = 0;
					if ($years > 0)
					{
						$time = (31536000 * $years) + $date;
					}
					if ($months > 0)
					{
						$time = (2628000 * $months) + $time;
					}
					if ($days > 0)
					{
						$time = (86400 * $days) + $time;
					}
					if ($hours > 0)
					{
						$time = (3600 * $hours) + $time;
					}

					if ($time <= 0)
					{
						newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_time_empty, 1);
					}

					$group = intval($mybb->input['group']);
					$query = $db->simple_select('usergroups', 'title', 'gid=\''.intval($group).'\'');
					if (!$db->fetch_field($query, 'title'))
					{
						// invalid new group
						newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_invalid_group, 1);
					}

					$additional = intval($mybb->input['additional']);
					if ($additional >= 1)
						$additional = 1;
					else
						$additional = 0;

					$enabled = intval($mybb->input['enabled']);
					if ($enabled >= 1)
						$enabled = 1;
					else
						$enabled = 0;

					$price = floatval($mybb->input['price']);

					$insert_query = array('enabled' => $enabled, 'title' => $title, 'description' => $description, 'price' => $price, 'group' => $group, 'years' => $years, 'months' => $months, 'days' => $days, 'hours' => $hours, 'additional' => $additional, 'time' => $time);
					$db->insert_query('newpoints_subscriptions', $insert_query);

					newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_sub_added);
				break;
				case 'do_editsubscription':
					$sid = intval($mybb->input['sid']);
					if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('newpoints_subscriptions', '*', "sid = $sid")))))
					{
						newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_invalid_sub, 1);
					}

					if ($mybb->input['title'] == '' || $mybb->input['description'] == '' || intval($mybb->input['years']) < 0 || intval($mybb->input['months']) < 0 || intval($mybb->input['days']) < 0 || intval($mybb->input['hours']) < 0 || intval($mybb->input['group']) <= 0)
					{
						newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_missing_field, 1);
					}

					$title = $db->escape_string($mybb->input['title']);
					$description = $db->escape_string($mybb->input['description']);

					$years = intval($mybb->input['years']);
					$months = intval($mybb->input['months']);
					$days = intval($mybb->input['days']);
					$hours = intval($mybb->input['hours']);

					// calculate time
					$time = 0;
					if ($years > 0)
					{
						$time = (31536000 * $years) + $date;
					}
					if ($months > 0)
					{
						$time = (2628000 * $months) + $time;
					}
					if ($days > 0)
					{
						$time = (86400 * $days) + $time;
					}
					if ($hours > 0)
					{
						$time = (3600 * $hours) + $time;
					}

					if ($time <= 0)
					{
						newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_time_empty, 1);
					}

					$group = intval($mybb->input['group']);
					$query = $db->simple_select('usergroups', 'title', 'gid=\''.intval($group).'\'');
					if (!$db->fetch_field($query, 'title'))
					{
						// invalid new group
						newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_invalid_group, 1);
					}

					$additional = intval($mybb->input['additional']);
					if ($additional >= 1)
						$additional = 1;
					else
						$additional = 0;

					$enabled = intval($mybb->input['enabled']);
					if ($enabled >= 1)
						$enabled = 1;
					else
						$enabled = 0;

					$price = floatval($mybb->input['price']);

					$update_query = array('enabled' => $enabled, 'title' => $title, 'description' => $description, 'price' => $price, 'group' => $group, 'years' => $years, 'months' => $months, 'days' => $days, 'hours' => $hours, 'additional' => $additional, 'time' => $time);
					$db->update_query('newpoints_subscriptions', $update_query, 'sid=\''.intval($sub['sid']).'\'');

					newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_sub_edited);
				break;
			}
		}

		if ($mybb->input['action'] == 'do_deletesubscription')
		{
			$page->add_breadcrumb_item($lang->newpoints_subscriptions, 'index.php?module=newpoints-subscriptions');
			$page->output_header($lang->newpoints_subscriptions);

			$sid = intval($mybb->input['sid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=newpoints-subscriptions");
			}

			if($mybb->request_method == "post")
			{
				if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('newpoints_subscriptions', 'sid', "sid = $sid")))))
				{
					newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_invalid_sub, 1);
				}

				// delete subscription plan
				$db->delete_query('newpoints_subscriptions', "sid = $sid");

				// unsubscribe each user, we have to query all entries but there is no other way with the current structure of this plugin
				$query = $db->simple_select('newpoints_log', '*', 'action=\'subscriptions\'');
				while ($slog = $db->fetch_array($query))
				{
					$data = explode('-', $slog['data']);

					if ($data[6] == 1) continue; // if it has already expired, return
					if ($data[0] != $sid) continue; // if this entry does not belong to this subscription, return

					//if an additional group, leave it
					if ($data[4] == 1)
						leave_usergroup($slog['uid'], $data[1]); /* $data[1] = new group */
					else // change primary group to end group
						$db->update_query('users', array('usergroup' => $data[2]), 'uid='.$slog['uid']); /* $data[2] = end group */

					$data[6] = 1; // expired field is set to 1 now

					// pm user
					newpoints_send_pm(array('subject' => $lang->newpoints_subscriptions_ended_title, 'message' => $lang->sprintf($lang->newpoints_subscriptions_ended, $data[7]), 'touid' => $slog['uid'], 'receivepms' => 1), 0);

					$data = implode('-', $data);

					// update the log entry and set it to expired
					$db->update_query('newpoints_log', array('data' => $data), 'lid='.$slog['lid']);
				}

				newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_sub_deleted);
			}
			else
			{
				$mybb->input['sid'] = intval($mybb->input['sid']);
				$form = new Form("index.php?module=newpoints-subscriptions&amp;action=do_deletesubscription&amp;sid={$mybb->input['sid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->newpoints_subscriptions_confirm_deletesub}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}

			$page->output_footer();
			exit;
		}

		$page->add_breadcrumb_item($lang->newpoints_subscriptions, 'index.php?module=newpoints-subscriptions');

		$page->output_header($lang->newpoints_subscriptions);

		$sub_tabs['newpoints_subscriptions'] = array(
			'title'			=> $lang->newpoints_subscriptions,
			'link'			=> 'index.php?module=newpoints-subscriptions',
			'description'	=> $lang->newpoints_subscriptions_desc
		);

		$sub_tabs['newpoints_subscriptions_add'] = array(
			'title'			=> $lang->newpoints_subscriptions_add,
			'link'			=> 'index.php?module=newpoints-subscriptions&amp;action=addsubscription',
			'description'	=> $lang->newpoints_subscriptions_add_desc
		);

		$sub_tabs['newpoints_subscriptions_edit'] = array(
			'title'			=> $lang->newpoints_subscriptions_edit,
			'link'			=> 'index.php?module=newpoints-subscriptions&amp;action=editsubscription',
			'description'	=> $lang->newpoints_subscriptions_edit_desc
		);

		if (!$mybb->input['action'])
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_subscriptions');

			$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$groups[$usergroup['gid']] = $usergroup['title'];
			}

			// table
			$table = new Table;
			$table->construct_header($lang->newpoints_subscriptions_title, array('width' => '20%'));
			$table->construct_header($lang->newpoints_subscriptions_period, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_subscriptions_group, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_subscriptions_price, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_subscriptions_action, array('width' => '20%', 'class' => 'align_center'));

			$query = $db->simple_select('newpoints_subscriptions', '*', '', array('order_by' => 'title', 'order_dir' => 'ASC'));
			while ($sub = $db->fetch_array($query))
			{
				if($sub['enabled'] == 1)
				{
					$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_on.png\" alt=\"({$lang->newpoints_subscriptions_alt_enabled})\" title=\"{$lang->newpoints_subscriptions_alt_enabled}\"  style=\"vertical-align: middle;\" /> ";
				}
				else
				{
					$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_off.png\" alt=\"({$lang->newpoints_subscriptions_alt_disabled})\" title=\"{$lang->newpoints_subscriptions_alt_disabled}\"  style=\"vertical-align: middle;\" /> ";
				}

				if ($sub['additional'])
					$table->construct_cell("<div>{$icon}".htmlspecialchars_uni($sub['title'])." ".$lang->newpoints_subscriptions_additional_title."</div>");
				else
					$table->construct_cell("<div>{$icon}".htmlspecialchars_uni($sub['title'])."</div>");

				// build time
				$table->construct_cell(intval($sub['years'])."Y ".intval($sub['months'])."M ".intval($sub['days'])."D ".intval($sub['hours'])."H", array('class' => 'align_center')); // expiration date
				$table->construct_cell(htmlspecialchars_uni($groups[$sub['group']]), array('class' => 'align_center'));
				$table->construct_cell(newpoints_format_points($sub['price']), array('class' => 'align_center'));
				// actions column
				$table->construct_cell("<a href=\"index.php?module=newpoints-subscriptions&amp;action=editsubscription&amp;sid=".intval($sub['sid'])."\">".$lang->newpoints_subscriptions_edit."</a> - <a href=\"index.php?module=newpoints-subscriptions&amp;action=do_deletesubscription&amp;sid=".intval($sub['sid'])."\">".$lang->newpoints_subscriptions_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->newpoints_subscriptions_no_subs, array('colspan' => 5));
				$table->construct_row();
			}

			$table->output($lang->newpoints_subscriptions_plans);
		}
		elseif ($mybb->input['action'] == 'addsubscription')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_subscriptions_add');

			$groups[0] = $lang->newpoints_subscriptions_select_group;

			// get groups
			$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
			while($group = $db->fetch_array($query, 'title, gid'))
			{
				$groups[$group['gid']] = $group['title'];
			}

			$form = new Form("index.php?module=newpoints-subscriptions&amp;action=do_addsubscription", "post", "newpoints_subscriptions");

			$form_container = new FormContainer($lang->newpoints_subscriptions_addsubscription);
			$form_container->output_row($lang->newpoints_subscriptions_title."<em>*</em>", $lang->newpoints_subscriptions_title_desc, $form->generate_text_box('title', '', array('id' => 'title')), 'title');
			$form_container->output_row($lang->newpoints_subscriptions_description."<em>*</em>", $lang->newpoints_subscriptions_description_desc, $form->generate_text_area('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->newpoints_subscriptions_group."<em>*</em>", $lang->newpoints_subscriptions_group_desc, $form->generate_select_box('group', $groups, '', array('id' => 'group')), 'group');
			$form_container->output_row($lang->newpoints_subscriptions_price, $lang->newpoints_subscriptions_price_desc, $form->generate_text_box('price', '0.00', array('id' => 'price')), 'price');
			$form_container->output_row($lang->newpoints_subscriptions_years, $lang->newpoints_subscriptions_years_desc, $form->generate_text_box('years', '0', array('id' => 'years')), 'years');
			$form_container->output_row($lang->newpoints_subscriptions_months, $lang->newpoints_subscriptions_months_desc, $form->generate_text_box('months', '0', array('id' => 'months')), 'months');
			$form_container->output_row($lang->newpoints_subscriptions_days, $lang->newpoints_subscriptions_days_desc, $form->generate_text_box('days', '0', array('id' => 'days')), 'days');
			$form_container->output_row($lang->newpoints_subscriptions_hours, $lang->newpoints_subscriptions_hours_desc, $form->generate_text_box('hours', '0', array('id' => 'hours')), 'hours');
			$form_container->output_row($lang->newpoints_subscriptions_enabled, $lang->newpoints_subscriptions_enabled_desc, $form->generate_check_box('enabled', '1', '', array('id' => 'enabled','checked' => false)), 'enabled');
			$form_container->output_row($lang->newpoints_subscriptions_additional, $lang->newpoints_subscriptions_additional_desc, $form->generate_check_box('additional', '1', '', array('id' => 'additional','checked' => false)), 'additional');
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->newpoints_subscriptions_submit);
			$buttons[] = $form->generate_reset_button($lang->newpoints_subscriptions_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'editsubscription')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_subscriptions_edit');

			$sid = intval($mybb->input['sid']);
			if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('newpoints_subscriptions', '*', "sid = $sid")))))
			{
				newpoints_subscriptions_messageredirect($lang->newpoints_subscriptions_invalid_sub, 1);
			}

			$groups[0] = $lang->newpoints_subscriptions_select_group;

			// get groups
			$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
			while($group = $db->fetch_array($query, 'title, gid'))
			{
				$groups[$group['gid']] = $group['title'];
			}

			$form = new Form("index.php?module=newpoints-subscriptions&amp;action=do_editsubscription", "post", "newpoints_subscriptions");

			echo $form->generate_hidden_field('sid', $sub['sid']);

			$form_container = new FormContainer($lang->newpoints_subscriptions_editsubscription);
			$form_container->output_row($lang->newpoints_subscriptions_title."<em>*</em>", $lang->newpoints_subscriptions_title_desc, $form->generate_text_box('title', $sub['title'], array('id' => 'title')), 'title');
			$form_container->output_row($lang->newpoints_subscriptions_description."<em>*</em>", $lang->newpoints_subscriptions_description_desc, $form->generate_text_area('description', $sub['description'], array('id' => 'description')), 'description');
			$form_container->output_row($lang->newpoints_subscriptions_group."<em>*</em>", $lang->newpoints_subscriptions_group_desc, $form->generate_select_box('group', $groups, intval($sub['group']), array('id' => 'group', 'multiple' => true, 'size' => 5)), 'group');
			$form_container->output_row($lang->newpoints_subscriptions_price, $lang->newpoints_subscriptions_price_desc, $form->generate_text_box('price', floatval($sub['price']), array('id' => 'price')), 'price');
			$form_container->output_row($lang->newpoints_subscriptions_years, $lang->newpoints_subscriptions_years_desc, $form->generate_text_box('years', intval($sub['years']), array('id' => 'years')), 'years');
			$form_container->output_row($lang->newpoints_subscriptions_months, $lang->newpoints_subscriptions_months_desc, $form->generate_text_box('months', intval($sub['months']), array('id' => 'months')), 'months');
			$form_container->output_row($lang->newpoints_subscriptions_days, $lang->newpoints_subscriptions_days_desc, $form->generate_text_box('days', intval($sub['days']), array('id' => 'days')), 'days');
			$form_container->output_row($lang->newpoints_subscriptions_hours, $lang->newpoints_subscriptions_hours_desc, $form->generate_text_box('hours', intval($sub['hours']), array('id' => 'hours')), 'hours');
			$form_container->output_row($lang->newpoints_subscriptions_enabled, $lang->newpoints_subscriptions_enabled_desc, $form->generate_check_box('enabled', '1', '', array('id' => 'enabled','checked' => intval($sub['enabled']))), 'enabled');
			$form_container->output_row($lang->newpoints_subscriptions_additional, $lang->newpoints_subscriptions_additional_desc, $form->generate_check_box('additional', '1', '', array('id' => 'additional','checked' => intval($sub['additional']))), 'additional');
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->newpoints_subscriptions_submit);
			$buttons[] = $form->generate_reset_button($lang->newpoints_subscriptions_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}

		$page->output_footer();
		exit;
	}
}

?>
