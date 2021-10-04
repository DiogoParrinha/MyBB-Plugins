<?php

/***************************************************************************
 *
 *   MySubscriptions plugin (/mysubscriptions_coinpayments.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'mysubscriptions_ipn.php');

require_once "./inc/init.php";

// Fill these in with the information from your CoinPayments.net account.
$cp_merchant_id = $mybb->settings['mysubscriptions_coinpayments_merchantid'];
$cp_ipn_secret = '';
$cp_debug_email = ''; // email used for debugging

function errorAndDie($error_msg) {
	global $cp_debug_email;
	if (!empty($cp_debug_email)) {
		$report = 'Error: '.$error_msg."\n\n";
		$report .= "POST Data\n\n";
		foreach ($_POST as $k => $v) {
			$report .= "|$k| = |$v|\n";
		}
		my_mail($cp_debug_email, 'CoinPayments IPN Error', nl2br($report));
	}
	die('IPN Error: '.$error_msg);
}

function emailDebug($msg) {
	global $cp_debug_email;
	if (!empty($cp_debug_email)) {
		$report = 'Debug: '.$msg."\n\n";
		$report .= "POST Data\n\n";
		foreach ($_POST as $k => $v) {
			$report .= "|$k| = |$v|\n";
		}
		my_mail($cp_debug_email, 'CoinPayments Debug Message', nl2br($report));
	}
}

emailDebug("[0/1] Starting.");

if (!isset($_POST['ipn_mode']) || $_POST['ipn_mode'] != 'hmac') {
	errorAndDie('IPN Mode is not HMAC');
}

if (!isset($_SERVER['HTTP_HMAC']) || empty($_SERVER['HTTP_HMAC'])) {
	errorAndDie('No HMAC signature sent.');
}

$request = file_get_contents('php://input');
if ($request === FALSE || empty($request)) {
	errorAndDie('Error reading POST data');
}

if (!isset($_POST['merchant']) || $_POST['merchant'] != trim($cp_merchant_id)) {
	errorAndDie('No or incorrect Merchant ID passed');
}

$hmac = hash_hmac("sha512", $request, trim($cp_ipn_secret));
if ($hmac != $_SERVER['HTTP_HMAC']) {
	errorAndDie('HMAC signature does not match');
}

// HMAC Signature verified at this point, load some variables.
emailDebug("[0/2] HMAC signature verified.");

$txn_id = $db->escape_string($_POST['txn_id']);
$custom = $db->escape_string($_POST['custom']);
$item_name = $db->escape_string($_POST['item_name']);
$item_desc = $db->escape_string($_POST['item_desc']);
$item_number = (int)$_POST['item_number'];
$status = (int)$_POST['status'];
$amount1 = floatval($_POST['amount1']);
$amount2 = floatval($_POST['amount2']);
$fee = floatval($_POST['fee']);
$tax = floatval($_POST['tax']);
$subtotal = floatval($_POST['subtotal']);
$currency1 = $db->escape_string($_POST['currency1']);
$currency2 = $db->escape_string($_POST['currency2']);
$status = intval($_POST['status']);
$status_text = $db->escape_string($_POST['status_text']);
$received_amount = $db->escape_string($_POST['received_amount']);
$received_confirms = $db->escape_string($_POST['received_confirms']);
$first_name = $db->escape_string($_POST['first_name']);
$last_name = $db->escape_string($_POST['last_name']);
$buyer_email = $db->escape_string($_POST['email']);
$merchant = $db->escape_string($_POST['merchant']);

// Find an existing entry in our log with this txn_id
$q = $db->simple_select('mysubscriptions_coinpayments_log', '*', 'txn_id=\''.$txn_id.'\' AND status_text=\''.$status_text.'\'');
$exists = $db->fetch_array($q);

// If empty, it wasn't processed yet
if(empty($exists))
{
    emailDebug("[1] Subscription does not exist yet for ".$txn_id);

	$lang->load("mysubscriptions");

	// Extract data from 'custom'
	$data = explode(':', $custom);
	$sid = (int)$data[0];
	$time = $data[1];
	$period = (int)$data[2];
	$price = (float)$data[3];
	$uid = (int)$data[4];

	// does the user exist?
	$user = get_user(intval($uid));
	if(empty($user))
	{
		errorAndDie('Invalid user');
	}
	else
	{
        emailDebug("[2] User found: ".$uid);

		// verify if subscription exists
		$query = $db->simple_select('mysubscriptions_subscriptions', '*', 'sid=\''.$item_number.'\''); // check sid
		$sub = $db->fetch_array($query);
		if(!$sub)
		{
			errorAndDie("Invalid subscription\nItem Number:".$item_number."\nItem Name:".$item_name);
		}

        emailDebug("[3] Subscription plan found: ".$item_number);

		$additional = $sub['additional'];
		$endgroup = intval($user['usergroup']);
		$uname = $db->escape_string($user['username']);

		$query = $db->query("
			SELECT *
			FROM ".TABLE_PREFIX."mysubscriptions_coinpayments_log
			WHERE enddate!=0 AND expired=0 AND sid={$sub['sid']} AND additional={$additional} AND uid=".(int)$uid." AND (status=100 OR status=2)
		");
		$exists = $db->fetch_array($query);
		if(!empty($exists))
		{
			errorAndDie("User already has active subscription for {$sub['sid']}");
		}

        emailDebug("[4] User does not have active subscription, will upgrade user.");

		// Validate selected time,period,price
		$time_period = '';
		$tp_array = my_unserialize($sub['time_period']);
		if(empty($tp_array))
		{
			errorAndDie("Empty time period array");
		}

        emailDebug("[5] Time period array is not empty.");

		// Check if the one we selected is right
		if(!isset($tp_array[$time]))
		{
			errorAndDie("Invalid time");
		}
		else
		{
			$sel_tp = array($period, $price);
			if(!in_array($sel_tp, $tp_array[$time]))
			{
				errorAndDie("Invalid period/price");
			}
		}

        emailDebug("[5] Time period array is valid.");

		// Calculate ending period
		if($period != 0)
		{
			switch($time)
			{
				case 'D':
					$enddate = TIME_NOW + (24*60*60*$period);
				break;
				case 'W':
					$enddate = TIME_NOW + (7*24*60*60*$period);
				break;
				case 'M':
					$enddate = TIME_NOW + (30*24*60*60*$period);
				break;
				case 'Y':
					$enddate = TIME_NOW + (365*24*60*60*$period);
				break;
				default:
					errorAndDie("Invalid time/period switch");
				break;
			}
		}
		else
		{
			$enddate = 0;
		}

        emailDebug("[5] Ending period calculated.");

		// Slotted subs enabled?
		if($mybb->settings['mysubscriptions_locked_period'] != 0 && $sub['maxactive'] > 0)
		{
			// Count total active subscribers
			$query = $db->query("
				SELECT COUNT(l.lid) as totalsubs
				FROM ".TABLE_PREFIX."mysubscriptions_log l
				WHERE l.enddate!=0 AND l.expired=0 AND l.sid=".(int)$sub['sid']."
			");
			$totalsubs = $db->fetch_field($query, 'totalsubs');

			$lockedsubs = array();
			if($sub['lockedsubs'] != '')
			{
				$lockedsubs = explode(',', $sub['lockedsubs']);
			}

			// Equal to or greater than maxactive?
			if($totalsubs+count($lockedsubs) >= $sub['maxactive'])
			{
				// Check if we're in the lockedsubs
				if(!empty($lockedsubs))
				{
					if(!in_array($uid, $lockedsubs))
					{
						errorAndDie("Not in locked");
					}
				}
				else
				{
					errorAndDie("Empty locked");
				}
			}

            emailDebug("[6] Slotted subscriptions validated.");
		}
        else {
            emailDebug("[6] Slotted subscriptions disabled.");
        }

        emailDebug("[7] Inserting CoinPayments log.");

		$db->insert_query("mysubscriptions_coinpayments_log", array(
			'uname' => $uname,
			'uid' => $uid,
			'additional' => $additional,
			'sid' => $item_number,
			'endgroup' => $endgroup,
			'merchant' => $merchant,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'buyer_email' => $buyer_email,
			'status' => $status,
			'status_text' => $status_text,
			'txn_id' => $txn_id,
			'currency1' => $currency1,
			'currency2' => $currency2,
			'amount1' => $amount1,
			'amount2' => $amount2,
			'subtotal' => $subtotal,
			'tax' => $tax,
			'fee' => $fee,
			'item_amount' => $item_amount,
			'item_name' => $item_name,
			'item_desc' => $item_desc,
			'item_number' => $item_number,
			'received_amount' => $received_amount,
			'received_confirms' => $received_confirms,
			'custom' => $custom,
			'timestamp' => TIME_NOW,
			'enddate' => $enddate,
			'expired' => 0
		));

        emailDebug("[8] Log inserted, validating data. Status: ".$status." | Amount1: ".$amount1." | SubPrice: ".$sub['price']." | Currency1: ".$currency1." | SubCurrency: ".$mybb->settings['mysubscriptions_coinpayments_currency']);

		// now is the time!
		if($sub['enabled'] == 1 && ($status == 100 || $status == 2) && $amount1 == $price && $currency1 == $mybb->settings['mysubscriptions_coinpayments_currency'])
		{
            emailDebug("[9] User will join group ".$sub['group']);

			if ($sub['additional'])
				join_usergroup($uid, $sub['group']);
			else
				$db->update_query('users', array('usergroup' => $sub['group']), 'uid=\''.$uid.'\'');

			if($sub['message'] == '')
			{
				$sub['message'] = $lang->sprintf($lang->mysubscriptions_success_message, $sub['title']);
			}

			// Remove us from lockedsubs
			$lockedsubs = array();
			if($sub['lockedsubs'] != '')
			{
				$lockedsubs = explode(',', $sub['lockedsubs']);
				$k = array_search($uid, $lockedsubs);
				unset($lockedsubs[$k]);
				$db->update_query('mysubscriptions_subscriptions', array('lockedsubs' => $db->escape_string(implode(',', $lockedsubs))), 'sid='.$sub['sid']);
			}

            emailDebug("[10] Sending PM.");

			send_pm(array('receivepms' => 1, 'subject' => $lang->mysubscriptions_success_title, 'message' => $sub['message'], 'touid' => $uid), 1);
			send_pm(array('receivepms' => 1, 'subject' => $lang->mysubscriptions_success_title_admin, 'message' => $lang->sprintf($lang->mysubscriptions_success_message_admin, $sub['title']), 'touid' => 1), $uid);
		}

		// If Refunded, Reversed or Canceled, lets make sure the user gets "unsubscribed"
		if($payment_status < 0)
		{
			$query = $db->simple_select('mysubscriptions_coinpayments_log', '*', 'sid=\''.$item_number.'\' AND uid=\''.(int)$uid.'\' AND (status=100 OR status=2)', array('order_by' => 'lid', 'order_dir' => 'desc', 'limit' => 1));
			$sub = $db->fetch_array($query);
			if(!$sub)
			{
				errorAndDie('Invalid subscription (Refuned/Reversed)');
			}

			if ($sub['additional'])
				leave_usergroup($uid, (int)$sub['endgroup']);
			else
				$query = $db->query("UPDATE ".TABLE_PREFIX."users SET usergroup='".$sub['endgroup']."' WHERE uid='".(int)$uid."'");
		}
	}
}
else
{
    emailDebug("[1] Subscription already exists for ".$txn_id);
	errorAndDie('txn_id already exists: '.$txn_id);
}

exit;

?>
