<?php
/***************************************************************************
 *
 *   MyDownloads plugin (/inc/languages/english/mydownloads.php)
 *	 Author: Diogo Parrinha
 *   Copyright: � 2009-2010 Diogo Parrinha
 *   
 *
 *
 *   MyDownloads adds a downloads system to MyBB.
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

$l['mydownloads'] = 'MyDownloads';
$l['mydownloads_no_categories'] = "No categories have been found.";
$l['mydownloads_no_sub_categories'] = "No sub categories have been found.";
$l['mydownloads_categories'] = "Categories";
$l['mydownloads_select_category'] = "Select a category";
$l['mydownloads_no_permissions'] = "You either don't have permission to view this submission or it was set to hidden for administrative reasons.<br/>If you are the author of this submission, you can contact an administrator for more details.";
$l['mydownloads_no_cid'] = "The category you have selected is not valid.";
$l['mydownloads_no_did'] = "The download you have selected is not valid.";
$l['mydownloads_no_downloads'] = "No downloads found.";
$l['mydownloads_download_points'] = "Points";
$l['mydownloads_download_price'] = "Price";
$l['mydownloads_download_name'] = "Name";
$l['mydownloads_download_preview'] = "Preview";
$l['mydownloads_download_description'] = "Description";
$l['mydownloads_number_downloads'] = "Downloads";
$l['mydownloads_downloaded'] = "<strong>Downloads:</strong> {1}";
$l['mydownloads_viewed'] = "<br /><strong>Views:</strong> {1}";
$l['mydownloads_download_free'] = "Download for free";
$l['mydownloads_purchase'] = "Purchase download with {1}";
$l['mydownloads_download'] = "Download";
$l['mydownloads_not_enough_money'] = "You do not have enough points to purchase this download.";
$l['mydownloads_download_purchased'] = "You have successfully purchased this download.";
$l['mydownloads_download_purchased_title'] = "Download purchased";
$l['mydownloads_default_license'] = "The author hasn't set a license for this download.";
$l['mydownloads_your_money'] = "You have";
$l['mydownloads_closed'] = "The downloads page is temporarily closed.";
$l['mydownloads_downloads_number'] = "{1} downloads";
$l['mydownloads_your_rate'] = "Your rating";
$l['mydownloads_total_rate'] = "Average rating";
$l['mydownloads_one_star'] = "1 star out of 5";
$l['mydownloads_two_stars'] = "2 stars out of 5";
$l['mydownloads_three_stars'] = "3 stars out of 5";
$l['mydownloads_four_stars'] = "3 stars out of 5";
$l['mydownloads_five_stars'] = "5 stars out of 5";
$l['mydownloads_download_rate'] = "Rating";
$l['mydownloads_na'] = "N/A";
$l['mydownloads_download_image'] = "Download image";
$l['mydownloads_purchase_image'] = "Purchase image with {1}";
$l['mydownloads_purchase_url'] = "View download link";
$l['mydownloads_delete_confirm'] = "Are you sure you want to delete this comment?";
$l['mydownloads_message'] = "Message";
$l['mydownloads_comment'] = "Leave a comment";
$l['mydownloads_submit_comment'] = "Submit";
$l['mydownloads_log_in_register'] = "To leave a comment you must log in.";
$l['mydownloads_download_views'] = "Views";
$l['mydownloads_download_comments'] = "Comments";
$l['mydownloads_download_delete_comment'] = "Delete";
$l['mydownloads_download_edit_comment'] = "Edit";
$l['mydownloads_edit_comment'] = "Edit Comment";
$l['mydownloads_no_points_set'] = "No points";
$l['mydownloads_no_price_set'] = "No price";
$l['mydownloads_no_money'] = "No money";
/*$l['mydownloads_download_creator'] = 'Creator';
$l['mydownloads_download_creator_none'] = 'No developer/creator has been set';*/
$l['mydownloads_download_submitter'] = 'Submitted by';
//$l['mydownloads_creator_desc'] = 'The developer/creator of this download';
$l['mydownloads_submitter_desc'] = 'The user that has submitted this download.';
$l['mydownloads_version'] = 'Version';
$l['mydownloads_license'] = 'License';
$l['mydownloads_by_user'] = '';
$l['mydownloads_by_username'] = 'by <a href="{1}/{2}">{3}</a> at {4} on {5}';
$l['mydownloads_confirm_delete'] = 'Confirm that you want to delete the selected comment.<br /><form action="{1}/mydownloads/comment_download.php" method="post" ">
					<input type="hidden" name="my_post_key" value="{2}" />
					<input type="hidden" name="cid" value="{3}" />
					<input type="hidden" name="action" value="delete_comment" />
					<input type="submit" value="Delete" class="button" />
			</form>';
			
