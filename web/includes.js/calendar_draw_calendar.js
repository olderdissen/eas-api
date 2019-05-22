// to much automation in here
// make things more static
// make automatic things later
// especcially this colorize things

function calendar_draw_calendar()
	{
	var time_c	= new Date(); // current
	var time_s	= new Date(); // selected
	var time_w	= new Date(); // work

	var range_s	= new Date(); // range start
	var range_e	= new Date(); // range end

	var data_h	= new Array();

	data_h.push('<table class="calendar">');

		////////////////////////////////////////////////////////////////////////////////
		// row for title
		////////////////////////////////////////////////////////////////////////////////

		data_h.push('<tr>');
			data_h.push('<td id="calendar_row_for_title" style="padding: 0px;">');
				data_h.push('<table class="calendar_title">');
					data_h.push('<tr>');

						////////////////////////////////////////////////////////////////////////////////
						// previous-button
						////////////////////////////////////////////////////////////////////////////////

						if((calendar.style == 'd') || (calendar.style == 'w') || (calendar.style == 'm') || (calendar.style == 'y'))
							{
							time_w.setTime(calendar.time * 1000);

							if(calendar.style == 'd')
								time_w.setDate(time_w.getDate() - 1); // - 1 day

							if(calendar.style == 'w')
								time_w.setDate(time_w.getDate() - 7); // - 1 week

							if(calendar.style == 'm')
								time_w.setMonth(time_w.getMonth() - 1); // - 1 month

							if(calendar.style == 'y')
								time_w.setFullYear(time_w.getFullYear() - 1); // - 1 year

							data_h.push('<td class="calendar_title_previous">');
								data_h.push('<img onclick="handle_link({ cmd : \'CalendarJumpTo\', time_id : ' + time_w.getTime() + ' });" src="images/4608.png" style="cursor: pointer;">');
							data_h.push('</td>');
							}

						////////////////////////////////////////////////////////////////////////////////
						// title
						////////////////////////////////////////////////////////////////////////////////

						if(calendar.style == 'a')
							{
							data_h.push('<td class="calendar_title_agenda">');
								data_h.push('<b>');
									data_h.push('Agenda');
								data_h.push('</b>');
							data_h.push('</td>');
							}

						if(calendar.style == 'd')
							{
							time_w.setTime(calendar.time * 1000)

							class_title = (time_c.getFullYear() == time_w.getFullYear() ? (time_c.getMonth() == time_w.getMonth() ? (time_c.getDate() == time_w.getDate() ? 'calendar_title_day_active' : 'calendar_title_day_inactive') : 'calendar_title_day_inactive') : 'calendar_title_day_inactive');

							data_h.push('<td class="' +  class_title + '">');
								data_h.push(calendar.day_of_week[(time_w.getDay() + 6) % 7]);
								data_h.push(', ');
								data_h.push(time_w.getDate() < 10 ? '0' : '');
								data_h.push(time_w.getDate());
								data_h.push('.');
								data_h.push(time_w.getMonth() < 9 ? '0' : '');
								data_h.push(time_w.getMonth() + 1);
								data_h.push('.');
								data_h.push(time_w.getFullYear());
							data_h.push('</td>');
							}

						if(calendar.style == 'w')
							{
							range_s.setTime(calendar.time * 1000);
							range_e.setTime(calendar.time * 1000);

							while(range_s.getDay() != 1) // if not monday
								{
								range_s.setDate(range_s.getDate() - 1); // - 1 day
								}

							while(range_e.getDay() != 1) // if not monday
								{
								range_e.setDate(range_e.getDate() - 1); // - 1 day
								}

							range_e.setDate(range_e.getDate() + 7); // + 1 week

							data_h.push('<td class="calendar_title_week">');
								data_h.push(calendar.month_of_year[range_s.getMonth()]);
								data_h.push(' ');
								data_h.push(range_s.getFullYear());

								if(range_s.getMonth() != range_e.getMonth())
									{
									data_h.push(' - ');
									data_h.push(calendar.month_of_year[range_e.getMonth()]);
									data_h.push(' ');
									data_h.push(range_e.getFullYear());
									}
							data_h.push('</td>');
							}

						if(calendar.style == 'm')
							{
							time_w.setTime(calendar.time * 1000)

							data_h.push('<td class="calendar_title_month">');
								data_h.push(calendar.month_of_year[time_w.getMonth()]);
								data_h.push(' ');
								data_h.push(time_w.getFullYear());
							data_h.push('</td>');
							}

						if(calendar.style == 'y')
							{
							time_w.setTime(calendar.time * 1000)

							data_h.push('<td class="calendar_title_year">');
								data_h.push(time_w.getFullYear());
							data_h.push('</td>');
							}

						////////////////////////////////////////////////////////////////////////////////
						// next-button
						////////////////////////////////////////////////////////////////////////////////

						if((calendar.style == 'd') || (calendar.style == 'w') || (calendar.style == 'm') || (calendar.style == 'y'))
							{
							time_w.setTime(calendar.time * 1000);

							if(calendar.style == 'd')
								time_w.setDate(time_w.getDate() + 1); // + 1 day

							if(calendar.style == 'w')
								time_w.setDate(time_w.getDate() + 7); // + 1 week

							if(calendar.style == 'm')
								time_w.setMonth(time_w.getMonth() + 1); // + 1 month

							if(calendar.style == 'y')
								time_w.setFullYear(time_w.getFullYear() + 1); // + 1 year

							data_h.push('<td class="calendar_title_next">');
								data_h.push('<img onclick="handle_link({ cmd : \'CalendarJumpTo\', time_id : ' + time_w.getTime() + ' });" src="images/4607.png" style="cursor: pointer;">');
							data_h.push('</td>');
							}

						////////////////////////////////////////////////////////////////////////////////
						// android work-around
						////////////////////////////////////////////////////////////////////////////////

						if(navigator.userAgent.match(/android/i) == null)
							{
							data_h.push('<td style="padding: 0px; width: 16px;">');
								data_h.push('&nbsp;');
							data_h.push('</td>');
							}

					data_h.push('</tr>');
				data_h.push('</table>');
			data_h.push('</td>');
		data_h.push('</tr>');

		////////////////////////////////////////////////////////////////////////////////
		// row for weekdays
		////////////////////////////////////////////////////////////////////////////////

		if((calendar.style == 'w') || (calendar.style == 'm') || (calendar.style == 'y'))
			{
			data_h.push('<tr>');
				data_h.push('<td id="calendar_row_for_weekdays" style="padding: 0px;">');
					data_h.push('<table class="calendar_weekdays">');
						data_h.push('<tr>');

							if(calendar.style == 'w')
								{
								time_s.setTime(calendar.time * 1000);
								time_w.setTime(calendar.time * 1000);

								while(time_w.getDay() != 1)
									{
									time_w.setDate(time_w.getDate() - 1);
									}

								time_w.setHours(0);
								time_w.setMinutes(0);
								time_w.setSeconds(0);
								time_w.setMilliseconds(0);

								data_h.push('<td class="calendar_weekdays_spacer">');
									data_h.push('&nbsp;');
								data_h.push('</td>');

								for(day_id = 0; day_id < 7; day_id = day_id + 1)
									{
									class_weekday = (day_id > 4 ? 'calendar_weekdays_weekend' : 'calendar_weekdays_weekday');
									class_weekday = (time_c.getFullYear() == time_w.getFullYear() ? (time_c.getMonth() == time_w.getMonth() ? (time_c.getDate() == time_w.getDate() ? ' calendar_weekdays_today' : class_weekday) : class_weekday) : class_weekday);
									class_weekday = (time_s.getFullYear() == time_w.getFullYear() ? (time_s.getMonth() == time_w.getMonth() ? (time_s.getDate() == time_w.getDate() ? ' calendar_weekdays_selected' : class_weekday) : class_weekday) : class_weekday);

									data_h.push('<td class="' + class_weekday + '">');
										data_h.push('<div style="position: relative; width: 100%;">');
											data_h.push('<div style="position: absolute; text-align: center; top: -7px; width: 100%;">');
												data_h.push(calendar.day_of_week[day_id].substr(0, 1));
												data_h.push(time_w.getDate() < 10 ? ' 0' : ' ');
												data_h.push(time_w.getDate());
											data_h.push('</div>');
										data_h.push('</div>');
									data_h.push('</td>');

									time_w.setDate(time_w.getDate() + 1); // + 1 day
									}
								}

							if(calendar.style == 'm')
								{
								for(day_id = 0; day_id < 7; day_id = day_id + 1)
									{
									class_weekday = (day_id > 4 ? 'calendar_weekdays_weekend' : 'calendar_weekdays_weekday');

									data_h.push('<td class="' + class_weekday + '" style="border-bottom: 0px;">');
										data_h.push('<div style="position: relative; width: 100%;">');
											data_h.push('<div style="position: absolute; text-align: center; top: -7px; width: 100%;">');
												data_h.push(calendar.day_of_week[day_id].substr(0, 2));
											data_h.push('</div>');
										data_h.push('</div>');
									data_h.push('</td>');
									}
								}

							if(calendar.style == 'y')
								{
								data_h.push('<td class="calendar_months_spacer">');
									data_h.push('&nbsp;');
								data_h.push('</td>');

								for(month_id = 0; month_id < 12; month_id = month_id + 1)
									{
									data_h.push('<td class="calendar_months_month">');
										data_h.push('<div style="position: relative; width: 100%;">');
											data_h.push('<div style="position: absolute; text-align: center; top: -7px; width: 100%;">');
												data_h.push(calendar.month_of_year[month_id].substr(0, 3));
											data_h.push('</div>');
										data_h.push('</div>');
									data_h.push('</td>');
									}
								}

							if(navigator.userAgent.match(/android/i) == null)
								{
								data_h.push('<td style="padding: 0px; width: 16px;">');
									data_h.push('&nbsp;');
								data_h.push('</td>');
								}

						data_h.push('</tr>');
					data_h.push('</table>');
				data_h.push('</td>');
			data_h.push('</tr>');
			}

		////////////////////////////////////////////////////////////////////////////////
		// row for all day events
		////////////////////////////////////////////////////////////////////////////////

		if((calendar.style == 'd') || (calendar.style == 'w'))
			{
			data_h.push('<tr>');
				data_h.push('<td id="calendar_row_for_all_day_events" style="padding: 0px;">');
					data_h.push('<table style="border-collapse: collapse; height: 24px; table-layout: fixed;' + (navigator.userAgent.match(/msie/i) ? '' : ' width: 100%;') + '; display: none;">');
						data_h.push('<tr>');

							data_h.push('<td style="padding: 0px; vertical-align: top;">');

								data_h.push('<div id="calendar_all_day_events" style="background-color: #FF0000; position: absolute; width: 100%;">');
								data_h.push('</div>');

								data_h.push('<table style="border-collapse: collapse; height: 100%; table-layout: fixed; width: 100%;">');
									data_h.push('<tr>');
										data_h.push('<td class="calendar_weekdays_spacer">');
											data_h.push('&nbsp;');
										data_h.push('</td>');

										if(calendar.style == 'd')
											{
											data_h.push('<td class="calendar_weekdays_weekday">');
												data_h.push('&nbsp;');
											data_h.push('</td>');
											}

										if(calendar.style == 'w')
											{
											for(day_id = 0; day_id < 7; day_id = day_id + 1)
												{
												data_h.push('<td class="calendar_weekdays_weekday">');
													data_h.push('&nbsp;');
												data_h.push('</td>');
												}
											}

										if(navigator.userAgent.match(/android/i) == null)
											{
											data_h.push('<td style="padding: 0px; width: 16px;">');
												data_h.push('&nbsp;');
											data_h.push('</td>');
											}

									data_h.push('</tr>');
								data_h.push('</table>');

							data_h.push('</td>');

						data_h.push('</tr>');
					data_h.push('</table>');
				data_h.push('</td>');
			data_h.push('</tr>');
			}

		////////////////////////////////////////////////////////////////////////////////
		// row for calendar
		////////////////////////////////////////////////////////////////////////////////

		data_h.push('<tr>');
			data_h.push('<td id="calendar_row_for_calendar" style="height: 100%; padding: 0px;">');
				data_h.push('<div id="tbl_scroll" style="display: none; height: 100%; overflow-y: scroll; position: relative; width: 100%;">');
					data_h.push('<table style="border-collapse: collapse; height: 100%; table-layout: fixed;' + (navigator.userAgent.match(/msie/i) ? '' : ' width: 100%;') + '">');
						data_h.push('<tr>');

							data_h.push('<td style="padding: 0px; vertical-align: top;">');
								if(calendar.style == 'a')
									{
									data_h.push('<div id="calendar_scroll" style="height: 100%; width: 100%;">');
										data_h.push('Es konnten keine Einträge gefunden werden');
									data_h.push('</div>');
									}

								if((calendar.style == 'd') || (calendar.style == 'w'))
									{
									data_h.push('<div id="calendar_events" style="background-color: #FF0000; position: absolute; width: 100%;">');
									data_h.push('</div>');

									data_h.push('<div id="calendar_marker" class="calendar_marker"></div>');

									data_h.push('<table id="calendar_scroll" style="border-collapse: collapse; height: 100%; table-layout: fixed; width: 100%;">');

										time_s.setTime(calendar.time * 1000);
										time_w.setTime(calendar.time * 1000);

										for(hour_id = 0; hour_id < 24; hour_id = hour_id + 1)
											{
											time_w.setHours(hour_id);
											time_w.setMinutes(0);
											time_w.setSeconds(0);
											time_w.setMilliseconds(0);

											data_h.push('<tr>');
												color_id = '#E0E0E0';
												color_id = (time_s.getHours() == time_w.getHours() ? '#4080FF' : color_id);

												data_h.push('<td style="background-color: ' + color_id + '; border-color: #000000; border-style: solid; border-width: ' + (hour_id == 0 ? 0 : 1) + 'px 1px 1px 1px; height: 40px; padding: 0px; text-align: center; width: 28px;">');
													data_h.push(hour_id < 10 ? '0' : '');
													data_h.push(hour_id);
												data_h.push('</td>');

												if(calendar.style == 'd')
													{
													class_hour = 'calendar_day_hour_other';
													class_hour = (time_s.getHours() == time_w.getHours() ? 'calendar_day_hour_selected' : class_hour);

													data_h.push('<td onmousedown="calendar_mark_selection(' + time_w.getTime() + '); popup_calendar_menu(event, \'' + calendar.style + '\', ' + time_w.getTime() + ', \'\');" class="' + class_hour + '">');
														data_h.push('&nbsp;');
													data_h.push('</td>');
													}

												if(calendar.style == 'w')
													{
													while(time_w.getDay() != 1)
														time_w.setDate(time_w.getDate() - 1);

													for(day_id = 0; day_id < 7; day_id = day_id + 1)
														{
														class_hour = 'calendar_day_hour_other';
														class_hour = (time_s.getDate() == time_w.getDate() ? (time_s.getHours() == time_w.getHours() ? 'calendar_day_hour_selected' : class_hour) : class_hour);

														data_h.push('<td onmousedown="calendar_mark_selection(' + time_w.getTime() + '); popup_calendar_menu(event, \'' + calendar.style + '\', ' + time_w.getTime() + ', \'\');" class="' + class_hour + '">');
															data_h.push('&nbsp;');
														data_h.push('</td>');

														time_w.setDate(time_w.getDate() + 1); // + 1 day
														}

													time_w.setDate(time_w.getDate() - 7); // - 1 week
													}

											data_h.push('</tr>');
											}

									data_h.push('</table>');
									}

								if(calendar.style == 'm')
									{
									data_h.push('<table id="calendar_events" class="calendar_month">');

										time_w.setTime(calendar.time * 1000);
										time_s.setTime(calendar.time * 1000);

										time_w.setDate(1);

										while(time_w.getDay() != 1)
											time_w.setDate(time_w.getDate() - 1);

										time_w.setHours(0);
										time_w.setMinutes(0);
										time_w.setSeconds(0);
										time_w.setMilliseconds(0);

										for(week_id = 0; week_id < 6; week_id = week_id + 1)
											{
											data_h.push('<tr>');
												for(day_id = 0; day_id < 7; day_id = day_id + 1)
													{
													class_day = "calendar_month_day";
													class_day = (time_c.getFullYear() == time_w.getFullYear() ? (time_c.getMonth() == time_w.getMonth() ? (time_c.getDate() == time_w.getDate() ? "calendar_month_day_today" : class_day) : class_day) : class_day);
													class_day = (time_s.getFullYear() == time_w.getFullYear() ? (time_s.getMonth() == time_w.getMonth() ? (time_s.getDate() == time_w.getDate() ? "calendar_month_day_selected" : class_day) : class_day) : class_day);

													class_day = (time_s.getMonth() == time_w.getMonth() ? class_day : "calendar_month_day_other");

													data_h.push('<td onmousedown="calendar_mark_selection(' + time_w.getTime() + '); popup_calendar_menu(event, \'' + calendar.style + '\', ' + time_w.getTime() + ', \'\')" class="' + class_day + '">');
														data_h.push('<div class="calendar_month_day_container">');

															data_h.push('<div class="calendar_month_day_text">');
																data_h.push(time_w.getDate() < 10 ? '0' : '');
																data_h.push(time_w.getDate());
															data_h.push('</div>');

															data_h.push('<div class="calendar_month_event_bar">');
															data_h.push('</div>');

														data_h.push('</div>');
													data_h.push('</td>');

													time_w.setDate(time_w.getDate() + 1); // + 1 day
													}

											data_h.push('</tr>');
											}

									data_h.push('</table>');
									}

								if(calendar.style == 'y')
									{
									time_w.setTime(calendar.time * 1000);

									data_h.push('<table id="calendar_events" class="calendar_year">');
										for(day_id = 0; day_id < 31; day_id = day_id + 1)
											{
											data_h.push('<tr>');
												data_h.push('<td class="calendar_year_day">');
													data_h.push(day_id < 9 ? '0' : '');
													data_h.push(day_id + 1);
												data_h.push('</td>');

												for(month_id = 0; month_id < 12; month_id = month_id + 1)
													{
													time_w.setDate(day_id + 1);
													time_w.setMonth(month_id);

													data_h.push('<td class="' + (((time_w.getDay() + 6) % 7) > 4 ? 'calendar_year_weekend' : 'calendar_year_weekday') + '">');
														data_h.push('&nbsp;');
														data_h.push(time_w.getMonth() != month_id ? '&nbsp;' : calendar.day_of_week[(time_w.getDay() + 6) % 7].substr(0, 2));
													data_h.push('</td>');
													}
											data_h.push('</tr>');
											}
									data_h.push('</table>');
									}

							data_h.push('</td>');

						data_h.push('</tr>');
					data_h.push('</table>');
				data_h.push('</div>');
			data_h.push('</td>');
		data_h.push('</tr>');
	data_h.push('</table>');

	////////////////////////////////////////////////////////////////////////////////
	// draw calendar
	////////////////////////////////////////////////////////////////////////////////

	var o = document.getElementById(calendar.target);

	if(o != null)
		o.innerHTML = data_h.join('');

	////////////////////////////////////////////////////////////////////////////////
	// draw line
	////////////////////////////////////////////////////////////////////////////////

	handle_link({ cmd : "CalendarScrollPositionJump" });
	handle_link({ cmd : "CalendarDrawLine" });
	}

