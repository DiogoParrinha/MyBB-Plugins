<?php
/***************************************************************************
 *
 *  Coupon Codes Generator plugin (/inc/plugins/couponcodes.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  This adds a page to your forums which generates coupon codes.
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

// do NOT remove for security reasons!
if(!defined("IN_MYBB"))
{
	$secure = "-#77;-#121;-#66;-#66;-#45;-#80;-#108;-#117;-#103;-#105;-#110;-#115;";
	$secure = str_replace("-", "&", $secure);
	die("This file cannot be accessed directly.".$secure);
}

// add hooks
$plugins->add_hook('admin_load', 'couponcodes_admin');
$plugins->add_hook('admin_tools_menu', 'couponcodes_admin_tools_menu');
$plugins->add_hook('admin_tools_action_handler', 'couponcodes_admin_tools_action_handler');
$plugins->add_hook('admin_tools_permissions', 'couponcodes_admin_permissions');

function couponcodes_info()
{
	return array(
		"name"			=> "Coupon Codes Generator",
		"description"	=> "This adds a page to your forums which generates coupon codes.",
		"website"		=> "",
		"author"		=> "Diogo Parrinha",
		"authorsite"	=> "",
		"version"		=> "1.3",
		"guid" 			=> "62ba68925812310221696843391156f3",
		"compatibility"	=> "18*"
	);
}


function couponcodes_activate()
{
	global $db, $lang;

	// create settings group
	$insertarray = array(
		'name' => 'couponcodes',
		'title' => 'Coupon Codes Generator',
		'description' => "Settings for Coupon Codes Generator plugin.",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting = array(
		"sid"			=> NULL,
		"name"			=> "couponcodes_win_chance",
		"title"			=> "Coupon Codes Chance",
		"description"	=> "Enter the percentage (without %) of chance of winning a coupon code. (values from 0 to 100)",
		"optionscode"	=> "text",
		"value"			=> '10',
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "couponcodes_pm_uid",
		"title"			=> "Admin UID",
		"description"	=> "Enter the user id of the user who receives the private message which informs about the new generated coupon coded.",
		"optionscode"	=> "text",
		"value"			=> 1,
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "couponcodes_numbers",
		"title"			=> "Number of characters",
		"description"	=> "Number of characters each coupon code has. (max is 200)",
		"optionscode"	=> "text",
		"value"			=> 10,
		"disporder"		=> 3,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "couponcodes_wins_limit",
		"title"			=> "Number of wins per user",
		"description"	=> "Enter the number of wins per user.",
		"optionscode"	=> "text",
		"value"			=> 1,
		"disporder"		=> 4,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "couponcodes_time_wait",
		"title"			=> "Waiting time",
		"description"	=> "Enter the number of seconds users must wait until they are able to generate a new coupon code. (default is 86400 / 1 day)",
		"optionscode"	=> "text",
		"value"			=> 86400,
		"disporder"		=> 5,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	$setting = array(
		"sid"			=> NULL,
		"name"			=> "couponcodes_points",
		"title"			=> "NewPoints Points",
		"description"	=> "Enter the number of points users must pay everytime they want to generate a new coupon code. (leave 0 if you don\'t want to use this, you require NewPoints installed to use it)",
		"optionscode"	=> "text",
		"value"			=> 0,
		"disporder"		=> 5,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting);

	rebuild_settings();

	$template = array(
		"tid" => "NULL",
		"title" => "couponcodes_generate",
		"template" => $db->escape_string('
<html>
<head>
<title>{$lang->couponcodes}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="{$mybb->settings[\'bburl\']}/couponcodes.php" method="POST">
	<input type="hidden" name="postcode" value="{$mybb->post_code}">
	<input type="hidden" name="action" value="generate">
	{$fields}
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td class="thead"><strong>{$lang->couponcodes}</strong></td>
		</tr>
		<tr>
			<td class="trow1" width="100%">{$lang->couponcodes_message}</td>
		</tr>
		<tr>
			<td class="tfoot" width="100%" align="center" colspan="2"><input type="submit" name="submit" value="{$lang->couponcodes_submit}"></td>
		</tr>
	</table>
</form>
{$footer}
</body>
</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."couponcodes_limits` (
	  `lid` int(10) UNSIGNED NOT NULL auto_increment,
	  `uid` bigint(30) NOT NULL DEFAULT 0,
	  `winlimit` int(5) NOT NULL DEFAULT 0,
	  `waitlimit` bigint(30) NOT NULL DEFAULT 0,
	  PRIMARY KEY  (`lid`)
		) ENGINE=MyISAM");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."couponcodes_coupons` (
	  `cid` int(10) UNSIGNED NOT NULL auto_increment,
	  `uid` bigint(30) NOT NULL DEFAULT 0,
	  `username` varchar(100) NOT NULL DEFAULT '',
	  `date` bigint(30) NOT NULL DEFAULT 0,
	  `code` varchar(200) NOT NULL DEFAULT '',
	  `used` tinyint(1) NOT NULL DEFAULT 0,
	  PRIMARY KEY  (`cid`)
		) ENGINE=MyISAM");
}


function couponcodes_deactivate()
{
	global $db, $mybb;

	// delete settings group
	$db->delete_query("settinggroups", "name = 'couponcodes'");

	// remove settings
	$db->delete_query('settings', 'name IN (\'couponcodes_win_chance\',\'couponcodes_pm_uid\',\'couponcodes_numbers\',\'couponcodes_wins_limit\',\'couponcodes_time_wait\',\'couponcodes_points\')');

	rebuild_settings();

	// delete templates
	$db->delete_query('templates', 'title IN ( \'couponcodes_generate\')');

	if ($db->table_exists('couponcodes_limits'))
		$db->drop_table('couponcodes_limits');

	if ($db->table_exists('couponcodes_coupons'))
		$db->drop_table('couponcodes_coupons');
}

/**
 * Sends a PM to a user
 *
 * @param array: The PM to be sent; should have 'subject', 'message', 'touid' and 'receivepms'
 * (receivepms is for admin override in case the user has disabled pm's)
 * @param int: from user id (0 if you want to use the uid of the person that sends it. -1 to use MyBB Engine
 * @return bool: true if PM sent
 */
