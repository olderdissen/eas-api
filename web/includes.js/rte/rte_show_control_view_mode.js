function rte_show_control_view_mode(html)
	{
	html.push('<table>');
		html.push('<tr>');
			html.push('<td style="padding: 0px;">');
				html.push('<div class="rte_button_b" id="rte_mode_design" onclick="rte_mode(0);">');
					html.push('<img src="' + rte_image_path + 'design.gif"> Normal');
				html.push('</div>');
			html.push('</td>');

			html.push('<td style="padding: 0px;">');
				html.push('<div class="rte_button_a" id="rte_mode_code" onclick="rte_mode(1);">');
					html.push('<img src="' + rte_image_path + 'design.gif"> HTML');
				html.push('</div>');
			html.push('</td>');

			html.push('<td style="padding: 0px;">');
				html.push('<div class="rte_button_a" id="rte_mode_preview" onclick="rte_mode(2);">');
					html.push('<img src="' + rte_image_path + 'design.gif"> Vorschau');
				html.push('</div>');
			html.push('</td style="padding: 0px;">');

			html.push('<td style="padding: 0px;">');
				html.push('<div class="rte_button_a" onclick="rte_mode(3);">');
					html.push('<img src="' + rte_image_path + 'design.gif"> Speichern');
				html.push('</div>');
			html.push('</td>');

			html.push('<td style="padding: 0px;">');
				html.push('<div class="rte_button_a" onclick="rte_mode(4);">');
					html.push('<img src="' + rte_image_path + 'design.gif"> Zurück');
				html.push('</div>');
			html.push('</td>');
		html.push('</tr>');
	html.push('</table>');
	}

