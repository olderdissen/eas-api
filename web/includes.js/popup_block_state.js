function popup_block_state(user, collection_id, device_id, state)
	{
	onload = function(data)
		{
		if(data == 3)
			{
			}
		else if(user == '')
			{
			var v = JSON.parse(data);

			for(user in v['users'])
				{
				var d = v['users'][user];

				document.getElementById('BLOCK_' + user).innerHTML = '[<span class="span_link" onclick="handle_link({ cmd : \'BlockStateConfirm\', long_id : \'7\', item_id : \'' + ['1', '0'][d] + '\', user : \'' + user + '\' });\">' + ['sperren', 'entsperren'][d] + '</span>]';
				}

			for(user in v['devices'])
				for(device_id in v['devices'][user])
					{
					var d = v['devices'][user][device_id];

					document.getElementById('BLOCK_' + user + '_' + device_id).innerHTML = '[<span class="span_link" onclick="handle_link({ cmd : \'BlockStateConfirm\', long_id : \'6\', item_id : \'' + ['1', '0'][d] + '\', user : \'' + user + '\', device_id : \'' + device_id + '\' });\">' + ['sperren', 'entsperren'][d] + '</span>]';
					}

			for(user in v['folders'])
				for(device_id in v['folders'][user])
					for(collection_id in v['folders'][user][device_id])
						{
						var d = v['folders'][user][device_id][collection_id];

						document.getElementById('BLOCK_' + user + '_' + device_id + '_' + collection_id).innerHTML = '[<span class="span_link" onclick="handle_link({ cmd : \'BlockStateConfirm\', long_id : \'5\', item_id : \'' + ['1', '0'][d] + '\', user : \'' + user + '\', device_id : \'' + device_id + '\', collection_id : \'' + collection_id + '\' });\">' + ['sperren', 'entsperren'][d] + '</span>]';
						}
			}
		else if(collection_id != '')
			document.getElementById('BLOCK_' + user + '_' + device_id + '_' + collection_id).innerHTML = '[<span class="span_link" onclick="handle_link({ cmd : \'BlockStateConfirm\', long_id : \'5\' item_id : \'' + ['1', '0'][d] + '\', user : \'' + user + '\', device_id : \'' + device_id + '\', collection_id : \'' + collection_id + '\' });\">' + ['sperren', 'entsperren'][d] + '</span>]';
		else if(device_id != '')
			document.getElementById('BLOCK_' + user + '_' + device_id).innerHTML = '[<span class="span_link" onclick="handle_link({ cmd : \'BlockStateConfirm\', long_id : \'6\', item_id : \'' + ['1', '0'][d] + '\', user : \'' + user + '\', device_id : \'' + device_id + '\' });\">' + ['sperren', 'entsperren'][d] + '</span>]';
		else
			document.getElementById('BLOCK_' + user).innerHTML = '[<span class="span_link" onclick="handle_link({ cmd : \'BlockStateConfirm\', long_id : \'7\', item_id : \'' + [1, 0][d] + '\', user : \'' + user + '\' });\">' + ['sperren', 'entsperren'][d] + '</span>]';
		}

	ajax({ type : "GET", url : "index.php?Cmd=BlockState" + (user ? "&User=" + user : "") + (collection_id ? "&CollectionId=" + collection_id : "") + (device_id ? "&DeviceId=" + device_id : "") + "&ItemId=" + state, success : onload});
	}