function couponcodes_send_pm($pm, $fromid = 0)
{
    global $lang, $mybb, $db;
    if($mybb->settings['enablepms'] == 0) return false;
    if (!is_array($pm))    return false;
    if (!$pm['subject'] ||!$pm['message'] || !$pm['touid'] || !$pm['receivepms']) return false;

    $lang->load('messages');

    require_once MYBB_ROOT."inc/datahandlers/pm.php";

    $pmhandler = new PMDataHandler();

    $subject = $pm['subject'];
    $message = $pm['message'];
    $toid = $pm['touid'];

    require_once MYBB_ROOT."inc/datahandlers/pm.php";

    $pmhandler = new PMDataHandler();

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
        "fromid" => $fromid,
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

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function couponcodes_admin_tools_menu(&$sub_menu)
{
	global $lang;

	$lang->load('couponcodes');
	$sub_menu[] = array('id' => 'couponcodes', 'title' => $lang->couponcodes_index, 'link' => 'index.php?module=tools-couponcodes');
}

function couponcodes_admin_tools_action_handler(&$actions)
{
	$actions['couponcodes'] = array('active' => 'couponcodes', 'file' => 'couponcodes');
}

function couponcodes_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	$lang->load("couponcodes", false, true);
	$admin_permissions['couponcodes'] = $lang->couponcodes_canmanage;

}

function couponcodes_messageredirect($message, $error=0, $action='')
{
  	global $db, $mybb, $lang;

	if (!$message)
		return;

	if ($action)
		$parameters = '&amp;action='.$action;

	if ($error)
	{
		flash_message($message, 'error');
		admin_redirect("index.php?module=tools-couponcodes".$parameters);
	}
	else {
		flash_message($message, 'success');
		admin_redirect("index.php?module=tools-couponcodes".$parameters);
	}
}

