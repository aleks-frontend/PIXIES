#!/usr/bin/perl --
use strict;
use Socket;

# <H1>If you can see this text from a web browser, then there is a problem.
# <A HREF="http://www.xav.com/scripts/search/help/1089.html">Get help here.</A></H1><XMP>


my $ext = 'pl';
$ext = $1 if (&query_env('SCRIPT_NAME') =~ m!proxy\.(\w+)$!);

my $overview = <<"EOM";

Overview
========

The proxy.$ext redirect script is a utility to be used with the Fluid Dynamics
Search Engine.

What It Does
============

Visitors who wish to view a search result can request this proxy.$ext script
instead, with the destination URL passed as a parameter.  This proxy script
will request the URL on their behalf, and then display it to the visitor
with all search terms highlighted in bold yellow.  This should help the
visitor find the sought-after information.

More?  See: http://www.xav.com/scripts/search/help/1106.html

How To Enable
=============

First, install FDSE and make sure it works normally for normal search
results.

Next, test the proxy.$ext script by requesting it directly.

Then edit the "line_listing.txt" template.  Add a link below the search
results as follows:

<BR><A HREF="proxy.$ext?terms=%url_terms%&url=%url_url%">
View with Highlighted Search Terms</A>

On some systems, this script will be named proxy.pl, or proxy.cgi, or
proxy.somthing.  On those systems, simply use that alternate filename.

Copyright 2002 by Zoltan Milosevic; distributed under the same terms as FDSE.

EOM


# ~~ read http://www.xav.com/scripts/search/help/1106.html "Security" first ~~
#
# "turn on" the proxy:

my $SECURITY_ENABLE = 0;

# allow retrieval of only URL's on this server:

my $SECURITY_MATCH_SERVER_NAME = 0;

# allow retrieval of only URL's listed in the search.pending.txt file: (recommended)

my $SECURITY_MATCH_PENDING_FILE = 1;


my $VERSION = '2.0.0.0055';
my %FORM = ();
&WebFormL(\%FORM);
my $hurl = &html_encode($FORM{'url'});


my $highlight_open = '<B STYLE=color:#000000;background-color:#ffff77>';
my $highlight_close = '</B>';

my $header = <<"EOM";

<META NAME="robots" CONTENT="none">
<BASE HREF="%base_href%">

<TABLE WIDTH=100% BORDER=1 BGCOLOR="#ffffff"><TR><TD STYLE="color:#000000"><FONT COLOR="#000000">

	<P>This is a pre-processed version of the web page <A HREF="%link_href%" STYLE="text-decoration:underline">%base_href%</A>. In this copy, the search terms %str% have been $highlight_open highlighted $highlight_close to make them easier to find. If a search term was not found, then it may exist in the non-visible title, description, keywords or URL fields, or the contents of this document may have changed since it was indexed.</P>

	<P>Some web pages won't display properly in this pre-processor. Visit those pages directly by following <A HREF="%link_href%" STYLE="text-decoration:underline">this link</A>. Visit the page itself before bookmarking it.</P>

	<P ALIGN=center><SMALL>The search engine that brought you here is not necessarily affiliated with, nor responsible for, the contents of this page.</SMALL></P>

</FONT></TD></TR></TABLE>

EOM


# optional - maps are of the form "url/" => "folder/"
# If proxy.pl intercepts a URL which matches one of these maps entries, it will do a file-request rather than an HTTP request.
# Use forward slashes for Windows paths. Always include trailing slashes.
# caveats:
#	will bypass server logging of visits
#	will bypass username-password and/or SSL protection of file
#	will return source code of file; not appropriate for active content or files that contain includes
# Remove the "#" signs in the %maps entries below to activate:

my %maps = (
	#'http://www.xav.com/' => '/usr/www/users/xav/',
	#'http://nickname.net/tori/' => '/usr/www/users/xav/tori/',
	);



my %termcount = ();

my %httpcookie = ();
my $NetStream = '';
my $httpInit = 1;


