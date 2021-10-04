<?php

/***************************************************************************
 *
 *  Hide Content plugin (/inc/plugins/hidecontent.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  License: licence.txt
 *
 *  Adds MyCode which hides content inside posts.
 *
 ***************************************************************************/

if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly.");

$plugins->add_hook('archive_thread_post', 'hidecontent_archive');
$plugins->add_hook('newreply_end', 'hidecontent_reply');
$plugins->add_hook('postbit', 'hidecontent_postbit');
$plugins->add_hook('postbit_prev', 'hidecontent_postbit');
$plugins->add_hook('xmlhttp_edit_post_end', 'hidecontent_xmlhttp_edit_get');
$plugins->add_hook('xmlhttp_update_post', 'hidecontent_xmlhttp_edit_update');
$plugins->add_hook('xmlhttp_get_multiquoted_end', 'hidecontent_xmlhttp_multiquote');
$plugins->add_hook('editpost_end', 'hidecontent_reply');
$plugins->add_hook('printthread_post', 'hidecontent_print');
$plugins->add_hook('parse_message_end', 'hidecontent_portal');

$plugins->add_hook('parse_message_start', 'hidecontent_highlight');
$plugins->add_hook('misc_start', 'hidecontent_misc');
$plugins->add_hook('search_results_post', 'hidecontent_search');
$plugins->add_hook('datahandler_post_insert_subscribed', 'hidecontent_subscription');

function hidecontent_info()
{
	return array(
		"name"			=> "Hide Content",
		"description"	=> "Adds MyCode which hides content inside posts.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.4.3",
		"guid" 			=> "",
		"compatibility" => "18*"
	);
}

function hidecontent_install()
{
	global $db, $lang;

	// create settings group
	$insertarray = array(
		'name' => 'hidecontent',
		'title' => 'Hide Content',
		'description' => "Settings for Hide Content plugin",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting0 = array(
		"name"			=> "hidecontent_usergroups",
		"title"			=> "Usergroups",
		"description"	=> "Enter the user group ID\'s (separated by comma) of the usergroups allowed to use the Hide Content tag. (\'all\' for all usergroups)",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting0);

	$setting1 = array(
		"name"			=> "hidecontent_usergroups_bypass",
		"title"			=> "Bypass Usergroups",
		"description"	=> "Enter the user group ID\'s (separated by comma) of the usergroups that do not require to pay.",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting1);

	$setting2 = array(
		"name"			=> "hidecontent_points",
		"title"			=> "Points",
		"description"	=> "Enter the price to unlock a post\'s hidden content. Leave this set to 0 to disable this unlocking method.",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting2);

	$setting3 = array(
		"name"			=> "hidecontent_commission",
		"title"			=> "Commission paid to Author",
		"description"	=> "Enter the % of the total points that are paid to the post author. E.g. 50 for 50%. Only used if the setting above is greater than 0.",
		"optionscode"	=> "text",
		"value"			=> "50",
		"disporder"		=> 3,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting3);

	$setting4 = array(
		"name"			=> "hidecontent_reply",
		"title"			=> "Reply to Unlock?",
		"description"	=> "Select whether or not users can reply to the thread in order to unlock the hidden content within the thread.",
		"optionscode"	=> "yesno",
		"value"			=> "0",
		"disporder"		=> 4,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting4);

	rebuild_settings();

	if(!$db->table_exists("hidecontent"))
	{
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."hidecontent` (
		  `id` bigint(30) UNSIGNED NOT NULL auto_increment,
		  `uid` int(10) NOT NULL,
		  `pid` bigint(10) NOT NULL,
		  PRIMARY KEY  (`id`), INDEX(`uid`), INDEX(`pid`)
			) ENGINE=MyISAM");
	}
}

function hidecontent_is_installed()
{
	global $db;

	$q = $db->simple_select('settings', 'sid', 'name=\'hidecontent_usergroups\'');
	if ($db->fetch_field($q, 'sid'))
		return true;

	return false;
}

