<?

//...........Last X Posts v1.0.2...........\\
//......by Kevin (kevin@tubescan.com)......\\

// For vBulletin version 2 (betas 3, 4, 5, RC1, RC2)
// (c) 2001 Jelsoft Enterprises, Ltd.

// vBulletin.com thread: http://www.vbulletin.com/forum/showthread.php?threadid=12324

//////// CONFIG EXPLANATION //////////
//
//	a] $path > path to your config.php file (usually in the /admin directory) - NO TRAILING SLASH! ( e.g. forums/admin ) and DO NOT PUT config.php ON THE END OF THIS PATH OR THE SCRIPT WILL NOT WORK!!!!
//	b] $url > URL to your board - NO TRAILING SLASH! ( e.g. http://www.mysite.com/forums )
//  c] $urlimg > URL to your board's images - NO TRAILING SLASH ( e.g. http://www.mysite.com/forums/images )
//	d] $maxthreads > max threads to show. will show less if $last24 or $last7 limits it to less results than this number
//	e] $ob > determines the sort order of the list. replycount and views are numbers, lastposter is a name, title is the name of the thread, and lastpost is the last             posts' date. set to one of the following: replycount , views , lastposter , title , lastpost (lastpost is most popular. it's the thread most recently replied              to, then the second-to-last most recent, etc.)
//	f] $obdir > set to "desc" or "asc". which direction to sort? "desc" goes from bottom to top (9 to 1, z to a, etc.). "asc" goes top to bottom (1 to 9, a to z, etc.) leave this set to "desc" if you use lastpost for $ob or it will not work correctly!
//	g] $last24 > set to 1 to limit the possible results to the last 24 hours; 0 for no limit (must set this to 0 if $last7 is set to 1)
//	h] $last7 > set to 1 to limit the possible results to the last 24 hours; 0 for no limit (must set this to 0 if $last24 is set to 1)
//	i] $bc1 > first alt color (for the alternating colored rows)
//	j] $bc2 > second alt color
//	k] $hc > head background color (title, last poster, etc.)
//	l] $lc > text link color
//	m] $tc > text color
//	n] $f > font face
//	o] $fs > font size in points - 8 is normal, 6 is on the small side, 10 on the large side. play around with it. :) just put a number here - no pt, pts, or anything!
//	p] $lastposter > show the "last poster" column? 1 = yes; 0 = no
//	q] $views > show the view count for each thread? 1 = yes; 0 = no
//	r] $replies > show the reply count for each thread? 1 = yes; 0 = no
//  s] $lastpostdate > show the last post date and time for each thread? 1 = yes; 0 = no
//	t] $len > maximum number of characters of the title to show. e.g. if the title is 60 characters and this is set to 25, only the first 25 characters of the title              will be shown (followed by ...)
//  u] $excludeforums > if you want to exclude certain forums from having their threads displayed, this is the place to enter their numbers. separate more than 1 number with commas, NO SPACES! e.g. 1,2,3,4 (note: $excludeforums and $includeforums are mutually exclusive, meaning DO NOT USE BOTH AT THE SAME TIME! only fill one or the other in with numbers!)
//  v] $includeforums > if you just want to use certain forums (instead of a whole list, or instead of excluding 10 of 12 forums or something similar), put their numbers here. separate more than one number with commas NO SPACES e.g. 1,2,3,4 (note: $excludeforums and $includeforums are mutually exclusive, meaning DO NOT USE BOTH AT THE SAME TIME! only fill one or the other in with numbers!)
//  w] $showmessages > if you want to show the text of the last post in each thread as well, set this to "1". set this to "0" if you don't.
//  x] $lplen > the maximum length of post to allow. if the post is longer than this, it will be shortened to this many characters and "..." added. if $showmessages is set to 0, this won't do anything.
//  y] $tw > the width of the table holding the information. can be a percent ( e.g. 95% ) or a number of pixels ( e.g. 300 ). leave blank if you want the table to be sized naturally.
//  z] $showdate > if you enable the "last post date" column and would like the date shown for each post as well as the time, set this to 1. if you have a busy board and all of posts are going to be from the current day, or if you set $last24 to 1, then you can set this to 0. if the last posts are likely to be spread over multiple days (for small boards, etc.) then you might want to set this to 1.
// aa] $cs > if you want to show a thin line around the cells (see the first example on the vBulletin.com thread referenced above) then set this to "1" (or higher - experiment with it!) otherwise set it to 0.
// ab] $showicon > if you want to show the icon the author chose for their post, set this to 1. otherwise set it to 0.
// ac] $showforumtitle > if you want to show the forum title, linked to that forum, for each thread also (forum title: thread title) then set this to 1. otherwise set it to 0.
// ad] $nb > if you want breaks in text to appear as such, set this to 1. otherwise set it to 0 (this may cause problems if there are large breaks in the text)
//
////// END CONFIG EXPLANATION ////////

