<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/admin/modules/mydownloads/downloads_categories.php)
 *	 Author: Diogo Parrinha
 *   Copyright: � 2009-2010 Diogo Parrinha
 *   
 *
 *
 *   MyDownloads is a plugin which adds a downloads page to your forum.
 *
 ***************************************************************************/
 
/****************************************************************************
* You are NOT authorized to share/re-distribute this plugin with ANYONE without my express permission.
* You MUST NOT give credits to anyone besides me, Diogo Parrinha.
* You MUST NOT remove the license file or any conditions/rules that you may find in the included PHP files.
* The author is NOT responsible for any damaged caused by this plugin.
* 
* By downloading/installing this module you agree with the conditions stated above.
****************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$lang->load('mydownloads');

$page->add_breadcrumb_item($lang->mydownloads_categories_downloads, 'index.php?module=mydownloads-categories');

// check if NewPoints is installed
$plugins_cache = $cache->read("plugins");
if(isset($plugins_cache['active']['newpoints']) && $mybb->settings['mydownloads_bridge_newpoints'] == 1)
	$newpoints_installed = true;

if ($mybb->input['action'] == "browse_downloads")
{
	// check if we are trying to browse a valid category
	$cid = intval($mybb->input['cid']);
	if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'cid', "cid = $cid")))))
	{
		flash_message($lang->mydownloads_select_category, 'error');
		admin_redirect("index.php?module=mydownloads-downloads_categories");
	}
	
	// build breadcrumb navigation
	mydownloads_build_breadcrumb($cid);
}
			
$page->output_header($lang->mydownloads_categories_downloads);

if (!$mybb->input['type'])
{
	$sub_tabs['mydownloads_categories'] = array(
		'title'			=> $lang->mydownloads_categories,
		'link'			=> 'index.php?module=mydownloads-downloads_categories',
		'description'	=> $lang->mydownloads_categories_description
	);
	
	$sub_tabs['mydownloads_categories_create'] = array(
		'title'			=> $lang->mydownloads_create_category."/".$lang->mydownloads_edit_category,
		'link'			=> 'index.php?module=mydownloads-downloads_categories&amp;action=create&amp;parent='.intval($mybb->input['cid']),
		'description'	=> $lang->mydownloads_create_category_description2
	);
	
	switch ($mybb->input['action'])
	{
		case 'edit_category':
			$sub_tabs['mydownloads_categories_create']['description'] = $lang->mydownloads_edit_category_description2;
			$page->output_nav_tabs($sub_tabs, 'mydownloads_categories_create');
		break;
		case 'create':
			$page->output_nav_tabs($sub_tabs, 'mydownloads_categories_create');
		break;
		default:
			$page->output_nav_tabs($sub_tabs, 'mydownloads_categories');
		break;
	}
}
elseif ($mybb->input['type'] == 'downloads')
{
	$sub_tabs['mydownloads_categories'] = array(
		'title'			=> $lang->mydownloads_categories,
		'link'			=> 'index.php?module=mydownloads-downloads_categories',
		'description'	=> $lang->mydownloads_categories_description
	);
	
	$sub_tabs['mydownloads_categories_create'] = array(
		'title'			=> $lang->mydownloads_create_category."/".$lang->mydownloads_edit_category,
		'link'			=> 'index.php?module=mydownloads-downloads_categories&amp;action=create&amp;parent='.intval($mybb->input['cid']),
		'description'	=> $lang->mydownloads_create_category_description2
	);
	
	$sub_tabs['mydownloads_downloads'] = array(
		'title'			=> $lang->mydownloads_view_downloads,
		'link'			=> 'index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid='.intval($mybb->input['cid']),
		'description'	=> $lang->mydownloads_view_downloads_description
	);
	
	$sub_tabs['mydownloads_categories_add'] = array(
		'title'			=> $lang->mydownloads_add_download."/".$lang->mydownloads_edit_download,
		'link'			=> 'index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=add&amp;cid='.intval($mybb->input['cid']),
		'description'	=> $lang->mydownloads_add_download_description2
	);
	
	switch ($mybb->input['action'])
	{
		case 'edit_download':
			$sub_tabs['mydownloads_categories_add']['description'] = $lang->mydownloads_edit_download_description2;
			$page->output_nav_tabs($sub_tabs, 'mydownloads_categories_add');
		break;
		case 'add':
			$page->output_nav_tabs($sub_tabs, 'mydownloads_categories_add');
		break;
		default:
			$page->output_nav_tabs($sub_tabs, 'mydownloads_downloads');
		break;
	}
}

/*
 * Function similar to the one in NewPoints
 *
 * Generates a checkbox for each group in a row
 * $form, the form that has the form container
 * $form_container, the container which contains the rows
 * $invalidate, set to 1 if you want to remove guests from the list
 * $name, name of the checkbox
 * $title, title of the row
 * $description, description of the form row
 * $label, the id of the row
 *  
*/
function mydownloads_make_usergroup_checkbox_code($form, $form_container, $invalidate, $name, $title, $description, $label, $values="", $all_is_checked=0)
{
	global $db, $lang;
	
	if ($invalidate == 1)
		$query = $db->simple_select("usergroups", "gid, title", 'gid not IN (\'1\')', array('order_by' => 'title','order_dir' => 'ASC'));
	else
		$query = $db->simple_select("usergroups", "gid, title", '', array('order_by' => 'title','order_dir' => 'ASC'));
	
	$html = "";
	
	$boxid = 0;
	
	while($usergroup = $db->fetch_array($query))
	{
		$checked = 0;

		$token = strtok($values, ",");

		while ($token !== false) {
			if ($token == $usergroup['gid'])
			{
				$checked = 1;
				break;
			}
			$token = strtok(",");
		}

		$html .= $form->generate_check_box($name.'[]', $usergroup['gid'], $usergroup['title'], array('id' => $name.'_'.$boxid,'checked' => $checked))."<br/>";
		
		$boxid++;
	}
	
	$checked = 0;
	
	$token = strtok($values, ",");

	while ($token !== false)
	{
		if ($token == $usergroup['gid'])
		{
			$checked = 1;
			break;
		}
		elseif ($token == 'all')
		{
			$checked = 1;
			break;
		}
		$token = strtok(",");
	}
	
	if ($all_is_checked == 1)
	{
		$checked = 1;
	}
	
	$html .= $form->generate_check_box($name.'[]', 'all', $lang->mydownloads_all_usergroups, array('id' => $name.'_'.$boxid,'checked' => $checked))."<br/>";

	return $form_container->output_row($title, $description, $html, $label);
}

