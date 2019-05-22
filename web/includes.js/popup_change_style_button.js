var button_remember = null;

window.onmouseup = function(e)
	{
	button_remember = null; // catch mouseup, else, mouseover will create mousedown on last remembered object
	}

function popup_change_style_button(obj, action)
	{
	switch(action)
		{
		case(0): // onmouseup
			obj.className = 'popup_button popup_border_a';

			button_remember = null;

			break;
		case(1): // onmousedown
			obj.className = 'popup_button popup_border_b';

			button_remember = obj;

			break;
		case(2): // onmouseover
			if(obj == button_remember)
				{
				obj.className = 'popup_button popup_border_b';
				}

			break;
		case(3): // onmouseout
			obj.className = 'popup_button popup_border_a';

			break;
		}
	}

