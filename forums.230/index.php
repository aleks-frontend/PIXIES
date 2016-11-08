<?php
error_reporting(7);

$templatesused='forumhome_birthdaybit,error_nopermission,forumhome_pmloggedin,forumhome_welcometext,forumhome_logoutcode,forumhome_newposts,forumhome_todayposts,forumhome_logincode,forumhome_loggedinuser,forumhome_loggedinusers,forumhome_lastpostby,forumhome_moderator,forumhome_forumbit_level1_nopost,forumhome_forumbit_level1_post,forumhome_forumbit_level2_nopost,forumhome_forumbit_level2_post,forumhome,forumhome_unregmessage';
// ##################################### chatbox par p0s3id0n
$templatesused .= ',chatbox,chatbox_bit,chatbox_bit_me,chatbox_bit_vide,chatbox_chat,chatbox_fermee,chatbox_frame,chatbox_popup_smilbox,chatbox_popup_smilboxbits,chatbox_saisie,chatbox_vbcodes';
require("./admin/chatbox_config.php");
// ##################################### chatbox par p0s3id0n
$loadbirthdays=1;
$loadmaxusers=1;

require('./global.php');
require('./displaychatusers.php');

$permissions=getpermissions();
if (!$permissions['canview']) {
	show_nopermission();
}


if ($bbuserinfo[userid]!=0) {
  $avatarurl=getavatarurl($bbuserinfo[userid]);
  if ($avatarurl=='') {
    $avatarurl='images/avatars/noavatar.gif';
  }
  $avatarimage='<a href="member.php?s='.$session[sessionhash].'&action=editavatar"><img src="'.$avatarurl.'" border="0">';
} else {
  $avatarimage='<a href="register.php?s='.$session[sessionhash].'&action=signup"><img src="images/avatars/guestavatar.gif" border="0"></a>';
}


//check usergroup of user to see if they can use PMs
//$permissions=getpermissions($forumid);
if ($enablepms==1 and $permissions['canusepm'] and $bbuserinfo['receivepm']) {
  $ignoreusers="";
  if (trim($bbuserinfo['ignorelist'])!="") {
    $ignoreusers='AND fromuserid<>'.implode(' AND fromuserid<>',explode(' ', trim($bbuserinfo['ignorelist'])));
  }

  $allpm=$DB_site->query_first("SELECT COUNT(*) AS messages FROM privatemessage WHERE userid=$bbuserinfo[userid] $ignoreusers");
  $newpm=$DB_site->query_first("SELECT COUNT(*) AS messages FROM privatemessage WHERE userid=$bbuserinfo[userid] AND dateline>$bbuserinfo[lastvisit] AND folderid=0 $ignoreusers");
  $unreadpm=$DB_site->query_first("SELECT COUNT(*) AS messages FROM privatemessage WHERE userid=$bbuserinfo[userid] AND messageread=0 AND folderid=0 $ignoreusers");

  if ($newpm['messages']==0) {
    $lightbulb='off';
  } else {
    $lightbulb='on';
  }
  eval("\$pminfo = \"".gettemplate('forumhome_pmloggedin')."\";");

} else {
  $pminfo='';
}

$numbersmembers=$DB_site->query_first('SELECT COUNT(*) AS users,MAX(userid) AS max FROM user');
$numbermembers=number_format($numbersmembers['users']);

// get total posts
$countposts=$DB_site->query_first('SELECT COUNT(*) AS posts FROM post');
$totalposts=number_format($countposts['posts']);

$countthreads=$DB_site->query_first('SELECT COUNT(*) AS threads FROM thread');
$totalthreads=number_format($countthreads['threads']);

// get newest member
$getnewestusers=$DB_site->query_first("SELECT userid,username FROM user WHERE userid=$numbersmembers[max]");
$newusername=$getnewestusers['username'];
$newuserid=$getnewestusers['userid'];

// if user is know, then welcome

 $getnewthread=$DB_site->query_first("SELECT COUNT(*) AS threads FROM thread WHERE lastpost > '$bbuserinfo[lastvisit]'");
 $getnewpost=$DB_site->query_first("SELECT count(*) AS posts FROM post WHERE dateline > '$bbuserinfo[lastvisit]'");

if ($bbuserinfo['userid']!=0) {
  $username=$bbuserinfo['username'];
  eval("\$welcometext = \"".gettemplate('forumhome_welcometext')."\";");
  eval("\$logincode = \"".gettemplate('forumhome_logoutcode')."\";");
  eval("\$newposts = \"".gettemplate('forumhome_newposts')."\";");

} else {
  $welcometext = "";
  eval("\$newposts = \"".gettemplate('forumhome_todayposts')."\";");
  eval("\$logincode = \"".gettemplate('forumhome_logincode')."\";");
}

