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

	// do some checks to make sure everything is ok
	if(floor(phpversion()) < 4) {
		die("phpLedAds v2.0 Requires PHP 4 (or higher)! (Your current version is: " . phpversion() .")");
	}

	/*
		Config File for phpLedAds v2.0
	*/

	$placonfig	= array(
		// db settings
		db_host		=> 'lit6.xcite.net',
		db_user		=> 'thedata',
		db_pass		=> 'mollie666',
		db_db		=> 'banners',

		// table prefix (pla_<tablename> by default)
		tbl_prefix	=> 'pla',

		// set user name and password to login with
		user		=> 'admin',
		pass		=> 'nofd',

		// set this to false to disable the graph stuff
		do_graphs	=> true,

		// set this equal to a mysql connection
		// handle if you want
		// for advanced users only!
		// Example where $conn is a connection handle
		// from mysql_connect() (or mysql_pconnect()):
		// conn_handle => &$conn,
		conn_handle	=> null,
		// you must also mysql_select_db() on the handle!

		// web path
		// this script attempts to configure out the web path
		// automagically. If its doing it wrong (usually noticable on the 'click.php' links
		// then set this to the *directory* relative to the root of the webpage
		// example: http://mysite.com/ledads is the full web path
		// so set this like so:
		// web_path => '/ledads',
		// or if you wanted to use the full path
		// web_path => 'http://mysite.com/ledads',
		web_path	=> null,

		// set this equal to true to use fill path linking
		// you can set this equal to true and set the above
		// (web_path) to the full url of the directory these files
		// reside in if things don't seem to be linking correctly
		usefullpath	=> true,

		// other stuff (no need to edit)
		'authname'	=> 'LedAds',
		'datadir'	=> _extras::catfile(array(getcwd(), 'data')),
		'libdir'	=> _extras::catfile(array(getcwd(), 'lib'))
	);

	// stop editing!
	if(defined('LEDPHPADS_CONFIG')) {
		return;
	} else {
		define('LEDPHPADS_CONFIG', 1);
	}
	
	// pay no attention to this line
	$pla = new phpLedAds( &$placonfig ); // core stuff
	
	// dir path
	if(!is_dir($pla->config('datadir'))) {
		if(!$pla->mkpath($pla->config('datadir'))) {
			die("Unable to create data dir: " . $pla->config('datadir'));
		}
	}
	
// put most of the stuff in this so that
// it wont interfere with other udf's
class phpLedAds // $pla == phpLedAds
{
	var $version = '2.0';
	var $_config;
	var $errstr;
	var $errno;
	var $_errno;
	var $users;
	var $me;
	var $authed;
	
	function phpLedAds( &$placonfig ) {
		global $PHP_SELF;
		
		$this->_config = &$placonfig;
		
		$this->_errno = array(
								'badmkdir'		=> ++$i
								);
								
		$this->me = $PHP_SELF;
		$this->path = $this->_find_path();
		$this->webpath = 'http://' . getenv('HTTP_HOST') . $this->path . '/';
		$this->setup_tables();
		
		if(is_resource($this->_config['conn_handle'])) {
			$this->conn = &$this->_config['conn_handle'];
		}
		
		if(!is_resource($this->conn)) {
			$this->db_connect( );
		}
	}
	
	function do_require( $class ) {
		return require(
			_extras::catfile(array($this->config('libdir'), 'class.' . $class . '.php'))
		);
	}
	
	function db_connect( ) {
		$this->conn = mysql_connect(
							$this->config('db_host'),
							$this->config('db_user'),
							$this->config('db_pass')
						) or $this->croak("Unable to connect to database: " . mysql_error());
						
		mysql_select_db($this->config('db_db'), $this->conn);
	}
	
	// this is web path, not full path!
	function _find_path( ) {
		if($this->config('web_path')) {
			return $this->config('web_path');
		}
	
		//$path = dirname($this->me);
		// we can't use the above incase this is included (so PHP_SELF wouldn't be correct)
		$path = str_replace(
					_extras::catfile(getenv('DOCUMENT_ROOT')), '', _extras::catfile(dirname(__FILE__)));
		
		return str_replace('\\', '/', $path);
	}
	
	function setup_tables( ) {
		$prefix = $this->config('tbl_prefix');
		if(empty($prefix)) {
			$prefix = 'pla';
		}
		$prefix = preg_replace('/[^a-z0-9_]/i', '', $prefix);
		
		foreach(
				explode(',', 'ads,impressions,richtext,images')
					as $table) {
			$this->tables[$table] = $prefix .'_' . $table;
		}
	}
	
	function config( $key, $val = null ) {
		if(isset($val)) {
			$this->_config[$key] = $val;
		}
		
		return $this->_config[$key];
	}
	
	function softerror( $msg = 'Unknown Error', $number = 0 ) {
		$this->errstr = $msg;
		$this->errno = $number;
		
		return false;
	}
	
	function croak( $msg, $number = 0 ) {
		$this->softerror($msg, $number);
		
		echo '<h3>Error</h3>';
		echo( ($this->errno ? '['.$this->errno.'] ' : null) . $this->errstr );
		
		echo $this->bottom();
		
		exit;
	}

	function do_auth($user, $pass) {
		if(!isset($user)) {
			header("WWW-Authenticate: Basic realm=\"".$this->config('authname')."\"");
			header("HTTP/1.0 401 Unauthorized");
			
			echo "Bad Username/Password.\n";
			exit;
		} else {
			if(!($user == $this->config('user') and $pass == $this->config('pass'))) {
				 Header("WWW-Authenticate: Basic realm=\"".$this->config('authname')."\"");
				 Header("HTTP/1.0 401 Unauthorized");
				 echo "Bad Username/Password.";
				 exit;
			}
		}
		
		return $this->authed = true;
	}