$l['mydownloads_download_comment_deleted'] = "You have successfully deleted this comment.";
$l['mydownloads_download_comment_deleted_title'] = "Comment deleted";
$l['mydownloads_download_comment_edited'] = 'Comment edited successfully.';

$l['mydownloads_download_commented'] = "You have successfully commented this download.";
$l['mydownloads_download_commented_title'] = "Download commented";

$l['mydownloads_no_did'] = "The download you have selected is not valid.";
$l['mydownloads_no_cid'] = "The category you have selected is not valid.";
$l['mydownloads_no_comid'] = "The comment you have selected is not valid.";
$l['mydownloads_flood_check'] = "You are trying to post a comment too quickly after posting a previous comment. Please wait {1} more second(s)."; // copied from MyBB :P

$l['mydownloads_download_rate'] = "Rate";
$l['mydownloads_error_invalidrating'] = "You have selected an invalid rating for this download.";
$l['mydownloads_already_rated'] = "You have already rated this download.";

$l['mydownloads_download_rated'] = "You have successfully rated this download.";
$l['mydownloads_download_rated_title'] = "Download rated";

$l['mydownloads_your_rate'] = "Your rating";
$l['mydownloads_total_rate'] = "Average rating";

$l['mydownloads_sub_categories'] = 'Sub Categories';
$l['mydownloads_sub_categories2'] = '{1} Sub Categories and {2} downloads';

$l['mydownloads_categories_main'] = 'Main Page';

$l['mydownloads_sub_categories_in_cat'] = 'Categories whose parent category is "{1}"';
$l['mydownloads_submit_download_in_category'] = 'Submitting download in category "{1}"';
$l['mydownloads_submit_download'] = 'Submit Download';

$l['mydownloads_submit_download_name'] = 'Name';
$l['mydownloads_submit_download_name_desc'] = 'The name of the download.';

$l['mydownloads_submit_download_description'] = 'Description';
$l['mydownloads_submit_download_description_desc'] = 'Enter a description for this download.';

$l['mydownloads_submit_download_points'] = 'Points';
$l['mydownloads_submit_download_points_desc'] = 'Enter the cost of this download in points. This is the amount of points users must pay to download the uploaded file. Everytime this download is purchased, you get the amount of points you enter here multiplied by the percentage the administrator has set in settings.';
$l['mydownloads_submit_download_points_desc_mp_not_installed'] = '<span style="color: #FF0000;"><strong>NewPoints feature is disabled. Leave blank.</strong></span>';

$l['mydownloads_submit_download_price'] = 'Price';
$l['mydownloads_submit_download_price_desc'] = 'Enter how much money users must pay to download this file - via PayPal';
$l['mydownloads_submit_download_price_desc_paypal_deactivated'] = '<span style="color: #FF0000;"><strong>PayPal feature is disabled. Leave blank.</strong></span>';

$l['mydownloads_submit_download_preview'] = 'Preview image';
$l['mydownloads_submit_download_preview_desc'] = 'Select the preview image to upload. Leave blank if you do not want to show a preview image.';

$l['mydownloads_submit_download_download'] = 'Download file';
$l['mydownloads_submit_download_download_desc'] = 'Select the download file to upload.';

$l['mydownloads_submit_download_license'] = 'License';
$l['mydownloads_submit_download_license_desc'] = 'Enter a license for this download. Leave blank if you do not want to show a license.';

$l['mydownloads_submit_download_version'] = 'Version';
$l['mydownloads_submit_download_version_desc'] = 'Enter the version of the download. Leave blank if there is no version.';

$l['mydownloads_upload_problem_downloadfile'] = "The following problem has occurred when uploading the download file: ";
$l['mydownloads_upload_problem_previewfile'] = "The following problem has occurred when uploading the preview file: ";

