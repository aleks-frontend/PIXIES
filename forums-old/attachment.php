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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('NO_REGISTER_GLOBALS', 1);
define('THIS_SCRIPT', 'attachment');
if ($_GET['stc'] == 1) // we were called as <img src=> from showthread.php
{
	define('LOCATION_BYPASS', 1);
}

// Immediately send back the 304 Not Modified header if this image is cached, don't load global.php
// Don't check the modify date since if an attachment is modified, it gains a new attachmentid
if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
{
	$sapi_name = php_sapi_name();
	if ($sapi_name == 'cgi' OR $sapi_name == 'cgi-fcgi')
	{
		header('Status: 304 Not Modified');
	}
	else
	{
		header('HTTP/1.1 304 Not Modified');
	}
	exit;
}

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array();

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

$noheader = 1;

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

globalize($_REQUEST, array('attachmentid' => INT, 'thumb' => INT, 'postid' => INT));

$idname = $vbphrase['attachment'];

$imagetype = iif($thumb, 'thumbnail', 'filedata');

if (!$attachmentinfo = $DB_site->query_first("
	SELECT filename, attachment.postid, attachment.userid, attachmentid,
		" . iif($thumb, 'thumbnail AS filedata, thumbnail_dateline AS dateline, thumbnail_filesize AS filesize,', 'attachment.dateline, filedata, filesize,') . "
		attachment.visible, mimetype, NOT ISNULL(deletionlog.primaryid) AS isdeleted,
		thread.forumid, forum.password, thread.threadid
	FROM " . TABLE_PREFIX . "attachment AS attachment
	LEFT JOIN " . TABLE_PREFIX . "attachmenttype AS attachmenttype ON(attachmenttype.extension = SUBSTRING_INDEX(attachment.filename, '.', -1))
	LEFT JOIN " . TABLE_PREFIX . "deletionlog AS deletionlog ON(attachment.postid = deletionlog.primaryid AND type = 'post')
	LEFT JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = attachment.postid)
	LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (post.threadid = thread.threadid)
	LEFT JOIN " . TABLE_PREFIX . "forum AS forum ON (forum.forumid = thread.forumid)
	" . iif($postid, "WHERE attachment.postid = $postid", "WHERE attachmentid = $attachmentid") . "
"))
{
	eval(print_standard_error('error_invalidid'));
}

if ($attachment['postid'] == 0 AND $bbuserinfo['userid'] == $attachmentinfo['userid'])
{
	// Attachment is "in progress" and the viewing person is the owner.. Allow them to view it from their file attachment manager or attachment window
}
else
{
	$forumperms = fetch_permissions($attachmentinfo['forumid']);

	$threadinfo = array('threadid' => $attachmentinfo['threadid']); // used for session.inthread

	if (!can_moderate($attachmentinfo['forumid']) AND $attachmentinfo['isdeleted'])
	{
		eval(print_standard_error('error_invalidid'));
	}

	if (!($forumperms & CANVIEW) OR !($forumperms & CANGETATTACHMENT))
	{
		print_no_permission();
	}

	// check if there is a forum password and if so, ensure the user has it set
	verify_forum_password($attachmentinfo['forumid'], $attachmentinfo['password']);

	if (!$attachmentinfo['visible'] AND !can_moderate($attachmentinfo['forumid'], 'canmoderateattachments') AND $attachmentinfo['userid'] != $bbuserinfo['userid'])
	{
		$idname = 'attachment';
		eval(print_standard_error('error_invalidid'));
	}
}

// update views counter
if (!$thumb)
{
	if ($vboptions['attachmentviewslive'])
	{
		// doing it as they happen
		$DB_site->shutdown_query("
			UPDATE " . TABLE_PREFIX . "attachment
			SET counter = counter + 1
			WHERE attachmentid = $attachmentinfo[attachmentid]
		");
	}
	else
	{
		// or doing it once an hour
		$DB_site->shutdown_query("
			INSERT INTO " . TABLE_PREFIX . "attachmentviews (attachmentid)
			VALUES ($attachmentinfo[attachmentid])
		");
	}
}

$extension = strtolower(file_extension($attachmentinfo['filename']));

if ($vboptions['attachfile'])
{
	require_once('./includes/functions_file.php');
	if ($thumb)
	{
		$attachpath = fetch_attachment_path($attachmentinfo['userid'], $attachmentinfo['attachmentid'], true);
	}
	else
	{
		$attachpath = fetch_attachment_path($attachmentinfo['userid'], $attachmentinfo['attachmentid']);
	}

	if (!($attachmentinfo['filedata'] = @file_get_contents($attachpath)))
	{
		$filedata = base64_decode('R0lGODlhAQABAIAAAMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
		$filesize = strlen($filedata);
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');             // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: no-cache, must-revalidate');           // HTTP/1.1
		header('Pragma: no-cache');                                   // HTTP/1.0
		header("Content-disposition: inline; filename=clear.gif");
		header('Content-transfer-encoding: binary');
		header("Content-Length: $filesize");
		header('Content-type: image/gif');
		echo $filedata;
		exit;
	}
}

header('Cache-control: max-age=31536000');
header('Expires: ' . gmdate("D, d M Y H:i:s", TIMENOW + 31536000) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $attachmentinfo['dateline']) . ' GMT');
if ($extension != 'txt')
{
	header("Content-disposition: inline; filename=\"$attachmentinfo[filename]\"");
	header('Content-transfer-encoding: binary');
}
else
{
	// force txt files to be downloaded because of a possible XSS issue
	header("Content-disposition: attachment; filename=\"$attachmentinfo[filename]\"");
}

header('Content-Length: ' . $attachmentinfo['filesize']);

$mimetype = unserialize($attachmentinfo['mimetype']);
if (is_array($mimetype))
{
	foreach ($mimetype AS $index => $header)
	{
		header($header);
	}
}
else
{
	header('Content-type: unknown/unknown');
}

echo $attachmentinfo['filedata'];

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 17:03, Sat Jan 22nd 2005
|| # CVS: $RCSfile: attachment.php,v $ - $Revision: 1.96.2.7 $
|| ####################################################################
\*======================================================================*/
?>