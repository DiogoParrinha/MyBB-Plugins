<?php
/***************************************************************************
 *
 *   MySubscriptions plugin (/inc/plugins/mysubscriptions.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly.");

// add hooks
$plugins->add_hook('admin_load', 'mysubscriptions_admin');
$plugins->add_hook('admin_user_menu', 'mysubscriptions_admin_user_menu');
$plugins->add_hook('admin_user_action_handler', 'mysubscriptions_admin_user_action_handler');
$plugins->add_hook('admin_user_permissions', 'mysubscriptions_admin_permissions');

$plugins->add_hook('usercp_start', 'mysubscriptions_usercp');
$plugins->add_hook('usercp_menu_built', 'mysubscriptions_usercp_nav');

if(defined('THIS_SCRIPT') && THIS_SCRIPT == 'usercp.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'mysubscriptions_nav,mysubscriptions_usercp_row,mysubscriptions_usercp';
}

//$plugins->add_hook('global_end', 'mysubscriptions_global');

function mysubscriptions_info()
{
	return array(
		"name"			=> "MySubscriptions",
		"description"	=> "Admins can setup PayPal subscriptions for their website.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "2.3.6",
		"guid" 			=> "",
		"compatibility"	=> "18*"
	);
}


function mysubscriptions_install()
{
	global $db, $lang;
	// create settings group
	$insertarray = array(
		'name' => 'mysubscriptions',
		'title' => 'MySubscriptions',
		'description' => "Settings for MySubscriptions plugin",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting1 = array(
        "name" => "mysubscriptions_paypal_currency",
        "title" => "PayPal Currency",
        "description" => "Select the PayPal currency you want to use in subscriptions.",
        "optionscode" => "select
AUD= Australian Dollars
CAD= Canadian Dollars
CHF= Swiss Franc
CZK= Czech Koruna
DKK= Danish Krone
EUR= Euro
GBP= British Pound
HKD= Hong Kong Dollar
HUF= Hungarian Forint
JPY= Japanese Yen
MXN= Mexican Peso
NOK= Norwegian Krone
NZD= New Zealand Dollar
PHP= Philippine Peso
PLN= Polish Zloty
SEK= Swedish Krona
SGD= Singapore Dollar
CHF= Swiss Franc
TWD= Taiwan New Dollar
USD= US Dollars",
        "value" => "USD",
        "disporder" => 1,
        "gid" => intval($gid),
	);

	$db->insert_query("settings", $setting1);

	$setting2 = array(
		"name"			=> "mysubscriptions_paypal_email",
		"title"			=> "PayPal email",
		"description"	=> "Enter the PayPal email that receives the money of subscriptions. The account MUST have IPN activated! Leave this empty to disable this payment type.",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting2);

	$setting3 = array(
        "name" => "mysubscriptions_coinpayments_currency",
        "title" => "CoinPayments Currency",
        "description" => "Select the CoinPayments currency you want to use in subscriptions.",
        "optionscode" => "select
AUD= Australian Dollars
CAD= Canadian Dollars
CHF= Swiss Franc
CZK= Czech Koruna
DKK= Danish Krone
EUR= Euro
GBP= British Pound
HKD= Hong Kong Dollar
HUF= Hungarian Forint
JPY= Japanese Yen
MXN= Mexican Peso
NOK= Norwegian Krone
NZD= New Zealand Dollar
PHP= Philippine Peso
PLN= Polish Zloty
SEK= Swedish Krona
SGD= Singapore Dollar
CHF= Swiss Franc
TWD= Taiwan New Dollar
USD= US Dollars",
        "value" => "USD",
        "disporder" => 3,
        "gid" => intval($gid),
	);

	$db->insert_query("settings", $setting3);

	$setting4 = array(
		"name"			=> "mysubscriptions_coinpayments_merchantid",
		"title"			=> "CoinPayments Merchant ID",
		"description"	=> "Enter the CoinPayments merchant ID. Leave this empty to disable this payment type.",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> 4,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting4);

	$setting5 = array(
		"name"			=> "mysubscriptions_expire_emails",
		"title"			=> "Expiration Notification E-mails",
		"description"	=> "Whether or not to send an e-mail to users when their One-off subscriptions will end in one week.",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 5,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting5);

	$setting6 = array(
		"name"			=> "mysubscriptions_show_plans",
		"title"			=> "Show plans without viewing permission",
		"description"	=> "Select whether or not you want to display plans that the users have no permission to view, but instead of displaying the buttons, a message is shown instead, stating that the user cannot upgrade to the designated plan.",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 6,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting6);

	$setting7 = array(
		"name"			=> "mysubscriptions_locked_period",
		"title"			=> "Subscription Lock Period",
		"description"	=> "Enter how much time (in seconds!) a subscriber, whose subscription has expired, has to renew the subscription before their slot gets available to someone else. This is only important if you specify a maximum amount of active subscribers (slots) per plan, as this allows users to have a window to re-subscribe without losing their slot. Leave this set to 0 if you do not use slotted subscriptions. ",
		"optionscode"	=> "text",
		"value"			=> 0,
		"disporder"		=> 7,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting7);

	rebuild_settings();

	$collation = $db->build_create_table_collation();

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."mysubscriptions_subscriptions` (
	  `sid` bigint(30) UNSIGNED NOT NULL auto_increment,
	  `title` varchar(100) NOT NULL default '',
	  `description` text NOT NULL,
	  `message` text NOT NULL,
	  `time_period` text NOT NULL,
	  `lockedsubs` text NOT NULL,
	  `maxactive` int(10) UNSIGNED NOT NULL default 0,
	  `group` tinyint(3) UNSIGNED NOT NULL default '0',
	  `visible` varchar(50) NOT NULL default '',
	  `additional` tinyint(1) UNSIGNED NOT NULL default '0',
	  `enabled` tinyint(1) UNSIGNED NOT NULL default '0',
	  `disporder` smallint(5) UNSIGNED NOT NULL default '0',
	  PRIMARY KEY  (`sid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."mysubscriptions_log` (
		`lid` bigint(30) UNSIGNED NOT NULL auto_increment,
		`uname` varchar(50) NOT NULL default '',
		`uid` bigint(30) UNSIGNED NOT NULL default '0',
		`additional` smallint(1) UNSIGNED NOT NULL default '0',
		`sid` int(10) UNSIGNED NOT NULL default '0',
		`endgroup` int(10) UNSIGNED NOT NULL default '0',
		`receiver_email` varchar(150) NOT NULL default '',
		`receiver_id` varchar(30) NOT NULL default '',
		`business` varchar(150) NOT NULL default '',
		`item_name` varchar(127) default NULL,
		`item_number` varchar(127) default NULL,
		`quantity` int(10) default NULL,
		`invoice` varchar(127) default NULL,
		`option_name1` varchar(127) default NULL,
		`option_selection1` varchar(200) default NULL,
		`option_name2` varchar(127) default NULL,
		`option_selection2` varchar(200) default NULL,
		`payment_type` enum('echeck','instant') default NULL,
		`payment_status` varchar(30) default NULL,
		`pending_reason` varchar(30) default NULL,
		`reason_code` varchar(127) default NULL,
		`payment_date` varchar(55) default NULL,
		`settle_amount` decimal(9,2) default NULL,
		`settle_currency` varchar(3) default NULL,
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
		`for_auction` enum('true') default NULL,
		`auction_buyer_id` varchar(64) default NULL,
		`auction_closing_date` varchar(64) default NULL,
		`auction_multi_item` varchar(64) default NULL,
		`first_name` varchar(64) default NULL,
		`last_name` varchar(64) default NULL,
		`address_name` varchar(128) default NULL,
		`address_street` varchar(64) default NULL,
		`address_city` varchar(40) default NULL,
		`address_state` varchar(32) default NULL,
		`address_zip` varchar(25) default NULL,
		`address_country` varchar(64) default NULL,
		`address_country_code` varchar(2) default NULL,
		`address_status` enum('confirmed','unconfirmed') default NULL,
		`payer_business_name` varchar(127) default NULL,
		`payer_email` varchar(127) default NULL,
		`payer_id` varchar(13) default NULL,
		`payer_status` varchar(30) default NULL,
		`residence_country` varchar(2) default NULL,
		`memo` tinytext,
		`subscr_date` varchar(55) default NULL,
		`subscr_effective` varchar(55) default NULL,
		`period1` varchar(10) default NULL,
		`period2` varchar(10) default NULL,
		`period3` varchar(10) default NULL,
		`amount1` decimal(9,2) default NULL,
		`amount2` decimal(9,2) default NULL,
		`amount3` decimal(9,2) default NULL,
		`mc_amount1` decimal(9,2) default NULL,
		`mc_amount2` decimal(9,2) default NULL,
		`mc_amount3` decimal(9,2) default NULL,
		`recurring` int(10) default NULL,
		`reattempt` int(10) default NULL,
		`retry_at` varchar(55) default NULL,
		`recur_times` int(10) default NULL,
		`username` varchar(32) default NULL,
		`password` varchar(32) default NULL,
		`subscr_id` varchar(50) default NULL,
		`auth_id` varchar(127) default NULL,
		`auth_exp` varchar(127) default NULL,
		`auth_amount` varchar(127) default NULL,
		`auth_status` varchar(30) default NULL,
		`transaction_entity` varchar(30) default NULL,
		`remaining_settle` varchar(127) default NULL,
		`parent_txn_id` varchar(17) default NULL,
		`case_id` varchar(17) default NULL,
		`case_type` varchar(30) default NULL,
		`case_creation_date` varchar(55) default NULL,
		`notify_version` varchar(10) default NULL,
		`verify_sign` varchar(128) default NULL,
		`timestamp` int(10) not null default 0,
		`enddate` int(10) not null default 0,
		`expired` tinyint(1) not null default 0,
		`email` tinyint(1) not null default 0,
		`frozen` int(10) not null default 0,
		PRIMARY KEY  (`lid`)
		) ENGINE=MyISAM{$collation}");

		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mysubscriptions_coinpayments_log` (
		`lid` bigint(30) UNSIGNED NOT NULL auto_increment,
		`uname` varchar(50) NOT NULL default '',
		`uid` bigint(30) UNSIGNED NOT NULL default '0',
		`additional` smallint(1) UNSIGNED NOT NULL default '0',
		`sid` int(10) UNSIGNED NOT NULL default '0',
		`endgroup` int(10) UNSIGNED NOT NULL default '0',
		`merchant` varchar(255) NOT NULL default '',
		`first_name` varchar(255) NOT NULL default '',
		`last_name` varchar(255) NOT NULL default '',
		`buyer_email` varchar(255) NOT NULL default '',
		`status` smallint(5) NOT NULL default '0',
		`status_text` varchar(255) NOT NULL default '',
		`txn_id` varchar(255) NOT NULL default '',
		`currency1` varchar(10) NOT NULL default '',
		`currency2` varchar(10) NOT NULL default '',
		`amount1` varchar(20) NOT NULL default '',
		`amount2` varchar(20) NOT NULL default '',
		`subtotal` varchar(20) NOT NULL default '',
		`tax` varchar(50) NOT NULL default '',
		`fee` varchar(50) NOT NULL default '',
		`item_amount` tinyint(1) UNSIGNED NOT NULL default 0,
		`item_name` varchar(255) NOT NULL default '',
		`item_desc` varchar(255) NOT NULL default '',
		`item_number` int(10) UNSIGNED NOT NULL default '0',
		`received_amount` varchar(20) NOT NULL default '',
		`received_confirms` varchar(20) NOT NULL default '',
		`custom` int(10) UNSIGNED NOT NULL default '0',
		`timestamp` int(10) not null default 0,
		`enddate` int(10) not null default 0,
		`expired` tinyint(1) not null default 0,
		`email` tinyint(1) not null default 0,
		`frozen` int(10) not null default 0,
		PRIMARY KEY  (`lid`)
		) ENGINE=MyISAM{$collation}");

		// create task
		$new_task = array(
			"title" => "MySubscriptions (Expire)",
			"description" => "Checks for members whose one-off subscriptions have expired.",
			"file" => "mysubscriptions",
			"minute" => '10,25,40,55',
			"hour" => '*',
			"day" => '*',
			"month" => '*',
			"weekday" => '*',
			"enabled" => '0',
			"logging" => '1'
		);

		$new_task['nextrun'] = 0; // once the task is enabled, it will generate a nextrun date
		$tid = $db->insert_query("tasks", $new_task);
}

function mysubscriptions_activate()
{
	global $db, $lang;

	// insert templates
	$templatearray = array(
		"title" => 'mysubscriptions',
		"template" => $db->escape_string('
<html>
	<head>
		<title>{$lang->mysubscriptions}</title>
		{$headerinclude}
		<script type="text/javascript">
			$(document).ready(function(){
				$(\'.timeperiod\').each(function(i, obj) {

					$(this).change(function () {

						// Get info from value
						// Extract sub ID and price
						var data = $(this).val().split(\':\');

						if(data[0] != 0) // the default option (select period) has value 0
						{
							var sid = data[0];
							var price = data[3];

							console.log(sid);
							console.log(price);

							// Set custom to value of the selected option (amount_pp_sid, amount_cp_sid and custom_pp_sid, custom_cp_sid)
							$(\'#amount_pp_\'+sid).val(price);
							$(\'#amount_cp_\'+sid).val(price);
							$(\'#custom_pp_\'+sid).val($(this).val());
							$(\'#custom_cp_\'+sid).val($(this).val());
						}
					}).change();
				});
			});
		</script>
	</head>
	<body>
		{$header}
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
			<tr>
				<td class="thead" colspan="5"><strong>{$lang->mysubscriptions_plans}</strong></td>
			</tr>
			<tr>
				<td class="tcat" width="40%"><strong>{$lang->mysubscriptions_title}</strong></td>
				<td class="tcat" width="15%" align="center"><strong>{$lang->mysubscriptions_usergroup}</strong></td>
				<td class="tcat" width="30%" align="center"><strong>{$lang->mysubscriptions_period}</strong></td>
				<td class="tcat" width="15%" align="center"><strong>{$lang->mysubscriptions_subscribe}</strong></td>
			</tr>
			{$subplans}
		</table>
		{$footer}
	</body>
</html>'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	$templatearray = array(
		"title" => 'mysubscriptions_row',
		"template" => $db->escape_string('
<tr>
<td class="{$bgcolor}" width="40%">{$sub[\'title\']}<br /><span class="smalltext">{$sub[\'description\']}<span></td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'usergroup\']}</td>
<td class="{$bgcolor}" width="30%" align="center">
	<select name="time_period" class="timeperiod">
		<option value="0">{$lang->mysubscriptions_select_time}</option>
		{$sub[\'time_period\']}
	</select>
</td>
<td class="{$bgcolor}" width="15%" align="center">{$sub[\'button\']}</td>
</tr>'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	$templatearray = array(
		"title" => 'mysubscriptions_row_empty',
		"template" => $db->escape_string('
<tr>
<td class="trow1" width="100%" colspan="5">{$lang->mysubscriptions_empty}</td>
</tr>'),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);

	$template = array(
		"title" => "mysubscriptions_nav",
		"template" => $db->escape_string('
<tr><td class="trow1 smalltext"><a href="usercp.php?action=mysubscriptions" class="usercp_nav_item usercp_nav_fsubscriptions">{$lang->mysubscriptions}</a></td></tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "mysubscriptions_usercp",
		"template" => $db->escape_string('
		<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->mysubscriptions}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
	<colgroup>
	<col style="width: 30%;" />
	</colgroup>
	<tr>
		<td colspan="3" class="thead"><strong>{$lang->mysubscriptions_active_plans}</strong></td>
	</tr>
	<tr>
		<td colspan="3" class="tcat"><strong>{$lang->mysubscriptions_active_plans_desc}</strong></td>
	</tr>
	<tr>
		<td class="tcat" width="40%"><strong>{$lang->mysubscriptions_title}</strong></td>
		<td class="tcat" width="30%"><strong>{$lang->mysubscriptions_processor}</strong></td>
		<td class="tcat" width="30%"><strong>{$lang->mysubscriptions_expires_on}</strong></td>
	</tr>
	{$rows}
</table>
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
		"title" => "mysubscriptions_usercp_row",
		"template" => $db->escape_string('
	<tr>
		<td class="{$bgcolor}">{$sub[\'title\']}</td>
		<td class="{$bgcolor}">{$sub[\'processor\']}</td>
		<td class="{$bgcolor}">{$sub[\'enddate\']}</td>
	</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	$template = array(
		"title" => "mysubscriptions_usercp_empty",
		"template" => $db->escape_string('
	<tr>
		<td class="trow1" colspan="3">{$lang->mysubscriptions_no_active_subs}</td>
	</tr>'),
		"sid" => "-1",
	);
	$db->insert_query("templates", $template);

	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('usercp_nav_misc', '#'.preg_quote('id="usercpmisc_e">').'#', 'id="usercpmisc_e">'.'{mysubscriptions_nav}');
}

function mysubscriptions_is_installed()
{
	global $db;
	if ($db->table_exists('mysubscriptions_subscriptions'))
		return true;
	return false;
}


function mysubscriptions_uninstall()
{
	global $db, $mybb;
	// delete settings group
	$db->delete_query("settinggroups", "name = 'mysubscriptions'");

	// remove settings
	$db->delete_query('settings', 'name LIKE \'%mysubscriptions%\'');

	rebuild_settings();

	if ($db->table_exists('mysubscriptions_subscriptions'))
		$db->drop_table('mysubscriptions_subscriptions');

	if ($db->table_exists('mysubscriptions_log'))
		$db->drop_table('mysubscriptions_log');

	if ($db->table_exists('mysubscriptions_coinpayments_log'))
		$db->drop_table('mysubscriptions_coinpayments_log');

	$db->delete_query('tasks', 'file=\'mysubscriptions\'');
}

function mysubscriptions_deactivate()
{
	global $db, $mybb;

	// delete templates
	$db->delete_query('templates', 'title LIKE \'%mysubscriptions%\'');

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('usercp_nav_misc', '#'.preg_quote('{mysubscriptions_nav}').'#', '', 0);
}

function mysubscriptions_get_grouptitle($gid)
{
	global $db;
	$query = $db->simple_select('usergroups', 'title', 'gid=\''.intval($gid).'\'', 1);
	return $db->fetch_field($query, 'title');
}

function mysubscriptions_get_usergroup($uid)
{
	global $db;
	$query = $db->simple_select('users', 'usergroup', 'uid=\''.intval($uid).'\'', 1);
	return $db->fetch_field($query, 'usergroup');
}

function mysubscriptions_get_username($uid)
{
	global $db;
	$query = $db->simple_select('users', 'username', 'uid=\''.intval($uid).'\'', 1);
	return $db->fetch_field($query, 'username');
}

function mysubscriptions_get_uid($username)
{
	global $db;
	$query = $db->simple_select('users', 'uid', 'username=\''.$db->escape_string($username).'\'', 1);
	return $db->fetch_field($query, 'uid');
}

/**
 * Checks if a user has permissions or not.
 *
 * @param array|string Allowed usergroups (if set to 'all', every user has access; if set to '' no one has)
 * @param array The user to be checked
 *
*/
function mysubscriptions_check_permissions($groups_comma, $user=null)
{
	global $mybb;

	if ($groups_comma == 'all' || $groups_comma == -1 || $groups_comma == '0')
		return true;

	if ($groups_comma == '')
		return false;

	$groups = explode(",", $groups_comma);

	if($user == null)
	{
		$user = $mybb->user;
	}

	$ourgroups = explode(",", $user['additionalgroups']);
	$ourgroups[] = $user['usergroup'];

	if(count(array_intersect($ourgroups, $groups)) == 0)
		return false;
	else
		return true;
}

