<?php
/***************************************************************************
 *
 *   NewPoints Points Stealing plugin (/inc/plugins/newpoints/languages/english/newpoints_stealing.php)
 *	Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *
 *   License: licence.txt
 *
 *   Adds a points stealing system to NewPoints.
 *
 ***************************************************************************/
 
$l['newpoints_stealing'] = "Points Stealing";

$l['newpoints_stealing_info'] = 'You can try to steal from another member for {1} with a succesful rate of {2}%. There is a possibility that the chosen victim has a Blocker Item purchased from the Shop. You can try to steal a maximum of {3}.';
$l['newpoints_stealing_points'] = 'Points';
$l['newpoints_stealing_victim'] = 'Victim';
$l['newpoints_stealing_steal'] = 'Steal';

$l['newpoints_stealing_laststealers'] = 'Last Stealers';
$l['newpoints_stealing_stealer'] = 'Stealer';
$l['newpoints_stealing_amount'] = 'Amount';
$l['newpoints_stealing_victim'] = 'Victim';
$l['newpoints_stealing_date'] = 'Date';
$l['newpoints_stealing_no_data'] = 'No statistics available.';

$l['newpoints_stealing_stole_log'] = '{1}-{2}-(victim UID - amount)';
$l['newpoints_stealing_failed_log'] = '{1}-{2}-(victim UID - amount)';
$l['newpoints_stealing_blocked_log'] = '{1}-{2}-(victim UID - amount)';

$l['newpoints_stealing_self'] = 'You cannot steal from yourself.';
$l['newpoints_stealing_not_enough_points'] = 'You do not have enough points.';
$l['newpoints_stealing_invalid_user'] = 'The selected user does not exist.';
$l['newpoints_stealing_redirect'] = 'You have successfully stolen from another user.';
$l['newpoints_stealing_failed'] = 'Unfortunately you were not succesful at stealing from another member.';
$l['newpoints_stealing_flood'] = 'Unfortunately you must wait {1}s until you are able to steal again.';
$l['newpoints_stealing_blocked'] = 'Your stealing try was blocked because the victim had at least one Blocker Item (which has now been spent).';
$l['newpoints_stealing_victim_points'] = 'You cannot steal from the chosen user because the user does not have enough points to cover your desired amount.';
$l['newpoints_stealing_success'] = 'You successfully stole {1} from {2}!';
$l['newpoints_stealing_success_title'] = 'SUCCESS!';

$l['newpoints_stealing_over_maxpoints'] = 'You cannot go over the maximum points limit.';

$l['newpoints_stealing_pm_stolen_subject'] = 'You were stolen';
$l['newpoints_stealing_pm_stolen_message'] = 'Ooops! {1} stole {2} from you!';

$l['newpoints_stealing_pm_blocked_subject'] = 'Your Steal Blocker item has been used';
$l['newpoints_stealing_pm_blocked_message'] = 'Great! The Steal Blocker item you purchased has blocked {1} from stealing {2} from you! The item has been removed from your inventory now.';

$l['newpoints_stealing_pm_failed_subject'] = 'Someone tried to steal from you';
$l['newpoints_stealing_pm_failed_message'] = 'Great! {1} tried to steal {2} from you but was unsuccessful!';

$l['newpoints_stealing_own_points'] = 'You do not have enough points.';
$l['newpoints_stealing_invalid_points'] = 'You must enter a valid amount of points.';

?>
