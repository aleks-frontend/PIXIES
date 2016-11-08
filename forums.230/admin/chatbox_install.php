<?
error_reporting(7);
require("./global.php");
require("chatbox_config.php");
cpheader("<title>P0s3id0n's chatbox installation</title>");
?>
<center>
	<table border="0" cellspacing="0" cellpadding="1" class='tblborder' width='500'>
		<tr>
			<td><table cellpadding='4' cellspacing='0' border='0' width="100%">
				<tr class='tblhead'>
					<td width="100%" align="center"><b><font size="2">P0s3id0n's chatbox installation.</font></b></td>
				</tr>
				<tr class='secondalt'>
<?


// ###################################################
// #                 première étape                  #
// ###################################################

if(!$etape){
?>
		<td width="100%">First step : Adding tables to the database.<br>
			If the folowing page has 'tables added', all went right.<br><br>
			<center><a href="?etape=1">Install the chatbox in the database</a></center><br><br>
			If you prefer, you can add the tables manualy, so here is<br>
			the SQL query :<br><br>
			<center><textarea onfocus="this.select();" cols="50" rows="12">
CREATE TABLE chatbox (
  id int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  comment text NOT NULL,
  date varchar(30) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
CREATE TABLE chatbox_ban (
  id int(11) NOT NULL auto_increment,
  user varchar(255) NOT NULL default '',
  why text NOT NULL,
  date varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
CREATE TABLE chatbox_swears (
  id int(11) NOT NULL auto_increment,
  original varchar(255) NOT NULL default '',
  remplace varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
ALTER TABLE user
  ADD chatbox TINYINT DEFAULT 1 not null;
</textarea></center>
			<center>If you add the tables manually click <a href="?etape=1&param=1">here</a> to bypass next step.</center>
			<br><br>
			<center>If you want to uninstall the templates and the database structure of a previous version of the vbChatbox/vbShoutbox <a href="?etape=5">click here</a>.</center>
			<br><br>
<?


// ###################################################
// #                 deuxième étape                  #
// ###################################################

}elseif($etape == 1){
	chmod ("chatbox_config.php", 0775);
	if($param != 1){
		$result = $DB_site->query("CREATE TABLE chatbox (id INT(11) NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL default '', comment TEXT NOT NULL, date VARCHAR(30) NOT NULL default '', PRIMARY KEY  (id))");
		$result = $DB_site->query("CREATE TABLE chatbox_ban (id INT(11) NOT NULL AUTO_INCREMENT, user VARCHAR(255) NOT NULL default '', why TEXT NOT NULL, date VARCHAR(255) NOT NULL default '', PRIMARY KEY  (id))");
		$result = $DB_site->query("CREATE TABLE chatbox_swears (id INT not null AUTO_INCREMENT, original VARCHAR(255) NOT NULL, remplace VARCHAR(255) NOT NULL , PRIMARY KEY (id))");
		$result = $DB_site->query("ALTER TABLE user ADD chatbox TINYINT DEFAULT '1' not null");
		$validmessage = "<center><b>Tables added.</b></center><br><br>";
	}else $validmessage = "<center><b>Make sure you added the tables to the database before continuing.</b></center><br><br>";
	$result = $DB_site->query("SELECT * FROM templateset");
	$i = 0;
	while($row = mysql_fetch_assoc($result)){
		$selectbox .= "<option value=\"$row[templatesetid]\"> $row[title]";
	}
?>
		<form method="get" action="chatbox_install.php" name="install">
		<input type="hidden" name="etape" value="2">
		<td width="100%"><?=$validmessage?>
			If you have an error message about a chmod() function having failed,
			<b><font color="green">go to your admin folder and chmod manually the <font color="red"><i>chatbox_config.php</i></font>
			to 777</font></b><br><br>
			Second step : Adding templates necessary for the chatbox.<br><br>
			Select the templateset(s) in which you want to install the chatbox.<br>
			Keep the ctrl key pressed while clicking if you want to select more 
			than one templateset :<br>
			<center><select multiple name="templateset[]" size="3">
				<?=$selectbox?>
			</select></center><br><br>
			Then <a href="javascript:document.install.submit();">Click here</a> to add the templates and go to the next step.</form>
<?


// ###################################################
// #                troisième étape                  #
// ###################################################

}elseif($etape == 2){
	$path = "chatbox.style";
	$filesize = filesize($path);
	$filenum = fopen($path, "r");
	$styletext = fread($filenum, $filesize);
	fclose($filenum);
	$stylebits = explode("|||", $styletext);
	if($templateset){
		for($i = 0; $i < 11; $i++){
			list($devnull, $title) = each($stylebits);
			list($devnull, $template) = each($stylebits);
			foreach($templateset as $eachtemplateset){
				$DB_site->query("INSERT INTO template (templatesetid, title, template) VALUES ($eachtemplateset, '" . addslashes($title) . "', '" . addslashes($template) . "')");
			}
		}
		$validmessage = "<center><b>Templates added.</b></center>";
	}else $validmessage = "<center><b><font color=\"red\">You didn't select any templateset go back to <a href=\"?etape=1&param=1\">last step</a> and choose one, before continuing !</font></b></center>";
?>
		<td width="100%"><?=$validmessage?><br><br>
			Third step : Modification of the <b>forumhome</b> template in the
			group 'Forum Home Page Templates' in every templateset you inserted 
			the chatbox templates.<br><br>
			Just before <input type="text" value="<!-- main -->" onfocus="this.select();" readonly size="30"><br>
			add <input type="text" value="$chatbox" onfocus="this.select();" readonly size="30"><br><br>
			This step is yours to do !! Don't forget it, you wouldn't see anything different if you forgot ;)<br><br>
			<center><a href="?etape=3">Next step.</a></center>
<?


// ###################################################
// #                quatrième étape                  #
// ###################################################

}elseif($etape == 3){
?>
		<td width="100%">Fourth step : Files modification.<br><br>
			Open the <b>index.php</b> file in the admin directory of your forum and find :
			<input type="text" value='makenavoption("Mass Prune","thread.php?action=prune","|");' onfocus="this.select();" readonly size="30"><br>
			Then add just before :<br>
			<textarea onfocus="this.select();" cols="75" rows="12">// ##################################### chatbox par p0s3id0n
makenavoption("Prune", "chatbox_admin.php?action=prune","<br>");
makenavoption("Ban/unban", "chatbox_admin.php?action=ban","<br>");
makenavoption("Swear/unswear", "chatbox_admin.php?action=swear","<br>");
makenavoption("Config", "chatbox_admin.php?action=params","<br>");
makenavselect("Chatbox");
// ***
// ##################################### chatbox par p0s3id0n</textarea><br><br>
			Then open the <b>online.php</b> file in your forum root directory,
			find :<br>
			<textarea onfocus="this.select();" cols="60" rows="3">    case 'spider':
      $userinfo[where] = "Search Engine Spider";
      break;</textarea><br>
			And add just after :<br>
			<textarea onfocus="this.select();" cols="75" rows="5">// ##################################### chatbox par p0s3id0n
    case 'chatbox':
      $userinfo[where] = "Is currently reading the chatbox.";
      break;
// ##################################### chatbox par p0s3id0n</textarea><br><br>
			Then find :<br>
			<textarea onfocus="this.select();" cols="60" rows="3">  case '/robots.txt':
    $userinfo[activity] = 'spider';
    break;</textarea><br>
			And add just after :<br>
			<textarea onfocus="this.select();" cols="75" rows="5">// ##################################### chatbox par p0s3id0n
  case 'chatbox.php':
    $userinfo[activity] = 'chatbox';
	break;
// ##################################### chatbox par p0s3id0n</textarea><br><br>
			Then open the <b>index.php</b> file of your forum's root directory,
			find :<br>
			<input type="text" value="$templatesused='forumhome_birthdaybit,error_nopermission,forumhome_pmloggedin,forumhome_welcometext,forumhome_logoutcode,forumhome_newposts,forumhome_todayposts,forumhome_logincode,forumhome_loggedinuser,forumhome_loggedinusers,forumhome_lastpostby,forumhome_moderator,forumhome_forumbit_level1_nopost,forumhome_forumbit_level1_post,forumhome_forumbit_level2_nopost,forumhome_forumbit_level2_post,forumhome,forumhome_unregmessage';" onfocus="this.select();" readonly size="30"><br>
			And add just after :<br>
			<textarea onfocus="this.select();" cols="75" rows="5">// ##################################### chatbox par p0s3id0n
$templatesused .= ',chatbox,chatbox_bit,chatbox_bit_me,chatbox_bit_vide,chatbox_chat,chatbox_fermee,chatbox_frame,chatbox_popup_smilbox,chatbox_popup_smilboxbits,chatbox_saisie,chatbox_vbcodes';
require("./admin/chatbox_config.php");
// ##################################### chatbox par p0s3id0n</textarea><br><br>
			Then find :<br>
			<input type="text" value="eval(&quot;dooutput(\&quot;&quot;.gettemplate('forumhome').&quot;\&quot;);&quot;);" onfocus="this.select();" readonly size="30"><br>
			And add just before :<br>
			<textarea onfocus="this.select();" cols="75" rows="12">// ##################################### chatbox par p0s3id0n
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
// ##################################### chatbox par p0s3id0n</textarea><br><br>
			<center><a href="?etape=4">Next step.</a></center>
<?


// ###################################################
// #                cinquième étape                  #
// ###################################################

}elseif($etape == 4){
?>
		<td width="100%">Install is finished.<br><br>
			You can delete the chatbox_install.php file from your admin directory on your web server.<br>
			Then go back to your <a href="index.php">Admin CP</a> to configurate the new chatbox :).
<?


// ###################################################
// #           suppression de la chatbox             #
// ###################################################

}elseif($etape == 5){
	$result = $DB_site->query("SHOW COLUMNS FROM user");
	while($rangee = $DB_site->fetch_array($result)){
		if($rangee[0] == "chatbox")$resultat = $DB_site->query("ALTER TABLE user DROP COLUMN chatbox");
		if($rangee[0] == "shoutbox")$resultat = $DB_site->query("ALTER TABLE user DROP COLUMN shoutbox");
	}
	$result = $DB_site->query("DROP TABLE IF EXISTS chatbox");
	$result = $DB_site->query("DROP TABLE IF EXISTS chatbox_ban");
	$result = $DB_site->query("DROP TABLE IF EXISTS chatbox_swears");
	$result = $DB_site->query("DROP TABLE IF EXISTS shoutbox");
	$result = $DB_site->query("DROP TABLE IF EXISTS shoutbox_ban");
	$result = $DB_site->query("DROP TABLE IF EXISTS shoutbox_swears");
	$result = $DB_site->query("
		DELETE FROM template WHERE
			title = 'chatbox' OR
			title = 'chatbox_bit' OR
			title = 'chatbox_bit_me' OR
			title = 'chatbox_bit_vide' OR
			title = 'chatbox_chat' OR
			title = 'chatbox_fermee' OR
			title = 'chatbox_frame' OR
			title = 'chatbox_popup_smilbox' OR
			title = 'chatbox_popup_smilboxbits' OR
			title = 'chatbox_saisie' OR
			title = 'chatbox_vbcodes' OR
			title = 'shoutbox' OR
			title = 'shoutbox_bit' OR
			title = 'shoutbox_bit_me' OR
			title = 'shoutbox_bit_vide' OR
			title = 'shoutbox_chat' OR
			title = 'shoutbox_fermee' OR
			title = 'shoutbox_frame' OR
			title = 'shoutbox_popup_smilbox' OR
			title = 'shoutbox_popup_smilboxbits' OR
			title = 'shoutbox_saisie' OR
			title = 'shoutbox_vbcodes'
	");
	$validmessage = "<center><b>Chatbox database structure and templates deleted.</b></center><br><br>";
?>
		<td width="100%"><center><?=$validmessage?></center><br><br>
			You now have to delete the <b>shoutbox.php</b>,
			<b>admin/shoutbox_admin.php</b> and <b>admin/shoutbox_config.php</b>
			files if the version you are uninstalling has those names, otherwise delete <b>chatbox.php</b>,
			<b>admin/chatbox.php</b> and <b>admin/chatbox_config.php</b>.<br><br>
			Then open your <b>index.php</b>, <b>online.php</b>, <b>admin/index.php</b> files, find all the code between
			<i>'##### shoutbox by ...'</i> or <i>'##### chatbox by ...'</i> and delete it.<br><br>
			You can now <a href="chatbox_install.php">Install</a> this new version.
<?
}
?>
				</td>
			</tr>
		</table></td>
	</tr>
</table>
</center>
<?
cpfooter();
?>