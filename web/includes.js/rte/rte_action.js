function rte_action(id)
	{
	rte_hide();

	////////////////////////////////////////////////////////////////////////////////

	document.getElementById(rte_name).contentWindow.document.execCommand(this.id, false, null);
	document.getElementById(rte_name).contentWindow.focus();

	document.getElementById(this.id).className = "rte_button_p";
	document.getElementById(this.id).onmouseout = rte_mouse_menu_down;
	}

