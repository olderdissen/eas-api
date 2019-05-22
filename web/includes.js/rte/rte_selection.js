function rte_selection()
	{
	rte_hide();

	////////////////////////////////////////////////////////////////////////////////

	if(document.getElementById("rte_mode_code").className != "rte_button_a")
		{
		document.getElementById("toolbar").style.display = "none";
		}
	else
		{
		document.getElementById("format1").innerHTML = "Paragraph";
		document.getElementById("fontface1").innerHTML = "Verdana";
		document.getElementById("fontsize1").innerHTML = "2";

		var o = document.getElementsByTagName('DIV');

		for(i in o)
			{
			switch(o[i].className)
				{
				case("rte_button_a"):
					o[i].onmouseover = rte_mouse_view_over;
					o[i].onmouseout = rte_mouse_view_out;
					o[i].onmousedown = rte_mouse_view_down;
					o[i].onmouseup = rte_mouse_view_up;

					break;
				case("rte_button_m"):
				case("rte_button_n"):
				case("rte_button_o"):
				case("rte_button_p"):
					o[i].className = "rte_button_m";

					o[i].onclick = rte_action;

					o[i].onmouseover = rte_mouse_menu_over;
					o[i].onmouseout = rte_mouse_menu_out;
					o[i].onmousedown = rte_mouse_menu_down;
					o[i].onmouseup = rte_mouse_menu_up;

					break;
				}

			switch(o[i].id)
				{
				case("format1"):
				case("format2"):
					o[i].onmouseover = rte_mouse_format_over;
					o[i].onmousedown = rte_mouse_format_down;
					o[i].onmouseout = rte_mouse_format_out;
					o[i].onmouseout = (o[i].className == "rte_dropdown_button_over" ? rte_mouse_format_down : rte_mouse_format_out);

					break;
				case("fontface1"):
				case("fontface2"):
					o[i].onmouseover = rte_mouse_face_over;
					o[i].onmousedown = rte_mouse_face_down;
					o[i].onmouseout = rte_mouse_face_out;
					o[i].onmouseout = (o[i].className == "rte_dropdown_button_over" ? rte_mouse_face_down : rte_mouse_face_out);

					break;
				case("fontsize1"):
				case("fontsize2"):
					o[i].onmouseover = rte_mouse_size_over;
					o[i].onmousedown = rte_mouse_size_down;
					o[i].onmouseout = rte_mouse_size_out;
					o[i].onmouseout = (o[i].className == "rte_dropdown_button_over" ? rte_mouse_size_down : rte_mouse_size_out);

					break;
				case("fontcolor1"):
				case("fontcolor2"):
					o[i].onmouseover = rte_mouse_color_over;
					o[i].onmousedown = rte_mouse_color_down;
					o[i].onmouseout = rte_mouse_color_out;
					o[i].onmouseout = (o[i].className == "rte_dropdown_arrow_over" ? rte_mouse_color_down : rte_mouse_color_out);

					break;
				case("formatblock"):
				case("fontface"):
				case("fontsize"):
					o[i].onmouseover = rte_mouse_ca_over;
					o[i].onmouseout = rte_mouse_ca_out;
					o[i].onmousedown = rte_mouse_ca_down;

					break;
				case("fontcolor"):
					o[i].onmouseover = rte_mouse_da_over;
					o[i].onmouseout = rte_mouse_da_out;

					break;
				}
			}

		////////////////////////////////////////////////////////////////////////////////
		// get handle to written text
		////////////////////////////////////////////////////////////////////////////////

		var o = document.getElementById(rte_name)

		if(window.getSelection)
			{
			var selected_obj = o.contentWindow.window.getSelection().focusNode;
			}
		else if(document.getSelection)
			{
			var selected_obj = o.contentWindow.document.getSelection().focusNode;
			}
		else if(document.selection)
			{
			var selected_obj = o.contentWindow.document.selection.createRange().parentElement();
			}

		////////////////////////////////////////////////////////////////////////////////

		var textcolor = "#000000";

		var current_tag = selected_obj;

		var previous_tagName = (current_tag ? selected_obj.tagName : "HTML");

		////////////////////////////////////////////////////////////////////////////////

		while(previous_tagName != "HTML")
			{
			if((previous_tagName == "B") || (previous_tagName == "STRONG"))
				{
				var o = document.getElementById("bold");

				o.className = "rte_button_p";
				o.onmouseout = rte_mouse_menu_down;
				}

			if((previous_tagName == "I") || (previous_tagName == "EM"))
				{
				var o = document.getElementById("italic");

				o.className = "rte_button_p";
				o.onmouseout = rte_mouse_menu_down;
				}

			if(previous_tagName == "U")
				{
				var o = document.getElementById("underline");

				o.className = "rte_button_p";
				o.onmouseout = rte_mouse_menu_down;
				}

			if(previous_tagName == "UL")
				{
				var o = document.getElementById("insertunorderedlist");

				o.className = "rte_button_p";
				o.onmouseout = rte_mouse_menu_down;
				}

			if(previous_tagName == "OL")
				{
				var o = document.getElementById("insertorderedlist");

				o.className = "rte_button_p";
				o.onmouseout = rte_mouse_menu_down;
				}

			for(i = 1; i < 7; i = i + 1)
				{
				if(previous_tagName == ("H" + i))
					{
					document.getElementById("format1").innerHTML = "Header " + i;
					}
				}

			if(previous_tagName == "BLOCKQUOTE")
				{
				var o = document.getElementById("indent");

				o.className = "rte_button_p";
				o.onmouseout = rte_mouse_menu_down;
				}

			var x = ['left', 'center', 'right', 'justify'];

			for(i = 0; i < 4; i = i + 1)
				{
				if(current_tag.align == x[i])
					{
					var o = document.getElementById('justify' + x[i]);

					o.className = "rte_button_p";
					o.onmouseout = rte_mouse_menu_down;
					}
				}

			if(current_tag.align == "")
				{
				document.getElementById("justifyleft").className = "rte_button_m";
				}

			for(i = 0; i < 11; i = i + 1)
				{
				if(current_tag.face == rte_fonts[i].toLowerCase())
					{
					document.getElementById("fontface1").innerHTML = rte_fonts[i];

					break;
					}
				}

			for(i = 1; i < 8; i = i + 1)
				{
				if(current_tag.size == i)
					{
					document.getElementById("fontsize1").innerHTML = i;

					break;
					}
				}

			textcolor = (current_tag.color ? current_tag.color : textcolor);

			current_tag = current_tag.parentNode;

			previous_tagName = current_tag.tagName;
			}
		}

	////////////////////////////////////////////////////////////////////////////////

	document.getElementById("fontcolor4").style.backgroundColor = textcolor;
	}

