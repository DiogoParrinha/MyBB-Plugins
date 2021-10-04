<?php
/***************************************************************************
 *
 *   NewPoints Shop plugin (/inc/plugins/newpoints/languages/english/newpoints_shop.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2014 Diogo Parrinha
 *
 *   Website: http://www.mybb-plugins.com
 *
 *   Integrates a shop system with NewPoints.
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

$plugins->add_hook("newpoints_start", "newpoints_shop_page");

if (defined("IN_ADMINCP"))
{
	$plugins->add_hook("newpoints_admin_stats_noaction_end", "newpoints_shop_admin_stats");

	$plugins->add_hook('newpoints_admin_load', 'newpoints_shop_admin');
	$plugins->add_hook('newpoints_admin_newpoints_menu', 'newpoints_shop_admin_newpoints_menu');
	$plugins->add_hook('newpoints_admin_newpoints_action_handler', 'newpoints_shop_admin_newpoints_action_handler');
	$plugins->add_hook('newpoints_admin_newpoints_permissions', 'newpoints_shop_admin_permissions');

	$plugins->add_hook("newpoints_admin_grouprules_add", "newpoints_shop_admin_rule");
	$plugins->add_hook("newpoints_admin_grouprules_edit", "newpoints_shop_admin_rule");
	$plugins->add_hook("newpoints_admin_grouprules_add_insert", "newpoints_shop_admin_rule_post");
	$plugins->add_hook("newpoints_admin_grouprules_edit_update", "newpoints_shop_admin_rule_post");
}
else
{
	$plugins->add_hook("newpoints_stats_start", "newpoints_shop_stats");
	$plugins->add_hook("member_profile_end", "newpoints_shop_profile");
	$plugins->add_hook("member_profile_start", "newpoints_shop_profile_lang");

	$plugins->add_hook('postbit_prev', 'newpoints_shop_postbit');
	$plugins->add_hook('postbit_pm', 'newpoints_shop_postbit');
	$plugins->add_hook('postbit_announcement', 'newpoints_shop_postbit');
	$plugins->add_hook('postbit', 'newpoints_shop_postbit');

	$plugins->add_hook("newpoints_default_menu", "newpoints_shop_menu");
}

// backup shop fields too
$plugins->add_hook("newpoints_task_backup_tables", "newpoints_shop_backup");

function newpoints_shop_info()
{
	return array(
		"name"			=> "Shop",
		"description"	=> "Integrates a shop system with NewPoints.",
		"website"		=> "http://www.mybb-plugins.com",
		"author"		=> "Diogo Parrinha",
		"authorsite"	=> "http://www.mybb-plugins.com",
		"version"		=> "1.9.4",
		"guid" 			=> "",
		"compatibility" => "2*"
	);
}

function newpoints_shop_install()
{
	global $db;
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `newpoints_items` TEXT NOT NULL;");
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."newpoints_grouprules` ADD `items_rate` DECIMAL(6,3) NOT NULL default 1.000;");

	// add settings
	newpoints_add_setting('newpoints_shop_sendable', 'newpoints_shop', 'Send items', 'Allow users to send items to other users.', 'yesno', '1', 1);
	newpoints_add_setting('newpoints_shop_sellable', 'newpoints_shop', 'Sell items', 'Allow users to sell items.', 'yesno', '1', 2);
	newpoints_add_setting('newpoints_shop_lastpurchases', 'newpoints_shop', 'Last Purchases', 'Number of last purchases to show in statistics.', 'text', '10', 3);
	newpoints_add_setting('newpoints_shop_percent', 'newpoints_shop', 'Sell Percentage', 'The (discounted) rate at which items can be sold for.', 'text', '0.75', 4);
	newpoints_add_setting('newpoints_shop_viewothers', 'newpoints_shop', 'Can View Others\' inventories', 'Allow users to view other users\' inventories. Note, admins always are able to view other users\' inventories.', 'yesno', '1', 5);
	newpoints_add_setting('newpoints_shop_itemsprofile', 'newpoints_shop', 'Items on profile', 'Number of items to show in profile page. Set to 0 to disable this feature.', 'text', '5', 6);
	newpoints_add_setting('newpoints_shop_itemspostbit', 'newpoints_shop', 'Items on postbit', 'Number of items to show in postbit. Set to 0 to disable this feature.', 'text', '5', 7);
	newpoints_add_setting('newpoints_shop_pmadmins', 'newpoints_shop', 'PM Admins', 'Enter the user IDs of the users that get PMs whenever an item is bought (separated by a comma).', 'text', '1', 8);
	newpoints_add_setting('newpoints_shop_pm_default', 'newpoints_shop', 'Default PM', 'Enter the content of the message body that is sent by default to users when they buy an item (note: this PM can be customized for each item; this is used in case one is not present). You can use {itemname} and {itemid}.', 'textarea', '', 9);
	newpoints_add_setting('newpoints_shop_pmadmin_default', 'newpoints_shop', 'Default Admin PM', 'Enter the content of the message body that is sent by default to admins when a user buys an item (note: this PM can be customized for each item; this is used in case one is not present). You can use {itemname} and {itemid}.', 'textarea', '', 10);

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_shop_categories` (
	  `cid` bigint(30) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(100) NOT NULL default '',
	  `description` text NOT NULL,
	  `visible` smallint(1) NOT NULL default '1',
	  `icon` varchar(255) NOT NULL default '',
	  `usergroups` varchar(100) NOT NULL default '',
	  `disporder` int(5) NOT NULL default '0',
	  `items` int(10) NOT NULL default '0',
	  `expanded` smallint(1) NOT NULL default '1',
	  PRIMARY KEY  (`cid`)
		) ENGINE=MyISAM");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_shop_items` (
	  `iid` bigint(30) UNSIGNED NOT NULL auto_increment,
	  `name` varchar(100) NOT NULL default '',
	  `description` text NOT NULL,
	  `price` DECIMAL(16,2) NOT NULL default 0.00,
	  `icon` varchar(255) NOT NULL default '',
	  `visible` smallint(1) NOT NULL default '1',
	  `disporder` int(5) NOT NULL default '0',
	  `infinite` smallint(1) NOT NULL default '0',
	  `limit` smallint(1) NOT NULL default '0',
	  `stock` int(10) NOT NULL default '0',
	  `sendable` smallint(1) NOT NULL default '1',
	  `sellable` smallint(1) NOT NULL default '1',
	  `cid` int(10) NOT NULL default '0',
	  `pm` text NOT NULL,
	  `pmadmin` text NOT NULL,
	  PRIMARY KEY  (`iid`)
		) ENGINE=MyISAM");

	rebuild_settings();
}

function newpoints_shop_is_installed()
{
	global $db;
	if($db->field_exists('newpoints_items', 'users'))
	{
		return true;
	}
	return false;
}

function newpoints_shop_uninstall()
{
	global $db;
	if($db->field_exists('newpoints_items', 'users'))
		$db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `newpoints_items`;");
	if($db->field_exists('items_rate', 'newpoints_grouprules'))
		$db->write_query("ALTER TABLE `".TABLE_PREFIX."newpoints_grouprules` DROP `items_rate`;");

	// delete settings
	newpoints_remove_settings("'newpoints_shop_sendable','newpoints_shop_sellable','newpoints_shop_lastpurchases','newpoints_shop_percent','newpoints_shop_viewothers','newpoints_shop_itemsprofile','newpoints_shop_itemspostbit'");
	rebuild_settings();

	if($db->table_exists('newpoints_shop_categories'))
	{
		$db->drop_table('newpoints_shop_categories');
	}

	if($db->table_exists('newpoints_shop_items'))
	{
		$db->drop_table('newpoints_shop_items');
	}

	newpoints_remove_log(array('shop_purchase', 'shop_send', 'shop_sell'));
}

function newpoints_shop_activate()
{
	global $db, $mybb;

	newpoints_add_template('newpoints_shop', '
<html>
<head>
<title>{$lang->newpoints} - {$lang->newpoints_shop}</title>
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
<td class="thead" colspan="5"><div style="float: right"><strong><a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=shop&amp;shop_action=myitems" style="text-decoration: underline">{$lang->newpoints_shop_myitems}</a></strong></div><strong>{$lang->newpoints_shop}</strong></td>
</tr>
<tr>
<td class="tcat" width="1%"><strong>{$lang->newpoints_shop_icon}</strong></td>
<td class="tcat"><strong>{$lang->newpoints_shop_name}</strong></td>
<td class="tcat" width="20%" align="center"><strong>{$lang->newpoints_shop_price}</strong></td>
<td class="tcat" width="20%" align="center"><strong>{$lang->newpoints_shop_stock}</strong></td>
<td class="tcat" width="10%" align="center"><strong>{$lang->newpoints_shop_buy}</strong></td>
</tr>
{$cats}
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>');

	newpoints_add_template('newpoints_shop_category', '
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="5"><div class="expcolimage"><img src="{$theme[\'imgdir\']}/{$expcolimage}" id="shopcat_{$category[\'cid\']}_img" class="expander" alt="{$expaltext}" title="{$expaltext}" /></div>
{$category[\'icon\']} <strong>{$category[\'name\']}</strong> <span class="smalltext">{$category[\'description\']}</span></td>
</tr>
</table>
<div style="max-height: 200px; overflow: auto;">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tbody id="shopcat_{$category[\'cid\']}_e" style="{$expdisplay}">
{$items}
</tbody>
</table>
</div>');

	newpoints_add_template('newpoints_shop_item', '
<tr>
<td class="{$bgcolor}" width="1%" valign="middle" align="center">{$item[\'icon\']}</td>
<td class="{$bgcolor}" valign="middle"><a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=shop&amp;shop_action=view&amp;iid={$item[\'iid\']}">{$item[\'name\']}</a><br /><span class="smalltext">{$item[\'description\']}</span></td>
<td class="{$bgcolor}" width="20%" align="center" valign="middle">{$item[\'price\']}</td>
<td class="{$bgcolor}" width="20%" align="center" valign="middle">{$item[\'stock\']}</td>
<td class="{$bgcolor}" width="10%" align="center" valign="middle"><form action="newpoints.php?action=do_shop&amp;shop_action=buy" method="POST"><input type="hidden" name="postcode" value="{$mybb->post_code}"><input type="hidden" name="iid" value="{$item[\'iid\']}"><input type="submit" name="buy" value="{$lang->newpoints_shop_buy}" onclick="return confirm(\'{$lang->newpoints_shop_confirm_buy}\');"></form></td>
</tr>');

	newpoints_add_template('newpoints_shop_no_items', '
<tr>
<td class="trow1" colspan="5">{$lang->newpoints_shop_no_items}</td>
</tr>');

	newpoints_add_template('newpoints_shop_no_cats', '
<tr>
<td class="trow1" colspan="5">{$lang->newpoints_shop_no_cats}</td>
</tr>');

	newpoints_add_template('newpoints_shop_myitems', '
<html>
<head>
<title>{$lang->newpoints} - {$lang->newpoints_shop_myitems}</title>
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
<td class="thead" colspan="4"><strong><div style="float: right"><a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=shop">{$lang->newpoints_shop}</a></div>{$lang->newpoints_shop_myitems}</strong></td>
</tr>
{$items}
</table>
{$multipage}
</td>
</tr>
</table>
{$footer}
</body>
</html>');

	newpoints_add_template('newpoints_shop_myitems_item', '
<td class="{$bgcolor}" width="50%" valign="middle" align="center">
<fieldset class="{$invert_bgcolor}" style="width: 80%;"><legend>{$item[\'icon\']} <strong><a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=shop&amp;shop_action=view&amp;iid={$item[\'iid\']}">{$item[\'name\']}</a></strong></legend>
<div align="left"><span class="smalltext">{$item[\'description\']}</span></div>
<div align="left">
{$lang->newpoints_shop_price}: {$item[\'price\']}<br />
{$lang->newpoints_shop_quantity}: {$item[\'quantity\']}
</div>
</fieldset>
<fieldset class="{$invert_bgcolor}" style="width: 80%; margin-bottom: 5px;">
<legend>{$lang->newpoints_shop_options}</legend>
<table border="0">
<tr>
{$send}{$sell}
</tr>
</table>
</fieldset>
</td>
');

	newpoints_add_template('newpoints_shop_myitems_item_empty', '
<td class="{$bgcolor}" width="50%" valign="middle"></td>
');

	newpoints_add_template('newpoints_shop_myitems_no_items', '
<tr>
<td class="trow1" colspan="2">{$lang->newpoints_shop_no_items}</td>
</tr>');

	newpoints_add_template('newpoints_shop_do_action', '
<head>
<title>{$lang->newpoints} - {$lang->newpoints_shop_action}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="newpoints.php?action=do_shop" method="POST">
<input type="hidden" name="postcode" value="{$mybb->post_code}">
<input type="hidden" name="shop_action" value="{$shop_action}">
{$fields}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->newpoints_shop_action}:</strong> {$item[\'name\']}</td>
</tr>
<tr>
{$data}
</tr>
<tr>
<td class="tfoot" width="100%" align="center" colspan="{$colspan}"><input type="submit" name="submit" value="{$lang->newpoints_shop_confirm}"></td>
</tr>
</table>
</form>
{$footer}
</body>
</html>');

	newpoints_add_template('newpoints_shop_stats', '
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="4"><strong>{$lang->newpoints_shop_lastpurchases}</strong></td>
</tr>
<tr>
<td class="tcat" width="40%"><strong>{$lang->newpoints_shop_user}</strong></td>
<td class="tcat" width="30%"><strong>{$lang->newpoints_shop_item}</strong></td>
<td class="tcat" width="30%" align="center"><strong>{$lang->newpoints_shop_date}</strong></td>
</tr>
{$last_purchases}
</table><br />');

	newpoints_add_template('newpoints_shop_stats_purchase', '
<tr>
<td class="{$bgcolor}" width="40%">{$purchase[\'user\']}</td>
<td class="{$bgcolor}" width="30%">{$purchase[\'item\']}</td>
<td class="{$bgcolor}" width="30%" align="center">{$purchase[\'date\']}</td>
</tr>');

	newpoints_add_template('newpoints_shop_stats_nopurchase', '
<tr>
<td class="trow1" width="100%" colspan="3">{$lang->newpoints_shop_no_purchases}</td>
</tr>');

	newpoints_add_template('newpoints_shop_profile', '<tr>
	<td class="trow2"><strong>{$lang->newpoints_shop_items}:</strong></td>
	<td class="trow2">{$shop_items} <span class="smalltext">(<a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=shop&amp;shop_action=myitems&amp;uid={$memprofile[\'uid\']}">{$lang->newpoints_shop_view_all_items}</a>)</span></td>
</tr>');

	newpoints_add_template('newpoints_shop_postbit', '
	<br />{$lang->newpoints_shop_items}: {$shop_items} <span class="smalltext">(<a href="{$mybb->settings[\'bburl\']}/newpoints.php?action=shop&amp;shop_action=myitems&amp;uid={$post[\'uid\']}">{$lang->newpoints_shop_view_all_items}</a>)</span>');

	newpoints_add_template('newpoints_shop_view_item', '
<html>
<head>
<title>{$lang->newpoints} - {$lang->newpoints_shop_view_item}</title>
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
<td class="thead" width="100%"><strong>{$lang->newpoints_shop_view_item}:</strong> {$item[\'name\']}</td>
</tr>
<tr>
<td class="trow1" width="100%"><div style="float: left; padding: 5px">{$item[\'icon\']}</div>{$item[\'name\']}<br /><span class="smalltext">{$item[\'description\']}</span></td>
</tr>
<tr>
<td class="trow2" width="100%"><strong>{$lang->newpoints_shop_price}:</strong> {$item[\'price\']}</td>
</tr>
<tr>
<td class="trow1" width="100%"><strong>{$lang->newpoints_shop_stock}:</strong> {$item[\'stock\']}</td>
</tr>
<tr>
<td class="trow2" width="100%"><strong>{$lang->newpoints_shop_sendable}:</strong> {$item[\'sendable\']}</td>
</tr>
<tr>
<td class="trow1" width="100%"><strong>{$lang->newpoints_shop_sellable}:</strong> {$item[\'sellable\']}</td>
</tr>
<tr>
<td class="tfoot" width="100%" align="center" colspan="2"><form action="newpoints.php?action=do_shop&amp;shop_action=buy" method="POST"><input type="hidden" name="postcode" value="{$mybb->post_code}"><input type="hidden" name="iid" value="{$item[\'iid\']}"><input type="submit" name="buy" value="{$lang->newpoints_shop_buy}"  onclick="return confirm(\'{$lang->newpoints_shop_confirm_buy}\');"></form></td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>
');

	// edit templates
	newpoints_find_replace_templatesets('newpoints_statistics', '#'.preg_quote('width="60%">').'#', 'width="60%">{$newpoints_shop_lastpurchases}');
	newpoints_find_replace_templatesets('newpoints_postbit', '#'.preg_quote('{$donate}').'#', '{$donate}{$post[\'newpoints_shop_items\']}');

	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#'.preg_quote('{$warning_level}').'#', '{$warning_level}'.'{$newpoints_shop_profile}');
	find_replace_templatesets("member_profile_adminoptions", '#'.preg_quote('</ul>').'#', '<li><a href="{$mybb->settings[\'bburl\']}/{$config[\'admin_dir\']}/index.php?module=newpoints-shop&amp;action=inventory&amp;uid={$uid}">{$lang->newpoints_shop_edit_inventory}</a></li></ul>');
}

function newpoints_shop_deactivate()
{
	global $db, $mybb;

	newpoints_remove_templates("'newpoints_shop','newpoints_shop_category','newpoints_shop_item','newpoints_shop_no_items','newpoints_shop_no_cats','newpoints_shop_myitems','newpoints_shop_myitems_item','newpoints_shop_myitems_no_items','newpoints_shop_do_action','newpoints_shop_stats','newpoints_shop_stats_purchase','newpoints_shop_stats_nopurchase','newpoints_shop_myitems_item_empty','newpoints_shop_profile','newpoints_shop_view_item','newpoints_shop_postbit'");

	// edit templates
	newpoints_find_replace_templatesets('newpoints_statistics', '#'.preg_quote('{$newpoints_shop_lastpurchases}').'#', '');
	//newpoints_find_replace_templatesets('newpoints_profile', '#'.preg_quote('{$newpoints_shop_profile}').'#', '');
	newpoints_find_replace_templatesets('newpoints_postbit', '#'.preg_quote('{$post[\'newpoints_shop_items\']}').'#', '');

	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", '#'.preg_quote('{$newpoints_shop_profile}').'#', '', 0);
	find_replace_templatesets("member_profile_adminoptions", '#'.preg_quote('<li><a href="{$mybb->settings[\'bburl\']}/{$config[\'admin_dir\']}/index.php?module=newpoints-shop&amp;action=inventory&amp;uid={$uid}">{$lang->newpoints_shop_edit_inventory}</a></li>').'#', '', 0);
}

// show shop in the list
function newpoints_shop_menu(&$menu)
{
	global $mybb, $lang;
	newpoints_lang_load("newpoints_shop");

	if ($mybb->input['action'] == 'shop')
		$menu[] = "&raquo; <a href=\"{$mybb->settings['bburl']}/newpoints.php?action=shop\">".$lang->newpoints_shop."</a>";
	else
		$menu[] = "<a href=\"{$mybb->settings['bburl']}/newpoints.php?action=shop\">".$lang->newpoints_shop."</a>";
}

function newpoints_shop_get_item($iid = 0)
{
	global $db, $mybb;
	if (!$iid)
		return false;

	$query = $db->simple_select('newpoints_shop_items', '*', 'iid=\''.intval($iid).'\'');
	$item = $db->fetch_array($query);

	return $item;
}

function newpoints_shop_get_category($cid = 0)
{
	global $db, $mybb;
	if (!$cid)
		return false;

	$query = $db->simple_select('newpoints_shop_categories', '*', 'cid=\''.intval($cid).'\'');
	$cat = $db->fetch_array($query);

	return $cat;
}

/* Checks if the primary or any of the additional groups of the current user are in the groups list passed as a parameter
 * @param String group ids seperated by a comma
 * @return true if the user has permissions, false if not
*/
function newpoints_shop_check_permissions($groups_comma)
{
    global $mybb;

    if ($groups_comma == '')
        return false;

    $groups = explode(",", $groups_comma);
    $add_groups = explode(",", $mybb->user['additionalgroups']);

    if (!in_array($mybb->user['usergroup'], $groups)) { // primary user group not allowed
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

function newpoints_shop_page()
{
	global $mybb, $db, $lang, $cache, $theme, $header, $templates, $plugins, $headerinclude, $footer, $options, $inline_errors;

	if (!$mybb->user['uid'])
		return;

	newpoints_lang_load("newpoints_shop");

	if ($mybb->input['action'] == "do_shop")
	{
		verify_post_check($mybb->input['postcode']);

		$plugins->run_hooks("newpoints_do_shop_start");

		switch ($mybb->input['shop_action'])
		{
			case 'buy':
				$plugins->run_hooks("newpoints_shop_buy_start");

				// check if the item exists
				if (!($item = newpoints_shop_get_item($mybb->input['iid'])))
				{
					error($lang->newpoints_shop_invalid_item);
				}

				// check if the item is assigned to category
				if (!($cat = newpoints_shop_get_category($item['cid'])))
				{
					error($lang->newpoints_shop_invalid_cat);
				}

				// check if we have permissions to view the parent category
				if (!newpoints_shop_check_permissions($cat['usergroups']))
				{
					error_no_permission();
				}

				if ($item['visible'] == 0 || $cat['visible'] == 0)
					error_no_permission();

				// check group rules - primary group check
				$grouprules = newpoints_getrules('group', $mybb->user['usergroup']);
				if (!$grouprules)
					$grouprules['items_rate'] = 1.0; // no rule set so default income rate is 1

				// if the group items rate is 0, the price of the item is 0
				if (floatval($grouprules['items_rate']) == 0)
					$item['price'] = 0;
				else
					$item['price'] = $item['price']*floatval($grouprules['items_rate']);

				if (floatval($item['price']) > floatval($mybb->user['newpoints']))
				{
					$errors[] = $lang->newpoints_shop_not_enough;
				}

				if ($item['infinite'] != 1 && $item['stock'] <= 0)
				{
					$errors[] = $lang->newpoints_shop_out_of_stock;
				}

				if ($item['limit'] != 0)
				{
					// Get how many items of this type we have in our inventory
					$myitems = @unserialize($mybb->user['newpoints_items']);
					if(!$myitems)
						$myitems = array();

					// If more than or equal to $item['limit'] -> FAILED
					if(count(array_keys($myitems, $item['iid'])) >= $item['limit'])
					{
						$errors[] = $lang->newpoints_shop_limit_reached;
					}
				}

				if (!empty($errors))
				{
					$inline_errors = inline_error($errors, $lang->newpoints_shop_inline_errors);
					$mybb->input = array();
					$mybb->input['action'] = 'shop';
				}
				else {
					$myitems = @unserialize($mybb->user['newpoints_items']);
					if (!$myitems)
						$myitems = array();
					$myitems[] = $item['iid'];
					$db->update_query('users', array('newpoints_items' => serialize($myitems)), 'uid=\''.$mybb->user['uid'].'\'');

					// update stock
					if ($item['infinite'] != 1)
						$db->update_query('newpoints_shop_items', array('stock' => $item['stock']-1), 'iid=\''.$item['iid'].'\'');

					// get money from user
					newpoints_addpoints($mybb->user['uid'], -(floatval($item['price'])));

					if(!empty($item['pm']) || $mybb->settings['newpoints_shop_pm_default'] != '')
					{
						// send PM if item has private message
						if($item['pm'] == '' && $mybb->settings['newpoints_shop_pm_default'] != '')
						{
							$item['pm'] = str_replace(array('{itemname}', '{itemid}'), array($item['name'], $item['iid']), $mybb->settings['newpoints_shop_pm_default']);
						}

						newpoints_send_pm(array('subject' => $lang->newpoints_shop_bought_item_pm_subject, 'message' => $item['pm'], 'touid' => $mybb->user['uid'], 'receivepms' => 1), -1);
					}

					if (!empty($item['pmadmin']) || $mybb->settings['newpoints_shop_pmadmins'] != '')
					{
						// send PM if item has private message
						if($item['pmadmin'] == '' && $mybb->settings['newpoints_shop_pm_default'] != '')
						{
							$item['pmadmin'] = str_replace(array('{itemname}', '{itemid}'), array($item['name'], $item['iid']), $mybb->settings['newpoints_shop_pmadmin_default']);
						}

						newpoints_send_pm(array('subject' => $lang->newpoints_shop_bought_item_pmadmin_subject, 'message' => $item['pmadmin'], 'touid' => array(explode(',', $mybb->settings['newpoints_shop_pmadmins'])), 'receivepms' => 1), $mybb->user['uid']);
					}

					$plugins->run_hooks("newpoints_shop_buy_end", $item);

					// log purchase
					newpoints_log('shop_purchase', $lang->sprintf($lang->newpoints_shop_purchased_log, $item['iid'], $item['price']));

					redirect($mybb->settings['bburl']."/newpoints.php?action=shop", $lang->newpoints_shop_item_bought, $lang->newpoints_shop_item_bought_title);
				}
			break;

			case 'send':
				$plugins->run_hooks("newpoints_shop_send_start");

				// check if the item exists
				if (!($item = newpoints_shop_get_item($mybb->input['iid'])))
				{
					error($lang->newpoints_shop_invalid_item);
				}

				// check if the item is assigned to category
				if (!($cat = newpoints_shop_get_category($item['cid'])))
				{
					error($lang->newpoints_shop_invalid_cat);
				}

				// check if we have permissions to view the parent category
				if (!newpoints_shop_check_permissions($cat['usergroups']))
				{
					error_no_permission();
				}

				if ($item['visible'] == 0 || $cat['visible'] == 0)
					error_no_permission();

				$myitems = @unserialize($mybb->user['newpoints_items']);
				if (!$myitems)
					error($lang->newpoints_shop_inventory_empty);

				// make sure we own the item
				$key = array_search($item['iid'], $myitems);
				if ($key === false)
					error($lang->newpoints_shop_selected_item_not_owned);

				$lang->newpoints_shop_action = $lang->newpoints_shop_send_item;
				$item['name'] = htmlspecialchars_uni($item['name']);

				global $shop_action, $data, $colspan;
				$colspan = 2;
				$shop_action = 'do_send';
				$fields = '<input type="hidden" name="iid" value="'.$item['iid'].'">';
				$data = "<td class=\"trow1\" width=\"50%\"><strong>".$lang->newpoints_shop_send_item_username.":</strong><br /><small>".$lang->newpoints_shop_send_item_message."</small></td><td class=\"trow1\" width=\"50%\"><input type=\"text\" class=\"textbox\" name=\"username\" value=\"\"></td>";

				$plugins->run_hooks("newpoints_shop_send_end");

				eval("\$page = \"".$templates->get('newpoints_shop_do_action')."\";");
				output_page($page);
			break;

			case 'do_send':
				$plugins->run_hooks("newpoints_shop_do_send_start");

				// check if the item exists
				if (!($item = newpoints_shop_get_item($mybb->input['iid'])))
				{
					error($lang->newpoints_shop_invalid_item);
				}

				// check if the item is assigned to category
				if (!($cat = newpoints_shop_get_category($item['cid'])))
				{
					error($lang->newpoints_shop_invalid_cat);
				}

				// check if we have permissions to view the parent category
				if (!newpoints_shop_check_permissions($cat['usergroups']))
				{
					error_no_permission();
				}

				if ($item['visible'] == 0 || $cat['visible'] == 0)
					error_no_permission();

				$myitems = @unserialize($mybb->user['newpoints_items']);
				if (!$myitems)
					error($lang->newpoints_shop_inventory_empty);

				// make sure we own the item
				$key = array_search($item['iid'], $myitems);
				if ($key === false)
					error($lang->newpoints_shop_selected_item_not_owned);

				$username = trim($mybb->input['username']);
				if (!($user = newpoints_getuser_byname($username)))
				{
					error($lang->newpoints_shop_invalid_user);
				}
				else
				{
					if ($user['uid'] == $mybb->user['uid'])
					{
						error($lang->newpoints_shop_cant_send_item_self);
					}

					// send item to the selected user
					$useritems = @unserialize($user['newpoints_items']);
					if (!$useritems)
						$useritems = array();
					$useritems[] = $item['iid'];
					$db->update_query('users', array('newpoints_items' => serialize($useritems)), 'uid=\''.$user['uid'].'\'');

					// remove item from our inventory
					unset($myitems[$key]);
					sort($myitems);
					$db->update_query('users', array('newpoints_items' => serialize($myitems)), 'uid=\''.$mybb->user['uid'].'\'');

					$plugins->run_hooks("newpoints_shop_do_send_end");

					// send pm to user
					newpoints_send_pm(array('subject' => $lang->newpoints_shop_item_received_title, 'message' => $lang->sprintf($lang->newpoints_shop_item_received, htmlspecialchars_uni($mybb->user['username']), htmlspecialchars_uni($item['name'])), 'touid' => $user['uid'], 'receivepms' => 1), -1);

					// log
					newpoints_log('shop_send', $lang->sprintf($lang->newpoints_shop_sent_log, $item['iid'], $user['uid'], $user['username']));

					redirect($mybb->settings['bburl']."/newpoints.php?action=shop&amp;shop_action=myitems", $lang->newpoints_shop_item_sent, $lang->newpoints_shop_item_sent_title);
				}
			break;

			case 'sell':
				$plugins->run_hooks("newpoints_shop_sell_start");

				// check if the item exists
				if (!($item = newpoints_shop_get_item($mybb->input['iid'])))
				{
					error($lang->newpoints_shop_invalid_item);
				}

				// check if the item is assigned to category
				if (!($cat = newpoints_shop_get_category($item['cid'])))
				{
					error($lang->newpoints_shop_invalid_cat);
				}

				// check if we have permissions to view the parent category
				if (!newpoints_shop_check_permissions($cat['usergroups']))
				{
					error_no_permission();
				}

				if ($item['visible'] == 0 || $cat['visible'] == 0)
					error_no_permission();

				$myitems = @unserialize($mybb->user['newpoints_items']);
				if (!$myitems)
					error($lang->newpoints_shop_inventory_empty);

				// make sure we own the item
				$key = array_search($item['iid'], $myitems);
				if ($key === false)
					error($lang->newpoints_shop_selected_item_not_owned);

				$lang->newpoints_shop_action = $lang->newpoints_shop_sell_item;
				$item['name'] = htmlspecialchars_uni($item['name']);

				global $shop_action, $data, $colspan;
				$colspan = 1;
				$shop_action = 'do_sell';
				$fields = '<input type="hidden" name="iid" value="'.$item['iid'].'">';
				$data = "<td class=\"trow1\" width=\"100%\">".$lang->sprintf($lang->newpoints_shop_sell_item_confirm, htmlspecialchars_uni($item['name']), newpoints_format_points(floatval($item['price'])*$mybb->settings['newpoints_shop_percent']))."</td>";

				$plugins->run_hooks("newpoints_shop_sell_end");

				eval("\$page = \"".$templates->get('newpoints_shop_do_action')."\";");
				output_page($page);
			break;

			case 'do_sell':
				$plugins->run_hooks("newpoints_shop_do_sell_start");

				// check if the item exists
				if (!($item = newpoints_shop_get_item($mybb->input['iid'])))
				{
					error($lang->newpoints_shop_invalid_item);
				}

				// check if the item is assigned to category
				if (!($cat = newpoints_shop_get_category($item['cid'])))
				{
					error($lang->newpoints_shop_invalid_cat);
				}

				// check if we have permissions to view the parent category
				if (!newpoints_shop_check_permissions($cat['usergroups']))
				{
					error_no_permission();
				}

				if ($item['visible'] == 0 || $cat['visible'] == 0)
					error_no_permission();

				$myitems = @unserialize($mybb->user['newpoints_items']);
				if (!$myitems)
					error($lang->newpoints_shop_inventory_empty);

				// make sure we own the item
				$key = array_search($item['iid'], $myitems);
				if ($key === false)
					error($lang->newpoints_shop_selected_item_not_owned);

				// remove item from our inventory
				unset($myitems[$key]);
				sort($myitems);
				$db->update_query('users', array('newpoints_items' => serialize($myitems)), 'uid=\''.$mybb->user['uid'].'\'');

				// update stock
				if ($item['infinite'] != 1)
					$db->update_query('newpoints_shop_items', array('stock' => $item['stock']+1), 'iid=\''.$item['iid'].'\'');

				newpoints_addpoints($mybb->user['uid'], floatval($item['price'])*$mybb->settings['newpoints_shop_percent']);

				$plugins->run_hooks("newpoints_shop_do_sell_end");

				// log
				newpoints_log('shop_sell', $lang->sprintf($lang->newpoints_shop_sell_log, $item['iid'], floatval($item['price'])*$mybb->settings['newpoints_shop_percent']));

				redirect($mybb->settings['bburl']."/newpoints.php?action=shop&amp;shop_action=myitems", $lang->newpoints_shop_item_sell, $lang->newpoints_shop_item_sell_title);
			break;

			default:
				error_no_permission();
		}

		$plugins->run_hooks("newpoints_do_shop_end");
	}

	// shop page
	if ($mybb->input['action'] == "shop")
	{
		$plugins->run_hooks("newpoints_shop_start");

		if ($mybb->input['shop_action'] == 'view')
		{
			// check if the item exists
			if (!($item = newpoints_shop_get_item($mybb->input['iid'])))
			{
				error($lang->newpoints_shop_invalid_item);
			}

			// check if the item is assigned to category
			if (!($cat = newpoints_shop_get_category($item['cid'])))
			{
				error($lang->newpoints_shop_invalid_cat);
			}

			// check if we have permissions to view the parent category
			if (!newpoints_shop_check_permissions($cat['usergroups']))
			{
				error_no_permission();
			}

			if ($item['visible'] == 0 || $cat['visible'] == 0)
				error_no_permission();

			$item['name'] = htmlspecialchars_uni($item['name']);
			$item['description'] = htmlspecialchars_uni($item['description']);

			// check group rules - primary group check
			$grouprules = newpoints_getrules('group', $mybb->user['usergroup']);
			if (!$grouprules)
				$grouprules['items_rate'] = 1.0; // no rule set so default income rate is 1

			// if the group items rate is 0, the price of the item is 0
			if (floatval($grouprules['items_rate']) == 0)
				$item['price'] = 0;
			else
				$item['price'] = $item['price']*floatval($grouprules['items_rate']);

			$item['price'] = newpoints_format_points($item['price']);
			if ($item['price'] > $mybb->user['newpoints'])
				$item['price'] = '<span style="color: #FF0000;">'.$item['price'].'</span>';

			// build icon
			if ($item['icon'] != '')
			{
				$item['icon'] = htmlspecialchars_uni($item['icon']);
				$item['icon'] = '<img src="'.$mybb->settings['bburl'].'/'.$item['icon'].'">';
			}
			else
				$item['icon'] = '<img src="'.$mybb->settings['bburl'].'/images/newpoints/default.png">';

			if ($item['infinite'] == 1)
				$item['stock'] = $lang->newpoints_shop_infinite;
			else
				$item['stock'] = intval($item['stock']);

			if ($item['sendable'] == 1)
				$item['sendable'] = $lang->newpoints_shop_yes;
			else
				$item['sendable'] = $lang->newpoints_shop_no;

			if ($item['sellable'] == 1)
				$item['sellable'] = $lang->newpoints_shop_yes;
			else
				$item['sellable'] = $lang->newpoints_shop_no;

			eval("\$page = \"".$templates->get('newpoints_shop_view_item')."\";");
		}
		elseif ($mybb->input['shop_action'] == 'myitems')
		{
			$uid = intval($mybb->input['uid']);
			$uidpart = '';
			if ($uid > 0)
			{
				$user = get_user($uid);
				// we're viewing someone else's inventory
				if (!empty($user))
				{
					// we can't view others inventories if we don't have enough previleges
					if ($mybb->settings['newpoints_shop_viewothers'] != 1 && $mybb->usergroup['cancp'] != 1 && $mybb->user['uid'] != $uid)
						error_no_permission();

					$myitems = @unserialize($user['newpoints_items']);
					$lang->newpoints_shop_myitems = $lang->sprintf($lang->newpoints_shop_items_username, htmlspecialchars_uni($user['username']));
					$uidpart = "&amp;uid=".$uid; // we need this for pagination
				}
				else
					$myitems = @unserialize($mybb->user['newpoints_items']);
			}
			else
				$myitems = @unserialize($mybb->user['newpoints_items']);
			$items = '';
			$newrow = true;
			$invert_bgcolor = alt_trow();

			if ($mybb->settings['newpoints_shop_sendable'] != 1)
				$sendable = false;
			else
				$sendable = true;

			if ($mybb->settings['newpoints_shop_sellable'] != 1)
				$sellable = false;
			else
				$sellable = true;

			require_once MYBB_ROOT."inc/class_parser.php";
			$parser = new postParser;

			$parser_options = array(
				'allow_mycode' => 1,
				'allow_smilies' => 1,
				'allow_imgcode' => 0,
				'allow_html' => 0,
				'filter_badwords' => 1
			);

			if (!empty($myitems))
			{
				// pagination
				$per_page = 10;
				$mybb->input['page'] = intval($mybb->input['page']);
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

				// total items
				$total_rows = $db->fetch_field($db->simple_select("newpoints_shop_items", "COUNT(iid) as items", 'visible=1 AND iid IN ('.implode(',', array_unique($myitems)).')'), "items");

				// multi-page
				if ($total_rows > $per_page)
					$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/newpoints.php?action=shop&shop_action=myitems".$uidpart);

				$query = $db->simple_select('newpoints_shop_items', '*', 'visible=1 AND iid IN ('.implode(',', array_unique($myitems)).')', array('limit' => "{$start}, {$per_page}"));
				while ($item = $db->fetch_array($query))
				{
					if ($newrow === true)
					{
						$trstart = '<tr>';
						$trend = '';
						$newrow = false;
					}
					elseif ($newrow === false)
					{
						$trstart = '';
						$trend = '</tr>';
						$newrow = true;
					}

					if ($sellable === true && $item['sellable'])
					{
						if ($sendable === true && $item['sendable'])
							$tdstart = '<td width="50%">';
						else
							$tdstart = '<td width="100%">';

						$sell = $tdstart.'<form action="newpoints.php" method="POST"><input type="hidden" name="action" value="do_shop"><input type="hidden" name="shop_action" value="sell"><input type="hidden" name="iid" value="'.$item['iid'].'"><input type="hidden" name="postcode" value="'.$mybb->post_code.'"><input type="submit" name="submit" value="'.$lang->newpoints_shop_sell.'"></form></td>';
					}
					else
						$sell = '';

					if ($sendable === true && $item['sendable'])
					{
						if ($sell == '')
							$tdstart = '<td width="100%">';
						else
							$tdstart = '<td width="50%">';

						$send = $tdstart.'<form action="newpoints.php" method="POST"><input type="hidden" name="action" value="do_shop"><input type="hidden" name="shop_action" value="send"><input type="hidden" name="iid" value="'.$item['iid'].'"><input type="hidden" name="postcode" value="'.$mybb->post_code.'"><input type="submit" name="submit" value="'.$lang->newpoints_shop_send.'"></form></td>';
					}
					else
						$send = '';

					if (!$send && !$sell)
						$send = $lang->newpoints_shop_no_options;

					$item['description'] = $parser->parse_message($item['description'], $parser_options);

					// check group rules - primary group check
					$grouprules = newpoints_getrules('group', $mybb->user['usergroup']);
					if (!$grouprules)
						$grouprules['items_rate'] = 1.0; // no rule set so default income rate is 1

					// if the group items rate is 0, the price of the item is 0
					if (floatval($grouprules['items_rate']) == 0)
						$item['price'] = 0;
					else
						$item['price'] = $item['price']*floatval($grouprules['items_rate']);

					$item['price'] = newpoints_format_points($item['price']);
					$item['quantity'] = count(array_keys($myitems, $item['iid']));

					// build icon
					if ($item['icon'] != '')
					{
						$item['icon'] = htmlspecialchars_uni($item['icon']);
						$item['icon'] = '<img src="'.$mybb->settings['bburl'].'/'.$item['icon'].'" style="width: 24px; height: 24px">';
					}
					else
						$item['icon'] = '<img src="'.$mybb->settings['bburl'].'/images/newpoints/default.png">';

					$bgcolor = alt_trow();
					$invert_bgcolor = alt_trow();
                    $plugins->run_hooks("newpoints_shop_myitems_item", $item);
					eval("\$items .= \"".$trstart.$templates->get('newpoints_shop_myitems_item').$trend."\";");
				}

				if (!$items)
				{
					eval("\$items = \"".$templates->get('newpoints_shop_myitems_no_items')."\";");
				}
				else {
					if ($newrow === false) // we haven't closed the row, that means there's a missing td
					{
						eval("\$items .= \"".$templates->get('newpoints_shop_myitems_item_empty')."</tr>"."\";");
						$newrow = true;
					}
				}
			}
			else {
				eval("\$items = \"".$templates->get('newpoints_shop_myitems_no_items')."\";");
			}

			eval("\$page = \"".$templates->get('newpoints_shop_myitems')."\";");
		}
		else {

			// check group rules - primary group check
			$grouprules = newpoints_getrules('group', $mybb->user['usergroup']);
			if (!$grouprules)
				$grouprules['items_rate'] = 1.0; // no rule set so default income rate is 1

			// if the group items rate is 0, the price of the item is 0
			$itemsrate = floatval($grouprules['items_rate']);

			global $cats, $items;

			// get categories
			$query = $db->simple_select('newpoints_shop_categories', '*', '', array('order_by' => 'disporder', 'order_dir' => 'ASC'));
			while ($cat = $db->fetch_array($query))
			{
				$categories[$cat['cid']] = $cat;
			}

			// get items and store them in their categories
			$query = $db->simple_select('newpoints_shop_items', '*', 'visible=1 AND cid>0', array('order_by' => 'disporder', 'order_dir' => 'ASC'));
			while ($item = $db->fetch_array($query))
			{
				$items_array[$item['cid']][$item['iid']] = $item;
			}

			$cats = '';
			$bgcolor = '';
			$bgcolor = alt_trow();

			// build items and categories
			if (!empty($categories))
			{
				foreach ($categories as $cid => $category)
				{
					$items = '';

					if ($category['items'] > 0 && !empty($items_array[$category['cid']]))
					{
						foreach ($items_array as $cid => $member)
						{
							if ($cid != $category['cid'])
								continue;

							$bgcolor = alt_trow();
							foreach ($member as $iid => $item)
							{
								// skip hidden items
								if ($item['visible'] == 0)
									continue;

								if ($item['infinite'] == 1)
									$item['stock'] = $lang->newpoints_shop_infinite;

								if ($item['price'] > $mybb->user['newpoints'])
									$enough_money = false;
								else
									$enough_money = true;

								$item['name'] = htmlspecialchars_uni($item['name']);
								$item['description'] = htmlspecialchars_uni($item['description']);
								$item['price'] = newpoints_format_points($item['price']*$itemsrate);

								// build icon
								if ($item['icon'] != '')
								{
									$item['icon'] = htmlspecialchars_uni($item['icon']);
									$item['icon'] = '<img src="'.$mybb->settings['bburl'].'/'.$item['icon'].'" style="width: 24px; height: 24px">';
								}
								else
									$item['icon'] = '<img src="'.$mybb->settings['bburl'].'/images/newpoints/default.png">';

								if (!$enough_money)
									$item['price'] = '<span style="color: #FF0000;">'.$item['price'].'</span>';

								$plugins->run_hooks("newpoints_shop_item", $item);

								eval("\$items .= \"".$templates->get('newpoints_shop_item')."\";");
							}
						}
					}
					else
						eval("\$items = \"".$templates->get('newpoints_shop_no_items')."\";");

					// if it's not visible, don't show it
					if ($category['visible'] == 0)
						continue;

					// check if we have permissions to view the category
					if (!newpoints_shop_check_permissions($category['usergroups']))
						continue;

					// Expanded by default feature
					global $extdisplay, $expcolimage, $expdisplay, $expaltext, $icon;

					$expdisplay = '';
					if(intval($category['expanded']) == 0)
					{
						$expcolimage = "collapse_collapsed.png";
						$expdisplay = "display: none;";
						$expaltext = "[+]";
					}
					else
					{
						$expcolimage = "collapse.png";
						$expaltext = "[-]";
					}

					// build icon
					if ($category['icon'] != '')
					{
						$category['icon'] = htmlspecialchars_uni($category['icon']);
						$category['icon'] = '<img src="'.$mybb->settings['bburl'].'/'.$category['icon'].'" style="vertical-align:middle; width: 24px; height: 24px">';
					}

					// sanitize html
					$category['description'] = htmlspecialchars_uni($category['description']);
					$category['name'] = htmlspecialchars_uni($category['name']);

					$plugins->run_hooks("newpoints_shop_category", $category);

					eval("\$cats .= \"".$templates->get('newpoints_shop_category')."\";");
				}
			}
			else {
				eval("\$cats = \"".$templates->get('newpoints_shop_no_cats')."\";");
			}

			eval("\$page = \"".$templates->get('newpoints_shop')."\";");
		}

		$plugins->run_hooks("newpoints_shop_end", $page);

		// output page
		output_page($page);
	}
}

function newpoints_shop_profile_lang()
{
	global $lang;

	// load language
	newpoints_lang_load("newpoints_shop");
}

function newpoints_shop_profile()
{
	global $mybb, $lang, $db, $memprofile, $templates, $newpoints_shop_profile;

	if ($mybb->settings['newpoints_shop_itemsprofile'] == 0)
	{
		$newpoints_shop_profile = '';
		return;
	}

	global $shop_items;

	$shop_items = '';
	if (empty($memprofile['newpoints_items']))
		$shop_items = $lang->newpoints_shop_user_no_items;
	else{
		$items = unserialize($memprofile['newpoints_items']);
		if (!empty($items))
		{
			// do not show multiple icons of the same item if we own more than one
			$query = $db->simple_select('newpoints_shop_items', 'iid,name,icon', 'visible=1 AND iid IN ('.implode(',', array_unique($items)).')', array('limit' => intval($mybb->settings['newpoints_shop_itemsprofile'])));
			while ($item = $db->fetch_array($query))
			{
				if ($item['icon'] != '')
					$shop_items .= '<a href="'.$mybb->settings['bburl'].'/newpoints.php?action=shop&amp;shop_action=view&amp;iid='.$item['iid'].'"><img src="'.$mybb->settings['bburl'].'/'.$item['icon'].'" title="'.htmlspecialchars_uni($item['name']).'" style="width: 24px; height: 24px"></a> ';
				else
					$shop_items .= '<a href="'.$mybb->settings['bburl'].'/newpoints.php?action=shop&amp;shop_action=view&amp;iid='.$item['iid'].'"><img src="'.$mybb->settings['bburl'].'/images/newpoints/default.png" title="'.htmlspecialchars_uni($item['name']).'"></a> ';
			}
		}
		else
			$shop_items = $lang->newpoints_shop_user_no_items;
	}

	eval("\$newpoints_shop_profile = \"".$templates->get('newpoints_shop_profile')."\";");
}

function newpoints_shop_postbit(&$post)
{
	global $mybb, $lang, $db, $templates;

	$post['newpoints_shop_items'] = '';

	if ($mybb->settings['newpoints_shop_itemspostbit'] == 0)
	{
		return;
	}

	if (empty($post['newpoints_items']))
	{
		return;
	}

	$items = unserialize($post['newpoints_items']);
	if (empty($items))
	{
		return;
	}

	// load language
	newpoints_lang_load("newpoints_shop");

	static $postbit_items_cache; // we need to cache all items' icons and names to use less queries

	if(!isset($postbit_items_cache) || !is_array($postbit_items_cache))
	{
		$postbit_items_cache = array();
		$query = $db->simple_select('newpoints_shop_items', 'iid,name,icon', 'visible=1');
		while ($item = $db->fetch_array($query))
		{
			$postbit_items_cache[$item['iid']] = array('name' => $item['name'], 'icon' => $item['icon']);
		}
	}

	if (empty($postbit_items_cache))
		return;

	$shop_items = '';
	$count = 1;

	$items = array_unique($items);

	foreach ($postbit_items_cache as $iid => $item)
	{
		if (!in_array($iid, $items))
			continue;

		if ($item['icon'] != '')
			$shop_items .= '<a href="'.$mybb->settings['bburl'].'/newpoints.php?action=shop&amp;shop_action=view&amp;iid='.$iid.'"><img src="'.$mybb->settings['bburl'].'/'.$item['icon'].'" title="'.htmlspecialchars_uni($item['name']).'" style="width: 24px; height: 24px"></a> ';
		else
			$shop_items .= '<a href="'.$mybb->settings['bburl'].'/newpoints.php?action=shop&amp;shop_action=view&amp;iid='.$iid.'"><img src="'.$mybb->settings['bburl'].'/images/newpoints/default.png" title="'.htmlspecialchars_uni($item['name']).'"></a> ';

		$count++;
		if ($count > (int)$mybb->settings['newpoints_shop_itemspostbit'])
			break;
	}

	eval("\$post['newpoints_shop_items'] = \"".$templates->get('newpoints_shop_postbit')."\";");
	if ($shop_items != '')
		$post['newpoints_shop_items_count'] = count($items);
	else
		$post['newpoints_shop_items_count'] = "0";
}

function newpoints_shop_stats()
{
	global $mybb, $db, $templates, $cache, $theme, $newpoints_shop_lastpurchases, $last_purchases, $lang;

	// load language
	newpoints_lang_load("newpoints_shop");
	$last_purchases = '';

	// build stats table
	$query = $db->simple_select('newpoints_log', '*', 'action=\'shop_purchase\'', array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => intval($mybb->settings['newpoints_shop_lastpurchases'])));
	while($purchase = $db->fetch_array($query)) {
		$bgcolor = alt_trow();
		$data = explode('-', $purchase['data']);

		$item = newpoints_shop_get_item($data[0]);
		$purchase['item'] = htmlspecialchars_uni($item['name']);

		$link = build_profile_link(htmlspecialchars_uni($purchase['username']), intval($purchase['uid']));
		$purchase['user'] = $link;

		$purchase['date'] = my_date($mybb->settings['dateformat'], intval($purchase['date']), '', false);

		eval("\$last_purchases .= \"".$templates->get('newpoints_shop_stats_purchase')."\";");
	}

	if (!$last_purchases)
		eval("\$last_purchases = \"".$templates->get('newpoints_shop_stats_nopurchase')."\";");

	eval("\$newpoints_shop_lastpurchases = \"".$templates->get('newpoints_shop_stats')."\";");
}

function newpoints_shop_backup(&$backup_fields)
{
	global $db, $table;
	$backup_fields[] = 'newpoints_items';
}

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function newpoints_shop_admin_newpoints_menu(&$sub_menu)
{
	global $lang;

	newpoints_lang_load('newpoints_shop');
	$sub_menu[] = array('id' => 'shop', 'title' => $lang->newpoints_shop, 'link' => 'index.php?module=newpoints-shop');
}

function newpoints_shop_admin_newpoints_action_handler(&$actions)
{
	$actions['shop'] = array('active' => 'shop', 'file' => 'newpoints_shop');
}

function newpoints_shop_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	newpoints_lang_load('newpoints_shop');
	$admin_permissions['shop'] = $lang->newpoints_shop_canmanage;

}

