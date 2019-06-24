function rte_mode(id)
	{
	if(id == 0)
		{
		document.getElementById(rte_name).contentWindow.document.body.innerHTML = document.getElementById(rte_form_name).value;
		document.getElementById(rte_name).style.display = "";
		document.getElementById(rte_name).contentWindow.focus();

		document.getElementById("toolbar").style.display = "";

		document.getElementById(rte_form_name).style.display = "none";

		document.getElementById("preview_" + rte_name).style.display = "none";
		}

	if(id == 1)
		{
		document.getElementById(rte_name).style.display = "none";

		document.getElementById("toolbar").style.display = "none";

		document.getElementById(rte_form_name).value = document.getElementById(rte_name).contentWindow.document.body.innerHTML;
		document.getElementById(rte_form_name).style.display = "";

		document.getElementById("preview_" + rte_name).style.display = "none";
		}

	if(id == 2)
		{
		document.getElementById(rte_name).style.display = "none";

		document.getElementById("toolbar").style.display = "none";

		document.getElementById(rte_form_name).style.display = "none";

		document.getElementById("preview_" + rte_name).contentWindow.document.body.innerHTML = document.getElementById(rte_form_name).value;
		document.getElementById("preview_" + rte_name).style.display = "";
		}

	if(id == 3)
		{
		if(document.getElementById(rte_name).style.display == "none")
			{
			document.getElementById(rte_name).contentWindow.document.body.innerHTML = document.getElementById(rte_form_name).value;
			}
		else
			{
//			var o = document.getElementById(rte_name).contentWindow.document.body;
//			document.getElementById(rte_form_name).value = (o.innerText || o.textContent);

			document.getElementById(rte_form_name).value = document.getElementById(rte_name).contentWindow.document.body.innerHTML;
			}

//		document.forms[0].submit();
		}

	if(id == 4)
		history.back();
	}

