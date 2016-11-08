<?
if ($showforumusers) {
$datecut = $ourtimenow - $cookietimeout;
$chatters = '';
$comma = '';
$forumusers = $DB_site->query("SELECT username, invisible, userid
FROM user
WHERE inchat = 1 AND
lastchatactivity > $datecut");
while ($forumuser = $DB_site->fetch_array($forumusers)) {
if (!$forumuser['invisible'] or $bbuserinfo['usergroupid'] == 6) {
$userid = $forumuser['userid'];
$username = $forumuser['username'];
if ($forumuser['invisible'] == 1) { // Invisible User but show to Admin
$invisibleuser = '*';
} else {
$invisibleuser = '';
}
eval("\$chatters .= \"".$comma.gettemplate('forumdisplay_loggedinuser')."\";");
$comma = ', ';
}
}

}
// get total chatters 
$datecut=time()-$cookietimeout; 
$chatnum = mysql_num_rows(mysql_query("select * from user WHERE inchat=1 AND lastchatactivity>$datecut"));

?>


