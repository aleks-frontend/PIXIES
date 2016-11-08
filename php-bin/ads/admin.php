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

	require('config.inc.php');

	if(is_file('install.php')) {
		die("You must remove (or rename) the install script (install.php)!");
	}
	
	_extras::fix_slashes( );
	
	$pla->do_require('LedHTML');
	$html = new LedHTML(array(width => '550',
								border => 1,
								cellspacing => 1,
								cellpadding => 0,
								bordercolor => "#000000",
								align => 'center')
						); // html 'engine'
	
	$pla->do_auth($PHP_AUTH_USER, $PHP_AUTH_PW);

	echo $pla->top( );
		// do the action now
		$action = isset($action) ? $action : 'main';

		if(function_exists('sub_'.$action)) {
			call_user_func('sub_'.$action);
		} else {
			$pla->croak("Unable to find action: $action");
		}
	echo $pla->bottom( );
	
function sub_main( ) {
	global $pla, $html;
	
	echo 'Welcome to phpLedAds v'.$pla->version.'!';
	echo $html->br( );
	echo 'Select a menu item above to begin!';
}

function sub_newad( ) {
	global $pla, $html;
	
	echo $html->form_start(
				array(
					action => $pla->me . '?action=exec_newad',
					method => 'POST',
					enctype => 'multipart/form-data'
				)
			);
	
	$typebit = $html->param('banner_type') == 'rich' ? false : true;
	$imgbit = $html->param('image_type') == 'upload' ? false : true;
	
	echo $html->table(
				$html->tr(
					$html->td(
						$html->radiobutton('banner_type', 'image', $typebit) . 'Image Ad',
						array(colspan =>2 )
					)
				) .
				$html->tr(
					$html->td('Image:',
							array(valign=>'top')
					) .
					$html->td(
							$html->radiobutton('image_type', 'url', $imgbit) .' Existing URL' . $html->br( ) .
								$html->textfield('image_url', array(size=>40)) . $html->br( ) .
							$html->radiobutton('image_type', 'upload', !$imgbit) . ' Upload New Image (See Readme)' . $html->br( ) .
								$html->filefield('image_upload', array(size=>40))
					)
				) .
				$html->tr(
						$html->td('Links To:') .
						$html->td($html->textfield('links_to', array(size=>40)))
				) .
				$html->tr(
					$html->td('Alt Text:') .
					$html->td($html->textfield('alt_text', array(size=>35, value => '[ Advertisement Info ]')))
				) .
				$html->tr(
					$html->td('Target:') .
					$html->td(
							$html->popup_menu('target',
									array(
											_top	=> 'Current Browser (On Top)',
											_self	=> 'Current Window/Frame',
											_blank	=> 'New Window')
									  )
								)
				).
				$html->tr(
					$html->td('Width:') .
					$html->td($html->textfield('width', array(size => 5, value => 468)))
				) .
				$html->tr(
					$html->td('Height:') .
					$html->td($html->textfield('height', array(size=>5, value=>60)))
				) .
				$html->tr(
					$html->td(
						$html->radiobutton('banner_type', 'rich', !$typebit) . ' Rich Text/HTML (Insert [random] for a radom ID)',
						array(colspan=>2)
					)
				) .
				$html->tr(
					$html->td(
						$html->textarea('rich_text', array(rows=>10, cols=>50, wrap=>'VIRTUAL')),
						array(colspan => 2)
					)
				)
			);
					
	echo $html->center( $html->submit('Add New Ad') . $html->reset('Reset Fields') );
	echo $html->form_end( );
}