/////////////// CONFIG ///////////////
// 
$path = "admin"; // path to your config.php file (usually in the /admin directory) - NO TRAILING SLASH! Do not include "config.php"
$url = "http://www.pixies-place.com/forums"; // URL to your board - NO TRAILING SLASH!
$urlimg = "http://www.pixies-place.com/forums/images"; // URL to your board's images - NO TRAILING SLASH!
$maxthreads = "25"; // max threads to show. will show less if $last24 or $last7 limits it to less results than this number
$ob = "lastpost"; // set to one of the following: replycount , views , lastposter , title , lastpost (lastpost is most popular. it's the thread most recently replied to, then the second-to-last most recent, etc.)
$obdir = "desc"; // which direction to sort? "desc" goes from bottom to top (9 to 1, z to a, etc.). "asc" goes top to bottom (1 to 9, a to z, etc.). if you use lastpost for $ob, leave this set to desc or it will not work correctly!
$last24 = "0"; // 1 = last 24 hours; 0 = all (must set this to 0 if $last7 is set to 1)
$last7 = "0"; // 1 = last 7 days; 0 = all (must set this to 0 if $last24 is set to 1)
$bc1 = "#f1f1f1"; // first alt color
$bc2 = "#ffffff"; // second alt color
$hc = "#ffffff"; // head background color
$lc = "maroon"; // link color
$tc = "#000000"; // text color
$f = "Verdana"; // font face
$fs = "8"; // font size in points - 8 is normal, 6 is on the small side, 10 on the large side. play around with it. :)
$lastposter = "1"; // show last poster? 1 = yes; 0 = no
$views = "0"; // show view count? 1 = yes; 0 = no
$replies = "0"; // show reply count? 1 = yes; 0 = no
$lastpostdate = "0"; // show last post date and time? 1 = yes; 0 = no
$len = 45; // maximum number of characters of the title to show. e.g. if the title is 60 characters and this is set to 25, only the first 25 characters of the title will be shown (followed by ...)
$excludeforums = ""; // if you want to exclude a forum, put it's ID here. more than one, seperate them with commas, NO SPACES! e.g. 1,2,3,4
$includeforums = "38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53"; // if you only want to include certain forums, put their ids here. separate more than one with commas, NO SPACES! e.g. 1,2,3,4
$showmessages = "0"; // show the text of the last post too? 1 = yes; 0 = no
$lplen = "300"; // character length of last post text (if $showmessages is set to 0 this won't do anything).
$tw = "100%"; // width of the table that shows the info, in either a percent ( e.g. 95% ) or in pixels ( e.g. 300 ). leave blank if you want the table to be sized naturally
$showdate = "0"; // show the date, as well as the time? if the posts that show up in the list are likely to all be from today (or you set $last24 to "1"), you can set this to 0. if the posts are spread over multiple days, you probably want this set to 1.
$cs = "0"; // this is the cellspacing. 1 makes a thin line around the cells. 0 makes no line.
$showicon = "0"; // shows the posts' icon next to the post
$showforumtitle = "0"; // shows the forum title (linked to that forum) next to the thread title
$nb = "0"; // do you want breaks in text to appear as such? this may cause problems if there are large breaks in the text
//
///////////// END CONFIG /////////////

?>