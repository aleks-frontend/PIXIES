<?php

/*

    ====================================================
     F I N A L  F A N T A S Y  D I S C O V E R Y
                       ATTACHMENTS
    ====================================================
  http://www.ffdiscovery.com | http://forums.ffdiscovery.com
    ====================================================
            copyright 2003 rake (gheghe@rnc.ro)
    ====================================================

*/

require("./global.php");
set_time_limit(0);

//===================== config ===============
$privattach = 0; // set this to 1 if you have the private attachments hack installed. 0 if not.
$ppg = 30; // atttachments to show per page
$maxppg = 100; // maximum attachments per page
$attachviewer = 0; //set this to 1 if you've got the attachment viewer installed. 0 if not.
//============================================

$permissions=getpermissions();
if (!$permissions['canview'] OR !$permissions['cangetattachment']) {
  show_nopermission();
}

if(isset($perpage)) {
	$perpage = intval($perpage);
	if($perpage > $maxppg) {
	$perpage = $maxppg;
	}
} else {
	$perpage = $ppg;
}


$query = $DB_site->query_first("SELECT COUNT(*) FROM attachment WHERE visible=1 ".iif($privattach,'AND private=0','')." ");
if(!isset($pagenumber)) {
	$pagenumber=1;
}
$min = ($pagenumber-1)*$perpage;




$postquery = $DB_site->query("SELECT attachment.attachmentid FROM attachment");
while($pq = $DB_site->fetch_array($postquery)) {
	if($pqadd!="") {
		$pqadd .= ",$pq[0]";
	} else {
		$pqadd = $pq[0];
	}
}

if(!isset($ascdesc) OR !in_array($ascdesc, array("asc", "desc"))) {
$ascdesc = "desc";
}

if(!isset($order) OR !in_array($order, array("user.username", "dateline", "thread.title", "counter", "filename"))) {
$order = "dateline";
}

$sort = iif($ascdesc=="asc",'desc','asc');
$order_temp  = str_replace(".","",$order);
$$order_temp = "<a href='$PHP_SELF?s=$session[sessionhash]&order=$order&ascdesc=$sort'><img src='{imagesfolder}/sort$ascdesc.gif' border='0' align='absmiddle'>";

$getattach = $DB_site->query("SELECT attachment.attachmentid,attachment.userid,attachment.dateline,attachment.filename,attachment.visible ".iif($privattach,',attachment.private','').",attachment.counter,post.postid,post.threadid,thread.title,user.username FROM attachment LEFT JOIN post on attachment.attachmentid=post.attachmentid LEFT JOIN thread USING(threadid) LEFT JOIN user on attachment.userid=user.userid WHERE attachment.visible=1 AND post.attachmentid IN ($pqadd) ".iif($privattach,'AND private=0','')." ORDER BY $order $ascdesc LIMIT $min,$perpage");
while($att = $DB_site->fetch_array($getattach)) {
	$extension = substr(strrchr($att[filename],"."),1);
	$date = vbdate($dateformat,$att[dateline]);
	$link = "attachment.php?s=$session[sessionhash]&postid=$att[postid]";
	if($attachviewer==1) {
		if ($extension=="gif" or $extension=="jpg" or $extension=="jpeg" or $extension=="jpe" or $extension=="png") {
			$link.="&fullpage=1";
		}
	}
	eval("\$attachbits .= \"".gettemplate("attachmentbit")."\";");
}

$pages = getpagenav($query[0],"attach.php?s=$session[sessionhash]&perpage=$perpage");

eval("dooutput(\"".gettemplate("attachment")."\");");
?>