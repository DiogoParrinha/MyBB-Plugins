<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/inc/plugins/mydownloads.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *   MyDownloads adds a downloads system to MyBB.
 *
 ***************************************************************************/

if(!defined("IN_MYBB"))
	die("This file cannot be accessed directly. ");

if (defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_home_menu_quick_access', 'mydownloads_admin_quick_access');
	$plugins->add_hook('admin_user_users_edit_commit', 'mydownloads_update_username');
}
else
{
	$plugins->add_hook('global_start', 'mydownloads_start');
	$plugins->add_hook('global_start', 'mydownloads_alerts_formatter');
	$plugins->add_hook("build_friendly_wol_location_end", "mydownloads_online");
	$plugins->add_hook("index_start", "mydownloads_box");
	$plugins->add_hook("portal_start", "mydownloads_box");
	$plugins->add_hook('postbit', 'mydownloads_postbit', 50); // set priority to 50
	$plugins->add_hook('postbit_pm', 'mydownloads_postbit', 50); // set priority to 50
	$plugins->add_hook('postbit_announcement', 'mydownloads_postbit', 50); // set priority to 50
	$plugins->add_hook('postbit_prev', 'mydownloads_postbit', 50); // set priority to 50
	$plugins->add_hook("member_profile_end", "mydownloads_profile");

	$plugins->add_hook('global_intermediate', 'mydownloads_header');

	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager'))
		$plugins->add_hook('myalerts_load_lang', 'mydownloads_alerts_lang');
}

// cache templates
if(THIS_SCRIPT == 'index.php' || THIS_SCRIPT == 'portal.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'mydownloads_latest_submissions,mydownloads_latest_submissions_row,mydownloads_latest_submissions_row_empty,mydownloads_header_reports';
}
elseif(THIS_SCRIPT == 'showthread.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'mydownloads_postbit';
}
elseif(THIS_SCRIPT == 'member.php')
{
    global $templatelist;
    if(isset($templatelist))
    {
        $templatelist .= ',';
    }
    $templatelist .= 'mydownloads_profile';
}

function mydownloads_info()
{
	return array(
		"name"			=> "MyDownloads",
		"description"	=> "MyDownloads adds a downloads system to MyBB.",
		"author"		=> "Diogo Parrinha",
		"version"		=> "3.0.2",
		"guid" 			=> "",
		"compatibility" => "18*"
	);
}

function mydownloads_add_template($name = '', $contents = '')
{
	global $db;

	$templatearray = array(
		"title" => $name,
		"template" => $db->escape_string($contents),
		"sid" => "-1",
		);

	$db->insert_query("templates", $templatearray);
}

