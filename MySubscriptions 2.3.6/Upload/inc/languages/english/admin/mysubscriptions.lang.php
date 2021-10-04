<?php
/***************************************************************************
 *
 *   MySubscriptions plugin (/inc/languages/english/admin/mysubscriptions.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

$l['mysubscriptions_index'] = 'MySubscriptions';
$l['mysubscriptions_canmanage'] = 'Can manage MySubscriptions?';
$l['mysubscriptions'] = 'MySubscriptions';
$l['mysubscriptions_submit'] = 'Submit';
$l['mysubscriptions_reset'] = 'Reset';

$l['mysubscriptions_add'] = 'Add';
$l['mysubscriptions_add_desc'] = 'Add a new subscription plan.';
$l['mysubscriptions_edit'] = 'Edit';
$l['mysubscriptions_edit_desc'] = 'Edit an existing subscription plan.';

$l['mysubscriptions_title'] = 'Title';
$l['mysubscriptions_description'] = 'Description';
$l['mysubscriptions_message'] = 'Private Message';
$l['mysubscriptions_price'] = 'Price';

$l['mysubscriptions_time_period'] = 'Available Time Periods';
$l['mysubscriptions_time_period_desc'] = "Enter the available time periods and prices for this plan, one per line in the format T:P:Pr. Available times (T): D for Day, W for Week, M for Month and Y for Year. One per line!
<br />
Example: (5 weeks, 10 days and 3 years are the available times for users to select from, and their respective prices: $5, $20 and $100)
<br />
W:5:5<br />
D:10:20<br />
Y:3:100<br />";

$l['mysubscriptions_time'] = 'Time';
$l['mysubscriptions_time_desc'] = 'Select the time of the subscription.';
$l['mysubscriptions_period'] = 'Period';
$l['mysubscriptions_period_desc'] = 'Enter the period of time the users will remain in the subscribed group.<br /><ul><li>Years: minimum is 1 and maximum is 5</li><li>Months: minimum is 1 and maximum is 24</li><li>Weeks: minimum is 1 and maximum is 52</li><li>Days: minimum is 1 and maximum is 90</li></ul>';

$l['mysubscriptions_additional'] = 'Additional Group?';
$l['mysubscriptions_enabled'] = 'Enabled?';
$l['mysubscriptions_enabled_desc'] = 'When disabled, the plan is not displayed in the subscription plans list and users cannot subscribe to this plan. Active subscriptions on this plan can still expire without any problems.';

$l['mysubscriptions_alt_enabled'] = 'Enabled';
$l['mysubscriptions_alt_disabled'] = 'Disabled';

$l['mysubscriptions_username'] = 'Username';
$l['mysubscriptions_period'] = 'Period';
$l['mysubscriptions_date'] = 'Date';
$l['mysubscriptions_price'] = 'Price';

$l['mysubscriptions_add'] = 'Add';
$l['mysubscriptions_edit'] = 'Edit';
$l['mysubscriptions_delete'] = 'Delete';
$l['mysubscriptions_period'] = 'Period';

$l['mysubscriptions_invalid_sub'] = 'Invalid subscription plan.';
$l['mysubscriptions_sub_deleted'] = 'Subscription plan deleted.';
$l['mysubscriptions_confirm_deletesub'] = 'Are you sure you want to delete the selected subscription plan?';
$l['mysubscriptions_sub_edited'] = 'Subscription plan edited.';
$l['mysubscriptions_sub_added'] = 'Subscription plan added.';
$l['mysubscriptions_missing_field'] = 'There are missing fields, please go back and try again.';

$l['mysubscriptions_no_subs'] = 'Could not find any subscription plans.';
$l['mysubscriptions_plans'] = 'Plans';

$l['mysubscriptions_action'] = 'Action';

$l['mysubscriptions_addsubscription'] = 'Add Subscription Plan';
$l['mysubscriptions_editsubscription'] = 'Edit Subscription Plan';

$l['mysubscriptions_submit'] = 'Submit';
$l['mysubscriptions_reset'] = 'Reset';

$l['mysubscriptions_maxactive'] = 'Maximum Active Subscribers';
$l['mysubscriptions_maxactive_desc'] = 'Enter the amount of maximum active subscribers allowed for this plan, at the same time. Leave 0 to disable.';
$l['mysubscriptions_title_desc'] = 'Enter a title.';
$l['mysubscriptions_description_desc'] = 'Enter a description.';
$l['mysubscriptions_message_desc'] = 'Enter the private message to send to subscribers.';
$l['mysubscriptions_group'] = 'Usergroup';
$l['mysubscriptions_group_desc'] = 'Select the group where users are moved into when subscribing to this plan.';

$l['mysubscriptions_years_desc'] = 'Years the user will remain in the selected group.';
$l['mysubscriptions_months_desc'] = 'Months the user will remain in the selected group.';
$l['mysubscriptions_days_desc'] = 'Days the user will remain in the selected group.';
$l['mysubscriptions_hours_desc'] = 'Hours the user will remain in the selected group.';

$l['mysubscriptions_select_group'] = 'Select Group';

$l['mysubscriptions_invalid_group'] = 'Invalid group.';
$l['mysubscriptions_additional'] = 'Additional Group?';
$l['mysubscriptions_additional_desc'] = 'Tick the checkbox if you want the group to be an additional group when users subscribe to this plan.';

$l['mysubscriptions_desc'] = 'View all subscription plans.';
$l['mysubscriptions_add_desc'] = 'Add a new subscription plan.';
$l['mysubscriptions_edit_desc'] = 'Edit an existing subscription plan.';

$l['mysubscriptions_time_empty'] = 'Subscription period cannot be zero.';

$l['mysubscriptions_price'] = 'Price';
$l['mysubscriptions_price_desc'] = 'Enter the amount of money users must pay to subscribe to this plan.';

$l['mysubscriptions_additional_title'] = '<small>(Additional)</small>';
$l['mysubscriptions_frozen_title'] = '<small>(Frozen)</small>';

$l['mysubscriptions_ended_title'] = 'Subscription Ended';
$l['mysubscriptions_ended'] = "Your subscription to {1} has just ended.\n\nThis is an automated message sent by the system.";

$l['mysubscriptions_select_time'] = 'Select Time';

$l['mysubscriptions_disporder'] = 'Display Order';
$l['mysubscriptions_disporder_desc'] = 'Enter the display order of this plan.';

$l['mysubscriptions_invalid_period'] = 'You have entered an invalid period of time.';
$l['mysubscriptions_invalid_time'] = 'You have selected and invalid time.';

$l['mysubscriptions_day'] = 'Day';
$l['mysubscriptions_days'] = 'Days';
$l['mysubscriptions_week'] = 'Week';
$l['mysubscriptions_weeks'] = 'Weeks';
$l['mysubscriptions_month'] = 'Month';
$l['mysubscriptions_months'] = 'Months';
$l['mysubscriptions_year'] = 'Year';
$l['mysubscriptions_years'] = 'Years';

$l['mysubscriptions_oneoff'] = 'One Off Payment';
$l['mysubscriptions_oneoff_desc'] = 'Set to Yes if you do not want to use PayPal subscriptions.';

$l['mysubscriptions_user_groups'] = 'Usergroups';
$l['mysubscriptions_user_groups_desc'] = 'Select which usergroups can view this plan, and therefore subscribe to it.';
$l['mysubscriptions_all_groups'] = 'All Usergroups';

$l['mysubscriptions_unlimited'] = 'Unlimited';

// Log
$l['mysubscriptions_log'] = 'PayPal Log';
$l['mysubscriptions_log_desc'] = 'Log of IPN messages sent by PayPal to MySubscriptions.';
$l['mysubscriptions_coinpayments_log'] = 'CoinPayments Log';
$l['mysubscriptions_coinpayments_log_desc'] = 'Log of IPN messages sent by CoinPayments to MySubscriptions.';
$l['mysubscriptions_lid'] = 'LID';
$l['mysubscriptions_uid'] = 'User';
$l['mysubscriptions_view_log'] = 'View Log';
$l['mysubscriptions_subscription'] = 'Subscription';
$l['mysubscriptions_date'] = 'Date';
$l['mysubscriptions_type'] = 'Type';
$l['mysubscriptions_amount'] = 'Amount';
$l['mysubscriptions_time'] = 'Time';
$l['mysubscriptions_subid'] = 'Subscription ID';
$l['mysubscriptions_log_empty'] = 'Log is empty.';
$l['mysubscriptions_amount1'] = 'Amount in Original Currency';
$l['mysubscriptions_amount2'] = 'Amount in Buyer\'s Currency';
$l['mysubscriptions_txn_id'] = 'Txn ID';
$l['mysubscriptions_status'] = 'Status';
$l['mysubscriptions_stats'] = 'Stats (fees excluded)';
$l['mysubscriptions_plan'] = 'Plan';
$l['mysubscriptions_price'] = 'Price';
$l['mysubscriptions_subscriptions'] = 'Subscriptions';
$l['mysubscriptions_total'] = 'Total:';

$l['mysubscriptions_options'] = 'Options';
$l['mysubscriptions_from'] = 'From';
$l['mysubscriptions_to'] = 'To';
$l['mysubscriptions_view'] = 'View';
$l['mysubscriptions_user'] = 'User';
$l['mysubscriptions_enter_usergroups'] = 'Usergroups (gids, separated by comma)';
$l['mysubscriptions_no_subscribers'] = 'Could not find any subscribers.';
$l['mysubscriptions_subscribers'] = 'Subscribers';

// Stats
$l['mysubscriptions_oneofflogs'] = 'PayPal Stats';
$l['mysubscriptions_oneofflogs_desc'] = 'View a list of subscribers to PayPal subscription plans.';
$l['mysubscriptions_coinpayments_oneofflogs'] = 'CoinPayments Stats';
$l['mysubscriptions_coinpayments_oneofflogs_desc'] = 'View a list of subscribers to CoinPayments subscription plans.';
$l['mysubscriptions_expiredate'] = 'Expire Date';

$l['mysubscriptions_enddate'] = 'End Date';
$l['mysubscriptions_enddate_desc'] = 'Enter the date in the following format: 10 September 2014';
$l['mysubscriptions_user_edited'] = 'User edited successfully';
$l['mysubscriptions_sort_username'] = 'Sort by Username';
$l['mysubscriptions_startdate'] = 'Start Date';


$l['mysubscriptions_task_ran'] = 'MySubscriptions task ran successfully.';

// Manual Upgrade
$l['mysubscriptions_confirm_give'] = 'Are you sure you want to upgrade the selected user with the selected plan?';
$l['mysubscriptions_upgrade_user'] = 'Upgrade User';
$l['mysubscriptions_proceed'] = 'Proceed';
$l['mysubscriptions_invalid_username'] = 'You entered an invalid username.';
$l['mysubscriptions_upgraded'] = 'User upgraded successfully.';
$l['mysubscriptions_select_plan'] = 'Select Plan';
$l['mysubscriptions_pm_upgraded_subject'] = 'Upgraded!';
$l['mysubscriptions_pm_upgraded_message'] = 'You have successfully been upgraded by {1}: {2}.';

/// Task

$l['mysubscriptions_expire_email_subject'] = "Your membership at {1} will expire soon";
$l['mysubscriptions_expire_email_message'] = "Dear {1},

Your membership at <a href=\"{2}\">{5}</a> for the plan \"{3}\" will expire on {4}.

It will not renew automatically, so if you want to continue to enjoy the the same features, you will need to re-subscribe after it expires.

This kind of messages can be disabled from your UserCP -> Receive e-mails from Administrators.
By ticking it off, Administrators will not be able to contact you.

Best Regards,
{5} Staff
";

$l['mysubscriptions_active_subscriptions'] = 'Active Subscriptions';
$l['mysubscriptions_active_subscriptions_desc'] = 'List of currently active subscriptions.';
$l['mysubscriptions_processor'] = 'Processor';
$l['mysubscriptions_manually_upgraded'] = 'Manually Upgraded';
$l['mysubscriptions_multi_freeze_unfreeze'] = 'Multi Freeze/Unfreeze';
$l['mysubscriptions_multi_extend'] = 'Multi Extend';
$l['mysubscriptions_freeze'] = 'Freeze';
$l['mysubscriptions_unfreeze'] = 'Unfreeze';
$l['mysubscriptions_extend'] = 'Extend';
$l['mysubscriptions_select_plan'] = 'Select Plan';
$l['mysubscriptions_invalid_days'] = 'You must enter a valid amount of days.';
$l['mysubscriptions_extended'] = 'All subscriptions under the selected plan have been extended.';
$l['mysubscriptions_confirm_extend'] = 'Are you sure you want to extend all subscriptions under the selected plan for {1} amount of days?';
$l['mysubscriptions_invalid_processor'] = 'Invalid payment processor.';
$l['mysubscriptions_invalid_price'] = 'You entered an invalid price.';
$l['mysubscriptions_frozen'] = 'All subscriptions under the selected plan have been frozen.';
$l['mysubscriptions_unfrozen'] = 'All subscriptions under the selected plan have been unfrozen.';

?>
