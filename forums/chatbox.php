<?
error_reporting(7);

$templatesused = 'chatbox,chatbox_bit,chatbox_bit_me,chatbox_bit_vide,chatbox_chat,chatbox_fermee,chatbox_frame,chatbox_popup_smilbox,chatbox_popup_smilboxbits,chatbox_saisie,chatbox_vbcodes';

require("./global.php");
require("./includes/chatbox_config.php");
require_once( './includes/functions_newpost.php' );
require_once( './includes/functions_bbcodeparse.php' );


// ###################################################
// #               fonction swear                    #
// ###################################################
function swear($comment){
	global $DB_site, $swears;
	if($swears){
		while(list($orig, $replace) = each($swears)){
			$comment = str_replace($orig, $replace, $comment);
		}
		reset($swears);
	}
	return $comment;
}


// ###################################################
// #                 frame saisie                    #
// ###################################################

if($page_chat == "saisie"){
	if($setting == "save"){
		$result = $DB_site->query("SELECT ip FROM ban WHERE ip = '$bbuserinfo[username]'");
		if(mysql_num_rows($result)){
			exit();
		}
	}
	eval("print_output(\"".fetch_template('chatbox_saisie')."\");");


// ###################################################
// #                 frame chat                     #
// ###################################################

}elseif($page_chat == "chat"){
	if($setting == "save" && $bbuserinfo['username'] != "Unregistered" && $message){
		$date = "[" . date("d/m") . "|" . date("H:i") . "]";
		$result = $DB_site->query("SELECT user FROM chatbox_ban WHERE user = '$bbuserinfo[username]'");
		if(mysql_num_rows($result)){
			echo "<font face=\"Verdana\" size=1>You are banned from the chatbox. PM an admin if you think it's a mistake.</font>\n";
			exit();
		}
//		if(chop($message . "") == ""){
//			echo "<meta http-equiv=\"refresh\" content=\"0;URL=chatbox.php?page_chat=chat\">";
//		}
		$sql = "SELECT id FROM chatbox WHERE date LIKE '" . substr($date,0,10) . "%' AND name = '$bbuserinfo[username]' AND comment = '" . addslashes($message)."'";
		$result = $DB_site->query($sql);
		if(!mysql_num_rows($result)){
			$sql = "INSERT INTO chatbox (name, comment, date) VALUES ('$bbuserinfo[username]', '" . addslashes($message) . "', '$date')";
			$result = $DB_site->query($sql);
			$result = $DB_site->query("SELECT id FROM chatbox WHERE name='$bbuserinfo[username]' AND comment = '" . addslashes($message) . "' AND date = '$date'");
			$rangee = mysql_fetch_assoc($result) ;
			$identifiant = $rangee['id'] ;
			$sql = "DELETE FROM chatbox WHERE id = '" . ($identifiant - $perpage - 10) . "'";
			$result = $DB_site->query($sql);
			echo "<meta http-equiv=\"refresh\" content=\"0;URL=chatbox.php?page_chat=chat\">";
		}else{
			echo "<meta http-equiv=\"refresh\" content=\"0;URL=chatbox.php?page_chat=chat\">";
		}
	}else{
		$result = $DB_site->query("SELECT original, remplace FROM chatbox_swears");
		while($row = mysql_fetch_assoc($result)){
			$swears[$row["original"]] = $row["remplace"];
		}
		$result = $DB_site->query("SELECT * FROM chatbox ORDER BY id DESC LIMIT $perpage");
		if($rafraichissement){
			$refresh = "<meta http-equiv=\"refresh\" content=\"$rafraichissement;URL=chatbox.php?page_chat=chat\">";
		}
		if(!mysql_num_rows($result)){
			eval("\$chatbit .= \"".fetch_template('chatbox_bit_vide')."\";");
		}else{
			$compteur=0;
			while($get = mysql_fetch_assoc($result)){
				$compteur = $compteur + 1;
				if($compteur%2)$colorirc = $un_color_chat;
				else $colorirc = $deux_color_chat;
				$name = $get["name"];
				$comment = $get["comment"];
				$date = $get["date"];
				$date = date("Y") . "-" . substr($date, 4, 2) . "-" . substr($date, 1, 2) . " " . substr($date, 7, 2) . ":" . substr($date, 10, 2);
				$date = date("[d/m|H:i]", strtotime($date) + (($bbuserinfo['timezoneoffset']+5) * 3600));
				$comment = convert_url_to_bbcode($comment);
				$comment = ereg_replace("sessionhash=[a-z0-9]{32}", "", $comment);
				$comment = ereg_replace("s=[a-z0-9]{32}", "", $comment);
				$comment = parse_bbcode($comment);
				$comment = str_replace("<br />"," ",$comment);
				$comment = str_replace("<br>"," ",$comment);
				$comment = swear($comment);
				if(substr($comment,0,3) == "/me"){
					$comment = stripslashes(substr($comment,3));
					eval("\$chatbit .= \"".fetch_template('chatbox_bit_me')."\";");
					$chatbit .= "<br>";
				}else{
					eval("\$chatbit .= \"".fetch_template('chatbox_bit')."\";");
					$chatbit .= "<br>";
				}
			}
		}
		eval("print_output(\"".fetch_template('chatbox_chat')."\");");
	}

// ###################################################
// #           Popup Smilies for chatbox            #
// ###################################################
}elseif($page_chat == "popupsmil"){
	$smilies = $DB_site->query("SELECT smilietext AS text, smiliepath AS path, title FROM smilie");

	$rightorleft = 'left';
	while($smilie = $DB_site->fetch_array($smilies)){
		if($rightorleft == 'left'){
			if(($i++ % 2) != 0)$backcolor='{firstaltcolor}';
			else $backcolor='{secondaltcolor}';
			$popup_smiliesbits .= "";
			eval ("\$popup_smiliesbits .= \"<tr>".fetch_template("chatbox_popup_smilboxbits")."\";");
			$rightorleft = 'right';
		}else{
			eval ("\$popup_smiliesbits .= \"".fetch_template("chatbox_popup_smilboxbits")."</tr>\";");
			$rightorleft = 'left';
		}
	}
	if($rightorleft == 'right'){
		$popup_smiliesbits .= "		<td bgcolor=\"$backcolor\">&nbsp;</td>\n";
		$popup_smiliesbits .= "		<td bgcolor=\"$backcolor\">&nbsp;</td>\n";
		$popup_smiliesbits .= "	</tr>";
	}
	eval("print_output(\"".fetch_template("chatbox_popup_smilbox")."\");");


// ###################################################
// #                  Popup vbcodes                  #
// ###################################################
}elseif($page_chat=="popupvb"){
	global $vbcodemode,$vbcode_smilies;

	// set $vbcodemode to an integer, even if cookie is not set
	$vbcodemode = number_format($vbcodemode);
	// set mode based on cookie set by javascript
	$modechecked[$vbcodemode] = "checked";

	// get contents of the <select> font menus
	eval ("\$vbcode_sizebits = \"".fetch_template("vbcode_sizebits")."\";");
	eval ("\$vbcode_fontbits = \"".fetch_template("vbcode_fontbits")."\";");
	eval ("\$vbcode_colorbits = \"".fetch_template("vbcode_colorbits")."\";");

	eval("print_output(\"".fetch_template("chatbox_vbcodes")."\");");


// ###################################################
// #                   frameset                      #
// ###################################################

}else{
	$comment = urlencode($comment);
	eval("print_output(\"".fetch_template('chatbox_frame')."\");");
}
?>
