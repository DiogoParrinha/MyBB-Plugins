<?php
/***************************************************************************
 *
 *   NewPoints Subscriptions plugin (/inc/tasks/newpoints_subscriptions.php)
 *	 Author: Pirata Nervo
 *   Copyright: © 2009-2011 Pirata Nervo
 *   
 *   Website: http://www.mybb-plugins.com
 *
 *   Integrates a subscriptions system with NewPoints.
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

function task_newpoints_subscriptions($task)
{
	global $mybb, $db, $lang, $cache;
	
	newpoints_lang_load("newpoints_subscriptions");
	
	// get subscriptions plans
	$query = $db->simple_select('newpoints_log', '*', 'action=\'subscriptions\'');
	while ($log = $db->fetch_array($query))
	{
		$data = explode('-', $log['data']);
					
		if ($data[6] == 1) continue; // if it has already expired, return
		
		// calculate date on the fly 
		$log['date'] += $data[5];
		
		// expiration date has already passed
		if ($log['date'] < TIME_NOW)
		{
			// Gotta keep backwards compatibility with old logs (they don't have an 9th member)
			if(!isset($data[8]) || (isset($data[8]) && $data[8] == 0) || $mybb->settings['newpoints_subscriptions_renew'] == 0)
			{
				$data[6] = 1; // expired field is set to 1 now
				
				// if additional, leave user group
				if ($data[4] == 1)
				{
					leave_usergroup($log['uid'], $data[1]);
				}
				// else just change the primary group
				else {
					$db->update_query('users', array('usergroup' => $data[2]), 'uid='.$log['uid']);
				}
				
				// pm user
				newpoints_send_pm(array('subject' => $lang->newpoints_subscriptions_ended_title, 'message' => $lang->sprintf($lang->newpoints_subscriptions_ended, htmlspecialchars_uni($data[7])), 'touid' => $log['uid'], 'receivepms' => 1), 0);
				
				$data = implode('-', $data);
				
				// update the log entry and set it to expired
				$db->update_query('newpoints_log', array('data' => $db->escape_string($data)), 'lid='.$log['lid']);
			}
			elseif(isset($data[8]) && $data[8] == 1)
			{
				// It's set for auto renewal so we must check if we have money to do it
				$q = $db->simple_select('users', 'newpoints', 'uid=\''.(int)$log['uid'].'\'');
				$points = $db->fetch_field($q, 'newpoints');
				
				if((float)$points < (float)$data[3])
				{
					// Not enough money -> expire it
					
					$data[6] = 1; // expired field is set to 1 now
			
					// if additional, leave user group
					if ($data[4] == 1)
					{
						leave_usergroup($log['uid'], $log[1]);
					}
					// else just change the primary group
					else {
						$db->update_query('users', array('usergroup' => $data[2]), 'uid='.$log['uid']);
					}
					
					// pm user
					newpoints_send_pm(array('subject' => $lang->newpoints_subscriptions_ended_title, 'message' => $lang->sprintf($lang->newpoints_subscriptions_ended, $data[7]), 'touid' => $log['uid'], 'receivepms' => 1), 0);
					
					$data = implode('-', $data);
					
					// update the log entry and set it to expired
					$db->update_query('newpoints_log', array('data' => $db->escape_string($data)), 'lid='.$log['lid']);
				}
				else
				{
					// renew it
					$db->update_query('newpoints_log', array('date' => TIME_NOW), 'lid='.$log['lid']);
					
					// get points from user
					newpoints_addpoints($mybb->user['uid'], -(floatval($data[3])));
				}
			}
		}
	}
	
	add_task_log($task, $lang->newpoints_subscriptions_task_ran);
}
?>
