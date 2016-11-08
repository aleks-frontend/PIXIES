<?php
require("config.php");

?>

<HTML>
<HEAD>
<TITLE>Chat</TITLE>
</head>
<BODY bgcolor=#6384B5 leftmargin="0" marginwidth="0" topmargin="0" marginheight="0">
<script language="JavaScript" src="controls.js"></script>
<form name="jform">  
  <table width="471" height="425" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#FF9966" bgcolor="#FFCC00">
    <tr bgcolor="#FFCC00"> 
      <td width="22" height="16">&nbsp;</td>
      <td height="16" valign="middle"> <? if ($showchans == "true") {
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
      </td>
    </tr>
    <tr> 
      <td width="22" height="277" align="center" valign="top" bgcolor="#FFCC00"> <a href="javascript:document.jchat.processJInput(':)');"><img src="gfx/smile.gif" border=0 alt="happy" vspace="2" hspace="0"></a><br> 
        <a href="javascript:document.jchat.processJInput(';)');"><img src="gfx/winkface.gif" border=0 width="15" height="15" alt="wink" hspace="0" vspace="2"></a><br> 
        <a href="javascript:document.jchat.processJInput(':(');"><img src="gfx/frown.gif" border=0 alt="sad" vspace="2" hspace="0"></a><br> 
        <a href="javascript:document.jchat.processJInput(':~(');"><img src="gfx/cry.gif" border=0 width="15" height="15" alt="cry" vspace="2" hspace="0"></a><br> 
        <a href="javascript:document.jchat.processJInput(':D');"><img src="gfx/biggrin.gif" border=0 alt="big grin" vspace="2" hspace="0"></a><br> 
        <a href="javascript:document.jchat.processJInput(':P');"><img src="gfx/tongue1.gif" border=0 alt="tongue" width="15" height="15" vspace="2" hspace="0"></a><br> 
        <a href="javascript:document.jchat.processJInput('>:)');"><img src="gfx/evil.gif" border=0 alt="evil" width="15" height="15" vspace="2" hspace="0"></a><br> 
        <a href="javascript:document.jchat.processJInput('§:o)');"><img src="gfx/clown.gif" border=0 width="15" height="15" alt="clown" vspace="2" hspace="0"></a><br> 
      </td>
      <td width="602" valign="top" height="277"> <applet name="jchat" archive="jirc_nss.zip"  code=Chat.class width=450 height=300 vspace="1" hspace="1" >
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
    </tr>
    <tr valign="top" align="center"> 
      <td colspan="2"> <div align="left"> </div>
        <table border="0" cellspacing="0" cellpadding="0" bordercolor="<?echo $bcolor;?>">
          <tr> 
            <td width="239"><font color="#000000" size="2" face="Arial, Helvetica, sans-serif">Nickserv</font></td>
            <td width="239"><font size="2" face="Arial, Helvetica, sans-serif">Chanserv/botserv</font></td>
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
          </tr>
        </table></td>
    </tr>
  </table>
 </form><br><iframe name="chat" noresize width="0" src="../chat.php" scrolling="no" height="0"></iframe></iframe>
</BODY>
</HTML>