<?php

/***************************************************************************
 *
 *  Hide Content plugin (/inc/plugins/hidecontent.php)
 *  Author: ZLight Software
 *  Copyright: � 2014 ZLight Software
 *  
 *  
 *  License: licence.txt
 *
 *  Adds MyCode which hides content inside posts.
 *
 ***************************************************************************/
 
/****************************************************************************
* You are NOT authorized to share/re-distribute this plugin with ANYONE without my express permission.
* You MUST NOT give credits to anyone besides ZLight Software or the name of the developer of the plugin.
* You MUST NOT remove the license file or any conditions/rules that you may find in the included PHP files.
* The author is NOT responsible for any damaged caused by this plugin.
* 
* By downloading/installing this module you agree with the conditions stated above.
****************************************************************************/

$l['hidecontent'] = 'Hide Content';
$l['hidecontent_not_paid'] = "<div class=\"hidecontent\"><strong>You have not unlocked this post's content yet. Points required: {1}</strong><br /><form action=\"misc.php?action=hidecontent\" method=\"POST\"><input type=\"hidden\" name=\"my_post_key\" value=\"{2}\" /><input type=\"hidden\" name=\"pid\" value=\"{3}\" /><input type=\"submit\" name=\"submit\" value=\"Unlock\" onclick=\"javascript: return confirm('Are you sure you want to pay {1} to view the content?');\" /></form></div>";
$l['hidecontent_not_replied'] = "<div class=\"hidecontent\"><strong>You have not unlocked this post's content yet. Please reply to this thread to unlock the content.</strong></div>";
$l['hidecontent_not_both'] = "<div class=\"hidecontent\"><strong>You have not unlocked this post's content yet. Points required: {1}</strong><br /><form action=\"misc.php?action=hidecontent\" method=\"POST\"><input type=\"hidden\" name=\"my_post_key\" value=\"{2}\" /><input type=\"hidden\" name=\"pid\" value=\"{3}\" /><input type=\"submit\" name=\"submit\" value=\"Unlock\" onclick=\"javascript: return confirm('Are you sure you want to pay {1} to view the content?');\" /></form><br /><strong>Alternatively, reply to this thread to unlock the hidden content on this post.</strong></div>";
$l['hidecontent_not_none'] = "<div class=\"hidecontent\"><strong>You have not unlocked this post's content yet. Unfortunately, the administrator has disabled all means of unlocking it.</strong></div>";
$l['hidecontent_content_unlocked'] = "<div><strong>Content Unlocked:</strong><br />{1}</div>";
$l['hidecontent_bad_content'] = 'Content is hidden! Please check original post to view it.';
$l['hidecontent_stripped_content'] = 'Content has been stripped. Go to the quoted post to view the content.';
$l['hidecontent_unlocked_content'] = 'Content unlocked successfully.';
$l['hidecontent_not_enough'] = 'You do not have enough points.';
$l['hidecontent_invalid_post'] = 'You have selected an invalid post.';
$l['hidecontent_paid_already'] = 'You have already unlocked this post\'s content';
$l['hidecontent_alert'] = '{1} unlocked your content.';
$l['hidecontent_log'] = '{1}-{2}-{3}-{4}-(to username, to userid, postid, amount)';

?>