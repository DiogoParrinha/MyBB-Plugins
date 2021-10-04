<?php

define("IN_MYBB", 1);

require_once "./global.php";

$db->write_query("ALTER TABLE `".TABLE_PREFIX."mypermissions_actions` ADD `field` varchar(50) NOT NULL default '';");
$db->write_query("ALTER TABLE `".TABLE_PREFIX."mypermissions_actions` ADD `value` varchar(50) NOT NULL default '';");

$db->write_query("UPDATE `".TABLE_PREFIX."mypermissions_actions` SET `field`='action';");
$db->write_query("UPDATE `".TABLE_PREFIX."mypermissions_actions` SET `value`=`action`;");

$db->write_query("ALTER TABLE `".TABLE_PREFIX."mypermissions_actions` DROP `action`;");

// cache all rules
$rules = '';
$query = $db->simple_select('mypermissions_actions', '*', '', array('order_by' => 'file', 'order_dir' => 'asc'));
while ($rule = $db->fetch_array($query))
{
	$rules[$rule['aid']] = $rule;
}

$cache->update('mypermissions', $rules);

die("Upgraded to 1.3, please do not run this file again. DELETE THIS FILE!");

?>