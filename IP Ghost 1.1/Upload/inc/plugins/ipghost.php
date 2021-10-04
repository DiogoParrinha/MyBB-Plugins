<?php

/***************************************************************************
 *
 *  IP Ghost plugin (/inc/plugins/ipghost.php)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
 *  License: licence.txt
 *
 *  Allows admins to make certain users' IP disappear from the logs.
 *
 ***************************************************************************/

if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly.");

if (!defined("IN_ADMINCP"))
{
	// Hooks to stop tracking IP
	$plugins->add_hook('global_start', 'ipghost_ghost');
}
else
{
	$plugins->add_hook('admin_load', 'ipghost_admin');
	$plugins->add_hook('admin_user_menu', 'ipghost_admin_user_menu');
	$plugins->add_hook('admin_user_action_handler', 'ipghost_admin_user_action_handler');
	$plugins->add_hook('admin_user_permissions', 'ipghost_admin_permissions');
}

function ipghost_info()
{
	return array(
		"name"			=> "IP Ghost",
		"description"	=> "Allows admins to make certain users' IP disappear from the logs.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.1",
		"guid" 			=> "005db82938b531892623f2e28eb119d6",
		"compatibility" => "18*"
	);
}

function ipghost_activate()
{
}

function ipghost_deactivate()
{
}

