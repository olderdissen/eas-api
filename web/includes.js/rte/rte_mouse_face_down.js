function rte_mouse_face_down()
	{
	rte_hide();

	rte_mouse_face_over();

	////////////////////////////////////////////////////////////////////////////////

	var o = document.getElementById("fontface3");

	o.style.display = (o.style.display == "none" ? "" : "none");

	////////////////////////////////////////////////////////////////////////////////

	var o = document.getElementsByTagName('DIV');

	for(i in o)
		{
		if((o[i].id == "format1") || (o[i].id == "format2"))
			{
			o[i].onmouseout = rte_mouse_format_out;
			}

		if((o[i].id == "fontface1") || (o[i].id == "fontface2"))
			{
			o[i].onmouseout = rte_mouse_face_down;
			}

		if((o[i].id == "fontsize1") || (o[i].id == "fontsize2"))
			{
			o[i].onmouseout = rte_mouse_size_out;
			}

		if((o[i].id == "fontcolor1") || (o[i].id == "fontcolor2"))
			{
			o[i].onmouseout = rte_mouse_color_out;
			}
		}
	}