function hidecontent_uninstall()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'hidecontent'");

	// remove settings
	$db->delete_query('settings', 'name LIKE \'%hidecontent%\'');

	rebuild_settings();

	if($db->table_exists("hidecontent"))
		$db->write_query("DROP TABLE `".TABLE_PREFIX."hidecontent`;");
}

function hidecontent_activate()
{
}

function hidecontent_deactivate()
{
}

/**
 * Checks if a user has permissions or not.
 *
 * @param array|string Allowed usergroups (if set to 'all', every user has access; if set to '' no one has)
 *
*/
function hidecontent_check_permissions($groups_comma, $user)
{
	global $mybb;

	if ($groups_comma == 'all')
		return true;

	if ($groups_comma == '')
		return false;

	$groups = explode(",", $groups_comma);

	$ourgroups = explode(",", $user['additionalgroups']);
	$ourgroups[] = $user['usergroup'];

	if(count(array_intersect($ourgroups, $groups)) == 0)
		return false;
	else
		return true;
}

function hidecontent_parse_tags(&$post, $hide=false, $msg='', $strip=false)
{
	global $mybb, $lang, $db;

	if (empty($post))
		$bypass = true;
	else
		$bypass = false;

	if (!isset($lang->hidecontent_not_paid))
		$lang->load("hidecontent");

	if (!empty($msg))
		$message = $msg;
	else
		$message = &$post['message'];

	$pattern = "#\[hide\](.*?)\[\/hide\](\r\n?|\n?)#si";

	$user = array();
	$user['additionalgroups'] = $post['additionalgroups'];
	$user['usergroup'] = $post['usergroup'];

	if ($strip===true)
	{
		$matches = array();
		// do we have enough posts to view the content?
		preg_match_all($pattern, $message, $matches, PREG_SET_ORDER);

		foreach ($matches as $val) {
			$message = str_replace($val[0], $lang->hidecontent_stripped_content, $message);
		}

		return $message;
	}

	// Get posts that we have paid
	if((float)$mybb->settings['hidecontent_points'] > 0)
	{
		static $posts;
		if(!isset($posts))
		{
			$q = $db->simple_select('hidecontent', '*', 'uid='.(int)$mybb->user['uid']);
			while($p = $db->fetch_array($q))
				$posts[$p['pid']] = 1;
		}
	}
	else
		$posts = array();

	// if we have permission to use the Hide Content
	if (hidecontent_check_permissions($mybb->settings['hidecontent_usergroups'], $user) || $bypass === true)
	{
		$matches = array();
		// do we have enough posts to view the content?
		preg_match_all($pattern, $message, $matches, PREG_SET_ORDER);

		foreach ($matches as $val) {

			// are we just hiding the tags?
			if ($hide === true)
			{
				// If we're forcing to hide the content, we only see it if we can bypass it
				if (!hidecontent_check_permissions($mybb->settings['hidecontent_usergroups_bypass'], $mybb->user))
				{
					$message = str_replace($val[1], $lang->hidecontent_bad_content, $message);
				}
			}
			else
			{
				// If we've paid we can see it (or we can bypass it, or we're the author)
				if(isset($posts[$post['pid']]) || hidecontent_check_permissions($mybb->settings['hidecontent_usergroups_bypass'], $mybb->user) || $post['uid'] == $mybb->user['uid'])
				{
					$message = str_replace($val[0], $lang->sprintf($lang->hidecontent_content_unlocked, $val[1]), $message);
				}
				else
				{
					// Alright so we haven't paid, let's see if we have made a reply in this thread
					if($mybb->settings['hidecontent_reply'] == 1)
					{
						static $haveireplied;
						if(!isset($haveireplied))
						{
							// We did reply...didn't we?
							$q = $db->simple_select('posts', 'tid', 'tid='.(int)$post['tid'].' AND visible=1 AND uid='.(int)$mybb->user['uid'], array('limit' => 1));
							$reply = $db->fetch_array($q);
							if(!empty($reply))
							    $haveireplied = true;
							else
							    $haveireplied = false;
						}

						if($haveireplied)
						{
							// Yes
						    $message = str_replace($val[0], $lang->sprintf($lang->hidecontent_content_unlocked, $val[1]), $message);
						}
						else
						{
							// No
							if($mybb->settings['hidecontent_reply'] == 1 && (float)$mybb->settings['hidecontent_points'] > 0)
							{
								$message = str_replace($val[0], $lang->sprintf($lang->hidecontent_not_both, newpoints_format_points($mybb->settings['hidecontent_points']), $mybb->post_code, $post['pid']), $message);
							}
							elseif($mybb->settings['hidecontent_reply'] == 1 && (float)$mybb->settings['hidecontent_points'] == 0)
							{
								$message = str_replace($val[0], $lang->hidecontent_not_replied, $message);
							}
							elseif($mybb->settings['hidecontent_reply'] == 0 && (float)$mybb->settings['hidecontent_points'] > 0)
							{
								$message = str_replace($val[0], $lang->sprintf($lang->hidecontent_not_paid, newpoints_format_points($mybb->settings['hidecontent_points']), $mybb->post_code, $post['pid']), $message);
							}
							else
								$message = $lang->hidecontent_not_none;
						}
					}
					else
					{
						if($mybb->settings['hidecontent_reply'] == 1 && (float)$mybb->settings['hidecontent_points'] > 0)
						{
							$message = str_replace($val[0], $lang->sprintf($lang->hidecontent_not_both, newpoints_format_points($mybb->settings['hidecontent_points']), $mybb->post_code, $post['pid']), $message);
						}
						elseif($mybb->settings['hidecontent_reply'] == 1 && (float)$mybb->settings['hidecontent_points'] == 0)
						{
							$message = str_replace($val[0], $lang->hidecontent_not_replied, $message);
						}
						elseif($mybb->settings['hidecontent_reply'] == 0 && (float)$mybb->settings['hidecontent_points'] > 0)
						{
							$message = str_replace($val[0], $lang->sprintf($lang->hidecontent_not_paid, newpoints_format_points($mybb->settings['hidecontent_points']), $mybb->post_code, $post['pid']), $message);
						}
						else
							$message = $lang->hidecontent_not_none;
					}
				}
			}
		}
	}

	return $message;
}