$birthdaybits="";
if ($showbirthdays) {

  $birthdays = gettemplate('birthdays',0,0);
  $btoday = explode('|||',$birthdays);
  $today = vbdate("Y-m-d",time());
  if (($today != $btoday[0] and $today != $btoday[1]) or empty($birthdays))  { // Need to update!
    if (empty($birthdays)) {
		$DB_site->query("INSERT INTO template (templateid, templatesetid, title, template) VALUES (NULL, '-2', 'birthdays', '')");
	}
    getbirthdays();
    $birthdays = $DB_site->query_first("SELECT template FROM template WHERE title='birthdays' and templatesetid = -2");
    $birthdays = $birthdays[template];
    $btoday = explode('|||',$birthdays);
  }

  if ($today == $btoday[0]) {
    $birthdays = $btoday[2];
  } elseif ($today == $btoday[1]) {
    $birthdays = $btoday[3];
  }

  if ($birthdays) {
    eval("\$birthdaybits = \"".gettemplate("forumhome_birthdaybit")."\";");
  }
}

//Forum info
$forums=$DB_site->query('SELECT * FROM forum WHERE displayorder<>0 AND active=1 ORDER BY parentid,displayorder');
while ($forum=$DB_site->fetch_array($forums)) {
    $iforumcache["$forum[parentid]"]["$forum[displayorder]"]["$forum[forumid]"] = $forum;
}
$DB_site->free_result($forums);
unset($forum);

//Forum perms
$forumperms=$DB_site->query("SELECT forumid,canview,canpostnew FROM forumpermission WHERE usergroupid='$bbuserinfo[usergroupid]'");
while ($forumperm=$DB_site->fetch_array($forumperms)) {
  $ipermcache["$forumperm[forumid]"] = $forumperm;
}
$DB_site->free_result($forumperms);
unset($forumperm);

$accesscache = array();
if ($bbuserinfo['userid']!=0 AND $enableaccess) {
  //Access table perms
  $accessperms=$DB_site->query("SELECT forumid,accessmask FROM access WHERE userid='$bbuserinfo[userid]'");
  while ($accessperm=$DB_site->fetch_array($accessperms)) {
    $accesscache["$accessperm[forumid]"] = $accessperm;
  }
  $DB_site->free_result($accessperms);
  unset($accessperm);

  // usergroup defaults
  $usergroupdef['canview'] = $permissions['canview'];
  $usergroupdef['canpostnew'] = $permissions['canpostnew'];

  // array for accessmask=0
  $noperms['canview'] = 0;
  $noperms['canpostnew'] = 0;
}