	function top( ) {
		static $top;
		
		if($top++ || !$this->authed) return;
		
		return implode("\n", file($this->checkfile('./top.html')));
	}
	
	function bottom( ) {
		static $bottom;
		
		if($bottom++ || !$this->authed) return;
		
		return implode("\n", file($this->checkfile('./bottom.html')));
	}
	
	/*
	function getdatafile( $file, $ser = true ) {
		$file = $this->checkfile($file);
		
		if(!($data = @file($file))) {
			return $this->softerror('Unable to fetch '. $file . '.');
		} else {
			$data = $this->_untaint($data);
			
			if($ser) {
				return unserialize(implode("\n", $data));
			} else {
				return implode("\n", $data);
			}
		}
	}
	
	function putdatafile( $file, $data, $ser = true ) {
		$file .= '.php';
		
		if(!($fp = fopen($file, 'wb'))) {
			return $this->softerror('Unable to open ' . $file . ' for writing.');
		}
		
		if($ser) {
			$data = serialize($data);
		}
		
		fwrite($fp, implode("\n", array('<? die("!"); ?>', $data)));
		fclose($fp);
	}
	*/
	
	function mkpath( $path, $perm = '0700' ) {
		foreach(preg_split("|[/\\\\]|", $path) as $key => $val) {
			$tot = _extras::catfile(array($tot, $val));
			
			if(!is_dir($tot)) {
				@mkdir($tot, $perm) or $this->softerror('Unable to make ' . $tot, $this->_errno['badmkdir']);
			}
		}
		
		if($this->errno == $this->_errno['badmkdir']) {
			return false;
		} else {
			return true;
		}
	}
	
	// check the path of a file
	// attempts to make it absolute if its not already
	function checkfile( $file ) {
		if(preg_match("|^\\.?[/\\\\]|", trim($file))) {
			return trim($file);
		} else {
			// assume the data dir
			return _extras::catfile(array($this->config('datadir'), _extras::catfile($file)));
			// notice the 2nd call to catfile -- quit cool, eh?
			// nice for writing something as if it's for one os, and have it
			// automatically convert to the current
		}
	}

	/*	
	function _untaint( $data ) {
		if(is_array($data)) {
			// take off the top line
			array_shift($data);
		}
		
		return $data;
	}
	*/
}

// trying to preserve namespace
class _extras
{
	function addslashes_array ( $array ) {
		while(list($key, $val) = each($array)) {
			if(is_string($array[$key])) {
				$array[$key] = trim(addslashes($array[$key]));
			} else {
				if(is_array($array[$key])) {
					$array[$key] = addslashes_array($array[$key]);
				}
			}
		}

		return $array;
	}

	// get file paths based on our os (and fix paths to match it)
	function catfile( $paths ) {
		static $env;

		if(!isset($env)) {
			$env = preg_match("/^Windows/i", getenv('OS')) ? 'Win32' : getenv('OS');
		}

		if(is_array($paths)) {
			foreach($paths as $key => $val) {
				if(empty($val)) {
					continue;
				} else {
					$use[] = $val;
				}
			}

			$return = implode(($env == 'Win32' ? '\\' : '/'), $use);
		} else {
			$return = str_replace(($env == 'Win32' ? '/' : '\\'), ($env == 'Win32' ? '\\' : '/'), $paths);
		}

		return $return;
	}
	
	function make_seed() {
		list($usec, $sec) = explode(' ', microtime());
		return (float) $sec + ((float) $usec * 100000);
	}
	
	function handle_upload( $field, $path ) {
		global $HTTP_POST_FILES;
		
		$name = $HTTP_POST_FILES[$field]['name'];
		$type = $HTTP_POST_FILES[$field]['type'];
		$tmpname = $HTTP_POST_FILES[$field]['tmp_name'];

		// move the file
		$tmp_file = _extras::catfile(array($path, md5(time())));
		
		if(!is_uploaded_file($tmpname)) {
			return false;
		}
		move_uploaded_file($tmpname, $tmp_file);
		$data = fread(fopen($tmp_file, 'rb'), filesize($tmp_file));
		@unlink($tmp_file);
		
		// add step to fix redhat's release of 4.0.4pl1
		
		$file = _extras::catfile(array($path, $name));
		if(file_exists($file)) {
			preg_match('/\.([^\.]+)$/', $name, $match);
			$name = time( );
			
			while( file_exists( _extras::catfile(array($path, $name . '.' . $match[1])) ) ) {
					$name++;
			}
			$name = $name . '.' . $match[1];
			$file = _extras::catfile(array($path, $name));
		}
		
		if(!($fp = @fopen($file, 'wb'))) {
			return false;
		}
		fwrite($fp, $data);
		@fclose($fp);
		
		return $name;
	}
	
	function last_insert_id( $conn ) {
		$result = mysql_query("select last_insert_id() as return", $conn);
		return mysql_result($result, 0);
	}
	
	function fix_slashes( ) {
		global $HTTP_POST_VARS, $HTTP_GET_VARS;
		
		// fix quotes, if needed
		if(!get_magic_quotes_gpc()) {
			// add slashes
			$HTTP_POST_VARS = _extras::addslashes_array($HTTP_POST_VARS);
				reset($HTTP_POST_VARS);
			$HTTP_GET_VARS = _extras::addslashes_array($HTTP_GET_VARS);
				reset($HTTP_GET_VARS);
		}
	}
}

?>