function newpoints_shop_messageredirect($message, $error=0, $action='')
{
  	global $db, $mybb, $lang;

	if (!$message)
		return;

	if ($action)
		$parameters = '&amp;action='.$action;

	if ($error)
	{
		flash_message($message, 'error');
		admin_redirect("index.php?module=newpoints-shop".$parameters);
	}
	else {
		flash_message($message, 'success');
		admin_redirect("index.php?module=newpoints-shop".$parameters);
	}
}

function newpoints_shop_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	newpoints_lang_load('newpoints_shop');

	if($run_module == 'newpoints' && $action_file == 'newpoints_shop')
	{
		if ($mybb->request_method == "post")
		{
			switch ($mybb->input['action'])
			{
				case 'do_addcat':
					if ($mybb->input['name'] == '')
					{
						newpoints_shop_messageredirect($lang->newpoints_shop_missing_field, 1);
					}

					$name = $db->escape_string($mybb->input['name']);
					$description = $db->escape_string($mybb->input['description']);

					// get visible to user groups options
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
					$visible = intval($mybb->input['visible']);
					$icon = '';

					if(isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != '')
					{
						$icon = basename($_FILES['icon']['name']);
						if ($icon)
							$icon = "icon_".TIME_NOW."_".md5(uniqid(rand(), true)).".".get_extension($icon);

						// Already exists?
						if(file_exists(MYBB_ROOT."uploads/shop/".$icon))
						{
							flash_message($lang->mydownloads_background_upload_error2, 'error');
							admin_redirect("index.php?module=newpoints-newpoints_shop");
						}

						if(!move_uploaded_file($_FILES['icon']['tmp_name'], MYBB_ROOT."uploads/shop/".$icon))
						{
							flash_message($lang->mydownloads_background_upload_error, 'error');
							admin_redirect("index.php?module=newpoints-newpoints_shop");
						}

						$icon = $db->escape_string('uploads/shop/'.$icon);
					}

					$disporder = intval($mybb->input['disporder']);
					$expanded = intval($mybb->input['expanded']);

					$insert_query = array('name' => $name, 'description' => $description, 'usergroups' => $usergroups, 'visible' => $visible, 'disporder' => $disporder, 'icon' => $icon, 'expanded' => $expanded);
					$db->insert_query('newpoints_shop_categories', $insert_query);

					newpoints_shop_messageredirect($lang->newpoints_shop_cat_added);
				break;
				case 'do_editcat':
					$cid = intval($mybb->input['cid']);
					if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('newpoints_shop_categories', '*', "cid = $cid")))))
					{
						newpoints_shop_messageredirect($lang->newpoints_shop_invalid_cat, 1);
					}

					if ($mybb->input['name'] == '')
					{
						newpoints_shop_messageredirect($lang->newpoints_shop_missing_field, 1);
					}

					$name = $db->escape_string($mybb->input['name']);
					$description = $db->escape_string($mybb->input['description']);
					// get visible to user groups options
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
					$visible = intval($mybb->input['visible']);

					$icon = '';

					if(isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != '')
					{
						$icon = basename($_FILES['icon']['name']);
						if ($icon)
							$icon = "icon_".TIME_NOW."_".md5(uniqid(rand(), true)).".".get_extension($icon);

						// Already exists?
						if(file_exists(MYBB_ROOT."uploads/shop/".$icon))
						{
							flash_message($lang->mydownloads_background_upload_error2, 'error');
							admin_redirect("index.php?module=newpoints-newpoints_shop");
						}

						if(!move_uploaded_file($_FILES['icon']['tmp_name'], MYBB_ROOT."uploads/shop/".$icon))
						{
							flash_message($lang->mydownloads_background_upload_error, 'error');
							admin_redirect("index.php?module=newpoints-newpoints_shop");
						}

						$icon = $db->escape_string('uploads/shop/'.$icon);
					}

					$disporder = intval($mybb->input['disporder']);
					$expanded = intval($mybb->input['expanded']);

					$update_query = array('name' => $name, 'description' => $description, 'usergroups' => $usergroups, 'visible' => $visible, 'disporder' => $disporder, 'icon' => $icon, 'expanded' => $expanded);
					$db->update_query('newpoints_shop_categories', $update_query, 'cid=\''.$cid.'\'');

					newpoints_shop_messageredirect($lang->newpoints_shop_cat_edited);
				break;

				case 'do_additem':
					if ($mybb->input['name'] == '' || $mybb->input['cid'] == '')
					{
						newpoints_shop_messageredirect($lang->newpoints_shop_missing_field, 1);
					}

					$name = $db->escape_string($mybb->input['name']);
					$description = $db->escape_string($mybb->input['description']);

					$icon = '';

					if(isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != '')
					{
						$icon = basename($_FILES['icon']['name']);
						if ($icon)
							$icon = "icon_".TIME_NOW."_".md5(uniqid(rand(), true)).".".get_extension($icon);

						// Already exists?
						if(file_exists(MYBB_ROOT."uploads/shop/".$icon))
						{
							flash_message($lang->mydownloads_background_upload_error2, 'error');
							admin_redirect("index.php?module=newpoints-newpoints_shop");
						}

						if(!move_uploaded_file($_FILES['icon']['tmp_name'], MYBB_ROOT."uploads/shop/".$icon))
						{
							flash_message($lang->mydownloads_background_upload_error, 'error');
							admin_redirect("index.php?module=newpoints-newpoints_shop");
						}

						$icon = $db->escape_string('uploads/shop/'.$icon);
					}

					$pm = $db->escape_string($mybb->input['pm']);
					$pmadmin = $db->escape_string($mybb->input['pmadmin']);
					$price = floatval($mybb->input['price']);

					$infinite = intval($mybb->input['infinite']);
					if ($infinite == 1)
						$stock = 0;
					else
						$stock = intval($mybb->input['stock']);

					$limit = intval($mybb->input['limit']);

					$visible = intval($mybb->input['visible']);
					$disporder = intval($mybb->input['disporder']);
					$sendable = intval($mybb->input['sendable']);
					$sellable = intval($mybb->input['sellable']);

					$cid = intval($mybb->input['cid']);
					if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('newpoints_shop_categories', '*', "cid = $cid")))))
					{
						newpoints_shop_messageredirect($lang->newpoints_shop_invalid_cat, 1);
					}

					$insert_array = array('name' => $name, 'description' => $description, 'icon' => $icon, 'visible' => $visible, 'disporder' => $disporder, 'price' => $price, 'infinite' => $infinite, 'stock' => $stock, 'limit' => $limit, 'sendable' => $sendable, 'sellable' => $sellable, 'cid' => $cid, 'pm' => $pm, 'pmadmin' => $pmadmin);

					$plugins->run_hooks("newpoints_shop_commit", $insert_array);

					$db->insert_query('newpoints_shop_items', $insert_array);

					$db->write_query('UPDATE '.TABLE_PREFIX.'newpoints_shop_categories SET items = items+1 WHERE cid=\''.$cid.'\'');

					newpoints_shop_messageredirect($lang->newpoints_shop_item_added, 0, "items&amp;cid=".$cid);
				break;
				case 'do_edititem':
					$iid = intval($mybb->input['iid']);
					if ($iid <= 0 || (!($item = $db->fetch_array($db->simple_select('newpoints_shop_items', '*', "iid = $iid")))))
					{
						newpoints_shop_messageredirect($lang->newpoints_shop_invalid_item, 1, 'items');
					}

					if ($mybb->input['name'] == '' || $mybb->input['cid'] == '')
					{
						newpoints_shop_messageredirect($lang->newpoints_shop_missing_field, 1);
					}

					$name = $db->escape_string($mybb->input['name']);
					$description = $db->escape_string($mybb->input['description']);

					$icon = '';

					if(isset($_FILES['icon']['name']) && $_FILES['icon']['name'] != '')
					{
						$icon = basename($_FILES['icon']['name']);
						if ($icon)
							$icon = "icon_".TIME_NOW."_".md5(uniqid(rand(), true)).".".get_extension($icon);

						// Already exists?
						if(file_exists(MYBB_ROOT."uploads/shop/".$icon))
						{
							flash_message($lang->mydownloads_background_upload_error2, 'error');
							admin_redirect("index.php?module=newpoints-newpoints_shop");
						}

						if(!move_uploaded_file($_FILES['icon']['tmp_name'], MYBB_ROOT."uploads/shop/".$icon))
						{
							flash_message($lang->mydownloads_background_upload_error, 'error');
							admin_redirect("index.php?module=newpoints-newpoints_shop");
						}

						$icon = $db->escape_string('uploads/shop/'.$icon);
					}

					$price = floatval($mybb->input['price']);
					$pm = $db->escape_string($mybb->input['pm']);
					$pmadmin = $db->escape_string($mybb->input['pmadmin']);

					$infinite = intval($mybb->input['infinite']);
					if ($infinite == 1)
						$stock = 0;
					else
						$stock = intval($mybb->input['stock']);

					$limit = intval($mybb->input['limit']);

					$visible = intval($mybb->input['visible']);
					$disporder = intval($mybb->input['disporder']);
					$sendable = intval($mybb->input['sendable']);
					$sellable = intval($mybb->input['sellable']);

					$cid = intval($mybb->input['cid']);
					if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('newpoints_shop_categories', '*', "cid = $cid")))))
					{
						newpoints_shop_messageredirect($lang->newpoints_shop_invalid_cat, 1);
					}

					$update_array = array('name' => $name, 'description' => $description, 'icon' => ($icon != '' ? $icon : $db->escape_string($item['icon'])), 'visible' => $visible, 'disporder' => $disporder, 'price' => $price, 'infinite' => $infinite, 'stock' => $stock, 'limit' => $limit, 'sendable' => $sendable, 'sellable' => $sellable, 'cid' => $cid, 'pm' => $pm, 'pmadmin' => $pmadmin);

					$plugins->run_hooks("newpoints_shop_commit", $update_array);

					$db->update_query('newpoints_shop_items', $update_array, 'iid=\''.$iid.'\'');

					if ($cid != $item['cid'])
					{
						$db->write_query('UPDATE '.TABLE_PREFIX.'newpoints_shop_categories SET items = items-1 WHERE cid=\''.$item['cid'].'\'');
						$db->write_query('UPDATE '.TABLE_PREFIX.'newpoints_shop_categories SET items = items+1 WHERE cid=\''.$cid.'\'');
					}

					newpoints_shop_messageredirect($lang->newpoints_shop_item_edited, 0, "items&amp;cid=".$cid);
				break;
			}
		}

		if ($mybb->input['action'] == 'do_deletecat')
		{
			$page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');
			$page->output_header($lang->newpoints_shop);

			$cid = intval($mybb->input['cid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=newpoints-shop");
			}

			if($mybb->request_method == "post")
			{
				if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('newpoints_shop_categories', 'cid', "cid = $cid")))))
				{
					newpoints_shop_messageredirect($lang->newpoints_shop_invalid_cat, 1);
				}

				$db->delete_query('newpoints_shop_categories', "cid = $cid");

				// unassign items from this category
				$db->update_query('newpoints_shop_items', array('cid' => 0), "cid = $cid");

				newpoints_shop_messageredirect($lang->newpoints_shop_cat_deleted);
			}
			else
			{
				$mybb->input['cid'] = intval($mybb->input['cid']);
				$form = new Form("index.php?module=newpoints-shop&amp;action=do_deletecat&amp;cid={$mybb->input['cid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->newpoints_shop_confirm_deletecat}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}
		elseif ($mybb->input['action'] == 'do_deleteitem')
		{
			$page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');
			$page->output_header($lang->newpoints_shop);

			$iid = intval($mybb->input['iid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=newpoints-shop", 0, "items&amp;cid=".$cid);
			}

			if($mybb->request_method == "post")
			{
				if ($iid <= 0 || (!($item = $db->fetch_array($db->simple_select('newpoints_shop_items', 'cid', "iid = $iid")))))
				{
					newpoints_shop_messageredirect($lang->newpoints_shop_invalid_item, 1, "items&amp;cid=".$cid);
				}

				$db->delete_query('newpoints_shop_items', "iid = $iid");

				// remove one from the items count
				$db->write_query('UPDATE '.TABLE_PREFIX.'newpoints_shop_categories SET items = items-1 WHERE cid=\''.$item['cid'].'\'');

				newpoints_shop_messageredirect($lang->newpoints_shop_item_deleted, 0, "items&amp;cid=".$cid);
			}
			else
			{
				$mybb->input['iid'] = intval($mybb->input['iid']);
				$form = new Form("index.php?module=newpoints-shop&amp;action=do_deleteitem&amp;iid={$mybb->input['iid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->newpoints_shop_confirm_deleteitem}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}
		elseif ($mybb->input['action'] == 'remove')
		{
			$page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');
			$page->output_header($lang->newpoints_shop);

			$iid = intval($mybb->input['iid']);
			$mybb->input['uid'] = intval($mybb->input['uid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=newpoints-shop", 0, "items&amp;cid=".$cid);
			}

			if($mybb->request_method == "post")
			{
				if ($iid <= 0 || (!($item = $db->fetch_array($db->simple_select('newpoints_shop_items', '*', "iid = $iid")))))
				{
					newpoints_shop_messageredirect($lang->newpoints_shop_invalid_item, 1, "items&amp;cid=".$cid);
				}

				$uid = (int)$mybb->input['uid'];
				if ($uid <= 0)
				{
					newpoints_shop_messageredirect($lang->newpoints_shop_invalid_user, 1);
				}

				$user = get_user($uid);
				// we're viewing someone else's inventory
				if (empty($user))
				{
					newpoints_shop_messageredirect($lang->newpoints_shop_invalid_user, 1);
				}

				$inventory = @unserialize($user['newpoints_items']);
				if (!$inventory)
				{
					newpoints_shop_messageredirect($lang->newpoints_shop_inventory_empty, 1);
				}

				// make sure we own the item
				$key = array_search($item['iid'], $inventory);
				if ($key === false)
				{
					newpoints_shop_messageredirect($lang->newpoints_shop_selected_item_not_owned, 1);
				}

				// remove item from our inventory
				unset($inventory[$key]);
				sort($inventory);
				$db->update_query('users', array('newpoints_items' => serialize($inventory)), 'uid=\''.$uid.'\'');

				// update stock
				if ($item['infinite'] != 1)
					$db->update_query('newpoints_shop_items', array('stock' => $item['stock']+1), 'iid=\''.$item['iid'].'\'');

				newpoints_addpoints($uid, floatval($item['price'])*$mybb->settings['newpoints_shop_percent']);

				newpoints_shop_messageredirect($lang->newpoints_shop_item_removed, 0, "inventory&amp;uid=".$uid);
			}
			else
			{
				$form = new Form("index.php?module=newpoints-shop&amp;action=remove&amp;iid={$mybb->input['iid']}&amp;uid={$mybb->input['uid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->newpoints_shop_confirm_removeitem}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}

		if (!$mybb->input['action'] || $mybb->input['action'] == 'categories' || $mybb->input['action'] == 'inventory' || $mybb->input['action'] == 'addcat' || $mybb->input['action'] == 'editcat')
		{
			$page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');

			$page->output_header($lang->newpoints_shop);

			$sub_tabs['newpoints_shop_categories'] = array(
				'title'			=> $lang->newpoints_shop_categories,
				'link'			=> 'index.php?module=newpoints-shop',
				'description'	=> $lang->newpoints_shop_categories_desc
			);

			if (!$mybb->input['action'] || $mybb->input['action'] == 'categories' || $mybb->input['action'] == 'addcat' || $mybb->input['action'] == 'editcat')
			{
				$sub_tabs['newpoints_shop_categories_add'] = array(
					'title'			=> $lang->newpoints_shop_addcat,
					'link'			=> 'index.php?module=newpoints-shop&amp;action=addcat',
					'description'	=> $lang->newpoints_shop_addcat_desc
				);
				$sub_tabs['newpoints_shop_categories_edit'] = array(
					'title'			=> $lang->newpoints_shop_editcat,
					'link'			=> 'index.php?module=newpoints-shop&amp;action=editcat',
					'description'	=> $lang->newpoints_shop_editcat_desc
				);
				$sub_tabs['newpoints_shop_categories_delete'] = array(
					'title'			=> $lang->newpoints_shop_deletecat,
					'link'			=> 'index.php?module=newpoints-shop&amp;action=do_deletecat',
					'description'	=> $lang->newpoints_shop_deletecat_desc
				);
			}
		}

		if ($mybb->input['action'] == 'inventory')
		{
			$sub_tabs['newpoints_shop_inventory'] = array(
				'title'			=> $lang->newpoints_shop_inventory,
				'link'			=> 'index.php?module=newpoints-shop&amp;action=inventory&amp;uid='.intval($mybb->input['uid']),
				'description'	=> $lang->newpoints_shop_inventory_desc
			);
		}

		if ($mybb->input['action'] == 'items' || $mybb->input['action'] == 'additem' || $mybb->input['action'] == 'edititem')
		{
			$page->add_breadcrumb_item($lang->newpoints_shop, 'index.php?module=newpoints-shop');

			$page->output_header($lang->newpoints_shop);

			$sub_tabs['newpoints_shop_categories'] = array(
				'title'			=> $lang->newpoints_shop_categories,
				'link'			=> 'index.php?module=newpoints-shop',
				'description'	=> $lang->newpoints_shop_categories_desc
			);

			$sub_tabs['newpoints_shop_items'] = array(
				'title'			=> $lang->newpoints_shop_items,
				'link'			=> 'index.php?module=newpoints-shop&amp;action=items&amp;cid='.intval($mybb->input['cid']),
				'description'	=> $lang->newpoints_shop_items_desc
			);

			if ($mybb->input['action'] == 'items' || $mybb->input['action'] == 'additem' || $mybb->input['action'] == 'edititem')
			{
				$sub_tabs['newpoints_shop_items_add'] = array(
					'title'			=> $lang->newpoints_shop_additem,
					'link'			=> 'index.php?module=newpoints-shop&amp;action=additem&amp;cid='.intval($mybb->input['cid']),
					'description'	=> $lang->newpoints_shop_additem_desc
				);
				$sub_tabs['newpoints_shop_items_edit'] = array(
					'title'			=> $lang->newpoints_shop_edititem,
					'link'			=> 'index.php?module=newpoints-shop&amp;action=edititem',
					'description'	=> $lang->newpoints_shop_edititem_desc
				);
				$sub_tabs['newpoints_shop_items_delete'] = array(
					'title'			=> $lang->newpoints_shop_deleteitem,
					'link'			=> 'index.php?module=newpoints-shop&amp;action=do_deleteitem',
					'description'	=> $lang->newpoints_shop_deleteitem_desc
				);
			}
		}

		if (!$mybb->input['action'] || $mybb->input['action'] == 'categories')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_shop_categories');

			// table
			$table = new Table;
			$table->construct_header($lang->newpoints_shop_item_icon, array('width' => '1%'));
			$table->construct_header($lang->newpoints_shop_cat_name, array('width' => '30%'));
			$table->construct_header($lang->newpoints_shop_cat_description, array('width' => '35%'));
			$table->construct_header($lang->newpoints_shop_cat_items, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_shop_cat_disporder, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_shop_cat_action, array('width' => '25%', 'class' => 'align_center'));

			$query = $db->simple_select('newpoints_shop_categories', '*', '', array('order_by' => 'disporder', 'order_dir' => 'ASC'));
			while ($cat = $db->fetch_array($query))
			{
				$table->construct_cell(htmlspecialchars_uni($cat['icon']) ? '<img src="'.$mybb->settings['bburl'].'/'.htmlspecialchars_uni($cat['icon']).'" style="width: 24px; height: 24px">' : '<img src="'.$mybb->settings['bburl'].'/images/newpoints/default.png">', array('class' => 'align_center'));
				$table->construct_cell("<a href=\"index.php?module=newpoints-shop&amp;action=items&amp;cid={$cat['cid']}\">".htmlspecialchars_uni($cat['name'])."</a>");
				$table->construct_cell(htmlspecialchars_uni($cat['description']));
				$table->construct_cell(intval($cat['items']), array('class' => 'align_center'));
				$table->construct_cell(intval($cat['disporder']), array('class' => 'align_center'));

				// actions column
				$table->construct_cell("<a href=\"index.php?module=newpoints-shop&amp;action=editcat&amp;cid=".intval($cat['cid'])."\">".$lang->newpoints_shop_edit."</a> - <a href=\"index.php?module=newpoints-shop&amp;action=do_deletecat&amp;cid=".intval($cat['cid'])."\">".$lang->newpoints_shop_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->newpoints_shop_no_cats, array('colspan' => 5));
				$table->construct_row();
			}

			$table->output($lang->newpoints_shop_categories);
		}
		elseif ($mybb->input['action'] == 'addcat')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_shop_categories_add');

			$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$options[$usergroup['gid']] = $usergroup['title'];
			}

			$form = new Form("index.php?module=newpoints-shop&amp;action=do_addcat", "post", "newpoints_shop", 1);

			$form_container = new FormContainer($lang->newpoints_shop_addcat);
			$form_container->output_row($lang->newpoints_shop_addedit_cat_name."<em>*</em>", $lang->newpoints_shop_addedit_cat_name_desc, $form->generate_text_box('name', '', array('id' => 'name')), 'name');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_description, $lang->newpoints_shop_addedit_cat_description_desc, $form->generate_text_box('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_visible, $lang->newpoints_shop_addedit_cat_visible_desc, $form->generate_yes_no_radio('visible', 1), 'visible');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_icon, $lang->newpoints_shop_addedit_cat_icon_desc, $form->generate_file_upload_box("icon", array('style' => 'width: 200px;')), 'icon');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_usergroups, $lang->newpoints_shop_addedit_cat_usergroups_desc, $form->generate_select_box('usergroups[]', $options, '', array('id' => 'usergroups', 'multiple' => true, 'size' => 5)), 'groups');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_disporder, $lang->newpoints_shop_addedit_cat_disporder_desc, $form->generate_text_box('disporder', '0', array('id' => 'disporder')), 'disporder');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_expanded, $lang->newpoints_shop_addedit_cat_expanded_desc, $form->generate_yes_no_radio('expanded', 1), 'expanded');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->newpoints_shop_submit);
			$buttons[] = $form->generate_reset_button($lang->newpoints_shop_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'editcat')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_shop_categories_edit');

			$cid = intval($mybb->input['cid']);
			if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('newpoints_shop_categories', '*', "cid = $cid")))))
			{
				newpoints_shop_messageredirect($lang->newpoints_shop_invalid_cat, 1);
			}

			$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$options[$usergroup['gid']] = $usergroup['title'];
			}

			$form = new Form("index.php?module=newpoints-shop&amp;action=do_editcat", "post", "newpoints_shop", 1);

			echo $form->generate_hidden_field('cid', $cat['cid']);

			$form_container = new FormContainer($lang->newpoints_shop_addcat);
			$form_container->output_row($lang->newpoints_shop_addedit_cat_name."<em>*</em>", $lang->newpoints_shop_addedit_cat_name_desc, $form->generate_text_box('name', htmlspecialchars_uni($cat['name']), array('id' => 'name')), 'name');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_description, $lang->newpoints_shop_addedit_cat_description_desc, $form->generate_text_box('description', htmlspecialchars_uni($cat['description']), array('id' => 'description')), 'description');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_visible, $lang->newpoints_shop_addedit_cat_visible_desc, $form->generate_yes_no_radio('visible', intval($cat['visible'])), 'visible');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_icon, $lang->newpoints_shop_addedit_cat_icon_desc, $form->generate_file_upload_box("icon", array('style' => 'width: 200px;')), 'icon');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_usergroups, $lang->newpoints_shop_addedit_cat_usergroups_desc, $form->generate_select_box('usergroups[]', $options, explode(',', $cat['usergroups']), array('id' => 'usergroups', 'multiple' => true, 'size' => 5)), 'groups');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_disporder, $lang->newpoints_shop_addedit_cat_disporder_desc, $form->generate_text_box('disporder', intval($cat['disporder']), array('id' => 'disporder')), 'disporder');
			$form_container->output_row($lang->newpoints_shop_addedit_cat_expanded, $lang->newpoints_shop_addedit_cat_expanded_desc, $form->generate_yes_no_radio('expanded', intval($cat['expanded'])), 'expanded');

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->newpoints_shop_submit);
			$buttons[] = $form->generate_reset_button($lang->newpoints_shop_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		else if ($mybb->input['action'] == 'items')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_shop_items');

			$cid = intval($mybb->input['cid']);
			if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('newpoints_shop_categories', '*', "cid = $cid")))))
			{
				newpoints_shop_messageredirect($lang->newpoints_shop_invalid_cat, 1);
			}

			// table
			$table = new Table;
			$table->construct_header($lang->newpoints_shop_item_icon, array('width' => '1%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_shop_item_name, array('width' => '30%'));
			$table->construct_header($lang->newpoints_shop_item_price, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_shop_item_disporder, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_shop_item_action, array('width' => '20%', 'class' => 'align_center'));

			$query = $db->simple_select('newpoints_shop_items', '*', 'cid=\''.$cid.'\'', array('order_by' => 'disporder', 'order_dir' => 'ASC'));

			while ($item = $db->fetch_array($query))
			{
				if ($item['infinite'] == 1)
					$item['stock'] = $lang->newpoints_shop_infinite;

				if ($item['visible'] == 0)
					$visible_info = ' (<span style="color: #FF0000;">hidden</span>)';
				else
					$visible_info = '';

				$table->construct_cell(htmlspecialchars_uni($item['icon']) ? '<img src="'.$mybb->settings['bburl'].'/'.htmlspecialchars_uni($item['icon']).'" style="width: 24px; height: 24px">' : '<img src="'.$mybb->settings['bburl'].'/images/newpoints/default.png">', array('class' => 'align_center'));
				$table->construct_cell(htmlspecialchars_uni($item['name'])." (".(intval($item['infinite']) ? $lang->newpoints_shop_infinite : intval($item['stock'])).")".$visible_info."<br /><small>".htmlspecialchars_uni($item['description'])."</small>");
				$table->construct_cell(newpoints_format_points($item['price']), array('class' => 'align_center'));
				$table->construct_cell(intval($item['disporder']), array('class' => 'align_center'));

				// actions column
				$table->construct_cell("<a href=\"index.php?module=newpoints-shop&amp;action=edititem&amp;iid=".intval($item['iid'])."\">".$lang->newpoints_shop_edit."</a> - <a href=\"index.php?module=newpoints-shop&amp;action=do_deleteitem&amp;iid=".intval($item['iid'])."\">".$lang->newpoints_shop_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->newpoints_shop_no_items, array('colspan' => 6));

				$table->construct_row();
			}

			$table->output($lang->newpoints_shop_items);
		}
		elseif ($mybb->input['action'] == 'additem')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_shop_items_add');

			$cid = intval($mybb->input['cid']);
			if ($cid > 0)
			{
				if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('newpoints_shop_categories', '*', "cid = $cid")))))
				{
					newpoints_shop_messageredirect($lang->newpoints_shop_invalid_cat, 1);
				}
			}
			else
				$cid = 0;

			$categories[0] = $lang->newpoints_shop_select_cat;

			$query = $db->simple_select('newpoints_shop_categories', '*');
			while ($cat = $db->fetch_array($query))
				$categories[$cat['cid']] = $cat['name'];

			$form = new Form("index.php?module=newpoints-shop&amp;action=do_additem", "post", "newpoints_shop", 1);

			$form_container = new FormContainer($lang->newpoints_shop_additem);
			$form_container->output_row($lang->newpoints_shop_addedit_item_name."<em>*</em>", $lang->newpoints_shop_addedit_item_name_desc, $form->generate_text_box('name', '', array('id' => 'name')), 'name');
			$form_container->output_row($lang->newpoints_shop_addedit_item_description, $lang->newpoints_shop_addedit_item_description_desc, $form->generate_text_box('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->newpoints_shop_addedit_item_price, $lang->newpoints_shop_addedit_item_price_desc, $form->generate_text_box('price', '0', array('id' => 'price')), 'price');
			$form_container->output_row($lang->newpoints_shop_addedit_item_icon, $lang->newpoints_shop_addedit_item_icon_desc, $form->generate_file_upload_box("icon", array('style' => 'width: 200px;')), 'icon');
			$form_container->output_row($lang->newpoints_shop_addedit_item_disporder, $lang->newpoints_shop_addedit_item_disporder_desc, $form->generate_text_box('disporder', '0', array('id' => 'disporder')), 'disporder');
			$form_container->output_row($lang->newpoints_shop_addedit_item_stock, $lang->newpoints_shop_addedit_item_stock_desc, $form->generate_text_box('stock', '0', array('id' => 'stock')), 'stock');
			$form_container->output_row($lang->newpoints_shop_addedit_item_infinite, $lang->newpoints_shop_addedit_item_infinite_desc, $form->generate_yes_no_radio('infinite', 1), 'infinite');
			$form_container->output_row($lang->newpoints_shop_addedit_item_limit, $lang->newpoints_shop_addedit_item_limit_desc, $form->generate_text_box('limit', '0', array('id' => 'limit')), 'limit');
			$form_container->output_row($lang->newpoints_shop_addedit_item_visible, $lang->newpoints_shop_addedit_item_visible_desc, $form->generate_yes_no_radio('visible', 1), 'visible');
			$form_container->output_row($lang->newpoints_shop_addedit_item_sendable, $lang->newpoints_shop_addedit_item_sendable_desc, $form->generate_yes_no_radio('sendable', 1), 'sendable');
			$form_container->output_row($lang->newpoints_shop_addedit_item_sellable, $lang->newpoints_shop_addedit_item_sellable_desc, $form->generate_yes_no_radio('sellable', 1), 'sellable');
			$form_container->output_row($lang->newpoints_shop_addedit_item_pm, $lang->newpoints_shop_addedit_item_pm_desc, $form->generate_text_area('pm', ''), 'pm');
			$form_container->output_row($lang->newpoints_shop_addedit_item_pmadmin, $lang->newpoints_shop_addedit_item_pmadmin_desc, $form->generate_text_area('pmadmin', ''), 'pmadmin');
			$form_container->output_row($lang->newpoints_shop_addedit_item_category."<em>*</em>", $lang->newpoints_shop_addedit_item_category_desc, $form->generate_select_box('cid', $categories, $cid, array('id' => 'cid')), 'cid');

			$args = array($form_container, $form, array());
			$plugins->run_hooks("newpoints_shop_row", $args);

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->newpoints_shop_submit);
			$buttons[] = $form->generate_reset_button($lang->newpoints_shop_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'edititem')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_shop_items_edit');

			$iid = intval($mybb->input['iid']);
			if ($iid <= 0 || (!($item = $db->fetch_array($db->simple_select('newpoints_shop_items', '*', "iid = $iid")))))
			{
				newpoints_shop_messageredirect($lang->newpoints_shop_invalid_item, 1, 'items');
			}

			$categories[0] = $lang->newpoints_shop_select_cat;

			$query = $db->simple_select('newpoints_shop_categories', '*');
			while ($cat = $db->fetch_array($query))
				$categories[$cat['cid']] = $cat['name'];

			$form = new Form("index.php?module=newpoints-shop&amp;action=do_edititem", "post", "newpoints_shop", 1);

			echo $form->generate_hidden_field('iid', $iid);

			$form_container = new FormContainer($lang->newpoints_shop_additem);
			$form_container->output_row($lang->newpoints_shop_addedit_item_name."<em>*</em>", $lang->newpoints_shop_addedit_item_name_desc, $form->generate_text_box('name', htmlspecialchars_uni($item['name']), array('id' => 'name')), 'name');
			$form_container->output_row($lang->newpoints_shop_addedit_item_description, $lang->newpoints_shop_addedit_item_description_desc, $form->generate_text_box('description', htmlspecialchars_uni($item['description']), array('id' => 'description')), 'description');
			$form_container->output_row($lang->newpoints_shop_addedit_item_price, $lang->newpoints_shop_addedit_item_price_desc, $form->generate_text_box('price', floatval($item['price']), array('id' => 'price')), 'price');
			$form_container->output_row($lang->newpoints_shop_addedit_item_icon, $lang->newpoints_shop_addedit_item_icon_desc, $form->generate_file_upload_box("icon", array('style' => 'width: 200px;')), 'icon');
			$form_container->output_row($lang->newpoints_shop_addedit_item_disporder, $lang->newpoints_shop_addedit_item_disporder_desc, $form->generate_text_box('disporder', intval($item['disporder']), array('id' => 'disporder')), 'disporder');
			$form_container->output_row($lang->newpoints_shop_addedit_item_stock, $lang->newpoints_shop_addedit_item_stock_desc, $form->generate_text_box('stock', intval($item['stock']), array('id' => 'stock')), 'stock');
			$form_container->output_row($lang->newpoints_shop_addedit_item_infinite, $lang->newpoints_shop_addedit_item_infinite_desc, $form->generate_yes_no_radio('infinite', intval($item['infinite'])), 'infinite');
			$form_container->output_row($lang->newpoints_shop_addedit_item_limit, $lang->newpoints_shop_addedit_item_limit_desc, $form->generate_text_box('limit', intval($item['limit']), array('id' => 'limit')), 'limit');
			$form_container->output_row($lang->newpoints_shop_addedit_item_visible, $lang->newpoints_shop_addedit_item_visible_desc, $form->generate_yes_no_radio('visible', intval($item['visible'])), 'visible');
			$form_container->output_row($lang->newpoints_shop_addedit_item_sendable, $lang->newpoints_shop_addedit_item_sendable_desc, $form->generate_yes_no_radio('sendable', intval($item['sendable'])), 'sendable');
			$form_container->output_row($lang->newpoints_shop_addedit_item_sellable, $lang->newpoints_shop_addedit_item_sellable_desc, $form->generate_yes_no_radio('sellable', intval($item['sellable'])), 'sellable');
			$form_container->output_row($lang->newpoints_shop_addedit_item_pm, $lang->newpoints_shop_addedit_item_pm_desc, $form->generate_text_area('pm', htmlspecialchars_uni($item['pm']), array('id' => 'pm_text')), 'pm');
			$form_container->output_row($lang->newpoints_shop_addedit_item_pmadmin, $lang->newpoints_shop_addedit_item_pmadmin_desc, $form->generate_text_area('pmadmin', htmlspecialchars_uni($item['pmadmin'])), 'pmadmin');
			$form_container->output_row($lang->newpoints_shop_addedit_item_category."<em>*</em>", $lang->newpoints_shop_addedit_item_category_desc, $form->generate_select_box('cid', $categories, intval($item['cid']), array('id' => 'cid')), 'cid');

			$args = array($form_container, $form, $item);
			$plugins->run_hooks("newpoints_shop_row", $args);

			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->newpoints_shop_submit);
			$buttons[] = $form->generate_reset_button($lang->newpoints_shop_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		else if ($mybb->input['action'] == 'inventory')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_shop_inventory');

			$uid = (int)$mybb->input['uid'];
			if ($uid <= 0)
			{
				newpoints_shop_messageredirect($lang->newpoints_shop_invalid_user, 1);
			}

			$user = get_user($uid);
			// we're viewing someone else's inventory
			if (empty($user))
			{
				newpoints_shop_messageredirect($lang->newpoints_shop_invalid_user, 1);
			}

			$inventory = @unserialize($user['newpoints_items']);
			if(!$inventory)
				$inventory = array(0); // Item id is 0 because it doesn't exist, this when we use it in the query we won't show anything

			// table
			$table = new Table;
			$table->construct_header($lang->newpoints_shop_item_icon, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_shop_item_name, array('width' => '30%'));
			$table->construct_header($lang->newpoints_shop_item_price, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_shop_item_disporder, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_shop_item_action, array('width' => '20%', 'class' => 'align_center'));

			$query = $db->simple_select('newpoints_shop_items', '*', 'iid IN ('.implode(',', array_unique($inventory)).')', array('order_by' => 'disporder', 'order_dir' => 'ASC'));
			while ($item = $db->fetch_array($query))
			{
				if ($item['infinite'] == 1)
					$item['stock'] = $lang->newpoints_shop_infinite;

				if ($item['visible'] == 0)
					$visible_info = ' (<span style="color: #FF0000;">hidden</span>)';
				else
					$visible_info = '';

				$table->construct_cell(htmlspecialchars_uni($item['icon']) ? '<img src="'.$mybb->settings['bburl'].'/'.htmlspecialchars_uni($item['icon']).'" style="width: 24px; height: 24px">' : '<img src="'.$mybb->settings['bburl'].'/images/newpoints/default.png">', array('class' => 'align_center'));
				$table->construct_cell(htmlspecialchars_uni($item['name'])." (".count(array_keys($inventory, $item['iid'])).")".$visible_info."<br /><small>".htmlspecialchars_uni($item['description'])."</small>");
				$table->construct_cell(newpoints_format_points($item['price']), array('class' => 'align_center'));
				$table->construct_cell(intval($item['disporder']), array('class' => 'align_center'));

				// actions column
				$table->construct_cell("<a href=\"index.php?module=newpoints-shop&amp;action=remove&amp;iid=".intval($item['iid'])."&amp;uid=".(int)$user['uid']."\">".$lang->newpoints_shop_remove."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->newpoints_shop_no_items, array('colspan' => 5));

				$table->construct_row();
			}

			$table->output($lang->newpoints_shop_inventory_of." ".htmlspecialchars_uni($user['username']));
		}

		$page->output_footer();
		exit;
	}
}

