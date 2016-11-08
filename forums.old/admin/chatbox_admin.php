<?
error_reporting(7);
require("./global.php");
require("chatbox_config.php");
cpheader();

$date_show = date("l, M. dS, Y");
$date = date("Y-m-d");
$back = $HTTP_REFERER;


// ###################################################
// #                     config                      #
// ###################################################

if($action == "params"){
	$path = "chatbox_config.php";
	if($do == "save"){
		$perpage_niou = str_pad($perpage_niou, 5, " ", STR_PAD_LEFT);
		$rafraichissement_niou = str_pad($rafraichissement_niou, 5, " ", STR_PAD_LEFT);
		$filenum = fopen($path, "w");
		$fichierconfig = "<" . "?\$perpage = $perpage_niou;\$rafraichissement = $rafraichissement_niou;\$un_color_chat = \"#$un_color_chat_niou\";\$deux_color_chat = \"#$deux_color_chat_niou\";\$me_color_chat = \"#$me_color_chat_niou\";\$date_color_chat = \"#$date_color_chat_niou\";\$fondchatbox = \"#$fondchatbox_niou\";?" . ">";
		fwrite($filenum, $fichierconfig);
		fclose($filenum);
	}
	$filesize = filesize($path);
	$filenum = fopen($path, "r");
	$fichierconfig = fread($filenum, $filesize);
	fclose($filenum);
	$perpage = substr($fichierconfig, 13, 5);
	$rafraichissement = substr($fichierconfig, 39, 5);
	$un_color_chat = substr($fichierconfig, 64, 6);
	$deux_color_chat = substr($fichierconfig, 93, 6);
	$me_color_chat = substr($fichierconfig, 120, 6);
	$date_color_chat = substr($fichierconfig, 149, 6);
	$fondchatbox = substr($fichierconfig, 174, 6);
?>
<script language="javascript">
function updatecolor(couleur){
	if(couleur.value.length == 6)document.config.preview.style.backgroundColor = "#" + couleur.value;
}
</script>
<center>
<b><font size="4">chatbox Administration</font><br><br>
<form action="chatbox_admin.php" method="post" name="config">
	<input type="hidden" name="action" value="params">
	<input type="hidden" name="do" value="save">
	<table border="0" cellspacing="0" cellpadding="1" class="tblborder">
		<tr>
			<td><table cellpadding="4" cellspacing="0" border="0" width="100%">
				<tr class="tblhead">
					<td colspan="2" class="tblhead"><font size="1"><b>Configuration :</b></font></td>
				</tr>
				<tr class="secondalt">
					<td><font size="1">Total number of chats in the box : </font></td>
					<td align="right"><input type="text" name="perpage_niou" size="10" value="<?=intval($perpage)?>"></td>
				</tr>
				<tr class="firstalt">
					<td><font size="1">Time in seconds between each refresh :<br>Leave 0 for the refresh to be manual.</font></td>
					<td align="right"><input type="text" name="rafraichissement_niou" size="10" value="<?=intval($rafraichissement)?>"></td>
				</tr>
				<tr class="secondalt">
					<td><font size="1">First chat color : </font></td>
					<td>#<input type="text" name="un_color_chat_niou" size="15" value="<?=$un_color_chat?>" onkeyup="updatecolor(this)" onfocus="updatecolor(this)"></td>
				</tr>
				<tr class="firstalt">
					<td><font size="1">Second chat color :</font></td>
					<td>#<input type="text" name="deux_color_chat_niou" size="15" value="<?=$deux_color_chat?>" onkeyup="updatecolor(this)" onfocus="updatecolor(this)"></td>
				</tr>
				<tr class="secondalt">
					<td><font size="1">chat /me color : </font></td>
					<td>#<input type="text" name="me_color_chat_niou" size="15" value="<?=$me_color_chat?>" onkeyup="updatecolor(this)" onfocus="updatecolor(this)"></td>
				</tr>
				<tr class="firstalt">
					<td><font size="1">Datetime color :</font></td>
					<td>#<input type="text" name="date_color_chat_niou" size="15" value="<?=$date_color_chat?>" onkeyup="updatecolor(this)" onfocus="updatecolor(this)"></td>
				</tr>
				<tr class="secondalt">
					<td><font size="1">chatbox background color : </font></td>
					<td>#<input type="text" name="fondchatbox_niou" size="15" value="<?=$fondchatbox?>" onkeyup="updatecolor(this)" onfocus="updatecolor(this)"></td>
				</tr>
				<tr id="submitrow">
					<td colspan="2" align="center"><input type="submit" value="Update config"><input type="reset" value="Reset"></td>
				</tr>
			</table></td>
		</tr>
	</table>
	<br><br><input type="button" disabled value="Selected color" name="preview" style="background-color:#ffffff">
</form>
</center>
<?


// ###################################################
// #                   ban/unban                     #
// ###################################################

}elseif($action == "ban"){
	if($do == "unban" && $id_ban){
		foreach($id_ban as $eachid){
			$result = $DB_site->query("DELETE FROM chatbox_ban WHERE id = '$eachid'");
		}
	}elseif($do == "ban"){
		$user_ban = addslashes($user_ban);
		$why = addslashes($why);
		$result = $DB_site->query("INSERT INTO chatbox_ban (user, why, date) VALUES ('$user_ban', '$why', '$date')");
		$result = $DB_site->query("DELETE FROM chatbox WHERE name = '$user_ban'");
	}
?>
<center>
<b><font size="4">chatbox Administration</font><br><br>
<form action="chatbox_admin.php" method="post">
	<input type="hidden" name="action" value="ban">
	<input type="hidden" name="do" value="ban">
	<table border="0" cellspacing="0" cellpadding="1" class='tblborder'>
		<tr>
			<td><table cellpadding="4" cellspacing="0" border="0" width="100%">
				<tr class="tblhead">
					<td colspan="2" class="tblhead"><font size="1"><b>Ban a user nick </b>(only one user at a time) :</font></td>
				</tr>
				<tr class="secondalt">
					<td width="50%" valign="top"><font size="1">User to ban : </font></td>
					<td width="50%" valign="top"><input type="text" name="user_ban" size="20"></td>
				</tr>
				<tr class="firstalt">
					<td width="50%" valign="top"><font size="1">Reason : </font></td>
					<td width="50%" valign="top"><textarea rows="5" name="why" cols="23"></textarea></td>
				</tr>
				<tr id="submitrow">
					<td width="100%" colspan="2" align="center"><input type="submit" value="Ban" name="B1"><input type="reset" value="Reset" name="B2"></td>
				</tr>
			</table></td>
		</tr>
	</table>
</form>
<br><br>
<form action="chatbox_admin.php" method="post">
	<input type="hidden" name="action" value="ban">
	<input type="hidden" name="do" value="unban">
	<table border="0" cellspacing="0" cellpadding="1" class="tblborder">
		<tr>
			<td><table cellpadding="4" cellspacing="0" border="0" width="100%">
				<tr class="tblhead">
					<td colspan="4" class="tblhead"><b><font size="1">Unban </b> :</font></td>
				</tr>
<?
	$result = $DB_site->query("SELECT * FROM chatbox_ban ORDER BY id DESC");
	$num_result = mysql_num_rows($result);
	if($num_result == "0"){
?>
				<tr class="secondalt">
					<td colspan="4" width="100%"><font size="1">There are no banned users.</font></td>
				</tr>
<?
	}else{
		while($ipped = mysql_fetch_row($result)){
			$count_bg += 1;
			if($count_bg%2)$couleur_bg = "class=\"secondalt\"";
			else $couleur_bg = "class=\"firstalt\"";
?>
				<tr <?=$couleur_bg?>>
					<td><input type="checkbox" name="id_ban[]" value="<?=$ipped[0]?>"></td>
					<td nowrap><font size="1"><?=stripslashes($ipped[1])?></font></td>
					<td><font size="1"><?=stripslashes($ipped[2])?></font></td>
					<td nowrap><font size="1"><?=$ipped[3]?></font></td>
				</tr>
<?
		}
?>
				<tr id="submitrow">
					<td colspan="4" align="center"><input type="submit" value="Unban" name="B1"></td>
				</tr>
			</table></td>
		</tr>
	</table>
</form>
</center>
<?
	}


// ###################################################
// #                     prune                       #
// ###################################################

}elseif($action == "prune"){
	if($do == "del" && $id){
		foreach($id as $eachid){
			$requete = "DELETE FROM chatbox WHERE id = '$eachid'";
			$result = $DB_site->query($requete);
		}
	}elseif($do == "del"){
		$requete = "DELETE FROM chatbox";
		$result = $DB_site->query($requete);
	}
	$result = $DB_site->query("SELECT * FROM chatbox ORDER BY id");
	$num_result = mysql_num_rows($result);
?>
<center>
<b><font size="4">chatbox Administration</font><br><br>
<form action="chatbox_admin.php" method="post" name="prune">
	<input type="hidden" name="action" value="prune">
	<input type="hidden" name="do" value="del">
	<table border="0" cellspacing="0" cellpadding="1" class="tblborder">
		<tr>
			<td><table cellpadding="4" cellspacing="0" border="0" width="100%">
				<tr class="tblhead">
					<td colspan="4" class="tblhead"><b><font size="1">Delete chat(s) </b> :</font></td>
				</tr>
<?
	if($num_result == "0"){
?>
				<tr class="secondalt">
					<td colspan="4" width="100%"><font size="1">There are no chats yet.</font></td>
				</tr>
<?
	}else{
		while($ipped = mysql_fetch_row($result)){
			$count_bg += 1;
			if($count_bg % 2)$couleur_bg = "class=\"secondalt\"";
			else $couleur_bg = "class=\"firstalt\"";
?>
				<tr <?=$couleur_bg?>>
					<td><input type="checkbox" name="id[]" value="<?=$ipped[0]?>"></td>
					<td nowrap><font size="1"><?=stripslashes($ipped[3])?></font></td>
					<td nowrap><font size="1"><?=stripslashes($ipped[1])?></font></td>
					<td><font size="1"><?=stripslashes(bbcodeparse($ipped[2]))?></font></td>
				</tr>
<?
		}
	}
?>
				<tr id="submitrow">
					<td colspan="4" align="center"><input type="submit" value="Delete the selected chat(s)"> <input type="button" onclick="if(confirm('Are you sure you want to delete all the chats ?'))prune.submit();" value="Delete all the chats"></td>
				</tr></form>
			</table></td>
		</tr>
	</table>
</center>
<?


// ###################################################
// #                swear/unswear                    #
// ###################################################

}elseif($action == "swear"){
	if($do == "del" && $id){
		foreach($id as $eachid){
			$result = $DB_site->query("DELETE FROM chatbox_swears WHERE id = '$eachid'");
		}
	}elseif($do == "add"){
		$orig = addslashes($orig);
		$replace = addslashes($replace);
		$result = $DB_site->query("INSERT INTO chatbox_swears (id, original, remplace) VALUES ('', '$orig', '$replace')");
	}
?>
<center>
<b><font size="4">chatbox Administration</font><br><br>
<form action="chatbox_admin.php" method="post">
	<input type="hidden" name="action" value="swear">
	<input type="hidden" name="do" value="add">
	<table border="0" cellspacing="0" cellpadding="1" class="tblborder">
		<tr>
			<td><table cellpadding="4" cellspacing="0" border="0" width="100%">
				<tr class="tblhead">
					<td colspan="2" class="tblhead"><font size="1"><b>Add a forbidden word</b> (pay attention to the case) :</font></td>
				</tr>
				<tr class="secondalt">
					<td width="50%" valign="top"><font size="1">Word to replace : </font></td>
					<td width="50%" valign="top"><input type="text" name="orig" size="30"></td>
				</tr>
				<tr class="firstalt">
					<td width="50%" valign="top"><font size="1">Remplacement word : </font></td>
					<td width="50%" valign="top"><input type="text" name="replace" size="30"></td>
				</tr>
				<tr id="submitrow">
					<td width="100%" colspan="2" align="center"><input type="submit" value="Add"><input type="reset" value="Effacer"></td>
				</tr>
			</table></td>
		</tr>
	</table>
</form>
<br><br>
<form action="chatbox_admin.php" method="post">
	<input type="hidden" name="action" value="swear">
	<input type="hidden" name="do" value="del">
	<table border="0" cellspacing="0" cellpadding="1" class="tblborder">
		<tr>
			<td><table cellpadding="4" cellspacing="0" border="0" width="100%">
				<tr class="tblhead">
					<td colspan="4" class="tblhead"><b><font size="1">Delete forbiden words </b> :</font></td>
				</tr>
<?
	$result = $DB_site->query("SELECT * FROM chatbox_swears ORDER BY original");
	$num_result = mysql_num_rows($result);
	if($num_result == "0"){
?>
				<tr class="secondalt">
					<td colspan="4" width="100%"><font size="1">There are no forbidden words yet.</font></td>
				</tr>
<?
	}else{
		while($ipped = mysql_fetch_row($result)){
			$count_bg += 1;
			if($count_bg % 2)$couleur_bg = "class=\"secondalt\"";
			else $couleur_bg = "class=\"firstalt\"";
?>
				<tr <?=$couleur_bg?>>
					<td><input type="checkbox" name="id[]" value="<?=$ipped[0]?>"></td>
					<td nowrap><font size="1"><?=stripslashes($ipped[1])?></font></td>
					<td><font size="1"><?=stripslashes($ipped[2])?></font></td>
					<td nowrap><font size="1"><?=$ipped[3]?></font></td>
				</tr>
<?
		}
?>
				<tr id="submitrow">
					<td colspan="4" align="center"><input type="submit" value="Delete"></td>
				</tr>
			</table></td>
		</tr>
	</table>
</form>
</center>
<?
	}
}
cpfooter();
?>