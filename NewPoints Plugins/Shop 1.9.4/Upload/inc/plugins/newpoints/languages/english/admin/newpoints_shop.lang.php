<?php
/***************************************************************************
 *
 *   NewPoints Shop plugin (/inc/plugins/newpoints/languages/english/admin/newpoints_shop.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2009-2011 Diogo Parrinha
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

$l['newpoints_shop'] = "Shop";
$l['newpoints_shop_canmanage'] = "Can manage Shop?";

// Tabs
$l['newpoints_shop_categories'] = 'Categories';
$l['newpoints_shop_categories_desc'] = 'Manage categories.';
$l['newpoints_shop_items'] = 'Items';
$l['newpoints_shop_items_desc'] = 'Manage items in the selected category.';

$l['newpoints_shop_addcat'] = 'Add Category';
$l['newpoints_shop_addcat_desc'] = 'Add a new category to the shop.';
$l['newpoints_shop_editcat'] = 'Edit Category';
$l['newpoints_shop_editcat_desc'] = 'Edit an existing category.';
$l['newpoints_shop_deletecat'] = 'Delete Category';

$l['newpoints_shop_additem'] = 'Add Item';
$l['newpoints_shop_additem_desc'] = 'Add a new item to the shop.';
$l['newpoints_shop_edititem'] = 'Edit Item';
$l['newpoints_shop_edititem_desc'] = 'Edit an existing item.';
$l['newpoints_shop_deleteitem'] = 'Delete Item';

$l['newpoints_shop_inventory'] = 'Inventory';
$l['newpoints_shop_inventory_desc'] = 'Browsing a user\'s inventory.';

// Tcat
$l['newpoints_shop_cat_name'] = 'Name';
$l['newpoints_shop_cat_description'] = 'Description';
$l['newpoints_shop_cat_disporder'] = 'Display Order';
$l['newpoints_shop_cat_items'] = 'Items';
$l['newpoints_shop_cat_action'] = 'Action';

$l['newpoints_shop_item_action'] = 'Action';
$l['newpoints_shop_item_name'] = 'Name';
$l['newpoints_shop_item_description'] = 'Description';
$l['newpoints_shop_item_price'] = 'Price';
$l['newpoints_shop_item_stock'] = 'Stock';
$l['newpoints_shop_item_icon'] = 'Icon';
$l['newpoints_shop_item_disporder'] = 'Display Order';

// Add/Edit Cat
$l['newpoints_shop_addedit_cat_name'] = 'Name';
$l['newpoints_shop_addedit_cat_name_desc'] = 'Name of the category.';
$l['newpoints_shop_addedit_cat_description'] = 'Description';
$l['newpoints_shop_addedit_cat_description_desc'] = 'Description of the category.';
$l['newpoints_shop_addedit_cat_usergroups'] = 'Visible to Usergroups';
$l['newpoints_shop_addedit_cat_usergroups_desc'] = 'Select the user groups that can view this category.';
$l['newpoints_shop_addedit_cat_icon'] = 'Icon';
$l['newpoints_shop_addedit_cat_icon_desc'] = 'Path to a small icon image.';
$l['newpoints_shop_addedit_cat_visible'] = 'Visible';
$l['newpoints_shop_addedit_cat_visible_desc'] = 'Set to no if you do not want this category to be visible.';
$l['newpoints_shop_addedit_cat_disporder'] = 'Display Order';
$l['newpoints_shop_addedit_cat_disporder_desc'] = 'Display order of the category.';
$l['newpoints_shop_addedit_cat_expanded'] = 'Expanded by default';
$l['newpoints_shop_addedit_cat_expanded_desc'] = 'Do you want the category to be expanded by default?';

// Add/Edit Item
$l['newpoints_shop_addedit_item_name'] = 'Name';
$l['newpoints_shop_addedit_item_name_desc'] = 'Name of the item.';
$l['newpoints_shop_addedit_item_description'] = 'Description';
$l['newpoints_shop_addedit_item_description_desc'] = 'Description of the item.';
$l['newpoints_shop_addedit_item_price'] = 'Price';
$l['newpoints_shop_addedit_item_price_desc'] = 'Price of the item.';
$l['newpoints_shop_addedit_item_icon'] = 'Icon';
$l['newpoints_shop_addedit_item_icon_desc'] = 'Path to a small icon image.';
$l['newpoints_shop_addedit_item_visible'] = 'Visible';
$l['newpoints_shop_addedit_item_visible_desc'] = 'Set to no if you do not want this item to be visible.';
$l['newpoints_shop_addedit_item_disporder'] = 'Display Order';
$l['newpoints_shop_addedit_item_disporder_desc'] = 'Display order of the item.';
$l['newpoints_shop_addedit_item_stock'] = 'Stock';
$l['newpoints_shop_addedit_item_stock_desc'] = 'Number of items in stock.';
$l['newpoints_shop_addedit_item_limit'] = 'Limit per User';
$l['newpoints_shop_addedit_item_limit_desc'] = 'Maximum number of items of this type one user can buy. Leave empty/zero for infinite.';
$l['newpoints_shop_addedit_item_infinite'] = 'Infinite Stock';
$l['newpoints_shop_addedit_item_infinite_desc'] = 'Set to yes if you want this item to have an infinite stock.';
$l['newpoints_shop_addedit_item_sendable'] = 'Users can send';
$l['newpoints_shop_addedit_item_sendable_desc'] = 'Users can send this item to other users?';
$l['newpoints_shop_addedit_item_sellable'] = 'Users can sell';
$l['newpoints_shop_addedit_item_sellable_desc'] = 'Users can sell this item to other users?';
$l['newpoints_shop_addedit_item_category'] = 'Category';
$l['newpoints_shop_addedit_item_category_desc'] = 'The category where this item is going to be placed.';
$l['newpoints_shop_addedit_item_pm'] = 'Private Message';
$l['newpoints_shop_addedit_item_pm_desc'] = 'Enter the private message received by users when they buy this item. Leave blank to use the default PM.';
$l['newpoints_shop_addedit_item_pmadmin'] = 'Admin Private Message';
$l['newpoints_shop_addedit_item_pmadmin_desc'] = 'Enter the private message received by admins when users buy this item. Leave to use the default PM.';
$l['newpoints_shop_infinite'] = 'Infinite';
$l['newpoints_shop_select_cat'] = 'Select a category';

// Inventory
$l['newpoints_shop_inventory_of'] = 'Viewing Inventory of ';

// Success messages
$l['newpoints_shop_cat_added'] = 'A new category has been added';
$l['newpoints_shop_cat_edited'] = 'The selected category has been edited.';
$l['newpoints_shop_cat_deleted'] = 'The selected category has been deleted.';

$l['newpoints_shop_item_added'] = 'A new item has been added';
$l['newpoints_shop_item_edited'] = 'The selected item has been edited.';
$l['newpoints_shop_item_deleted'] = 'The selected item has been deleted.';

$l['newpoints_shop_item_removed'] = 'The item has been removed from the user\'s inventory.';

// Error messages
$l['newpoints_shop_invalid_cat'] = 'Invalid category';
$l['newpoints_shop_invalid_item'] = 'Invalid item';
$l['newpoints_shop_missing_field'] = 'There\'s at least one field missing.';
$l['newpoints_shop_invalid_user'] = 'You have selected an invalid user.';
$l['newpoints_shop_inventory_empty'] = 'The inventory is empty.';
$l['newpoints_shop_selected_item_not_owned'] = 'The user does not own the selected item.';

// Confirm messages
$l['newpoints_shop_confirm_deletecat'] = 'Are you sure you want to delete the selected category?';
$l['newpoints_shop_confirm_deleteitem'] = 'Are you sure you want to delete the selected item?';
$l['newpoints_shop_confirm_removeitem'] = 'Are you sure you want to remove the selected item from the user\'s inventory?';

// Other messages
$l['newpoints_shop_no_items'] = 'No items found.';
$l['newpoints_shop_no_cats'] = 'No categories found.';

// Buttons
$l['newpoints_shop_edit'] = 'Edit';
$l['newpoints_shop_delete'] = 'Delete';
$l['newpoints_shop_remove'] = 'Remove';
$l['newpoints_shop_submit'] = 'Submit';
$l['newpoints_shop_reset'] = 'Reset';

// Statistics
$l['newpoints_shop_item'] = 'Item';
$l['newpoints_shop_username'] = 'User';
$l['newpoints_shop_price'] = 'Price';
$l['newpoints_shop_date'] = 'Date';
$l['newpoints_stats_lastpurchases'] = 'Last Purchases';

// Rule
$l['newpoints_shop_items_rate'] = 'Items Rate';
$l['newpoints_shop_items_rate_desc'] = 'Items prices are multiplied by this number. Default is 1';

?>