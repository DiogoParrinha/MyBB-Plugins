<?php
/***************************************************************************
 *
 *   NewPoints Purchase Credits plugin (/credits_coinpayments.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *   
 *   Integrates a credits purchasing system with NewPoints.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'credits_coinpayments.php');

require_once "./inc/init.php";

header("Status: 200 OK");

// Fill these in with the information from your CoinPayments.net account.
$cp_merchant_id = $mybb->settings['newpoints_purchasecredits_coinpayments'];
$cp_ipn_secret = ''; // ENTER IPN SECRET HERE, inside the single quotes
$cp_debug_email = '';


$email = ""; // email to receive debug emails

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

/* TEST

$_POST['merchant'] = '106dff0a6ae88371ef8866f2d7872aa9';
$_POST['first_name'] = 'test';
$_POST['last_name'] = 'test';
$_POST['buyer_email'] = 'parrinhadiogo@gmail.com';
$_POST['status'] = 100;
$_POST['status_text'] = 'Complete';
$_POST['txn_id'] = 'CPAE2JBJ8SIHKJK85HPELXB0GL';
$_POST['currency1'] = 'BTC';
$_POST['currency2'] = 'BTC';
$_POST['amount1'] = 5;
$_POST['amount2'] = 5;
$_POST['subtotal'] = 5;
$_POST['fee'] = 0.02;
$_POST['item_amount'] = 0;
$_POST['item_name'] = 'P2';
$_POST['item_number'] = 2;
$_POST['custom'] = 1;
$_POST['received_confirms'] = 2;
$_POST['received_amount'] = 5-0.02;

*/

// HMAC Signature verified at this point, load some variables.

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
$q = $db->simple_select('newpoints_purchasecredits_log_cp', '*', 'txn_id=\''.$txn_id.'\' AND status_text=\''.$status_text.'\'');
$exists = $db->fetch_array($q);

// If empty, it wasn't processed yet
if(empty($exists))
{
	newpoints_lang_load("newpoints_purchasecredits");

	// Does the user exist?
	$user = get_user(intval($custom));
	if(empty($user))
	{
		errorAndDie('Invalid user');
	}
	else
	{
		// Verify if package exists
		$query = $db->simple_select('newpoints_purchasecredits', '*', 'pid=\''.intval($item_number).'\'');
		$package = $db->fetch_array($query);
		if(!$package)
		{
			errorAndDie("Invalid package\nItem Number:".$item_number."\nItem Name:".$item_name);
		}

		// Insert into log
		$db->insert_query("newpoints_purchasecredits_log_cp", array(
			'username' => $db->escape_string($user['username']),
			'uid' => $custom,
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
			'date' => TIME_NOW,
		));

		// Now is the time!
		if(($status == 100 || $status == 2) && (float)$amount1 == (float)$package['price'] && $currency1 == $package['currency'])
		{
			// Give points
			newpoints_addpoints($user['uid'], floatval($package['credits']), 1, 1, false, true);
		}

		// If Refunded, Reversed or Canceled, lets make sure the user gets points deducted
		if($payment_status < 0)
		{
			newpoints_addpoints($user['uid'], -floatval($package['credits']), 1, 1, false, true);
		}
	}
}
else
{
	errorAndDie('txn_id already exists: '.$txn_id);
}

exit;

?>
