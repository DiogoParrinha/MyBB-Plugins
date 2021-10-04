<?php
/***************************************************************************
 *
 *   NewPoints Purchase Credits plugin (/upgrade20.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   Integrates a credits purchasing system with NewPoints.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
require_once "./inc/init.php";

echo "Adding new fields...";
if(!$db->field_exists("payment_paypal", "newpoints_purchasecredits"))
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."newpoints_purchasecredits` ADD `payment_paypal` tinyint(0) unsigned not null default 0;");
if(!$db->field_exists("payment_payza", "newpoints_purchasecredits"))
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."newpoints_purchasecredits` ADD `payment_payza` tinyint(0) unsigned not null default 0;");
if(!$db->field_exists("payment_coinpayments", "newpoints_purchasecredits"))
	$db->write_query("ALTER TABLE `".TABLE_PREFIX."newpoints_purchasecredits` ADD `payment_coinpayments` tinyint(0) unsigned not null default 0;");
echo "Done!<br />";

echo "Creating new table...";
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
	) ENGINE=MyISAM");
echo "Done!<br />";

echo "Creating new setting...";
newpoints_add_setting('newpoints_purchasecredits_coinpayments', 'newpoints_purchasecredits', 'CoinPayments Merchant ID', 'Enter the CoinPayments merchant ID. Leave this empty to disable this payment type.', 'text', '', 2);
rebuild_settings();
echo "Done!<br />";

echo "Upgrade finished!<br />";
exit;

?>
