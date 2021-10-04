<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/admin/modules/mydownloads/tags.php)
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

$page->add_breadcrumb_item($lang->mydownloads_tags, 'index.php?module=mydownloads-tags');
			
$page->output_header($lang->mydownloads_tags);

$sub_tabs['mydownloads_tags'] = array(
	'title'			=> $lang->mydownloads_tags,
	'link'			=> 'index.php?module=mydownloads-tags',
	'description'	=> $lang->mydownloads_tags_description
);

$sub_tabs['mydownloads_tags_add'] = array(
	'title'			=> $lang->mydownloads_tags_add,
	'link'			=> 'index.php?module=mydownloads-tags&amp;action=add',
	'description'	=> $lang->mydownloads_tags_add_description
);
	
switch ($mybb->input['action'])
{
	case 'edit':
		$sub_tabs['mydownloads_tags_edit'] = array(
			'title'			=> $lang->mydownloads_tags_edit,
			'link'			=> 'index.php?module=mydownloads-tags&amp;action=edit&amp;tid='.(int)$mybb->input['tid'],
			'description'	=> $lang->mydownloads_tags_edit_description
		);

		$page->output_nav_tabs($sub_tabs, 'mydownloads_tags_edit');
	break;
	case 'add':
		$page->output_nav_tabs($sub_tabs, 'mydownloads_tags_add');
	break;
	default:
		$page->output_nav_tabs($sub_tabs, 'mydownloads_tags');
	break;
}

