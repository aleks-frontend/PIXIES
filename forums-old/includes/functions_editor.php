<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 3.0.6 - Licence Number 1729741e
|| # ---------------------------------------------------------------- # ||
|| # Copyright �2000�2005 Jelsoft Enterprises Ltd. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

error_reporting(E_ALL & ~E_NOTICE);

// ###################### Start gettextareawidth #######################
function fetch_textarea_width()
{
	// attempts to fix netscape textarea width problems
	global $_SERVER, $stylevar;

	if (is_browser('ie'))
	{
		// browser is IE
		return $stylevar['textareacols_ie4'];
	}
	else if (is_browser('mozilla'))
	{
		// browser is NS >= 6.x / Mozilla >= 1.x
		return $stylevar['textareacols_ns6'];
	}
	else if (is_browser('netscape'))
	{
		// browser is NS4
		return $stylevar['textareacols_ns4'];
	}
	else
	{
		// unknown browser - stick in a sensible value
		return 60;
	}

}

// #################### Start fetch editor styles ##########################
// returns the javascript required for the editor control styles
function construct_editor_styles_js($editorstyles = false)
{
	// istyles - CSS in order: background / color / padding / border
	global $istyles;

	if (!is_array($istyles))
	{
		if (!$editorstyles)
		{
			$istyles = unserialize($GLOBALS['style']['editorstyles']);
		}
		else
		{
			$istyles = unserialize($editorstyles);
		}
	}

	$istyle = array();
	foreach ($istyles AS $key => $array)
	{
		$istyle[] = "\"$key\" : [ \"$array[0]\", \"$array[1]\", \"$array[2]\", \"$array[3]\" ]";
	}

	return implode(", ", $istyle);
}

// ###################### Start wysiwyg_compatible #######################
// returns 0, 1, 2 depending on permissions, options and browser compatability
function is_wysiwyg_compatible($userchoice = -1)
{
	global $vboptions, $bbuserinfo, $_SERVER;

	// check for a standard setting
	if ($userchoice == -1)
	{
		$userchoice = $bbuserinfo['showvbcode'];
	}

	// netscape 4... don't even bother to check user choice as the toolbars won't work
	if (is_browser('netscape') OR is_browser('webtv'))
	{
		return 0;
	}

	// check board options for toolbar permissions
	if ($userchoice > $vboptions['allowvbcodebuttons'])
	{
		$w = $vboptions['allowvbcodebuttons'];
	}
	else
	{
		$w = $userchoice;
	}

	if ($w == 2) // attempt to use WYSIWYG
	{
		if (!is_browser('opera'))
		{
			// Check Mozilla Browsers
			if (is_browser('firebird', '0.6.1') OR (is_browser('mozilla', '20030312') AND !is_browser('firebird')) OR is_browser('camino', '0.9'))
			{
				return 2;
			}
			else if (is_browser('ie', '5.5') AND !is_browser('mac'))
			{
				return 2;
			}
			else
			{
				return 1;
			}
		}
		else
		{
			// browser is incompatible - return standard toolbar
			return 1;
		}
	}
	else
	{
		// return standard or no toolbar
		return $w;
	}
}

