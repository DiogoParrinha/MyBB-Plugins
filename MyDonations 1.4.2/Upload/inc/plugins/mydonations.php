<?php
/***************************************************************************
 *
 *   MyDonations plugin (/inc/plugins/mydonations.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *   MyDonations is a MyBB plugin where you can manage goals and donations.
 *
 ***************************************************************************/

if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly.");

$plugins->add_hook("misc_start", "mydonations_page");
$plugins->add_hook("index_end", "mydonations_index", 20);

if(my_strpos($_SERVER['PHP_SELF'], 'index.php'))
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'mydonations_header';
}
elseif(my_strpos($_SERVER['PHP_SELF'], 'misc.php'))
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'mydonations,mydonations_page,mydonations_page_row';
	$template .= ',multipage_page_current,multipage_nextpage,multipage_page,multipage,multipage_prevpage,multipage_start,multipage_end';
}

if (defined("IN_ADMINCP"))
{
	$plugins->add_hook('admin_load','mydonations_admin');
	$plugins->add_hook('admin_user_menu','mydonations_admin_user_menu');
	$plugins->add_hook('admin_user_action_handler','mydonations_admin_user_action_handler');
	$plugins->add_hook('admin_user_permissions','mydonations_admin_user_permissions');
}

function mydonations_info()
{
	return array(
		"name"			=> "MyDonations",
		"description"	=> "Adds a donation goal system to MyBB.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.4.2",
		"guid" 			=> "",
		"compatibility" => "18*"
	);
}

