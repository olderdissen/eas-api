function rte_show_dropdown_color(html)
	{
	// w = 23 + 9

	html.push('<table style="height: 20px; width: 33px;" border="0" cellspacing="0" cellpadding="0" title="Schriftfarbe">');
		html.push('<tr>');
			html.push('<td style="padding: 0px;">');
				html.push('<div style="height: 20px; width: 23px;">');
					html.push('<div id="fontcolor1" class="rte_dropdown_button_out_x" style="height: 20px; position: absolute; width: 23px;">');
						html.push('<img src="' + rte_image_path + 'fontcolor.png" style="left: 3px; position: relative; top: 2px;">');
						html.push('<img id="fontcolor4" src="' + rte_image_path + 'fontcolor2.png" style="left: 2px; position: relative; top: -7px;">');
					html.push('</div>');
				html.push('</div>');
			html.push('</td>');
			html.push('<td style="padding: 0px;">');
				html.push('<div style="height: 20px; width: 9px;">');
					html.push('<div id="fontcolor2" class="rte_dropdown_arrow_out" style="height: 20px; position: absolute; width: 9px;">');
						html.push('<img src="' + rte_image_path + 'arrow.png" style="left: -2px; position: relative; top: 4px;">');
					html.push('</div>');
				html.push('</div>');
			html.push('</td>');
		html.push('</tr>');
	html.push('</table>');

	html.push('<div id="fontcolor3" class="rte_dropdown_list" style="background-color: #C0C0C0; padding: 4px; z-index: 3;">');
		html.push('<table border="0" cellspacing="1" cellpadding="0">');

			xa = ['000000', '993300', '333300', '003300', '003366', '000080', '333399', '333333'];
			xb = ['800000', 'FF6600', '808000', '008000', '008080', '0000FF', '666699', '808080'];
			xc = ['FF0000', 'FF9900', '99CC00', '339966', '33CCCC', '3366FF', '800080', '999999'];
			xd = ['FF00FF', 'FFCC00', 'FFFF00', '00FF00', '00FFFF', '00CCFF', '993366', 'C0C0C0'];
			xe = ['FF99CC', 'FFCC99', 'FFFF99', 'CCFFCC', 'CCFFFF', '99CCFF', 'CC99FF', 'FFFFFF'];

			x = [xa, xb, xc, xd, xe];

			na = ['black', 'light-brown', 'brown-gold', 'dark green 2', 'navy', 'dark blue', 'purple #2', 'very dark grey'];
			nb = ['dark-red', 'red-orange', 'gold', 'dark green', 'dull blue', 'blue', 'dull purple', 'dark gey'];
			nc = ['red', 'orange', 'lime', 'dull green', 'dull blue #2', 'sky blue #2', 'purple', 'grey'];
			nd = ['magenta', 'bright orange', 'yellow', 'green', 'cyan', 'light blue', 'light purple', 'light grey'];
			ne = ['pink', 'light orange', 'light orange', 'light green', 'light cyan', 'light blue', 'light purple', 'white'];

			n = [na, nb, nc, nd, ne];

			for(r = 0; r < 5; r = r + 1)
				{
				html.push('<tr>');
					for(c = 0; c < 8; c = c + 1)
						{
						html.push('<td>');
							html.push('<div id="fontcolor" class="rte_dropdown_color_out">');
								html.push('<img src="' + rte_image_path + 'fontcolor3.gif" title="' + n[r][c] + '" style="background-color: #' + x[r][c] + ';" onClick="rte_color(\'#' + x[r][c] + '\');">');
							html.push('</div>');
						html.push('</td>');
						}
				html.push('</tr>');
				}

		html.push('</table>');
	html.push('</div>');
	}

