<?php
/***************************************************************************
 *
 *  Post Counter plugin (/inc/languages/english/admin/postcounter.lang.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *  
 *  Website: http://consoleaddicted.com
 *  License: license.txt
 *
 *  Count someone's posts since a certain date
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

$l['postcounter_index'] = 'Post Counter';
$l['postcounter_canmanage'] = 'Can manage Post Counter?';
$l['postcounter'] = 'Post Counter';
$l['postcounter_count'] = 'Count';
$l['postcounter_count_desc'] = 'Count someone\'s posts since a certain date.';

$l['postcounter_submit'] = 'Submit';
$l['postcounter_reset'] = 'Reset';

$l['postcounter_user'] = 'User';
$l['postcounter_user_desc'] = 'Enter the username of the user whose posts you want to count.';

$l['postcounter_exemptforums'] = 'Exempt Forums';
$l['postcounter_exemptforums_desc'] = 'Enter the id\'s of the forums you do not want to include in the counting process.';

$l['postcounter_date'] = 'Date';
$l['postcounter_date_desc'] = 'Enter the date since you want to start counting. Date should be formatted like: DAY MONTH YEAR (e.g. 20 September 2009)';

$l['postcounter_threads'] = 'Count Threads';
$l['postcounter_threads_desc'] = 'Do you want to count threads as well? Thread counters and posts counters will be shown seperately in the end.';

$l['postcounter_invalid_user'] = 'You have selected an invalid user.';
$l['postcounter_invalid_date'] = 'You have entered an invalid date.';

$l['postcounter_total_count'] = 'Total posts: {1}';
$l['postcounter_total_count_both'] = 'Total posts: {1} - Total threads: {2}';

?>
