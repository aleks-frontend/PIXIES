#!/usr/bin/perl --
require 5;
use strict;

=head1 copyright

Fluid Dynamics Search Engine

Copyright 1997-2002 by Zoltan Milosevic.  Please adhere to the copyright
notice and conditions of use, described in the attached help file and hosted
at the URL below.  For the latest version and help files, visit:

	http://www.xav.com/scripts/search/

This search engine is managed from the web, and it comes with a password to
keep it secure.  You can set the password when you first visit this script
using the special "Mode=Admin" query string - for example:

	http://my.host.com/search.pl?Mode=Admin

If you edit the source code, you'll find it useful to restore the function comments and #&Assert checks:

	cd "search/searchmods/powerusr/"
	hacksubs.pl build_map
	hacksubs.pl restore_comments
	hacksubs.pl assert_on

<h1>If you can see this text from a web browser, then there is a problem. <a
href="http://www.xav.com/scripts/search/help/1089.html">Get help here.</a></h1><xmp>

=cut

use vars qw( $VERSION %FORM $realms %const %private %Rules @str @sendmail %entity_value_by_name );
(%FORM,$realms,%const,%private,%Rules,@str,@sendmail,%entity_value_by_name) = ();

$VERSION = '2.0.0.0055';

my $all_code = "\n" x 38 . <<'END_OF_FILE';

@sendmail = (
	'/usr/sbin/sendmail -t',
	'/usr/bin/sendmail -t',
	'/usr/lib/sendmail -t',
	'/usr/sendmail -t',
	'/bin/sendmail -t',
	);

local $_;
foreach ('IFS','CDPATH','ENV','BASH_ENV','PATH') {
	delete $ENV{$_} if (defined($ENV{$_}));
	}

binmode(STDOUT);

#changed 0052
my $sn = &query_env('SCRIPT_NAME');
$sn =~ s!^.*/(.+?)$!$1!;

%private = (
	'pdf utility folder' => "",
	'global_lockfile_count' => 1,
	'script_start_time' => time(),
	'visitor_ip_addr' => &query_env('REMOTE_ADDR'),
	'file_mask' => 0666,
	);

$private{'PRINT_HTTP_STATUS_HEADER'} = 0;

%const = (
	'help_file' => 'http://www.xav.com/scripts/search/help/',
	'script_name' => $sn,
	'admin_url' => $sn . '?Mode=Admin',
	'search_url' => $sn,
	'search_url_ex' => $sn . '?',
	'copyright' => '<p align="center"><font size="-2">Powered by the<br /><a href="http://www.xav.com/scripts/search/" target="_blank">Fluid Dynamics<br />Search Engine</a><br />v' . $VERSION . '<br />&copy; 2002</font></p>',
	);