$l['mydownloads_submit_download_email'] = 'PayPal Email';
$l['mydownloads_submit_download_email_desc'] = 'Enter your PayPal email for which payments will be sent. Leave blank if you want the payments to be sent to the Administrator. If you fill in this field but leave the price 0, the donate button will appear instead.';

$l['mydownloads_no_dl_name'] = "You haven't entered a name for the download.";
$l['mydownloads_no_download_file'] = "You haven't selected the download file to be uploaded.";
$l['mydownloads_no_description'] = "You haven't entered a description.";
$l['mydownloads_no_hidden'] = "You haven't entered a setting for 'hidden'.";
$l['mydownloads_no_cid'] = "The category you have selected is not valid.";
$l['mydownloads_no_did'] = "The download you have selected is not valid.";

$l['mydownloads_download_successfully_added'] = "You have successfully submitted a new download which is now waiting to be approved.";
$l['mydownloads_download_successfully_added_title'] = 'Download submitted';

$l['mydownloads_download_successfully_added_auto'] = "You have successfully submitted a new download which has been automatically approved.";
$l['mydownloads_download_successfully_added_title_auto'] = 'Download submitted and approved';

$l['mydownloads_download_successfully_edited'] = "You have successfully edited a download which is now waiting to be approved.";
$l['mydownloads_download_successfully_edited_title'] = 'Download Edited';

$l['mydownloads_download_successfully_edited_auto'] = "You have successfully edited a download which has been automatically approved.";
$l['mydownloads_download_successfully_edited_title_auto'] = 'Download edited and approved';

$l['mydownloads_upload_problem_dl_already_exists'] = "A download file with the same name has already been uploaded";
$l['mydownloads_upload_problem_pr_already_exists'] = "A preview file with the same name has already been uploaded";

$l['mydownloads_no_version_set'] = 'No version has been set.';
$l['mydownloads_enter_a_comment'] = 'Enter a comment please.';

$l['mydownloads_download_url'] = 'View download link(s)';
$l['mydownloads_purchase_url'] = 'Purchase download link(s) with {1}';
$l['mydownloads_url_download'] = "The author of this download has decided to enter download links instead of uploading a file.<br />Download links:<br />{1}";
$l['mydownloads_url_download_title'] = "Download file";
$l['mydownloads_exceeded'] = 'PHP upload limit exceeded. Maximum is {1}.';
$l['mydownloads_md5'] = 'MD5';
$l['mydownloads_latest_submissions'] = 'Latest Download Submissions';
$l['mydownloads_submit_date'] = 'Submit Date';
$l['mydownloads_report_download'] = 'Report Download';
$l['mydownloads_report_download_reason'] = 'Reason';
$l['mydownloads_report_download_reason_desc'] = 'Enter a reason for reporting this download.';
$l['mydownloads_submit'] = 'Submit';
$l['mydownloads_report_breacrumb'] = 'Reporting download ';
$l['mydownloads_reported_message'] = 'You have successfully reported the selected download.';
$l['mydownloads_reported_title'] = 'Download Reported';
$l['mydownloads_empty_reason'] = 'You must specify a reason for reporting the selected download.';
$l['mydownloads_options'] = 'Options';
$l['mydownloads_my_submissions'] = 'My Download Submissions <span class="smalltext">(only approved downloads are shown)</span>';
$l['mydownloads_user_submissions'] = '{1}\'s Download Submissions <span class="smalltext">(only approved downloads are shown)</span>';
$l['mydownloads_user_history'] = '{1}\'s Download History';
$l['mydownloads_date'] = 'Date';
$l['mydownloads_edit'] = 'Edit';
$l['mydownloads_delete'] = 'Delete';
$l['mydownloads_mysubmissions'] = 'My Submissions';
$l['mydownloads_user_mysubmissions'] = '{1}\'s Download Submissions';
$l['mydownloads_no_submissions'] = 'No download submissions found.';
$l['mydownloads_download_category'] = 'Category';
$l['mydownloads_status'] = 'Status';
$l['mydownloads_active'] = '<strong>Active</strong>';
$l['mydownloads_hidden'] = '<em>Hidden</em>';
$l['mydownloads_download_deleted_title'] = 'Download Deleted';
$l['mydownloads_download_deleted'] = 'You have successfully deleted the selected download.';
$l['mydownloads_delete'] = 'Delete';
$l['mydownloads_delete_download_confirm'] = 'Are you sure you want to delete download "{1}"?';
$l['mydownloads_delete_download'] = 'Delete Download Submission';
$l['mydownloads_delete_download_breadcrumb'] = 'Deleting download';
$l['mydownloads_edit_download'] = 'Edit Download';
$l['mydownloads_editing_download'] = 'Editing Download {1}';
$l['mydownloads_edit_download_preview_desc'] = 'Select the preview image to upload. (Leave empty to not change this field)';
$l['mydownloads_edit_download_download_desc'] = 'Select the download file to upload. (Leave empty to not change this field)';
$l['mydownloads_my_submissions_profile'] = 'Download Submissions:';
$l['mydownloads_view_submissions'] = 'View Submissions';
$l['mydownloads_view_history'] = 'Download History';
$l['mydownloads_being_updated'] = '<em>Being Updated</em>';
$l['mydownloads_being_updated2'] = "<em>(Being Updated)</em>";
$l['mydownloads_being_updated_error'] = 'This download has been updated is currently under approval, you cannot modify it until it has been approved or unapproved.';
$l['mydownloads_being_updated_notice'] = 'This download has been edited and it is currently under approval, in the mean time, you can download the old files.';
$l['mydownloads_invalid_receiver_email'] = 'You have entered an invalid email.';