function hidecontent_postbit(&$post)
{
	global $mybb, $db, $lang;

	// we're previewing a post
	if ($mybb->input['previewpost'] && (THIS_SCRIPT == "newthread.php" || THIS_SCRIPT == "newreply.php" || THIS_SCRIPT == "editpost.php"))
	{
		if (THIS_SCRIPT != "editpost.php")
		{
			if (empty($post['usergroup']))
				$post['usergroup'] = $mybb->user['usergroup'];
			if (empty($post['additionalgroups']))
				$post['additionalgroups'] = $mybb->user['additionalgroups'];
		}
	}

	hidecontent_parse_tags($post);
}

function hidecontent_xmlhttp_edit_get()
{
	global $mybb, $db, $post;

	// Hide if we are editing a post and it's not ours and we are not in the "bypass" group
	// EVEN if we have unlocked it -> we can only see it in the final message, not by quick editing it
	if($mybb->get_input('do') == "get_post")
	{
		if ($post['uid'] != $mybb->user['uid'])
		{
			// get usergroup and additionalgroups of author
			$query = $db->simple_select('users', 'usergroup,additionalgroups', 'uid=\''.$post['uid'].'\'');
			$user = $db->fetch_array($query);

			$post['usergroup'] = $user['usergroup'];
			$post['additionalgroups'] = $user['additionalgroups'];

			$post['message'] = htmlspecialchars_uni($post['message']);

			hidecontent_parse_tags($post, true);
		}
	}
}

