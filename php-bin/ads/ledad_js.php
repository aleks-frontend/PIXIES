<?php
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

	// imports everything we need
	$dir = dirname(__FILE__);
	if(empty($dir)) {
		$dir = getcwd( );
	}
	if(empty($dir)) {
		$dir = '.';
	}
	require_once($dir . '/ad_class.php');	
	
	$adcode = str_replace(array("\n", "\r"), array('\n', ''), $pla_class->adcode( ));
	$adcode = str_replace(array("'", '"'), array("\\'", '\\"'), $adcode);
?>
<!--
	document.write('<?=$adcode?>');
//-->