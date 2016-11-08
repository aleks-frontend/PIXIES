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

// ###################### Start getforumpermissions #######################
// queries forumpermissions for a single forum and either returns the forumpermissions,
// or the usergroup default.
function fetch_forum_permissions($usergroupid, $forumid)
{
	global $DB_site, $forumcache, $usergroupcache;

	// assign the permissions to the usergroup defaults
	$perms = $usergroupcache["$usergroupid"]['forumpermissions'];
	DEVDEBUG("FPerms: Usergroup Defaults: $perms");

	// get the parent list of the forum we are interested in, excluding -1
	$parentlist = substr($forumcache["$forumid"]['parentlist'], 0, -3);

	// query forum permissions for the forums in the parent list of the current one
	$fperms = $DB_site->query("
		SELECT forumid, forumpermissions
		FROM " . TABLE_PREFIX . "forumpermission
		WHERE usergroupid = $usergroupid
		AND forumid IN($parentlist)
	");
	// no custom permissions found, return usergroup defaults
	if ($DB_site->num_rows($fperms) == 0)
	{
		return array('forumpermissions' => $perms);
	}
	else
	{
		// assign custom permissions to forums
		$fp = array();
		while ($fperm = $DB_site->fetch_array($fperms))
		{
			$fp["$fperm[forumid]"] = $fperm['forumpermissions'];
		}
		unset($fperm);
		$DB_site->free_result($fperms);

		// run through each forum in the forum's parent list in order
		foreach(array_reverse(explode(',', $parentlist)) AS $parentid)
		{
			// if the current parent forum has a custom permission, use it
			if (isset($fp["$parentid"]))
			{
				$perms = $fp["$parentid"];
				DEVDEBUG("FPerms: Custom - forum '" . $forumcache["$parentid"]['title'] . "': $perms");
			}
		}

		// return the permissions, whatever they may be now.
		return array('forumpermissions' => $perms);
	}
}

// ###################### Start makechildlist ########################
function construct_child_list($forumid)
{
	global $DB_site;

	if ($forumid == -1)
	{
		return '-1';
	}

	$childlist = $forumid;

	$children = $DB_site->query("
		SELECT forumid
		FROM " . TABLE_PREFIX . "forum
		WHERE parentlist LIKE '%,$forumid,%'
	");
	while ($child = $DB_site->fetch_array($children))
	{
		$childlist .= ',' . $child['forumid'];
	}

	$childlist .= ',-1';

	return $childlist;

}

// ###################### Start updatechildlists #######################
function build_forum_child_lists($forumid = -1)
{
	global $DB_site;
	$forums = $DB_site->query("SELECT forumid FROM " . TABLE_PREFIX . "forum WHERE FIND_IN_SET('$forumid', childlist)");
	while ($forum = $DB_site->fetch_array($forums))
	{
		$childlist = construct_child_list($forum['forumid']);
		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "forum
			SET childlist = '$childlist'
			WHERE forumid = $forum[forumid]
		");
	}
}

// ###################### Start makeparentlist #######################
function fetch_forum_parentlist($forumid)
{
	global $DB_site;

	if ($forumid == -1)
	{
		return '-1';
	}

	$foruminfo = $DB_site->query_first("SELECT parentid FROM " . TABLE_PREFIX . "forum WHERE forumid = $forumid");

	$forumarray = $forumid;

	if ($foruminfo['parentid'] != 0)
	{
		$forumarray .= ',' . fetch_forum_parent_list($foruminfo['parentid']);
	}

	if (substr($forumarray, -2) != -1)
	{
		$forumarray .= '-1';
	}

	return $forumarray;
}

// ###################### Start updateparentlists #######################
function build_forum_parentlists($forumid = -1)
{
	global $DB_site;

	$forums = $DB_site->query("
		SELECT forumid, (CHAR_LENGTH(parentlist) - CHAR_LENGTH(REPLACE(parentlist, ',', ''))) AS parents
		FROM " . TABLE_PREFIX . "forum
		WHERE FIND_IN_SET('$forumid', parentlist)
		ORDER BY parents ASC
	");
	while($forum = $DB_site->fetch_array($forums))
	{
		$parentlist = fetch_forum_parentlist($forum['forumid']);
		$DB_site->query("
			UPDATE " . TABLE_PREFIX . "forum
			SET parentlist = '" . addslashes($parentlist) . "'
			WHERE forumid = $forum[forumid]
		");
	}
}

// ###################### Start permboxes #######################
function print_forum_permission_rows($customword, $forumpermission = array(), $extra = '')
{
	global $vbphrase;

	print_label_row(
		"<b>$customword</b>",'
		<input type="button" class="button" value="' . $vbphrase['all_yes'] . '" onclick="' . iif($extra != '', 'if (js_set_custom()) { ') . ' js_check_all_option(this.form, 1); fetch_object(\'rb_0_forumpermission[isalwaysmoderated]\').checked = true;' . iif($extra != '', ' }') . '" class="button" />
		<input type="button" class="button" value=" ' . $vbphrase['all_no'] . ' " onclick="' . iif($extra != '', 'if (js_set_custom()) { ') . ' js_check_all_option(this.form, 0); fetch_object(\'rb_1_forumpermission[isalwaysmoderated]\').checked = true;' . iif($extra != '', ' }') . '" class="button" />
		<!--<input type="submit" class="button" value="Okay" class="button" />-->
	', 'tcat', 'middle');

	print_description_row($vbphrase['forum_viewing_permissions'], 0, 2, 'thead', 'center');
	print_yes_no_row($vbphrase['can_view_forum'], 'forumpermission[canview]', $forumpermission['canview'], $extra);
	print_yes_no_row($vbphrase['can_view_others_threads'], 'forumpermission[canviewothers]', $forumpermission['canviewothers'], $extra);
	print_yes_no_row($vbphrase['can_see_deletion_notices'], 'forumpermission[canseedelnotice]', $forumpermission['canseedelnotice'], $extra);
	print_yes_no_row($vbphrase['can_search_forum'], 'forumpermission[cansearch]', $forumpermission['cansearch'], $extra);
	print_yes_no_row($vbphrase['can_use_email_to_friend'], 'forumpermission[canemail]', $forumpermission['canemail'], $extra);
	print_yes_no_row($vbphrase['can_download_attachments'], 'forumpermission[cangetattachment]', $forumpermission['cangetattachment'], $extra);

	print_description_row($vbphrase['post_permissions'], 0, 2, 'thead', 'center');
	print_yes_no_row($vbphrase['can_post_threads'], 'forumpermission[canpostnew]', $forumpermission['canpostnew'], $extra);
	print_yes_no_row($vbphrase['can_reply_to_own_threads'], 'forumpermission[canreplyown]', $forumpermission['canreplyown'], $extra);
	print_yes_no_row($vbphrase['can_reply_to_others_threads'], 'forumpermission[canreplyothers]', $forumpermission['canreplyothers'], $extra);
	print_yes_no_row($vbphrase['always_moderate_posts'], 'forumpermission[isalwaysmoderated]', $forumpermission['isalwaysmoderated'], $extra);
	print_yes_no_row($vbphrase['can_upload_attachments'], 'forumpermission[canpostattachment]', $forumpermission['canpostattachment'], $extra);
	print_yes_no_row($vbphrase['can_rate_threads'], 'forumpermission[canthreadrate]', $forumpermission['canthreadrate'], $extra);

	print_description_row($vbphrase['post_thread_permissions'], 0, 2, 'thead', 'center');
	print_yes_no_row($vbphrase['can_edit_own_posts'], 'forumpermission[caneditpost]', $forumpermission['caneditpost'], $extra);
	print_yes_no_row($vbphrase['can_delete_own_posts'], 'forumpermission[candeletepost]', $forumpermission['candeletepost'], $extra);
	print_yes_no_row($vbphrase['can_move_own_threads'], 'forumpermission[canmove]', $forumpermission['canmove'], $extra);
	print_yes_no_row($vbphrase['can_open_close_own_threads'], 'forumpermission[canopenclose]', $forumpermission['canopenclose'], $extra);
	print_yes_no_row($vbphrase['can_delete_own_threads'], 'forumpermission[candeletethread]', $forumpermission['candeletethread'], $extra);

	print_description_row($vbphrase['poll_permissions'], 0, 2, 'thead', 'center');
	print_yes_no_row($vbphrase['can_post_polls'], 'forumpermission[canpostpoll]', $forumpermission['canpostpoll'], $extra);
	print_yes_no_row($vbphrase['can_vote_on_polls'], 'forumpermission[canvote]', $forumpermission['canvote'], $extra);
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 17:03, Sat Jan 22nd 2005
|| # CVS: $RCSfile: adminfunctions_forums.php,v $ - $Revision: 1.30 $
|| ####################################################################
\*======================================================================*/
?>