$l['mydownloads_stats'] = 'Statistics';
$l['mydownloads_stats_of'] = 'Statistics of category {1}';
$l['mydownloads_most_rated'] = 'Most Rated';
$l['mydownloads_most_downloaded'] = 'Most Downloaded';
$l['mydownloads_most_viewed'] = 'Most Viewed';

$l['mydownloads_one_star'] = "1 star out of 5";
$l['mydownloads_two_stars'] = "2 stars out of 5";
$l['mydownloads_three_stars'] = "3 stars out of 5";
$l['mydownloads_four_stars'] = "4 stars out of 5";
$l['mydownloads_five_stars'] = "5 stars out of 5";
$l['mydownloads_ratings_update_error'] = 'Error rating download:';
$l['mydownloads_cannot_rate_own'] = 'You cannot rate your own downloads.';

$l['mydownloads_asc'] = 'asc';
$l['mydownloads_desc'] = 'desc';

$l['mydownloads_search'] = 'Download Search';
$l['mydownloads_search_results'] = 'Search Results: ';
$l['mydownloads_invalid_url'] = 'The following URL is invalid: ';
$l['mydownloads_submit_download_urls'] = 'Download URLs';
$l['mydownloads_submit_download_urls_desc'] = 'This is an optional field. You can enter download URLs rather than uploading a file. If this is not empty, the selected file will not be uploaded.';
$l['mydownloads_in'] = 'in';
$l['mydownloads_all_categories'] = 'All Categories';

$l['mydownloads_manage_previews'] = 'Manage Previews';
$l['mydownloads_previews'] = 'Previews';
$l['mydownloads_cover'] = 'Cover';
$l['mydownloads_set_cover'] = 'Set Cover';
$l['mydownloads_max_previews'] = 'You can upload a maximum of {1} previews to each download.';
$l['mydownloads_submit_preview'] = 'Submit Preview';
$l['mydownloads_no_previews'] = 'No previews available.';
$l['mydownloads_max_previews_error'] = 'You cannot upload anymore previews because you have already reached your limit for this download item.';
$l['mydownloads_delete_preview_confirm'] = 'Are you sure you want to delete the selected preview?';
$l['mydownloads_preview_submitted'] = 'Preview submitted successfully.';
$l['mydownloads_cover_updated'] = 'Cover updated successfully.';
$l['mydownloads_preview_deleted'] = 'Preview deleted successfully.';

$l['mydownloads_max_res'] = 'Your previews are limited to {1}px of width and {2}px of height.';
$l['mydownloads_max_width'] = 'Your preview\'s width is bigger than {1}px.';
$l['mydownloads_max_height'] = 'Your preview\'s height is bigger than {1}px.';

