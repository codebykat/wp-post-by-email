<?php

/* Wrapper for Horde IMAP Client */

require_once('Horde/Exception.php');
require_once('Horde/Exception-Wrapped.php');

require_once('Horde/Mail-Rfc822.php');
require_once('Horde/Mail-Rfc822-Object.php');
require_once('Horde/Mail-Rfc822-Address.php');
require_once('Horde/Mail-Rfc822-List.php');

require_once('Horde/Support-CaseInsensitiveArray.php');
require_once('Horde/Support-Randomid.php');

require_once('Horde/Stream-Filter-Eol.php');

require_once('Horde/Mime.php');
require_once('Horde/Mime-Headers.php');
require_once('Horde/Mime-Part.php');

require_once('Horde/Horde-Util.php');

require_once('Horde/Stub.php');

require_once('Horde/String.php');

require_once('Horde-Translation-Wrapper.php');

require_once('Horde/Client.php');

require_once('Horde/Client/Base.php');
require_once('Horde/Client/Base/Mailbox.php');
require_once('Horde/Client/Base/Connection.php');

require_once('Horde/Client/Data/Fetch.php');
require_once('Horde/Client/Data/Fetch/Pop3.php');

require_once('Horde/Client/Data/Format.php');
require_once('Horde/Client/Data/Format/Atom.php');
require_once('Horde/Client/Data/Format/List.php');

require_once('Horde/Client/Fetch/Query.php');
require_once('Horde/Client/Fetch/Results.php');

require_once('Horde/Client/Ids.php');
require_once('Horde/Client/Ids/Map.php');
require_once('Horde/Client/Ids/Pop3.php');

require_once('Horde/Client/Search/Query.php');

require_once('Horde/Client/Socket/Pop3.php');
require_once('Horde/Client/Socket/Connection.php');
require_once('Horde/Client/Socket/Connection/Pop3.php');

require_once('Horde/Client/Mailbox.php');

require_once('Horde/Client/Exception.php');

?>