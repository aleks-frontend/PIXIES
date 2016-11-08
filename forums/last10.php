<?

//...........Last X Posts v1.0.2...........\\
//......by Kevin (kevin@tubescan.com)......\\

// For vBulletin version 2 (betas 3, 4, 5, RC1, RC2)
// (c) 2001 Jelsoft Enterprises, Ltd.

// vBulletin.com thread: http://www.vbulletin.com/forum/showthread.php?threadid=12324

// let's get connected
require("last10config.php");
require("$path/config.php");
$db=mysql_connect($servername,$dbusername,$dbpassword) or die("Can't open connection to MySQL");
mysql_select_db($dbname) or die("Can't select database");

$hfs = $fs+2;
$fs .= "pt";
$hfs .= "pt";
if ($tw == "") {
	$twt = "";
} else {
	$twt = "width=\"$tw\"";
}
if ($cs == "") {
	$cs = 0;
}
// start up our table, decide whether to show
echo("<table border=0 cellpadding=2 cellspacing=$cs $twt><tr bgcolor=\"$hc\">\n");
if ($showicon == "1") {
	echo("<td>&nbsp;</td>");
}
echo("<td style=\"font-family:$f; font-size:$hfs; color:$tc;\"><b><nobr><a href=/forums><font color=black><b>Latest Forum Threads:</font></b></a></nobr></b></td>\n");
// the last poster column,
if ($lastposter == "1") {
	echo("<td style=\"font-family:$f; font-size:$hfs; color:$tc;\" align=\"center\"><b><nobr>Last Poster</nobr></b></td>\n");
}
// the last post date & time column,
if ($lastpostdate == "1") {
	echo("<td style=\"font-family:$f; font-size:$hfs; color:$tc;\" align=\"center\"><b><nobr>Last Post</nobr></b></td>\n");
}
// the views column,
if ($views == "1") {
	echo("<td style=\"font-family:$f; font-size:$hfs; color:$tc;\" align=\"center\"><b>Views</b></td>\n");
}
// and/or the replies column
if ($replies == "1") {
	echo("<td style=\"font-family:$f; font-size:$hfs; color:$tc;\" align=\"center\"><b>Replies</b></td>\n");
}
echo("</tr>\n");

// the base WHERE statement
$wheresql = "WHERE thread.lastposter=user.username AND thread.open!='10'";

