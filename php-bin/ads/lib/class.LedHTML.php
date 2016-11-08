<?php

// all the PEAR classes suck for real html, so i made my own here
// this took me all of 15 minutes to write, so no big deal.
class LedHTML
{
	var $_config = array();
	var $_param = array();
	var $_usespaces;
	
	function LedHTML ( $args = null ) {
		global $HTTP_GET_VARS, $HTTP_POST_VARS;
		
		$this->_config = array(
								face		=> "Verdana, Arial, Helvetica, sans-serif",
								size		=> '2',
								width		=> '95%',
								border		=> 1,
								cellspacing	=> 0,
								cellpadding	=> 1
							);
		
		if(is_array($args)) {
			foreach($args as $key => $val) {
				$this->_config[strtolower($key)] = $val;
			}
		}
		
		$this->_usespaces = true;
		
		// cool stuff like CGI.pm does
		foreach($HTTP_GET_VARS as $key => $val) {
			$this->_param[$key] = $val;
		}
		
		foreach($HTTP_POST_VARS as $key => $val) {
			$this->_param[$key] = $val;
		}
	}
	
	function param( $key = null, $val = null ) {
		if(isset($val) && isset($key)) {
			$this->_param[$key] = $val;
		}
	
		return isset($key) ? $this->_param[$key] : $this->_param; // array of params
	}
	
	function usepaces( $val = true ) {
		$this->_usespaces = $val;
	}
	
	function pack( $text = null ) {
		if(!$this->_usespaces) {
			return;
		} else {
			return $text;
		}
	}
	
	function b ( $text ) {
		return '<b>' . $text . '</b>';
	}
	
	function center ( $text ) {
		return "<center>" . $text . "</center>";
	}
	
	function ahref ( $link, $text, $args = null) {
		$return = "<a href=\"$link\"";
		$return = $this->addargs($return, $args);
		$return .= '>' . $text . '</a>';
		
		return $return;
	}
	
	function imgsrc( $img, $args = null ) {
		$return = "<img src=\"$img\"";
		return $this->addargs($return, $args) . '>';
	}
	
	function table ( $tbl, $args = null ) {
		foreach(array(width, border, bordercolor, cellspacing, cellpadding) as $key => $val) {
			$args[$val] = isset($args[$val]) ? $args[$val] : $this->_config[$val];
		}
		
		$return = "<table";		
		$return = $this->addargs($return, $args);
		$return .= ">" .$this->pack("\n") . $tbl . "</table>" . $this->pack("\n");
		
		return $return;
	}
	
	function tr ( $text, $args = null ) {
		$return = $this->pack("\t") . "<tr";
		$return = $this->addargs($return, $args);
		$return .= ">".$this->pack("\n") . $text . $this->pack("\t") . "</tr>" . $this->pack("\n");
		
		return $return;
	}
	
	function th ( $text, $args = null ) {
		return $this->td( $text, $args, true );
	}
	
	function td ( $text, $args = null, $useth = false ) {
		$return = $this->pack("\t\t") . "<" . ($useth ? 'th' : 'td');
		$return = $this->addargs($return, $args) . ">" . $this->pack("\n");
		$return .= $this->pack("\t\t\t") . $this->font( $text ) . $this->pack("\n\t\t") . "</".($useth ? 'th' : 'td').">" . $this->pack("\n");
		
		return $return;
	}
	
	function font ( $text = null, $args = null ) {
		$args['face'] = isset($args['face']) ? $args['face'] : $this->_config['face'];
		$args['size'] = isset($args['size']) ? $args['size'] : $this->_config['size'];
		
		$return = $this->addargs('<font', $args) . '>';
		
		if(isset($text)) {
			$return .= $text . '</font>';
		}
		
		return $return;
	}
	
	function hr ( $args = null ) {
		return $this->addargs( '<hr', $args ) . ' />';
	}
	
	function br () {
		return '<br />';
	}
	
	function pre ( $text = null ) {
		if(isset($pre)) {
			return '<pre>' . $text . '</pre>';
		} else {
			return '<pre>';
		}
	}
	
	function cpre() {
		return '</pre>';
	}
	
	function ul ( $text ) {
		if(is_array($text)) {
			foreach($text as $key => $val) {
				$return .= $this->ul($val);
			}
		} else {
			$return = "<ul>".$this->pack("\n").$text.$this->pack("\n")."</ul>".$this->pack("\n");
		}
		
		return $return;
	}
	
