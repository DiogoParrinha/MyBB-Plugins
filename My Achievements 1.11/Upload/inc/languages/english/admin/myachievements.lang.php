<?php
/***************************************************************************
 *
 *  My Achievements plugin (/inc/languages/english/myachievements.lang.php)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  Adds an achievements system to MyBB.
 *
 ***************************************************************************/

$l['myachievements'] = 'My Achievements';
$l['myachievements_achievements'] = 'Achievements';
$l['myachievements_ranks'] = 'Ranks';
$l['myachievements_submit'] = 'Submit';
$l['myachievements_reset'] = 'Reset';
$l['myachievements_delete'] = 'Delete';
$l['myachievements_edit'] = 'Edit';
$l['myachievements_error'] = 'An unknown error has occurred.';
$l['myachievements_options'] = 'Options';
$l['myachievements_years'] = 'Years';
$l['myachievements_months'] = 'Months';
$l['myachievements_days'] = 'Days';
$l['myachievements_year'] = 'Year';
$l['myachievements_month'] = 'Month';
$l['myachievements_day'] = 'Day';

$l['myachievements_icon'] = 'Icon';
$l['myachievements_name'] = 'Name';
$l['myachievements_description'] = 'Description';
$l['myachievements_level'] = 'Level';
$l['myachievements_numposts'] = 'Posts Number';
$l['myachievements_numthreads'] = 'Threads Number';
$l['myachievements_numpoints'] = 'Points Amount';
$l['myachievements_timespent'] = 'Time Spent Online';
$l['myachievements_activity'] = 'Activity Achievements';

$l['myachievements_icon_desc'] = 'Enter the path to the icon of the {1}. Path must start from the MyBB root. (Do not include the first trailing slash)';
$l['myachievements_name_desc'] = 'Enter the name of the {1}.';
$l['myachievements_description_desc'] = 'Enter the description of this {1}.';
$l['myachievements_level_desc'] = 'Enter the level of this {1}.';
$l['myachievements_numposts_desc'] = 'Number of posts required to get this achievement.';
$l['myachievements_numthreads_desc'] = 'Number of threads required to get this achievement.';
$l['myachievements_numpoints_desc'] = 'Amount of points required to get this achievement.';
$l['myachievements_years_desc'] = 'Amount of years spent online required to get this achievement. Can be 0 if you do not want to use this field.';
$l['myachievements_months_desc'] = 'Amount of months spent online required to get this achievement. Can be 0 if you do not want to use this field.';
$l['myachievements_days_desc'] = 'Amount of days spent online required to get this achievement. Can be 0 if you do not want to use this field.';

$l['myachievements_view'] = 'View';
$l['myachievements_add'] = 'Add';
$l['myachievements_edit'] = 'Edit';

$l['myachievements_view_desc'] = 'View all {1}';
$l['myachievements_add_desc'] = 'Add a new {1}';
$l['myachievements_edit_desc'] = 'Edit an existing {1}';

$l['myachievements_no_data'] = 'Could not find any data.';

$l['myachievements_select_achievement'] = 'Select an achievement';

$l['myachievements_add_achievement'] = 'Add Achievement';
$l['myachievements_edit_achievement'] = 'Edit Achievement';
$l['myachievements_achievement_deleteconfirm'] = 'Are you sure you want to delete the selected achievement?';

$l['newpoints_no_filters'] = '<a href="index.php?module=myachievements/log">Stop filtering!</a>';
$l['newpoints_current_filter'] = 'Current filter';

