<?php
/***************************************************************************
 *
 *  Coupon Codes Generator plugin (/inc/languages/english/couponcodes.php)
 *  Author: Diogo Parrinha
 *  Copyright: © 2021 Diogo Parrinha
 *
 *
 *  License: license.txt
 *
 *  This adds a page to your forums which generates coupon codes.
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

$l['couponcodes'] = 'Coupon Codes Generator';
$l['couponcodes_message'] = 'Click Generate to generate a coupon code. The coupon code will be sent to your private messages inbox.<br />Chance of winning: {1}%';
$l['couponcodes_message_points'] = 'Click Generate to generate a coupon code. The coupon code will be sent to your private messages inbox.<br />Chance of winning: {1}%<br />Points required: {2}<br />You have: {3}';
$l['couponcodes_submit'] = 'Generate';

$l['couponcodes_pm_admin_subject'] = 'Coupon Code Generated';
$l['couponcodes_pm_admin_message'] = '{1} has generated the following coupon code: {2}';

$l['couponcodes_pm_user_subject'] = 'Coupon Code Generated';
$l['couponcodes_pm_user_message'] = 'You have generated the following coupon code: {1}';

$l['couponcodes_redirect_generated_title'] = 'Coupon Code Generated';
$l['couponcodes_redirect_generated_message'] = 'The generated coupon code has been sent to you through private messaging.';

$l['couponcodes_error_generated_title'] = 'You did not win';
$l['couponcodes_error_generated_message'] = 'Unforunately you did not win a coupon code.';

$l['couponcodes_error_wait'] = 'You must wait {1} second(s) until you can try to generate a new coupon code again.';
$l['couponcodes_error_win'] = 'You can only win {1} time(s).';

$l['couponcodes_not_enough_points'] = 'You do not have enough points.';

?>
