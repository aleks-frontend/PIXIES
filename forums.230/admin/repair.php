<?php
error_reporting(7);

extract($HTTP_GET_VARS);

function checktable($table,$loops=0) {
  global $DB_site,$nohtml;

   $result=$DB_site->query("CHECK TABLE $table");
   if (!$nohtml) {
     echo "<tr bgcolor='#CCCCCC'><td colspan=4 align='center'>Checking table $table</td></tr>";
     echo "<tr><td>Table</td><td>Operation</td><td>Type</td><td>Text</td></tr>";
   } else {
     echo "\n>>>>>>>>>>>>>Checking Table $table\n";
     echo "---------------------------------\n";
   }
   $error=0;
   while ($r=$DB_site->fetch_array($result)) {
     if ($r[2]=='error') {
       if ($r[3]=="The handler for the table doesn't support check/repair") {
         $r[2] = 'status';
         $r[3] = 'This table does not support check/repair/optimize';
         unset($bgcolor);
         $nooptimize=1;
       } else {
         $error=1;
         $bgcolor='red';
         unset($nooptimize);
       }
     } else {
       unset($bgcolor);
       unset($nooptimize);
     }
     if (!$nohtml) {
       echo "<tr><td>$r[0]</td><td>$r[1]</td><td bgcolor='$bgcolor'>$r[2]</td><td>$r[3]</td></tr>";
     } else {
       echo "$r[0] | $r[1] | $r[2] | $r[3]\n";
     }
   }
   if ($error) {
     if (!$nohtml) {
       echo "<tr><td colspan=4 align='center'>Repairing table $table</td></tr>";
     } else {
       echo ">>>>>>>>>>>>>Repairing Table $table\n";
     }
     $result2=$DB_site->query_first("REPAIR TABLE $table");
     if ($result2[3]!='OK')
       $bgcolor='red';
     else
       unset($bgcolor);
     if (!$nohtml) {
       echo "<tr><td>$result2[0]</td><td>$result2[1]</td><td>$result2[2]</td><td bgcolor='$bgcolor'>$result2[3]</td></tr>";
     } else {
       echo "$result2[0] | $result[1] | $result2[2] | $result2[3]\n";
     }
   }
   if (($result2[3]=='OK'||!$error)&&!$nooptimize) {
     if (!$nohtml) {
       echo "<tr><td colspan=4 align='center'>Optimizing table $table</td></tr>";
     } else {
       echo ">>>>>>>>>>>>>Optimizing Table $table\n";
     }
     $result3=$DB_site->query("OPTIMIZE TABLE $table");
     $error=0;
     while ($r3=$DB_site->fetch_array($result3)) {
       if ($r3[2]=='error') {
         $error=1;
         $bgcolor='red';
       } else {
         unset($bgcolor);
       }
       if (!$nohtml) {
         echo "<tr><td>$r3[0]</td><td>$r3[1]</td><td bgcolor='$bgcolor'>$r3[2]</td><td>$r3[3]</td></tr>";
       } else {
         echo "$r3[0] | $r3[1] | $r3[2] | $r3[3]\n";
       }
     }
   }
   if ($error&&$loops) {
     checktable($table,($loops-1));
   }
}

if ($check) {
  include('./global.php');

  if ($forum) {
    $DB_site->query("USE ".addslashes($forum)."");
  }

  $tables=$DB_site->query("SHOW TABLES");

  if (!$nohtml) {
    echo "<HTML><HEAD></HEAD><BODY><table border=1 cellspacing=0 cellpadding=4>";
  }

  if ($iterations) {
    $iterations--;
  }
  while ($table=$DB_site->fetch_array($tables)) {

     $answer=checktable($table[0],$iterations);
     if (!$nohtml) {
       echo "<tr><td colspan=4>&nbsp;</td></tr>";
     }
     flush();
  }

  if (!$nohtml) {
    echo "</table></BODY></HTML>";
  }
} else {
  echo "<HTML><HEAD></HEAD><BODY><b>repair.php for mysql 3.23 or greater</b><br><br>".
       "This script will attempt to repair corrupted databases. While it can repair most".
       " common errors, it can not repair everything. As a side benefit it will also optimize".
       " your tables. This script is unsupported by jelsoft.<br><br>".
       "Usage:<br><br>".
       "<b>iterations=x</b> (x is the number of times you wish for the script to attempt to repair a broken table)<br>".
       "<b>nohtml=1</b> (Output text only no html. Useful for cronjobs and you wish to receive text only)<br>".
       "<b>forum=x</b> (x is the name of the database to check. Leave it off to use the default database of your vBulletin forum)<br>".
       "<b>check=1</b> (without this you will get this screen and no checking)<br><br>".
       "Example Usage:<br><br>".
       "<b>repair.php?html=1&check=1</b> (most common usage, will check a forum and return html results)<br>".
       "<b>repair.php?check=1&iterations=4&forum=vbdatabase</b> (check the database \"vbdatabase\", try to repair damaged tables 4 times)".
       "</BODY></HTML>";
}
?>