function mydownloads_install()
{
	global $db, $lang, $mybb;

	// create settings group
	$insertarray = array(
		'name' => 'mydownloads',
		'title' => 'MyDownloads',
		'description' => "Settings for MyDownloads plugin.",
		'disporder' => 100,
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);

	// add settings
	$setting1 = array(
		"name"			=> "mydownloads_is_active",
		"title"			=> "Is MyDownloads opened?",
		"description"	=> "Set to no if you want the downloads page to be closed to everybody.",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 1,
		"gid"			=> $gid
	);

	$setting2 = array(
		"name"			=> "mydownloads_guests_download",
		"title"			=> "Can guests download?",
		"description"	=> "Can guests download? If the points (in NewPoints points) and the price (PayPal money) of a download is 0, guests can get it because it is free. Set to No if you don\'t want guests to be able to download free downloads.",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 2,
		"gid"			=> $gid
	);

	$setting3 = array(
		"name"			=> "mydownloads_hide_guests",
		"title"			=> "Hide to guests?",
		"description"	=> "Do you want to hide the downloads page to guests?",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 3,
		"gid"			=> $gid
	);

	$setting4 = array(
		"name"			=> "mydownloads_downloads_dir",
		"title"			=> "Downloads folder",
		"description"	=> "The folder where download files are uploaded to.",
		"optionscode"	=> "text",
		"value"			=> "mydownloads/downloads",
		"disporder"		=> 4,
		"gid"			=> $gid
	);

	$setting5 = array(
		"name"			=> "mydownloads_previews_dir",
		"title"			=> "Previews folder",
		"description"	=> "The folder where preview files are uploaded to.",
		"optionscode"	=> "text",
		"value"			=> "mydownloads/previews",
		"disporder"		=> 5,
		"gid"			=> $gid
	);

	$setting6 = array(
		"name"			=> "mydownloads_flood",
		"title"			=> "Comment Flood Time",
		"description"	=> "Set the time (in seconds) users have to wait between commenting. This option doesn\'t affect moderators and administrators.",
		"optionscode"	=> "text",
		"value"			=> "30",
		"disporder"		=> 6,
		"gid"			=> $gid
	);

	$setting7 = array(
		"name"			=> "mydownloads_number_comments",
		"title"			=> "Comments per page",
		"description"	=> "Number of comments to display per page when viewing a download.",
		"optionscode"	=> "text",
		"value"			=> '5',
		"disporder"		=> 7,
		"gid"			=> $gid
	);

	$setting8 = array(
		"name"			=> "mydownloads_allow_html",
		"title"			=> "Allow HTML in descriptions?",
		"description"	=> "Do you allow the use of HTML in downloads/categories descriptions?",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 8,
		"gid"			=> $gid
	);

	$setting9 = array(
		"name"			=> "mydownloads_allow_img",
		"title"			=> "Allow IMG in descriptions?",
		"description"	=> "Do you allow the use of IMG tags in downloads/categories descriptions?",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 9,
		"gid"			=> $gid
	);

	$setting10 = array(
		"name"			=> "mydownloads_allow_mycode",
		"title"			=> "Allow MyCode in descriptions?",
		"description"	=> "Do you allow the use of MyCode in downloads/categories descriptions?",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 10,
		"gid"			=> $gid
	);

	$setting11 = array(
		"name"			=> "mydownloads_allow_smilies",
		"title"			=> "Allow Smilies in descriptions?",
		"description"	=> "Do you allow the use of smilies in downloads/categories descriptions?",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 11,
		"gid"			=> $gid
	);

	$setting12 = array(
		"name"			=> "mydownloads_filter_bad_words",
		"title"			=> "Filter Bad Words in descriptions?",
		"description"	=> "Filter bad words in downloads/categories descriptions?",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 12,
		"gid"			=> $gid
	);

	$setting13 = array(
		"name"			=> "mydownloads_allow_html2",
		"title"			=> "Allow HTML in comments?",
		"description"	=> "Do you allow the use of HTML in comments?",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 13,
		"gid"			=> $gid
	);

	$setting14 = array(
		"name"			=> "mydownloads_allow_mycode2",
		"title"			=> "Allow MyCode in comments?",
		"description"	=> "Do you allow the use of MyCode in comments?",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 14,
		"gid"			=> $gid
	);

	$setting15 = array(
		"name"			=> "mydownloads_allow_smilies2",
		"title"			=> "Allow Smilies in comments?",
		"description"	=> "Do you allow the use of smilies in comments?",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 15,
		"gid"			=> $gid
	);

	$setting16 = array(
		"name"			=> "mydownloads_allow_img2",
		"title"			=> "Allow IMG tags in comments?",
		"description"	=> "Do you allow the use of IMG tags in comments?",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 16,
		"gid"			=> $gid
	);

	$setting17 = array(
		"name"			=> "mydownloads_filter_bad_words2",
		"title"			=> "Filter Bad Words in comments?",
		"description"	=> "Filter bad words in comments?",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 17,
		"gid"			=> $gid
	);

	$setting18 = array(
		"name"			=> "mydownloads_pm_on_managing",
		"title"			=> "PM on approval/rejection",
		"description"	=> "PM the author of the download whenever a download is approved or rejected.",
		"optionscode"	=> "yesno",
		"value"			=> 1,
		"disporder"		=> 18,
		"gid"			=> $gid
	);

	$setting19 = array(
		"name"			=> "mydownloads_downloads_page",
		"title"			=> "Downloads per page",
		"description"	=> "How many downloads are shown per page?",
		"optionscode"	=> "text",
		"value"			=> 10,
		"disporder"		=> 19,
		"gid"			=> $gid
	);

	$setting20 = array(
		"name"			=> "mydownloads_gid_auto_approval",
		"title"			=> "Auto approval of download submissions",
		"description"	=> "Enter the group id\'s of the user groups whose download subsmissions are automatically approved. Leave blank to disable this feature.",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> 20,
		"gid"			=> $gid
	);

	$setting21 = array(
		"name"			=> "mydownloads_characters_limit",
		"title"			=> "Limit of characters in file names",
		"description"	=> "When someone downloads a file, the name of the file will not be longer than the number of characters you insert here. Extensions do not count. (Leave 0 to use a generated file name using the user id and an md5 hash - no limit of characters)",
		"optionscode"	=> "text",
		"value"			=> '0',
		"disporder"		=> 21,
		"gid"			=> $gid
	);

	$setting22 = array(
		"name"			=> "mydownloads_bridge_newpoints",
		"title"			=> "[NewPoints] Work together with NewPoints?",
		"description"	=> "Set to yes if you want downloads points to be activated. This takes effect only if NewPoints is installed.",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 22,
		"gid"			=> $gid
	);

	$setting23 = array(
		"name"			=> "mydownloads_newpoints_percentage",
		"title"			=> "[NewPoints] Points percentage",
		"description"	=> "If NewPoints is installed, enter the percentage of download points the author will get whenever someone purchases the download.",
		"optionscode"	=> "text",
		"value"			=> 100,
		"disporder"		=> 23,
		"gid"			=> $gid
	);

	$setting24 = array(
		"name"			=> "mydownloads_paypal_enabled",
		"title"			=> "Is PayPal enabled?",
		"description"	=> "Set to yes if you want users to pay for downloads using PayPal. This will display a \"Buy Now\" PayPal button (if the user hasn\'t paid yet) instead of the \"Download file\" button.",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 24,
		"gid"			=> $gid
	);

	$setting25 = array(
		"name"			=> "mydownloads_paypal_email",
		"title"			=> "PayPal email",
		"description"	=> "If the setting above is set to yes, you must enter your PayPal e-mail here in order to receive payments.",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> 25,
		"gid"			=> $gid
	);

	$setting26 = array(
		"name"			=> "mydownloads_paypal_pay_each",
		"title"			=> "Pay each time",
		"description"	=> "If the \"Is PayPal Enabled?\" setting is set to yes, do you want users to pay everytime they want to download the SAME download? E.g. user X has paid to download file Y but for some reason the user deleted the download from the hard drive and wants to re-download. Does user X have to pay again?",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 26,
		"gid"			=> $gid
	);

	$setting27 = array(
        "name" => "mydownloads_paypal_currency",
        "title" => "PayPal Currency",
        "description" => "If the \"Is PayPal Enabled?\" setting is set to yes, what is the currency you want users to pay in?",
        "optionscode" => "select
AUD= Australian Dollars
CAD= Canadian Dollars
CHF= Swiss Franc
DKK= Danish Krone
EUR= Euro
GBP= British Pound
HKD= Hong Kong Dollar
JPY= Japanese Yen
PLN= Polish Zloty
SGD= Singapore Dollar
USD= US Dollars",
        "value" => "USD",
        "disporder" => 27,
        "gid" => intval($gid),
	);

	$setting28 = array(
		"name"			=> "mydownloads_show_index",
		"title"			=> "Latest Submissions on Index",
		"description"	=> "How many downloads are shown in the index box \"Latest Download Submissions\"? Leave blank to not show the box. Downloads shown here do not go through permissions check (i.e. if user A has no permissions to view the category in which download B is, the user still sees the item listed, but cannot open it).",
		"optionscode"	=> "text",
		"value"			=> "5",
		"disporder"		=> 28,
		"gid"			=> $gid
	);

	$setting29 = array(
		"name"			=> "mydownloads_show_portal",
		"title"			=> "Latest Submissions on Portal",
		"description"	=> "How many downloads are shown in the portal box \"Latest Download Submissions\"? Leave blank to not show the box. Downloads shown here do not go through permissions check (i.e. if user A has no permissions to view the category in which download B is, the user still sees the item listed, but cannot open it).",
		"optionscode"	=> "text",
		"value"			=> "5",
		"disporder"		=> 29,
		"gid"			=> $gid
	);

	$setting30 = array(
		"name"			=> "mydownloads_can_delete",
		"title"			=> "Can authors delete submissions?",
		"description"	=> "Can download authors delete their submissions?",
		"optionscode"	=> "yesno",
		"value"			=> "0",
		"disporder"		=> 30,
		"gid"			=> $gid
	);

	$setting31 = array(
		"name"			=> "mydownloads_can_edit",
		"title"			=> "Can authors edit submissions?",
		"description"	=> "Can download authors edit their submissions?",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> 31,
		"gid"			=> $gid
	);

	$setting32 = array(
		"name"			=> "mydownloads_show_postbit",
		"title"			=> "Show \"View My Download Submissions\" link in postbit?",
		"description"	=> "Set to yes if you want to show a link to each user\'s download submissions in postbit.",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> 32,
		"gid"			=> $gid
	);

	$setting33 = array(
		"name"			=> "mydownloads_show_profile",
		"title"			=> "Show \"View My Download Submissions\" link in profile?",
		"description"	=> "Set to yes if you want to show a link to each user\'s download submissions in profile.",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> 33,
		"gid"			=> $gid
	);

	$setting34 = array(
		"name"			=> "mydownloads_show_updated",
		"title"			=> "Can view downloads being updated?",
		"description"	=> "Set to yes if you want users to be able to view and download items that have been updated and are waiting approval.",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> 34,
		"gid"			=> $gid
	);

	$setting35 = array(
		"name"			=> "mydownloads_allow_paypal_users",
		"title"			=> "Allow users to enter their PayPal Account",
		"description"	=> "Set to yes if you want users to be able to enter their PayPal account email. Payments will be sent to their account instead of yours.",
		"optionscode"	=> "yesno",
		"value"			=> "0",
		"disporder"		=> 35,
		"gid"			=> $gid
	);

	$setting36 = array(
		"name"			=> "mydownloads_stats_all",
		"title"			=> "Show Stats on All Categories",
		"description"	=> "Set to yes if you want to show the stats boxes on all category browsing pages. Set to No if you want to show on the MyDownloads home page only.",
		"optionscode"	=> "yesno",
		"value"			=> "0",
		"disporder"		=> 36,
		"gid"			=> $gid
	);

	$setting37 = array(
		"name"			=> "mydownloads_allow_urls",
		"title"			=> "Allow download URLs",
		"description"	=> "Set to yes if you want users to be able to enter a list of URLs rather than providing a download file.",
		"optionscode"	=> "yesno",
		"value"			=> "0",
		"disporder"		=> 37,
		"gid"			=> $gid
	);

	$setting38 = array(
		"name"			=> "mydownloads_max_previews",
		"title"			=> "Maximum Previews",
		"description"	=> "Enter the maximum amount of previews allowed per download item.",
		"optionscode"	=> "text",
		"value"			=> "5",
		"disporder"		=> 38,
		"gid"			=> $gid
	);

	$setting39 = array(
		"name"			=> "mydownloads_max_resolution",
		"title"			=> "Maximum Resolution for Previews",
		"description"	=> "Enter the maximum resolution allowed for previews: widthxheight, e.g. 200x300. Empty for no maximum.",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 39,
		"gid"			=> $gid
	);

	$setting40 = array(
		"name"			=> "mydownloads_latest_submissions",
		"title"			=> "All Submissions Page",
		"description"	=> "Select whether or not to enable the All Submissions Page. Downloads shown here do not go through permissions check (i.e. if user A has no permissions to view the category in which download B is, the user still sees the item listed, but cannot open it).",
		"optionscode"	=> "yesno",
		"value"			=> "1",
		"disporder"		=> 40,
		"gid"			=> $gid
	);

	$setting41 = array(
		"name"			=> "mydownloads_require_preview",
		"title"			=> "Preview required?",
		"description"	=> "If set to yes, users are required to upload a preview when submitting a new download.",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 41,
		"gid"			=> $gid
	);

	$setting42 = array(
		"name"            => "mydownloads_thumb_resolution_width",
		"title"            => "Maximum Width for Thumbnails",
		"description"    => "Enter the maximum width allowed for preview-thumbnails. This will not regenerate already existing thumbnails. Do not leave empty!",
		"optionscode"    => "text",
		"value"            => "100",
		"disporder"        => 42,
		"gid"            => $gid
    );

    $setting43 = array(
		"name"            => "mydownloads_thumb_resolution_height",
		"title"            => "Maximum Height for Thumbnails",
		"description"    => "Enter the maximum height allowed for preview-thumbnails. This will not regenerate already existing thumbnails. Do not leave empty!",
		"optionscode"    => "text",
		"value"            => "100",
		"disporder"        => 43,
		"gid"            => $gid
    );

    $setting44 = array(
		"name"            => "mydownloads_allow_video",
		"title"            => "Allow Videos via MyCode in descriptions?",
		"description"    => "Allow videos via MyCode in downloads/categories descriptions?",
		"optionscode"    => "yesno",
		"value"            => 0,
		"disporder"        => 44,
		"gid"            => $gid
    );

	$setting45 = array(
		"name"            => "mydownloads_time_edit",
		"title"            => "Time for Comment Edit",
		"description"    => "Enter the amount of seconds users have to edit their comments. After this time has passed, they can no longer edit comments unless they are moderators/administrators. If set to 0, there is no time limit. If set to -1, users cannot edit comments.",
		"optionscode"    => "text",
		"value"            => 900,
		"disporder"        => 45,
		"gid"            => $gid
    );

	$setting46 = array(
		"name" => "mydownloads_points_available",
		"title" => "[NewPoints] Points Available",
		"description" => "Enter the options available for the price of the items. If empty, users will be able to enter their desired value in a textbox.",
		"optionscode" => "text",
		"value" => "10,20,30,40,50",
		"disporder" => 46,
		"gid" => intval($gid),
	);

	$setting47 = array(
		"name"			=> "mydownloads_newpoints_pay_each",
		"title"			=> "[NewPoints] Pay each time",
		"description"	=> "If the \"Work together with NewPoints\" setting is set to yes, do you want users to pay everytime they want to download the SAME download? E.g. user X has paid to download file Y but for some reason the user deleted the download from the hard drive and wants to re-download. Does user X have to pay again?",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 47,
		"gid"			=> $gid
	);

	$db->insert_query("settings", $setting1);
	$db->insert_query("settings", $setting2);
	$db->insert_query("settings", $setting3);
	$db->insert_query("settings", $setting4);
	$db->insert_query("settings", $setting5);
	$db->insert_query("settings", $setting6);
	$db->insert_query("settings", $setting7);
	$db->insert_query("settings", $setting8);
	$db->insert_query("settings", $setting9);
	$db->insert_query("settings", $setting10);
	$db->insert_query("settings", $setting11);
	$db->insert_query("settings", $setting12);
	$db->insert_query("settings", $setting13);
	$db->insert_query("settings", $setting14);
	$db->insert_query("settings", $setting15);
	$db->insert_query("settings", $setting16);
	$db->insert_query("settings", $setting17);
	$db->insert_query("settings", $setting18);
	$db->insert_query("settings", $setting19);
	$db->insert_query("settings", $setting20);
	$db->insert_query("settings", $setting21);
	$db->insert_query("settings", $setting22);
	$db->insert_query("settings", $setting23);
	$db->insert_query("settings", $setting24);
	$db->insert_query("settings", $setting25);
	$db->insert_query("settings", $setting26);
	$db->insert_query("settings", $setting27);
	$db->insert_query("settings", $setting28);
	$db->insert_query("settings", $setting29);
	$db->insert_query("settings", $setting30);
	$db->insert_query("settings", $setting31);
	$db->insert_query("settings", $setting32);
	$db->insert_query("settings", $setting33);
	$db->insert_query("settings", $setting34);
	$db->insert_query("settings", $setting35);
	$db->insert_query("settings", $setting36);
	$db->insert_query("settings", $setting37);
	$db->insert_query("settings", $setting38);
	$db->insert_query("settings", $setting39);
	$db->insert_query("settings", $setting40);
	$db->insert_query("settings", $setting41);
	$db->insert_query("settings", $setting42);
	$db->insert_query("settings", $setting43);
	$db->insert_query("settings", $setting44);
	$db->insert_query("settings", $setting45);
	$db->insert_query("settings", $setting46);
	$db->insert_query("settings", $setting47);

	rebuild_settings();

	$collation = $db->build_create_table_collation();

	// create tables
	if(!$db->table_exists("mydownloads_categories"))
    {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydownloads_categories` (
		  `cid` int(10) UNSIGNED NOT NULL auto_increment,
		  `name` varchar(100) NOT NULL default '',
		  `description` varchar(255) NOT NULL default '',
		  `usergroups` varchar(50) NOT NULL default '',
		  `submit_dl_usergroups` varchar(50) NOT NULL default '',
		  `dl_usergroups` varchar(50) NOT NULL default '',
		  `downloads` int(10) UNSIGNED NOT NULL default '0',
		  `hidden` smallint(1) UNSIGNED NOT NULL default '0',
		  `disporder` smallint(5) UNSIGNED NOT NULL default '0',
		  `parent` int(10) UNSIGNED NOT NULL default '0',
		  `background` varchar(255) NOT NULL default '',
		  PRIMARY KEY  (`cid`), INDEX(`name`,`disporder`)
			) ENGINE=MyISAM{$collation}");
	}


	if(!$db->table_exists("mydownloads_log"))
    {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydownloads_log` (
		  `lid` int(10) UNSIGNED NOT NULL auto_increment,
		  `uid` bigint(30) UNSIGNED NOT NULL default '0',
		  `username` varchar(50) NOT NULL DEFAULT '',
		  `did` int(10) UNSIGNED NOT NULL,
		  `date` bigint(30) UNSIGNED NOT NULL default '0',
		  `type` smallint(1) UNSIGNED NOT NULL default '0',
		  `rating` smallint(5) UNSIGNED NOT NULL default '0',
		  PRIMARY KEY  (`lid`), INDEX(`date`,`did`)
			) ENGINE=MyISAM{$collation}");
	}

	if(!$db->table_exists("mydownloads_downloads"))
    {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydownloads_downloads` (
		  `did` int(10) UNSIGNED NOT NULL auto_increment,
		  `cid` int(10) UNSIGNED NOT NULL default '0',
		  `name` varchar(100) NOT NULL default '',
		  `description` text NOT NULL,
		  `hidden` smallint(1) UNSIGNED NOT NULL default '0',
		  `preview` text NOT NULL,
		  `thumbnail` varchar(255) NOT NULL default '',
		  `download` varchar(255) NOT NULL default '',
		  `url` text NOT NULL,
		  `filetype` varchar(50) NOT NULL default '',
		  `filesize` bigint(30) NOT NULL default '0',
		  `points` decimal(16,2) NOT NULL default '0',
		  `price` decimal(16,2) NOT NULL default '0',
		  `downloads` bigint(30) UNSIGNED NOT NULL default '0',
		  `numratings` int(10) UNSIGNED NOT NULL default '0',
		  `totalratings` int(10) UNSIGNED NOT NULL default '0',
		  `views` bigint(30) UNSIGNED NOT NULL default '0',
		  `comments` int(10) UNSIGNED NOT NULL default '0',
		  `submitter` varchar(100) NOT NULL default '',
		  `submitter_uid` bigint(30) UNSIGNED NOT NULL default '0',
		  `license` text NOT NULL,
		  `version` varchar(10) NOT NULL default '',
		  `md5` varchar(32) NOT NULL default '',
		  `date` bigint(30) NOT NULL default '0',
		  `receiver_email` varchar(127) NOT NULL default '',
		  `tags` varchar(255) NOT NULL default '',
		  `banner` varchar(255) NOT NULL default '',
		  PRIMARY KEY  (`did`), INDEX(`name`,`date`)
			) ENGINE=MyISAM{$collation}");
	}

	if(!$db->table_exists("mydownloads_paypal_logs"))
    {
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."mydownloads_paypal_logs (
			`lid` int(10) UNSIGNED NOT NULL auto_increment,
			`uid` bigint(30) UNSIGNED NOT NULL default '0',
			`receiver_email` varchar(127) NOT NULL default '',
			`receiver_id` varchar(13) NOT NULL default '',
			`business` varchar(127) NOT NULL default '',
			`item_name` varchar(127) default NULL,
			`item_number` varchar(127) default NULL,
			`quantity` int(10) default NULL,
			`invoice` varchar(127) default NULL,
			`custom` varchar(255) default NULL,
			`payment_type` enum('echeck','instant') default NULL,
			`payment_status` varchar(30) default NULL,
			`pending_reason` varchar(30) default NULL,
			`payment_date` varchar(55) default NULL,
			`exchange_rate` varchar(15) default NULL,
			`payment_gross` decimal(9,2) default NULL,
			`payment_fee` decimal(9,2) default NULL,
			`mc_gross` decimal(9,2) default NULL,
			`mc_fee` decimal(9,2) default NULL,
			`mc_currency` varchar(3) default NULL,
			`mc_handling` decimal(9,2) default NULL,
			`mc_shipping` decimal(9,2) default NULL,
			`tax` decimal(9,2) default NULL,
			`txn_id` varchar(17) default NULL,
			`txn_type` varchar(64) default NULL,
			`first_name` varchar(64) default NULL,
			`last_name` varchar(64) default NULL,
			`payer_business_name` varchar(127) default NULL,
			`payer_email` varchar(127) default NULL,
			`payer_id` varchar(13) default NULL,
			`payer_status` varchar(30) default NULL,
			`residence_country` varchar(2) default NULL,
			`parent_txn_id` varchar(17) default NULL,
			`notify_version` varchar(10) default NULL,
			`verify_sign` varchar(128) default NULL,
			`downloaded` smallint(1) NOT NULL default 0,
			PRIMARY KEY  (lid)
		) ENGINE=MyISAM{$collation}");
	}

	if(!$db->table_exists("mydownloads_submissions"))
    {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydownloads_submissions` (
		  `sid` int(10) UNSIGNED NOT NULL auto_increment,
		  `cid` int(10) UNSIGNED NOT NULL default '0',
		  `name` varchar(100) NOT NULL default '',
		  `description` text NOT NULL,
		  `hidden` smallint(1) UNSIGNED NOT NULL default '0',
		  `preview` text NOT NULL,
		  `thumbnail` varchar(255) NOT NULL default '',
		  `download` varchar(255) NOT NULL default '',
		  `url` text NOT NULL,
		  `filetype` varchar(50) NOT NULL default '',
		  `filesize` bigint(30) NOT NULL default '0',
		  `points` decimal(16,2) NOT NULL default '0',
		  `price` decimal(16,2) NOT NULL default '0',
		  `downloads` bigint(30) UNSIGNED NOT NULL default '0',
		  `numratings` int(10) UNSIGNED NOT NULL default '0',
		  `totalratings` int(10) UNSIGNED NOT NULL default '0',
		  `views` bigint(30) UNSIGNED NOT NULL default '0',
		  `comments` int(10) UNSIGNED NOT NULL default '0',
		  `submitter` varchar(100) NOT NULL default '',
		  `submitter_uid` bigint(30) UNSIGNED NOT NULL default '0',
		  `license` text NOT NULL,
		  `version` varchar(10) NOT NULL default '',
		  `update_did` int(10) UNSIGNED NOT NULL default '0',
		  `receiver_email` varchar(127) NOT NULL default '',
		  `tags` varchar(255) NOT NULL default '',
		  `banner` varchar(255) NOT NULL default '',
		  PRIMARY KEY (`sid`), KEY(`update_did`)
			) ENGINE=MyISAM{$collation}");
	}

	if(!$db->table_exists("mydownloads_ratings"))
    {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydownloads_ratings` (
		  `rid` int(10) UNSIGNED NOT NULL auto_increment,
		  `did` int(10) UNSIGNED NOT NULL default '0',
		  `rating` smallint(1) UNSIGNED NOT NULL default '0',
		  `ipaddress` varchar(30) NOT NULL default '',
		  `uid` bigint(30) UNSIGNED NOT NULL default '0',
		  PRIMARY KEY  (`rid`)
			) ENGINE=MyISAM{$collation}");
	}

	if(!$db->table_exists("mydownloads_comments"))
    {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydownloads_comments` (
		  `cid` int(10) UNSIGNED NOT NULL auto_increment,
		  `did` int(10) UNSIGNED NOT NULL default '0',
		  `uid` bigint(30) UNSIGNED NOT NULL default '0',
		  `comment` text NOT NULL,
		  `ipaddress` varchar(30) NOT NULL default '',
		  `username` varchar(100) NOT NULL default '',
		  `date` bigint(30) UNSIGNED NOT NULL default '0',
		  PRIMARY KEY  (`cid`)
			) ENGINE=MyISAM{$collation}");
	}

	if(!$db->table_exists("mydownloads_reports"))
    {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydownloads_reports` (
		  `rid` int(10) NOT NULL auto_increment,
		  `username` varchar(100) NOT NULL DEFAULT '',
		  `uid` int(10) NOT NULL DEFAULT 0,
		  `reason` varchar(255) NOT NULL DEFAULT '',
		  `date` bigint(30) NOT NULL DEFAULT 0,
		  `did` int(10) NOT NULL DEFAULT 0,
		  `marked` tinyint(1) NOT NULL DEFAULT 0,
		  `name` varchar(100) NOT NULL default '',
		  PRIMARY KEY  (`rid`), KEY(`date`)
			) ENGINE=MyISAM{$collation}");
	}

	if(!$db->table_exists("mydownloads_tags"))
    {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."mydownloads_tags` (
		  `tid` int(10) NOT NULL auto_increment,
		  `tag` varchar(255) NOT NULL DEFAULT '',
		  `color` varchar(7) NOT NULL DEFAULT '',
		  `categories` text,
		  PRIMARY KEY  (`tid`), KEY(`tag`)
			) ENGINE=MyISAM{$collation}");
	}
}

function mydownloads_is_installed()
{
	global $db, $lang, $mybb;

	if ($db->table_exists('mydownloads_categories')) return true;
	else return false;
}

function mydownloads_uninstall()
{
	global $db, $lang, $mybb;

	// delete settings group
	$db->delete_query("settinggroups", "name = 'mydownloads'");

	// remove settings
	$db->delete_query('settings', 'name LIKE \'%mydownloads%\'');

	// drop tables
	if($db->table_exists('mydownloads_categories'))
		$db->drop_table('mydownloads_categories');

	if($db->table_exists('mydownloads_downloads'))
		$db->drop_table('mydownloads_downloads');

	if($db->table_exists('mydownloads_submissions'))
		$db->drop_table('mydownloads_submissions');

	if($db->table_exists('mydownloads_log'))
		$db->drop_table('mydownloads_log');

	if($db->table_exists('mydownloads_ratings'))
		$db->drop_table('mydownloads_ratings');

	if($db->table_exists('mydownloads_comments'))
		$db->drop_table('mydownloads_comments');

	if ($db->table_exists('mydownloads_paypal_logs'))
		$db->drop_table('mydownloads_paypal_logs');

	if ($db->table_exists('mydownloads_reports'))
		$db->drop_table('mydownloads_reports');

	if ($db->table_exists('mydownloads_tags'))
		$db->drop_table('mydownloads_tags');

	rebuild_settings();
}

