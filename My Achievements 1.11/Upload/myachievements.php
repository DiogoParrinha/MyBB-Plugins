<?php
/***************************************************************************
 *
 *  My Achievements plugin (/myachievements.php)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  Adds an achievements system to MyBB.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'myachievements.php');

// Templates used by My Achievements
$templatelist  = "myachievements_achievements,myachievements_achievement,myachievements_ranksmyachievements_ranks_rank,myachievements_no_data,myachievements_topranks_rank,myachievements_topranks,myachievements_userachievement";

require_once "./global.php";

/*
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;
*/

// load language
$lang->load("myachievements");

$myachievements = '';

$plugins->run_hooks("myachievements_start");

$lang->myachievements_topranks = $lang->sprintf($lang->myachievements_topranks, $mybb->settings['myachievements_topranks']);

switch ($mybb->input['action'])
{
	case 'ranks':
		$ranks = '';

		$query = $db->simple_select('myachievements_ranks', '*', '', array('order_dir' => 'desc', 'order_by' => 'level'));
		while ($rank = $db->fetch_array($query))
		{
			$bgcolor = alt_trow();

			$required = '';

			$rank['image'] = htmlspecialchars_uni($rank['image']);
			$rank['name'] = htmlspecialchars_uni($rank['name']);
			$rank['description'] = nl2br(htmlspecialchars_uni($rank['description']));

			if ($rank['achievements_apid'] > 0)
			{
				// get achievement name
				$query2 = $db->simple_select('myachievements_numposts', 'image,name', 'apid='.intval($rank['achievements_apid']));
				$achievement = $db->fetch_array($query2);
				$required .= '<li>'.'<img src="'.htmlspecialchars_uni($achievement['image']).'" /> '.htmlspecialchars_uni($achievement['name']).'</li>';
			}

			if ($rank['achievements_atid'] > 0)
			{
				// get achievement name
				$query2 = $db->simple_select('myachievements_numthreads', 'image,name', 'atid='.intval($rank['achievements_atid']));
				$achievement = $db->fetch_array($query2);
				$required .= '<li>'.'<img src="'.htmlspecialchars_uni($achievement['image']).'" /> '.htmlspecialchars_uni($achievement['name']).'</li>';
			}

			if ($rank['achievements_aaid'] > 0)
			{
				// get achievement name
				$query2 = $db->simple_select('myachievements_activity', 'image,name', 'aaid='.intval($rank['achievements_aaid']));
				$achievement = $db->fetch_array($query2);
				$required .= '<li>'.'<img src="'.htmlspecialchars_uni($achievement['image']).'" /> '.htmlspecialchars_uni($achievement['name']).'</li>';
			}

			if ($rank['achievements_acid'] > 0)
			{
				// get achievement name
				$query2 = $db->simple_select('myachievements_custom', 'image,name', 'acid='.intval($rank['achievements_acid']));
				$achievement = $db->fetch_array($query2);
				$required .= '<li>'.'<img src="'.htmlspecialchars_uni($achievement['image']).'" /> '.htmlspecialchars_uni($achievement['name']).'</li>';
			}

			if ($rank['achievements_apoid'] > 0)
			{
				// get achievement name
				$query2 = $db->simple_select('myachievements_points', 'image,name', 'apoid='.intval($rank['achievements_apoid']));
				$achievement = $db->fetch_array($query2);
				$required .= '<li>'.'<img src="'.htmlspecialchars_uni($achievement['image']).'" /> '.htmlspecialchars_uni($achievement['name']).'</li>';
			}

			if (!$required)
			{
				$required .= '<li>'.$lang->myachievements_none.'</li>';
			}

			$rank['description'] .= $lang->sprintf($lang->myachievements_required_achievements, $required);

			$rank['level'] = intval($rank['level']);

			eval("\$ranks .= \"".$templates->get("myachievements_ranks_rank")."\";");
		}

		if ($ranks == '')
		{
			$colspan = 4;
			eval("\$ranks = \"".$templates->get("myachievements_no_data")."\";");
		}

		eval("\$myachievements = \"".$templates->get("myachievements_ranks")."\";");
	break;

	case 'topranks':
		$ranks = '';

		$query = $db->simple_select('users', 'username,uid,myachievements_rank', 'myachievements_level>0', array('order_dir' => 'desc', 'order_by' => 'myachievements_level', 'limit' => $mybb->settings['myachievements_topranks']));
		while ($user = $db->fetch_array($query))
		{
			$bgcolor = alt_trow();

			$rank = unserialize($user['myachievements_rank']);

			$rank['username'] = build_profile_link($user['username'],$user['uid']);
			$rank['image'] = htmlspecialchars_uni($rank['image']);
			$rank['name'] = htmlspecialchars_uni($rank['name']);
			$rank['level'] = intval($rank['level']);
			$rank['view'] = $lang->sprintf($lang->myachievements_view_rank, $mybb->settings['bburl']."/myachievements.php?uid=".$user['uid']);

			eval("\$ranks .= \"".$templates->get("myachievements_topranks_rank")."\";");
		}

		if ($ranks == '')
		{
			$colspan = 4;
			eval("\$ranks = \"".$templates->get("myachievements_no_data")."\";");
		}

		eval("\$myachievements = \"".$templates->get("myachievements_topranks")."\";");
	break;

	// default show achievements list
	default:
		$idtypes = array('numposts' => 'apid', 'numthreads' => 'atid', 'activity' => 'aaid', 'custom' => 'acid', 'points' => 'apoid');
		$types = array('numposts', 'numthreads', 'activity', 'custom', 'points');

		$posts_achievements = $threads_achievements = $activity_achievements = $custom_achievements = $points_achievements = '';
		$uid = (int)$mybb->input['uid'];

		if ($uid)
		{
			$user = get_user($uid);
			$user['myachievements'] = unserialize($user['myachievements']);

			$ids = array();

			if (!isset($user['myachievements']['apid']) && !isset($user['myachievements']['atid']) && !isset($user['myachievements']['aaid']) && !isset($user['myachievements']['acid']))
			{
				$user['myachievements'] = $user['myachievements'][0];
			}

			if (!empty($user['myachievements']))
			{
				foreach ($user['myachievements'] as $type => $ach_type)
				{
					if (!empty($ach_type))
					{
						foreach ($ach_type as $achievement)
						{
							$ids[$type][] = $achievement[$type];
						}
					}
				}
			}

			if (!empty($ids))
			{
				foreach ($types as $type)
				{
					if ($type == 'points' && $mybb->settings['myachievements_points_enabled'] == 0)
						continue;

					if (!isset($ids[$idtypes[$type]]))
						continue;

					switch ($type)
					{
						case 'numposts':
							$order_by = array('order_by' => 'numposts', 'order_dir' => 'desc');
						break;

						case 'numthreads':
							$order_by = array('order_by' => 'numthreads', 'order_dir' => 'desc');
						break;

						case 'activity':
							$order_by = array('order_by' => 'time', 'order_dir' => 'desc');
						break;

						case 'custom':
							$order_by = array('order_by' => 'acid', 'order_dir' => 'asc');
						break;

						case 'points':
							$order_by = array('order_by' => 'points', 'order_dir' => 'desc');
						break;
					}

					$query = $db->simple_select('myachievements_'.$type, '*', $idtypes[$type].' IN (\''.implode('\',\'', $ids[$idtypes[$type]]).'\')', $order_by);
					while ($achievement = $db->fetch_array($query))
					{
						$bgcolor = alt_trow();

						$achievement['image'] = htmlspecialchars_uni($achievement['image']);
						$achievement['name'] = htmlspecialchars_uni($achievement['name']);
						$achievement['description'] = nl2br(htmlspecialchars_uni($achievement['description']));

						switch ($type)
						{
							case 'numposts':
								eval("\$posts_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
							break;

							case 'numthreads':
								eval("\$threads_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
							break;

							case 'activity':
								eval("\$activity_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
							break;

							case 'custom':
								eval("\$custom_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
							break;

							case 'points':
								eval("\$points_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
							break;
						}
					}
				}
			}

			if ($posts_achievements == '')
			{
				$colspan = 3;
				eval("\$posts_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}
			if ($threads_achievements == '')
			{
				$colspan = 3;
				eval("\$threads_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}
			if ($activity_achievements == '')
			{
				$colspan = 3;
				eval("\$activity_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}
			if ($custom_achievements == '')
			{
				$colspan = 3;
				eval("\$custom_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}
			if ($points_achievements == '')
			{
				$colspan = 3;
				eval("\$points_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}

			$user_rank = unserialize($user['myachievements_rank']);

			$lang->myachievements_userrank = $lang->sprintf($lang->myachievements_userrank,$user_rank['image'], $user_rank['name']);

			$lang->myachievements_userachievements = $lang->sprintf($lang->myachievements_userachievements, htmlspecialchars_uni($user['username']));

			eval("\$myachievements = \"".$templates->get("myachievements_userachievements")."\";");

		}
		else {
			// query all types of achievements
			foreach ($types as $type)
			{
				if ($type == 'points' && $mybb->settings['myachievements_points_enabled'] == 0)
					continue;

				switch ($type)
				{
					case 'numposts':
						$order_by = array('order_by' => 'numposts', 'order_dir' => 'desc');
					break;

					case 'numthreads':
						$order_by = array('order_by' => 'numthreads', 'order_dir' => 'desc');
					break;

					case 'activity':
						$order_by = array('order_by' => 'time', 'order_dir' => 'desc');
					break;

					case 'custom':
						$order_by = array('order_by' => 'name', 'order_dir' => 'asc');
					break;

					case 'points':
						$order_by = array('order_by' => 'points', 'order_dir' => 'desc');
					break;
				}

				$query = $db->simple_select('myachievements_'.$type, '*', '', $order_by);
				while ($achievement = $db->fetch_array($query))
				{
					$bgcolor = alt_trow();

					$achievement['image'] = htmlspecialchars_uni($achievement['image']);
					$achievement['name'] = htmlspecialchars_uni($achievement['name']);
					$achievement['description'] = nl2br(htmlspecialchars_uni($achievement['description']));

					switch ($type)
					{
						case 'numposts':
							eval("\$posts_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
						break;

						case 'numthreads':
							eval("\$threads_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
						break;

						case 'activity':
							eval("\$activity_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
						break;

						case 'custom':
							eval("\$custom_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
						break;

						case 'points':
							eval("\$points_achievements .= \"".$templates->get("myachievements_userachievement")."\";");
						break;
					}
				}
			}

			if ($posts_achievements == '')
			{
				$colspan = 3;
				eval("\$posts_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}
			if ($threads_achievements == '')
			{
				$colspan = 3;
				eval("\$threads_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}
			if ($activity_achievements == '')
			{
				$colspan = 3;
				eval("\$activity_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}
			if ($custom_achievements == '')
			{
				$colspan = 3;
				eval("\$custom_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}
			if ($points_achievements == '')
			{
				$colspan = 3;
				eval("\$points_achievements.= \"".$templates->get("myachievements_no_data")."\";");
			}

			eval("\$myachievements = \"".$templates->get("myachievements_achievements")."\";");
		}
	break;
}

$plugins->run_hooks("myachievements_end");

output_page($myachievements);

run_shutdown();

exit;

?>
