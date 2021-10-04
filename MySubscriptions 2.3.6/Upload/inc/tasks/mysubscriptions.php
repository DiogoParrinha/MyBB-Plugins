<?php
/***************************************************************************
 *
 *   MySubscriptions plugin (/inc/tasks/mysubscriptions.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

function task_mysubscriptions($task)
{
	global $mybb, $db, $lang, $cache;

	$lang->load("mysubscriptions");

	$db->update_query('tasks', array('locked' => 0), 'tid=19');

	// Get banned user groups
	$banned_groups = array();
	$q = $db->simple_select('usergroups', 'gid', 'isbannedgroup=1');
	while($gid = $db->fetch_field($q, 'gid'))
		$banned_groups[] = (int)$gid;

	/////////////////////////
	//// get expired PayPal one-off subscriptions
	$query = $db->query("
		SELECT s.*, l.*, u.usergroup as user_usergroup
		FROM ".TABLE_PREFIX."mysubscriptions_log l
		LEFT JOIN ".TABLE_PREFIX."mysubscriptions_subscriptions s ON (s.sid=l.sid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		WHERE l.enddate!=0 AND l.enddate < ".TIME_NOW." AND l.expired=0 AND l.frozen=0
	");
	while ($sub = $db->fetch_array($query))
	{
		// If banned, don't do anything
		if(in_array($sub['user_usergroup'], $banned_groups))
		{
			$db->update_query('mysubscriptions_log', array('expired' => 1), 'lid='.$sub['lid']);
			continue;
		}

		// if additional, leave user group
		if ($sub['additional'] == 1)
		{
			leave_usergroup($sub['uid'], $sub['group']);
		}
		// else just change the primary group
		else {
			$db->update_query('users', array('usergroup' => $sub['endgroup']), 'uid='.$sub['uid']);
		}

		// Set to max int value
		$db->update_query('mysubscriptions_log', array('expired' => 1), 'sid='.$sub['item_number'].' AND timestamp='.$sub['timestamp']);
	}

	/////////////////////////
	//// get CoinPayments expired one-off subscriptions
	$query = $db->query("
		SELECT s.*, l.*, u.usergroup as user_usergroup
		FROM ".TABLE_PREFIX."mysubscriptions_coinpayments_log l
		LEFT JOIN ".TABLE_PREFIX."mysubscriptions_subscriptions s ON (s.sid=l.sid)
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		WHERE l.enddate!=0 AND l.enddate < ".TIME_NOW." AND l.expired=0
	");
	while ($sub = $db->fetch_array($query))
	{
		// If banned, don't do anything
		if(in_array($sub['user_usergroup'], $banned_groups))
		{
			$db->update_query('mysubscriptions_coinpayments_log', array('expired' => 1), 'lid='.$sub['lid']);
			continue;
		}

		// if additional, leave user group
		if ($sub['additional'] == 1)
		{
			leave_usergroup($sub['uid'], $sub['group']);
		}
		// else just change the primary group
		else {
			$db->update_query('users', array('usergroup' => $sub['endgroup']), 'uid='.$sub['uid']);
		}

		// Set to max int value
		$db->update_query('mysubscriptions_coinpayments_log', array('expired' => 1), 'lid='.$sub['lid']);
	}

	if($mybb->settings['mysubscriptions_expire_emails'] == 1)
	{
		/////////////////////////
		/// Get	PayPal subscriptions that will expire in one week [one-off]
		$query = $db->query("
			SELECT s.*, l.*, u.email as useremail, u.username
			FROM ".TABLE_PREFIX."mysubscriptions_log l
			LEFT JOIN ".TABLE_PREFIX."mysubscriptions_subscriptions s ON (s.sid=l.sid)
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
			WHERE l.enddate!=0 AND l.enddate < ".(TIME_NOW+7*24*60*60)." AND l.expired=0 AND l.email=0 AND u.allownotices=1 AND l.frozen=0
		");
		while ($sub = $db->fetch_array($query))
		{
			// Send email
			$subject = $lang->sprintf($lang->mysubscriptions_expire_email_subject, $mybb->settings['bbname']);

			$enddate = my_date($mybb->settings['dateformat'], $sub['enddate']);
			$enddate .= ', '.my_date($mybb->settings['timeformat'], $sub['enddate']);

			$message = $lang->sprintf($lang->mysubscriptions_expire_email_message, $sub['username'], $mybb->settings['bburl'], $sub['title'], $enddate, $mybb->settings['bbname']);

			my_mail($sub['useremail'], $subject, nl2br($message), "", "", "", false, "html", $message);

			// Set email to 1
			$db->update_query('mysubscriptions_log', array('email' => 1), 'lid='.$sub['lid']);
		}

		/////////////////////////
		/// Get	CoinPayments subscriptions that will expire in one week [one-off]
		$query = $db->query("
			SELECT s.*, l.*
			FROM ".TABLE_PREFIX."mysubscriptions_coinpayments_log l
			LEFT JOIN ".TABLE_PREFIX."mysubscriptions_subscriptions s ON (s.sid=l.sid)
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
			WHERE l.enddate!=0 AND l.enddate < ".(TIME_NOW+7*24*60*60)." AND l.expired=0 AND l.email=0 AND u.allownotices=1 AND (l.status=100 || l.status=2) AND l.frozen=0
		");
		while ($sub = $db->fetch_array($query))
		{
			// Send email
			$subject = $lang->sprintf($lang->mysubscriptions_expire_email_subject, $mybb->settings['bbname']);

			$enddate = my_date($mybb->settings['dateformat'], $sub['enddate']);
			$enddate .= ', '.my_date($mybb->settings['timeformat'], $sub['enddate']);

			$message = $lang->sprintf($lang->mysubscriptions_expire_email_message, $sub['username'], $mybb->settings['bburl'], $sub['title'], $enddate, $mybb->settings['bbname']);

			my_mail($sub['email'], $subject, nl2br($message), "", "", "", false, "html", $message);

			// Set email to 1
			$db->update_query('mysubscriptions_coinpayments_log', array('email' => 1), 'lid='.$sub['lid']);
		}
	}

	add_task_log($task, $lang->mysubscriptions_task_ran);
}
?>
