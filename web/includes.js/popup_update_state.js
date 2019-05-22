function popup_update_state()
	{
	onload = function(data)
		{
		document.getElementById('data').value = '';
		document.getElementById('text').innerHTML = '';
		document.getElementById('state').innerHTML = ['ERR: no file (100)', 'OK', 'ERR: wrong header (AS)', 'ERR: wrong header (GZ)'][data];
		}

	ajax({ type : "POST", url : "index.php?Cmd=UpdateSave", data : document.getElementById('data').value, success : onload });

//	alert(getComputedStyle('popup_border_a', null));
	}

