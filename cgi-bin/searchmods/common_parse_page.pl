use strict;
sub version_cpp {
	return '2.0.0.0055';
	}

=head1 HEAD

Copyright 1997-2002 by Zoltan Milosevic, All Rights Reserved
See http://www.xav.com/scripts/search/ for more information.

If you edit the source code, you'll find it useful to restore the function comments and #&Assert checks:

	cd "search/searchmods/powerusr/"
	hacksubs.pl build_map
	hacksubs.pl restore_comments
	hacksubs.pl assert_on

common_parse_page.pl contains all the functions used to index local documents.  This library is always loaded during admin requests.  This library needs to be available during public search requests, because if there is a runtime realm involved, then searching that realm will require the local indexing functions.

Since the public search code needs to be kept as slim as possible, and because runtime realms are rare, the search code will optimize away from loading this library if no runtime realms are detected.

=cut





sub load_custom_metadata {
	my ($url, $p_metadata) = @_;
	my $err = '';
	Err: {
		my %custom = ();
		if (dbmopen( %custom, 'custom_metadata', 0666 )) {
			if (defined($custom{$url})) {
				my $data = $custom{$url};
				my $pair;
				foreach $pair (split(m! !s, $data)) {
					next unless ($pair =~ m!^(.+?)=(.*)$!s);
					$$p_metadata{$1} = &url_decode($2);
					}
				}
			}
		last Err;
		}
	return $err;
	};



sub convert_mp3_to_text {
	my ($text,$filename) = @_;
	my $err = '';
	my $content_type = 'audio/mpeg';
	Err: {
		$text = substr($text, -129);
		my ($tag, $title, $artist, $album, $year, $comment) = unpack('A3A30A30A30A4A30', $text);
		if ($tag ne 'TAG') {
			$filename = $1 if ($filename =~ m!^.*/(.*?)$!);
			$text = $filename;
			}
		else {
			$artist = "$2 $1" if ($artist =~ m!^(.*), (the)$!i);
			$text = "<TITLE>$artist - $title.mp3</TITLE><META NAME=description CONTENT=\"";
			if (($album) and ($year)) {
				$text .= "From &quot;$album&quot;, $year. ";
				}
			elsif ($album) {
				$text .= "From &quot;$album&quot;. ";
				}
			elsif ($year) {
				$text .= "$year. ";
				}
			if (($artist) and ($title)) {
				$text .= "&quot;$title&quot; by $artist. ";
				}
			elsif ($artist) {
				$text .= "By $artist. ";
				}
			else {
				$text .= "Song &quot;$title&quot;. ";
				}
			if ($comment) {
				$text .= "$comment.";
				}
			$text .= "\"> $album $year $title $artist $comment ";
			}
		}
	return $text;
	}





sub convert_pdf_to_text {
	my ($text) = @_;

	my $content_type = 'application/pdf';

	my $err = '';
	Err: {

		last Err unless ($private{'pdf utility folder'});

		unless (-e $private{'pdf utility folder'}) {
			$err = "PDF utility folder '$private{'pdf utility folder'}' does not exist";
			next Err;
			}

		# create a temp file with a random name

		my $tempname = 'temp' . $$ . rand() . '.pdf';


		$err = &WriteFile($tempname, $text);
		next Err if ($err);

		my $headtext = '';
		my $body = '';

		block: {
			local $/ = undef();
			my $cmd = "$private{'pdf utility folder'}pdfinfo $tempname";
			print "<h2><xmp>Running command '$cmd'</xmp></h2>\n" if ($FORM{'debug'});
			$headtext = `$cmd`;
			print "<h2><xmp>Output follows:\n$headtext\nEnd output</xmp></h2>\n" if ($FORM{'debug'});
			$cmd = "$private{'pdf utility folder'}pdftotext -raw $tempname -";
			print "<h2><xmp>Running command '$cmd'</xmp></h2>\n" if ($FORM{'debug'});
			$body = `$cmd`;
			print "<h2><xmp>Output follows:\n$body\nEnd output</xmp></h2>\n" if ($FORM{'debug'});
			}

		unless (unlink($tempname)) {
			$err = &pstr(54, $tempname, $!);
			next Err;
			}

		my $headers = '';
		foreach (split(m!\n!s, $headtext)) {
			next unless (m!^(.*?)\:\s*(.+?)$!);
			my ($name, $value) = (&Trim($1), &Trim($2));
			next unless ($value);
			if (lc($name) eq 'title') {
				$headers .= "\t<TITLE>$value</TITLE>\n";
				}
			else {
				$headers .= "\t<META HTTP-EQUIV=\"$name\" CONTENT=\"$value\">\n";
				}
			}
		$body = &html_encode($body);

		$text = "<HTML> <HEAD>$headers</HEAD> <BODY><PRE>$body</PRE></BODY> </HTML>";

		$content_type = 'text/html';

		last Err;
		}
	return ($err, $content_type, $text);
	}





sub parse_meta_header {
	my ($p_text, $name) = @_;
	my $value = '';
	$name = quotemeta($name);
	#&Assert('SCALAR' eq ref($p_text));
	#&Assert($name);

#changed 0054 - allow meta="foo"content="bar" w/o intervening whitespace

	if ($$p_text =~ m!^.{0,4096}<meta\s+(http-equiv|name)\s*=\s*\"?\'?fdse-$name\s*?(\"|\'|\s)([^\>]*?)content\s*=\s*(.*?)\s*/?>!is) {
		$value = $4;
		}
	elsif ($$p_text =~ m!^.{0,4096}<meta\s+content\s*=\s*(.*?)\s+(name|http-equiv)\s*=\s*\"?\'?fdse-$name\s*?(\"|\')?\s*/?>!is) {
		$value = $1;
		}
	elsif ($$p_text =~ m!^.{0,4096}<meta\s+(http-equiv|name)\s*=\s*\"?\'?$name\s*?(\"|\'|\s)([^\>]*?)content\s*=\s*(.*?)\s*/?>!is) {
		$value = $4;
		}
	elsif ($$p_text =~ m!^.{0,4096}<meta\s+content\s*=\s*(.*?)\s+(name|http-equiv)\s*=\s*\"?\'?$name\s*?(\"|\')?\s*/?>!is) {
		$value = $1;
		}
	$value = &Trim($value);
	if ($value =~ m!^\"(.*)\"!s) {
		$value = $1;
		}
	elsif ($value =~ m!^\'(.*)\'!s) {
		$value = $1;
		}
	return $value;
	}





