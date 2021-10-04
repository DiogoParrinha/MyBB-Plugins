<?php
/***************************************************************************
 *
 *  My Advertisements plugin (/inc/plugins/myadvertisements.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  This plugin adds advertizements zones to your forum.
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
//$plugins->add_hook('global_start', 'myadvertisements_expire');
$plugins->add_hook('pre_output_page', 'myadvertisements_ads');
$plugins->add_hook("postbit","myadvertisements_postbit", 30);
$plugins->add_hook("postbit_pm","myadvertisements_postbit_disable");
$plugins->add_hook("postbit_announcement","myadvertisements_postbit_disable");
$plugins->add_hook("postbit_prev","myadvertisements_postbit_disable");
$plugins->add_hook('xmlhttp', 'myadvertisements_click');
$plugins->add_hook('admin_load', 'myadvertisements_admin');
$plugins->add_hook('admin_tools_menu', 'myadvertisements_admin_tools_menu');
$plugins->add_hook('admin_tools_action_handler', 'myadvertisements_admin_tools_action_handler');
$plugins->add_hook('admin_tools_permissions', 'myadvertisements_admin_permissions');

// Disable adds on error pages (Google doesn't allow it) - only hides on header and footer
$plugins->add_hook('error', 'myadvertisements_error_block');

// When this is set to 1 MyAdvertisements queries an advertisement on every post or on X posts or on the first post (depends on the Postbit Zone settings)
// so they are all different (in case you have multiple ads assigned to the Postbit Zone).
// However, it is useless if you only have one, thus we provide this setting for you so you can decide whether to do it or not.
// By setting it to 1, it will increase the number of queries run and consequently the amount of resources consumed.
define("MYADS_DIF_POST", 0);

function myadvertisements_info()
{
	return array(
		"name"			=> "My Advertisements",
		"description"	=> "This plugin adds a powerful advertisements manager to your forum.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "2.3.4",
		"guid" 			=> "45fbb4d766836d05aee863b4258fdd7f",
		"compatibility"	=> "18*"
	);
}


function myadvertisements_install()
{
	global $db;
	// create settings group
	$insertarray = array(
		'name' => 'myadvertisements',
		'title' => 'My Advertisements',
		'description' => "Settings for My Advertisements",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);
	// add settings

	$setting0 = array(
		"name"			=> "myadvertisements_disabled",
		"title"			=> "Advertisements disabled?",
		"description"	=> "Set to yes if you want to disable all advertisements.",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$setting1 = array(
		"name"			=> "myadvertisements_sendpm",
		"title"			=> "Send PM on expiration?",
		"description"	=> "Do you want to receive a private message whenever an advertisement expires?",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$setting2 = array(
		"name"			=> "myadvertisements_sendpmuid",
		"title"			=> "User ID",
		"description"	=> "User ID of the user who receives the private messages.",
		"optionscode"	=> "text",
		"value"			=> 1,
		"disporder"		=> 3,
		"gid"			=> $gid
	);

	$setting3 = array(
		"name"			=> "myadvertisements_email_days",
		"title"			=> "E-mail Notices Days",
		"description"	=> "How many days before the ad expires, should the system send e-mail notices? Set to 0 disable the e-mail notices feature globally.",
		"optionscode"	=> "text",
		"value"			=> 5,
		"disporder"		=> 3,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting0);
	$db->insert_query("settings", $setting1);
	$db->insert_query("settings", $setting2);
	$db->insert_query("settings", $setting3);

	rebuild_settings();

	// create advertisements table
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myadvertisements_zones` (
	  `zid` int(10) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(50) NOT NULL default '',
	  `description` varchar(300) NOT NULL default '',
	  `ads` int(10) UNSIGNED NOT NULL default '0',
	  `postbit_type` smallint(1) UNSIGNED NOT NULL default '1',
	  `postbit_xposts` int(10) UNSIGNED NOT NULL default '0',
	  PRIMARY KEY (`zid`)
		) ENGINE=MyISAM");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."myadvertisements_advertisements` (
	  `aid` int(10) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(50) NOT NULL default '',
	  `description` varchar(300) NOT NULL default '',
	  `expire` bigint(30) UNSIGNED NOT NULL default '0',
	  `created` bigint(30) UNSIGNED NOT NULL default '0',
	  `exemptgroups` varchar(300) NOT NULL default '',
	  `advertisement` text NOT NULL,
	  `email_subject` varchar(50) NOT NULL default '',
	  `email_message` text NOT NULL,
	  `emails` text NOT NULL,
	  `zone` int(10) UNSIGNED NOT NULL default '0',
	  `unlimited` smallint(1) UNSIGNED NOT NULL default '0',
	  `views` bigint(30) UNSIGNED NOT NULL default '0',
	  `clicks` bigint(30) UNSIGNED NOT NULL default '0',
	  `disabled` tinyint(1) UNSIGNED NOT NULL default '0',
	  `email` tinyint(1) UNSIGNED NOT NULL default '0',
	  PRIMARY KEY (`aid`), INDEX(`expire`,`unlimited`,`email`)
		) ENGINE=MyISAM");

	// insert 3 defaults zones (header, footer and postbit)
	$db->insert_query('myadvertisements_zones', array('name' => "Header", 'description' => "Ads in this zone will be displayed in the header.")); // 1
	$db->insert_query('myadvertisements_zones', array('name' => "Footer", 'description' => "Ads in this zone will be displayed in the footer.")); // 2
	$db->insert_query('myadvertisements_zones', array('name' => "Postbit", 'description' => "Ads in this zone will be displayed in the postbit.")); // 3

	// create task
	$new_task = array(
		"title" => "MyAdvertisements",
		"description" => "Checks for expired advertisements every hour.",
		"file" => "myadvertisements",
		"minute" => '0',
		"hour" => '*',
		"day" => '*',
		"month" => '*',
		"weekday" => '*',
		"enabled" => '0',
		"logging" => '1'
	);

	$new_task['nextrun'] = 0; // once the task is enabled, it will generate a nextrun date
	$new_task['lastrun'] = 0; // last run is 0
	$tid = $db->insert_query("tasks", $new_task);

}

function myadvertisements_activate()
{
	global $db, $lang;

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('headerinclude', '#'.preg_quote('{$stylesheets}').'#', '<script language="javascript" type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/myadvertisements.js"></script>'."\n".'{$stylesheets}');

	// append our zones code to header, footer and postbit templates
	myadvertisements_append_templatesets('header', '{myadvertisements[zone_1]}');
	myadvertisements_appendtop_templatesets('footer', '{myadvertisements[zone_2]}');
	myadvertisements_append_templatesets('postbit', '{myadvertisements[zone_3]}');
	myadvertisements_append_templatesets('postbit_classic', '{myadvertisements[zone_3]}');
}

function myadvertisements_is_installed()
{
	global $db;

	if($db->table_exists('myadvertisements_zones'))
		return true;

	return false;
}

function myadvertisements_uninstall()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'myadvertisements'");

	// remove settings
	$db->delete_query('settings', 'name IN ( \'myadvertisements_disabled\',\'myadvertisements_sendpm\',\'myadvertisements_sendpmuid\',\'myadvertisements_email_days\')');

	rebuild_settings();

	if ($db->table_exists('myadvertisements_zones'))
		$db->drop_table('myadvertisements_zones');

	if ($db->table_exists('myadvertisements_advertisements'))
		$db->drop_table('myadvertisements_advertisements');

	$db->delete_query('tasks', 'file=\'myadvertisements\'');
}

function myadvertisements_deactivate()
{
	global $db, $mybb;

	// remove templates edits
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('headerinclude', '#'.preg_quote("\n".'<script language="javascript" type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/myadvertisements.js"></script>').'#', '', 0);
	find_replace_templatesets('header', '#'.preg_quote('{myadvertisements[zone_1]}').'#', '', 0);
	find_replace_templatesets('footer', '#'.preg_quote('{myadvertisements[zone_2]}').'#', '', 0);
	find_replace_templatesets('postbit', '#'.preg_quote('{myadvertisements[zone_3]}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{myadvertisements[zone_3]}').'#', '', 0);
}

/**
 * Appends a string to the top of a specific template
 *
 * @param string The name of the template
 * @param string The code to append
 * @param int Set to 1 to automatically create templates which do not exist for that set (based off master) - Defaults to 1
 * @return bolean true if matched template name, false if not.
 */

