<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/fix_thumbnails.php)
 *	 Author: Diogo Parrinha
 *   Copyright: © 2021 Diogo Parrinha
 *
 *
 *
 *   MyDownloads adds a downloads system to MyBB.
 *
 ***************************************************************************/


///// Any downloads that had the bug where the thumbnail field would be empty, will be fixed after running this script.

define("IN_MYBB", 1);
require_once "./inc/init.php";

echo "Fixing thumbnails...";

$q = $db->simple_select('mydownloads_downloads', '*', 'thumbnail=\'\' AND preview!=\'\'');
while($d = $db->fetch_array($q))
{
	$d['preview'] = unserialize($d['preview']);
	if(!empty($d['preview']))
	{
		// Update thumbnail
		if(file_exists(MYBB_ROOT.$mybb->settings['mydownloads_previews_dir'].'/thumbnail_'.$d['preview'][0]))
		{
			$d['thumbnail'] = 'thumbnail_'.$db->escape_string($d['preview'][0]);
		}
		else
		{
			$d['thumbnail'] = $db->escape_string($d['preview'][0]);
		}

		$db->update_query('mydownloads_downloads', array('thumbnail' => $db->escape_string($d['thumbnail'])), 'did='.(int)$d['did']);
	}
}

echo "Done!<br />";
echo "Thumbnails fixed!<br />";
exit;

?>
