function rte_color(hexcolor)
	{
	rte_hide();

	////////////////////////////////////////////////////////////////////////////////

	document.getElementById(rte_name).contentWindow.document.execCommand("forecolor", false, hexcolor);
	document.getElementById("fontcolor4").style.backgroundColor = hexcolor;
	}