my $err = '';
Err: {
	local $_;

	unless ($SECURITY_ENABLE) {
		$err = "this proxy script is currently turned off. To turn it on, edit it's source code and set:</P><P><PRE>my \$SECURITY_ENABLE = 1;</PRE>";
		next Err;
		}


	my $base_href = $hurl;
	my $link_href = $hurl;

	$FORM{'terms'} = $FORM{'terms'} || '';
	$FORM{'terms'} =~ s!\+|\-|\||\"!!g;
	$FORM{'terms'} =~ s!&quot;!!g;
	my @terms = split(m!\s+!s, $FORM{'terms'});

	unless ($FORM{'url'}) {
		$err = "must supply a URL parameter";
		next Err;
		}

	my $text = '';


	if (($0 =~ m!^(.*)(\\|/)!) and ($0 !~ m!safeperl\d*$!i)) {
		unless (chdir($1)) {
			$err = "unable to chdir to script folder '$1' - $!";
			next Err;
			}
		}

	GetText: {

		my $local_path = '';

		foreach (keys %maps) {
			my $pat = quotemeta($_);
			next unless ($FORM{'url'} =~ m!^$pat(.*)$!i);
			unless (-d $maps{$_}) {
				$err = "unable to find folder named '$maps{$_}'";
				next Err;
				}
			$local_path = $maps{$_} . $1;
			if ($local_path =~ m!\.\.!) {
				$err = "path cannot contain '..' string";
				next Err;
				}
			last;
			}

		if ($local_path) {
			unless (open(FILE, "<$local_path")) {
				$err = "unable to open file '$local_path' for reading - $!";
				next Err;
				}
			binmode(FILE);
			$text = join('',<FILE>);
			close(FILE);
			last GetText;
			}


		my ($clean_url, $host, $port, $path, $is_valid) = &parse_url($FORM{'url'});
		unless ($is_valid) {
			$err = "URL must pattern match to 'http://host.tld/file.txt' - '" . &html_encode($FORM{'url'}) . "' is invalid";
			next Err;
			}
		my $sn = &query_env('SERVER_NAME');
		if (($SECURITY_MATCH_SERVER_NAME) and ($host ne $sn)) {
			$err = "this script has setting \$SECURITY_MATCH_SERVER_NAME = 1 and so it will only query web site http://$sn, not http//" . &html_encode($host);
			next Err;
			}
		if ($SECURITY_MATCH_PENDING_FILE) {
			my $b_found = 0;
			my $qm_url = quotemeta($clean_url);
			# get pending file...
			unless (open(FILE, "<searchdata/search.pending.txt")) {
				$err = "unable to read from file 'searchdata/search.pending.txt' - $!";
				next Err;
				}
			binmode(FILE);
			while (defined($_ = <FILE>)) {
				next unless (m!^$qm_url !);
				next unless (m!^$qm_url \S+ (\d+)!); # do expensive ()-matching only on valid lines
				next unless ($1 > 2); # match only valid points
				$b_found = 1;
				last;
				}
			close(FILE);
			unless ($b_found) {
				$err = "this script has setting \$SECURITY_MATCH_PENDING_FILE = 1 but it was not able to find the URL '" . &html_encode($clean_url) . "' in the file 'searchdata/search.pending.txt'.";
				next Err;
				}
			}


		my $Method = 'GET';
		my $RequestBody = '';
		my $AllowRedir = 6;
		my %CustomHeaders = (
			'USER-AGENT' => &query_env('HTTP_USER_AGENT'),
			'REFERER' => &query_env('HTTP_REFERER'),
			);
		#fixed 0052 - blank-spaces-in-URL bug
		$FORM{'url'} =~ s! !%20!g;
		my ($is_error, $error_msg, $URL, $ResponseBody, $ResponseCode, %Headers) = &http_ex($clean_url, $Method, $RequestBody, $AllowRedir, %CustomHeaders);
		if ($is_error) {
			$err = $error_msg;
			next Err;
			}
		if ($ResponseCode ne '200') {
			$err = "proxy.pl received HTTP response code '$ResponseCode' rather than '200 OK'";
			next Err;
			}
		foreach (keys %Headers) {
			next if (m!^(set-cookie|content-length|connection)$!i);
			print "$_: $Headers{$_}\015\012";
			}
		print "\015\012";
		$text = $ResponseBody;

		$base_href = $URL; # update on redirect

		last GetText;
		}

	if ($FORM{'terms'}) {

		my @parts = split(m!\<(SCRIPT|STYLE|TITLE)!is, $text);

		my $c = 0;

		my $new = &proc( $parts[0], @terms );
		local $_;

		for ($c = 1; $c < $#parts; $c += 2) {
			my $end = quotemeta(uc($parts[$c]));
			if ($parts[$c+1] =~ m!^(.*?)</$end>(.+)$!is) {
				$new .= "<$parts[$c]$1</$end>" . &proc( $2, @terms );
				}
			else {
				$new .= '<' . $parts[$c] . $parts[$c+1];
				}
			}
		$text = $new;
		}

	my $str = '';
	foreach (@terms) {
		my $qmterm = quotemeta($_);
		$str .= $highlight_open . &html_encode($_) . "$highlight_close (" . ($termcount{$qmterm} || 0) . ") ";
		}

	$header =~ s!%base_href%!$base_href!isg;
	$header =~ s!%link_href%!$link_href!isg;
	$header =~ s!%str%!$str!isg;

	print $header;
	print $text;



	last Err;
	}
