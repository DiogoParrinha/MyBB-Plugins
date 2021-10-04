<?php
/***************************************************************************
 *
 *   NewPoints plugin (/inc/plugins/newpoints/upgrades/upgrade_shop19.php)
 *	 Author: Pirata Nervo
 *   Copyright: © 2009-2012 Pirata Nervo
 *   
 *   Website: http://www.mybb-plugins.com
 *
 *   Upgrade file to upgrade NewPoints Shop 1.8 to NewPoints Shop 1.9
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

if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly.");
	
if (!defined('IN_ADMINCP'))
	die("This file must be accessed from the Administrator Panel.");

function upgrade_shop17_info()
{
	return array('new_version' => '1.9',
				 'name' => 'Upgrade Shop to 1.9',
				 'description' => 'Upgrade Shop 1.8 to Shop 1.9.<br />Two new settings will be added.'
				 );
}

// upgrade function
function upgrade_shop17_run()
{
	global $db;
	
	newpoints_add_setting('newpoints_shop_pmadmins', 'newpoints_shop', 'PM Admins', 'Enter the user IDs of the users that get PMs whenever an item is bought (separated by a comma).', 'text', '1', 8);
	newpoints_add_setting('newpoints_shop_pm_default', 'newpoints_shop', 'Default PM', 'Enter the content of the message body that is sent by default to users when they buy an item (note: this PM can be customized for each item; this is used in case one is not present). You can use {itemname} and {itemid}.', 'textarea', '', 9);
	newpoints_add_setting('newpoints_shop_pmadmin_default', 'newpoints_shop', 'Default Admin PM', 'Enter the content of the message body that is sent by default to admins when a user buys an item (note: this PM can be customized for each item; this is used in case one is not present). You can use {itemname} and {itemid}.', 'textarea', '', 10);
}

?>