my $err = '';
Err: {

	# Give the folder where all data files are located
	# See http://www.xav.com/scripts/search/help/1138.html about changing this value:

	$err = &load_files_ex( '.' );
	next Err if ($err);


	my $address_offer = '';

	my $terms = $FORM{'Terms'} || $FORM{'terms'} || $FORM{'q'} || '';
	$FORM{'Terms'} = $FORM{'terms'} = $FORM{'q'} = $terms;
	$const{'terms'} = &html_encode($FORM{'Terms'});
	$const{'terms_url'} = &url_encode($FORM{'Terms'});

	#changed 0052 - support persistent fields and secondary queries
	my ($n,$v);
	while (($n,$v) = each %FORM) {
		next unless ($n =~ m!^p:!);
		$const{'search_url_ex'} .= &url_encode($n) . '=' . &url_encode($v) . '&amp;';

		if ($n =~ m!^p:t:!) { $const{$n} = $v; } # changed 0053 - persistent/template namespace

		next unless ($n =~ m!^p:q\d+$!);
		$terms .= ' ' . $v;
		}



	AddressAsTerm: {
		last unless ($Rules{'handling url search terms'} > 1);
		last if ($terms =~ m!\s!);
		my $address = '';
		if ($terms =~ m!^(http|ftp|https|telnet)://(\w+)\.(\w+)(.*)$!) {
			$address = $terms;
			}
		elsif ($terms =~ m!^www\.(\w+)\.(\w+)(.*)$!i) {
			$address = "http://$terms";
			}
		if ($address) {
			$address_offer = &pstr(65, &html_encode($address), &html_encode($address) );
			if ($Rules{'handling url search terms'} == 3) {
				print "HTTP/1.0 302 Moved Temporarily\015\012" if ($private{'PRINT_HTTP_STATUS_HEADER'});
				print "Status: 302 Moved Temporarily\015\012";
				print "Location: $address\015\012";
				print "Content-Type: text/html\015\012\015\012";
				print $address_offer;
				last Err;
				}
			}
		}



	if ($FORM{'NextLink'}) {
		#changed 0034 - fixes bug where NextLink contains &
		if (&query_env('QUERY_STRING') =~ m!^NextLink=(.*)$!) {
			$FORM{'NextLink'} = $1;
			}
		my $html_link = &html_encode($FORM{'NextLink'});
		# security re-director from admin screen (prevents query-string-based
		# password from showing up in referer logs of remote systems:
		print "HTTP/1.0 200 OK\015\012" if ($private{'PRINT_HTTP_STATUS_HEADER'});
		print "Content-Type: text/html\015\012\015\012";
		print qq!<meta http-equiv="refresh" content="0;url=$html_link"></head><a href="$html_link">$html_link</a>!;
		last Err;
		}

	my $Realm = $FORM{'Realm'} || 'All';
	$const{'realm'} = &html_encode($Realm);

	if ($FORM{'Mode'} eq 'Admin') {
		$err = &admin_main();
		next Err if ($err);
		last Err;
		}

	$const{'copyright'} =~ s!<br />! !g;

	# improve perceived snappiness
	$| = 1;

	#changed 0053 - persist user settings via cookies
	my %cookie = ();
	foreach (split(m!;\s*!,&query_env('HTTP_COOKIE'))) {
		next unless (m!^\s*(.*?)=(\d+)\s*$!);
		$cookie{ &url_decode($1) } = &url_decode($2);
		}
	my $cdomain = &query_env('HTTP_HOST');
	if (($cdomain !~ m!^\d+\.\d+\.!) and ($cdomain =~ m!^(\w+)\.(.*)$!) and (length($2) > 5)) {
		$cdomain = lc(".$2");
		}
	foreach ('maxhits','p:pm','Match','p:lang') {
		if (defined($FORM{$_})) { # user has set an explicit value... respect it
			if ((defined($cookie{$_})) and ($cookie{$_} eq $FORM{$_})) {
				# ok, equal, skip it...
				}
			elsif ($FORM{$_} =~ m!^\d+$!) {
				print "Set-Cookie: $_=$FORM{$_}; expires=Mon, 31-Dec-2029 23:59:59 GMT; domain=$cdomain; path=/\015\012";
				}
			}
		# set defaults
		elsif (defined($cookie{$_})) {
			$FORM{$_} = $cookie{$_};
			}
		}
	print "HTTP/1.0 200 OK\015\012" if ($private{'PRINT_HTTP_STATUS_HEADER'});
	print "Content-Type: text/html\015\012\015\012";
	$FORM{'Match'} = $Rules{'default match'} unless ((defined($FORM{'Match'})) and ($FORM{'Match'} =~ m!^(0|1|2)$!)); #fixed 0055 sec
	$FORM{'maxhits'} = $Rules{'hits per page'} unless (($FORM{'maxhits'}) and ($FORM{'maxhits'} =~ m!^\d+$!));

	#changed 0046
	if ($FORM{'Mode'} eq 'SearchForm') {
		print &str_search_form( &html_encode($FORM{'search_url'}) || $const{'search_url'} );
		last Err;
		}


	&PrintTemplate(0, 'header.htm', $Rules{'language'}, \%const);
	$| = 0;

	if ($FORM{'Mode'} eq 'AnonAdd') {
		&anonadd_main();
		}
	elsif (not ($FORM{'Terms'})) {
		$const{'query_example'} = $str[66];
		$const{'url_query_example'} = &url_encode($const{'query_example'});
		print &str_search_form( $const{'search_url'} );
		&PrintTemplate(0, 'tips.htm', $Rules{'language'}, \%const);
		}
	else {

		if ($address_offer) {
			print '<p>' . $address_offer . '</p>';
			}

		my $Rank = 1; # fixed 0055 sec
		if (($FORM{'Rank'}) and ($FORM{'Rank'} =~ m!^(\d+)$!)) {
			$Rank = $1;
			}

		my ($bTermsExist, $Ignored_Terms, $Important_Terms, $DocSearch, $RealmSearch, $where_clause, @SearchTerms) = &parse_search_terms($terms, $FORM{'Match'});

		#changed 0042 - persist maxhits
		my $linkhits = $const{'search_url_ex'} . 'Realm=' . &url_encode($FORM{'Realm'}) . "&amp;Match=$FORM{'Match'}&amp;Terms=" . &url_encode($FORM{'Terms'}) . '&amp;';

		unless ($realms->realm_count('is_runtime')) {
			$linkhits .= 'nocpp=1&amp;';
			}
		if ($FORM{'sort-method'}) {
			$linkhits .= 'sort-method=' . &url_encode($FORM{'sort-method'}) . '&amp;';
			}


		my ($pages_searched, @HITS, $p_realm_data, $DD, $MM, $YYYY, $FBYTES) = (0);

#printf("<h2>Init + prep: user time: %s; system time: %s</h2>", times());

		Search: {
			next Search unless ($bTermsExist);

			#changed 0042 -- added support for include-by-name, fixed runtime under sql

			# include runtime realms:
			if ($Realm eq 'include-by-name') {
				unless ($FORM{'nocpp'}) {
					foreach $p_realm_data ($realms->listrealms('is_runtime')) {
						next unless ($FORM{"Realm:$$p_realm_data{'name'}"});
						$linkhits .= "Realm:$$p_realm_data{'url_name'}=1&amp;";
						$const{'record_realm'} = $$p_realm_data{'url_name'};
						&SearchRunTime($p_realm_data, $DocSearch, \$pages_searched, \@HITS);
						}
					}
				}
			elsif ($Realm eq 'All') {
				unless ($FORM{'nocpp'}) {
					foreach $p_realm_data ($realms->listrealms('is_runtime')) {
						$const{'record_realm'} = $$p_realm_data{'url_name'};
						&SearchRunTime($p_realm_data, $DocSearch, \$pages_searched, \@HITS);
						}
					}
				}
			else {
				($err, $p_realm_data) = $realms->hashref($Realm);
				next Err if ($err);
				if (($$p_realm_data{'is_runtime'}) and (not $FORM{'nocpp'})) {
					$const{'record_realm'} = $$p_realm_data{'url_name'};
					&SearchRunTime($p_realm_data, $DocSearch, \$pages_searched, \@HITS);
					last Search;
					}
				}

			# include indexed realms:

			if ($Rules{'sql: enable'}) {
				if ($Realm eq 'include-by-name') {
					my @id_ok = ();
					foreach $p_realm_data ($realms->listrealms('all')) {
						next unless ($FORM{"Realm:$$p_realm_data{'name'}"});
						$linkhits .= "Realm:$$p_realm_data{'url_name'}=1&amp;";
						push(@id_ok, "(realm_id = $$p_realm_data{'realm_id'})");
						$pages_searched += $$p_realm_data{'pagecount'};
						}
					last Search unless (@id_ok);
					$where_clause .= ' AND (' . join(' OR ', @id_ok) . ')';
					}
				elsif ($Realm ne 'All') {
					($err, $p_realm_data) = $realms->hashref($Realm);
					next Err if ($err);
					$where_clause .= " AND realm_id = $$p_realm_data{'realm_id'}";
					$pages_searched = $$p_realm_data{'pagecount'};
					}
				else {
					foreach $p_realm_data ($realms->listrealms('all')) {
						$pages_searched += $$p_realm_data{'pagecount'};
						}
					}
				$const{'record_realm'} = '';
				&SearchDatabase($where_clause, $DocSearch, \@HITS);
				}
			else {
				if ($Realm eq 'include-by-name') {
					foreach $p_realm_data ($realms->listrealms('has_file')) {
						next unless ($FORM{"Realm:$$p_realm_data{'name'}"});
						$linkhits .= "Realm:$$p_realm_data{'url_name'}=1&amp;";
						$const{'record_realm'} = $$p_realm_data{'url_name'};
						&SearchIndexFile($$p_realm_data{'file'}, $RealmSearch, \$pages_searched, \@HITS);
						}
					}
				elsif ($Realm ne 'All') {
					($err, $p_realm_data) = $realms->hashref($Realm);
					next Err if ($err);
					$const{'record_realm'} = $$p_realm_data{'url_name'};
					&SearchIndexFile($$p_realm_data{'file'}, $RealmSearch, \$pages_searched, \@HITS);
					}
				else {
					foreach $p_realm_data ($realms->listrealms('has_file')) {
						$const{'record_realm'} = $$p_realm_data{'url_name'};
						&SearchIndexFile($$p_realm_data{'file'}, $RealmSearch, \$pages_searched, \@HITS);
						}
					}
				}
			}

#printf("<h2>Search complete: user time: %s; system time: %s</h2>", times());

		my ($HitCount, $PerPage, $Next) = (scalar @HITS, $FORM{'maxhits'}, 0);
		$linkhits .= 'maxhits=' . $PerPage . '&amp;';
		my $Remaining = $HitCount - $Rank - $PerPage + 1;
		my $RangeUpper = $Rank + $PerPage - 1;

		if ($Remaining >= $PerPage) {
			$Next = $PerPage;
			}
		elsif ($Remaining > 0) {
			$Next = $Remaining;
			}
		else {
			$RangeUpper = $HitCount;
			}

		my @Ads = &SelectAdEx(\@SearchTerms);
		print $Ads[0];

		print &str_search_form($const{'search_url'}) if ($Rules{'ui: search form display'} % 2);

		print '<p class="fd_results"><b>' . $str[10] . '</b><br />';

		if ($Ignored_Terms) {
			&ppstr(11, &html_encode($Ignored_Terms));
			}

		if ($HitCount) {
			&ppstr(12, &html_encode($Important_Terms), $pages_searched);
			}
		else {
			&ppstr(13, &html_encode($Important_Terms), $pages_searched);
			}

		print '<br />';

		print $Ads[1];

		PrintHits: {
			if ($HitCount < 1) {
				# print: No documents found
				print qq!</p><p class="fd_results">$str[19]</p>\n!;
				last PrintHits;
				}

			# print: Results $Rank-$RangeUpper of $HitCount
			&ppstr(14, $Rank, $RangeUpper, $HitCount );

			print '</p>';

			my ($jump_sum, $jumptext) = &str_jumptext( $Rank, $PerPage, $HitCount, $linkhits . 'Rank=', 1 );
			# $jump_sum = "Documents 1-10 of 15 displayed."
			# $jumptext = "<p><- Previous 1 2 3 4 5 Next -></p>"

			my $i = $Rank;
			foreach ((sort @HITS)[($Rank-1)..($RangeUpper-1)]) {
				next unless (m!^\d+\.(\d+)\.(\d+)\s*\d*\s*\d* u= (.+) t= (.*?) d= (.*?) c= (.*?) r= (.*?)$!);
				($DD, $MM, $YYYY, $FBYTES) = (unpack('A2A2A2A4A*',$2))[1..4];
				my $relevance = 10E6 - $1;
				print &StandardVersion(
					\@SearchTerms,
					'relevance' => $relevance,
					'redirector' => $Rules{'redirector'},
					'rank' => $i,
					'url' => $3,
					'title' => $4,
					'description' => $5,
					'size' => $FBYTES,
					'dd' => $DD,
					'mm' => $MM,
					'yyyy' => $YYYY,
					'context' => $6,
					'record_realm' => &html_encode(&url_decode($7)),
					);
				$i++;
				}
			print $jump_sum;
			print $jumptext;
			}
		print $Ads[2];
		print &str_search_form($const{'search_url'}) if ($Rules{'ui: search form display'} > 1);
		print $Ads[3];
		&log_search( $Realm, $terms, $Rank, $HitCount, $pages_searched );

#printf("<h2>Display complete: user time: %s; system time: %s</h2>", times());
		}

	if (($Rules{'allowanonadd'}) and ($realms->realm_count('has_no_base_url')) and ($const{'mode'} != 3)) {
		# print: Search Tips - Add New URL - Main Page
		&PrintTemplate(0, 'linkline2.txt', $Rules{'language'}, \%const);
		}
	else {
		# print: Search Tips - Main Page
		&PrintTemplate(0, 'linkline1.txt', $Rules{'language'}, \%const);
		}
	&PrintTemplate(0, 'footer.htm', $Rules{'language'}, \%const);
	last Err;
	}