function hidecontent_xmlhttp_edit_update()
{
	global $mybb, $db, $post;

	// Hide if we are editing a post and it's not ours and we haven't unlocked it
	if($mybb->get_input('do') == "update_post")
	{
		global $parser;

		if ($post['uid'] != $mybb->user['uid'])
		{
			// get usergroup and additionalgroups of author
			$query = $db->simple_select('users', 'usergroup,additionalgroups', 'uid=\''.$post['uid'].'\'');
			$user = $db->fetch_array($query);

			$post['usergroup'] = $user['usergroup'];
			$post['additionalgroups'] = $user['additionalgroups'];

			$post['message'] = hidecontent_parse_tags($post, false);
		}
	}
}

function hidecontent_xmlhttp_multiquote()
{
	global $mybb, $db, $message;

	$array = array();
	$message = hidecontent_parse_tags($array, false, $message, true);
}

function hidecontent_archive()
{
	global $lang, $mybb, $db, $post;

	hidecontent_parse_tags($post);
}

function hidecontent_print()
{
	global $lang, $mybb, $db, $postrow;

	hidecontent_parse_tags($postrow);
}

function hidecontent_reply()
{
	global $lang, $mybb, $message, $db, $threadreview;

	if (THIS_SCRIPT != "editpost.php")
	{
		$myvar = $myvar2 = array();
		if(!$mybb->input['previewpost'])
		{
			// if we're quoting a post, it is not possible to check for permissions so we'll just assume the user has permissions to have Hide Content in his/her post
			$message = hidecontent_parse_tags($myvar, true, $message);
		}

		// thread review may contain Hide Content!
		$threadreview = hidecontent_parse_tags($myvar2, true, $threadreview);
	}
	else
	{
		// Editing a post

		if($mybb->input['previewpost'])
		{
			// Previewing?
			global $postinfo;
			$post = $postinfo;
		}
		else
		{
			global $post;
		}

		if($post['uid'] != $mybb->user['uid'])
		{
			// get usergroup and additionalgroups of author
			$query = $db->simple_select('users', 'usergroup,additionalgroups', 'uid=\''.$post['uid'].'\'');
			$user = $db->fetch_array($query);

			$post['usergroup'] = $user['usergroup'];
			$post['additionalgroups'] = $user['additionalgroups'];
		}
		else {
			$post['usergroup'] = $mybb->user['usergroup'];
			$post['additionalgroups'] = $mybb->user['additionalgroups'];
		}
	}

	//$message = hidecontent_parse_tags($post, true, $message, true);
}

function hidecontent_highlight($message)
{
	global $parser;
	if(THIS_SCRIPT == 'showthread.php') // Disable highlight for this post if we have hidden content
	{
		$pattern = "#\[hide\](.*?)\[\/hide\](\r\n?|\n?)#si";
		if (preg_match($pattern, $message))
			$parser->options['highlight'] = false;
	}
}

