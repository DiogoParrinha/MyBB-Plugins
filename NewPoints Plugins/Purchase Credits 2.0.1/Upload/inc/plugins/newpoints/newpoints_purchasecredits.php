<?php
/***************************************************************************
 *
 *   NewPoints Purchase Credits plugin (/inc/plugins/newpoints/newpoints_purchasecredits.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   Integrates a credits purchasing system with NewPoints.
 *
 ***************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if (defined("IN_ADMINCP"))
{
	// Packages ACP page (Add/Edit/Delete packages)
	$plugins->add_hook('newpoints_admin_load', 'newpoints_purchasecredits_admin');
	$plugins->add_hook('newpoints_admin_newpoints_menu', 'newpoints_purchasecredits_admin_newpoints_menu');
	$plugins->add_hook('newpoints_admin_newpoints_action_handler', 'newpoints_purchasecredits_admin_newpoints_action_handler');
	$plugins->add_hook('newpoints_admin_newpoints_permissions', 'newpoints_purchasecredits_admin_permissions');
}
else
{
	// show Purchase Credits in the menu
	$plugins->add_hook("newpoints_default_menu", "newpoints_purchasecredits_menu");
	$plugins->add_hook("newpoints_start", "newpoints_purchasecredits_page");
}


// backup tables too
$plugins->add_hook("newpoints_task_backup_tables", "newpoints_purchasecredits_backup");

function newpoints_purchasecredits_info()
{
	return array(
		"name"			=> "Purchase Credits",
		"description"	=> "Integrates a credits purchasing system with NewPoints.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "2.0.1",
		"guid" 			=> "",
		"compatibility" => "2*"
	);
}

function newpoints_purchasecredits_install()
{
	global $db;

	$collation = $db->build_create_table_collation();

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_purchasecredits` (
	  `pid` int(10) UNSIGNED NOT NULL auto_increment,
	  `title` varchar(100) NOT NULL default '',
	  `description` text NOT NULL,
	  `credits` decimal(16,2) UNSIGNED NOT NULL default '0',
	  `price` decimal(16,2) UNSIGNED NOT NULL default '0',
	  `currency` varchar(3) NOT NULL default '',
	  `payment_paypal` tinyint(1) NOT NULL default 0,
	  `payment_payza` tinyint(1) NOT NULL default 0,
	  `payment_coinpayments` tinyint(1) NOT NULL default 0,
	  PRIMARY KEY  (`pid`)
		) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_purchasecredits_log_ap` (
		`lid` int(10) UNSIGNED NOT NULL auto_increment,
		`uid` bigint(30) UNSIGNED NOT NULL default 0,
		`username` varchar(50) NOT NULL default '',
		`item_code` int(10) UNSIGNED NOT NULL default 0,
		`item_name` varchar(127) NOT NULL default '',
		`item_amount` decimal(9,2) NOT NULL default 0,
		`first_name` varchar(64) NOT NULL default '',
		`last_name` varchar(64) NOT NULL default '',
		`currency` varchar(3) NOT NULL default '',
		`email` varchar(127) NOT NULL default '',
		`date` varchar(55) NOT NULL default '',
		`transaction_type` varchar(55) NOT NULL default '',
		PRIMARY KEY  (`lid`)
	) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_purchasecredits_log_pp` (
		`lid` int(10) UNSIGNED NOT NULL auto_increment,
		`uid` bigint(30) UNSIGNED NOT NULL default '0',
		`receiver_email` varchar(127) NOT NULL default '',
		`receiver_id` varchar(13) NOT NULL default '',
		`business` varchar(127) NOT NULL default '',
		`item_name` varchar(127) default NULL,
		`item_number` int(10) default NULL,
		`quantity` int(10) default NULL,
		`invoice` varchar(127) default NULL,
		`custom` varchar(255) default NULL,
		`payment_type` enum('echeck','instant') default NULL,
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
		PRIMARY KEY  (lid)
	) ENGINE=MyISAM{$collation}");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_purchasecredits_log_cp` (
		`lid` int(10) UNSIGNED NOT NULL auto_increment,
		`uid` bigint(30) UNSIGNED NOT NULL default '0',
		`username` varchar(50) NOT NULL default '',
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
		`date` int(10) NOT NULL default 0,
		PRIMARY KEY  (lid)
	) ENGINE=MyISAM{$collation}");

	// add settings
	newpoints_add_setting('newpoints_purchasecredits_paypal', 'newpoints_purchasecredits', 'PayPal Email', 'Enter the email of your PayPal account.', 'text', '', 1);
	newpoints_add_setting('newpoints_purchasecredits_payza', 'newpoints_purchasecredits', 'Payza Email', 'Enter the email of your Payza account.', 'text', '', 2);
	newpoints_add_setting('newpoints_purchasecredits_coinpayments', 'newpoints_purchasecredits', 'CoinPayments Merchant ID', 'Enter the CoinPayments merchant ID. Leave this empty to disable this payment type.', 'text', '', 2);
	rebuild_settings();
}

function newpoints_purchasecredits_is_installed()
{
	global $db;
	if($db->table_exists('newpoints_purchasecredits'))
	{
		return true;
	}
	return false;
}

function newpoints_purchasecredits_uninstall()
{
	global $db;

	if($db->table_exists('newpoints_purchasecredits'))
	{
		$db->drop_table('newpoints_purchasecredits');
	}

	if($db->table_exists('newpoints_purchasecredits_log_ap'))
	{
		$db->drop_table('newpoints_purchasecredits_log_ap');
	}

	if($db->table_exists('newpoints_purchasecredits_log_pp'))
	{
		$db->drop_table('newpoints_purchasecredits_log_pp');
	}

	if($db->table_exists('newpoints_purchasecredits_log_cp'))
	{
		$db->drop_table('newpoints_purchasecredits_log_cp');
	}

	// delete settings
	newpoints_remove_settings("'newpoints_purchasecredits_paypal','newpoints_purchasecredits_payza','newpoints_purchasecredits_coinpayments'");
	rebuild_settings();
}

function newpoints_purchasecredits_activate()
{
	global $db, $mybb;

	newpoints_add_template('newpoints_purchasecredits_no_permission', '
<html>
<head>
<title>{$lang->newpoints} - {$lang->newpoints_purchasecredits}</title>
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
<td class="thead" colspan="5"><strong>{$lang->newpoints_purchasecredits_packages}</strong></td>
</tr>
<tr><td class="trow1">{$lang->newpoints_purchasecredits_permissions}</td></tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>');

	newpoints_add_template('newpoints_purchasecredits', '
<html>
<head>
<title>{$lang->newpoints} - {$lang->newpoints_purchasecredits}</title>
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
<td class="thead" colspan="5"><strong>{$lang->newpoints_purchasecredits_packages}</strong></td>
</tr>
<tr>
<td class="tcat" width="40%"><strong>{$lang->newpoints_purchasecredits_title}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_purchasecredits_price}</strong></td>
<td class="tcat" width="15%" align="center"><strong>{$lang->newpoints_purchasecredits_credits}</strong></td>
<td class="tcat" width="30%" align="center"><strong>{$lang->newpoints_purchasecredits_purchase}</strong></td>
</tr>
{$packages}
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>');

newpoints_add_template('newpoints_purchasecredits_row', '
<tr>
<td class="{$bgcolor}" width="40%">{$package[\'title\']}<br /><span class="smalltext">{$package[\'description\']}<span></td>
<td class="{$bgcolor}" width="15%" align="center">{$package[\'price\']}</td>
<td class="{$bgcolor}" width="15%" align="center">{$package[\'credits\']}</td>
<td class="{$bgcolor}" width="30%" align="center">
	{$buttons}
</td>
</tr>');

newpoints_add_template('newpoints_purchasecredits_row_paypal', '
<form name="_xclick" action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="{$mybb->settings[\'newpoints_purchasecredits_paypal\']}">
<input type="hidden" name="currency_code" value="{$package[\'currency\']}">
<input type="hidden" name="item_name" value="{$package[\'title\']}">
<input type="hidden" name="item_number" value="{$package[\'pid\']}" />
<input type="hidden" name="amount" value="{$package[\'amount\']}">
<input type="hidden" name="return" value="{$mybb->settings[\'bburl\']}/index.php" />
<input type="hidden" name="no_shipping" value="1" />
<input type="hidden" name="custom" value="{$mybb->user[\'uid\']}" />
<input type="hidden" name="notify_url" value="{$mybb->settings[\'bburl\']}/credits_paypal.php" />
<input type="hidden" name="no_note" value="1" />
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!" />
</form>');

newpoints_add_template('newpoints_purchasecredits_row_payza', '
<form method="post" action="https://secure.payza.com/checkout" >
    <input type="hidden" name="ap_merchant" value="{$mybb->settings[\'newpoints_purchasecredits_payza\']}"/>
    <input type="hidden" name="ap_purchasetype" value="item-goods"/>
    <input type="hidden" name="ap_itemname" value="{$package[\'title\']}"/>
    <input type="hidden" name="ap_amount" value="{$package[\'amount\']}"/>
    <input type="hidden" name="ap_currency" value="{$package[\'currency\']}"/>

    <input type="hidden" name="ap_quantity" value="1"/>
    <input type="hidden" name="ap_itemcode" value="{$package[\'pid\']}"/>
    <input type="hidden" name="ap_description" value="{$package[\'apdescription\']}"/>
    <input type="hidden" name="ap_returnurl" value="{$mybb->settings[\'bburl\']}"/>
    <input type="hidden" name="ap_cancelurl" value="{$mybb->settings[\'bburl\']}"/>
    <input type="hidden" name="ap_alerturl" value="{$mybb->settings[\'bburl\']}/credits_payza.php"/>

    <input type="hidden" name="ap_taxamount" value="0"/>
    <input type="hidden" name="ap_additionalcharges" value="0"/>
    <input type="hidden" name="ap_shippingcharges" value="0"/>

    <input type="hidden" name="ap_discountamount" value="0"/>
    <input type="hidden" name="apc_1" value="{$mybb->user[\'uid\']}"/>

    <input type="image" src="https://secure.payza.com/PayNow/7378A68110D349B38C1AE3F2681B3AFDb7en.gif"/>
</form>');

newpoints_add_template('newpoints_purchasecredits_row_coinpayments', '
<form action="https://www.coinpayments.net/index.php" method="post">
	<input type="hidden" name="cmd" value="_pay">
	<input type="hidden" name="reset" value="1">
<input type="hidden" name="merchant" value="{$mybb->settings[\'newpoints_purchasecredits_coinpayments\']}">
	<input type="hidden" name="item_name" value="{$package[\'title\']}">
	<input type="hidden" name="item_number" value="{$package[\'pid\']}">
	<input type="hidden" name="custom" value="{$mybb->user[\'uid\']}">
	<input type="hidden" name="currency" value="{$package[\'currency\']}">
	<input type="hidden" name="amountf" value="{$package[\'amount\']}">
	<input type="hidden" name="quantity" value="1">
	<input type="hidden" name="allow_quantity" value="0">
	<input type="hidden" name="want_shipping" value="0">
	<input type="hidden" name="success_url" value="{$mybb->settings[\'bburl\']}/">
	<input type="hidden" name="cancel_url" value="{$mybb->settings[\'bburl\']}/">
	<input type="hidden" name="ipn_url" value="{$mybb->settings[\'bburl\']}/credits_coinpayments.php">
	<input type="hidden" name="allow_extra" value="0">
	<input type="image" src="https://www.coinpayments.net/images/pub/buynow-wide-blue.png" alt="Buy Now with CoinPayments.net">
</form>
<br />');

newpoints_add_template('newpoints_purchasecredits_empty', '
<tr>
<td class="trow1" width="100%" colspan="4">{$lang->newpoints_purchasecredits_empty}</td>
</tr>');
}

function newpoints_purchasecredits_deactivate()
{
	global $db, $mybb;

	newpoints_remove_templates("'newpoints_purchasecredits','newpoints_purchasecredits_row','newpoints_purchasecredits_empty','newpoints_purchasecredits_no_permission','newpoints_purchasecredits_row_paypal','newpoints_purchasecredits_row_payza','newpoints_purchasecredits_row_coinpayments'");
}

// show purchase credits in the list
function newpoints_purchasecredits_menu(&$menu)
{
	global $mybb, $lang;
	newpoints_lang_load("newpoints_purchasecredits");

	if ($mybb->input['action'] == 'purchasecredits')
		$menu[] = "&raquo; <a href=\"{$mybb->settings['bburl']}/newpoints.php?action=purchasecredits\">".$lang->newpoints_purchasecredits."</a>";
	else
		$menu[] = "<a href=\"{$mybb->settings['bburl']}/newpoints.php?action=purchasecredits\">".$lang->newpoints_purchasecredits."</a>";
}

function newpoints_purchasecredits_page()
{
	global $mybb, $db, $lang, $cache, $theme, $header, $templates, $plugins, $headerinclude, $footer, $options, $inline_errors;

	if ($mybb->input['action'] != 'purchasecredits') return;

	newpoints_lang_load("newpoints_purchasecredits");

	$plugins->run_hooks("newpoints_purchasecredits_start");

	if (!$mybb->user['uid'])
	{
		eval("\$page = \"".$templates->get('newpoints_purchasecredits_no_permission')."\";");

		// output page
		output_page($page);
		exit;
	}

	$query = $db->simple_select('newpoints_purchasecredits', '*', '', array('order_by' => 'credits', 'order_dir' => 'ASC'));
	while ($package = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();

		$package['pid'] = (int)$package['pid'];
		$package['title'] = $package['title'];
		$package['description'] = $package['description'];
		$package['apdescription'] = htmlspecialchars_uni($package['description']);

		$package['credits'] = newpoints_format_points($package['credits']);
		$package['amount'] = floatval($package['price']);
		$package['price'] = number_format($package['price'], 2)." ".htmlspecialchars_uni($package['currency']);

		// Buttons
		$buttons = '';
		if($mybb->settings['newpoints_purchasecredits_paypal'] != '' && $package['payment_paypal'] == 1)
		{
			eval("\$buttons .= \"".$templates->get('newpoints_purchasecredits_row_paypal')."\";");
		}
		if($mybb->settings['newpoints_purchasecredits_payza'] != '' && $package['payment_payza'] == 1)
		{
			if($buttons != '')
				$buttons .= '<br />';
			eval("\$buttons .= \"".$templates->get('newpoints_purchasecredits_row_payza')."\";");
		}
		if($mybb->settings['newpoints_purchasecredits_coinpayments'] != '' && $package['payment_coinpayments'] == 1)
		{
			if($buttons != '')
				$buttons .= '<br />';
			eval("\$buttons .= \"".$templates->get('newpoints_purchasecredits_row_coinpayments')."\";");
		}

		eval("\$packages .= \"".$templates->get('newpoints_purchasecredits_row')."\";");
	}

	if (empty($packages))
	{
		eval("\$packages = \"".$templates->get('newpoints_purchasecredits_empty')."\";");
	}

	eval("\$page = \"".$templates->get('newpoints_purchasecredits')."\";");

	$plugins->run_hooks("newpoints_purchasecredits_end");

	// output page
	output_page($page);
}

// backup tables too
function newpoints_purchasecredits_backup(&$backup_fields)
{
	global $db, $table, $tables;

	$tables[] = TABLE_PREFIX.'newpoints_purchasecredits';
	$tables[] = TABLE_PREFIX.'newpoints_purchasecredits_logs';
}

/*************************************************************************************/
// ADMIN PART
/*************************************************************************************/

