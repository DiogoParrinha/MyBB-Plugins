<?php
/*
	MyProtection
	Author: Diogo Parrinha
	Copyright: © 2021 Diogo Parrinha

	This plugin enhances your forum security and prevents data loss.
*/

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

if(!defined("IN_MYBB")) {
	header('HTTP/1.0 404 Not Found');
	echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL /inc/plugins/myprotection.php was not found on this server.</p>
</body></html>
";
	exit;
}

$plugins->add_hook("global_start", "myprotection_global"); // user
$plugins->add_hook("admin_load", "myprotection_global"); // admin

define('MP_EMAIL', 'myemail@mail.com'); // change this to your email
define('MP_ADMIN_UID', 1); // change this to your admin user id (usually super admin)
define('MP_ADMINS_UIDS', '1'); // uid's of all admins on your forum seperated by a comma, for example: '1,3,5'
define('MP_EMAIL_DELAY', 10); // delay in seconds between emails are sent - 600 seconds by default = 10 minutes
define('MP_BLOCK_BOARD', 1); // set to 1 if you want MyProtection to immediately block the usage of the forums once something wrong is found

function myprotection_info()
{
	return array(
		"name"			=> "MyProtection",
		"description"	=> "This plugin enhances your forum security and prevents data loss.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "1.5",
		"guid" 			=> "176b9f14804d1bc8ad4d2042af7238c3",
		"compatibility" => "18*"
	);
}

function myprotection_activate()
{
	global $cache;

	$cache->update('myprotection_email_sent', array(
		'email_sent' => 0,
		'time' => 0
	));
}

function myprotection_deactivate()
{
	global $db;

	$db->delete_query("datacache", "title = 'myprotection_email_sent'");
}

function myprotection_global()
{
	global $mybb, $db, $cache;

	$email_sent = $cache->read('myprotection_email_sent');

	if (TIME_NOW - $email_sent['time'] < MP_EMAIL_DELAY && $email_sent['email_sent'] == 1)
	{

		if (MP_BLOCK_BOARD == 1)
		{
			// block board usage
			die("I'm sorry, but you can't use this board until the administrator fixes the problem.");
		}
		return;
	}
	else {
		$cache->update('myprotection_email_sent', array(
			'email_sent' => 0,
			'time' => $email_sent['time']
		));
	}

	$super_admin = $db->fetch_field($db->simple_select("users", 'username', 'uid='.intval(MP_ADMIN_UID), array('limit' => 1)), 'username');

	$failed = false;

	if (!$super_admin) // super admin account not found?
	{
		my_mail(MP_EMAIL, "Super Admin not found", "Your administrator account was not found in the database.\nMyProtection plugin has made a backup of the database right after this email was sent to you.", "MyProtection Plugin");
		$failed = true;
	}

	$admin_count = 0;
	$admins = explode(',', MP_ADMINS_UIDS);
	$admins_array = array();

	switch($db->type)
	{
		case "pgsql":
		case "sqlite3":
		case "sqlite2":
			$additional_sql .= " OR ','||additionalgroups||',' LIKE '%,4,%'";
			break;
		default:
			$additional_sql .= "OR CONCAT(',',additionalgroups,',') LIKE '%,4,%'";
	}
	$search_sql .= "usergroup='4' {$additional_sql}";

	$query = $db->simple_select("users", 'uid,username', $search_sql);
	while ($user = $db->fetch_array($query))
	{
		if (!in_array($user['uid'], $admins))
		{
			my_mail(MP_EMAIL, "Extra administrator found", "MyProtection found an administrator that was not defined. User id: ".$user['uid']."\nA database backup was made right after this email was sent to you.", "MyProtection Plugin");
		}
		$admin_count++;
		$admins_array[] = $user['uid'];
	}

	if (intval($admin_count) != intval(count($admins))) //number of admins doesn't match?
	{
		my_mail(MP_EMAIL, "Extra Administrators found", "MyProtection found ".$admin_count." administrators while you have specified only ".count($admins)." admins.\nA database backup was made right after this email was sent to you.", "MyProtection Plugin");
		$failed = true;
	}
	elseif($admin_count < 1) // no admins found?
	{
		my_mail(MP_EMAIL, "Admin accounts not found", "MyProtection could not find any administrators in your database.\nA database backup was made right after this email was sent to you.", "MyProtection Plugin");
		$failed = true;
	}

	if ($search = array_diff($admins_array, $admins))
	{
		$search = implode(",", $search);
		my_mail(MP_EMAIL, "Admin not found", "MyProtection could not find the following administrators in the database: ".$search, "MyProtection Plugin");
		$failed = true;
	}

	if ($failed) // a problem has occurred
	{
		$cache->update('myprotection_email_sent', array(
			'email_sent' => 1,
			'time' => TIME_NOW
		));

		// backup database
		myprotection_backupdb();
	}
}

