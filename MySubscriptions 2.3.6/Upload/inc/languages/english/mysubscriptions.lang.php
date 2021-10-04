<?php
/***************************************************************************
 *
 *   MySubscriptions plugin (/inc/languages/english/admin/mysubscriptions.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

$l['mysubscriptions'] = 'MySubscriptions';
$l['mysubscriptions_plans'] = 'Subscription Plans';
$l['mysubscriptions_none'] = 'The administrator hans\'t setup any subscriptions yet.';

$l['mysubscriptions_title'] = 'Title';
$l['mysubscriptions_description'] = 'Description';
$l['mysubscriptions_price'] = 'Price';
$l['mysubscriptions_usergroup'] = 'Usergroup';
$l['mysubscriptions_period'] = 'Period';

$l['mysubscriptions_additional_notice'] = 'This group will not be your primary user group but an additional one.';

$l['mysubscriptions_day'] = 'Day';
$l['mysubscriptions_days'] = 'Days';
$l['mysubscriptions_week'] = 'Week';
$l['mysubscriptions_weeks'] = 'Weeks';
$l['mysubscriptions_month'] = 'Month';
$l['mysubscriptions_months'] = 'Months';
$l['mysubscriptions_year'] = 'Year';
$l['mysubscriptions_years'] = 'Years';

$l['mysubscriptions_success_title'] = 'Successfully subscribed';
$l['mysubscriptions_success_message'] = 'You have subscribed successfully to {1}. Thank you!';

$l['mysubscriptions_success_title_admin'] = 'New Subscription';
$l['mysubscriptions_success_message_admin'] = 'I have successfully subscribed to {1}.';

$l['mysubscriptions_empty'] = 'No subscriptions plans found.';

$l['mysubscriptions_task_ran'] = 'MySubscriptions (Expire) task ran successfully.';
$l['mysubscriptions_task2_ran'] = 'MySubscriptions (Reserved Subs) task ran successfully.';

$l['mysubscriptions_lifetime'] = 'Lifetime';

$l['mysubscriptions_expire_email_subject'] = "Your membership at {1} will expire soon";
$l['mysubscriptions_expire_email_message'] = "Dear {1},

Your membership at <a href=\"{2}\">{5}</a> for the plan \"{3}\" will expire on {4}.

It will not renew automatically, so if you want to continue to enjoy the the same features, you will need to re-subscribe after it expires.

This kind of messages can be disabled from your UserCP -> Receive e-mails from Administrators.
By ticking it off, Administrators will not be able to contact you.

Best Regards,
{5} Staff
";

$l['mysubscriptions_no_active_subs'] = 'You have no active (one-time) upgrades at the moment.';
$l['mysubscriptions_processor'] = 'Payment Processor';
$l['mysubscriptions_expires_on'] = 'Expires On';
$l['mysubscriptions_active_plans'] = 'Active Upgrades';
$l['mysubscriptions_active_plans_desc'] = 'If you made any upgrades using PayPal recurring payments/subscriptions, these will not show below. Only one-time payment upgrades are shown.';
$l['mysubscriptions_manually_upgraded'] = 'Manually Upgraded';
$l['mysubscriptions_login_register'] = 'Please <a href="{1}">login</a> or <a href="{2}">register</a>.';

$l['mysubscriptions_cant_upgrade'] = 'This plan is not available for your usergroup.';
$l['mysubscriptions_max_subs'] = 'This plan has reached the maximum amount of subscribers at the same time.';

$l['mysubscriptions_select_time'] = 'Select subscription period';

?>