function newpoints_purchasecredits_admin_newpoints_menu(&$sub_menu)
{
	global $lang;

	newpoints_lang_load('newpoints_purchasecredits');
	$sub_menu[] = array('id' => 'purchasecredits', 'title' => $lang->newpoints_purchasecredits, 'link' => 'index.php?module=newpoints-purchasecredits');
}

function newpoints_purchasecredits_admin_newpoints_action_handler(&$actions)
{
	$actions['purchasecredits'] = array('active' => 'purchasecredits', 'file' => 'newpoints_purchasecredits');
}

function newpoints_purchasecredits_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;

	newpoints_lang_load('newpoints_purchasecredits');
	$admin_permissions['purchasecredits'] = $lang->newpoints_purchasecredits_canmanage;

}

function newpoints_purchasecredits_messageredirect($message, $error=0, $action='')
{
  	global $db, $mybb, $lang;

	if (!$message)
		return;

	if ($action)
		$parameters = '&amp;action='.$action;

	if ($error)
	{
		flash_message($message, 'error');
		admin_redirect("index.php?module=newpoints-purchasecredits".$parameters);
	}
	else {
		flash_message($message, 'success');
		admin_redirect("index.php?module=newpoints-purchasecredits".$parameters);
	}
}

function newpoints_purchasecredits_admin()
{
	global $db, $lang, $mybb, $page, $run_module, $action_file, $mybbadmin, $plugins;

	newpoints_lang_load('newpoints_purchasecredits');

	if($run_module == 'newpoints' && $action_file == 'newpoints_purchasecredits')
	{
		if ($mybb->request_method == "post")
		{
			switch ($mybb->input['action'])
			{
				case 'do_addpackage':
					if ($mybb->input['title'] == '' || $mybb->input['description'] == '')
					{
						newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_missing_field, 1);
					}

					$title = $db->escape_string($mybb->input['title']);
					$description = $db->escape_string($mybb->input['description']);

					$price = floatval($mybb->input['price']);
					$credits = floatval($mybb->input['credits']);

					$available_currencies = array(
						'AUD' => 1,
						'BGN' => 1,
						'BTC' => 1,
						'CAD' => 1,
						'CHF' => 1,
						'CZK' => 1,
						'DKK' => 1,
						'EEK' => 1,
						'EUR' => 1,
						'GBP' => 1,
						'HKD' => 1,
						'HUF' => 1,
						'JPY' => 1,
						'LTL' => 1,
						'MYR' => 1,
						'MKD' => 1,
						'NOK' => 1,
						'NZD' => 1,
						'PLN' => 1,
						'RON' => 1,
						'SEK' => 1,
						'SGD' => 1,
						'USD' => 1,
						'ZAR' => 1,
					);

					if (!isset($available_currencies[$mybb->input['currency']]))
					{
						newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_invalid_currency, 1);
					}

					$insert_array = array(
						'title' => $title,
						'description' => $description,
						'price' => $price,
						'credits' => $credits,
						'currency' => $db->escape_string($mybb->input['currency']),
						'payment_paypal' => (int)$mybb->input['payment_paypal'],
						'payment_payza' => (int)$mybb->input['payment_payza'],
						'payment_coinpayments' => (int)$mybb->input['payment_coinpayments']
					);

					$db->insert_query('newpoints_purchasecredits', $insert_array);

					newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_package_added);
				break;
				case 'do_editpackage':
					$pid = intval($mybb->input['pid']);
					if ($pid <= 0 || (!($package = $db->fetch_array($db->simple_select('newpoints_purchasecredits', '*', "pid = $pid")))))
					{
						newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_invalid_package, 1);
					}

					if ($mybb->input['title'] == '' || $mybb->input['description'] == '')
					{
						newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_missing_field, 1);
					}

					$title = $db->escape_string($mybb->input['title']);
					$description = $db->escape_string($mybb->input['description']);

					$price = floatval($mybb->input['price']);
					$credits = floatval($mybb->input['credits']);


					$available_currencies = array(
						'AUD' => 1,
						'BGN' => 1,
						'BTC' => 1,
						'CAD' => 1,
						'CHF' => 1,
						'CZK' => 1,
						'DKK' => 1,
						'EEK' => 1,
						'EUR' => 1,
						'GBP' => 1,
						'HKD' => 1,
						'HUF' => 1,
						'JPY' => 1,
						'LTL' => 1,
						'MYR' => 1,
						'MKD' => 1,
						'NOK' => 1,
						'NZD' => 1,
						'PLN' => 1,
						'RON' => 1,
						'SEK' => 1,
						'SGD' => 1,
						'USD' => 1,
						'ZAR' => 1,
					);

					if (!isset($available_currencies[$mybb->input['currency']]))
					{
						newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_invalid_currency, 1);
					}

					$update_array = array(
						'title' => $title,
						'description' => $description,
						'price' => $price,
						'credits' => $credits,
						'currency' => $db->escape_string($mybb->input['currency']),
						'payment_paypal' => (int)$mybb->input['payment_paypal'],
						'payment_payza' => (int)$mybb->input['payment_payza'],
						'payment_coinpayments' => (int)$mybb->input['payment_coinpayments'],
					);

					$db->update_query('newpoints_purchasecredits', $update_array, 'pid=\''.intval($package['pid']).'\'');

					newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_package_edited);
				break;
			}
		}

		if ($mybb->input['action'] == 'do_deletepackage')
		{
			$page->add_breadcrumb_item($lang->newpoints_purchasecredits, 'index.php?module=newpoints-purchasecredits');
			$page->output_header($lang->newpoints_purchasecredits);

			$pid = intval($mybb->input['pid']);

			if($mybb->input['no']) // user clicked no
			{
				admin_redirect("index.php?module=newpoints-purchasecredits");
			}

			if($mybb->request_method == "post")
			{
				if ($pid <= 0 || (!($package = $db->fetch_array($db->simple_select('newpoints_purchasecredits', 'pid', "pid = $pid")))))
				{
					newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_invalid_package, 1);
				}

				// delete package
				$db->delete_query('newpoints_purchasecredits', "pid = $pid");

				newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_package_deleted);
			}
			else
			{
				$mybb->input['pid'] = intval($mybb->input['pid']);
				$form = new Form("index.php?module=newpoints-purchasecredits&amp;action=do_deletepackage&amp;pid={$mybb->input['pid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->newpoints_purchasecredits_confirm_deletepackage}</p>\n";
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

		$page->add_breadcrumb_item($lang->newpoints_purchasecredits, 'index.php?module=newpoints-purchasecredits');

		$page->output_header($lang->newpoints_purchasecredits);

		$sub_tabs['newpoints_purchasecredits'] = array(
			'title'			=> $lang->newpoints_purchasecredits,
			'link'			=> 'index.php?module=newpoints-purchasecredits',
			'description'	=> $lang->newpoints_purchasecredits_desc
		);

		$sub_tabs['newpoints_purchasecredits_add'] = array(
			'title'			=> $lang->newpoints_purchasecredits_add,
			'link'			=> 'index.php?module=newpoints-purchasecredits&amp;action=addpackage',
			'description'	=> $lang->newpoints_purchasecredits_add_desc
		);

		$sub_tabs['newpoints_purchasecredits_edit'] = array(
			'title'			=> $lang->newpoints_purchasecredits_edit,
			'link'			=> 'index.php?module=newpoints-purchasecredits&amp;action=editpackage',
			'description'	=> $lang->newpoints_purchasecredits_edit_desc
		);

		$sub_tabs['newpoints_purchasecredits_pp_log'] = array(
			'title'			=> $lang->newpoints_purchasecredits_paypal_log,
			'link'			=> 'index.php?module=newpoints-purchasecredits&amp;action=paypal',
			'description'	=> $lang->newpoints_purchasecredits_paypal_log_desc
		);

		$sub_tabs['newpoints_purchasecredits_ap_log'] = array(
			'title'			=> $lang->newpoints_purchasecredits_payza_log,
			'link'			=> 'index.php?module=newpoints-purchasecredits&amp;action=payza',
			'description'	=> $lang->newpoints_purchasecredits_payza_log_desc
		);

		$sub_tabs['newpoints_purchasecredits_cp_log'] = array(
			'title'			=> $lang->newpoints_purchasecredits_coinpayments_log,
			'link'			=> 'index.php?module=newpoints-purchasecredits&amp;action=coinpayments',
			'description'	=> $lang->newpoints_purchasecredits_coinpayments_log_desc
		);

		if (!$mybb->input['action'])
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_purchasecredits');

			// table
			$table = new Table;
			$table->construct_header($lang->newpoints_purchasecredits_title, array('width' => '25%'));
			$table->construct_header($lang->newpoints_purchasecredits_price, array('width' => '25', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_credits, array('width' => '25%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_action, array('width' => '25%', 'class' => 'align_center'));

			$query = $db->simple_select('newpoints_purchasecredits', '*', '', array('order_by' => 'title', 'order_dir' => 'ASC'));
			while ($package = $db->fetch_array($query))
			{
				$table->construct_cell(htmlspecialchars_uni($package['title']));
				$table->construct_cell(number_format($package['price'], 2)." ".htmlspecialchars_uni($package['currency']), array('class' => 'align_center'));
				$table->construct_cell(newpoints_format_points($package['credits']), array('class' => 'align_center'));

				// actions column
				$table->construct_cell("<a href=\"index.php?module=newpoints-purchasecredits&amp;action=editpackage&amp;pid=".intval($package['pid'])."\">".$lang->newpoints_purchasecredits_edit."</a> - <a href=\"index.php?module=newpoints-purchasecredits&amp;action=do_deletepackage&amp;pid=".intval($package['pid'])."\">".$lang->newpoints_purchasecredits_delete."</a>", array('class' => 'align_center'));

				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->newpoints_purchasecredits_no_packages, array('colspan' => 5));
				$table->construct_row();
			}

			$table->output($lang->newpoints_purchasecredits_packages);
		}
		elseif ($mybb->input['action'] == 'paypal')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_purchasecredits_pp_log');

			$per_page = 15;

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

			$query = $db->simple_select("newpoints_purchasecredits_log_pp", "COUNT(lid) as entries");
			$total_rows = $db->fetch_field($query, "entries");

			if ($total_rows > $per_page)
				echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=newpoints-purchasecredits&amp;action=paypal&amp;page={page}");


			$table = new Table;
			$table->construct_header($lang->newpoints_purchasecredits_lid, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_uid, array('width' => '15%'));
			$table->construct_header($lang->newpoints_purchasecredits_package, array('width' => '15%'));
			$table->construct_header($lang->newpoints_purchasecredits_date, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_type, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_amount, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_packageid, array('width' => '15%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT *
				FROM ".TABLE_PREFIX."newpoints_purchasecredits_log_pp
				ORDER BY lid DESC
				LIMIT {$start}, {$per_page}
			");

			while($log = $db->fetch_array($query))
			{
				$table->construct_cell($log['lid'], array("class" => "align_center"));
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($log['custom']), $log['uid']));
				$table->construct_cell(htmlspecialchars_uni($log['item_name']));
				$table->construct_cell($log['payment_date'], array("class" => "align_center"));
				if ($log['payment_status'] != 'Refunded')
					$table->construct_cell($log['txn_type'], array("class" => "align_center"));
				else
					$table->construct_cell($log['payment_status'], array("class" => "align_center"));
				$table->construct_cell($log['mc_gross'], array("class" => "align_center"));
				$table->construct_cell($log['item_number'], array("class" => "align_center"));
				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->newpoints_purchasecredits_log_empty, array('colspan' => 7));
				$table->construct_row();
			}

			$table->output($lang->newpoints_purchasecredits_view_log);
		}
		elseif ($mybb->input['action'] == 'payza')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_purchasecredits_ap_log');

			$per_page = 15;

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

			$query = $db->simple_select("newpoints_purchasecredits_log_ap", "COUNT(lid) as entries");
			$total_rows = $db->fetch_field($query, "entries");

			if ($total_rows > $per_page)
				echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=newpoints-purchasecredits&amp;action=payza&amp;page={page}");


			$table = new Table;
			$table->construct_header($lang->newpoints_purchasecredits_lid, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_uid, array('width' => '15%'));
			$table->construct_header($lang->newpoints_purchasecredits_package, array('width' => '15%'));
			$table->construct_header($lang->newpoints_purchasecredits_date, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_type, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_amount, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_packageid, array('width' => '15%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT *
				FROM ".TABLE_PREFIX."newpoints_purchasecredits_log_ap
				ORDER BY lid DESC
				LIMIT {$start}, {$per_page}
			");

			while($log = $db->fetch_array($query))
			{
				$table->construct_cell($log['lid'], array("class" => "align_center"));
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($log['username']), $log['uid']));
				$table->construct_cell(htmlspecialchars_uni($log['item_name']));
				$table->construct_cell($log['date'], array("class" => "align_center"));
				$table->construct_cell($log['transaction_type'], array("class" => "align_center"));
				$table->construct_cell($log['item_amount'], array("class" => "align_center"));
				$table->construct_cell($log['item_code'], array("class" => "align_center"));
				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->newpoints_purchasecredits_log_empty, array('colspan' => 8));
				$table->construct_row();
			}

			$table->output($lang->newpoints_purchasecredits_view_log);
		}
		elseif ($mybb->input['action'] == 'coinpayments')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_purchasecredits_cp_log');

			$per_page = 15;

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

			$query = $db->simple_select("newpoints_purchasecredits_log_cp", "COUNT(lid) as entries");
			$total_rows = $db->fetch_field($query, "entries");

			if ($total_rows > $per_page)
				echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=newpoints-purchasecredits&amp;action=coinpayments&amp;page={page}");


			$table = new Table;
			$table->construct_header($lang->newpoints_purchasecredits_lid, array('width' => '5%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_uid, array('width' => '15%'));
			$table->construct_header($lang->newpoints_purchasecredits_package, array('width' => '15%'));
			$table->construct_header($lang->newpoints_purchasecredits_date, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_type, array('width' => '15%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_amount, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->newpoints_purchasecredits_packageid, array('width' => '15%', 'class' => 'align_center'));

			$query = $db->query("
				SELECT *
				FROM ".TABLE_PREFIX."newpoints_purchasecredits_log_cp
				ORDER BY lid DESC
				LIMIT {$start}, {$per_page}
			");

			while($log = $db->fetch_array($query))
			{
				$table->construct_cell($log['lid'], array("class" => "align_center"));
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($log['username']), $log['uid']));
				$table->construct_cell(htmlspecialchars_uni($log['item_name']));
				$table->construct_cell($log['date'], array("class" => "align_center"));
				$table->construct_cell($log['status_text'], array("class" => "align_center"));
				$table->construct_cell($log['received_amount'], array("class" => "align_center"));
				$table->construct_cell($log['item_number'], array("class" => "align_center"));
				$table->construct_row();
			}

			if ($table->num_rows() == 0)
			{
				$table->construct_cell($lang->newpoints_purchasecredits_log_empty, array('colspan' => 8));
				$table->construct_row();
			}

			$table->output($lang->newpoints_purchasecredits_view_log);
		}
		elseif ($mybb->input['action'] == 'addpackage')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_purchasecredits_add');

			$available_currencies = array(
				'' => $lang->newpoints_purchasecredits_select_currency,
				'AUD' => 'AUD',
				'BGN' => 'BGN',
				'BTC' => 'BTC',
				'CAD' => 'CAD',
				'CHF' => 'CHF',
				'CZK' => 'CZK',
				'DKK' => 'DKK',
				'EEK' => 'EEK',
				'EUR' => 'EUR',
				'GBP' => 'GBP',
				'HKD' => 'HKD',
				'HUF' => 'HUF',
				'LTL' => 'LTL',
				'MYR' => 'MYR',
				'MKD' => 'MKD',
				'NOK' => 'NOK',
				'NZD' => 'NZD',
				'PLN' => 'PLN',
				'RON' => 'RON',
				'SEK' => 'SEK',
				'SGD' => 'SGD',
				'USD' => 'USD',
				'ZAR' => 'ZAR',
			);

			$form = new Form("index.php?module=newpoints-purchasecredits&amp;action=do_addpackage", "post", "newpoints_purchasecredits");

			$form_container = new FormContainer($lang->newpoints_purchasecredits_addpackage);
			$form_container->output_row($lang->newpoints_purchasecredits_title."<em>*</em>", $lang->newpoints_purchasecredits_title_desc, $form->generate_text_box('title', '', array('id' => 'title')), 'title');
			$form_container->output_row($lang->newpoints_purchasecredits_description."<em>*</em>", $lang->newpoints_purchasecredits_description_desc, $form->generate_text_area('description', '', array('id' => 'description')), 'description');
			$form_container->output_row($lang->newpoints_purchasecredits_currency."<em>*</em>", $lang->newpoints_purchasecredits_currency_desc, $form->generate_select_box('currency', $available_currencies, '', array('id' => 'currency')), 'currency');
			$form_container->output_row($lang->newpoints_purchasecredits_price."<em>*</em>", $lang->newpoints_purchasecredits_price_desc, $form->generate_text_box('price', '0.00', array('id' => 'price')), 'price');
			$form_container->output_row($lang->newpoints_purchasecredits_credits."<em>*</em>", $lang->newpoints_purchasecredits_credits_desc, $form->generate_text_box('credits', '0.00', array('id' => 'credits')), 'credits');
			$form_container->output_row($lang->newpoints_purchasecredits_accept_paypal, '', $form->generate_check_box('payment_paypal', '1', '', array('checked' => false)));
			$form_container->output_row($lang->newpoints_purchasecredits_accept_payza, '', $form->generate_check_box('payment_payza', '1', '', array('checked' => false)));
			$form_container->output_row($lang->newpoints_purchasecredits_accept_coinpayments, '', $form->generate_check_box('payment_coinpayments', '1', '', array('checked' => false)));
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->newpoints_purchasecredits_submit);
			$buttons[] = $form->generate_reset_button($lang->newpoints_purchasecredits_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'editpackage')
		{
			$page->output_nav_tabs($sub_tabs, 'newpoints_purchasecredits_edit');

			$pid = intval($mybb->input['pid']);
			if ($pid <= 0 || (!($package = $db->fetch_array($db->simple_select('newpoints_purchasecredits', '*', "pid = $pid")))))
			{
				newpoints_purchasecredits_messageredirect($lang->newpoints_purchasecredits_invalid_package, 1);
			}

			$available_currencies = array(
				'' => $lang->newpoints_purchasecredits_select_currency,
				'AUD' => 'AUD',
				'BGN' => 'BGN',
				'BTC' => 'BTC',
				'CAD' => 'CAD',
				'CHF' => 'CHF',
				'CZK' => 'CZK',
				'DKK' => 'DKK',
				'EEK' => 'EEK',
				'EUR' => 'EUR',
				'GBP' => 'GBP',
				'HKD' => 'HKD',
				'HUF' => 'HUF',
				'LTL' => 'LTL',
				'MYR' => 'MYR',
				'MKD' => 'MKD',
				'NOK' => 'NOK',
				'NZD' => 'NZD',
				'PLN' => 'PLN',
				'RON' => 'RON',
				'SEK' => 'SEK',
				'SGD' => 'SGD',
				'USD' => 'USD',
				'ZAR' => 'ZAR',
			);



			$form = new Form("index.php?module=newpoints-purchasecredits&amp;action=do_editpackage", "post", "newpoints_purchasecredits");

			echo $form->generate_hidden_field('pid', $package['pid']);

			$form_container = new FormContainer($lang->newpoints_purchasecredits_editpackage);
			$form_container->output_row($lang->newpoints_purchasecredits_title."<em>*</em>", $lang->newpoints_purchasecredits_title_desc, $form->generate_text_box('title', $package['title'], array('id' => 'title')), 'title');
			$form_container->output_row($lang->newpoints_purchasecredits_description."<em>*</em>", $lang->newpoints_purchasecredits_description_desc, $form->generate_text_area('description', $package['description'], array('id' => 'description')), 'description');
			$form_container->output_row($lang->newpoints_purchasecredits_currency."<em>*</em>", $lang->newpoints_purchasecredits_group_desc, $form->generate_select_box('currency', $available_currencies, $package['currency'], array('id' => 'currency')), 'currency');
			$form_container->output_row($lang->newpoints_purchasecredits_price."<em>*</em>", $lang->newpoints_purchasecredits_price_desc, $form->generate_text_box('price', floatval($package['price']), array('id' => 'price')), 'price');
			$form_container->output_row($lang->newpoints_purchasecredits_credits."<em>*</em>", $lang->newpoints_purchasecredits_credits_desc, $form->generate_text_box('credits', floatval($package['credits']), array('id' => 'credits')), 'credits');
			$form_container->output_row($lang->newpoints_purchasecredits_accept_paypal, '', $form->generate_check_box('payment_paypal', '1', '', array('checked' => (int)$package['payment_paypal'])));
			$form_container->output_row($lang->newpoints_purchasecredits_accept_payza, '', $form->generate_check_box('payment_payza', '1', '', array('checked' => (int)$package['payment_payza'])));
			$form_container->output_row($lang->newpoints_purchasecredits_accept_coinpayments, '', $form->generate_check_box('payment_coinpayments', '1', '', array('checked' => (int)$package['payment_coinpayments'])));
			$form_container->end();

			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->newpoints_purchasecredits_submit);
			$buttons[] = $form->generate_reset_button($lang->newpoints_purchasecredits_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}

		$page->output_footer();
		exit;
	}
}

?>