continue {
	print "Content-Type: text/html\n\n";
print <<"EOM";
<META NAME="robots" CONTENT="none">
<P><B>Error:</B> $err.</P>
EOM

	unless ($FORM{'url'}) {
print <<"EOM";


<HR>
<FORM METHOD="GET" ACTION="$ENV{'SCRIPT_NAME'}">
URL: <INPUT NAME="url" VALUE="http://"><BR>
Search Terms: <INPUT NAME="terms"> <INPUT TYPE=submit VALUE="Test"></FORM>
<HR><PRE>
EOM
		print &html_encode($overview);
		}
	}


sub proc {
	my ($text, @terms) = @_;
	local $_;

	my $new = '';
	foreach (split(m!<!s, $text)) {
		if (m!^(.*?)\>(.+)$!s) {
			$new .= "<$1>" . &replace($2, @terms);
			}
		else {
			$new .= "<$_";
			}
		}
	$new =~ s!^\<!!o;
	return $new;
	}

sub replace {
	my ($text, @terms) = @_;
	local $_;
	foreach (@terms) {
		my $qmterm = quotemeta($_);
		$termcount{$qmterm} += (scalar ($text =~ s!($qmterm)!<<$1>>!sig));
		}
	$text =~ s!\<+!$highlight_open!sg;
	$text =~ s!\>\>+!$highlight_close!sg;
	return $text;
	}





=item WebFormL

Usage:
	&WebFormL( \%FORM );

Returns a by-reference hash of all name-value pairs submitted to the CGI script.

updated: 8/21/2001

Dependencies:
	&url_decode
	&query_env

=cut

sub WebFormL {
	my ($p_hash) = @_;
	my @Pairs = ();
	if ('POST' eq &query_env('REQUEST_METHOD')) {
		my $buffer = '';
		my $len = &query_env('CONTENT_LENGTH',0);
		read(STDIN, $buffer, $len);
		@Pairs = split(m!\&!, $buffer);
		}
	elsif (&query_env('QUERY_STRING')) {
		@Pairs = split(m!\&!, &query_env('QUERY_STRING'));
		}
	else {
		@Pairs = @ARGV;
		}
	local $_;
	foreach (@Pairs) {
		next unless (m!^(.*?)=(.*)$!s);
		my ($name, $value) = (&url_decode($1), &url_decode($2));
		if ($$p_hash{$name}) {
			$$p_hash{$name} .= ",$value";
			}
		else {
			$$p_hash{$name} = $value;
			}
		}
	}