function myadvertisements_appendtop_templatesets($title, $codeappend, $autocreate=1)
{
	global $db;
	if($autocreate != 0)
	{
		$query = $db->simple_select("templates", "*", "title='$title' AND sid='-2'");
		$master = $db->fetch_array($query);
		$oldmaster = $master['template'];
		$master['template'] = $codeappend.$master['template'];
		if($oldmaster == $master['template'])
		{
			return false;
		}
		$master['template'] = $db->escape_string($master['template']);
	}
	$query = $db->query("
		SELECT s.sid, t.template, t.tid
		FROM ".TABLE_PREFIX."templatesets s
		LEFT JOIN ".TABLE_PREFIX."templates t ON (t.title='$title' AND t.sid=s.sid)
	");
	while($template = $db->fetch_array($query))
	{
		if($template['template']) // Custom template exists for this group
		{
			$newtemplate = $codeappend.$template['template'];
			$template['template'] = $newtemplate;
			$update[] = $template;
		}
		elseif($autocreate != 0) // No template exists, create it based off master
		{
			$newtemp = array(
				"title" => $title,
				"template" => $master['template'],
				"sid" => $template['sid']
			);
			$db->insert_query("templates", $newtemp);
		}
	}

	if(is_array($update))
	{
		foreach($update as $template)
		{
			$updatetemp = array("template" => $db->escape_string($template['template']), "dateline" => TIME_NOW);
			$db->update_query("templates", $updatetemp, "tid='".$template['tid']."'");
		}
	}
	return true;
}


/**
 * Appends a string to specific template
 *
 * @param string The name of the template
 * @param strign The code to append
 * @param int Set to 1 to automatically create templates which do not exist for that set (based off master) - Defaults to 1
 * @return bolean true if matched template name, false if not.
 */

function myadvertisements_append_templatesets($title, $codeappend, $autocreate=1)
{
	global $db;
	if($autocreate != 0)
	{
		$query = $db->simple_select("templates", "*", "title='$title' AND sid='-2'");
		$master = $db->fetch_array($query);
		$oldmaster = $master['template'];
		$master['template'] = $master['template'].$codeappend;
		if($oldmaster == $master['template'])
		{
			return false;
		}
		$master['template'] = $db->escape_string($master['template']);
	}
	$query = $db->query("
		SELECT s.sid, t.template, t.tid
		FROM ".TABLE_PREFIX."templatesets s
		LEFT JOIN ".TABLE_PREFIX."templates t ON (t.title='$title' AND t.sid=s.sid)
	");
	while($template = $db->fetch_array($query))
	{
		if($template['template']) // Custom template exists for this group
		{
			$newtemplate = $template['template'].$codeappend;
			$template['template'] = $newtemplate;
			$update[] = $template;
		}
		elseif($autocreate != 0) // No template exists, create it based off master
		{
			$newtemp = array(
				"title" => $title,
				"template" => $master['template'],
				"sid" => $template['sid']
			);
			$db->insert_query("templates", $newtemp);
		}
	}

	if(is_array($update))
	{
		foreach($update as $template)
		{
			$updatetemp = array("template" => $db->escape_string($template['template']), "dateline" => TIME_NOW);
			$db->update_query("templates", $updatetemp, "tid='".$template['tid']."'");
		}
	}
	return true;
}

function myadvertisements_get_zonename($zid)
{
	global $db;
	$query = $db->simple_select('myadvertisements_zones', 'name', 'zid='.intval($zid));
	return $db->fetch_field($query, 'name');
}

/**
 * Sends a PM to a user, with Admin Override
 *
 * @param array: The PM to be sent; should have 'subject', 'message', 'touid'
 * @param int: from user id (0 if you want to use the uid the person that sends it. -1 to use MyBB Engine
 * @return bool: true if PM sent
 */
function myadvertisements_send_pm($pm, $fromid = 0)
{
	global $lang, $mybb, $db;
	if($mybb->settings['enablepms'] == 0) return false;
	if (!is_array($pm))	return false;
	if (!$pm['subject'] ||!$pm['message'] || !$pm['touid'] || !$pm['receivepms']) return false;

	$lang->load('messages'); // required for email notification

	require_once MYBB_ROOT."inc/datahandlers/pm.php";

	$pmhandler = new PMDataHandler();

	$subject = $pm['subject'];
	$message = $pm['message'];
	$toid = $pm['touid'];

	if (is_array($toid))
		$recipients_to = $toid;
	else
		$recipients_to = array($toid);
	$recipients_bcc = array();

	if (intval($fromid) == 0)
		$fromid = intval($mybb->user['uid']);
	elseif (intval($fromid) < 0)
		$fromid = 0;

	$pm = array(
		"subject" => $subject,
		"message" => $message,
		"icon" => -1,
		"fromid" => 0,
		"toid" => $recipients_to,
		"bccid" => $recipients_bcc,
		"do" => '',
		"pmid" => ''
	);

	$pm['options'] = array(
		"signature" => 0,
		"disablesmilies" => 0,
		"savecopy" => 0,
		"readreceipt" => 0
	);
	$pm['saveasdraft'] = 0;
	$pmhandler->admin_override = 1;
	$pmhandler->set_data($pm);
	if($pmhandler->validate_pm())
	{
		$pmhandler->insert_pm();
	}
	else
	{
		return false;
	}

	return true;
}

// expire function!
// this should be in a task, will probably implement it in the next version. bleh
// TODO: make it task - DONE: MyAdvertisements 1.6 comes with task now
/*function myadvertisements_expire()
{
	global $mybb, $db, $lang;

	// TODO: store advertisements in cache and use cache here instead of running a query and fetching it like this

	// one query per page for sure
	$query = $db->simple_select('myadvertisements_advertisements', '*', 'expire<'.TIME_NOW.' AND expire != 0 AND unlimited = 0');
	while ($ad = $db->fetch_array($query))
	{
		if ($mybb->settings['myadvertisements_sendpm'] && $mybb->settings['myadvertisements_sendpmuid'])
		{
			$lang->load("myadvertisements");
			// more queries to send PM
			myadvertisements_send_pm(array('subject' => $lang->myadvertisements_pm_subject, 'message' => $lang->sprintf($lang->myadvertisements_pm_message, htmlspecialchars_uni($ad['name']), $ad['aid']), 'receivepms' => 1, 'touid' => $mybb->settings['myadvertisements_sendpmuid']));
		}

		// second query is run if advertisement has experied
		$db->update_query('myadvertisements_advertisements', array('expire' => 0), 'aid='.$ad['aid']);
	}
}*/

// we clicked an advertisement so increase counter
function myadvertisements_click()
{
	global $mybb, $db;

	if ($mybb->settings['myadvertisements_disabled'])
		return;

	if ($mybb->request_method != 'post' || $mybb->input['action'] != 'do_click')
		return;

	if(!verify_post_check($mybb->input['my_post_key'], true))
	{
		xmlhttp_error($lang->invalid_post_code);
	}

	// in case we switch page (we clicked a link, right?) the script is not stopped
	ignore_user_abort(true);

	// this query could be avoided if we logged all advertisement ID's in cache or something
	// TODO: store active advertisements ID's and expiration time in cache and check if the aid exists in the line below instead of running the query
	$query = $db->simple_select('myadvertisements_advertisements', '*', 'aid='.intval($mybb->input['aid']));
	$ad = $db->fetch_array($query);
	if (!$ad)
		return; // do not log clicks as the ad doesn't exist

	// increase clicks
	$db->update_query('myadvertisements_advertisements', array('clicks' => 'clicks+1'), 'aid='.$ad['aid'], 1, true);
}

function myadvertisements_postbit_disable(&$post)
{
	global $mybb, $templates;

	$cachetemps = array();

	if(!$templates->cache['postbit'])
		$cachetemps[] = 'postbit';

	if(!$templates->cache['postbit_classic'])
		$cachetemps[] = 'postbit_classic';

	if (!empty($cachetemps))
		$templates->cache(implode(',', $cachetemps));

	if (!isset($mypostcounter))
		$mypostcounter = 0;

	static $postbit_backup = '';
	static $postbit_classic_backup = '';

	// Fix purposed by Yumi/Zinga
	// http://mybbhacks.zingaburga.com/showthread.php?tid=572&pid=5133#pid5133
    static $restore_postbit = null;
    if (empty($postbit_backup) || $restore_postbit)
    {
        $viewmode = ($mybb->settings['postlayout'] == 'classic' ? '_classic' : '');
        $restore_postbit = (
            !isset($restore_postbit)
            && isset($templates->cache['postbit_first'.$viewmode])
            && $templates->cache['postbit_first'.$viewmode] == $templates->cache['postbit'.$viewmode]
        );
        $postbit_backup = $templates->cache['postbit'];
        $postbit_classic_backup = $templates->cache['postbit_classic'];
    }

	$templates->cache['postbit'] = str_replace('{myadvertisements[zone_3]}', '', $postbit_backup);
	$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_3]}', '', $postbit_classic_backup);
}

