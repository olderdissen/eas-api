var state = {
	cmd : "",
	user : "",
	collection_id : "",
	server_id : "",
	device_id : "",
	request_id : "",
	user_response : "",
	src_msg_id : "",
	dst_msg_id : "",
	src_fld_id : "",
	dst_fld_id : "",
	long_id : "",
	item_id : "",
	class_id : "",

	object_id : "",
	action_id : "",
	position : 0,
	init : 0,		// cookie state

	title : "",		// popup - title
	text : "",		// popup - text
	options : 0,		// popup - menu height
	ok_cmd : "",		// popup - command for ok
	cancel_cmd : "",	// popup - command for cancel

	data : "",		// blocking layer - inner data
	blocking : 0,		// blocking layer - abort on click

	picture_id : 0,		// progress - picture id
	size : 32,		// progress - picture size
	timer_id : 0,		// progress - update timer

	category_id : "*",	// contact - categories

	view_id : "",		// calendar - view
	time_id : 0,		// calendar - time
	time_start : 0,		// calendar - start
	time_end : 0		// calendar - end
	};

var evt = {
	ox: 0,
	oy: 0,
	oz: null,
	mx: 0,
	my: 0
	};

var calendar = {
	style : "",
	target : "",
	time : "",
	scroll : "",
	events : "",
	birthdays : "",
	timeout : "",
	day_of_week : ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'],
	month_of_year : ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'July', 'August', 'September', 'Oktober', 'November', 'Dezember']
	};

var evt_touch = {
	x : 0,
	y : 0
	};

var popup_buttons = {
	"button:cancel" : "Abbrechen",
	"button:create" : "Erstellen",
	"button:delete" : "Löschen",
	"button:discard" : "Verwerfen",
	"button:edit" : "Bearbeiten",
	"button:flag" : "Markierung",
	"button:flag:clear" : "Löschen",
	"button:flag:completed" : "Abgeschlossen",
	"button:flag:set" : "Einstellen",
	"button:forward" : "Weiterleiten",
	"button:mark:read" : "Als gelesen markieren",
	"button:mark:unread" : "Als ungelesen markieren",
	"button:move" : "Verschieben",
	"button:open" : "Öffnen",
	"button:page:home" : "Privat",
	"button:page:other" : "Andere",
	"button:page:work" : "Arbeit",
	"button:reply" : "Antworten",
	"button:send:contact_information" : "Kontaktinformationen senden",
	"button:send:namecard" : "Visitenkarte senden",
	"button:show:agenda" : "Agenda anzeigen",
	"button:show:day" : "Tag anzeigen",
	"button:view:event" : "Ereignis anzeigen"
	};

var suggest = {
	action : null,

	object_handle : null,
	collection_id : null,
	multi : false,
	timer : null,

	src_obj : null,
	dst_obj : null,

	src_id : null,
	dst_id : null,

	data : ""
	}

var date_picker = {
	field : null,
	target : null,
	time : false,
	direction : 0,
	wheel : null,
	value : ""
	};

var popup_state = {
	cmd : null,
	title : null,
	text : null,
	ok : null,
	cancel : null,
	data : null,
	id : null
	}