$l['mydownloads_error_attachtype'] = "The type of file that you attached is not allowed. Please remove the attachment or choose a different type.";
$l['mydownloads_error_attachsize'] = "The file you attached is too large. The maximum size for that type of file is {1} kilobytes.";
$l['mydownloads_error_uploadsize'] = "The size of the uploaded file is too large.";
$l['mydownloads_error_uploadfailed'] = "The file upload failed. Please choose a valid file and try again. ";
$l['mydownloads_error_uploadfailed_detail'] = "Error details: ";
$l['mydownloads_error_uploadfailed_php1'] = "PHP returned: Uploaded file exceeded upload_max_filesize directive in php.ini.  Please contact your forum administrator with this error.";
$l['mydownloads_error_uploadfailed_php2'] = "The uploaded file exceeded the maximum file size specified.";
$l['mydownloads_error_uploadfailed_php3'] = "The uploaded file was only partially uploaded.";
$l['mydownloads_error_uploadfailed_php4'] = "No file was uploaded.";
$l['mydownloads_error_uploadfailed_php6'] = "PHP returned: Missing a temporary folder.  Please contact your forum administrator with this error.";
$l['mydownloads_error_uploadfailed_php7'] = "PHP returned: Failed to write the file to disk.  Please contact your forum administrator with this error.";
$l['mydownloads_error_uploadfailed_phpx'] = "PHP returned error code: {1}.  Please contact your forum administrator with this error.";
$l['mydownloads_error_uploadfailed_nothingtomove'] = "An invalid file was specified, so the uploaded file could not be moved to its destination.";
$l['mydownloads_error_uploadfailed_movefailed'] = "There was a problem moving the uploaded file to its destination.";
$l['mydownloads_error_uploadfailed_lost'] = "The attachment could not be found on the server.";
$l['mydownloads_header_reports'] = 'There are {1} unread download reports.';
$l['mydownloads_select_category'] = 'Select a Category';
$l['mydownloads_category'] = 'Category';

$l['mydownloads_preview_empty'] = 'The preview field is empty.';
$l['mydownloads_invalid_extension'] = 'The preview file can only have one the following extensions: jpeg, png or gif.';
$l['mydownloads_require_preview'] = 'A preview file is required.';

$l['mydownloads_add_previews'] = 'Add Previews';
$l['mydownloads_add_previews_desc'] = 'Drag and drop previews below to start the uploading process.';
$l['mydownloads_cancel'] = 'Cancel';
$l['mydownloads_start'] = 'Start';
$l['mydownloads_delete'] = 'Delete';
$l['mydownloads_error'] = 'Error';
$l['mydownloads_success'] = 'Success';
$l['mydownloads_processing'] = 'Processing...';
$l['mydownloads_use_legacy'] = 'Please use the legacy version.';
$l['mydownloads_start_upload'] = 'Start Upload';
$l['mydownloads_cancel_upload'] = 'Cancel Upload';
$l['mydownloads_switch_legacy'] = 'Switch to Legacy';
$l['mydownloads_switch_dragdrop'] = 'Switch to Drag &amp; Drop';

$l['mydownloads_new_comment'] = 'A new comment was posted by {1} to a download of yours.';
$l['mydownloads_cant_edit_comment'] = 'You can no longer edit this comment because the edit time has passed.';

$l['myalerts_setting_mydownloads_new_comment'] = 'Receive alert when somebody comments your downloads?';
$l['mydownloads_last_updated'] = 'Last Updated';
$l['mydownloads_go_to_all_downloads'] = 'Go to All Downloads';

$l['mydownloads_tags'] = 'Tags';
$l['mydownloads_submit_download_tags'] = 'Tags';
$l['mydownloads_submit_download_tags_desc'] = 'Select the tags associated with your download.';
$l['mydownloads_filter_by_tags'] = 'Filter results by tags.';

$l['mydownloads_invalid_points'] = 'You selected an invalid amount of points.';

$l['mydownloads_history'] = 'Download History';
$l['mydownloads_submit_download_bannerurl'] = 'Banner URL (Optional)';
$l['mydownloads_submit_download_bannerurl_desc'] = 'You can specify a banner URL for your download. For best results use 1500x300 resolution as minimum or same aspect ratio. Accepted extensions: bmp, gif, png, jpeg.';
$l['mydownloads_invalid_banner'] = 'You entered an invalid banner. Accepted extensions: bmp, gif, png, jpeg.';

$l['mydownloads_download_with'] = 'Download with:';