<?

// Welcome-Login Panel on a non-vB Page by Darren Lewis
// V1.30 (14th September 2002)

// This script can be seen in action at www.thebookforum.com
// Please ask in the vbulletin.org thread http://www.vbulletin.org/forum/showthread.php?s=&threadid=37134 for support.

// CONFIG OPTIONS

$showavatar = "no"; // Set to no if don't want to show user avatar.
$showpminfo = "yes"; // Set to no if don't want to show PM info to logged in user.
$showwhosonline = "yes"; // Set to no if don't want to show who's online bit.
$showusernames = "no"; // Set to no if don't want to also show member's usernames in the who's online bit.

// END CONFIG OPTIONS

error_reporting(7);

$templatesused='webwelcome,webwelcome_welcometext,webwebwelcome_logincode,webwelcome_logoutcode,webwelcome_unregmessage,webwelcome_pmloggedin';


// Added from MrLister's hack at http://www.vbulletin.org/forum/showthread.php?s=&threadid=31957
$getnewthread=$DB_site->query_first("SELECT COUNT(*) AS threads FROM thread WHERE lastpost > '$bbuserinfo[lastvisit]'");
$getnewpost=$DB_site->query_first("SELECT count(*) AS posts FROM post WHERE dateline > '$bbuserinfo[lastvisit]'");
// End of addition

if ($showavatar == "yes")
{
	// Modified version of Firefly's Avatar Hack
	// Check the paths to your vb forum directory for your server.
	if ($bbuserinfo[userid]!=0) {
	  $avatarurl=getavatarurl($bbuserinfo[userid]);
	  if ($avatarurl=='') {
	    $avatarurl='/forums/images/avatars/noavatar.gif';
	  } else {
	  $avatarurl='/forums/'.$avatarurl;
	  }
	  $avatarimage='<td align="center" width="70"><a href="/forums/member.php?s='.$session[sessionhash].'&action=editavatar"><img src="'.$avatarurl.'" border="0" alt="Click me to edit your avatar"></a></td>';
	} else {
	  $avatarimage='<td align="center" width="70"><a href="/forums/register.php?s='.$session[sessionhash].'&action=signup"><img src="/forums/images/avatars/guestavatar.gif" border="0" alt="Click me to register"></a></td>';
	}
	//End of Avatars Hack
}

if ($showpminfo == "yes")
{
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
	  if ($newpm['messages']==1) {
	    $messagetext='message';
	  } else {
	    $messagetext='messages';
	  }
	  eval("\$pminfo = \"".fetch_template('webwelcome_pminfo')."\";");

	} else {
	  $pminfo='';
	}

}

// who's online?
if ($showwhosonline == "yes")
{
	  $datecut=time()-$cookietimeout;

	  $loggedins=$DB_site->query_first("SELECT COUNT(*) AS sessions FROM session WHERE userid=0 AND lastactivity>$datecut");
	  $numberguest=$loggedins['sessions'];

	  $numbervisible=0;
	  $numberregistered=0;

	  $loggedins=$DB_site->query("SELECT DISTINCT session.userid,username,usergroupid,
                      (user.options & $_USEROPTIONS[invisible]) AS invisible
				      FROM session
				      LEFT JOIN user ON (user.userid=session.userid)
				      WHERE session.userid>0 AND session.lastactivity>$datecut
				      ORDER BY username ASC");
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
	      if ($showusernames == "yes") {

	      eval("\$activeusers = \"".fetch_template('webwelcome_whosonline_usernames')."\";");
	      }
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
	      if ($showusernames == "yes")
	      {
	      eval("\$activeusers .= \", ".fetch_template('webwelcome_whosonline_usernames')."\";");
	      }
	    }
	  }
	  $DB_site->free_result($loggedins);

	  $totalonline=$numberregistered+$numberguest;
	  $numberinvisible=$numberregistered-$numbervisible;

	  if ($numberregistered == 1) {
	  	    $membertext='member';
	  	  } else {
	  	    $membertext='members';
	  }
	  if ($numberguest == 1) {
	  	    $guesttext='guest';
	  	  } else {
	  	    $guesttext='guests';
	  }
	  eval("\$whosonline = \"".fetch_template('webwelcome_whosonline')."\";");
}

// if user is know, then welcome
if ($bbuserinfo['userid']!=0) {
  $username=$bbuserinfo['username'];
  eval("\$welcometext = \"".fetch_template('webwelcome_welcometext')."\";");
  eval("\$logincode = \"".fetch_template('webwelcome_logoutcode')."\";");
  eval("\$newposts = \"".fetch_template('webwelcome_newposts')."\";");

} else {
  $welcometext = "";
  eval("\$newposts = \"".fetch_template('webwelcome_todayposts')."\";");
  eval("\$logincode = \"".fetch_template('webwelcome_logincode')."\";");
}

if ($bbuserinfo['userid']==0) {
  eval("\$unregwelcomemessage = \"".fetch_template('webwelcome_unregmessage')."\";");
}
//print 'TEMPLATE:'.fetch_template('webwelcome');
//eval("print_output(\"".fetch_template('webwelcome')."\");");

?>
