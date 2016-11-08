<?  
/************* PHPRecommend v 1.2 ***************/ 
/************************************************/ 
/*  written by A. Gianotto - 3/28/2000          */ 
/*  To find out if this is the latest version   */ 
/*  of this script, email snipe@snipe.net, or   */ 
/*  visit http://www.snipe.net                  */ 
/************************************************/ 
/************************************************/ 
/*  PHPRecommend may be used freely for private */ 
/*  or commercial use.  All I ask is that you   */ 
/*  don't include it in any collections without */ 
/*  my permission.                              */ 
/************************************************/ 
/************************************************/ 
/*  DESCRIPTION:                                */ 
/* PHP "Recommend this page to a friend" script */ 
/*                                              */ 
/************************************************/ 
/************************************************/
/* NEW IN VERSION 1.2 - LOGGING TO TEXT DATABASE*/
/* ADDED 7/31/00                                */
/*                                              */
/* If you wish to use this feature, the only    */
/* additional step you must take is to upload   */
/* a blank text file into the same directory as */
/* this script lives.  For our purposes, we     */
/* have named it "url-log.txt".  You may name   */
/* it whatever you like, but if you change the  */
/* name, be sure to change it in the variable   */
/* below, where it's marked                     */
/*                                              */
/* Also BE SURE TO CHMOD THE TEXT FILE TO 666   */
/* so that the sever can open the file and      */
/* write to it.                                 */
/* ENJOY!                                       */
/************************************************/ 
/************************************************/ 
/*  INSTALLATION:                               */ 
/*  There is no readme or install file with     */ 
/*  script, because it's so easy to install,    */ 
/*  you just have to follow the comments in the */ 
/*  first part of the code                      */ 
/************************************************/ 
/************************************************/ 

// make this point to your header file.  rename the file 
// and the path to point to the correct file on your server 
// If you do not use a header or footer file, simply replace  
// the header and footer includes with the proper HTML code  
// for your site 

include("d:/web/header.txt");  

// Change the variable below to "no" if you do not wish to write 
// the data to a text log
$logging="yes";

// Enter the website administrators email address here 
$adminaddress = "you@your-site.com"; 

// Enter the company name or site name here 
$sitename = "your-site.com"; 

// Enter the address of your website here 
$siteaddress ="http://www.your-site.com"; 


// Unless you are changing the verbage of the printed message, 
// there are no further configurations that need to be done  
// past this point, except editing the path to your footer includes 

// If you do not use header and footer files, you can just replace 
// the include calls with your HTML, but be sure to close the PHP tags 
// before and after the HTML, so you don't get parse errors. 


IF ($action==""): 
?> 

<H3>Recommend this page to a friend!</H3> 
<? 
$referer = getenv("HTTP_REFERER");  
print "<B>$referer</B><BR><BR>"; 

?> 
To send the URL of this page and a brief message to a friend who might like it, just fill out the  
form below. 
<BR> 
<BR><B>NOTE:</B>  We only request your name and email address so that the person you  
are recommending the page to knows that you wanted them to see it, and that it is not junk  
mail. 
<BR><FORM METHOD=POST ACTION="<? echo "$PHP_SELF"; ?>"> 

<TABLE BORDER="0" CELLSPACING="3" CELLPADDING="3"> 
<TR> 
    <TD VALIGN="TOP"><FONT FACE="Arial, Helvetica, Verdana, Sans Serif" SIZE="- 
1">Your Name:</FONT></TD> 
    <TD VALIGN="TOP"><FONT FACE="Arial, Helvetica, Verdana, Sans Serif"  
SIZE="3"><INPUT TYPE="text" NAME="yname"></FONT></TD> 
</TR> 
<TR> 
    <TD VALIGN="TOP"><FONT FACE="Arial, Helvetica, Verdana, Sans Serif" SIZE="- 
