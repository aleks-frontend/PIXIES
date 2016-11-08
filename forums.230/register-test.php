<?php
error_reporting(7);

$templatesused = "signupadult,signupverify,modifyoptions_maxposts,register_birthday,modifyoptions_styleset,register_stylecell,register_customfields,registeradult,registercoppa,email_newuser,emailsubject_newuser,email_activateaccount,emailsubject_activateaccount,redirect_registerthanks";

require("./global.php");

if ((!isset($action) or $action=="") and (!isset($a) or $a=="")) {
  $action="signup";
}

if ($url==$HTTP_REFERER) {
  $url=urlencode($url);
}

// ############################### start signup ###############################
if ($action=="signup") {

  if (!$allowregistration) {
    eval("standarderror(\"".gettemplate("error_noregister")."\");");
    exit;
  }

  if ($bbuserinfo[userid]!=0 and !$allowmultiregs) {
    $getuser=$DB_site->query_first("SELECT username FROM user WHERE userid='$bbuserinfo[userid]'");
    $username=$getuser[username];

    eval("standarderror(\"".gettemplate("error_alreadyregistered")."\");");

    exit;
  }

  $coppadate=vbdate($dateformat,mktime(0,0,0,date("m"),date("d"),date("Y")-13));

  if (!$usecoppa) {
    $who="adult";
  }

  if ($who=="coppa") {
    eval("dooutput(\"".gettemplate("signupcoppa")."\");");
  } else {
    if ($who=="adult") {
      eval("dooutput(\"".gettemplate("signupadult")."\");");
    } else {
      eval("dooutput(\"".gettemplate("signupverify")."\");");
   }
 }
}