sub ParseRobotFile {
	local $_;
	my ($RobotText, $my_user_agent) = @_;
	my @forbidden_paths = ();
	my @star_paths = ();


	my $applies = 0; # 0 => not me; 1 => me by substr match; 2 => me by * match (substr match wins over *)
	my $is_ua_bloc = 0;
	my $ua_is_god = 0;

	foreach (split(m!\015|\012!, $RobotText)) {
		if (m!^user-agent:([^\#]*)!i) {

			if ($is_ua_bloc == 0) {
				# we are at the start of a new UA block

				# do we already have a perfectly good substr match? first one wins
				last if ($applies == 1);
				$ua_is_god = 0;
				$is_ua_bloc = 1;
				$applies = 0;
				}
			my $agent = &Trim($1);
			next unless ($agent);
			$agent = quotemeta($agent);
			#changed 0051; now matching on bare "fdse" instead of "fdse robot"
			if (($my_user_agent =~ m!$agent!i) or ('fdse' =~ m!$agent!i)) {
				# ua:fdse overrides any other $applies value
				$applies = 1;
				}
			elsif (($applies == 0) and ($agent eq '\*')) {
				# ua:* overrides no-match but not a substr match
				$applies = 2;
				}
			}
		elsif ((not $ua_is_god) and (m!^disallow:([^\#]*)!i)) {
			$is_ua_bloc = 0;
			next unless ($applies);
			my $forbidden_path = &clean_path($1);
			if ($forbidden_path eq '') {
				# null lines mean we are god, nuff said:
				$ua_is_god = 1;
				# clear any current data:
				if ($applies == 1) {
					@forbidden_paths = ();
					}
				else {
					@star_paths = ();
					}
				}
			elsif ($applies == 1) {
				push(@forbidden_paths, $forbidden_path);
				}
			else {
				push(@star_paths, $forbidden_path);
				}
			}
		}
	if ($applies == 1) {
		# okay, we had a substr match
		return @forbidden_paths;
		}
	else {
		# return whatever was present for * or nothing
		return @star_paths;
		}
	}





sub GetAbsoluteAddress {
	my ($link_fragment, $full_url_context) = @_;
	my $abs_url = '';
	if (($link_fragment =~ m!^/!) and ($full_url_context =~ m!^http://([^/]+)!i)) {
		# absolute link from top-level directory:
		$abs_url = "http://$1$link_fragment";
		}
	elsif (($link_fragment =~ m!^(\w+)\:!) and ($1 !~ m!^http$!i)) {
		# not http protocol (mailto:, ftp:, etc)
		$abs_url = '';
		}
	elsif (($link_fragment !~ m!^http://!i) and ($full_url_context =~ m!^(.*)/!)) {
		# http: protocol not specified, some kind of internal link, but we know
		# it doesn't start with "/" so it's relative.
		$abs_url = $1.'/'.$link_fragment;
		}
	else {
		$abs_url = $link_fragment;
		}
	return (&parse_url_ex($abs_url))[1];
	}





sub Capitalize {
	my $Text = defined($_[0]) ? $_[0] : '';
	my ($NewText, $Word, $NonWord) = ('');
	my @NoCaps = ('the', 'an', 'a', 'of', 'and', 'or'); #changed 0027 - using array not qw{}
	my $b_first_word = 1;
	while ($Text =~ m!^([\w|\'|\-]*)(\W*)(.*?)$!) {
		($Word, $NonWord, $Text) = (lc($1), $2, $3);
		last unless ($Word or $NonWord);
		my $qm_Word = quotemeta($Word);
		$Word = ucfirst($Word) unless ((grep {m!^$qm_Word$!} @NoCaps) and (not $b_first_word));
		$NewText .= $Word . $NonWord;
		$b_first_word = ($NonWord =~ m![\:|\.|\!|\?|\-]!);
		}
	return $NewText;
	}





sub SearchRunTime {
	local $_;
	my ($p_realm_data, $DocSearch, $r_pages_searched, $r_hits) = @_;

	my $err = '';
	Err: {
		my $URL = '';

		my @WordCount = ();
		my ($WordMatches, $sort_num, $t, $d, $k, $hdr, $n_context_matches, $context_str, $delta, $text);

		my ($title, $description) = ();

		undef($@);

		my $fr = &fdse_filter_rules_new($p_realm_data);

		my $gf = &GetFiles_new();

		$err = $gf->create_file_list(
			'base_dir' => $$p_realm_data{'base_dir'},
			'base_url' => $$p_realm_data{'base_url'},
			'fr'       => \$fr,
			'tempfile' => "runtime.file_list. " . int(10000 * rand()) . ".txt",
			'verbose' => 0,
			);
		next Err if ($err);

		my $count = 0;
		my $record_err_msg = '';
		while (1) {
			my ($lastmodt, $size, $fullfile, $basefile, $url) = $gf->get_next_file();
			last unless ($url);

			my %pagedata = ();
			($record_err_msg, $url) = &pagedata_from_file( $fullfile, $url, \%pagedata, \$fr );
			next if ($record_err_msg);

			($record_err_msg, $_) = &text_record_from_hash( \%pagedata );
			next if ($record_err_msg);

			eval($DocSearch);
			die($@) if ($@);
			}
		$err = $gf->quit(0);
		next Err if ($err);
		last Err;
		}
	continue {
		&ppstr(64,$err);
		}
	}





sub check_parse_patterns {
	my ($doctext, $p_metadata) = @_;
	my $err = '';
	Err: {
		last Err unless (-s 'parse_patterns.txt');
		my $text = '';
		($err, $text) = &ReadFileL('parse_patterns.txt');
		next Err if ($err);
		my $rule_str;
		foreach $rule_str (split(m!\r|\n|\015|\012!s, $text)) {
			next if ($rule_str =~ m!^\s*$!s);
			my @fields = split(m!,!s, $rule_str);
			my ($pattern, $index, $key) = (&url_decode($fields[0]), $fields[1], $fields[2]);
			next if ($$p_metadata{$key}); # first one wins
			$err = &check_regex($pattern);
			next Err if ($err);
			if ($doctext =~ m!$pattern!is) {
				my @out = ('', $1, $2, $3, $4, $5, $6, $7, $8, $9);
				$$p_metadata{ $key } = $out[$index];
				$$p_metadata{ $key } = ' ' if ($$p_metadata{ $key } eq ''); # so that we can pass as if ($xx)
				}
			}
		last Err;
		}
	continue {
		&ppstr(64,$err);
		}
	};





sub html_decode {
	local $_ = $_[0] || '';
	s!&lt;!<!sig;
	s!&gt;!>!sig;
	s!&quot;!\"!sig;
	s!&amp;!&!sig;
	return $_;
	};






sub parse_html_ex {
	my ($HTML_Text, $URL, $b_SaveLinks, $r_link_array, $p_pagedata) = @_;

	#&Assert('HASH' eq ref($p_pagedata));

	local $_;
	if ($b_SaveLinks) {
		#&Assert('ARRAY' eq ref($r_link_array));
		}

	# replace line breaks with spaces:
	$HTML_Text =~ tr!\r\n\t!   !;

	# replace high spaces with \s:
	my $high_space = chr(160);
	$HTML_Text =~ s!$high_space! !og;

	# Initialize return values:
	foreach ('title', 'description', 'keywords', 'text', 'links') {
		$$p_pagedata{$_} = '';
		}

	# strip unwanted portions of the HTML:
	$HTML_Text =~ s!(<script.*?</script>|<style.*?</style>|<FDSE:ROBOTS?\s+VALUE=\"?none\"?>.*?</FDSE:ROBOTS?>|<\!--\s*robots?\s+content\s*=\s*\"?none\"?\s*-->.*?<\!--\s*/robots?\s*-->)! !ig;

	# extract links:
	if (($b_SaveLinks) or ($Rules{'index links'})) {

		# This critical chunk of code parses all of the links out of an HTML file, saving them
		# for later crawling, or for indexing as part of the record for the given HTML file.

		my $str_base = $URL;
		my $str_text = $HTML_Text;

		#changed 0031 - Remove sections blocked by FDSE:ROBOT tags:
		$str_text =~ s!(<FDSE:ROBOTS?\s+VALUE=\"?nofollow\"?>.*?</FDSE:ROBOTS?>|<\!--\s*robots?\s+content\s*=\s*\"?nofollow\"?\s*-->.*?<\!--\s*/robots?\s*-->)! !ig;

		# remove remaining comment tags:
		$str_text =~ s!<\!--.*?-->! !g;

		my $hostname = '';
		$hostname = $1 if ($URL =~ m!^http://(.*?)/!);

		my ($core_tag, $attribs) = ();

		my @links = split(m!<(A|FRAME|IFRAME|BASE|AREA) (.*?)>!i, $str_text);

		my $x = 1;
		for ($x = 1; $x < $#links; $x += 3) {

			($core_tag, $attribs) = ($links[$x], $links[$x+1]);

			my $ThisLink = '';

			# double-quoted attribute:
			if ($attribs =~ m!(HREF|SRC)\s*=\s*\"([^\"]+)!is) {
				$ThisLink = $2;
				}
			# single-quoted attribute:
			elsif ($attribs =~ m!(HREF|SRC)\s*=\s*\'([^\']+)!is) {
				$ThisLink = $2;
				}
			# unquoted attribute:
			elsif ($attribs =~ m!(HREF|SRC)\s*=\s*(\S+)!is) {
				$ThisLink = $2;
				}
			else {
				next;
				}
			$ThisLink = &Trim($ThisLink);

			if (lc($core_tag) eq 'base') {
				$str_base = &GetAbsoluteAddress($ThisLink, $URL);
				next;
				}

			$$p_pagedata{'links'} .= ' '.$ThisLink if ($Rules{'index links'});
			next unless $b_SaveLinks;
			next if ((not $Rules{'crawler: follow query strings'}) and ($ThisLink =~ m!\?!));

			$ThisLink = &GetAbsoluteAddress($ThisLink, $str_base);
			# skip if GetAbsoluteAddress failed:
			next unless ($ThisLink);


			# insert URL rewrite level 0

			$ThisLink = &rewrite_url( 0, $ThisLink );


			# skip file types that aren't interesting:
			next if ($const{'pattern_is_ignored_extension'} and ($ThisLink =~ m!$const{'pattern_is_ignored_extension'}!i));

			unless ($Rules{'crawler: follow offsite links'}) {
				# skip remote links:
				unless ($ThisLink =~ m!^http://$hostname/!) {
					next;
					}
				}

			# changed 0054 - decode:
			$ThisLink = &html_decode($ThisLink);

			# skip long addresses:
			next if (length($ThisLink) > $Rules{'max characters: url'});
			if (($r_link_array) and ('ARRAY' eq ref($r_link_array))) {
				push(@$r_link_array, $ThisLink);
				}
			}
		}

	#changed 0031 - Remove sections blocked by FDSE:ROBOT tags:
	$HTML_Text =~ s!(<FDSE:ROBOTS?\s+VALUE=\"?noindex\"?>.*?</FDSE:ROBOTS?>|<\!--\s*robots?\s+content\s*=\s*\"?noindex\"?\s*-->.*?<\!--\s*/robots?\s*-->)! !ig;

	# remove remaining comment tags:
	$HTML_Text =~ s!<\!--.*?-->! !sg;

	# strip any noframes text that is less than 2 kb in size (mostly these are simple messages telling user to get a frames-capable browser)
	$HTML_Text =~ s!\<noframes.*?\>.{0,1023}\</noframes\>! !ig;#changed 0054, moved to 1023 bytes not 2048

	if ($Rules{'index alt text'}) {
		$HTML_Text =~ s!\<[^\>]* ALT\s*=\s*"(([^\>"])*)".*?\>! $1 !sig;
		$HTML_Text =~ s!\<[^\>]* TITLE\s*=\s*"(([^\>"])*)".*?\>! $1 !sig;
		}

	&check_parse_patterns($HTML_Text, $p_pagedata);


	# seed with normal values if the parse_patterns didn't bear fruit:

	unless ($$p_pagedata{'title'}) {
		if ($HTML_Text =~ m!\<title.*?\>([^\>\<]+?)\<!is) {
			$$p_pagedata{'title'} = &Trim($1);
			}
		unless ($$p_pagedata{'title'}) {
			if ($URL =~ m!([^/]+)$!) {
				$$p_pagedata{'title'} = $1;
				}
			else {
				$$p_pagedata{'title'} = 'Document';
				}
			}
		}

	unless ($$p_pagedata{'keywords'}) {
		$$p_pagedata{'keywords'} = &parse_meta_header(\$HTML_Text, 'keywords');
		}

	unless ($$p_pagedata{'description'}) {
		$$p_pagedata{'description'} = &parse_meta_header(\$HTML_Text, 'description');
		}


	# metadata wins all fights:
	my $err = &load_custom_metadata($URL, $p_pagedata);
	if ($err) {
		&ppstr(64,$err);
		$err = '';
		}


	$HTML_Text =~ s!\<title.*?\>.*?\<.*?\>! !is;


	$$p_pagedata{'title'} = substr($$p_pagedata{'title'}, 0, $Rules{'max characters: title'});
	if (($Rules{'forbid all cap titles'}) and not ($$p_pagedata{'title'} =~ m![a-z]!)) {
		$$p_pagedata{'title'} = &Capitalize($$p_pagedata{'title'});
		}

	if ($$p_pagedata{'keywords'}) {
		#changed 0042 - strip duplicates
		my %seen = ();

		if (length($$p_pagedata{'keywords'}) > $Rules{'max characters: keywords'}) {
			$$p_pagedata{'keywords'} = substr($$p_pagedata{'keywords'}, 0, $Rules{'max characters: keywords'});
			$$p_pagedata{'keywords'} =~ s!\s+\S+$!!; # strip trailing word fragment
			}

		foreach (split(m!\s+!,$$p_pagedata{'keywords'})) {
			$seen{$_} = 1;
			}
		$$p_pagedata{'keywords'} = join(' ', sort keys %seen);
		}



	# replace some HTML tags with null space:
	$HTML_Text =~ s!\</?(b|tt|i|em|strong|big|small|font|span)>!!ig;
	$HTML_Text =~ s!\</?(b|tt|i|em|strong|big|small|font|span)\W.*?>!!ig;

	# replace remaining HTML tags with space (word boundary):
	$HTML_Text =~ s!\<.*?\>! !g;

	# Now all multiple spaces become one space:
	$HTML_Text =~ s!\s+! !g;

	$HTML_Text = substr($HTML_Text, 0, $Rules{'max characters: text'});

	unless ($$p_pagedata{'description'}) {
		my $text = substr($HTML_Text, 0, $Rules{'max characters: auto description'});
		if ($text =~ m!^(.*)\s!) {
			$$p_pagedata{'description'} = $1 . '...';
			}
		else {
			$$p_pagedata{'description'} = 'No description available.';
			}
		}
	if (($Rules{'forbid all cap descriptions'}) and ($$p_pagedata{'description'} !~ m![a-z]!)) {
		$$p_pagedata{'description'} = &Capitalize($$p_pagedata{'description'});
		}

	$$p_pagedata{'description'} = substr($$p_pagedata{'description'}, 0, $Rules{'max characters: description'});

	foreach ('title', 'description', 'keywords') {
		my $val = $$p_pagedata{$_};
		$val =~ tr!\<\>\=!   !;
		$val =~ s!\&nbsp;! !ig;
		$val =~ s!\s+! !g;
		$$p_pagedata{$_} = &Trim($val);
		}

	$$p_pagedata{'text'} = $HTML_Text;
	}





sub pagedata_from_file {
	my ($file, $URL, $p_pagedata, $p_fr) = @_;
	my $err = '';
	Err: {

		#&Assert('HASH' eq ref($p_pagedata));

		($$p_pagedata{'size'},$$p_pagedata{'lastmodtime'}) = (stat($file))[7,9];

		my $text = '';


		my $b_is_binary = (-T $file) ? 0 : 1;

		if ($file =~ m!\.pdf$!i) {

			$b_is_binary = 1;

			($err, $text) = &ReadFileL($file);
			next Err if ($err);

			my $ct = '';
			($err, $ct, $text) = &convert_pdf_to_text($text);
			next Err if ($err);

			$b_is_binary = 0 if ($ct eq 'text/html');

			$text = substr($text, 0, $Rules{'max characters: file'}) if ($Rules{'max characters: file'});

			}

		#changed 0052
		elsif ($file =~ m!\.mp3$!i) {
			# read in final 128 bytes
			if (open(FILE, "<$file")) {
				binmode(FILE);
				seek(FILE, -128, 2);
				read(FILE,$text,128);
				close(FILE);
				}
			$text = &convert_mp3_to_text($text, $file);
			$text = substr($text, 0, $Rules{'max characters: file'}) if ($Rules{'max characters: file'});
			$b_is_binary = 0;
			}
		else {
			unless (open(FILE,"<$file")) {
				$err = &pstr(44,$file,$!);
				next Err;
				}
			unless (binmode(FILE)) {
				$err = &pstr(39,$file,$!);
				next Err;
				}
			my $readsize = $$p_pagedata{'size'};
			$readsize = $Rules{'max characters: file'} if (($Rules{'max characters: file'}) and ($readsize > $Rules{'max characters: file'}));
			my $read_ok = read(FILE,$text,$readsize);
			close(FILE);
			if ($read_ok != $readsize) {
				$err = &pstr(47,$file,$read_ok,$readsize);
				next Err;
				}
			}

		my $index_as = '';
		my $lastmodt = 0;

		($err, $index_as, $lastmodt) = (&process_text( \$text, $URL, $b_is_binary, $$p_pagedata{'size'} ))[0,5,6];
		next Err if ($err);

		$$p_pagedata{'lastmodtime'} = $lastmodt if ($lastmodt);

		$URL = $index_as;

		($err,$URL) = &parse_url_ex($URL);

		$$p_pagedata{'url'} = $URL;
		$$p_pagedata{'lastindex'} = time();

		$text = '' if ($b_is_binary);


		my ($is_denied, $require_approval, $promote_val, $filter_err_msg, $no_update_on_redirect, $b_index_nofollow, $b_follow_noindex) = ();
		($is_denied, $require_approval, $promote_val, $filter_err_msg, $no_update_on_redirect, $b_index_nofollow, $b_follow_noindex) = $$p_fr->check_filter_rules( $URL, $text, 0);
		if ($is_denied) {
			$err = $filter_err_msg;
			next Err;
			}
		# ignore require approval at this level


		if ($b_follow_noindex) {
			$err = $str[87];
			next Err;
			}

		$$p_pagedata{'promote'} = $promote_val;



		&parse_html_ex( $text, $URL, 0, 0, $p_pagedata );
		&compress_hash( $p_pagedata );
		}
	return ($err, $URL);
	}





sub process_text {
	my ($ref_text, $url, $b_is_binary, $size_override) = @_;
	my ($err, $no_index_but_follow, $no_follow, $is_redirect, $full_redir_url, $index_as, $lastmodt) = ('', 0, 0, 0, '', $url, 0);
	Err: {

		#&Assert('SCALAR' eq ref($ref_text));

		my $value = '';

		$value = &parse_meta_header( $ref_text, 'refresh' );
		if (($value) and ($value =~ m!(\d+);\s*url=(.*)!i)) {
			my ($TimeDelay, $NextPage) = ($1, $2);
			if ($TimeDelay < 10) {
				$full_redir_url = &GetAbsoluteAddress($NextPage, $url);
				if ($full_redir_url) {
					$err = &pstr(80, $full_redir_url );
					$is_redirect = 1;
					next Err;
					}
				}
			}

		$value = &parse_meta_header( $ref_text, 'robots' );
		if (($value) and (not ($Rules{'crawler: rogue'}))) {
			$no_index_but_follow = (($value !~ m!(nofollow|none)!i) and ($value =~ m!noindex!i)) ? 1 : 0;
			$no_follow = ($value =~ m!(nofollow|none)!i) ? 1: 0;
			if ($value =~ m!(none|noindex)!i) {
				$err = $str[81];
				next Err;
				}
			}

		if ($Rules{'parse fdse-index-as header'}) {
			$value = &parse_meta_header( $ref_text, 'fdse-index-as' );
			if ($value) {
				my ($pux_err,$clean_url) = &parse_url_ex($value);
				unless ($pux_err) {
					$index_as = $clean_url;
					}
				}
			}

		$value = &parse_meta_header($ref_text,'last-modified');
		if (($value) and ($value =~ m!(\d+)(\s+|-)(\w\w\w)(\s+|-)(\d+)\s+(\d+)\:(\d+)\:?(\d*)!)) {
			my ($mday, $mon, $year, $hours, $min, $sec) = ($1,$3,$5,$6,$7,$8 || 0);
			$lastmodt = &timegm($sec,$min,$hours,$mday,$mon,$year);
			}

		my $size = $size_override || length($$ref_text);

		my $min_size = $Rules{'minimum page size'};
		if ($min_size > $size) {
			$err = &pstr(79, $size, $min_size );
			next Err;
			}

		unless ($b_is_binary) {
			my $min_whitespace_ratio = $Rules{'crawler: minimum whitespace'};
			my @count = ();
			my $whitespace_size = scalar (@count = ($$ref_text =~ m! !sog));
			if (length($$ref_text) > 0) {
				my $ws_ratio = $whitespace_size / length($$ref_text);
				if ($min_whitespace_ratio > $ws_ratio) {
					$err = &pstr(78, &FormatNumber( $ws_ratio, 3, 1, 0, 1, $Rules{'ui: number format'} ), $min_whitespace_ratio );
					next Err;
					}
				}
			}
		}
	return ($err, $no_index_but_follow, $no_follow, $is_redirect, $full_redir_url, $index_as, $lastmodt);
	}





sub GetFiles_new {
	my $self = {};
	bless($self);
	return $self;
	}





sub create_file_list {
	my ($self, %params) = @_;
	my $err = '';
	Err: {
		local $_;
		foreach ('base_dir', 'base_url', 'fr', 'tempfile') {
			next if (defined($params{$_}));
			$err = &pstr(21,$_);
			next Err;
			}
		# Strip trailing slashes:
		$params{'base_dir'} =~ s!/$!!o;
		$params{'base_url'} =~ s!/$!!o;
		$self->{'base_dir'} = $params{'base_dir'};
		$self->{'base_url'} = $params{'base_url'};
		$self->{'tempfile'} = $params{'tempfile'};

		my ($filecount,$obj, $p_rhandle, $p_whandle) = (-1);

		BuildTempList: {
			my $max_age = $params{'no_older_than'} || 0;

			if ($max_age) {
				# okay, we'll allow older files...
				if (-e $params{'tempfile'}) {

					my $age = 86400 * (-M $params{'tempfile'});
					last BuildTempList if ($age <= $max_age);
					}
				}


			my $p_fr = $params{'fr'};

			$obj = &LockFile_new(
				'create_if_needed' => 1,
				);
			($err, $p_rhandle, $p_whandle) = $obj->ReadWrite( $params{'tempfile'} );
			next Err if ($err);

			my $lc_ext = lc(" $Rules{'ext'} ");

			my @ForbiddenPaths = ();

			# Should we be parsing a local robots.txt file?
			# Find out how many folders deep we are from the base directory
			if ($params{'base_url'} =~ m!^http://([^\/]+)(.*)$!) {
				my $path = $2;
				my $depth = 1 * scalar ($path =~ s!/!/!g);

				my $robotfile = $params{'base_dir'} . ('/..' x $depth) . '/robots.txt';

				# Yes, we should...

				if (open(FILE, "<$robotfile")) {
					binmode(FILE);
					my $RobotText = join('', <FILE>);
					close(FILE);
					my $qm_path = quotemeta($path);
					# returns an array of absolute paths, like /images or /foo:
					foreach (&ParseRobotFile($RobotText, $Rules{'crawler: user agent'})) {
						next unless (m!^$qm_path(.*)$!i);
						my $fpath = quotemeta("$params{'base_dir'}$1");
						push(@ForbiddenPaths, $fpath);
						}
					}
				}

			# Hold names of symbolic links:
			my %SymLinks;

			$filecount = $self->_handle_folder('.', $p_whandle, $p_fr, $params{'base_dir'}, $params{'base_url'}, \%SymLinks, $lc_ext, \@ForbiddenPaths, $params{'verbose'});

			$err = $obj->Merge();
			next Err if ($err);
			}
		# Create a read handle on the temp file and save it:
		$obj = &LockFile_new();
		($err, $p_rhandle) = $obj->Read($params{'tempfile'});
		next Err if ($err);

		if ($filecount == -1) {
			# well... go ahead and count the lines in the cachefile...
			#changed 0045 -- only count valid files
			$filecount = 0;
			while (defined($_ = readline($$p_rhandle))) {
				next unless (m!^\d+\t\d+\t.*?\t0\t.*?\t!);
				$filecount++;
				}
			unless (seek($$p_rhandle,0,0)) {
				$err = &pstr(72,0,$params{'tempfile'},$!);
				next Err;
				}
			}


		$self->{'count'} = $filecount;
		$self->{'obj'} = $obj;
		$self->{'p_rhandle'} = $p_rhandle;
		}
	return $err;
	}





sub _handle_folder {
	my ($self, $rel_folder_path, $p_whandle, $p_fr, $base_dir, $base_url, $p_symlinks, $lc_ext, $p_ForbiddenPaths, $b_verbose) = @_;
	my $count = 0;

	Err: {
		my $time = 0;

		my $fpath = '';

		my $abs_folder_path = $base_dir;
		if ($rel_folder_path ne '.') {
			$abs_folder_path .= "/$rel_folder_path";
			}
		my $rel_file_path = '';
		if ($rel_folder_path ne '.') {
			$rel_file_path = "$rel_folder_path/";
			}

		&ppstr(89,&html_encode($abs_folder_path)) if ($b_verbose);

		unless (opendir(DIR, $abs_folder_path)) {
			print { $$p_whandle } "$time\t0\t$rel_folder_path\t2\tcould not open folder '$abs_folder_path' - $!\t\n";
			print "-&gt; $str[73]: " . &pstr(91, $abs_folder_path, $!) . "<br />\n" if ($b_verbose);
			next Err;
			}
		print { $$p_whandle } "$time\t0\t$rel_folder_path\t2\t\t\n";

		FolderEntry: foreach (sort (readdir(DIR))) {
			$time = 0;

			# Skip, for the . and .. entries:
			next FolderEntry if (m!^\.\.?$!);

			# Build the absolute file name:

			my $FullFileName = "$abs_folder_path/$_";

			foreach $fpath (@$p_ForbiddenPaths) {
				if ($FullFileName =~ m!^$fpath!i) {
					&ppstr(64,&pstr(90, 'robots.txt', $FullFileName)) if ($b_verbose);
					next FolderEntry;
					}
				}

			my $basename = $rel_file_path . $_;

			my ($is_denied, $require_approval, $promote_val, $filter_err_msg, $no_update_on_redirect, $b_index_nofollow, $b_follow_noindex) = $$p_fr->check_filter_rules( "$base_url/$basename", '', 1);
			if (($is_denied) or ($b_follow_noindex)) {
				$filter_err_msg = $str[87] if ($b_follow_noindex);

				if ($b_verbose) {
					&ppstr(89,&html_encode($FullFileName));
					&ppstr(64,$filter_err_msg);
					}


				print { $$p_whandle } "$time\t0\t$basename\t4\t$filter_err_msg\t\n";
				next FolderEntry;
				}

			$time = (stat($FullFileName))[9];

			# If this is a folder, store for a later loop:
			if (-d _) {

				# Is this a symlink?
				if (-l $FullFileName) {
					unless ($Rules{'allowsymboliclinks'}) {
						print { $$p_whandle } "$time\t0\t$basename\t3\tallow symbolic links is false\t\n";
						if ($b_verbose) {
							&ppstr(89,&html_encode($FullFileName));
							&ppstr(64, 'allow symbolic links is false' );
							}
						next FolderEntry;
						}
					unless ($Rules{'trustsymboliclinks'}) {
						# Record this name for avoiding loops:
						if ($$p_symlinks{$_}) {
							print { $$p_whandle } "$time\t0\t$basename\t5\ta symbolic link named '$_' has already been encountered during this crawl session\t\n";

							if ($b_verbose) {
								&ppstr(89,&html_encode($FullFileName));
								&ppstr(64, "a symbolic link named '$_' has already been encountered during this crawl session" );
								}
							next FolderEntry;
							}
						$$p_symlinks{$_}++;
						}
					}

				$count += $self->_handle_folder($basename, $p_whandle, $p_fr, $base_dir, $base_url, $p_symlinks, $lc_ext, $p_ForbiddenPaths, $b_verbose);
				next FolderEntry;
				}

			# Skip, if this is not a text file, and we care about bin/text:
			unless (($Rules{'allowbinaryfiles'}) or (-T _)) {
				print { $$p_whandle } "$time\t0\t$basename\t6\tbinary file and AllowBinaryFiles is false\t\n";
				next FolderEntry;
				}

			# Skip, if this doesn't have a file extension:
			unless ($FullFileName =~ m!\.([^\.|/|\\]+)$!) {
				print { $$p_whandle } "$time\t0\t$basename\t7\tno file extension\t\n";
				next FolderEntry;
				}
			my $qm_lc_extension = quotemeta(lc($1));

			# Skip, if this extension is forbidden:
			unless ($lc_ext =~ m! $qm_lc_extension !) {
				print { $$p_whandle } "$time\t0\t$basename\t8\tfile extension not listed in EXT rule\t\n";
				next FolderEntry;
				}

			print { $$p_whandle } "$time\t0\t$basename\t0\t\t\n";
			$count++;
			}
		closedir(DIR);
		}
	return $count;
	}





sub resume_file_position {
	my ($self, $count) = @_;
	for (1..$count) {
		$self->get_next_file();
		}
	}





sub get_next_file {
	my ($self) = @_;
	my $p_rhandle = $self->{'p_rhandle'};

	#0054 - strong assertions; some platform-dependent(?) error reports in this sector
	unless (defined($self)) {
		die "{self} reference undefined in subr get_next_file - caller " . join('-', caller() );
		}
	unless (exists($self->{'p_rhandle'})) {
		die "self->{p_rhandle} does not exist as hash element in subr get_next_file - caller " . join('-', caller() );
		}
	unless (defined($p_rhandle)) {
		die "p_rhandle file reference undefined in subr get_next_file - caller " . join('-', caller() );
		}

	my ($lastmodt, $size, $fullfile, $basefile, $url) = ();
	unless ($self->{'is_eof'}) {
		while (1) {
			unless (defined($_ = readline($$p_rhandle))) {
				$self->{'is_eof'} = 1;
				last;
				}
			if (m!^(\d+)\t(\d+)\t(.*?)\t0!) {
				($lastmodt, $size, $basefile) = ($1, $2, $3);
				$fullfile = $self->{'base_dir'} . '/' . $basefile;
				$url = $self->{'base_url'} . '/' . $basefile;
				last
				}
			}
		}
	return ($lastmodt, $size, $fullfile, $basefile, $url);
	}





sub quit {
	my ($self, $save_temp_file) = @_;
	my $err = '';
	Err: {
		my $obj = $self->{'obj'};
		$err = $obj->Close();
		next Err if ($err);
		last Err if ($save_temp_file);
		unless (unlink($self->{'tempfile'})) {
			$err = &pstr(54, $self->{'tempfile'}, $!);
			next Err;
			}
		}
	return $err;
	}





sub fdse_filter_rules_new {
	my $self = {
		'delim' => '____',
		'separ' => '{END}',
		'strlim' => '~~~~',
		};
	bless($self);
	$self->_load_filter_rules(@_);
	return $self;
	}





sub list_system_rules {
	return (
		'Promote Sites'      => 1,
		'Forbid Sites'       => 1,
		'Always Allow Pages' => 1,
		'Admin Pages'        => 1,
		);
	}





sub parse_pics_label {
	my ($self, $text) = @_;
	my ($is_denied, $require_approval, $err) = (0, 0, '');

	Err: {
		last Err unless (($Rules{'pics_rasci_enable'}) or ($Rules{'pics_ss_enable'}));
		local $_;

		my $meta_value = &parse_meta_header( \$text, 'PICS-Label');

		if (($Rules{'pics_rasci_enable'}) and ($meta_value =~ m!\(n (\d) s (\d) v (\d) l (\d)\)!is)) {
			my %page_ratings = (
				'n' => $1,
				's' => $2,
				'v' => $3,
				'l' => $4,
				);
			foreach (keys %page_ratings) {
				my $max = $Rules{"pics_rasci_$_"};
				next unless ($page_ratings{$_} > $max);

				# caught 'em
				$err = &pstr(67, 'RASCi', "$_$page_ratings{$_}", $max );
				if ($Rules{'pics_rasci_handle'}) {
					$require_approval = 1;
					}
				else {
					$is_denied = 1;
					last Err;
					}
				}
			}

		if (($Rules{'pics_ss_enable'}) and ($meta_value =~ m!\(ss\~\~(.*?)\)!is)) {

			my $ss_ratings_str = $1;

			foreach (0..9,'A') {
				my $tag = "00$_";
				next unless ($ss_ratings_str =~ m!$tag (\d)!is);

				my $rating = $1;

				my $max = $Rules{"pics_ss_$tag"};

				next unless ($rating > $max);

				# caught 'em
				$err = &pstr(67, 'SafeSurf', "$tag-$rating", $max );
				if ($Rules{'pics_ss_handle'}) {
					$require_approval = 1;
					}
				else {
					$is_denied = 1;
					last Err;
					}
				}
			}
		}
	return ($is_denied, $require_approval, $err);
	}





sub check_filter_rules {
	my ($self, $url, $text, $check_only_url_deny) = @_;
	my ($is_denied, $requires_approval, $promote_val, $filter_err, $no_update_on_redirect, $b_index_nofollow, $b_follow_noindex) = (0, 0, 1, '', 0, 0, 0);
	my @MetaData = (
		'',
		$url,
		$text,
		);
	$MetaData[0] = $1 if ($url =~ m!^\w+\://([^/|:]+)!);

	my @WordCount = ();
	my $p_data = ();
	my @analyze_names = (
		$str[85],
		$str[74],
		$str[84],
		);
	# Hostname, URL, Document Text

	my $hits;


		Err: {

			AllowDeny: {

				# 0 == always allow
				last AllowDeny if ($self->check_rule(\@MetaData, 0, $check_only_url_deny));


				# check for Deny

				foreach $p_data ($self->list_filter_rules()) {
					next if (($const{'mode'} == 3) and (not $$p_data{'is_system'}));
					next unless ($$p_data{'action'} == 1);
					next unless ($$p_data{'enabled'});
					if ($check_only_url_deny) {
						next if ($$p_data{'analyze'} == 2);
						}
					my @matched_strings = ();
					my $count_matches = 0;
					my $p_strings = $$p_data{'p_eval_strings'};
					foreach (@$p_strings) {
						$hits = (@WordCount = ($MetaData[$$p_data{'analyze'}] =~ m!$_!isg));
						if ($hits) {
							$count_matches += $hits;
							push(@matched_strings,$_);
							}
						}
					if ($$p_data{'mode'} == 0) {
						if ($count_matches >= $$p_data{'occurrences'}) {
							$is_denied = 1;
							$filter_err = &pstr(77, $$p_data{'name'}, $$p_data{'occurrences'}, $analyze_names[$$p_data{'analyze'}], join('\'; \'', @matched_strings), $count_matches );
							last AllowDeny;
							}
						}
					else {
						if ($count_matches < $$p_data{'occurrences'}) {
							$is_denied = 1;
							$filter_err = &pstr(88, $$p_data{'name'}, $$p_data{'occurrences'}, $analyze_names[$$p_data{'analyze'}], join('\'; \'', @$p_strings), $count_matches );
							last AllowDeny;
							}
						}
					}

				last AllowDeny if ($check_only_url_deny);

				($is_denied, $requires_approval, $filter_err) = $self->parse_pics_label( $text );
				last AllowDeny if (($is_denied) or ($requires_approval));

				# check for Require Approval

				foreach $p_data ($self->list_filter_rules()) {
					next if (($const{'mode'} == 3) and (not $$p_data{'is_system'}));
					next unless ($$p_data{'action'} == 2);
					next unless ($$p_data{'enabled'});
					my $p_strings = $$p_data{'p_eval_strings'};
					my @matched_strings = ();
					my $count_matches = 0;
					foreach (@$p_strings) {
						my $hits = (@WordCount = ($MetaData[$$p_data{'analyze'}] =~ m!$_!isg));
						if ($hits) {
							$count_matches += $hits;
							push(@matched_strings,$_);
							}
						}
					if ($$p_data{'mode'} == 0) {
						if ($count_matches >= $$p_data{'occurrences'}) {
							$requires_approval = 1;
							$filter_err = &pstr(83, $$p_data{'name'}, $$p_data{'occurrences'}, $analyze_names[$$p_data{'analyze'}], join('\'; \'', @matched_strings), $count_matches);
							last AllowDeny;
							}
						}
					else {
						if ($count_matches < $$p_data{'occurrences'}) {
							$requires_approval = 1;
							$filter_err = &pstr(86, $$p_data{'name'}, $$p_data{'occurrences'}, $analyze_names[$$p_data{'analyze'}], join('\'; \'', @$p_strings), $count_matches);
							last AllowDeny;
							}
						}
					}
				}


			Promote: {

				# if we aren't denied, then computer Promote Value:

				last Promote if ($is_denied);
				last Promote if ($check_only_url_deny);

				foreach $p_data ($self->list_filter_rules()) {
					next if (($const{'mode'} == 3) and (not $$p_data{'is_system'}));
					next unless ($$p_data{'action'} == 3);
					next unless ($$p_data{'enabled'});

					my $p_strings = $$p_data{'p_eval_strings'};

					my $count_matches = 0;

					foreach (@$p_strings) {
						$count_matches += (@WordCount = ($MetaData[$$p_data{'analyze'}] =~ m!$_!isg));
						}

					if ($$p_data{'mode'} == 0) {
						if ($count_matches >= $$p_data{'occurrences'}) {
							# folks we have winner
							$promote_val *= $$p_data{'promote_val'};
							}
						}
					else {
						# rare reverse-formulation - granting promotions to everyone 'cept those with sufficient hits
						if ($count_matches < $$p_data{'occurrences'}) {
							# folks another winner!
							$promote_val *= $$p_data{'promote_val'};
							}
						}
					}
				$promote_val = 99 if ($promote_val > 99);
				}

			last Err if ($is_denied);

			# 4 == no update on redirect
			$no_update_on_redirect = $self->check_rule(\@MetaData, 4, 0);

			# 5 == index nofollow
			$b_index_nofollow = $self->check_rule(\@MetaData, 5, 0);

			# 6 == follow noindex
			$b_follow_noindex = $self->check_rule(\@MetaData, 6, 0);
			};
	return ($is_denied, $requires_approval, $promote_val, $filter_err, $no_update_on_redirect, $b_index_nofollow, $b_follow_noindex);
	}





sub check_rule {
	my ($self, $p_metadata, $index, $check_only_url_deny) = @_;
	my $is_valid = 0;
	my $p_data;
	my @WordCount;
	foreach $p_data ($self->list_filter_rules()) {
		next if (($const{'mode'} == 3) and (not $$p_data{'is_system'}));
		next unless ($$p_data{'action'} == $index);
		next unless ($$p_data{'enabled'});
		if ($check_only_url_deny) {
			next if ($$p_data{'analyze'} == 2);
			}
		my $p_strings = $$p_data{'p_eval_strings'};
		my $count_matches = 0;
		foreach (@$p_strings) {
			$count_matches += (@WordCount = ($$p_metadata[$$p_data{'analyze'}] =~ m!$_!isg));
			}
		if ($$p_data{'mode'} == 0) {
			if ($count_matches >= $$p_data{'occurrences'}) {
				# folks we have winner
				$is_valid = 1;
				last;
				}
			}
		else {
			# rare reverse-formulation - granting promotions to everyone 'cept those with sufficient hits
			if ($count_matches < $$p_data{'occurrences'}) {
				# folks another winner!
				$is_valid = 1;
				last;
				}
			}
		}
	return $is_valid;
	}





sub list_filter_rules {
	my ($self) = (@_);
	my @sorted_rules = ();
	local $_;
	my $p_data = ();
	foreach (sort keys %$self) {
		$p_data = $self->{$_};
		next unless ('HASH' eq ref($p_data));
		next unless ($$p_data{'is_system'});
		push(@sorted_rules,$p_data);
		}
	foreach (sort keys %$self) {
		$p_data = $self->{$_};
		next unless ('HASH' eq ref($p_data));
		next if ($$p_data{'is_system'});
		push(@sorted_rules,$p_data);
		}
	return @sorted_rules;
	}





sub validate {
	my ($self, $p_rule) = @_;
	my $err = '';
	Err: {
		#&Assert('HASH' eq ref($p_rule));

		my ($a,$b,$c) = (quotemeta($self->{'delim'}),quotemeta($self->{'separ'}),quotemeta($self->{'strlim'}));

		unless ($$p_rule{'name'}) {
			$err = "no name provided";
			next Err;
			}

		if ($$p_rule{'name'} =~ m!($a|$b|$c)!) {
			my $badstr = $1;
			$err = &pstr(75,$badstr);
			next Err;
			}

		unless (($$p_rule{'action'} > -1) and ($$p_rule{'action'} < 7)) {
			$err = &pstr(69, 'action', 0, 6);
			next Err;
			}

		unless (($$p_rule{'promote_val'} =~ m!^\d+$!) and ($$p_rule{'promote_val'} > 0) and ($$p_rule{'promote_val'} < 100)) {
			$err =  &pstr(69, 'promote_val', 1, 99);
			next Err;
			}

		unless (($$p_rule{'analyze'} > -1) and ($$p_rule{'analyze'} < 3)) {
			$err = &pstr(69, 'analyze', 0, 2);
			next Err;
			}

		unless (($$p_rule{'mode'} == 0) or ($$p_rule{'mode'} == 1)) {
			$err =  &pstr(69, 'mode', 0, 1);
			next Err;
			}

		unless (($$p_rule{'occurrences'} =~ m!^\d+$!) and ($$p_rule{'occurrences'} > 0) and ($$p_rule{'occurrences'} < 100)) {
			$err =  &pstr(69, 'occurrences', 1, 99);
			next Err;
			}
		undef($@);
		my $pattern = '';
		my $p_strings = $$p_rule{'p_strings'};
		foreach $pattern (@$p_strings) {
			$err = &check_regex($pattern);
			next Err if ($err);
			if ($pattern =~ m!($a|$b|$c)!) {
				my $badstr = $1;
				$err = &pstr(75,$badstr);
				next Err;
				}
			}
		my $p_litstrings = $$p_rule{'p_litstrings'};
		foreach $pattern (@$p_litstrings) {
			if ($pattern =~ m!($a|$b|$c)!) {
				my $badstr = $1;
				$err = &pstr(75,$badstr);
				next Err;
				}
			}
		last Err;
		}
	continue {
		# error rules are disabled
		$$p_rule{'enabled'} = 0;
		}
	return $err;
	}





sub _load_filter_rules {
	my ($self, $p_realm_data) = @_;
	my $err = '';
	Err: {

		#rev compat - pre 0038
		my %rev_actions = (
			'Always Allow' => 0,
			'Deny' => 1,
			'Require Approval' => 2,
			'Promote' => 3,
			);
		my %rev_analyze = (
			'Hostname' => 0,
			'URL' => 1,
			'Document HTML' => 2,
			);
		my %rev_mode = (
			'if' => 0,
			'unless' => 1,
			);
		#/rev compat


		my $is_single_realm = defined($p_realm_data) ? 1 : 0;

		my $type = 0;
		my $urlname = '';

		if ($is_single_realm) {
			$type = $$p_realm_data{'type'};
			$urlname = $$p_realm_data{'url_name'};
			}

		my $FileText = '';
		($err, $FileText) = &ReadFile('filter_rules.txt');
		next Err if ($err);

		my $separ = $self->{'separ'};

		my @list = ();

		my ($has_promote_sites, $has_forbid_sites, $has_always_allow, $has_frontpage) = (0, 0, 0, 0);

		foreach (split(m!$separ!s, &Trim($FileText))) {
			my $delim = $self->{'delim'};
			my @Fields = split(m!$delim!s, &Trim($_));

			#rev compat - pre 0038
			if ($Fields[2] =~ m!\D!) {
				$Fields[2] = $rev_actions{$Fields[2]};
				}
			if ($Fields[4] =~ m!\D!) {
				$Fields[4] = $rev_analyze{$Fields[4]};
				}
			if ($Fields[5] =~ m!\D!) {
				$Fields[5] = $rev_mode{$Fields[5]};
				}
			#/rev compat

			my %data = (
				'enabled' => $Fields[0],
				'name' => $Fields[1],
				'action' => $Fields[2],
				'promote_val' => $Fields[3],
				'analyze' => $Fields[4],
				'mode' => $Fields[5],
				'occurrences' => $Fields[6],
				'strings' => $Fields[7] || '',
				'litstrings' => $Fields[8] || '',
				'apply_to' => $Fields[9] || 1,
				'apply_to_str' => $Fields[10] || '',
				);

			my $strlim = $self->{'strlim'};

			my @litstrings = split(m!$strlim!s, $data{'litstrings'} );
			$data{'p_litstrings'} = \@litstrings;

			my @strings = split(m!$strlim!s, $data{'strings'} );
			$data{'p_strings'} = \@strings;

			my @eval_strings = @strings;
			foreach (@litstrings) {
				push(@eval_strings, quotemeta($_));
				}
			$data{'p_eval_strings'} = \@eval_strings;

			# validation is expensive - only do it on enabled rules
			# also fixes bug in which invalid rules are dropped from the set sometimes w/o chance to fix
			if ($data{'enabled'}) {
				my $record_err_msg = $self->validate(\%data);
				if ($record_err_msg) {
					&ppstr(76, $str[92] . ' - ' . $record_err_msg );
					next;
					}
				}

			if ($is_single_realm) {
				if ($data{'apply_to'} eq '2') {
					# only certain types
					next unless ($data{'apply_to_str'} =~ m!$type!);
					}
				elsif ($data{'apply_to'} eq '3') {
					# only certain named realms
					next unless ($data{'apply_to_str'} =~ m!(^|,)$urlname(,|$)!i);
					}
				}

			if ($data{'name'} =~ m!^(Admin Pages|Forbid Sites|Promote Sites|Always Allow Pages)$!) {
				$data{'is_system'} = 1;
				}
			else {
				$data{'is_system'} = 0;
				}
			$self->{ $data{'name'} } = \%data;
			}

		my @empty = ();
		unless ($self->{'Admin Pages'}) {
			my @litstrings = ('/_vti_','/_private/','searchmods','searchdata','script_data','Terms=');
			my %data = (
				'enabled' => 1,
				'name' => 'Admin Pages',
				'action' => 1,
				'promote_val' => 5,
				'analyze' => 1,
				'mode' => 0,
				'occurrences' => 1,
				'p_strings' => \@empty,
				'p_litstrings' => \@litstrings,
				'p_eval_strings' => \@litstrings,
				'is_system' => 1,
				'apply_to' => 1,
				'apply_to_str' => '',
				);
			$self->{'Admin Pages'} = \%data;
			}


		unless ($self->{'Always Allow Pages'}) {
			my %data = (
				'enabled' => 1,
				'name' => 'Always Allow Pages',
				'action' => 0,
				'promote_val' => 5,
				'analyze' => 1,
				'mode' => 0,
				'occurrences' => 1,
				'p_strings' => \@empty,
				'p_litstrings' => \@empty,
				'p_eval_strings' => \@empty,
				'is_system' => 1,
				'apply_to' => 1,
				'apply_to_str' => '',
				);
			$self->{'Always Allow Pages'} = \%data;
			}
		unless ($self->{'Promote Sites'}) {
			my %data = (
				'enabled' => 1,
				'name' => 'Promote Sites',
				'action' => 3,
				'promote_val' => 5,
				'analyze' => 1,
				'mode' => 0,
				'occurrences' => 1,
				'p_strings' => \@empty,
				'p_litstrings' => \@empty,
				'p_eval_strings' => \@empty,
				'is_system' => 1,
				'apply_to' => 1,
				'apply_to_str' => '',
				);
			$self->{'Promote Sites'} = \%data;
			}
		unless ($self->{'Forbid Sites'}) {
			my %data = (
				'enabled' => 1,
				'name' => 'Forbid Sites',
				'action' => 1,
				'promote_val' => 5,
				'analyze' => 1,
				'mode' => 0,
				'occurrences' => 1,
				'p_strings' => \@empty,
				'p_litstrings' => \@empty,
				'p_eval_strings' => \@empty,
				'is_system' => 1,
				'apply_to' => 1,
				'apply_to_str' => '',
				);
			$self->{'Forbid Sites'} = \%data;
			}
		}
	return $err;
	}





sub WriteFile {
	my ($file, $text) = @_;
	my $err = '';
	Err: {
		my ($obj, $p_rhandle, $p_whandle) = ();
		$obj = &LockFile_new(
			'create_if_needed' => 1,
			);
		($err, $p_rhandle, $p_whandle) = $obj->ReadWrite($file);
		next Err if ($err);
		unless (print { $$p_whandle } $text) {
			$err = &pstr(43,$obj->{'wname'},$!);
			my $cancel_msg = $obj->Cancel();
			if ($cancel_msg) {
				$err .= "</p><p><b>$str[73]:</b> $cancel_msg";
				}
			next Err;
			}
		$err = $obj->Merge();
		next Err if ($err);
		}
	return $err;
	}





sub ReadWrite {
	my ($self, $filename) = @_;

	$self->{'rname'} = $filename;
	$self->{'ename'} = "$filename.exclusive_lock_request";
	$self->{'wname'} = "$filename.working_copy";

	my ($p_rhandle, $rname, $p_whandle, $wname, $p_ehandle, $ename) = ($self->{'p_rhandle'}, $self->{'rname'}, $self->{'p_whandle'}, $self->{'wname'}, $self->{'p_ehandle'}, $self->{'ename'});

	my $err = '';
	Err: {

		$err = $self->LockFile_get_read_access();
		next Err if ($err);

		# Create the appropriate files to secure our access from other LockFile.pm processes:

		unless (open($$p_ehandle, "+>$ename")) {
			$err = &pstr(70,$ename,$!);
			next Err;
			}
		unless (binmode($$p_ehandle)) {
			$err = &pstr(39,$ename,$!);
			next Err;
			}
		unless (&FlockEx($p_ehandle,6)) {
			$err = &pstr(68,$ename,$!);
			close($$p_ehandle);
			next Err;
			}
		my $h = select($$p_ehandle);
		$| = 1;
		select($h);
		print { $$p_ehandle } '';
		chmod($private{'file_mask'},$ename);


		unless (open($$p_whandle,">$wname")) {
			$err = &pstr(43,$wname,$!);
			next Err;
			}
		chmod($private{'file_mask'},$wname);
		unless (&FlockEx($p_whandle, 6)) {
			$err = &pstr(68,$wname,$!);
			close($$p_whandle);
			next Err;
			}
		unless (binmode($$p_whandle)) {
			$err = &pstr(39,$wname,$!);
			next Err;
			}


		chmod($private{'file_mask'},$rname);
		unless (open($$p_rhandle, "<$rname")) {
			$err = &pstr(44,$rname,$!);
			next Err;
			}
		unless (&FlockEx($p_rhandle, 5)) {
			$err = &pstr(41,$rname,$!);
			close($$p_rhandle);
			next Err;
			}
		unless (binmode($$p_rhandle)) {
			$err = &pstr(39,$rname,$!);
			next Err;
			}
		}
	return ($err, $p_rhandle, $p_whandle);
	}





sub get_wname {
	my ($self) = @_;
	return $self->{'wname'};
	}





sub Cancel {
	my ($self) = @_;
	my ($p_rhandle, $rname, $p_whandle, $wname, $p_ehandle, $ename) = ($self->{'p_rhandle'}, $self->{'rname'}, $self->{'p_whandle'}, $self->{'wname'}, $self->{'p_ehandle'}, $self->{'ename'});

	my $err = '';
	Err: {

		# Release the read lock on $readfile, retain data

		$err = &freeh($p_rhandle,$rname);

		# Delete writefile, abandoning changes:


		$err = &freeh($p_whandle,$wname,1);

		# Delete exclusive_lock_request file:
		$err = &freeh($p_ehandle,$ename,1);
		}
	return $err;
	}





sub Resume {
	my ($self, $filename) = @_;

	$self->{'rname'} = $filename;
	$self->{'ename'} = "$filename.exclusive_lock_request";
	$self->{'wname'} = "$filename.working_copy";

	my ($p_rhandle, $rname, $p_whandle, $wname, $p_ehandle, $ename) = ($self->{'p_rhandle'}, $self->{'rname'}, $self->{'p_whandle'}, $self->{'wname'}, $self->{'p_ehandle'}, $self->{'ename'});

	my $err = '';

	Err: {

		unless (open($$p_ehandle, "+<$ename")) {
			$err = &pstr(70, $ename, $! );
			next Err;
			}
		unless (binmode($$p_ehandle)) {
			$err = &pstr(39, $ename, $! );
			next Err;
			}
		unless (&FlockEx($p_ehandle, 6)) {
			$err = &pstr(68, $ename, $! );
			next Err;
			}

		my $e_size = -s $ename;

		unless ($e_size == length(pack('LLL'))) {
			$err = "unable to resume file read/write operation - lock file was only size $e_size and did not contain information about where to resume the process. You will have to manually restart this process";
			next Err;
			}

		my $data;
		unless (12 == read($$p_ehandle, $data, 12)) {
			$err = "error while reading from file '$ename' - $! - $^E";
			next Err;
			}


		my ($pid, $read_depth, $write_depth) = unpack('LLL', $data);

		unless (defined($pid)) {
			$err = "unable to resume operation -- expected file '$ename' to contain data about the last PID but the value was not defined";
			next Err;
			}
		unless (defined($read_depth)) {
			$err = "unable to resume operation -- expected file '$ename' to contain data about the read_depth but the value was not defined";
			next Err;
			}
		unless (defined($write_depth)) {
			$err = "unable to resume operation -- expected file '$ename' to contain data about the write_depth but the value was not defined";
			next Err;
			}

		unless ($write_depth =~ m!^\d+$!) {
			$err = "unable to resume operation -- write_depth returned non-integer value '$write_depth'";
			next Err;
			}

		unless (open($$p_whandle, "+<$wname")) {
			$err = &pstr(70, $wname, $! );
			next Err;
			}
		unless (binmode($$p_whandle)) {
			$err = &pstr(39, $wname, $! );
			next Err;
			}
		unless (&FlockEx($p_whandle, 6)) {
			$err = &pstr(68, $wname, $! );
			next Err;
			}
		unless (seek($$p_whandle, $write_depth, 0)) {
			$err = &pstr(72,$write_depth,$wname,$!);
			next Err;
			}

		my $w_size = -s $wname;
		if ($write_depth > $w_size) {
			&ppstr(76, &pstr(82, $write_depth, $wname, $w_size ) );
			}
		elsif ($write_depth < $w_size) {
			&ppstr(76, &pstr(71, $write_depth, $w_size ) );
			}


		unless (open($$p_rhandle, "+<$rname")) {
			$err = &pstr(70, $rname, $! );
			next Err;
			}
		unless (binmode($$p_rhandle)) {
			$err = &pstr(39, $rname, $! );
			next Err;
			}
		unless (&FlockEx($p_rhandle, 6)) {
			$err = &pstr(68, $rname, $! );
			next Err;
			}
		unless (seek($$p_rhandle, $read_depth, 0)) {
			$err = &pstr(72, $read_depth, $rname, $! );
			next Err;
			}
		}
	return ($err, $p_rhandle, $p_whandle);
	}





sub Suspend {
	my ($self) = @_;
	my ($p_rhandle, $rname, $p_whandle, $wname, $p_ehandle, $ename) = ($self->{'p_rhandle'}, $self->{'rname'}, $self->{'p_whandle'}, $self->{'wname'}, $self->{'p_ehandle'}, $self->{'ename'});

	my $err = '';
	Err: {


		my ($read_depth, $write_depth) = (0, 0);

		# close the reading filehandle:

		$read_depth = tell($$p_rhandle);
		if (-1 == $read_depth) {
			$err = "unable to determine read depth on in-progress readfile - $! - $^E";
			next Err;
			}

		$err = &freeh( $p_rhandle, $rname );
		next Err if ($err);

		# close the writing filehandle:

		$write_depth = tell($$p_whandle);

		unless (defined($write_depth)) {
			$err = "write depth not defined - $! - $^E";
			next Err;
			}
		if (-1 == $write_depth) {
			$err = "unable to determine write depth on in-progress writefile - $! - $^E";
			next Err;
			}



		$err = &freeh( $p_whandle, $wname );
		next Err if ($err);

		# Call it a day...
		unless (seek($$p_ehandle, 0, 0)) {
			$err = &pstr(72,0,$ename,$!);
			next Err;
			}

		my $data = pack('LLL', $$, $read_depth, $write_depth);

		unless (print { $$p_ehandle } $data) {
			$err = &pstr( 43, $ename, $! );
			next Err;
			}

		$err = &freeh( $p_ehandle, $ename );
		next Err if ($err);

		# We're leaving these files behind, hoping that somebody will come along and call LockFile->Resume very soon. If they don't, though, then a human admin will have to come along and clean up the files.  Make sure the permissions are set on files we've created/owned so that they'll be allowed to:
		chmod($private{'file_mask'}, $ename);
		chmod($private{'file_mask'}, $rname);

		last Err;
		}
	continue {
		# there was some error - try to nuke the $ename file just to be safe
		unlink($ename) if (-e $ename);
		}
	return $err;
	}





sub Merge {
	my ($self) = @_;
	my ($p_rhandle, $rname, $p_whandle, $wname, $p_ehandle, $ename) = ($self->{'p_rhandle'}, $self->{'rname'}, $self->{'p_whandle'}, $self->{'wname'}, $self->{'p_ehandle'}, $self->{'ename'});

	my $err = '';
	Err: {
		my $abort = 0;

		# Release the read lock on $readfile on close it:

		$err .= &freeh($p_rhandle,$rname);
		$abort = 1 if ($err);

		# Request an exclusive write lock on $readfile (waits for other processes with shared locks to finish up...)
		my $write_filehandle = *WRITE;
		$write_filehandle = *WRITE;

		unless (open($write_filehandle, "+<$rname")) {
			$err .= &pstr(70, $rname, $! );
			$abort = 1;
			}
		else {
			my $attempts = $self->{'timeout'};
			my $success = 0;
			Try: while ($attempts > 0) {
				if (&FlockEx(\$write_filehandle, 6)) {
					$success = 1;
					last Try;
					}
				$attempts--;
				sleep(1);
				}
			unless ($attempts > 0) {
				$err .= &pstr(68, $wname, $! );
				}

			# Got an exclusive lock?  Good.  Release it and kill readfile.

			$err .= &freeh( \$write_filehandle, $wname );
			}

		unless ($abort) {
			unless (unlink($rname)) {
				$err .= &pstr(54,$rname,$!);
				$abort = 1;
				}
			}

		# Replace readfile with writefile

		$err .= &freeh( $p_whandle, $wname );
		$abort = 1 if ($err);

		unless ($abort) {
			unless (rename($wname, $rname)) {
				$err .= &pstr(38,$wname,$rname,$!);
				$abort = 1;
				}
			}
		chmod($private{'file_mask'}, $rname);

		# Call it a day...

		$err .= &freeh($p_ehandle,$ename,1);
		}
	return $err;
	}





sub timegm {
	my ($sec, $min, $hours, $mday, $month, $year, $p_timecache) = @_;

	if ($month =~ m!\D!) {
		my $n = 0;
		$month = lc($month);
		foreach ('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec') {
			last if ($month eq $_);
			$n++;
			}
		$month = $n % 12;
		}

	if ($year < 100) {

		# Handle two-digit years:

		# since our effective range is only 1970 to 2037, necessity dictates the following:

		if ($year > 70) {
			$year += 1900;
			}
		else {
			$year += 2000;
			}

		}

	if (($year < 1970) or ($year > 2037)) {
		return 0;
		}

	# convert back to base-1900 to prevent overflows:
	$year -= 1900;

	my $base_time_at_mon_year = &basetime($month, $year, $p_timecache);

	if ($base_time_at_mon_year == -1) {
		return 0;
		}

	return ($base_time_at_mon_year) + ($sec) + ($min * 60) + (3600 * $hours) + (86400 * ($mday - 1));
	}





sub timelocal {
	my $gtime = &timegm(@_);
	return 0 unless ($gtime);

	# Calculate seconds offset between localtime and gmtime

	my $testtime = $gtime;

	# If we're anywhere near a year boundary, shift up by a day or two:
	my $yday = (gmtime($testtime))[7];
	if (($yday < 2) or ($yday > 360)) {
		$testtime += 86400 * 15;
		}
	my @lt = localtime($testtime);
	my @gt = gmtime($testtime);
	my $offset = ($lt[0] - $gt[0]) + 60 * ($lt[1] - $gt[1]) + 3600 * ($lt[2] - $gt[2]) + 86400 * ($lt[7] - $gt[7]);

	my $ltime = $gtime - $offset;

	# kludge kludge kludge... I hate this ... this is a +/- 1 search pattern in case our response doesn't agree with what they input. this corrects for some weird crazyness surrounding gmtime vs localtime while daylight savings time is propagating between them.
	if ((localtime($ltime))[2] != $_[2]) {
		$ltime -= 3600;
		}
	if ((localtime($ltime))[2] != $_[2]) {
		$ltime += 2 * 3600;
		}
	return $ltime;
	}





sub basetime {
	my ($month, $year, $p_timecache) = @_;

	my $time = -1;

	Err: {

		if (($p_timecache) and ('HASH' eq ref($p_timecache))) {
			my $key = pack('LC', $year, $month);
			last Err if ($time = $$p_timecache{$key});
			}

		my $guess_time = time();

		my ($guess_month, $guess_year) = (gmtime($guess_time))[4,5];

		my $yeardiff = $guess_year - $year;
		my $mondiff = $guess_month - $month;

		$guess_time -= (366 * 86400) * $yeardiff;
		$guess_time -= (31 * 86400) * (1 + $mondiff);
		$guess_time = 0 if ($guess_time < 0);

		# Okay, no $guess_time should lie sometime before the start of $month/$year. We took that extra month just in case.

		# Now step forward by 25-day increments until $guess_time returns a matching $month/year
		while (1) {
			($guess_month, $guess_year) = (gmtime($guess_time))[4,5];
			last Err unless (defined($guess_month));
			last Err unless (defined($guess_year));
			last if (($guess_month == $month) and ($guess_year == $year));
			$guess_time += 25 * 86400;
			last Err if ($guess_year > $year);
			}

		# Take $guess_time down to the time the month/year started:
		my ($sec, $min, $hour, $mday) = gmtime($guess_time);
		$guess_time -= ( $sec + 60 * $min + 3600 * $hour + 86400 * ($mday - 1) );

		if (($p_timecache) and ('HASH' eq ref($p_timecache))) {
			my $key = pack('LC', $year, $month);
			$$p_timecache{$key} = $guess_time;
			}
		$time = $guess_time;
		}
	return $time;
	}


1;
