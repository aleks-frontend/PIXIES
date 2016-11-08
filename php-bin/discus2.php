<?
############################################################################
#  Per-Site Variable Settings
############################################################################

   $username = "pixiesplace";
   $password = "pixie";
   $database = "pixiesplace";
   $forumpath = "http://www.pixies-place.com/forums"; # no trailing /

############################################################################
   
   $link = db_connect() or exit();
   $query = "SELECT title FROM forum WHERE forumid = $forum";

   $result = mysql_query("$query") or exit();

   if (mysql_num_rows($result)) {
      while ($row = mysql_fetch_array($result)) {
         $forum_name = $row["title"];
      }
   }

   $quoted_title = addslashes($topic);
   $query = "SELECT threadid FROM thread WHERE forumid = $forum AND title = \"$quoted_title\"";
   $result = mysql_query("$query") or exit();
   if (mysql_num_rows($result)) {
      while ($row = mysql_fetch_array($result)) {
         $threadid = $row["threadid"];
      }
   }
   $topic = str_replace(" ", "+", $topic);
  
   if ($threadid <= 0) {
      print "<A HREF=\"$forumpath/newthread.php?s=&action=newthread&forumid=14&subject=$thread[title]\">Start a Conversation about this story!</A>";
   } else {
     print ("<A HREF=\"$forumpath/showthread.php?s=&threadid=$threadid\">Join the Conversation about this story!</A>");
   }

function db_connect ()
{
    global $username, $password, $database;
    $link = @mysql_pconnect("localhost", $username, $password);
    if ($link && mysql_select_db($database)) return($link);
    return(FALSE);
}

?>