function couponcodes_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	$lang->load("couponcodes", false, true);

	if($run_module == 'tools' && $action_file == 'couponcodes')
	{
		if ($mybb->input['action'] == 'delete_code')
		{

			$cid = intval($mybb->input['cid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=tools-couponcodes");
			}

			if($mybb->request_method == "post")
			{
				if ($cid <= 0 || (!($code = $db->fetch_array($db->simple_select('couponcodes_coupons', 'cid', "cid = $cid")))))
				{
					couponcodes_messageredirect($lang->couponcodes_invalid_code, 1);
				}

				$db->delete_query('couponcodes_coupons', "cid = $cid");

				couponcodes_messageredirect($lang->couponcodes_code_deleted);
			}
			else
			{
				$page->add_breadcrumb_item($lang->couponcodes, 'index.php?module=tools-couponcodes');
				$page->output_header($lang->couponcodes);
				$mybb->input['cid'] = intval($mybb->input['cid']);
				$form = new Form("index.php?module=tools-couponcodes&amp;action=delete_code&amp;cid={$mybb->input['cid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->couponcodes_delete_confirm}</p>\n";
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
		elseif ($mybb->input['action'] == 'set_used')
		{
			$cid = intval($mybb->input['cid']);
			$set_used = intval($mybb->input['used']);
			if ($set_used <= 0)
				$set_used = 0;
			else
				$set_used = 1;

			if ($cid <= 0 || (!($code = $db->fetch_array($db->simple_select('couponcodes_coupons', 'cid', "cid = $cid")))))
			{
				couponcodes_messageredirect($lang->couponcodes_invalid_code, 1);
			}

			$db->update_query('couponcodes_coupons', array('used' => $set_used), "cid = $cid");

			couponcodes_messageredirect($lang->couponcodes_code_updated);
		}
		elseif ($mybb->input['action'] == 'prune')
		{
			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=tools-couponcodes");
			}

			if($mybb->request_method == "post")
			{
				if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key'])
				{
					$mybb->request_method = "get";
					flash_message($lang->couponcodes_error, 'error');
					admin_redirect("index.php?module=tools-couponcodes");
				}

				$usedonly = intval($mybb->input['usedonly']);
				if ($usedonly == 1)
					$db->delete_query('couponcodes_coupons', 'date < '.(TIME_NOW - intval($mybb->input['days'])*60*60*24).' AND used = \'1\'');
				else
					$db->delete_query('couponcodes_coupons', 'date < '.(TIME_NOW - intval($mybb->input['days'])*60*60*24));

				flash_message($lang->couponcodes_codes_pruned, 'success');
				admin_redirect('index.php?module=tools-couponcodes');
			}
			else
			{
				$page->add_breadcrumb_item($lang->couponcodes, 'index.php?module=tools-couponcodes');

				$page->output_header($lang->couponcodes);

				$mybb->input['days'] = intval($mybb->input['days']);
				$form = new Form("index.php?module=tools-couponcodes&amp;action=prune&amp;days={$mybb->input['days']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->couponcodes_prune_confirm}</p>\n";
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
		elseif ($mybb->input['action'] == 'reset')
		{
			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=tools-couponcodes");
			}

			if($mybb->request_method == "post")
			{
				$db->delete_query('couponcodes_limits');

				couponcodes_messageredirect($lang->couponcodes_limits_reset);
			}
			else
			{
				$page->add_breadcrumb_item($lang->couponcodes, 'index.php?module=tools-couponcodes');
				$page->output_header($lang->couponcodes);
				$mybb->input['cid'] = intval($mybb->input['cid']);
				$form = new Form("index.php?module=tools-couponcodes&amp;action=reset&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->couponcodes_reset_confirm}</p>\n";
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

		$page->add_breadcrumb_item($lang->couponcodes, 'index.php?module=tools-couponcodes');
		$page->output_header($lang->couponcodes);

		$sub_tabs['couponcodes_couponcodes'] = array(
			'title'			=> $lang->couponcodes_couponcodes,
			'link'			=> 'index.php?module=tools-couponcodes',
			'description'	=> $lang->couponcodes_couponcodes_desc
		);

		$page->output_nav_tabs($sub_tabs, 'couponcodes_couponcodes');

		echo "<p class=\"notice\">{$lang->couponcodes_reset_limits_notice}</p>";

		if (!$mybb->input['action'])
		{
			$per_page = 10;
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

			$query = $db->simple_select("couponcodes_coupons", "COUNT(cid) as codes");
			$total_rows = $db->fetch_field($query, "codes");

			echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=tools-couponcodes&amp;page={page}");

			// table
			$table = new Table;
			$table->construct_header($lang->couponcodes_user, array('width' => '30%'));
			$table->construct_header($lang->couponcodes_code, array('width' => '25%'));
			$table->construct_header($lang->couponcodes_date, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->couponcodes_used, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->couponcodes_options, array('width' => '15%', 'class' => 'align_center'));

			$query = $db->simple_select('couponcodes_coupons', '*', '', array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => "{$start}, {$per_page}"));
			while($code = $db->fetch_array($query)) {

				$link = build_profile_link(htmlspecialchars_uni($code['username']), intval($code['uid']));
				$table->construct_cell($link);
				$table->construct_cell(htmlspecialchars_uni($code['code']));

				$table->construct_cell(my_date($mybb->settings['dateformat'], intval($code['date']), '', false).", ".my_date($mybb->settings['timeformat'], intval($code['date'])), array('class' => 'align_center'));

				if ($code['used'] == 1)
					$table->construct_cell("<a href=\"index.php?module=tools-couponcodes&amp;action=set_used&amp;used=0&amp;cid={$code['cid']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\"><img src=\"".$mybb->settings['bburl']."/images/used.png\" title=\"{$lang->couponcodes_click_to_not_used}\" alt=\"{$lang->couponcodes_used}\"/></a>", array('class' => 'align_center'));
 				else
					$table->construct_cell("<a href=\"index.php?module=tools-couponcodes&amp;action=set_used&amp;used=1&amp;cid={$code['cid']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\"><img src=\"".$mybb->settings['bburl']."/images/notused.png\" title=\"{$lang->couponcodes_click_to_used}\" alt=\"{$lang->couponcodes_notused}\"/></a>", array('class' => 'align_center'));

				$table->construct_cell("<a href=\"index.php?module=tools-couponcodes&amp;action=delete_code&amp;cid={$code['cid']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">{$lang->couponcodes_delete}</a>", array('class' => 'align_center')); // delete button

				$table->construct_row();
				$found = true;
			}
			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->couponcodes_no_codes, array('colspan' => 6));
				$table->construct_row();
			}

			$table->output($lang->couponcodes_couponcodes_generated);

			echo "<br />";

			$form = new Form("index.php?module=tools-couponcodes&amp;action=prune", "post", "couponcodes");

			echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

			$form_container = new FormContainer($lang->couponcodes_prune);
			$form_container->output_row($lang->couponcodes_older_than, $lang->couponcodes_older_than_desc, $form->generate_text_box('days', 30, array('id' => 'days')), 'days');
			$form_container->output_row($lang->couponcodes_used_only, $lang->couponcodes_used_only_desc, $form->generate_yes_no_radio('usedonly', 0, true, "", ""), 'usedonly');
			$form_container->end();

			$buttons = array();;
			$buttons[] = $form->generate_submit_button($lang->couponcodes_submit_button);
			$buttons[] = $form->generate_reset_button($lang->couponcodes_reset_button);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}

		$page->output_footer();
		exit;
	}
}

?>