=item query_env

Usage:
	my $remote_host = &query_env('REMOTE_HOST');

Abstraction layer for the %ENV hash.  Why abstract?  Here's why:
 1. adds safety for -T taint checks
 2. always returns '' if undef; prevent -w warnings

=cut

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




=item url_encode

Usage:
	my $str_url = &url_encode($str);

Formats strings consistent with RFC 1945 by rewriting metacharacters in their
%HH format.

=cut

sub url_encode {
	local $_ = defined($_[0]) ? $_[0] : '';
	s!([^a-zA-Z0-9_.-])!uc(sprintf("%%%02x", ord($1)))!eg;
	return $_;
	}


sub url_decode {
	local $_ = defined($_[0]) ? $_[0] : '';
	tr!+! !;
	s!\%([a-fA-F0-9][a-fA-F0-9])!pack('C', hex($1))!eg;
	return $_;
	}


=item html_encode

Usage:
	my $html_str = &html_encode($str);

Formats string consistent with embedding in an HTML document.  Escapes the
"><& characters.

=cut

sub html_encode {
	local $_ = defined($_[0]) ? $_[0] : '';
	s!\&!\&amp;!g;
	s!\>!\&gt;!g;
	s!\<!\&lt;!g;
	s!\"!\&quot;!g;
	return $_;
	}

=item http_ex

Usage:
	my ($is_error, $error_msg, $URL, $ResponseBody, $ResponseCode, %Headers) = &http_ex($URL, $Method, $RequestBody, $AllowRedir, %CustomHeaders);

	if ($is_error) {
		print "<P><B>Error:</B> $error_msg.</P>\n";
		}

Error message contains an error fragment, suitable for inclusion as above.

=cut

