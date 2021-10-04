/***************************************************************************
 *
 *  Custom Status plugin (/jscripts/custom_status.js)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  This plugin allows users to set a custom status which appears on index, profile and posts.
 *
 ***************************************************************************/

/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

var Custom_Status = {

	init: function()
	{
		$(document).ready(function(){
			// Set spinner image
			$('#customstatus_spinner img').attr('src', spinner_image);
		});
	},

	reset_status: function()
	{
		$('custom_status_changed_success').html('');
	},

	get_new: function()
	{
		var new_status = prompt("What are you doing now?", "");
		if (new_status == '' || new_status == null)
			return false;
		else
		{
			if(use_xmlhttprequest != 1)
			{
				return true;
			}

			$('#customstatus_spinner').show();

			document.body.style.cursor = 'wait';

			$.ajax(
			{
				url: 'xmlhttp.php?action=change_custom_status&my_post_key='+my_post_key+'&status='+encodeURIComponent(new_status),
				type: 'get',
				complete: function (request, status)
				{
					var json = $.parseJSON(request.responseText);
					if(json.hasOwnProperty("error"))
					{
						$(".jGrowl").jGrowl("close");
						$.jGrowl(json.error);
					}
					else
					{
						if(json.hasOwnProperty("success"))
						{
							$('#custom_status_changed_success').html(json.success);
							$('#custom_status_changed_success').hide();
							$('#custom_status_changed_success').fadeToggle();
						}

						if(json.hasOwnProperty("status"))
						{
							$('#custom_status').html(json.status);
						}

						setInterval(function () {$('#custom_status_changed_success').fadeToggle(); }, 4000);
					}

					document.body.style.cursor = 'default';
					$('#customstatus_spinner').hide();
				}
			});
			return false;
		}
	},
};

Custom_Status.init();
