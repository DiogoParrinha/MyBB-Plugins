<?php
/***************************************************************************
 *
 *  Coupon Codes Generator plugin (/couponcodes.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  This adds a page to your forums which generates coupon codes.
 *
 ***************************************************************************/

/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'couponcodes.php');

// Templates used by Coupon Codes
$templatelist  = "couponcodes_generate";

require_once "./global.php";

require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// load language
$lang->load("couponcodes");

$plugins->run_hooks("couponcodes_start");

$chance = intval($mybb->settings['couponcodes_win_chance']);
$adminuid = intval($mybb->settings['couponcodes_pm_uid']);
$length = intval($mybb->settings['couponcodes_numbers']);
$winslimit = intval($mybb->settings['couponcodes_wins_limit']);
$secondswait = intval($mybb->settings['couponcodes_time_wait']);

$query = $db->simple_select('couponcodes_limits', '*', 'uid=\''.$mybb->user['uid'].'\'');
$limit = $db->fetch_array($query);

if ($limit['winlimit'] >= $winslimit)
	error($lang->sprintf($lang->couponcodes_error_win, $winslimit));

if ($limit['waitlimit'] > 0)
{
	// only delete if our wins limit is over the limit set in the settings and if we have waited enough
	if (($limit['waitlimit'] - TIME_NOW) <= 0 && $limit['winlimit'] >= $winslimit)
		$db->delete_query('couponcodes_limits', 'lid=\''.$limit['lid'].'\'');
	elseif (!(($limit['waitlimit'] - TIME_NOW) <= 0))
		error($lang->sprintf($lang->couponcodes_error_wait, $limit['waitlimit']-TIME_NOW));
}


if ($mybb->request_method == "post")
{

	// Verify incoming POST request
	verify_post_check($mybb->input['postcode']);

	// do we have enough points?
	if ($mybb->settings['couponcodes_points'] != 0)
	{
		if ($mybb->settings['couponcodes_points'] > $mybb->user['newpoints'])
		{
			error($lang->couponcodes_not_enough_points);
		}
	}

	// did we win?
	$win = mt_rand(1, 100/$chance);

	// if yes
	if ($win == 1)
	{
		// generate a random coupon code with $length length
		$valid_chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z', '1', '2', '3', '4', '5', '6', '7', '8', '9');

		$coupon = '';

		for ($i=1;$i<=$length;$i++)
		{
			$coupon .= $valid_chars[mt_rand(0, count($valid_chars)-1)];
		}

		// the code has been generated, PM it to the admin and to the user
		$pm = array();
		$pm['subject'] = $lang->couponcodes_pm_admin_subject;
		$pm['message'] = $lang->sprintf($lang->couponcodes_pm_admin_message, $mybb->user['username'], $coupon);
		$pm['touid'] = $mybb->settings['couponcodes_pm_uid'];
		$pm['receivepms'] = 1;
		couponcodes_send_pm($pm, -1);

		$pm = array();
		$pm['subject'] = $lang->couponcodes_pm_user_subject;
		$pm['message'] = $lang->sprintf($lang->couponcodes_pm_user_message, $coupon);
		$pm['touid'] = $mybb->user['uid'];
		$pm['receivepms'] = 1;
		couponcodes_send_pm($pm, -1);

		// insert limits into database
		if ($limit['winlimit'] > 0)
			$db->update_query('couponcodes_limits', array('winlimit' => '`winlimit`+1', 'waitlimit' => TIME_NOW+$secondswait), 'uid=\''.$mybb->user['uid'].'\'', 1, true);
		else
			$db->insert_query('couponcodes_limits', array('uid' => $mybb->user['uid'], 'winlimit' => 1, 'waitlimit' => TIME_NOW+$secondswait));

		$db->insert_query('couponcodes_coupons', array('uid' => $mybb->user['uid'], 'username' => $db->escape_string($mybb->user['username']), 'date' => TIME_NOW, 'code' => $db->escape_string($coupon)));

		// subtract points
		if ($mybb->settings['couponcodes_points'] != 0)
		{
			if (function_exists("newpoints_addpoints"))
				newpoints_addpoints($mybb->user['uid'], -$mybb->settings['couponcodes_points'], 1, 1, false, true); // last param is for immediate query
		}

		// show redirect page
		redirect("index.php", $lang->couponcodes_redirect_generated_message, $lang->couponcodes_redirect_generated_title);
	}
	else {

		// subtract points
		if ($mybb->settings['couponcodes_points'] != 0)
		{
			if (function_exists("newpoints_addpoints"))
				newpoints_addpoints($mybb->user['uid'], -$mybb->settings['couponcodes_points'], 1, 1, false, true); // last param is for immediate query
		}

		// insert limits into database
		// $db->insert_query('couponcodes_limits', array('uid' => $mybb->user['uid'], 'winlimit' => 0, 'waitlimit' => TIME_NOW+$secondswait));

		if (!empty($limit))
			$db->update_query('couponcodes_limits', array('waitlimit' => TIME_NOW+$secondswait), 'uid=\''.$mybb->user['uid'].'\'', 1, true);
		else
			$db->insert_query('couponcodes_limits', array('uid' => $mybb->user['uid'], 'winlimit' => 0, 'waitlimit' => TIME_NOW+$secondswait));

		// we lost, show error page
		error($lang->couponcodes_error_generated_message, $lang->couponcodes_error_generated_title);
	}
}

if ($mybb->settings['couponcodes_points'] != 0)
	$lang->couponcodes_message = $lang->sprintf($lang->couponcodes_message_points, $chance, newpoints_format_points($mybb->settings['couponcodes_points']), newpoints_format_points($mybb->user['newpoints']));
else
	$lang->couponcodes_message = $lang->sprintf($lang->couponcodes_message, $chance);

// get our generate page
eval("\$couponcodes = \"".$templates->get("couponcodes_generate")."\";");

$plugins->run_hooks("couponcodes_end");

output_page($couponcodes);

run_shutdown();

exit;

?>