continue {
	print "HTTP/1.0 200 OK\015\012" if ($private{'PRINT_HTTP_STATUS_HEADER'});
	print "Content-Type: text/html\015\012\015\012";
	print "<p><b>Error:</b> $err.</p>\n";
	}





sub query_env {
	my ($name,$default) = @_;
	if (($ENV{$name}) and ($ENV{$name} =~ m!^(.*)$!s)) {
		return $1;
		}
	elsif (defined($default)) {
		return $default;
		}
	else {
		return '';
		}
	}


sub untaintme {
	my ($p_val) = @_;
	$$p_val = $1 if ($$p_val =~ m!^(.*)$!s);
	}





sub load_files_ex {
	($private{'support_dir'}) = @_;

	my $err = '';
	Err: {

		# This manually sets the current working directory to the directory that
		# contains this script. This is necessary in case people have used a
		# relative path to the $data_files_dir:

		if (($0 =~ m!^(.*)(\\|/)!) and ($0 !~ m!safeperl\d*$!i)) {
			#changed 0045 - added error check
			unless (chdir($1)) {
				$err = "unable to chdir to folder '$1' - $! ($^E)";
				next Err;
				}
			}

		# force forward slashes:
		$private{'support_dir'} =~ s!\\!/!g;
		$private{'support_dir'} .= "/searchdata";
		$private{'support_dir'} =~ s!/+searchdata$!/searchdata!;

		unless (chdir($private{'support_dir'})) {
			$err = "unable to chdir to folder '$private{'support_dir'}' - $! ($^E)";
			next Err;
			}

		@INC = ( '../searchmods', @INC );


		#require
		my $lib = 'common.pl';
		delete $INC{$lib};
		require $lib;
		if (&version_c() ne $VERSION) {
			$err = "the library '$lib' is not version $VERSION";
			next Err;
			}
		#/require

		&ReadInput();



		if ($FORM{'ApproveRealm'}) {
			$FORM{'Realm'} = $FORM{'ApproveRealm'};
			$FORM{'Mode'} = 'Admin';
			$FORM{'Action'} = 'FilterRules';
			$FORM{'subaction'} = 'ShowPending';
			}

		unless ($FORM{'Mode'}) {
			#revcompat - pre-0010
			if ($FORM{'AddSite'}) {
				$FORM{'Mode'} = 'AnonAdd';
				$FORM{'URL'} = $FORM{'AddSite'};
				delete $FORM{'AddSite'};
				}
			#/revcompat
			if ('mode=admin' eq lc(&query_env('QUERY_STRING'))) {
				$FORM{'Mode'} = 'Admin';
				delete $FORM{'mode'};
				}
			}
		#revcompat 0030
		if (($FORM{'Action'}) and ($FORM{'Action'} eq 'ReCrawlRealm')) {
			$FORM{'Action'} = 'rebuild';
			}
		#/revcompat

		my $is_admin_rq = (($FORM{'Mode'}) and (($FORM{'Mode'} eq 'Admin') or ($FORM{'Mode'} eq 'AnonAdd'))) or (&query_env('FDSE_NO_EXEC'));



		$const{'bypass_file_locking'} = (-e 'bypass_file_locking.txt') ? 1 : 0;

		# Can we load the rules?

		my $DEFAULT_LANGUAGE = 'english';

		$err = &LoadRules($DEFAULT_LANGUAGE);
		next Err if ($err);


		#0054 - allow end-user overrides:
		my $language = $Rules{'language'};
		if (defined($FORM{'set:lang'})) {
			$FORM{'p:lang'} = $FORM{'set:lang'};
			delete $FORM{'set:lang'};
			}
		if (($FORM{'p:lang'}) and ($FORM{'p:lang'} =~ m!^(\w+)$!)) {
			$Rules{'language'} = $1;
			}

		#to hard-code a language, uncomment this line:
		# $Rules{'language'} = 'english';


		# init err strings
		$str[44] = 'unable to read from file "$s1" - $s2';

		my $str_file = 'templates/' . $Rules{'language'} . '/strings.txt';
		my $str_text;
		($err, $str_text) = &ReadFileL($str_file);
		next Err if ($err);
		@str = (0);
		my $i = 1;
		foreach (split(m!\n!s,$str_text)) {
			s!(\r|\n|\015|\012)!!sg;
			push(@str,$_);
			unless ($is_admin_rq) {
				last if ($i > 93);#strdepth
				}
			$i++;
			}
		unless (&Trim($str[1]) eq "VERSION $VERSION") {
			$err = "strings file '$str_file' is not version $VERSION ($str[1]).</p><p>Loaded $i strings from file; sample: <xmp>" . substr($str_text,0,128) . "</" . "xmp>";
			next Err;
			}
		$const{'content_type'} = $str[3];
		$const{'language_str'} = $str[2];



		$realms = &fdse_realms_new();
		$realms->use_database($Rules{'sql: enable'});
		$realms->load();

		$const{'is_demo'} = 1 if (-e 'is_demo');

		$const{'mode'} = $Rules{'mode'};
		if (($const{'mode'} == 2) and (not $Rules{'regkey'})) {
			$const{'mode'} = 1;
			}
		$const{'mode'} = 0 if (-e 'is_demo');



		#require
		if (($is_admin_rq) or ($realms->listrealms('is_runtime'))) {
			$lib = 'common_parse_page.pl';
			delete $INC{$lib};
			require $lib;
			if (&version_cpp() ne $VERSION) {
				$err = "the library '$lib' is not version $VERSION";
				next Err;
				}
			}
		if ($is_admin_rq) {
			$lib = 'common_admin.pl';
			delete $INC{$lib};
			require $lib;
			if (&version_ca() ne $VERSION) {
				$err = "the library '$lib' is not version $VERSION";
				next Err;
				}
			}
		#/require


		last Err;
		}
	return $err;
	}

END_OF_FILE

undef($@);
eval $all_code;
if ($@) {
	my $errstr = $@;
	print "Content-Type: text/html\015\012\015\012";
	print "<hr /><p><b>Perl Execution Error</b> in $0:</p><blockquote><xmp>$@</xmp></blockquote>";#<xmp>
	$errstr =~ s!\"!\&quot;!g;
	$errstr =~ s!\<!\&lt;!g;
	$errstr =~ s!\>!\&gt;!g;
print <<"EOM";

<form method="post" action="http://www.xav.com/bug.pl">
<input type="hidden" name="product" value="search">
<input type="hidden" name="version" value="$VERSION">
<input type="hidden" name="Perl Version" value="$]">
<input type="hidden" name="Script Path" value="$0">
<input type="hidden" name="Perl Error" value="$errstr">
EOM

my ($name, $value) = ();
while (($name, $value) = each %FORM) {
	print "<input type=\"hidden\" name=\"Form: $name\" value=\"$value\">\n";
	}
print <<"EOM";

<p>Please report this error to the script author:</p>
<blockquote><input type="submit" value="Report Error"></blockquote>
</form><hr />

EOM

	}
1;
