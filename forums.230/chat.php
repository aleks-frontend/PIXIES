<?php
require("global.php");
if( $bbuserid ) {

$DB_site->query("UPDATE user SET inchat='1' WHERE userid='$bbuserinfo[userid]'");
$DB_site->query("UPDATE user SET lastchatactivity=$ourtimenow WHERE userid='$bbuserinfo[userid]'");
} else {

} // end if
?><html>
<head>
<meta http-equiv="refresh" content="100; url=http://www.pixies-place.com/forums/chat.php">
</head>
<body bgcolor="#0F1D2D">
</body>
</html>
