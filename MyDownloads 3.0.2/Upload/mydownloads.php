<?php

/***************************************************************************
 *
 *   MyDownloads plugin (/mydownloads.php)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *
 *
 *   Adds a subscriptions system to MyBB.
 *
 ***************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'mydownloads.php');
define('IN_MYDOWNLOADS', 1);

// 1 - points are paid to the author of the download whenever someone buys a download
// 0 - this feature is disabled
define('PAY_AUTHOR', 1);
// groups that can still view MyDownloads if it's disabled
// group ids separated by a comma
define('VALID_GROUPS', '3,4,6'); // default = admins

$sandbox = ''; // set to .sandbox if you want to use sandbox

// Templates used by MyDownloads
$templatelist  = "mydownloads,mydownloads_categories_category,mydownloads_categories_category_no_cat,mydownloads_categories_table,mydownloads_delete_download,mydownloads_downloads_comment_comment,mydownloads_downloads_comment_comment_delete,mydownloads_downloads_comment_textarea,mydownloads_downloads_comment_textarea_login,mydownloads_downloads_download,mydownloads_downloads_download_button,mydownloads_downloads_download_button_url,mydownloads_downloads_download_license,mydownloads_downloads_download_md5,mydownloads_downloads_download_page,mydownloads_downloads_download_page_previews,mydownloads_downloads_download_page_previews_preview,mydownloads_downloads_download_version,mydownloads_downloads_no_download,mydownloads_downloads_rate,mydownloads_edit_download,mydownloads_email_row,mydownloads_head_downloads,mydownloads_latest_submissions,mydownloads_latest_submissions_row,mydownloads_latest_submissions_row_empty,mydownloads_manage_previews,mydownloads_manage_previews_nodata,mydownloads_manage_previews_preview,mydownloads_mysubmissions,mydownloads_mysubmissions_button,mydownloads_mysubmissions_no_submissions,mydownloads_mysubmissions_options,mydownloads_mysubmissions_options_head,mydownloads_mysubmissions_submission,mydownloads_points_column,mydownloads_points_column_head,mydownloads_points_row,mydownloads_postbit,mydownloads_price_column,mydownloads_price_column_head,mydownloads_price_row,mydownloads_profile,mydownloads_report_download,mydownloads_stats,mydownloads_stats_download,mydownloads_stats_nodata,mydownloads_submit_download,mydownloads_submit_download_button,mydownloads_submit_email,mydownloads_submit_points,mydownloads_submit_points_predefined,mydownloads_submit_price,mydownloads_submit_urls,mydownloads_sub_categories_table,mydownloads_title_categories,mydownloads_downloads_edit_button,mydownloads_latest_submissions_page,mydownloads_latest_submissions_row,mydownloads_latest_submissions_row_empty,mydownloads_categories_category_no_name,mydownloads_downloads_manage_previews,";
$templatelist .= "multipage_page_current,multipage_nextpage,multipage_page,multipage,multipage_prevpage,multipage_start,multipage_end"; // multi page templates

// This is workaround for a MyAlerts bug not accepting array input and its function myalerts_get_current_url() gets executed somwhere when the plugin loads and shows an error when a tag is selected in the filter boxes
if(isset($_GET['tags']))
{
	$tags_input = @$_GET['tags'];
	$_GET['tags'] = '';
}

require_once "./global.php";

if(isset($_GET['tags']))
	$mybb->input['tags'] = $tags_input; // revert back

if (!function_exists("mydownloads_check_permissions"))
	die("MyDownloads is not activated.");

require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// load language
$lang->load("mydownloads");

$license = '';
$comment = '';
$comments = '';
$banner = '';

if ($mybb->settings['mydownloads_is_active'] == 0 && !mydownloads_check_permissions(VALID_GROUPS)) // if MyDownloads is 'off', inform user about it
	error($lang->mydownloads_closed);

// check if NewPoints is installed
$plugins_cache = $cache->read("plugins");
if(isset($plugins_cache['active']['newpoints']) && $mybb->settings['mydownloads_bridge_newpoints'] == 1)
	$newpoints_installed = true;
else
	$newpoints_installed = false;

// Check if the PayPal feature is enabled
if ($mybb->settings['mydownloads_paypal_enabled'] == 1)
	$paypal_enabled = true;
else
	$paypal_enabled = false;

$plugins->run_hooks("mydownloads_start");

// Do not allow guests if Hide to Guests setting is set to Yes
if(!$mybb->user['uid'])
{
	if ($mybb->settings['mydownloads_hide_guests'])
		error_no_permission();

	if ($newpoints_installed) // set guests' money to 0
		$mybb->user['newpoints'] = 0;
}

// add breadcrumb
add_breadcrumb($lang->mydownloads, 'mydownloads.php');

/*** START OF PRETTY BAD CODE ***/
$colspan = 1;
if ($newpoints_installed && $paypal_enabled)
{
	$mp_start = '<tr>';
	$mp_end = '';

	$pp_start = '';
	$pp_end = '</tr>';

	$col_width = "35%";

	$row_span = 4;
	$row_colspan = 1;
}
elseif (($newpoints_installed && !$paypal_enabled) || (!$newpoints_installed && $paypal_enabled)) {
	if ($newpoints_installed && !$paypal_enabled) {
		$mp_start = '<tr>';
		$mp_end = '</tr>';
	}
	else {
		$pp_start = '<tr>';
		$pp_end = '</tr>';
	}
	$col_width = "70%";
	$row_colspan = 2;
	$row_span = 4;
}
elseif (!$newpoints_installed && !$paypal_enabled) {
	$pp_start = $pp_end = $mp_start = $mp_end = '';
	$row_colspan = 2;
	$row_span = 3;
	$col_width = "0%";
}
/*** END OF PRETTY BAD CODE ***/

$meta = array('content' => '', 'description' => '');

// build button for My Submissions
if($mybb->user['uid'] > 0)
{
	eval('$mysubmissions_button = "'.$templates->get('mydownloads_mysubmissions_button').'";');
}

$mybb->settings['mydownloads_thumb_resolution_width'] = htmlspecialchars_uni($mybb->settings['mydownloads_thumb_resolution_width']);
$mybb->settings['mydownloads_thumb_resolution_height'] = htmlspecialchars_uni($mybb->settings['mydownloads_thumb_resolution_height']);

$table_layout = 'auto';

