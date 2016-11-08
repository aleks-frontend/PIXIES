/*
 *****************************************************************
 *			       	phpLedAds 2.0
 *
 * This program is distributed as freeware. We are not
 * responsible for any damages that the program causes	
 * to your system. It may be used and modified free of 
 * charge, as long as the copyright notice
 * in the program that give me credit remain intact.
 * If you find any bugs in this program. Please feel free 
 * to report it to bugs@ledscripts.com.
 * If you have any troubles installing this program. Please feel
 * free to post a message on our Support Forum.
 * Selling this script is absolutely forbidden and illegal.
 *
 *****************************************************************
 *
 *	               COPYRIGHT NOTICE:
 *	
 *	         Copyright 2002 Jon Coulter
 *	
 *	      Author:  Jon Coulter
 *	      Web Site: http://www.ledscripts.com
 *	      E-Mail: ledjon@ledscripts.com
 *	      Support: http://www.ledscripts.com/
 *			(or support@ledscripts.com)
 *
 *       This program is protected by the U.S. Copyright Law
 *****************************************************************
*/

Requirements:
	PHP 4.0 or greater
	Mysql (3.22+)
	
	* If you want to be able to create graphs:
	GD (Graphics Library)


INSTALLATION
============================
Here's the quick and dirty:
	- Hardest part (and its pretty easy!): Configuring it!
		- Open config.inc.php and edit the value in the $placonfig array
		- Fill in the correct values for these fields:
		db_host	=> 'localhost', // the host of your mysql database
		db_user	=> 'pla', // the username to login to mysql with
		db_pass	=> 'pla', // the password to login to mysql with
		db_db	=> 'pla', // the database name itself
		
		- Then edit this field:
		tbl_prefix => 'pla',
		  // set it equal to the prefix you want before tables
		  // (this allows you to install multiple copied of the program by changing
		  //  this prefix for each 'copy')
		
		- Then set the login username and password
		- Note that this is the login to the admin.php script, nothing else
		user	=> 'admin',
		pass	=> 'admin',
		// defaults shown
		
		- The other fields in the config are documented in the
		  source file. Feel free to mess around with stuff
	
	- Upload all files (including directories) into a sub-directory of your
		main site.
		
	- chmod steps:
		1) chmod data/ directory to 777
		
	     Here's a bash one-liner you can use if you've got shell access:
		
		chmod -R 777 data/

	- Go to install.php on your site:
		http://www.yoursite.com/ledads/install.php
		
		Follow the on-screen instructions (basically just click the link(s))
	
	- Login to admin.php (default username/password: admin/admin)
		
	- Start adding ads!
	

See below for style settings information

USAGE
============================
	To include the adcode itself:
	(this assumes your files are in www.yoursite.com/ledads/)
	- For html pages, put <!--#include virtual="/ledads/ledad.php"--> where you want
		the ad to show
	- For php pages, put
		<?
			require('path/to/ledads/ad_class.php');
			echo $pla_class->adcode( );
		?>
	where you want the ad to show up
	
	- If you want to include the ad in a page remotely (or on the same server, doesn't matter),
		then use the ledad_js.php url as a <script src=..> file.
		The included jsexample.html is an example of this.
	- See other examples in the examples/ folder

Note:
	For those of you that are *nix guru's and decided to download this package
	straight to your server non-windows server (meaning you didn't upload
	all files in ascii mode), some of the cgi files might have
	\r's (carriage returns) (looks like ^M in vi or pico). You can fix this up by running this
	one simple command in the same directory as the LedNews cgi files:
		
		perl -pi -e 's/\r//g' *.cgi

Please e-mail any bugs you find to bugs@ledscripts.com with the details
(such as program name and what you did) so that it can be fixed ASAP.