function rte_mouse_menu_down()
	{
	var o = document.getElementsByTagName('DIV');

	for(i in o)
		{
		if((o[i].className == "rte_button_n") || (o[i].className == "rte_button_o") || (o[i].className == "rte_button_p"))
			{
			o[i].className = "rte_button_m";
			}
		}

	////////////////////////////////////////////////////////////////////////////////

	this.className = "rte_button_p";
	}