function myadvertisements_postbit(&$post)
{
	global $mybb, $templates, $db, $postcounter, $mypostcounter;

	if ($mybb->settings['myadvertisements_disabled'])
		return;

	/*if(!strpos($templates->cache['postbit_classic'], '{myadvertisements[zone_') && !strpos($templates->cache['postbit'], '{myadvertisements[zone_'))
		return;*/

	static $my_adszone;

	if (isset($my_adszone) && is_array($my_adszone))
		$zone = $my_adszone;
	else
	{
		// TODO: use cache instead of queries like this one
		$query = $db->simple_select('myadvertisements_zones', '*', 'zid=3');
		$my_adszone = $zone = $db->fetch_array($query);
	}

	$cachetemps = array();

	if(!isset($templates->cache['postbit']))
		$cachetemps[] = 'postbit';

	if(!isset($templates->cache['postbit_classic']))
		$cachetemps[] = 'postbit_classic';

	if (!empty($cachetemps))
		$templates->cache(implode(',', $cachetemps));

	if (!isset($mypostcounter))
		$mypostcounter = 0;

	static $postbit_backup = '';
	static $postbit_classic_backup = '';

	// Fix purposed by Yumi/Zinga
	// http://mybbhacks.zingaburga.com/showthread.php?tid=572&pid=5133#pid5133
	static $restore_postbit = null;
	if(empty($postbit_backup) || $restore_postbit || (isset($templates->cache['firstpostonly']) && $postcounter > 1))
	{
		$viewmode = ($mybb->settings['postlayout'] == 'classic' ? '_classic' : '');
		$restore_postbit = (
		    !isset($restore_postbit)
		    && isset($templates->cache['postbit_first'.$viewmode])
		    && $templates->cache['postbit_first'.$viewmode] == $templates->cache['postbit'.$viewmode]
		);
		$postbit_backup = $templates->cache['postbit'];
		$postbit_classic_backup = $templates->cache['postbit_classic'];
	}

	switch($zone['postbit_type'])
	{
		case 1:
			// each post
			if (MYADS_DIF_POST == 1)
			{
				$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
				$ad = $db->fetch_array($query);
			}
			else
			{
				static $myads_ad;
				if (isset($myads_ad) && is_array($myads_ad))
					$ad = $myads_ad;
				else
				{
					$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
					$myads_ad = $ad = $db->fetch_array($query);
				}
			}
			if ($ad)
			{
				if (myadvertisements_check_permissions($ad['exemptgroups']))
				{
					$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
					$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
					return;
				}

				$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_backup);
				$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_classic_backup);

				// increase views
				$db->update_query('myadvertisements_advertisements', array('views' => 'views+1'), 'aid='.$ad['aid'], 1, true);
			}
			else {
				$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
				$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
			}
		break;
		case 2:
			// first post of each page only
			if (($postcounter - 1) % $mybb->settings['postsperpage'] == "0")
			{
				if (MYADS_DIF_POST == 1)
				{
					$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
					$ad = $db->fetch_array($query);
				}
				else
				{
					static $myads_ad;
					if (isset($myads_ad) && is_array($myads_ad))
						$ad = $myads_ad;
					else
					{
						$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
						$myads_ad = $ad = $db->fetch_array($query);
					}
				}

				if ($ad)
				{
					if (myadvertisements_check_permissions($ad['exemptgroups']))
					{
						$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
						$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
						return;
					}

					$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_backup);
					$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_classic_backup);

					// increase views
					$db->update_query('myadvertisements_advertisements', array('views' => 'views+1'), 'aid='.$ad['aid'], 1, true);
				}
				else {
					$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
					$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
				}
			}
			else {
				// remove ad from the templates
				$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
				$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
			}
		break;
		case 3:
			// first post of each page and every X posts
			if (($postcounter - 1) % $mybb->settings['postsperpage'] == 0)
			{
				if (MYADS_DIF_POST == 1)
				{
					$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
					$ad = $db->fetch_array($query);
				}
				else
				{
					static $myads_ad;
					if (isset($myads_ad) && is_array($myads_ad))
						$ad = $myads_ad;
					else
					{
						$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
						$myads_ad = $ad = $db->fetch_array($query);
					}
				}

				if ($ad)
				{
					if (myadvertisements_check_permissions($ad['exemptgroups']))
					{
						$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
						$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
						return;
					}

					$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_backup);
					$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_classic_backup);

					// increase views
					$db->update_query('myadvertisements_advertisements', array('views' => 'views+1'), 'aid='.$ad['aid'], 1, true);
				}
				else {
					$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
					$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
				}
			}
			else {
				$mypostcounter++;

				if ($mypostcounter < $zone['postbit_xposts'])
				{
					// remove ad from the templates
					$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
					$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
				}
				elseif ($mypostcounter == $zone['postbit_xposts'])
				{
					if (MYADS_DIF_POST == 1)
					{
						$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
						$ad = $db->fetch_array($query);
					}
					else
					{
						static $myads_ad;
						if (isset($myads_ad) && is_array($myads_ad))
							$ad = $myads_ad;
						else
						{
							$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
							$myads_ad = $ad = $db->fetch_array($query);
						}
					}

					if ($ad)
					{
						if (myadvertisements_check_permissions($ad['exemptgroups']))
						{
							$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
							$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
							return;
						}

						$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_backup);
						$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_classic_backup);

						// increase views
						$db->update_query('myadvertisements_advertisements', array('views' => 'views+1'), 'aid='.$ad['aid'], 1, true);
					}
					else {
						$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
						$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
					}

					$mypostcounter = 0;
				}
			}
		break;
		case 4:
			$mypostcounter++;

			if ($mypostcounter < $zone['postbit_xposts'])
			{
				// remove ad from the templates
				$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
				$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
			}
			elseif ($mypostcounter == $zone['postbit_xposts'])
			{
				if (MYADS_DIF_POST == 1)
				{
					$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
					$ad = $db->fetch_array($query);
				}
				else
				{
					static $myads_ad;
					if (isset($myads_ad) && is_array($myads_ad))
						$ad = $myads_ad;
					else
					{
						$query = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
						$myads_ad = $ad = $db->fetch_array($query);
					}
				}

				if ($ad)
				{
					if (myadvertisements_check_permissions($ad['exemptgroups']))
					{
						$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
						$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
						return;
					}

					$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_backup);
					$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $postbit_classic_backup);

					// increase views
					$db->update_query('myadvertisements_advertisements', array('views' => 'views+1'), 'aid='.$ad['aid'], 1, true);
				}
				else {
					$templates->cache['postbit'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_backup);
					$templates->cache['postbit_classic'] = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $postbit_classic_backup);
				}

				$mypostcounter = 0;
			}
		break;
	}
}