function sub_exec_newad( ) {
	global $html, $pla;
	
	$type = $html->param('banner_type');
	if($type == 'image') {
		if($html->param('image_type') == 'upload') {
			if($name = _extras::handle_upload('image_upload', $pla->config('datadir'))) {
				$url = $pla->webpath . 'data/' . $name;
			} else {
				echo("Unable to handle uploaded image!");
				return sub_newad( );
			}
		} else {
			$url = $html->param('image_url');
		}

		if(empty($url)) {
			echo("URL must not be empty!");
			return sub_newad( );
		}

		$link = $html->param('links_to');
		if(empty($link)) {
			echo("What it links to must not be empty!");
			return sub_newad( );
		}
		
		$alt_text = $html->param('alt_text');
		$target = $html->param('target');
		$width = intval($html->param('width'));
		$height = intval($html->param('height'));
		
		$sql = "insert into ". $pla->tables[images] ." (image_url, url, alt_text, target, width, height)
					values ('$url', '$link', '$alt_text', '$target', $width, $height)";
	} else {
		$data = $html->param('rich_text');
		
		if(empty($data)) {
			echo("You must beter something into the 'Rick Text' text box.");
			return sub_newad( );
		}
		
		$sql = "insert into ". $pla->tables[richtext] ." (data) values ('$data')";
	}
	
	mysql_query($sql, $pla->conn) or $pla->croak("Mysql query error: ($sql) " . mysql_error( ));
	
	$did = mysql_insert_id( );
	if(!$did) {
		$did = _extras::last_insert_id($pla->conn);
	}
	
	$sql = "insert into ". $pla->tables[ads] ." (type, did, active, datetime)
				values ('$type', $did, 'yes', now( ))";
	mysql_query($sql, $pla->conn) or $pla->croak("Mysql error: ($sql) " . mysql_error( ));
	
	return sub_modify( );
}

function sub_modify( ) {
	global $pla, $html, $pla_class;
	require_once('ad_class.php');
	
	$sql = "select aid, did, type from "
			. $pla->tables['ads']
			. " order by aid";
	
	$result = mysql_query($sql, $pla->conn) or $pla->croak("Mysql Error: ($sql) " . mysql_error( ));
	while($row = mysql_fetch_object($result)) {
		$table .= $html->tr(
					$html->td(
						($row->type == 'image' ? 
						$pla_class->_image_code( $row->aid ) :
						$pla_class->_rich_code( $row->aid )),
						array(align => 'center')
					)
				) .
				$html->tr(
					$html->td(
						'[ ' . $html->ahref($pla->me . '?action=edit_ad&aid=' . $row->aid,
							'Modify') . ' ]' .
						'[ ' . $html->ahref($pla->me . '?action=delete_ad&aid=' . $row->aid,
							'Delete',
								array(onclick => "if(!confirm('Are you sure you wan to delete this ad?')) { return false; }")
							) . ' ]' .
						'[ ' . $html->ahref($pla->me . '?action=stats&highlight=' . $row->aid,
							'Stats') . ' ]',
						array(align => 'center')
					)
				);
	}
	
	echo $html->table($table);
	
	return true;
}

function sub_edit_ad( ) {
	global $pla, $html, $pla_class;
	require_once('ad_class.php');
	
	$key = intval($html->param('aid'));
	
	list($type, $did) = $pla_class->get_type( $key );
	$func = ($type == 'image') ? '_edit_image_ad' : '_edit_rich_ad';
	return $func( $key, $did );
}

function _edit_image_ad( $key, $did ) {
	global $pla, $html, $pla_class;
	
	require_once('ad_class.php');
	
	$sql = "select * from "
			. $pla->tables['images']
			. " where (did = $did)";

	$result = mysql_query($sql, $pla->conn) or $pla->croak("Mysql Error: ($sql) " . mysql_error( ));
	$row = mysql_fetch_object($result);
	
	echo $html->center($pla_class->_image_code( $key ));
	
	echo $html->form_start(
				array(
					action => $pla->me . '?action=exec_edit_imagead&key=' . $key . '&did='. $did,
					method => 'POST'
				)
			);
	
	echo $html->table(
				$html->tr(
					$html->td('Image:',
							array(valign=>'top')
					) .
					$html->td(
							$html->textfield('image_url', array(value => $row->image_url, size=>40))
					)
				) .
				$html->tr(
						$html->td('Links To:') .
						$html->td($html->textfield('links_to', array(value => $row->url, size=>40)))
				) .
				$html->tr(
					$html->td('Alt Text:') .
					$html->td($html->textfield('alt_text', array(size=>35, value => $row->alt_text)))
				) .
				$html->tr(
					$html->td('Target:') .
					$html->td(
							$html->popup_menu('target',
									array(
											'_top'		=> 'Current Browser (On Top)',
											'_self'		=> 'Current Window/Frame',
											'_blank'	=> 'New Window'
									),
									$row->target
								)
						)
				).
				$html->tr(
					$html->td('Width:') .
					$html->td($html->textfield('width', array(size => 5, value => $row->width)))
				) .
				$html->tr(
					$html->td('Height:') .
					$html->td($html->textfield('height', array(size=>5, value => $row->height)))
				)
			);
					
	echo $html->center( $html->submit('Modify Ad') . $html->reset('Reset Fields') );
	echo $html->form_end( );
}