sub http_ex {
	my ($URL, $Method, $RequestBody, $AllowRedir, %CustomHeaders) = @_;

	my ($is_error, $error_msg) = (0, '');

	my ($ResponseBody, $ResponseCode, %ResponseHeaders) = ('', 0);

	my $trace = '';

	Err: {

		my ($Request, %Headers);
		$Method = uc($_[1] ? $_[1] : 'GET'); # default to GET; force uppercase.
		$RequestBody = '' unless $RequestBody; # init
		$AllowRedir = $_[3] ? $_[3] : 0; # force numeric
		local $_;

		my ($clean_url, $host, $port, $path, $is_valid) = &parse_url( $URL );

		unless ($is_valid) {
			$error_msg = "web address '$URL' is not in a valid format";
			next Err;
			}

		%Headers = (
			'ACCEPT', '*/*',
			'ACCEPT-ENCODING', 'none',
			'ACCEPT-LANGUAGE', 'en-us',
			'CONNECTION', 'close',
			'PRAGMA', 'no-cache',
			'USER-AGENT', 'Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)',
			);

		foreach (keys %CustomHeaders) {
			$Headers{uc($_)} = $CustomHeaders{$_};
			}


		#changed 0052 security/tracking
		delete $Headers{'COOKIE'};
		$Headers{'X_FORWARDED_FOR'} = &query_env('REMOTE_ADDR');
		$Headers{'VIA'} = &query_env('SERVER_NAME');
		if (&query_env('HTTP_VIA')) {
			$Headers{'VIA'} .= "; " . &query_env('HTTP_VIA');
			}



		# Force HTTP/1.1 compliance:
		$Headers{'HOST'} = $host;
		if ($RequestBody) {
			$Headers{'CONTENT-LENGTH'} = length($RequestBody);
			$Headers{'CONTENT-TYPE'} = 'application/x-www-form-urlencoded' unless $Headers{'CONTENT-TYPE'};
			}

		# Cookies?
		unless ($Headers{'COOKIE'}) {
			$Headers{'COOKIE'} = '';
			foreach (keys %httpcookie) {
				$Headers{'COOKIE'} .= "$_=$httpcookie{$_};";
				}
			}

		my $CRLF = "\015\012";

		$Request = "$Method $path HTTP/1.0$CRLF";
		foreach (keys %Headers) {
			$Request .= "$_: $Headers{$_}$CRLF" if ($Headers{$_});
			}
		$Request .= "$CRLF";
		$Request .= $RequestBody;


		my $HexIP = inet_aton($host);
		unless ($HexIP) {
			$error_msg = "could not resolve hostname '$host' into an IP address";
			next Err;
			}

		unless (socket(HTTP, PF_INET, SOCK_STREAM, getprotobyname('tcp'))) {
			$error_msg = "could not create socket - $! ($^E)";
			next Err;
			}
		unless (connect(HTTP, sockaddr_in($port, $HexIP))) {
			$error_msg = "could not connect to '$host:$port' - $! ($^E)";
			next Err;
			}
		unless (binmode(HTTP)) {
			$error_msg = "could not set binmode on HTTP socket - $! - $^E";
			next Err;
			}

		select(HTTP);
		$| = 1;
		select(STDOUT);

		$trace = $Request;

		my $ExpectBytes = length($Request);

		my $SentBytes = send(HTTP, $Request, 0);

		if ($SentBytes != $ExpectBytes) {
			$error_msg = "unable to send a full $ExpectBytes - only send $SentBytes - $! ($^E)";
			close(HTTP);
			next Err;
			}

		my $FirstLine = <HTTP>;

		$trace .= $FirstLine;

		# Determine the HTTP version:
		if ($FirstLine =~ m!^HTTP/1.\d (\d+)!) {
			# Is HTTP 1.x, great.
			$ResponseCode = $1;

			# Get HTTP headers:
			while (defined($_ = <HTTP>)) {
				$trace .= $_;
				last unless m!^(.*?)\:\s+(.*?)\r?$!;
				$ResponseHeaders{uc($1)} = $2;
				if ((uc($1) eq 'SET-COOKIE') and ($2 =~ m!^(\w+)\=([^\;]+)!)) {
					$httpcookie{$1} = $2;
					}
				}

			# Get HTTP body:
			if ($ResponseHeaders{'TRANSFER-ENCODING'} and
					($ResponseHeaders{'TRANSFER-ENCODING'} =~ m!^chunked$!i)) {
				my $buffer;
				my $ReadLine;
				while (defined($ReadLine = <HTTP>)) {
					$NetStream .= $ReadLine;
					last unless ($ReadLine =~ m!^(\w+)\r?$!);
					last unless read(HTTP, $buffer, hex($1));
					$trace .= $buffer;
					$ResponseBody .= $buffer;
					}
				}
			else {
				$ResponseBody = '';
				while (defined($_ = <HTTP>)) {
					$ResponseBody .= $_;
					}
				$trace .= $ResponseBody;
				}
			}
		else {

			# This is an HTTP 0.9 response, which has no headers:

			# Set Code to 200 to satisfy 80% of customers:
			$ResponseCode = 200;

			$ResponseBody = $FirstLine;
			while (defined($_ = <HTTP>)) {
				$trace .= $_;
				$ResponseBody .= $_;
				}
			}
		close(HTTP);
		if ($AllowRedir and ($ResponseCode =~ m!^(301|302)$!)) {
			$httpInit = 0;
			$AllowRedir--;
			return http_ex(GetAbsoluteAddress($ResponseHeaders{'LOCATION'}, $URL), 'GET', '', $AllowRedir, %CustomHeaders);
			}
		else {
			$httpInit = 1;
			}
		last Err;
		}
	continue {
		$is_error = 1;
		}
	return ($is_error, $error_msg, $URL, $ResponseBody, $ResponseCode, %ResponseHeaders);
	}



=item clean_path($)

Function for stripping garbage from web page paths. It will collapse "." and
".." paths, collapse stacked /// slashes, and strip pound links.

Examples:

	"/foo/../bar/index.htm" => "/bar/index.htm"
	"/test.htm#top" => "/test.htm"
	"/../foo/bar" => "/foo/bar"
	"////top//level/../no_this/./file" => "/top/no_this/file"

