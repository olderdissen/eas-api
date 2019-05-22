function get_menu_items(expression)
	{
	switch(expression)
		{
		case("Calendar"):
			var retval = ['button:view:event', 'button:edit', 'button:delete', 'button:create', 'button:show:day', 'button:show:agenda', 'button:cancel'];

			break;
		case("Contacts"):
			var retval = ['button:edit', 'button:delete', 'button:send:contact_information', 'button:send:namecard', 'button:move', 'button:cancel'];

			break;
		case("Contacts:Page"):
			var retval = ['button:page:work', 'button:page:home', 'button:page:other'];

			break;
		case("Email"):
			var retval = ['button:open', 'button:edit', 'button:delete', 'button:discard', 'button:forward', 'button:reply', 'button:mark:read', 'button:mark:unread', 'button:flag', 'button:move', 'button:cancel', 'button:flag:set', 'button:flag:completed', 'button:flag:clear'];

			break;
		case("Notes"):
			var retval = ['button:edit', 'button:delete', 'button:cancel'];

			break;
		case("SMS"):
			var retval = [];

			break;
		case("Tasks"):
			var retval = [];

			break;
		default:
			var retval = [];

			break;
		}

	return(retval);
	}