function handle_link(settings)
	{
	var defaults = {};

	////////////////////////////////////////////////////////////////////////////////
	// check if values have to be replaced
	////////////////////////////////////////////////////////////////////////////////

	for(var item in state)
		{
		state[item] = (settings[item] == null ? state[item] : settings[item]);
		}

	////////////////////////////////////////////////////////////////////////////////
	// save settings
	////////////////////////////////////////////////////////////////////////////////

	if(state.init == 1)
		{
		var fields = ["category_id", "view_id", "time_id"];

		var expires = new Date();

		expires.setTime(expires.getTime() + (365 * 24 * 60 * 60 * 1000));

		for(var field in fields)
			document.cookie = fields[field] + "=_" + state[fields[field]] + "; expires=" + expires.toUTCString();
		}

	if(state.init == 0)
		{
		var fields = ["category_id", "view_id", "time_id"];

		var cookies = document.cookie.split("; ");

		for(var field in fields)
			{
			for(var cookie in cookies)
				{
				var kv = cookies[cookie].split("=");

				if(fields[field] != kv[0])
					continue;

				state[fields[field]] = kv[1].substr(1);
				}
			}

		state.init = 1;
		}

	////////////////////////////////////////////////////////////////////////////////
	// handle the called command
	////////////////////////////////////////////////////////////////////////////////

	state.class_id = (state.collection_id != 9002 ? state.class_id : "Email");
	state.class_id = (state.collection_id != 9003 ? state.class_id : "Email");
	state.class_id = (state.collection_id != 9004 ? state.class_id : "Email");
	state.class_id = (state.collection_id != 9005 ? state.class_id : "Email");
	state.class_id = (state.collection_id != 9006 ? state.class_id : "Email");
	state.class_id = (state.collection_id != 9007 ? state.class_id : "Tasks");
	state.class_id = (state.collection_id != 9008 ? state.class_id : "Calendar");
	state.class_id = (state.collection_id != 9009 ? state.class_id : "Contacts");
	state.class_id = (state.collection_id != 9010 ? state.class_id : "Notes");

	////////////////////////////////////////////////////////////////////////////////
	// handle the called command
	////////////////////////////////////////////////////////////////////////////////

	if(state.cmd == "AttachmentDelete")
		{
		var o = document.forms[0].attachments;

		var onload = function(data)
			{
			if(data == 1)
				o.remove(o.selectedIndex);
			}

		ajax({ type : "GET", url : "index.php?Cmd=Upload&CollectionId=" + state.collection_id + "&LongId=0&ItemId=" + escape(o[o.selectedIndex].text), success : onload });
		}

	if(state.cmd == "AttachmentDownload")
		{
		location.href = "index.php?Cmd=Attachment&CollectionId=" + state.collection_id + "&ServerId=" + state.server_id + "&ItemId=" + document.getElementById("attachment").value;
		}

	if(state.cmd == "AttachmentUpload")
		{
		handle_files_x();
		}

	if(state.cmd == "BlockingLayer")
		{
		var o = document.getElementById("blocking_layer");

		if(state.blocking == 1)
			{
			}
		else if(o != null)
			{
			o.parentNode.removeChild(o);
			}

		if(state.blocking == 0)
			{
			}
		else if(o == null)
			{
			var o = document.createElement("div");

			o.id = "blocking_layer";
			o.className = "blocking_layer"; // for stylesheet

			var n = document.body.childNodes[0];

			n.parentNode.insertBefore(o, n.nextSibling);
			}
		}

	if(state.cmd == "CalendarDrawLine")
		{
		var time_c	= new Date(); // current

		var time_s	= new Date(); // range start
		var time_e	= new Date(); // range end

		if((calendar.style == "d") || (calendar.style == "w"))
			{
			var data_m = document.getElementById("calendar_marker");
			var data_s = document.getElementById("calendar_scroll");
			}

		if(calendar.style == "d")
			{
			time_s.setTime(calendar.time * 1000);
			time_e.setTime(calendar.time * 1000);

			time_e.setDate(time_s.getDate() + 1); // + 1 day

			time_s.setHours(0);
			time_s.setMinutes(0);
			time_s.setSeconds(0);
			time_s.setMilliseconds(0);

			time_e.setHours(0);
			time_e.setMinutes(0);
			time_e.setSeconds(0);
			time_e.setMilliseconds(0);

			if((time_c.getTime() >= time_s.getTime()) && (time_c.getTime() < time_e.getTime()))
				{
				data_m.style.display = "block";
				data_m.style.left = 30 + "px";
				data_m.style.top = ((time_c.getHours() + (time_c.getMinutes() / 60)) * 41) + "px";
				data_m.style.width = ((data_s.scrollWidth - 30) / 1) + "px";
				}
			}

		if(calendar.style == "w")
			{
			time_s.setTime(calendar.time * 1000);
			time_e.setTime(calendar.time * 1000);

			while(time_s.getDay() != 1)
				time_s.setDate(time_s.getDate() - 1);

			time_e.setDate(time_s.getDate() + 7); // + 1 week

			time_s.setHours(0);
			time_s.setMinutes(0);
			time_s.setSeconds(0);
			time_s.setMilliseconds(0);

			time_e.setHours(0);
			time_e.setMinutes(0);
			time_e.setSeconds(0);
			time_e.setMilliseconds(0);

			if((time_c.getTime() >= time_s.getTime()) && (time_c.getTime() < time_e.getTime()))
				{
				data_m.style.display = "block";
				data_m.style.left = (30 + (((data_s.scrollWidth - 30) / 7) * ((time_c.getDay() + 6) % 7))) + "px";
				data_m.style.top = ((time_c.getHours() + (time_c.getMinutes() / 60)) * 41) + "px";
				data_m.style.width = ((data_s.scrollWidth - 30) / 7) + "px";
				}
			}
		}

	if(state.cmd == "CalendarJumpTo")
		{
		handle_link({ cmd : "CalendarScrollPositionSave" });

		if(calendar.time != state.time_id / 1000)
			{
			calendar.time = state.time_id / 1000;

			calendar_draw_calendar();
			calendar_retrieve("data");
			calendar_retrieve("birthday");
			}
		}

	if(state.cmd == "CalendarJumpToNow")
		{
		var time_c = new Date();

		handle_link({ cmd : "CalendarJumpTo", time_id : time_c.getTime() });
		}

	if(state.cmd == "CalendarResize")
		{
		if(state.collection_id != 9008)
			return;

		if(calendar.timeout != null)
			clearTimeout(calendar.timeout);

		handle_link({ cmd : "CalendarUpdate" });
		}

	if(state.cmd == "CalendarScrollPositionJump")
		{
		var o = document.getElementById("tbl_scroll");

		if(o != null)
			{
			o.style.height = o.parentNode.offsetHeight + "px";
			o.style.display = "block";

			o.scrollTop = calendar.scroll;
			}
		}

	if(state.cmd == "CalendarScrollPositionSave")
		{
		if(document.getElementById("calendar_scroll") != null)
			calendar.scroll = document.getElementById("tbl_scroll").scrollTop;
		}

	if(state.cmd == "CalendarSelect")
		{
		var o = document.getElementById("view");

		if(o != null)
			handle_link({ cmd : "CalendarShow", view_id : o.value });
		}

	if(state.cmd == "CalendarShow")
		{
		if(calendar.style != state.view_id)
			{
			var o = document.getElementById("view");

			for(i = 0; i < o.length; i = i + 1)
				{
				if(o[i].value != state.view_id)
					continue;

				o.selectedIndex = i;
				}

			calendar.style = state.view_id;

			calendar_draw_calendar();
			calendar_retrieve("data");
			calendar_retrieve("birthday");
			}
		}

	if(state.cmd == "CalendarUpdate")
		{
		handle_link({ cmd : "CalendarScrollPositionSave" });

		calendar_draw_calendar();
		calendar_draw_events();
		calendar_draw_birthdays();
		}

	if(state.cmd == "Category")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;
			}

		ajax({ type : "GET", url : "index.php?Cmd=Category", success : onload });
		}

	if(state.cmd == "CategoryCreate")
		{
		var form_data = get_form_data();

		var onload = function(data)
			{
			handle_link({ cmd : "Category" });
			}

		ajax({ type : "POST", url : "index.php?Cmd=CategoryCreate", data : form_data, success : onload });
		}

	if(state.cmd == "CategoryDelete")
		{
		var onload = function(data)
			{
			handle_link({ cmd : "PopupRemove" });
			handle_link({ cmd : "Category" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=CategoryDelete&ItemId=" + state.item_id, success : onload });
		}

	if(state.cmd == "CategoryDeleteConfirm")
		{
		state.title = "Kategorie löschen";
		state.text = "Diese Kategorie wird gelöscht.";

		state.ok_cmd = "CategoryDelete";
		state.cancel_cmd = "Nothing";

		handle_link({ cmd : "PopupDelete" });
		}

	if(state.cmd == "CategoryEdit")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;
			}

		ajax({ type : "GET", url : "index.php?Cmd=CategoryEdit&ItemId=" + state.item_id, success : onload });
		}

	if(state.cmd == "CategorySave")
		{
		var form_data = get_form_data();

		var onload = function(data)
			{
			handle_link({ cmd : "Category" });
			}

		ajax({ type : "POST", url : "index.php?Cmd=CategorySave", data : form_data, success : onload });
		}

	if(state.cmd == "CategoryUpdate")
		{
		var form_data = get_form_data();

		var onload = function(data)
			{
			handle_link({ cmd : "Category" });
			}

		ajax({ type : "POST", url : "index.php?Cmd=CategoryUpdate", data : form_data, success : onload });
		}

	if(state.cmd == "Create") // only used for calendar
		{
		switch(state.class_id)
			{
			case("Email"):
				break;
			case("Tasks"):
				break;
			case("Calendar"):
				break;
			case("Contacts"):
				break;
			case("Notes"):
				break;
			}

		handle_link({ cmd : "Edit", server_id : "" });
		}

	if(state.cmd == "Delete")
		{
		handle_link({ cmd : "DeleteHelper" });
		handle_link({ cmd : "PopupRemove" });
		handle_link({ cmd : "List" });
		}

	if(state.cmd == "DeleteConfirm")
		{
		switch(state.class_id)
			{
			case("Email"): // outbox
				state.title = "Nachricht löschen";
				state.text = "Diese Nachricht wird gelöscht.";

				break;
			case("Tasks"): // tasks
				state.title = "Aufgabe löschen";
				state.text = "Diese Aufgabe wird gelöscht.";

				break;
			case("Calendar"): // calendar
				state.title = "Ereignis löschen";
				state.text = "Dieses Ereignis wird gelöscht.";

				break;
			case("Contacts"): // contacts
				state.title = "Kontakt löschen";
				state.text = "Dieser Kontakt wird gelöscht.";

				break;
			case("Notes"): // notes
				state.title = "Notiz löschen";
				state.text = "Diese Notiz wird gelöscht.";

				break;
			}

		state.ok_cmd = "Delete";
		state.cancel_cmd = "Nothing";

		handle_link({ cmd : "PopupDelete" });
		}

	if(state.cmd == "DeleteHelper")
		{
		ajax({ type : "GET", url : "index.php?Cmd=Delete&CollectionId=" + state.collection_id + "&ServerId=" + state.server_id});
		}

	if(state.cmd == "DeleteMultiple")
		{
		var form_data = get_form_data();

		var ids = form_data.split("&");

		for(id in ids)
			handle_link({ cmd : "DeleteHelper", server_id : ids[id].split("=")[1] });

		handle_link({ cmd : "PopupRemove" });
		handle_link({ cmd : "List" });
		}

	if(state.cmd == "DeleteMultipleConfirm")
		{
		switch(state.class_id)
			{
			case("Email"): // outbox
				state.title = "Nachrichten löschen";
				state.text = "Diese Nachrichten werden gelöscht.";

				break;
			case("Tasks"): // tasks
				state.title = "Aufgabe löschen";
				state.text = "Diese Aufgabe wird gelöscht.";

				break;
			case("Calendar"): // calendar
				state.title = "Ereignisse löschen";
				state.text = "Diese Ereignisse werden gelöscht.";

				break;
			case("Contacts"): // contacts
				state.title = "Kontakte löschen";
				state.text = "Diese Kontakte werden gelöscht.";

				break;
			case("Notes"): // notes
				state.title = "Notizen löschen";
				state.text = "Diese Notizen werden gelöscht.";

				break;
			}

		state.ok_cmd = "DeleteMultiple";
		state.cancel_cmd = "Nothing";

		handle_link({ cmd : "PopupDelete" });
		}

	if(state.cmd == "Device")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;
			}

		ajax({ type : "GET", url : "index.php?Cmd=Device", success : onload });
		}

	if(state.cmd == "DeviceDelete")
		{
		var onload = function(data)
			{
			handle_link({ cmd : "PopupRemove" });
			handle_link({ cmd : "Device" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=DeviceDelete&DeviceId=" + state.device_id, success : onload });
		}

	if(state.cmd == "DeviceDeleteConfirm")
		{
		state.title = "Gerät löschen";
		state.text = "Dieses Gerät wird gelöscht.";

		state.ok_cmd = "DeviceDelete";
		state.cancel_cmd = "Nothing";

		handle_link({ cmd : "PopupDelete" });
		}

	if(state.cmd == "Draft")
		{
		rte_mode(3);

		document.forms[0].Draft.value = 1;

		handle_link({ cmd : "Save" });
		}

	if(state.cmd == "Edit")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;

			handle_link({ cmd : "PopupRemove" });
			handle_link({ cmd : "Reset" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=Edit&CollectionId=" + state.collection_id + "&ServerId=" + state.server_id + "&LongId=" + state.long_id + "&ItemId=" + state.item_id, success : onload });
		}

	if(state.cmd == "EventContext")
		{
		evt_context();
		}

	if(state.cmd == "Flag")
		{
		// as a side effect, this is triggered before submenu is triggered

		var onload = function(data)
			{
			handle_link({ cmd : "PopupRemove" });
			handle_link({ cmd : "List" });
			}

		if(state.long_id != "")
			ajax({ type : "GET", url : "index.php?Cmd=Flag&CollectionId=" + state.collection_id + "&ServerId=" + state.server_id + "&LongId=" + state.long_id, success : onload });
		}

	if(state.cmd == "Folder")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;
			}

		ajax({ type : "GET", url : "index.php?Cmd=Folder", success : onload });
		}

	if(state.cmd == "FolderDelete")
		{
		var onload = function(data)
			{
			handle_link({ cmd : "PopupRemove" });
			handle_link({ cmd : "Folder" });
			handle_link({ cmd : "MenuItems" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=FolderDelete&ServerId=" + state.server_id, success : onload });
		}

	if(state.cmd == "FolderDeleteConfirm")
		{
		state.title = "Ornder löschen";
		state.text = "Dieser Ordner wird gelöscht.";

		state.ok_cmd = "FolderDelete";
		state.cancel_cmd = "Nothing";

		handle_link({ cmd : "PopupDelete" });
		}

	if(state.cmd == "FolderEdit")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;
			}

		ajax({ type : "GET", url : "index.php?Cmd=FolderEdit&ServerId=" + state.server_id, success : onload });
		}

	if(state.cmd == "FolderSave")
		{
		var form_data = get_form_data();

		var onload = function(data)
			{
			handle_link({ cmd : "Folder" });
			handle_link({ cmd : "MenuItems" });
			}

		ajax({ type : "POST", url : "index.php?Cmd=FolderSave", data : form_data, success : onload });
		}

	if(state.cmd == "Forward")
		{
		handle_link({ cmd : "Edit", long_id : "F" });
		}

	if(state.cmd == "IM")
		{
		if(state.item_id == "")
			alert("this element should be hidden");
		else
			location.href = state.item_id;
		}

	if(state.cmd == "List")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;

			switch(state.class_id)
				{
				case("Email"): // inbox
					list_emails("search_result");

					break;
				case("Tasks"): // tasks
					list_tasks("search_result");

					break;
				case("Calendar"): // calendar
					var o = document.getElementById("view");

					for(i = 0; i < o.length; i = i + 1)
						{
						if(o[i].value == state.view_id)
							{
							o.selectedIndex = i;

							break;
							}
						}

					calendar_init("search_result");

					break;
				case("Contacts"): // contacts
//					document.getElementById("search_result").innerHTML = "";
//					document.getElementById("search_count").innerHTML = 0;

					var o = document.getElementById("search_category");

					for(i = 0; i < o.length; i = i + 1)
						{
						if(o[i].value == state.category_id)
							{
							o.selectedIndex = i;

							break;
							}
						}

					list_contacts("search_name", "search_category", "search_result", "search_count");

					break;
				case("Notes"): // notes
					list_notes("search_result");

					break;
				}

			evt_touch_init("touchscroll_div");
			}

		ajax({ type : "GET", url : "index.php?Cmd=List&CollectionId=" + state.collection_id, success : onload });
		}

	if(state.cmd == "MarkAsRead")
		{
		handle_link({ cmd : "Read", long_id : 1 });
		}

	if(state.cmd == "MarkAsUnread")
		{
		handle_link({ cmd : "Read", long_id : 0 });
		}

	if(state.cmd == "Meeting")
		{
		var onload = function(data)
			{
			handle_link({ cmd : "Show" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=Meeting&CollectionId=" + state.collection_id + "&RequestId=" + state.request_id + "&UserResponse=" + state.user_response, success : onload });
		}

	if(state.cmd == "Move")
		{
		// as a side effect, this is triggered before submenu is triggered

		if(state.item_id != "")
			{
			var onload = function(data)
				{
				handle_link({ cmd : "List" });
				}

			ajax({ type : "GET", url : "index.php?Cmd=Move&SrcFldId=" + state.collection_id + "&SrcMsgId=" + state.server_id + "&DstFldId=" + state.item_id, success : onload });
			}
		}

	if(state.cmd == "Nothing")
		{
		handle_link({ cmd : "PopupRemove" });
		}

	if(state.cmd == "Oof")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;

			handle_link({ cmd : "ToggleSettings" });
			handle_link({ cmd : "ToggleTimes" });
			handle_link({ cmd : "ToggleExternal" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=Oof", success : onload });
		}

	if(state.cmd == "OofSave")
		{
		var form_data = get_form_data();

		var onload = function(data)
			{
			handle_link({ cmd : "Oof" });
			}

		if(oof_check() == true)
			ajax({ type : "POST", url : "index.php?Cmd=OofSave", data : form_data, success : onload });
		}

	if(state.cmd == "PictureDelete")
		{
		document.getElementById("img_data").value = "";
		document.getElementById("img_preview").src = "images/contacts_default_image_add.png";
		}

	if(state.cmd == "PictureLoad")
		{
		handle_files("img_preview", "img_data");
		}

	if(state.cmd == "Policy")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;

			handle_link({ cmd : "PolicyInit" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=Policy", success : onload });
		}

	if(state.cmd == "PolicyDelete")
		{
		var onload = function(data)
			{
			handle_link({ cmd : "Policy" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=PolicyDelete", success : onload });
		}

	if(state.cmd == "PolicyInit")
		{
		var o = document.getElementById("policy_options");

		var s = document.getElementById("policy_selection");

		var p = 0;

		for(i = 0; i < o.childNodes.length; i = i + 1)
			{
			var v = o.childNodes[i].id;

			switch(v)
				{
				case("AllowSimpleDevicePassword"):
				case("AlphanumericDevicePasswordRequired"):
				case("DevicePasswordExpiration"):
				case("DevicePasswordHistory"):
				case("MinDevicePasswordComplexCharacters"):
				case("MaxDevicePasswordFailedAttempts"):
				case("MinDevicePasswordLength"):
				case("PasswordRecoveryEnabled"):
					if(document.forms[0].DevicePasswordEnabled.value == 1)
						{
						s[p] = new Option(v, v, false, false);

						p = p + 1;
						}

					break;
				default:
					s[p] = new Option(v, v, false, false);

					p = p + 1;

					break;
				}
			}

		for(i = 0; i < s.length; i = i + 1)
			{
			if(s[i].value != s.value)
				{
				continue;
				}

			s.selectedIndex = i;
			}
		}

	if(state.cmd == "PolicySave")
		{
		var form_data = get_form_data();

		var onload = function(data)
			{
			handle_link({ cmd : "Policy" });
			}

		ajax({ type : "POST", url : "index.php?Cmd=PolicySave", data : form_data, success : onload });
		}

	if(state.cmd == "PopupCreate")
		{
		state.blocking = 1;

		handle_link({ cmd : "BlockingLayer" });

		var o = document.getElementById("blocking_layer");

		o.innerHTML = state.data.join("");

		o.onmousedown = function(e)
			{
			if(e.target.id == "blocking_layer")
				handle_link({ cmd : "PopupRemove" });

			//alert(e.pageX);
			}
		}

	if(state.cmd == "PopupDelete")
		{
		var data_h = new Array();

		data_h.push('<div id="popup_dialog" class="popup_dialog" style="width: 255px;">');
			data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
				data_h.push(state.title);
			data_h.push('</div>');
			data_h.push('<div class="popup_text" id="popup_text">');
				data_h.push(state.text);
			data_h.push('</div>');
			data_h.push('<div class="popup_buttons" id="popup_buttons">');
				for(action_id = 0; action_id < 2; action_id = action_id + 1)
					{
					data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="handle_link({ cmd : \'' + [state.ok_cmd, state.cancel_cmd][action_id] + '\' });" style="left: ' + [30, 140][action_id] + 'px;">');
						data_h.push(['OK', "Abbrechen"][action_id]);
					data_h.push('</div>');
					}
			data_h.push('</div>');
		data_h.push('</div>');

		handle_link({ cmd : "PopupRemove" });
		handle_link({ cmd : "PopupCreate", data : data_h });
		handle_link({ cmd : "PopupPositionCenter" });
		}

	if(state.cmd == "PopupDevice")
		{
		var data_h = new Array();

		var fields = ["Model", "Imei", "FriendlyName", "OS", "OSLanguage", "PhoneNumber", "UserAgent", "EnableOutboundSMS", "MobileOperator"];

		data_h.push('<div id="popup_dialog" class="popup_dialog" style="width: 450px;">');
			data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
				data_h.push('Geräte Information');
			data_h.push('</div>');
			data_h.push('<div class="popup_text" id="popup_text">');
				data_h.push('<table>');
					for(field in fields)
						{
						data_h.push('<tr>');
							data_h.push('<td style="text-align: right;">');
								data_h.push(fields[field]);
							data_h.push('</td>');
							data_h.push('<td>');
								data_h.push(':');
							data_h.push('</td>');
							data_h.push('<td style="text-align: left;">');
								data_h.push('<span id="' + fields[field] + '">');
									data_h.push('...');
								data_h.push('</span>');
							data_h.push('</td>');
						data_h.push('</tr>');
						}
				data_h.push('</table>');
			data_h.push('</div>');
			data_h.push('<div class="popup_buttons" id="popup_buttons">');
				data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="handle_link({ cmd : \'Nothing\' });" style="left: 185px;">');
					data_h.push('OK');
				data_h.push('</div>');
			data_h.push('</div>');
		data_h.push('</div>');

		handle_link({ cmd : "PopupRemove" });
		handle_link({ cmd : "PopupCreate", data : data_h });
		handle_link({ cmd : "PopupPositionCenter" });

		////////////////////////////////////////////////////////////////////////

		var onload = function(data)
			{
			var retval = JSON.parse(data);

			for(field in fields)
				{
				var d = fields[field];

				document.getElementById(d).innerHTML = (retval[d] ? (d == "EnableOutboundSMS" ? ["Nein", "Ja"][retval[d]] : retval[d]) : "&nbsp;");
				}
			}

		ajax({ type : "GET", url : "index.php?Cmd=DeviceInfo&DeviceId=" + state.device_id, success : onload });
		}

	if(state.cmd == "PopupHeight")
		{
		var o = document.getElementById("popup_dialog");

		var h = 0;

		for(i = 0x00; i < o.childNodes.length ; i = i + 0x01)
			{
			o.childNodes[i].style.display = ((state.options >> i) & 0x0001 ? "block" : "none");

			h = h + ((state.options >> i) & 0x0001 ? 20 : 0)
			}

		o.style.height = h + "px";
		}

	if(state.cmd == "PopupInfo")
		{
		var data_h = new Array();

		data_h.push('<div id="popup_dialog" class="popup_dialog" style="width: 400px;">');
			data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
				data_h.push(state.title);
			data_h.push('</div>');
			data_h.push('<div class="popup_text" id="popup_text">');
				data_h.push(state.text);
			data_h.push('</div>');
			data_h.push('<div class="popup_buttons" id="popup_buttons">');
				data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="handle_link({ cmd : \'' + state.ok_cmd + '\' });" style="left: 150px;">');
					data_h.push('OK');
				data_h.push('</div>');
			data_h.push('</div>');
		data_h.push('</div>');

		handle_link({ cmd : "PopupRemove" });
		handle_link({ cmd : "PopupCreate", data : data_h });
		handle_link({ cmd : "PopupPositionCenter" });
		}

	if(state.cmd == "PopupPositionCenter")
		{
		var o = document.getElementById("popup_dialog");

		o.style.left = ((window.innerWidth - o.offsetWidth) / 2) + "px";
		o.style.top = ((window.innerHeight - o.offsetHeight) / 2) + "px";
		}

	if(state.cmd == "PopupPositionMouse")
		{
		var o = document.getElementById("popup_dialog");

		o.style.left = ((evt.mx + o.offsetWidth) > window.innerWidth ? (window.innerWidth - o.offsetWidth) : evt.mx) + "px";
		o.style.top = ((evt.my + o.offsetHeight) > window.innerHeight ? (window.innerHeight - o.offsetHeight) : evt.my) + "px";
		}

	if(state.cmd == "PopupRemove")
		{
		state.blocking = 0;

		handle_link({ cmd : "BlockingLayer" });
		}

	if(state.cmd == "PopupTitle")
		{
		var o = document.getElementById("popup_title");

		o.innerHTML = settings.title;
		}

	if(state.cmd == "PopupWipe")
		{
		var data_h = new Array();

		data_h.push('<div id="popup_dialog" class="popup_dialog" style="width: 500px;">');
			data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
				data_h.push('Gerät zurücksetzen');
			data_h.push('</div>');
			data_h.push('<div class="popup_text" id="popup_text">');
				data_h.push('Alle Daten im Speicher des Geräts werden gelöscht: Ihr Google-Konto, System- und Anwendungsdaten, Einstellungen und heruntergeladene Anwendungen.');
				data_h.push('<br>');
				data_h.push('<br>');
				data_h.push('Wenn Musik, Fotos und andere Benutzerdaten gelöscht werden sollen, muss die SD-Karte formatiert werden.');
			data_h.push('</div>');
			data_h.push('<div class="popup_buttons" id="popup_buttons">');
				for(action_id = 0; action_id < 2; action_id = action_id + 1)
					{
					data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="handle_link({ cmd : \'' + [state.ok_cmd, state.cancel_cmd][action_id] + '\' });" style="left: ' + [150, 260][action_id] + 'px;">');
						data_h.push(['OK', "Abbrechen"][action_id]);
					data_h.push('</div>');
					}
			data_h.push('</div>');
		data_h.push('</div>');

		handle_link({ cmd : "PopupRemove" });
		handle_link({ cmd : "PopupCreate", data : data_h });
		handle_link({ cmd : "PopupPositionCenter" });
		}

	if(state.cmd == "Print")
		{
		}

	if(state.cmd == "ProgressStart")
		{
		var o = document.getElementById("progress");

		data_h = new Array();

		data_h.push('<div class="progress" id="progress">');
			data_h.push('<img class="progress_image" src="images/progress_' + state.size + '.png">');
			data_h.push('<p class="progress_text">');
				data_h.push('Daten werden geladen');
				data_h.push('<br>');
				data_h.push('Bitte warten');
			data_h.push('</p>');
		data_h.push('</div>');

		////////////////////////////////////////////////////////////////////////////////

		// create progress node
		var o = document.createElement("div");

		o.innerHTML = data_h.join("");

		// insert progress node

		var n = document.body.childNodes[0];

		n.parentNode.insertBefore(o, n.nextSibling);

		handle_link({ cmd : "ProgressUpdate" });
		}

	if(state.cmd == "ProgressStop")
		{
		var o = document.getElementById("progress");

		if(o != null)
			{
			o.parentNode.parentNode.removeChild(o.parentNode);

			clearTimeout(state.timer_id);
			}
		}

	if(state.cmd == "ProgressUpdate")
		{
		state.picture_id = (state.picture_id == 31 ? 1 : state.picture_id + 1);

		var col = state.picture_id % 8;
		var row = (state.picture_id - col) / 8;

		var o = document.getElementById("progress");

		if(o != null)
			{
			o.childNodes[0].style.clip	= "rect(" + (row * state.size) + "px, " + ((col * state.size) + state.size) + "px, " + ((row * state.size) + state.size) + "px, " + (col * state.size) + "px)";
			o.childNodes[0].style.left	= (0 - (col * state.size) - (state.size / 2) + (o.childNodes[1].offsetWidth / 2)) + "px";
			o.childNodes[0].style.top	= (0 - (row * state.size) - (state.size / 2)) + "px";

			state.timer_id = setTimeout(function() { handle_link({ cmd : "ProgressUpdate" });}, 10);
			}
		}

	if(state.cmd == "Read")
		{
		var onload = function(data)
			{
			handle_link({ cmd : "PopupRemove" });
			handle_link({ cmd : "List" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=Read&CollectionId=" + state.collection_id + "&ServerId=" + state.server_id + "&LongId=" + state.long_id, success : onload });
		}

	if(state.cmd == "Reply")
		handle_link({ cmd : "Edit", long_id : "R" });

	if(state.cmd == "Reset")
		{
		handle_link({ cmd : "ResetForm" });

		switch(state.class_id)
			{
			case("Email"): // inbox
				rte_init(document.getElementById("mail_content").innerHTML, "mail_content");

				handle_link({ cmd : "SetCursorPosition", object_id : "inhalt_content", position : 0 });

				break;
			case("Tasks"): // tasks
				update_recurrence_type();

				handle_link({ cmd : "ToggleReminderSet" });
				handle_link({ cmd : "ToggleComplete" });

				break;
			case("Calendar"): // calendar
				state.time_start = document.forms[0].StartTime.value;
				state.time_end = document.forms[0].EndTime.value;

				update_recurrence_type();

				handle_link({ cmd : "UpdateAllDayEvent" });

				break;
			case("Contacts"): // contacts
				handle_link({ cmd : "SwitchAddress", item_id : document.getElementById("buffer_address").innerHTML });
				handle_link({ cmd : "SwitchContact", item_id : document.getElementById("buffer_contact").innerHTML });

				handle_link({ cmd : "ResetPicture" });
				handle_link({ cmd : "UpdateFileAs" });

				update_name_fields("init");

				document.forms[0]["FirstName"].focus();

				break;
			case("Notes"): // notes
				document.forms[0]["Body:Data"].focus();

				break;
			}
		}

	if(state.cmd == "ResetForm")
		document.forms[0].reset();

	if(state.cmd == "ResetPicture")
		{
		var o = document.getElementById("buffer_picture")

		document.getElementById("img_data").value = o.innerHTML;
		document.getElementById("img_preview").src = (o.innerHTML.length != 0 ? "data:image/unknown;base64," + o.innerHTML : "images/contacts_default_image_add.png");
		}

	if(state.cmd == "Rights")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;

			handle_link({ cmd : "RightsInit" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=Rights", success : onload });
		}

	if(state.cmd == "RightsInit")
		{
		}

	if(state.cmd == "Save")
		{
		switch(state.class_id)
			{
			case("Email"): // outbox
				break;
			case("Tasks"): // tasks
				break;
			case("Calendar"): // calendar
				break;
			case("Contacts"): // contacts
				var fields = ["Categories", "Children"];

				for(field in fields)
					{
					var o = document.getElementById(fields[field]);

					for(i = 0; i < o.options.length; i = i + 1)
						o.options[i].selected = true;
					}

				break;
			case("Notes"): // notes

				break;
			}

		var form_data = get_form_data();

		var onload = function(data)
			{
			if(data != 1)
				return;

			handle_link({ cmd : "List" });
			}

		ajax({ type : "POST", url : "index.php?Cmd=Save", data : form_data, success : onload });
		}

	if(state.cmd == "Send")
		{
		rte_mode(3);

		document.forms[0].Draft.value = 0;

		handle_link({ cmd : "Save" });
		}

	if(state.cmd == "SendContact")
		{
		// as a side effect, this is triggered before submenu is triggered

		if(state.item_id != "")
			handle_link({ cmd : "Edit", collection_id : "9002", server_id : "", item_id : "inline:" + state.collection_id + ":" + state.server_id + ":" + state.item_id });
		}

	if(state.cmd == "SendVCard")
		{
		handle_link({ cmd : "Edit", collection_id : "9002", server_id : "", item_id : "attachment:" + state.collection_id + ":" + state.server_id });
		}

	if(state.cmd == "SetCursorPosition")
		{
		var o = document.getElementById(state.object_id);

		if(o == null)
			{
			}
		else if(o.createTextRange)
			{
			var o = o.createTextRange();

			o.move("character", state.position);
			o.select();
			}
		else if(o.selectionStart)
			{
			o.focus();
			o.setSelectionRange(state.position, state.position);
			}
		else
			o.focus();
		}

	if(state.cmd == "Settings")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;
			}

		ajax({ type : "GET", url : "index.php?Cmd=Settings", success : onload });
		}

	if(state.cmd == "SettingsSave")
		{
		var form_data = get_form_data();

		var onload = function(data)
			{
			handle_link({ cmd : "Settings" });
			handle_link({ cmd : "MenuItems" });
			}

		ajax({ type : "POST", url : "index.php?Cmd=SettingsSave", data : form_data, success : onload });
		}

	if(state.cmd == "Show")
		{
		if(state.collection_id == "9003")
			{
			handle_link({ cmd : "Edit" });
			}
		else
			{
			var onload = function(data)
				{
				handle_link({ cmd : "PopupRemove" });

				document.getElementById("body_content").innerHTML = data;
				}

			ajax({ type : "GET", url : "index.php?Cmd=Show&CollectionId=" + state.collection_id + "&ServerId=" + state.server_id, success : onload });
			}
		}

	if(state.cmd == "ShowAgenda")
		{
		handle_link({ cmd : "PopupRemove" });

		calendar.time = state.time_id / 1000;

		handle_link({ cmd : "CalendarShow", view_id : "a" });
		}

	if(state.cmd == "ShowDay")
		{
		handle_link({ cmd : "PopupRemove" });

		calendar.time = state.time_id / 1000;

		handle_link({ cmd : "CalendarShow", view_id : "d" });
		}

	if(state.cmd == "ShowVCard")
		{
		location.href = "index.php?Cmd=ShowVCard&CollectionId=" + state.collection_id + "&ServerId=" + state.server_id;
		}

	if(state.cmd == "SwitchContact")
		{
		var n = ["E-Mail-Adresse", "Instant-Messenger"];

		var b = ["e", "i"];

		for(i = 0; i < b.length; i = i + 1)
			{
			document.getElementById("contact_tab_" + b[i]).innerHTML = "[" + (state.item_id == i ? n[i] : "<span class=\"span_link\" onclick=\"handle_link({ cmd : 'SwitchContact', item_id : '" + i + "' });\">" + n[i] + "</span>") + "]";
			document.getElementById("contact_page_" + b[i]).style.display = (state.item_id == i ? "block" : "none");
			}
		}

	if(state.cmd == "SwitchAddress")
		{
		var n = get_menu_items("Contacts:Page");

		var b = ["b", "h", "o"];

		for(i = 0; i < b.length; i = i + 1)
			{
			document.getElementById("address_tab_" + b[i]).innerHTML = "[" + (state.item_id == i ? popup_buttons[n[i]] : "<span class=\"span_link\" onclick=\"handle_link({ cmd : 'SwitchAddress', item_id : '" + i + "' });\">" + popup_buttons[n[i]] + "</span>") + "]";
			document.getElementById("address_page_" + b[i]).style.display = (state.item_id == i ? "block" : "none");
			}
		}

	if(state.cmd == "ToggleBlock")
		{
		document.getElementById(state.item_id).style.display = (document.getElementById(state.item_id).style.display == "none" ? "block" : "none");
		}

	if(state.cmd == "ToggleComplete")
		{
		var o = document.forms[0];

		o.DateCompleted.disabled = (o.Complete.checked ? false : true);
		}

	if(state.cmd == "ToggleExternal")
		{
		var o = document.forms[0];

		document.getElementById("external").style.display = (o.F6.checked ? "block" : "none");
		}

	if(state.cmd == "TogglePolicy")
		{
		var o = document.getElementById("policy_options");

		var s = document.getElementById("policy_selection");

		for(i = 0; i < o.childNodes.length; i = i + 1)
			{
			o.childNodes[i].style.display = (o.childNodes[i].id == s.value ? "block" : "none");
			}
		}

	if(state.cmd == "ToggleReminderSet")
		{
		var o = document.forms[0];

		o.ReminderTime.disabled = (o.ReminderSet.checked ? false : true);
		}

	if(state.cmd == "ToggleSettings")
		{
		var o = document.forms[0];

		document.getElementById("a").style.display = (o.F1.checked ? "block" : "none");
		}

	if(state.cmd == "ToggleTimes")
		{
		var o = document.forms[0];

		o.StartTime.disabled = (o.F2.checked == 0);
		o.EndTime.disabled = (o.F2.checked == 0);

		document.getElementById("times").style.color = (o.F2.checked ? "#000000" : "#808080");
		}

	if(state.cmd == "UpdateAllDayEvent")
		{
		var o = document.forms[0];

		var i = (o.AllDayEvent.checked ? 10 : 16);

		o.StartTime.value = state.time_start.substr(0, i); // public variable
		o.StartTime.maxLength = i;
		o.EndTime.value = state.time_end.substr(0, i); // public variable
		o.EndTime.maxLength = i;
		}

	if(state.cmd == "UpdateDisplayName")
		{
		var o = document.forms[0];

		var f = o.FirstName.value;
		var l = o.LastName.value;

		var n = "";

		n = n + (f ? (n ? " " : "") : "") + f;
		n = n + (l ? (n ? " " : "") : "") + l;

		o.DisplayName.value = n;
		}

	if(state.cmd == "UpdateFileAs")
		{
		var o = document.forms[0];

		var f = o.FirstName.value;
		var m = o.MiddleName.value;
		var l = o.LastName.value;
		var s = o.Suffix.value;

		var n = "";

		n = n + (f ? (n ? " " : "") : "") + f;
		n = n + (m ? (n ? " " : "") : "") + m;
		n = n + (l ? (n ? " " : "") : "") + l;
		n = n + (s ? (n ? ", " : "") : "") + s;

		o.FileAs.value = n;
		}

	if(state.cmd == "UpdateRecurrenceDayOfWeek")
		{
		var o = document.getElementById("Recurrence:WeekOfMonth");

		o.style.display = "none";

		if(document.forms[0]["Recurrence:DayOfWeek"].value == 127)
			{
			}
		else if(document.forms[0]["Recurrence:Type"].value == 3)
			{
			o.style.display = "block";
			}
		else if(document.forms[0]["Recurrence:Type"].value == 6)
			{
			o.style.display = "block";
			}

		////////////////////////////////////////////////////////////////////////////////

		if(expression == null)
			{
			return;
			}

		////////////////////////////////////////////////////////////////////////////////

		var o = document.forms[0]['Recurrence:DayOfWeek'];

		for(i = 0; i < o.length; i = i + 1)
			{
			if(o[i].value == expression)
				{
				o.selectedIndex = i;

				break;
				}
			}
		}

	if(state.cmd == "User")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;
			}

		ajax({ type : "GET", url : "index.php?Cmd=User", success : onload });
		}

	if(state.cmd == "UserDelete")
		{
		var onload = function(data)
			{
			handle_link({ cmd : "PopupRemove" });
			handle_link({ cmd : "User" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=UserDelete&User=" + state.user, success : onload });
		}

	if(state.cmd == "UserDeleteConfirm")
		{
		state.title = "Benutzer löschen";
		state.text = "Dieser Benutzer wird gelöscht.";

		state.ok_cmd = "UserDelete";
		state.cancel_cmd = "Nothing";

		handle_link({ cmd : "PopupDelete" });
		}

	if(state.cmd == "UserEdit")
		{
		var onload = function(data)
			{
			document.getElementById("body_content").innerHTML = data;

			handle_link({ cmd : "ResetForm" });
			}

		ajax({ type : "GET", url : "index.php?Cmd=UserEdit&User=" + state.user, success : onload });
		}

	if(state.cmd == "UserSave")
		{
		var form_data = get_form_data();

		var onload = function(data)
			{
			handle_link({ cmd : "User" });
			}

		ajax({ type : "POST", url : "index.php?Cmd=UserSave", data : form_data, success : onload });
		}

	if(state.cmd == "MenuItems")
		{
		var onload = function(data)
			{
			document.getElementById("menu_content").innerHTML = data;
			}

		ajax({ type : "GET", url : "index.php?Cmd=MenuItems", success : onload });
		}
	}