This is used to cleanse links discovered in user input or in web pages that
crawler visits. It is also used to clean forbidden paths in the robots.txt
files (by cleaning both the original URL and the exclusion paths with the
same function, we minimize risk of hitting an exluded path.)

Dependencies:
	&Trim

=cut

sub clean_path {
	local $_ = &Trim($_[0]);

	# strip pound signs and all that follows (links internal to a page)
	s!\#.*$!!;

	# map /%7E to /~ (common source of duplicate URL's)
	s!\/\%7E!\/\~!ig;

	# map "/./" to "/"
	s!/+\./+!/!g;

	# map trailing "/." to "/"
	s!/+\.$!/!g;

	# map "/folder/../" => "/"
	while (s!([^/]+)/+\.\./+!/!) {}

	# map /../foo => /foo
	while (s!^/+\.\./+!/!) {}

	s!^/+\.\.$!/!;

	# collapse back-to-back slashes in the part before the query string:
	my ($path, $qs) = ($_, '');
	if (m!^(.*?)\?(.*)$!) {
		($path,$qs) = ($1,$2);
		}
	# collapse back-to-back slashes in the path
	$path =~ s!/+!/!g;

	$_ = $path;
	if ($qs ne '') {
		$_ .= "?$qs";
		}
	return $_;
	}


=item parse_url

Usage:

	my ($clean_url, $host, $port, $path, $is_valid) = &parse_url( $url );

=cut

sub parse_url {
	local $_ = defined($_[0]) ? $_[0] : '';
	my ($clean_url, $host, $port, $path, $is_valid) = ('', '', 80, '/', 0);

	# add trailing slash if none present
	$_ .= '/' if (m!^http://([^/]+)$!i);

	if (m!^http://([\w|\.|\-]+)\:?(\d*)/(.*)$!i) {
		#changed 0009 - force $host to lowercase
		($host, $port, $path, $is_valid) = (lc($1), $2, clean_path("/$3"), 1);
		#end changes
		$port = 80 unless $port;

		if ($port == 80) {
			$clean_url = "http://$host$path";
			}
		else {
			$clean_url = "http://$host:$port$path";
			}
		}
	return ($clean_url, $host, $port, $path, $is_valid);
	}


=item Trim

Usage:

	my $word = &Trim("  word  \t\n");

Strips whitespace and line breaks from the beginning and end of the argument.

=cut

sub Trim {
	local $_ = defined($_[0]) ? $_[0] : '';
	s!^[\r\n\s]+!!o;
	s![\r\n\s]+$!!o;
	return $_;
	}




=item http_clear_cookie

=cut

sub http_clear_cookie {
	%httpcookie = ();
	}

=item GetAbsoluteAddress

Usage:

	my ($abolute_url) = GetAbsoluteAddress($link_fragment, $full_path_context);

For example, you spider "http://xav.com/foo/bar/index.html" and find a link
to "../nikken.txt". You run:

print GetAbsoluteAddress("../nikken.txt", "http://xav.com/foo/bar/index.html");
^D
http://xav.com/foo/nikken.txt

=cut

sub GetAbsoluteAddress {
	my ($Link, $URL) = @_;
	if (($Link =~ m!^/!) and ($URL =~ m!^http://([^/]+)!i)) {
		# absolute link from top-level directory:
		$Link = "http://$1$Link";
		}
	elsif (($Link =~ m!^(\w+)\:!) and ($1 !~ m!^http$!i)) {
		# not http protocol (mailto:, ftp:, etc)
		$Link = '';
		}
	elsif (($Link !~ m!^http://!i) and ($URL =~ m!^(.*)/!)) {
		# http: protocol not specified, some kind of internal link, but we know
		# it doesn't start with "/" so it's relative.
		$Link = $1.'/'.$Link;
		}
	my ($clean_url, $host, $port, $path, $is_valid) = parse_url($Link);
	return $clean_url;
	}