function sub_exec_edit_imagead( ) {
	global $pla, $html;

	$did = intval($html->param('did'));
	$url = $html->param('image_url');

	if(empty($url)) {
		$pla->croak("URL must not be empty!");
	}

	$link = $html->param('links_to');
	if(empty($link)) {
		$pla->croak("What it links to much not be empty!");
	}

	$alt_text = $html->param('alt_text');
	$target = $html->param('target');
	$width = intval($html->param('width'));
	$height = intval($html->param('height'));

	$sql = "update "
			. $pla->tables['images']
			. " set"
			. " image_url = '$url', url = '$link', alt_text = '$alt_text', target = '$target',"
			. " width = '$width', height = '$height'"
			. " where (did = $did)";

	mysql_query($sql, $pla->conn) or $pla->croak("Mysql Error: ($sql) " . mysql_error( ));

	//echo $html->center($html->b('Ad Updated!'));
	//return _edit_image_ad(intval($html->param('key')), $did);
	return sub_modify( );
}

function _edit_rich_ad( $key, $did ) {
	global $pla, $html, $pla_class;
	
	require_once('ad_class.php');
	
	$sql = "select * from "
			. $pla->tables['richtext']
			. " where (did = $did)";

	$result = mysql_query($sql, $pla->conn) or $pla->croak("Mysql Error: ($sql) " . mysql_error( ));
	$row = mysql_fetch_object($result);
	
	echo $html->form_start(
				array(
					action => $pla->me . '?action=exec_edit_richad&key=' . $key . '&did='. $did,
					method => 'POST',
				)
			);
	
	echo $html->table(
				$html->tr(
					$html->td(
						'Rich Text/HTML (Insert [random] for a radom ID)'
					)
				) .
				$html->tr(
					$html->td(
						$html->textarea('rich_text', array(rows=>10, cols=>50, wrap=>'VIRTUAL', value => $row->data))
					)
				)
			);
					
	echo $html->center( $html->submit('Modify Ad') . $html->reset('Reset Fields') );
	echo $html->form_end( );
}

function sub_exec_edit_richad( ) {
	global $html, $pla;
	
	$did = intval($html->param('did'));
	$data = $html->param('rich_text');
	
	$sql = "update "
			. $pla->tables['richtext']
			. " set data = '$data'"
			. " where (did = $did)";
			
	mysql_query($sql, $pla->conn) or $pla->croak("Mysql Error: ($sql) " . mysql_error( ));

	//echo $html->center($html->b('Ad Updated!'));
	//return _edit_rich_ad(intval($html->param('key')), $did);
	return sub_modify( );
}


function sub_delete_ad( ) {
	global $html, $pla, $pla_class;
	
	require_once('ad_class.php');
	
	$key = intval($html->param('aid'));
	list($did, $type) = $pla_class->get_type($key);
	
	// we're only going to delete it from the ads table
	//mysql_query("delete from " . $pla->tables['impressions'] . " where aid = $key");
	mysql_query("delete from " . $pla->tables['ads'] . " where aid = $key");
	mysql_query("delete from " . ($type == 'image' ? $pla->tables['images'] : $pla->tables['richtext']) . " where (did = $did)");
	
	return sub_modify( );
}