function mydownloads_activate()
{
	global $db, $lang;

	// First, get the instance of the alert type manager:
	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('mydownloads_new_comment'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
	}

	// edit templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

	find_replace_templatesets('index', '#'.preg_quote('{$forums}').'#', '{$mydownloads}'.'{$forums}');
	find_replace_templatesets('portal', '#'.preg_quote('{$announcements}').'#', '{$announcements}'.'{$mydownloads}');
	find_replace_templatesets('header', '#'.preg_quote('{$unreadreports}').'#', '{$unreadreports}'.'{$mydownloads_reports}');

	// create templates
	mydownloads_add_template("mydownloads", '
<html>
		<head>
		<title>{$title}</title>
			{$headerinclude}
			<script src="{$mybb->asset_url}/jscripts/mydownloads_tags.js"></script>
			<script src="{$mybb->asset_url}/jscripts/lightbox/js/lightbox.min.js"></script>
			<link href="{$mybb->asset_url}/jscripts/lightbox/css/lightbox.css" rel="stylesheet" />
			<style>
			.author_comment {
				background-color: #e5ffe5;
				color: #262626;
			}

			.tagspace{
				background-color: #777;
				float: left;
				margin: 2px;
				border-radius: 5px;
				padding: 0px;
			}
			.tag {
				float: left;
				margin: 2px;
				position: relative;
				font-family: Arial;
				font-size: 11px;
				font-weight: bold;
				padding: 1px 3px;
				border: 2px solid white;
				border-radius: 4px;
				color: white !important;
			}
			.tag:link, .tag:visited, .tag:hover, .tag:active{
				color: white !important;
				text-decoration: none;
			}
			.tagspace:hover {
				background-color: #444 !important;
			}
			</style>
			<meta name="description" content="{$meta[\'content\']}">
			<meta name="author" content="{$meta[\'author\']}">
		</head>
		<body>
		{$header}
		<div style="float: left">{$mysubmissions_button}{$submit_download}</div><div style="float: right"><form name="search_form" action="mydownloads.php" method="GET" style="padding-bottom: 5px"><input type="hidden" name="action" value="browse_cat" /><input type="hidden" name="cid" value="{$cid}"/><input type="text" value="{$lang->mydownloads_search}" class="textbox" id="name" name="name" onclick="if($(\'#name\').val() == \'{$lang->mydownloads_search}\') { $(\'#name\').val(\'\'); }" onblur="if ($(\'#name\').val().length == 0) { $(\'#name\').val(\'{$lang->mydownloads_search}\'); }">&nbsp;<input type="submit" class="button" value="{$lang->mydownloads_search}" name="search"></form></div>
		{$filter_tags}
		{$stats}
		{$sub_categories_table}
		{$categories_table}
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" style="table-layout: {$table_layout}">
			<thead>
				<tr>
					{$mydownloads_title}
				</tr>
			</thead>
			<tbody style="{$banner}">
				<tr>
					{$mydownloads_head}
				</tr>
				{$download_items}
			</tbody>
		</table>
		{$license}
		{$comment}
		{$comments}
		{$multipage}
		{$footer}
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_stats", '
		<br />
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<tbody>
				<tr>
					<td class="thead" colspan="3">
						<strong>{$lang->mydownloads_stats}</strong>
					</td>
				</tr>
				<tr>
					<td class="tcat" width="33%">
						<strong>{$lang->mydownloads_most_downloaded}</strong>
					</td>
					<td class="tcat" width="33%">
						<strong>{$lang->mydownloads_most_viewed}</strong>
					</td>
					<td class="tcat" width="33%">
						<strong>{$lang->mydownloads_most_rated}</strong>
					</td>
				</tr>
				<tr>
					<td class="trow1" valign="top">
						<table width="100%" border="0" align="center">
							{$most_downloaded}
						</table>
					</td>
					<td class="trow1" valign="top">
						<table width="100%" border="0" align="center">
							{$most_viewed}
						</table>
					</td>
					<td class="trow1" valign="top">
						<table width="100%" border="0" align="center">
							{$most_rated}
						</table>
					</td>
				</tr>
			</tbody>
		</table><br />
	');

	mydownloads_add_template("mydownloads_stats_download", '
        <tr>
            <td width="90%">
                <div style="float: left; margin: 5px; width:25px; height:25px; background-image:url(\'{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$download[\'thumbnail\']}\'); background-size: cover; background-position: center;"></div><a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=view_down&amp;did={$download[\'did\']}" style="padding-top: 7px; position: absolute; width: 20%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 225px;">{$download[\'name\']}</a>
            </td>
            <td width="10%" style="text-align: right; padding-right: 5px; top: -1px; position: relative;">
                {$download[\'stats\']}
            </td>
        </tr>
	');

	mydownloads_add_template("mydownloads_stats_nodata", '
		<tr>
			<td class="trow1" colspan="2" width="100%">
				{$lang->mydownloads_no_downloads}
			</td>
		</tr>
	');

	mydownloads_add_template("mydownloads_title_categories", '
		<td class="thead" width="100%" colspan="{$colspan}">
			<strong>{$category_name}</strong>
		</td>
	');

	mydownloads_add_template("mydownloads_head_downloads", '
		<td class="tcat" width="10%" align="center">
			<strong>{$lang->mydownloads_download_preview}</strong>
		</td>
		<td class="tcat" width="25%" align="center">
			<strong>{$lang->mydownloads_download_name}</strong>
		</td>
		<td class="tcat" width="10%" align="center">
			<strong>{$lang->mydownloads_download_comments}</strong>
		</td>
		<td class="tcat" width="5%" align="center">
			<strong>{$lang->mydownloads_download_views}</strong>
		</td>
		<td class="tcat" width="20%" align="center">
			<strong>{$lang->mydownloads_download_rate}</strong>
		</td>
		<td class="tcat" width="10%" align="center">
			<strong>{$lang->mydownloads_number_downloads}</strong>
		</td>
		{$points_column_head}
		{$price_column_head}
	');

	mydownloads_add_template("mydownloads_categories_category", '
		<tr>
			<td class="{$bgcolor}" width="100%">
				<div style="float: right; width: 75%">{$sub_categories}</div>
				<strong>{$category[\'name\']}</strong>
				<br />
				<small>{$category[\'description\']}</small>
			</td>
		</tr>
	');

	mydownloads_add_template("mydownloads_categories_category_no_name", '
		<tr>
            <td class="{$bgcolor}" style="height:50px; background-image: url(\'{$category[\'background\']}\'); background-repeat:no-repeat">
                <div style="float: right; width: 70%; margin-top: 1%">{$sub_categories}</div>
                <div style="float: left; width: 30%; height: 100%;">
					<a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=browse_cat&amp;cid={$category[\'cid\']}" style="display: block; height: 100%">
						<span style="width: 100%; display: inline-block;">&nbsp;</span>
					</a>
				</div>
            </td>
        </tr>
	');

	mydownloads_add_template("mydownloads_downloads_download", '
        <tr>
            <td class="{$bgcolor}" align="center">
                <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=view_down&amp;did={$download[\'did\']}"><img src="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$download[\'thumbnail\']}" style="max-width: {$mybb->settings[\'mydownloads_thumb_resolution_width\']}px; max-height: {$mybb->settings[\'mydownloads_thumb_resolution_height\']}px;" /></a>
            </td>
            <td class="{$bgcolor}" align="center">
                {$download[\'name\']}<br />
                <span class="smalltext">{$download[\'user\']}</span>
            </td>
            <td class="{$bgcolor}" align="center">
                {$download[\'comments\']}
            </td>
            <td class="{$bgcolor}" align="center">
                {$download[\'views\']}
            </td>
            <td class="{$bgcolor}" align="center">
                {$download[\'rate\']}
            </td>
            <td class="{$bgcolor}" align="center">
                {$download[\'downloads\']}
            </td>
            {$points_column}
            {$price_column}
        </tr>
	');

	mydownloads_add_template("mydownloads_downloads_rate", '
		<style type="text/css">

		.star_rating,
		.star_rating li a:hover,
		.star_rating .current_rating {
			background: url(images/star_rating.png) left -1000px repeat-x;
			vertical-align: middle;
		}

		.star_rating {
			position: relative;
			width:80px;
			height:16px;
			overflow: hidden;
			list-style: none;
			margin: 0;
			padding: 0;
			background-position: left top;
		}

		td .star_rating {
			margin: auto;
		}

		.star_rating li {
			display: inline;
		}

		.star_rating li a,
		.star_rating .current_rating {
			position: absolute;
			text-indent: -1000px;
			height: 16px;
			line-height: 16px;
			outline: none;
			overflow: hidden;
			border: none;
			top:0;
			left:0;
		}

		.star_rating_notrated li a:hover {
			background-position: left bottom;
		}

		.star_rating li a.one_star {
			width:20%;
			z-index:6;
		}

		.star_rating li a.two_stars {
			width:40%;
			z-index:5;
		}

		.star_rating li a.three_stars {
			width:60%;
			z-index:4;
		}

		.star_rating li a.four_stars {
			width:80%;
			z-index:3;
		}

		.star_rating li a.five_stars {
			width:100%;
			z-index:2;
		}

		.star_rating .current_rating {
			z-index:1;
			background-position: left center;
		}

		.star_rating_success, .success_message {
			color: #00b200;
			font-weight: bold;
			font-size: 10px;
			margin-bottom: 10px;
		}

		.inline_rating {
			margin-left: auto;
			margin-right: auto;
			vertical-align: middle;
			padding-right: 5px;
		}

		</style>

		<div id="user_rating_{$download[\'did\']}">{$lang->mydownloads_your_rate}: {$download[\'user_rate\']}</div>
		<div id="average_rating_{$download[\'did\']}">{$lang->mydownloads_total_rate}: {$download[\'averagerating\']}</div>

		<div style="margin-top: 6px; padding-right: 10px;" align="center">
				<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/download_rating.js?ver=1400"></script>
				<script type="text/javascript">
				<!--
					lang.stars = new Array();
					lang.stars[1] = "{$lang->mydownloads_one_star}";
					lang.stars[2] = "{$lang->mydownloads_two_stars}";
					lang.stars[3] = "{$lang->mydownloads_three_stars}";
					lang.stars[4] = "{$lang->mydownloads_four_stars}";
					lang.stars[5] = "{$lang->mydownloads_five_stars}";
					lang.mydownloads_ratings_update_error = "{$lang->mydownloads_ratings_update_error}";
				// -->
				</script>
				<div id="success_rating_{$download[\'did\']}" style="padding-top: 2px; padding-right: 10px;">&nbsp;</div>
				<div class="inline_rating" align="center">
					<ul class="star_rating{$not_rated}" id="rating_download_{$download[\'did\']}">
						<li style="width: {$download[\'width\']}%" class="current_rating" id="current_rating_{$download[\'did\']}">{$download[\'averagerating\']}</li>
						<li><a class="one_star" href="{$mybb->settings[\'bburl\']}/mydownloads/ratedownload.php?did={$download[\'did\']}&amp;rating=1&amp;my_post_key={$mybb->post_code}" title="{$lang->mydownloads_one_star}">1</a></li>
						<li><a class="two_stars" href="{$mybb->settings[\'bburl\']}/mydownloads/ratedownload.php?did={$download[\'did\']}&amp;rating=2&amp;my_post_key={$mybb->post_code}" title="{$lang->mydownloads_two_stars}">2</a></li>
						<li><a class="three_stars" href="{$mybb->settings[\'bburl\']}/mydownloads/ratedownload.php?did={$download[\'did\']}&amp;rating=3&amp;my_post_key={$mybb->post_code}" title="{$lang->mydownloads_three_stars}">3</a></li>
						<li><a class="four_stars" href="{$mybb->settings[\'bburl\']}/mydownloads/ratedownload.php?did={$download[\'did\']}&amp;rating=4&amp;my_post_key={$mybb->post_code}" title="{$lang->mydownloads_four_stars}">4</a></li>
						<li><a class="five_stars" href="{$mybb->settings[\'bburl\']}/mydownloads/ratedownload.php?did={$download[\'did\']}&amp;rating=5&amp;my_post_key={$mybb->post_code}" title="{$lang->mydownloads_five_stars}">5</a></li>
					</ul>
				</div>
		</div>
	');


	mydownloads_add_template("mydownloads_downloads_download_page", '
		<tr>
			<td class="tcat" colspan="3">
				<strong>{$lang->mydownloads_last_updated}:</strong> {$download[\'date\']}
			</td>
		</tr>
        <tr>
            <td class="trow1" align="center" align="30%" rowspan="{$row_span}" {$hidebackgroud}>
                <a data-title="{$download[\'realname\']}" data-lightbox="{$download[\'realname\']}" href="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$download[\'preview\']}" target="_blank" class="scaleimages"><img src="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$download[\'thumbnail\']}" /></a>
            </td>
            <td class="trow1" width="70%" colspan="2" {$hidebackgroud}>
                {$download[\'update_notice\']}
		    <div style="float: right">
				<form style="display: inline" action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="GET">
					<input name="did" value="{$download[\'did\']}" type="hidden" />
					<input name="action" value="report" type="hidden" />
					<input type="submit" name="report" class="button" value="{$lang->mydownloads_report_download}" />
				</form>
				{$edit_button}
				{$manage_previews}
			</div>
                <strong>{$lang->mydownloads_download_name}:</strong> {$download[\'name\']}
                <br /><strong>{$lang->mydownloads_category}:</strong> {$download[\'category\']}
		    <br class="clear" />
			{$tags}
            </td>
        </tr>
        {$points_row}
        {$price_row}
        <tr>
            <td class="trow1" width="35%" {$hidebackgroud}>
                <strong>{$lang->mydownloads_download_submitter}:</strong> <a href="{$mybb->settings[\'bburl\']}/{$download[\'submitter_url\']}">{$download[\'submitter\']}</a>
            </td>
            <td class="trow1" width="35%" align="center" {$hidebackgroud}>
                {$donatebutton}
            </td>
        </tr>
        <tr>
            <td class="trow1" width="35%" {$hidebackgroud}>
                {$lang->mydownloads_downloaded} {$lang->mydownloads_viewed}{$download[\'version\']}{$download[\'md5\']}
            </td>
            <td class="trow1" width="35%" align="center" {$hidebackgroud}>
                {$download[\'rate\']}
            </td>
        </tr>
        <tr>
            <td class="trow1" width="100%" colspan="3" {$hidebackgroud}>
                <strong>{$lang->mydownloads_download_description}:</strong><br/><div class="scaleimages">{$download[\'description\']}</div>
            </td>
        </tr>
        <tr>
            <td colspan="3" align="center" class="tfoot">
                {$download_button}
            </td>
        </tr>
');

	mydownloads_add_template("mydownloads_downloads_download_page_previews", '
	<div style="padding-top: 20px; width: 100%; margin: 0 auto; text-align: center">
		{$prevs}
	</div>
');

	mydownloads_add_template("mydownloads_downloads_download_page_previews_preview", '
<a data-title="{$download[\'realname\']}" data-lightbox="{$download[\'realname\']}" href="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$preview[\'preview\']}" target="_blank"><img src="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$preview[\'thumbnail\']}" style="max-width: {$mybb->settings[\'mydownloads_thumb_resolution_width\']}px; max-height: {$mybb->settings[\'mydownloads_thumb_resolution_height\']}px; border: 4px solid #ccc" /></a>
');

	mydownloads_add_template("mydownloads_downloads_no_download", '
		<tr>
			<td class="{$bgcolor}" width="100%" colspan="{$colspan}">
				{$lang->mydownloads_no_downloads}
			</td>
		</tr>
	');

	mydownloads_add_template("mydownloads_downloads_download_button", '
		<form action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="post" style="display: inline">
			<input type="hidden" name="postcode" value="{$mybb->post_code}" />
			<input type="hidden" name="did" value="{$download[\'did\']}" />
			<input type="hidden" name="action" value="do_download" />
			<input type="hidden" name="process" value="no" />
			<input type="submit" value="{$lang->mydownloads_purchase}" class="button" style="width: 228px; height: 44px; vertical-align: middle" />
		</form>
	');

	mydownloads_add_template("mydownloads_downloads_edit_button", '
		<form action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="get" style="display: inline">
			<input type="hidden" name="did" value="{$download[\'did\']}" />
			<input type="hidden" name="action" value="edit_down" />
			<input type="submit" value="{$lang->mydownloads_edit}" class="button" />
		</form>
	');

	mydownloads_add_template("mydownloads_downloads_manage_previews", '
		<form action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="get" style="display: inline">
			<input type="hidden" name="did" value="{$download[\'did\']}" />
			<input type="hidden" name="action" value="managepreviews" />
			<input type="submit" value="{$lang->mydownloads_manage_previews}" class="button" />
		</form>
	');

	mydownloads_add_template("mydownloads_downloads_download_button_url", '
		<form style="display: inline" action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="post" style="display: inline">
			<input type="hidden" name="postcode" value="{$mybb->post_code}" class="button" />
			<input type="hidden" name="did" value="{$download[\'did\']}" class="button" />
			<input type="hidden" name="action" value="do_download" class="button" />
			<input type="submit" name="submit" value="{$lang->mydownloads_download_url}" class="button" style="width: 228px; height: 44px; vertical-align: middle" />
		</form>
	');

	mydownloads_add_template("mydownloads_downloads_comment_textarea", '
		<br />
        <form method="post" action="{$mybb->settings[\'bburl\']}/mydownloads/comment_download.php?did={$download[\'did\']}" name="comment_form" id="comment_form">
            <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
            <input type="hidden" name="action" value="do_comment" />

            <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                <thead>
                    <tr>
                        <td class="thead" colspan="2">
                            <div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse.png" id="comment_img" class="expander" alt="[-]" title="[-]" /></div>
                            <div><strong>{$lang->mydownloads_comment}</strong></div>
                        </td>
                    </tr>
                </thead>
                <tbody id="comment_e">
                    <tr>
                        <td class="tcat" valign="top">
                            <strong>{$lang->mydownloads_message}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="trow1">
                            <div style="width: 100%">
                                <textarea style="width: 100%; padding: 4px; margin: 0;" rows="8" style="width: 90%" name="message" id="message" tabindex="1"></textarea>
                                {$codebuttons}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="center" class="tfoot"><input type="submit" class="button" value="{$lang->mydownloads_submit_comment}" tabindex="2" accesskey="s" id="comment_submit" /></td>
                    </tr>
                </tbody>
            </table>
        </form>
	');

	mydownloads_add_template("mydownloads_downloads_comment_textarea_login", '
		<br />
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="width: 100%;">
			<thead>
				<tr>
					<td class="thead" colspan="2">
						<div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse.png" id="comment_img" class="expander" alt="[-]" title="[-]" /></div
						<div><strong>{$lang->mydownloads_comment}</strong></div>
					</td>
				</tr>
			</thead>
			<tbody id="comment_e">
				<tr>
					<td class="trow1">
						<div><strong>{$lang->mydownloads_log_in_register}</strong></div>
					</td>
				</tr>

			</tbody>
		</table>
	');

	mydownloads_add_template("mydownloads_downloads_comment_comment", '
		<br />
		<a id="cid{$com[\'cid\']}" name="cid{$com[\'cid\']}"></a>
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="width: 100%; table-layout:fixed;">
			<thead>
				<tr>
					<td class="thead">
						{$edit_comment}{$delete_comment} {$com[\'date\']}
					</td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="trow1 {$com[\'author_style\']}">
						<table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
							<tr>
								<td class="post_author">
									<strong><span class="largetext"><a href="{$mybb->settings[\'bburl\']}/member.php?action=profile&uid={$com[\'uid\']}">{$com[\'username\']}</a></span></strong>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="trow2 post_content {$com[\'author_style\']}">
						<div class="post_body scaleimages">
							{$com[\'comment\']}
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	');

	mydownloads_add_template("mydownloads_downloads_comment_comment_delete", '
		<div style="float: right;">
			<form action="{$mybb->settings[\'bburl\']}/mydownloads/comment_download.php" method="post" onSubmit="return confirm(\'{$lang->mydownloads_delete_confirm}\');">
					<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
					<input type="hidden" name="cid" value="{$com[\'cid\']}" />
					<input type="hidden" name="action" value="delete_comment" />
					<input type="submit" value="{$lang->mydownloads_download_delete_comment}" class="button" />
			</form>
		</div>
	');

	mydownloads_add_template("mydownloads_downloads_comment_comment_edit", '
		<div style="float: right;">
			<form action="{$mybb->settings[\'bburl\']}/mydownloads/comment_download.php" method="get">
					<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
					<input type="hidden" name="cid" value="{$com[\'cid\']}" />
					<input type="hidden" name="action" value="edit_comment" />
					<input type="submit" value="{$lang->mydownloads_download_edit_comment}" class="button" />
			</form>
		</div>
	');

	mydownloads_add_template("mydownloads_categories_table", '
			<td width="220" valign="top">
				<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
					<tbody>
						<tr>
							<td class="thead">
								<strong>{$lang->mydownloads_categories}</strong>
							</td>
						</tr>
						{$data}
						<tr>
							<td class="tfoot">
								<small><a href="{$mybb->settings[\'bburl\']}/mydownloads.php"><strong>{$lang->mydownloads_categories_main}</strong></a></small>
							</td>
						</tr>
				</table>
			</td>
	');

	mydownloads_add_template("mydownloads_sub_categories_table", '
			<tr>
				<td width="100%" valign="top">
					<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
						<tbody>
							<tr>
								<td class="thead">
									<strong>{$lang->mydownloads_sub_categories}</strong>
								</td>
							</tr>
							<tr>
								<td class="tcat">
									<strong>{$lang->mydownloads_sub_categories_in_cat}</strong>
								</td>
							</tr>
							{$data2}
							<tr>
								<td class="tfoot">
									<small><a href="{$mybb->settings[\'bburl\']}/mydownloads.php"><strong>{$lang->mydownloads_categories_main}</strong></a></small>
								</td>
							</tr>
						</tbody>
					</table>
<br />
				</td>
			</tr>
	');

	mydownloads_add_template("mydownloads_submit_download_button", '
			<a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=submit_download&amp;cid={$cid}" class="button"><span style="background-position: 0 -198px">{$lang->mydownloads_submit}</span></a>
	');

	mydownloads_add_template("mydownloads_mysubmissions_button", '
			<a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=mysubmissions" class="button"><span style="background-position: 0 -77px">{$lang->mydownloads_mysubmissions}</span></a>
	');

	mydownloads_add_template("mydownloads_points_row", '
	<td class="trow1" width="{$col_width}" colspan="{$row_colspan}" {$hidebackgroud}>
		<strong>{$lang->mydownloads_download_points}:</strong> {$download[\'points\']} <small>({$lang->mydownloads_your_money}: {$usermoney})</small>
	</td>
	');

	mydownloads_add_template("mydownloads_price_row", '
	<td class="trow1" width="{$col_width}" colspan="{$row_colspan}" {$hidebackgroud}>
		<strong>{$lang->mydownloads_download_price}:</strong> {$download[\'price\']}
	</td>
	');

	mydownloads_add_template("mydownloads_email_row", '
	<td class="trow1" width="{$col_width}" colspan="{$row_colspan}" {$hidebackgroud}>
		<strong>{$lang->mydownloads_download_paypal}:</strong> {$download[\'receiver_email\']}
	</td>
	');

	mydownloads_add_template("mydownloads_tags_row", '
	<strong style="float: left; margin-right: 5px;display: inline-block;margin-top: 6px;">{$lang->mydownloads_tags}:</strong> {$download[\'tags\']}
	');

	mydownloads_add_template("mydownloads_tags_tag", '
	<div class="tagspace" style="background-color:{$tag[\'color\']};"><a class="tag" href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=browse_cat&amp;cid={$download[\'cid\']}&amp;tags[]={$tag[\'tid\']}">{$tag[\'tag\']}</a></div>
	');

	mydownloads_add_template("mydownloads_points_column", '
		<td align="center" class="{$bgcolor}">
			{$download[\'points\']}
		</td>
	');

	mydownloads_add_template("mydownloads_price_column", '
		<td align="center" class="{$bgcolor}">
			{$download[\'price\']}
		</td>
	');

	mydownloads_add_template("mydownloads_submit_points", '
<tr>
	<td width="30%" class="trow1">
		<strong>{$lang->mydownloads_submit_download_points}:</strong>
		<br />
		<span class="smalltext">{$lang->mydownloads_submit_download_points_desc}</span>
	</td>
	<td width="70%" class="trow1">
		<input type="text" name="points" class="textbox" value="{$download[\'points\']}" />
	</td>
</tr>
	');

	mydownloads_add_template("mydownloads_submit_points_predefined", '
<tr>
	<td width="30%" class="trow1">
		<strong>{$lang->mydownloads_submit_download_points}:</strong>
		<br />
		<span class="smalltext">{$lang->mydownloads_submit_download_points_desc}</span>
	</td>
	<td width="70%" class="trow1">
		<select name="points">
			{$pointsoptions}
		</select>
	</td>
</tr>
	');

	mydownloads_add_template("mydownloads_submit_price", '
<tr>
	<td width="30%" class="trow2">
		<strong>{$lang->mydownloads_submit_download_price}:</strong>
		<br />
		<span class="smalltext">{$lang->mydownloads_submit_download_price_desc}</span>
	</td>
	<td width="70%" class="trow2">
		<input type="text" name="price" class="textbox" value="{$download[\'price\']}" />
	</td>
</tr>
	');

	mydownloads_add_template("mydownloads_submit_urls", '
<tr>
	<td width="30%" valign="top" class="trow2">
		<strong>{$lang->mydownloads_submit_download_urls}:</strong>
		<br />
		<span class="smalltext">{$lang->mydownloads_submit_download_urls_desc}</span>
	</td>
	<td width="70%" class="trow2">
		<textarea style="width: 50%" rows="5" name="url">{$download[\'url\']}</textarea>
	</td>
</tr>
	');

	mydownloads_add_template("mydownloads_submit_email", '
<tr>
	<td width="30%" class="trow2">
		<strong>{$lang->mydownloads_submit_download_email}:</strong>
		<br />
		<span class="smalltext">{$lang->mydownloads_submit_download_email_desc}</span>
	</td>
	<td width="70%" class="trow2">
		<input type="text" name="business" class="textbox" value="{$download[\'receiver_email\']}" style="width: 50%" />
	</td>
</tr>
	');

	mydownloads_add_template("mydownloads_submit_tags", '
<tr>
	<td width="30%" valign="top" class="trow2">
		<strong>{$lang->mydownloads_submit_download_tags}:</strong>
		<br />
		<span class="smalltext">{$lang->mydownloads_submit_download_tags_desc}</span>
	</td>
	<td width="70%" class="trow2">
		{$tags}
	</td>
</tr>
	');

	mydownloads_add_template("mydownloads_submit_tags_tag", '
<span class="span_{$tag[\'categories\']}" style="{$hidden}">{$tag[\'tag\']}</span><input type="checkbox" name="tags[]" value="{$tag[\'tid\']}" {$checked} id="cats:{$tag[\'categories\']}" class="checkbox tag_checkbox" style="margin-right: 10px; {$hidden}" />
	');

	mydownloads_add_template("mydownloads_points_column_head", '
		<td class="tcat" width="10%" align="center">
			<strong>{$lang->mydownloads_download_points}</strong>
		</td>
	');

	mydownloads_add_template("mydownloads_price_column_head", '
		<td class="tcat" width="10%" align="center">
			<strong>{$lang->mydownloads_download_price}</strong>
		</td>
	');

	mydownloads_add_template("mydownloads_categories_category_no_cat", '
		<tr>
			<td class="{$bgcolor}" width="100%">
				{$lang->mydownloads_no_categories}
			</td>
		</tr>
	');

	mydownloads_add_template("mydownloads_downloads_download_version", '
		<br/><strong>{$lang->mydownloads_version}:</strong> {$download[\'version\']}
	');

	mydownloads_add_template("mydownloads_downloads_download_md5", '
		<br/><strong>{$lang->mydownloads_md5}:</strong> {$download[\'md5\']}
	');

	mydownloads_add_template("mydownloads_downloads_download_license", '
		<br />
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="width: 100%; table-layout:fixed;word-wrap:break-word;">
			  <thead>
				<tr>
				    <td class="thead">
					  <div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse_collapsed.png" id="license_img" class="expander" alt="[+]" title="[+]" /></div>
					  <strong>{$lang->mydownloads_license}</strong>
				    </td>
				</tr>
			  </thead>
			  <tbody id="license_e" style="display: none">
				 <tr>
				     <td class="trow1">
					   <div style="max-height: 300px; overflow: auto">{$license}</div>
				     </td>
				 </tr>
			</tbody>
		</table>
	');

	mydownloads_add_template("mydownloads_submit_download", '
<html>
		<head>
			<title>{$title}</title>
			{$headerinclude}
			<script type="text/javascript" src="{$mybb->asset_url}/jscripts/chosen-jquery/chosen.jquery.min.js"></script>
			<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/chosen-jquery/chosen.min.css" />
		</head>
		<body>
		{$header}
		<script type="text/javascript">
			$(document).ready(function() {
				$(\'.chosen-select\').chosen({width: "100%"});

				$(\'#submitform\').on(\'submit\', function(e){
					var require_preview = {$require_preview};
					if($(\'#preview_file\').val() == "" && require_preview == true)
					{
						e.preventDefault();
						alert("{$lang->mydownloads_require_preview}");
					}
				});

				$(\'#category\').on(\'change\', function(e){

					// So we changed the category...
					// Get the new value
					var new_val = $(this).val();

					$(\'.tag_checkbox\').each(function() {

						// Take categories out of the id attribute
						categories = $(this).attr(\'id\');
						categories = categories.substring(5);
						if(categories === \'0\')
						{
							// Global, make it visible (even if it is...doesn\'t matter)
							$(this).show();
							$(\'.span_\'+categories).show();
						}
						else
						{
							// Split by comma
							var array = categories.split(\'_\');

							if(jQuery.inArray(new_val, array) >= 0)
							{
								$(this).show();
								$(\'.span_\'+categories).show();
							}
							else
							{
								$(\'.span_\'+categories).hide();
								$(this).hide();
							}
						}
					});
				});

				// We need to check this here because if there is an error (missing field), the user must go back and the JS must run against the pre-selected category
				var new_val = $(\'#category\').val();

				$(\'.tag_checkbox\').each(function() {
					// Take categories out of the id attribute
					categories = $(this).attr(\'id\');
					categories = categories.substring(5);
					if(categories === \'0\')
					{
						// Global, make it visible (even if it is...doesn\'t matter)
						$(this).show();
						$(\'.span_\'+categories).show();
					}
					else
					{
						// Split by comma
						var array = categories.split(\'_\');

						if(jQuery.inArray(new_val, array) >= 0)
						{
							$(this).show();
							$(\'.span_\'+categories).show();
						}
						else
						{
							$(\'.span_\'+categories).hide();
							$(this).hide();
						}
					}
				});
			});
		</script>
		<br />
		<form id="submitform" action="{$mybb->settings[\'bburl\']}/mydownloads.php?action=submit_download" method="post" enctype="multipart/form-data">
		<input type="hidden" name="postcode" value="{$mybb->post_code}" />
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<thead>
				<tr>
					<td class="thead" colspan="2" width="100%">
						<strong>{$lang->mydownloads_submit_download}</strong>
					</td>
				<tr>
			</thead>
			<tbody>
				<tr>
					<td width="30%" class="trow1">
						<strong>{$lang->mydownloads_submit_download_name}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_name_desc}</span>
					</td>
					<td width="70%" class="trow1">
						<input type="text" name="name" class="textbox" />
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow2">
						<strong>{$lang->mydownloads_submit_download_description}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_description_desc}</span>
					</td>
					<td width="70%" class="trow2">
						<textarea class="textarea" id="description" style="width: 90%" rows="10" name="description"/></textarea>
						{$codebuttons}
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow1">
						<strong>{$lang->mydownloads_category}:</strong>
					</td>
					<td width="70%" class="trow1">
						{$cat_select}
					</td>
				</tr>
				{$submit_points}
				{$submit_price}
				{$submit_email}
				{$submit_url}
				{$submit_tags}
				<tr>
					<td width="30%" class="trow2">
						<strong>{$lang->mydownloads_submit_download_preview}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_preview_desc}</span>
					</td>
					<td width="70%" class="trow2">
						<input type="file" style="width: 200px;" name="preview_file" id="preview_file" />
						<!-- The container for the uploaded files -->
						<div id="files" class="files"></div>
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow1">
						<strong>{$lang->mydownloads_submit_download_download}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_download_desc}</span>
					</td>
					<td width="70%" class="trow1">
						<input class="fileupload" type="file" style="width: 200px;" name="download_file"/>
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow2">
						<strong>{$lang->mydownloads_submit_download_license}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_license_desc}</span>
					</td>
					<td width="70%" class="trow2">
						<textarea class="textarea" id="license" style="width: 50%" rows="5" name="license"/></textarea>
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow1">
						<strong>{$lang->mydownloads_submit_download_version}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_version_desc}</span>
					</td>
					<td width="70%" class="trow1">
						<input type="text" name="version" class="textbox" />
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow2">
						<strong>{$lang->mydownloads_submit_download_bannerurl}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_bannerurl_desc}</span>
					</td>
					<td width="70%" class="trow2">
						<input type="text" name="banner" class="textbox" />
					</td>
				</tr>
				<tr>
					<td width="100%" colspan="2" align="center" class="tfoot">
						<input type="submit" name="submit_download" class="button" value="{$lang->mydownloads_submit}" />
					</td>
				</tr>
			</tbody>
		</table>
		</form>
		{$footer}
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_edit_download", '
<html>
		<head>
		<title>{$title}</title>
		{$headerinclude}
		<script type="text/javascript" src="{$mybb->asset_url}/jscripts/chosen-jquery/chosen.jquery.min.js"></script>
		<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/chosen-jquery/chosen.min.css" />
		</head>
		<body>
		{$header}
		<script type="text/javascript">
			$(document).ready(function() {
				$(\'.chosen-select\').chosen({width: "100%"});

				$(\'#category\').on(\'change\', function(e){

					// So we changed the category...
					// Get the new value
					var new_val = $(this).val();

					$(\'.tag_checkbox\').each(function() {

						// Take categories out of the id attribute
						categories = $(this).attr(\'id\');
						categories = categories.substring(5);
						if(categories === \'0\')
						{
							// Global, make it visible (even if it is...doesn\'t matter)
							$(this).show();
							$(\'.span_\'+categories).show();
						}
						else
						{
							// Split by comma
							var array = categories.split(\'_\');

							if(jQuery.inArray(new_val, array) >= 0)
							{
								$(this).show();
								$(\'.span_\'+categories).show();
							}
							else
							{
								$(\'.span_\'+categories).hide();
								$(this).hide();
							}
						}
					});
				});

				// We need to check this here because if there is an error (missing field), the user must go back and the JS must run against the pre-selected category
				var new_val = $(\'#category\').val();

				$(\'.tag_checkbox\').each(function() {
					// Take categories out of the id attribute
					categories = $(this).attr(\'id\');
					categories = categories.substring(5);
					if(categories === \'0\')
					{
						// Global, make it visible (even if it is...doesn\'t matter)
						$(this).show();
						$(\'.span_\'+categories).show();
					}
					else
					{
						// Split by comma
						var array = categories.split(\'_\');

						if(jQuery.inArray(new_val, array) >= 0)
						{
							$(this).show();
							$(\'.span_\'+categories).show();
						}
						else
						{
							$(\'.span_\'+categories).hide();
							$(this).hide();
						}
					}
				});
			});
		</script>
		<br />
		<form action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="post" enctype="multipart/form-data">
		<input type="hidden" name="postcode" value="{$mybb->post_code}" />
		<input type="hidden" name="action" value="edit_down" />
		<input type="hidden" name="did" value="{$did}" />
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<thead>
				<tr>
					<td class="thead" colspan="2" width="100%">
						<strong>{$lang->mydownloads_edit_download}</strong>
					</td>
				<tr>
			</thead>
			<tbody>
				<tr>
					<td width="30%" class="trow1">
						<strong>{$lang->mydownloads_submit_download_name}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_name_desc}</span>
					</td>
					<td width="70%" class="trow1">
						<input type="text" name="name" class="textbox" value="{$download[\'name\']}" />
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow2">
						<strong>{$lang->mydownloads_submit_download_description}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_edit_download_description_desc}</span>
					</td>
					<td width="70%" class="trow2">
						<textarea class="textarea" id="description" style="width: 90%" rows="10" name="description"/>{$download[\'description\']}</textarea>
						{$codebuttons}
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow1">
						<strong>{$lang->mydownloads_category}:</strong>
					</td>
					<td width="70%" class="trow1">
						{$cat_select}
					</td>
				</tr>
				{$submit_points}
				{$submit_price}
				{$submit_email}
				{$submit_url}
				{$submit_tags}
				<tr>
					<td width="30%" class="trow2">
						<strong>{$lang->mydownloads_submit_download_download}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_edit_download_download_desc}</span>
					</td>
					<td width="70%" class="trow2">
						<input class="fileupload" type="file" style="width: 200px;" name="download_file"/>
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow1">
						<strong>{$lang->mydownloads_submit_download_license}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_license_desc}</span>
					</td>
					<td width="70%" class="trow1">
						<textarea class="textarea" id="license" style="width: 50%" rows="5" name="license" />{$download[\'license\']}</textarea>
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow2">
						<strong>{$lang->mydownloads_submit_download_version}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_version_desc}</span>
					</td>
					<td width="70%" class="trow2">
						<input type="text" name="version" class="textbox" value="{$download[\'version\']}" />
					</td>
				</tr>
				<tr>
					<td width="30%" class="trow1">
						<strong>{$lang->mydownloads_submit_download_bannerurl}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_submit_download_bannerurl_desc}</span>
					</td>
					<td width="70%" class="trow1">
						<input type="text" name="banner" class="textbox" value="{$download[\'banner\']}" />
					</td>
				</tr>
				<tr>
					<td width="100%" colspan="2" align="center" class="tfoot">
						<input type="submit" name="submit_download" class="button" value="{$lang->mydownloads_submit}" />
					</td>
				</tr>
			</tbody>
		</table>
		</form>
		{$footer}
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_edit_comment", '
<html>
		<head>
		<title>{$lang->mydownloads_edit_comment}</title>
		{$headerinclude}
		</head>
		<body>
		{$header}
		<br />
		<form action="{$mybb->settings[\'bburl\']}/mydownloads/comment_download.php" method="post">
		<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
		<input type="hidden" name="action" value="edit_comment" />
		<input type="hidden" name="cid" value="{$comment[\'cid\']}" />
		<input type="hidden" name="did" value="{$comment[\'did\']}" />
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<thead>
				<tr>
					<td class="thead" colspan="2" width="100%">
						<strong>{$lang->mydownloads_edit_comment}</strong>
					</td>
				<tr>
			</thead>
			<tbody>
				<tr>
					<td width="30%" class="trow2" valign="top">
						<strong>{$lang->mydownloads_message}:</strong>
					</td>
					<td width="70%" class="trow2">
						<textarea class="textarea" id="message" style="width: 90%" rows="10" name="message"/>{$comment[\'message\']}</textarea>
						{$codebuttons}
					</td>
				</tr>
				<tr>
					<td width="100%" colspan="2" align="center" class="tfoot">
						<input type="submit" name="submit" class="button" value="{$lang->mydownloads_submit}" />
					</td>
				</tr>
			</tbody>
		</table>
		</form>
		{$footer}
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_manage_previews", '
<html>
		<head>
			<title>{$title}</title>
			{$headerinclude}
			<script src="{$mybb->asset_url}/jscripts/lightbox/js/lightbox.min.js"></script>
			<link href="{$mybb->asset_url}/jscripts/lightbox/css/lightbox.css" rel="stylesheet" />
			<!-- blueimp Gallery styles -->
			<link rel="stylesheet" href="//blueimp.github.io/Gallery/css/blueimp-gallery.min.css">
			<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
			<link rel="stylesheet" href="{$mybb->asset_url}/fileupload/css/jquery.fileupload.css">
			<link rel="stylesheet" href="{$mybb->asset_url}/fileupload/css/jquery.fileupload-ui.css">
			<!-- CSS adjustments for browsers with JavaScript disabled -->
			<noscript><link rel="stylesheet" href="{$mybb->asset_url}/fileupload/css/jquery.fileupload-noscript.css"></noscript>
			<noscript><link rel="stylesheet" href="{$mybb->asset_url}/fileupload/css/jquery.fileupload-ui-noscript.css"></noscript>
		</head>
		<body>
		{$header}
		<br />
		<div id="dragdrop">
			<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
				<thead>
					<tr>
						<td class="thead">
							<strong>{$lang->mydownloads_add_previews}</strong> (<a href="javascript:;" onclick="javascript: $(\'#dragdrop\').slideToggle(); $(\'#legacy\').slideToggle();">{$lang->mydownloads_switch_legacy}</a>)
						</td>
					<tr>
				</thead>
				<tbody>
					<tr>
						<td class="tcat">
						<strong>{$lang->mydownloads_add_previews_desc} {$lang->mydownloads_max_previews}</strong>{$maxres}
						</td>
					</tr>
					<tr>
						<td class="trow1">
							<form id="fileupload" action="{$mybb->settings[\'bburl\']}/mydownloads.php?action=managepreviews" method="POST" enctype="multipart/form-data">
								<input type="hidden" name="postcode" value="{$mybb->post_code}" />
								<input type="hidden" name="cid" value="{$cid}" />
								<input type="hidden" name="did" value="{$did}" />
								<!-- Redirect browsers with JavaScript disabled to the origin page -->
								<noscript><strong>{$lang->mydownloads_use_legacy}</strong></noscript>
								<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
								<div class="row fileupload-buttonbar">
									<div class="col-lg-7">
										<!-- The fileinput-button span is used to style the file input field as button -->
										<input type="file" name="files[]" multiple class="button">
										<button type="submit" class="btn btn-primary start">
											<i class="glyphicon glyphicon-upload"></i>
											<span>{$lang->mydownloads_start_upload}</span>
										</button>
										<button type="reset" class="cancel">
											<i class="glyphicon glyphicon-ban-circle"></i>
											<span>{$lang->mydownloads_cancel_upload}</span>
										</button>
										<!-- The global file processing state -->
										<span class="fileupload-process"></span>
									</div>
									<!-- The global progress state -->
									<div class="col-lg-5 fileupload-progress fade">
										<!-- The global progress bar -->
										<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
											<div class="progress-bar progress-bar-success" style="width:0%;"></div>
										</div>
										<!-- The extended global progress state -->
										<div class="progress-extended">&nbsp;</div>
									</div>
								</div>
								<!-- The table listing the files available for upload/download -->
								<table role="presentation" class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" style="border: 0"><tbody class="files"></tbody></table>
							</form>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="legacy" style="display: none">
			<br />
			<form action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="post" enctype="multipart/form-data">
			<input type="hidden" name="postcode" value="{$mybb->post_code}" />
			<input type="hidden" name="action" value="managepreviews" />
			<input type="hidden" name="cid" value="{$cid}" />
			<input type="hidden" name="did" value="{$did}" />
			<input type="hidden" name="legacy" value="1" />
			<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
				<thead>
					<tr>
						<td class="thead" colspan="2" width="100%">
							<strong>{$lang->mydownloads_submit_preview}</strong> (<a href="javascript:;" onclick="javascript: $(\'#dragdrop\').slideToggle(); $(\'#legacy\').slideToggle();">{$lang->mydownloads_switch_dragdrop}</a>)
						</td>
					<tr>
				</thead>
				<tbody>
					<tr>
						<td class="tcat" colspan="2">
							<strong>{$lang->mydownloads_max_previews}</strong>{$maxres}
						</td>
					</tr>
					<tr>
						<td width="30%" class="trow1">
							<strong>{$lang->mydownloads_submit_download_preview}:</strong>
							<br />
							<span class="smalltext">{$lang->mydownloads_submit_download_preview_desc}</span>
						</td>
						<td width="70%" class="trow1">
							<input class="fileupload" type="file" style="width: 200px;" name="preview_file"/>
						</td>
					</tr>
					<tr>
						<td width="100%" colspan="2" align="center" class="tfoot">
							<input type="submit" name="submit_download" class="button" value="{$lang->mydownloads_submit}" />
						</td>
					</tr>
				</tbody>
			</table>
			</form>
		</div>
		<br />
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<thead>
				<tr>
					<td class="thead" colspan="2" width="100%">
						<strong>{$lang->mydownloads_previews}</strong>
					</td>
				<tr>
			</thead>
			<tbody>
				{$previews}
			</tbody>
		</table>
		{$footer}
		<!-- The template to display files available for upload -->
		<script id="template-upload" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
			<tr class="trow1 template-upload fade">
				<td>
					<span class="preview"></span>
				</td>
				<td>
					<p class="name">{%=file.name%}</p>
					<strong class="error text-danger"></strong>
				</td>
				<td>
					<p class="size">{$lang->mydownloads_processing}</p>
					<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
				</td>
				<td>
					{% if (!i && !o.options.autoUpload) { %}
						<button class="btn btn-primary start" disabled>
							<i class="glyphicon glyphicon-upload"></i>
							<span>{$lang->mydownloads_start}</span>
						</button>
					{% } %}
					{% if (!i) { %}
						<button class="btn btn-warning cancel">
							<i class="glyphicon glyphicon-ban-circle"></i>
							<span>{$lang->mydownloads_cancel}</span>
						</button>
					{% } %}
				</td>
			</tr>
		{% } %}
		</script>
		<!-- The template to display files after uploading -->
		<script id="template-download" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
			<tr class="trow1 template-upload fade">
				<td>
					<span class="preview">
						{% if (file.thumbnailUrl) { %}
							<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
						{% } %}
					</span>
				</td>
				<td>
					<p class="name">
						{% if (file.url) { %}
							<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?\'data-gallery\':\'\'%}>{%=file.name%}</a>
						{% } else { %}
							<span>{%=file.name%}</span>
						{% } %}
					</p>
				</td>
				<td>
					<span class="size">{%=o.formatFileSize(file.size)%}</span>
				</td>
				<td>
					{% if (file.error) { %}
						<div><span style="color: #B50300; font-weight: bold">{$lang->mydownloads_error}</span> {%=file.error%}</div>
					{% } else { %}
						<div><span style="color: #109E00; font-weight: bold">{$lang->mydownloads_success}</span></div>
						{% if (file.id) { %}
							&nbsp; <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=deletepreview&amp;did={$download[\'did\']}&amp;id={%=file.id%}&amp;my_post_key={$mybb->post_code}" onclick="return confirm(\'{$lang->mydownloads_delete_preview_confirm}\');">{$lang->mydownloads_delete}</a>
						{% } %}
					{% } %}
				</td>
			</tr>
		{% } %}
		</script>
		<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
		<script src="{$mybb->asset_url}/fileupload/js/vendor/jquery.ui.widget.js"></script>
		<!-- The Templates plugin is included to render the upload/download listings -->
		<script src="//blueimp.github.io/JavaScript-Templates/js/tmpl.min.js"></script>
		<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
		<script src="//blueimp.github.io/JavaScript-Load-Image/js/load-image.all.min.js"></script>
		<!-- The Canvas to Blob plugin is included for image resizing functionality -->
		<script src="//blueimp.github.io/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js"></script>
		<!-- blueimp Gallery script -->
		<script src="//blueimp.github.io/Gallery/js/jquery.blueimp-gallery.min.js"></script>
		<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
		<script src="{$mybb->asset_url}/fileupload/js/jquery.iframe-transport.js"></script>
		<!-- The basic File Upload plugin -->
		<script src="{$mybb->asset_url}/fileupload/js/jquery.fileupload.js"></script>
		<!-- The File Upload processing plugin -->
		<script src="{$mybb->asset_url}/fileupload/js/jquery.fileupload-process.js"></script>
		<!-- The File Upload image preview & resize plugin -->
		<script src="{$mybb->asset_url}/fileupload/js/jquery.fileupload-image.js"></script>
		<!-- The File Upload validation plugin -->
		<script src="{$mybb->asset_url}/fileupload/js/jquery.fileupload-validate.js"></script>
		<!-- The File Upload user interface plugin -->
		<script src="{$mybb->asset_url}/fileupload/js/jquery.fileupload-ui.js"></script>
		<!-- The main application script -->
		<script type="text/javascript">
			$(function () {
				\'use strict\';

				$(\'#fileupload\').fileupload({
					url: \'{$mybb->settings[\'bburl\']}/mydownloads.php?action=managepreviews\',
					// Enable image resizing, except for Android and Opera,
					// which actually support image resizing, but fail to
					// send Blob objects via XHR requests:
					disableImageResize: /Android(?!.*Chrome)|Opera/
						.test(window.navigator.userAgent),
					maxFileSize: 9999999999,
					sequentialUploads: true,
					acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i
				});
			});
		</script>
		<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
		<!--[if (gte IE 8)&(lt IE 10)]>
		<script src="{$mybb->asset_url}/fileupload/js/cors/jquery.xdr-transport.js"></script>
		<![endif]-->
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_manage_previews_preview", '
        <tr>
            <td width="50%" class="{$bgcolor}">
                {$preview[\'cover\']}<a data-title="{$download[\'name\']}" data-lightbox="{$download[\'name\']}" href="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$preview[\'preview\']}" target="_blank"><img src="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$preview[\'thumbnail\']}" style="max-width: {$mybb->settings[\'mydownloads_thumb_resolution_width\']}px; max-height: {$mybb->settings[\'mydownloads_thumb_resolution_height\']}px;" /></a>
            </td>
            <td width="50%" class="{$bgcolor}" align="center">
                <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=setcover&amp;did={$download[\'did\']}&amp;id={$preview[\'id\']}&amp;my_post_key={$mybb->post_code}">{$lang->mydownloads_set_cover}</a> - <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=deletepreview&amp;did={$download[\'did\']}&amp;id={$preview[\'id\']}&amp;my_post_key={$mybb->post_code}" onclick="return confirm(\'{$lang->mydownloads_delete_preview_confirm}\');">{$lang->mydownloads_delete}</a>
            </td>
        </tr>
	');

	mydownloads_add_template("mydownloads_manage_previews_nodata", '
		<tr>
			<td width="100%" colspan="2" class="trow1">
				{$lang->mydownloads_no_previews}
			</td>
		</tr>
	');

	mydownloads_add_template("mydownloads_latest_submissions_page", '
		<html>
		<head>
		<title>{$title}</title>
			{$headerinclude}
		</head>
		<body>
		{$header}
		<br />
		{$multipage}
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
		<tr>
			<td class="thead" colspan="2">
				<strong>{$lang->mydownloads_latest_submissions}</strong>
				<span style="float:right;"><a href="{$mybb->settings[\'bburl\']}/mydownloads.php">{$lang->mydownloads_go_to_all_downloads}</a>
			</td>
		</tr>
		{$latestsubmissions}
		</table>
		{$footer}
		</body>
		</html>
');

	mydownloads_add_template("mydownloads_latest_submissions", '
	<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
		<tr>
			<td class="thead" colspan="2">
				<strong>{$lang->mydownloads_latest_submissions}</strong>
				<span style="float:right;"><a href="{$mybb->settings[\'bburl\']}/mydownloads.php">{$lang->mydownloads_go_to_all_downloads}</a></td>
		</tr>
		{$latestsubmissions}
		</table>
	<br />');

	mydownloads_add_template("mydownloads_latest_submissions_row", '
<td class="{$bgcolor}" style="width: 50%;">
    <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=view_down&amp;did={$dl[\'did\']}" title="{$dl[\'name\']}"> <div style="float: left; margin: 2px; width: 60px; height: 60px; background-image:url(\'{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$dl[\'thumbnail\']}\'); background-size: cover; background-position: center;"></div></a>
    <div style="margin-left: 73px;">
        <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=view_down&amp;did={$dl[\'did\']}" title="{$dl[\'name\']}">{$dl[\'name\']}</a> - {$lang->mydownloads_download_submitter} {$dl[\'author\']}<br />
        <span class="smalltext">
		<strong>{$lang->mydownloads_submit_date}:</strong> {$dl[\'date\']} | <strong>{$lang->mydownloads_category}:</strong> <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=browse_cat&amp;cid={$dl[\'cid\']}" title="{$dl[\'category\']}">{$dl[\'category\']}</a>
		{$price}
	  </span>
    </div>
</td>');

	mydownloads_add_template("mydownloads_latest_submissions_row_price", '
		<br /><strong>{$lang->mydownloads_download_with}</strong>
		<br />
		<a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=view_down&amp;did={$dl[\'did\']}" title="{$dl[\'name\']}"><button class="class">{$prices}</button></a>');

	mydownloads_add_template("mydownloads_latest_submissions_row_empty", '<tr>
<td class="trow1">{$lang->mydownloads_no_submissions}</td>
</tr>');

	mydownloads_add_template("mydownloads_report_download", '
<html>
		<head>
		<title>{$title}</title>
		{$headerinclude}
		</head>
		<body>
		{$header}
		<br />
		<form action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="post">
		<input type="hidden" name="postcode" value="{$mybb->post_code}" />
		<input type="hidden" name="action" value="report" />
		<input type="hidden" name="did" value="{$did}" />
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<thead>
				<tr>
					<td class="thead" colspan="2" width="100%">
						<strong>{$lang->mydownloads_report_download}</strong>
					</td>
				<tr>
			</thead>
			<tbody>
				<tr>
					<td width="50%" class="trow1">
						<strong>{$lang->mydownloads_report_download_reason}:</strong>
						<br />
						<span class="smalltext">{$lang->mydownloads_report_download_reason_desc}</span>
					</td>
					<td width="50%" class="trow1">
						<textarea class="textarea" id="reason" style="width: 50%" rows="5" name="reason"/></textarea>
					</td>
				</tr>
				<tr>
					<td width="100%" colspan="2" align="center" class="tfoot">
						<input type="submit" name="report_download" class="button" value="{$lang->mydownloads_submit}" />
					</td>
				</tr>
			</tbody>
		</table>
		</form>
		{$footer}
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_mysubmissions", '
<html>
		<head>
		<title>{$title}</title>
		{$headerinclude}
		</head>
		<body>
		{$header}
		<br />
		{$multipage}
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<tbody>
				<tr><td class="thead" colspan="9"><strong>{$lang->mydownloads_my_submissions}</strong></td></tr>
				<tr>
					<td class="tcat" width="10%" align="center">
						<strong>{$lang->mydownloads_download_preview}</strong>
					</td>
					<td class="tcat" width="20%" align="center">
						<strong>{$lang->mydownloads_download_name}</strong>
					</td>
					<td class="tcat" width="20%" align="center">
						<strong>{$lang->mydownloads_download_category}</strong>
					</td>
					<td class="tcat" width="5%" align="center">
						<strong>{$lang->mydownloads_download_comments}</strong>
					</td>
					<td class="tcat" width="5%" align="center">
						<strong>{$lang->mydownloads_download_views}</strong>
					</td>
					<td class="tcat" width="10%" align="center">
						<strong>{$lang->mydownloads_download_rate}</strong>
					</td>
					<td class="tcat" width="5%" align="center">
						<strong>{$lang->mydownloads_number_downloads}</strong>
					</td>
					<td class="tcat" width="15%" align="center">
						<strong>{$lang->mydownloads_status}</strong>
					</td>
					{$options_head}
				</tr>
				{$download_items}
			</tbody>
		</table>
		{$multipage}
		{$footer}
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_mysubmissions_submission", '
				<tr>
					<td class="{$bgcolor}" align="center">
						<a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=view_down&amp;did={$download[\'did\']}"><img src="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$download[\'thumbnail\']}" style="max-width: {$mybb->settings[\'mydownloads_thumb_resolution_width\']}px; max-height: {$mybb->settings[\'mydownloads_thumb_resolution_height\']}px;"></a>
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'name\']}<br />
						<span class="smalltext">{$download[\'user\']}</span>
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'category\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'comments\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'views\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$lang->mydownloads_total_rate}: {$download[\'averagerating\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'downloads\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'status\']}
					</td>
					{$download[\'options\']}
				</tr>
	');

	mydownloads_add_template("mydownloads_mysubmissions_no_submissions", '
				<tr>
					<td class="trow1" colspan="9">
						{$lang->mydownloads_no_submissions}
					</td>
				</tr>
	');

	mydownloads_add_template("mydownloads_mysubmissions_options", '
				<td class="{$bgcolor}" align="center">
					<a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=managepreviews&amp;did={$download[\'did\']}">{$lang->mydownloads_previews}</a> - <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=edit_down&amp;did={$download[\'did\']}">{$lang->mydownloads_edit}</a> - <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=delete_down&amp;did={$download[\'did\']}">{$lang->mydownloads_delete}</a>
				</td>
	');

	mydownloads_add_template("mydownloads_mysubmissions_options_head", '
				<td class="tcat" width="20%" align="center">
					<strong>{$lang->mydownloads_options}</strong>
				</td>
	');

	mydownloads_add_template("mydownloads_history", '
<html>
		<head>
		<title>{$title}</title>
		{$headerinclude}
		</head>
		<body>
		{$header}
		<br />
		{$multipage}
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<tbody>
				<tr><td class="thead" colspan="7"><strong>{$lang->mydownloads_user_history}</strong></td></tr>
				<tr>
					<td class="tcat" width="10%" align="center">
						<strong>{$lang->mydownloads_download_preview}</strong>
					</td>
					<td class="tcat" width="20%" align="center">
						<strong>{$lang->mydownloads_download_name}</strong>
					</td>
					<td class="tcat" width="20%" align="center">
						<strong>{$lang->mydownloads_download_category}</strong>
					</td>
					<td class="tcat" width="10%" align="center">
						<strong>{$lang->mydownloads_download_comments}</strong>
					</td>
					<td class="tcat" width="10%" align="center">
						<strong>{$lang->mydownloads_download_views}</strong>
					</td>
					<td class="tcat" width="10%" align="center">
						<strong>{$lang->mydownloads_download_rate}</strong>
					</td>
					<td class="tcat" width="5%" align="center">
						<strong>{$lang->mydownloads_date}</strong>
					</td>
				</tr>
				{$download_items}
			</tbody>
		</table>
		{$multipage}
		{$footer}
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_history_download", '
				<tr>
					<td class="{$bgcolor}" align="center">
						<a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=view_down&amp;did={$download[\'did\']}"><img src="{$mybb->settings[\'bburl\']}/{$mybb->settings[\'mydownloads_previews_dir\']}/{$download[\'thumbnail\']}" style="max-width: {$mybb->settings[\'mydownloads_thumb_resolution_width\']}px; max-height: {$mybb->settings[\'mydownloads_thumb_resolution_height\']}px;"></a>
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'name\']}<br />
						<span class="smalltext">{$download[\'user\']}</span>
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'category\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'comments\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'views\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$lang->mydownloads_total_rate}: {$download[\'averagerating\']}
					</td>
					<td class="{$bgcolor}" align="center">
						{$download[\'date\']}
					</td>
				</tr>
	');

	mydownloads_add_template("mydownloads_history_no_downloads", '
				<tr>
					<td class="trow1" colspan="7">
						{$lang->mydownloads_no_downloads}
					</td>
				</tr>
	');

	mydownloads_add_template("mydownloads_delete_download", '
<html>
		<head>
		<title>{$title}</title>
		{$headerinclude}
		</head>
		<body>
		{$header}
		<br />
		<form action="{$mybb->settings[\'bburl\']}/mydownloads.php" method="post">
		<input type="hidden" name="postcode" value="{$mybb->post_code}" />
		<input type="hidden" name="action" value="delete_down" />
		<input type="hidden" name="did" value="{$did}" />
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<thead>
				<tr>
					<td class="thead" colspan="2" width="100%">
						<strong>{$lang->mydownloads_delete_download}</strong>
					</td>
				<tr>
			</thead>
			<tbody>
				<tr>
					<td width="100%" class="trow1">
						{$lang->mydownloads_delete_download_confirm}
					</td>
				</tr>
				<tr>
					<td width="100%" colspan="2" align="center" class="tfoot">
						<input type="submit" name="delete_download" class="button" value="{$lang->mydownloads_delete}" />
					</td>
				</tr>
			</tbody>
		</table>
		</form>
		{$footer}
		</body>
		</html>
	');

	mydownloads_add_template("mydownloads_filter_tags", '
		<br />
		<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
			<tbody>
				<tr>
					<td class="thead">
						<strong>{$lang->mydownloads_tags}</strong>
					</td>
				</tr>
				<tr>
					<td class="tcat">
						<strong>{$lang->mydownloads_filter_by_tags}</strong>
					</td>
				</tr>
				<tr>
					<td class="trow1">
						{$tags}
					</td>
				</tr>
			</tbody>
		</table><br /><div id="loading" style="display: none; width: 100%; margin: 0 auto; text-align: center; padding: 20px;"><img src="{$theme[\'imgdir\']}/spinner_big.gif" /></div>
	');

	mydownloads_add_template("mydownloads_filter_tags_tag", '
{$tag[\'tag\']}&nbsp;<input type="checkbox" name="tags[]" value="{$tag[\'tid\']}" {$checked} class="checkbox filter_tags" style="margin-right: 10px" />
	');

	mydownloads_add_template('mydownloads_postbit', '<br /><span class="smalltext"><a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=mysubmissions&amp;uid={$post[\'uid\']}">{$lang->mydownloads_view_submissions}</a> | <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=history&amp;uid={$post[\'uid\']}">{$lang->mydownloads_view_history}</a></span>');
	mydownloads_add_template('mydownloads_profile', '<tr>
	<td class="trow2"><strong>{$lang->mydownloads_my_submissions_profile}</strong></td>
	<td class="trow2">{$submissions_count} <span class="smalltext">(<a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=mysubmissions&amp;uid={$memprofile[\'uid\']}">{$lang->mydownloads_view_submissions}</a> | <a href="{$mybb->settings[\'bburl\']}/mydownloads.php?action=history&amp;uid={$memprofile[\'uid\']}">{$lang->mydownloads_view_history}</a>)</span></td>
</tr>');

	mydownloads_add_template('mydownloads_header_reports', '<div class="red_alert">{$lang->mydownloads_header_reports}</div>');

	//Change admin permissions
	change_admin_permission("mydownloads", false, 1);
	change_admin_permission("mydownloads", "downloads_categories", 1);
	change_admin_permission("mydownloads", "log", 1);
	change_admin_permission("mydownloads", "manage_submissions", 1);
	change_admin_permission("mydownloads", "paypal", 1);
	change_admin_permission("mydownloads", "reports", 1);

	// do edits
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit_author_user", '#'.preg_quote('{$post[\'warninglevel\']}').'#', '{mydownloads_submissions}'.'{$post[\'warninglevel\']}');
	find_replace_templatesets("member_profile", '#'.preg_quote('{$warning_level}').'#', '{$warning_level}'.'{$mydownloads_submissions}');
	find_replace_templatesets('header', '#'.preg_quote('{$mydownloads_reports}').'#', '', 0);
}

function mydownloads_deactivate()
{
	global $db, $mybb;

	// remove templates
	$db->delete_query('templates', 'title LIKE \'%mydownloads%\' AND sid=\'-1\'');

	// remove edits
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";

	find_replace_templatesets('index', '#'.preg_quote('{$mydownloads}').'#', '');
	find_replace_templatesets('portal', '#'.preg_quote('{$mydownloads}').'#', '');

	//Change admin permissions
	change_admin_permission("mydownloads", false, -1);
	change_admin_permission("mydownloads", "downloads_categories", -1);
	change_admin_permission("mydownloads", "log", -1);
	change_admin_permission("mydownloads", "manage_submissions", -1);
	change_admin_permission("mydownloads", "paypal", -1);
	change_admin_permission("mydownloads", "reports", -1);

	// do edits
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit_author_user", '#'.preg_quote('{mydownloads_submissions}').'#', '', 0);
	find_replace_templatesets("member_profile", '#'.preg_quote('{$mydownloads_submissions}').'#', '', 0);

	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('mydownloads_new_comment');
	}
}

function mydownloads_online(&$plugin_array)
{
	if (strpos('mydownloads.php', $plugin_array['user_activity']['location']) !== false)
	{
		global $lang;
		$lang->load("mydownloads");

		$plugin_array['location_name'] = "Viewing <a href=\"mydownloads.php\">".$lang->mydownloads."</a>";
	}

	return $plugin_array;

}

// This function was copied from functions_upload.php and was modified to match my needs
/**
 * Upload an attachment in to the file system
 *
 * @param array Attachment data (as fed by PHPs $_FILE)
 * @return array Array of attachment data if successful, otherwise array of error data
 */
function mydownloads_upload_attachment($attachment)
{
	global $db, $theme, $templates, $pid, $tid, $forum, $mybb, $lang, $plugins, $cache;

	$lang->load('mydownloads');

	if(isset($attachment['error']) && $attachment['error'] != 0)
	{
		$ret['error'] = $lang->mydownloads_error_uploadfailed.$lang->mydownloads_error_uploadfailed_detail;
		switch($attachment['error'])
		{
			case 1: // UPLOAD_ERR_INI_SIZE
				$ret['error'] .= $lang->mydownloads_error_uploadfailed_php1;
				break;
			case 2: // UPLOAD_ERR_FORM_SIZE
				$ret['error'] .= $lang->mydownloads_error_uploadfailed_php2;
				break;
			case 3: // UPLOAD_ERR_PARTIAL
				$ret['error'] .= $lang->mydownloads_error_uploadfailed_php3;
				break;
			case 4: // UPLOAD_ERR_NO_FILE
				$ret['error'] .= $lang->mydownloads_error_uploadfailed_php4;
				break;
			case 6: // UPLOAD_ERR_NO_TMP_DIR
				$ret['error'] .= $lang->mydownloads_error_uploadfailed_php6;
				break;
			case 7: // UPLOAD_ERR_CANT_WRITE
				$ret['error'] .= $lang->mydownloads_error_uploadfailed_php7;
				break;
			default:
				$ret['error'] .= $lang->sprintf($lang->mydownloads_error_uploadfailed_phpx, $attachment['error']);
				break;
		}
		return $ret;
	}

	if(!is_uploaded_file($attachment['tmp_name']) || empty($attachment['tmp_name']))
	{
		$ret['error'] = $lang->mydownloads_error_uploadfailed.$lang->mydownloads_error_uploadfailed_php4;
		return $ret;
	}

	$ext = get_extension($attachment['name']);
	// Check if we have a valid extension
	$query = $db->simple_select("attachtypes", "*", "extension='".$db->escape_string($ext)."'");
	$attachtype = $db->fetch_array($query);
	if(!$attachtype['atid'])
	{
		$ret['error'] = $lang->mydownloads_error_attachtype;
		return $ret;
	}

	// Check the size
	if($attachment['size'] > $attachtype['maxsize']*1024 && $attachtype['maxsize'] != "")
	{
		$ret['error'] = $lang->sprintf($lang->mydownloads_error_attachsize, $attachtype['maxsize']);
		return $ret;
	}

	// All seems to be good, lets move the attachment!
	$filename = "download_".$mybb->user['uid']."_".TIME_NOW."_".md5(uniqid(rand(), true)).".".$ext;

	require_once MYBB_ROOT.'inc/functions_upload.php';
	$file = upload_file($attachment, MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/", $filename);

	if($file['error'])
	{
		$ret['error'] = $lang->mydownloads_error_uploadfailed.$lang->mydownloads_error_uploadfailed_detail;
		switch(intval($file['error']))
		{
			case 1:
				$ret['error'] .= $lang->mydownloads_error_uploadfailed_nothingtomove;
				break;
			case 2:
				$ret['error'] .= $lang->mydownloads_error_uploadfailed_movefailed;
				break;
		}

		return $ret;
	}

	// Lets just double check that it exists
	if(!file_exists(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename))
	{
		$ret['error'] = $lang->mydownloads_error_uploadfailed.$lang->mydownloads_error_uploadfailed_detail.$lang->mydownloads_error_uploadfailed_lost;
		return $ret;
	}

	// Generate the array for the download
	$downloadarray = array(
		"pid" => intval($pid),
		"uid" => $mybb->user['uid'],
		"filename" => $filename,
		"filetype" => $file['type'],
		"filesize" => intval($file['size']),
		"orig_name" => $file['original_filename'],
		"downloads" => 0,
		"dateuploaded" => TIME_NOW
	);

	$downloadarray = $plugins->run_hooks("mydownloads_do_upload", $downloadarray);

	return $downloadarray;
}

/**
 * Somewhat like htmlspecialchars_uni but for JavaScript strings
 *
 * @param string: The string to be parsed
 * @return string: Javascript compatible string
 */
function mydownloads_jsspecialchars($str)
{
	// Converts & -> &amp; allowing Unicode
	// Parses out HTML comments as the XHTML validator doesn't seem to like them
	$string = preg_replace(array("#\<\!--.*?--\>#", "#&(?!\#[0-9]+;)#"), array('','&amp;'), $str);
	return strtr($string, array("\n" => '\n', "\r" => '\r', '\\' => '\\\\', '"' => '\x22', "'" => '\x27', '<' => '&lt;', '>' => '&gt;'));
}

// build the category breadcrumb navigation
//
// @param int the category id
function mydownloads_build_breadcrumb($cid)
{
	global $mybb, $lang, $db, $cache, $catcache;

	if (defined('IN_ADMINCP'))
		global $page;

	// get all categories
	if (!$catcache)
	{
		$query = $db->simple_select('mydownloads_categories', '*', '', array('order_by' => 'disporder', 'order_dir' => 'asc'));
		while($cats = $db->fetch_array($query))
		{
			$catcache[$cats['cid']] = $cats;
		}
	}

	if (!is_array($catcache))
		return false;

	foreach($catcache as $key => $category)
	{
		if ($category['cid'] == $cid)
		{
			if ($catcache[$category['parent']])
			{
				mydownloads_build_breadcrumb($category['parent']);
			}

			if (defined('IN_ADMINCP'))
				$page->add_breadcrumb_item(htmlspecialchars_uni($category['name']), 'index.php?module=mydownloads-categories&amp;action=browse_downloads&amp;cid='.intval($category['cid']));
			else
				add_breadcrumb(htmlspecialchars_uni($category['name']), 'mydownloads.php?action=browse_cat&amp;cid='.intval($category['cid']));
		}
	}

	return true;
}

// build the categories tree list
function mydownloads_build_tree(&$categories, $acp=false)
{
	global $mybb, $lang, $db, $cache, $catcache;

	if (defined('IN_ADMINCP'))
		global $page;

	// get all categories
	if(!isset($catcache))
	{
		$query = $db->simple_select('mydownloads_categories', '*', '', array('order_by' => 'disporder', 'order_dir' => 'asc'));
		while($cats = $db->fetch_array($query))
		{
			$catcache[$cats['cid']] = $cats;
		}
	}

	if (!is_array($catcache))
		return false;

	$list = array();

	function godeeper($cid, &$list)
	{
		global $catcache;

		// Now let's browse each individual category belonging to each main category
		foreach ($catcache as $cat)
		{
			if ($cat['parent'] != $cid) continue;

			$list[$cat['parent']][$cat['cid']] = $cat;

			godeeper($cat['cid'], $list);
		}
	}

	$top = array();

	// Get main categories
	foreach ($catcache as $cat)
	{
		if ($cat['parent'] == 0)
		{
			$list[$cat['parent']][$cat['cid']] = $cat;
			godeeper($cat['cid'], $list);
			$top[$cat['cid']] = $cat;
		}
	}

	// Build the hyphens
	function btree($cid)
	{
		global $catcache;

		$h = '';
		$p = $catcache[$cid]['parent'];
		while ($p > 0)
		{
			$h .= '-';

			$p = $catcache[$p]['parent'];
		}

		return (empty($h) ? '' : $h.' ');
	}

	function btree_frontend($cid)
	{
		global $catcache;

		$h = '';
		$p = $catcache[$cid]['parent'];
		while ($p > 0)
		{
			$h = $catcache[$p]['name'].' > '.$h;
			$p = $catcache[$p]['parent'];
		}

		return (empty($h) ? '' : $h.' ');
	}

	// Create the tree
	function ctree($cid, &$list, &$tree, $acp=false)
	{
		global $catcache;

		if($acp)
			$h = btree($cid);
		else
			$h = btree_frontend($cid);

		$tree[$cid] = $h.$catcache[$cid]['name'];

		if (!empty($list[$cid]))
		{
			foreach ($list[$cid] as $sub)
				ctree($sub['cid'], $list, $tree, $acp);
		}
	}

	$tree = &$categories;

	// Now create the actual tree view
	// Start from TOP to BOTTOM
	if (!empty($top))
	{
		foreach ($top as $topcat)
		{
			ctree($topcat['cid'], $list, $tree, $acp);
		}
	}
}

// get downloads inside a category (and its sub categories)
//
// @param int the category id
// @param int downloads of the parent category
function mydownloads_get_downloads($cid, $parent_downloads = 0)
{
	global $mybb, $lang, $db, $cache, $dl_catcache, $cat_downloads;

	if (!is_array($dl_catcache)) // a cache is necessary
		return 0;

	$downloads = 0;

	$downloads += $parent_downloads;

	// If no categories exist with this parent, don't do anything
	if(!is_array($dl_catcache[$cid]))
		return $downloads; // return downloads in this category, even though this category has no sub categories

	foreach($dl_catcache[$cid] as $parent)
	{
		foreach($parent as $category)
		{
			if(isset($dl_catcache[$category['cid']]))
				$downloads += intval(mydownloads_get_downloads($category['cid']));

			$downloads += intval($category['downloads']);
		}
	}

	return $downloads;
}

function mydownloads_admin_quick_access(&$sub_menu)
{
	global $lang, $db;

	if(!$db->table_exists('mydownloads_submissions'))
		return;

	$lang->load("mydownloads", false, true);

	$submissions = $db->fetch_field($db->simple_select("mydownloads_submissions", "COUNT(sid) as submissions"), "submissions");

	$sub_menu[] = array('id' => 'mydownloads', 'title' => $lang->sprintf($lang->mydownloads_index, $submissions), 'link' => 'index.php?module=mydownloads-manage_submissions');
}

function mydownloads_start()
{
	global $lang;

	$lang->load('mydownloads');
}

function mydownloads_json_error($error, $file=array(), $legacy=false)
{
	if($legacy)
	{
		error($error);
	}

	if(empty($file))
	{
		// There's not much we can do without the file data
		$filejson = new stdClass();
		$filejson->files[] = array(
			'error' => htmlspecialchars_uni($error),
		);
		echo json_encode($filejson);
		exit;
	}

	$filejson = new stdClass();
	$filejson->files[] = array(
		'name' => htmlspecialchars_uni($file['name']),
		'type' => htmlspecialchars_uni($file['type']),
		'size' => (int)htmlspecialchars_uni($file['size']),
		'error' => htmlspecialchars_uni($error),
	);

	if(isset($file['thumbnail']))
	{
		$filejson->files[0]['thumbnail'] = htmlspecialchars_uni($mybb->settings['bburl'].'/'.$mybb->settings['mydownloads_previews_dir'].'/'.$file['thumbnail']);
	}

	echo json_encode($filejson);
	exit;
}

/**
 * Cutom MyDownloads error page - copied from MyBB
 * Produce a friendly error message page
 *
 * @param string The error message to be shown
 * @param string The title of the message shown in the title of the page and the error table
 */
function mydownloads_error($error="", $title="", $breadcrumb="", $did="", $cid="", $name="")
{
	global $header, $footer, $theme, $headerinclude, $db, $templates, $lang, $mybb, $plugins;

	$error = $plugins->run_hooks("mydownloads_error", $error);
	if(!$error)
	{
		$error = $lang->unknown_error;
	}

	// AJAX error message?
	if($mybb->input['ajax'])
	{
		// Send our headers.
		@header("Content-type: text/html; charset={$lang->settings['charset']}");
		echo "<error>{$error}</error>\n";
		exit;
	}

	if(!$title)
	{
		$title = $mybb->settings['bbname'];
	}

	$timenow = my_date($mybb->settings['dateformat'], TIME_NOW) . " " . my_date($mybb->settings['timeformat'], TIME_NOW);
	reset_breadcrumb();
	//add_breadcrumb($breadcrumb);

	// build bread crumb
	mydownloads_build_breadcrumb($cid);
	// add breadcrumb
	add_breadcrumb(htmlspecialchars_uni($name), 'mydownloads.php?action=view_down&did='.intval($did));

	add_breadcrumb($breadcrumb);

	eval("\$errorpage = \"".$templates->get("error")."\";");
	output_page($errorpage);

	exit;
}

function mydownloads_approve_submission($submission, $cat, $sid=0)
{
	global $db, $mybb;

	if (!is_array($submission) || !is_array($cat))
		return false;

	// calculate MD5
	if(!empty($submission['download']))
	{
		$md5 = md5_file(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$submission['download']);
		if ($md5 === false)
			$md5 = '';
	}

	// insert new download into the database
	$insert_array = array(
		"name"				=> $db->escape_string($submission['name']),
		"cid"				=> intval($submission['cid']),
		"description"		=> $db->escape_string($submission['description']),
		"hidden"			=> 0, // it's been approved, no need for it to be hidden, right?
		"points"			=> floatval($submission['points']),
		"price"				=> floatval($submission['price']),
		"submitter" 		=> $db->escape_string($submission['submitter']),
		"submitter_uid" 	=> intval($submission['submitter_uid']),
		"license"			=> $db->escape_string($submission['license']),
		"version"			=> $db->escape_string($submission['version']),
		"banner"			=> $db->escape_string($submission['banner']),
		"date"				=> TIME_NOW,
		"url" 				=> $db->escape_string($submission['url']),
		"receiver_email"	=> $db->escape_string($submission['receiver_email']),
		"tags"				=> $db->escape_string($submission['tags']),
	);

	if (!empty($submission['download']))
	{
		$insert_array["download"] = $db->escape_string($submission['download']);
		$insert_array["filetype"] = $db->escape_string($submission['filetype']);
		$insert_array["md5"] = $db->escape_string($md5);
		$insert_array["filesize"] = intval($submission['filesize']);
	}

	if(!empty($submission['preview']))
	{
		if(!is_array($submission['preview']))
			$insert_array['preview'] = $db->escape_string(serialize(array($submission['preview'])));
		else
			$insert_array['preview'] = $db->escape_string(serialize($submission['preview']));

		$insert_array["thumbnail"] = $db->escape_string($submission['thumbnail']);
	}

	$id = true;

	if($submission['update_did'] > 0)
	{
		if (!empty($submission['old_download']))
			@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$submission['old_download']);

		// Get download
		$q = $db->simple_select('mydownloads_downloads', '*', 'did=\''.intval($submission['update_did']).'\'');
		$download = $db->fetch_array($q);
		if(!empty($download))
		{
			$insert_array['date'] = $download['date'];

			// We change the date if we have changed 'md5' or 'url'
			if(($insert_array['url'] != '' && $download['url'] == '') || ($insert_array['url'] == '' && $download['url'] != ''))
				$insert_array['date'] = TIME_NOW;
			elseif(($insert_array['md5'] != '' && ($download['md5'] == '' || ($download['md5'] != '' && $download['md5'] != $insert_array['md5'])))) // We only check this is we have something in the input because by default the input is empty because it's unchanged | if we got nothing (we assume it was either not changed or it's an URL)
				$insert_array['date'] = TIME_NOW;

			/*echo $insert_array['date']." = ".$download['date']." = ".TIME_NOW."<br />";
			echo (int)($insert_array['url'] == $download['url'])." => ".$insert_array['url']." = ".$download['url']."<br />";;
			echo (int)($insert_array['preview'] == $download['preview'])." => ".$insert_array['preview']." = ".$download['preview']."<br />";
			echo (int)($insert_array['md5'] == $download['md5'])." => ".$insert_array['md5']." = ".$download['md5']."<br />";;
			exit;*/

			$db->update_query("mydownloads_downloads", $insert_array, 'did=\''.intval($submission['update_did']).'\'');
		}
		else
			error();
	}
	else {
		// add a download to the category's stats
		$db->update_query('mydownloads_categories', array('downloads' => $cat['downloads']+1), 'cid='.$cat['cid']);

		$id = $db->insert_query("mydownloads_downloads", $insert_array);
	}

	global $plugins;
	$plugins->run_hooks('mydownloads_add_download', $insert_array);

	$sid = (int)$sid;
	if ($sid > 0)
	{
		// delete submission
		$db->delete_query('mydownloads_submissions', 'sid='.$sid, 1);
	}

	return $id;
}

function mydownloads_submit_download($submission)
{
	global $db, $mybb;

	if (!is_array($submission))
		return false;

	$insert_array = array(
		"name"				=> $db->escape_string($submission['name']),
		"cid"				=> $submission['cid'],
		"description"		=> $db->escape_string($submission['description']),
		"hidden"			=> 0,
		"points"			=> floatval($submission['points']),
		"price"				=> floatval($submission['price']),
		"submitter" 		=> $db->escape_string($submission['submitter']),
		"submitter_uid" 	=> intval($submission['submitter_uid']),
		"license"			=> $db->escape_string($submission['license']),
		"version"			=> $db->escape_string($submission['version']),
		"banner"			=> $db->escape_string($submission['banner']),
		"receiver_email"	=> $db->escape_string($submission['receiver_email']),
		"url" 				=> $db->escape_string($submission['url']),
		"tags" 				=> $db->escape_string($submission['tags']),
	);

	if (!empty($submission['download']))
	{
		$insert_array["download"] = $db->escape_string($submission['download']);
		$insert_array["filetype"] = $db->escape_string($submission['filetype']);
		$insert_array["filesize"] = intval($submission['filesize']);
	}

	if($insert_array['url'] != '')
	{
		// Erase above
		$insert_array['download'] = '';
		$insert_array['filetype'] = '';
		$insert_array['filesize'] = '';
	}

	if (!empty($submission['preview']))
	{
		if(!is_array($submission['preview']))
			$insert_array['preview'] = $db->escape_string(serialize(array($submission['preview'])));
		else
			$insert_array['preview'] = $db->escape_string(serialize($submission['preview']));

		$insert_array["thumbnail"] = $db->escape_string($submission['thumbnail']);
	}

	if ($submission['update_did'] > 0)
	{
		// Get download
		$q = $db->simple_select('mydownloads_downloads', '*', 'did=\''.intval($submission['update_did']).'\'');
		$download = $db->fetch_array($q);
		if(!empty($download))
		{
			$insert_array['update_did'] = (int)$submission['update_did'];
		}
		else
			error();
	}

	$db->insert_query("mydownloads_submissions", $insert_array);

	return true;
}

function mydownloads_check_permissions($groups_comma)
{
    global $mybb;

	if($groups_comma == 'all')
		return true;

    if ($groups_comma == '')
        return false;

    $groups = explode(",", $groups_comma);
    $add_groups = explode(",", $mybb->user['additionalgroups']);

    if (!in_array($mybb->user['usergroup'], $groups)) { // primary user group not allowed
        // check additional groups
        if ($add_groups) {
            if (count(array_intersect($add_groups, $groups)) == 0)
                return false;
            else
                return true;
        }
        else
            return false;
    }
    else
        return true;
}

function mydownloads_box()
{
	global $current_page, $mybb, $db, $lang, $templates, $mydownloads, $theme;

	$mydownloads = '';

	$page = str_replace(".php", "", $current_page);
	$number = (int)$mybb->settings['mydownloads_show_'.$page];

	if ($number == '') return;

	$lang->load('mydownloads');

	// Only the primary group is checked
	$mybb->user['usergroup'] = (int)$mybb->user['usergroup'];
	switch($db->type)
	{
		case "pgsql":
		case "sqlite":
			$sql_where = "(c.usergroups LIKE '%,'|| {$mybb->user['usergroup']}|| ',%') OR c.usergroups = 'all'";
		break;
		default:
			$sql_where = "(CONCAT(',',c.usergroups,',') LIKE '%,{$mybb->user['usergroup']},%') OR c.usergroups = 'all'";
	}

	$latestsubmissions = '';
	//$query = $db->simple_select('mydownloads_downloads', 'did,name,date,submitter_uid,submitter', '', array('order_by' => 'date', 'order_dir' => 'DESC', 'limit' => $number));
	$query = $db->query("
		SELECT d.did,d.name,d.date,d.submitter,d.submitter_uid,d.preview,d.thumbnail,d.points,d.price,c.name as catname,c.cid
		FROM `".TABLE_PREFIX."mydownloads_downloads` d
		LEFT JOIN `".TABLE_PREFIX."mydownloads_categories` c ON (d.cid=c.cid)
		WHERE d.hidden=0 AND c.hidden=0 AND ({$sql_where})
		ORDER BY d.date DESC
		LIMIT {$number}
	");
	$cell = 1;
	while ($dl = $db->fetch_array($query))
	{
		if($cell % 2 != 0) // Odd
		{
			$bgcolor = alt_trow();
			$latestsubmissions .= '<tr>';
		}

		$bgcolor = alt_trow();
		$dl['author'] = build_profile_link(htmlspecialchars_uni($dl['submitter']), $dl['submitter_uid']);
		$dl['date'] = my_date($mybb->settings['dateformat'], $dl['date'], '', false).", ".my_date($mybb->settings['timeformat'], $dl['date']);
		$dl['name'] = htmlspecialchars_uni($dl['name']);
		$dl['category'] = htmlspecialchars_uni($dl['catname']);

		/// Handle the thumbnail
		if($dl['preview'] != '')
		{
			$dl['preview'] = unserialize($dl['preview']);
			if(empty($dl['preview']))
			{
				$dl['preview'] = '';
			}
			else
			{
				// Take the first image as cover
				$dl['preview'] = $dl['preview'][0];
			}
		}

		if($dl['preview'] == '')
		{
			$dl['preview'] = 'nopreview.png';
		}

		// No thumbnail
		if($dl['thumbnail'] == '')
		{
			$dl['thumbnail'] = $dl['preview'];
		}

		// Prices
		$prices = '';
		$price = '';
		if($dl['points'] != 0 && $mybb->settings['mydownloads_bridge_newpoints'] == 1)
		{
			$prices .= newpoints_format_points($dl['points']);
		}
		if($dl['price'] != 0 && $mybb->settings['mydownloads_paypal_enabled'] == 1)
		{
			if($prices != '')
				$prices .= ' / ';

			$prices .= number_format($dl['price'], 2).' '.$mybb->settings['mydownloads_paypal_currency'];
		}
		if($prices != '')
			eval("\$price = \"".$templates->get('mydownloads_latest_submissions_row_price')."\";");

		eval("\$latestsubmissions .= \"".$templates->get('mydownloads_latest_submissions_row')."\";");

		if($cell % 2 == 0) // Even?
			$latestsubmissions .= '</tr>';

		$cell++;
	}

	if(($cell-1) % 2 != 0 && $latestsubmissions != '') // Odd
		$latestsubmissions .= '<td width="50%" class="'.$bgcolor.'">&nbsp;</td></tr>';

	if (empty($latestsubmissions))
	{
		eval("\$latestsubmissions = \"".$templates->get('mydownloads_latest_submissions_row_empty')."\";");
	}

	if($mybb->settings['mydownloads_latest_submissions'] == 1)
	{
		$lang->mydownloads_latest_submissions = '<a href="'.htmlspecialchars_uni($mybb->settings['bburl']).'/mydownloads.php?action=latest">'.$lang->mydownloads_latest_submissions.'</a>';
	}

	eval("\$mydownloads = \"".$templates->get('mydownloads_latest_submissions')."\";");
}

function mydownloads_build_download_link($name, $did)
{
	global $mybb;

	if (empty($name) || empty($did)) return;

	$did = (int)$did;
	$name = htmlspecialchars_uni($name);

	return '<a href="'.$mybb->settings['bburl'].'/mydownloads.php?action=view_down&amp;did='.$did.'" title="'.$name.'">'.$name.'</a>';
}

function mydownloads_build_category_link($name, $cid)
{
	global $mybb;

	if (empty($name) || empty($cid)) return;

	$cid = (int)$cid;
	$name = htmlspecialchars_uni($name);

	return '<a href="'.$mybb->settings['bburl'].'/mydownloads.php?action=browse_cat&amp;cid='.$cid.'" title="'.$name.'">'.$name.'</a>';
}

function mydownloads_get_download($did, $get='*')
{
	global $db, $mybb;

	if (empty($did)) return '';

	if (empty($get)) $get = '*';

	$q = $db->simple_select('mydownloads_downloads', $db->escape_string($get), 'did=\''.intval($did).'\'');
	$download = $db->fetch_array($q);

	return $download;
}

function mydownloads_get_category($cid, $get='*')
{
	global $db, $mybb;

	if (empty($cid)) return '';

	if (empty($get)) $get = '*';

	$q = $db->simple_select('mydownloads_categories', $db->escape_string($get), 'cid=\''.intval($cid).'\'');
	$category = $db->fetch_array($q);

	return $category;
}

function mydownloads_get_report($rid, $get='*')
{
	global $db, $mybb;

	if (empty($rid)) return '';

	if (empty($get)) $get = '*';

	$q = $db->simple_select('mydownloads_reports', $db->escape_string($get), 'rid=\''.intval($rid).'\'');
	$report = $db->fetch_array($q);

	return $report;
}

function mydownloads_verify_user($uid)
{
	global $db;

	$uid = (int)$uid;

	$q = $db->simple_select('users', 'username', 'uid=\''.$uid.'\'');
	$n = $db->fetch_field($q, 'username');

	if (empty($n)) return false;

	return array('uid' => $uid, 'username' => $n);
}

function mydownloads_profile()
{
	global $memprofile, $mydownloads_submissions, $mybb, $lang, $db, $templates;

	if ($mybb->settings['mydownloads_show_profile'] != 1) return;

	$lang->load("mydownloads");

	$q = $db->simple_select('mydownloads_downloads', 'did', 'submitter_uid=\''.intval($memprofile['uid']).'\' AND hidden=\'0\'');
	$submissions_count = $db->num_rows($q);

	$lang->mydownloads_view_submissions = $lang->sprintf($lang->mydownloads_view_submissions, htmlspecialchars_uni($memprofile['username']));

	eval("\$mydownloads_submissions = \"".$templates->get('mydownloads_profile')."\";");
}

function mydownloads_postbit(&$post)
{
	global $mybb, $lang, $db, $templates;

	if ($mybb->settings['mydownloads_show_postbit'] != 1)
	{
		$post['user_details'] = str_replace('{mydownloads_submissions}', '', $post['user_details']);
		return;
	}

	$lang->load("mydownloads");

	$q = $db->simple_select('mydownloads_downloads', 'did', 'submitter_uid=\''.intval($post['uid']).'\' AND hidden=\'0\'');
	$submissions_count = $db->num_rows($q);

	$backup = $lang->mydownloads_view_submissions;
	$lang->mydownloads_view_submissions = $lang->sprintf($lang->mydownloads_view_submissions, htmlspecialchars_uni($post['username']));

	eval("\$post['mydownloads_submissions'] = \"".$templates->get('mydownloads_postbit')."\";");

	$lang->mydownloads_view_submissions = $backup;

	$post['user_details'] = str_replace('{mydownloads_submissions}', $post['mydownloads_submissions'], $post['user_details']);

}

function mydownloads_update_username()
{
	global $mybb, $user, $db;

	if ($mybb->input['username'] == $user['username']) return;

	// changed username? Update it our download items too
	$db->update_query('mydownloads_downloads', array('submitter' => $db->escape_string($mybb->input['username'])), 'submitter_uid=\''.intval($user['uid']).'\'');
}

function mydownloads_header()
{
	global $mybb, $db, $lang, $templates, $theme, $mydownloads_reports;

	if($mybb->usergroup['cancp'])
	{
		$lang->load("mydownloads");

		$q = $db->simple_select('mydownloads_reports', 'COUNT(*) as reports', 'marked=0');
		$reports = (int)$db->fetch_field($q, 'reports');

		if($reports > 0)
		{
			$lang->mydownloads_header_reports = $lang->sprintf($lang->mydownloads_header_reports, $reports);
			eval("\$mydownloads_reports = \"".$templates->get("mydownloads_header_reports")."\";");
		}
		else
			$mydownloads_reports = '';
	}
	else
		$mydownloads_reports = '';
}

// Get MIME of url file
function mydownloads_getMime($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);

	// don't download content
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	if(curl_exec($ch)!==FALSE)
	{
		return curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	}
	else
	{
		return false;
	}
}

if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	/**
	 * Alert formatter for my custom alert type.
	 */
	class MyCustomAlertFormmatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
		/**
		 * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
		 *
		 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
		 *
		 * @return string The formatted alert string.
		 */
		public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
		{
			return $this->lang->sprintf(
				$this->lang->mydownloads_new_comment,
				$outputAlert['from_user'],
				$outputAlert['object_id'],
				$outputAlert['extra_details']['cid'],
				$outputAlert['dateline']
			);
		}

		/**
		 * Init function called before running formatAlert(). Used to load language files and initialize other required
		 * resources.
		 *
		 * @return void
		 */
		public function init()
		{
			if (!$this->lang->mydownloads_new_comment) {
				$this->lang->load('mydownloads');
			}
		}

		/**
		 * Build a link to an alert's content so that the system can redirect to it.
		 *
		 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
		 *
		 * @return string The built alert, preferably an absolute link.
		 */
		public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
		{
			global $mybb;
			$extra = $alert->getExtraDetails();
			return $mybb->settings['bburl'].'/mydownloads.php?action=view_down&did='.(int)$alert->getObjectId().'#cid'.(int)$extra['cid'];
		}
	}
}

function mydownloads_alerts_lang()
{
	global $lang;
	$lang->load("mydownloads");
}

function mydownloads_alerts_formatter()
{
	global $mybb, $lang;
	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$lang->load("mydownloads");

		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
			new MyCustomAlertFormmatter($mybb, $lang, 'mydownloads_new_comment')
		);
	}
}

// &#71;&#101;&#110;&#101;&#114;&#105;&#99;

?>