	function li ( $text ) {
		if(is_array($text)) {
			foreach($text as $key => $val) {
				$return .= $this->ul($val);
			}
		} else {
			$return = "<li>".$this->pack("\n").$text.$this->pack("\n")."</li>".$this->pack("\n");
		}
		
		return $return;
	}
	
	/*
		Start of form things here!
	*/
	function form_start ( $args = null ) {
		global $PHP_SELF;
		
		$args['action'] = isset($args['action']) ? $args['action'] : $PHP_SELF;
		$args['method'] = isset($args['method']) ? $args['method'] : 'POST';
		
		return $this->addargs('<form', $args) . ">" . $this->pack("\n");
	}
	
	function form_end() {
		return $this->pack("\n") . "</form>";
	}
	
	// the above 2 together
	function form ( $text, $args = null ) {
		return $this->form_start($args) . $text . $this->form_end();
	}
	
	// now actual things
	function _input ( $type, $name, $args = null ) {
		$this->_appendvalue($args, 'value', $name);
		
		$return = '<input type="'.$type.'" name="' . $name .'"';
		$return = $this->addargs( $return, $args ) . '>';
		
		return $return;	
	}
	
	function textfield ( $name, $args = null ) {
		return $this->_input('text', $name, $args);
	}
	
	function password_field ( $name, $args = null ) {
		return $this->_input('password', $name, $args);
	}
	
	function filefield ( $name, $args = null ) {
		return $this->_input('file', $name, $args);
	}
	
	function textarea ( $name, $args = null) {
		$this->_appendvalue($args, 'value', $name);
		
		if(isset($args['value'])) {
			$value = $args['value'];
			
			unset($args['value']);
		}
		
		$return = '<textarea name="' . $name .'"';
		$return = $this->addargs( $return, $args ) . '>'.$value.'</textarea>';
		
		return $return;
	}
	
	function popup_menu ( $name, $choices, $default = null, $args = null ) {
		if(!isset($default) && isset($this->_param[$name])) {
			$default = $this->param($name);
		}
		
		$return = $this->addargs('<select name="'.$name, $args) . "\">" . $this->pack("\n");

		if(is_array($choices)) {
			foreach($choices as $key => $val) {
				$return .= $this->pack("\t") . "<option value=\"$key\"";
				
				if($key == $default) {
					$return .= ' selected';
				}
				
				$return .= ">". $val . '</option>' . $this->pack("\n");
			}
		}
		
		$return .= "</select>" . $this->pack("\n");
		
		return $return;
	}
	
	function checkbox ( $name, $value, $checked = false, $args = null ) {
		if(is_array($args) && isset($args['label'])) {
			$lbl = $args['label'];
			unset($args['label']);
		}
		
		return $this->addargs('<input type="checkbox" name="'.$name.'" value="' . $value . '"', $args) . ($checked ? ' checked' : null) .'>' . $lbl;
	}
	
	function radiobutton ( $name, $value, $checked = false, $args = null ) {
		if(is_array($args) && isset($args['label'])) {
			$lbl = $args['label'];
			unset($args['label']);
		}
		
		return $this->addargs('<input type="radio" name="'.$name.'" value="' . $value . '"', $args) . ($checked ? ' checked' : null) .'>' . $lbl;
	}
	
	function submit ( $value = 'Submit Query', $args = null ) {
		return $this->addargs("<input type=\"submit\" value=\"$value\"", $args) . '>';
	}
	
	function reset ( $value = 'Reset', $args = null ) {
		return $this->addargs("<input type=\"reset\" value=\"$value\"", $args) . '>';
	}
	
	function button ( $value = 'Button', $args = null ) {
		return $this->addargs("<input type=\"button\" value=\"$value\"", $args) . '>';
	}
	
	function addargs ( $in, $args = null ) {
		if(is_array($args)) {
			// make sure we don't have doubles with different cases
			foreach($args as $key => $val) {
				$args[strtolower($key)] = $val;
			}
			
			// now add it
			foreach($args as $key => $val) {
				$in .= " $key=\"$val\"";
			}
		}
		
		return $in;
	}
	
	function _appendvalue(&$args, $value, $name) {
		if(!isset($args[$value]) && isset($this->_param[$name])) {
			$args[$value] = stripslashes($this->param($name));
		}
	}
	
	// special html dump
	// does a 'clean' dump of an array
	// doesn't work yet :p
	function htmldump ( $array ) {
		foreach($array as $key => $val) {
			$return .= $this->ul(
						$this->li($this->font($key)) .
							$this->ul(
								$this->font(is_array($val) ? $this->htmldump($val) : $this->li($val))
							)
						);
		}
		
		return $return;
	}
} // end html class
	
?>