// ############################### start register ###############################
if ($action=="register") {
  if (!$allowregistration) {
    eval("standarderror(\"".gettemplate("error_noregister")."\");");
    exit;
  }

  if ($bbuserinfo[userid]!=0 and !$allowmultiregs) {
    $getuser=$DB_site->query_first("SELECT username FROM user WHERE userid='$bbuserinfo[userid]'");
    $username=$getuser[username];

    eval("standarderror(\"".gettemplate("error_alreadyregistered")."\");");

    exit;
  }

  if ($allowhtml) {
    $htmlonoff=$ontext;
  } else {
    $htmlonoff=$offtext;
  }
  if ($allowbbcode) {
    $bbcodeonoff=$ontext;
  } else {
    $bbcodeonoff=$offtext;
  }
  if ($allowbbimagecode) {
    $imgcodeonoff=$ontext;
  } else {
    $imgcodeonoff=$offtext;
  }
  if ($allowsmilies) {
    $smiliesonoff=$ontext;
  } else {
    $smiliesonoff=$offtext;
  }
  //MaxPosts by User
  $optionArray = explode(",", $usermaxposts);
  $maxpostsoptions = '';
  while (list($key, $val) = each($optionArray))
  {
     eval ("\$maxpostsoptions .= \"".gettemplate("modifyoptions_maxposts")."\";");
  }
  $postsdefaultselected = "selected";
  // Referrer
  if ($usereferrer and $bbuserinfo['userid']==0) {
    if ($bgcolor=="{firstaltcolor}") {
      $bgcolor="{secondaltcolor}";
	  $bgclass="alt2";
    } else {
      $bgcolor="{firstaltcolor}";
	  $bgclass="alt1";
    }
    if ($bbreferrerid) {
      if ($referrername=$DB_site->query_first("SELECT username FROM user WHERE userid = '".addslashes($bbreferrerid)."'")) {
        $referrername = $referrername['username'];
      }
    }
    eval("\$referrer = \"".gettemplate("register_referrer")."\";");
  } else {
    $referrer = '';
  }
  //Birthday
  if ($calbirthday == 1) {
     if ($bgcolor=="{firstaltcolor}") {
        $bgcolor="{secondaltcolor}";
		$bgclass="alt2";
     } else {
        $bgcolor="{firstaltcolor}";
		$bgclass="alt1";
	 }
     eval("\$birthday = \"".gettemplate("register_birthday")."\";");
  } else {
    $birthday = '';
  }
  // get extra profile fields
  $profilefields=$DB_site->query("SELECT *
                                  FROM profilefield
                                  WHERE editable = 1
                                  ORDER BY displayorder");
  $customfields_required = '';
  $customfields = '';
  while ($profilefield=$DB_site->fetch_array($profilefields)) {
    $profilefieldname="field$profilefield[profilefieldid]";
    if ($profilefield[required] == 1) {
      if ($bgcolor1=="{firstaltcolor}") {
        $bgcolor1="{secondaltcolor}";
		$bgclass1="alt2";
      } else {
        $bgcolor1="{firstaltcolor}";
		$bgclass1="alt2";
      }
      $temp = $bgcolor;
	  $tempclass = $bgclass;
      $bgcolor = $bgcolor1;
	  $bgclass = $bgclass1;
      eval("\$customfields_required .= \"".gettemplate("register_customfields")."\";");
      $bgcolor = $temp;
	  $bgclass = $tempclass;
    } else { // Not Required
      if ($bgcolor=="{firstaltcolor}") {
	    $bgcolor="{secondaltcolor}";
		$bgclass="alt2";
	  } else {
	    $bgcolor="{firstaltcolor}";
		$bgclass="alt1";
	  }
      eval("\$customfields .= \"".gettemplate("register_customfields")."\";");
    }
  }

  $bgcolor = "{secondaltcolor}";
  // User selectable style sets
  $stylesetlist = "";
  if ($allowchangestyles==1) {
    $stylesets=$DB_site->query("SELECT * FROM style WHERE userselect=1");
    while($thisset=$DB_site->fetch_array($stylesets)) {
      if ($bbuserinfo[styleid]==$thisset[styleid]) {
        $selected = "selected";
      } else {
        $selected = "";
      }
      $thisid = $thisset[styleid];
      $thisstylename = $thisset[title];

      eval ("\$stylesetlist .= \"".gettemplate("modifyoptions_styleset")."\";");
      eval ("\$stylecell = \"".gettemplate("register_stylecell")."\";");
      $bgcolor = "{firstaltcolor}";
    }
  } else {
    $stylecell = "";
  }

  $avatarbits = '';
  if ($avatarenabled) {
    $avatars = $DB_site->query("SELECT avatarid, title, avatarpath
                                    FROM avatar
                                    WHERE minimumposts = 0
                                    ORDER BY title");
    if ($DB_site->num_rows($avatars) > 0) {
      while ($avatar = $DB_site->fetch_array($avatars)) {
        if (!$avatar['title']) {
		  $avatar['title'] = $avatar['avatarpath'];
		  if (strstr($avatar['title'], '/')) {
		    $avatar['title'] = substr( strrchr($avatar['title'], '/') , 1);
		  }
		  $avatar['title'] = str_replace('_', ' ', $avatar[title]);
		  $dotpos = strrpos($avatar[title], '.');
		  $avatar[title] =  substr($avatar[title], 0, $dotpos);
        }
        eval("\$avatarbits .= \"".gettemplate("register_avatarbit")."\";");
      }
      eval("\$avatarbit = \"".gettemplate("register_avatar")."\";");
    }
  } else {
    $avatarbit = '';
  }

  if ($who=="adult") {
    eval("dooutput(\"".gettemplate("registeradult")."\");");
  } else {
    eval("dooutput(\"".gettemplate("registercoppa")."\");");
  }
}

// ############################### start add member ###############################
if ($HTTP_POST_VARS['action']=="addmember") {
  if ($enablebanning and $banemail!="") {
    if (stristr(" ".$banemail." "," ".$email." ")!="") {
      eval("standarderror(\"".gettemplate("error_banemail")."\");");
      exit;
    }
    if ($emaildomain=substr(strstr($email,"@"),1)) {
      if (stristr(" ".$banemail." "," ".$emaildomain." ")!="") {
        eval("standarderror(\"".gettemplate("error_banemail")."\");");
        exit;
      }
    }
  }

  if (!$allowregistration) {
    eval("standarderror(\"".gettemplate("error_noregister")."\");");
    exit;
  }

  if ($bbuserinfo[userid]!=0 and !$allowmultiregs) {
    $getuser=$DB_site->query_first("SELECT username FROM user WHERE userid='$bbuserinfo[userid]'");
    $username=$getuser[username];

    eval("standarderror(\"".gettemplate("error_alreadyregistered")."\");");

    exit;
  }

  $username = trim($username);
  $username = eregi_replace("( ){2,}", " ", $username);
  $email = trim($email);
  $emailconfirm = trim($emailconfirm);
  $password = trim($password);
  $passwordconfirm = trim($passwordconfirm);

  // do add user

  // added by denis -- begin
  if ( preg_match( "/[\x80-\xff]/", $username ) ) {
    eval("standarderror(\"".gettemplate("error_wrongusername")."\");");
    exit();
  }
  // added by denis -- end

  if ($checkuser=$DB_site->query_first("SELECT username FROM user WHERE username='".addslashes(htmlspecialchars($username))."' OR username='".addslashes(eregi_replace("[^A-Za-z0-9]","",$username))."'")) {
    eval("standarderror(\"".gettemplate("error_usernametaken")."\");");
    exit;
  }

  if (!preg_match('/^[-!#$%&\'*+\\.\/0-9=?A-Z^_`{|}~]+@([-0-9A-Z]+\.)+([0-9A-Z]){2,4}$/i', $email)) {
    eval("standarderror(\"".gettemplate("error_bademail")."\");");
  }

  if ($requireuniqueemail and $checkuser=$DB_site->query_first("SELECT username,email FROM user WHERE email='".addslashes($email)."'")) {
    eval("standarderror(\"".gettemplate("error_emailtaken")."\");");
    exit;
  }

  if ($username=="" or $email=="" or $emailconfirm=="" or $password=="" or $passwordconfirm=="") {
    eval("standarderror(\"".gettemplate("error_fieldmissing")."\");");
    exit;
  }

  if ($password!=$passwordconfirm) {
    eval("standarderror(\"".gettemplate("error_passwordmismatch")."\");");
    exit;
  }

  if ($email!=$emailconfirm) {
    eval("standarderror(\"".gettemplate("error_emailmismatch")."\");");
    exit;
  }
  if (strlen($username)<$minuserlength) {
    eval("standarderror(\"".gettemplate("error_usernametooshort")."\");");
    exit;
  } elseif (strlen($username)>$maxuserlength) {
    eval("standarderror(\"".gettemplate("error_usernametoolong")."\");");
    exit;
  }

  // check max images
  if ($maximages!=0) {
    $parsedsig=bbcodeparse($signature,0,$allowsmilies);
    if (countchar($parsedsig,"<img")>$maximages) {
      eval("standarderror(\"".gettemplate("error_toomanyimages")."\");");
      exit;
    }
  }

  $testreferrerid['userid'] = 0;
  if ($usereferrer and $bbuserinfo['userid']==0) {
     if ($referrername) {
        if (!$testreferrerid=$DB_site->query_first("SELECT userid FROM user WHERE username = '".addslashes($referrername)."'")) {
          eval("standarderror(\"".gettemplate("error_badreferrer")."\");");
          exit;
        }
     }
  }

  if ($verifyemail) {
    $newusergroupid=3;
  } else {
    if ($moderatenewmembers or $coppauser) {
      $newusergroupid=4;
    } else {
      $newusergroupid=2;
    }
  }

  $adminemail=iif($allowmail=="yes",1,0);
  $showemail=iif($showemail=="yes",1,0);
  $invisible=iif($invisible=="yes",1,0);
  $cookieuser=iif($cookieuser=="yes",1,0);
  $nosessionhash=iif($nosessionhash=="yes",1,0);
  $emailnotification=iif($emailnotification=="yes",1,0);
  $options=iif($showsignatures=="yes",1,0);
  $options+=iif($showavatars=="yes",2,0);
  $options+=iif($showimages=="yes",4,0);
  $options+=iif($vbcode=="yes",8,0);
  $receivepm=iif($receivepm=="yes",1,0);
  $emailonpm=iif($emailonpm=="yes",1,0);
  $pmpopup=iif($pmpopup=="yes",1,0);

  $icq=intval($icq);
  if ($icq==0) {
    $icq="";
  }

  $usergroup=$DB_site->query_first("SELECT usertitle FROM usergroup WHERE usergroupid='$newusergroupid'");
  if ($usergroup[usertitle]=="") {
    $gettitle=$DB_site->query_first("SELECT title FROM usertitle WHERE minposts<=0 ORDER BY minposts DESC LIMIT 1");
    $usertitle=$gettitle[title];
  } else {
    $usertitle=$usergroup[usertitle];
  }

//  $timezoneoffset=intval($timezoneoffset); -- not needed

  // check that nothing illegal is in the username/biography/signature
  if ($username!=censortext($username)) {
    eval("standarderror(\"".gettemplate("error_censorfield")."\");");
    exit;
  }
  $signature=censortext($signature);

  if ($illegalusernames!="") {
    $usernames=explode(" ",$illegalusernames);

    while (list($key,$val)=each($usernames)) {
      if ($val!="") {
        if (strstr(strtolower($username),strtolower($val))!="") {
          eval("standarderror(\"".gettemplate("error_usernametaken")."\");");
          exit;
        }
      }
    }
  }
  // check extra profile fields
  $userfields="";
  $userfieldsnames="(userid";
  $profilefields=$DB_site->query("SELECT maxlength,profilefieldid,required,title
                                  FROM profilefield
                                  WHERE editable = 1
                                  ORDER BY displayorder");
  while ($profilefield=$DB_site->fetch_array($profilefields)) {
    $havefields = 1;
    $varname="field$profilefield[profilefieldid]";
    if ($profilefield[required] and $$varname=="") {
      eval("standarderror(\"".gettemplate("error_requiredfieldmissing")."\");");
      exit;
    }
    $$varname=censortext($$varname);
    // ENTER ANY CUSTOM FIELD VALIDATION HERE!
    //Make sure user didn't try to bypass the maxchars for the fields
    $$varname=substr($$varname,0,$profilefield[maxlength]);
    $userfieldsnames.=",field$profilefield[profilefieldid]";
    $userfields.=",'".addslashes(htmlspecialchars($$varname))."'";
  }
  $userfieldsnames.=')';

  // check coppa things
  $coppauser = intval($coppauser);
  if ($coppauser) {
    if ($parentemail=="") {
      eval("standarderror(\"".gettemplate("error_fieldmissing")."\");");
      exit;
    }

    if ($enableemail) {
      eval("\$message = \"".gettemplate("email_parentcoppa",1,0)."\";");
      eval("\$subject = \"".gettemplate("emailsubject_parentcoppa",1,0)."\";");

      mail($parentemail,$subject,$message,"From: \"$bbtitle Mailer\" <$webmasteremail>");
    }
  } else {
    $parentemail="";
    $coppauser=0;
  }

  // Birthday Stuff...
  if ($calbirthday == 1)  {
     if ( ($day == -1 and $month != -1) or ($day !=-1 and $month == -1) )   {
        eval("standarderror(\"".gettemplate("error_birthdayfield")."\");");
        exit;
     }
     if (($day == -1) and ($month==-1))  {
        $birthday = 0;
     } else {
        if (($year>1901) and ($year<date("Y")))
           $birthday = $year . "-" . $month . "-" . $day;
        else
           $birthday = "0000" . "-" . $month . "-" . $day;
        if ($showbirthdays) {
          $todayneggmt = date("n-j",time()+(-12-$timeoffset)*3600);
          $todayposgmt = date("n-j",time()+(12-$timeoffset)*3600);
          if ($todayneggmt == "$month-$day" or $todayposgmt == "$month-$day")
            getbirthdays();
        }
     }
  } else {
     $birthday = 0;
  }

  if ($allowchangestyles==1) {
    $newstylefield = "styleid,";
    $newstyleval = "'".intval($newstyleset)."',";
  } else {
    $newstylefield = "";
    $newstyleval = "";
  }

  $avatarid = 0;
  if ($avatarsel) { // Got an avatar, probably want to add an index on minimumposts!
    if ($avatarids = $DB_site->query_first("SELECT avatarid
                                           FROM avatar
                                           WHERE avatarpath = '".addslashes($avatarsel)."' AND
                                           minimumposts = 0")) {
      $avatarid = $avatarids['avatarid'];
    }
  }

  $homepage = trim($homepage);
  if ($homepage) {
    if (preg_match('#^www\.#si', $homepage)) {
      $homepage = "http://$homepage";
    } else if (!preg_match('#^[a-z0-9]+://#si', $homepage)) {
      // homepage doesn't match the http://-style format in the beginning -- possible attempted exploit
      $homepage = ''; 
    }
  }
  $DB_site->query("INSERT INTO user (userid,username,password,email,".$newstylefield."parentemail,coppauser,homepage,icq,aim,yahoo,signature,adminemail,showemail,invisible,usertitle,joindate,cookieuser,daysprune,lastvisit,lastactivity,usergroupid,timezoneoffset,emailnotification,receivepm,emailonpm,options,birthday,maxposts,startofweek,ipaddress,pmpopup,referrerid,nosessionhash,avatarid) VALUES (NULL,'".addslashes(htmlspecialchars($username))."','".addslashes(md5($password))."','".addslashes(htmlspecialchars($email))."',".$newstyleval."'".addslashes(htmlspecialchars($parentemail))."','$coppauser','".addslashes(htmlspecialchars($homepage))."','".addslashes(htmlspecialchars($icq))."','".addslashes(htmlspecialchars($aim))."','".addslashes(htmlspecialchars($yahoo))."','".addslashes($signature)."','$adminemail','$showemail','$invisible','".addslashes($usertitle)."','".time()."','$cookieuser','".addslashes($prunedays)."','".time()."','".time()."','$newusergroupid','".addslashes($timezoneoffset)."','$emailnotification','$receivepm','$emailonpm','$options','".addslashes($birthday)."','".addslashes($umaxposts)."','".addslashes($startofweek)."','".addslashes($ipaddress)."','$pmpopup','".addslashes($testreferrerid['userid'])."','$nosessionhash','$avatarid')");
  $userid=$DB_site->insert_id();

  // insert custom user fields
  $DB_site->query("INSERT INTO userfield $userfieldsnames VALUES ($userid$userfields)");

  // initialise cookies
  if ($cookieuser==1) {
    vbsetcookie("bbuserid",$userid);
    vbsetcookie("bbpassword",md5($password));
  }
  $DB_site->query("UPDATE session SET userid=$userid WHERE sessionhash='".addslashes($session['dbsessionhash'])."'");

  if ($newuseremail!="")
  {
    if ($birthday == 0) {
       $birthday = "N/A";
    } else {
       if (date("Y")>$year and $year>1901 && $year!='0000') {
          $birthday = @date($calformat1,mktime(0,0,0,$month,$day,$year));
       } else {
          $birthday = @date($calformat2,mktime(0,0,0,$month,$day,0));
       }
       if ($birthday=="") {
         $birthday = "$month-$day-$year";
       }
    }

    $customfields = '';
    if ($havefields)
    {
       $DB_site->data_seek(0,$profilefields);
       while ($profilefield=$DB_site->fetch_array($profilefields))
       {
          $varname="field$profilefield[profilefieldid]";
          $cfield = $$varname;
          $customfields .= "$profilefield[title] : $cfield\n";
       }
    }

    eval("\$message = \"".gettemplate("email_newuser",1,0)."\";");
    eval("\$subject = \"".gettemplate("emailsubject_newuser",1,0)."\";");

    mail ($newuseremail,$subject,$message,"From: \"$bbtitle Mailer\" <$webmasteremail>");
  }

  // sort out emails and usergroups
  if ($verifyemail) {
		// make random number
		mt_srand ((double) microtime() * 1000000);
    $activateid=mt_rand(0,100000000);

    //save to DB
		$DB_site->query("INSERT INTO useractivation VALUES (NULL,'$userid','".time()."','$activateid',0)");

    eval("\$message = \"".gettemplate("email_activateaccount",1,0)."\";");
    eval("\$subject = \"".gettemplate("emailsubject_activateaccount",1,0)."\";");

    mail ($email,$subject,$message,"From: \"$bbtitle Mailer\" <$webmasteremail>");

  }

  $username=htmlspecialchars($username);
  $url=urldecode($url);
  if ($coppauser) {
    $action="coppaform";
  } else {
    if ($verifyemail) {
      eval("standarderror(\"".gettemplate("error_registeremail")."\");");

    } else {
      if ($moderatenewmembers) {
        eval("standarderror(\"".gettemplate("error_moderateuser")."\");");
      } else {
        $url = str_replace("\"", "", $url);
        if (!$url) {
          $url = "index.php?s=$session[sessionhash]";
        }
if ( $_REQUEST['evaltest'] == 1 ) {
	$evalstr = "standardredirect(\"".gettemplate("redirect_registerthanks")."\",\"".iif(strpos($url,"register")>0,"index.php?s=$session[sessionhash]",$url)."\");";
	echo $evalstr;
	eval($evalstr);
} else {
        eval("standardredirect(\"".gettemplate("redirect_registerthanks")."\",\"".iif(strpos($url,"register")>0,"index.php?s=$session[sessionhash]",$url)."\");");
}
      }
    }
  }
}

// ############################### start activate form ###############################
if ($a=="ver") {
  // get username and password
  if ($bbuserinfo[userid]==0) {
    $bbuserinfo[username]="";
  }
  eval("dooutput(\"".gettemplate("activateform")."\");");
}

// ############################### start activate ###############################
if ($action=="activate") {
  if ($userinfo=$DB_site->query_first("SELECT userid FROM user WHERE username='".addslashes(htmlspecialchars($username))."'")) {

		$u=$userinfo[userid];
		$a="act";
		$i=$activateid;
  } else {
    eval("standarderror(\"".gettemplate("error_wrongusername")."\");");
  }
}
if ($a=="act") {
  // do activate account
  $u = intval($u);

  $userinfo=verifyid("user",$u,1,1);

  if ($userinfo[usergroupid]==3) {
		$user=$DB_site->query_first("SELECT activationid FROM useractivation WHERE userid='$userinfo[userid]' AND type=0");
		if ($i!=$user[activationid]) {
			// send email again
			eval("standarderror(\"".gettemplate("error_invalidactivateid")."\");");
			exit;
		}

		// delete activationid
		$DB_site->query("DELETE FROM useractivation WHERE userid='$userinfo[userid]' AND type=0");


		if ($userinfo[coppauser] or ($moderatenewmembers and !$userinfo['posts'])) {
			// put user in moderated group
			if ($userinfo['customtitle']==0) {
				$usergroup=$DB_site->query_first("SELECT usertitle FROM usergroup WHERE usergroupid=4");
				if ($usergroup[usertitle]=="") {
					$gettitle=$DB_site->query_first("SELECT title FROM usertitle WHERE minposts<=$userinfo[posts] ORDER BY minposts DESC LIMIT 1");
					$usertitle=$gettitle[title];
				} else {
					$usertitle=$usergroup[usertitle];
				}
				$dotitle=", usertitle='".addslashes($usertitle)."'";
			} else {
				$dotitle = '';
			}
			$DB_site->query("UPDATE user SET usergroupid=4$dotitle WHERE userid='$u'");
			eval("standarderror(\"".gettemplate("error_moderateuser")."\");");
		} else {
			// activate account
			if ($userinfo['customtitle']==0) {
				$usergroup=$DB_site->query_first("SELECT usertitle FROM usergroup WHERE usergroupid=2");
				if ($usergroup[usertitle]=="") {
					$gettitle=$DB_site->query_first("SELECT title FROM usertitle WHERE minposts<=$userinfo[posts] ORDER BY minposts DESC LIMIT 1");
					$usertitle=$gettitle[title];
				} else {
					$usertitle=$usergroup[usertitle];
				}
				$dotitle=", usertitle='".addslashes($usertitle)."'";
			} else {
				$dotitle = '';
			}
			$DB_site->query("UPDATE user SET usergroupid=2$dotitle WHERE userid='$u'");
			$username=$userinfo[username];
			eval("standarderror(\"".gettemplate("error_activatedthanks")."\");");
		}
  } else {
    if ($userinfo[usergroupid]==4) {
      // In Moderation Queue
      eval("standarderror(\"".gettemplate("error_activate_moderation")."\");");
      exit;
    } else {
      // Already activated
      eval("standarderror(\"".gettemplate("error_activate_wrongusergroup")."\");");
      exit;
    }
  }

}

// ############################### start request activation email ###############################
if ($action=="requestemail") {
  eval("dooutput(\"".gettemplate("activate_requestemail")."\");");
}

if ($HTTP_POST_VARS['action']=="emailcode") {
  $users=$DB_site->query("SELECT user.userid,usergroupid,username,email,password,activationid,dateline FROM user LEFT JOIN useractivation ON (user.userid=useractivation.userid AND type=0) WHERE email='".addslashes(htmlspecialchars($email))."'");

  if ($DB_site->num_rows($users)) {

    while ($user=$DB_site->fetch_array($users)) {
			if ($user[usergroupid]==3) { // only do it if the user is in the correct usergroup
				if ($user[activationid]=="") {
					// generate new one!
					// delete old activation id
					$DB_site->query("DELETE FROM useractivation WHERE userid='$user[userid]' AND type=0");

					// make random number
					mt_srand ((double) microtime() * 1000000);
					$user[activationid]=mt_rand(0,100000000);

					//save to DB
					$DB_site->query("INSERT INTO useractivation VALUES (NULL,'$user[userid]','".time()."','$user[activationid]',0)");
				}
                                         
                if($ourtimenow-$user['dateline'] <= 60) {
                    eval("standarderror(\"".gettemplate("error_emailflood")."\");");
                }
                                         
				$userid=$user[userid];
				$username=$user[username];
				$password=$user[password];
				$activateid=$user[activationid];

				eval("\$message = \"".gettemplate("email_activateaccount",1,0)."\";");
				eval("\$subject = \"".gettemplate("emailsubject_activateaccount",1,0)."\";");

				mail ($user[email],$subject,$message,"From: \"$bbtitle Mailer\" <$webmasteremail>");
			}
		}
    $url=urldecode($url);
		if ($url=="") {
			$url="index.php?s=$session[sessionhash]";
		}

    $url = str_replace("\"", "", $url);
    eval("standardredirect(\"".gettemplate("redirect_lostactivatecode")."\",\"\$url\");");
  } else {
    eval("standarderror(\"".gettemplate("error_invalidemail")."\");");
  }

}

// ############################### start coppa form ###############################
if ($action=="coppaform") {
  if ($bbuserinfo[userid]!=0) {

    $bbuserinfo[signature]=nl2br($bbuserinfo[signature]);

    if ($bbuserinfo[showemail]) {
      $bbuserinfo[showemail]="no";
    } else {
      $bbuserinfo[showemail]="yes";
    }

  } else {
    $bbuserinfo[username]="";
    $bbuserinfo[homepage]="http://";

  }

  eval("dooutput(\"".gettemplate("coppaform")."\");");

}

?>
