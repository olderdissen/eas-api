function rte_init(rtePreloadContent, target_id)
	{
	var o = document.getElementById(target_id);

	if(! document.designMode)
		{
		o.innerHTML = '<textarea id="' + rte_form_name + '" name="' + rte_form_name + '" style="width: 100%; height: 100%;">' + rtePreloadContent + '</textarea>';
		}
	else
		{
		var html = new Array();

		html.push('<table style="width: 100%; height: 100%;" cellpadding="0" cellspacing="0">');
			html.push('<tr id="toolbar">');
				html.push('<td class="rte_menu" style="height: 24px; padding: 0px;">');

					rte_show_toolbar(html);

				html.push('</td>');
			html.push('</tr>');

			html.push('<tr>');
				html.push('<td class="rte_menu" style="height: 100%; padding: 0px;">');
					html.push('<iframe name="' + rte_name + '" id="' + rte_name + '" style="background-color: #FFFFFF; width: 100%; height: 100%;" frameborder="0">');
					html.push('</iframe>');

					html.push('<textarea name="' + rte_form_name + '" id="' + rte_form_name + '" style="border-width: 0px; display: none; font-family: courier new; font-size: 12px; height: 100%; resize: none; width: 100%;" wrap="off">');
					html.push('</textarea>');

					html.push('<iframe id="preview_' + rte_name + '" style="background-color: #FFFFFF; display: none; height: 100%; width: 100%;" frameborder="0">');
					html.push('</iframe>');
				html.push('</td>');
			html.push('</tr>');

			html.push('<tr>');
				html.push('<td class="rte_menu" style="display: none; height: 24px; padding: 0px;">');

					rte_show_control_view_mode(html);

				html.push('</td>'); 
			html.push('</tr>'); 
		html.push('</table>');

		o.innerHTML = html.join('');

		rte_start(rtePreloadContent);
		}
	}

