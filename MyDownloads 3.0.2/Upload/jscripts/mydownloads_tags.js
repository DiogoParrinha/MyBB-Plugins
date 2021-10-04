
/***************************************************************************
 *
 *   MyDownloads plugin (/jscripts/mydownloads_tags.js)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   MyDownloads adds a downloads system to MyBB.
 *
 ***************************************************************************/

$(document).ready(function() {
	$('.filter_tags').each(function() {
		$(this).click(function() {
			$('#loading').show();

			var current_url = window.location.href;

			// Get the tag ID
			var id = $(this).val();

			if($(this).is(':checked')) // If it's checked now then it was not before
			{
				// append the tag to the URL then redirect to that page :)
				window.location.replace(current_url+'&tags[]='+parseInt(id));
			}
			else // Unchecked now so it was checked before!
			{
				// remove the tag from the URL!
				window.location.replace(current_url.replace('&tags[]='+parseInt(id), ''));
			}
		});
	});
});