function mysubscriptions_usercp_nav()
{
	global $lang, $usercpnav, $templates;
	$lang->load("mysubscriptions");

	eval("\$mysubscriptions = \"".$templates->get("mysubscriptions_nav")."\";");
	$usercpnav = str_replace('{mysubscriptions_nav}', $mysubscriptions, $usercpnav);
}

function mysubscriptions_usercp()
{
	global $headerinclude, $errors, $header, $footer, $theme, $templates, $mybb, $db, $lang, $usercpnav;

	if ($mybb->input['action'] != 'mysubscriptions') return;

	if($mybb->settings['mysubscriptions_paypal_email'] == '' && $mybb->settings['mysubscriptions_coinpayments_merchantid'] == '')
		return;

	$lang->load("mysubscriptions");

	add_breadcrumb($lang->mysubscriptions);

	$subscriptions = array();

	// Get our manual subscriptions
	$q = $db->query("
		SELECT s.title, l.*
		FROM `".TABLE_PREFIX."mysubscriptions_log` l
		LEFT JOIN `".TABLE_PREFIX."mysubscriptions_subscriptions` s ON (s.sid=l.sid)
		WHERE l.uid=".(int)$mybb->user['uid']." AND l.expired=0 AND l.enddate>0 AND l.memo='Manual Upgrade'
		ORDER BY l.enddate ASC
	");
	while($sub = $db->fetch_array($q))
	{
		$sub['processor'] = $lang->mysubscriptions_manually_upgraded;
		$subscriptions[$sub['enddate']] = $sub;
	}

	// Get our PayPal subscriptions
	if($mybb->settings['mysubscriptions_paypal_email'] != '')
	{
		$q = $db->query("
			SELECT s.title, l.*
			FROM `".TABLE_PREFIX."mysubscriptions_log` l
			LEFT JOIN `".TABLE_PREFIX."mysubscriptions_subscriptions` s ON (s.sid=l.sid)
			WHERE l.uid=".(int)$mybb->user['uid']." AND l.expired=0 AND l.enddate>0 AND l.memo!='Manual Upgrade'
			ORDER BY l.enddate ASC
		");
		while($sub = $db->fetch_array($q))
		{
			$sub['processor'] = 'PayPal';
			$subscriptions[$sub['enddate']] = $sub;
		}
	}

	if($mybb->settings['mysubscriptions_coinpayments_merchantid'] != '')
	{
		$q = $db->query("
			SELECT s.title, l.*
			FROM `".TABLE_PREFIX."mysubscriptions_coinpayments_log` l
			LEFT JOIN `".TABLE_PREFIX."mysubscriptions_subscriptions` s ON (s.sid=l.sid)
			WHERE l.uid=".(int)$mybb->user['uid']." AND l.expired=0 AND (l.status=100 || l.status=2)
			ORDER BY l.enddate ASC
		");
		while($sub = $db->fetch_array($q))
		{
			$sub['processor'] = 'CoinPayments';
			$subscriptions[$sub['enddate']] = $sub;
		}
	}

	$rows = '';
	if(!empty($subscriptions))
	{
		foreach($subscriptions as $sub)
		{
			$bgcolor = alt_trow();

			$sub['title'] = htmlspecialchars_uni($sub['title']);
			$sub['enddate'] = my_date($mybb->settings['dateformat'], $sub['enddate']).', '.my_date($mybb->settings['timeformat'], $sub['enddate']);

			eval("\$rows .= \"".$templates->get("mysubscriptions_usercp_row")."\";");
		}
	}

	if($rows == '')
	{
		eval("\$rows = \"".$templates->get('mysubscriptions_usercp_empty')."\";");
	}

	eval("\$page = \"".$templates->get("mysubscriptions_usercp")."\";");
	output_page($page);
}

/**
 * Get the user data of an user username.
 *
 * @param string The user username of the user.
 * @return array The users data
 */
function mysubscriptions_get_user_by_username($username, $options=array())
{
	global $mybb, $db;

	$username = $db->escape_string(my_strtolower($username));

	if(!isset($options['username_method']))
	{
		$options['username_method'] = 0;
	}

	switch($db->type)
	{
		case 'mysql':
		case 'mysqli':
			$field = 'username';
			$efield = 'email';
			break;
		default:
			$field = 'LOWER(username)';
			$efield = 'LOWER(email)';
			break;
	}

	switch($options['username_method'])
	{
		case 1:
			$sqlwhere = "{$efield}='{$username}'";
			break;
		case 2:
			$sqlwhere = "{$field}='{$username}' OR {$efield}='{$username}'";
			break;
		default:
			$sqlwhere = "{$field}='{$username}'";
			break;
	}

	$fields = array('uid');
	if(isset($options['fields']))
	{
		$fields = array_merge((array)$options['fields'], $fields);
	}

	$query = $db->simple_select('users', implode(',', array_unique($fields)), $sqlwhere, array('limit' => 1));

	if(isset($options['exists']))
	{
		return (bool)$db->num_rows($query);
	}

	return $db->fetch_array($query);
}

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function mysubscriptions_admin_user_menu(&$sub_menu)
{
	global $lang;

	$lang->load('mysubscriptions');
	$sub_menu[] = array('id' => 'mysubscriptions', 'title' => $lang->mysubscriptions_index, 'link' => 'index.php?module=user-mysubscriptions');
}

function mysubscriptions_admin_user_action_handler(&$actions)
{
	$actions['mysubscriptions'] = array('active' => 'mysubscriptions', 'file' => 'mysubscriptions');
}

function mysubscriptions_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	$lang->load("mysubscriptions", false, true);
	$admin_permissions['mysubscriptions'] = $lang->mysubscriptions_canmanage;

}

function mysubscriptions_messageredirect($message, $error=0, $action='')
{
  	global $db, $mybb, $lang;

	if (!$message)
		return;

	if ($action)
		$parameters = '&amp;action='.$action;

	if ($error)
	{
		flash_message($message, 'error');
		admin_redirect("index.php?module=user-mysubscriptions".$parameters);
	}
	else {
		flash_message($message, 'success');
		admin_redirect("index.php?module=user-mysubscriptions".$parameters);
	}
}

function mysubscriptions_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	$lang->load("mysubscriptions", false, true);

	if($run_module == 'user' && $action_file == 'mysubscriptions')
	{
		if ($mybb->request_method == "post")
		{
			switch ($mybb->input['action'])
			{
				case 'do_addsubscription':
					if ($mybb->input['title'] == '' || $mybb->input['description'] == '' || intval($mybb->input['group']) <= 0)
					{
						mysubscriptions_messageredirect($lang->mysubscriptions_missing_field, 1);
					}

					$title = $db->escape_string($mybb->input['title']);
					$description = $db->escape_string($mybb->input['description']);
					$message = $db->escape_string($mybb->input['message']);

					// Process time:period
					$accepted_tp = array();
					$time_period = explode("\n", $mybb->input['time_period']);
					$time_period = array_map('trim', $time_period);
					if(empty($time_period))
					{
						flash_message($lang->mysubscriptions_invalid_period, 'error');
						admin_redirect("index.php?module=user/mysubscriptions");
					}
					else
					{
						foreach($time_period as $tp)
						{
							$tp = explode(':', $tp);
							if(empty($tp))
							{
								flash_message($lang->mysubscriptions_invalid_period, 'error');
								admin_redirect("index.php?module=user/mysubscriptions");
							}

							if($tp[0] != 'Y' && $tp[0] != 'M' && $tp[0] != 'W' && $tp[0] != 'D')
							{
								flash_message($lang->mysubscriptions_invalid_period, 'error');
								admin_redirect("index.php?module=user/mysubscriptions");
							}

							if((int)$tp[1] < 0) // allow unlimited/lifetime if == 0
							{
								flash_message($lang->mysubscriptions_invalid_period, 'error');
								admin_redirect("index.php?module=user/mysubscriptions");
							}

							if((float)$tp[2] <= 0)
							{
								flash_message($lang->mysubscriptions_invalid_price, 'error');
								admin_redirect("index.php?module=user/mysubscriptions");
							}

							$accepted_tp[$tp[0]][] = array((int)$tp[1], (float)$tp[2]);
						}
					}
					if(empty($accepted_tp))
					{
						flash_message($lang->mysubscriptions_invalid_period, 'error');
						admin_redirect("index.php?module=user/mysubscriptions");
					}

					$group = intval($mybb->input['group']);
					$query = $db->simple_select('usergroups', 'title', 'gid=\''.intval($group).'\'');
					if (!$db->fetch_field($query, 'title'))
					{
						// invalid new group
						mysubscriptions_messageredirect($lang->mysubscriptions_invalid_group, 1);
					}

					// Get which groups can see it
					if(is_array($mybb->input['visible']))
					{
						if(in_array(0, $mybb->input['visible']))
							$visible = '0';
						else
							$visible = $db->escape_string(implode(",", $mybb->input['visible']));
					}
					else
					{
						$visible = '';
					}

					$maxactive = intval($mybb->input['maxactive']);
					if ($maxactive < 0)
						$maxactive = 0;

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

					$disporder = intval($mybb->input['disporder']);

					$insert_query = array('title' => $title, 'message' => $message, 'description' => $description, 'group' => $group, 'time_period' => my_serialize($accepted_tp), 'additional' => $additional, 'enabled' => $enabled, 'visible' => $visible, 'disporder' => $disporder, 'maxactive' => $maxactive, 'lockedsubs' => '');
					$db->insert_query('mysubscriptions_subscriptions', $insert_query);

					mysubscriptions_messageredirect($lang->mysubscriptions_sub_added);
				break;
				case 'do_editsubscription':
					$sid = intval($mybb->input['sid']);
					if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('mysubscriptions_subscriptions', '*', "sid = $sid")))))
					{
						mysubscriptions_messageredirect($lang->mysubscriptions_invalid_sub, 1);
					}

					if ($mybb->input['title'] == '' || $mybb->input['description'] == '' || intval($mybb->input['group']) <= 0)
					{
						mysubscriptions_messageredirect($lang->mysubscriptions_missing_field, 1);
					}

					$title = $db->escape_string($mybb->input['title']);
					$description = $db->escape_string($mybb->input['description']);
					$message = $db->escape_string($mybb->input['message']);

					// Process time:period
					$accepted_tp = array();
					$time_period = explode("\n", $mybb->input['time_period']);
					$time_period = array_map('trim', $time_period);
					if(empty($time_period))
					{
						flash_message($lang->mysubscriptions_invalid_period, 'error');
						admin_redirect("index.php?module=user/mysubscriptions");
					}
					else
					{
						foreach($time_period as $tp)
						{
							$tp = explode(':', $tp);
							if(empty($tp))
							{
								flash_message($lang->mysubscriptions_invalid_period, 'error');
								admin_redirect("index.php?module=user/mysubscriptions");
							}

							if($tp[0] != 'Y' && $tp[0] != 'M' && $tp[0] != 'W' && $tp[0] != 'D')
							{
								flash_message($lang->mysubscriptions_invalid_period, 'error');
								admin_redirect("index.php?module=user/mysubscriptions");
							}

							if((int)$tp[1] < 0) // allow unlimited/lifetime if == 0
							{
								flash_message($lang->mysubscriptions_invalid_period, 'error');
								admin_redirect("index.php?module=user/mysubscriptions");
							}

							if((float)$tp[2] <= 0)
							{
								flash_message($lang->mysubscriptions_invalid_price, 'error');
								admin_redirect("index.php?module=user/mysubscriptions");
							}

							$accepted_tp[$tp[0]][] = array((int)$tp[1], (float)$tp[2]);
						}
					}
					if(empty($accepted_tp))
					{
						flash_message($lang->mysubscriptions_invalid_period, 'error');
						admin_redirect("index.php?module=user/mysubscriptions");
					}

					$group = intval($mybb->input['group']);
					$query = $db->simple_select('usergroups', 'title', 'gid=\''.intval($group).'\'');
					if (!$db->fetch_field($query, 'title'))
					{
						// invalid new group
						mysubscriptions_messageredirect($lang->mysubscriptions_invalid_group, 1);
					}

					// Get which groups can see it
					if(is_array($mybb->input['visible']))
					{
						if(in_array(0, $mybb->input['visible']))
							$visible = '0';
						else
							$visible = $db->escape_string(implode(",", $mybb->input['visible']));
					}
					else
					{
						$visible = '';
					}

					$maxactive = intval($mybb->input['maxactive']);
					if ($maxactive < 0)
						$maxactive = 0;

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

					$disporder = intval($mybb->input['disporder']);

					$update_query = array('title' => $title, 'message' => $message, 'description' => $description, 'group' => $group, 'time_period' => my_serialize($accepted_tp), 'additional' => $additional, 'enabled' => $enabled, 'visible' => $visible, 'disporder' => $disporder, 'maxactive' => $maxactive);
					$db->update_query('mysubscriptions_subscriptions', $update_query, 'sid=\''.intval($sub['sid']).'\'');

					mysubscriptions_messageredirect($lang->mysubscriptions_sub_edited);
				break;
				case 'do_edituser':
					$lid = intval($mybb->input['lid']);
					if ($lid <= 0 || (!($log = $db->fetch_array($db->simple_select('mysubscriptions_log', '*', "lid = $lid AND enddate!=0")))))
					{
						mysubscriptions_messageredirect($lang->mysubscriptions_invalid_sub, 1);
					}

					if($mybb->input['p'] != 'pp' && $mybb->input['p'] != 'cp')
						mysubscriptions_messageredirect($lang->mysubscriptions_invalid_processor, 1);

					$enddate = strtotime($mybb->input['enddate']);

					$update_query = array('enddate' => $enddate);

					if($mybb->input['p'] == 'pp')
						$db->update_query('mysubscriptions_log', $update_query, 'lid=\''.intval($lid).'\'');
					else
						$db->update_query('mysubscriptions_coinpayments_log', $update_query, 'lid=\''.intval($lid).'\'');

					mysubscriptions_messageredirect($lang->mysubscriptions_user_edited);
				break;
			}
		}

		if ($mybb->input['action'] == 'do_deletesubscription')
		{
			$page->add_breadcrumb_item($lang->mysubscriptions, 'index.php?module=user-mysubscriptions');
			$page->output_header($lang->mysubscriptions);

			$sid = intval($mybb->input['sid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=user-mysubscriptions");
			}

			if($mybb->request_method == "post")
			{
				if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('mysubscriptions_subscriptions', 'sid', "sid = $sid")))))
				{
					mysubscriptions_messageredirect($lang->mysubscriptions_invalid_sub, 1);
				}

				// delete subscription plan
				$db->delete_query('mysubscriptions_subscriptions', "sid = $sid");

				// well, we won't delete the logs for this subscription, otherwise users won't get unsubscribed

				mysubscriptions_messageredirect($lang->mysubscriptions_sub_deleted);
			}
			else
			{
				$mybb->input['sid'] = intval($mybb->input['sid']);
				$form = new Form("index.php?module=user-mysubscriptions&amp;action=do_deletesubscription&amp;sid={$mybb->input['sid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->mysubscriptions_confirm_deletesub}</p>\n";
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
		elseif ($mybb->input['action'] == 'upgrade')
		{
			$page->add_breadcrumb_item($lang->mysubscriptions, 'index.php?module=user-mysubscriptions');
			$page->output_header($lang->mysubscriptions);

			$sid = intval($mybb->input['sid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=user-mysubscriptions");
			}

			if($mybb->request_method == "post")
			{
				if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('mysubscriptions_subscriptions', '*', "sid = $sid")))))
				{
					mysubscriptions_messageredirect($lang->mysubscriptions_invalid_sub, 1);
				}

				// Now check the user. If uid=1 or admin we can't do this
				$user = mysubscriptions_get_user_by_username($mybb->input['username'], array('fields' => array('uid', 'username', 'usergroup', 'email')));
				if(empty($user))
				{
					mysubscriptions_messageredirect($lang->mysubscriptions_invalid_username, 1);
				}

				if($user['usergroup'] == 4 || $user['uid'] == 1)
				{
					mysubscriptions_messageredirect($lang->mysubscriptions_invalid_username, 1);
				}

				$sub['additional'] = (int)$sub['additional'];
				$sub['sid'] = (int)$sub['sid'];
				$user['usergroup'] = (int)$user['usergroup'];
				$user['uid'] = (int)$user['uid'];
				$user['username'] = $db->escape_string($user['username']);
				$user['email'] = $db->escape_string($user['email']);

				// Validate selected time/period
				if($mybb->input['time'] != 'Y' && $mybb->input['time'] != 'M' && $mybb->input['time'] != 'W' && $mybb->input['time'] != 'D')
				{
					flash_message($lang->mysubscriptions_invalid_period, 'error');
					admin_redirect("index.php?module=user/mysubscriptions");
				}

				// Calculate ending period
				$mybb->input['period'] = (int)$mybb->input['period'];
				if($mybb->input['period'] != 0)
				{
					switch($mybb->input['time'])
					{
						case 'D':
							$enddate = TIME_NOW + (24*60*60*$mybb->input['period']);
						break;
						case 'W':
							$enddate = TIME_NOW + (7*24*60*60*$mybb->input['period']);
						break;
						case 'M':
							$enddate = TIME_NOW + (30*24*60*60*$mybb->input['period']);
						break;
						case 'Y':
							$enddate = TIME_NOW + (365*24*60*60*$mybb->input['period']);
						break;
						default:
							// Some problem!
							$enddate = 0;
						break;
					}
				}
				else
				{
					$enddate = 0;
				}

				// Upgrade
				$db->insert_query('mysubscriptions_log', array(
					'uname' => $user['username'],
					'uid' => (int)$user['uid'],
					'additional' => (int)$sub['additional'],
					'sid' => (int)$sub['sid'],
					'endgroup' => (int)$user['usergroup'],
					'receiver_email' => $user['email'],
					'business' => $user['email'],
					'item_name' => $sub['title'],
					'item_number' => (int)$sub['sid'],
					'quantity' => 1,
					'mc_gross' => 0,
					'memo' => 'Manual Upgrade',
					'timestamp' => TIME_NOW,
					'enddate' => $enddate,
					'expired' => 0,
					'payment_date' => my_date('Y-m-d H:i:s', TIME_NOW)
				));

				// Move user
				if ($sub['additional'])
					join_usergroup($user['uid'], $sub['group']);
				else
					$db->update_query('users', array('usergroup' => $sub['group']), 'uid=\''.$user['uid'].'\'');

				// Send PM
				send_pm(array('receivepms' => 1, 'subject' => $lang->mysubscriptions_pm_upgraded_subject, 'message' => $lang->sprintf($lang->mysubscriptions_pm_upgraded_message, $mybb->user['username'], $sub['title']), 'touid' => $user['uid']), 1);

				mysubscriptions_messageredirect($lang->mysubscriptions_upgraded);
			}
			else
			{
				$form = new Form("index.php?module=user-mysubscriptions&amp;action=upgrade&amp;sid={$mybb->input['sid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->mysubscriptions_confirm_give}</p>\n";
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
		elseif ($mybb->input['action'] == 'extend')
		{
			$page->add_breadcrumb_item($lang->mysubscriptions, 'index.php?module=user-mysubscriptions');
			$page->output_header($lang->mysubscriptions);

			$sid = intval($mybb->input['sid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=user-mysubscriptions");
			}

			if($mybb->request_method == "post")
			{
				if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('mysubscriptions_subscriptions', '*', "sid = $sid")))))
				{
					mysubscriptions_messageredirect($lang->mysubscriptions_invalid_sub, 1);
				}

				$days = (int)$mybb->input['days'];
				if($days <= 0)
				{
					mysubscriptions_messageredirect($lang->mysubscriptions_invalid_days, 1);
				}

				$db->query("UPDATE ".TABLE_PREFIX."mysubscriptions_log SET enddate=enddate+".($days*24*60*60)." WHERE expired=0 AND sid=".$sid);
				$db->query("UPDATE ".TABLE_PREFIX."mysubscriptions_coinpayments_log SET enddate=enddate+".($days*24*60*60)." WHERE expired=0 AND sid=".$sid);

				mysubscriptions_messageredirect($lang->mysubscriptions_extended, 0, 'active');
			}
			else
			{
				$form = new Form("index.php?module=user-mysubscriptions&amp;action=extend&amp;my_post_key={$mybb->post_code}", 'post');
				echo $form->generate_hidden_field('sid', (int)$mybb->input['sid']);
				echo $form->generate_hidden_field('days', (int)$mybb->input['days']);
				echo "<div class=\"confirm_action\">\n";
				echo "<p>".$lang->sprintf($lang->mysubscriptions_confirm_extend, (int)$mybb->input['days'])."</p>\n";
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
		elseif ($mybb->input['action'] == 'freeze')
		{
			$page->add_breadcrumb_item($lang->mysubscriptions, 'index.php?module=user-mysubscriptions');
			$page->output_header($lang->mysubscriptions);

			$sid = intval($mybb->input['sid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=user-mysubscriptions");
			}

			if($mybb->request_method == "post")
			{
				if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('mysubscriptions_subscriptions', '*', "sid = $sid")))))
				{
					mysubscriptions_messageredirect($lang->mysubscriptions_invalid_sub, 1);
				}

				if($mybb->input['freeze'] != '')
				{
					// Freeze!
					$db->query("UPDATE ".TABLE_PREFIX."mysubscriptions_log SET frozen=".TIME_NOW." WHERE frozen=0 AND expired=0 AND sid=".$sid);
					$db->query("UPDATE ".TABLE_PREFIX."mysubscriptions_coinpayments_log SET frozen=".TIME_NOW." WHERE frozen=0 AND expired=0 AND sid=".$sid);

					mysubscriptions_messageredirect($lang->mysubscriptions_frozen, 0, 'active');
				}
				else
				{
					// Unfreeze!
					$db->query("UPDATE ".TABLE_PREFIX."mysubscriptions_log SET enddate=(".TIME_NOW."+(enddate-frozen)), frozen=0 WHERE frozen!=0 AND expired=0 AND sid=".$sid);
					$db->query("UPDATE ".TABLE_PREFIX."mysubscriptions_coinpayments_log SET enddate=(".TIME_NOW."+(enddate-frozen)), frozen=0 WHERE frozen!=0 AND expired=0 AND sid=".$sid);

					mysubscriptions_messageredirect($lang->mysubscriptions_unfrozen, 0, 'active');
				}


			}
			else
			{
				$form = new Form("index.php?module=user-mysubscriptions&amp;action=extend&amp;my_post_key={$mybb->post_code}", 'post');
				echo $form->generate_hidden_field('sid', (int)$mybb->input['sid']);
				echo $form->generate_hidden_field('days', (int)$mybb->input['days']);
				echo "<div class=\"confirm_action\">\n";
				echo "<p>".$lang->sprintf($lang->mysubscriptions_confirm_extend, (int)$mybb->input['days'])."</p>\n";
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

		$page->add_breadcrumb_item($lang->mysubscriptions, 'index.php?module=user-mysubscriptions');

		$page->output_header($lang->mysubscriptions);

		$sub_tabs['mysubscriptions'] = array(
			'title'			=> $lang->mysubscriptions_plans,
			'link'			=> 'index.php?module=user-mysubscriptions',
			'description'	=> $lang->mysubscriptions_desc
		);

		$sub_tabs['mysubscriptions_add'] = array(
			'title'			=> $lang->mysubscriptions_add,
			'link'			=> 'index.php?module=user-mysubscriptions&amp;action=addsubscription',
			'description'	=> $lang->mysubscriptions_add_desc
		);

		$sub_tabs['mysubscriptions_log'] = array(
			'title'			=> $lang->mysubscriptions_log,
			'link'			=> 'index.php?module=user-mysubscriptions&amp;action=log',
			'description'	=> $lang->mysubscriptions_log_desc
		);

		$sub_tabs['mysubscriptions_coinpayments_log'] = array(
			'title'			=> $lang->mysubscriptions_coinpayments_log,
			'link'			=> 'index.php?module=user-mysubscriptions&amp;action=coinpayments_log',
			'description'	=> $lang->mysubscriptions_coinpayments_log_desc
		);

		$sub_tabs['mysubscriptions_oneoff'] = array(
			'title'			=> $lang->mysubscriptions_oneofflogs,
			'link'			=> 'index.php?module=user-mysubscriptions&amp;action=oneoff',
			'description'	=> $lang->mysubscriptions_oneofflogs_desc
		);

		$sub_tabs['mysubscriptions_coinpayments_oneoff'] = array(
			'title'			=> $lang->mysubscriptions_coinpayments_oneofflogs,
			'link'			=> 'index.php?module=user-mysubscriptions&amp;action=coinpayments_oneoff',
			'description'	=> $lang->mysubscriptions_coinpayments_oneofflogs_desc
		);

		$sub_tabs['mysubscriptions_active_subscriptions'] = array(
			'title'			=> $lang->mysubscriptions_active_subscriptions,
			'link'			=> 'index.php?module=user-mysubscriptions&amp;action=active',
			'description'	=> $lang->mysubscriptions_active_subscriptions_desc
		);

		if (!$mybb->input['action'])
		{
			$page->output_nav_tabs($sub_tabs, 'mysubscriptions');

			$query = $db->simple_select("mysubscriptions_subscriptions", "sid,title", '', array('order_by' => 'title'));
			$plans = array(0 => $lang->mysubscriptions_select_plan);
			while($plan = $db->fetch_array($query))
			{
				$plans[$plan['sid']] = $plan['title'];
			}

			$form = new Form("index.php?module=user-mysubscriptions&amp;action=upgrade", "post", "mysubscriptions");
			$form_container = new FormContainer($lang->mysubscriptions_upgrade_user);
			$form_container->output_row($lang->mysubscriptions_username, '', $form->generate_text_box('username'));
			$form_container->output_row($lang->mysubscriptions_plan, '', $form->generate_select_box('sid', $plans));

			$times = array();
			$times[0] = $lang->mysubscriptions_select_time;
			$times['Y'] = $lang->mysubscriptions_years;
			$times['M'] = $lang->mysubscriptions_months;
			$times['W'] = $lang->mysubscriptions_weeks;
			$times['D'] = $lang->mysubscriptions_days;

			$form_container->output_row($lang->mysubscriptions_time, '', $form->generate_select_box('time', $times));
			$form_container->output_row($lang->mysubscriptions_period, '', $form->generate_text_box('period'));
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->mysubscriptions_upgrade_user);
			$form->output_submit_wrapper($buttons);
			$form->end();

			echo "<br />";

			$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$groups[$usergroup['gid']] = $usergroup['title'];
			}

			// table
			$table = new Table;
			$table->construct_header($lang->mysubscriptions_title, array('width' => '20%'));
			$table->construct_header($lang->mysubscriptions_period, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_group, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_disporder, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_action, array('width' => '20%', 'class' => 'align_center'));

			$query = $db->simple_select('mysubscriptions_subscriptions', '*', '', array('order_by' => 'title', 'order_dir' => 'ASC'));
			while ($sub = $db->fetch_array($query))
			{
				if($sub['enabled'] == 1)
				{
					$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_on.png\" alt=\"({$lang->mysubscriptions_alt_enabled})\" title=\"{$lang->mysubscriptions_alt_enabled}\"  style=\"vertical-align: middle;\" /> ";
				}
				else
				{
					$icon = "<img src=\"styles/{$page->style}/images/icons/bullet_off.png\" alt=\"({$lang->mysubscriptions_alt_disabled})\" title=\"{$lang->mysubscriptions_alt_disabled}\"  style=\"vertical-align: middle;\" /> ";
				}

				// Display time/period/price available options here

				$time_period = '';
				$tp_array = my_unserialize($sub['time_period']);
				if(!empty($tp_array))
				{
					$nl = '';
					foreach($tp_array as $t => $tp)
					{
						foreach($tp as $p)
						{
							$sub['time'] = '';
							switch ($t)
							{
								case "Y": // years
									if ($p[0] > 1)
										$sub['time'] = $lang->mysubscriptions_years;
									elseif($p[0] == 0)
										$sub['time'] = $lang->mysubscriptions_unlimited;
									else
										$sub['time'] = $lang->mysubscriptions_year;
								break;

								case "M": // months
									if ($p[0] > 1)
										$sub['time'] = $lang->mysubscriptions_months;
									elseif($p[0] == 0)
										$sub['time'] = $lang->mysubscriptions_unlimited;
									else
										$sub['time'] = $lang->mysubscriptions_month;
								break;

								case "W": // years
									if ($p[0] > 1)
										$sub['time'] = $lang->mysubscriptions_weeks;
									elseif($p[0] == 0)
										$sub['time'] = $lang->mysubscriptions_unlimited;
									else
										$sub['time'] = $lang->mysubscriptions_week;
								break;

								case "D": // days
									if ($p[0] > 1)
										$sub['time'] = $lang->mysubscriptions_days;
									elseif($p[0] == 0)
										$sub['time'] = $lang->mysubscriptions_unlimited;
									else
										$sub['time'] = $lang->mysubscriptions_day;
								break;
							}

							$time_period .= (int)$p[0].' '.$sub['time'].' - '.number_format($p[1], 2).' '.$mybb->settings['mysubscriptions_paypal_currency'].'<br />';
						}
					}
				}

				if ($sub['additional'])
					$table->construct_cell("<div>{$icon}".$sub['title']." ".$lang->mysubscriptions_additional_title."</div>");
				else
					$table->construct_cell("<div>{$icon}".$sub['title']."</div>");

				// build time
				$table->construct_cell($time_period); // period of time
				$table->construct_cell(htmlspecialchars_uni($groups[$sub['group']]), array('class' => 'align_center'));
				$table->construct_cell(intval($sub['disporder']), array('class' => 'align_center'));
				// actions column
				$table->construct_cell("<a href=\"index.php?module=user-mysubscriptions&amp;action=editsubscription&amp;sid=".intval($sub['sid'])."\">".$lang->mysubscriptions_edit."</a> - <a href=\"index.php?module=user-mysubscriptions&amp;action=do_deletesubscription&amp;sid=".intval($sub['sid'])."\">".$lang->mysubscriptions_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->mysubscriptions_no_subs, array('colspan' => 6));
				$table->construct_row();
			}

			$table->output($lang->mysubscriptions_plans);
		}
		elseif ($mybb->input['action'] == 'oneoff')
		{
			$page->output_nav_tabs($sub_tabs, 'mysubscriptions_oneoff');

			// Do we have date range criteria?
			if($mybb->input['from_year'])
			{
				$start_dateline = mktime(0, 0, 0, intval($mybb->input['from_month']), intval($mybb->input['from_day']), intval($mybb->input['from_year']));
				$end_dateline = mktime(23, 59, 59, intval($mybb->input['to_month']), intval($mybb->input['to_day']), intval($mybb->input['to_year']));
				$range = "&amp;start={$start_dateline}&amp;end={$end_dateline}";
			}

			// Otherwise default to the last 30 days
			if(!$mybb->input['from_year'] || $start_dateline > TIME_NOW || $end_dateline > mktime(23, 59, 59))
			{
				$start_dateline = TIME_NOW-(60*60*24*30);
				$end_dateline = TIME_NOW;

				list($mybb->input['from_day'], $mybb->input['from_month'], $mybb->input['from_year']) = explode('-', date('j-n-Y', $start_dateline));
				list($mybb->input['to_day'], $mybb->input['to_month'], $mybb->input['to_year']) = explode('-', date('j-n-Y', $end_dateline));

				$range = "&amp;start={$start_dateline}&amp;end={$end_dateline}";
			}

			/*if(!isset($mybb->input['limit']))
				$mybb->input['limit'] = 200;
			else
				$mybb->input['limit'] = abs((int)$mybb->input['limit']);*/

			// We have entered usergroups? Format the variable correctly
			if(!empty($mybb->input['usergroups']))
			{
				$chosen_groups = " AND s.group IN (".$db->escape_string($mybb->input['usergroups']).")";
			}
			else
				$chosen_groups = '';

			// Date range fields
			$form = new Form("index.php?module=user-mysubscriptions&amp;action=oneoff", "post", "overall");
			echo "<fieldset><legend>{$lang->mysubscriptions_options}</legend>\n";
			echo "{$lang->mysubscriptions_from}: ".$form->generate_date_select('from', $mybb->input['from_day'], $mybb->input['from_month'], $mybb->input['from_year']);
			echo " {$lang->mysubscriptions_to}: ".$form->generate_date_select('to', $mybb->input['to_day'], $mybb->input['to_month'], $mybb->input['to_year']);
			echo "<br /><br />{$lang->mysubscriptions_enter_usergroups}: ".$form->generate_text_box('usergroups', $mybb->input['usergroups']);
			echo "<br /><br />{$lang->mysubscriptions_sort_username}: ".$form->generate_check_box('sortuser', 1);
			echo "<br /><br />".$form->generate_submit_button($lang->mysubscriptions_view);
			echo "</fieldset>\n";
			$form->end();

			// table
			$table = new Table;
			$table->construct_header($lang->mysubscriptions_user, array('width' => '25%'));
			$table->construct_header($lang->mysubscriptions_subscription, array('width' => '25%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_startdate, array('width' => '25%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_expiredate, array('width' => '25%', 'class' => 'align_center'));

			if($mybb->input['sortuser'])
			{
				$sortsql = 'ORDER BY l.uname ASC';
			}
			else
				$sortsql = 'ORDER BY l.timestamp';

			$recurring = array();

			$plans = array();

			$total = 0;
			$query = $db->query("
				SELECT s.*, l.*
				FROM ".TABLE_PREFIX."mysubscriptions_log l
				LEFT JOIN ".TABLE_PREFIX."mysubscriptions_subscriptions s ON (l.item_number=s.sid)
				LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
				WHERE l.timestamp>'".$start_dateline."' AND l.timestamp < '".$end_dateline."' AND l.enddate!=0 {$chosen_groups} AND l.payment_status='Completed'
				{$sortsql}
			");
			while ($sub = $db->fetch_array($query))
			{
				if(isset($recurring[$sub['uid']]))
				{
					$options = array('style' => 'background-color: #99FF66');
				}
				else
					$options = array();

				$recurring[$sub['uid']] = $sub['uid'];

				$table->construct_cell(build_profile_link(htmlspecialchars_uni($sub['uname']), $sub['uid']), $options);

				if ($sub['additional'])
					$table->construct_cell($sub['title']." ".$lang->mysubscriptions_additional_title, $options);
				else
					$table->construct_cell($sub['title'], $options);

				$sub['start'] = my_date($mybb->settings['dateformat'], $sub['timestamp']);
				$sub['start'] .= ', '.my_date($mybb->settings['timeformat'], $sub['timestamp']);

				$table->construct_cell($sub['start'], $options);

				$sub['end'] = my_date($mybb->settings['dateformat'], $sub['enddate']);
				$sub['end'] .= ', '.my_date($mybb->settings['timeformat'], $sub['enddate']);

				$table->construct_cell($sub['end'], $options);

				$total++;

				$plans[$sub['sid']]['title'] = $sub['title'];
				$plans[$sub['sid']]['price'] += (float)$sub['mc_gross'];
				$plans[$sub['sid']]['total']++;

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->mysubscriptions_no_subscribers, array('colspan' => 4));
				$table->construct_row();
			}

			$table->output($lang->mysubscriptions_subscribers." (".$total.")");

			echo "<br />";

			// table
			$table = new Table;

			$table->construct_header($lang->mysubscriptions_plan, array('width' => '20%'));
			$table->construct_header($lang->mysubscriptions_subscriptions, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_price, array('width' => '20%', 'class' => 'align_center'));

			$total = 0;
			$price = 0;

			if(!empty($plans))
			{
				foreach($plans as $plan)
				{
					$table->construct_cell($plan['title']);
					$table->construct_cell($plan['total'], array('class' => 'align_center'));
					$table->construct_cell($plan['price'].' '.$mybb->settings['mysubscriptions_paypal_currency'], array('class' => 'align_center'));

					$total += $plan['total'];
					$price += $plan['price'];

					$table->construct_row();
				}
			}

			$table->construct_cell('<strong>'.$lang->mysubscriptions_total.'</strong>');
			$table->construct_cell($total, array('class' => 'align_center'));
			$table->construct_cell($price.' '.$mybb->settings['mysubscriptions_paypal_currency'], array('class' => 'align_center'));
			$table->construct_row();

			$table->output($lang->mysubscriptions_stats);
		}
		elseif ($mybb->input['action'] == 'coinpayments_oneoff')
		{
			$page->output_nav_tabs($sub_tabs, 'mysubscriptions_coinpayments_oneoff');

			// Do we have date range criteria?
			if($mybb->input['from_year'])
			{
				$start_dateline = mktime(0, 0, 0, intval($mybb->input['from_month']), intval($mybb->input['from_day']), intval($mybb->input['from_year']));
				$end_dateline = mktime(23, 59, 59, intval($mybb->input['to_month']), intval($mybb->input['to_day']), intval($mybb->input['to_year']));
				$range = "&amp;start={$start_dateline}&amp;end={$end_dateline}";
			}

			// Otherwise default to the last 30 days
			if(!$mybb->input['from_year'] || $start_dateline > TIME_NOW || $end_dateline > mktime(23, 59, 59))
			{
				$start_dateline = TIME_NOW-(60*60*24*30);
				$end_dateline = TIME_NOW;

				list($mybb->input['from_day'], $mybb->input['from_month'], $mybb->input['from_year']) = explode('-', date('j-n-Y', $start_dateline));
				list($mybb->input['to_day'], $mybb->input['to_month'], $mybb->input['to_year']) = explode('-', date('j-n-Y', $end_dateline));

				$range = "&amp;start={$start_dateline}&amp;end={$end_dateline}";
			}

			/*if(!isset($mybb->input['limit']))
				$mybb->input['limit'] = 200;
			else
				$mybb->input['limit'] = abs((int)$mybb->input['limit']);*/

			// We have entered usergroups? Format the variable correctly
			if(!empty($mybb->input['usergroups']))
			{
				$chosen_groups = " AND s.group IN (".$db->escape_string($mybb->input['usergroups']).")";
			}
			else
				$chosen_groups = '';

			// Date range fields
			$form = new Form("index.php?module=user-mysubscriptions&amp;action=coinpayments_oneoff", "post", "overall");
			echo "<fieldset><legend>{$lang->mysubscriptions_options}</legend>\n";
			echo "{$lang->mysubscriptions_from}: ".$form->generate_date_select('from', $mybb->input['from_day'], $mybb->input['from_month'], $mybb->input['from_year']);
			echo " {$lang->mysubscriptions_to}: ".$form->generate_date_select('to', $mybb->input['to_day'], $mybb->input['to_month'], $mybb->input['to_year']);
			echo "<br /><br />{$lang->mysubscriptions_enter_usergroups}: ".$form->generate_text_box('usergroups', $mybb->input['usergroups']);
			echo "<br /><br />{$lang->mysubscriptions_sort_username}: ".$form->generate_check_box('sortuser', 1);
			echo "<br /><br />".$form->generate_submit_button($lang->mysubscriptions_view);
			echo "</fieldset>\n";
			$form->end();

			// table
			$table = new Table;
			$table->construct_header($lang->mysubscriptions_user, array('width' => '25%'));
			$table->construct_header($lang->mysubscriptions_subscription, array('width' => '25%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_startdate, array('width' => '25%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_expiredate, array('width' => '25%', 'class' => 'align_center'));

			if($mybb->input['sortuser'])
			{
				$sortsql = 'ORDER BY l.uname ASC';
			}
			else
				$sortsql = 'ORDER BY l.timestamp';

			$recurring = array();

			$plans = array();

			$total = 0;
			$query = $db->query("
				SELECT s.*, l.*
				FROM ".TABLE_PREFIX."mysubscriptions_coinpayments_log l
				LEFT JOIN ".TABLE_PREFIX."mysubscriptions_subscriptions s ON (l.item_number=s.sid)
				LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
				WHERE l.txn_id!='' AND l.timestamp>'".$start_dateline."' AND l.timestamp < '".$end_dateline."' AND l.enddate!=0 {$chosen_groups} AND (l.status=100 || l.status=2)
				{$sortsql}
			");
			while ($sub = $db->fetch_array($query))
			{
				if(isset($recurring[$sub['uid']]))
				{
					$options = array('style' => 'background-color: #99FF66');
				}
				else
					$options = array();

				$recurring[$sub['uid']] = $sub['uid'];

				$table->construct_cell(build_profile_link(htmlspecialchars_uni($sub['uname']), $sub['uid']), $options);

				if($sub['expired'])
					$sub['title'] .= ' ('.$lang->mysubscriptions_expired.')';

				if ($sub['additional'])
					$table->construct_cell($sub['title']." ".$lang->mysubscriptions_additional_title, $options);
				else
					$table->construct_cell($sub['title'], $options);

				$sub['start'] = my_date($mybb->settings['dateformat'], $sub['timestamp']);
				$sub['start'] .= ', '.my_date($mybb->settings['timeformat'], $sub['timestamp']);

				$table->construct_cell($sub['start'], $options);

				$sub['end'] = my_date($mybb->settings['dateformat'], $sub['enddate']);
				$sub['end'] .= ', '.my_date($mybb->settings['timeformat'], $sub['enddate']);

				$table->construct_cell($sub['end'], $options);

				$total++;

				$plans[$sub['sid']]['title'] = $sub['title'];
				$plans[$sub['sid']]['price'] += (float)$sub['amount1'];
				$plans[$sub['sid']]['total']++;

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->mysubscriptions_no_subscribers, array('colspan' => 4));
				$table->construct_row();
			}

			$table->output($lang->mysubscriptions_subscribers." (".$total.")");

			echo "<br />";

			// table
			$table = new Table;

			$table->construct_header($lang->mysubscriptions_plan, array('width' => '20%'));
			$table->construct_header($lang->mysubscriptions_subscriptions, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_price, array('width' => '20%', 'class' => 'align_center'));

			$total = 0;
			$price = 0;

			if(!empty($plans))
			{
				foreach($plans as $plan)
				{
					$table->construct_cell($plan['title']);
					$table->construct_cell($plan['total'], array('class' => 'align_center'));
					$table->construct_cell($plan['price'].' '.$mybb->settings['mysubscriptions_coinpayments_currency'], array('class' => 'align_center'));

					$total += $plan['total'];
					$price += $plan['price'];

					$table->construct_row();
				}
			}

			$table->construct_cell('<strong>'.$lang->mysubscriptions_total.'</strong>');
			$table->construct_cell($total, array('class' => 'align_center'));
			$table->construct_cell($price.' '.$mybb->settings['mysubscriptions_coinpayments_currency'], array('class' => 'align_center'));
			$table->construct_row();

			$table->output($lang->mysubscriptions_stats);
		}
		elseif ($mybb->input['action'] == 'addsubscription')
		{
			$page->output_nav_tabs($sub_tabs, 'mysubscriptions_add');

			$groups[0] = $lang->mysubscriptions_select_group;

			// get groups
			$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
			while($group = $db->fetch_array($query, 'title, gid'))
			{
				$groups[$group['gid']] = $group['title'];
			}

			// Usergroups
			$usergroups = array();
			$usergroups[0] = $lang->mysubscriptions_all_groups;
			$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$usergroups[$usergroup['gid']] = $usergroup['title'];
			}

			$form = new Form("index.php?module=user-mysubscriptions&amp;action=do_addsubscription", "post", "mysubscriptions");

			$form_container = new FormContainer($lang->mysubscriptions_addsubscription);
			$form_container->output_row($lang->mysubscriptions_title."<em>*</em>", $lang->mysubscriptions_title_desc, $form->generate_text_box('title', '', array('id' => 'title')), 'title');
			$form_container->output_row($lang->mysubscriptions_description."<em>*</em>", $lang->mysubscriptions_description_desc, $form->generate_text_area('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->mysubscriptions_message, $lang->mysubscriptions_message_desc, $form->generate_text_area('message', '', array('id' => 'message')), 'message');
			$form_container->output_row($lang->mysubscriptions_group."<em>*</em>", $lang->mysubscriptions_group_desc, $form->generate_select_box('group', $groups, '', array('id' => 'group')), 'group');
			$form_container->output_row($lang->mysubscriptions_disporder."<em>*</em>", $lang->mysubscriptions_disporder_desc, $form->generate_text_box('disporder', '0', array('id' => 'disporder')), 'disporder');
			$form_container->output_row($lang->mysubscriptions_time_period."<em>*</em>", $lang->mysubscriptions_time_period_desc, $form->generate_text_area('time_period', '', array('id' => 'time_period')), 'time_period');
			$form_container->output_row($lang->mysubscriptions_maxactive, $lang->mysubscriptions_maxactive_desc, $form->generate_text_box('maxactive', '0'));
			$form_container->output_row($lang->mysubscriptions_additional, $lang->mysubscriptions_additional_desc, $form->generate_check_box('additional', '1', '', array('id' => 'additional','checked' => false)), 'additional');
			$form_container->output_row($lang->mysubscriptions_enabled, $lang->mysubscriptions_enabled_desc, $form->generate_check_box('enabled', '1', '', array('id' => 'enabled','checked' => false)), 'enabled');
			$form_container->output_row($lang->mysubscriptions_user_groups, $lang->mysubscriptions_user_groups_desc, $form->generate_select_box('visible[]', $usergroups, '', array('id' => 'visible', 'multiple' => true, 'size' => 5)), 'visible');
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->mysubscriptions_submit);
			$buttons[] = $form->generate_reset_button($lang->mysubscriptions_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'editsubscription')
		{
			$page->output_nav_tabs($sub_tabs, 'mysubscriptions');

			$sid = intval($mybb->input['sid']);
			if ($sid <= 0 || (!($sub = $db->fetch_array($db->simple_select('mysubscriptions_subscriptions', '*', "sid = $sid")))))
			{
				mysubscriptions_messageredirect($lang->mysubscriptions_invalid_sub, 1);
			}

			// Time/Periods
			$time_period = '';
			$tp_array = my_unserialize($sub['time_period']);
			if(!empty($tp_array))
			{
				$nl = '';
				foreach($tp_array as $t => $tp)
				{
					foreach($tp as $p)
					{
						$time_period .= $nl.$t.':'.(int)$p[0].':'.(float)$p[1];
						$nl = "\n";
					}
				}
			}

			$groups[0] = $lang->mysubscriptions_select_group;

			// get groups
			$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
			while($group = $db->fetch_array($query, 'title, gid'))
			{
				$groups[$group['gid']] = $group['title'];
			}

			// Usergroups
			$usergroups = array();
			$usergroups[0] = $lang->mysubscriptions_all_groups;
			$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
			while($usergroup = $db->fetch_array($query))
			{
				$usergroups[$usergroup['gid']] = $usergroup['title'];
			}

			if($sub['visible'] != '')
				$sub['visible'] = explode(',', $sub['visible']);

			$form = new Form("index.php?module=user-mysubscriptions&amp;action=do_editsubscription", "post", "mysubscriptions");

			echo $form->generate_hidden_field('sid', $sub['sid']);

			$form_container = new FormContainer($lang->mysubscriptions_editsubscription);
			$form_container->output_row($lang->mysubscriptions_title."<em>*</em>", $lang->mysubscriptions_title_desc, $form->generate_text_box('title', $sub['title'], array('id' => 'title')), 'title');
			$form_container->output_row($lang->mysubscriptions_description."<em>*</em>", $lang->mysubscriptions_description_desc, $form->generate_text_area('description', $sub['description'], array('id' => 'description')), 'description');
			$form_container->output_row($lang->mysubscriptions_message, $lang->mysubscriptions_message_desc, $form->generate_text_area('message', $sub['message'], array('id' => 'message')), 'message');
			$form_container->output_row($lang->mysubscriptions_group."<em>*</em>", $lang->mysubscriptions_group_desc, $form->generate_select_box('group', $groups, intval($sub['group']), array('id' => 'group')), 'group');
			$form_container->output_row($lang->mysubscriptions_disporder."<em>*</em>", $lang->mysubscriptions_disporder_desc, $form->generate_text_box('disporder', (int)$sub['disporder'], array('id' => 'disporder')), 'disporder');
			$form_container->output_row($lang->mysubscriptions_time_period."<em>*</em>", $lang->mysubscriptions_time_period_desc, $form->generate_text_area('time_period', $time_period, array('id' => 'time_period')), 'time_period');
			$form_container->output_row($lang->mysubscriptions_maxactive, $lang->mysubscriptions_maxactive_desc, $form->generate_text_box('maxactive', $sub['maxactive']));
			$form_container->output_row($lang->mysubscriptions_additional, $lang->mysubscriptions_additional_desc, $form->generate_check_box('additional', '1', '', array('id' => 'additional','checked' => intval($sub['additional']))), 'additional');
			$form_container->output_row($lang->mysubscriptions_enabled, $lang->mysubscriptions_enabled_desc, $form->generate_check_box('enabled', '1', '', array('id' => 'enabled','checked' => intval($sub['enabled']))), 'enabled');
			$form_container->output_row($lang->mysubscriptions_user_groups, $lang->mysubscriptions_user_groups_desc, $form->generate_select_box('visible[]', $usergroups, $sub['visible'], array('id' => 'visible', 'multiple' => true, 'size' => 5)), 'visible');
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->mysubscriptions_submit);
			$buttons[] = $form->generate_reset_button($lang->mysubscriptions_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == "log")
		{
			$page->output_nav_tabs($sub_tabs, 'mysubscriptions_log');

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

			$query = $db->simple_select("mysubscriptions_log", "COUNT(lid) as entries");
			$total_rows = $db->fetch_field($query, "entries");

			if ($total_rows > $per_page)
				echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=user-mysubscriptions&amp;action=log&amp;page={page}");


			$table = new Table;
			$table->construct_header($lang->mysubscriptions_lid, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_uid, array('width' => '20%'));
			$table->construct_header($lang->mysubscriptions_subscription, array('width' => '15%'));
			$table->construct_header($lang->mysubscriptions_date, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_type, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_amount, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_time, array('width' => '15%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT *
				FROM ".TABLE_PREFIX."mysubscriptions_log
				ORDER BY lid DESC
				LIMIT {$start}, {$per_page}
			");

			while($sublog = $db->fetch_array($query))
			{
				$table->construct_cell($sublog['lid'], array("class" => "align_center"));
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($sublog['uname']), $sublog['uid']));
				$table->construct_cell(htmlspecialchars_uni($sublog['item_name']));
				$table->construct_cell(htmlspecialchars_uni($sublog['payment_date']), array("class" => "align_center"));
				if ($sublog['payment_status'] != 'Refunded')
					$table->construct_cell(htmlspecialchars_uni($sublog['txn_type']), array("class" => "align_center"));
				else
					$table->construct_cell(htmlspecialchars_uni($sublog['payment_status']), array("class" => "align_center"));
				$table->construct_cell(htmlspecialchars_uni($sublog['mc_gross']), array("class" => "align_center"));
				$table->construct_cell(htmlspecialchars_uni($sublog['residence_country']), array("class" => "align_center"));
				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->mysubscriptions_log_empty, array('colspan' => 8));
				$table->construct_row();
			}

			$table->output($lang->mysubscriptions_view_log);
		}
		elseif ($mybb->input['action'] == "coinpayments_log")
		{
			$page->output_nav_tabs($sub_tabs, 'mysubscriptions_coinpayments_log');

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

			$query = $db->simple_select("mysubscriptions_coinpayments_log", "COUNT(lid) as entries");
			$total_rows = $db->fetch_field($query, "entries");

			if ($total_rows > $per_page)
				echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=user-mysubscriptions&amp;action=coinpayments_log&amp;page={page}");


			$table = new Table;
			$table->construct_header($lang->mysubscriptions_lid, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_uid, array('width' => '15%'));
			$table->construct_header($lang->mysubscriptions_subscription, array('width' => '15%'));
			$table->construct_header($lang->mysubscriptions_date, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_txn_id, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_status, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_amount1, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_amount2, array('width' => '10%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT *
				FROM ".TABLE_PREFIX."mysubscriptions_coinpayments_log
				ORDER BY lid DESC
				LIMIT {$start}, {$per_page}
			");

			while($sublog = $db->fetch_array($query))
			{
				$table->construct_cell($sublog['lid'], array("class" => "align_center"));
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($sublog['uname']), $sublog['uid']));
				$table->construct_cell(htmlspecialchars_uni($sublog['item_name']));

				$sublog['date'] = my_date($mybb->settings['dateformat'], $sublog['timestamp']);
				$sublog['date'] .= ', '.my_date($mybb->settings['timeformat'], $sublog['timestamp']);
				$table->construct_cell($sublog['date'], array("class" => "align_center"));

				$table->construct_cell($sublog['txn_id'], array("class" => "align_center"));
				$table->construct_cell($sublog['status_text'], array("class" => "align_center"));

				$table->construct_cell($sublog['amount1']." ".$sublog['currency1'], array("class" => "align_center"));
				$table->construct_cell($sublog['amount2']." ".$sublog['currency2'], array("class" => "align_center"));
				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->mysubscriptions_log_empty, array('colspan' => 8));
				$table->construct_row();
			}

			$table->output($lang->mysubscriptions_view_log);
		}
		elseif ($mybb->input['action'] == 'active')
		{
			$page->output_nav_tabs($sub_tabs, 'mysubscriptions_active_subscriptions');

			// Get all plans
			$plans = array(0 => $lang->mysubscriptions_select_plan);
			$query = $db->simple_select('mysubscriptions_subscriptions', '*', '', array('order_by' => 'title', 'order_dir' => 'ASC'));
			while ($sub = $db->fetch_array($query))
				$plans[$sub['sid']] = $sub['title'];

			// Date range fields
			$form = new Form("index.php?module=user-mysubscriptions&amp;action=freeze", "post");
			echo "<fieldset><legend>{$lang->mysubscriptions_multi_freeze_unfreeze}</legend>\n";
			echo "{$lang->mysubscriptions_select_plan}: ".$form->generate_select_box('sid', $plans);
			echo "<br /><br />".$form->generate_submit_button($lang->mysubscriptions_freeze, array('name' => 'freeze'));
			echo "".$form->generate_submit_button($lang->mysubscriptions_unfreeze, array('name' => 'unfreeze'));
			echo "</fieldset>\n";
			$form->end();

			// Date range fields
			$form = new Form("index.php?module=user-mysubscriptions&amp;action=extend", "post");
			echo "<fieldset><legend>{$lang->mysubscriptions_multi_extend}</legend>\n";
			echo "{$lang->mysubscriptions_select_plan}: ".$form->generate_select_box('sid', $plans);
			echo "&nbsp;&nbsp;{$lang->mysubscriptions_days}: ".$form->generate_text_box('days', 0);
			echo "<br /><br />".$form->generate_submit_button($lang->mysubscriptions_extend, array('name' => 'extend'));
			echo "</fieldset>\n";
			$form->end();

			echo "<br />";

			// table
			$table = new Table;
			$table->construct_header($lang->mysubscriptions_user, array('width' => '20%'));
			$table->construct_header($lang->mysubscriptions_subscription, array('width' => '20%'));
			$table->construct_header($lang->mysubscriptions_startdate, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_expiredate, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_processor, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->mysubscriptions_action, array('width' => '20%', 'class' => 'align_center'));

			$recurring = array();

			$plans = array();

			$subscriptions = array();

			// Get our manual subscriptions
	        $q = $db->query("
		        SELECT s.title, l.*
		        FROM `".TABLE_PREFIX."mysubscriptions_log` l
		        LEFT JOIN `".TABLE_PREFIX."mysubscriptions_subscriptions` s ON (s.sid=l.sid)
		        WHERE l.expired=0 AND l.enddate>0 AND l.memo='Manual Upgrade'
		        ORDER BY l.enddate ASC
	        ");
	        while($sub = $db->fetch_array($q))
	        {
		        $sub['processor'] = $lang->mysubscriptions_manually_upgraded;
		        $subscriptions[$sub['enddate']] = $sub;
	        }

	        // Get our PayPal subscriptions
	        if($mybb->settings['mysubscriptions_paypal_email'] != '')
	        {
		        $q = $db->query("
			        SELECT s.title, l.*
			        FROM `".TABLE_PREFIX."mysubscriptions_log` l
			        LEFT JOIN `".TABLE_PREFIX."mysubscriptions_subscriptions` s ON (s.sid=l.sid)
			        WHERE l.expired=0 AND l.enddate>0 AND l.memo!='Manual Upgrade'
			        ORDER BY l.enddate ASC
		        ");
		        while($sub = $db->fetch_array($q))
		        {
			        $sub['processor'] = 'PayPal';
			        $subscriptions[$sub['enddate']] = $sub;
		        }
	        }

	        if($mybb->settings['mysubscriptions_coinpayments_merchantid'] != '')
	        {
		        $q = $db->query("
			        SELECT s.title, l.*
			        FROM `".TABLE_PREFIX."mysubscriptions_coinpayments_log` l
			        LEFT JOIN `".TABLE_PREFIX."mysubscriptions_subscriptions` s ON (s.sid=l.sid)
			        WHERE l.expired=0 AND (l.status=100 || l.status=2)
			        ORDER BY l.enddate ASC
		        ");
		        while($sub = $db->fetch_array($q))
		        {
			        $sub['processor'] = 'CoinPayments';
			        $subscriptions[$sub['enddate']] = $sub;
		        }
	        }

			$total = 0;
			if(!empty($subscriptions))
			{
				foreach($subscriptions as $sub)
				{
					$recurring[$sub['uid']] = $sub['uid'];

					$table->construct_cell(build_profile_link(htmlspecialchars_uni($sub['uname']), $sub['uid']));

					$title = $sub['title'];
					if ($sub['additional'])
						$title .= " ".$lang->mysubscriptions_additional_title;
					if ($sub['frozen'])
						$title .= " ".$lang->mysubscriptions_frozen_title;

					$table->construct_cell($title);

					$sub['start'] = my_date($mybb->settings['dateformat'], $sub['timestamp']);
					$sub['start'] .= ', '.my_date($mybb->settings['timeformat'], $sub['timestamp']);

					$table->construct_cell($sub['start'], array('class' => 'align_center'));

					$sub['end'] = my_date($mybb->settings['dateformat'], $sub['enddate']);
					$sub['end'] .= ', '.my_date($mybb->settings['timeformat'], $sub['enddate']);

					$table->construct_cell($sub['end'], array('class' => 'align_center'));

					$table->construct_cell($sub['processor'], array('class' => 'align_center'));

					// actions column
					if($sub['processor'] == 'CoinPayments')
						$table->construct_cell("<a href=\"index.php?module=user-mysubscriptions&amp;action=edituser&p=cp&amp;lid=".intval($sub['lid'])."\">".$lang->mysubscriptions_edit."</a>", array('class' => 'align_center'));
					else
						$table->construct_cell("<a href=\"index.php?module=user-mysubscriptions&amp;action=edituser&p=pp&amp;lid=".intval($sub['lid'])."\">".$lang->mysubscriptions_edit."</a>", array('class' => 'align_center'));

					$total++;

					$plans[$sub['sid']]['title'] = $sub['title'];
					$plans[$sub['sid']]['total']++;

					$table->construct_row();
				}
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->mysubscriptions_no_subscribers, array('colspan' => 5));
				$table->construct_row();
			}

			$table->output($lang->mysubscriptions_subscribers." (".$total.")");

			echo "<br />";

			// table
			$table = new Table;

			$table->construct_header($lang->mysubscriptions_plan, array('width' => '50%'));
			$table->construct_header($lang->mysubscriptions_subscriptions, array('width' => '50%', 'class' => 'align_center'));

			$total = 0;

			if(!empty($plans))
			{
				foreach($plans as $plan)
				{
					$table->construct_cell($plan['title']);
					$table->construct_cell($plan['total'], array('class' => 'align_center'));

					$total += $plan['total'];

					$table->construct_row();
				}
			}

			$table->construct_cell('<strong>'.$lang->mysubscriptions_total.'</strong>');
			$table->construct_cell($total, array('class' => 'align_center'));
			$table->construct_row();

			$table->output($lang->mysubscriptions_active_subscriptions);
		}
		elseif ($mybb->input['action'] == 'edituser')
		{
			$lid = intval($mybb->input['lid']);
			if ($lid <= 0 || (!($log = $db->fetch_array($db->simple_select('mysubscriptions_log', '*', "lid = $lid AND enddate!=0")))))
			{
				mysubscriptions_messageredirect($lang->mysubscriptions_invalid_sub, 1);
			}

			if($mybb->input['p'] != 'pp' && $mybb->input['p'] != 'cp')
				mysubscriptions_messageredirect($lang->mysubscriptions_invalid_processor, 1);

			$form = new Form("index.php?module=user-mysubscriptions&amp;action=do_edituser", "post", "mysubscriptions");

			echo $form->generate_hidden_field('lid', $log['lid']);
			echo $form->generate_hidden_field('p', htmlspecialchars_uni($mybb->input['p']));

			$form_container = new FormContainer($lang->mysubscriptions_editsubscription);
			$form_container->output_row($lang->mysubscriptions_enddate, $lang->mysubscriptions_enddate_desc, $form->generate_text_box('enddate', date("j F Y", $log['enddate']), array('id' => 'enddate')), 'enddate');
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->mysubscriptions_submit);
			$buttons[] = $form->generate_reset_button($lang->mysubscriptions_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}

		$page->output_footer();
		exit;
	}
}

// &#71;&#101;&#110;&#101;&#114;&#105;&#99;

?>
