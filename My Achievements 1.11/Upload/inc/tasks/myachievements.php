<?php
/***************************************************************************
 *
 *  My Achievements plugin (/inc/tasks/myachievements.php)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  Adds an achievements system to MyBB.
 *
 ***************************************************************************/

function task_myachievements($task)
{
	global $mybb, $db, $cache, $achievement_uid;

	$db->update_query('tasks', array('locked' => 0), 'tid=14');

	@set_time_limit(0); // we do not want it to stop

	// time to check for achievements
	// heavy process coming
	// the more the achievements, the more intensive

	// load all achievements first
	$types = array('numposts', 'numthreads', 'activity', 'points');

	$achievements_apid = array();
	$achievements_atid = array();
	$achievements_aaid = array();
	$achievements_apoid = array();

	// if Points Achievements, check if NewPoints is installed
	if ($mybb->settings['myachievements_points_enabled'] == 1)
	{
		$plugins_cache = $cache->read("plugins");
		if(!isset($plugins_cache['active']['newpoints']))
		{
			// NewPoints is not installed, set setting temporarily to No to avoid errors
			$mybb->settings['myachievements_points_enabled'] = 0;
		}
	}

	// query all types of achievements
	foreach ($types as $type)
	{
		// skip points achievements if they're not enabled
		if ($type == 'points' && $mybb->settings['myachievements_points_enabled'] == 0)
			continue;

		$query = $db->simple_select('myachievements_'.$type);
		while ($achievement = $db->fetch_array($query))
		{
			switch ($type)
			{
				case 'numposts':
					$achievements_apid[$achievement['apid']] = $achievement;
				break;

				case 'numthreads':
					$achievements_atid[$achievement['atid']] = $achievement;
				break;

				case 'activity':
					$achievements_aaid[$achievement['aaid']] = $achievement;
				break;

				case 'points':
					$achievements_apoid[$achievement['apoid']] = $achievement;
				break;
			}
		}
		$db->free_result($query);
	}

	$ranks = array();

	// query all ranks - order dir asc and order by level to make sure it starts checking ranks from the lowest level and ends up in the highest level
	$query = $db->simple_select('myachievements_ranks', '*', '', array('order_by' => 'level', 'order_dir' => 'asc'));
	while ($rank = $db->fetch_array($query))
	{
		$ranks[$rank['rid']] = $rank;
	}

	// we've got all achievements
	// load users into an array
	// only users that have visited the forum since last task run are checked to not make it so intensive
	$users = array();

	if ($mybb->settings['myachievements_points_enabled'] == 1)
		$points = 'newpoints,';
	else
		$points = '';

	// In case we're rebuilding for one user only
	if (!empty($achievement_uid))
	{
		$USERID_QUERY = ' AND uid = \''.intval($achievement_uid).'\'';
	}
	else
		$USERID_QUERY = '';

	$query = $db->simple_select("users", "username,{$points}uid,postnum,timeonline,myachievements_threads,myachievements,myachievements_rank", "lastactive >= '{$task['lastrun']}'{$USERID_QUERY}");
	while($user = $db->fetch_array($query))
	{
		$users[$user['uid']] = $user;
	}

	$new_rank = array();

	// we've got the users do a new foreach
	foreach ($users as $uid => $user)
	{
		// unserialize user data and prepare it to be used
		$postnum = intval($user['postnum']);
		$threadnum = intval($user['myachievements_threads']);
		$timeonline = intval($user['timeonline']);
		if ($mybb->settings['myachievements_points_enabled'] == 1)
			$points = round($user['newpoints']);

		$user_achievements = unserialize($user['myachievements']);

		// Are we using the new method?
		if(!isset($user_achievements['apid']) && !isset($user_achievements['atid']) && !isset($user_achievements['aaid']) && !isset($user_achievements['acid']))
		{
			// If so, the actual array we want is different
			$chosen_achs = $user_achievements[1];
			$user_achievements = $user_achievements[0];
			$newmethod = true;
		}
		else
			$newmethod = false;

		$bak_ser_achievements = $user_achievements;
		$user_rank = unserialize($user['myachievements_rank']);

		$updated_achievements = false;

		unset($new_ranks);

		if (!$user_rank)
		{
			$user_rank['rid'] = 0;
			$user_rank['level'] = 0;
			$no_level = true;
		}
		else
			$no_level = false;

		// post count achievements
		if (!empty($achievements_apid))
		{
			foreach ($achievements_apid as $apid => $achievement)
			{
				// if the achievement is not in the user's achievements list, add it
				if (!isset($user_achievements['apid'][$apid]) || empty($user_achievements['apid'][$apid]))
				{
					if ($achievement['numposts'] <= $postnum)
					{
						$user_achievements['apid'][$apid] = array('apid' => intval($achievement['apid']), 'name' => $db->escape_string($achievement['name']));
						$updated_achievements = true;
					}
				}
			}
		}

		// thread count achievements
		if (!empty($achievements_atid))
		{
			foreach ($achievements_atid as $atid => $achievement)
			{
				// if the achievement is not in the user's achievements list, add it
				if (!isset($user_achievements['atid'][$atid]) || empty($user_achievements['atid'][$atid]))
				{
					if ($achievement['numthreads'] <= $threadnum)
					{
						$user_achievements['atid'][$atid] = array('atid' => intval($achievement['atid']), 'name' => $db->escape_string($achievement['name']));
						$updated_achievements = true;
					}
				}
			}
		}

		// activity achievements
		if (!empty($achievements_aaid))
		{
			foreach ($achievements_aaid as $aaid => $achievement)
			{
				// if the achievement is not in the user's achievements list, add it
				if (!isset($user_achievements['aaid'][$aaid]) || empty($user_achievements['aaid'][$aaid]))
				{
					if ($achievement['time'] <= $timeonline)
					{
						$user_achievements['aaid'][$aaid] = array('aaid' => intval($achievement['aaid']), 'name' => $db->escape_string($achievement['name']));
						$updated_achievements = true;
					}
				}
			}
		}

		// points achievements
		if ($mybb->settings['myachievements_points_enabled'] == 1)
		{
			if (!empty($achievements_apoid))
			{
				foreach ($achievements_apoid as $apoid => $achievement)
				{
					// if the achievement is not in the user's achievements list, add it
					if (!isset($user_achievements['apoid'][$apoid]) || empty($user_achievements['apoid'][$apoid]))
					{
						if ($achievement['points'] <= $points)
						{
							$user_achievements['apoid'][$apoid] = array('apoid' => intval($achievement['apoid']), 'name' => $db->escape_string($achievement['name']));
							$updated_achievements = true;
						}
					}
				}
			}
		}

		// let's check if we should update our rank
		foreach($ranks as $rid => $rank)
		{
			$update_rank_apid = false;
			$update_rank_atid = false;
			$update_rank_aaid = false;
			$update_rank_apoid = false;
			$update_rank_acid = false;

			// rank 0 ? level 0 ?
			if ($user_rank['level'] == 0 && $no_level === true && $rank['level'] == 0)
			{
				// set achievements to true so we can update our rank to level 0
				$update_rank_apid = true;
				$update_rank_atid = true;
				$update_rank_aaid = true;
				$update_rank_acid = true;
				$update_rank_apoid = true;
			}
			else {
				// if we're already at this rank or level, skip to the next one
				if ($user_rank['rid'] == $rank['rid'] || $user_rank['level'] == $rank['level'])
					continue;
			}

			// if our current rank is lower than the rank's level
			if ($user_rank['level'] < $rank['level'])
			{
				// check if there are other achievements required for this rank
				if ($rank['achievements_apid'] != 0)
				{
					// check if we have this achievement
					if (isset($user_achievements['apid'][$rank['achievements_apid']]) && $user_achievements['apid'][$rank['achievements_apid']] != 0)
					{
						// we do
						$update_rank_apid = true;
					}
				}
				else
				{
					$update_rank_apid = true;
				}

				if ($rank['achievements_atid'] != 0)
				{
					// check if we have this achievement
					if (isset($user_achievements['atid'][$rank['achievements_atid']]) && $user_achievements['atid'][$rank['achievements_atid']] != 0)
					{
						// we do
						$update_rank_atid = true;
					}
				}
				else
				{
					$update_rank_atid = true;
				}

				if($rank['achievements_aaid'] != 0)
				{
					// check if we have this achievement
					if (isset($user_achievements['aaid'][$rank['achievements_aaid']]) && $user_achievements['aaid'][$rank['achievements_aaid']] != 0)
					{
						// we do
						$update_rank_aaid = true;
					}
				}
				else
				{
					$update_rank_aaid = true;
				}

				if ($rank['achievements_acid'] != 0)
				{
					// check if we have this achievement
					if (isset($user_achievements['acid'][$rank['achievements_acid']]) && $user_achievements['acid'][$rank['achievements_acid']] != 0)
					{
						// we do
						$update_rank_acid = true;
					}
				}
				else
				{
					$update_rank_acid = true;
				}

				if ($rank['achievements_apoid'] != 0 && $mybb->settings['myachievements_points_enabled'] == 1)
				{
					// check if we have this achievement
					if (isset($user_achievements['apoid'][$rank['achievements_apoid']]) && $user_achievements['apoid'][$rank['achievements_apoid']] != 0)
					{
						// we do
						$update_rank_apoid = true;
					}
				}
				else
				{
					$update_rank_apoid = true;
				}
			}

			// all okay, we have the needed achievements to upgrade rank, so do it!
			if ($update_rank_apid === true && $update_rank_atid === true && $update_rank_aaid === true && $update_rank_apoid === true && $update_rank_acid === true)
			{
				// we can't have ranks with the same levels so we can use it as key
				$new_ranks[$rank['level']] = array('rid' => intval($rank['rid']), 'name' => $db->escape_string($rank['name']), 'level' => intval($rank['level']), 'image' => $db->escape_string($rank['image']));
			}
		}

		if (!empty($new_ranks))
		{
			// sort array in the reverse order so the highest level is the first
			krsort($new_ranks);
			// keep the last one and delete the rest
			$new_rank = $new_ranks[key($new_ranks)];

			$rank_name = $new_rank['name'];

			// serialize our new rank
			$user_rank = serialize($new_rank);
			$level = $new_rank['level'];
		}
		else
		{
			if ($user_rank['rid'] == 0)
			{
				$user_rank = '';
				$level = 0;
			}
			else
			{
				$level = $user_rank['level'];
				$user_rank = serialize($user_rank);
			}

			$rank_name = '';
		}


		/*if ($user_rank['level'] == 0 && $no_level === true)
		{
		}
		else {
			// we won't update the user if nothing's changed
			if (!$updated_achievements)
				continue;
		}*/

		// Are we using the new method?
		if ($newmethod === true)
		{
			// If so then our final array is different
			if (!empty($user_achievements) || !empty($chosen_achs))
				$user_achievements = serialize(array($user_achievements, $chosen_achs));
			else
				$user_achievements = '';
		}
		else
		{
			$user_achievements = serialize(array($user_achievements, array()));
		}

		// update user with the new achievements and rank (if changed)
		$db->update_query('users', array('myachievements' => $db->escape_string($user_achievements), 'myachievements_rank' => $db->escape_string($user_rank), 'myachievements_level' => (int)$level), 'uid=\''.(int)$uid.'\'');

		// log that the user has been updated with a new rank and/or achievements
		if (!empty($new_ranks))	// we have upgraded our rank
			myachievements_log('updated_via_task', $uid, $user['username'], 'New achievement(s) and/or new rank: '.htmlspecialchars_uni($rank_name));
		else
			myachievements_log('updated_via_task', $uid, $user['username'], 'New achievement(s).');
	}

	add_task_log($task, 'MyAchievements task has run successfully.');
}
?>