function mydonations_install()
{
	global $db, $lang;

	// create settings group
	$insertarray = array(
		'name' => 'mydonations',
		'title' => 'MyDonations',
		'description' => "Settings for MyDonations plugin.",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	$setting1 = array(
		"name"			=> "mydonations_paypal_email",
		"title"			=> "PayPal email",
		"description"	=> "Enter your PayPal email (Make sure you have IPN enabled on your account)",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> "1",
		"gid"			=> $gid
	);

	$setting2 = array(
		"name"			=> "mydonations_paypal_currency",
		"title"			=> "PayPal Currency",
		"description"	=> "What is the currency you want users to pay in?",
        "optionscode" 	=> "select
AUD= Australian Dollars
CAD= Canadian Dollars
CHF= Swiss Franc
DKK= Danish Krone
EUR= Euro
GBP= British Pound
HKD= Hong Kong Dollar
JPY= Japanese Yen
PLN= Polish Zloty
SGD= Singapore Dollar
USD= US Dollars",
		"value"			=> "USD",
		"disporder"		=> "2",
		"gid"			=> $gid
	);

	$setting3 = array(
		"name"			=> "mydonations_min",
		"title"			=> "Minimum Amount",
		"description"	=> "Enter a minimum amount of money per donation. (default is 1)",
		"optionscode"	=> "text",
		"value"			=> "1",
		"disporder"		=> "3",
		"gid"			=> $gid
	);

	$setting4 = array(
		"name"			=> "mydonations_show_index",
		"title"			=> "Show on Index",
		"description"	=> "Select whether or not you want to show the donation bar on the index page.",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> "4",
		"gid"			=> $gid
	);

	$setting5 = array(
		"name"			=> "mydonations_allow_custom_amount",
		"title"			=> "Allow Custom Amount",
		"description"	=> "Select whether or not you want to allow users to donate a custom amount. If not, please enter the allowed values in the next setting. If the yes option is selected, the predefined amounts are not shown.",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> "5",
		"gid"			=> $gid
	);

	$setting6 = array(
		"name"			=> "mydonations_predefined_amounts",
		"title"			=> "Predefined Amounts",
		"description"	=> "Enter the predefined amounts, separated by a comma. For decimal separator, use the dot.",
		"optionscode"	=> "text",
		"value"			=> "5,10",
		"disporder"		=> "6",
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting1);
	$db->insert_query("settings", $setting2);
	$db->insert_query("settings", $setting3);
	$db->insert_query("settings", $setting4);
	$db->insert_query("settings", $setting5);
	$db->insert_query("settings", $setting6);

	rebuild_settings();

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydonations_goal` (
		`did` int(10) UNSIGNED NOT NULL auto_increment,
		`uid` bigint(30) UNSIGNED NOT NULL default '0',
		`receiver_email` varchar(127) NOT NULL default '',
		`receiver_id` varchar(13) NOT NULL default '',
		`business` varchar(127) NOT NULL default '',
		`item_name` varchar(127) default NULL,
		`quantity` int(10) default NULL,
		`invoice` varchar(127) default NULL,
		`custom` varchar(255) default NULL,
		`payment_type` varchar(255) default NULL,
		`payment_status` varchar(30) default NULL,
		`pending_reason` varchar(30) default NULL,
		`payment_date` varchar(55) default NULL,
		`exchange_rate` varchar(15) default NULL,
		`payment_gross` decimal(9,2) default NULL,
		`payment_fee` decimal(9,2) default NULL,
		`mc_gross` decimal(9,2) default NULL,
		`mc_fee` decimal(9,2) default NULL,
		`mc_currency` varchar(3) default NULL,
		`mc_handling` decimal(9,2) default NULL,
		`mc_shipping` decimal(9,2) default NULL,
		`tax` decimal(9,2) default NULL,
		`txn_id` varchar(17) default NULL,
		`txn_type` varchar(64) default NULL,
		`first_name` varchar(64) default NULL,
		`last_name` varchar(64) default NULL,
		`payer_business_name` varchar(127) default NULL,
		`payer_email` varchar(127) default NULL,
		`payer_id` varchar(13) default NULL,
		`payer_status` varchar(30) default NULL,
		`residence_country` varchar(2) default NULL,
		`parent_txn_id` varchar(17) default NULL,
		`notify_version` varchar(10) default NULL,
		`verify_sign` varchar(128) default NULL,
		`goal_title` varchar(50) NOT NULL default '',
		`goal_amount` DECIMAL(9,2) NOT NULL default '0',
		`goal_description` varchar(200) NOT NULL default '',
		`hidelist` tinyint(1) NOT NULL default 0,
		PRIMARY KEY  (did)
	) ENGINE=MyISAM;");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydonations_archive` (
		`aid` int(10) UNSIGNED NOT NULL auto_increment,
		`did` int(10) UNSIGNED NOT NULL default '0',
		`uid` bigint(30) UNSIGNED NOT NULL default '0',
		`receiver_email` varchar(127) NOT NULL default '',
		`receiver_id` varchar(13) NOT NULL default '',
		`business` varchar(127) NOT NULL default '',
		`item_name` varchar(127) default NULL,
		`quantity` int(10) default NULL,
		`invoice` varchar(127) default NULL,
		`custom` varchar(255) default NULL,
		`payment_type` varchar(255) default NULL,
		`payment_status` varchar(30) default NULL,
		`pending_reason` varchar(30) default NULL,
		`payment_date` varchar(55) default NULL,
		`exchange_rate` varchar(15) default NULL,
		`payment_gross` decimal(9,2) default NULL,
		`payment_fee` decimal(9,2) default NULL,
		`mc_gross` decimal(9,2) default NULL,
		`mc_fee` decimal(9,2) default NULL,
		`mc_currency` varchar(3) default NULL,
		`mc_handling` decimal(9,2) default NULL,
		`mc_shipping` decimal(9,2) default NULL,
		`tax` decimal(9,2) default NULL,
		`txn_id` varchar(17) default NULL,
		`txn_type` varchar(64) default NULL,
		`first_name` varchar(64) default NULL,
		`last_name` varchar(64) default NULL,
		`payer_business_name` varchar(127) default NULL,
		`payer_email` varchar(127) default NULL,
		`payer_id` varchar(13) default NULL,
		`payer_status` varchar(30) default NULL,
		`residence_country` varchar(2) default NULL,
		`parent_txn_id` varchar(17) default NULL,
		`notify_version` varchar(10) default NULL,
		`verify_sign` varchar(128) default NULL,
		`goal_title` varchar(50) NOT NULL default '',
		`goal_amount` DECIMAL(9,2) NOT NULL default '0',
		`goal_description` varchar(200) NOT NULL default '',
		PRIMARY KEY  (aid)
	) ENGINE=MyISAM;");

}

function mydonations_activate()
{
	global $db, $lang;

	// templates
	$templatearray = array (
		"title" => "mydonations_header",
		"template" => $db->escape_string('

		<style type="text/css">
		.mydonations {
			margin: auto;
			width: 300px;
			background: url(images/mydonations.png) no-repeat 0 -40px;
		}
		.mydonations-completed {
			height: 20px;
			margin-left: -1px;
			background: url(images/mydonations.png) no-repeat 1px 0;
		}
		.mydonations-progress {
			float: right;
			width: 50%;
			height: 20px;
			margin-right: -1px;
			background: url(images/mydonations.png) no-repeat 100% 0;
			display: inline; /* IE 6 double float bug */
		}
		</style>

		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
		<td class="thead"><strong>{$goal[\'title\']}</strong> - <a style="text-decoration: underline;" href="{$mybb->settings[\'bburl\']}/misc.php?action=mydonations_list">{$lang->mydonations_list}</a></td>
		</tr>
		<tr>
		<td class="trow1">
			<div style="text-align: center; margin-bottom: 10px;">{$goal[\'description\']}</div>
			<div class="mydonations">
				<div class="mydonations-completed" style="width:{$donationwidth}%;">
					<div class="mydonations-progress">&nbsp;</div>
				</div>
			</div>
			<div style="text-align: center; margin-top: 10px;">{$lang->mydonations_goal_current}{$donatebutton}</div>
		</td>
		</tr>
		</table><br />'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mydonations",
		"template" => $db->escape_string('
<html>
<head>
<title>{$lang->mydonations_donations} - {$mybb->settings[\'bbname\']}</title>
{$headerinclude}
<style type="text/css">
.mydonations {
	margin: auto;
	width: 300px;
	background: url(images/mydonations.png) no-repeat 0 -40px;
}
.mydonations-completed {
	height: 20px;
	margin-left: -1px;
	background: url(images/mydonations.png) no-repeat 1px 0;
}
.mydonations-progress {
	float: right;
	width: 50%;
	height: 20px;
	margin-right: -1px;
	background: url(images/mydonations.png) no-repeat 100% 0;
	display: inline; /* IE 6 double float bug */
}
</style>
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$goal[\'title\']}</strong> - <a style="text-decoration: underline;" href="{$mybb->settings[\'bburl\']}/misc.php?action=mydonations_list">{$lang->mydonations_list}</a></td>
</tr>
<tr>
<td class="trow1">
	<div style="text-align: center; margin-bottom: 10px;">{$goal[\'description\']}</div>
	<div class="mydonations">
		<div class="mydonations-completed" style="width:{$donationwidth}%;">
			<div class="mydonations-progress">&nbsp;</div>
		</div>
	</div>
	<div style="text-align: center; margin-top: 10px;">{$lang->mydonations_goal_current}{$donatebutton}</div>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mydonations_page",
		"template" => $db->escape_string('
<html>
<head>
<title>{$lang->mydonations_donations} - {$mybb->settings[\'bbname\']}</title>
{$headerinclude}
</head>
<body>
{$header}
{$multipage}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="3"><strong>{$lang->mydonations_donations}</strong></td>
	</tr>
	<tr>
		<td class="tcat" width="40%"><strong>{$lang->mydonations_username}</strong></td>
		<td class="tcat" width="30%" align="center"><strong>{$lang->mydonations_date}</strong></td>
		<td class="tcat" width="30%" align="center"><strong>{$lang->mydonations_amount}</strong></td>
	</tr>
	{$donations}
</table>
{$footer}
</body>
</html>'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mydonations_page_row",
		"template" => $db->escape_string('
	<tr>
		<td class="{$bgcolor}">{$donation[\'custom\']}</td>
		<td class="{$bgcolor}" align="center">{$donation[\'payment_date\']}</td>
		<td class="{$bgcolor}" align="center">{$donation[\'mc_gross\']}</td>
	</tr>'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	$templatearray = array (
		"title" => "mydonations_no_donations",
		"template" => $db->escape_string('
	<tr>
		<td class="trow1" colspan="3">{$lang->mydonations_no_donations}</td>
	</tr>'),
		"sid" => "-1",
	);

	$db->insert_query("templates", $templatearray);

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('index', '#'.preg_quote('{$forums}').'#', '{$mydonations}'."\n".'{$forums}');
}

function mydonations_is_installed()
{
	global $db;
	if ($db->table_exists('mydonations_goal'))
		return true;
	else
		return false;
}

function mydonations_uninstall()
{
	global $db;

	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name LIKE '%mydonations%'");

	$db->delete_query("settinggroups", "name = 'mydonations'");

	if ($db->table_exists('mydonations_goal'))
		$db->drop_table('mydonations_goal');

	if ($db->table_exists('mydonations_archive'))
		$db->drop_table('mydonations_archive');

	$db->delete_query("datacache", "title = 'mydonations_goal'");

	rebuild_settings();
}

function mydonations_deactivate()
{
	global $db;

	$db->delete_query("templates", "title IN ('mydonations_header','mydonations_page','mydonations_page_row','mydonations_no_donations')");

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('index', '#'.preg_quote("\n".'{$mydonations}').'#', '', 0);
}

function mydonations_page()
{
	global $mybb, $lang, $db, $cache, $templates, $header, $headerinclude, $footer, $theme;

	if ($mybb->input['action'] != 'mydonations' && $mybb->input['action'] != 'mydonations_list')
		return;

	$lang->load('mydonations');

	if($mybb->input['action'] == 'mydonations_list')
	{
		// pagination
		$per_page = 20;
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

		// total comments
		$total_rows = $db->fetch_field($query = $db->simple_select("mydonations_goal", "COUNT(did) as donations"), "donations");

		// multi-page
		if ($total_rows > $per_page)
			$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/misc.php?action=donations");

		$donations = '';

		if ($total_rows > 0)
		{
			$query = $db->query("
				SELECT u.usergroup, u.displaygroup, d.*
				FROM `".TABLE_PREFIX."mydonations_goal` d
				LEFT JOIN `".TABLE_PREFIX."users` u ON (u.uid=d.uid)
				WHERE d.hidelist=0
				ORDER BY d.did
				LIMIT {$start},{$per_page}
			");
			while($donation = $db->fetch_array($query)){

				$bgcolor = alt_trow();
				$donation['did'] = (int)$donation['did'];
				$donation['custom'] = build_profile_link(format_name(htmlspecialchars_uni($donation['custom']), $donation['usergroup'], $donation['displaygroup']), intval($donation['uid']));
				$donation['payment_type'] = htmlspecialchars_uni($donation['payment_type']);
				$donation['mc_gross'] = floatval($donation['mc_gross'])." ".$mybb->settings['mydonations_paypal_currency'];
				$donation['payment_date'] = htmlspecialchars_uni($donation['payment_date']);

				eval("\$donations .= \"".$templates->get('mydonations_page_row')."\";");
			}


		}
		else
			eval("\$donations = \"".$templates->get('mydonations_no_donations')."\";");

		eval("\$page = \"".$templates->get('mydonations_page')."\";");
	}
	else
	{
		if ($mybb->user['uid'] <= 0)
			$mybb->user['username'] = 'Guest';

		// do not sanitize HTML since they should be able to use HTML in the title and description
		$goal = $cache->read('mydonations_goal');

		if (!$goal) // no goal has been set since the board as installed?
		{
			$mydonations = '';
		}
		else
		{
			if ($goal['current'] < $goal['amount'])
			{
				$sandbox = ''; // set to .sandbox if you want to use sandbox
				$donatebutton = '<form action="https://www'.$sandbox.'.paypal.com/cgi-bin/webscr" method="post" style="margin-top:10px;text-align:center;">
			<input type="hidden" name="cmd" value="_xclick">
			<input type="hidden" name="business" value="'.htmlspecialchars_uni(trim($mybb->settings['mydonations_paypal_email'])).'" />
			<input type="hidden" name="custom" value="'.htmlspecialchars_uni($mybb->user['username']).'" />
			<input type="hidden" name="item_name" value="Donation from '.htmlspecialchars_uni($mybb->user['username']).'" />
			<input type="hidden" name="no_note" value="0" />
			<input type="hidden" name="currency_code" value="'.htmlspecialchars_uni($mybb->settings['mydonations_paypal_currency']).'" />
			<input name="return" value="'.$mybb->settings['bburl'].'" type="hidden" />
			<input name="cancel_return" value="'.$mybb->settings['bburl'].'" type="hidden" />
			<input type="hidden" name="notify_url" value="'.$mybb->settings['bburl'].'/mydonations_paypal.php" />
			<input type="hidden" name="tax" value="0" />
			';

				if($mybb->settings['mydonations_predefined_amounts'] != '' && $mybb->settings['mydonations_allow_custom_amount'] == 0)
				{
					$selectbox = '<select name="amount">';
					$amounts = explode(',', $mybb->settings['mydonations_predefined_amounts']);
					if(!empty($amounts))
					{
						foreach($amounts as $amount)
						{
							$selectbox .= '<option value="'.(float)$amount.'">'.$amount.' '.$mybb->settings['mydonations_paypal_currency'].'</option>';
						}
					}

					$selectbox .= '</select>';

					$donatebutton .= $selectbox;
					$donatebutton .= '<br /><br /><input type="image" src="https://www'.$sandbox.'.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" style="border:0;" name="submit" alt="PayPal - The safer, easier way to pay online!" /></form>';
				}
				elseif($mybb->settings['mydonations_allow_custom_amount'] == 1)
				{
					$donatebutton .= '<input type="text" class="textbox" name="amount" value="5" size="5" style="text-align: center" /> '.$mybb->settings['mydonations_paypal_currency'];
					$donatebutton .= '<br /><br /><input type="image" src="https://www'.$sandbox.'.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" style="border:0;" name="submit" alt="PayPal - The safer, easier way to pay online!" /></form><span class="smalltext">('.$lang->sprintf($lang->mydonations_min, $mybb->settings['mydonations_min'], $mybb->settings['mydonations_paypal_currency']).')</span>';
				}

				$amounted_donated = floatval($goal['current'])." ".$mybb->settings['mydonations_paypal_currency'];
				$total = floatval($goal['amount'])." ".$mybb->settings['mydonations_paypal_currency'];

				$lang->mydonations_goal_current = $lang->sprintf($lang->mydonations_goal_current, $amounted_donated, $total);
			}
			else {
				$lang->mydonations_goal_current = '';
				$donatebutton = $lang->mydonations_goal_reached;
			}

			if ($goal['amount'] > 0)
			{
				$donationwidth = $goal['current']*100/$goal['amount'];

				if($donationwidth > 100)
					$donationwidth = 100;
			}
			else
				$donationwidth = 0;

			eval("\$mydonations = \"".$templates->get('mydonations_header')."\";");
		}

		eval("\$page = \"".$templates->get('mydonations')."\";");
	}

	output_page($page);
	exit;
}

// Show donation table
function mydonations_index()
{
	global $mybb, $lang, $db, $cache, $templates, $mydonations, $theme;

	if($mybb->settings['mydonations_show_index'] == 0)
	{
		$mydonations = '';
		return;
	}

	$lang->load('mydonations');

	if ($mybb->user['uid'] <= 0)
		$mybb->user['username'] = 'Guest';

	// do not sanitize HTML since they should be able to use HTML in the title and description
	$goal = $cache->read('mydonations_goal');

	if (!$goal) // no goal has been set since the board as installed?
	{
		$mydonations = '';
	}
	else
	{
		if ($goal['current'] < $goal['amount'])
		{
			$sandbox = ''; // set to .sandbox if you want to use sandbox
			$donatebutton = '<form action="https://www'.$sandbox.'.paypal.com/cgi-bin/webscr" method="post" style="margin-top:10px;text-align:center;">
		<input type="hidden" name="cmd" value="_xclick">
		<input type="hidden" name="business" value="'.htmlspecialchars_uni(trim($mybb->settings['mydonations_paypal_email'])).'" />
		<input type="hidden" name="custom" value="'.htmlspecialchars_uni($mybb->user['username']).'" />
		<input type="hidden" name="item_name" value="Donation from '.$mybb->user['username'].'" />
		<input type="hidden" name="no_note" value="0" />
		<input type="hidden" name="currency_code" value="'.htmlspecialchars_uni($mybb->settings['mydonations_paypal_currency']).'" />
		<input name="return" value="'.$mybb->settings['bburl'].'" type="hidden" />
		<input name="cancel_return" value="'.$mybb->settings['bburl'].'" type="hidden" />
		<input type="hidden" name="notify_url" value="'.$mybb->settings['bburl'].'/mydonations_paypal.php" />
		<input type="hidden" name="tax" value="0" />
		';

			if($mybb->settings['mydonations_predefined_amounts'] != '' && $mybb->settings['mydonations_allow_custom_amount'] == 0)
			{
				$selectbox = '<select name="amount">';
				$amounts = explode(',', $mybb->settings['mydonations_predefined_amounts']);
				if(!empty($amounts))
				{
					foreach($amounts as $amount)
					{
						$selectbox .= '<option value="'.(float)$amount.'">'.$amount.' '.$mybb->settings['mydonations_paypal_currency'].'</option>';
					}
				}

				$selectbox .= '</select>';

				$donatebutton .= $selectbox;
				$donatebutton .= '<br /><br /><input type="image" src="https://www'.$sandbox.'.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" style="border:0;" name="submit" alt="PayPal - The safer, easier way to pay online!" /></form>';
			}
			elseif($mybb->settings['mydonations_allow_custom_amount'] == 1)
			{
				$donatebutton .= '<input type="text" class="textbox" name="amount" value="5" size="5" style="text-align: center" /> '.$mybb->settings['mydonations_paypal_currency'];
				$donatebutton .= '<br /><br /><input type="image" src="https://www'.$sandbox.'.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" style="border:0;" name="submit" alt="PayPal - The safer, easier way to pay online!" /></form><span class="smalltext">('.$lang->sprintf($lang->mydonations_min, $mybb->settings['mydonations_min'], $mybb->settings['mydonations_paypal_currency']).')</span>';
			}

			$amounted_donated = floatval($goal['current'])." ".$mybb->settings['mydonations_paypal_currency'];
			$total = floatval($goal['amount'])." ".$mybb->settings['mydonations_paypal_currency'];

			$lang->mydonations_goal_current = $lang->sprintf($lang->mydonations_goal_current, $amounted_donated, $total);
		}
		else {
			$lang->mydonations_goal_current = '';
			$donatebutton = $lang->mydonations_goal_reached;
		}

		if ($goal['amount'] > 0)
		{
			$donationwidth = $goal['current']*100/$goal['amount'];

			if($donationwidth > 100)
				$donationwidth = 100;
		}
		else
			$donationwidth = 0;

		eval("\$mydonations = \"".$templates->get('mydonations_header')."\";");
	}
}

/////// Admin CP ///////

function mydonations_admin_user_menu(&$sub_menu)
{
	global $lang;

	$lang->load('mydonations');
	$sub_menu[] = array('id' => 'mydonations', 'title' => $lang->mydonations_index, 'link' => 'index.php?module=user-mydonations');
}

function mydonations_admin_user_action_handler (&$actions)
{
	$actions['mydonations'] = array ('active' => 'mydonations', 'file' => 'mydonations');
}

function mydonations_admin_user_permissions (&$admin_permissions)
{
	global $db, $mybb, $lang;

	$lang->load('mydonations', false, true);
	$admin_permissions['mydonations'] = $lang->mydonations_canmanage;
}

function mydonations_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins, $cache;

	$lang->load('mydonations', false, true);

	if ($run_module == 'user' && $action_file == 'mydonations')
	{
		if($mybb->request_method == 'post')
		{
			if($mybb->input['action'] == 'setgoal')
			{
				if (empty($mybb->input['title']))
				{
					flash_message($lang->mydonations_missing_title, 'error');
					admin_redirect("index.php?module=user-mydonations");
				}

				if (empty($mybb->input['description']))
				{
					flash_message($lang->mydonations_missing_description, 'error');
					admin_redirect("index.php?module=user-mydonations");
				}

				if (empty($mybb->input['amount']))
				{
					flash_message($lang->mydonations_missing_amount, 'error');
					admin_redirect("index.php?module=user-mydonations");
				}

				$goal = $cache->read('mydonations_goal');

				// Move donations to mydonations_archive table
				if (intval($mybb->input['per_page']) > 0)
					$per_page = intval($mybb->input['per_page']);
				else
					$per_page = 10;

				$query = $db->simple_select("mydonations_goal", "COUNT(*) as donations");
				$total_donations = $db->fetch_field($query, 'donations');
				if ($total_donations > 0)
				{
					$query = $db->simple_select('mydonations_goal', '*', '', array('order_by' => 'did', 'order_dir' => 'ASC', 'limit' => "{$per_page}"));
					while ($donation = $db->fetch_array($query))
					{
						$insert_array = array(
							'did' => intval($donation['did']),
							'uid' => intval($donation['uid']),
							'receiver_email' => trim($db->escape_string($donation['receiver_email'])),
							'receiver_id' => trim($db->escape_string($donation['receiver_id'])),
							'business' => trim($db->escape_string($donation['business'])),
							'item_name' => trim($db->escape_string($donation['business'])),
							'quantity' => intval($donation['quantity']),
							'invoice' => trim($db->escape_string($donation['invoice'])),
							'custom' => trim($db->escape_string($donation['custom'])),
							'payment_type' => trim($db->escape_string($donation['payment_type'])),
							'payment_status' => trim($db->escape_string($donation['payment_status'])),
							'pending_reason' => trim($db->escape_string($donation['pending_reason'])),
							'payment_date' => trim($db->escape_string($donation['payment_date'])),
							'exchange_rate' => trim($db->escape_string($donation['exchange_rate'])),
							'payment_gross' => floatval($donation['payment_gross']),
							'payment_fee' => floatval($donation['payment_fee']),
							'mc_gross' => floatval($donation['mc_gross']),
							'mc_fee' => floatval($donation['mc_fee']),
							'mc_currency' => $mc_currency,
							'mc_handling' => floatval($donation['mc_handling']),
							'mc_shipping' => floatval($donation['mc_shipping']),
							'tax' => trim($db->escape_string($donation['tax'])),
							'txn_id' => trim($db->escape_string($donation['txn_id'])),
							'txn_type' => trim($db->escape_string($donation['txn_type'])),
							'first_name' => trim($db->escape_string($donation['first_name'])),
							'last_name' => trim($db->escape_string($donation['last_name'])),
							'payer_business_name' => trim($db->escape_string($donation['payer_business_name'])),
							'payer_email' => trim($db->escape_string($donation['payer_email'])),
							'payer_id' => trim($db->escape_string($donation['payer_id'])),
							'payer_status' => trim($db->escape_string($donation['payer_status'])),
							'residence_country' => trim($db->escape_string($donation['residence_country'])),
							'parent_txn_id' => trim($db->escape_string($donation['parent_txn_id'])),
							'notify_version' => trim($db->escape_string($donation['notify_version'])),
							'verify_sign' => trim($db->escape_string($donation['verify_sign'])),
							'goal_title' => trim($db->escape_string($donation['goal_title'])),
							'goal_description' => trim($db->escape_string($donation['goal_description'])),
							'goal_amount' => floatval($donation['goal_amount']),
						);
						$db->insert_query('mydonations_archive', $insert_array);
						$db->delete_query('mydonations_goal', 'did='.$donation['did']);
					}

					if (($total_donations - $per_page) > 0)
					{
						global $form, $page;
						$lang->load("mydonations");
						$page->output_header($lang->mydonations);

						$form = new Form("index.php?module=user-mydonations&amp;action=setgoal", "post", "myachievements");

						echo $form->generate_hidden_field("per_page", intval($mybb->input['per_page']));
						echo $form->generate_hidden_field("title", htmlspecialchars_uni($mybb->input['title']));
						echo $form->generate_hidden_field("description", htmlspecialchars_uni($mybb->input['description']));
						echo $form->generate_hidden_field("amount", floatval($mybb->input['amount']));
						echo "<div class=\"confirm_action\">\n";
						echo "<p>".$lang->mydonations_setgoal_click_continue."</p>\n";
						echo "<br />\n";
						echo "<p class=\"buttons\">\n";
						echo $form->generate_submit_button($lang->mydonations_continue, array('class' => 'button_yes'));
						echo "</p>\n";
						echo "</div>\n";

						$form->end();

						$page->output_footer();
						exit;
					}
				}

				// Reset goal in datacache
				$cache->update('mydonations_goal', array(
					'title' => trim($db->escape_string($mybb->input['title'])),
					'description' => trim($db->escape_string($mybb->input['description'])),
					'amount' => floatval($mybb->input['amount']),
					'current' => 0,
				));

				flash_message($lang->mydonations_goal_set, 'success');
				admin_redirect("index.php?module=user-mydonations");
			}
			elseif($mybb->input['action'] == 'editgoal')
			{
				if (empty($mybb->input['title']))
				{
					flash_message($lang->mydonations_missing_title, 'error');
					admin_redirect("index.php?module=user-mydonations");
				}

				if (empty($mybb->input['description']))
				{
					flash_message($lang->mydonations_missing_description, 'error');
					admin_redirect("index.php?module=user-mydonations");
				}

				if (empty($mybb->input['amount']))
				{
					flash_message($lang->mydonations_missing_amount, 'error');
					admin_redirect("index.php?module=user-mydonations");
				}

				// Reset goal in datacache
				$cache->update('mydonations_goal', array(
					'title' => trim($db->escape_string($mybb->input['title'])),
					'description' => trim($db->escape_string($mybb->input['description'])),
					'amount' => floatval($mybb->input['amount']),
					'current' => floatval($goal['current']),
				));

				$update_array = array(
					'goal_title' => trim($db->escape_string($mybb->input['title'])),
					'goal_description' => trim($db->escape_string($mybb->input['description'])),
					'goal_amount' => floatval($mybb->input['amount']),
				);

				// Update all donations assigned to this goal
				$db->update_query('mydonations_goal', $update_array);

				flash_message($lang->mydonations_goal_updated, 'success');
				admin_redirect("index.php?module=user-mydonations");
			}
			elseif($mybb->input['action'] == 'prune')
			{
				// Prune mydonations_archive table
				$db->delete_query('mydonations_archive');

				flash_message($lang->mydonations_archive_pruned, 'success');
				admin_redirect("index.php?module=user-mydonations");
			}
			elseif($mybb->input['action'] == 'add')
			{
				// get current goal information
				$goal = $cache->read('mydonations_goal');
				if(!$goal)
				{
					flash_message($lang->mydonations_no_goal, 'error');
					admin_redirect("index.php?module=user-mydonations");
				}

				// Get user
				$user = get_user_by_username($mybb->input['username'], array('fields' => array('uid','username','email')));
				if(empty($user))
				{
					// It's a Guest
					$user['uid'] = 0;
					$user['username'] = htmlspecialchars_uni($mybb->input['username']);
					$user['email'] = 'No e-mail / Added manually';
				}

				if($user['username'] == '')
					$user['username'] = 'Guest';

				// Validate amount
				$amount = (float)$mybb->input['amount'];
				if($amount <= 0)
				{
					flash_message($lang->mydonations_invalid_amount, 'error');
					admin_redirect("index.php?module=user-mydonations");
				}

				$insert_array = array(
					'uid' => intval($user['uid']),
					'receiver_email' => $mybb->settings['mydonations_paypal_email'],
					'business' => $db->escape_string($mybb->settings['mydonations_paypal_email']),
					'item_name' => 'Donation from '.$db->escape_string($user['username']),
					'custom' => $db->escape_string($user['username']),
					'payment_status' => $lang->mydonations_manual_payment,
					'payment_date' => my_date("H:i:s F j, Y", TIME_NOW),
					'mc_gross' => $amount,
					'mc_currency' => $db->escape_string($mybb->settings['mydonations_paypal_currency']),
					'payer_email' => $db->escape_string($user['email']),
					'goal_title' => $db->escape_string($goal['title']),
					'goal_description' => $db->escape_string($goal['description']),
					'goal_amount' => floatval($goal['amount']),
					'hidelist' => (int)$mybb->input['hidelist']
				);
				$db->insert_query('mydonations_goal', $insert_array);

				$cache->update('mydonations_goal', array(
					'title' => trim($db->escape_string($goal['title'])),
					'description' => trim($db->escape_string($goal['description'])),
					'amount' => floatval($goal['amount']),
					'current' => floatval($goal['current'])+floatval($amount),
				));

				flash_message($lang->mydonations_donation_added, 'success');
				admin_redirect("index.php?module=user-mydonations");
			}
		}

		$page->add_breadcrumb_item($lang->mydonations, 'index.php?module=user-donatiobar');

		$page->output_header($lang->mydonations);

		// Tabs
		$sub_tabs['mydonations_goal'] = array(
			'title'			=> $lang->mydonations_goal,
			'link'			=> 'index.php?module=user-mydonations',
			'description'	=> $lang->mydonations_goal_desc);

		$sub_tabs['mydonations_archive'] = array(
			'title'			=> $lang->mydonations_archive,
			'link'			=> 'index.php?module=user-mydonations&amp;action=archive',
			'description'	=> $lang->mydonations_archive_desc);

		$sub_tabs['mydonations_setgoal'] = array(
			'title'			=> $lang->mydonations_setgoal,
			'link'			=> 'index.php?module=user-mydonations&amp;action=setgoal',
			'description'	=> $lang->mydonations_setgoal_desc);

		$sub_tabs['mydonations_editgoal'] = array(
			'title'			=> $lang->mydonations_editgoal,
			'link'			=> 'index.php?module=user-mydonations&amp;action=editgoal',
			'description'	=> $lang->mydonations_editgoal_desc);

		if($mybb->input['action'] == 'setgoal')
		{
			$page->output_nav_tabs($sub_tabs, 'mydonations_setgoal');

			$form = new Form("index.php?module=user-mydonations&amp;action=setgoal", "post", "mydonations");

			$form_container = new FormContainer($lang->mydonations_setgoal);
			$form_container->output_row($lang->mydonations_setgoal_title, $lang->mydonations_setgoal_title_desc, $form->generate_text_box('title', '', array('id' => 'title')), 'title');
			$form_container->output_row($lang->mydonations_setgoal_description, $lang->mydonations_setgoal_description_desc, $form->generate_text_box('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->mydonations_setgoal_amount, $lang->mydonations_setgoal_amount_desc, $form->generate_text_box('amount', '', array('id' => 'amount')), 'amount');
			$form_container->output_row("<span style=\"text-align: center;\">".$lang->mydonations_setgoal_note."<span>");
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->mydonations_submit);
			$buttons[] = $form->generate_reset_button($lang->mydonations_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif($mybb->input['action'] == 'editgoal')
		{
			$page->output_nav_tabs($sub_tabs, 'mydonations_editgoal');

			$goal = $cache->read('mydonations_goal');

			if (empty($goal['title']))
			{
				flash_message($lang->mydonations_editgoal_not_set, 'error');
				admin_redirect("index.php?module=user-mydonations");
			}

			$form = new Form("index.php?module=user-mydonations&amp;action=editgoal", "post", "mydonations");

			$form_container = new FormContainer($lang->mydonations_editgoal);
			$form_container->output_row($lang->mydonations_setgoal_title, $lang->mydonations_setgoal_title_desc, $form->generate_text_box('title', htmlspecialchars_uni($goal['title']), array('id' => 'title')), 'title');
			$form_container->output_row($lang->mydonations_setgoal_description, $lang->mydonations_setgoal_description_desc, $form->generate_text_box('description', htmlspecialchars_uni($goal['description']), array('id' => 'description')), 'description');
			$form_container->output_row($lang->mydonations_setgoal_amount, $lang->mydonations_setgoal_amount_desc, $form->generate_text_box('amount', floatval($goal['amount']), array('id' => 'amount')), 'amount');
			$form_container->output_row("<p style=\"text-align: center; line-height: 1.2pt\"><strong>".$lang->mydonations_editgoal_note."</strong></p>");
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->mydonations_submit);
			$buttons[] = $form->generate_reset_button($lang->mydonations_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif($mybb->input['action'] == 'archive')
		{
			$page->output_nav_tabs($sub_tabs, 'mydonations_archive');

			// Archive
			$table = new Table;

			$per_page = 10;
			if($mybb->input['page'] && $mybb->input['page'] > 1)
			{
				$mybb->input['page'] = intval($mybb->input['page']);
				$start =($mybb->input['page']*$per_page)-$per_page;
			}
			else
			{
				$mybb->input['page'] = 1;
				$start = 0;
			}


			$query = $db->simple_select("mydonations_archive", "COUNT(did) as donations");
			$total_rows = $db->fetch_field($query, "donations");

			$table->construct_header($lang->mydonations_id, array('width'=> '10%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_username, array('width'=> '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_type, array('width'=> '15%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_amount, array('width'=> '15%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_date, array('width'=> '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_goal, array('width'=> '20%', 'class' => 'align_center'));

			$query = $db->simple_select('mydonations_archive', '*', '', array('order_by' => 'did', 'order_dir' => 'DESC', 'limit' => "{$start}, {$per_page}"));
			while($donation = $db->fetch_array($query)){
				$table->construct_cell($donation['did'],array("class" => "align_center"));
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($donation['custom']), intval($donation['uid'])),array("class" => "align_center"));

				if($donation['payment_type'] != '')
					$table->construct_cell($donation['payment_type'],array("class" => "align_center"));
				else
				{
					// payment_type is not set for manual payments added from ACP - but status is
					$table->construct_cell($donation['payment_status'],array("class" => "align_center"));
				}

				$table->construct_cell($donation['mc_gross']." ".$mybb->settings['mydonations_paypal_currency'],array("class" => "align_center"));
				$table->construct_cell(htmlspecialchars_uni($donation['payment_date']),array("class" => "align_center"));
				$table->construct_cell("<span style=\"border-bottom: 1px dashed; cursor: help;\" title=\"".htmlspecialchars_uni($donation['goal_description'])."\">".htmlspecialchars_uni($donation['goal_title'])."</span> (".floatval($donation['goal_amount'])." ".$mybb->settings['mydonations_paypal_currency'].")",array("class" => "align_center"));
				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->mydonations_could_not_find_any_data,array("colspan" => "6"));
				$table->construct_row();
			}

			$table->output($lang->mydonations_archive);

			echo "<br />";

			$form = new Form("index.php?module=user-mydonations&amp;action=prune", "post", "mydonations");

			$form_container = new FormContainer($lang->mydonations_prune_donations);
			$form_container->output_row("<p style=\"text-align: center; line-height: 1.2pt\"><strong>".$lang->mydonations_prune_notice."</strong></p>");
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->mydonations_submit);
			$buttons[] = $form->generate_reset_button($lang->mydonations_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		else
		{
			$page->output_nav_tabs($sub_tabs, 'mydonations_goal');

			$goal = $cache->read('mydonations_goal');

			if (!$goal)
			{
				echo "<p class=\"notice\">{$lang->mydonations_no_goal}</p>";
			}
			else {
				echo "<p class=\"notice\">{$lang->mydonations_current_goal}: <span style=\"border-bottom: 1px dashed; cursor: help;\" title=\"".htmlspecialchars_uni($goal['description'])."\">".htmlspecialchars_uni($goal['title'])."</span> - ".floatval($goal['amount'])." ".$mybb->settings['mydonations_paypal_currency']." [<a href=\"index.php?module=user-mydonations&amp;action=editgoal\">".$lang->mydonations_edit."</a>]</p>";
				if ($goal['current'] >= $goal['amount'])
					echo "<p class=\"notice\">{$lang->mydonations_goal_reached}</p>";
				else
					echo "<p class=\"notice\">{$lang->mydonations_current_amount}: ".floatval($goal['current'])." ".$mybb->settings['mydonations_paypal_currency']."</p>";

				echo "<br />";

				$form = new Form("index.php?module=user-mydonations&amp;action=add", "post", "mydonations");

				$form_container = new FormContainer($lang->mydonations_add_donation);
				$form_container->output_row($lang->mydonations_username, $lang->mydonations_username_desc, $form->generate_text_box('username'));
				$form_container->output_row($lang->mydonations_amount, $lang->mydonations_amount_desc, $form->generate_text_box('amount'));
				$form_container->output_row($lang->mydonations_hidelist, $lang->mydonations_hidelist_desc, $form->generate_check_box('hidelist', '1', '', array('checked' => false)), 'hidelist');
				$form_container->end();

				$buttons = array();;
				$buttons[] = $form->generate_submit_button($lang->mydonations_submit);
				$form->output_submit_wrapper($buttons);
				$form->end();
			}

			// Current Goal
			$table = new Table;

			$per_page = 10;
			if($mybb->input['page'] && $mybb->input['page'] > 1)
			{
				$mybb->input['page'] = intval($mybb->input['page']);
				$start =($mybb->input['page']*$per_page)-$per_page;
			}
			else
			{
				$mybb->input['page'] = 1;
				$start = 0;
			}

			$query = $db->simple_select("mydonations_goal", "COUNT(did) as donations");
			$total_rows = $db->fetch_field($query, "donations");

			echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=user-mydonations&amp;page={page}");

			$table->construct_header($lang->mydonations_id, array('width'=> '10%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_username, array('width'=> '25%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_type, array('width'=> '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_amount, array('width'=> '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mydonations_date, array('width'=> '25%', 'class' => 'align_center'));

			$query = $db->simple_select('mydonations_goal', '*', '', array('order_by' => 'did', 'order_dir' => 'DESC', 'limit' => "{$start}, {$per_page}"));
			while($donation = $db->fetch_array($query)){
				$table->construct_cell($donation['did'],array("class" => "align_center"));
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($donation['custom']), intval($donation['uid'])),array("class" => "align_center"));

				if($donation['payment_type'] != '')
					$table->construct_cell($donation['payment_type'],array("class" => "align_center"));
				else
				{
					// payment_type is not set for manual payments added from ACP - but status is
					$table->construct_cell($donation['payment_status'],array("class" => "align_center"));
				}

				$table->construct_cell($donation['mc_gross']." ".$mybb->settings['mydonations_paypal_currency'],array("class" => "align_center"));
				$table->construct_cell(htmlspecialchars_uni($donation['payment_date']),array("class" => "align_center"));
				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->mydonations_could_not_find_any_data,array("colspan" => "5"));
				$table->construct_row();
			}

			$table->output($lang->mydonations_goal);
		}

		$page->output_footer();
	}
}

// &#71;&#101;&#110;&#101;&#114;&#105;&#99;

?>