function sub_import_from_one( ) {
	global $html, $pla;
	
	echo $html->form_start(
				array(
					action => $pla->me . '?action=exec_import_from_one',
					method => 'POST',
				)
			);
			
	echo $html->table(
			$html->tr(
				$html->td(
					$html->b('Enter the information for the <i>old</i> LedAds (1.0) Mysql Settings:'),
					array(colspan => 2)
				)
			) .			
			$html->tr(
				$html->td(
					$html->b('Database Username:')
				) .
				$html->td(
					$html->textfield('db_username', array(size => 30))
				)
			) .
			$html->tr(
				$html->td(
					$html->b('Database Password:')
				) .
				$html->td(
					$html->textfield('db_password', array(size => 30))
				)
			) .
			$html->tr(
				$html->td(
					$html->b('Database Name:')
				) .
				$html->td(
					$html->textfield('db_name', array(size => 30))
				)
			) .
			$html->tr(
				$html->td(
					$html->b('Database Host:')
				) .
				$html->td(
					$html->textfield('db_host', array(size => 30, value => 'localhost'))
				)
			) .
			$html->tr(
				$html->td(
					$html->b('LedAds Table:')
				) .
				$html->td(
					$html->textfield('db_table', array(size => 30, value => 'ledbanners_adstats'))
				)
			)
		);
		
	echo $html->submit('Attempt To Import');
	echo $html->form_end( );
}

function sub_exec_import_from_one( ) {
	global $html, $pla;
	
	$conn = mysql_connect(
				$html->param('db_host'),
				$html->param('db_username'),
				$html->param('db_password')
			) or $pla->croak("Unable to connect to database to transfer LedAds 1.0 Stats");
			
	mysql_select_db($html->param('db_name'), $conn) or $pla->croak(mysql_error( ));
	
	$table = $html->param('db_table');
	
	$result = mysql_query("explain $table", $conn) or $pla->croak(mysql_error( ));
	while($row = mysql_fetch_object($result)) {
		$check .= serialize($row);
	}
	
	if(md5($check) != '8fd45c0e019fa03a78831f2f54244e5b') {
		$pla->croak("Table does not seem to be in the format for LedAds V1.0 -- make sure the version you are using is 1.0 and not a lower one.");
	}
	
	$result = mysql_query("select * from $table", $conn) or $pla->croak(mysql_error( ));
	while($row = mysql_fetch_object($result)) {
		
		if($row->dat_type == 'html') {
			$type = 'rich';
			
			$sql = "insert into ". $pla->tables[richtext] ." (data) values ('".addslashes($row->html)."')";
		} else {
			$type = 'image';
			
			$sql = sprintf("insert into ". $pla->tables[images] ." (image_url, url, alt_text, target, width, height)
						values ('%s', '%s', '%s', '%s', %s, %s)",
						addslashes($row->image_url),
						addslashes($row->url),
						'', '_top', '468', '60'
				);
		}
		
		mysql_query($sql, $pla->conn) or $pla->croak(mysql_error( ));
		
		$did = mysql_insert_id($pla->conn);
		if(!$did) {
			$did = _extras::last_insert_id($pla->conn);
		}
		
		$sql = "insert into ". $pla->tables[ads] ." (type, did, active, datetime)
						values ('$type', $did, 'yes', now( ))";

		//echo $html->br( ) . $sql;
		mysql_query($sql, $pla->conn) or $pla->croak(mysql_error( ));

		$aid = mysql_insert_id($pla->conn);
		if(!$aid) {
			$aid = _extras::last_insert_id($pla->conn);
		}
		
		// now ad stats
		$sql = sprintf("replace into " . $pla->tables[impressions]
						. " (aid, impdate, displays, clicks) values"
						. " ($aid, now( ), %s, %s)",
						$row->displays_life,
						$row->clicks_life
			);
		
		//echo $html->br( ) . $sql;
		mysql_query($sql, $pla->conn) or $pla->croak(mysql_error( ));
		
		echo "Imported " . ++$i . "...<br>\n";
	}
	
	echo "Finished!";
}

