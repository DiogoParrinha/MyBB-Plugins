<?php
/***************************************************************************
 *
 *  VIP Membership plugin (/inc/plugins/vipmembership.php)
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

$l['vipmembership_index'] = 'VIP Membership';
$l['vipmembership_canmanage'] = 'Can manage VIP Memberships?';
$l['vipmembership'] = 'VIP Membership';

$l['vipmembership_expired_memberships'] = 'Expired Memberships';

$l['vipmembership_members'] = 'Members';
$l['vipmembership_expired'] = 'Expired';
$l['vipmembership_add'] = 'Add';
$l['vipmembership_edit'] = 'Edit';
$l['vipmembership_end'] = 'End';
$l['vipmembership_note'] = 'Note';
$l['vipmembership_delete'] = 'Delete';

$l['vipmembership_members_desc'] = 'View current VIP members.';
$l['vipmembership_expired_desc'] = 'View expired memberships.';
$l['vipmembership_add_desc'] = 'Add a VIP member.';
$l['vipmembership_edit_desc'] = 'Edit an existing VIP member.';
$l['vipmembership_end_desc'] = 'End a VIP membership.';
$l['vipmembership_note_desc'] = 'View notes.';

$l['vipmembership_mid'] = 'MID';
$l['vipmembership_username'] = 'User Name';
$l['vipmembership_newgroup'] = 'New Group';
$l['vipmembership_endgroup'] = 'End Group';
$l['vipmembership_expiredate'] = 'Expiration Date';
$l['vipmembership_action'] = 'Action';
$l['vipmembership_nomembers'] = 'No members found.';

$l['vipmembership_user_username'] = 'Username:';
$l['vipmembership_user_newgroup'] = 'New Group:';
$l['vipmembership_user_endgroup'] = 'End Group:';
$l['vipmembership_user_years'] = 'Years:';
$l['vipmembership_user_months'] = 'Months:';
$l['vipmembership_user_days'] = 'Days:';
$l['vipmembership_user_hours'] = 'Hours:';
$l['vipmembership_user_minutes'] = 'Minutes:';
$l['vipmembership_user_seconds'] = 'Seconds:';
$l['vipmembership_user_additionalgroup'] = 'Additional group?';
$l['vipmembership_user_note'] = 'Note';
$l['vipmembership_user_note_desc'] = 'Enter a note that can be viewed by other administrators.';
$l['vipmembership_user_sendpm'] = 'Send PM';
$l['vipmembership_additional'] = 'Additional';

$l['vipmembership_user_username_desc'] = 'The name of the user.';
$l['vipmembership_user_newgroup_desc'] = 'Select the group the user will be moved to.';
$l['vipmembership_user_endgroup_desc'] = 'Select the group the user will be moved to once the membership expires. This is useless if the additional setting is set to Yes.';
$l['vipmembership_user_end_group_desc'] = 'Select the group the user will be moved to. This is useless if the additional setting is set to Yes.';
$l['vipmembership_user_years_desc'] = 'Number of years the user will stay in the new group.';
$l['vipmembership_user_months_desc'] = 'Number of months the user will stay in the new group.';
$l['vipmembership_user_days_desc'] = 'Number of days the user will stay in the new group.';
$l['vipmembership_user_hours_desc'] = 'Number of hours the user will stay in the new group.';
$l['vipmembership_user_minutes_desc'] = 'Number of minutes the user will stay in the new group.';
$l['vipmembership_user_seconds_desc'] = 'Number of seconds the user will stay in the new group.';
$l['vipmembership_user_additionalgroup_desc'] = 'Set to yes if you want to add the new group to the additional groups list of the user instead of making it a primary group. If set to Yes, the end group setting won\'t be used as the only thing this will do is add the new group to the addional groups list and remove it once the membership has expired.';
$l['vipmembership_user_sendpm_desc'] = 'Set to Yes if you want to send a private message to this user.';

$l['vipmembership_submit'] = 'Submit';
$l['vipmembership_reset'] = 'Reset';

$l['vipmembership_addmember'] = 'Add member';
$l['vipmembership_editmember'] = 'Edit member';
$l['vipmembership_nogroup'] = 'Select a group';
$l['vipmembership_curgroup'] = 'Leave as current group';

$l['vipmembership_missing'] = 'One ore more fields are missing.';
$l['vipmembership_missing_date'] = 'You haven\'t set how much time the user will stay in the new group.';

$l['vipmembership_invalid_group'] = 'Invalid user group.';
$l['vipmembership_invalid_user'] = 'Invalid user.';

$l['vipmembership_added_user'] = 'User added successfully.';
$l['vipmembership_invalid_member'] = 'Invalid member.';

$l['vipmembership_edited_user'] = 'User edited successfully.';
$l['vipmembership_endmember'] = 'End Membership';
$l['vipmembership_ended_user'] = 'You\'ve ended this user\'s membership successfully.';

$l['vipmembership_invalid_newgroup'] = 'The new group of this member is invalid.';

$l['vipmembership_alreadyadded'] = 'This user has been added already.';
$l['vipmembership_userdeleted'] = 'You have deleted the selected entry.';

// PM's
$l['vipmembership_pm_newmember_title'] = 'New member';
$l['vipmembership_pm_newmember'] = '{1} has added {2} to a new membership.';

$l['vipmembership_pm_endmember_title'] = 'Membership Terminated';
$l['vipmembership_pm_endmember'] = '{1} has terminated {2}\'s membership.';

$l['vipmembership_pm_yourended_title'] = 'Membership Expired';
$l['vipmembership_pm_yourended'] = 'Your membership has expired';

$l['vipmembership_pm_welcome_title'] = 'New membership';
$l['vipmembership_pm_welcome'] = '{1} has moved you from group {2} to group {3}.';
$l['vipmembership_pm_welcome_additional'] = '{1} has moved you to group {2}.';

$l['vipmembership_deleted_user'] = 'User deleted successfully.';

$l['vipmembership_privatemessage'] = 'Private Message';
$l['vipmembership_privatemessage_desc'] = 'Enter the private message to send to the user.<br />{1} is replaced by your username.<br />{2} is replaced by title of the old group.<br />{3} is replaced by the title of the new group.<br />However, if you set the Additional Group option to Yes, this message will not be used.';

$l['vipmembership_sendpm_privatemessage'] = 'Private Message';
$l['vipmembership_sendpm_privatemessage_desc'] = 'Enter the private message to send to the user. This won\'t take effect if the above option is set to No.';

$l['vipmembership_lifetime'] = 'Life Time';

?>
