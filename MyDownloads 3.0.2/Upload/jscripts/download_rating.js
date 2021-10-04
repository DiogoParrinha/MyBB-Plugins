
/***************************************************************************
 *
 *   MyDownloads plugin (/jscripts/download_rating.js)
 *	 Author: Diogo Parrinha
 *   Copyright: (c) 2021 Diogo Parrinha
 *
 *   MyDownloads adds a downloads system to MyBB.
 *
 ***************************************************************************/

var Download_Rating = {
	init: function()
	{
		$(".inline_rating ul li a").each(function()
		{
			if($(this).parent().parent().hasClass("star_rating_notrated"))
			{
				$(this).unbind().click(function() {
					var parameterString = $(this).attr("href").replace(/.*\?(.*)/, "$1");
					return Download_Rating.add_rating(parameterString);
				});
			}
			else
			{
				var element = $(this);
				element.attr("onclick", "return false;");
				element.css("cursor", "default");
				var did = element.attr("href").replace(/.*\?(.*)/, "$1").match(/did=(.*)&(.*)&/)[1];
				element.attr("title", $("#current_rating_"+did).text());
			}
		});
	},

	add_rating: function(parameterString)
	{
		var did = parameterString.match(/did=(.*)&(.*)&/)[1];
		var rating = parameterString.match(/rating=(.*)&(.*)/)[1];
		$.ajax(
		{
			url: 'mydownloads/ratedownload.php?ajax=1&my_post_key='+my_post_key+'&did='+did+'&rating='+rating,
			async: true,
			method: 'post',
			dataType: 'json',
	        complete: function (request)
	        {
	        	Download_Rating.rating_added(request, did);
	        }
		});
		return false;
	},

	rating_added: function(request, element_id)
	{
		var json = $.parseJSON(request.responseText);
		if(json.hasOwnProperty("errors"))
		{
			$.each(json.errors, function(i, error)
			{
				$.jGrowl(lang.mydownloads_ratings_update_error + ' ' + error);
			});
		}
		else if(json.hasOwnProperty("success"))
		{
			var element = $("#rating_download_"+element_id);
			element.parent().before(element.next());
			element.removeClass("star_rating_notrated");

			$.jGrowl(json.success);
			if(json.hasOwnProperty("average"))
			{
				$("#current_rating_"+element_id).html(json.average);
			}

			var element = $('#rating_download_'+element_id);
			$(element).find('li a').each(function()
			{
				$(this).attr("onclick", "return false;");
				$(this).css("cursor", "default");
				var did = $(this).attr("href").replace(/.*\?(.*)/, "$1").match(/did=(.*)&(.*)&/)[1];
				$(this).attr("title", $("#current_rating_"+did).text());
			});

			$("#current_rating_"+element_id).css("width", json.width+"%");
		}
	}
};

if(use_xmlhttprequest == 1)
{
	$(function()
	{
		Download_Rating.init();
	});
}
