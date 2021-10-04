<?php
/***************************************************************************
 *
 *  My Permissions plugin (/inc/plugins/mypermissions.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  This plugin allows you to manage permissions on a larger scale.
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
$plugins->add_hook('global_end', 'mypermissions_nopermission');
$plugins->add_hook('admin_load', 'mypermissions_admin');
$plugins->add_hook('admin_tools_menu', 'mypermissions_admin_tools_menu');
$plugins->add_hook('admin_tools_action_handler', 'mypermissions_admin_tools_action_handler');
$plugins->add_hook('admin_tools_permissions', 'mypermissions_admin_permissions');

function mypermissions_info()
{
	return array(
		"name"			=> "My Permissions",
		"description"	=> "This plugin allows you to manage permissions on a larger scale.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.4",
		"guid" 			=> "d4e5d208dc29b48dbd11b1adaf1fe8a8",
		"compatibility"	=> "18*"
	);
}


function mypermissions_activate()
{
	global $db, $lang;
	// create settings group
	$insertarray = array(
		'name' => 'mypermissions',
		'title' => 'My Permissions',
		'description' => "Settings for My Permissions",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);
	// add settings

	$setting0 = array(
		"sid"			=> NULL,
		"name"			=> "mypermissions_disabled",
		"title"			=> "My Permissions disabled?",
		"description"	=> "Set to yes if you want to disable My Permissions.",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting0);

	rebuild_settings();

	// create permissions table
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."mypermissions_actions` (
	  `aid` int(10) UNSIGNED NOT NULL auto_increment,
	  `file` varchar(100) NOT NULL default '',
	  `field` varchar(50) NOT NULL default '',
	  `value` varchar(50) NOT NULL default '',
	  `description` varchar(300) NOT NULL default '',
	  `usergroups` varchar(300) NOT NULL default '',
	  PRIMARY KEY  (`aid`)
		) ENGINE=MyISAM");
}


function mypermissions_deactivate()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'mypermissions'");

	// remove settings
	$db->delete_query('settings', 'name IN ( \'mypermissions_disabled\')');

	rebuild_settings();

	if ($db->table_exists('mypermissions_actions'))
		$db->drop_table('mypermissions_actions');

	$db->delete_query('datacache', 'title=\'mypermissions\'');
}

function mypermissions_nopermission()
{
	global $mybb, $db, $cache, $current_page;

	$error = false;

	/*if (!empty($current_page))
	{
		$query = $db->simple_select('mypermissions_actions', '*', 'file=\''.$db->escape_string($current_page).'\'');
	}
	else {
		$query = $db->simple_select('mypermissions_actions', '*', 'file=\''.$db->escape_string(basename(trim($_SERVER['SCRIPT_NAME']))).'\'');
	}

	while ($rule = $db->fetch_array($query))
	{
		if (empty($rule) || ($rule['field'] != '' && (!isset($mybb->input[$rule['field']]) || $mybb->input[$rule['field']] != $rule['value'])))
			continue;

		if (mypermissions_check_permissions($rule['usergroups']))
		{
			$error = true;
			break; // break the loop on no permissions - better than doing the no permissions error inside the loop
		}
	}*/

	$permissions = $cache->read("mypermissions");
	if ($permissions === false || empty($permissions)) return;

	foreach ($permissions as $rule)
	{
		if (isset($current_page) && $current_page != '')
			if($rule['file'] != $current_page)
				continue;
		else
			if($rule['file'] != basename(trim($_SERVER['SCRIPT_NAME'])))
				continue;

		if (empty($rule) || ($rule['field'] != '' && (!isset($mybb->input[$rule['field']]) || trim($mybb->input[$rule['field']]) != trim($rule['value']))))
			continue;

		if (mypermissions_check_permissions($rule['usergroups']))
		{
			$error = true;
			break; // break the loop on no permissions - better than doing the no permissions error inside the loop
		}
	}

	// No permissions -> Error :D
	if ($error)
		error_no_permission();
}

// Function to check for permissions of primary group and additional groups
function mypermissions_check_permissions($groups_comma)
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

function mypermissions_admin_tools_menu(&$sub_menu)
{
	global $lang;

	$lang->load('mypermissions');
	$sub_menu[] = array('id' => 'mypermissions', 'title' => $lang->mypermissions_index, 'link' => 'index.php?module=tools-mypermissions');
}