// MyDownloads page
if (!$mybb->input['action'] || $mybb->get_input('action') == 'categories')
{
	$bgcolor = 'tcat';
	$category['name'] = $lang->mydownloads_select_category;
	eval('$download_items = "'.$templates->get('mydownloads_categories_category').'";');

	$category = array();

	$parser_options = array(
		'allow_mycode' => intval($mybb->settings['mydownloads_allow_mycode']),
		'allow_videocode' => intval($mybb->settings['mydownloads_allow_video']),
		'allow_smilies' => intval($mybb->settings['mydownloads_allow_smilies']),
		'allow_imgcode' => intval($mybb->settings['mydownloads_allow_img']),
		'allow_html' => intval($mybb->settings['mydownloads_allow_html']),
		'filter_badwords' => intval($mybb->settings['mydownloads_filter_bad_words'])
	);

	// cache categories
	$query = $db->simple_select('mydownloads_categories', '*', '', array('order_by' => 'disporder', 'order_dir' => 'asc'));
	while($cats = $db->fetch_array($query))
	{
		if ($cats['hidden'] == 1)
			continue;

		// are allowed to view the category
		if (!mydownloads_check_permissions($cats['usergroups']))
		{
			continue;
		}

		// cache categories so we can use them later
		$catcache[$cats['cid']] = $cats;
		$dl_catcache[$cats['parent']][$cats['disporder']][$cats['cid']] = $cats; // this cache variable is used for counting downloads
	}

	if ($catcache) {

		// another big array
		$ccache = $dl_catcache;

		// display categories list
		foreach($catcache as $category)
		{
			if ($category['parent'] != 0)
				continue; // if this is not a main category, skip to the next one

			$category['description'] = $parser->parse_message($category['description'], $parser_options);

			$bgcolor = alt_trow();

			$category['name'] = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$category['cid']}\">".htmlspecialchars_uni($category['name'])."</a> <small>(".$lang->sprintf($lang->mydownloads_downloads_number,mydownloads_get_downloads($category['cid'], $category['downloads'])).")</small>";

			// GET SUB CATEGORIES PART
			$prefix_name = "<strong>".$lang->mydownloads_sub_categories."</strong>".': ';
			$prefix = '';
			$sub_categories = '';

			if (!empty($ccache[$category['cid']]))
			{
				// build subcategories list
				foreach ($ccache[$category['cid']] as $cats)
				{
					ksort($cats, SORT_NUMERIC);
					foreach ($cats as $cat)
					{
						$sub_categories .= '<small>'.$prefix_name.$prefix.'<a href="'.$mybb->settings['bburl'].'/mydownloads.php?action=browse_cat&amp;cid='.$cat['cid'].'">'.htmlspecialchars_uni($cat['name']).'</a></small>';

						if (!$prefix)
							$prefix = ' | ';

						if ($prefix_name)
							$prefix_name = '';
					}
				}
			}

			$prefix = '';
			$prefix_name = '';

			if($category['background'] == '')
				eval('$download_items .= "'.$templates->get('mydownloads_categories_category').'";');
			else
			{
				$category['background'] = $mybb->settings['bburl'].'/'.$mybb->settings['mydownloads_previews_dir'].'/'.htmlspecialchars_uni($category['background']);
				eval('$download_items .= "'.$templates->get('mydownloads_categories_category_no_name').'";');
			}
		}

		unset($ccache);
	}

	unset($catcache);
	unset($dl_catcache);

	if (empty($category))
	{
		$category['name'] = $lang->mydownloads_no_categories;
		$bgcolor = alt_trow();
		eval('$download_items = "'.$templates->get('mydownloads_categories_category').'";');
	}

	$category = array();

	$category_name = $lang->mydownloads;

	// Stats
	$most_rated = '';
	$q = $db->simple_select('mydownloads_downloads', 'did,name,totalratings,numratings,thumbnail,preview', 'numratings > 0', array('order_by' => 'totalratings/numratings', 'order_dir' => 'desc', 'limit' => 5));
	while($download = $db->fetch_array($q))
	{
		$bgcolor = alt_trow();
		$download['name'] = htmlspecialchars_uni($download['name']);
		$download['stats'] = round($download['totalratings']/$download['numratings'], 2);

		if($download['preview'] != '')
		{
			$download['preview'] = unserialize($download['preview']);
			if(empty($download['preview']))
			{
				$download['preview'] = '';
			}
			else
			{

				// Take the first image as cover
				$download['preview'] = $download['preview'][0];
			}
		}

		if($download['preview'] == '')
		{
			$download['preview'] = 'nopreview.png';
		}

		// No thumbnail
		if($download['thumbnail'] == '')
		{
			$download['thumbnail'] = $download['preview'];
		}

		$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

		eval('$most_rated .= "'.$templates->get('mydownloads_stats_download').'";');
	}
	if($most_rated == '')
	{
		eval('$most_rated = "'.$templates->get('mydownloads_stats_nodata').'";');
	}

	$most_downloaded = '';
	$q = $db->simple_select('mydownloads_downloads', 'did,name,downloads,thumbnail,preview', 'downloads > 0', array('order_by' => 'downloads', 'order_dir' => 'desc', 'limit' => 5));
	while($download = $db->fetch_array($q))
	{
		$bgcolor = alt_trow();
		$download['name'] = htmlspecialchars_uni($download['name']);
		$download['stats'] = (int)$download['downloads'];

		if($download['preview'] != '')
		{
			$download['preview'] = unserialize($download['preview']);
			if(empty($download['preview']))
			{
				$download['preview'] = '';
			}
			else
			{

				// Take the first image as cover
				$download['preview'] = $download['preview'][0];
			}
		}

		if($download['preview'] == '')
		{
			$download['preview'] = 'nopreview.png';
		}

		// No thumbnail
		if($download['thumbnail'] == '')
		{
			$download['thumbnail'] = $download['preview'];
		}

		$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

		eval('$most_downloaded .= "'.$templates->get('mydownloads_stats_download').'";');
	}
	if($most_downloaded == '')
	{
		eval('$most_downloaded = "'.$templates->get('mydownloads_stats_nodata').'";');
	}

	$most_viewed = '';
	$q = $db->simple_select('mydownloads_downloads', 'did,name,views,thumbnail,preview', 'views > 0', array('order_by' => 'views', 'order_dir' => 'desc', 'limit' => 5));
	while($download = $db->fetch_array($q))
	{
		$bgcolor = alt_trow();
		$download['name'] = htmlspecialchars_uni($download['name']);
		$download['stats'] = (int)$download['views'];

		if($download['preview'] != '')
		{
			$download['preview'] = unserialize($download['preview']);
			if(empty($download['preview']))
			{
				$download['preview'] = '';
			}
			else
			{

				// Take the first image as cover
				$download['preview'] = $download['preview'][0];
			}
		}

		if($download['preview'] == '')
		{
			$download['preview'] = 'nopreview.png';
		}

		// No thumbnail
		if($download['thumbnail'] == '')
		{
			$download['thumbnail'] = $download['preview'];
		}

		$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

		eval('$most_viewed .= "'.$templates->get('mydownloads_stats_download').'";');
	}
	if($most_viewed == '')
	{
		eval('$most_viewed = "'.$templates->get('mydownloads_stats_nodata').'";');
	}

	$submit_download = '';

	// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit button
	if($mybb->user['uid'])
	{
		eval('$submit_download = "'.$templates->get('mydownloads_submit_download_button').'";');
	}

	eval('$stats = "'.$templates->get('mydownloads_stats').'";');
	eval('$mydownloads_title = "'.$templates->get('mydownloads_title_categories').'";');
}
elseif($mybb->get_input('action') == "browse_cat")
{
	// check if category exists
	$cid = intval($mybb->input['cid']);
	if($cid > 0)
	{
		if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
			error($lang->mydownloads_no_cid);

		// verify permissions first
		if ($cat['hidden'] == 1)
			error($lang->mydownloads_no_permissions);

		// are we allowed to view the category?
		if (!mydownloads_check_permissions($cat['usergroups']))
		{
			error($lang->mydownloads_no_permissions);
		}

		$submit_download = '';

		// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit button
		if ($mybb->user['uid'])
		{
			if (mydownloads_check_permissions($cat['submit_dl_usergroups']))
			{
				eval('$submit_download = "'.$templates->get('mydownloads_submit_download_button').'";');
			}
		}
		else
		{
			if ($mybb->user['uid'])
			{
				eval('$submit_download = "'.$templates->get('mydownloads_submit_download_button').'";');
			}
		}

		// set colspan of the main table to 2
		$colspan = 2;

		if($mybb->input['name'] == '')
		{
			$query = $db->simple_select('mydownloads_categories', '*', '', array('order_by' => 'disporder', 'order_dir' => 'asc'));
			while($cats = $db->fetch_array($query))
			{
				if ($cats['hidden'] == 1)
					continue;

				// are we allowed to view the category?
				if (!mydownloads_check_permissions($cats['usergroups']))
				{
					continue;
				}

				// cache categories so we can use the later
				$catcache[$cats['cid']] = $cats;
				$dl_catcache[$cats['parent']][$cats['disporder']][$cats['cid']] = $cats; // this cache variable is used for counting downloads
			}

			// another big variable
			$ccache = $dl_catcache;

			// build categories table
			foreach($catcache as $category)
			{
				if ($category['parent'] != $cid)
					continue;

				$category['description'] = $parser->parse_message($category['description'], $parser_options);

				$bgcolor = alt_trow();

				$category['name'] = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$category['cid']}\">".htmlspecialchars_uni($category['name'])."</a> <small>(".$lang->sprintf($lang->mydownloads_downloads_number,intval(mydownloads_get_downloads($category['cid'], $category['downloads']))).")</small>";

				// GET SUB CATEGORIES PART
				$prefix_name = "<strong>".$lang->mydownloads_sub_categories."</strong>".': ';
				$prefix = '';
				$sub_categories = '';


				if (!empty($ccache[$category['cid']]))
				{
					// build subcategories list
					foreach ($ccache[$category['cid']] as $cats)
					{
						ksort($cats, SORT_NUMERIC);
						foreach ($cats as $subcat)
						{
							$sub_categories .= '<small>'.$prefix_name.$prefix.'<a href="'.$mybb->settings['bburl'].'/mydownloads.php?action=browse_cat&amp;cid='.$subcat['cid'].'">'.htmlspecialchars_uni($subcat['name']).'</a></small>';

							if (!$prefix)
								$prefix = ' | ';

							if ($prefix_name)
								$prefix_name = '';
						}
					}
				}

				$prefix = '&nbsp;';
				$prefix_name = '&nbsp;';

				if($category['background'] == '')
					eval('$data2 .= "'.$templates->get('mydownloads_categories_category').'";');
				else
				{
					$category['background'] = $mybb->settings['bburl'].'/'.$mybb->settings['mydownloads_previews_dir'].'/'.htmlspecialchars_uni($category['background']);
					eval('$data2 .= "'.$templates->get('mydownloads_categories_category_no_name').'";');
				}

				$bgcolor = $bgcolor_bak;
			}

			// build bread crumb
			mydownloads_build_breadcrumb($cid);

			unset($catcache);
			unset($dl_catcache);
			unset($ccache);

			if ($data2 != '')
			{
				$lang->mydownloads_sub_categories_in_cat = $lang->sprintf($lang->mydownloads_sub_categories_in_cat, htmlspecialchars_uni($cat['name']));
				eval('$sub_categories_table = "'.$templates->get('mydownloads_sub_categories_table').'";');
			}
			else
				$sub_categories_table = '';
		}
	}
	else
	{
		// Get unviewable categories
		$unviewable = array();
		$q = $db->simple_select('mydownloads_categories', 'cid,hidden,usergroups');
		while($category = $db->fetch_array($q))
		{
			if ($category['hidden'] == 1)
				$unviewable[] = $category['cid'];
			else
			{
				// are we allowed to view the category?
				if (!mydownloads_check_permissions($category['usergroups']))
				{
					$unviewable[] = $category['cid'];
				}
			}
		}

		if(!empty($unviewable))
		{
			$unviewable = ' AND cid NOT IN ('.implode(',', $unviewable).')';
		}
		else
			$unviewable = '';
	}

	if($mybb->input['name'] != '')
	{
		// Breadcrumb for searching
		if($cid <= 0)
			add_breadcrumb($lang->mydownloads_search_results." ".htmlspecialchars_uni($mybb->input['name'])." ".$lang->mydownloads_in." ".$lang->mydownloads_all_categories, 'mydownloads.php?action=browse_cat&amp;cid=0&amp;name='.urlencode(htmlspecialchars_uni($mybb->input['name'])));
		else
			add_breadcrumb($lang->mydownloads_search_results." ".htmlspecialchars_uni($mybb->input['name'])." ".$lang->mydownloads_in." ".$cat['name'], 'mydownloads.php?action=browse_cat&amp;cid=0&amp;name='.urlencode(htmlspecialchars_uni($mybb->input['name'])));

		$filterurl .= '&amp;name='.htmlspecialchars_uni($mybb->input['name']);
	}

	$ratings = array();
	// Get our  ratings, will save us some queries below
	$query = $db->simple_select("mydownloads_ratings", "rating,did", "uid='{$mybb->user['uid']}'");
	while ($rated = $db->fetch_array($query))
	{
		$ratings[$rated['did']] = $rated['rating'];
	}

	// Sorting
	if(isset($mybb->input['sortbyname']))
	{
		$orderby = 'name';

		if($mybb->input['sortbyname'] == 'asc')
		{
			// asc
			$orderdir = 'asc';
			$changedir = 'desc';
			$sorturl = '&amp;sortbyname=asc';
			$sortusername = $lang->mydownloads_asc;
		}
		else
		{
			// desc
			$orderdir = 'desc';
			$changedir = 'asc';
			$sorturl = '&amp;sortbyname=desc';
			$sortusername = $lang->mydownloads_desc;
		}

		$lang->mydownloads_download_name = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyname=asc{$filterurl}\">{$lang->mydownloads_download_name}</a> <span class=\"smalltext\">[<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyname={$changedir}{$filterurl}\">{$sortusername}</a>]</span>";
		$lang->mydownloads_download_views = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyviews=asc{$filterurl}\">{$lang->mydownloads_download_views}</a>";
		$lang->mydownloads_download_rate = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyrating=asc{$filterurl}\">{$lang->mydownloads_download_rate}</a>";
		$lang->mydownloads_number_downloads = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbydownloads=asc{$filterurl}\">{$lang->mydownloads_number_downloads}</a>";
		$lang->mydownloads_download_points = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbypoints=asc{$filterurl}\">{$lang->mydownloads_download_points}</a>";
	}
	elseif(isset($mybb->input['sortbyviews']))
	{
		$orderby = 'views';

		if($mybb->input['sortbyviews'] == 'asc')
		{
			// asc
			$orderdir = 'asc';
			$changedir = 'desc';
			$sorturl = '&amp;sortbyviews=asc';
			$sortviews = $lang->mydownloads_asc;
		}
		else
		{
			// desc
			$orderdir = 'desc';
			$changedir = 'asc';
			$sorturl = '&amp;sortbyviews=desc';
			$sortviews = $lang->mydownloads_desc;
		}

		$lang->mydownloads_download_name = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyname=asc{$filterurl}\">{$lang->mydownloads_download_name}</a>";
		$lang->mydownloads_download_views = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyviews=asc{$filterurl}\">{$lang->mydownloads_download_views}</a> <span class=\"smalltext\">[<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyviews={$changedir}{$filterurl}\">{$sortviews}</a>]</span>";
		$lang->mydownloads_download_rate = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyrating=asc{$filterurl}\">{$lang->mydownloads_download_rate}</a>";
		$lang->mydownloads_number_downloads = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbydownloads=asc{$filterurl}\">{$lang->mydownloads_number_downloads}</a>";
		$lang->mydownloads_download_points = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbypoints=asc{$filterurl}\">{$lang->mydownloads_download_points}</a>";
	}
	elseif(isset($mybb->input['sortbyrating']))
	{
		$orderby = 'totalratings/numratings';

		if($mybb->input['sortbyrating'] == 'asc')
		{
			// asc
			$orderdir = 'asc';
			$changedir = 'desc';
			$sorturl = '&amp;sortbyrating=asc';
			$sortrating = $lang->mydownloads_asc;
		}
		else
		{
			// desc
			$orderdir = 'desc';
			$changedir = 'asc';
			$sorturl = '&amp;sortbyrating=desc';
			$sortrating = $lang->mydownloads_desc;
		}

		$lang->mydownloads_download_name = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyname=asc{$filterurl}\">{$lang->mydownloads_download_name}</a>";
		$lang->mydownloads_download_views = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyviews=asc{$filterurl}\">{$lang->mydownloads_download_views}</a>";
		$lang->mydownloads_download_rate = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyrating=asc{$filterurl}\">{$lang->mydownloads_download_rate}</a> <span class=\"smalltext\">[<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyrating={$changedir}{$filterurl}\">{$sortrating}</a>]</span>";
		$lang->mydownloads_number_downloads = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbydownloads=asc{$filterurl}\">{$lang->mydownloads_number_downloads}</a>";
		$lang->mydownloads_download_points = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbypoints=asc{$filterurl}\">{$lang->mydownloads_download_points}</a>";
	}
	elseif(isset($mybb->input['sortbydownloads']))
	{
		$orderby = 'downloads';

		if($mybb->input['sortbydownloads'] == 'asc')
		{
			// asc
			$orderdir = 'asc';
			$changedir = 'desc';
			$sorturl = '&amp;sortbydownloads=asc';
			$sortdownloads = $lang->mydownloads_asc;
		}
		else
		{
			// desc
			$orderdir = 'desc';
			$changedir = 'asc';
			$sorturl = '&amp;sortbydownloads=desc';
			$sortdownloads = $lang->mydownloads_desc;
		}

		$lang->mydownloads_download_name = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyname=asc{$filterurl}\">{$lang->mydownloads_download_name}</a>";
		$lang->mydownloads_download_views = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyviews=asc{$filterurl}\">{$lang->mydownloads_download_views}</a>";
		$lang->mydownloads_download_rate = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyrating=asc{$filterurl}\">{$lang->mydownloads_download_rate}</a>";
		$lang->mydownloads_download_points = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbypoints=asc{$filterurl}\">{$lang->mydownloads_download_points}</a>";
		$lang->mydownloads_number_downloads = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbydownloads=asc{$filterurl}\">{$lang->mydownloads_number_downloads}</a> <span class=\"smalltext\">[<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbydownloads={$changedir}{$filterurl}\">{$sortdownloads}</a>]</span>";
	}
	elseif(isset($mybb->input['sortbypoints']) && $newpoints_installed)
	{
		$orderby = 'points';

		if($mybb->input['sortbypoints'] == 'asc')
		{
			// asc
			$orderdir = 'asc';
			$changedir = 'desc';
			$sorturl = '&amp;sortbypoints=asc';
			$sortdownloads = $lang->mydownloads_asc;
		}
		else
		{
			// desc
			$orderdir = 'desc';
			$changedir = 'asc';
			$sorturl = '&amp;sortbypoints=desc';
			$sortdownloads = $lang->mydownloads_desc;
		}

		$lang->mydownloads_download_name = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyname=asc{$filterurl}\">{$lang->mydownloads_download_name}</a>";
		$lang->mydownloads_download_views = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyviews=asc{$filterurl}\">{$lang->mydownloads_download_views}</a>";
		$lang->mydownloads_download_rate = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyrating=asc{$filterurl}\">{$lang->mydownloads_download_rate}</a>";
		$lang->mydownloads_number_downloads = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbydownloads=asc{$filterurl}\">{$lang->mydownloads_number_downloads}</a>";
		$lang->mydownloads_download_points = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbypoints=asc{$filterurl}\">{$lang->mydownloads_download_points}</a> <span class=\"smalltext\">[<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbypoints={$changedir}{$filterurl}\">{$sortdownloads}</a>]</span>";
	}
	else
	{
		$orderby = 'date';
		$orderdir = 'desc';

		$lang->mydownloads_download_name = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyname=asc{$filterurl}\">{$lang->mydownloads_download_name}</a>";
		$lang->mydownloads_download_views = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyviews=asc{$filterurl}\">{$lang->mydownloads_download_views}</a>";
		$lang->mydownloads_download_rate = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbyrating=asc{$filterurl}\">{$lang->mydownloads_download_rate}</a>";
		$lang->mydownloads_number_downloads = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbydownloads=asc{$filterurl}\">{$lang->mydownloads_number_downloads}</a>";
		$lang->mydownloads_download_points = "<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=browse_cat&amp;cid={$cid}&amp;sortbypoints=asc{$filterurl}\">{$lang->mydownloads_download_points}</a>";
	}

	// Filter by Tags
	$filter_tags = '';
	$tags = '';
	$tagsurl = '';
	$tagsql = '';
	$q = $db->simple_select('mydownloads_tags', '*', 'categories=\'0\' OR CONCAT(\',\',categories,\',\') LIKE \'%,0,%\' OR CONCAT(\',\',categories,\',\') LIKE \'%,'.$cid.',%\'', array('order_by' => 'tag', 'order_dir' => 'asc'));
	while($tag = $db->fetch_array($q))
	{
		$tag['tid'] = (int)$tag['tid'];

		if(!empty($mybb->input['tags']) && is_array($mybb->input['tags']) && @in_array($tag['tid'], $mybb->input['tags']))
		{
			$checked = 'checked="checked"';

			$tagsurl .= '&amp;tags[]='.$tag['tid'];
			$tagssql .= ' AND CONCAT(\',\',tags,\',\') LIKE \'%,'.$tag['tid'].',%\'';
		}
		else
			$checked = '';

		$tag['tag'] = htmlspecialchars_uni($tag['tag']);
		eval('$tags .= "'.$templates->get('mydownloads_filter_tags_tag').'";');
	}

	if($tags != '')
	{
		eval('$filter_tags = "'.$templates->get('mydownloads_filter_tags').'";');
	}

	// pagination
	$per_page = intval($mybb->settings['mydownloads_downloads_page']);
	$mybb->input['page'] = intval($mybb->input['page']);
	if($mybb->input['page'] && $mybb->input['page'] > 1)
	{
		$mybb->input['page'] = intval($mybb->input['page']);
		$start = ($mybb->input['page']*$per_page)-$per_page;
	}
	else
	{
		$mybb->input['page'] = 1;
		$start = 0;
	}

	if ($mybb->settings['mydownloads_show_updated'] == 0)
		$hidden = ' AND hidden = 0';
	else
		$hidden = ' AND hidden != 1';

	// total rows
	if($cid > 0)
	{
		// Browsing/searching one category
		if($mybb->input['name'] != '')
			$total_rows = $db->fetch_field($db->simple_select("mydownloads_downloads", "COUNT(did) as downloads", 'cid='.$cid.$hidden.$unviewable.' AND name LIKE \'%'.$db->escape_string($mybb->input['name']).'%\''.$tagssql), "downloads");
		else
			$total_rows = $db->fetch_field($db->simple_select("mydownloads_downloads", "COUNT(did) as downloads", 'cid='.$cid.$hidden.$unviewable.$tagssql), "downloads");
	}
	else // We're searching all categories
	{
		if($mybb->input['name'] != '')
			$total_rows = $db->fetch_field($db->simple_select("mydownloads_downloads", "COUNT(did) as downloads", 'name LIKE \'%'.$db->escape_string($mybb->input['name']).'%\''.$hidden.$unviewable.$tagssql), "downloads");
		else
			$total_rows = $db->fetch_field($db->simple_select("mydownloads_downloads", "COUNT(did) as downloads", 'cid!=0'.$hidden.$unviewable.$tagssql), "downloads");
	}

	// multi-page
	if ($total_rows > $per_page)
	{
		$sorturl .= '&amp;name='.urlencode(htmlspecialchars_uni($mybb->input['name']));
		$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/mydownloads.php?action=browse_cat&amp;cid={$cid}".$sorturl.$tagsurl);
	}

	if($cid > 0)
	{
		// Browsing/searching one category
		if($mybb->input['name'] != '')
			$query = $db->simple_select('mydownloads_downloads', '*', 'cid='.$cid.' AND name LIKE \'%'.$db->escape_string($mybb->input['name']).'%\''.$hidden.$unviewable.$tagssql, array('limit' => "{$start}, {$per_page}", 'order_by' => $orderby, 'order_dir' => $orderdir));
		else
			$query = $db->simple_select('mydownloads_downloads', '*', 'cid='.$cid.$hidden.$unviewable.$tagssql, array('limit' => "{$start}, {$per_page}", 'order_by' => $orderby, 'order_dir' => $orderdir));
	}
	else
	{
		// Searching all categories
		if($mybb->input['name'] != '')
			$query = $db->simple_select('mydownloads_downloads', '*', 'cid!=0 AND name LIKE \'%'.$db->escape_string($mybb->input['name']).'%\''.$hidden.$unviewable.$tagssql, array('limit' => "{$start}, {$per_page}", 'order_by' => $orderby, 'order_dir' => $orderdir));
		else
			$query = $db->simple_select('mydownloads_downloads', '*', 'cid!=0'.$hidden.$unviewable.$tagssql, array('limit' => "{$start}, {$per_page}", 'order_by' => $orderby, 'order_dir' => $orderdir));
	}

	$download_items = '';
	while($download = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();

		if($download['preview'] != '')
		{
			$download['preview'] = unserialize($download['preview']);
			if(empty($download['preview']))
			{
				$download['preview'] = '';
			}
			else
			{

				// Take the first image as cover
				$download['preview'] = $download['preview'][0];
			}
		}

		if($download['preview'] == '')
		{
			$download['preview'] = 'nopreview.png';
		}

		// No thumbnail
		if($download['thumbnail'] == '')
		{
			$download['thumbnail'] = $download['preview'];
		}

		$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

		if ($download['date'] == 0)
			$time = $date = $lang->na;
		else
		{
			$date = my_date($mybb->settings['dateformat'], intval($download['date']), '', false);
			$time = my_date($mybb->settings['timeformat'], intval($download['date']));
		}
		$download['user'] = $lang->sprintf($lang->mydownloads_by_username, $mybb->settings['bburl'], get_profile_link((int)$download['submitter_uid'], htmlspecialchars_uni($download['submitter'])), htmlspecialchars_uni($download['submitter']), $time, $date);

		if ($newpoints_installed) {
			if (floatval($download['points']) > floatval($mybb->user['newpoints']))
				$download['points'] = "<span style=\"color: #FF0000\">".newpoints_format_points($download['points'])."</a></span>";
			else
				$download['points'] = newpoints_format_points($download['points']);

			eval('$points_column = "'.$templates->get('mydownloads_points_column').'";');
		}
		else
			$points_column = '';

		if ($paypal_enabled) {
			$download['price'] = $download['price']." ".$mybb->settings['mydownloads_paypal_currency'];
			eval('$price_column = "'.$templates->get('mydownloads_price_column').'";'); // eval price column if PayPal is enabled
		}
		else
			$price_column = '';

		$download['name'] = mydownloads_build_download_link($download['name'], $download['did']);

		if ($download['hidden'] == 2)
			$download['name'] .= " ".$lang->mydownloads_being_updated2;

		if($download['numratings'] <= 0)
		{
			$download['width'] = 0;
			$download['averagerating'] = $lang->mydownloads_na; // Premium: Show N/A when no rate has been given
			$download['numratings'] = $lang->mydownloads_na; // Premium: Show N/A when no rate has been given
		}
		else
		{
			$download['averagerating'] = floatval(round($download['totalratings']/$download['numratings'], 2));
			$download['width'] = intval(round($download['averagerating']))*20;
			$download['numratings'] = intval($download['numratings']);
		}

		//$myquery = $db->simple_select("mydownloads_ratings", "rating", "did='{$download['did']}' AND uid='{$mybb->user['uid']}'");
		//$rated = $db->fetch_field($myquery, 'rating');
		$rated = $ratings[$download['did']];
		$not_rated = '';
		if(!$rated)
		{
			$not_rated = ' star_rating_notrated';
		}

		$download['user_rate'] = floatval(round($rated, 2));
		if (!$download['user_rate'])
			$download['user_rate'] = $lang->mydownloads_na; // Premium: Show N/A when no rate has been given

		eval('$download[\'rate\'] = "'.$templates->get('mydownloads_downloads_rate').'";');
		eval('$download_items .= "'.$templates->get('mydownloads_downloads_download').'";');
	}

	if ($newpoints_installed && $paypal_enabled)
		$colspan = 8;
	elseif (($newpoints_installed && !$paypal_enabled) || (!$newpoints_installed && $paypal_enabled))
		$colspan = 7;
	elseif (!$newpoints_installed && !$paypal_enabled)
		$colspan = 6;

	if ($download_items == '')
	{
		$download['name'] = $lang->mydownloads_no_downloads;
		$bgcolor = alt_trow();

		eval('$download_items = "'.$templates->get('mydownloads_downloads_no_download').'";');
	}

	if ($newpoints_installed) {
		eval('$points_column_head = "'.$templates->get('mydownloads_points_column_head').'";');
	}
	else
		$points_column_head = '';

	if ($paypal_enabled) {
		eval('$price_column_head = "'.$templates->get('mydownloads_price_column_head').'";');
	}
	else
		$price_column_head = '';

	// Table title
	if($mybb->input['name'] != '')
	{
		if($cid <= 0)
			$category_name = $lang->mydownloads_search_results." ".htmlspecialchars_uni($mybb->input['name'])." ".$lang->mydownloads_in." ".$lang->mydownloads_all_categories;
		else
			$category_name = $lang->mydownloads_search_results." ".htmlspecialchars_uni($mybb->input['name'])." ".$lang->mydownloads_in." ".htmlspecialchars_uni($cat['name']);
	}
	else
		$category_name = htmlspecialchars_uni($cat['name']);

	if($mybb->settings['mydownloads_stats_all'] == 1)
	{
		$most_rated = '';
		if($cid > 0)
			$q = $db->simple_select('mydownloads_downloads', 'did,name,totalratings,numratings,preview,thumbnail', 'numratings > 0 AND cid='.$cid, array('order_by' => 'totalratings/numratings', 'order_dir' => 'desc', 'limit' => 5));
		else
			$q = $db->simple_select('mydownloads_downloads', 'did,name,totalratings,numratings,preview,thumbnail', 'numratings > 0', array('order_by' => 'totalratings/numratings', 'order_dir' => 'desc', 'limit' => 5));
		while($download = $db->fetch_array($q))
		{
			$bgcolor = alt_trow();
			$download['stats'] = round($download['totalratings']/$download['numratings'], 2);
			$download['name'] = htmlspecialchars_uni($download['name']);

			if($download['preview'] != '')
			{
				$download['preview'] = unserialize($download['preview']);
				if(empty($download['preview']))
				{
					$download['preview'] = '';
				}
				else
				{

					// Take the first image as cover
					$download['preview'] = $download['preview'][0];
				}
			}

			if($download['preview'] == '')
			{
				$download['preview'] = 'nopreview.png';
			}

			// No thumbnail
			if($download['thumbnail'] == '')
			{
				$download['thumbnail'] = $download['preview'];
			}

			$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

			eval('$most_rated .= "'.$templates->get('mydownloads_stats_download').'";');
		}
		if($most_rated == '')
		{
			eval('$most_rated = "'.$templates->get('mydownloads_stats_nodata').'";');
		}

		$most_downloaded = '';
		if($cid > 0)
			$q = $db->simple_select('mydownloads_downloads', 'did,name,downloads,preview,thumbnail', 'downloads > 0 AND cid='.$cid, array('order_by' => 'downloads', 'order_dir' => 'desc', 'limit' => 5));
		else
			$q = $db->simple_select('mydownloads_downloads', 'did,name,downloads,preview,thumbnail', 'downloads > 0', array('order_by' => 'downloads', 'order_dir' => 'desc', 'limit' => 5));
		while($download = $db->fetch_array($q))
		{
			$bgcolor = alt_trow();
			$download['stats'] = (int)$download['downloads'];
			$download['name'] = htmlspecialchars_uni($download['name']);

			if($download['preview'] != '')
			{
				$download['preview'] = unserialize($download['preview']);
				if(empty($download['preview']))
				{
					$download['preview'] = '';
				}
				else
				{

					// Take the first image as cover
					$download['preview'] = $download['preview'][0];
				}
			}

			if($download['preview'] == '')
			{
				$download['preview'] = 'nopreview.png';
			}

			// No thumbnail
			if($download['thumbnail'] == '')
			{
				$download['thumbnail'] = $download['preview'];
			}

			$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

			eval('$most_downloaded .= "'.$templates->get('mydownloads_stats_download').'";');
		}
		if($most_downloaded == '')
		{
			eval('$most_downloaded = "'.$templates->get('mydownloads_stats_nodata').'";');
		}

		$most_viewed = '';
		if($cid > 0)
			$q = $db->simple_select('mydownloads_downloads', 'did,name,views,thumbnail,preview', 'views > 0 AND cid='.$cid, array('order_by' => 'views', 'order_dir' => 'desc', 'limit' => 5));
		else
			$q = $db->simple_select('mydownloads_downloads', 'did,name,views,thumbnail,preview', 'views > 0', array('order_by' => 'views', 'order_dir' => 'desc', 'limit' => 5));
		while($download = $db->fetch_array($q))
		{
			$bgcolor = alt_trow();
			$download['stats'] = (int)$download['views'];

			$download['name'] = htmlspecialchars_uni($download['name']);

			if($download['preview'] != '')
			{
				$download['preview'] = unserialize($download['preview']);
				if(empty($download['preview']))
				{
					$download['preview'] = '';
				}
				else
				{

					// Take the first image as cover
					$download['preview'] = $download['preview'][0];
				}
			}

			if($download['preview'] == '')
			{
				$download['preview'] = 'nopreview.png';
			}

			// No thumbnail
			if($download['thumbnail'] == '')
			{
				$download['thumbnail'] = $download['preview'];
			}

			$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

			eval('$most_viewed .= "'.$templates->get('mydownloads_stats_download').'";');
		}
		if($most_viewed == '')
		{
			eval('$most_viewed = "'.$templates->get('mydownloads_stats_nodata').'";');
		}

		$lang->mydownloads_stats = $lang->sprintf($lang->mydownloads_stats_of, htmlspecialchars_uni($cat['name']));

		eval('$stats = "'.$templates->get('mydownloads_stats').'";');
	}

	eval('$mydownloads_head = "'.$templates->get('mydownloads_head_downloads').'";');
	eval('$mydownloads_title = "'.$templates->get('mydownloads_title_categories').'";');

	// set page title
	if($cid > 0)
	{
		$title = $lang->mydownloads .= ' - '.htmlspecialchars_uni($cat['name']);

		$meta['content'] = htmlspecialchars_uni(strip_tags($cat['description']));
	}
	else
		$title = $lang->mydownloads .= ' - '.$lang->mydownloads_search_results." ".htmlspecialchars_uni($mybb->input['name']);
}
elseif ($mybb->get_input('action') == "mysubmissions")
{
	if ($mybb->user['uid'] == 0) error_no_permission(); // we should not be here

	$uid = (isset($mybb->input['uid'])) ? (int)$mybb->input['uid'] : (int)$mybb->user['uid'];

	if ($uid != $mybb->user['uid'])
		$user = mydownloads_verify_user($uid);
	else
		eval('$options_head = "'.$templates->get('mydownloads_mysubmissions_options_head').'";');

	$query = $db->simple_select('mydownloads_categories', 'cid,hidden,name,usergroups', '', array('order_by' => 'disporder', 'order_dir' => 'asc'));
	while($cats = $db->fetch_array($query))
	{
		if ($cats['hidden'] == 1)
			continue;

		// are we allowed to view the category?
		if (!mydownloads_check_permissions($cats['usergroups']))
		{
			continue;
		}

		// cache categories so we can use the later
		$catcache[$cats['cid']] = $cats['name'];
	}

	$download_items = '';

	// pagination
	$per_page = intval($mybb->settings['mydownloads_downloads_page']);
	$mybb->input['page'] = intval($mybb->input['page']);
	if($mybb->input['page'] && $mybb->input['page'] > 1)
	{
		$mybb->input['page'] = intval($mybb->input['page']);
		$start = ($mybb->input['page']*$per_page)-$per_page;
	}
	else
	{
		$mybb->input['page'] = 1;
		$start = 0;
	}


	$numusers = $db->num_rows($query);

	// total comments
	$total_rows = $db->fetch_field($db->simple_select("mydownloads_downloads", "COUNT(did) as downloads", 'submitter_uid=\''.$uid.'\''), "downloads");

	// multi-page
	if ($total_rows > $per_page)
		$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/mydownloads.php?action=mysubmissions&amp;uid={$uid}");

	$query = $db->simple_select('mydownloads_downloads', '*', 'submitter_uid=\''.$uid.'\'', array('limit' => "{$start}, {$per_page}", 'order_by' => 'date', 'order_dir' => 'desc'));
	while($download = $db->fetch_array($query))
	{
		if($download['preview'] != '')
		{
			$download['preview'] = unserialize($download['preview']);
			if(empty($download['preview']))
			{
				$download['preview'] = '';
			}
			else
			{
				// Take the first image as cover
				$download['preview'] = $download['preview'][0];
			}
		}

		if($download['preview'] == '')
		{
			$download['preview'] = 'nopreview.png';
		}

		// No thumbnail
		if($download['thumbnail'] == '')
		{
			$download['thumbnail'] = $download['preview'];
		}

		$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

		if ($download['date'] == 0)
			$time = $date = $lang->na;
		else
		{
			$date = my_date($mybb->settings['dateformat'], intval($download['date']), '', false);
			$time = my_date($mybb->settings['timeformat'], intval($download['date']));
		}
		$download['user'] = $lang->sprintf($lang->mydownloads_by_username, $mybb->settings['bburl'], get_profile_link((int)$download['submitter_uid'], htmlspecialchars_uni($download['submitter'])), htmlspecialchars_uni($download['submitter']), $time, $date);

		$bgcolor = alt_trow();

		$download['category'] = mydownloads_build_category_link($catcache[$download['cid']], $download['cid']);

		$download['name'] = mydownloads_build_download_link($download['name'], $download['did']);

		if ($download['hidden'] == 0)
		{
			$download['status'] = $lang->mydownloads_active;
		}
		elseif ($download['hidden'] == 2)
		{
			$download['status'] = $lang->mydownloads_being_updated;
		}
		else {
			$download['status'] = $lang->mydownloads_hidden;
		}

		if ($uid == $mybb->user['uid'])
		{
			eval('$download[\'options\'] = "'.$templates->get('mydownloads_mysubmissions_options').'";');
		}
		else {
			$download['options'] = '';
		}

		// get rate and calculate average rate
		if($download['numratings'] <= 0)
		{
			$download['averagerating'] = $lang->mydownloads_na; // Premium: Show N/A when no rate has been given
		}
		else
		{
			$download['averagerating'] = floatval(round($download['totalratings']/$download['numratings'], 2));
		}

		eval('$download_items .= "'.$templates->get('mydownloads_mysubmissions_submission').'";');
	}

	unset($catcache);

	if (empty($download_items))
	{
		eval('$download_items = "'.$templates->get('mydownloads_mysubmissions_no_submissions').'";');
	}

	add_breadcrumb($lang->mydownloads_mysubmissions, 'mydownloads.php?action=mysubmissions');

	// set page title
	if ($uid != $mybb->user['uid'])
	{
		$title = $lang->mydownloads .= ' - '.$lang->sprintf($lang->mydownloads_user_mysubmissions, htmlspecialchars_uni($user['username']));
		$lang->mydownloads_my_submissions = $lang->sprintf($lang->mydownloads_user_submissions, htmlspecialchars_uni($user['username']));
	}
	else
		$title = $lang->mydownloads .= ' - '.$lang->mydownloads_mysubmissions;

	// get our downloads page
	eval("\$mydownloads = \"".$templates->get("mydownloads_mysubmissions")."\";");

	$plugins->run_hooks("mydownloads_mysubmissions_end");

	output_page($mydownloads);

	exit;
}
elseif ($mybb->get_input('action') == "history")
{
	if ($mybb->user['uid'] == 0) error_no_permission(); // we should not be here

	$uid = (isset($mybb->input['uid'])) ? (int)$mybb->input['uid'] : (int)$mybb->user['uid'];

	if ($uid != $mybb->user['uid'])
		$user = mydownloads_verify_user($uid);

	$query = $db->simple_select('mydownloads_categories', 'cid,hidden,name,usergroups', '', array('order_by' => 'disporder', 'order_dir' => 'asc'));
	while($cats = $db->fetch_array($query))
	{
		if ($cats['hidden'] == 1)
			continue;

		// are we allowed to view the category?
		if (!mydownloads_check_permissions($cats['usergroups']))
		{
			continue;
		}

		// cache categories so we can use the later
		$catcache[$cats['cid']] = $cats['name'];
	}

	$download_items = '';

	// pagination
	$per_page = intval($mybb->settings['mydownloads_downloads_page']);
	$mybb->input['page'] = intval($mybb->input['page']);
	if($mybb->input['page'] && $mybb->input['page'] > 1)
	{
		$mybb->input['page'] = intval($mybb->input['page']);
		$start = ($mybb->input['page']*$per_page)-$per_page;
	}
	else
	{
		$mybb->input['page'] = 1;
		$start = 0;
	}


	$numusers = $db->num_rows($query);

	// total downloads made
	$total_rows = $db->fetch_field($db->simple_select("mydownloads_log", "COUNT(lid) as downloads", 'uid=\''.$uid.'\' AND (type=1 OR type=5)'), "downloads");

	// multi-page
	if ($total_rows > $per_page)
		$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/mydownloads.php?action=history&amp;uid={$uid}");

	//$query = $db->simple_select('mydownloads_downloads', '*', 'submitter_uid=\''.$uid.'\'', array('limit' => "{$start}, {$per_page}", 'order_by' => 'date', 'order_dir' => 'desc'));
	$query = $db->query("
		SELECT l.*,d.*
		FROM `".TABLE_PREFIX."mydownloads_log` l
		LEFT JOIN `".TABLE_PREFIX."mydownloads_downloads` d ON (d.did=l.did)
		WHERE uid={$uid} AND  (type=1 OR type=5)
		ORDER BY l.date DESC
		LIMIT {$start},{$per_page}
	");
	while($download = $db->fetch_array($query))
	{
		if($download['preview'] != '')
		{
			$download['preview'] = unserialize($download['preview']);
			if(empty($download['preview']))
			{
				$download['preview'] = '';
			}
			else
			{
				// Take the first image as cover
				$download['preview'] = $download['preview'][0];
			}
		}

		if($download['preview'] == '')
		{
			$download['preview'] = 'nopreview.png';
		}

		// No thumbnail
		if($download['thumbnail'] == '')
		{
			$download['thumbnail'] = $download['preview'];
		}

		$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

		if ($download['date'] == 0)
			$time = $date = $lang->na;
		else
		{
			$date = my_date($mybb->settings['dateformat'], intval($download['date']), '', false);
			$time = my_date($mybb->settings['timeformat'], intval($download['date']));
		}
		$download['user'] = $lang->sprintf($lang->mydownloads_by_username, $mybb->settings['bburl'], get_profile_link((int)$download['submitter_uid'], htmlspecialchars_uni($download['submitter'])), htmlspecialchars_uni($download['submitter']), $time, $date);

		$bgcolor = alt_trow();

		$download['category'] = mydownloads_build_category_link($catcache[$download['cid']], $download['cid']);

		$download['name'] = mydownloads_build_download_link($download['name'], $download['did']);

		$download['date'] = my_date('relative', intval($download['date']));

		// get rate and calculate average rate
		if($download['numratings'] <= 0)
		{
			$download['averagerating'] = $lang->mydownloads_na; // Premium: Show N/A when no rate has been given
		}
		else
		{
			$download['averagerating'] = floatval(round($download['totalratings']/$download['numratings'], 2));
		}

		eval('$download_items .= "'.$templates->get('mydownloads_history_download').'";');
	}

	unset($catcache);

	if (empty($download_items))
	{
		eval('$download_items = "'.$templates->get('mydownloads_history_no_downloads').'";');
	}

	add_breadcrumb($lang->mydownloads_history, 'mydownloads.php?action=history');

	// set page title
	if ($uid != $mybb->user['uid'])
	{
		$title = $lang->mydownloads .= ' - '.$lang->sprintf($lang->mydownloads_user_history, htmlspecialchars_uni($user['username']));
		$lang->mydownloads_user_history = $lang->sprintf($lang->mydownloads_user_history, htmlspecialchars_uni($user['username']));
	}
	else
	{
		$title = $lang->mydownloads .= ' - '.$lang->sprintf($lang->mydownloads_user_history, htmlspecialchars_uni($mybb->user['username']));
		$lang->mydownloads_user_history = $lang->sprintf($lang->mydownloads_user_history, htmlspecialchars_uni($mybb->user['username']));
	}

	// get our downloads page
	eval("\$mydownloads = \"".$templates->get("mydownloads_history")."\";");

	$plugins->run_hooks("mydownloads_history_end");

	output_page($mydownloads);

	exit;
}
elseif ($mybb->get_input('action') == "edit_down")
{
	if (!$mybb->user['uid'] || $mybb->settings['mydownloads_can_edit'] != 1) // guests cannot submit downloads
		error_no_permission();

	// Let's check if we exceeded the post_max_size php ini directive
	if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
		error($lang->sprintf($lang->mydownloads_exceeded, ini_get('post_max_size')));
	}

	$did = intval($mybb->input['did']);
	if ($did <= 0) // download id's can't be smaller than 1
		error($lang->mydownloads_no_did);

	// get download from the database
	$download = mydownloads_get_download($did);
	if (empty($download))
		error($lang->mydownloads_no_did);

	if ($download['submitter_uid'] != $mybb->user['uid'])
		error_no_permission();

	// check if category exists
	$cid = intval($download['cid']);
	if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
		error($lang->mydownloads_no_cid);

	// check if category is hidden
	if ($cat['hidden'] == 1 || $download['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	if ($download['hidden'] == 2)
		error($lang->mydownloads_being_updated_error);

	// verify permissions first
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['usergroups']))
	{
		error($lang->mydownloads_no_permissions);
	}

	// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit download page
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['submit_dl_usergroups']))
	{
		error_no_permission();
	}

	if ($mybb->request_method == "post") // edit download
	{
		verify_post_check($mybb->input['postcode']);

		if (empty($mybb->input['name']))
		{
			error($lang->mydownloads_no_dl_name);
		}

		// Verify category
		if($download['cid'] != $mybb->input['cid'])
		{
			// check if category exists
			$cid = intval($mybb->input['cid']);
			if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
				error($lang->mydownloads_no_cid);

			// check if category is hidden
			if ($cat['hidden'] == 1)
				error($lang->mydownloads_no_permissions);

			// verify permissions first
			// are we allowed to view the category?
			if (!mydownloads_check_permissions($cat['usergroups']))
			{
				error($lang->mydownloads_no_permissions);
			}

			// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit download page
			// are we allowed to view the category?
			if (!mydownloads_check_permissions($cat['submit_dl_usergroups']))
			{
				error_no_permission();
			}
		}

		if (!$newpoints_installed) // NewPoints is not installed, don't allow users to set the number of points
			$mybb->input['points'] = 0;

		if (!$paypal_enabled)
		{
			$mybb->input['price'] = 0;
			$mybb->input['business'] = '';
		}
		else
		{
			// After 2.5 we allow donations so we don't need to check if we have a price > 0
			if($mybb->settings['mydownloads_allow_paypal_users'] == 1 && $mybb->input['business'] != '')
			{
				// Validate receiver email
				$mybb->input['business'] = trim_blank_chrs($mybb->input['business']);
				if(!preg_match("/^[a-zA-Z0-9&*+\-_.{}~^\?=\/]+@[a-zA-Z0-9-]+\.([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]{2,}$/si", $mybb->input['business']))
				{
					error($lang->mydownloads_invalid_receiver_email);
				}
			}
		}

		// Validate URLs
		if($mybb->settings['mydownloads_allow_urls'] == 1 && $mybb->input['url'] != '')
		{
			$urls = preg_split("/\r\n|\n|\r/", $mybb->input['url']);
			if(!empty($urls))
			{
				foreach($urls as $url)
				{
					if(!filter_var($url, FILTER_VALIDATE_URL))
						error($lang->mydownloads_invalid_url.htmlspecialchars_uni($url));
				}
			}
		}

		// Validate banner image
		$urlvalidate = mydownloads_getMime($mybb->input['banner']);
		$valid = 0;

		//Check for Valid Types
		$valid_types = array("image/bmp","image/x-windows-bmp","image/gif", "image/jpeg", "image/pjpeg", "image/png");
		foreach ($valid_types as $mime) {
			if (strpos($urlvalidate, $mime) !== FALSE) {
				$valid++;
			}
		}

		//No valid result found, error out
		if(isset($mybb->input['banner']) && $mybb->input['banner'] != '' && $valid == 0)
			error($lang->mydownloads_invalid_banner);

		$submission = array();

		$filename = basename($_FILES['download_file']['name']);

		// are we keeping the existing download file or not?
		if (!empty($filename) && ($mybb->settings['mydownloads_allow_urls'] == 0 || $mybb->input['url'] == ''))
		{
			// commented because we'll only delete it if our edit gets accepted
			//@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$download['download']); // delete old download file

			$download_file = mydownloads_upload_attachment($_FILES['download_file']);

			$submission['download'] = $download_file['filename'];
			$submission['filetype'] = $download_file['filetype'];
			$submission['filesize'] = $download_file['filesize'];

			if($download_file['error'])
				error($lang->mydownloads_upload_problem_downloadfile.$download_file['error']);
		}

		// Validate points
		if($mybb->settings['mydownloads_points_available'] != '' && $newpoints_installed)
		{
			$points_available = explode(',', $mybb->settings['mydownloads_points_available']);
			if(!empty($points_available))
			{
				if(!in_array($mybb->input['points'], $points_available))
					error($lang->mydownloads_invalid_points);
			}
		}

		// no need to escape things here because it's done in mydownloads_approve_submission() and mydownloads_submit_download()
		$submission['name'] = $mybb->input['name'];
		$submission['cid'] = $cid;
		$submission['description'] = $mybb->input['description'];
		$submission['points'] = $mybb->input['points'];
		$submission['submitter'] = $mybb->user['username'];
		$submission['submitter_uid'] = $mybb->user['uid'];
		$submission['license'] = $mybb->input['license'];
		$submission['version'] = $mybb->input['version'];
		$submission['price'] = $mybb->input['price'];
		$submission['banner'] = $mybb->input['banner'];
		if($mybb->settings['mydownloads_allow_urls'] == 1 && $mybb->input['url'] != '')
			$submission['url'] = $mybb->input['url'];
		else
			$submission['url'] = $download['url'];

		if($mybb->input['url'] == '')
			$submission['url'] = '';

		$submission['update_did'] = $download['did'];

		if($download['preview'] != '')
		{
			$submission['preview'] = unserialize($download['preview']); // this one cannot be changed here
			$submission['thumbnail'] = $download['thumbnail'];

			// Due to a bug introduced in early stages, we need to make sure the thumbnail field gets set to the proper value after editing a download
			if($submission['thumbnail'] == '')
			{
				// Update thumbnail too
				if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'].'/thumbnail_'.$submission['preview'][0]))
				{
					$submission['thumbnail'] = 'thumbnail_'.$db->escape_string($submission['preview'][0]);
				}
				else
				{
					$submission['thumbnail'] = $db->escape_string($submission['preview'][0]);
				}
			}
		}
		else
			$submission['preview'] = '';

		$submission['receiver_email'] = $mybb->input['business'];

		// Get possible tags
		if(!empty($mybb->input['tags']) && is_array($mybb->input['tags']))
		{
			$tags_array = array();
			$q = $db->simple_select('mydownloads_tags', '*', 'categories=\'0\' OR CONCAT(\',\',categories,\',\') LIKE \'%,0,%\' OR CONCAT(\',\',categories,\',\') LIKE \'%,'.$cid.',%\'', array('order_by' => 'tag', 'order_dir' => 'asc'));
			while($tag = $db->fetch_array($q))
			{
				// Check if it's in our input
				if(in_array($tag['tid'], $mybb->input['tags']))
					$tags_array[] = (int)$tag['tid'];
			}

			$submission['tags'] = implode(',', $tags_array);
		}

		if (mydownloads_check_permissions($mybb->settings['mydownloads_gid_auto_approval']))
		{
			if ($filename)
				$submission['old_download'] = $download['download'];

			mydownloads_approve_submission($submission, $cat);
			redirect($mybb->settings['bburl']."/mydownloads.php?action=mysubmissions&amp;uid=".intval($mybb->user['uid']), $lang->mydownloads_download_successfully_edited_auto, $lang->mydownloads_download_successfully_edited_title_auto);
		}
		else
		{
			mydownloads_submit_download($submission);
			// let's change its update status to one and hidden to one so it doesn't show up anywhere
			$db->update_query('mydownloads_downloads', array('hidden' => 2), 'did=\''.$did.'\'');

			redirect($mybb->settings['bburl']."/mydownloads.php?action=mysubmissions&amp;uid=".intval($mybb->user['uid']), $lang->mydownloads_download_successfully_edited, $lang->mydownloads_download_successfully_edited_title);
		}
	}

	// build bread crumb for categories
	mydownloads_build_breadcrumb($cid);

	$submit_points = $submit_price = '';

	if ($newpoints_installed)
	{
		$download['points'] = floatval($download['points']);

		if($mybb->settings['mydownloads_points_available'] != '')
		{
			// Get available options
			$points_available = explode(',', $mybb->settings['mydownloads_points_available']);
			$pointsoptions = '';
			if(!empty($points_available))
			{
				foreach($points_available as $points)
				{
					if($points == $download['points'])
						$pointsoptions .= '<option value="'.(float)$points.'" selected>'.newpoints_format_points($points).'</option>';
					else
						$pointsoptions .= '<option value="'.(float)$points.'">'.newpoints_format_points($points).'</option>';
				}
			}

			eval("\$submit_points = \"".$templates->get("mydownloads_submit_points_predefined")."\";");
		}
		else
			eval("\$submit_points = \"".$templates->get("mydownloads_submit_points")."\";");
	}

	if($paypal_enabled)
	{
		$download['price'] = floatval($download['price']);
		eval("\$submit_price = \"".$templates->get("mydownloads_submit_price")."\";");

		$download['receiver_email'] = htmlspecialchars_uni($download['receiver_email']);
		if($mybb->settings['mydownloads_allow_paypal_users'] == 1)
		{
			eval('$submit_email = "'.$templates->get('mydownloads_submit_email').'";');
		}
		else
			$submit_email = '';
	}

	if($mybb->settings['mydownloads_allow_urls'] == 1)
	{
		eval('$submit_url = "'.$templates->get('mydownloads_submit_urls').'";');
	}

	$download['version'] = htmlspecialchars_uni($download['version']);
	$download['name'] = htmlspecialchars_uni($download['name']);
	$download['md5'] = htmlspecialchars_uni($download['md5']);
	$download['licence'] = htmlspecialchars_uni($download['licence']);
	$download['banner'] = htmlspecialchars_uni($download['banner']);

	//** Categories Dropdown **//
	$catcache = array();
	$foundparents = array();


	switch($db->type)
	{
		case "pgsql":
		case "sqlite":
			$sql_where = "(usergroups LIKE '%,'|| {$mybb->user['usergroup']}|| ',%') OR usergroups = 'all'";
		break;
		default:
			$sql_where = "(CONCAT(',',usergroups,',') LIKE '%,{$mybb->user['usergroup']},%') OR usergroups = 'all'";
	}


	// fetch categories
	$cat_query = $db->simple_select('mydownloads_categories', 'usergroups,cid,name,disporder,parent', 'hidden=0 AND ('.$sql_where.')', array('order_by' => 'name', 'order_dir' => 'asc'));
	while($cat = $db->fetch_array($cat_query))
	{
		// We want to check your additional groups too
		if(!mydownloads_check_permissions($cat['usergroups']))
		{
			continue;
		}

		$catcache[$cat['cid']] = $cat;
	}

	$db->free_result($cat_query);

	$categories = array();

	// Build tree list
	$categories[0] = $lang->mydownloads_select_category;
	mydownloads_build_tree($categories);

	if(!empty($categories))
	{
		// Build the category list with dropdown
		$cat_select = '<select class="chosen-select"  name="cid" id="category">';
		foreach($categories as $cid => $c)
		{
			if($download['cid'] == $cid)
				$cat_select .= '<option value="'.(int)$cid.'" selected="selected">'.htmlspecialchars_uni($c).'</option>';
			else
				$cat_select .= '<option value="'.(int)$cid.'">'.htmlspecialchars_uni($c).'</option>';
		}
		$cat_select .= '</select>';
	}

	unset($catcache);

	// Tags
	// We want to load all tags, display them and hide the ones that are not for this category or not global
	// This way we can switch categories in the dropdown and update the visibility with javascript
	$submit_tags = '';
	$tags_array = array();
	//$q = $db->simple_select('mydownloads_tags', '*', 'categories=\'0\' OR CONCAT(\',\',categories,\',\') LIKE \'%,0,%\' OR CONCAT(\',\',categories,\',\') LIKE \'%,'.$cid.',%\'', array('order_by' => 'tag', 'order_dir' => 'asc'));
	$q = $db->simple_select('mydownloads_tags', '*', '', array('order_by' => 'tag', 'order_dir' => 'asc'));
	while($tag = $db->fetch_array($q))
	{
		$tags_array[] = $tag;
	}

	if($download['tags'] != '')
	{
		$download['tags'] = explode(',', $download['tags']);
	}
	else
		$download['tags'] = array();

	if(!empty($tags_array))
	{
		$tags = '';
		foreach($tags_array as $tag)
		{
			$hidden = '';
			if($tag['categories'] != '0')
			{
				$cats = explode(',', $tag['categories']);

				if(!in_array('0', $cats))
				{
					if($cid > 0 && !in_array($cid, $cats))
						$hidden = 'display: none';
					elseif($cid == 0)
						$hidden = 'display: none';
				}
				else
					$tag['categories'] = 0; // set this to 0 temporarily (makes the javascript easier)
			}

			if(in_array($tag['tid'], $download['tags']))
				$checked = 'checked="checked"';
			else
				$checked = '';

			// Replace commas by underscores as classes can't seem to use commas, otherwise JS will not act properly
			$tag['categories'] = str_replace(',', '_', htmlspecialchars_uni($tag['categories']));

			$tag['tag'] = htmlspecialchars_uni($tag['tag']);
			eval('$tags .= "'.$templates->get('mydownloads_submit_tags_tag').'";');
		}

		eval('$submit_tags = "'.$templates->get('mydownloads_submit_tags').'";');
	}

	$lang->mydownloads_editing_download = $lang->sprintf($lang->mydownloads_editing_download, htmlspecialchars_uni($download['name']));

	// add 'Editing Download XXXYYYZZZ' breadcrumb
	add_breadcrumb($lang->mydownloads_editing_download, 'mydownloads.php?action=edit_down&amp;cid='.$cid);

	$codebuttons = build_mycode_inserter("description");

	// set page title
	$title = $lang->mydownloads .= ' - '.$lang->mydownloads_editing_download;

	eval("\$edit_download_page = \"".$templates->get("mydownloads_edit_download")."\";");

	output_page($edit_download_page);

	exit;
}
elseif ($mybb->get_input('action') == 'delete_down')
{
	if (!$mybb->user['uid'] || $mybb->settings['mydownloads_can_delete'] != 1) // guests cannot submit downloads
		error_no_permission();

	$did = intval($mybb->input['did']);
	if ($did <= 0) // download id's can't be smaller than 1
		error($lang->mydownloads_no_did);

	// get download from the database
	$download = mydownloads_get_download($did);
	if (empty($download))
		error($lang->mydownloads_no_did);

	if ($download['submitter_uid'] != $mybb->user['uid'])
		error_no_permission();

	// check if category exists
	$cid = intval($download['cid']);
	if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
		error($lang->mydownloads_no_cid);

	// check if category is hidden
	if ($cat['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	if ($download['hidden'] == 2)
		error($lang->mydownloads_being_updated_error);

	// verify permissions first
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['usergroups']))
	{
		error($lang->mydownloads_no_permissions);
	}

	// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit download page
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['submit_dl_usergroups']))
	{
		error_no_permission();
	}

	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['postcode']);

		// If we have any previews, we must delete them and their thumbnails
		if($download['preview'] != '')
		{
			$download['preview'] = unserialize($download['preview']);
			if(!empty($download['preview']))
			{
				foreach($download['preview'] as $preview)
				{
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview);
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/thumbnail_".$preview);
				}
			}
		}

		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$download['download']);

		$plugins->run_hooks('mydownloads_remove_download', $download);

		$db->delete_query('mydownloads_downloads', "did = $did");

		$rids = $cids = array();

		// delete rates too
		$rquery = $db->simple_select('mydownloads_ratings', 'rid', "did = ".$did);
		while($rating = $db->fetch_array($rquery)) {
			$rids[] = $rating['rid'];
		}

		$db->delete_query('mydownloads_ratings', "rid IN ('".implode('\',\'', $rids)."')");

		// delete comments too
		$cquery = $db->simple_select('mydownloads_comments', 'cid', "did = ".$did);
		while($comment = $db->fetch_array($cquery)) {
			$cids[] = $comment['cid'];
		}

		$db->delete_query('mydownloads_comments', "cid IN ('".implode('\',\'', $cids)."')");

		// remove a download from the category's stats
		$db->update_query('mydownloads_categories', array('downloads' => $cat['downloads']-1), 'cid='.$cid, '', true);

		redirect($mybb->settings['bburl']."/mydownloads.php?action=mysubmissions", $lang->mydownloads_download_deleted, $lang->mydownloads_download_deleted_title);
	}

	add_breadcrumb($lang->mydownloads_delete_download_breadcrumb, 'mydownloads.php?action=delete_down&did='.$did);

	$lang->mydownloads_delete_download_confirm = $lang->sprintf($lang->mydownloads_delete_download_confirm, htmlspecialchars_uni($download['name']));

	// set page title
	$title = $lang->mydownloads .= ' - '.$lang->mydownloads_delete_download;

	eval("\$delete_page = \"".$templates->get("mydownloads_delete_download")."\";");

	output_page($delete_page);

	exit;
}
elseif ($mybb->get_input('action') == "view_down")
{
	$did = intval($mybb->input['did']);

	if ($did <= 0) // download id's can't be smaller than 1
		error($lang->mydownloads_no_did);

	// get download from the database
	$download = mydownloads_get_download($did);
	if (!$download)
		error($lang->mydownloads_no_did);

	// check if category exists, if category doesn't exist the download is not assigned to a category? weird
	$cid = intval($download['cid']);
	if ($cid <= 0 || (!($cat = mydownloads_get_category($cid, 'hidden,usergroups,name'))))
		error($lang->mydownloads_no_cid);

	// is the category hidden? don't continue if it is
	if ($cat['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	// verify permissions
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['usergroups']))
	{
		error($lang->mydownloads_no_permissions);
	}

	// build bread crumb
	mydownloads_build_breadcrumb($cid);

	// set colspan of the main table to 2
	$colspan = 2;

	if ($download['hidden'] == 1) // download is hidden
		error($lang->mydownloads_no_permissions);

	if ($download['hidden'] == 2 && $mybb->settings['mydownloads_show_updated'] == 0)
		error($lang->mydownloads_being_updated_error);

	// add breadcrumb
	add_breadcrumb(htmlspecialchars_uni($download['name']), 'mydownloads.php?action=view_down&did='.$download['did']);

	// if we are here, we have permission to be here

	$download['category'] = htmlspecialchars_uni($cat['name']);

	// get rate and calculate average rate
	if($download['numratings'] <= 0)
	{
		$download['width'] = 0;
		$download['averagerating'] = $lang->mydownloads_na; // Premium: Show N/A when no rate has been given
		$download['numratings'] = $lang->mydownloads_na; // Premium: Show N/A when no rate has been given
	}
	else
	{
		$download['averagerating'] = floatval(round($download['totalratings']/$download['numratings'], 2));
		$download['width'] = intval(round($download['averagerating']))*20;
		$download['numratings'] = intval($download['numratings']);
	}

	$rated = $db->fetch_field($db->simple_select("mydownloads_ratings", "rating", "did='{$download['did']}' AND uid='{$mybb->user['uid']}'"), 'rating');
	$not_rated = '';
	if(!$rated)
		$not_rated = ' star_rating_notrated';

	$download['user_rate'] = floatval(round($rated, 2));
	if (!$download['user_rate'])
			$download['user_rate'] = $lang->mydownloads_na; // Premium: Show N/A when no rate has been given

	eval('$download[\'rate\'] = "'.$templates->get('mydownloads_downloads_rate').'";'); // eval rate template

	$previews = $download['preview'];
	if($download['preview'] != '')
	{
		$download['preview'] = unserialize($download['preview']);
		if(empty($download['preview']))
		{
			$download['preview'] = '';
		}
		else
		{
			// Take the first image as cover
			$download['preview'] = $download['preview'][0];
		}
	}

	if($download['preview'] == '')
	{
		$download['preview'] = 'nopreview.png';
	}

	// No thumbnail
	if($download['thumbnail'] == '')
	{
		$download['thumbnail'] = $download['preview'];
	}

	$download['thumbnail'] = htmlspecialchars_uni($download['thumbnail']);

	$download['downloads'] = intval($download['downloads']);
	$lang->mydownloads_downloaded = $lang->sprintf($lang->mydownloads_downloaded, $download['downloads']);
	$lang->mydownloads_viewed = $lang->sprintf($lang->mydownloads_viewed, $download['views']);

	// Banner
	$hidebackgroud = '';
	if($download['banner'] != '')
	{
		$banner = 'background-image:url(\''.htmlspecialchars_uni($download['banner']).'\');';
		$hidebackgroud = 'style="background: none"';
	}

	global $usermoney;

	if ($newpoints_installed)
	{ // is NewPoints installed?

		$usermoney = newpoints_format_points($mybb->user['newpoints']);
		if($download['points'] == 0)
			$lang->mydownloads_download_image = $lang->mydownloads_download_free;
		else
			$lang->mydownloads_download_image = $lang->sprintf($lang->mydownloads_purchase_image, newpoints_format_points($download['points']));

		if($download['points'] == 0)
			$lang->mydownloads_download_url = $lang->mydownloads_download_free;
		else
			$lang->mydownloads_download_url = $lang->sprintf($lang->mydownloads_purchase_url, newpoints_format_points($download['points']));

		if($download['points'] == 0)
			$lang->mydownloads_purchase = $lang->mydownloads_download_free;
		else
			$lang->mydownloads_purchase = $lang->sprintf($lang->mydownloads_purchase, newpoints_format_points($download['points']));

		if (floatval($download['points']) > floatval($mybb->user['newpoints']))
			$download['points'] = "<span style=\"color: #FF0000\">".newpoints_format_points($download['points'])."</a></span>";
		else
			$download['points'] = newpoints_format_points($download['points']);

		eval('$points_row = "'.$templates->get('mydownloads_points_row').'";'); // eval points row if newpoints is installed
		$points_row = $mp_start.$points_row.$mp_end;
	}
	else {
		$usermoney = $lang->mydownloads_no_money;
		$lang->mydownloads_purchase = $lang->mydownloads_download;

		$points_row = '';
	}

	if($mybb->settings['mydownloads_allow_paypal_users'] == 1)
	{
		eval('$email_row = "'.$templates->get('mydownloads_email_row').'";');
	}
	else
		$email_row = '';

	if ($paypal_enabled)
	{
		$download['price'] = $download['price']." ".$mybb->settings['mydownloads_paypal_currency'];
		eval('$price_row = "'.$templates->get('mydownloads_price_row').'";'); // eval price row if PayPal is enabled
		$price_row = $pp_start.$price_row.$pp_end;
	}
	else
		$price_row = '';

	$download['realname'] = htmlspecialchars_uni($download['name']);

	$download['name'] = mydownloads_build_download_link($download['name'], $download['did']);

	// Tags
	if($download['tags'] != '')
	{
		$download['tags'] = explode(',', $download['tags']);
	}
	else
		$download['tags'] = array();

	$tags_array = array();
	$q = $db->simple_select('mydownloads_tags', '*', 'categories=\'0\' OR CONCAT(\',\',categories,\',\') LIKE \'%,0,%\' OR CONCAT(\',\',categories,\',\') LIKE \'%,'.$download['cid'].',%\'', array('order_by' => 'tag', 'order_dir' => 'asc'));
	while($tag = $db->fetch_array($q))
	{
		$tag['tag'] = htmlspecialchars_uni($tag['tag']);

		if(in_array($tag['tid'], $download['tags']))
			eval('$tags .= "'.$templates->get('mydownloads_tags_tag').'";');
	}

	if($tags != '')
	{
		$download['tags'] = $tags;
		eval('$tags = "'.$templates->get('mydownloads_tags_row').'";');
	}


	if ($download['hidden'] == 2)
		$download['update_notice'] = "<div class=\"red_alert\"><strong>".$lang->mydownloads_being_updated_notice."</strong></div>";
	else
		$download['update_notice'] = '';

	if (!$download['license'])
		$download['license'] = '';
	else {
		$license = nl2br(htmlspecialchars_uni($download['license']));
		eval('$license = "'.$templates->get('mydownloads_downloads_download_license').'";');
	}

	if (!$download['version'])
		$download['version'] = '';
	else {
		$download['version'] = htmlspecialchars_uni($download['version']);
		eval('$download[\'version\'] = "'.$templates->get('mydownloads_downloads_download_version').'";');
	}

	if (!$download['md5'])
		$download['md5'] = '';
	else {
		$download['md5'] = htmlspecialchars_uni($download['md5']);
		eval('$download[\'md5\'] = "'.$templates->get('mydownloads_downloads_download_md5').'";');
	}

	$parser_options = array(
		'allow_mycode' => intval($mybb->settings['mydownloads_allow_mycode']),
		'allow_smilies' => intval($mybb->settings['mydownloads_allow_smilies']),
		'allow_imgcode' => intval($mybb->settings['mydownloads_allow_img']),
		'allow_videocode' => intval($mybb->settings['mydownloads_allow_video']),
		'allow_html' => intval($mybb->settings['mydownloads_allow_html']),
		'filter_badwords' => intval($mybb->settings['mydownloads_filter_bad_words'])
	);

	$download['description'] = $parser->parse_message($download['description'], $parser_options);

	$bgcolor = alt_trow();

	if ($paypal_enabled && $download['price'] != 0)
	{
		$query = $db->simple_select('mydownloads_paypal_logs', 'item_name,downloaded', 'item_number=\''.$download['did'].'\' AND uid=\''.$mybb->user['uid'].'\'');
		$downloaded = 1;
		$bought = 0;

		while ($paypal_log = $db->fetch_array($query))
		{
			$bought = 1;
			if ($paypal_log['downloaded'] == 0)
				$downloaded = 0;
		}
		if ($bought == 0) {
			$downloaded = 0;
		}
	}

	if ($paypal_enabled && $download['price'] != 0 && (($bought == 0) || ($bought == 1 && $downloaded == 1 && $mybb->settings['mydownloads_paypal_pay_each'] == 1))) // if PayPal is enabled, price is not 0, hasn't bought the download OR has bought and has downloaded already and the setting 'pay for each download' is set to yes, display the buy button
	{
		if($mybb->settings['mydownloads_allow_paypal_users'] == 1 && $download['receiver_email'] != '')
			$business = htmlspecialchars_uni($download['receiver_email']);
		else
			$business = $mybb->settings['mydownloads_paypal_email'];

		$buy_button = "<form name=\"_xclick\" action=\"https://www".$sandbox.".paypal.com/cgi-bin/webscr\" method=\"post\" style=\"display: inline\">
<input type=\"hidden\" name=\"cmd\" value=\"_xclick\">
<input type=\"hidden\" name=\"business\" value=\"".$business."\">
<input type=\"hidden\" name=\"currency_code\" value=\"".$mybb->settings['mydownloads_paypal_currency']."\">
<input type=\"hidden\" name=\"item_name\" value=\"".$download['realname']."\">
<input type=\"hidden\" name=\"item_number\" value=\"{$download['did']}\" />
<input type=\"hidden\" name=\"amount\" value=\"".floatval($download['price'])."\">
<input type=\"hidden\" name=\"return\" value=\"".htmlspecialchars_uni($mybb->settings['bburl'])."/mydownloads.php?action=view_down&did=".$download['did']."\" />
<input type=\"hidden\" name=\"cbt\" value=\"Return to Merchant\" />
<input type=\"hidden\" name=\"no_shipping\" value=\"1\" />
<input type=\"hidden\" name=\"custom\" value=\"{$mybb->user['uid']}\" />
<input type=\"hidden\" name=\"notify_url\" value=\"".$mybb->settings['bburl']."/mydownloads_paypal.php\" />
<input type=\"hidden\" name=\"no_note\" value=\"1\" />
<input type=\"image\" src=\"https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png\" style=\"vertical-align: middle\" border=\"0\" name=\"submit\" alt=\"Make payments with PayPal - it's fast, free and secure!\">
</form>";

		if($newpoints_installed && $download['points'] > 0)
		{
			// Show Purchase with Points too
			eval('$download_button = "'.$templates->get('mydownloads_downloads_download_button').'";');
			$download_button .= $buy_button;
		}
		else
			$download_button = $buy_button;
	}
	else {
		if ($download['url'] != "")
			eval('$download_button = "'.$templates->get('mydownloads_downloads_download_button_url').'";');
		else
			eval('$download_button = "'.$templates->get('mydownloads_downloads_download_button').'";');
	}

	// Show donate button?
	$donatebutton = '';
	if($paypal_enabled === true && $download['receiver_email'] != '' && $download['price'] == 0)
	{
		$donatebutton = '<div style="width: 100%; text-align: center; margin: 0 auto"><form action="https://www'.$sandbox.'.paypal.com/cgi-bin/webscr" method="post" style="margin-top:10px;text-align:center;">
		<input type="hidden" name="cmd" value="_xclick">
		<input type="hidden" name="business" value="'.$download['receiver_email'].'" />
		<input type="hidden" name="custom" value="'.$mybb->user['username'].'" />
		<input type="hidden" name="item_name" value="Donation from '.$mybb->user['username'].'" />
		<input type="hidden" name="no_note" value="0" />
		<input type="hidden" name="currency_code" value="'.$mybb->settings['mydownloads_paypal_currency'].'" />
		<input name="return" value="'.$mybb->settings['bburl'].'" type="hidden" />
		<input name="cancel_return" value="'.$mybb->settings['bburl'].'" type="hidden" />
		<input type="hidden" name="tax" value="0" />
		<input type="image" src="https://www'.$sandbox.'.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" style="border:0;" name="submit" alt="PayPal - The safer, easier way to pay online!" />
		</form></div>';

	}

	$download['submitter_url'] = get_profile_link((int)$download['submitter_uid'], htmlspecialchars_uni($download['submitter']));

	if ($mybb->user['uid'] == $download['submitter_uid'] && $mybb->settings['mydownloads_can_edit'] == 1)
		eval('$edit_button = "'.$templates->get('mydownloads_downloads_edit_button').'";');
	else
		$edit_button = '';

	if ($mybb->user['uid'] == $download['submitter_uid'])
		eval('$manage_previews = "'.$templates->get('mydownloads_downloads_manage_previews').'";');
	else
		$manage_previews = '';

	$table_layout = 'fixed';

	// Last Updated
	$download['date'] = my_date($mybb->settings['dateformat'], $download['date']).", ".my_date($mybb->settings['timeformat'], $download['date']);

	eval('$download_items = "'.$templates->get('mydownloads_downloads_download_page').'";');

	// comments
	if (!$mybb->user['uid']) // guests can't comment
		eval('$comment = "'.$templates->get('mydownloads_downloads_comment_textarea_login').'";');
	else
	{
		$codebuttons = build_mycode_inserter("message");

		eval('$comment = "'.$templates->get('mydownloads_downloads_comment_textarea').'";');
	}

	// If we have any previes, show them before the comment form
	// Build previews list
	if($previews != '')
	{
		$prevbox = '';
		$prevs = '';
		$previews = unserialize($previews);
		if(count($previews) > 1)
		{
			foreach($previews as $k => $p)
			{
				$bgcolor = alt_trow();

				$preview = array();

				if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'].'/thumbnail_'.$p))
				{
					$preview['thumbnail'] = 'thumbnail_'.htmlspecialchars_uni($p);
				}
				else
				{
					$preview['thumbnail'] = htmlspecialchars_uni($p);
				}

				$preview['preview'] = htmlspecialchars_uni($p);

				eval("\$prevs .= \"".$templates->get("mydownloads_downloads_download_page_previews_preview")."\";");
			}

			eval("\$prevbox = \"".$templates->get("mydownloads_downloads_download_page_previews")."\";");

			$comment = $prevbox.$comment;
		}
	}

	$comments = '';

	// pagination :P
	$per_page = intval($mybb->settings['mydownloads_number_comments']);
	if($per_page > 0)
	{
		$mybb->input['page'] = intval($mybb->input['page']);
		if($mybb->input['page'] && $mybb->input['page'] > 1)
		{
			$mybb->input['page'] = intval($mybb->input['page']);
			$start = ($mybb->input['page']*$per_page)-$per_page;
		}
		else
		{
			$mybb->input['page'] = 1;
			$start = 0;
		}

		// total comments
		$total_rows = $db->fetch_field($db->simple_select("mydownloads_comments", "COUNT(cid) as comments", "did=".$did), "comments");

		// gold - multi page for comments
		// multi-page
		if ($total_rows > $per_page)
			$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/mydownloads.php?action=view_down&amp;did={$download['did']}");

		$parser_options = array(
			'allow_mycode' => intval($mybb->settings['mydownloads_allow_mycode2']),
			'allow_smilies' => intval($mybb->settings['mydownloads_allow_smilies2']),
			'allow_imgcode' => intval($mybb->settings['mydownloads_allow_img2']),
			'allow_html' => intval($mybb->settings['mydownloads_allow_html2']),
			'filter_badwords' => intval($mybb->settings['mydownloads_filter_bad_words2'])
		);

		// show comments
		$query = $db->query("
			SELECT u.*, u.username AS userusername, c.*
			FROM ".TABLE_PREFIX."mydownloads_comments c
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=c.uid)
			WHERE c.did=$did
			ORDER BY c.date DESC LIMIT {$start}, {$per_page}
		");

		if ($mybb->usergroup['canmodcp'] != 1)
			$delete_comment = $edit_comment = '';

		while($com = $db->fetch_array($query))
		{
			if ($com['userusername'])
				$com['username'] = $com['userusername'];
			$parser_options['me_username'] = $com['username'];
			$com['username'] = format_name(htmlspecialchars_uni($com['username']), $com['usergroup'], $com['displaygroup']);
			$com['comment'] = $parser->parse_message($com['comment'], $parser_options);

			$delete_comment = $edit_comment = '';
			if ($mybb->usergroup['canmodcp'] == 1) {
				$lang->mydownloads_delete_confirm = mydownloads_jsspecialchars($lang->mydownloads_delete_confirm);
				eval('$delete_comment = "'.$templates->get('mydownloads_downloads_comment_comment_delete').'";');

				eval('$edit_comment = "'.$templates->get('mydownloads_downloads_comment_comment_edit').'";');
			}
			elseif($com['uid'] == $download['submitter_uid'])
			{
				// Check edit time
				if($mybb->settings['mydownloads_time_edit'] == 0 || ($mybb->settings['mydownloads_time_edit'] != -1 && (TIME_NOW-$com['date'] < $mybb->settings['mydownloads_time_edit'])))
					eval('$edit_comment = "'.$templates->get('mydownloads_downloads_comment_comment_edit').'";');
			}

			$com['date'] = my_date($mybb->settings['dateformat'], $com['date']).", ".my_date($mybb->settings['timeformat'], $com['date']);

			$com['author_style'] = '';
			if($com['uid'] == $download['submitter_uid'])
			{
				$com['author_style'] = 'author_comment';
			}

			eval('$comments .= "'.$templates->get('mydownloads_downloads_comment_comment').'";');
		}
	}

	$db->update_query('mydownloads_downloads', array('views' => $download['views']+1), 'did='.intval($did));

	$category_name = $download['realname'];

	// set page title
	$title = $lang->mydownloads .= ' - '.htmlspecialchars_uni($download['realname']);
	$meta['content'] = htmlspecialchars_uni(strip_tags($download['description']));
	$meta['author'] = htmlspecialchars_uni($download['submitter']);
}
elseif ($mybb->get_input('action') == "do_download")
{
	verify_post_check($mybb->input['postcode']);

	if($mybb->request_method != 'post')
	{
		error_no_permission();
	}

	// Guest downloads disabled but we're a guest...!
	if($mybb->settings['mydownloads_guests_download'] == 0 && !$mybb->user['uid'])
	{
		error_no_permission();
	}

	$did = intval($mybb->input['did']);
	if ($did <= 0)
		error($lang->mydownloads_no_did);
	$dl = $db->fetch_array($db->simple_select('mydownloads_downloads', '*', 'did='.$did, array('limit' => 1)));


	// check if category exists
	$cid = intval($dl['cid']);
	if ($cid <= 0 || (!($cat = mydownloads_get_category($cid, 'hidden,usergroups,dl_usergroups'))))
		error($lang->mydownloads_no_cid);

	// category must not be hidden
	if ($cat['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	// make sure we can view the category - if we can't, then we shouldn't be trying to download this item..
	if ($cat['usergroups'] != 'all') {
		// are we allowed to view the category?
		if (!mydownloads_check_permissions($cat['usergroups']))
		{
			error($lang->mydownloads_no_permissions);
		}
	}

	// can our usergroup download files from this category?
	if ($cat['dl_usergroups'] != 'all') {
		if (!mydownloads_check_permissions($cat['dl_usergroups']))
		{
			error_no_permission();
		}
	}

	// the download must not be hidden
	if ($dl['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	if ($dl['hidden'] == 2 && $mybb->settings['mydownloads_show_updated'] == 0)
		error($lang->mydownloads_being_updated_error);

	// If we have PayPal enabled and it's a download with a price > 0
	// We want to check if we already purchased AND if we downlaoded or not
	if($paypal_enabled && $dl['price'] > 0)
	{
		$query = $db->simple_select('mydownloads_paypal_logs', 'item_name,downloaded', 'item_number=\''.$dl['did'].'\' AND uid=\''.$mybb->user['uid'].'\'');
		$downloaded = true; // var which handles if the user has downloaded already - default is 1 because it will be set to 0 if an entry with the 'downloaded' field whose value is 0 is found
		$paypal_bought = false; // if any entries are found, then this will be set to 1 - which means we have bought this item at least one time
		while ($paypal_log = $db->fetch_array($query))
		{
			$paypal_bought = true;
			if ($paypal_log['downloaded'] == 0)
				$downloaded = false;
		}

		// We bought it in the past but we downloaded all purchases AND pay each = 1
		if($paypal_bought == true && $downloaded == true && $mybb->settings['mydownloads_paypal_pay_each'] == 1)
		{
			$paypal_bought = false;
		}
	}

	// if NewPoints is installed and the price of the download (in points) is greater than our amount of points, error out
	if($newpoints_installed && (float)$dl['points'] > 0)
	{
		if ((float)$dl['points'] > (float)$mybb->user['newpoints'] && $paypal_bought == 0)
		{
			// Not enough points and we haven't paid with PayPal
			error($lang->mydownloads_not_enough_money);
		}

		// Did we buy already?
		$newpoints_bought = false;
		if($mybb->settings['mydownloads_newpoints_pay_each'] == 0)
		{
			$q = $db->simple_select('mydownloads_log', '*', 'uid='.(int)$mybb->user['uid'].' AND did='.$dl['did'], array('limit' => 1));
			if(!$db->fetch_array($q))
				$newpoints_bought = false;
			else
				$newpoints_bought = true;
		}

		if(!$newpoints_bought)
		{
			$db->update_query('users', array('newpoints' => $mybb->user['newpoints']-floatval($dl['points'])), 'uid='.intval($mybb->user['uid']));

			if (PAY_AUTHOR == 1)
			{
				$submitter_money = $db->fetch_field($db->simple_select("users", 'newpoints', "uid=".intval($dl['submitter_uid'])), 'newpoints');
				$db->update_query('users', array('newpoints' => $submitter_money+floatval($dl['points'])*floatval($mybb->settings['mydownloads_newpoints_percentage']/100)), 'uid=\''.intval($dl['submitter_uid']).'\'');
			}

			$newpoints_bought = true;
		}
	}

	// Download not bought
	if ($paypal_bought === false && $newpoints_bought === false)
	{
		error_no_permission();
	}

	// if PayPal is enabled, make sure we update the row in the logs table and set the downloaded field to 1
	if($paypal_enabled)
	{
		$db->update_query('mydownloads_paypal_logs', array('downloaded' => 1), 'item_number='.intval($did).' AND downloaded != 1 AND uid=\''.$mybb->user['uid'].'\'', 1, true);
	}

	// update downloads counter
	$db->update_query('mydownloads_downloads', array('downloads' => $dl['downloads']+1), 'did='.intval($did), '', true);

	if ($dl['price'] > 0 || $dl['points'] > 0)
		$type = 1;
	else
		$type = 5;

	// log download
	$insert_array = array(
		'uid' => intval($mybb->user['uid']),
		'did' => $did,
		'username' => $db->escape_string($mybb->user['username']),
		'date' => TIME_NOW,
		'type' => $type
	);
	$db->insert_query('mydownloads_log', $insert_array);

	// in case there's a download url
	// show a page with the links
	if ($dl['url'] != "")
	{
		$parser_options = array(
			'allow_mycode' => 1,
			'allow_smilies' => 0,
			'allow_imgcode' => 0,
			'allow_html' => 0,
			'filter_badwords' => 0
		);

		mydownloads_error($lang->sprintf($lang->mydownloads_url_download, $parser->parse_message("[code]".$dl['url']."[/code]", $parser_options)), $lang->mydownloads_url_download_title, $lang->mydownloads_url_download_title, $dl['did'], $dl['cid'], $dl['name']);
	}

	// output download
	$ext = get_extension($dl['download']);

	switch($dl['filetype'])
	{
		case "application/pdf":
		case "image/bmp":
		case "image/gif":
		case "image/jpeg":
		case "image/pjpeg":
		case "image/png":
		case "text/plain":
			/*header("Content-type: {$dl['filetype']}");
			$disposition = "inline";*/
			//header("Content-type: application/force-download"); // commented because this breaks on devices that can't save to disk
			header("Content-type: {$dl['filetype']}");
			$disposition = "attachment";
			break;

		default:
			header("Content-type: application/force-download");
			$disposition = "attachment";
	}

	if ($mybb->settings['mydownloads_characters_limit'] > 0)
	{
		// As we are using the name stored in the database, it can contain illegal characters like \/:*?"<>| so let's remove them :D
		// We must do this before escaping using htmlspecialchars_uni() because otherwise it will convert < into &lt; > into &gt; etc, I prefer to "remove" (We replaced them with an underscore actually) these characters instead.
		$illegal_characters = array("\\", "/", ":", "*", "?", "\"", "<", ">", "|");
		$dl['name'] = str_replace($illegal_characters, "_", $dl['name']);

		// Limit file names to $mybb->settings['mydownloads_characters_limit'] characters
		$dl['name'] = substr_replace($dl['name'], '', intval($mybb->settings['mydownloads_characters_limit']));

		// we've got a name for the download
		$dl['name'] = htmlspecialchars_uni($dl['name']).".".$ext;
	}
	else
	{
		$illegal_characters = array("\\", "/", ":", "*", "?", "\"", "<", ">", "|");
		$dl['name'] = str_replace($illegal_characters, "_", $dl['name']);

		// we've got a name for the download
		$dl['name'] = htmlspecialchars_uni($dl['name']);

		$dl['name'] = $dl['did'].'_'.$dl['name'];
		if($dl['version'] != '')
			$dl['name'] .= '_'.htmlspecialchars_uni($dl['version']);

		$dl['name'] .= '.'.$ext;

		//$dl['name'] = "downloaded_file_".$mybb->user['uid']."_".md5(uniqid(rand(), true)).".".$ext;
	}

	if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), "msie") !== false)
	{
		header("Content-disposition: attachment; filename=\"{$dl['name']}\"");
	}
	else
	{
		header("Content-disposition: {$disposition}; filename=\"{$dl['name']}\"");
	}

	if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), "msie 6.0") !== false)
	{
		header("Expires: -1");
	}

	header("Content-length: {$dl['filesize']}");
	header("Content-range: bytes=0-".($dl['filesize']-1)."/".$dl['filesize']);

	echo file_get_contents(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$dl['download']);

	exit;
}
elseif ($mybb->get_input('action') == "submit_download")
{
	if (!$mybb->user['uid']) // guests cannot submit downloads
		error_no_permission();

	// Let's check if we exceeded the post_max_size php ini directive
	if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
		error($lang->sprintf($lang->mydownloads_exceeded, ini_get('post_max_size')));
	}

	// check if category exists
	if($mybb->input['cid'] > 0 || $mybb->request_method == "post")
	{
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
			error($lang->mydownloads_no_cid);

		// check if category is hidden
		if ($cat['hidden'] == 1)
			error($lang->mydownloads_no_permissions);

		// verify permissions first
		// are we allowed to view the category?
		if (!mydownloads_check_permissions($cat['usergroups']))
		{
			error($lang->mydownloads_no_permissions);
		}

		// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit download page
		// are we allowed to view the category?
		if (!mydownloads_check_permissions($cat['submit_dl_usergroups']))
		{
			error_no_permission();
		}
	}

	if ($mybb->request_method == "post") // add download
	{
		if (($mybb->request_method != 'post' && !$mybb->input['postcode']) || !verify_post_check($mybb->input['postcode'], true))
		{
			error_no_permission(); // post code must be valid :)
		}

		if($mybb->input['cid'] <= 0)
			error();

		if (empty($mybb->input['name']))
		{
			error($lang->mydownloads_no_dl_name);
		}

		$mybb->input['hidden'] = 0; // download is not hidden

		if(!$newpoints_installed) // NewPoints is not installed, don't allow users to set the number of points
			$mybb->input['points'] = 0;
		elseif($mybb->settings['mydownloads_points_available'] != '')
		{
			// Validate points
			$points_available = explode(',', $mybb->settings['mydownloads_points_available']);
			if(!empty($points_available) && $newpoints_installed)
			{
				if(!in_array($mybb->input['points'], $points_available))
					error($lang->mydownloads_invalid_points);
			}
		}

		if(!$paypal_enabled)
		{
			$mybb->input['price'] = 0;
			$mybb->input['business'] = '';

			// After 2.5 we allow donations, we don't need to check if price > 0
			if($mybb->settings['mydownloads_allow_paypal_users'] == 1 && $mybb->input['business'] != '')
			{
				// Validate receiver email
				$mybb->input['business'] = trim_blank_chrs($mybb->input['business']);
				if(!preg_match("/^[a-zA-Z0-9&*+\-_.{}~^\?=\/]+@[a-zA-Z0-9-]+\.([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]{2,}$/si", $mybb->input['business']))
				{
					error($lang->mydownloads_invalid_receiver_email);
				}
			}
		}

		// Validate URLs
		if($mybb->settings['mydownloads_allow_urls'] == 1 && $mybb->input['url'] != '')
		{
			$urls = preg_split("/\r\n|\n|\r/", $mybb->input['url']);
			if(!empty($urls))
			{
				foreach($urls as $url)
				{
					if(!filter_var($url, FILTER_VALIDATE_URL))
						error($lang->mydownloads_invalid_url.htmlspecialchars_uni($url));
				}
			}
		}

		// Validate banner image
		$urlvalidate = mydownloads_getMime($mybb->input['banner']);
		$valid = 0;

		//Check for Valid Types
		$valid_types = array("image/bmp","image/x-windows-bmp","image/gif", "image/jpeg", "image/pjpeg", "image/png");
		foreach ($valid_types as $mime) {
			if (strpos($urlvalidate, $mime) !== FALSE) {
				$valid++;
			}
		}

		//No valid result found, error out
		if(isset($mybb->input['banner']) && $mybb->input['banner'] != '' && $valid == 0)
			error($lang->mydownloads_invalid_banner);

		// Upload time
		$filename = basename($_FILES['download_file']['name']);
		$preview = basename($_FILES['preview_file']['name']);

		if($preview)
		{
			$ext = get_extension($preview);
			if($ext != 'jpeg' && $ext != 'png' && $ext != 'jpg' && $ext != 'gif')
			{
				error($lang->mydownloads_invalid_extension);
			}

			$preview = "preview_".$mybb->user['uid']."_".TIME_NOW."_".md5(uniqid(rand(), true)).".".get_extension($preview);

			require_once MYBB_ROOT."inc/functions_image.php";
		}
		elseif($mybb->settings['mydownloads_require_preview'] == 1)
		{
			error($lang->mydownloads_require_preview);
		}
		else
			$thumbnail = $preview = '';

		if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview) && $preview != "")
		{
			error($lang->mydownloads_upload_problem_pr_already_exists);
		}

		if($mybb->settings['mydownloads_allow_urls'] == 0 || $mybb->input['url'] == '')
		{
			$download_file = mydownloads_upload_attachment($_FILES['download_file']);
		}

		if($download_file['error'] && ($mybb->settings['mydownloads_allow_urls'] != 1 || $mybb->input['url'] == ''))
		{
			error($lang->mydownloads_upload_problem_downloadfile."<br />".$download_file['error']);
		}
		else
		{
			if($preview == "" || move_uploaded_file($_FILES['preview_file']['tmp_name'], MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview))
			{
								if($preview != '')
				{
					$ext = get_extension($preview);
					if($ext != 'jpeg' && $ext != 'png' && $ext != 'jpg' && $ext != 'gif')
					{
						error($lang->mydownloads_invalid_extension);
					}

					// Does it meet the max resolution?
					if($mybb->settings['mydownloads_max_resolution'] != '')
					{
						$size = getimagesize(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview);

						$maxsize = explode('x', $mybb->settings['mydownloads_max_resolution']);
						if($size[0] > $maxsize[0])
						{
							@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview);
							@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename);
							error($lang->sprintf($lang->mydownloads_max_width, $maxsize[0]));
						}

						if($size[1] > $maxsize[1])
						{
							@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview);
							@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename);
							error($lang->sprintf($lang->mydownloads_max_height, $maxsize[1]));
						}
					}

					$r = generate_thumbnail(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview, MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'], 'thumbnail_'.$preview, $mybb->settings['mydownloads_thumb_resolution_height'], $mybb->settings['mydownloads_thumb_resolution_width']);
					if ($r['code'] == 4) // image is too small already, set thumbnail to the image
					{
						$thumbnail = $preview;
					}
					else
						$thumbnail = 'thumbnail_'.$preview;
				}
				else
					$thumbnail = '';

				// everything was uploaded, insert new download into the submissions table or auto approve it

				$auto = false;

				$submission = array();
				// no need to escape things here because it's done in mydownloads_approve_submission() and mydownloads_submit_download()
				$submission['name'] = $mybb->input['name'];
				$submission['cid'] = $cid;
				$submission['description'] = $mybb->input['description'];
				$submission['hidden'] = $mybb->input['hidden'];
				$submission['preview'] = $preview;
				$submission['thumbnail'] = $thumbnail;
				$submission['download'] = $download_file['filename'];
				$submission['filetype'] = $download_file['filetype'];
				$submission['filesize'] = $download_file['filesize'];
				$submission['points'] = $mybb->input['points'];
				$submission['submitter'] = $mybb->user['username'];
				$submission['submitter_uid'] = $mybb->user['uid'];
				$submission['license'] = $mybb->input['license'];
				$submission['version'] = $mybb->input['version'];
				$submission['price'] = (float)$mybb->input['price'];
				$submission['receiver_email'] = $mybb->input['business'];
				$submission['banner'] = $mybb->input['banner'];

				if($mybb->settings['mydownloads_allow_urls'] == 1)
					$submission['url'] = $mybb->input['url'];
				else
					$submission['url'] = '';

				// Get possible tags
				if(!empty($mybb->input['tags']) && is_array($mybb->input['tags']))
				{
					$tags_array = array();
					$q = $db->simple_select('mydownloads_tags', '*', 'categories=\'0\' OR CONCAT(\',\',categories,\',\') LIKE \'%,0,%\' OR CONCAT(\',\',categories,\',\') LIKE \'%,'.$cid.',%\'', array('order_by' => 'tag', 'order_dir' => 'asc'));
					while($tag = $db->fetch_array($q))
					{
						// Check if it's in our input
						if(in_array($tag['tid'], $mybb->input['tags']))
							$tags_array[] = (int)$tag['tid'];
					}

					$submission['tags'] = implode(',', $tags_array);
				}

				if (mydownloads_check_permissions($mybb->settings['mydownloads_gid_auto_approval']))
				{
					$did = mydownloads_approve_submission($submission, $cat);
					$auto = true;
				}
				else
				{
					mydownloads_submit_download($submission);
				}

				if ($auto)
					redirect($mybb->settings['bburl']."/mydownloads.php?action=managepreviews&amp;did=".$did, $lang->mydownloads_download_successfully_added_auto, $lang->mydownloads_download_successfully_added_title_auto);
				else
					redirect($mybb->settings['bburl']."/mydownloads.php?action=browse_cat&amp;cid=".$cid, $lang->mydownloads_download_successfully_added, $lang->mydownloads_download_successfully_added_title);
			}
			else
			{
				// a problem has a occurred, remove the download file and redirect the user
				@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename);

				error($lang->mydownloads_upload_problem_previewfile.$_FILES['preview_file']['error']);
			}
		}
	}

	//** Categories Dropdown **//
	$catcache = array();
	$foundparents = array();


	switch($db->type)
	{
		case "pgsql":
		case "sqlite":
			$sql_where = "(usergroups LIKE '%,'|| {$mybb->user['usergroup']}|| ',%') OR usergroups = 'all'";
		break;
		default:
			$sql_where = "(CONCAT(',',usergroups,',') LIKE '%,{$mybb->user['usergroup']},%') OR usergroups = 'all'";
	}


	// fetch categories
	$cat_query = $db->simple_select('mydownloads_categories', 'usergroups,cid,name,disporder,parent', 'hidden=0 AND ('.$sql_where.')', array('order_by' => 'name', 'order_dir' => 'asc'));
	while($cat = $db->fetch_array($cat_query))
	{
		// We want to check your additional groups too
		if (!mydownloads_check_permissions($cat['usergroups']))
		{
			continue;
		}

		$catcache[$cat['cid']] = $cat;
	}

	$db->free_result($cat_query);

	$categories = array();

	// Build tree list
	$categories[0] = $lang->mydownloads_select_category;
	mydownloads_build_tree($categories);

	if(!empty($categories))
	{
		// Build the category list with dropdown
		$cat_select = '<select class="chosen-select" name="cid" id="category">';
		foreach($categories as $catid => $c)
		{
			if($catid == $cid)
				$cat_select .= '<option value="'.(int)$catid.'" selected="selected">'.htmlspecialchars_uni($c).'</option>';
			else
				$cat_select .= '<option value="'.(int)$catid.'">'.htmlspecialchars_uni($c).'</option>';
		}
		$cat_select .= '</select>';
	}

	unset($catcache);

	// build bread crumb for categories
	if($cid > 0)
		mydownloads_build_breadcrumb($cid);

	// add 'Submit Download' breadcrumb
	add_breadcrumb($lang->mydownloads_submit_download, 'mydownloads.php?action=submit_download&amp;cid='.$cid);

	$submit_points = $submit_price = '';

	if ($newpoints_installed)
	{
		if($mybb->settings['mydownloads_points_available'] != '')
		{
			// Get available options
			$points_available = explode(',', $mybb->settings['mydownloads_points_available']);
			$pointsoptions = '';
			if(!empty($points_available))
			{
				foreach($points_available as $points)
				{
					$pointsoptions .= '<option value="'.(float)$points.'">'.newpoints_format_points($points).'</option>';
				}
			}

			eval("\$submit_points = \"".$templates->get("mydownloads_submit_points_predefined")."\";");
		}
		else
			eval("\$submit_points = \"".$templates->get("mydownloads_submit_points")."\";");
	}

	if($paypal_enabled)
	{
		eval("\$submit_price = \"".$templates->get("mydownloads_submit_price")."\";");

		// After 2.5 we allow donations
		if($mybb->settings['mydownloads_allow_paypal_users'] == 1)
		{
			eval('$submit_email = "'.$templates->get('mydownloads_submit_email').'";');
		}
		else
			$submit_email = '';
	}

	if($mybb->settings['mydownloads_allow_urls'] == 1)
	{
		eval('$submit_url = "'.$templates->get('mydownloads_submit_urls').'";');
	}

	$codebuttons = build_mycode_inserter("description");

	// Tags
	// We want to load all tags, display them and hide the ones that are not for this category or not global
	// This way we can switch categories in the dropdown and update the visibility with javascript
	$submit_tags = '';
	$tags_array = array();
	//$q = $db->simple_select('mydownloads_tags', '*', 'categories=\'0\' OR CONCAT(\',\',categories,\',\') LIKE \'%,0,%\' OR CONCAT(\',\',categories,\',\') LIKE \'%,'.$cid.',%\'', array('order_by' => 'tag', 'order_dir' => 'asc'));
	$q = $db->simple_select('mydownloads_tags', '*', '', array('order_by' => 'tag', 'order_dir' => 'asc'));
	while($tag = $db->fetch_array($q))
	{
		$tags_array[] = $tag;
	}

	if(!empty($tags_array))
	{
		$tags = '';
		foreach($tags_array as $tag)
		{
			$hidden = '';
			if($tag['categories'] != '0')
			{
				$cats = explode(',', $tag['categories']);

				if(!in_array('0', $cats))
				{
					if($cid > 0 && !in_array($cid, $cats))
						$hidden = 'display: none';
					elseif($cid == 0)
						$hidden = 'display: none';
				}
				else
					$tag['categories'] = 0; // set this to 0 temporarily (makes the javascript easier)
			}

			// Replace commas by underscores as classes can't seem to use commas, otherwise JS will not act properly
			$tag['categories'] = str_replace(',', '_', htmlspecialchars_uni($tag['categories']));

			$tag['tag'] = htmlspecialchars_uni($tag['tag']);
			eval('$tags .= "'.$templates->get('mydownloads_submit_tags_tag').'";');
		}

		eval('$submit_tags = "'.$templates->get('mydownloads_submit_tags').'";');
	}

	// set page title
	$title = $lang->mydownloads .= ' - '.$lang->mydownloads_submit_download;

	if($mybb->settings['mydownloads_require_preview'] == 1)
		$require_preview = 'true';
	else
		$require_preview = 'false';

	eval("\$submit_download_page = \"".$templates->get("mydownloads_submit_download")."\";");

	output_page($submit_download_page);

	exit;
}
elseif ($mybb->get_input('action') == "report")
{
	if ($mybb->user['uid'] <= 0)
		error_no_permission();

	$did = intval($mybb->input['did']);

	if ($did <= 0) // download id's can't be smaller than 1
		error($lang->mydownloads_no_did);

	// get download from the database
	$dl = $db->fetch_array($db->simple_select('mydownloads_downloads', '*', 'did='.$did, array('limit' => 1)));
	if (!$dl)
		error($lang->mydownloads_no_did);

	// check if category exists, if category doesn't exist the download is not assigned to a category? weird
	$cid = intval($dl['cid']);
	if ($cid <= 0 || (!($cat = mydownloads_get_category($cid, 'hidden,usergroups'))))
		error($lang->mydownloads_no_cid);

	// is the category hidden? don't continue if it is
	if ($cat['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	// verify permissions
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['usergroups']))
	{
		error($lang->mydownloads_no_permissions);
	}

	// build bread crumb
	mydownloads_build_breadcrumb($cid);

	// set colspan of the main table to 2
	$colspan = 2;

	if ($dl['hidden'] == 1) // download is hidden
		error($lang->mydownloads_no_permissions);

	$download = $dl;

	if ($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['postcode']);

		if (empty($mybb->input['reason']))
		{
			error($lang->mydownloads_empty_reason);
		}

		$insert_array = array(
			'username' => $db->escape_string($mybb->user['username']),
			'uid' => $db->escape_string($mybb->user['uid']),
			'reason' => $db->escape_string($mybb->input['reason']),
			'date' => TIME_NOW,
			'did' => $did,
			'name' => $db->escape_string($download['name'])
		);

		$db->insert_query('mydownloads_reports', $insert_array);

		redirect($mybb->settings['bburl']."/mydownloads.php", $lang->mydownloads_reported_message, $lang->mydownloads_reported_title);
	}

	// add breadcrumb
	add_breadcrumb($lang->mydownloads_report_breacrumb.htmlspecialchars_uni($download['name']), 'mydownloads.php?action=report&did='.$download['did']);

	// if we are here, we have permission to be here

	// set page title
	$title = $lang->mydownloads .= ' - '.$lang->mydownloads_report_download;

	eval("\$report_page = \"".$templates->get("mydownloads_report_download")."\";");

	output_page($report_page);

	exit;
}
elseif($mybb->get_input('action') == 'search')
{
	// add breadcrumb
	add_breadcrumb($lang->mydownloads_search, 'mydownloads.php?action=search');

	if(isset($mybb->input['cid']))
	{
		// check if category exists
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
			error($lang->mydownloads_no_cid);

		// verify permissions first
		if ($cat['hidden'] == 1)
			error($lang->mydownloads_no_permissions);

		// are we allowed to view the category?
		if (!mydownloads_check_permissions($cat['usergroups']))
		{
			error($lang->mydownloads_no_permissions);
		}
	}

	// set page title
	$title = $lang->mydownloads .= ' - '.$lang->search;

	eval("\$search = \"".$templates->get("mydownloads_search")."\";");

	output_page($search);

	exit;
}
elseif($mybb->get_input('action') == 'managepreviews')
{
	if (!$mybb->user['uid']) // guests cannot submit downloads
		error_no_permission();

	// Legacy?
	$mybb->input['ajax'] = 1;
	if($mybb->get_input('legacy', INPUT_INT) == 1)
	{
		// Disable ajax
		$mybb->input['ajax'] = 0;
	}

	// Let's check if we exceeded the post_max_size php ini directive
	if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
		mydownloads_json_error($lang->sprintf($lang->mydownloads_exceeded, ini_get('post_max_size')), array(), $mybb->get_input('legacy', INPUT_INT));
	}

	$did = intval($mybb->input['did']);
	if ($did <= 0) // download id's can't be smaller than 1
		error($lang->mydownloads_no_did);

	// get download from the database
	$download = mydownloads_get_download($did);
	if (empty($download))
		error($lang->mydownloads_no_did);

	if ($download['submitter_uid'] != $mybb->user['uid'])
		error_no_permission();

	// check if category exists
	$cid = intval($download['cid']);
	if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
		error($lang->mydownloads_no_cid);

	// check if category is hidden
	if ($cat['hidden'] == 1 || $download['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	if ($download['hidden'] == 2)
		error($lang->mydownloads_being_updated_error);

	// verify permissions first
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['usergroups']))
	{
		error($lang->mydownloads_no_permissions);
	}

	// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit download page
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['submit_dl_usergroups']))
	{
		error_no_permission();
	}

	if ($mybb->request_method == "post") // edit download
	{
		verify_post_check($mybb->input['postcode']);

		if($download['preview'] != '')
		{
			$download['preview'] = unserialize($download['preview']);
			if(count($download['preview']) >= (int)$mybb->settings['mydownloads_max_previews'])
			{
				mydownloads_json_error($lang->mydownloads_max_previews_error, array(), $mybb->get_input('legacy', INPUT_INT));
			}
		}
		else
			$download['preview'] = array();

		// Legacy?
		if($mybb->get_input('legacy', INPUT_INT) == 1)
		{
			$preview_file = $_FILES['preview_file'];
		}
		else
		{
			// Build a new a array
			$preview_file = array(
				'name' => $_FILES['files']['name'][0],
				'type' => $_FILES['files']['type'][0],
				'tmp_name' => $_FILES['files']['tmp_name'][0],
				'error' => $_FILES['files']['error'][0],
				'size' => $_FILES['files']['size'][0]
			);
		}

		$preview = basename($preview_file['name']);
		if($preview != '')
			$filename = "preview_".$mybb->user['uid']."_".TIME_NOW."_".md5(uniqid(rand(), true)).".".get_extension($preview);
		else
			mydownloads_json_error($lang->mydownloads_preview_empty, $preview_file, $mybb->get_input('legacy', INPUT_INT));

		$ext = get_extension($preview);
		if($ext != 'jpeg' && $ext != 'png' && $ext != 'gif' && $ext != 'jpg')
		{
			mydownloads_json_error($lang->mydownloads_invalid_extension, $preview_file, $mybb->get_input('legacy', INPUT_INT));
		}

		if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$filename) && $preview != "")
		{
			mydownloads_json_error($lang->mydownloads_upload_problem_pr_already_exists, $preview_file, $mybb->get_input('legacy', INPUT_INT));
		}

		// are we changing the preview image? Perhaps yes.
		if (!empty($preview))
		{
			if(!move_uploaded_file($preview_file['tmp_name'], MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$filename))
			{
				// a problem has a occurred, remove the download file and redirect the user
				@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$filename);

				switch($preview_file['error'])
				{
					case 1: // UPLOAD_ERR_INI_SIZE
						$preview_file['error'] = $lang->mydownloads_error_uploadfailed_php1;
						break;
					case 2: // UPLOAD_ERR_FORM_SIZE
						$preview_file['error'] = $lang->mydownloads_error_uploadfailed_php2;
						break;
					case 3: // UPLOAD_ERR_PARTIAL
						$preview_file['error'] = $lang->mydownloads_error_uploadfailed_php3;
						break;
					case 4: // UPLOAD_ERR_NO_FILE
						$preview_file['error'] = $lang->mydownloads_error_uploadfailed_php4;
						break;
					case 6: // UPLOAD_ERR_NO_TMP_DIR
						$preview_file['error'] = $lang->mydownloads_error_uploadfailed_php6;
						break;
					case 7: // UPLOAD_ERR_CANT_WRITE
						$preview_file['error'] = $lang->mydownloads_error_uploadfailed_php7;
						break;
					default:
						$preview_file['error'] = $lang->sprintf($lang->mydownloads_error_uploadfailed_phpx, $attachment['error']);
					break;
				}

				mydownloads_json_error($lang->mydownloads_upload_problem_previewfile.$preview_file['error'], $preview_file, $mybb->get_input('legacy', INPUT_INT));
			}

			// Does it meet the max resolution?
			if($mybb->settings['mydownloads_max_resolution'] != '')
			{
				$size = getimagesize(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$filename);

				$maxsize = explode('x', $mybb->settings['mydownloads_max_resolution']);
				if($size[0] > $maxsize[0])
				{
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview);
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename);
					mydownloads_json_error($lang->sprintf($lang->mydownloads_max_width, $maxsize[0]), $preview_file, $mybb->get_input('legacy', INPUT_INT));
				}

				if($size[1] > $maxsize[1])
				{
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview);
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename);
					mydownloads_json_error($lang->sprintf($lang->mydownloads_max_height, $maxsize[1]), $preview_file, $mybb->get_input('legacy', INPUT_INT));
				}
			}

			require_once MYBB_ROOT."inc/functions_image.php";

			$r = generate_thumbnail(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$filename, MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'], 'thumbnail_'.$filename, $mybb->settings['mydownloads_thumb_resolution_height'], $mybb->settings['mydownloads_thumb_resolution_width']);
			if ($r['code'] == 4) // image is too small already, set thumbnail to the image
			{
				$thumbnail = $filename;
			}
			else
				$thumbnail = 'thumbnail_'.$filename;
		}

		// Update preview field to include the new preview
		$download['preview'][] = $filename;

		// Reset keys!
		$download['preview'] = array_values($download['preview']);

		$k = count($download['preview'])-1;

		// Only update thumbnail if this is the cover
		if(count($download['preview']) == 1)
		{
			$download['thumbnail'] = $thumbnail;
		}

		$download['preview'] = serialize($download['preview']);

		$db->update_query('mydownloads_downloads', array('preview' => $db->escape_string($download['preview']), 'thumbnail' => $db->escape_string($download['thumbnail'])), 'did='.$did);

		// Legacy?
		if($mybb->get_input('legacy', INPUT_INT) == 1)
			redirect($mybb->settings['bburl']."/mydownloads.php?action=managepreviews&amp;did=".$did, $lang->mydownloads_preview_submitted);
		else
		{
			$filejson = new stdClass();
			$filejson->files[] = array(
				'thumbnailUrl' => htmlspecialchars_uni($mybb->settings['bburl'].'/'.$mybb->settings['mydownloads_previews_dir'].'/'.$thumbnail),
				'name' => htmlspecialchars_uni($preview_file['name']),
				'type' => htmlspecialchars_uni($preview_file['type']),
				'size' => (int)htmlspecialchars_uni($preview_file['size']),
				'id' => (int)$k
			);
			echo json_encode($filejson);
			exit;
		}
	}

	// build bread crumb for categories
	mydownloads_build_breadcrumb($cid);

	$previews = '';
	// Build previews list
	if($download['preview'] != '')
	{
		$download['preview'] = unserialize($download['preview']);
		if(!empty($download['preview']))
		{
			foreach($download['preview'] as $k => $p)
			{
				$bgcolor = alt_trow();

				$preview = array();
				$preview['cover'] = '';
				if($k == 0)
				{
					$preview['cover'] = '<strong>'.$lang->mydownloads_cover.':</strong><br />';
				}

				$preview['id'] = $k;

				if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'].'/thumbnail_'.$p))
					$preview['thumbnail'] = 'thumbnail_'.htmlspecialchars_uni($p);
				else
				{
					$preview['thumbnail'] = htmlspecialchars_uni($p);
				}

				$preview['preview'] = htmlspecialchars_uni($p);

				eval("\$previews.= \"".$templates->get("mydownloads_manage_previews_preview")."\";");
			}
		}
		else
		{
			$download['preview'] = '';
		}
	}

	if($download['preview'] == '')
	{
		eval("\$previews = \"".$templates->get("mydownloads_manage_previews_nodata")."\";");
	}

	$lang->mydownloads_max_previews = $lang->sprintf($lang->mydownloads_max_previews, (int)$mybb->settings['mydownloads_max_previews']);

	$lang->mydownloads_manage_previews = $lang->sprintf($lang->mydownloads_manage_previews, htmlspecialchars_uni($download['name']));

	$download['name'] = htmlspecialchars_uni($download['name']);

	if($mybb->settings['mydownloads_max_resolution'] != '')
	{
		$maxsize = explode('x', $mybb->settings['mydownloads_max_resolution']);

		$maxres = '&nbsp;<strong>'.$lang->sprintf($lang->mydownloads_max_res, (int)$maxsize[0], (int)$maxsize[1]).'</strong>';
	}

	// add breadcrumb
	add_breadcrumb($download['name'], 'mydownloads.php?action=view_down&did='.$download['did']);

	// add 'Manage Previews for Download XXXYYYZZZ' breadcrumb
	add_breadcrumb($lang->mydownloads_manage_previews, 'mydownloads.php?action=managepreviews&amp;did='.$did);

	// set page title
	$title = $lang->mydownloads .= ' - '.$lang->mydownloads_manage_previews;

	eval("\$page = \"".$templates->get("mydownloads_manage_previews")."\";");

	output_page($page);

	exit;
}
elseif($mybb->get_input('action') == 'setcover')
{
	if (!$mybb->user['uid']) // guests cannot submit downloads
		error_no_permission();

	// Let's check if we exceeded the post_max_size php ini directive
	if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
		error($lang->sprintf($lang->mydownloads_exceeded, ini_get('post_max_size')));
	}

	$did = intval($mybb->input['did']);
	if ($did <= 0) // download id's can't be smaller than 1
		error($lang->mydownloads_no_did);

	// get download from the database
	$download = mydownloads_get_download($did);
	if (empty($download))
		error($lang->mydownloads_no_did);

	if ($download['submitter_uid'] != $mybb->user['uid'])
		error_no_permission();

	// check if category exists
	$cid = intval($download['cid']);
	if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
		error($lang->mydownloads_no_cid);

	// check if category is hidden
	if ($cat['hidden'] == 1 || $download['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	if ($download['hidden'] == 2)
		error($lang->mydownloads_being_updated_error);

	// verify permissions first
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['usergroups']))
	{
		error($lang->mydownloads_no_permissions);
	}

	// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit download page
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['submit_dl_usergroups']))
	{
		error_no_permission();
	}

	verify_post_check($mybb->input['my_post_key']);

	if($download['preview'] != '')
	{
		$download['preview'] = unserialize($download['preview']);

		$newcover = (int)$mybb->input['id'];
		$last = count($download['preview'])-1;

		$out = array_splice($download['preview'], $newcover, 1);
		array_splice($download['preview'], 0, 0, $out);
	}
	else
	{
		error();
	}

	// Update thumbnail too
	if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'].'/thumbnail_'.$download['preview'][0]))
	{
		$download['thumbnail'] = 'thumbnail_'.$db->escape_string($download['preview'][0]);
	}
	else
	{
		$download['thumbnail'] = $db->escape_string($download['preview'][0]);
	}

	// Update preview field to include the new preview
	$download['preview'] = serialize($download['preview']);

	$db->update_query('mydownloads_downloads', array('preview' => $db->escape_string($download['preview']), 'thumbnail' => $db->escape_string($download['thumbnail'])), 'did='.$did);

	redirect($mybb->settings['bburl']."/mydownloads.php?action=managepreviews&amp;did=".$did, $lang->mydownloads_cover_updated);
	exit;
}
elseif($mybb->get_input('action') == 'deletepreview')
{
	if (!$mybb->user['uid']) // guests cannot submit downloads
		error_no_permission();

	// Let's check if we exceeded the post_max_size php ini directive
	if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
		error($lang->sprintf($lang->mydownloads_exceeded, ini_get('post_max_size')));
	}

	$did = intval($mybb->input['did']);
	if ($did <= 0) // download id's can't be smaller than 1
		error($lang->mydownloads_no_did);

	// get download from the database
	$download = mydownloads_get_download($did);
	if (empty($download))
		error($lang->mydownloads_no_did);

	if ($download['submitter_uid'] != $mybb->user['uid'])
		error_no_permission();

	// check if category exists
	$cid = intval($download['cid']);
	if ($cid <= 0 || (!($cat = mydownloads_get_category($cid))))
		error($lang->mydownloads_no_cid);

	// check if category is hidden
	if ($cat['hidden'] == 1 || $download['hidden'] == 1)
		error($lang->mydownloads_no_permissions);

	if ($download['hidden'] == 2)
		error($lang->mydownloads_being_updated_error);

	// verify permissions first
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['usergroups']))
	{
		error($lang->mydownloads_no_permissions);
	}

	// check permissions to submit downloads in this category. If we're allowed to submit downloads here, display the submit download page
	// are we allowed to view the category?
	if (!mydownloads_check_permissions($cat['submit_dl_usergroups']))
	{
		error_no_permission();
	}

	verify_post_check($mybb->input['my_post_key']);

	if($download['preview'] != '')
	{
		$download['preview'] = unserialize($download['preview']);

		$delete = (int)$mybb->input['id'];

		$preview = '';

		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$download['preview'][$delete]);
		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/thumbnail_".$download['preview'][$delete]);

		// Are we deleting the cover?
		if($delete == 0)
		{
			// Do we have more than one image?
			if(count($download['preview']) > 1)
			{
				// Find our next cover
				$next = 0;
				foreach($download['preview'] as $k => $p)
				{
					if($k != 0)
					{
						$next = $k;
						break;
					}
				}

				$preview = $download['preview'][$next];

				// Update thumbnail too
				if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'].'/thumbnail_'.$download['preview'][$next]))
				{
					$download['thumbnail'] = 'thumbnail_'.$db->escape_string($download['preview'][$next]);
				}
				else
				{
					$download['thumbnail'] = $db->escape_string($download['preview'][$next]);
				}

				unset($download['preview'][0]);
				unset($download['preview'][$next]);
				$download['preview'][0] = $preview;
			}
			else
			{
				$download['thumbnail'] = '';
				$download['preview'] = array();
			}
		}
		else
			unset($download['preview'][$delete]);
	}
	else
	{
		error();
	}

	// Reset keys!
	$download['preview'] = array_values($download['preview']);

	// Update preview field
	$download['preview'] = serialize($download['preview']);

	$db->update_query('mydownloads_downloads', array('preview' => $db->escape_string($download['preview']), 'thumbnail' => $db->escape_string($download['thumbnail'])), 'did='.$did);

	redirect($mybb->settings['bburl']."/mydownloads.php?action=managepreviews&amp;did=".$did, $lang->mydownloads_preview_deleted);
	exit;
}
elseif($mybb->get_input('action') == 'latest')
{
	if($mybb->settings['mydownloads_latest_submissions'] == 0)
		error_no_permission();

	add_breadcrumb($lang->mydownloads_latest_submissions, 'mydownloads.php?action=latest');

	// pagination
	$per_page = 20;
	$mybb->input['page'] = intval($mybb->input['page']);
	if($mybb->input['page'] && $mybb->input['page'] > 1)
	{
		$mybb->input['page'] = intval($mybb->input['page']);
		$start = ($mybb->input['page']*$per_page)-$per_page;
	}
	else
	{
		$mybb->input['page'] = 1;
		$start = 0;
	}

	// total comments
	$total_rows = $db->fetch_field($query = $db->simple_select("mydownloads_downloads", "COUNT(did) as downloads"), "downloads");

	// multi-page
	if ($total_rows > $per_page)
		$multipage = multipage($total_rows, $per_page, $mybb->input['page'], $mybb->settings['bburl']."/mydownloads.php?action=latest");

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
	$query = $db->query("
		SELECT d.did,d.name,d.date,d.submitter,d.submitter_uid,d.preview,d.thumbnail,c.name as catname,c.cid
		FROM `".TABLE_PREFIX."mydownloads_downloads` d
		LEFT JOIN `".TABLE_PREFIX."mydownloads_categories` c ON (d.cid=c.cid)
		WHERE d.hidden=0 AND c.hidden=0 AND ({$sql_where})
		ORDER BY d.date DESC
		LIMIT {$start}, {$per_page}
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

	$title = $lang->mydownloads_latest_submissions;

	eval("\$mydownloads = \"".$templates->get("mydownloads_latest_submissions_page")."\";");

	$plugins->run_hooks("mydownloads_latest_submissions_end");

	output_page($mydownloads);

	exit;
}

// set page title if it hasn't been set already
if (!$title)
	$title = $lang->mydownloads;

// get our downloads page
eval("\$mydownloads = \"".$templates->get("mydownloads")."\";");

$plugins->run_hooks("mydownloads_end");

output_page($mydownloads);

exit;

?>
