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


if(defined('LEDPHPADS_OUTPUT')) {
	return;
} else {
	define('LEDPHPADS_OUTPUT', 1);
}

require_once(dirname(__FILE__) . '/config.inc.php');
$pla_class = new phpLedAdsOutput( &$pla );

srand( _extras::make_seed() );

class phpLedAdsOutput
{
	function phpLedAdsOutput( &$pla ) {
		$this->core = &$pla;
		$this->conn = &$this->core->conn;
	}
	
	function random_key( $wanttype = false ) {
		static $total;
		
		/*
			Note:
				We could have used mysql's rand() function
				to save 1 query here, but that requires mysql version 3.23 or above...
				some people are still using 3.22 or less.
		*/
		
		// cache our count of all ads
		if(!isset($total)) {
			$sql = "select count(*) as total from "
					. $this->core->tables['ads'];

			$result = mysql_query($sql, $this->conn)
							or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
			$row = mysql_fetch_object($result);
			
			$total = $row->total;
			
			mysql_free_result($result);
			
			if($total == 0) {
				$this->core->croak("No ads found in database!");
			}
		}
		
		$rand = rand(1, $total);
		
		// use mysql's limit atribute to get the random ad key
		$sql = "select aid, type, did from "
				. $this->core->tables['ads']
				. " limit " . ($rand - 1) .", "
				. $total;
		
		$result = mysql_query($sql, $this->conn)
						or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
		$row = mysql_fetch_object($result);
		
		return ($wanttype) ? array($row->aid, $row->type, $row->did) : $row->aid;
	}
	
	function get_type( $key ) {
		static $cache;

		$key = intval($key);
		
		// cache it!
		if(isset($cache[$key])) {
			return $cache[$key];
		}
		
		$sql = "select type, did from "
				. $this->core->tables['ads']
				. " where (aid = $key)";
				
		$result = mysql_query($sql, $this->conn)
						or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
		$row = mysql_fetch_object($result);
		
		return $cache[$key] = array($row->type, intval($row->did));
	}
	
	function adcode( $key = 0 ) {
		if($key == 0) {
			list($key, $type, $did) = $this->random_key( true );
		}
		
		if(!$key) {
			$this->core->croak("Unable to get a valid key ($key)");
		}
		
		if(!$type || !$did) {
			list($type, $did) = $this->get_type( $key ) or $this->core->croak("Unable to figure out the type of ($key)");
		}
		
		$this->update_impression( $key );
		if($type == 'image') {
			return $this->_image_code( $key );
		} else {
			return $this->_rich_code( $key );
		}
	}
	
	function update_impression( $key ) {
		$key = intval($key);
		$sql = "update "
				. $this->core->tables['impressions']
				. " set displays = displays + 1"
				. " where (impdate = now()) and (aid = $key)";
				
		mysql_query($sql, $this->conn)
					or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
		if(mysql_affected_rows($this->conn) <= 0) {
			$sql = "replace into "
					. $this->core->tables['impressions']
					. " (aid, impdate, displays, clicks)"
					. " values ($key, now(), 1, 0)";
			mysql_query($sql, $this->conn) or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
		}
		
		return true;
	}
	
	function update_click( $key ) {
		$key = intval($key);
		$sql = "update "
				. $this->core->tables['impressions']
				. " set clicks = clicks + 1"
				. " where (impdate = now()) and (aid = $key)";
				
		mysql_query($sql, $this->conn)
					or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
		if(mysql_affected_rows($this->conn) <= 0) {
			$sql = "replace into "
					. $this->core->tables['impressions']
					. " (aid, impdate, displays, clicks)"
					. " values ($key, now(), 0, 1)";
			mysql_query($sql, $this->conn) or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
		}
		
		return true;
	}
	
	function redirect( $key ) {
		$key = intval($key);
		
		list($type, $did) = $this->get_type($key);
		
		$sql = "select url from "
				. $this->core->tables['images']
				. " where (did = $did)";
				
		$result = mysql_query($sql, $this->conn) or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
		$row = mysql_fetch_object($result);
		
		header("Location: " . $row->url);
		exit;
	}
	
	function _image_code( $key ) {
		$aid = intval($key);
		list($type, $key) = $this->get_type($aid);
		$sql = "select * from "
				. $this->core->tables['images']
				. " where (did = $key)";
				
		$result = mysql_query($sql, $this->conn)
						or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
		
		if(mysql_num_rows($result) <= 0) {
			$this->core->croak("No rows returned for image (from images table)!");
		} else {
			$row = mysql_fetch_object($result);
			
			$path = $this->core->config('usefullpath') ?
						$this->core->webpath :
						$this->core->path .'/';
			
			$click = $path . 'click.php';
			return $this->_head( ) .
					'<a href="' . $click . '?key=' . $aid . '" target="' . $row->target .'">'
					. '<img src="' 
					. $row->image_url . '"'
					. ' width="' . $row->width . '"'
					. ' height="' . $row->height . '"'
					. ' alt="' . htmlentities($row->alt_text) . '"'
					. ' border="0"></a>'
					. $this->_foot( );
		}
	}
	
	function _rich_code( $key ) {
		list($type, $key) = $this->get_type(intval($key));
		$sql = "select * from "
				. $this->core->tables['richtext']
				. " where (did = " . $key  . ")";
		
		$result = mysql_query($sql, $this->conn)
						or $this->core->croak("Mysql Error: ($sql) " . mysql_error());
						
		if(mysql_num_rows($result) <= 0) {
			$this->core->croak("No Rows returend for rich text ad (from rich text table)! (ID: $key)");
		} else {
			$row = mysql_fetch_object($result);
			
			return $this->_head( ) .
					str_replace('[random]',
						preg_replace('/[^0-9]/', '', uniqid(rand(1, (getrandmax() - 100)))), $row->data)
					. $this->_foot( );
		}
	}
	
	function _head( ) {
		return '<!-- Generated by LedAds V2.0 -- http://www.ledscripts.com -->';
	}
	
	function _foot( ) {
		return $this->_head( );
	}
}

?>