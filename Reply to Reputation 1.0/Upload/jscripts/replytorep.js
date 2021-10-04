var Replytorep = {

	reply: function(rid)
	{
		if(!rid)
		{
			var rid = 0;
		}

		MyBB.popupWindow("/reputation.php?action=replytorep_reply&rid="+rid);
	},
	
	edit: function(rid)
	{
		if(!rid)
		{
			var rid = 0;
		}

		MyBB.popupWindow("/reputation.php?action=replytorep_edit&rid="+rid);
	},

	submitMessage: function(rid)
	{
		// Get form, serialize it and send it
		var datastring = $(".replytorep_"+rid).serialize();

		$.ajax({
			type: "POST",
			url: "reputation.php?modal=1",
			data: datastring,
			dataType: "html",
			success: function(data) {
				// Replace modal HTML (we have to access by class because the modals are appended to the end of the body, and when we get by class we get the last element of that class - which is what we want)
				$(".modal_replytorep_"+rid).fadeOut('slow', function() {
					$(".modal_replytorep_"+rid).html(data);
					$(".modal_replytorep_"+rid).fadeIn('slow');
					$(".modal").fadeIn('slow');
				});
			},
			error: function(){
				  alert(lang.unknown_error);
			}
		});

		return false;
	},
};