// Posts, Threads, Custom, Points
$l['myachievements_posts'] = 'Post Count Achievements';
$l['myachievements_threads'] = 'Thread Count Achievements';
$l['myachievements_custom'] = 'Custom Achievements';
$l['myachievements_points'] = 'Points Achievements';
$l['myachievements_give'] = 'Give';
$l['myachievements_revoke'] = 'Revoke';
$l['myachievements_give_desc'] = 'Give {1} to users';
$l['myachievements_revoke_desc'] = 'Revoke {1} to users';
$l['myachievements_give_achievement'] = 'Give Achievement';
$l['myachievements_revoke_achievement'] = 'Revoke Achievement';
$l['myachievements_username'] = 'Username';
$l['myachievements_username_desc'] = 'Enter the name of the user you want to give this achievement.';
$l['myachievements_username_desc'] = 'Enter the name of the user from who you want to revoke the achievement.';
$l['myachievements_reason'] = 'Reason';
$l['myachievements_reason_desc'] = 'Enter the reason for why you are giving this achievement.';
$l['newpoints_points_disabled'] = '<strong>Points achievements are disabled.</strong> You can enable them in settings but make sure NewPoints is installed.';
$l['newpoints_newpoints_not_installed'] = '<strong>Although Points Achievements are enabled, NewPoints is not installed.</strong>';
$l['myachievements_name_invalid_characters'] = 'The name you have entered contains invalid characters (single quotes and double quotes are not permitted).';
$l['myachievements_reason_invalid_characters'] = 'The reason you have entered contains invalid characters (single quotes and double quotes are not permitted).';
$l['myachievements_achievements_acid_give_desc'] = 'Select the achievement you want to give.';
$l['myachievements_achievements_acid_revoke_desc'] = 'Select the achievement you want to revoke.';
// Ranks
$l['myachievements_add_rank'] = 'Add Rank';
$l['myachievements_edit_rank'] = 'Edit Rank';
$l['myachievements_achievements_apid'] = 'Posts Achievement';
$l['myachievements_achievements_apid_desc'] = 'Select the required post achievement to get this rank. Do not choose one if you don\'t want this rank to require any achievement.';
$l['myachievements_achievements_atid'] = 'Threads Achievement';
$l['myachievements_achievements_atid_desc'] = 'Select the required thread achievement to get this rank. Do not choose one if you don\'t want this rank to require any achievement.';
$l['myachievements_achievements_aaid'] = 'Activity Achievement';
$l['myachievements_achievements_aaid_desc'] = 'Select the required activity achievement to get this rank. Do not choose one if you don\'t want this rank to require any achievement.';
$l['myachievements_achievements_acid'] = 'Custom Achievement';
$l['myachievements_achievements_acid_desc'] = 'Select the required custom achievement to get this rank. Do not choose one if you don\'t want this rank to require any achievement.';
$l['myachievements_achievements_apoid'] = 'Points Achievement';
$l['myachievements_achievements_apoid_desc'] = 'Select the required points achievement to get this rank. Do not choose one if you don\'t want this rank to require any achievement.';

$l['myachievements_ranks_deleteconfirm'] = 'Are you sure you want to delete the selected rank?';

// Log
$l['myachievements_log'] = 'Log';
$l['myachievements_log_description'] = 'Manage log entries.';
$l['myachievements_log_type'] = 'Type';
$l['myachievements_log_data'] = 'Data';
$l['myachievements_log_user'] = 'User';
$l['myachievements_log_date'] = 'Date';
$l['myachievements_log_options'] = 'Options';
$l['myachievements_no_log_entries'] = 'Could not find any log entries.';
$l['myachievements_log_entries'] = 'Log entries';
$l['myachievements_log_deleteconfirm'] = 'Are you sure you want to delete the selected log entry?';
$l['myachievements_log_invalid'] = 'Invalid log entry.';
$l['myachievements_log_deleted'] = 'Log entry successfully deleted.';
$l['myachievements_log_prune'] = 'Prune log entries';
$l['myachievements_older_than'] = 'Older than';
$l['myachievements_older_than_desc'] = 'Prune log entries older than the number of days you enter.';
$l['myachievements_log_pruned'] = 'Log entries successfully pruned.';
$l['myachievements_log_pruneconfirm'] =' Are you sure you want to prune log entries?';