// Browse tags
if($mybb->input['action'] == '')
{
	// Get all categories
	$cats = array();
	$q = $db->simple_select('mydownloads_categories', 'cid,name');
	while($cat = $db->fetch_array($q))
	{
		$cats[$cat['cid']] = $cat['name'];
	}
	$db->free_result($q);
	
	// table
	$table = new Table;
	$table->construct_header($lang->mydownloads_tag, array('width' => '40%'));
	$table->construct_header($lang->mydownloads_categories, array('width' => '40%', 'class' => 'align_center'));
	$table->construct_header($lang->mydownloads_options, array('width' => '20%', 'class' => 'align_center'));
	
	$q = $db->simple_select('mydownloads_tags', '*', '', array('order_by' => 'tag', 'order_dir' => 'asc'));
	while($tag = $db->fetch_array($q))
	{		
		$table->construct_cell(htmlspecialchars_uni($tag['tag']));
		
		$names = '';
		$comma = '';
		$categories = explode(',', $tag['categories']);
		
		if(!in_array('0', $categories))
		{
			if(!empty($categories))
			{
				foreach($categories as $c)
				{
					$names .= $comma.htmlspecialchars_uni($cats[$c]);
					$comma = ', ';
				}
				
				$table->construct_cell($names, array('class' => 'align_center'));
			}
			else
			{
				$table->construct_cell('<i>'.$lang->mydownloads_tags_global.'</i>', array('class' => 'align_center'));
			}
		}
		else
		{
			$table->construct_cell('<i>'.$lang->mydownloads_tags_global.'</i>', array('class' => 'align_center'));
		}
		
		$table->construct_cell("<a href=\"index.php?module=mydownloads-tags&amp;action=edit&amp;tid={$tag['tid']}\">".$lang->mydownloads_edit."</a> ".$lang->mydownloads_or." <a href=\"index.php?module=mydownloads-tags&amp;action=delete&amp;tid={$tag['tid']}&amp;my_post_key={$mybb->post_code}\" target=\"_self\">".$lang->mydownloads_delete."</a>", array('class' => 'align_center'));
		
		$table->construct_row();
	}
	if ($table->num_rows() == 0)
	{
		$table->construct_cell($lang->mydownloads_no_tags, array('colspan' => 3));
		
		$table->construct_row();
	}
	
	$table->output($lang->mydownloads_tags);
}
elseif ($mybb->input['action'] == 'add') // Create tag action
{
	if ($mybb->request_method == "post")
	{
		if(empty($mybb->input['tag']))
		{
			flash_message($lang->mydownloads_no_tag, 'error');
			admin_redirect("index.php?module=mydownloads-tags");
		}
		
		if($mybb->input['categories'] == '' || !is_array($mybb->input['categories']) || empty($mybb->input['categories']))
		{
			$mybb->input['categories'] = array(0); // Global
		}
		
		$insert_array = array(
			'tag' => $db->escape_string($mybb->input['tag']),
			'categories' => $db->escape_string(implode(',', array_map('intval', $mybb->input['categories']))),
			'color' => $db->escape_string($mybb->input['color'])
		);
	
		$db->insert_query('mydownloads_tags', $insert_array);
		
		flash_message($lang->mydownloads_tag_added, 'success');
		admin_redirect("index.php?module=mydownloads-tags");
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
		$categories[0] = $lang->mydownloads_global;
		mydownloads_build_tree($categories);
		
		unset($catcache);
		
		$form = new Form("index.php?module=mydownloads-tags&amp;action=add", "post", "mydownloads", 1);
		
		$form_container = new FormContainer($lang->mydownloads_tags_add);
		$form_container->output_row($lang->mydownloads_tag, '', $form->generate_text_box('tag', '', array('id' => 'tag')), 'tag');
		$form_container->output_row($lang->mydownloads_color, $lang->mydownloads_color_desc, $form->generate_text_box('color', '', array('id' => 'color')), 'color');
		$form_container->output_row($lang->mydownloads_categories, '', $form->generate_select_box('categories[]', $categories, '', array('id' => 'parent', 'multiple' => true)), 'categories');
		$form_container->end();
	
		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->mydownloads_submit_changes);
		$buttons[] = $form->generate_reset_button($lang->mydownloads_reset_button);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'delete') // Delete tag action
{
	if($mybb->input['no']) // user clicked no
	{
		admin_redirect("index.php?module=mydownloads-tags");
	}

	if($mybb->request_method == "post")
	{
		$tid = intval($mybb->input['tid']);
		if ($tid <= 0 || (!($tag = $db->fetch_array($db->simple_select('mydownloads_tags', 'tid', "tid = $tid")))))
		{
			flash_message($lang->mydownloads_no_tid, 'error');
			admin_redirect("index.php?module=mydownloads-tags");
		}
		
		$db->delete_query('mydownloads_tags', 'tid='.$tid);
		
		flash_message($lang->mydownloads_tag_deleted, 'success');
		admin_redirect("index.php?module=mydownloads-tags");
	}
	else
	{
		$mybb->input['tid'] = intval($mybb->input['tid']);
		$form = new Form("index.php?module=mydownloads-tags&amp;action=delete&amp;tid={$mybb->input['tid']}&amp;my_post_key={$mybb->post_code}", 'post');
		echo "<div class=\"confirm_action\">\n";
		echo "<p>{$lang->mydownloads_tag_deleteconfirm}</p>\n";
		echo "<br />\n";
		echo "<p class=\"buttons\">\n";
		echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
		echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
		echo "</p>\n";
		echo "</div>\n";
		$form->end();
	}
}
elseif ($mybb->input['action'] == 'edit') // edit tag
{
	$tid = intval($mybb->input['tid']);
	if ($tid <= 0 || (!($tag = $db->fetch_array($db->simple_select('mydownloads_tags', '*', "tid = $tid")))))
	{
		flash_message($lang->mydownloads_no_tid, 'error');
		admin_redirect("index.php?module=mydownloads-tags");
	}
		
	if ($mybb->request_method == "post")
	{
		if (empty($mybb->input['tag']))
		{
			flash_message($lang->mydownloads_no_tag, 'error');
			admin_redirect("index.php?module=mydownloads-tags");
		}
		
		if($mybb->input['categories'] == '' || !is_array($mybb->input['categories']) || empty($mybb->input['categories']))
		{
			$mybb->input['categories'] = array(0); // Global
		}
		
		$update_array = array(
			'tag' => $db->escape_string($mybb->input['tag']),
			'categories' => $db->escape_string(implode(',', array_map('intval', $mybb->input['categories']))),
			'color' => $db->escape_string($mybb->input['color'])
		);
		
		$db->update_query('mydownloads_tags', $update_array, "tid = $tid");
		
		flash_message($lang->mydownloads_tag_edited, 'success');
		admin_redirect("index.php?module=mydownloads-tags");
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
		$categories[0] = $lang->mydownloads_global;
		mydownloads_build_tree($categories);
		
		unset($catcache);
		
		$form = new Form("index.php?module=mydownloads-tags&amp;action=edit", "post", "mydownloads", 1);
		
		echo $form->generate_hidden_field('tid', intval($mybb->input['tid']));
		
		$form_container = new FormContainer($lang->mydownloads_tags_edit);
		$form_container->output_row($lang->mydownloads_tag, '', $form->generate_text_box('tag', $tag['tag'], array('id' => 'tag')), 'tag');
		$form_container->output_row($lang->mydownloads_color, $lang->mydownloads_color_desc, $form->generate_text_box('color', $tag['color'], array('id' => 'color')), 'color');
		$form_container->output_row($lang->mydownloads_categories, '', $form->generate_select_box('categories[]', $categories, explode(',', $tag['categories']), array('id' => 'categories', 'multiple' => true)), 'categories');
		$form_container->end();
	
		$buttons = array();
		$buttons[] = $form->generate_submit_button($lang->mydownloads_submit_changes);
		$buttons[] = $form->generate_reset_button($lang->mydownloads_reset_button);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}

?>
