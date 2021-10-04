<?php
/***************************************************************************
 *
 *  VIP Membership plugin (/inc/tasks/vipmembership.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2009-2010 Diogo Parrinha
 *  
 *  Website: http://consoleaddicted.com
 *  License: license.txt
 *
 *  Admins can move users to other groups, e.g. VIP group, and set how much time that user will stay there.
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

function task_vipmembership($task)
{
	global $mybb, $db, $lang, $cache;
	
	$lang->load("vipmembership");
	
	// get memberships
	$query = $db->simple_select('vipmembership_memberships', '*', 'expired=0 AND (years != 0 OR months != 0 OR days != 0 OR hours != 0 OR minutes != 0 OR seconds != 0)');
	while ($member = $db->fetch_array($query))
	{
		// calculate date on the fly 
		$member['date'] += $member['wait'];
		
		// expiration date has already passed
		if ($member['date'] < TIME_NOW)
		{
			// if auto expire setting is set to Yes
			if ($mybb->settings['vipmembership_runtask'] == 1)
			{
				// terminate membership
				$db->update_query('vipmembership_memberships', array('expired' => 1), 'mid='.$member['mid']);
				
				$uid = $member['uid'];
				
				// if additional, leave user group
				if ($member['additional'] == 1)
				{
					leave_usergroup($uid, $member['newgroup']);
				}
				// else just change the primary group
				else {
					$db->update_query('users', array('usergroup' => $member['endgroup']), 'uid='.$uid);
				}
				
				// pm user
				$to = $member['uid'];
				vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_global_yourended_title, 'message' => $lang->sprintf($lang->vipmembership_pm_global_yourended, vipmembership_get_username($member['uid'])), 'touid' => $to, 'receivepms' => 1), 0);
				
				// send pm to users set in settings
				$to = explode(',', $mybb->settings['vipmembership_uids']);
				vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_global_endmember_title, 'message' => $lang->sprintf($lang->vipmembership_pm_global_endmember, vipmembership_get_username($member['uid'])), 'touid' => $to, 'receivepms' => 1), 0);
			}
			else {
				if ($member['alerted'] == 0)
				{
					// send pm to users set in settings
					$to = explode(',', $mybb->settings['vipmembership_uids']);
					vipmembership_send_pm(array('subject' => $lang->vipmembership_pm_global_man_endmember_title, 'message' => $lang->sprintf($lang->vipmembership_pm_global_man_endmember, vipmembership_get_username($member['uid'])), 'touid' => $to, 'receivepms' => 1), 0);
					
					$db->update_query('vipmembership_memberships', array('alerted' => 1), 'mid='.$member['mid']);
				}
			}
		}
	}
	
	add_task_log($task, $lang->vipmembership_task_ran);
}
?>