function ipghost_install()
{
	global $mybb, $db;

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ipghost` (
		`id` int(10) UNSIGNED NOT NULL auto_increment,
		`uid` bigint(30) UNSIGNED NOT NULL,
		PRIMARY KEY (id), INDEX(`uid`)
	) ENGINE=MyISAM;");
}

function ipghost_is_installed()
{
	global $db;

	if($db->table_exists("ipghost"))
		return true;
	else
		return false;
}

function ipghost_uninstall()
{
	global $mybb, $db;

	if($db->table_exists("ipghost"))
		$db->write_query("DROP TABLE `".TABLE_PREFIX."ipghost`;");
}

function ipghost_ghost()
{
	global $db, $mybb;

	if((int)$mybb->user['uid'] <= 0)
		return;

	global $cache;
	$ghosts = $cache->read("ipghost");
	if(!empty($ghosts))
	{
		$uids = array();
		foreach($ghosts as $ghost)
		{
			if($ghost['uid'] == $mybb->user['uid'])
			{
				$_SERVER['HTTP_CLIENT_IP'] = $_SERVER['HTTP_X_REAL_IP'] = $_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER['REMOTE_ADDR'] = '';
			}

			global $session;
			if($session->ipaddress != '')
			{
				$uids[] = $ghost['uid'];
			}
		}

		if(!empty($uids))
		{
			$db->update_query('sessions', array('ip' => ''), 'uid IN ('.implode(',', $uids).')');
			$db->update_query('users', array('lastip' => ''), 'uid IN ('.implode(',', $uids).')');
		}
	}
}

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function ipghost_admin_user_menu(&$sub_menu)
{
	global $lang;

	$lang->load('ipghost');
	$sub_menu[] = array('id' => 'ipghost', 'title' => $lang->ipghost_index, 'link' => 'index.php?module=user-ipghost');
}

function ipghost_admin_user_action_handler(&$actions)
{
	$actions['ipghost'] = array('active' => 'ipghost', 'file' => 'ipghost');
}

function ipghost_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	$lang->load("ipghost", false, true);
	$admin_permissions['ipghost'] = $lang->ipghost_canmanage;

}

function ipghost_messageredirect($message, $error=0, $action='')
{
  	global $db, $mybb, $lang;

	if (!$message)
		return;

	if ($action)
		$parameters = '&amp;action='.$action;

	if ($error)
	{
		flash_message($message, 'error');
		admin_redirect("index.php?module=user-ipghost".$parameters);
	}
	else {
		flash_message($message, 'success');
		admin_redirect("index.php?module=user-ipghost".$parameters);
	}
}

function ipghost_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	$lang->load("ipghost");

	if($run_module == 'user' && $action_file == 'ipghost')
	{
		if ($mybb->request_method == "post")
		{
			if($mybb->input['action'] == 'erase')
			{
				// User exists?
				$q = $db->simple_select('users', 'uid', 'username=\''.$db->escape_string($mybb->input['username']).'\'');
				$user = $db->fetch_array($q);
				if(empty($user))
				{
					ipghost_messageredirect($lang->ipghost_invalid_username, 1, 'erase');
				}

				// Erase IPs
				$db->update_query('posts', array('ipaddress' => ''), 'uid=\''.(int)$user['uid'].'\'');
				$db->update_query('users', array('lastip' => '', 'regip' => ''), 'uid=\''.(int)$user['uid'].'\'');

				ipghost_messageredirect($lang->ipghost_user_erased, 0, 'erase');
			}
			elseif($mybb->input['action'] == 'ghost')
			{
				// User exists?
				$q = $db->simple_select('users', 'uid', 'username=\''.$db->escape_string($mybb->input['username']).'\'');
				$user = $db->fetch_array($q);
				if(empty($user))
				{
					ipghost_messageredirect($lang->ipghost_invalid_username, 1, 'ghosts');
				}

				// Already ghost?
				$q = $db->simple_select('ipghost', '*', 'uid=\''.(int)$user['uid'].'\'');
				$ghost = $db->fetch_array($q);
				if(!empty($ghost))
				{
					ipghost_messageredirect($lang->ipghost_already_ghost, 1, 'ghosts');
				}

				$db->insert_query('ipghost', array('uid' => (int)$user['uid']), 'uid=\''.(int)$user['uid'].'\'');

				$q = $db->simple_select('ipghost');
				$ghosts = array();
				while($ghost = $db->fetch_array($q))
					$ghosts[] = $ghost;

				global $cache;
				$cache->update('ipghost', $ghosts);

				ipghost_messageredirect($lang->ipghost_ghost_added, 0, 'ghosts');
			}
		}

		if ($mybb->input['action'] == 'delete')
		{
			$page->add_breadcrumb_item($lang->ipghost, 'index.php?module=user-ipghost');
			$page->output_header($lang->ipghost);

			$uid = intval($mybb->input['uid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=user-ipghost");
			}

			if($mybb->request_method == "post")
			{
				if ($uid <= 0 || (!($user = $db->fetch_array($db->simple_select('ipghost', '*', "uid = $uid")))))
				{
					ipghost_messageredirect($lang->ipghost_invalid_user, 1);
				}

				// Delete Ghost
				$db->delete_query('ipghost', "uid = $uid");

				$q = $db->simple_select('ipghost');
				$ghosts = array();
				while($ghost = $db->fetch_array($q))
					$ghosts[] = $ghost;

				global $cache;
				$cache->update('ipghost', $ghosts);

				ipghost_messageredirect($lang->ipghost_deleted, 0, 'ghosts');
			}
			else
			{
				$mybb->input['uid'] = intval($mybb->input['uid']);
				$form = new Form("index.php?module=user-ipghost&amp;action=delete&amp;uid={$mybb->input['uid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->ipghost_confirm_delete}</p>\n";
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

		$page->add_breadcrumb_item($lang->ipghost, 'index.php?module=user-ipghost');

		$page->output_header($lang->ipghost);

		$sub_tabs['ipghost_erase'] = array(
			'title'			=> $lang->ipghost_erase,
			'link'			=> 'index.php?module=user-ipghost',
			'description'	=> $lang->ipghost_erase_desc
		);

		$sub_tabs['ipghost_ghosts'] = array(
			'title'			=> $lang->ipghost_ghosts,
			'link'			=> 'index.php?module=user-ipghost&amp;action=ghosts',
			'description'	=> $lang->ipghost_ghosts_desc
		);

		if($mybb->input['action'] == '' || $mybb->input['action'] == 'erase')
		{
			$page->output_nav_tabs($sub_tabs, 'ipghost_erase');

			$form = new Form("index.php?module=user-ipghost&amp;action=erase", "post", "ipghost");

			$form_container = new FormContainer($lang->ipghost_erase);
			$form_container->output_row($lang->ipghost_username, $lang->ipghost_username_desc, $form->generate_text_box('username', '', array('id' => 'username')), 'username');
			$form_container->end();

			$buttons = "";
			$buttons[] = $form->generate_submit_button($lang->ipghost_submit);
			$buttons[] = $form->generate_reset_button($lang->ipghost_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif($mybb->input['action'] == "ghosts")
		{
			$page->output_nav_tabs($sub_tabs, 'ipghost_ghosts');

			$form = new Form("index.php?module=user-ipghost&amp;action=ghost", "post", "ipghost");

			$form_container = new FormContainer($lang->ipghost_add);
			$form_container->output_row($lang->ipghost_username, $lang->ipghost_username_desc, $form->generate_text_box('username', '', array('id' => 'username')), 'username');
			$form_container->end();

			$buttons = "";
			$buttons[] = $form->generate_submit_button($lang->ipghost_submit);
			$buttons[] = $form->generate_reset_button($lang->ipghost_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();

			echo "<br />";

			$per_page = 25;

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

			$query = $db->simple_select("ipghost", "COUNT(id) as entries");
			$total_rows = $db->fetch_field($query, "entries");

			if ($total_rows > $per_page)
				echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=user-ipghost&amp;action=ghosts&amp;page={page}");

			$table = new Table;
			$table->construct_header($lang->ipghost_user, array('width' => '15%'));
			$table->construct_header($lang->ipghost_action, array('width' => '15%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT g.*, u.username
				FROM ".TABLE_PREFIX."ipghost g
				LEFT JOIN ".TABLE_PREFIX."users u ON (g.uid=u.uid)
				ORDER BY username ASC
				LIMIT {$start}, {$per_page}
			");

			while($user = $db->fetch_array($query))
			{
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($user['username']), $user['uid']));

				$table->construct_cell("<a href=\"index.php?module=user-ipghost&amp;action=delete&amp;uid=".intval($user['uid'])."\">".$lang->ipghost_remove."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->ipghost_empty, array('colspan' => 2));
				$table->construct_row();
			}

			$table->output($lang->ipghost_ghosts);
		}

		$page->output_footer();
		exit;
	}
}

// &#71;&#101;&#110;&#101;&#114;&#105;&#99;
?>
