<?
function active_sync_web_edit_contacts($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_contacts() as $token => $value)
		$data["Contacts"][$token] = (isset($data["Contacts"][$token]) === false ? $value : $data["Contacts"][$token]);

	foreach(active_sync_get_default_contacts2() as $token => $value)
		$data["Contacts2"][$token] = (isset($data["Contacts2"][$token]) === false ? $value : $data["Contacts2"][$token]);

	if(isset($data["Body"]) === false)
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		{
		if(isset($body["Type"]) === false)
			continue;

		if($body["Type"] != 1)
			continue;

		foreach(active_sync_get_default_body() as $token => $value)
			$data["Body"][0][$token] = (isset($body[$token]) === false ? $value : $body[$token]);
		}

	foreach(array("Categories", "Children") as $key)
		$data[$key] = (isset($data[$key]) === false ? array() : $data[$key]);

	foreach(array("Anniversary", "Birthday") as $key)
		$data["Contacts"][$key] = ($data["Contacts"][$key] == "" ? "" : date("d.m.Y", strtotime($data["Contacts"][$key])));

	foreach(array("Email1Address", "Email2Address", "Email3Address") as $key)
		list($null, $data["Contacts"][$key]) = active_sync_mail_parse_address($data["Contacts"][$key]);

	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"width: 100%; height: 100%;\" valign=\"top\">");
				print("<form style=\"height: 100%;\">");
					print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
					print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
					print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
					print("<table style=\"height: 100%;\">");
						print("<tr>");
							print("<td valign=\"top\">");
								print("<table>");
									print("<tr>");
										print("<td style=\"cursor: default;\" id=\"address_tab_b\">");
											# nothing to display yet
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"cursor: default;\" id=\"address_tab_h\">");
											# nothing to display yet
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"cursor: default;\" id=\"address_tab_o\">");
											# nothing to display yet
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
						print("</tr>");
						print("<tr style=\"height: 200px; \">");
							print("<td style=\"vertical-align: top;\">");

								$weight_address = array(0, 0, 0); # Work, Home, Other

								$fields = array(array(0, "b", array("BusinessAddressStreet" => "Straße", "BusinessAddressCity" => "Stadt", "BusinessAddressState" => "Bundesland", "BusinessAddressPostalCode" => "Postleitzahl", "BusinessAddressCountry" => "Land", "BusinessPhoneNumber" => "Telefon", "Business2PhoneNumber" => "Telefon", "BusinessFaxNumber" => "Fax")), array(1, "h", array("HomeAddressStreet" => "Straße", "HomeAddressCity" => "Stadt", "HomeAddressState" => "Bundesland", "HomeAddressPostalCode" => "Postleitzahl", "HomeAddressCountry" => "Land", "HomePhoneNumber" => "Telefon", "Home2PhoneNumber" => "Telefon", "HomeFaxNumber" => "Fax")), array(2, "o", array("OtherAddressStreet" => "Straße", "OtherAddressCity" => "Stadt", "OtherAddressState" => "Bundesland", "OtherAddressPostalCode" => "Postleitzahl", "OtherAddressCountry" => "Land")));

								foreach($fields as $field_data)
									{
									list($weight_id, $page_id, $tokens) = $field_data;

									print("<span id=\"address_page_" . $page_id . "\" style=\"display: none;\">");

										foreach($tokens as $token => $value)
											{
											print("<table>");
												print("<tr>");
													print("<td class=\"field_label\">");
														print($value);
													print("</td>");
													print("<td>");
														print(":");
													print("</td>");
													print("<td>");
														print("<input");
														print(" ");
														print("type=\"text\"");
														print(" ");
														print("name=\"" . $token . "\"");
														print(" ");
														print("class=\"xi\"");
														print(" ");
														print("id=\"" . $token . "\"");

														if(strpos($token, "Address") !== false)
															{
															print(" onfocus=\"suggest_register(this.id, '" . $_GET["CollectionId"] . "', 0);\"");
															}

														if(strpos($token, "Phone") !== false)
															{
															print(" onfocus=\"suggest_register(this.id, '" . $_GET["CollectionId"] . "', 0);\"");
															}

														print(" value=\"" . $data["Contacts"][$token] . "\"");
														print(">");
													print("</td>");
												print("</tr>");
											print("</table>");

											$weight_address[$weight_id] = $weight_address[$weight_id] + ($data["Contacts"][$token] != "" ? 1 : 0);
											}

										$weight_address[$weight_id] = 100 / count($tokens) * $weight_address[$weight_id];
									print("</span>");
									}

								natcasesort($weight_address);

								$weight_address = array_keys($weight_address);

								$weight_address = end($weight_address);

							print("</td>");
							print("<td style=\"width: 32px;\">");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("<input type=\"hidden\" id=\"img_data\" name=\"Picture\">");

								print("<table style=\"height: 100%; width: 100%;\">");
									print("<tr>");
										print("<td style=\"width: 165px;\">");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td style=\"height: 100%; text-align: center; border: none;\">");
											# image is stored with 69 x 69 pixels, but we have enough space, so display it in double size
											print("<img style=\"height: 108px;\" class=\"xl\" id=\"img_preview\" onclick=\"handle_link({ cmd : 'PictureLoad' });\" src=\"images/contacts_default_image_add.png\">");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
										print("<td>");
											print("[");
												print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PictureLoad' });\">");
													print("Hinzufügen");
												print("</span>");
											print("]");
											print(" ");
											print("[");
												print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'PictureDelete' });\">");
													print("Löschen");
												print("</span>");
											print("]");
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("<div style=\"background-color: #000000; height: 1px;\">");
								print("</div>");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("<div style=\"background-color: #000000; height: 1px;\">");
								print("</div>");
							print("</td>");
						print("</tr>");
						print("<tr style=\"height: 100%;\">");
							print("<td style=\"vertical-align: top;\">");

								foreach(array("Title" => "Namenspräfix", "FirstName" => "Vorname", "MiddleName" => "Zweiter Vorname", "LastName" => "Nachname", "Suffix" => "Namenssuffix", "YomiFirstName" => "Phonetischer Vorname", "YomiLastName" => "Phonetischer Nachname", "Anniversary" => "Jahrestag", "AssistantName" => "...", "AssistnamePhoneNumber" => "...", "Birthday" => "Geburtstag", "CompanyName" => "Firma", "Department" => "Abteilung", "FileAs" => "...", "JobTitle" => "Beruf", "CarPhoneNumber" => "...", "MobilePhoneNumber" => "Mobiltelefon", "OfficeLocation" => "Büro", "PagerNumber" => "Pager", "RadioPhoneNumber" => "Funk", "Spouse" => "Ehepartner", "WebPage" => "Webseite", "Alias" => "...", "WeightedRank" => "...") as $token => $value)
									{
									switch($token)
										{
										case("Anniversary"):
										case("Birthday"):

											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("Title"):
										case("FirstName"):
										case("MiddleName"):
										case("LastName"):
										case("Suffix"):
										case("YomiFirstName"):
										case("YomiLastName"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" id=\"". $token . "\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\" onchange=\"handle_link({ cmd : 'UpdateFileAs' });\">");
														print("</td>");
														print("<td>");
															# nothing to display yet
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("Spouse"):
										case("CompanyName"):
										case("Department"):
										case("JobTitle"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\" id=\"". $token . "\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("FileAs"):
										case("WeightedRank"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("RadioPhoneNumber"):
										case("CarPhoneNumber"):
										case("AssistnamePhoneNumber"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("Alias"):
										case("AssistantName"):
										case("OfficeLocation"):
											print("<input type=\"hidden\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\">");

											break;
										case("MobilePhoneNumber"):
										case("PagerNumber"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("WebPage"):
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										default:
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										}
									}

								foreach(array("NickName" => "Spitzname", "CustomerId" => "Kundennummer", "GovernmentId" => "...", "ManagerName" => "...", "CompanyMainPhone" => "...", "AccountName" => "...", "MMS" => "...") as $token => $value)
									{
									switch($token)
										{
										case("CustomerId");
										case("NickName");
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts2"][$token] . "\" class=\"xi\" autocomplete=\"off\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										case("AccountName");
										case("CompanyMainPhone");
										case("GovernmentId");
										case("ManagerName");
										case("MMS");
											print("<input type=\"hidden\" name=\"". $token . "\" value=\"" . $data["Contacts2"][$token] . "\">");

											break;
										default:
											print("<div id=\"Hide" . $token . "\">");
												print("<table>");
													print("<tr>");
														print("<td class=\"field_label\">");
															print($value);
														print("</td>");
														print("<td>");
															print(":");
														print("</td>");
														print("<td>");
															print("<input type=\"text\" name=\"" . $token . "\" value=\"" . $data["Contacts2"][$token] . "\" class=\"xi\">");
														print("</td>");
													print("</tr>");
												print("</table>");
											print("</div>");

											break;
										}
									}

								print("<table>");
									print("<tr>");
										print("<td class=\"field_label\">");
											print("Memo");
										print("</td>");
										print("<td>:</td>");
										print("<td>");
											print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
											print("<input type=\"hidden\" name=\"Body:EstimatedDataSize\">"); # not stored by device, so ...
											print("<textarea name=\"Body:Data\" class=\"xt\">");
												print($data["Body"][0]["Data"]);
											print("</textarea>");
										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
							print("<td style=\"width: 32px;\">");
								print("&nbsp;");
							print("</td>");
							print("<td style=\"vertical-align: top;\">");
								print("<table style=\"height: 100%; width: 100%;\">");
									print("<tr>");
										print("<td>");
											print("<table>");
												print("<tr>");
													print("<td style=\"cursor: default;\" id=\"contact_tab_e\">");
														# nothing to display yet
													print("</td>");
													print("<td>");
														print("&nbsp;");
													print("</td>");
													print("<td style=\"cursor: default;\" id=\"contact_tab_i\">");
														# nothing to display yet
													print("</td>");
												print("</tr>");
											print("</table>");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("&nbsp;");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");

											$weight_contact = array(0, 0); # email, im

											$fields = array(array(0, "e", "Contacts", array("Email1Address" => "E-Mail-Adresse", "Email2Address" => "E-Mail-Adresse", "Email3Address" => "E-Mail-Adresse")), array(1, "i", "Contacts2", array("IMAddress" => "Instant-Messenger", "IMAddress2" => "Instant-Messenger", "IMAddress3" => "Instant-Messenger")));

											foreach($fields as $field_data)
												{
												list($weight_id, $page_id, $codepage, $tokens) = $field_data;

												print("<span id=\"contact_page_" . $page_id . "\" style=\"display: none;\">");

													foreach($tokens as $token => $value)
														{
														print("<table>");
															print("<tr>");
																print("<td class=\"field_label\">");
																	print($value);
																print("</td>");
																print("<td>");
																	print(":");
																print("</td>");
																print("<td>");
																	print("<input type=\"text\" name=\"". $token . "\" class=\"xi\" value=\"" . $data[$codepage][$token] . "\">");
																print("</td>");
																print("<td>");

																	if($page_id == "e")
																		{
																		print("<img class=\"xl\" onclick=\"handle_link({ cmd : 'Edit' , collection_id : '9002', server_id : '', item_id : '" . $data[$codepage][$token] . "' });\" src=\"images/contacts_list_email_icon_small.png\">");
																		}

																	if($page_id == "i")
																		{
																		print("<img class=\"xl\" onclick=\"handle_link({ cmd : 'IM', item_id : '" . $data[$codepage][$token] . "' });\" src=\"images/contacts_list_im_icon_small.png\">");
																		}

																print("</td>");
															print("</tr>");
														print("</table>");

														$weight_contact[$weight_id] = $weight_contact[$weight_id] + ($data[$codepage][$token] != "" ? 1 : 0);
														}

													$weight_contact[$weight_id] = 100 / count($tokens) * $weight_contact[$weight_id];
												print("</span>");
												}

											natcasesort($weight_contact);

											$weight_contact = array_keys($weight_contact);

											$weight_contact = end($weight_contact);

										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td>");
											print("<div style=\"background-color: #000000; height: 1px;\">");
											print("</div>");
										print("</td>");
									print("</tr>");
									print("<tr>");
										print("<td style=\"height: 100%;\">");

											foreach(array(array("Category", "Categories", "Gruppen"), array("Child", "Children", "Kinder")) as $token)
												{
												print("<table style=\"height: 50%; width: 100%;\">");
													print("<tr>");
														print("<td class=\"field_label\" style=\"vertical-align: top;\">");
															print($token[2]);
														print("</td>");
														print("<td style=\"vertical-align: top;\">");
															print(":");
														print("</td>");
														print("<td style=\"height: 100%;\">");
															asort($data[$token[1]], SORT_LOCALE_STRING);

															print("<table style=\"height: 100%; width: 100%\">");
																print("<tr>");
																	print("<td style=\"height: 100%;\">");
																		print("<select id=\"" . $token[1] . "\" name=\"" . $token[1] . "[]\" ondblclick=\"this.remove(this.selectedIndex);\" size=\"2\" style=\"height: 100%; width: 250px;\" multiple>");

																			foreach($data[$token[1]] as $item)
																				{
																				print("<option value=\"" . $item . "\">");
																					print($item);
																				print("</option>");
																				}

																		print("</select>");
																	print("</td>");
																print("</tr>");
																print("<tr>");
																	print("<td>");
																		print("<input type=\"text\" class=\"xi\" id=\"" . $token[0] . "\" onfocus=\"options_handle_contacts('" . $token[0] . "', '" . $token[1] . "');\">");
																	print("</td>");
																print("</tr>");
															print("</table>");
														print("</td>");
													print("</tr>");
												print("</table>");
												}

										print("</td>");
									print("</tr>");
								print("</table>");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</form>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("&nbsp;");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

						if($request["ServerId"] != "")
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");

						print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
	print("</table>");

	print("<span id=\"buffer_address\" style=\"display: none;\">" . $weight_address . "</span>");
	print("<span id=\"buffer_contact\" style=\"display: none;\">" . $weight_contact . "</span>");
	print("<span id=\"buffer_picture\" style=\"display: none;\">" . $data["Contacts"]["Picture"] . "</span>");
	print("<script language=\"JavaScript\">");
	print("var general_data = " . json_encode($data) . ";");
	print("</script>");
	}
?>
