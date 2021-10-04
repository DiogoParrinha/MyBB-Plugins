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

echo "Adding new field...";
if(!$db->field_exists("hidelist", "mydonations_goal"))
		$db->write_query("ALTER TABLE `".TABLE_PREFIX."mydonations_goal` ADD `hidelist` tinyint(1) NOT NULL DEFAULT '0';");

echo "Done!<br />";

echo "Upgrade finished!<br />";
exit;

?>
