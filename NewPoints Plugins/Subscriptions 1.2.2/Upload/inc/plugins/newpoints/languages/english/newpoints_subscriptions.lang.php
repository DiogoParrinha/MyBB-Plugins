<?php
/***************************************************************************
 *
 *   NewPoints Subscriptions plugin (/inc/plugins/newpoints/languages/english/newpoints_subscriptions.lang.php)
 *	 Author: Pirata Nervo
 *   Copyright: © 2009-2011 Pirata Nervo
 *   
 *   Website: http://www.mybb-plugins.com
 *
 *   Integrates a shop system with NewPoints.
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

$l['newpoints_subscriptions'] = "Subscriptions";

$l['newpoints_subscriptions_title'] = 'Title';
$l['newpoints_subscriptions_description'] = 'Description';
$l['newpoints_subscriptions_price'] = 'Price';
$l['newpoints_subscriptions_subscribe'] = 'Subscribe';
$l['newpoints_subscriptions_period'] = 'Period';
$l['newpoints_subscriptions_usergroup'] = 'Usergroup';
$l['newpoints_subscriptions_years'] = 'Years';
$l['newpoints_subscriptions_months'] = 'Months';
$l['newpoints_subscriptions_days'] = 'Days';
$l['newpoints_subscriptions_hours'] = 'Hours';

$l['newpoints_subscriptions_additional_notice'] = 'This group will not be your primary user group but an additional one.';

$l['newpoints_subscriptions_redirect_subject'] = 'Subscribed Successfully';
$l['newpoints_subscriptions_redirect_message'] = 'You have successfully subscribed to the selected subscription.';

// Log
$l['newpoints_subscriptions_subscribed_log'] = '{1}-{2}-{3}-{4}-{5}-{6}-{7}-{8}-{9}(sid,newgroup,endgroup,price,additional,time,expired,title,auto renew)';

$l['newpoints_subscriptions_plans'] = 'Subscription Plans';
$l['newpoints_subscriptions_plans_primary'] = 'Primary Subscription Plans';
$l['newpoints_subscriptions_plans_sub'] = 'Additional Subscription Plans';

$l['newpoints_subscriptions_subscribe_plan'] = 'Subscribe to plan ';
$l['newpoints_subscriptions_subscribe_confirm'] = 'Are you sure you want to subscribe to the selected subscription plan?';

$l['newpoints_subscriptions_invalid_sub'] = 'Invalid subscription plan.';

$l['newpoints_subscriptions_subscribed'] = 'You have successfully subscribed to the selected plan.';
$l['newpoints_subscriptions_subscribed_title'] = 'Subscribed successfully';

$l['newpoints_subscriptions_empty'] = 'Could not find any subscription plans.';

$l['newpoints_subscriptions_ended_title'] = 'Subscription Ended';
$l['newpoints_subscriptions_ended'] = "Your subscription to {1} has just ended.\n\nThis is an automated message sent by the system.";

$l['newpoints_subscriptions_task_ran'] = 'NewPoints Subscriptions Task has ran.';

$l['newpoints_subscriptions_not_enough'] = 'You do not have enough points to subscribe to this plan.';
$l['newpoints_subscriptions_already_subscribed'] = 'This plan\'s group is of primary type and since you have already subscribed to a plan, you must wait until it ends.';
$l['newpoints_subscriptions_already_group'] = 'You are already in this group.';

$l['newpoints_subscriptions_mysubscriptions'] = 'My Subscriptions';
$l['newpoints_subscriptions_expires'] = 'Expires On';
$l['newpoints_subscriptions_auto_renewal'] = 'Auto Renewal';
$l['newpoints_subscriptions_notavailable'] = 'Not Available';
$l['newpoints_subscriptions_disabled'] = 'Disabled (<a href="newpoints.php?action=enable_renewal&amp;lid={1}&amp;my_post_key={2}">Enable</a>)';
$l['newpoints_subscriptions_enabled'] = 'Enabled (<a href="newpoints.php?action=disable_renewal&amp;lid={1}&amp;my_post_key={2}">Disable</a>)';

$l['newpoints_subscriptions_auto_renewal_enabled'] = 'You have successfully enabled auto renewal for the selected subscription plan.';
$l['newpoints_subscriptions_auto_renewal_disabled'] = 'You have successfully disabled auto renewal for the selected subscription plan.';

?>