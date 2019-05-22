<?
function active_sync_handle_get_hierarchy($request)
	{
	# request is always empty

	$response = new active_sync_wbxml_response();

	$response->x_switch("FolderHierarchy");

	$response->x_open("Folders");

		$folders = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		foreach($folders as $folder)
			{
			$response->x_open("Folder");

				foreach(array("ServerId", "ParentId", "DisplayName", "Type") as $token);
					{
					$response->x_open($token);
						$response->x_print($folder[$token]);
					$response->x_close($token);
					}

			$response->x_close("Folder");
			}

	$response->x_close("Folders");

	return($response->response);
	}
?>