function newpoints_shop_admin_stats()
{
	global $form, $db, $lang, $mybb;

	newpoints_lang_load("newpoints_shop");

	echo "<br />";

	// table
	$table = new Table;
	$table->construct_header($lang->newpoints_shop_item, array('width' => '30%'));
	$table->construct_header($lang->newpoints_shop_username, array('width' => '30%'));
	$table->construct_header($lang->newpoints_shop_price, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->newpoints_shop_date, array('width' => '20%', 'class' => 'align_center'));

	$query = $db->simple_select('newpoints_log', '*', 'action=\'shop_purchase\'', array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => intval($mybb->settings['newpoints_shop_lastpurchases'])));
	while($stats = $db->fetch_array($query)) {
		$data = explode('-', $stats['data']);

		$item = newpoints_shop_get_item($data[0]);
		$table->construct_cell(htmlspecialchars_uni($item['name']));

		$link = build_profile_link(htmlspecialchars_uni($stats['username']), intval($stats['uid']));
		$table->construct_cell($link);

		$table->construct_cell(newpoints_format_points($data[1]), array('class' => 'align_center'));
		$table->construct_cell(my_date($mybb->settings['dateformat'], intval($stats['date']), '', false).", ".my_date($mybb->settings['timeformat'], intval($stats['date'])), array('class' => 'align_center'));

		$table->construct_row();
	}

	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->newpoints_error_gathering, array('colspan' => 4));
		$table->construct_row();
	}

	$table->output($lang->newpoints_stats_lastpurchases);
}

function newpoints_shop_admin_rule(&$form_container)
{
	global $mybb, $db, $lang, $form, $rule;

	if ($mybb->input['action'] == 'add')
	{
		$form_container->output_row($lang->newpoints_shop_items_rate, $lang->newpoints_shop_items_rate_desc, $form->generate_text_box('items_rate', 1, array('id' => 'items_rate')), 'items_rate');
	}
	elseif ($mybb->input['action'] == 'edit')
	{
		$form_container->output_row($lang->newpoints_shop_items_rate, $lang->newpoints_shop_items_rate_desc, $form->generate_text_box('items_rate', $rule['items_rate'], array('id' => 'items_rate')), 'items_rate');
	}
}

function newpoints_shop_admin_rule_post(&$array)
{
	global $mybb, $db, $lang, $form, $rule;

	$array['items_rate'] = floatval($mybb->input['items_rate']);
}

?>