//disable ads on error pages
function myadvertisements_error_block()
{
	global $header, $footer, $mybb, $db;

	if ($mybb->settings['myadvertisements_disabled'])
		return;

	$query = $db->simple_select('myadvertisements_zones', 'zid', 'zid!=3');
	while ($zone = $db->fetch_array($query))
	{
		$header = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $header);
		$footer = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $footer);
	}
}

// replace zone codes with advertisements
function myadvertisements_ads(&$page)
{
	global $mybb, $templates, $db;

	if ($mybb->settings['myadvertisements_disabled'])
		return;

	// TODO: use cache to store advertisements and zones (instead of tables only)
	// we could probably save up to 1~2 queries
	$query = $db->simple_select('myadvertisements_zones', '*', 'zid!=3');
	while ($zone = $db->fetch_array($query))
	{
		if (strstr($page, '{myadvertisements[zone_'.$zone['zid'].']}'))
		{
			// if we have advertisements assigned to this zone, place them there
			$query2 = $db->simple_select('myadvertisements_advertisements', '*', 'zone='.$zone['zid'].' AND (expire>'.TIME_NOW.' OR unlimited=1) AND disabled=0', array('order_by' => 'RAND()', 'limit' => 1));
			$ad = $db->fetch_array($query2);
			if ($ad)
			{
				if (myadvertisements_check_permissions($ad['exemptgroups']))
				{
					$page = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $page);
					continue;
				}

				$page = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', "<div onclick=\"MyAdvertisements.do_click(".$ad['aid'].");\">".$ad['advertisement']."</div>", $page);

				// increase views
				$db->update_query('myadvertisements_advertisements', array('views' => 'views+1'), 'aid='.$ad['aid'], 1, true);
			}
			else
			{
				// no ads so get rid of the code
				$page = str_replace('{myadvertisements[zone_'.$zone['zid'].']}', '', $page);
			}
		}
	}
}

function myadvertisements_check_permissions($groups_comma)
{
	global $mybb;

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

function myadvertisements_admin_tools_menu(&$sub_menu)
{
	global $lang;

	$lang->load('myadvertisements');
	$sub_menu[] = array('id' => 'myadvertisements', 'title' => $lang->myadvertisements_index, 'link' => 'index.php?module=tools-myadvertisements');
}

function myadvertisements_admin_tools_action_handler(&$actions)
{
	$actions['myadvertisements'] = array('active' => 'myadvertisements', 'file' => 'myadvertisements');
}

function myadvertisements_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	$lang->load("myadvertisements", false, true);
	$admin_permissions['myadvertisements'] = $lang->myadvertisements_canmanage;

}

function myadvertisements_messageredirect($message, $error=0, $action='')
{
  	global $db, $mybb, $lang;

	if (!$message)
		return;

	if ($action)
		$parameters = '&amp;action='.$action;

	if ($error)
	{
		flash_message($message, 'error');
		admin_redirect("index.php?module=tools-myadvertisements".$parameters);
	}
	else {
		flash_message($message, 'success');
		admin_redirect("index.php?module=tools-myadvertisements".$parameters);
	}
}

