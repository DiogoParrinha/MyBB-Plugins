/***************************************************************************
 *
 *	MyChatbox plugin (/jscripts/mychatbox.js)
 *	Author: Diogo Parrinha
 *	Copyright: (c) 2021 Diogo Parrinha
 *
 *	Adds a chatbox to MyBB.
 *
 ***************************************************************************/

$(document).ready(function() {
	if($("#mychatbox_messages").length)
	{
		$(document).on("submit", "#mychatbox_post_form", function(e){
			e.preventDefault();

			// Spinner!
			 $('#mychatbox_spinner').css('display', 'inline');

			$.ajax(
			{
				url: 'xmlhttp.php?action=mychatbox',
				data: 'post=1&my_post_key='+my_post_key+'&message='+$('#mychatbox_message').val(),
				type: 'post',
				complete: function (request, status)
				{
					var json = $.parseJSON(request.responseText);
					if(typeof json == 'object')
					{
						if(json.hasOwnProperty("errors"))
						{
							$.each(json.errors, function(i, message)
							{
								$.jGrowl(message);
							});

							 $('#mychatbox_spinner').hide();
							return false;
						}

						if(json.hasOwnProperty("success"))
						{
							$.jGrowl(json.success);
						}

						// Update all messages
						if(json.hasOwnProperty("messages"))
						{
							$('#mychatbox_messages').html(json.messages);
						}

						$('#mychatbox_spinner').hide();

						$("#mychatbox_messages").animate({ scrollTop: $("#mychatbox_messages").prop("scrollHeight") }, 1000);

						$('#mychatbox_message').val('');
					}
				}
			});
		});

		$(document).on("click", ".mychatbox_delete", function(e){
			var c = confirm(lang.mychatbox_confirm_delete);

			if(c == true)
			{
				// Take ID out of the id attribute
				id = $(this).attr('id');
				id = id.replace( /[^\d.]/g, '');

				var contents = $('#mychatbox_message_'+id).children().eq(2).html();
				 $('#mychatbox_message_'+id).children().eq(2).html('<div id="mychatbox_spinner_delete" style="display: inline">'+spinner +'</div>');

				$.ajax(
				{
					url: 'xmlhttp.php?action=mychatbox',
					data: 'delete='+id+'&my_post_key='+my_post_key,
					type: 'post',
					complete: function (request, status)
					{
						var json = $.parseJSON(request.responseText);
						if(typeof json == 'object')
						{
							if(json.hasOwnProperty("errors"))
							{
								$.each(json.errors, function(i, message)
								{
									$.jGrowl(message);
								});

								$('#mychatbox_message_'+id).children().eq(2).html(contents);
								return false;
							}

							if(json.hasOwnProperty("success"))
							{
								$.jGrowl(json.success);
							}

							// Update all messages
							if(json.hasOwnProperty("messages"))
							{
								$('#mychatbox_messages').html(json.messages);
							}

							$('#mychatbox_message').val('');
						}
					}
				});
			}
		});

		$(document).on("click", ".mychatbox_edit", function(e){
			var c = prompt(lang.mychatbox_enter_new_message);

			if(c != null)
			{
				// Take ID out of the id attribute
				id = $(this).attr('id');
				id = id.replace( /[^\d.]/g, '');

				var contents = $('#mychatbox_message_'+id).children().eq(2).html();
				$('#mychatbox_message_'+id).children().eq(2).html('<div id="mychatbox_spinner_edit" style="display: inline">'+spinner +'</div>');

				$.ajax(
				{
					url: 'xmlhttp.php?action=mychatbox',
					data: 'edit='+id+'&my_post_key='+my_post_key+'&message='+c,
					type: 'post',
					complete: function (request, status)
					{
						var json = $.parseJSON(request.responseText);
						if(typeof json == 'object')
						{
							if(json.hasOwnProperty("errors"))
							{
								$.each(json.errors, function(i, message)
								{
									$.jGrowl(message);
								});

								$('#mychatbox_message_'+id).children().eq(2).html(contents);
								return false;
							}

							if(json.hasOwnProperty("success"))
							{
								$.jGrowl(json.success);
							}

							if(json.hasOwnProperty("message"))
							{
								$('#mychatbox_message_'+id).children().eq(2).html(json.message);
							}
						}
					}
				});
			}
		});

		function refresh()
		{
			$.ajax(
			{
				url: 'xmlhttp.php?action=mychatbox',
				type: 'get',
				complete: function (request, status)
				{
					var json = $.parseJSON(request.responseText);
					if(typeof json == 'object')
					{
						if(json.hasOwnProperty("errors"))
						{
							$.each(json.errors, function(i, message)
							{
								$.jGrowl(message);
							});
							return false;
						}

						// Update all messages
						if(json.hasOwnProperty("messages"))
						{
							$('#mychatbox_messages').html(json.messages);
						}
					}
				}
			});
		}

		// Auto refresh every X seconds
		setInterval(function(){
			refresh();
		}, mychatbox_refresh*1000);
	}
});
