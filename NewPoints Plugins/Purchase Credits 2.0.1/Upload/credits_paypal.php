<?php
/***************************************************************************
 *
 *   NewPoints Purchase Credits plugin (/credits_paypal.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *
 *
 *   Integrates a credits purchasing system with NewPoints.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'credits_paypal.php');

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

/*$emailtext = "Valid 0";
$headers = "From: youremail@mail.com\r\nReply-To: youremail@mail.com";
mail($email, "TEST 1", $emailtext . "\n\n", $headers);*/

// assign posted variables to local variables
$item_name = $db->escape_string($_POST['item_name']); // item name
$item_number = $db->escape_string($_POST['item_number']); // item id

$payment_status = $db->escape_string($_POST['payment_status']);
$mc_gross = $db->escape_string($_POST['mc_gross']);
$mc_currency = $db->escape_string($_POST['mc_currency']);
$txn_id = $db->escape_string($_POST['txn_id']);
$receiver_email = $db->escape_string($_POST['receiver_email']);
$payer_email = $db->escape_string($_POST['payer_email']);
$custom = $db->escape_string($_POST['custom']); // user id

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
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Content-Length: " . strlen($req)));
curl_setopt($ch, CURLOPT_HEADER , 0);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // should be the default for libCurl > 7.28.1
curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2); // TLS 1.2 required by PayPal | May require libCurl > 7.34 (http://devdocs.magento.com/guides/v2.0/install-gde/system-requirements_tls1-2.html)
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$curl_result = @curl_exec($ch);
$curl_err = curl_error($ch);
curl_close($ch);

if (strpos($curl_result, "VERIFIED")!==false) {

	// check the payment_status is Completed
	// check that txn_id has not been previously processed
	// check that receiver_email is your Primary PayPal email
	// check that payment_amount/payment_currency are correct
	// process payment

	/*$emailtext = "Valid 1";
    $headers = "From: youremail@mail.com\r\nReply-To: youremail@mail.com";
    mail($email, "TEST 1", $emailtext . "\n\n" . $req, $headers);*/

	// Get package info
	$query = $db->simple_select('newpoints_purchasecredits', '*', 'pid=\''.intval($item_number).'\'');
	$package = $db->fetch_array($query);
	if (!empty($package))
	{
	    /*$emailtext = "Valid 2";
        $headers = "From: youremail@mail.com\r\nReply-To: youremail@mail.com";
        mail($email, "TEST 2", "Package:". $package['pid'], $headers);*/

		// if payment status is Completed and the download price is equal to the amount of money the user has paid and the receiver emails matches the email in the Admin CP and the currency is the same, proceed
		if ($payment_status == "Completed" && floatval($mc_gross) == floatval($package['price']) && $receiver_email == $mybb->settings['newpoints_purchasecredits_paypal'] && $mc_currency == $package['currency'])
		{
	        /*
            $headers = "From: youremail@mail.com\r\nReply-To: youremail@mail.com";
            mail($email, "TEST 3", "Custom: ".$custom, $headers);*/

			// Get user info
			$query = $db->simple_select('users', 'uid,username', 'uid=\''.intval($custom).'\'');
			$user = $db->fetch_array($query);
			if (!empty($user))
			{
                /*
                $headers = "From: youremail@mail.com\r\nReply-To: youremail@mail.com";
                mail($email, "TEST 4", "INSERTING!", $headers);*/

				$insert_array = array(
					'uid' => intval($user['uid']),
					'receiver_email' => $receiver_email,
					'receiver_id' => $receiver_id,
					'business' => $business,
					'item_name' => $package['title'],
					'item_number' => $package['pid'],
					'quantity' => $quantity,
					'invoice' => $invoice,
					'custom' => $db->escape_string($user['username']),
					'payment_type' => $payment_type,
					'payment_status' => $payment_status,
					'pending_reason' => $pending_reason,
					'payment_date' => $payment_date,
					'exchange_rate' => $exchange_rate,
					'payment_gross' => $payment_gross,
					'payment_fee' => $payment_fee,
					'mc_gross' => $mc_gross,
					'mc_fee' => $mc_fee,
					'mc_currency' => $mc_currency,
					'mc_handling' => $mc_handling,
					'mc_shipping' => $mc_shipping,
					'tax' => $tax,
					'txn_id' => $txn_id,
					'txn_type' => $txn_type,
					'first_name' => $first_name,
					'last_name' => $last_name,
					'payer_business_name' => $payer_business_name,
					'payer_email' => $payer_email,
					'payer_id' => $payer_id,
					'payer_status' => $payer_status,
					'residence_country' => $residence_country,
					'parent_txn_id' => $parent_txn_id,
					'notify_version' => $notify_version,
					'verify_sign' => $verify_sign,
				);

				$db->insert_query('newpoints_purchasecredits_log_pp', $insert_array);

				// Add points!
				newpoints_addpoints($user['uid'], floatval($package['credits']), 1, 1, false, true);
			}
		}
	}
}
else if (strcmp ($curl_result, "INVALID") == 0) {

	// log for manual investigation
	/*$emailtext = "Invalid";
	$headers = "From: youremail@mail.com\r\nReply-To: youremail@mail.com";
	mail($email, "$curl_result", $emailtext . "\n\n" . $req, $headers);*/
}

exit;

?>
