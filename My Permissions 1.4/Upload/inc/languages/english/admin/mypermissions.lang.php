<?php
/***************************************************************************
 *
 *  My Advertisements plugin (/inc/languages/english/admin/mypermissions.lang.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *  
 *  
 *  License: license.txt
 *
 *  This plugin allows you to manage permissions on a larger scale.
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

$l['mypermissions'] = 'My Permissions';
$l['mypermissions_index'] = 'My Permissions';
$l['mypermissions_canmanage'] = 'Can manage My Permissions?';

// Tabs
$l['mypermissions_rules'] = 'Rules';
$l['mypermissions_rules_desc'] = 'Manage file permissions.';
$l['mypermissions_rules_add'] = 'Add';
$l['mypermissions_rules_add_desc'] = 'Add a new permission rule.';
$l['mypermissions_rules_edit'] = 'Edit';
$l['mypermissions_rules_edit_desc'] = 'Edit an existing permission rule.';
$l['mypermissions_rules_delete'] = 'Delete';
$l['mypermissions_rules_delete_desc'] = 'Delete an existing permission rule.';

// General
$l['mypermissions_edit'] = 'Edit';
$l['mypermissions_delete'] = 'Delete';
$l['mypermissions_submit'] = 'Submit';
$l['mypermissions_reset'] = 'Reset';

// Error messages
$l['mypermissions_norules'] = 'No permission rules found.';
$l['mypermissions_invalid_rule'] = 'Invalid permission rule.';
$l['mypermissions_missing_field'] = 'One or more fields are missing.';
$l['mypermissions_unknown_error'] = 'An unknown error has occurred.';

// Table cat
$l['mypermissions_file'] = 'File';
$l['mypermissions_description'] = 'Description';
$l['mypermissions_usergroups'] = 'User Groups';
$l['mypermissions_field'] = 'Field';
$l['mypermissions_value'] = 'Value';
$l['mypermissions_options'] = 'Options';

// Rules - Add
$l['mypermissions_addrule'] = 'Add Rule';
$l['mypermissions_addrule_file'] = 'File';
$l['mypermissions_addrule_file_desc'] = 'Enter the file that you want the permissions to affect.';
$l['mypermissions_addrule_field'] = 'Field';
$l['mypermissions_addrule_field_desc'] = 'Enter the the name of the field you want to block. This can remain blank if you want to block all fields for the file speciefied above.';
$l['mypermissions_addrule_value'] = 'Value';
$l['mypermissions_addrule_value_desc'] = 'Enter the the value that must be matched for the field above.';
$l['mypermissions_addrule_description'] = 'Description';
$l['mypermissions_addrule_description_desc'] = 'Enter a description for this rule.';
$l['mypermissions_addrule_usergroups'] = 'User Groups';
$l['mypermissions_addrule_usergroups_desc'] = 'Select the user groups that you want to be blocked.';
$l['mypermissions_rule_added'] = 'A new permission rule has been added successfully.';

// Rules - Edit
$l['mypermissions_editrule'] = 'Edit Rule';
$l['mypermissions_editrule_file'] = 'File';
$l['mypermissions_editrule_file_desc'] = 'Enter the file that you want the permissions to affect.';
$l['mypermissions_editrule_field'] = 'Field';
$l['mypermissions_editrule_field_desc'] = 'Enter the the name of the field you want to block. This can remain blank if you want to block all fields for the file speciefied above.';
$l['mypermissions_editrule_value'] = 'Value';
$l['mypermissions_editrule_value_desc'] = 'Enter the the value that must be matched for the field above.';
$l['mypermissions_editrule_description'] = 'Description';
$l['mypermissions_editrule_description_desc'] = 'Enter a description for this rule.';
$l['mypermissions_editrule_usergroups'] = 'User Groups';
$l['mypermissions_editrule_usergroups_desc'] = 'Select the user groups that you want to be blocked.';
$l['mypermissions_rule_edited'] = 'You have edited the selected rule successfully.';

// Rules - Delete
$l['mypermissions_rule_deleted'] = 'The selected rule has been deleted successfully.';
$l['mypermissions_confirm_deleterule'] = 'Are you sure you want to delete the selected rule?';

?>
