function rte_events()
	{
	var o = document.getElementById(rte_name).contentWindow.document;

	if(document.all && !window.opera)
		{
		o.attachEvent("onkeyup", rte_counter, true);
		o.attachEvent("onkeypress", rte_selection);
		o.attachEvent("onclick", rte_selection);
		o.attachEvent("onmouseup", rte_selection);
		}
	else
		{
		o.execCommand("useCSS", false, null);

		o.addEventListener("keyup", rte_counter, true);
		o.addEventListener("keypress", rte_selection, true);
		o.addEventListener("click", rte_selection, true);
		o.addEventListener("mouseup", rte_selection, true);
		}
	}