function hidecontent_misc()
{
	global $mybb, $db, $lang;

	// Not what we want? Get out of here
	if(!isset($mybb->input['action']) || $mybb->input['action'] != 'hidecontent')
		return;

	if($mybb->settings['hidecontent_points'] <= 0)
		return;

	$lang->load("hidecontent");

	// First make sure we are logged in
	if($mybb->user['uid'] == 0)
	{
		error_no_permission();
	}

	// Are we allowed to do this?
	if(hidecontent_check_permissions($mybb->settings['hidecontent_usergroups_bypass'], $mybb->user))
	{
		error_no_permission();
	}

	$pid = (int)$mybb->input['pid'];

	// Valid post?
	$query = $db->simple_select('posts', '*', 'pid=\''.$pid.'\'');
	$post = $db->fetch_array($query);
	if($post === null || empty($post))
	{
		error($lang->hidecontent_invalid_post);
	}

	$points = (float)$mybb->settings['hidecontent_points']; // these points can be modified by the user but when we parse the tags we check the amount paid
	if($mybb->user['newpoints'] < $points)
	{
		error($lang->hidecontent_not_enough);
	}

	// Have we paid already?
	$q = $db->simple_select('hidecontent', '*', 'uid='.(int)$mybb->user['uid'].' AND pid='.$post['pid']);
	$entry = $db->fetch_array($q);
	if(!empty($entry))
	{
		error($lang->hidecontent_paid_already);
	}
	else
	{
		newpoints_addpoints($mybb->user['uid'], -$points);
		newpoints_addpoints($post['uid'], $points*(float)$mybb->settings['hidecontent_commission']/100);

		// Insert entry
		$db->insert_query('hidecontent', array(
			'uid' => (int)$mybb->user['uid'],
			'pid' => $pid
		));

		if(function_exists("thankyoulike_memprofile"))
		{
			$prefix = "g33k_thankyoulike_";
			// Check if the user liked this post already
			$q = $db->simple_select($prefix."thankyoulike", '*', 'pid='.intval($post['pid']).' AND uid='.intval($mybb->user['uid']));
			$liked = $db->fetch_array($q);
			if(empty($liked)) {
				// Add ty/l to db
				$tyl_data = array(
						"pid" => intval($post['pid']),
						"uid" => intval($mybb->user['uid']),
						"puid" => intval($post['uid']),
						"dateline" => TIME_NOW
				);
				$tlid = $db->insert_query($prefix."thankyoulike", $tyl_data);

				if($tlid)
				{
					// Update tyl count in posts and threads and users and total
					if($post['tyl_pnumtyls'] == 0)
					{
						// Post thanks were previously 0, so add this post to user's thanked posts
						$db->write_query("UPDATE ".TABLE_PREFIX."users SET tyl_unumptyls=tyl_unumptyls+1 WHERE uid='".intval($post['uid'])."'");
					}
					$db->write_query("UPDATE ".TABLE_PREFIX."posts SET tyl_pnumtyls=tyl_pnumtyls+1 WHERE pid='".intval($post['pid'])."'");
					$db->write_query("UPDATE ".TABLE_PREFIX."threads SET tyl_tnumtyls=tyl_tnumtyls+1 WHERE tid='".intval($post['tid'])."'");
					$db->write_query("UPDATE ".TABLE_PREFIX."users SET tyl_unumtyls=tyl_unumtyls+1 WHERE uid='".intval($mybb->user['uid'])."'");
					$db->write_query("UPDATE ".TABLE_PREFIX."users SET tyl_unumrcvtyls=tyl_unumrcvtyls+1 WHERE uid='".intval($post['uid'])."'");
					$db->write_query("UPDATE ".TABLE_PREFIX.$prefix."stats SET value=value+1 WHERE title='total'");
				}
			}
		}

		if(function_exists("quickdonation_xmlhttp"))
		{
			$donors = unserialize($post['quickdonation']);

			$donors[$mybb->user['uid']] = array($mybb->user['username'], TIME_NOW, (float)$mybb->settings['quickdonation_amount']);

			// Update donors' list
			$donors = serialize($donors);
			$db->update_query('posts', array('quickdonation' => $db->escape_string($donors)), 'pid='.(int)$post['pid']);
		}
	}

	redirect(htmlentities($_SERVER['HTTP_REFERER']), $lang->hidecontent_unlocked_content);
}

function hidecontent_portal($message)
{
	global $mybb, $db, $lang, $announcement;

	if(THIS_SCRIPT != 'portal.php')
		return;

	$a = $announcement;
	$a['message'] = $message;

	// Get usergroup and additionalgroups because portal.php doesn't get them
	$q = $db->simple_select('users', 'usergroup,additionalgroups', 'uid='.(int)$a['uid']);
	$data = $db->fetch_array($q);
	$a['usergroup'] = $data['usergroup'];
	$a['additionalgroups'] = $data['additionalgroups'];

	return hidecontent_parse_tags($a);
}

function hidecontent_search()
{
	global $prev, $post;

	$msg = hidecontent_parse_tags($post, false, $post['message'], true);

	if(my_strlen($msg) > 200)
	{
		$prev = my_substr($msg, 0, 200)."...";
	}
	else
	{
		$prev = $msg;
	}
}

function hidecontent_subscription() {
    // TODO: We need to modify the message in mailqueue table and cache!
}

// &#71;&#101;&#110;&#101;&#114;&#105;&#99;
