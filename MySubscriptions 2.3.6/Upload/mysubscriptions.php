<?php

/***************************************************************************
 *
 *   MySubscriptions plugin (/mysubscriptions.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2014 Diogo Parrinha
 *
 *
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'mysubscriptions.php');

$sandbox = ''; // set to .sandbox if you want to use sandbox

// Templates used by MySubscriptions
$templatelist  = "mysubscriptions,mysubscriptions_row,mysubscriptions_row_empty";

require_once "./global.php";

require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;

$plugins->run_hooks("mysubscriptions_start");

// load language
$lang->load("mysubscriptions");

$subscriptions = '';
$bgcolor = alt_trow();
$subs = $options = array();

// CHANGE THIS TO ALTER WHICH ONES CAN BE PARSED
$parser_options = array(
	'allow_mycode' => 1,
	'allow_smilies' => 1,
	'allow_imgcode' => 1,
	'allow_html' => 1,
	'filter_badwords' => 0,
	'allow_video' => 0
);

// get all groups
$query = $db->simple_select('usergroups', 'title, gid', '', array('order_by' => 'gid', 'order_dir' => 'asc'));
while($group = $db->fetch_array($query, 'title, gid'))
{
	$groups[$group['gid']] = $group['title'];
}

$subplans = '';

$lang->mysubscriptions_login_register = $lang->sprintf($lang->mysubscriptions_login_register, $mybb->settings['bburl'].'/member.php?action=login', $mybb->settings['bburl'].'/member.php?action=register');

//now query all plans
$query = $db->simple_select('mysubscriptions_subscriptions', '*', 'enabled=1', array('order_by' => 'disporder', 'order_dir' => 'ASC'));
while ($sub = $db->fetch_array($query))
{
	if($sub['visible'] != '0' && !mysubscriptions_check_permissions($sub['visible']) && $mybb->settings['mysubscriptions_show_plans'] == 0)
		continue;

	$bgcolor = alt_trow();

	$sub['sid'] = (int)$sub['sid'];
	$sub['btitle'] = htmlspecialchars_uni($sub['title']);
	$sub['title'] = $parser->parse_message($sub['title'], $parser_options);
	$sub['description'] = $parser->parse_message($sub['description'], $parser_options);
	if ($sub['additional'])
	{
		$sub['description'] .= "<br /><strong>".$lang->mysubscriptions_additional_notice."</strong>";
	}

	if (!isset($groups[$sub['group']]))
	{
		// invalid new group
		$sub['usergroup'] = 'INVALID';
	}
	else
		$sub['usergroup'] = htmlspecialchars_uni($groups[$sub['group']]);

	// Time/Periods
	$sub['price'] = '';
	$sub['custom'] = '';
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

				if($sub['price'] == '')
					$sub['price'] = (float)$p[1]; // default

				if($sub['custom'] == '')
					$sub['custom'] = (int)$sub['sid'].':'.htmlspecialchars_uni($t).':'.(int)$p[0].':'.(float)$p[1].':'.(int)$mybb->user['uid']; // default

				$time_period .= '<option value="'.(int)$sub['sid'].':'.htmlspecialchars_uni($t).':'.(int)$p[0].':'.(float)$p[1].':'.(int)$mybb->user['uid'].'">'.(int)$p[0].' '.$sub['time'].' - '.number_format($p[1], 2).' '.$mybb->settings['mysubscriptions_paypal_currency'].'</option>';
				$nl = "\n";
			}
		}
	}

	$sub['time_period'] = $time_period;

	// PayPal Button
	$sub['button'] = '';

	if($mybb->settings['mysubscriptions_paypal_email'] != '')
	{
		$sub['button'] = '
			<form action="https://www'.$sandbox.'.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_xclick">
			<input type="hidden" name="business" value="'.$mybb->settings['mysubscriptions_paypal_email'].'">
			<input type="hidden" name="currency_code" value="'.$mybb->settings['mysubscriptions_paypal_currency'].'">
			<input type="hidden" name="notify_url" value="'.$mybb->settings['bburl'].'/mysubscriptions_paypal2.php" />

			<input type="hidden" name="item_name" value="'.$sub['btitle'].'" />
			<input type="hidden" name="item_number" value="'.$sub['sid'].'" />
			<input type="hidden" id="custom_pp_'.(int)$sub['sid'].'" name="custom" value="'.$sub['custom'].'" />
			<input type="hidden" id="amount_pp_'.(int)$sub['sid'].'" name="amount" value="'.$sub['price'].'">

			<input type="hidden" name="no_shipping" value="1" />

			<input type="hidden" name="return" value="'.$mybb->settings['bburl'].'/" />
			<input type="hidden" name="cancel_return" value="'.$mybb->settings['bburl'].'" />
			<input type="hidden" name="no_note" value="1">

			<input type="hidden" name="image_url" value="'.$theme['logo'].'" />

			<input type="image" src="http://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" border="0" name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!">

			</form>
			<br />
		';
	}

	if($mybb->settings['mysubscriptions_coinpayments_merchantid'] != '')
	{
		$sub['button'] .= '<form action="https://www.coinpayments.net/index.php" method="post">
			<input type="hidden" name="cmd" value="_pay">
			<input type="hidden" name="reset" value="1">
			<input type="hidden" name="merchant" value="'.$mybb->settings['mysubscriptions_coinpayments_merchantid'].'">
			<input type="hidden" name="item_name" value="'.$sub['btitle'].'">
			<input type="hidden" name="item_number" value="'.$sub['sid'].'">
			<input type="hidden" name="custom" id="custom_cp_'.(int)$sub['sid'].'" value="'.$sub['custom'].'">
			<input type="hidden" name="currency" value="'.$mybb->settings['mysubscriptions_coinpayments_currency'].'">
			<input type="hidden" name="amountf" id="amount_cp_'.(int)$sub['sid'].'" value="'.$sub['price'].'">
			<input type="hidden" name="quantity" value="1">
			<input type="hidden" name="allow_quantity" value="0">
			<input type="hidden" name="want_shipping" value="0">
			<input type="hidden" name="success_url" value="'.$mybb->settings['bburl'].'/">
			<input type="hidden" name="cancel_url" value="'.$mybb->settings['bburl'].'/">
			<input type="hidden" name="ipn_url" value="'.$mybb->settings['bburl'].'/mysubscriptions_coinpayments.php">
			<input type="hidden" name="allow_extra" value="0">
			<input type="image" src="https://www.coinpayments.net/images/pub/buynow-wide-blue.png" alt="Buy Now with CoinPayments.net">
		</form>
		<br />';
	}

	if($mybb->user['uid'] <= 0)
		$sub['button'] = $lang->mysubscriptions_login_register;
	else
	{
		// Slotted subs enabled?
		if($mybb->settings['mysubscriptions_locked_period'] != 0 && $sub['maxactive'] > 0)
		{
			// Any reserved/locked subs? If yes, then re-build the 'lockedsubs' field
			if($sub['lockedsubs'] != '')
			{
				$lockedsubs = array();
				$q = $db->query("
					SELECT l.uid
					FROM ".TABLE_PREFIX."mysubscriptions_log l
					WHERE l.enddate>".(TIME_NOW-(int)$mybb->settings['mysubscriptions_locked_period'])." AND l.expired=1 AND l.sid=".(int)$sub['sid']." AND l.uid IN(".$db->escape_string($sub['lockedsubs']).")
				");
				while($u = $db->fetch_field($q, 'uid'))
				{
					$lockedsubs[] = (int)$u;
				}

				$lockedsubs = array_unique($lockedsubs);
				$db->update_query('mysubscriptions_subscriptions', array('lockedsubs' => $db->escape_string(implode(',', $lockedsubs))), 'sid='.(int)$sub['sid']);
			}

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
					if(!in_array($mybb->user['uid'], $lockedsubs))
					{
						$sub['button'] = $lang->mysubscriptions_max_subs;
					}
				}
				else
					$sub['button'] = $lang->mysubscriptions_max_subs;
			}
		}

		if(!mysubscriptions_check_permissions($sub['visible']) && $mybb->settings['mysubscriptions_show_plans'] == 1)
		{
			$sub['button'] = $lang->mysubscriptions_cant_upgrade;
		}
	}

	eval("\$subplans .= \"".$templates->get('mysubscriptions_row')."\";");
}

if (empty($subplans))
{
	eval("\$subplans = \"".$templates->get('mysubscriptions_row_empty')."\";");
}

eval("\$page = \"".$templates->get('mysubscriptions')."\";");

$plugins->run_hooks("mysubscriptions_end");

output_page($page);

exit;

?>
