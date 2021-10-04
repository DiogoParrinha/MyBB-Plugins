<?php

/***************************************************************************
 *
 *   MySubscriptions plugin (/mysubscriptions_paypal2.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2014 Diogo Parrinha
 *
 *
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'mysubscriptions_paypal2.php');

require_once "./inc/init.php";

if (!$_POST['verify_sign'])
{
	header("Status: 404 Not Found");
	exit;
}
else
	header("Status: 200 OK");

$email = ""; // email to receive debug emails
$sandbox = ''; // set to .sandbox if you want to use sandbox
$debugging = true;

if($debugging)
	my_mail($email, "IPN - DEBUGGING 1", "email working");

/*$emailtext = "Valid 0";
$headers = "From: youremail@mail.com\r\nReply-To: youremail@mail.com";
mail($email, "$reply", $emailtext . "\n\n" . $req, $headers);*/

// assign posted variables to local variables
$item_name = $db->escape_string($_POST['item_name']); // item name

$item_number = intval($_POST['item_number']);

$payment_status = $db->escape_string($_POST['payment_status']);
$mc_gross = $db->escape_string($_POST['mc_gross']);
$mc_currency = $db->escape_string($_POST['mc_currency']);
$txn_id = $db->escape_string($_POST['txn_id']);
$receiver_email = $db->escape_string($_POST['receiver_email']);
$payer_email = $db->escape_string($_POST['payer_email']);
$custom = $db->escape_string($_POST['custom']);

$first_name = $db->escape_string($_POST['first_name']);
$last_name = $db->escape_string($_POST['last_name']);
$payer_business_name = $db->escape_string($_POST['payer_business_name']);
$payer_id = $db->escape_string($_POST['payer_id']);
$payer_status = $db->escape_string($_POST['payer_status']);
$residence_country = $db->escape_string($_POST['residence_country']);
$business = $db->escape_string($_POST['business']);
$quantity = $db->escape_string($_POST['quantity']);
$receiver_id = $db->escape_string($_POST['receiver_id']);
$invoice = $db->escape_string($_POST['invoice']);
$tax = $db->escape_string($_POST['tax']);
$mc_handling = $db->escape_string($_POST['mc_handling']);
$mc_shipping = $db->escape_string($_POST['mc_shipping']);
$num_cart_items = $db->escape_string($_POST['num_cart_items']);
$parent_txn_id = $db->escape_string($_POST['parent_txn_id']);
$payment_date = $db->escape_string($_POST['payment_date']);
$payment_type = $db->escape_string($_POST['payment_type']);
$txn_type = $db->escape_string($_POST['txn_type']);
$exchange_rate = $db->escape_string($_POST['exchange_rate']);
$mc_fee = $db->escape_string($_POST['mc_fee']);
$payment_fee = $db->escape_string($_POST['payment_fee']);
$payment_gross = $db->escape_string($_POST['payment_gross']);
$notify_version = $db->escape_string($_POST['notify_version']);
$verify_sign = $db->escape_string($_POST['verify_sign']);

// read the post from PayPal system and add 'cmd'
if($_SERVER['REQUEST_METHOD']!="POST") die("No data");
$req = 'cmd=_notify-validate';
foreach ($_POST as $key => $value) {
	$value = urlencode(stripslashes($value));
	$req .= "&$key=$value";
}

// post back to PayPal system to validate
$url= 'https://www'.$sandbox.'.paypal.com/cgi-bin/webscr';
$curl_result = $curl_err = '';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", 'Connection: Close', 'User-Agent: Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0', "Content-Length: " . strlen($req)));
curl_setopt($ch, CURLOPT_HEADER , 0);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // should be the default for libCurl > 7.28.1
curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2); // TLS 1.2 required by PayPal | May require libCurl > 7.34 (http://devdocs.magento.com/guides/v2.0/install-gde/system-requirements_tls1-2.html)
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);


if(!($curl_result = curl_exec($ch)))
{
	if($debugging)
	{
		$cInfo = curl_getinfo($ch);
		my_mail($email, "IPN ERROR #1", "Error from cURL: #".curl_errno($ch).': ' . curl_error($ch)."\n\n".implode("\n", $cInfo));
	}
    curl_close($ch);
    exit;
}
curl_close($ch);

