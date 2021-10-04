<?php
/***************************************************************************
 *
 *   NewPoints Purchase Credits plugin (/credits_payza.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   Integrates a credits purchasing system with NewPoints.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'credits_payza.php');

require_once "./inc/init.php";

header("Status: 200 OK");

define("SANDBOX_MODE", 0);

if (SANDBOX_MODE == 1)
{
	define("IPN_SECURITY_CODE", ""); // Enter your IPN sandbox security code like: define("IPN_SECURITY_CODE", "CODE_GOES_HERE");
	define("MY_MERCHANT_EMAIL", $mybb->settings['newpoints_purchasecredits_payza']);
}
else
{
	define("IPN_SECURITY_CODE", ""); // Enter your IPN security code like: define("IPN_SECURITY_CODE", "CODE_GOES_HERE");
	define("MY_MERCHANT_EMAIL", $mybb->settings['newpoints_purchasecredits_payza']);
}


define("DEBUG", 0);
$debug_email = '';

//Setting information about the transaction
$receivedSecurityCode = urldecode($_POST['ap_securitycode']);
$receivedMerchantEmailAddress = urldecode($_POST['ap_merchant']);
$transactionStatus = urldecode($_POST['ap_status']);
$testModeStatus = urldecode($_POST['ap_test']);
$purchaseType = urldecode($_POST['ap_purchasetype']);
$totalAmountReceived = urldecode($_POST['ap_totalamount']);
$feeAmount = urldecode($_POST['ap_feeamount']);
$netAmount = urldecode($_POST['ap_netamount']);
$transactionReferenceNumber = urldecode($_POST['ap_referencenumber']);
$currency = urldecode($_POST['ap_currency']);
$transactionDate= urldecode($_POST['ap_transactiondate']);
$transactionType= urldecode($_POST['ap_transactiontype']);

//Setting the customer's information from the IPN post variables
$customerFirstName = urldecode($_POST['ap_custfirstname']);
$customerLastName = urldecode($_POST['ap_custlastname']);
$customerAddress = urldecode($_POST['ap_custaddress']);
$customerCity = urldecode($_POST['ap_custcity']);
$customerState = urldecode($_POST['ap_custstate']);
$customerCountry = urldecode($_POST['ap_custcountry']);
$customerZipCode = urldecode($_POST['ap_custzip']);
$customerEmailAddress = urldecode($_POST['ap_custemailaddress']);

//Setting information about the purchased item from the IPN post variables
$myItemName = urldecode($_POST['ap_itemname']);
$myItemCode = urldecode($_POST['ap_itemcode']);
$myItemDescription = urldecode($_POST['ap_description']);
$myItemQuantity = urldecode($_POST['ap_quantity']);
$myItemAmount = urldecode($_POST['ap_amount']);

//Setting extra information about the purchased item from the IPN post variables
$additionalCharges = urldecode($_POST['ap_additionalcharges']);
$shippingCharges = urldecode($_POST['ap_shippingcharges']);
$taxAmount = urldecode($_POST['ap_taxamount']);
$discountAmount = urldecode($_POST['ap_discountamount']);

//Setting your customs fields received from the IPN post variables
$myCustomField_1 = urldecode($_POST['apc_1']);
$myCustomField_2 = urldecode($_POST['apc_2']);
$myCustomField_3 = urldecode($_POST['apc_3']);
$myCustomField_4 = urldecode($_POST['apc_4']);
$myCustomField_5 = urldecode($_POST['apc_5']);
$myCustomField_6 = urldecode($_POST['apc_6']);

if (DEBUG)
{
	$headers = "From: $debug_email\r\nReply-To: $debug_email";
	mail($debug_email, "Testing Payza 1", "Testing Payza", $headers);

	mylog("----------\nTesting Payza 1\n");
}

if ($receivedMerchantEmailAddress != MY_MERCHANT_EMAIL) {
	// The data was not meant for the business profile under this email address.
	// Take appropriate action
}
else {
	//Check if the security code matches
	if ($receivedSecurityCode != IPN_SECURITY_CODE) {
		// The data is NOT sent by Payza.
		// Take appropriate action.
	}
	else {
		if ($transactionStatus == "Success") {
			if ($testModeStatus == "1") {
				// Since Test Mode is ON, no transaction reference number will be returned.
				// Your site is currently being integrated with Payza IPN for TESTING PURPOSES
				// ONLY. Don't store any information in your production database and
				// DO NOT process this transaction as a real order.
			}
			else {
				// This REAL transaction is complete and the amount was paid successfully.
				// Process the order here by cross referencing the received data with your database.
				// Check that the total amount paid was the expected amount.
				// Check that the amount paid was for the correct service.
				// Check that the currency is correct.
				// ie: if ($totalAmountReceived == 50) ... etc ...
				// After verification, update your database accordingly.

				if (DEBUG)
				{
					$headers = "From: $debug_email\r\nReply-To: $debug_email";
					mail($debug_email, "Testing Payza 2", "Testing Payza - ".$myItemCode, $headers);

					mylog("Testing Payza 2\n");
				}

				// Get package info
				$query = $db->simple_select('newpoints_purchasecredits', '*', 'pid=\''.intval($myItemCode).'\'');
				$package = $db->fetch_array($query);
				if (!empty($package))
				{
					if (DEBUG)
					{
						$headers = "From: $debug_email\r\nReply-To: $debug_email";
						mail($debug_email, "Testing Payza 3", "Testing Payza", $headers);

						mylog("Testing Payza 3\n");
					}

					if ($package['currency'] == $currency && $package['price'] == $myItemAmount)
					{
						if (DEBUG)
						{
							$headers = "From: $debug_email\r\nReply-To: $debug_email";
							mail($debug_email, "Testing Payza 4", "Testing Payza", $headers);

							mylog("Testing Payza 4\n");
						}

						// Get user info
						$query = $db->simple_select('users', 'uid,username', 'uid=\''.intval($myCustomField_1).'\'');
						$user = $db->fetch_array($query);
						if (!empty($user))
						{
							if (DEBUG)
							{
								$headers = "From: $debug_email\r\nReply-To: $debug_email";
								mail($debug_email, "Testing Payza 5", "Testing Payza", $headers);

								mylog("Testing Payza 5\n");
							}

							$insert_array = array(
								'uid' => (int)$user['uid'],
								'username' => $db->escape_string($user['username']),
								'item_code' => $db->escape_string($myItemCode),
								'item_name' => $db->escape_string($myItemName),
								'item_amount' => $db->escape_string($myItemAmount),
								'currency' => $db->escape_string($currency),
								'first_name' => $db->escape_string($customerFirstName),
								'last_name' => $db->escape_string($customerLastName),
								'email' => $db->escape_string($customerLastName),
								'date' => $db->escape_string($transactionDate),
								'transaction_type' => $db->escape_string($transactionType),
							);

							$db->insert_query('newpoints_purchasecredits_log_ap', $insert_array);

							// Add points!
							newpoints_addpoints($user['uid'], floatval($package['credits']), 1, 1, false, true);
						}
					}
				}
			}
		}
		else {
				// Transaction was cancelled or an incorrect status was returned.
				// Take appropriate action.
		}
	}
}
exit;

function mylog($data)
{
	$fd = fopen("./payzalog.txt", "a");
	fwrite($fd, $data);
	fclose($fd);
}

?>
