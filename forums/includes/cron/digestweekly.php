<?php
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 3.0.10 - Licence Number 21035487
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000–2005 Jelsoft Enterprises Ltd. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

error_reporting(E_ALL & ~E_NOTICE);

if (!is_object($DB_site))
{
	exit;
}

require_once('./includes/functions_digest.php');

// send weekly digest of new posts in threads and threads in forums
exec_digest(3);

log_cron_action('Weekly Digests Sent', $nextitem);

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 09:31, Thu Nov 3rd 2005
|| # CVS: $RCSfile: digestweekly.php,v $ - $Revision: 1.11 $
|| ####################################################################
\*======================================================================*/
?>