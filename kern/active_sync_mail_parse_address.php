<?
function active_sync_mail_parse_address($data, $localhost = "localhost")
	{
	list($null, $name, $mailbox, $comment) = array("", "", "", "");

	if($data == "")
		{
		}
#	elseif(preg_match("/\"(.*)\" \[MOBILE: (.*)\]/", $data, $matches) == 1)	# "name" [MOBILE: number]		!!! this is a special active sync construction for sending sms !!!
#		list($null, $name, $mailbox) = $matches;
	elseif(preg_match("/\"(.*)\" <(.*)>/", $data, $matches) == 1)		# "name" <mailbox>
		list($null, $name, $mailbox) = $matches;
	elseif(preg_match("/\"(.*)\" <(.*)> \((.*)\)/", $data, $matches) == 1)	# "name" <mailbox> (comment)
		list($null, $name, $mailbox, $comment) = $matches;
	elseif(preg_match("/(.*) <(.*)>/", $data, $matches) == 1)		# name <mailbox>
		list($null, $name, $mailbox) = $matches;
	elseif(preg_match("/(.*) <(.*)> \((.*)\)/", $data, $matches) == 1)	# name <mailbox> (comment)
		list($null, $name, $mailbox, $comment) = $matches;
	elseif(preg_match("/<(.*)>/", $data, $matches) == 1)			# <mailbox>
		list($null, $mailbox) = $matches;
	elseif(preg_match("/<(.*)> \((.*)\)/", $data, $matches) == 1)		# <mailbox> (comment)
		list($null, $mailbox, $comment) = $matches;
	elseif(preg_match("/(.*)/", $data, $matches) == 1)			# mailbox
		list($null, $mailbox) = $matches;

	$retval = array($name, $mailbox);

	return($retval);
	}
?>