// ###################### Start buildEditToolbar #######################
// DOTOOLBAR: 0 = no toolbar, 1 = standard toolbar, 2 = wysiwyg toolbar
function construct_edit_toolbar($text = '', $ishtml = 0, $forumid = 0, $allowsmilie = 1, $parsesmilie = 1)
{
	// standard stuff
	global $DB_site, $vboptions, $vbphrase, $stylevar, $session, $bbuserinfo, $show, $_COOKIE, $_POST, $_REQUEST;
	// templates generated by this function
	global $messagearea, $smiliebox, $disablesmiliesoption, $checked;
	// misc stuff built by this function
	global $onload;

	// allow temporary switching between wysiwyg / standard systems
	if ($_REQUEST['toolbartype'])
	{
		$bbuserinfo['showvbcode'] = intval($_REQUEST['toolbartype']);
	}

	// make sure we have DOTOOLBAR defined
	if (!defined('DOTOOLBAR'))
	{
		define('DOTOOLBAR', is_wysiwyg_compatible());
	}

	$wysiwyg = iif(DOTOOLBAR == 2, 1, 0);
	$show['wysiwyg'] = iif(DOTOOLBAR == 2, true, false);
	$show['syscolorpicker'] = false;

	// init the variables used by the templates built by this function
	$tbtcheck = array(DOTOOLBAR => HTML_CHECKED);
	$textareacols = fetch_textarea_width();
	$vBeditJs = array(
		'font_options_array' => '',
		'size_options_array' => '',
		'istyle_array'       => '',
		'normalmode'         => 'false'
	);
	$vBeditTemplate = array(
		'extrabuttons'       => '',
		'clientscript'       => '',
		'fontfeedback'       => '',
		'sizefeedback'       => '',
		'smiliepopup'        => ''
	);

	// show a post editing toolbar of some sort
	if (DOTOOLBAR)
	{
		// set up some javascript
		global $istyles;

		// set the onload event for the body tag
		$onload = ' onload="editInit();"';

		// extract the variables from the vbcode_font_options and vbcode_size_options templates
		foreach(array('editor_jsoptions_font', 'editor_jsoptions_size') AS $template)
		{
			eval('$string = "' . fetch_template($template, 1, 0) . '";');
			$$template = preg_split('#\r?\n#s', $string, -1, PREG_SPLIT_NO_EMPTY);
		}

		// get the javascript vars to drive the editor
		$vBeditJs = array(
			// font popup options array
			'font_options_array' => '"' . implode('", "', $editor_jsoptions_font) . '"',
			// size popup options array
			'size_options_array' => implode(", ", $editor_jsoptions_size),
			// 'istyle' CSS definitions for controls
			'istyle_array' => construct_editor_styles_js(),
			// normalmode thingy
			'normalmode' => 'false'
		);

		// get extra buttons... experimental at the moment
		$vBeditTemplate['extrabuttons'] = construct_editor_extra_buttons(DOTOOLBAR);

		if (DOTOOLBAR == 2)
		{
			// got to parse the message to be displayed from bbcode into HTML
			require_once('./includes/functions_wysiwyg.php');
			if ($text)
			{
				$newpost['message'] = parse_wysiwyg_html($text, $ishtml, $forumid, iif($allowsmilie AND $parsesmilie, 1, 0));
			}
			else
			{
				$newpost['message'] = iif(is_browser('ie'), '<p></p>', '<br>');
			}

			DEVDEBUG('Parsing BBcode to populate edit box: ' .  strrchr(__FILE__, '/') . ' line ' . __LINE__);

			// build the feedback sections for the WYSIWYG font and size popups
			// using a different method for IE to avoid the undo buffer problem
			if (is_browser('ie'))
			{
				// font feedback - IE
				foreach ($editor_jsoptions_font AS $fontoption)
				{
					$vBeditTemplate['fontfeedback'] .= '<div style="width:91px; display:none">' . $fontoption . '</div>';
				}
				$vBeditTemplate['fontfeedback'] .= '<div style="width:91px; display:block"></div>';

				// size feedback - IE
				foreach ($editor_jsoptions_size AS $sizeoption)
				{
					$vBeditTemplate['sizefeedback'] .= '<div style="width:12px; display:none">' . $sizeoption . '</div>';
				}
				$vBeditTemplate['sizefeedback'] .= '<div style="width:12px; display:block"></div>';

				// system color picker
				$show['syscolorpicker'] = iif($vboptions['syscolorpicker'], true, false);
			}
			else
			{
				// font feedback - MOZ
				$vBeditTemplate['fontfeedback'] = '<input type="text" id="fontOut" style="width:91px" value="" readonly="readonly" tabindex="0" />';
				// size feedback - MOZ
				$vBeditTemplate['sizefeedback'] = '<input type="text" id="sizeOut" style="width:12px" value="" readonly="readonly" tabindex="0" />';
			}

			$newpost['message_html'] = htmlspecialchars_uni($newpost['message']);

			$templatename = 'editor_toolbar_wysiwyg';
			$smilietemplate = 'editor_smilie_wysiwyg';
		}
		else
		{
			// set mode based on cookie set by javascript
			$_COOKIE['vbcodemode'] = intval($_COOKIE['vbcodemode']);
			$modechecked[$_COOKIE['vbcodemode']] = HTML_CHECKED;
			$vBeditJs['normalmode'] = iif($_COOKIE['vbcodemode'] == 1, 'false', 'true');
			$newpost['message'] = $text;

			$templatename = 'editor_toolbar_standard';
			$smilietemplate = 'editor_smilie_standard';
		}

	}
	// do not show a post editing toolbar
	else
	{
		$editorscripts = '';
		$newpost['message'] = $text;
		$templatename = 'editor_toolbar_off';
		$smilietemplate = 'editor_smilie_standard';
		$onload = ' onload="editInit();"';
	}

	// get the template containing the javascript sources and extra CSS for the editor
	if (!is_browser('ie'))
	{
		$show['mozilla_js'] = true;
	}
	eval('$vBeditTemplate[\'clientscript\'] = "' . fetch_template('editor_clientscript') . '";');

	// disable smilies option and clickable smilie
	$show['smiliebox'] = false;
	$smiliebox = '';
	$disablesmiliesoption = '';

	if ($allowsmilie)
	{
		if (!isset($checked['disablesmilies']))
		{
			$checked['disablesmilies'] = iif(isset($_REQUEST['disablesmilies']), HTML_CHECKED);
		}

		if (DOTOOLBAR AND $vboptions['smtotal'] > 0 OR $vboptions['wysiwyg_smtotal'] > 0)
		{
			// query smilies
			$smilies = $DB_site->query("
				SELECT smilieid, smilietext, smiliepath, smilie.title,
				imagecategory.title AS category
				FROM " . TABLE_PREFIX . "smilie AS smilie
				LEFT JOIN " . TABLE_PREFIX . "imagecategory AS imagecategory USING(imagecategoryid)
				ORDER BY imagecategory.displayorder, smilie.displayorder
			");

			// get total number of smilies
			$totalsmilies = $DB_site->num_rows($smilies);
			if ($totalsmilies > 0)
			{
				if (DOTOOLBAR == 2)
				{

					$show['wysiwygsmilieslink'] = iif ($totalsmilies > $vboptions['wysiwyg_smtotal'], true, false);
					$show['wysiwygsmilies'] = iif($vboptions['wysiwyg_smtotal'], true, false);

					// WYSIWYG smilie popup
					$lastcat = '';
					$vboptions['smcolumns_tmp'] = $vboptions['smcolumns'];
					$vboptions['smcolumns'] = 1;

					$i = 0;
					while ($smilie = $DB_site->fetch_array($smilies) AND $i++ < $vboptions['wysiwyg_smtotal'])
					{
						if ($smilie['category'] != $lastcat)
						{
							$lastcat = $smilie['category'];
							eval('$vBeditTemplate[\'smiliepopup\'] .= "' . fetch_template('editor_smiliemenu_category') . '";');
						}
						eval('$vBeditTemplate[\'smiliepopup\'] .= "' . fetch_template('editor_smiliemenu_smilie') . '";');
					}
					$DB_site->data_seek(0, $smilies);

					$vboptions['smcolumns'] = $vboptions['smcolumns_tmp'];
				}

				if (DOTOOLBAR == 1 OR (DOTOOLBAR == 2 AND $vboptions['wysiwyg_show_smiliebox'] AND $vboptions['smtotal']))
				{
					// NON-WYSIWYG smilie click box
					$smcache = array();
					$smiliesbits = '';

					$i = 0;
					while ($smilie = $DB_site->fetch_array($smilies) AND $i++ < $vboptions['smtotal'])
					{
						$smcache["$smilie[category]"][] = $smilie;
					}
					$DB_site->free_result($smilies);

					foreach($smcache AS $category => $smilies)
					{
						eval('$smiliebits .= "' . fetch_template('editor_smiliebox_category') . '";');
						$bits = array();
						foreach ($smilies AS $smilie)
						{
							$smilie['smilietext'] = addslashes($smilie['smilietext']);
							eval('$bits[] = "' . fetch_template($smilietemplate) . '";');
							if (sizeof($bits) == $vboptions['smcolumns'])
							{
								$smiliecells = implode('', $bits);
								eval('$smiliebits .= "' . fetch_template('editor_smiliebox_row') . '";');
								$bits = array();
							}
						}

						// fill in empty cells if required
						$remaining = sizeof($bits);
						if ($remaining > 0)
						{
							$remainingcolumns = $vboptions['smcolumns'] - $remaining;
							eval('$bits[] = "' . fetch_template('editor_smiliebox_straggler') . '";');
							$smiliecells = implode('', $bits);
							eval('$smiliebits .= "' . fetch_template('editor_smiliebox_row') . '";');
						}
					}
					$show['moresmilieslink'] = iif ($totalsmilies > $vboptions['smtotal'], true, false);
					$show['smiliebox'] = true;
				}
				$DB_site->free_result($smilies);
			}
		}
		eval('$disablesmiliesoption = "' . fetch_template('newpost_disablesmiliesoption') . '";');
	}

	eval('$smiliebox = "' . fetch_template('editor_smiliebox') . '";');

	require_once('./includes/functions_bbcodeparse.php');
	$show['font_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_FONT, true, false);
	$show['size_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_SIZE, true, false);
	$show['color_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_COLOR, true, false);
	$show['basic_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_BASIC, true, false);
	$show['align_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_ALIGN, true, false);
	$show['list_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_LIST, true, false);
	$show['code_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_CODE, true, false);
	$show['html_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_HTML, true, false);
	$show['php_bbcode'] = iif ($vboptions['allowedbbcodes'] & ALLOW_BBCODE_PHP, true, false);
	$show['url_bbcode'] = iif($vboptions['allowedbbcodes'] & ALLOW_BBCODE_URL, true, false);

	if (empty($forumid))
	{
		$forumid = 'nonforum';
	}
	switch($forumid)
	{
		case 'privatemessage':
			$show['img_bbcode'] = $vboptions['privallowbbimagecode'];
			break;
		case 'usernote':
			$show['img_bbcode'] = $vboptions['unallowimg'];
			break;
		case 'calendar':
			global $calendarinfo;
			$show['img_bbcode'] = $calendarinfo['allowimgcode'];
			break;
		case 'nonforum':
			$show['img_bbcode'] = $vboptions['allowbbimagecode'];
			break;
		default:
			$forum = fetch_foruminfo($forumid);
			$show['img_bbcode'] = $forum['allowimages'];
			break;
	}

	// create the message area template
	eval('$messagearea = "' . fetch_template($templatename) . '";');

}

// #################### Start fetch editor extra buttons ##########################
// returns the extra buttons as defined by the bbcode editor
function construct_editor_extra_buttons($toolbartype)
{
	global $datastore, $bbcodecache, $vbphrase;

	if (!is_array($bbcodecache))
	{
		$bbcodecache = unserialize($datastore['bbcodecache']);
	}

	$extrabuttons = '';

	foreach ($bbcodecache AS $bbcode)
	{
		if ($bbcode['buttonimage'] != '')
		{
			$tag = strtoupper($bbcode['bbcodetag']);

			$alt = construct_phrase($vbphrase['wrap_x_tags'], $tag);

			if ($toolbartype == 2)
			{
				$extrabuttons .= "<td><div class=\"imagebutton\" id=\"cmd_wrap$bbcode[twoparams]_$bbcode[bbcodetag]\"><img src=\"$bbcode[buttonimage]\" alt=\"$alt\" title=\"$alt\" width=\"21\" height=\"20\" /></div></td>\n";
			}
			else
			{
				$extrabuttons .= "<td><div class=\"imagebutton\"><a href=\"#\" onclick=\"return vbcode('$tag', '', $bbcode[twoparams])\"><img src=\"$bbcode[buttonimage]\" alt=\"$alt\" title=\"$alt\" width=\"21\" height=\"20\" border=\"0\" /></a></div></td>\n";
			}
		}
	}

	return $extrabuttons;
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 17:03, Sat Jan 22nd 2005
|| # CVS: $RCSfile: functions_editor.php,v $ - $Revision: 1.12.2.3 $
|| ####################################################################
\*======================================================================*/
?>