function mypermissions_admin_tools_action_handler(&$actions)
{
	$actions['mypermissions'] = array('active' => 'mypermissions', 'file' => 'mypermissions');
}

function mypermissions_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	$lang->load("mypermissions", false, true);
	$admin_permissions['mypermissions'] = $lang->mypermissions_canmanage;

}

function mypermissions_messageredirect($message, $error=0, $action='')
{
  	global $db, $mybb, $lang;

	if (!$message)
		return;

	if ($action)
		$parameters = '&amp;action='.$action;

	if ($error)
	{
		flash_message($message, 'error');
		admin_redirect("index.php?module=tools-mypermissions".$parameters);
	}
	else {
		flash_message($message, 'success');
		admin_redirect("index.php?module=tools-mypermissions".$parameters);
	}
}

function mypermissions_admin()
{
	global $db, $lang, $mybb, $cache, $page, $run_module, $action_file, $mybbadmin, $plugins;

	$lang->load("mypermissions", false, true);

	if($run_module == 'tools' && $action_file == 'mypermissions')
	{

		if ($mybb->request_method == "post")
		{
			switch($mybb->input['action'])
			{
				case 'do_addrule':
					if ($mybb->input['file'] == '' || $mybb->input['usergroups'] == '')
					{
						mypermissions_messageredirect($lang->mypermissions_missing_field, 1);
					}

					$file = $db->escape_string($mybb->input['file']);
					$field = $db->escape_string($mybb->input['field']);
					$value = $db->escape_string($mybb->input['value']);
					$description = $db->escape_string($mybb->input['description']);

					// get user groups entered
					if(is_array($mybb->input['usergroups']))
					{
						foreach($mybb->input['usergroups'] as $gid)
						{
							if($gid == $mybb->input['usergroups'])
							{
								unset($mybb->input['usergroups'][$gid]);
							}
						}
						$usergroups = implode(",", $mybb->input['usergroups']);
					}
					else
					{
						$usergroups = '';
					}
					$usergroups = $db->escape_string($usergroups);

					//$usergroups = $db->escape_string($mybb->input['usergroups']);

					$insert_query = array('file' => $file, 'field' => $field, 'value' => $value, 'description' => $description, 'usergroups' => $usergroups);
					$db->insert_query('mypermissions_actions', $insert_query);

					// cache all rules again
					$rules = '';
					$query = $db->simple_select('mypermissions_actions', '*', '', array('order_by' => 'file', 'order_dir' => 'asc'));
					while ($rule = $db->fetch_array($query))
					{
						$rules[$rule['aid']] = $rule;
					}

					$cache->update('mypermissions', $rules);

					mypermissions_messageredirect($lang->mypermissions_rule_added);
				break;
				case 'do_editrule':
					$aid = intval($mybb->input['aid']);
					if ($aid <= 0 || (!($rule = $db->fetch_array($db->simple_select('mypermissions_actions', '*', "aid = $aid")))))
					{
						mypermissions_messageredirect($lang->mypermissions_invalid_rule, 1);
					}

					if ($mybb->input['file'] == '' || $mybb->input['usergroups'] == '')
					{
						mypermissions_messageredirect($lang->mypermissions_missing_field, 1);
					}

					$file = $db->escape_string($mybb->input['file']);
					$field = $db->escape_string($mybb->input['field']);
					$value = $db->escape_string($mybb->input['value']);
					$description = $db->escape_string($mybb->input['description']);

					// get user groups entered
					if(is_array($mybb->input['usergroups']))
					{
						foreach($mybb->input['usergroups'] as $gid)
						{
							if($gid == $mybb->input['usergroups'])
							{
								unset($mybb->input['usergroups'][$gid]);
							}
						}
						$usergroups = implode(",", $mybb->input['usergroups']);
					}
					else
					{
						$usergroups = '';
					}
					$usergroups = $db->escape_string($usergroups);

					//$usergroups = $db->escape_string($mybb->input['usergroups']);

					$update_query = array('file' => $file, 'field' => $field, 'value' => $value, 'description' => $description, 'usergroups' => $usergroups);
					$db->update_query('mypermissions_actions', $update_query, 'aid=\''.$aid.'\'');

					// cache all rules again
					$rules = '';
					$query = $db->simple_select('mypermissions_actions', '*', '', array('order_by' => 'file', 'order_dir' => 'asc'));
					while ($rule = $db->fetch_array($query))
					{
						$rules[$rule['aid']] = $rule;
					}

					$cache->update('mypermissions', $rules);

					mypermissions_messageredirect($lang->mypermissions_rule_edited);
				break;
			}
		}

		if ($mybb->input['action'] == 'do_deleterule')
		{
			$aid = intval($mybb->input['aid']);
			if ($aid <= 0 || (!($rule = $db->fetch_array($db->simple_select('mypermissions_actions', 'aid', "aid = $aid")))))
			{
				mypermissions_messageredirect($lang->mypermissions_invalid_rule, 1);
			}

			$page->add_breadcrumb_item($lang->mypermissions, 'index.php?module=tools-mypermissions');
			$page->output_header($lang->mypermissions);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=tools-mypermissions");
			}

			if($mybb->request_method == "post")
			{
				$db->delete_query('mypermissions_actions', "aid = $aid");

				// cache all rules again
				$rules = '';
				$query = $db->simple_select('mypermissions_actions', '*', '', array('order_by' => 'file', 'order_dir' => 'asc'));
				while ($rule = $db->fetch_array($query))
				{
					$rules[$rule['aid']] = $rule;
				}

				$cache->update('mypermissions', $rules);

				mypermissions_messageredirect($lang->mypermissions_rule_deleted);
			}
			else
			{
				$mybb->input['pid'] = intval($mybb->input['pid']);
				$form = new Form("index.php?module=tools-mypermissions&amp;action=do_deleterule&amp;aid={$mybb->input['aid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->mypermissions_confirm_deleterule}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}

		if (!$mybb->input['action'] || $mybb->input['action'] == 'rules' || $mybb->input['action'] == 'addrule' || $mybb->input['action'] == 'editrule')
		{
			$page->add_breadcrumb_item($lang->mypermissions, 'index.php?module=tools-mypermissions');

			$page->output_header($lang->mypermissions);

			$sub_tabs['mypermissions_rules'] = array(
				'title'			=> $lang->mypermissions_rules,
				'link'			=> 'index.php?module=tools-mypermissions',
				'description'	=> $lang->mypermissions_rules_desc
			);

			$sub_tabs['mypermissions_rules_add'] = array(
				'title'			=> $lang->mypermissions_rules_add,
				'link'			=> 'index.php?module=tools-mypermissions&amp;action=addrule',
				'description'	=> $lang->mypermissions_rules_add_desc
			);
			$sub_tabs['mypermissions_rules_edit'] = array(
				'title'			=> $lang->mypermissions_rules_edit,
				'link'			=> 'index.php?module=tools-mypermissions&amp;action=editrule',
				'description'	=> $lang->mypermissions_rules_edit_desc
			);
			$sub_tabs['mypermissions_rules_delete'] = array(
				'title'			=> $lang->mypermissions_rules_delete,
				'link'			=> 'index.php?module=tools-mypermissions&amp;action=do_deleterule',
				'description'	=> $lang->mypermissions_rules_delete_desc
			);
		}

		if (!$mybb->input['action'] || $mybb->input['action'] == 'rules')
		{
			$page->output_nav_tabs($sub_tabs, 'mypermissions_rules');

			// table
			$table = new Table;
			$table->construct_header($lang->mypermissions_file, array('width' => '25%'));
			$table->construct_header($lang->mypermissions_field, array('width' => '20%'));
			$table->construct_header($lang->mypermissions_value, array('width' => '20%'));
			$table->construct_header($lang->mypermissions_usergroups, array('width' => '20%'));
			$table->construct_header($lang->mypermissions_options, array('width' => '15%', 'class' => 'align_center'));

			$query = $db->simple_select('mypermissions_actions', '*', '', array('order_by' => 'file', 'order_dir' => 'asc'));

			while ($rule = $db->fetch_array($query))
			{
				$table->construct_cell(htmlspecialchars_uni($rule['file'])."<br />".htmlspecialchars_uni($rule['description']));
				$table->construct_cell(htmlspecialchars_uni($rule['field']));
				$table->construct_cell(htmlspecialchars_uni($rule['value']));
				$table->construct_cell(htmlspecialchars_uni($rule['usergroups']));

				// actions column
				$table->construct_cell("<a href=\"index.php?module=tools-mypermissions&amp;action=editrule&amp;aid=".intval($rule['aid'])."\">".$lang->mypermissions_edit."</a> - <a href=\"index.php?module=tools-mypermissions&amp;action=do_deleterule&amp;aid=".intval($rule['aid'])."\">".$lang->mypermissions_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if ($table->num_rows()==0)
			{
				$table->construct_cell($lang->mypermissions_norules, array('colspan' => 5));

				$table->construct_row();
			}

			$table->output($lang->mypermissions_rules);
		}
		elseif ($mybb->input['action'] == 'addrule')
		{
			$page->output_nav_tabs($sub_tabs, 'mypermissions_rules_add');

			$options = array();

			$query = $db->simple_select("usergroups", "gid, title", "", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$options[$usergroup['gid']] = $usergroup['title'];
			}

			$form = new Form("index.php?module=tools-mypermissions&amp;action=do_addrule", "post", "mypermissions");

			$form_container = new FormContainer($lang->mypermissions_addrule);
			$form_container->output_row($lang->mypermissions_addrule_file."<em>*</em>", $lang->mypermissions_addrule_file_desc, $form->generate_text_box('file', '', array('id' => 'file')), 'file');
			$form_container->output_row($lang->mypermissions_addrule_field, $lang->mypermissions_addrule_field_desc, $form->generate_text_box('field', '', array('id' => 'field')), 'field');
			$form_container->output_row($lang->mypermissions_addrule_value, $lang->mypermissions_addrule_value_desc, $form->generate_text_box('value', '', array('id' => 'value')), 'value');
			$form_container->output_row($lang->mypermissions_addrule_description, $lang->mypermissions_addrule_description_desc, $form->generate_text_box('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->mypermissions_addrule_usergroups."<em>*</em>", $lang->mypermissions_addrule_usergroups_desc, $form->generate_select_box('usergroups[]', $options, '', array('id' => 'usergroups', 'multiple' => true, 'size' => 5)), 'groups');

			$form_container->end();

			$buttons = "";
			$buttons[] = $form->generate_submit_button($lang->mypermissions_submit);
			$buttons[] = $form->generate_reset_button($lang->mypermissions_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'editrule')
		{
			$page->output_nav_tabs($sub_tabs, 'mypermissions_rules_edit');

			$options = array();

			$query = $db->simple_select("usergroups", "gid, title", "", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$options[$usergroup['gid']] = $usergroup['title'];
			}

			$aid = intval($mybb->input['aid']);
			if ($aid <= 0 || (!($rule = $db->fetch_array($db->simple_select('mypermissions_actions', '*', "aid = $aid")))))
			{
				mypermissions_messageredirect($lang->mypermissions_invalid_rule, 1);
			}

			$form = new Form("index.php?module=tools-mypermissions&amp;action=do_editrule", "post", "mypermissions");
			echo $form->generate_hidden_field('aid', $aid);

			$form_container = new FormContainer($lang->mypermissions_editrule);
			$form_container->output_row($lang->mypermissions_editrule_file."<em>*</em>", $lang->mypermissions_editrule_file_desc, $form->generate_text_box('file', htmlspecialchars_uni($rule['file']), array('id' => 'file')), 'file');
			$form_container->output_row($lang->mypermissions_editrule_field, $lang->mypermissions_editrule_field_desc, $form->generate_text_box('field', htmlspecialchars_uni($rule['field']), array('id' => 'field')), 'field');
			$form_container->output_row($lang->mypermissions_editrule_value, $lang->mypermissions_editrule_value_desc, $form->generate_text_box('value', htmlspecialchars_uni($rule['value']), array('id' => 'value')), 'value');
			$form_container->output_row($lang->mypermissions_editrule_description, $lang->mypermissions_editrule_description_desc, $form->generate_text_box('description', htmlspecialchars_uni($rule['description']), array('id' => 'description')), 'description');
			$form_container->output_row($lang->mypermissions_editrule_usergroups."<em>*</em>", $lang->mypermissions_editrule_usergroups_desc, $form->generate_select_box('usergroups[]', $options, explode(',',$rule['usergroups']), array('id' => 'usergroups', 'multiple' => true, 'size' => 5)), 'groups');

			$form_container->end();

			$buttons = "";
			$buttons[] = $form->generate_submit_button($lang->mypermissions_submit);
			$buttons[] = $form->generate_reset_button($lang->mypermissions_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}

		$page->output_footer();
		exit;
	}
}

?>
