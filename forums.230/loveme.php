<?php

/* header
_______________________________________________
_________ « Is there any love? hack » _________

× Author: dymo
× Modification: floris
× Status: Beta (version ?unknown?)
× Last build: 30 April 2002 (13:45h)
× Support: Through thread!
× Any of the files from this add-on may only be 
  distributed through vBulletin.org/creations.nl
× Modifications are allowed as long as this header stays
_______________________________________________

Changelog 0.0.1 - beta release on vbulletin by dymo
          0.0.2 - loveme.php & loveme template modification for better integration with vbulletin
          0.0.3 - added loveme_help template
          
*/

error_reporting(7); 
$templatesused="loveme";
require('./global.php');

// if nothing is entered set action to help
if (!isset($action) or $action=="") {
  $action="help";
}

// display loveme_help template
if ($action=="help") {
eval("dooutput(\"".gettemplate("loveme_help")."\");");
}

if(!$bbuserinfo[userid]) {
  eval("dooutput(\"".show_nopermission()."\");");
} 

// display loveme_help template
if ($action=="loveme") {

$usershit = $DB_site->query_first("SELECT userid,username,email FROM user WHERE userid=$bbuserinfo[userid]");
$username=$usershit[username];
$htmldoctype;


if($calculate == "love")
{ 
$first = strtoupper($name1); 
$firstlength = strlen($name1); 
$second = strtoupper($name2); 
$secondlength = strlen($name2); 
$LoveCount=0; 
for ($Count=0; $Count < $firstlength; $Count++) 
{ 
$letter1 = $first[$Count]; 
if ($letter1=="L") 
$LoveCount+=2; 
if ($letter1=="I") 
$LoveCount+=2; 
if ($letter1=="L") 
$LoveCount+=2; 
if ($letter1=="I") 
$LoveCount+=2; 
if ($letter1=="T") 
$LoveCount+=3; 
if ($letter1=="H") 
$LoveCount+=1; 
if ($letter1=="U") 
$LoveCount+=3; 
} 
for ($Count=0; $Count < $secondlength; $Count++) 
{ 
$letter2= $second[$Count]; 
if ($letter2=="L") 
$LoveCount+=2; 
if ($letter2=="O") 
$LoveCount+=2; 
if ($letter2=="V") 
$LoveCount+=2; 
if ($letter2=="E") 
$LoveCount+=2; 
if ($letter2=="Y") 
$LoveCount+=3; 
if ($letter2=="O") 
$LoveCount+=1; 
if ($letter2=="U") 
$LoveCount+=3; 
} 
$amount=0; 
if ($LoveCount> 0) 
$amount=  5-(($firstlength+$secondlength)/2); 
if ($LoveCount> 2) 
$amount= 10-(($firstlength+$secondlength)/2); 
if ($LoveCount> 4) 
$amount= 20-(($firstlength+$secondlength)/2); 
if ($LoveCount> 6) 
$amount= 30-(($firstlength+$secondlength)/2); 
if ($LoveCount> 8) 
$amount= 40-(($firstlength+$secondlength)/2); 
if ($LoveCount>10) 
$amount= 50-(($firstlength+$secondlength)/2); 
if ($LoveCount>12) 
$amount= 60-(($firstlength+$secondlength)/2); 
if ($LoveCount>14) 
$amount= 70-(($firstlength+$secondlength)/2); 
if ($LoveCount>16) 
$amount= 80-(($firstlength+$secondlength)/2); 
if ($LoveCount>18) 
$amount= 90-(($firstlength+$secondlength)/2); 
if ($LoveCount>20) 
$amount=100-(($firstlength+$secondlength)/2); 
if ($LoveCount>22) 
$amount=110-(($firstlength+$secondlength)/2); 
if ($firstlength==0 || $secondlength==0) 
$amount= "Err"; 
if ($amount < 0) 
$amount= 0; 
if ($amount > 99) 
$amount=99; 
eval("dooutput(\"".gettemplate("loveme")."\");");
} 
else 
{
}
}

?>  