function myadvertisements_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	$lang->load("myadvertisements", false, true);

	if($run_module == 'tools' && $action_file == 'myadvertisements')
	{
		if ($mybb->request_method == "post")
		{
			switch ($mybb->input['action'])
			{
				case 'do_addzone':
					if ($mybb->input['name'] == '' || $mybb->input['description'] == '')
					{
						myadvertisements_messageredirect($lang->myadvertisements_missing_field, 1);
					}

					$name = $db->escape_string($mybb->input['name']);
					$description = $db->escape_string($mybb->input['description']);

					$insert_query = array('name' => $name, 'description' => $description);
					$db->insert_query('myadvertisements_zones', $insert_query);

					myadvertisements_messageredirect($lang->myadvertisements_zone_added);
				break;
				case 'do_editzone':
					$zid = intval($mybb->input['zid']);
					if ($zid <= 0 || (!($zone = $db->fetch_array($db->simple_select('myadvertisements_zones', '*', "zid = $zid")))))
					{
						myadvertisements_messageredirect($lang->myadvertisements_invalid_zone, 1);
					}

					if ($mybb->input['name'] == '' || $mybb->input['description'] == '')
					{
						myadvertisements_messageredirect($lang->myadvertisements_missing_field, 1);
					}

					if ($zid == 3 && $mybb->input['postbit'] != 1 && $mybb->input['postbit'] != 2 && $mybb->input['postbit'] != 3 && $mybb->input['postbit'] != 4 || (($mybb->input['postbit'] == 3 || $mybb->input['postbit'] == 4) && !$mybb->input['xposts']))
					{
						myadvertisements_messageredirect($lang->myadvertisements_missing_field, 1);
					}

					$name = $db->escape_string($mybb->input['name']);
					$description = $db->escape_string($mybb->input['description']);
					$postbit = intval($mybb->input['postbit']);
					$xposts = intval($mybb->input['xposts']);

					$update_query = array('name' => $name, 'description' => $description, 'postbit_type' => $postbit, 'postbit_xposts' => $xposts);
					$db->update_query('myadvertisements_zones', $update_query, 'zid=\''.$zid.'\'');

					myadvertisements_messageredirect($lang->myadvertisements_zone_edited);
				break;

				case 'do_addadvertisement':
					if ($mybb->input['name'] == '' || $mybb->input['description'] == '' || $mybb->input['expire'] == '' || $mybb->input['ad'] == '' || $mybb->input['zone'] == '')
					{
						myadvertisements_messageredirect($lang->myadvertisements_missing_field, 1, 'advertisements');
					}

					$name = $db->escape_string($mybb->input['name']);
					$description = $db->escape_string($mybb->input['description']);
					if ($mybb->input['expire'] == 'unlimited')
					{
						$expire = 0;
						$unlimited = 1;
					}
					else {
						$unlimited = 0;
						$expire = intval($mybb->input['expire'])*86400+time();
					}

					$created = TIME_NOW;

					$exemptgroups = $db->escape_string($mybb->input['exemptgroups']);
					$advertisement = $db->escape_string($mybb->input['ad']);
					$email_subject = $db->escape_string($mybb->input['email_subject']);
					$email_message = $db->escape_string($mybb->input['email_message']);
					$emails = $db->escape_string($mybb->input['emails']);
					$zid = intval($mybb->input['zone']);
					if ($zid <= 0 || (!($zid = $db->fetch_field($db->simple_select('myadvertisements_zones', 'zid', 'zid='.$zid), 'zid'))))
					{
						myadvertisements_messageredirect($lang->myadvertisements_invalid_zone, 1, 'advertisements');
					}

					$disabled = intval($mybb->input['disabled']);

					$insert_query = array('name' => $name, 'description' => $description, 'created' => $created, 'expire' => $expire, 'exemptgroups' => $exemptgroups, 'advertisement' => $advertisement, 'zone' => $zid, 'unlimited' => $unlimited, 'disabled' => $disabled, 'email_subject' => $email_subject, 'email_message' => $email_message, 'emails' => $emails);
					$db->insert_query('myadvertisements_advertisements', $insert_query);

					$db->update_query('myadvertisements_zones', array('ads' => 'ads+1'), 'zid='.$zid, 1, true);

					myadvertisements_messageredirect($lang->myadvertisements_advertisement_added, 0, 'advertisements');
				break;
				case 'do_editadvertisement':
					$aid = intval($mybb->input['aid']);
					if ($aid <= 0 || (!($aid = $db->fetch_field($db->simple_select('myadvertisements_advertisements', 'aid', 'aid='.$aid), 'aid'))))
					{
						myadvertisements_messageredirect($lang->myadvertisements_invalid_advertisement, 1, 'advertisements');
					}

					if ($mybb->input['name'] == '' || $mybb->input['description'] == '' || $mybb->input['ad'] == '' || $mybb->input['zone'] == '')
					{
						myadvertisements_messageredirect($lang->myadvertisements_missing_field, 1, 'advertisements');
					}

					$name = $db->escape_string($mybb->input['name']);
					$description = $db->escape_string($mybb->input['description']);
					$exemptgroups = $db->escape_string($mybb->input['exemptgroups']);
					$advertisement = $db->escape_string($mybb->input['ad']);
					$email_subject = $db->escape_string($mybb->input['email_subject']);
					$email_message = $db->escape_string($mybb->input['email_message']);
					$emails = $db->escape_string($mybb->input['emails']);
					$zid = intval($mybb->input['zone']);
					if ($zid <= 0 || (!($zid = $db->fetch_field($db->simple_select('myadvertisements_zones', 'zid', 'zid='.$zid), 'zid'))))
					{
						myadvertisements_messageredirect($lang->myadvertisements_invalid_zone, 1, 'advertisements');
					}

					$disabled = intval($mybb->input['disabled']);

					$update_query = array('name' => $name, 'description' => $description, 'exemptgroups' => $exemptgroups, 'advertisement' => $advertisement, 'zone' => $zid, 'disabled' => $disabled, 'email_subject' => $email_subject, 'email_message' => $email_message, 'emails' => $emails);
					$db->update_query('myadvertisements_advertisements', $update_query, 'aid=\''.$aid.'\'');

					myadvertisements_messageredirect($lang->myadvertisements_advertisement_edited, 0, 'advertisements');
				break;
			}
		}

		if ($mybb->input['action'] == 'do_deletezone')
		{
			$page->add_breadcrumb_item($lang->myadvertisements, 'index.php?module=tools-myadvertisements');
			$page->output_header($lang->myadvertisements);

			$zid = intval($mybb->input['zid']);
			if (!$zid || $zid == 1 || $zid == 2 || $zid == 3) // default zones can't be deleted
				myadvertisements_messageredirect($lang->myadvertisements_invalid_zone, 1);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=tools-myadvertisements");
			}

			if($mybb->request_method == "post")
			{
				if ($zid <= 0 || (!($zone = $db->fetch_array($db->simple_select('myadvertisements_zones', 'zid', "zid = $zid")))))
				{
					myadvertisements_messageredirect($lang->myadvertisements_invalid_zone, 1);
				}

				$db->delete_query('myadvertisements_zones', "zid = $zid");

				// delete adds aissnged to this zone
				$db->delete_query('myadvertisements_advertisements', "zone = $zid");

				myadvertisements_messageredirect($lang->myadvertisements_zone_deleted);
			}
			else
			{
				$mybb->input['zid'] = intval($mybb->input['zid']);
				$form = new Form("index.php?module=tools-myadvertisements&amp;action=do_deletezone&amp;zid={$mybb->input['zid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->myadvertisements_confirm_deletezone}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}
		elseif ($mybb->input['action'] == 'do_deleteadvertisement')
		{
			$page->add_breadcrumb_item($lang->myadvertisements, 'index.php?module=tools-myadvertisements');
			$page->output_header($lang->myadvertisements);

			$aid = intval($mybb->input['aid']);
			if (!$aid)
				myadvertisements_messageredirect($lang->myadvertisements_invalid_advertisement, 1, 'advertisements');

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=tools-myadvertisements&amp;action=advertisements");
			}

			if($mybb->request_method == "post")
			{
				if ($aid <= 0 || (!($ad = $db->fetch_array($db->simple_select('myadvertisements_advertisements', '*', "aid = $aid")))))
				{
					myadvertisements_messageredirect($lang->myadvertisements_invalid_advertisement, 1, 'advertisements');
				}

				$db->delete_query('myadvertisements_advertisements', "aid = $aid");

				$db->update_query('myadvertisements_zones', array('ads' => 'ads-1'), 'zid='.$ad['zone'], 1, true);

				myadvertisements_messageredirect($lang->myadvertisements_advertisement_deleted, 0, 'advertisements');
			}
			else
			{
				$mybb->input['aid'] = intval($mybb->input['aid']);
				$form = new Form("index.php?module=tools-myadvertisements&amp;action=do_deleteadvertisement&amp;aid={$mybb->input['aid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->myadvertisements_confirm_deleteadvertisement}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}

		if (!$mybb->input['action'] || $mybb->input['action'] == 'zones' || $mybb->input['action'] == 'advertisements' || $mybb->input['action'] == 'expired' || $mybb->input['action'] == 'addzone' || $mybb->input['action'] == 'editzone' || $mybb->input['action'] == 'addadvertisement' || $mybb->input['action'] == 'editadvertisement')
		{
			$page->add_breadcrumb_item($lang->myadvertisements, 'index.php?module=tools-myadvertisements');

			$page->output_header($lang->myadvertisements);

			$sub_tabs['myadvertisements_zones'] = array(
				'title'			=> $lang->myadvertisements_zones,
				'link'			=> 'index.php?module=tools-myadvertisements',
				'description'	=> $lang->myadvertisements_zones_desc
			);

			if (!$mybb->input['action'] || $mybb->input['action'] == 'zones' || $mybb->input['action'] == 'addzone' || $mybb->input['action'] == 'editzone')
			{
				$sub_tabs['myadvertisements_zones_add'] = array(
					'title'			=> $lang->myadvertisements_zones_add,
					'link'			=> 'index.php?module=tools-myadvertisements&amp;action=addzone',
					'description'	=> $lang->myadvertisements_zones_add_desc
				);
				$sub_tabs['myadvertisements_zones_edit'] = array(
					'title'			=> $lang->myadvertisements_zones_edit,
					'link'			=> 'index.php?module=tools-myadvertisements&amp;action=editzone',
					'description'	=> $lang->myadvertisements_zones_edit_desc
				);
				$sub_tabs['myadvertisements_zones_delete'] = array(
					'title'			=> $lang->myadvertisements_zones_delete,
					'link'			=> 'index.php?module=tools-myadvertisements&amp;action=do_deletezone',
					'description'	=> $lang->myadvertisements_zones_delete_desc
				);
			}

			$sub_tabs['myadvertisements_advertisements'] = array(
				'title'			=> $lang->myadvertisements_advertisements,
				'link'			=> 'index.php?module=tools-myadvertisements&amp;action=advertisements',
				'description'	=> $lang->myadvertisements_advertisements_desc
			);

			if ($mybb->input['action'] == 'advertisements' || $mybb->input['action'] == 'addadvertisement' || $mybb->input['action'] == 'editadvertisement')
			{
				$sub_tabs['myadvertisements_advertisements_add'] = array(
					'title'			=> $lang->myadvertisements_advertisements_add,
					'link'			=> 'index.php?module=tools-myadvertisements&amp;action=addadvertisement',
					'description'	=> $lang->myadvertisements_advertisements_add_desc
				);
				$sub_tabs['myadvertisements_advertisements_edit'] = array(
					'title'			=> $lang->myadvertisements_advertisements_edit,
					'link'			=> 'index.php?module=tools-myadvertisements&amp;action=editadvertisement',
					'description'	=> $lang->myadvertisements_advertisements_edit_desc
				);
				$sub_tabs['myadvertisements_advertisements_delete'] = array(
					'title'			=> $lang->myadvertisements_advertisements_delete,
					'link'			=> 'index.php?module=tools-myadvertisements&amp;action=do_deleteadvertisement',
					'description'	=> $lang->myadvertisements_advertisements_delete_desc
				);
			}

			$sub_tabs['myadvertisements_expired'] = array(
				'title'			=> $lang->myadvertisements_advertisements_expired,
				'link'			=> 'index.php?module=tools-myadvertisements&amp;action=expired',
				'description'	=> $lang->myadvertisements_advertisements_expired_desc
			);
		}

		if (!$mybb->input['action'] || $mybb->input['action'] == 'zones')
		{
			$page->output_nav_tabs($sub_tabs, 'myadvertisements_zones');

			// table
			$table = new Table;
			$table->construct_header($lang->myadvertisements_name, array('width' => '30%'));
			$table->construct_header($lang->myadvertisements_description, array('width' => '35%'));
			$table->construct_header($lang->myadvertisements_ads, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_action, array('width' => '25%', 'class' => 'align_center'));

			$query = $db->simple_select('myadvertisements_zones', '*');

			while ($z = $db->fetch_array($query))
			{
				$zone = $z;

				$table->construct_cell(htmlspecialchars_uni($zone['name']));
				$table->construct_cell(htmlspecialchars_uni($zone['description']));
				$table->construct_cell(intval($zone['ads']), array('class' => 'align_center'));

				// actions column
				$table->construct_cell("<a href=\"index.php?module=tools-myadvertisements&amp;action=editzone&amp;zid=".intval($zone['zid'])."\">".$lang->myadvertisements_edit."</a> - <a href=\"index.php?module=tools-myadvertisements&amp;action=do_deletezone&amp;zid=".intval($zone['zid'])."\">".$lang->myadvertisements_delete."</a> - <a href=\"javascript: void(0);\" onclick=\"alert('".'{myadvertisements[zone_'.$zone['zid'].']}\')">'.$lang->myadvertisements_getcode."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if (!$zone)
			{
				$table->construct_cell($lang->myadvertisements_nozones, array('colspan' => 4));

				$table->construct_row();
			}

			$table->output($lang->myadvertisements_zones);
		}
		elseif ($mybb->input['action'] == 'addzone')
		{
			$page->output_nav_tabs($sub_tabs, 'myadvertisements_zones_add');

			$form = new Form("index.php?module=tools-myadvertisements&amp;action=do_addzone", "post", "myadvertisements");

			$form_container = new FormContainer($lang->myadvertisements_addzone);
			$form_container->output_row($lang->myadvertisements_addzone_name, $lang->myadvertisements_addzone_name_desc, $form->generate_text_box('name', '', array('id' => 'name')), 'name');
			$form_container->output_row($lang->myadvertisements_addzone_description, $lang->myadvertisements_addzone_description_desc, $form->generate_text_box('description', '', array('id' => 'description')), 'description');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->myadvertisements_submit);
			$buttons[] = $form->generate_reset_button($lang->myadvertisements_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'editzone')
		{
			$page->output_nav_tabs($sub_tabs, 'myadvertisements_zones_edit');

			$zid = intval($mybb->input['zid']);
			if ($zid <= 0 || (!($zone = $db->fetch_array($db->simple_select('myadvertisements_zones', '*', "zid = $zid")))))
			{
				myadvertisements_messageredirect($lang->myadvertisements_invalid_zone, 1);
			}

			$form = new Form("index.php?module=tools-myadvertisements&amp;action=do_editzone", "post", "myadvertisements");

			$form_container = new FormContainer($lang->myadvertisements_editzone);
			echo $form->generate_hidden_field('zid', $zid);
			$form_container->output_row($lang->myadvertisements_editzone_name, $lang->myadvertisements_editzone_name_desc, $form->generate_text_box('name', htmlspecialchars_uni($zone['name']), array('id' => 'name')), 'name');
			$form_container->output_row($lang->myadvertisements_editzone_description, $lang->myadvertisements_editzone_description_desc, $form->generate_text_box('description', htmlspecialchars_uni($zone['description']), array('id' => 'description')), 'description');

			if ($zid == 3) // postbit
			{
				switch ($zone['postbit_type'])
				{
					case 1:
						$postbit1 = 1;
						$postbit2 = $postbit3 = $postbit4 = 0;
					break;
					case 2:
						$postbit2 = 1;
						$postbit1 = $postbit3 = $postbit4 = 0;
					break;
					case 3:
						$postbit3 = 1;
						$postbit1 = $postbit2 = $postbit4 = 0;
					break;
					case 4:
						$postbit4 = 1;
						$postbit1 = $postbit2 = $postbit3 = 0;
					break;
				}
				$form_container->output_row($lang->myadvertisements_editzone_postbit, $lang->myadvertisements_editzone_postbit_desc, $form->generate_radio_button('postbit', 1, $lang->myadvertisements_editzone_eachpost, array('checked' => $postbit1))."<br />".$form->generate_radio_button('postbit', 2, $lang->myadvertisements_editzone_firstonly, array('checked' => $postbit2))."<br />".$form->generate_radio_button('postbit', 3, $lang->myadvertisements_editzone_firstandx, array('checked' => $postbit3))."<br />".$form->generate_radio_button('postbit', 4, $lang->myadvertisements_editzone_everyx, array('checked' => $postbit4)), 'posts');
				$form_container->output_row($lang->myadvertisements_editzone_xposts, $lang->myadvertisements_editzone_xposts_desc, $form->generate_text_box('xposts', htmlspecialchars_uni($zone['postbit_xposts']), array('id' => 'xposts')), 'xposts');
			}

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->myadvertisements_submit);
			$buttons[] = $form->generate_reset_button($lang->myadvertisements_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'advertisements')
		{
			$page->output_nav_tabs($sub_tabs, 'myadvertisements_advertisements');

			// table
			$table = new Table;
			$table->construct_header($lang->myadvertisements_name, array('width' => '15%'));
			$table->construct_header($lang->myadvertisements_description, array('width' => '25%'));
			$table->construct_header($lang->myadvertisements_created, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_expire, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_zone, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_views, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_clicks, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_action, array('width' => '20%', 'class' => 'align_center'));

			$query = $db->simple_select('myadvertisements_advertisements', '*', 'unlimited=1 OR expire!=0');
			while ($ad = $db->fetch_array($query))
			{
				$advertisement = $ad;

				$table->construct_cell(htmlspecialchars_uni($advertisement['name']).($advertisement['disabled'] ? $lang->myadvertisements_notice_disabled : ''));
				$table->construct_cell(htmlspecialchars_uni($advertisement['description']));

				if(!$advertisement['created'])
					$table->construct_cell($lang->na);
				else
					$table->construct_cell(my_date($mybb->settings['dateformat'], $advertisement['created']).", ".my_date($mybb->settings['timeformat'], $advertisement['created']), array('class' => 'align_center'));

				if ($ad['unlimited'])
					$table->construct_cell($lang->myadvertisements_unlimited, array('class' => 'align_center'));
				else
				{
					$table->construct_cell(my_date($mybb->settings['dateformat'], $advertisement['expire']).", ".my_date($mybb->settings['timeformat'], $advertisement['expire']), array('class' => 'align_center'));
				}
				$table->construct_cell(myadvertisements_get_zonename($advertisement['zone']), array('class' => 'align_center'));
				$table->construct_cell(number_format($advertisement['views']), array('class' => 'align_center'));
				$table->construct_cell(number_format($advertisement['clicks']), array('class' => 'align_center'));

				// actions column
				$table->construct_cell("<a href=\"index.php?module=tools-myadvertisements&amp;action=editadvertisement&amp;aid=".intval($advertisement['aid'])."\">".$lang->myadvertisements_edit."</a> - <a href=\"index.php?module=tools-myadvertisements&amp;action=do_deleteadvertisement&amp;aid=".intval($advertisement['aid'])."\">".$lang->myadvertisements_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if (!$advertisement)
			{
				$table->construct_cell($lang->myadvertisements_noadvertisements, array('colspan' => 7));

				$table->construct_row();
			}

			$table->output($lang->myadvertisements_advertisements);
		}
		elseif ($mybb->input['action'] == 'expired')
		{
			$page->output_nav_tabs($sub_tabs, 'myadvertisements_expired');

			// table
			$table = new Table;
			$table->construct_header($lang->myadvertisements_name, array('width' => '15%'));
			$table->construct_header($lang->myadvertisements_description, array('width' => '25%'));
			$table->construct_header($lang->myadvertisements_created, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_expire, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_zone, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_views, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_clicks, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->myadvertisements_action, array('width' => '20%', 'class' => 'align_center'));

			$query = $db->simple_select('myadvertisements_advertisements', '*', 'expire=0 AND unlimited=0');

			while ($ad = $db->fetch_array($query))
			{
				$advertisement = $ad;

				$table->construct_cell(htmlspecialchars_uni($advertisement['name']).($advertisement['disabled'] ? $lang->myadvertisements_notice_disabled : ''));
				$table->construct_cell(htmlspecialchars_uni($advertisement['description']));

				if(!$advertisement['created'])
					$table->construct_cell($lang->na);
				else
					$table->construct_cell(my_date($mybb->settings['dateformat'], $advertisement['created']).", ".my_date($mybb->settings['timeformat'], $advertisement['created']), array('class' => 'align_center'));

				$table->construct_cell($lang->myadvertisements_notice_expired, array('class' => 'align_center'));

				$table->construct_cell(myadvertisements_get_zonename($advertisement['zone']), array('class' => 'align_center'));
				$table->construct_cell(number_format($advertisement['views']), array('class' => 'align_center'));
				$table->construct_cell(number_format($advertisement['clicks']), array('class' => 'align_center'));

				// actions column
				$table->construct_cell("<a href=\"index.php?module=tools-myadvertisements&amp;action=editadvertisement&amp;aid=".intval($advertisement['aid'])."\">".$lang->myadvertisements_edit."</a> - <a href=\"index.php?module=tools-myadvertisements&amp;action=do_deleteadvertisement&amp;aid=".intval($advertisement['aid'])."\">".$lang->myadvertisements_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if (!$advertisement)
			{
				$table->construct_cell($lang->myadvertisements_noadvertisements, array('colspan' => 7));

				$table->construct_row();
			}

			$table->output($lang->myadvertisements_advertisements);
		}
		elseif ($mybb->input['action'] == 'addadvertisement')
		{
			$page->output_nav_tabs($sub_tabs, 'myadvertisements_advertisements_add');

			$zones[0] = $lang->myadvertisements_select_zone;

			$query = $db->simple_select('myadvertisements_zones', '*');
			while ($zone = $db->fetch_array($query))
				$zones[$zone['zid']] = $zone['name'];

			$form = new Form("index.php?module=tools-myadvertisements&amp;action=do_addadvertisement", "post", "myadvertisements");

			$form_container = new FormContainer($lang->myadvertisements_addadvertisement);
			$form_container->output_row($lang->myadvertisements_addadvertisement_name, $lang->myadvertisements_addadvertisement_name_desc, $form->generate_text_box('name', '', array('id' => 'name')), 'name');
			$form_container->output_row($lang->myadvertisements_addadvertisement_description, $lang->myadvertisements_addadvertisement_description_desc, $form->generate_text_box('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->myadvertisements_addadvertisement_exemptgroups, $lang->myadvertisements_addadvertisement_exemptgroups_desc, $form->generate_text_box('exemptgroups', '', array('id' => 'exemptgroups')), 'exemptgroups');
			$form_container->output_row($lang->myadvertisements_addadvertisement_expire, $lang->myadvertisements_addadvertisement_expire_desc, $form->generate_text_box('expire', '', array('id' => 'description')), 'expire');
			$form_container->output_row($lang->myadvertisements_addadvertisement_zone, $lang->myadvertisements_addadvertisement_zone_desc, $form->generate_select_box('zone', $zones, 0, array('id' => 'zone')), 'zone');
			$form_container->output_row($lang->myadvertisements_addadvertisement_advertisement, $lang->myadvertisements_addadvertisement_advertisement_desc, $form->generate_text_area('ad', '', array('id' => 'ad')), 'ad');
			$form_container->output_row($lang->myadvertisements_addadvertisement_disabled, $lang->myadvertisements_addadvertisement_disabled_desc, $form->generate_yes_no_radio('disabled', 0, true), 'disabled');
			$form_container->output_row($lang->myadvertisements_emails, $lang->myadvertisements_emails_desc, $form->generate_text_box('emails', '', array('id' => 'emails')), 'emails');
			$form_container->output_row($lang->myadvertisements_email_subject, '', $form->generate_text_box('email_subject', '', array('id' => 'email_subject')), 'email_subject');
			$form_container->output_row($lang->myadvertisements_email_message, $lang->myadvertisements_email_message_desc, $form->generate_text_area('email_message', '', array('id' => 'email_message')), 'email_message');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->myadvertisements_submit);
			$buttons[] = $form->generate_reset_button($lang->myadvertisements_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'editadvertisement')
		{
			$page->output_nav_tabs($sub_tabs, 'myadvertisements_advertisements_edit');

			$aid = intval($mybb->input['aid']);
			if ($aid <= 0 || (!($advertisement = $db->fetch_array($db->simple_select('myadvertisements_advertisements', '*', "aid = $aid")))))
			{
				myadvertisements_messageredirect($lang->myadvertisements_invalid_advertisement, 1, 'advertisements');
			}

			$zones[0] = $lang->myadvertisements_select_zone;

			$query = $db->simple_select('myadvertisements_zones', '*');
			while ($zone = $db->fetch_array($query))
				$zones[$zone['zid']] = $zone['name'];

			$form = new Form("index.php?module=tools-myadvertisements&amp;action=do_editadvertisement", "post", "myadvertisements");
			echo $form->generate_hidden_field('aid', $aid);

			$form_container = new FormContainer($lang->myadvertisements_editadvertisement);
			$form_container->output_row($lang->myadvertisements_editadvertisement_name, $lang->myadvertisements_editadvertisement_name_desc, $form->generate_text_box('name', htmlspecialchars_uni($advertisement['name']), array('id' => 'name')), 'name');
			$form_container->output_row($lang->myadvertisements_editadvertisement_description, $lang->myadvertisements_editadvertisement_description_desc, $form->generate_text_box('description', htmlspecialchars_uni($advertisement['description']), array('id' => 'description')), 'description');
			$form_container->output_row($lang->myadvertisements_editadvertisement_exemptgroups, $lang->myadvertisements_editadvertisement_exemptgroups_desc, $form->generate_text_box('exemptgroups', $advertisement['exemptgroups'], array('id' => 'exemptgroups')), 'exemptgroups');
			$form_container->output_row($lang->myadvertisements_editadvertisement_zone, $lang->myadvertisements_editadvertisement_zone_desc, $form->generate_select_box('zone', $zones, intval($advertisement['zone']), array('id' => 'zone')), 'zone');
			$form_container->output_row($lang->myadvertisements_editadvertisement_advertisement, $lang->myadvertisements_editadvertisement_advertisement_desc, $form->generate_text_area('ad', $advertisement['advertisement'], array('id' => 'ad')), 'ad');
			$form_container->output_row($lang->myadvertisements_editadvertisement_disabled, $lang->myadvertisements_editadvertisement_disabled_desc, $form->generate_yes_no_radio('disabled', $advertisement['disabled'], true), 'disabled');
			$form_container->output_row($lang->myadvertisements_emails, $lang->myadvertisements_emails_desc, $form->generate_text_box('emails', htmlspecialchars_uni($advertisement['emails']), array('id' => 'emails')), 'emails');
			$form_container->output_row($lang->myadvertisements_email_subject, '', $form->generate_text_box('email_subject', $advertisement['email_subject'], array('id' => 'email_subject')), 'email_subject');
			$form_container->output_row($lang->myadvertisements_email_message, $lang->myadvertisements_email_message_desc, $form->generate_text_area('email_message', $advertisement['email_message'], array('id' => 'email_message')), 'email_message');
			$form_container->end();

			$buttons = array();;
			$buttons[] = $form->generate_submit_button($lang->myadvertisements_submit);
			$buttons[] = $form->generate_reset_button($lang->myadvertisements_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}

		$page->output_footer();
		exit;
	}
}

?>
