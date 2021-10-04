<?php
/***************************************************************************
 *
 *  My Advertisements plugin (/inc/languages/english/admin/myadvertisements.lang.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *  
 *  
 *  License: license.txt
 *
 *  This plugin adds advertizements zones to your forum.
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

$l['myadvertisements'] = 'My Advertisements';
$l['myadvertisements_index'] = 'My Advertisements';
$l['myadvertisements_canmanage'] = 'Can manage My Advertisements?';

// Tabs
$l['myadvertisements_zones'] = 'Zones';
$l['myadvertisements_zones_desc'] = 'Manage advertisement zones.';
$l['myadvertisements_zones_add'] = 'Add';
$l['myadvertisements_zones_add_desc'] = 'Add an advertising zone.';
$l['myadvertisements_zones_edit'] = 'Edit';
$l['myadvertisements_zones_edit_desc'] = 'Edit an existing advertising zone.';
$l['myadvertisements_zones_delete'] = 'Delete';
$l['myadvertisements_zones_delete_desc'] = 'Delete an existing advertising zone.';

$l['myadvertisements_advertisements'] = 'Advertisements';
$l['myadvertisements_advertisements_expired'] = 'Expired Ads';
$l['myadvertisements_advertisements_desc'] = 'Manage advertisements.';
$l['myadvertisements_advertisements_expired_desc'] = 'Manage expired advertisements.';
$l['myadvertisements_advertisements_add'] = 'Add';
$l['myadvertisements_advertisements_add_desc'] = 'Add a new advertisement.';
$l['myadvertisements_advertisements_edit'] = 'Edit';
$l['myadvertisements_advertisements_edit_desc'] = 'Edit an existing advertisement.';
$l['myadvertisements_advertisements_delete'] = 'Delete';
$l['myadvertisements_advertisements_delete_desc'] = 'Delete an existing advertisement.';

// General
$l['myadvertisements_edit'] = 'Edit';
$l['myadvertisements_delete'] = 'Delete';
$l['myadvertisements_submit'] = 'Submit';
$l['myadvertisements_reset'] = 'Reset';
$l['myadvertisements_select_zone'] = 'Select a zone';
$l['myadvertisements_getcode'] = 'Get Code';
$l['myadvertisements_unlimited'] = 'Unlimited';

// Error messages
$l['myadvertisements_nozones'] = 'No zones found.';
$l['myadvertisements_noadvertisements'] = 'No advertisements found.';
$l['myadvertisements_invalid_zone'] = 'Invalid ad zone.';
$l['myadvertisements_invalid_ad'] = 'Invalid advertisement.';
$l['myadvertisements_missing_field'] = 'One or more fields are missing.';
$l['myadvertisements_unknown_error'] = 'An unknown error has occurred.';

// Table cat
$l['myadvertisements_name'] = 'Name';
$l['myadvertisements_description'] = 'Description';
$l['myadvertisements_exemptgroups'] = 'Exempt Groups';
$l['myadvertisements_ads'] = 'Ads';
$l['myadvertisements_action'] = 'Action';
$l['myadvertisements_expire'] = 'Expires on';
$l['myadvertisements_zone'] = 'Zone';
$l['myadvertisements_clicks'] = 'Clicks';
$l['myadvertisements_views'] = 'Views';

// Zones - Add
$l['myadvertisements_addzone'] = 'Add Zone';
$l['myadvertisements_addzone_name'] = 'Name';
$l['myadvertisements_addzone_name_desc'] = 'Enter the name of the zone.';
$l['myadvertisements_addzone_description'] = 'Description';
$l['myadvertisements_addzone_description_desc'] = 'Enter a description for this zone.';
$l['myadvertisements_zone_added'] = 'A new zone has been added successfully.';

// Zones - Edit
$l['myadvertisements_editzone'] = 'Edit Zone';
$l['myadvertisements_editzone_name'] = 'Name';
$l['myadvertisements_editzone_name_desc'] = 'Enter the name of the zone.';
$l['myadvertisements_editzone_description'] = 'Description';
$l['myadvertisements_editzone_description_desc'] = 'Enter a description for this zone.';
$l['myadvertisements_zone_edited'] = 'The selected zone has been edited successfully.';
$l['myadvertisements_editzone_postbit'] = 'Postbit Options';
$l['myadvertisements_editzone_postbit_desc'] = 'Choose the ad display mode for postbit.';
$l['myadvertisements_editzone_eachpost'] = 'Each Post';
$l['myadvertisements_editzone_firstonly'] = 'First Post only';
$l['myadvertisements_editzone_firstonly'] = 'First Post only';
$l['myadvertisements_editzone_firstandx'] = 'First Post and every X posts';
$l['myadvertisements_editzone_everyx'] = 'Every X posts';
$l['myadvertisements_editzone_xposts'] = 'X Posts';
$l['myadvertisements_editzone_xposts_desc'] = 'Only enter the number of posts in this field, if the postbit ad display mode is 3rd or 4th.';

// Zones - Delete
$l['myadvertisements_zone_deleted'] = 'The selected zone has been deleted successfully.';
$l['myadvertisements_confirm_deletezone'] = 'Are you sure you want to delete the selected zone? ALL Advertisements set to this zone will be deleted and it CANNOT be undone!';

// Advertisements - Add
$l['myadvertisements_addadvertisement'] = 'Add Advertisement';
$l['myadvertisements_addadvertisement_name'] = 'Name';
$l['myadvertisements_addadvertisement_name_desc'] = 'Enter the name of the advertisement.';
$l['myadvertisements_addadvertisement_description'] = 'Description';
$l['myadvertisements_addadvertisement_description_desc'] = 'Enter a description for this advertisement.';
$l['myadvertisements_addadvertisement_expire'] = 'Days to expire';
$l['myadvertisements_addadvertisement_expire_desc'] = 'Enter the number of days until this advertisement expires. (set to "unlimited" (without quotes) if you want it to be unlimited)';
$l['myadvertisements_addadvertisement_exemptgroups'] = 'Exempt Groups';
$l['myadvertisements_addadvertisement_exemptgroups_desc'] = 'Enter the group id\'s of the user groups that you do not want to view this ad. (separated by a comma)';
$l['myadvertisements_addadvertisement_zone'] = 'Zone';
$l['myadvertisements_addadvertisement_zone_desc'] = 'Select the ad zone this ad will be shown.';
$l['myadvertisements_addadvertisement_advertisement'] = 'Advertisement';
$l['myadvertisements_addadvertisement_advertisement_desc'] = 'Enter the advertisement code.';
$l['myadvertisements_addadvertisement_disabled'] = 'Disabled';
$l['myadvertisements_addadvertisement_disabled_desc'] = 'Set to Yes if this advertisement is disabled, otherwise set to No.';
$l['myadvertisements_advertisement_added'] = 'A new advertisement has been added successfully.';

$l['myadvertisements_emails'] = 'Emails';
$l['myadvertisements_emails_desc'] = 'Enter the e-mails to which an e-mail notice will be sent X days before this advertisement expires. If empty, the fields below can be left empty as they will not be used. Separated by comma.';
$l['myadvertisements_email_subject'] = 'E-mail Subject';
$l['myadvertisements_email_message'] = 'E-mail Message';
$l['myadvertisements_email_message_desc'] = 'You can use {boardname},{email},{boardurl},{adname},{expirationdate},{stats}.';

// Advertisements - Edit
$l['myadvertisements_editadvertisement'] = 'Edit advertisement';
$l['myadvertisements_editadvertisement_name'] = 'Name';
$l['myadvertisements_editadvertisement_name_desc'] = 'Enter the name of the advertisement.';
$l['myadvertisements_editadvertisement_description'] = 'Description';
$l['myadvertisements_editadvertisement_description_desc'] = 'Enter a description for this advertisement.';
$l['myadvertisements_editadvertisement_expire'] = 'Days to expire';
$l['myadvertisements_editadvertisement_expire_desc'] = 'Enter the number of days until this advertisement expires. (set to "unlimited" (without quotes) if you want it to be unlimited)';
$l['myadvertisements_editadvertisement_exemptgroups'] = 'Exempt Groups';
$l['myadvertisements_editadvertisement_exemptgroups_desc'] = 'Enter the group id\'s of the user groups that you do not want to view this ad. (separated by a comma)';
$l['myadvertisements_editadvertisement_advertisement'] = 'Advertisement';
$l['myadvertisements_editadvertisement_zone'] = 'Zone';
$l['myadvertisements_editadvertisement_zone_desc'] = 'Select the ad zone this ad will be shown.';
$l['myadvertisements_editadvertisement_advertisement_desc'] = 'Enter the advertisement code.';
$l['myadvertisements_editadvertisement_disabled'] = 'Disabled';
$l['myadvertisements_editadvertisement_disabled_desc'] = 'Set to Yes if this advertisement is disabled, otherwise set to No.';
$l['myadvertisements_advertisement_edited'] = 'The selected advertisement has been edited successfully.';

// Advertisements - Delete
$l['myadvertisements_advertisement_deleted'] = 'The selected advertisement has been deleted successfully.';
$l['myadvertisements_confirm_deleteadvertisement'] = 'Are you sure you want to delete the selected advertisement?';

//This has to be here in case the task is run from the ACP
$l['myadvertisements_task_ran'] = 'My Advertisements task ran.';

$l['myadvertisements_pm_subject'] = 'Advertisement expired';
$l['myadvertisements_pm_message'] = 'Advertisement whose name is {1} has expired. Ad ID: {2}';

$l['myadvertisements_notice_expired'] = 'Expired';
$l['myadvertisements_notice_disabled'] = ' <small>(Disabled)</small>';

$l['myadvertisements_created'] = 'Created';

$l['myadvertisements_at'] = 'at';
$l['myadvertisements_stats_email'] = 'Clicks: {1}; Views: {2}';
?>