1">Your Email:</FONT></TD> 
    <TD VALIGN="TOP"><FONT FACE="Arial, Helvetica, Verdana, Sans Serif"  
SIZE="3"><INPUT TYPE="text" NAME="yemail"></FONT></TD> 
</TR> 
<TR> 
    <TD VALIGN="TOP"><FONT FACE="Arial, Helvetica, Verdana, Sans Serif" SIZE="- 
1">Friend's Name:</FONT></TD> 
    <TD VALIGN="TOP"><FONT FACE="Arial, Helvetica, Verdana, Sans Serif"  
SIZE="3"><INPUT TYPE="text" NAME="fname"></FONT></TD> 
</TR> 
<TR> 
    <TD VALIGN="TOP"><FONT FACE="Arial, Helvetica, Verdana, Sans Serif" SIZE="- 
1">Friend's Email:</FONT></TD> 
    <TD VALIGN="TOP"><FONT FACE="Arial, Helvetica, Verdana, Sans Serif"  
SIZE="3"><INPUT TYPE="text" NAME="femail"></FONT></TD> 
</TR> 
<TR> 
    <TD VALIGN="TOP" COLSPAN="2"><FONT FACE="Arial, Helvetica, Verdana, Sans  
Serif" SIZE="-1">Additional Comments:</FONT></TD> 
</TR> 
<TR> 
    <TD VALIGN="TOP" COLSPAN="2"><FONT FACE="Arial, Helvetica, Verdana, Sans  
Serif" SIZE="3"><TEXTAREA NAME="comments" ROWS="3"  
COLS="30"></TEXTAREA></FONT></TD> 
</TR> 
<TR> 
    <TD VALIGN="TOP" COLSPAN="2"><FONT FACE="Arial, Helvetica, Verdana, Sans  
Serif" SIZE="3"><INPUT TYPE="hidden" NAME="url" VALUE="<? echo "$referer"; ?>"><INPUT  
TYPE="submit" NAME="action" VALUE="Send"></FONT></TD> 
</TR> 
</TABLE> 

</FORM> 
    <BR> 
    <BR> 
    <BR> 
    <BR> 
    <BR> 


<? 

// don't forget to change this path if you're using a footer file!
include("d:/web/footer.txt"); 


exit; 
ELSEIF ($action="Send"): 
mail("$femail","$yname went to $sitename and recommended you check this out","$yname  
stopped by $sitename and thought you would find the following URL of interest: 

URL: $url 

Additional Comments: 
------------------------------------ 
$comments 
------------------------------------ 

Thank you! 

$sitename 
$adminaddress 
$siteaddress","FROM:$yemail"); 
?> 
<H3>Thank you!</H3> 
Your recommendation has been sent!   
    <BR> 
    <BR> 
    <FONT SIZE="3"><B><A HREF="<? echo "$url"; ?>">GO BACK</A></B></FONT> 
    <BR> 
    <BR> 
<? 
// NEW IN VERSION 1.2 - LOGGING TO A TEXT FILE - 7/31/00
// this writes the url, comments, and email address
// to a text database so you can easily track which 
// pages are being recommended most often

// Be sure to upload the blank text file that the 
// data will write to.  In this case, we named it url-log.txt
// If you name your blank file url-log.txt, no changes are needed here.
// If you decide to use a different filename, you must change the 
// variable below.
	
	IF ($logging=="yes"):
	$fname="url-log.txt"; 

	// Nothing needs ot be changed here

	$fl=fopen($fname,"a+"); 
	// sets the pipe delimiters
	fwrite($fl,"'$url'|'$femail'|'$yemail'|'$comments'\n"); 
	fclose($fl); 
	ENDIF;

ENDIF; 

	// change this pathname to point to your footer file
	include("d:/web/footer.txt"); 



// end of script 

// ********************************************* 
// Making it work on your page: 
// Just put a link to phprecommend.php3 (or whatever you name 
// the above file), and the script  
// does the rest! 

?> 