// we can't have both the last 24 hours *and* the last 7 days, so error out if needed
if ($last24 == "1" && $last7 == "1") {
	print("Error: \$last24 and \$last7 are both set to 1. Please change one of them to 0.");
	exit;
}
// otherwise we're gonna find out which one it is
// last 24
if ($last24 == "1") {
	$time = time()-86400;
	$wheresql .= " AND thread.lastpost>'$time'";
}
// last 7
if ($last7 == "1") {
	$time = time()-604800;
	$wheresql .= " AND thread.lastpost>'$time'";
}
// are we trying to exclude *and* include forums? if so, error out
if ($excludeforums != "" && $includeforums != "") {
	print("Error: \$includeforums and \$excludeforums are both set with numbers. Please remove the numbers from one of these two to proceed.");
	exit;
}
// otherwise figure out which one we're using
// include forums
if ($includeforums == "" or $includeforums <= "0") {
	$quarter = "no";
} else {
	$incfid = explode(",",$includeforums); $i = 0; $a = count($incfid);
	if ($a > 1) {
		$wheresql .= " AND (forumid='$incfid[0]'";
		++$i;
		while ($i < $a) {
			$wheresql .= " OR forumid='$incfid[$i]'"; ++$i;
		}
		$wheresql .= ")";
	} else {
		$wheresql .= " AND forumid='$incfid[$i]'";
	}
}
// or exclude forums
if ($excludeforums == "" or $excludeforums <= "0") {
	$quarter = "no";
} else {
	$excfid = explode(",",$excludeforums); $i = 0; $a = count($excfid);
	while ($i < $a) {
		$wheresql .= " AND forumid!='$excfid[$i]'";	++$i;
	}
}
if ($showforumtitle == "1") {
	$ftitle = ",forum";
	$fsel = ",forum.title AS ftitle";
	$wheresql .= " AND thread.forumid=forum.forumid";
}
// ooh a query!
$query = "SELECT thread.lastpost,thread.title,thread.lastposter,thread.replycount,thread.views,user.userid,thread.threadid,thread.forumid$fsel,thread.iconid FROM thread,user$ftitle $wheresql ORDER BY thread.$ob $obdir LIMIT $maxthreads";
// let's get the info
$tr = mysql_query($query) or die("Can't select info");
$dtf = mysql_query("SELECT value FROM setting WHERE varname='dateformat' OR varname='timeformat' OR varname='timeoffset' ORDER BY varname");
$df = mysql_result($dtf,0,0);
$tf = mysql_result($dtf,1,0);
$tof = mysql_result($dtf,2,0);
if ($showdate == "1") {
	$fdt = "$df $tf";
} else {
	$fdt = "$tf";
}
$cols = 1;
// let's display the info
while ($threads = mysql_fetch_array($tr)) {	
	// are we going to show the message too?
	if ($showmessages == "1") {
		$query0 = "SELECT pagetext,postid,dateline,iconid FROM post WHERE threadid='$threads[threadid]' ORDER BY dateline DESC LIMIT 1";
		$lastpost = mysql_query($query0);
		while ($lastpost1 = mysql_fetch_array($lastpost)) {
			$lastpostshort = $lastpost1[pagetext];
			$postii = $lastpost1[iconid];
		}
		if (strlen($lastpostshort) > $lplen) {
			$lastpostshort = substr($lastpostshort,0,$lplen);
			$lastpostshort .= "...";
		}
		$smilies = mysql_query("SELECT smilietext,smiliepath FROM smilie");
		while ($smiles = mysql_fetch_array($smilies)) {
			$lastpostshort = str_replace($smiles[smilietext],"<img src=\"".$url."/".$smiles[smiliepath]."\" border=0>",$lastpostshort);
		}
		if ($nb == "1") {
			$lastpostshort = nl2br($lastpostshort);
		}
		$lastpostshort = str_replace("[i]","<i>",$lastpostshort);
		$lastpostshort = str_replace("[/i]","</i>",$lastpostshort);
		$lastpostshort = str_replace("[u]","<u>",$lastpostshort);
		$lastpostshort = str_replace("[/u]","</u>",$lastpostshort);
		$lastpostshort = str_replace("[b]","<b>",$lastpostshort);
		$lastpostshort = str_replace("[/b]","</b>",$lastpostshort);
		$lastpostshort = str_replace("[quote]","<br>quote:<br><hr> ",$lastpostshort);
		$lastpostshort = str_replace("[/quote]"," <hr><br>\n",$lastpostshort);
		$lastpostshort = str_replace("[I]","<i>",$lastpostshort);
		$lastpostshort = str_replace("[/I]","</i>",$lastpostshort);
		$lastpostshort = str_replace("[U]","<u>",$lastpostshort);
		$lastpostshort = str_replace("[/U]","</u>",$lastpostshort);
		$lastpostshort = str_replace("[B]","<b>",$lastpostshort);
		$lastpostshort = str_replace("[/B]","</b>",$lastpostshort);
		$lastpostshort = str_replace("[QUOTE]","<br>quote:<br><hr> ",$lastpostshort);
		$lastpostshort = str_replace("[/QUOTE]"," <hr><br>\n",$lastpostshort);
		$lastpostshort = str_replace("[CODE]","<br>code:<br><hr> ",$lastpostshort);
		$lastpostshort = str_replace("[/CODE]"," <hr><br>\n",$lastpostshort);
		$lastpostshort = str_replace("[code]","<br>code:<br><hr> ",$lastpostshort);
		$lastpostshort = str_replace("[/code]"," <hr><br>\n",$lastpostshort);
		$lastpostshort = str_replace("[img]","",$lastpostshort);
		$lastpostshort = str_replace("[/img]","",$lastpostshort);
		$lastpostshort = str_replace("[IMG]","",$lastpostshort);
		$lastpostshort = str_replace("[/IMG]","",$lastpostshort);
		$lastpostshort = str_replace("[url]","",$lastpostshort);
		$lastpostshort = str_replace("[/url]","",$lastpostshort);
		$lastpostshort = str_replace("[URL]","",$lastpostshort);
		$lastpostshort = str_replace("[/URL]","",$lastpostshort);
	}
	// thanks to kier for this idea to do the alternating row colors
	if (($counter++ % 2) != 0) {
		$bc=$bc1;
	} else {
		$bc=$bc2;
	}
	// if the title is more than $len characters, we need to cut it off and add ... to the end
	if (strlen($threads[title]) > $len) { 
		$title = substr($threads[title],0,$len);
		$title .= "...";
	} else { 
		$title = $threads[title];
	}
	// convert the date to a format readable by non-unix geeks :)
	$fd = date($fdt,$threads[lastpost]);
	// display everything in a nice table. in the future we're gonna try to do this so others can format the data, but this is sufficient for now
	echo("<tr>");
	if ($showicon == "1") {
		echo("<td bgcolor=\"$bc\">");
		if ($postii != "0" && $postii != "") {
			echo("<img src=\"$urlimg/icons/icon$postii.gif\" border=\"0\">");
		}
		if (($postii == "0" || $postii == "") && $threads[iconid] != "0" && $threads[iconid] != "") {
			echo("<img src=\"$urlimg/icons/icon$threads[iconid].gif\" border=\"0\">");
		}
		if (($postii == "0" || $postii == "") && ($threads[iconid] == "0" || $threads[iconid] == "")) {
			echo("&nbsp;");
		}
		echo("</td>");
		++$cols;
	}
	echo("<td bgcolor=\"$bc\" style=\"font-family:$f; font-size:$fs; color:$tc;\"><nobr>");
	if ($showforumtitle == "1") {
		echo("<a class=small href=\"$url/forumdisplay.php?forumid=$threads[forumid]\">$threads[ftitle]</a>: ");
	}
	echo("<a class=small href=\"$url/showthread.php?threadid=$threads[threadid]&goto=newpost\" title=\"$threads[title]\">$title</a></nobr></td>\n");
	// last poster column?
	if ($lastposter == "1") {
		echo("<td bgcolor=\"$bc\" style=\"font-family:$f; font-size:$fs; color:$tc;\" align=\"center\"><a class=small href=\"$url/member.php?action=getinfo&userid=$threads[userid]\">$threads[lastposter]</a></td>\n");
		++$cols;
	}
	// the last post date & time column,
	if ($lastpostdate == "1") {
		echo("<td bgcolor=\"$bc\" style=\"font-family:$f; font-size:$fs; color:$tc;\" align=\"center\">$fd</td>\n");
		++$cols;
	}
	// views column?
	if ($views == "1") {
		echo("<td bgcolor=\"$bc\" style=\"font-family:$f; font-size:$fs; color:$tc;\" align=\"center\">$threads[views]</td>\n");
		++$cols;
	}
	// replies column?
	if ($replies == "1") {
		echo("<td bgcolor=\"$bc\" style=\"font-family:$f; font-size:$fs; color:$tc;\" align=\"center\">$threads[replycount]</td>\n");
		++$cols;
	}
	echo("</tr>");
	// are we showing the last post?
	if ($showmessages == "1") {
		echo("<tr bgcolor=\"$bc\"><td colspan=\"$cols\" style=\"font-family:$f; font-size:$fs; color:$tc;\" align=\"left\">\n");
		echo("<table border=0 cellpadding=4 cellspacing=0 width=\"100%\">\n");
		echo("<tr bgcolor=\"$bc\"><td style=\"font-family:$f; font-size:$fs; color:$tc;\" align=\"right\" valign=\"top\"><b><nobr>Last Post:</nobr></b></td>\n");
		echo("<td style=\"font-family:$f; font-size:$fs; color:$tc;\" align=\"left\" width=\"100%\">$lastpostshort</td></tr>\n");
		echo("</table></td>\n");
	}
	$fd = "";
}
// close it all up
echo("</tr></table>");
// bye!
?>