$imodcache = array();
$mod = array();
$forummoderators=$DB_site->query('SELECT user.userid,user.username,moderator.forumid
                                  FROM moderator
                                  LEFT JOIN user
                                    ON (moderator.userid=user.userid)
                                  ORDER BY user.username');
while ($moderator=$DB_site->fetch_array($forummoderators)) {
  $imodcache["$moderator[forumid]"][] = $moderator;
  $mod["$moderator[userid]"] = 1;
}
$DB_site->free_result($forummoderators);
unset($moderator);

$activeusers = "";
$loggedinusers = "";
if ($displayloggedin) {
  $datecut=time()-$cookietimeout;

  $loggedins=$DB_site->query_first("SELECT COUNT(*) AS sessions FROM session WHERE userid=0 AND lastactivity>$datecut");
  $numberguest=$loggedins['sessions'];

  $numbervisible=0;
  $numberregistered=0;

  $loggedins=$DB_site->query("SELECT DISTINCT session.userid,username,invisible,usergroupid
                              FROM session
                              LEFT JOIN user ON (user.userid=session.userid)
                              WHERE session.userid>0 AND session.lastactivity>$datecut
                              ORDER BY invisible ASC, username ASC");
  if ($loggedin=$DB_site->fetch_array($loggedins)) {
    $numberregistered++;
    if ($loggedin['invisible']==0 or $bbuserinfo['usergroupid']==6) {
      $numbervisible++;
      $userid = $loggedin['userid'];
      if ($loggedin['invisible'] == 1) { // Invisible User but show to Admin
        $invisibleuser = '*';
      } else {
        $invisibleuser = '';
      }
      if ($loggedin['usergroupid'] == 6 and $highlightadmin) {
      	$username = "<b><i>$loggedin[username]</i></b>";
      } else if (($mod["$userid"] or $loggedin['usergroupid'] == 5) and $highlightadmin) {
      	$username = "<b>$loggedin[username]</b>";
      } else {
				$username = $loggedin['username'];
			}
      eval("\$activeusers = \"".gettemplate('forumhome_loggedinuser')."\";");
    }

    while ($loggedin=$DB_site->fetch_array($loggedins)) {
      $numberregistered++;
      $invisibleuser = '';
      if ($loggedin['invisible']==1 and $bbuserinfo['usergroupid']!=6) {
        continue;
      }
      $numbervisible++;
      $userid=$loggedin['userid'];
      if ($loggedin['invisible'] == 1) { // Invisible User but show to Admin
        $invisibleuser = '*';
      }
      if ($loggedin['usergroupid'] == 6 and $highlightadmin) {
	    $username = "<b><i>$loggedin[username]</i></b>";
			} else if (($mod["$userid"] or $loggedin['usergroupid'] == 5) and $highlightadmin) {
				$username = "<b>$loggedin[username]</b>";
			} else {
				$username = $loggedin['username'];
			}
      eval("\$activeusers .= \", ".gettemplate('forumhome_loggedinuser')."\";");
    }
  }
  $DB_site->free_result($loggedins);

  $totalonline=$numberregistered+$numberguest;
  $numberinvisible=$numberregistered-$numbervisible;

  $maxusers=explode(" ", gettemplate('maxloggedin',0,0));
  if ((int)$maxusers[0] <= $totalonline) {
    $time = time();
    $maxloggedin = "$totalonline " . $time;
    $DB_site->query("UPDATE template SET template='$maxloggedin' WHERE title='maxloggedin'");
    $maxusers[0] = $totalonline;
    $maxusers[1] = $time;
  }
  $recordusers = $maxusers[0];
  $recorddate = vbdate($dateformat,$maxusers[1]);
  $recordtime = vbdate($timeformat,$maxusers[1]);
  eval("\$loggedinusers = \"".gettemplate('forumhome_loggedinusers')."\";");
}

// Start makeforumbit
function makeforumbit($forumid,$depth=1,$permissions='') {
  global $DB_site,$bbuserinfo,$iforumcache,$ipermcache,$imodcache,$session,$accesscache,$usergroupdef,$noperms;
  global $showlocks,$hideprivateforums,$showforumdescription,$forumhomedepth,$dateformat,$timeformat,$enableaccess;

  if ( !isset($iforumcache["$forumid"]) ) {
    return;
  }

  $forumbits = '';

  while ( list($key1,$val1)=each($iforumcache["$forumid"]) ) {
    while ( list($key2,$forum)=each($val1) ) {

      // Permissions
      if ( $enableaccess and is_array($accesscache["$forum[forumid]"]) ) {
        if ($accesscache["$forum[forumid]"]['accessmask']==1) {
          $forumperms = $usergroupdef;
        } else {
          $forumperms = $noperms;
        }
      } else if ( is_array($ipermcache["$forum[forumid]"]) ) {
        $forumperms = $ipermcache["$forum[forumid]"];
      } else {
        $forumperms = $permissions;
      }

      if (!$hideprivateforums) {
        $forumperms['canview']=1;
      }

      if (!$forumperms['canview']) {
        continue;
      } else {
        $forumshown=1;

        // do light bulb
        if ($bbuserinfo['lastvisitdate']=='Never') {
          $forum['onoff']='on';
        } else {
					if (($fview = get_bbarraycookie('forumview', $forum['forumid'])) > $bbuserinfo['lastvisit']) {
						$userlastvisit=$fview;
					} else {
						$userlastvisit=$bbuserinfo['lastvisit'];
					}
          if ($userlastvisit<$forum['lastpost']) {
            $forum['onoff']='on';
          } else {
            $forum['onoff']='off';
          }
        }

        if ((!$forumperms['canpostnew'] and $showlocks) or $forum['allowposting']==0) {
          $forum['onoff'].='lock';
        }

        // prepare template vars
        if (!$showforumdescription) {
          $forum['description']='';
        }

        // dates
        if ($forum['lastpost']>0) {
          $forum['lastpostdate']=vbdate($dateformat,$forum['lastpost']);
          $forum['lastposttime']=vbdate($timeformat,$forum['lastpost']);
          eval("\$forum['lastpostinfo'] = \"".gettemplate('forumhome_lastpostby')."\";");
        } else {
          $forum['lastpostinfo']='Never';
        }

        $listexploded=explode(",", $forum['parentlist']);
        while ( list($mkey1,$mval1)=each($listexploded) ) {
          if ( !isset($imodcache["$mval1"]) ) {
            continue;
          }
          reset($imodcache["$mval1"]);
          while ( list($mkey2,$moderator)=each($imodcache["$mval1"]) ) {
            if ( !isset($forum['moderators']) ) {
              eval("\$forum['moderators'] = \"".gettemplate('forumhome_moderator')."\";");
            } else {
              eval("\$forum['moderators'] .= \", ".gettemplate('forumhome_moderator')."\";");
            }
          }
        }

        if ( !isset($forum['moderators']) ) {
          $forum['moderators'] = '&nbsp;';
        }

        if ($forum['cancontainthreads']==1) {
          $tempext = '_post';
        } else {
          $tempext = '_nopost';
        }

        eval("\$forumbits .= \"".gettemplate("forumhome_forumbit_level$depth$tempext")."\";");

        if ($depth<$forumhomedepth) {
          $forumbits.=makeforumbit($forum['forumid'],$depth+1,$forumperms);
        }
      } // END if can view
    } // END while ( list($key2,$forum)=each($val1) ) {
  } // END while ( list($key1,$val1)=each($iforumcache["$forumid"]) ) {

  unset($iforumcache["$forumid"]);
  return $forumbits;
}

if (!isset($forumid) or $forumid==0 or $forumid=='') {
  $forumid=-1;
} else {
  // need to get permissions for this specific forum
  $permissions=getpermissions(intval($forumid));
}
$forumbits=makeforumbit(intval($forumid), 1, $permissions);

$unregwelcomemessage='';
if ($bbuserinfo['userid']==0) {
  eval("\$unregwelcomemessage = \"".gettemplate('forumhome_unregmessage')."\";");
}

// ##################################### chatbox par p0s3id0n
if($chatbox == "open"){
	$request_chat = $DB_site->query("UPDATE user SET chatbox = 1 WHERE username = '$bbuserinfo[username]'");
	$bbuserinfo['chatbox'] = 1;
}elseif($chatbox == "close"){
	$request_chat = $DB_site->query("UPDATE user SET chatbox=0 WHERE username='".$bbuserinfo['username']."'");
	$bbuserinfo['chatbox'] = 0;
}

if($bbuserinfo['chatbox'] == 1){
	eval("\$chatbox = \"".gettemplate('chatbox')."\";");
}else{
	$chat_result = $DB_site->query("SELECT original,remplace FROM chatbox_swears");
	while($chat_row = mysql_fetch_assoc($chat_result)){
		$swears[$chat_row["original"]] = $chat_row["remplace"];
	}
	$chat_result = $DB_site->query("SELECT * FROM chatbox ORDER BY id DESC LIMIT 1");
	if(!mysql_num_rows($chat_result)){
		eval("\$chatbit = \"".gettemplate('chatbox_bit_vide')."\";");
		eval("\$chatbox = \"".gettemplate('chatbox_fermee')."\";");
	}else{
		$chat_row = mysql_fetch_assoc($chat_result);
		$name = $chat_row["name"];
		$comment = $chat_row["comment"];
		$date = $chat_row["date"];
		$date = date("Y") . "-" . substr($date, 4, 2) . "-" . substr($date, 1, 2) . " " . substr($date, 7, 2) . ":" . substr($date, 10, 2);
		$date = date("[d/m|H:i]", strtotime($date) + ($bbuserinfo['timezoneoffset'] * 3600));
		$comment = parseurl($comment);
		$comment = ereg_replace("sessionhash=[a-z0-9]{32}", "", $comment);
		$comment = ereg_replace("s=[a-z0-9]{32}", "", $comment);
		$comment = bbcodeparse($comment);
		$comment = str_replace("<br />"," ",$comment);
		$comment = str_replace("<br>"," ",$comment);
		$comment = swear($comment);
		if(substr($comment,0,3) == "/me"){
			$comment = stripslashes(substr($comment,3));
			eval("\$chatbit = \"".gettemplate('chatbox_bit_me')."\";");
		}else{
			$colorirc = $un_color_chat;
			$comment = stripslashes($comment);
			eval("\$chatbit = \"".gettemplate('chatbox_bit')."\";");
		}
		eval("\$chatbox = \"".gettemplate('chatbox_fermee')."\";");
	}
}

// ###################################################
// #               fonction swear                    #
// ###################################################
function swear($comment){
	global $DB_site,$swears;
	if($swears){
		while(list($orig,$replace) = each($swears)){
			$comment = str_replace($orig,$replace,$comment);
		}
		reset($swears);
	}
	return $comment;
}
// ##################################### chatbox par p0s3id0n
eval("dooutput(\"".gettemplate('forumhome')."\");");

?>