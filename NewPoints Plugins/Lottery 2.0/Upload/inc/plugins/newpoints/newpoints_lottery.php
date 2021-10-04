<?php
/***************************************************************************
 *
 *   NewPoints Lottery plugin (/inc/plugins/newpoints/newpoints_lottery.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   Integrates a lottery system with NewPoints.
 *
 ***************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("newpoints_start", "newpoints_lottery_page");
$plugins->add_hook("newpoints_default_menu", "newpoints_lottery_menu");
$plugins->add_hook("newpoints_stats_start", "newpoints_lottery_stats");

function newpoints_lottery_info()
{
	return array(
		"name"			=> "Lottery",
		"description"	=> "Integrates a lottery system with NewPoints.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "2.0",
		"guid" 			=> "",
		"compatibility" => "2*"
	);
}

function newpoints_lottery_install()
{
	global $db, $cache;

	// add settings
	newpoints_add_setting('newpoints_lottery_ticket_price', 'newpoints_lottery', 'Ticket Price', 'The price of each ticket. (default is 100)', 'text', '100', 1);
	newpoints_add_setting('newpoints_lottery_draw_frequency', 'newpoints_lottery', 'Time between each drawing', 'How often (in seconds) the lottery is run. (default is 7 days: 604800)', 'text', '604800', 2);
	newpoints_add_setting('newpoints_lottery_win', 'newpoints_lottery', 'Prize', 'The amount of points which is paid to the winner.', 'text', '1000', 3);
	newpoints_add_setting('newpoints_lottery_rest', 'newpoints_lottery', 'Rest time', 'Time (in seconds) between each term and that the winners are shown and users are not allowed to buy tickets. (default is 2 hours: 7200)', 'text', '7200', 4);
	newpoints_add_setting('newpoints_lottery_usepot', 'newpoints_lottery', 'Use Lottery Pot', 'If yes, the cost of each ticket goes towards the pot of the lottery. (default is Yes)', 'yesno', '1', 5);
	newpoints_add_setting('newpoints_lottery_lastwinners', 'newpoints_lottery', 'Last Winners', 'Number of last winners to show in statistics.', 'text', '10', 6);

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_lottery_tickets` (
	  `ticket_id` int(10) UNSIGNED NOT NULL auto_increment,
	  `term_id` bigint(30) unsigned NOT NULL default '0',
	  `uid` bigint(30) unsigned NOT NULL default '0',
	  `dateline` bigint(30) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`ticket_id`), KEY (`uid`)
		) ENGINE=MyISAM");

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."newpoints_lottery_term` (
	  `term_id` int(10) UNSIGNED NOT NULL auto_increment,
	  `winner_uid` bigint(30) unsigned NOT NULL default '0',
	  `winner_ticket_number` varchar(150) NOT NULL default '',
	  `ticket_count` int(8) unsigned NOT NULL default '0',
	  `money` decimal(16,2) unsigned NOT NULL default '0',
	  `start_time` bigint(30) unsigned NOT NULL default '0',
	  `end_time` bigint(30) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`term_id`)
		) ENGINE=MyISAM");

	rebuild_settings();

	$cache->update('lottery_term', array());
	$cache->update('lottery_pot', array());

	// create task
	$new_task = array(
		"title" => "NewPoints Lottery",
		"description" => "Calculates the winner.",
		"file" => "newpoints_lottery",
		"minute" => '0',
		"hour" => '*',
		"day" => '*',
		"month" => '*',
		"weekday" => '*',
		"enabled" => '0',
		"logging" => '1'
	);

	$new_task['nextrun'] = 0; // once the task is enabled, it will generate a nextrun date
	$tid = $db->insert_query("tasks", $new_task);
}

function newpoints_lottery_is_installed()
{
	global $db;

	if($db->table_exists('newpoints_lottery_tickets'))
	{
		return true;
	}
	return false;
}

function newpoints_lottery_uninstall()
{
	global $db;

	// delete settings
	newpoints_remove_settings("'newpoints_lottery_ticket_price','newpoints_lottery_draw_frequency','newpoints_lottery_win','newpoints_lottery_rest','newpoints_lottery_usepot','newpoints_lottery_lastwinners'");
	rebuild_settings();

	if($db->table_exists('newpoints_lottery_tickets'))
	{
		$db->drop_table('newpoints_lottery_tickets');
	}

	if($db->table_exists('newpoints_lottery_term'))
	{
		$db->drop_table('newpoints_lottery_term');
	}

	newpoints_remove_log(array('lottery_ticket','lottery_winner'));

	$db->delete_query('tasks', 'file=\'newpoints_lottery\''); // delete all tasks that use vipmembership task file

	// Delete cache
	$db->delete_query('datacache', 'title=\'lottery_pot\'');
	$db->delete_query('datacache', 'title=\'lottery_term\'');
}

function newpoints_lottery_activate()
{
	global $db, $mybb;

	newpoints_add_template('newpoints_lottery', '
<html>
<head>
<title>{$lang->newpoints} - {$lang->newpoints_lottery}</title>
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
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder"  style="padding:0px;" >
<thead>
	<tr><th class="thead" colspan="2"><strong>{$lang->newpoints_lottery_viewing_lottery}</strong></th></tr>
</thead>
<tbody>
	<tr>
		<td width="300px" style="padding:0px" valign="top" class="trow1">
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="" width="100%">
			<tr class="tcat" >
				<td>{$lang->newpoints_lottery_buy_ticket}</td>
			</tr>
			<tr>
				<td class="trow1" align="center">{$buyticket}</td>
			</tr>
		</table>
		</td>
		<td width="300px" class="trow1" rowspan="2" align="center" dir="ltr">
		{$lotteryinfo}
		</td>
	</tr>
	<tr>
		<td width="300px" style="padding:0px" valign="top" class="trow1">
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="" width="100%">
			<tr class="tcat">
				<td >{$lang->newpoints_lottery_your_ticket}</td>
			</tr>
			<tr>
				<td class="trow1">{$usertickets}</td>
			</tr>
		</table>
		</td>
	</tr>
<tbody>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>
');

	newpoints_add_template('newpoints_lottery_buyticket', '
<form action="newpoints.php?action=lottery" method="post">
		<input type="hidden" name="postcode" value="{$mybb->post_code}" />
		<input type="hidden" name="invaction" value="buy" />
		{$lang->newpoints_lottery_ticket_price} : {$lottery_ticket_price}
		<input type="hidden" name="process" value="yes" />
		<br /><input type="submit" value="{$lang->newpoints_lottery_buy}" class="button" />
</form>
');

	newpoints_add_template('newpoints_lottery_info', '
	<br>
	{$lottery_end}
	{$lottery_countdown}
	{$lottery_win}<br>
	{$ticket_bought}
	<p>
	<b>{$lang->newpoints_lottery_winner_ticket} :</b>  {$winner_ticket_number}
	<p>
	{$last_winner}
');

	newpoints_add_template('newpoints_lottery_countdown', '
<div id="cntdwn" class="lottery_countdown"></div>
<script>
CountActive = true;
CountStepper = -1;
LeadingZero = true;
var secs={$lottery_sec};
DisplayFormat = "{$lang->newpoints_lottery_countdown}";

function calcage(secs, num1, num2) {
  s = ((Math.floor(secs/num1))%num2).toString();
  if (LeadingZero && s.length < 2)s = "0" + s;
  return "<b>" + s + "</b>";
}

function CountBack(secs) {
  if (secs < 0) {
	location.href="newpoints.php?action=lottery&refresh="+parseInt(Math.random()*(999));
	return;
  }
  DisplayStr = DisplayFormat.replace(/%%D%%/g, calcage(secs,86400,100000));
  DisplayStr = DisplayStr.replace(/%%H%%/g, calcage(secs,3600,24));
  DisplayStr = DisplayStr.replace(/%%M%%/g, calcage(secs,60,60));
  DisplayStr = DisplayStr.replace(/%%S%%/g, calcage(secs,1,60));

  document.getElementById("cntdwn").innerHTML = DisplayStr;
  if (CountActive){
	setTimeout("CountBack(" + (secs+CountStepper) + ")", SetTimeOutPeriod);
  }
}

CountStepper = Math.ceil(CountStepper);
if (CountStepper == 0)  CountActive = false;
var SetTimeOutPeriod = (Math.abs(CountStepper)-1)*1000 + 990;
CountBack(secs);
</script>
');

	newpoints_add_template('newpoints_lottery_stats', '
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="4"><strong>{$lang->newpoints_lottery_lastwinners}</strong></td>
</tr>
<tr>
<td class="tcat" width="40%"><strong>{$lang->newpoints_lottery_user}</strong></td>
<td class="tcat" width="30%"><strong>{$lang->newpoints_lottery_amount}</strong></td>
<td class="tcat" width="30%" align="center"><strong>{$lang->newpoints_lottery_date}</strong></td>
</tr>
{$lastwinners}
</table><br />');

	newpoints_add_template('newpoints_lottery_stats_lastwinner', '
<tr>
<td class="{$bgcolor}" width="40%">{$winner[\'user\']}</td>
<td class="{$bgcolor}" width="30%">{$winner[\'amount\']}</td>
<td class="{$bgcolor}" width="30%" align="center">{$winner[\'date\']}</td>
</tr>');

	newpoints_add_template('newpoints_lottery_stats_nowinners', '
<tr>
<td class="trow1" width="100%" colspan="3">{$lang->newpoints_lottery_no_winners}</td>
</tr>');

	// edit templates
	newpoints_find_replace_templatesets('newpoints_statistics', '#'.preg_quote('width="60%">').'#', 'width="60%">{$newpoints_lottery_lastwinners}');

}

function newpoints_lottery_deactivate()
{
	global $db, $mybb;

	newpoints_remove_templates("'newpoints_lottery','newpoints_lottery_buyticket','newpoints_lottery_info','newpoints_lottery_countdown','newpoints_lottery_stats','newpoints_lottery_stats_lastwinner','newpoints_lottery_stats_nowinners'");

	// edit templates
	newpoints_find_replace_templatesets('newpoints_statistics', '#'.preg_quote('{$newpoints_lottery_lastwinners}').'#', '');
}

// show lottery in the list
function newpoints_lottery_menu(&$menu)
{
	global $mybb, $lang;
	newpoints_lang_load("newpoints_lottery");

	if ($mybb->input['action'] == 'lottery')
		$menu[] = "&raquo; <a href=\"{$mybb->settings['bburl']}/newpoints.php?action=lottery\">".$lang->newpoints_lottery."</a>";
	else
		$menu[] = "<a href=\"{$mybb->settings['bburl']}/newpoints.php?action=lottery\">".$lang->newpoints_lottery."</a>";
}

function newpoints_lottery_page()
{
	global $mybb;

	if ($mybb->input['action'] != 'lottery') return;

	if(!$mybb->user['uid'])
	{
		error_no_permission();
	}
	else
	{
		global $db, $lang, $cache, $theme, $header, $templates, $plugins, $headerinclude, $footer, $options;

		if($mybb->input['refresh'])
		{
			redirect('newpoints.php?action=lottery');
		}

		$term = $cache->read('lottery_term');
		if(empty($term))
		{
			// Find one
			$q = $db->simple_select('newpoints_lottery_term', '*', 'end_time=0', array('order_by' => 'start_time', 'order_dir' => 'desc', 'limit' => 1));
			$term = $db->fetch_array($q);
			if(!empty($term))
			{
				$cache->update('lottery_term', array('term_id' => $term['term_id'], 'start_time' => $term['start_time'], 'ticket_count' => $term['ticket_count']));
				$cache->update('lottery_pot', array((float)$term['money']));
			}
		}

		if(empty($term))
		{
			// Create one!
			$term = array(
				'winner_uid' => 0,
				'winner_ticket_number' => 0,
				'money' => 0,
				'end_time' => 0,
				'start_time' => TIME_NOW+$mybb->settings['newpoints_lottery_rest'] // People can start buying tickets now
			);

			$term_id = (int)$db->insert_query("newpoints_lottery_term", $term);
			$term['term_id'] = $term_id;
			$cache->update('lottery_term', array('term_id' => $term_id, 'start_time' => TIME_NOW+$mybb->settings['newpoints_lottery_rest'], 'ticket_count' => 0));
		}

		$term_id = (int)$term['term_id'];

		if($mybb->request_method == 'post')
		{
			verify_post_check($mybb->input['postcode']);

			// Time for buying has ended
			if(
			TIME_NOW < $term['start_time']+$mybb->settings['newpoints_lottery_draw_frequency'] // not yet ended
			&&
			TIME_NOW > $term['start_time']
			)
			{
				// Do we have enough points to buy a ticket?
				if($mybb->settings['newpoints_lottery_ticket_price'] > $mybb->user['newpoints'])
				{
					error($lang->newpoints_lottery_enough_money);
				}
				else
				{
					// We have enough points, so buy us a ticket!
					newpoints_addpoints($mybb->user['uid'], -($mybb->settings['newpoints_lottery_ticket_price']));

					// is the pot enabled?
					if($mybb->settings['newpoints_lottery_usepot'] == 1)
					{
						// add points to the pot
						$pot = $cache->read('lottery_pot');
						$pot = (float)$pot[0]+$mybb->settings['newpoints_lottery_ticket_price'];
						$cache->update('lottery_pot', array($pot));
					}

					$row = array(
						'term_id' => (int)$term['term_id'],
						'uid' => $mybb->user['uid'],
						'dateline' => TIME_NOW
					);
					$db->insert_query('newpoints_lottery_tickets', $row);
					$ticketid = $db->insert_id();

					// log purchase
					newpoints_log('lottery_ticket', $lang->sprintf($lang->newpoints_lottery_log_buy, $ticketid, $mybb->settings['newpoints_lottery_ticket_price']));

					$db->update_query('newpoints_lottery_term', array('ticket_count' => $term['ticket_count']+1), 'term_id=\''.$term_id.'\'');
					$cache->update('lottery_term', array('term_id' => $term_id, 'start_time' => $term['start_time'], 'ticket_count' => $term['ticket_count']+1));
					redirect('newpoints.php?action=lottery',$lang->newpoints_lottery_ticketbuy_success);
				}
			}
			else
			{
				error($lang->newpoints_lottery_rest_time);
			}
		}

		if($term_id >= 1)
		{
			$last_winner_array = $db->fetch_array($db->simple_select('newpoints_log','*','action=\'lottery_winner\'', array('order_by' => 'date', 'order_dir' => 'desc', 'limit' => 1)));
		}

		// Is it still time for buying?
		if(
			TIME_NOW < $term['start_time']+$mybb->settings['newpoints_lottery_draw_frequency'] // not yet ended
			&&
			TIME_NOW > $term['start_time']
		)
		{
			$winner_ticket_number = $lang->newpoints_lottery_winner_ticket_hide;

			$lottery_end = $lang->sprintf($lang->newpoints_lottery_end, my_date($mybb->settings['dateformat'], $term['start_time']+$mybb->settings['newpoints_lottery_draw_frequency']).", ".my_date($mybb->settings['timeformat'], $term['start_time']+$mybb->settings['newpoints_lottery_draw_frequency']));
			$lottery_sec = ($term['start_time']+$mybb->settings['newpoints_lottery_draw_frequency'])-TIME_NOW;

			$lottery_win = $lang->sprintf($lang->newpoints_lottery_win, newpoints_format_points(newpoints_lottery_money()));
			$ticket_bought = $lang->sprintf($lang->newpoints_lottery_ticket_bought, (int)$term['ticket_count']);

			eval('$lottery_countdown = "'.$templates->get('newpoints_lottery_countdown').'";');
		}
		else
		{
			// Here we have three options:
			// 1) No winner has been drawn yet
			// 2) We have a winner
			// 3) We couldn't find a winner but the result was drawn - no winner
			// For 1 and 3 we know that start_time = TIME_NOW (when it was drawn) + rest_time
			// Therefore we can simply check if start_time > TIME_NOW -> it means we're in rest time
			// NOTE that we we're here beacause we can't buy tickets therefore we don't need to check if we're out of the rest time already

			// If we don't have a last winner to show OR the lottery start date is smaller than TIME_NOW
			if($term['start_time'] > TIME_NOW)
			{
				$lang->newpoints_lottery_countdown = $lang->newpoints_lottery_drawed;

				$lottery_sec = $term['start_time']-TIME_NOW;

				eval('$lottery_countdown = "'.$templates->get('newpoints_lottery_countdown').'";');
			}
			else
			{
				$lottery_countdown = $lang->newpoints_lottery_rest;
			}
		}

		if(!empty($last_winner_array) && $last_winner_array['uid'] > 0)
		{
			$last_winner_array['data'] = explode('-', $last_winner_array['data']);
			$winner_ticket_number = $last_winner_array['data'][0];
			$last_winner = $lang->sprintf($lang->newpoints_lottery_last_winner, (int)$last_winner_array['uid'], htmlspecialchars_uni($last_winner_array['username']), newpoints_format_points($last_winner_array['data'][2]));
		}
		else
		{
			$winner_ticket_number = $lang->newpoints_lottery_noticket;
			$last_winner = '';
		}

		eval('$lotteryinfo .= "'.$templates->get('newpoints_lottery_info').'";');

		$usertickets='';
		$comma='';
		$query = $db->simple_select('newpoints_lottery_tickets','*','uid='.$mybb->user['uid'].' AND term_id='.intval($term_id));
		while($ticket = $db->fetch_array($query))
		{
			$usertickets .= $comma.$ticket['ticket_id'];
			$comma=' , ';
		}

		global $lottery_ticket_price;

		$lottery_ticket_price = newpoints_format_points($mybb->settings['newpoints_lottery_ticket_price']);

		eval('$buyticket .= "'.$templates->get('newpoints_lottery_buyticket').'";');
		$title = strip_tags($lang->newpoints_lottery_viewing_lottery);

		eval("\$page = \"".$templates->get('newpoints_lottery')."\";");
		output_page($page);
	}
}

function newpoints_lottery_money()
{
	global $mybb, $cache;

	$money = (float)$mybb->settings['newpoints_lottery_win'];

	if($mybb->settings['newpoints_lottery_usepot'] == 1)
	{
		$pot = $cache->read('lottery_pot');
		if(!empty($pot))
			$money += (float)$pot[0];
	}

	return $money;
}

function newpoints_lottery_stats()
{
	global $mybb, $db, $templates, $cache, $theme, $newpoints_lottery_lastwinners, $lastwinners, $lang;

	// load language
	newpoints_lang_load("newpoints_lottery");
	$lastwinners = '';

	// build stats table
	//$query = $db->simple_select('newpoints_log', '*', 'action=\'lottery_winner\'', array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => intval($mybb->settings['newpoints_lottery_lastwinners'])));
	$query = $db->query("
		SELECT u.username, u.uid, l.*
		FROM ".TABLE_PREFIX."newpoints_log l
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
		WHERE l.action='lottery_winner'
		ORDER BY l.date DESC LIMIT ".intval($mybb->settings['newpoints_lottery_lastwinners'])."
	");

	while($winner = $db->fetch_array($query)) {
		$bgcolor = alt_trow();
		$data = explode('-', $winner['data']);

		$winner['amount'] = newpoints_format_points($data[2]);

		$link = build_profile_link(htmlspecialchars_uni($winner['username']), intval($winner['uid']));
		$winner['user'] = $link;

		$winner['date'] = my_date($mybb->settings['dateformat'], intval($winner['date']), '', false);

		eval("\$lastwinners .= \"".$templates->get('newpoints_lottery_stats_lastwinner')."\";");
	}

	if (!$lastwinners)
		eval("\$lastwinners = \"".$templates->get('newpoints_lottery_stats_nowinners')."\";");

	eval("\$newpoints_lottery_lastwinners = \"".$templates->get('newpoints_lottery_stats')."\";");
}

?>
