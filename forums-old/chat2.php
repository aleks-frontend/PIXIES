<? require("config.php");?>
<?php
require('global.php');
?>
<HTML>
<HEAD>
<TITLE>Chat</TITLE>
</head>
<BODY bgcolor=#6384B5 leftmargin="1" marginwidth="1" topmargin="1" marginheight="1">
<script language="JavaScript" src="controls.js"></script>
<form name="jform">  
  <table width="755" cellspacing="0" cellpadding="0" bgcolor="<?echo $tbgcolor;?>" height="500" align="center" bordercolor="<?echo $bcolor;?>" border="1">
    <tr> 
      <td width="19" height="16">&nbsp;</td>
      <td colspan="2" height="16" valign="middle"> 
<? if ($showchans == "true") {
?>
<select name="channels" onChange="javascript:jpilot('join');" size="1">
<option selected>Channels</option>
<?
foreach ($channels as $chans) {
	echo "<option value=\"$chans\">$chans</option>";
}
?>
<option value="other">Other?</option>
</select>
<?
}
?>
        <select name="actions" onChange="javascript:jpilot('actions');" size="1">
          <option selected>Actions</option>
          <option value="Is innocent O:o)">Innocent</option>
          <option value="goes Awwww........">Aww</option>
          <option value="giggles loudly">Giggle</option>
          <option value="falls off chair from laughing">Fall laughing</option>
          <option value="Welcomes you to the channel">Welcome</option>
          <option value="Is going to bed,, nighty night">Going to bed</option>
          <option value="Yaaawns loudly enough to wake the entire channel">Yaaawn</option>
          <option value="Is incredible bored">Bored</option>
          <option value="other">Other?</option>
        </select>
        &nbsp;<a href="javascript:void(-1);" onClick="javascript:jpilot('away');"><img src="gfx/setaway.gif" width="70" height="20" vspace="2" hspace="1" name="away" border="0" alt="Away control" align="absmiddle"></a>&nbsp; 
        <a href="javascript:void(-1);" onClick="javascript:jpilot('whois');"><img src="gfx/whois.gif" width="70" height="20" vspace="2" hspace="1" name="whois" border="0" alt="whois selected user" align="absmiddle"></a>&nbsp; 
        <a href="javascript:void(-1);" onClick="javascript:jpilot('ping');"><img src="gfx/ping.gif" width="70" height="20" vspace="2" hspace="1" name="ping" border="0" alt="ping selected user" align="absmiddle"></a>&nbsp; 
        <a href="javascript:void(-1);" onClick="javascript:jpilot('version');"><img src="gfx/version.gif" width="70" height="20" vspace="2" hspace="1" name="version" border="0" alt="Version selected user" align="absmiddle"></a>&nbsp; 
        <a href="javascript:void(-1);" onClick="javascript:jpilot('time');"><img src="gfx/time.gif" width="70" height="20" vspace="2" hspace="1" name="time" border="0" alt="Check selected users time" align="absmiddle"></a>&nbsp; 
        <a href="javascript:void(-1);" onClick="javascript:jpilot('sclear');"><img src="gfx/clear.gif" height="20" align="absmiddle" name="clear" border="0" alt="Clear screen" vspace="0" hspace="0"></a> 
      </td>
    </tr>
    <tr> 
      <td width="19" height="277" valign="top" align="center"> <a href="javascript:document.jchat.processJInput(':)');"><img src="gfx/smile.gif" border=0 alt="happy" vspace="2" hspace="0"></a><br>
        <a href="javascript:document.jchat.processJInput(';)');"><img src="gfx/winkface.gif" border=0 width="15" height="15" alt="wink" hspace="0" vspace="2"></a><br>
        <a href="javascript:document.jchat.processJInput(':(');"><img src="gfx/frown.gif" border=0 alt="sad" vspace="2" hspace="0"></a><br>
        <a href="javascript:document.jchat.processJInput(':~(');"><img src="gfx/cry.gif" border=0 width="15" height="15" alt="cry" vspace="2" hspace="0"></a><br>
        <a href="javascript:document.jchat.processJInput(':D');"><img src="gfx/biggrin.gif" border=0 alt="big grin" vspace="2" hspace="0"></a><br>
        <a href="javascript:document.jchat.processJInput(':P');"><img src="gfx/tongue1.gif" border=0 alt="tongue" width="15" height="15" vspace="2" hspace="0"></a><br>
        <a href="javascript:document.jchat.processJInput('>:)');"><img src="gfx/evil.gif" border=0 alt="evil" width="15" height="15" vspace="2" hspace="0"></a><br>
        <a href="javascript:document.jchat.processJInput('§:o)');"><img src="gfx/clown.gif" border=0 width="15" height="15" alt="clown" vspace="2" hspace="0"></a><br>
      </td>
      <td width="604" valign="top" height="277"> <applet name="jchat" archive="jirc_nss.zip"  code=Chat.class width=600 height=350 vspace="1" hspace="1" >
          <param name="CABBASE" value="jirc_mss.cab">
