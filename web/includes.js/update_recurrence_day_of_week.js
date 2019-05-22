function update_recurrence_day_of_week(expression)
	{
	var o = document.getElementById("Recurrence:WeekOfMonth");

	o.style.display = "none";

	if(document.forms[0]["Recurrence:DayOfWeek"].value == 127)
		{
		}
	else if(document.forms[0]["Recurrence:Type"].value == 3)
		o.style.display = "block";
	else if(document.forms[0]["Recurrence:Type"].value == 6)
		o.style.display = "block";

	////////////////////////////////////////////////////////////////////////////////

	if(expression == null)
		return;

	////////////////////////////////////////////////////////////////////////////////

	var o = document.forms[0]['Recurrence:DayOfWeek'];

	for(i = 0; i < o.length; i = i + 1)
		if(o[i].value == expression)
			{
			o.selectedIndex = i;

			break;
			}
	}