if($curl_result === false)
{
	if($debugging)
		my_mail($email, "IPN - CURL ERROR", 'Curl error: ' . $curl_err);
}

if (strpos($curl_result, "VERIFIED")!==false) {

	// check the payment_status is Completed
	// check that txn_id has not been previously processed
	// check that receiver_email is your Primary PayPal email
	// check that payment_amount/payment_currency are correct
	// process payment

	$lang->load("mysubscriptions");

	$valid = true;

	// Extract data from 'custom'
	$data = explode(':', $custom);
	$sid = (int)$data[0];
	$time = $data[1];
	$period = (int)$data[2];
	$price = (float)$data[3];
	$uid = (int)$data[4];

	// does the user exist?
	$user = get_user(intval($uid));
	if (empty($user))
	{
		if ($debugging)
			my_mail($email, "IPN - Subscription Invalid #1", "\nInvalid user.\n");
	}
	else {
		// verify if subscription exists
		$query = $db->simple_select('mysubscriptions_subscriptions', '*', 'sid=\''.$item_number.'\''); // check sid
		$sub = $db->fetch_array($query);
		if (!$sub)
		{
			if ($debugging)
				my_mail($email, "IPN - Subscription Invalid #2", "\nInvalid subscription\nItem Number:".$item_number."\nItem Name:".$item_name);
			$valid = false;
		}

		$additional = $sub['additional'];
		$endgroup = intval($user['usergroup']);
		$uname = $db->escape_string($user['username']);

		// Does the user have an active subscription for the same group?
		$query = $db->query("
			SELECT *
			FROM ".TABLE_PREFIX."mysubscriptions_log
			WHERE expired=0 AND sid={$sub['sid']} AND additional={$additional} AND uid=".(int)$uid."
		");
		$exists = $db->fetch_array($query);
		if(!empty($exists))
		{
			$valid = false; // do not accept re-subscribe
		}

		if ($debugging)
			my_mail($email, "IPN - Validation", "\nis valid: ".intval($valid));

		// Validate selected time,period,price
		$time_period = '';
		$tp_array = my_unserialize($sub['time_period']);
		if(empty($tp_array))
		{
			$valid = false;
			if ($debugging)
				my_mail($email, "IPN - Empty TP", "Empty time period array");
		}

		// Check if the one we selected is right
		if(!isset($tp_array[$time]))
		{
			$valid = false;
			if ($debugging)
				my_mail($email, "IPN - Invalid Time", "Invalid time");
		}
		else
		{
			$sel_tp = array($period, $price);
			if(!in_array($sel_tp, $tp_array[$time]))
			{
				$valid = false;
				if ($debugging)
					my_mail($email, "IPN - Invalid Period/Price", "Invalid period/price");
			}
		}

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
					// Some problem!
					$enddate = 0;
					$valid = false;
				break;
			}
		}
		else
		{
			$enddate = 0;
		}

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
						$valid = false;
						if ($debugging)
							my_mail($email, "IPN - Not in Locked", "Not in Locked");
					}
				}
				else
				{
					$valid = false;
					if ($debugging)
						my_mail($email, "IPN - Empty Locked", "Empty Locked");
				}
			}
		}

		// Fake period3
		$period3 = (int)$period." ".$db->escape_string($time);

		if($valid)
		{
			$query = $db->query("INSERT INTO `".TABLE_PREFIX."mysubscriptions_log` values (
				0,
				'$uname',
				'$uid',
				'$additional',
				'$item_number',
				'$endgroup',
				'$receiver_email',
				'$receiver_id',
				'$business',
				'$item_name',
				'$item_number',
				'$quantity',
				'$invoice',
				'$option_name1',
				'$option_selection1',
				'$option_name2',
				'$option_selection2',
				'$payment_type',
				'$payment_status',
				'$pending_reason',
				'$reason_code',
				'$payment_date',
				'$settle_amount',
				'$settle_currency',
				'$exchange_rate',
				'$payment_gross',
				'$payment_fee',
				'$mc_gross',
				'$mc_fee',
				'$mc_currency',
				'$mc_handling',
				'$mc_shipping',
				'$tax',
				'$txn_id',
				'$txn_type',
				'$for_auction',
				'$auction_buyer_id',
				'$auction_closing_date',
				'$auction_multi_item',
				'$first_name',
				'$last_name',
				'$address_name',
				'$address_street',
				'$address_city',
				'$address_state',
				'$address_zip',
				'$address_country',
				'$address_country_code',
				'$address_status',
				'$payer_business_name',
				'$payer_email',
				'$payer_id',
				'$payer_status',
				'$residence_country',
				'$memo',
				'$subscr_date',
				'$subscr_effective',
				'$period1',
				'$period2',
				'$period3',
				'$amount1',
				'$amount2',
				'$amount3',
				'$mc_amount1',
				'$mc_amount2',
				'$mc_amount3',
				'$recurring',
				'$reattempt',
				'$retry_at',
				'$recur_times',
				'$username',
				'$password',
				'$subscr_id',
				'$auth_id',
				'$auth_exp',
				'$auth_amount',
				'$auth_status',
				'$transaction_entity',
				'$remaining_settle',
				'$parent_txn_id',
				'$case_id',
				'$case_type',
				'$case_creation_date',
				'$notify_version',
				'$verify_sign',
				'".TIME_NOW."',
				'".$enddate."',
				'0',
				'0',
				'0')");
		}

		if ($debugging)
			my_mail($email, "IPN VERIFICATION ".(int)$valid, "\npayment_status: {$payment_status}\nmc_gross: {$mc_gross}\nprice: {$price}\nreceiver_email: {$receiver_email}\nmybb->settings['mysubscriptions_paypal_email']: {$mybb->settings['mysubscriptions_paypal_email']}\nmc_currency: {$mc_currency}\nmybb->settings['mysubscriptions_paypal_currency']: {$mybb->settings['mysubscriptions_paypal_currency']}\n\n");

		if ($sub['enabled'] == 1 && $valid === true && $payment_status == "Completed" && $mc_gross == $price && $receiver_email == $mybb->settings['mysubscriptions_paypal_email'] && $mc_currency == $mybb->settings['mysubscriptions_paypal_currency']) // now is the time!
		{
			if ($sub['additional'])
				join_usergroup($uid, $sub['group']);
			else
				$db->update_query('users', array('usergroup' => $sub['group']), 'uid=\''.$uid.'\'');

			// send PM to subscriber
			if ($debugging)
				my_mail($email, "IPN Success", "\n Verified IPN $txn_type  Transaction, sending PM.\n \n $postipn\n");

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

			send_pm(array('receivepms' => 1, 'subject' => $lang->mysubscriptions_success_title, 'message' => $sub['message'], 'touid' => $uid), 1);
			send_pm(array('receivepms' => 1, 'subject' => $lang->mysubscriptions_success_title_admin, 'message' => $lang->sprintf($lang->mysubscriptions_success_message_admin, $sub['title']), 'touid' => 1), $uid);
		}

		// If Refuned or Reversed, lets make sure the user gets "unsubscribed"
		if($payment_status == "Refunded" || $payment_status == "Reversed")
		{
			$query = $db->simple_select('mysubscriptions_log', '*', 'sid=\''.$item_number.'\' AND uid=\''.(int)$uid.'\' AND payment_status=\'Completed\'', array('order_by' => 'lid', 'order_dir' => 'desc', 'limit' => 1));
			$sub = $db->fetch_array($query);
			if(!$sub)
			{
				if ($debugging)
					my_mail($email, "IPN - Subscription Invalid", "\nInvalid subscription (Refuned/Reversed)\n");
			}

			if ($sub['additional'])
				leave_usergroup($uid, (int)$sub['endgroup']);
			else
				$query = $db->query("UPDATE ".TABLE_PREFIX."users SET usergroup='".$sub['endgroup']."' WHERE uid='".(int)$uid."'");
		}
	}
}
else if (strcmp ($curl_result, "INVALID") == 0) {

	// log for manual investigation
	/*$emailtext = "Invalid";
	$headers = "From: youremail@mail.com\r\nReply-To: youremail@mail.com";
	mail($email, "$reply", $emailtext . "\n\n" . $req, $headers);*/
}

exit;

?>