function sub_stats( ) {
	global $html, $pla;
	
	echo $html->table(
			$html->tr(
				$html->td(
					$html->center($html->b('10 Day Totals')),
					array(bgcolor => '#003366')
				)
			) .
			$html->tr(
				$html->td(
					phpLedAdsStats::tenday($pla, $html)
				)
			) .
			$html->tr(
				$html->td(
					$html->hr(array(size => 1)),
					array(bgcolor => 'black')
				)
			) .
			$html->tr(
				$html->td(
					$html->center($html->b('Per-Ad Stats')),
					array(bgcolor => '#003366')
				)
			) .
			$html->tr(
				$html->td(
					phpLedAdsStats::perad($pla, $html)
				)
			) .
			$html->tr(
				$html->td(
					$html->hr(array(size => 1)),
					array(bgcolor => 'black')
				)
			) .
			$html->tr(
				$html->td(
					$html->center($html->b('Over-All Totals')),
					array(bgcolor => '#003366')
				)
			) .
			$html->tr(
				$html->td(
					$html->center('<small>Includes deleted ads</small>'),
					array(bgcolor => 'black')
				)
			) .
			$html->tr(
				$html->td(
					phpLedAdsStats::totals($pla, $html)
				)
			) .
			$html->tr(
				$html->td(
					$html->hr(array(size => 1)),
					array(bgcolor => 'black')
				)
			)
	);
}

function sub_view_graph( ) {
	global $pla, $html;
	
	echo $html->imgsrc($html->param('graph'));
	echo $html->br( ) .
			'[ ' . $html->ahref('#', 'Close Window', array(onclick => "javascript:self.close( )")) .
			' ]';
}

/*
	Stats class (not designed to be called in OO method, but can be)
*/

class phpLedAdsStats
{
	function tenday(&$pla, &$html) {
		$topten_row = $html->tr(
						$html->td(
							$html->b('Date'),
							array(width => '33%', align => 'center', bgcolor => 'black')
						) .
						$html->td(
							$html->b('Displays'),
							array(width => '33%', align => 'center', bgcolor => 'black')
						) .
						$html->td(
							$html->b('Clicks'),
							array(width => '33%', align => 'center', bgcolor => 'black')
						)
					);
		// last 10 days
		for($i = 0; $i < 10; $i++) {
			$time = time( ) - ($i * 86400);
			/*
			$sql = "select impdate, sum(displays) as dtotal, sum(clicks) as ctotal from "
					. $pla->tables['impressions'] .' i, '
					. $pla->tables['ads'] . ' a'
					. " where (i.aid = a.aid) group by impdate order by impdate";
			*/
			$sql = "select impdate, sum(displays) as dtotal, sum(clicks) as ctotal from "
					. $pla->tables['impressions']
					. " where (to_days(impdate) = to_days(from_unixtime($time)))"
					. " group by impdate";

			$result = mysql_query($sql, $pla->conn) or $pla->croak("Mysql Error: ($sql) " . mysql_error( ));

			//while($row = mysql_fetch_object($result)) {
			$row = mysql_fetch_object($result);
			$date = date('Y-m-d', $time);
			$topten_row .= $html->tr(
							$html->td(
								$date,
								array(width => '33%', bgcolor => 'black', align => 'center')
							) .
							$html->td(
								number_format(intval($row->dtotal)),
								array(width => '33%', align => 'right')
							) .
							$html->td(
								number_format(intval($row->ctotal)),
								array(width => '33%', align => 'right')
							)
						);

			$topten_count['displays'] += $row->dtotal;
			$topten_count['clicks'] += $row->ctotal;
			
			$graph[$date] = $row;
			//}
		} // end for( )
		
		// generate a graph
		if($imgs = phpLedAdsStats::_gengraph_topten($graph)) {
			list($img_display, $img_click) = $imgs;
		}

		$topten_row .= $html->tr(
						$html->td(
							$html->b('Totals:'),
							array(width => '33%', align => 'center')
						) .
						$html->td(
							$html->b(number_format($topten_count['displays'])),
							array(width => '33%', align => 'right')
						) .
						$html->td(
							$html->b(number_format($topten_count['clicks'])),
							array(width => '33%', align => 'right')
						)
					);

		$genlink = "javascript:winopen=open('".$pla->me."?%s', 'Graph_%s', 'height=550,width=750,scrollbars=yes');winopen.focus( );";

		if($img_display && $img_click) {
			$topten_row .= $html->tr(
							$html->td(
								$html->b('Graphs:'),
								array(width => '33%', align => 'center')
							) .
							$html->td(
								$html->center(
									'[ ' . $html->ahref(
											sprintf($genlink, 'action=view_graph&graph=' .
												urlencode($img_display), 'display'),
											'View Graph')
									. ' ]'
								),
								array(width => '33%', align => 'right')
							) .
							$html->td(
								$html->center(
									'[ ' . $html->ahref(
											sprintf($genlink, 'action=view_graph&graph=' .
												urlencode($img_click), 'click'),
											'View Graph')
									. ' ]'
								),
								array(width => '33%', align => 'right')
							)
					);
		}
					
		return $html->table($topten_row);
	}
	
