/***************************************************************************
 *
 *  My Advertisements plugin (/jscripts/myadvertisements.js)
 *  Author: Diogo Parrinha
 *  Copyright: (c) 2021 Diogo Parrinha
 *
 *  License: license.txt
 *
 *  This plugin adds advertizements zones to your forum.
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

var MyAdvertisements = {

	do_click: function(aid)
	{
		if(use_xmlhttprequest != 1)
		{
			return true;
		}

		$.ajax({
			type: "POST",
			url: 'xmlhttp.php?action=do_click&my_post_key='+my_post_key,
			data: { aid: parseInt(aid) }
		});

		return false;
	}
};