if (!$mybb->input['action']) // No action, browse categories
{
	$per_page = 10;
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
	
	$query = $db->simple_select("mydownloads_categories", "COUNT(cid) as categories", 'parent=0');
	$total_rows = $db->fetch_field($query, "categories");
	
	//die("".$total_rows." ".$per_page." ".$mybb->input['page']);

	if ($total_rows > $mybb->input['page'])
		echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=mydownloads-downloads_categories&amp;page={page}");
	
	
	$maincat = array();
	$regcat = array();
	$dl_catcache = array(); // used inside mydownloads_get_downloads()

	// fetch all main categories categories - within limit
	$query = $db->simple_select('mydownloads_categories', '*', 'parent=0', array('order_by' => 'disporder', 'order_dir' => 'asc', 'limit' => "{$start}, {$per_page}"));
	while($cat = $db->fetch_array($query))
	{
		$maincat[$cat['cid']] = $cat;
		$dl_catcache[$cat['parent']][$cat['disporder']][$cat['cid']] = $cat; // this cache variable is used for counting downloads
	}
	$db->free_result($query);
	
	// we should use a single query to get all categories but the limit (and consecutively pagination) will be affected
	// fetch all non main categories categories
	$query = $db->simple_select('mydownloads_categories', '*', 'parent!=0', array('order_by' => 'disporder', 'order_dir' => 'asc'));
	while($cat = $db->fetch_array($query))
	{
		$regcat[$cat['parent']][$cat['cid']] = $cat;
		$dl_catcache[$cat['parent']][$cat['disporder']][$cat['cid']] = $cat; // this cache variable is used for counting downloads
	}
	$db->free_result($query);
	
	// table
	$table = new Table;
	$table->construct_header($lang->mydownloads_category, array('width' => '55%'));
	$table->construct_header($lang->mydownloads_num_downloads, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_admin_options, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_disporder, array('width' => '5%', 'class' => 'align_center'));
	
	if (!empty($maincat))
	{	
		// display categories list
		foreach($maincat as $r)
		{
			if ($r['hidden'])
				$hidden = $lang->mydownloads_hidden;
			else 
				$hidden = "";
				
			$r['name'] = "<strong>".htmlspecialchars_uni($r['name'])."</strong>";
			$r2['description'] = $r['description'];
			$r['description'] = substr($r['description'], 0, 70);
			if ($r['description'] != $r2['description'])
				$r['description'] = $r['description']." (...)";
			$r['description'] = htmlspecialchars_uni($r['description']);
			
			$sub_categories = '';
			
			$prefix_name = "<strong>".$lang->mydownloads_sub_categories."</strong>".': ';
			$prefix = '';
			
			if (!empty($regcat[$r['cid']]))
			{
				foreach($regcat[$r['cid']] as $category)
				{
					$sub_categories .= "<small>".$prefix_name.$prefix.'<a href="index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid='.$category['cid'].'">'.htmlspecialchars_uni($category['name']).'</a></small>';
					
					if (!$prefix)
						$prefix = ' | ';
						
					if ($prefix_name)
						$prefix_name = '';
				}
				
				if (!empty($sub_categories))
					$sub_categories = '<br /><br />'.$sub_categories;
			}
			
			$prefix = '';
				
			$table->construct_cell("<a href=\"index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$r['cid']}\">".$r['name']."</a> {$hidden}<br /><small>".$r['description']."</small>".$sub_categories); // category name and description
			$table->construct_cell(mydownloads_get_downloads($r['cid'], intval($r['downloads'])), array('class' => 'align_center'));
			$table->construct_cell("<a href=\"index.php?module=mydownloads-downloads_categories&amp;action=edit_category&amp;cid={$r['cid']}\">".$lang->mydownloads_edit."</a> ".$lang->mydownloads_or." <a href=\"index.php?module=mydownloads-downloads_categories&amp;action=delete_category&amp;cid={$r['cid']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">".$lang->mydownloads_delete."</a>", array('class' => 'align_center')); // edit button
			$table->construct_cell(intval($r['disporder']), array('class' => 'align_center')); // disporder
			
			$table->construct_row();
		}
	}
	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->mydownloads_no_categories, array('colspan' => 4));
		
		$table->construct_row();
	}
	
	$table->output($lang->mydownloads_categories);
}
elseif ($mybb->input['action'] == 'create') // Create category action
{
	if ($mybb->request_method == "post") // create category
	{
		if (empty($mybb->input['name']))
		{
			flash_message($lang->mydownloads_no_name, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		if (intval($mybb->input['hidden']) != 0 && intval($mybb->input['hidden']) != 1)
		{
			flash_message($lang->mydownloads_no_hidden, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		if (intval($mybb->input['disporder']) <= 0)
			$mybb->input['disporder'] = 1;
					
		//visible groups
		if(is_array($visiblegroups = $mybb->input['visiblegroups']))
		{
			if(in_array('all', $visiblegroups))
				$visiblegroups = 'all';
			else
			{
				for($i=0, $im=count($visiblegroups); $i<$im; $i++)
					$visiblegroups[$i] = intval($visiblegroups[$i]);
				$visiblegroups = implode(',', $visiblegroups);
			}
		}
		
		// groups which can submit downloads
		if(is_array($submitgroups = $mybb->input['submit_dl_usergroups']))
		{
			if(in_array('all', $submitgroups))
				$submitgroups = 'all';
			else
			{
				for($i=0, $im=count($submitgroups); $i<$im; $i++)
					$submitgroups[$i] = intval($submitgroups[$i]);
				$submitgroups = implode(',', $submitgroups);
			}
		}
		
		// groups which can download files
		if(is_array($downloadgroups = $mybb->input['dl_usergroups']))
		{
			if(in_array('all', $downloadgroups))
				$downloadgroups = 'all';
			else
			{
				for($i=0, $im=count($downloadgroups); $i<$im; $i++)
					$downloadgroups[$i] = intval($downloadgroups[$i]);
				$downloadgroups = implode(',', $downloadgroups);
			}
		}
		
		// check if parent category is valid
		$parent = intval($mybb->input['parent']);
			
		if ($parent < 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'cid', "cid = $parent"))) && $parent > 0))
		{
			flash_message($lang->mydownloads_no_parent_error, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		$insert_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'usergroups' => $visiblegroups,
			'submit_dl_usergroups' => $submitgroups,
			'dl_usergroups' => $downloadgroups,
			'hidden' => intval($mybb->input['hidden']),
			'disporder' => intval($mybb->input['disporder']),
			'parent' => intval($mybb->input['parent']),
		);
		
		// Do we want to upload a new background image?
		if(isset($_FILES['background']['name']) && $_FILES['background']['name'] != '')
		{
			$background = basename($_FILES['background']['name']);
			if ($background)
				$background = "category_".TIME_NOW."_".md5(uniqid(rand(),�true)).".".get_extension($background);
			
			// Already exists?
			if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$background))
			{
				flash_message($lang->mydownloads_background_upload_error2, 'error');
				admin_redirect("index.php?module=mydownloads-downloads_categories");
			}

			if(!move_uploaded_file($_FILES['background']['tmp_name'], MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$background))
			{
				flash_message($lang->mydownloads_background_upload_error, 'error');
				admin_redirect("index.php?module=mydownloads-downloads_categories");
			}
			
			$insert_array['background'] = $db->escape_string($background);
		}
	
		$db->insert_query('mydownloads_categories', $insert_array);
		
		flash_message($lang->mydownloads_category_created, 'success');
		admin_redirect("index.php?module=mydownloads-downloads_categories");
	}
	else {
		
		$catcache = array();
		
		// fetch categories
		$cat_query = $db->simple_select('mydownloads_categories', 'cid,name,disporder,parent', '', array('order_by' => 'name', 'order_dir' => 'asc'));
		while($cat = $db->fetch_array($cat_query))
		{
			$catcache[$cat['cid']] = $cat;
		}
		$db->free_result($cat_query);
		
		$categories = array();
		
		// Build tree list
		$categories[0] = $lang->mydownloads_no_parent;
		mydownloads_build_tree($categories, true);
		
		unset($catcache);
		
		$form = new Form("index.php?module=mydownloads-downloads_categories&amp;action=create", "post", "mydownloads", 1);
		
		$form_container = new FormContainer($lang->mydownloads_create_category);
		$form_container->output_row($lang->mydownloads_create_category_name, '', $form->generate_text_box('name', '', array('id' => 'name')), 'name');
		$form_container->output_row($lang->mydownloads_create_category_description, '', $form->generate_text_area('description', '', array('id' => 'description')), 'description');
		mydownloads_make_usergroup_checkbox_code($form, $form_container, 0, 'visiblegroups', $lang->mydownloads_create_category_usergroups, "", 'visiblegroups', '', 1);
		mydownloads_make_usergroup_checkbox_code($form, $form_container, 1, 'submit_dl_usergroups', $lang->mydownloads_create_category_submit_dl_usergroups, "", 'submit_dl_usergroups', '', 1);
		mydownloads_make_usergroup_checkbox_code($form, $form_container, 1, 'dl_usergroups', $lang->mydownloads_create_category_dl_usergroups, "", 'dl_usergroups', '', 1);
		$form_container->output_row($lang->mydownloads_create_category_hidden, "", $form->generate_yes_no_radio('hidden', 0, true), 'hidden');
		$form_container->output_row($lang->mydownloads_create_category_disporder, '', $form->generate_text_box('disporder', '1', array('id' => 'disporder')), 'disporder');
		$form_container->output_row($lang->mydownloads_create_category_parent, "", $form->generate_select_box('parent', $categories, array(intval($mybb->input['parent'])), array('id' => 'parent')), 'parent');
		$form_container->output_row($lang->mydownloads_create_category_background, $lang->mydownloads_create_category_background_desc, $form->generate_file_upload_box("background", array('style' => 'width: 200px;')), 'background');
		$form_container->end();
	
		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->mydownloads_submit_changes);
		$buttons[] = $form->generate_reset_button($lang->mydownloads_reset_button);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'delete_category') // Delete category action
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=mydownloads-downloads_categories");
	}

	if($mybb->request_method == "post")
	{
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'cid', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_no_cid, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		mydownloads_deletecat($cid);
		
		flash_message($lang->mydownloads_category_deleted, 'success');
		admin_redirect("index.php?module=mydownloads-downloads_categories");
	}
	else
	{
		$mybb->input['cid'] = intval($mybb->input['cid']);
		$form = new Form("index.php?module=mydownloads-downloads_categories&amp;action=delete_category&amp;cid={$mybb->input['cid']}&amp;my_post_key={$mybb->post_code}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->mydownloads_cat_deleteconfirm}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
		echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
		echo "</p>\n";
		echo "</div>\n";
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'edit_category') // edit category page
{
	if ($mybb->request_method == "post") // edit category
	{
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'cid', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_no_cid, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		if (empty($mybb->input['name']))
		{
			flash_message($lang->mydownloads_no_name, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		if (intval($mybb->input['disporder']) <= 0)
		{
			$mybb->input['disporder'] = 1;
		}
		
		if (intval($mybb->input['hidden']) != 0 && intval($mybb->input['hidden']) != 1)
		{
			flash_message($lang->mydownloads_no_hidden, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		// check if parent category is valid
		$parent = intval($mybb->input['parent']);
			
		if ($parent < 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'cid', "cid = $parent"))) && $parent > 0) || $parent == $cid)
		{
			flash_message($lang->mydownloads_no_parent_error, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
					
		//visible groups
		if(is_array($visiblegroups = $mybb->input['visiblegroups']))
		{
			if(in_array('all', $visiblegroups))
				$visiblegroups = 'all';
			else
			{
				for($i=0, $im=count($visiblegroups); $i<$im; $i++)
					$visiblegroups[$i] = intval($visiblegroups[$i]);
				$visiblegroups = implode(',', $visiblegroups);
			}
		}
		
		// groups which can submit downloads
		if(is_array($submitgroups = $mybb->input['submit_dl_usergroups']))
		{
			if(in_array('all', $submitgroups))
				$submitgroups = 'all';
			else
			{
				for($i=0, $im=count($submitgroups); $i<$im; $i++)
					$submitgroups[$i] = intval($submitgroups[$i]);
				$submitgroups = implode(',', $submitgroups);
			}
		}
		
		// groups which can submit downloads
		if(is_array($downloadgroups = $mybb->input['dl_usergroups']))
		{
			if(in_array('all', $downloadgroups))
				$downloadgroups = 'all';
			else
			{
				for($i=0, $im=count($downloadgroups); $i<$im; $i++)
					$downloadgroups[$i] = intval($downloadgroups[$i]);
				$downloadgroups = implode(',', $downloadgroups);
			}
		}
		
		$update_array = array(
			'name' => $db->escape_string($mybb->input['name']),
			'description' => $db->escape_string($mybb->input['description']),
			'usergroups' => $visiblegroups,
			'submit_dl_usergroups' => $submitgroups,
			'dl_usergroups' => $downloadgroups,
			'hidden' => intval($mybb->input['hidden']),
			'disporder' => intval($mybb->input['disporder']),
			'parent' => intval($mybb->input['parent']),
		);
		
		// Delete current image
		if($mybb->input['delete_background'] == 1)
		{
			@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".'category_'.basename($update_array['background']));
			$update_array['background'] = '';
		}
		
		// Do we want to upload a new background image?
		if(isset($_FILES['background']['name']) && $_FILES['background']['name'] != '')
		{
			$background = basename($_FILES['background']['name']);
			if ($background)
				$background = "category_".TIME_NOW."_".md5(uniqid(rand(),�true)).".".get_extension($background);
			
			// Already exists?
			if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$background))
			{
				flash_message($lang->mydownloads_background_upload_error2, 'error');
				admin_redirect("index.php?module=mydownloads-downloads_categories");
			}

			if(!move_uploaded_file($_FILES['background']['tmp_name'], MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$background))
			{
				flash_message($lang->mydownloads_background_upload_error, 'error');
				admin_redirect("index.php?module=mydownloads-downloads_categories");
			}
			
			$update_array['background'] = $db->escape_string($background);
		}
		
		$db->update_query('mydownloads_categories', $update_array, "cid = $cid");
		
		flash_message($lang->mydownloads_category_edited, 'success');
		admin_redirect("index.php?module=mydownloads-downloads_categories");
	}
	else {
		
		$catcache = array();
		$foundparents = array();
		
		// fetch categories
		$cat_query = $db->simple_select('mydownloads_categories', 'cid,name,disporder,parent', '', array('order_by' => 'name', 'order_dir' => 'asc'));
		while($cat = $db->fetch_array($cat_query))
		{
			$catcache[$cat['cid']] = $cat;
		}
		$db->free_result($cat_query);
		
		$categories = array();
		
		// Build tree list
		$categories[0] = $lang->mydownloads_no_parent;
		mydownloads_build_tree($categories, true);
		
		unset($catcache);
		
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', '*', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_select_category, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		$form = new Form("index.php?module=mydownloads-downloads_categories&amp;action=edit_category", "post", "mydownloads", 1);
		
		echo $form->generate_hidden_field('cid', intval($mybb->input['cid']));
		
		$form_container = new FormContainer($lang->mydownloads_edit_category);
		$form_container->output_row($lang->mydownloads_edit_category_name, '', $form->generate_text_box('name', $cat['name'], array('id' => 'name')), 'name');
		$form_container->output_row($lang->mydownloads_edit_category_description, '', $form->generate_text_area('description', $cat['description'], array('id' => 'description')), 'description');
		mydownloads_make_usergroup_checkbox_code($form, $form_container, 0, 'visiblegroups', $lang->mydownloads_edit_category_usergroups, "", 'visiblegroups', $cat['usergroups']);
		mydownloads_make_usergroup_checkbox_code($form, $form_container, 1, 'submit_dl_usergroups', $lang->mydownloads_edit_category_submit_dl_usergroups, "", 'submit_dl_usergroups', $cat['submit_dl_usergroups']);
		mydownloads_make_usergroup_checkbox_code($form, $form_container, 1, 'dl_usergroups', $lang->mydownloads_edit_category_dl_usergroups, "", 'dl_usergroups', $cat['dl_usergroups']);
		$form_container->output_row($lang->mydownloads_edit_category_hidden, "", $form->generate_yes_no_radio('hidden', $cat['hidden'], true), 'hidden');
		$form_container->output_row($lang->mydownloads_edit_category_disporder, '', $form->generate_text_box('disporder', $cat['disporder'], array('id' => 'disporder')), 'disporder');
		$form_container->output_row($lang->mydownloads_edit_category_parent, "", $form->generate_select_box('parent', $categories, intval($cat['parent']), array('id' => 'parent')), 'parent');
		$form_container->output_row($lang->mydownloads_edit_category_background, $lang->mydownloads_edit_category_background_desc, $form->generate_file_upload_box("background", array('style' => 'width: 200px;')), 'background');
		$form_container->output_row($lang->mydownloads_edit_category_delete_background, $lang->mydownloads_edit_category_delete_background_desc, $form->generate_check_box('delete_background', '1', '', array('id' => 'delete_background','checked' => 'false')), 'background');
		$form_container->end();
	
		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->mydownloads_submit_changes);
		$buttons[] = $form->generate_reset_button($lang->mydownloads_reset_button);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}
elseif ($mybb->input['action'] == "browse_downloads")
{
	$maincat = array();
	$regcat = array();
	$dl_catcache = array(); // used inside mydownloads_get_downloads()

	// fetch all categories whose parent is the current one - within limit
	$query = $db->simple_select('mydownloads_categories', '*', 'parent='.$cid, array('order_by' => 'disporder', 'order_dir' => 'asc'));
	while($cat = $db->fetch_array($query))
	{
		$maincat[$cat['cid']] = $cat;
		$dl_catcache[$cat['parent']][$cat['disporder']][$cat['cid']] = $cat; // this cache variable is used for counting downloads
	}
	$db->free_result($query);
	
	// we should use a single query to get all categories but the limit (and consecutively pagination) will be affected
	// fetch all non main categories categories
	$query = $db->simple_select('mydownloads_categories', '*', 'parent!=0', array('order_by' => 'disporder', 'order_dir' => 'asc'));
	while($cat = $db->fetch_array($query))
	{
		$regcat[$cat['parent']][$cat['cid']] = $cat;
		$dl_catcache[$cat['parent']][$cat['disporder']][$cat['cid']] = $cat; // this cache variable is used for counting downloads
	}
	$db->free_result($query);

	// show sub categories
	$table = new Table;
	$table->construct_header($lang->mydownloads_category, array('width' => '55%'));
	$table->construct_header($lang->mydownloads_num_downloads, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_admin_options, array('width' => '20%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_disporder, array('width' => '5%', 'class' => 'align_center'));
	
	if ($maincat)
	{
		// display categories list
		foreach($maincat as $r)
		{	
			if ($r['hidden'])
				$hidden = $lang->mydownloads_hidden;
			else 
				$hidden = "";
				
			$r['name'] = htmlspecialchars_uni($r['name']);
			$r2['description'] = $r['description'];
			$r['description'] = substr($r['description'], 0, 70);
			if ($r['description'] != $r2['description'])
				$r['description'] = $r['description']." (...)";
			$r['description'] = htmlspecialchars_uni($r['description']);
			
			$query2 = $db->simple_select('mydownloads_categories', '*', 'parent='.$r['cid'], array('order_by' => 'disporder', 'order_dir' => 'asc'));
			
			$sub_categories = '';
			
			$prefix_name = "<strong>".$lang->mydownloads_sub_categories."</strong>".': ';
			$prefix = '';
			
			if (!empty($regcat[$r['cid']]))
			{
				foreach($regcat[$r['cid']] as $category)
				{
					$sub_categories .= "<small>".$prefix_name.$prefix.'<a href="index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid='.$category['cid'].'">'.htmlspecialchars_uni($category['name']).'</a></small>';
					
					if (!$prefix)
						$prefix = ' | ';
						
					if ($prefix_name)
						$prefix_name = '';
				}
				
				if (!empty($sub_categories))
					$sub_categories = '<br /><br />'.$sub_categories;
			}
			
			$prefix = '';
			
			$table->construct_cell("<a href=\"index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$r['cid']}\">".$r['name']."</a> {$hidden}<br /><small>".$r['description']."</small>".$sub_categories); // category name and description
			//$table->construct_cell("<div style=\"float: right;\"><small>".$sub_categories."</small></div><a href=\"index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$r['cid']}\">".$r['name']."</a> {$hidden}<br /><small>".$r['description']."</small>"); // category name and description
			$table->construct_cell(mydownloads_get_downloads($r['cid'], intval($r['downloads'])), array('class' => 'align_center'));
			$table->construct_cell("<a href=\"index.php?module=mydownloads-downloads_categories&amp;action=edit_category&amp;cid={$r['cid']}\">".$lang->mydownloads_edit."</a> ".$lang->mydownloads_or." <a href=\"index.php?module=mydownloads-downloads_categories&amp;action=delete_category&amp;cid={$r['cid']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">".$lang->mydownloads_delete."</a>", array('class' => 'align_center')); // edit button
			$table->construct_cell(intval($r['disporder']), array('class' => 'align_center')); // dispoerder
			
			$table->construct_row();
		}
	}
	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->mydownloads_no_sub_categories, array('colspan' => 4));
		
		$table->construct_row();
	}
	
	$table->output($lang->mydownloads_sub_categories);
	
	$per_page = 25;
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
	
	$query = $db->simple_select("mydownloads_downloads", "COUNT(did) as downloads", "cid=".$cid);
	$total_rows = $db->fetch_field($query, "downloads");
	$cat_name = $db->fetch_field($db->simple_select("mydownloads_categories", "name", "cid=".$cid), "name");

	echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid=".$cid."&amp;page={page}");
	
	// table
	$table = new Table;
	$table->construct_header($lang->mydownloads_download, array('width' => '50%'));
	$table->construct_header($lang->mydownloads_username);
	$table->construct_header($lang->mydownloads_admin_options);
	
	$found = false;

	$query = $db->simple_select('mydownloads_downloads', '*', "cid = $cid", array('order_by' => 'name', 'order_dir' => 'ASC', 'limit' => "{$start}, {$per_page}"));
	while($r = $db->fetch_array($query)) {
		
		if ($r['hidden'] == 1)
			$hidden = $lang->mydownloads_hidden;
		elseif ($r['hidden'] == 2)
			$hidden = $lang->mydownloads_being_updated;
		else 
			$hidden = "";
			
		$r['name'] = htmlspecialchars_uni($r['name']);
		$r2['description'] = $r['description'];
			$r['description'] = substr($r['description'], 0, 70);
			if ($r['description'] != $r2['description'])
				$r['description'] = $r['description']." (...)";
		$r['description'] = htmlspecialchars_uni($r['description']);
		
		$table->construct_cell($r['name']." {$hidden}<br /><small>".$r['description']."</small>"); // download name and description
		
		$table->construct_cell("<a href=\"index.php?module=user-users&action=edit&uid=".$r['submitter_uid']."\">".htmlspecialchars_uni($r['submitter'])."</a>"); // username and useracc link
		
		$table->construct_cell("<a href=\"{$mybb->settings['bburl']}/mydownloads.php?action=view_down&did={$r['did']}\">".$lang->view."</a>, <a href=\"index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=edit_download&amp;did={$r['did']}\">".$lang->mydownloads_edit."</a>, <a href=\"index.php?module=mydownloads-downloads_categories&amp;action=delete_download&amp;type=downloads&amp;did={$r['did']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">".$lang->mydownloads_delete."</a> ".$lang->mydownloads_or." <a href=\"index.php?module=mydownloads-downloads_categories&amp;action=delete_previews&amp;type=downloads&amp;did={$r['did']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">".$lang->mydownloads_delete_previews."</a>"); // edit button
		
		$table->construct_row();
		$found = true;
	}
	if (!$found)
	{
		$table->construct_cell($lang->mydownloads_no_downloads, array('colspan' => 2)); 
		
		$table->construct_row();
	}
	
	$table->output($lang->sprintf($lang->mydownloads_downloads_in_cat, htmlspecialchars_uni($cat_name)));
}
elseif ($mybb->input['action'] == 'add') // add download page
{
	// Let's check if we exceeded the post_max_size php ini directive
	// we must check it here because $mybb->request_method is set to "get" by admin/index.php if the admin key is invalid (and it is invalid because nothing is passedy since we exceeded the limit)
	if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') { 
		flash_message($lang->sprintf($lang->mydownloads_exceeded, ini_get('post_max_size')), 'error');
		admin_redirect("index.php?module=mydownloads-downloads_categories");
	} 

	if ($mybb->request_method == "post") // add download
	{
		// Let's check if we exceeded the post_max_size php ini directive
		if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') { 
			flash_message($lang->sprintf($lang->mydownloads_exceeded, ini_get('post_max_size')), 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		} 
	
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', '*', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_no_cid, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		if (empty($mybb->input['name']))
		{
			flash_message($lang->mydownloads_no_dl_name, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}
		
		if (intval($mybb->input['hidden']) != 0 && intval($mybb->input['hidden']) != 1)
		{
			flash_message($lang->mydownloads_no_hidden, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}
		
		if (!$newpoints_installed)
			$mybb->input['points'] = 0;
			
		if ($mybb->settings['mydownloads_paypal_enabled'] != 1)
			$mybb->input['price'] = 0;
		
		$filename = basename($_FILES['download_file']['name']);
		$preview = basename($_FILES['preview_file']['name']);
		if ($preview)
			$preview = "preview_".$mybb->user['uid']."_".TIME_NOW."_".md5(uniqid(rand(),�true)).".".get_extension($preview);
		
		/*if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename))
		{
			flash_message($lang->mydownloads_upload_problem_dl_already_exists, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}*/
		
		if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview) && $preview != "")
		{
			flash_message($lang->mydownloads_upload_problem_pr_already_exists, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}
		
		$download_file = mydownloads_upload_attachment($_FILES['download_file']);
		
		if($download_file['error'] && $mybb->input['download_url'] == "")
		{
			flash_message($lang->mydownloads_upload_problem_downloadfile."<br />".$download_file['error'], 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}
		else
		{
			if($preview == "" || move_uploaded_file($_FILES['preview_file']['tmp_name'], MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview))
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				
				if ($preview) {
					$r = generate_thumbnail(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview, MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'], 'thumbnail_'.$preview, 100, 100);
					if ($r['code'] == 4) // image is too small already, set thumbnail to the image
					{
						$thumbnail = $preview;
					}
					else
						$thumbnail = 'thumbnail_'.$preview;
					
					$preview = serialize(array($preview));
				}
				else
					$thumbnail = '';
					
				//$url = $mybb->input['download_url'];
				
				// calculate MD5
				$md5 = '';
				if($download_file['filename'] != '')
				{
					$md5 = md5_file(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$download_file['filename']);
					if ($md5 === false)
						$md5 = '';
				}
				
				// everything was uploaded, insert new download into the database
				$insert_array = array(
					"name"			=> $db->escape_string($mybb->input['name']),
					"cid"			=> $cid,
					"description"	=> $db->escape_string($mybb->input['description']),
					"hidden"		=> intval($mybb->input['hidden']),
					"preview"		=> $db->escape_string($preview),
					"thumbnail"		=> $db->escape_string($thumbnail),
					"download"		=> $db->escape_string($download_file['filename']),
					"url"			=> $db->escape_string($mybb->input['download_url']),
					"filetype" 		=> $db->escape_string($download_file['filetype']),
					"filesize" 		=> $download_file['filesize'],
					"price"			=> floatval($mybb->input['price']),
					"points"		=> floatval($mybb->input['points']),
					"submitter" 	=> $db->escape_string($mybb->user['username']),
					"submitter_uid" => intval($mybb->user['uid']),
					"license"		=> $db->escape_string($mybb->input['license']),
					"version"		=> $db->escape_string($mybb->input['version']),
					"banner"		=> $db->escape_string($mybb->input['banner']),
					"md5"			=> $db->escape_string($md5),
					"date"			=> TIME_NOW
				);
				
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
					
					$insert_array['tags'] = implode(',', $tags_array);
				}
				
				// Do we want to upload a new background image?
				if(isset($_FILES['background']['name']) && $_FILES['background']['name'] != '')
				{
					$background = basename($_FILES['background']['name']);
					if($background)
						$background = "category_".TIME_NOW."_".md5(uniqid(rand(),�true)).".".get_extension($background);
					
					// Already exists?
					if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$background))
					{
						// a problem has a occurred, remove the download file and redirect the user
						@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename);
						@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".'thumbnail_'.$preview);
						
						flash_message($lang->mydownloads_background_upload_error2, 'error');
						admin_redirect("index.php?module=mydownloads-downloads_categories");
					}

					if(!move_uploaded_file($_FILES['background']['tmp_name'], MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$background))
					{
						// a problem has a occurred, remove the download file and redirect the user
						@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename);
						@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".'thumbnail_'.$preview);
				
						flash_message($lang->mydownloads_background_upload_error, 'error');
						admin_redirect("index.php?module=mydownloads-downloads_categories");
					}
					
					$insert_array['background'] = $db->escape_string($background);
				}
				
				$db->insert_query("mydownloads_downloads", $insert_array);
				
				// add a download to the category's stats
				$db->update_query('mydownloads_categories', array('downloads' => $cat['downloads']+1), 'cid='.$cid);
				
				flash_message($lang->mydownloads_download_successfully_added, 'success');
				admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
			}
			else
			{
				// a problem has a occurred, remove the download file and redirect the user
				@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$filename);
				@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".'thumbnail_'.$preview);
				
				flash_message($lang->mydownloads_upload_problem_previewfile.$_FILES['preview_file']['error'], 'error');
				admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
			}
		}
	}
	else {
		
		echo '<script type="text/javascript">
			$(document).ready(function() {
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
		</script>';
		
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'cid', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_select_category, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		$form = new Form("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=add", "post", "mydownloads", 1);
		
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

				$tags .= '<span class="span_'.$tag['categories'].'" style="'.$hidden.'">'.$form->generate_check_box('tags[]', (int)$tag['tid'], $tag['tag'], array('checked' => false, 'class' => 'tag_checkbox', 'style' => $hidden, 'id' => "cats:{$tag['categories']}"))."</span>";
			}
		}
		
		echo $form->generate_hidden_field('category', intval($mybb->input['cid']), array('id' => 'category'));
		echo $form->generate_hidden_field('cid', intval($mybb->input['cid']));

		$form_container = new FormContainer($lang->mydownloads_add_download);
		$form_container->output_row($lang->mydownloads_add_download_name, '', $form->generate_text_box('name', '', array('id' => 'name')), 'name');
		$form_container->output_row($lang->mydownloads_add_download_description, '', $form->generate_text_area('description', '', array('id' => 'description')), 'description');
		if ($mybb->settings['mydownloads_paypal_enabled'])
			$form_container->output_row($lang->mydownloads_add_download_price, $lang->mydownloads_add_download_price_description, $form->generate_text_box('price', '0', array('id' => 'price')), 'price');
		if ($newpoints_installed)
			$form_container->output_row($lang->mydownloads_add_download_points, $lang->mydownloads_add_download_points_description, $form->generate_text_box('points', '0', array('id' => 'points')), 'points');
		if ($mybb->settings['mydownloads_paypal_enabled'] == 1)
			$form_container->output_row($lang->mydownloads_add_download_hidden, $lang->mydownloads_add_download_hidden_description, $form->generate_yes_no_radio('hidden', 0, true), 'hidden');
		$form_container->output_row($lang->mydownloads_add_download_download_preview, '', $form->generate_file_upload_box("preview_file", array('style' => 'width: 200px;')), 'preview_file');
		$form_container->output_row($lang->mydownloads_add_download_download_file, '', $form->generate_file_upload_box("download_file", array('style' => 'width: 200px;')), 'download_file');
		$form_container->output_row($lang->mydownloads_add_download_url, $lang->mydownloads_add_download_url_desc, $form->generate_text_area('download_url', '', array('id' => 'download_url')), 'download_url');
		$form_container->output_row($lang->mydownloads_add_download_license, $lang->mydownloads_add_download_license_desc, $form->generate_text_area('license', '', array('id' => 'license')), 'license');
		$form_container->output_row($lang->mydownloads_add_download_version, $lang->mydownloads_add_download_version_desc, $form->generate_text_box('version', '', array('id' => 'version')), 'version');
		if($tags != '')
		{
			$form_container->output_row($lang->mydownloads_add_download_tags, $lang->mydownloads_add_download_tags_desc, $tags, 'tags');
		}
		$form_container->output_row($lang->mydownloads_add_download_banner_url, '', $form->generate_text_box('banner', '', array('id' => 'banner')), 'banner');
		$form_container->end();
	
		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->mydownloads_submit_changes);
		$buttons[] = $form->generate_reset_button($lang->mydownloads_reset_button);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'edit_download') // edit download page
{
	if ($mybb->request_method == "post") // edit download
	{
		$cid = intval($mybb->input['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', '*', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_no_cid, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		$did = intval($mybb->input['did']);
		if ($did <= 0 || (!($dl = $db->fetch_array($db->simple_select('mydownloads_downloads', '*', "did = $did")))))
		{
			flash_message($lang->mydownloads_no_did, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}
		
		if (empty($mybb->input['name']))
		{
			flash_message($lang->mydownloads_no_dl_name, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}
		
		if (intval($mybb->input['hidden']) != 0 && intval($mybb->input['hidden']) != 1)
		{
			flash_message($lang->mydownloads_no_hidden, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}
		
		if ($dl['cid'] != $cid)
		{
			// get old cat
			$old_cat = $db->fetch_array($db->simple_select('mydownloads_categories', '*', "cid = ".$dl['cid']));
			
			// subtract one download from the old category and add one to the new
			$db->update_query('mydownloads_categories', array('downloads' => $old_cat['downloads']-1), 'cid='.$dl['cid'], '', true);
			$db->update_query('mydownloads_categories', array('downloads' => $cat['downloads']+1), 'cid='.$cid, '', true);
		}
		
		$filename = basename($_FILES['download_file']['name']);
		
		$download_file = array();
		
		if (!empty($filename)) // if we are uploading a new download file
		{
			@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$dl['download']); // delete old download file
			
			$download_file = mydownloads_upload_attachment($_FILES['download_file']);
		
			if($download_file['error'])
			{
				flash_message($lang->mydownloads_upload_problem_downloadfile."<br />".$download_file['error'], 'error');
				admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
			}
			
			// calculate MD5
			$md5 = md5_file(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$download_file['filename']);
			if ($md5 === false)
				$md5 = '';
		}
		else { // we are keeping the download file
			$download_file['filename'] = $dl['download'];
			$download_file['filetype'] = $dl['filetype'];
			$download_file['filesize'] = $dl['filesize'];
			$md5 = $dl['md5'];
		}
		
		if (!$newpoints_installed)
			$mybb->input['points'] = 0;
			
		if ($mybb->settings['mydownloads_paypal_enabled'] != 1)
			$mybb->input['price'] = 0;

		// everything was uploaded, insert new download into the database
		$update_array = array(
			"name"			=> $db->escape_string($mybb->input['name']),
			"cid"			=> $cid,
			"description"	=> $db->escape_string($mybb->input['description']),
			"hidden"		=> intval($mybb->input['hidden']),
			"download"		=> $db->escape_string($download_file['filename']),
			"url"			=> $db->escape_string($mybb->input['download_url']),
			"filetype" 		=> $db->escape_string($download_file['filetype']),
			"filesize" 		=> $download_file['filesize'],
			"points"		=> floatval($mybb->input['points']),
			"price"			=> floatval($mybb->input['price']),
			"license"		=> $db->escape_string($mybb->input['license']),
			"version"		=> $db->escape_string($mybb->input['version']),
			"banner"		=> $db->escape_string($mybb->input['banner']),
			"md5"			=> $db->escape_string($md5),
		);
		
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
			
			$update_array['tags'] = implode(',', $tags_array);
		}
		
		$db->update_query("mydownloads_downloads", $update_array, "did = $did");
		
		flash_message($lang->mydownloads_download_successfully_edited, 'success');
		admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
	}
	else {
	
		$did = intval($mybb->input['did']);
		if ($did <= 0 || (!($dl = $db->fetch_array($db->simple_select('mydownloads_downloads', '*', "did = $did")))))
		{
			flash_message($lang->mydownloads_no_did, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid={$cid}");
		}
		
		$cid = intval($dl['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', 'cid', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_select_category, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		echo '<script type="text/javascript">
			$(document).ready(function() {
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
		</script>';
		
		$form = new Form("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=edit_download", "post", "mydownloads", 1);
		
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
		
		if($dl['tags'] != '')
		{
			$dl['tags'] = explode(',', $dl['tags']);
		}
		else
			$dl['tags'] = array();
		
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
				
				if(in_array($tag['tid'], $dl['tags']))
					$checked = true;
				else
					$checked = false;

				// Replace commas by underscores as classes can't seem to use commas, otherwise JS will not act properly
				$tag['categories'] = str_replace(',', '_', htmlspecialchars_uni($tag['categories']));
				
				$tag['tag'] = htmlspecialchars_uni($tag['tag']);

				$tags .= '<span class="span_'.$tag['categories'].'" style="'.$hidden.'">'.$form->generate_check_box('tags[]', (int)$tag['tid'], $tag['tag'], array('checked' => $checked, 'class' => 'tag_checkbox', 'style' => $hidden, 'id' => "cats:{$tag['categories']}"))."</span>";
			}
		}
		
		$catcache = array();
		$foundparents = array();
		
		// fetch categories
		$cat_query = $db->simple_select('mydownloads_categories', 'cid,name,disporder,parent', '', array('order_by' => 'name', 'order_dir' => 'asc'));
		while($cat = $db->fetch_array($cat_query))
		{
			$catcache[$cat['cid']] = $cat;
		}
		$db->free_result($cat_query);
		
		$categories = array();
		
		// Build tree list
		$categories[0] = $lang->mydownloads_no_parent;
		mydownloads_build_tree($categories, true);
		
		unset($catcache);

		echo $form->generate_hidden_field('did', intval($mybb->input['did']));
		
		$form_container = new FormContainer($lang->mydownloads_edit_download);
		$form_container->output_row($lang->mydownloads_username, '', build_profile_link(htmlspecialchars_uni($dl['submitter']), (int)$dl['submitter_uid']), 'name');
		$form_container->output_row($lang->mydownloads_edit_download_name, '', $form->generate_text_box('name', $dl['name'], array('id' => 'name')), 'name');
		$form_container->output_row($lang->mydownloads_edit_download_description, '', $form->generate_text_area('description', $dl['description'], array('id' => 'description')), 'description');
		if ($newpoints_installed)
			$form_container->output_row($lang->mydownloads_edit_download_points, $lang->mydownloads_edit_download_points_description, $form->generate_text_box('points', floatval($dl['points']), array('id' => 'points')), 'points');
		if ($mybb->settings['mydownloads_paypal_enabled'] == 1)
			$form_container->output_row($lang->mydownloads_edit_download_price, $lang->mydownloads_edit_download_price_description, $form->generate_text_box('price', floatval($dl['price']), array('id' => 'price')), 'price');
		$form_container->output_row($lang->mydownloads_edit_download_hidden, $lang->mydownloads_edit_download_hidden_description, $form->generate_yes_no_radio('hidden', intval($dl['hidden']), true), 'hidden');
		$form_container->output_row($lang->mydownloads_edit_download_download_file, $lang->mydownloads_edit_download_download_file_desc, $form->generate_file_upload_box("download_file", array('style' => 'width: 200px;')), 'download_file');
		$form_container->output_row($lang->mydownloads_edit_download_url, $lang->mydownloads_edit_download_url_desc, $form->generate_text_area('download_url', htmlspecialchars_uni($dl['url']), array('id' => 'download_url')), 'download_url');
		$form_container->output_row($lang->mydownloads_edit_download_category, "", $form->generate_select_box('cid', $categories, $dl['cid'], array('id' => 'category')), 'category');
		$form_container->output_row($lang->mydownloads_edit_download_license, $lang->mydownloads_edit_download_license_desc, $form->generate_text_area('license', htmlspecialchars_uni($dl['license']), array('id' => 'license')), 'license');
		$form_container->output_row($lang->mydownloads_edit_download_version, $lang->mydownloads_edit_download_version_desc, $form->generate_text_box('version', htmlspecialchars_uni($dl['version']), array('id' => 'version')), 'version');
		if($tags != '')
		{
			$form_container->output_row($lang->mydownloads_edit_download_tags, $lang->mydownloads_edit_download_tags_desc, $tags, 'tags');
		}
		$form_container->output_row($lang->mydownloads_edit_download_banner_url, '', $form->generate_text_box('banner', $dl['banner'], array('id' => 'banner')), 'banner');
		$form_container->end();
	
		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->mydownloads_submit_changes);
		$buttons[] = $form->generate_reset_button($lang->mydownloads_reset_button);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'delete_download') // delete download action
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=mydownloads-downloads_categories");
	}

	if($mybb->request_method == "post")
	{
		$did = intval($mybb->input['did']);
		if ($did <= 0 || (!($dl = $db->fetch_array($db->simple_select('mydownloads_downloads', '*', "did = $did")))))
		{
			flash_message($lang->mydownloads_no_did, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid=".intval($mybb->input['cid']));
		}
		
		$cid = intval($dl['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', '*', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_no_cid, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		// If we have any previews, we must delete them and their thumbnails
		if($dl['preview'] != '')
		{
			$dl['preview'] = unserialize($dl['preview']);
			if(!empty($dl['preview']))
			{
				foreach($dl['preview'] as $preview)
				{
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview);
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/thumbnail_".$preview);
				}
			}
		}
		
		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$dl['download']);
		
		$plugins->run_hooks('mydownloads_remove_download', $dl);
		
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
		
		$db->delete_query('mydownloads_comments', "cid IN ('".implode(',', $cids)."')");
		
		// remove a download from the category's stats
		$db->update_query('mydownloads_categories', array('downloads' => $cat['downloads']-1), 'cid='.$cid, '', true);
		
		flash_message($lang->mydownloads_download_deleted, 'success');
		admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid=".intval($cid));
	}
	else
	{
		$mybb->input['did'] = intval($mybb->input['did']);
		$form = new Form("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=delete_download&amp;did={$mybb->input['did']}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->mydownloads_down_deleteconfirm}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
		echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
		echo "</p>\n";
		echo "</div>\n";
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'delete_previews')
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=mydownloads-downloads_categories");
	}

	if($mybb->request_method == "post")
	{
		$did = intval($mybb->input['did']);
		if ($did <= 0 || (!($dl = $db->fetch_array($db->simple_select('mydownloads_downloads', '*', "did = $did")))))
		{
			flash_message($lang->mydownloads_no_did, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid=".intval($mybb->input['cid']));
		}
		
		$cid = intval($dl['cid']);
		if ($cid <= 0 || (!($cat = $db->fetch_array($db->simple_select('mydownloads_categories', '*', "cid = $cid")))))
		{
			flash_message($lang->mydownloads_no_cid, 'error');
			admin_redirect("index.php?module=mydownloads-downloads_categories");
		}
		
		// If we have any previews, we must delete them and their thumbnails
		if($dl['preview'] != '')
		{
			$dl['preview'] = unserialize($dl['preview']);
			if(!empty($dl['preview']))
			{
				foreach($dl['preview'] as $preview)
				{
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$preview);
					@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/thumbnail_".$preview);
				}
			}
		}

		$db->update_query('mydownloads_downloads', array('preview' => '', 'thumbnail' => ''), 'did='.$did);
		
		flash_message($lang->mydownloads_previews_reset, 'success');
		admin_redirect("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=browse_downloads&amp;cid=".intval($cid));
	}
	else
	{
		$mybb->input['did'] = intval($mybb->input['did']);
		$form = new Form("index.php?module=mydownloads-downloads_categories&amp;type=downloads&amp;action=delete_previews&amp;did={$mybb->input['did']}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->mydownloads_down_resetconfirm}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
		echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
		echo "</p>\n";
		echo "</div>\n";
		$form->end();
	}
}

$page->output_footer();

function mydownloads_deletecat($cid)
{
	global $mybb, $db;
	
	$query = $db->simple_select('mydownloads_downloads', '*', "cid = $cid", array('order_by' => 'did', 'order_dir' => 'ASC'));
	while($dl = $db->fetch_array($query)) {
		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$dl['preview']);
		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_downloads_dir']."/".$dl['download']);
		@unlink(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir']."/".$dl['thumbnail']); // delete thumbnail
		
		$db->delete_query('mydownloads_downloads', "did = ".$dl['did']);
		
		$rids = $cids = array();
		
		// delete rates too
		$rquery = $db->simple_select('mydownloads_ratings', 'rid', "did = ".$dl['did']);
		while($rating = $db->fetch_array($rquery)) {
			$rids[] = $rating['rid'];
		}
		
		$db->delete_query('mydownloads_ratings', "rid IN ('".implode('\',\'', $rids)."')");
		
		// delete comments too
		$cquery = $db->simple_select('mydownloads_comments', 'cid', "did = ".$dl['did']);
		while($comment = $db->fetch_array($cquery)) {
			$cids[] = $comment['cid'];
		}
		
		$db->delete_query('mydownloads_comments', "cid IN ('".implode('\',\'', $cids)."')");
	}
	
	$query = $db->simple_select('mydownloads_categories', '*', "parent = $cid", array('order_by' => 'cid', 'order_dir' => 'ASC'));
	while($cat = $db->fetch_array($query)) {
		mydownloads_deletecat($cat['cid']);
	}
	
	$db->delete_query('mydownloads_categories', "cid = $cid");
}

?>