	function perad(&$pla, &$html) {
		global $pla_class;
		require_once('ad_class.php');
		
		$highlight = intval($html->param('highlight'));
		
		// we use inner joins to make sure that the ads still exist (and not just their stats)
		// life totals
		$sql = "select a.aid, sum(displays) as displays, sum(clicks) as clicks from "
				. $pla->tables['impressions'] .' i, '
				. $pla->tables['ads'] . ' a'
				. " where (a.aid = i.aid)"
				. " group by aid order by aid";
				
		$result = mysql_query($sql, $pla->conn) or $pla->croak(mysql_error( ));
		while($row = mysql_fetch_object($result)) {
			$life[$row->aid] = $row;
		}

		// yearly stats
		$sql = "select a.aid, sum(displays) as displays, sum(clicks) as clicks from "
				. $pla->tables['impressions'] .' i, '
				. $pla->tables['ads'] . ' a'
				. " where (a.aid = i.aid) and (year(impdate) = year(now( ))) "
				. " group by aid order by aid";
				
		$result = mysql_query($sql, $pla->conn) or $pla->croak(mysql_error( ));
		while($row = mysql_fetch_object($result)) {
			$year[$row->aid] = $row;
		}
		
		// monthly stats
		$sql = "select a.aid, sum(displays) as displays, sum(clicks) as clicks from "
				. $pla->tables['impressions'] .' i, '
				. $pla->tables['ads'] . ' a'
				. " where (a.aid = i.aid) and (year(impdate) = year(now( )))"
				. " and (month(impdate) = month(now( ))) "
				. " group by aid order by aid";
				
		$result = mysql_query($sql, $pla->conn) or $pla->croak(mysql_error( ));
		while($row = mysql_fetch_object($result)) {
			$month[$row->aid] = $row;
		}
		
		// today's stats
		$sql = "select a.aid, sum(displays) as displays, sum(clicks) as clicks from "
				. $pla->tables['impressions'] .' i, '
				. $pla->tables['ads'] . ' a'
				. " where (a.aid = i.aid) and (impdate = now( ))"
				. " group by aid order by aid";
				
		$result = mysql_query($sql, $pla->conn) or $pla->croak(mysql_error( ));
		while($row = mysql_fetch_object($result)) {
			$day[$row->aid] = $row;
		}

		$table = $html->tr(
					$html->td(
						'&nbsp;',
						array(bgcolor => 'black')
					) .
					$html->td(
						$html->b('Displays'),
						array(colspan => 4, align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Clicks'),
						array(colspan => 4, align => 'center', bgcolor => 'black')
					)
			);
		
		$table .= $html->tr(
					$html->td(
						'<u>H|Link|ID</u>',
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Life'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Year'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Month'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Day'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Life'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Year'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Month'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Day'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					)
			);
			
		$sql = "select aid from "
				. $pla->tables['ads']
				. " order by aid";
		$result = mysql_query($sql, $pla->conn) or $pla->croak(mysql_error( ));
		while($row = mysql_fetch_object($result)) {
			$bgcolor = ($row->aid == $highlight) ? '#000066' : null;
			
			list($type, $did) = $pla_class->get_type($row->aid);
			$is_na = ($type == 'rich') ? true : false;
			
			if($type == 'image') {
				$imgresult = mysql_query("select url from " . $pla->tables['images']
											. " where (did = $did)") or $pla->croak(mysql_error( ));
											
				$r = mysql_fetch_object($imgresult);
				$link = $html->ahref($r->url, 'Link', array(target => '_blank'));
				mysql_free_result($imgresult);
			} else {
				$link = 'N/A';
			}
			
			$table .= $html->tr(
					$html->td(
						$html->ahref($pla->me . '?action=stats&highlight=' . $row->aid, 'H') . '|' .
						$link . '|' .
						$html->ahref($pla->me . '?action=edit_ad&aid=' . $row->aid, $row->aid,
							array(target => '_blank')),
						array(width => '10%', align => 'left', bgcolor => 'black')
					) .
					$html->td(
						number_format($life[$row->aid]->displays),
						array(width => '10%', align => 'right', bgcolor => $bgcolor)
					) .
					$html->td(
						number_format($year[$row->aid]->displays),
						array(width => '10%', align => 'right', bgcolor => $bgcolor)
					) .
					$html->td(
						number_format($month[$row->aid]->displays),
						array(width => '10%', align => 'right', bgcolor => $bgcolor)
					) .
					$html->td(
						number_format($day[$row->aid]->displays),
						array(width => '10%', align => 'right', bgcolor => $bgcolor)
					) .
					$html->td(
						($is_na ? 'N/A' : number_format($life[$row->aid]->clicks)),
						array(width => '10%', align => 'right', bgcolor => $bgcolor)
					) .
					$html->td(
						($is_na ? 'N/A' : number_format($year[$row->aid]->clicks)),
						array(width => '10%', align => 'right', bgcolor => $bgcolor)
					) .
					$html->td(
						($is_na ? 'N/A' : number_format($month[$row->aid]->clicks)),
						array(width => '10%', align => 'right', bgcolor => $bgcolor)
					) .
					$html->td(
						($is_na ? 'N/A' : number_format($day[$row->aid]->clicks)),
						array(width => '10%', align => 'right', bgcolor => $bgcolor)
					)
			);
			
		}

		// calc totals
		foreach(array('life', 'year', 'month', 'day') as $val) {
			if(is_array($$val)) {
				foreach($$val as $k => $v) {
					$totals['displays'][$val] += $v->displays;
					$totals['clicks'][$val] += $v->clicks;
				}
			}
		}
		
		// totals
		$table .= $html->tr(
				$html->td(
					$html->b('Totals:'),
					array(width => '10%', align => 'left')
				) .
				$html->td(
					number_format($totals['displays']['life']),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['displays']['year']),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['displays']['month']),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['displays']['day']),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['clicks']['life']),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['clicks']['year']),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['clicks']['month']),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['clicks']['day']),
					array(width => '10%', align => 'right')
				)
		);
			
		return $html->table($table);
	}
	
	function totals(&$pla, &$html) {
		$queries = array(
					'life'	=> 
						"select sum(displays) as displays, sum(clicks) as clicks from "
						. $pla->tables['impressions'],
						
					'year'	=>
						"select sum(displays) as displays, sum(clicks) as clicks from "
						. $pla->tables['impressions']
						. " where (year(impdate) = year(now( )))",
					'month'	=>
							"select sum(displays) as displays, sum(clicks) as clicks from "
						. $pla->tables['impressions']
						. " where (year(impdate) = year(now( ))) and (month(impdate) = month(now( )))",
					'day'	=>
						"select sum(displays) as displays, sum(clicks) as clicks from "
						. $pla->tables['impressions']
						. " where (impdate = now( ))"
				);
				
		foreach($queries as $key => $val) {
			$result = mysql_query($val, $pla->conn) or $pla->croak("Mysql Error: ($sql) " . mysql_error( ));
			if(mysql_num_rows($result) > 0) {
				$totals[$key] = mysql_fetch_object($result);
			}
		}
		
		$table = $html->tr(
					$html->td(
						'&nbsp;',
						array(bgcolor => 'black')
					) .
					$html->td(
						$html->b('Displays'),
						array(colspan => 4, align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Clicks'),
						array(colspan => 4, align => 'center', bgcolor => 'black')
					)
			);
		
		$table .= $html->tr(
					$html->td(
						'<u>Period</u>',
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Life'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Year'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Month'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Day'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Life'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Year'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Month'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					) .
					$html->td(
						$html->b('Day'),
						array(width => '10%', align => 'center', bgcolor => 'black')
					)
			);
			
		$table .= $html->tr(
				$html->td(
					$html->b('Totals:'),
					array(width => '10%', align => 'left')
				) .
				$html->td(
					number_format($totals['life']->displays),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['year']->displays),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['month']->displays),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['day']->displays),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['life']->clicks),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['year']->clicks),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['month']->clicks),
					array(width => '10%', align => 'right')
				) .
				$html->td(
					number_format($totals['day']->clicks),
					array(width => '10%', align => 'right')
				)
		);
		
		return $html->table($table);
	}
	
	/*
		Graphing functions
	*/
	function _gengraph_topten( $data ) {
		global $pla;
		
		if(file_exists('lib/jgraph/jpgraph.php') and file_exists('lib/jgraph/jpgraph_bar.php')) {
			require_once('lib/jgraph/jpgraph.php');
			require_once('lib/jgraph/jpgraph_bar.php');
		} else {
			return false;
		}
		
		// incase its disabled
		if(!$pla->config('do_graphs')) {
			return false;
		}
		
		$data = array_reverse($data, true);
		foreach($data as $key => $val) {
			if(!isset($val->dtotal)) {
				$val->dtotal = 0;
			}
			
			if(!isset($val->ctotal)) {
				$val->ctotal = 0;
			}
			
			$datax[] = $key;
			$d_datay[] = $val->dtotal;
			$c_datay[] = $val->ctotal;
		}

		// clean up old graphs (15 min cache)
		$dir = './data/';
		$fp = opendir($dir);
		while($file = readdir($fp)) {
			if(preg_match('/^(displays|clicks)-.+\.png$/', $file)) {
				if(filemtime($dir . $file) < (time( ) - 900)) {
					@unlink($dir . $file);
				}
			}
		}
		@closedir($fp);

		// checksum the results
		$checksum = md5(serialize($data));

		$names = array(
						'data/displays-' . $checksum . '.png',
						'data/clicks-' . $checksum . '.png',
					);

		// create display graph
		if(file_exists($names[0]) and file_exists($names[1])) {
			// no need to recreate the graph
			return $names;
		}
		
		/*
			Create the graphs!
		*/
		if(!phpLedAdsStats::_create_graph($datax, $d_datay, 'phpLedAds 10-Day Statistics (Displays)', $names[0])) {
			return false;
		}
		
		// create clicks graph
		if(!phpLedAdsStats::_create_graph($datax, $c_datay, 'phpLedAds 10-Day Statistics (Clicks)', $names[1])) {
			return false;
		}
		
		return $names;
	}
	
	/*
		the function that actually creates the graph
		given specific data to create it with
	*/
	function _create_graph( $datax, $datay, $title = '[ No Title ]', $file = null ) {
		if(!extension_loaded('gd')) {
			return false;
		}
		
		// Create the graph. These two calls are always required
		$graph = new Graph(700,400,"auto");	// Graph(width, height, 'auto')
		$graph->img->SetMargin(60,30,20,75); // left, right, top, bottom
		$graph->SetBackgroundImage("background.png", 3);
		$graph->AdjBackgroundImage(0,-.7);
		$graph->SetScale("textlin");
		$graph->SetShadow(true, 2);

		// Create a bar pot
		$bplot = new BarPlot($datay);
		$bplot->SetFillColor("darkblue");
		$bplot->ShowValue(true);
		$bplot->SetValueFormat("%02d");
		$bplot->SetFillGradient("navy","lightsteelblue",GRAD_MIDVER);

		$graph->Add($bplot); // add 'objects'

		$graph->SetMarginColor("silver");

		$graph->title->Set($title);
		$graph->xaxis->SetTickLabels($datax);

		$graph->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

		$graph->xaxis->SetPos("min");
		$graph->xaxis->SetFont(FF_VERDANA,FS_NORMAL,12);
		$graph->yaxis->SetFont(FF_VERDANA,FS_NORMAL,11);
		$graph->yscale->ticks->SupressZeroLabel(true);
		$graph->xaxis->SetTickLabels($datax);
		$graph->xaxis->SetLabelAngle(50);

		// see if we're saving it to a file or what
		if(isset($file) && file_exists($file)) {
			@unlink($file);
		}
		
		// do the work!
		$graph->Stroke($file);
		
		return true;
	}
}

?>