<param name="ServerName1" value="<?echo $server;?>">
          <param name="Channel1" value="#<?echo $channel;?>">
          <param name="NickName" value="<?echo $nickname;?>">
	  <param name="DirectStart" value="true">
<? include "appletparm.txt";?> 
          <? if ($nickpass) { ?>
          <param name="InitCommands" value="/msg nickserv identify <? echo $nickpass;?>">
          <? }?>
        </applet></td>
      <td valign="top" align="center" width="130" height="277"> 
	  <? if ($opcontrol == 'show') { ?>
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr> 
            <td align="center" height="93"> 
              <table width="121" border="1" cellspacing="0" cellpadding="0" bordercolor="<?echo $bcolor;?>">
                <tr> 
                  <td width="119"><u><i>Op Control</i></u></td>
                </tr>
                <tr> 
                  <td valign="middle" align="center" width="119"><b><u><a href="javascript:void(-1);" onClick="javascript:jpilot('opmode','+o');"><img src="gfx/op.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="op" alt="giver selected user +o (@)"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('opmode','-o');"><img src="gfx/deop.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="deop" alt="take ops away from selected user"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('opmode','+v');"><img src="gfx/voice.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="voice" alt="give selected user +v (+)"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('opmode','-v');"><img src="gfx/devoice.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="devoice" alt="take voice away from selected user"></a><br>
                    <a href="javascript:void(-1);" onClick="javascript:jpilot('kick','fast');"><img src="gfx/kick.gif" width="39" height="20" vspace="0" hspace="2" border="0" name="kick" alt="kick selected user"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('kick','msg');"><img src="gfx/reason.gif" width="70" height="20" vspace="0" hspace="2" name="reason" border="0" alt="kick selected user with a reason"></a><br>
                    <a href="javascript:void(-1);" onClick="javascript:jpilot('opmode','+b');"><img src="gfx/ban.gif" width="39" height="20" vspace="0" hspace="2" border="0" name="ban" alt="ban selected user"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('kickban');"><img src="gfx/kickban.gif" width="70" height="20" vspace="0" hspace="2" name="kickban" border="0" alt="kick and ban selected user from channel"></a></u></b></td>
                </tr>
              </table>
            </td>
          </tr>
          <tr> 
            <td align="center" height="127" valign="top"> 
              <table width="121" border="1" cellspacing="0" cellpadding="0" bordercolor="<?echo $bcolor;?>">
                <tr> 
                  <td width="116"><i>Channel modes</i><b><i></i></b></td>
                </tr>
                <tr> 
                  <td valign="middle" align="center" width="116"><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','+t');"><img src="gfx/opstopicon.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="opstopic" alt="Only ops set topic"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','-t');"><img src="gfx/opstopicoff.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="opstopic" alt="Only ops set topic"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','+n');"><img src="gfx/noexternalon.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="noexternal" alt="No external messages"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','-n');"><img src="gfx/noexternaloff.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="noexternal" alt="No external messages"></a> 
                    <br>
                    <a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','+i');"><img src="gfx/inviteon.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="inviteonly" alt="Invite only"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','-i');"><img src="gfx/inviteoff.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="inviteonly" alt="Invite only"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','+m');"><img src="gfx/moderateon.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="moderated" alt="moderated"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','-m');"><img src="gfx/moderateoff.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="moderated" alt="moderated"></a> 
                    <br>
                    <a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','+p');"><img src="gfx/privateon.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="private" alt="Private"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','-p');"><img src="gfx/privateoff.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="private" alt="Private"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','+s');"><img src="gfx/secreton.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="secret" alt="Secret"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('cmode','-s');"><img src="gfx/secretoff.gif" width="20" height="20" border="0" vspace="1" hspace="2" name="secret" alt="Secret"></a> 
                    <br>
                    <a href="javascript:void(-1);" onClick="javascript:jpilot('limit');"><img src="gfx/limit.gif" width="39" height="20" vspace="0" hspace="6" border="0" name="Limit" alt="Set or unset limit"></a> 
                    <a href="javascript:void(-1);" onClick="javascript:jpilot('invite');"><img src="gfx/invite.gif" width="39" height="20" vspace="0" hspace="6" border="0" name="invite" alt="invite someone to the channel"></a></td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
		<? } ?>
      </td>
    </tr>
    <tr valign="top" align="center"> 
      <td colspan="3"> 
        <div align="left"> &nbsp;&nbsp;&nbsp;<i><b>Services (these only include 
          some of the basic commands for easy access)...</b></i><br>
        </div>
        <table width="720" border="1" cellspacing="0" cellpadding="0" bordercolor="<?echo $bcolor;?>" height="90">
          <tr>
            <td width="239"><i>Nickserv</i></td>
            <td width="239"><i>Chanserv/botserv</i></td>
            <td width="240"><i>Memoserv</i></td>
          </tr>
          <tr>
            <td width="239" valign="top" align="center"><a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','register');"><img src="gfx/register.gif" height="20" align="absmiddle" name="register" border="0" alt="Register your nickname" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','identify');"><img src="gfx/identify.gif" height="20" align="absmiddle" name="identify" border="0" alt="Identify your nickname" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','ghost');"><img src="gfx/ghost.gif" height="20" align="absmiddle" name="ghost" border="0" alt="Remove a ghost" vspace="1" hspace="2" width="70"></a><br>
              <a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','recover');"><img src="gfx/recover.gif" height="20" align="absmiddle" name="recover" border="0" alt="Recover your nickname" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','release');"><img src="gfx/release.gif" height="20" align="absmiddle" name="release" border="0" alt="release a nickname from services" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','drop');"><img src="gfx/drop.gif" height="20" align="absmiddle" name="drop" border="0" alt="drop a nickname registration" vspace="1" hspace="2" width="70"></a><br>
              <a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','setkill');"><img src="gfx/kill.gif" height="20" align="absmiddle" name="kill" border="0" alt="set kill on/off" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','setsecure');"><img src="gfx/secure.gif" height="20" align="absmiddle" name="secure" border="0" alt="set security on/off" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('nickserv','newpass');"><img src="gfx/npass.gif" height="20" align="absmiddle" name="newpassword" border="0" alt="change your nickname password" vspace="1" hspace="2" width="70"></a><br>
            </td>
            <td width="239" align="center" valign="top"><a href="javascript:void(-1);" onClick="javascript:jpilot('chanserv','register');"><img src="gfx/register.gif" height="20" align="absmiddle" name="registerchan" border="0" alt="Register your channel" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('chanserv','identify');"><img src="gfx/identify.gif" height="20" align="absmiddle" name="identifyowner" border="0" alt="Identify as founder" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('chanserv','drop');"><img src="gfx/drop.gif" height="20" align="absmiddle" name="dropchan" border="0" alt="drop a channel registration" vspace="1" hspace="2" width="70"></a><br>
              <a href="javascript:void(-1);" onClick="javascript:jpilot('botserv','list');"><img src="gfx/listbots.gif" height="20" align="absmiddle" name="listbots" border="0" alt="List avaliable services bots" vspace="1" hspace="2" width="70"></a> 
              <a href="javascript:void(-1);" onClick="javascript:jpilot('botserv','assign');"><img src="gfx/assign.gif" height="20" align="absmiddle" name="assign" border="0" alt="Assign a bot to your channel" vspace="1" hspace="2" width="70"></a> 
              <a href="javascript:void(-1);" onClick="javascript:jpilot('botserv','unassign');"><img src="gfx/unassign.gif" height="20" align="absmiddle" name="unassign" border="0" alt="Un Assign a bot" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('chanserv','accesslist');"><img src="gfx/accesslist.gif" height="20" align="absmiddle" name="accesslist" border="0" alt="Access list" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('chanserv','add');"><img src="gfx/add.gif" height="20" align="absmiddle" name="addppl" border="0" alt="Add someone to access list" vspace="1" hspace="2" width="70"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('chanserv','del');"><img src="gfx/del.gif" height="20" align="absmiddle" name="delppl" border="0" alt="Delete someone from access list" vspace="1" hspace="2" width="70"></a></td>
			<td width="240" align="center" valign="top"><a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','send');"><img src="gfx/sendmemo.gif" width="70" height="20" vspace="1" hspace="2" name="sendmemo" alt="Send a memo to nick or channel" border="0"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','cancel');"><img src="gfx/cancelmemo.gif" width="70" height="20" vspace="1" hspace="2" name="cancelmemo" alt="Cancel your last send memo" border="0"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','list');"><img src="gfx/listmemos.gif" width="70" height="20" vspace="1" hspace="2" name="listmemos" alt="List your current memos" border="0"></a><br>
              <a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','readlast');"><img src="gfx/readlastmemo.gif" width="70" height="20" vspace="1" hspace="2" name="readlast" alt="read last received memo" border="0"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','read');"><img src="gfx/readmemo.gif" width="70" height="20" vspace="1" hspace="2" name="read" alt="Read memo number ?" border="0"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','delete');"><img src="gfx/delmemo.gif" width="70" height="20" vspace="1" hspace="2" name="del" alt="Delete memo number ?" border="0"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','delall');"><img src="gfx/delall.gif" width="70" height="20" vspace="1" name="delall" alt="Delete all memos" border="0" hspace="2"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','setlimit');"><img src="gfx/setlimit.gif" width="70" height="20" vspace="1" hspace="2" name="setlimit" alt="Set limit of memos to receive" border="0"></a><a href="javascript:void(-1);" onClick="javascript:jpilot('memoserv','setnotify');"><img src="gfx/setnotify.gif" width="70" height="20" vspace="1" hspace="2" name="setnotify" alt="When to be notifyed about new memos" border="0"></a></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
 </form>
</BODY>
</HTML>