// a modified copy of task_backupdb() from backupdb.php
function myprotection_backupdb()
{
	global $db, $config, $lang;
	static $contents;

	@set_time_limit(0);

	if(!defined('MYBB_ADMIN_DIR'))
	{
		if(!isset($config['admin_dir']))
		{
			$config['admin_dir'] = "admin";
		}

		define('MYBB_ADMIN_DIR', MYBB_ROOT.$config['admin_dir'].'/');
	}

	// Check if folder is writable, before allowing submission
	if(!is_writable(MYBB_ADMIN_DIR."/myprotection/backups"))
	{
		return false;
	}
	else
	{
		$db->set_table_prefix('');

		$file = MYBB_ADMIN_DIR.'/myprotection/backups/backup_'.substr(md5($mybb->user['uid'].TIME_NOW), 0, 10).random_str(54);

		if(function_exists('gzopen'))
		{
			$fp = gzopen($file.'.sql.gz', 'w9');
		}
		else
		{
			$fp = fopen($file.'.sql', 'w');
		}

		$tables = $db->list_tables($config['database']['database'], $config['database']['table_prefix']);

		$time = date('dS F Y \a\t H:i', TIME_NOW);
		$header = "-- MyBB Database Backup\n-- Generated: {$time}\n-- -------------------------------------\n\n";
		$contents = $header;
		foreach($tables as $table)
		{
			$field_list = array();
			$fields_array = $db->show_fields_from($table);
			foreach($fields_array as $field)
			{
				$field_list[] = $field['Field'];
			}

			$fields = implode(",", $field_list);

			$structure = $db->show_create_table($table).";\n";
			$contents .= $structure;
			myprotection_clear_overflow($fp, $contents);

			$query = $db->simple_select($table);
			while($row = $db->fetch_array($query))
			{
				$insert = "INSERT INTO {$table} ($fields) VALUES (";
				$comma = '';
				foreach($field_list as $field)
				{
					if(!isset($row[$field]) || trim($row[$field]) == "")
					{
						$insert .= $comma."''";
					}
					else
					{
						$insert .= $comma."'".$db->escape_string($row[$field])."'";
					}
					$comma = ',';
				}
				$insert .= ");\n";
				$contents .= $insert;
				myprotection_clear_overflow($fp, $contents);
			}
		}

		$db->set_table_prefix(TABLE_PREFIX);

		if(function_exists('gzopen'))
		{
			gzwrite($fp, $contents);
			gzclose($fp);
		}
		else
		{
			fwrite($fp, $contents);
			fclose($fp);
		}

		return true;
	}
}

// Allows us to refresh cache to prevent over flowing
function myprotection_clear_overflow($fp, &$contents)
{
	global $mybb;

	if(function_exists('gzopen'))
	{
		gzwrite($fp, $contents);
	}
	else
	{
		fwrite($fp, $contents);
	}

	$contents = '';
}

?>
