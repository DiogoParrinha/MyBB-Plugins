<?php
/***************************************************************************
 *
 *   MyDonations plugin (/upgrade14.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *   MyDonations is a MyBB plugin where you can manage goals and donations.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
require_once "./inc/init.php";

echo "Modify field...";
$db->write_query("ALTER TABLE `".TABLE_PREFIX."mydonations_goal` MODIFY payment_type varchar(255);");
$db->write_query("ALTER TABLE `".TABLE_PREFIX."mydonations_archive` MODIFY payment_type varchar(255);");
echo "Done!<br />";

echo "Upgrade finished!<br />";
exit;

?>