// Rebuild
$l['myachievements_rebuild'] = 'Rebuild';
$l['myachievements_rebuild_description'] = 'Rebuild achievements and ranks.';
$l['myachievements_log_type'] = 'Type';
$l['myachievements_log_data'] = 'Data';
$l['myachievements_rebuild_ranks_achievements'] = 'Rebuild Achievements and Ranks';
$l['myachievements_rebuild_ranks_achievements_desc'] = 'This will rebuild all achievements and ranks, that means all users will lose their current achievements and ranks in order to fix them.<br />Note: after all members\' achievements and rank have been reset, the system will give achievements and ranks to users that have been online since you have last run the task.<br /><strong>Custom achievements will be removed too and you will need to give them again.</strong>';
$l['myachievements_rebuild_confirm'] = 'Are you sure you want to rebuild achievements and ranks?';
$l['myachievements_rebuilt'] = 'Achievements and ranks have been successfully rebuilt.';
$l['myachievements_click_continue'] = 'Click Continue to proceed with the rebuild process.';
$l['myachievements_continue'] = 'Continue';
$l['myachievements_per_page'] = 'Per Page';
$l['myachievements_per_page_desc'] = 'Enter how many users are update per page.';
$l['myachievements_full'] = 'Full Reset';
$l['myachievements_full_desc'] = 'If set to Yes, it will calculate achievements and ranks for all users, no matter if they have been online after the last run of the task. This can be a painful process for large boards.';
$l['myachievements_ignore_custom'] = 'Ignore Custom';
$l['myachievements_ignore_custom_desc'] = 'Set to No if you want to erase all custom achievements. Custom achievements cannot be rebuilt so by default this is set to Yes to avoid accidental data loss.';

// Success
$l['myachievements_rank_added'] = 'You have successfully added a new rank.';
$l['myachievements_rank_deleted'] = 'You have successfully deleted the selected rank. <strong>It is STRONGLY recommended that you run the Rebuild function now.</strong>';
$l['myachievements_rank_edited'] = 'You have successfully edited the selected rank. <strong>It is STRONGLY recommended that you run the Rebuild function now.</strong>';

$l['myachievements_achievement_added'] = 'You have successfully added a new achievement.';
$l['myachievements_achievement_deleted'] = 'You have successfully deleted the selected achievement. <strong>It is STRONGLY recommended that you run the Rebuild function now.</strong>';
$l['myachievements_achievement_edited'] = 'You have successfully edited the selected achievement.';

$l['myachievements_achievement_given'] = 'You have successfully given the selected achievement.';
$l['myachievements_achievement_revoked'] = 'You have successfully revoked the selected achievement.';

// Error
$l['myachievements_no_name'] = 'You did not enter a valid name.';
$l['myachievements_no_icon'] = 'You did not enter a valid icon.';
$l['myachievements_no_level'] = 'You did not enter a valid level.';
$l['myachievements_rank_invalid'] = 'You have selected an invalid rank.';
$l['myachievements_achievement_invalid'] = 'You have selected an invalid achievement.';
$l['myachievements_no_numposts'] = 'You did not enter a valid number of posts.';
$l['myachievements_no_numthreads'] = 'You did not enter a valid number of threads.';
$l['myachievements_no_points'] = 'You did not enter a valid amount of points.';
$l['myachievements_no_years'] = 'You did not enter a valid amount of years.';
$l['myachievements_no_months'] = 'You did not enter a valid amount of months.';
$l['myachievements_no_days'] = 'You did not enter a valid amount of days.';
$l['myachievements_no_time'] = 'You did not enter a valid amount of time.';
$l['myachievements_no_user'] = 'You did not enter a valid username.';
$l['myachievements_no_achievement'] = 'You have entered an invalid achievement ID.';
$l['myachievements_user_no_acid'] = 'The user you have selected does not have this achievement.';
$l['myachievements_user_already_acid'] = 'The user you have selected already has this achievement.';

?>
