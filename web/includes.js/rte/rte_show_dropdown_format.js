function rte_show_dropdown_format(html)
	{
	html.push('<table style="background-color: #FFFFFF;" cellpadding="0" cellspacing="0" title="Formatvorlage">');
		html.push('<tr>');
			html.push('<td style="padding: 0px;">');
				html.push('<div id="format1" class="rte_dropdown_label_out" style="width: 110px;">');
					html.push('Paragraph');
				html.push('</div>');
			html.push('</td>');
			html.push('<td style="padding: 0px;">');
				html.push('<div id="format2" class="rte_dropdown_button_out">');
					html.push('<img src="' + rte_image_path + 'arrow.png" style="padding-top: 1px;">');
				html.push('</div>');
			html.push('</td>');
		html.push('</tr>');
	html.push('</table>');

	html.push('<div id="format3" class="rte_dropdown_list" style="width: 126px;">');
		for(i = 0; i < 7; i = i + 1)
			{
			html.push('<div id="formatblock" style="padding: 2px;">');
				html.push(rte_formats[i]);
			html.push('</div>');
			}
	html.push('</div>');
	}

