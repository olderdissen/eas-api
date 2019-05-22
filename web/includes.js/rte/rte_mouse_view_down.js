function rte_mouse_view_down()
	{
	var o = document.getElementsByTagName("DIV");

	for(i in o)
		{
		if((o[i].className == "rte_button_a") || (o[i].className == "rte_button_b") || (o[i].className == "rte_button_c"))
			{
			o[i].className = "rte_button_a";

			o[i].onmouseover = rte_mouse_view_over;
			o[i].onmouseout = rte_mouse_view_out;
			o[i].onmousedown = rte_mouse_view_down;
			o[i].onmouseup = rte_mouse_view_up;
			}
		}

	////////////////////////////////////////////////////////////////////////////////

	this.className = "rte_button_b";

	this.onmouseover = rte_mouse_view_over;
	this.onmouseout = rte_mouse_bb_down;
	this.onmouseup = rte_mouse_view_up;
	}

