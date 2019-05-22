function rte_show_dropdown_size(html)
	{
	html.push('<table style="background-color: #FFFFFF;" cellpadding="0" cellspacing="0" title="Schriftgr&ouml;&szlig;e">');
		html.push('<tr>');
		html.push('<td style="padding: 0px;">');
			html.push('<div id="fontsize1" class="rte_dropdown_label_out" style="width: 25px;">');
				html.push('1');
			html.push('</div>');
		html.push('</td>');
		html.push('<td style="padding: 0px;">');
			html.push('<div id="fontsize2" class="rte_dropdown_button_out">');
					html.push('<img src="' + rte_image_path + 'arrow.png" style="padding-top: 1px;">');
				html.push('</div>');
			html.push('</td>');
		html.push('</tr>');
	html.push('</table>');

	html.push('<div id="fontsize3" class="rte_dropdown_list" style="width: 41px;">');
		for(i = 0; i < 7; i = i + 1)
			{
			html.push('<div id="fontsize" style="padding: 2px;">');
				html.push(i + 1);
			html.push('</div>');
			}
	html.push('</div>');
	}

