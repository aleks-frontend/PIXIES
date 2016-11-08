use strict;
sub version_c {
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

This library, common.pl, contains simple standalone functions which are shared among all modes.

=cut





sub rewrite_url {
	my ($level, $url) = @_;
	my $key = "rewrite_url_" . $level;
	unless ($Rules{$key}) {
		return $url;
		}

	# format is b_enabled,p1,p2,comment,b_verbose,

	unless ($private{$key}) {
		# create a cache copy

		my @rules = ();

		my $rule;
		foreach $rule (split(m!\&!, $Rules{$key})) {
			my @fields = split(m!\=!, $rule);
			next unless ($fields[0]);
			my @rule = ( &url_decode($fields[1]), &url_decode($fields[2]), $fields[4] );
			push(@rules, \@rule);
			}

		$private{$key} = \@rules;
		}

	my $init = $url;
	my $p_rules = $private{$key}; # pointer to an array of arrays
	my $p_rule;
	foreach $p_rule (@$p_rules) {
		my ($p1, $p2, $b_verbose) = @$p_rule;
		my $count = ($url =~ s!$p1!$p2!isg);
		if (($count) and ($b_verbose)) {
			my $h_init = &html_encode($init);
			print "<p><b>Status:</b> URL rewrite feature has converted $h_init to " . &html_encode($url) . ".</p>\n";
			}
		}
	return $url;
	}





sub check_regex {
	my ($pattern) = @_;
	my $err = '';
	Err: {
		if ($pattern =~ m!\?\{!) {
			$err = &pstr(50,&html_encode($pattern));
			next Err;
			}
		eval '"foo" =~ m!$pattern!;';
		if ($@) {
			$err = &pstr(51,&html_encode($pattern),&html_encode($@));
			undef($@);
			next Err;
			}
		}
	return $err;
	}





sub pstr {
	local $_ = $str[$_[0]];
	my $x = 0;
	foreach $x (1..((scalar @_) - 1)) {
		my $c = (s!\$s$x!$_[$x]!g);
		#&Assert($c != 0);
		}
	#&Assert( $_ !~ m!\$s\d! );
	return $_;
	}





sub ppstr {
	local $_ = $str[$_[0]];
	#&Assert(defined($_));
	my $x = 0;
	foreach $x (1..((scalar @_) - 1)) {
		#&Assert(defined($_[$x]));
		my $c = (s!\$s$x!$_[$x]!g);
		#&Assert($c != 0);
		}
	#&Assert( $_ !~ m!\$s\d! );
	print;
	}





sub pppstr {
	local $_ = $str[$_[0]];
	my $x = 0;
	foreach $x (1..((scalar @_) - 1)) {
		my $c = (s!\$s$x!$_[$x]!g);
		#&Assert($c != 0);
		}
	#&Assert( $_ !~ m!\$s\d! );

	if ($const{'is_cmd'}) {
		print "\n$_\n";
		}
	else {
		print "<p>" . $_ . "</p>\n";
		}
	}





sub CompressStrip {
	local $_ = defined($_[0]) ? $_[0] : '';
	$_ = &RawTranslate(" $_ ");
	s'\s+'  'og;
	eval($const{'code_strip_ignored_words'});
	die $@ if $@;
	s'\s+' 'og;
	s'^ '';
	s' $'';
	return " $_ ";
	}





sub create_conversion_code {
	my ($b_verbose) = @_;
	my $code = '';

	# Format of %charset is { char_number => [ @values, $name ] }
	# where @values represents what the character should be converted to under 4 circumstances
	# -1 means "strip, is non-word"
	#  0 means "leave as is"
	# any other string value is the value to be converted to


	my %base_charset = (

		  9 => [   -1,   -1,   -1,   -1, 'Horizontal tab'],
		 10 => [   -1,   -1,   -1,   -1, 'Line feed'],

		 13 => [   -1,   -1,   -1,   -1, 'Carriage Return'],

		 32 => [   -1,   -1,   -1,   -1, 'Space'],
		 33 => [   -1,   -1,   -1,   -1, 'Exclamation mark'],
		 34 => [   -1,   -1,   -1,   -1, 'Quotation mark'],
		 35 => [   -1,   -1,   -1,   -1, 'Number sign'],
		 36 => [   -1,   -1,   -1,   -1, 'Dollar sign'],
		 37 => [   -1,   -1,   -1,   -1, 'Percent sign'],
		 38 => [   -1,   -1,   -1,   -1, 'Ampersand'],
		 39 => [   -1,   -1,   -1,   -1, 'Apostrophe'],
		 40 => [   -1,   -1,   -1,   -1, 'Left parenthesis'],
		 41 => [   -1,   -1,   -1,   -1, 'Right parenthesis'],
		 42 => [   -1,   -1,   -1,   -1, 'Asterisk'],
		 43 => [   -1,   -1,   -1,   -1, 'Plus sign'],
		 44 => [   -1,   -1,   -1,   -1, 'Comma'],
		 45 => [   -1,   -1,   -1,   -1, 'Hyphen'],
		 46 => [   -1,   -1,   -1,   -1, 'Period (fullstop)'],
		 47 => [   -1,   -1,   -1,   -1, 'Solidus (slash)'],
		 48 => [    0,    0,    0,    0, 'Digit 0'],
		 49 => [    0,    0,    0,    0, 'Digit 1'],
		 50 => [    0,    0,    0,    0, 'Digit 2'],
		 51 => [    0,    0,    0,    0, 'Digit 3'],
		 52 => [    0,    0,    0,    0, 'Digit 4'],
		 53 => [    0,    0,    0,    0, 'Digit 5'],
		 54 => [    0,    0,    0,    0, 'Digit 6'],
		 55 => [    0,    0,    0,    0, 'Digit 7'],
		 56 => [    0,    0,    0,    0, 'Digit 8'],
		 57 => [    0,    0,    0,    0, 'Digit 9'],
		 58 => [   -1,   -1,   -1,   -1, 'Colon'],
		 59 => [   -1,   -1,   -1,   -1, 'Semicolon'],
		 60 => [   -1,   -1,   -1,   -1, 'Less than'],
		 61 => [   -1,   -1,   -1,   -1, 'Equals sign'],
		 62 => [   -1,   -1,   -1,   -1, 'Greater than'],
		 63 => [   -1,   -1,   -1,   -1, 'Question mark'],
		 64 => [   -1,   -1,   -1,   -1, 'Commercial at'],
		 65 => [  'a',    0,  'a',    0, 'Capital A'],
		 66 => [  'b',    0,  'b',    0, 'Capital B'],
		 67 => [  'c',    0,  'c',    0, 'Capital C'],
		 68 => [  'd',    0,  'd',    0, 'Capital D'],
		 69 => [  'e',    0,  'e',    0, 'Capital E'],
		 70 => [  'f',    0,  'f',    0, 'Capital F'],
		 71 => [  'g',    0,  'g',    0, 'Capital G'],
		 72 => [  'h',    0,  'h',    0, 'Capital H'],
		 73 => [  'i',    0,  'i',    0, 'Capital I'],
		 74 => [  'j',    0,  'j',    0, 'Capital J'],
		 75 => [  'k',    0,  'k',    0, 'Capital K'],
		 76 => [  'l',    0,  'l',    0, 'Capital L'],
		 77 => [  'm',    0,  'm',    0, 'Capital M'],
		 78 => [  'n',    0,  'n',    0, 'Capital N'],
		 79 => [  'o',    0,  'o',    0, 'Capital O'],
		 80 => [  'p',    0,  'p',    0, 'Capital P'],
		 81 => [  'q',    0,  'q',    0, 'Capital Q'],
		 82 => [  'r',    0,  'r',    0, 'Capital R'],
		 83 => [  's',    0,  's',    0, 'Capital S'],
		 84 => [  't',    0,  't',    0, 'Capital T'],
		 85 => [  'u',    0,  'u',    0, 'Capital U'],
		 86 => [  'v',    0,  'v',    0, 'Capital V'],
		 87 => [  'w',    0,  'w',    0, 'Capital W'],
		 88 => [  'x',    0,  'x',    0, 'Capital X'],
		 89 => [  'y',    0,  'y',    0, 'Capital Y'],
		 90 => [  'z',    0,  'z',    0, 'Capital Z'],
		 91 => [   -1,   -1,   -1,   -1, 'Left square bracket'],
		 92 => [   -1,   -1,   -1,   -1, 'Reverse solidus (backslash)'],
		 93 => [   -1,   -1,   -1,   -1, 'Right square bracket'],
		 94 => [   -1,   -1,   -1,   -1, 'Caret'],
		 95 => [   -1,   -1,   -1,   -1, 'Horizontal bar (underscore)'],
		 96 => [   -1,   -1,   -1,   -1, 'Acute accent'],
		 97 => [    0,    0,    0,    0, 'Small a'],
		 98 => [    0,    0,    0,    0, 'Small b'],
		 99 => [    0,    0,    0,    0, 'Small c'],
		100 => [    0,    0,    0,    0, 'Small d'],
		101 => [    0,    0,    0,    0, 'Small e'],
		102 => [    0,    0,    0,    0, 'Small f'],
		103 => [    0,    0,    0,    0, 'Small g'],
		104 => [    0,    0,    0,    0, 'Small h'],
		105 => [    0,    0,    0,    0, 'Small i'],
		106 => [    0,    0,    0,    0, 'Small j'],
		107 => [    0,    0,    0,    0, 'Small k'],
		108 => [    0,    0,    0,    0, 'Small l'],
		109 => [    0,    0,    0,    0, 'Small m'],
		110 => [    0,    0,    0,    0, 'Small n'],
		111 => [    0,    0,    0,    0, 'Small o'],
		112 => [    0,    0,    0,    0, 'Small p'],
		113 => [    0,    0,    0,    0, 'Small q'],
		114 => [    0,    0,    0,    0, 'Small r'],
		115 => [    0,    0,    0,    0, 'Small s'],
		116 => [    0,    0,    0,    0, 'Small t'],
		117 => [    0,    0,    0,    0, 'Small u'],
		118 => [    0,    0,    0,    0, 'Small v'],
		119 => [    0,    0,    0,    0, 'Small w'],
		120 => [    0,    0,    0,    0, 'Small x'],
		121 => [    0,    0,    0,    0, 'Small y'],
		122 => [    0,    0,    0,    0, 'Small z'],
		123 => [   -1,   -1,   -1,   -1, 'Left curly brace'],
		124 => [   -1,   -1,   -1,   -1, 'Vertical bar'],
		125 => [   -1,   -1,   -1,   -1, 'Right curly brace'],
		126 => [   -1,   -1,   -1,   -1, 'Tilde'],
		);

	my %extended_charset = (


		138 => [  's',  'S', chr(154),    0, 'Scaron'],

		140 => [ 'oe', 'OE', chr(156),    0, 'OE ligature'],

		142 => [  'z',  'Z', chr(158),    0, ''],

		154 => [  's',  's',    0,    0, 'scaron'],

		156 => [ 'oe', 'oe',    0,    0, 'oe ligature'],

		158 => [  'z',  'z',    0,    0, ''],
		159 => [  'y',  'Y', chr(255),    0, ''],
		160 => [   -1,   -1,   -1,   -1, 'Nonbreaking space'],
		161 => [   -1,   -1,   -1,   -1, 'Inverted exclamation'],
		162 => [   -1,   -1,   -1,   -1, 'Cent sign'],
		163 => [   -1,   -1,   -1,   -1, 'Pound sterling'],
		164 => [   -1,   -1,   -1,   -1, 'General currency sign'],
		165 => [   -1,   -1,   -1,   -1, 'Yen sign'],
		166 => [   -1,   -1,   -1,   -1, 'Broken vertical bar'],
		167 => [   -1,   -1,   -1,   -1, 'Section sign'],
		168 => [   -1,   -1,   -1,   -1, 'Diæresis / Umlaut'],
		169 => [   -1,   -1,   -1,   -1, 'Copyright'],
		170 => [   -1,   -1,   -1,   -1, 'Feminine ordinal'],
		171 => [   -1,   -1,   -1,   -1, 'Left angle quote, guillemet left'],
		172 => [   -1,   -1,   -1,   -1, 'Not sign'],
		173 => [   -1,   -1,   -1,   -1, 'Soft hyphen'],
		174 => [   -1,   -1,   -1,   -1, 'Registered trademark'],
		175 => [   -1,   -1,   -1,   -1, 'Macron accent'],
		176 => [   -1,   -1,   -1,   -1, 'Degree sign'],
		177 => [   -1,   -1,   -1,   -1, 'Plus or minus'],
		178 => [   -1,   -1,   -1,   -1, 'Superscript 2'],
		179 => [   -1,   -1,   -1,   -1, 'Superscript 3'],
		180 => [   -1,   -1,   -1,   -1, 'Acute accent'],
		181 => [   -1,   -1,   -1,   -1, 'Micro sign'],
		182 => [   -1,   -1,   -1,   -1, 'Paragraph sign'],
		183 => [   -1,   -1,   -1,   -1, 'Middle dot'],
		184 => [   -1,   -1,   -1,   -1, 'Cedilla'],
		185 => [   -1,   -1,   -1,   -1, 'Superscript 1'],
		186 => [   -1,   -1,   -1,   -1, 'Masculine ordinal'],
		187 => [   -1,   -1,   -1,   -1, 'Right angle quote, guillemet right'],
		188 => [   -1,   -1,   -1,   -1, 'Fraction one-fourth'],
		189 => [   -1,   -1,   -1,   -1, 'Fraction one-half'],
		190 => [   -1,   -1,   -1,   -1, 'Fraction three-fourths'],
		191 => [   -1,   -1,   -1,   -1, 'Inverted question mark'],
		192 => [  'a',  'A', chr(224),    0, 'Capital A, grave accent'],
		193 => [  'a',  'A', chr(225),    0, 'Capital A, acute accent'],
		194 => [  'a',  'A', chr(226),    0, 'Capital A, circumflex'],
		195 => [  'a',  'A', chr(227),    0, 'Capital A, tilde'],
		196 => [ 'ae', 'Ae', chr(228),    0, 'Capital A, diaeresis / umlaut'],
		197 => [  'a',  'A', chr(229),    0, 'Capital A, ring'],
		198 => [ 'ae', 'AE', chr(230),    0, 'Capital AE ligature'],
		199 => [  'c',  'c', chr(231),    0, 'Capital C, cedilla'],
		200 => [  'e',  'E', chr(232),    0, 'Capital E, grave accent'],
		201 => [  'e',  'E', chr(233),    0, 'Capital E, acute accent'],
		202 => [  'e',  'E', chr(234),    0, 'Capital E, circumflex'],
		203 => [  'e',  'E', chr(235),    0, 'Capital E, diaeresis / umlaut'],
		204 => [  'i',  'I', chr(236),    0, 'Capital I, grave accent'],
		205 => [  'i',  'I', chr(237),    0, 'Capital I, acute accent'],
		206 => [  'i',  'I', chr(238),    0, 'Capital I, circumflex'],
		207 => [  'i',  'I', chr(239),    0, 'Capital I, diaeresis / umlaut'],
		208 => [  'd',  'D', chr(240),    0, 'Capital Eth, Icelandic'],
		209 => [  'n',  'N', chr(241),    0, 'Capital N, tilde'],
		210 => [  'o',  'O', chr(242),    0, 'Capital O, grave accent'],
		211 => [  'o',  'O', chr(243),    0, 'Capital O, acute accent'],
		212 => [  'o',  'O', chr(244),    0, 'Capital O, circumflex'],
		213 => [  'o',  'O', chr(245),    0, 'Capital O, tilde'],
		214 => [ 'oe', 'Oe', chr(246),    0, 'Capital O, diaeresis / umlaut'],
		215 => [   -1,   -1,   -1,   -1, 'Multiply sign'],
		216 => [  'o',  'O', chr(248),    0, 'Capital O, slash'],
		217 => [  'u',  'U', chr(249),    0, 'Capital U, grave accent'],
		218 => [  'u',  'U', chr(250),    0, 'Capital U, acute accent'],
		219 => [  'u',  'U', chr(251),    0, 'Capital U, circumflex'],
		220 => [ 'ue', 'Ue', chr(252),    0, 'Capital U, diaeresis / umlaut'],
		221 => [  'y',  'Y', chr(253),    0, 'Capital Y, acute accent'],
		222 => [  'p',  'P', chr(254),    0, 'Capital Thorn, Icelandic'],
		223 => [ 'ss', 'ss',    0,    0, 'Small sharp s, German sz'],
		224 => [  'a',  'a',    0,    0, 'Small a, grave accent'],
		225 => [  'a',  'a',    0,    0, 'Small a, acute accent'],
		226 => [  'a',  'a',    0,    0, 'Small a, circumflex'],
		227 => [  'a',  'a',    0,    0, 'Small a, tilde'],
		228 => [ 'ae', 'ae',    0,    0, 'Small a, diaeresis / umlaut'],
		229 => [  'a',  'a',    0,    0, 'Small a, ring'],
		230 => [ 'ae', 'ae',    0,    0, 'Small ae ligature'],
		231 => [  'c',  'c',    0,    0, 'Small c, cedilla'],
		232 => [  'e',  'e',    0,    0, 'Small e, grave accent'],
		233 => [  'e',  'e',    0,    0, 'Small e, acute accent'],
		234 => [  'e',  'e',    0,    0, 'Small e, circumflex'],
		235 => [  'e',  'e',    0,    0, 'Small e, diaeresis / umlaut'],
		236 => [  'i',  'i',    0,    0, 'Small i, grave accent'],
		237 => [  'i',  'i',    0,    0, 'Small i, acute accent'],
		238 => [  'i',  'i',    0,    0, 'Small i, circumflex'],
		239 => [  'i',  'i',    0,    0, 'Small i, diaeresis / umlaut'],
		240 => [  'o',  'o',    0,    0, 'Small eth, Icelandic'],
		241 => [  'n',  'n',    0,    0, 'Small n, tilde'],
		242 => [  'o',  'o',    0,    0, 'Small o, grave accent'],
		243 => [  'o',  'o',    0,    0, 'Small o, acute accent'],
		244 => [  'o',  'o',    0,    0, 'Small o, circumflex'],
		245 => [  'o',  'o',    0,    0, 'Small o, tilde'],
		246 => [ 'oe', 'oe',    0,    0, 'Small o, diaeresis / umlaut'],
		247 => [   -1,   -1,   -1,   -1, 'Division sign'],
		248 => [  'o',  'o',    0,    0, 'Small o, slash'],
		249 => [  'u',  'u',    0,    0, 'Small u, grave accent'],
		250 => [  'u',  'u',    0,    0, 'Small u, acute accent'],
		251 => [  'u',  'u',    0,    0, 'Small u, circumflex'],
		252 => [ 'ue', 'ue',    0,    0, 'Small u, diaeresis / umlaut'],
		253 => [  'y',  'y',    0,    0, 'Small y, acute accent'],
		254 => [  'p',  'p',    0,    0, 'Small thorn, Icelandic'],
		255 => [  'y',  'y',    0,    0, 'Small y, diaeresis / umlaut'],
		);





=item reserved

	The %reserved hash contains the Latin character index of characters that FDSE uses internally to delimit data, including newlines, whitespace, and the equals sign.  These characters are *always* stripped from incoming data regardless of locale settings.

=cut

	my %reserved = (
		34 => 1,
		38 => 1,
		60 => 1,
		62 => 1,
		9 => 1,
		95 => 1,
		10 => 1,
		13 => 1,
		32 => 1,
		61 => 1,
		);






=item named_entities

	The %named_entities hash maps HTML entities to their Latin character index.

	Numeric formats like "#ddd" and "xHH" are programmatically added to the hash -- there is no need to manually add them.

	Named entities which do not map to alphanumeric "word" characters, like "amp", are omitted as an optimization, since those characters are never included in the index.

=cut

	my %named_entities = (
		'#338' => 140,
		'#339' => 156,
		'#352' => 138,
		'#353' => 154,
		'AElig' => 198,
		'Aacute' => 193,
		'Acirc' => 194,
		'Agrave' => 192,
		'Aring' => 197,
		'Atilde' => 195,
		'Auml' => 196,
		'Ccedil' => 199,
		'ETH' => 208,
		'Eacute' => 201,
		'Ecirc' => 202,
		'Egrave' => 200,
		'Euml' => 203,
		'Iacute' => 205,
		'Icirc' => 206,
		'Igrave' => 204,
		'Iuml' => 207,
		'Ntilde' => 209,
		'OElig' => 140,
		'Oacute' => 211,
		'Ocirc' => 212,
		'Ograve' => 210,
		'Oslash' => 216,
		'Otilde' => 213,
		'Ouml' => 214,
		'Scaron' => 138,
		'THORN' => 222,
		'Uacute' => 218,
		'Ucirc' => 219,
		'Ugrave' => 217,
		'Uuml' => 220,
		'Yacute' => 221,
		'aacute' => 225,
		'acirc' => 226,
		'aelig' => 230,
		'agrave' => 224,
		'aring' => 229,
		'atilde' => 227,
		'auml' => 228,
		'ccedil' => 231,
		'eacute' => 233,
		'ecirc' => 234,
		'egrave' => 232,
		'eth' => 240,
		'euml' => 235,
		'iacute' => 237,
		'icirc' => 238,
		'igrave' => 236,
		'iquest' => 191,
		'iuml' => 239,
		'ntilde' => 241,
		'oacute' => 243,
		'ocirc' => 244,
		'oelig' => 156,
		'ograve' => 242,
		'oslash' => 248,
		'otilde' => 245,
		'ouml' => 246,
		'scaron' => 154,
		'sup1' => 185,
		'sup2' => 178,
		'sup3' => 179,
		'szlig' => 223,
		'thorn' => 254,
		'uacute' => 250,
		'ucirc' => 251,
		'ugrave' => 249,
		'uuml' => 252,
		'yacute' => 253,
		'yuml' => 255,
		);




	my %entity_name_by_num = ();
	%entity_value_by_name = ();

	my ($name, $number) = ('', 0);
	while (($name, $number) = each %named_entities) {
		$entity_name_by_num{ $number } .= "$name ";
		$entity_value_by_name{ $name } = chr($number);
		}






	my %ac_map_cs = ();
	my @nonword = ();
	my $focus = (2 + (-2 * $Rules{'character conversion: accent insensitive'})) + (1 + (-1 * $Rules{'character conversion: case insensitive'}));

	my $chx = 0;

	if (not $b_verbose) {
		for (my $chx = 255; $chx > 0; $chx--) {
			my $ch = chr($chx);

			my $value = -1;
			if (defined($base_charset{$chx})) {
				$value = $base_charset{$chx}[$focus];
				}
			elsif (defined($extended_charset{$chx})) {
				$value = $extended_charset{$chx}[$focus];
				}
			if ($value eq '-1') {
				$nonword[$chx] = 1;
				}
			elsif ($value ne '0') {
				$ac_map_cs{$value} .= $ch;
				}
			}
		}
	else {

print <<"EOM";

<table border="1">
<tr>
	<th>$str[62]</th>
	<th>$str[63]</th>
	<th>$str[61]</th>
	<th>$str[60]</th>
	<th>$str[59]<br />$str[57]</th>
	<th>$str[59]<br />$str[56]</th>
	<th>$str[58]<br />$str[57]</th>
	<th>$str[58]<br />$str[56]</th>
</tr>

EOM
		for (my $chx = 255; $chx > 0; $chx--) {
			my $ch = chr($chx);
			my @data = (-1, -1, -1, -1, 'Unused'); #default
			if (defined($base_charset{$chx})) {
				for (0..4) {
					$data[$_] = $base_charset{$chx}[$_];
					}
				}
			elsif (defined($extended_charset{$chx})) {
				for (0..4) {
					$data[$_] = $extended_charset{$chx}[$_];
					}
				}
			print qq!<tr><td align="center"><tt>! . substr(1000 + $chx, 1, 3) . qq!</tt></td><td align="center">$data[4]<br /></td><td nowrap="nowrap"><tt>!;

			if ($entity_name_by_num{$chx}) {
				my @list = split(m!\s+!, $entity_name_by_num{$chx});
				my $en;
				foreach $en (@list) {
					next unless ($en);
					print '&' . "amp;$en; - &$en;<br />";
					}
				}
			else {
				print "<br />";
				}
			print qq!</tt></td><td class="fdtan" align="center"><b>! . &html_encode($ch) . "<br /></b></td>";
			my $zz = 0;
			for $zz (0..3) {
				if ($zz == $focus) {
					if ($data[$zz] eq '-1') {
						print qq!<td align="center" bgcolor="#cccccc">---</td>\n!;
						$nonword[$chx] = 1;
						}
					elsif ($data[$zz] eq '0') {
						print qq!<td class="fdtan" align="center"><b>$ch</b></td>\n!;
						}
					else {
						print qq!<td class="fdtan" align="center"><b>$data[$zz]</b></td>\n!;
						# format {dest} = {orig orig orig}
						$ac_map_cs{$data[$zz]} .= $ch;
						}
					}
				else {
					if ($data[$zz] eq '-1') {
						print qq!<td align="center"><br /></td>\n!;
						}
					elsif ($data[$zz] eq '0') {
						print qq!<td align="center">$ch</td>\n!;
						}
					else {
						print qq!<td align="center">$data[$zz]</td>\n!;
						}
					}
				}
			print "</tr>\n";
			next;
			}
		print '</table>';
		}



	# build the code to strip spans of non-word characters:

	my @kill = ();
	foreach (1..255) {
		next unless ($nonword[$_]);
		push(@kill,quotemeta(chr($_)));
		}
	my $frag = join("|",@kill);

	my $cnw = '';
	if ($frag) {
		$cnw = "s'($frag)+' 'og;\n";
		}





	my $ccc = '';

	foreach (keys %ac_map_cs) {

		my $ch = ();
		my @chars = ();
		foreach $ch (split(m!!, $ac_map_cs{$_})) {
			push(@chars, quotemeta($ch));
			}

		my $in = join('|',@chars);
		if (1 == length($in)) {
			$ccc .= "s!$in!$_!og;\n";
			}
		elsif ($in) {
			$ccc .= "s!($in)!$_!og;\n";
			}
		}



	# Add numeric entities for 1..255:

	for (1..255) {
		next if ($nonword[$_]);
		$entity_value_by_name{ "#$_" } = chr($_);
		}


	@kill = ();
	foreach (keys %reserved) {
		push(@kill, quotemeta(chr($_)));
		}
	$frag = join('|', @kill);
	my $csr = '';
	if ($frag) {
		$csr = "s!($frag)+! !sog;\n";
		}


	$code = <<'EOM';

# Replace all hex entities:
s!&#x(..);!chr(hex($1))!eisg;

# Replace all numeric and named entities with their single-character equivalent; unknown entities will be replaced with spaces:

s!&(\S+?);!{$entity_value_by_name{$1} || ' '}!esg;


EOM

	$code .= $csr;
	$code .= $ccc;
	$code .= $cnw;

	return $code;
	}


=item RawTranslate

Usage:
	my $lc_ai_string = &RawTranslate($string);

Returns a lowercase, accent-stripped version on its input.  Replaces HTML-encoded characters with their ASCII equivalents.

This function is called mainly by &CompressStrip; also by &LoadRules when preparing the code for ignore words.

See http://www.utoronto.ca/webdocs/HTMLdocs/NewHTML/iso_table.html

Dependencies:

	Called by: CompressStrip
	Called by: LoadRules

	Global: %Rules - 1

	Dependency: none

=cut

sub RawTranslate {
	local $_ = defined($_[0]) ? $_[0] : '';
	unless (defined($const{'conversion_code'})) {
		$const{'conversion_code'} = &create_conversion_code(0);
		}
	eval $const{'conversion_code'};
	return $_;
	}





sub clean_path {
	my $path = &Trim($_[0]);


	#if-fdse; convert space to %20
	$path =~ s! !%20!g;

	# strip pound signs and all that follows (links internal to a page)
	$path =~ s!\#.*$!!;

	my ($base, $question, $query) = ($path, '', '');

	if ($path =~ m!^(.*?)(\?)(.*)$!s) {
		($base, $question, $query) = ($1, $2, $3);
		}

	local $_ = $base;

	# map /%7E to /~ (common source of duplicate URL's)
	s!\/\%7E!\/\~!ig;

	# map "/./" to "/"
	s!/+\./+!/!g;

	# map trailing "/." to "/"
	s!/+\.$!/!g;


	# nuke all leading "/../" entries (meaningless for us)
	# map /../foo => /foo
	while (s!^/+\.\./+!/!) {}


	# map "folder/../" => "/"
	# map "bar/folder/../" => "bar//"
	while (s!([^/]+)/+\.\./+!/!) {}


	# map "/folder/.." => "/"
	s!/+([^/]+)/+\.\.$!/!;



	# collapse back-to-back slashes in the path
	s!/+!/!g;

	return $_ . $question . $query;
	}





sub parse_url_ex {
	local $_ = defined($_[0]) ? $_[0] : '';
	my ($err,$clean_url,$host,$port,$path) = ('','','',80,'/');

	# add trailing slash if none present
	if (m!^http://([^/]+?)(\?|\#)(.*)$!i) {
		$_ = "http://$1/$2$3";
		}
	elsif (m!^http://([^/]+)$!i) {
		$_ .= '/';
		}
	# http://xav.com#a => http://xav.com/#a
	# http://xav.com?foo => http://xav.com/?foo
	# http://xav.com => http://xav.com

	unless (m!^http://([\w|\.|\-]+)\:?(\d*)/(.*)$!i) {
		$err = &pstr(53,&html_encode($_));
		}
	else {
		($host, $port, $path) = (lc($1), $2, &clean_path("/$3"));
		$port = 80 unless $port;
		if ($port == 80) {
			$clean_url = "http://$host$path";
			}
		else {
			$clean_url = "http://$host:$port$path";
			}
		}
	return ($err,$clean_url,$host,$port,$path);
	}





sub SelectAdEx {
	my ($p_terms) = @_;
	my @Ads = ('','','','');

	my $err = '';
	Err: {
		last Err if ($const{'mode'} == 3);

		my $text = '';
		($err, $text) = &ReadFileL('ads.xml');
		next Err if ($err);

		my $ads_ver = 1;
		if ($text =~ m! version=\"(\d)!s) {
			$ads_ver = $1;
			}

		last Err unless ($text =~ m!<FDSE:Ads placement="(.*?)">(.+)</FDSE:Ads>!s);
		my ($master_pos_str, $ads) = ($1, $2);
		next unless ($master_pos_str);

		my $term_pattern = '';
		foreach (@$p_terms) {
			$term_pattern .= quotemeta($_) . '|';
			}
		if ($FORM{'Realm'}) {
			$term_pattern .= "realm:$FORM{'Realm'}|";
			}
		$term_pattern =~ s!\|$!!;
		$term_pattern = "($term_pattern)" if ($term_pattern);

		my @match_ads = ();
		my @all_ads = ();
		foreach (split(m!<FDSE:Ad !s, $ads)) {
			next unless (m!(.*?)>(.*)</FDSE:Ad>!s);
			my %adinfo = ();
			$adinfo{'text'} = $2;
			my $attributes = $1;
			while ($attributes =~ m!^\s*(\S+)\=\"(.*?)\"(.*)$!s) {
				$adinfo{$1} = $2;
				$attributes = $3;
				}
			if ($ads_ver > 1) {
				foreach (keys %adinfo) {
					$adinfo{$_} = &url_decode($adinfo{$_});
					}
				}
			push(@all_ads, \%adinfo);
			}


		# for each of 4 positions, select an ad:
		my $i = 1;
		for ($i = 1; $i < 5; $i++) {

			# skip if we've globally decided not to put ads in this position
			next unless ($master_pos_str =~ m!$i!);

			my ($matchweight, $weight) = (0, 0);
			my (@my_ads, @match_ads) = ();

			# Select an ad for position $i
			my $p_data = ();
			foreach $p_data (@all_ads) {

				# skip this ad if we've decided to to show it at position $i:
				next unless ($$p_data{'placement'} =~ m!$i!);

				# ok, do we have search words to work with, and are there keywords with this ad?
				my $is_keyword_match = 0;
				if (($term_pattern) and ($$p_data{'keywords'})) {

					# Is there a keyword match?
					if ($$p_data{'keywords'} =~ m!$term_pattern!i) {
						$matchweight += $$p_data{'weight'};
						push(@match_ads, $p_data);
						$is_keyword_match = 1;
						}
					}

				# have they decided that this ad *only* appears for keyword matches?
				if (($$p_data{'kw'}) and (not $is_keyword_match)) {
					# sorry maybe next time:
					next;
					}

				$weight += $$p_data{'weight'};
				push(@my_ads, $p_data);
				}
			if ($matchweight) {
				$weight = $matchweight;
				@my_ads = @match_ads;
				}

			my $num = int($weight * rand());

			foreach $p_data (@my_ads) {
				$num -= $$p_data{'weight'};
				next if ($num > 0);

				# Increment the logfile
				my $logfile = "ads_hitcount_$$p_data{'ident'}.txt";
				my $hits = 0;
				if ((not (-e $logfile)) and (open(FILE, ">$logfile" ))) {
					print FILE 0;
					close(FILE);
					}
				if (open(FILE, "+<$logfile")) {
					$hits = <FILE>;
					seek(FILE, 0, 0);
					print FILE ++$hits;
					close(FILE);
					}
				$Ads[$i-1] = $$p_data{'text'};
				last;
				}
			}
		}
	return @Ads;
	}





sub PrintTemplate {
	my ($b_return_as_string, $file, $language, $p_replace, $p_visited, $p_cache) = @_;
	my $return_text = '';

	my $err = '';
	Err: {

		# Initialize:
		unless ($p_replace) {
			my %hash = ();
			$p_replace = \%hash;
			}
		$$p_replace{'version'} = $VERSION;

		unless ($p_visited) {
			my %hash = ();
			$p_visited = \%hash;
			}

		my $text = '';
		if (($p_cache) and ('HASH' eq ref($p_cache)) and ($$p_cache{$file})) {
			$text = $$p_cache{$file};
			}
		else {
			my $fullfile = '';
			my $base = "templates/$language/";
			my $max_parents = 12;
			for (0..$max_parents) {
				$fullfile = $base . ('../' x $_) . $file;
				$fullfile =~ s!/+!/!g;
				last if (-e $fullfile);
				}
			unless (-e $fullfile) {
				$err = "unable to find file '$file'";
				next Err;
				}
			if ($fullfile =~ m!([^\\|/]+)$!) {
				$$p_visited{$1}++;
				}
			($err, $text) = &ReadFileL($fullfile);
			next Err if ($err);

			if (($p_cache) and ('HASH' eq ref($p_cache))) {
				$$p_cache{$file} = $text;
				}
			}



		#conditionals
		foreach (reverse sort keys %$p_replace) {
			next unless (defined($_));
			$$p_replace{$_} = '' if (not defined($$p_replace{$_}));
			if ($$p_replace{$_}) {
				# true
				$text =~ s!<%\s*if\s+$_\s*%\>(.*?)<%\s*end\s*if\s*%>!$1!eisg;
				$text =~ s!<%\s*(if\s+not|unless)\s+$_\s*%>.*?<%\s*end\s*if\s*%>!!eisg;
				}
			else {
				# false
				$text =~ s!<%\s*if\s+$_\s*%>.*?<%\s*end\s*if\s*%>!!isg;
				$text =~ s!<%\s*(if\s+not|unless)\s+$_\s*%>(.*?)<%\s*end\s*if\s*%>!$2!eisg;
				}
			}



		foreach (reverse sort keys %$p_replace) {
			#revcompat
			$text =~ s!\$$_!$$p_replace{$_}!isg;
			$text =~ s!\_\_$_\_\_!$$p_replace{$_}!isg;
			#/revcompat
			$text =~ s!\%$_\%!$$p_replace{$_}!isg;
			}

		my $pattern = '<!--#(include file|include virtual|echo var)=\"(.*?)\" -->';

		while ($text =~ m!^(.*?)$pattern(.*)$!is) {
			my ($start, $c1, $incfile, $end) = ($1, lc($2), $3, $4);

			if ($b_return_as_string) {
				$return_text .= $start;
				}
			else {
				print $start;
				}

			if ($c1 eq 'echo var') {
				my $var = uc($incfile);
				my $vardata = '';
				if ($var eq 'DATE_GMT') {
					$vardata = scalar gmtime();
					}
				elsif ($var eq 'DATE_LOCAL') {
					$vardata = scalar localtime();
					}
				elsif ($var eq 'DOCUMENT_NAME') {
					$vardata = $1 if ($0 =~ m!([^\\|/]+)$!);
					}
				elsif ($var eq 'DOCUMENT_URI') {
					$vardata = &query_env('SCRIPT_NAME');
					}
				elsif ($var eq 'LAST_MODIFIED') {
					$vardata = scalar localtime( (stat($0))[9] );
					}
				elsif (defined($ENV{$var})) {
					$vardata = &query_env($var);
					}

				if ($b_return_as_string) {
					$return_text .= $vardata;
					}
				else {
					print $vardata;
					}

				}
			else {

				my $basefile = $incfile;
				if ($incfile =~ m!.*(\\|/)(.*?)$!) {
					$basefile = $2;
					}

				my $outstr = '';

				# Do we have a file extension?
				if ($basefile !~ m!\.(txt|htm|html|shtml|stm|inc)$!i) {
					$outstr = "<!-- FDSE: not including file '$incfile' because does not have a text/html file extension -->";
					}
				elsif ($$p_visited{$basefile}) {
					$outstr = "<!-- FDSE: loop avoidance: already parsed file '$basefile' -->";
					}
				else {
					$$p_visited{$basefile}++;
					$outstr .= &PrintTemplate( $b_return_as_string, $incfile, $language, $p_replace, $p_visited );
					}

				if ($b_return_as_string) {
					$return_text .= $outstr;
					}
				else {
					print $outstr;
					}

				}


			$text = $end;
			}

		if ($b_return_as_string) {
			$return_text .= $text;
			}
		else {
			print $text;
			}

		last Err;
		}
	continue {
		if ($b_return_as_string) {
			$return_text .= &pstr(64,$err);
			}
		else {
			&ppstr(64,$err);
			}
		}
	return $return_text;
	}





sub ReadInput {
	# Initialize:
	%FORM = ();
	my @Pairs = ();
	if (&query_env('REQUEST_METHOD') eq 'POST') {
		my $buffer = '';
		read(STDIN, $buffer, &query_env('CONTENT_LENGTH',0));
		&untaintme(\$buffer);
		@Pairs = split(m!\&!, $buffer);
		}
	elsif ($ENV{'QUERY_STRING'}) {
		@Pairs = split(m!\&!, &query_env('QUERY_STRING'));
		}
	else {
		@Pairs = @ARGV;
		}
	#changed 0054 - support for multi-select
	my ($name, $value);
	foreach (@Pairs) {
		next unless (m!^(.*?)=(.*)$!);
		($name, $value) = (&url_decode($1), &url_decode($2));
		if (defined($FORM{$name})) {
			# multi
			$FORM{$name} .= ",$value";
			}
		else {
			$FORM{$name} = $value;
			}
		}
	#changed 0053 - support for undefined-alt-value
	foreach (keys %FORM) {
		next unless (m!^(.*)_udav$!);
		next if (defined($FORM{$1}));
		$FORM{$1} = $FORM{$_};
		}
	$FORM{'Mode'} = '' if (not (defined($FORM{'Mode'})));
	}





sub db_exec {
	my ($statement) = @_;

	my $dbh = undef();
	my $sth = undef();

	my $err = '';
	Err: {
		$err = &get_dbh(\$dbh);
		next Err if ($err);
		unless ($sth = $dbh->prepare($statement)) {
			$err = $str[45] . ' ' . $dbh->errstr();
			next Err;
			}
		unless ($sth->execute()) {
			$err = $str[29] . ' ' . $sth->errstr();
			next Err;
			}
		}
	$sth->finish() if ($sth);
	$dbh->disconnect() if ($dbh);
	return $err;
	}





sub get_dbh {
	my ($ref_dbh) = @_;
	my $err = '';
	Err: {
		foreach ('database', 'hostname', 'username', 'password') {
			my $var = "sql: $_";
			unless ($Rules{$var}) {
				$err = &pstr(21, $var );
				next Err;
				}
			}

		# load the DBI

		my %rq_mods = (
			'DBI' => 0.9,
			'DBD::mysql' => 1,
			);

		my $mod = ();
		foreach $mod ('DBI', 'DBD::mysql') {
			my $dbiver = 0;
			my $code = 'use ' . $mod . '; $dbiver = $' . $mod . '::VERSION; ';
			eval $code;
			if ($@) {
				$err = &pstr(22, $mod, $@ );
				undef($@);
				next Err;
				}
			elsif ($dbiver < $rq_mods{$mod}) {
				$err = &pstr(23, $mod, $dbiver, $rq_mods{$mod} );
				next Err;
				}
			}


		$$ref_dbh = DBI->connect("DBI:mysql:$Rules{'sql: database'}:$Rules{'sql: hostname'}", $Rules{'sql: username'}, $Rules{'sql: password'});
		unless ($$ref_dbh) {

			my $dberr = '';
			$err = $str[20];
			eval '$dberr = DBI->errstr();';
			if ($@) {
				 # well, some old DBI versions don't support DBI->errstr()
				undef($@);
				}
			else {
				$err .= ' - ' . $dberr;
				}
			next Err;
			}
		}
	return $err;
	}





sub Trim {
	local $_ = defined($_[0]) ? $_[0] : '';
	s!^[\r\n\s]+!!o;
	s![\r\n\s]+$!!o;
	return $_;
	}





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



sub he {
	my @out = @_;
	local $_;
	foreach (@out) {
		$_ = '' if (not defined($_));
		s!\&!\&amp;!g;
		s!\>!\&gt;!g;
		s!\<!\&lt;!g;
		s!\"!\&quot;!g;
		}
	return @out;
	}





sub html_encode {
	local $_ = defined($_[0]) ? $_[0] : '';
	s!\&!\&amp;!sg;
	s!\>!\&gt;!sg;
	s!\<!\&lt;!sg;
	s!\"!\&quot;!sg;
	return $_;
	}





sub ReadFile {
	my ($file) = @_;
	my ($err, $text) = ('', '');
	Err: {
		my ($BytesToRead, $BytesRead, $obj, $p_rhandle) = (-s $file);

		last Err unless ($BytesToRead);

		$obj = &LockFile_new();
		($err, $p_rhandle) = $obj->Read($file);
		next Err if ($err);

		$BytesRead = read($$p_rhandle, $text, $BytesToRead);
		$err = $obj->Close();
		next Err if ($err);

		unless ($BytesRead == $BytesToRead) {
			$err = &pstr(47, $file, $BytesRead, $BytesToRead );
			next Err;
			}
		}
	return ($err, $text);
	}





sub ReadFileL {
	my ($file) = @_;
	my ($err,$text) = ('','');
	Err: {
		unless (open(FILE, "<$file")) {
			$err = &pstr(44,$file,$!);
			next Err;
			}
		unless (binmode(FILE)) {
			$err = &pstr(39,$file,$!);
			next Err;
			}
		$text = join('',<FILE>);
		}
	close(FILE);
	return ($err,$text);
	}





sub log_search {
	my ($realm, $terms, $rank, $documents_found, $documents_searched) = @_;
	my $err = '';
	Err: {
		last unless ($Rules{'logging: enable'});

		$terms = &html_encode( $terms );

		my $host = &query_env('REMOTE_HOST') || $private{'visitor_ip_addr'} || 'undefined';

		my $time = time();
		my $human_time = &FormatDateTime( $time, 14, 0 );

		if ($Rules{'sql: logfile'}) {

			$terms =~ s!\'!\'\'!g;

			my $query = "INSERT INTO $Rules{'sql: table name: logs'} (visitor_ip, unix_time, human_time, realm, terms, rank, documents_found, documents_searched) VALUES ('$host', $time, now(), '$realm', '$terms', $rank, $documents_found, $documents_searched)";

			$err = &db_exec($query);
			next Err if ($err);

			}
		else {

			$terms =~ s!\,! !g;
			$terms =~ s!\s+! !g;
			$terms =~ s!^ !!o;
			$terms =~ s! $!!o;

			my $logline = "$host ,$time,$human_time,$realm,$terms,$rank,$documents_found,$documents_searched,\n";

			unless (open(LOGFILE, ">>search.log.txt")) {
				$err = &pstr(42,'search.log.txt',$!);
				next Err;
				}

			binmode(LOGFILE);
			print LOGFILE $logline;
			close(LOGFILE);
			chmod($private{'file_mask'},'search.log.txt');
			}
		}
	return $err;
	}





sub FormatNumber {
	my ( $expression, $decimal_places, $include_leading_digit, $use_parens_for_negative, $group_digits, $euro_style ) = @_;

	my $dec_ch = ($euro_style) ? ',' : '.';
	my $tho_ch = ($euro_style) ? '.' : ',';

	my $qm_dec_ch = quotemeta( $dec_ch );

	local $_ = $expression;
	unless (m!^\-?\d*\.?\d*$!) {
		#print "Warning: arg '$num' isn't numeric.\n";
		$_ = 0;
		}

	my $exp = 1;
	for (1..$decimal_places) {
		$exp *= 10;
		}
	$_ *= $exp;
	$_ = int($_);
	$_ = ($_ / $exp);


	# Add a trailing decimal divider if we don't have one yet
	$_ .= '.' unless (m!\.!);

	# Pad zero'es if appropriate:
	if ($decimal_places) {
		if (m!^(.*)\.(.*)$!) {
			$_ .= '0' x ($decimal_places - length($2));
			}
		}

	# Re-write with localized decimal divider:
	s!\.!$dec_ch!o;

	# Group digits:
	if ($group_digits) {
		while (m!(.*)(\d)(\d\d\d)(\,|\.)(.*)!) {
			$_ = "$1$2$tho_ch$3$4$5";
			}
		}

	if ($include_leading_digit) {
		s!^$qm_dec_ch!0$dec_ch!o;
		}

	# Have we somehow ended up with just a decimal point?  Make it zero then:
	if ("foo$_" eq "foo$dec_ch") {
		$_ = "0";
		}

	# Strip trailing decimal point
	s!$dec_ch$!!o;

	if ($use_parens_for_negative) {
		s!^\-(.*)$!\($1\)!o;
		}

	return $_;
	}





sub FormatDateTime {
	my ($time, $format_type, $b_format_as_gmt) = @_;
	$format_type = 0 unless ($format_type);
	my $date_str = '';

	$time = 0 unless ($time);

	if ($format_type == 13) {

		if ($b_format_as_gmt) {
			$date_str = scalar gmtime( $time );
			}
		else {
			$date_str = scalar localtime( $time );
			}
		}
	else {

		my ($sec, $min, $milhour, $day, $month_index, $year, $weekday_index) = ($b_format_as_gmt) ? gmtime( $time ) : localtime( $time );

		$year += 1900;

		my $ampm = ( $milhour >= 12 ) ? 'PM' : 'AM';
		my $relhour = (($milhour - 1) % 12) + 1;
		my $month = $month_index + 1;

		foreach ($milhour, $relhour, $min, $sec, $month, $day) {
			$_ = "0$_" if (1 == length($_));
			}

		my @MonthNames = (
			$str[8],
			$str[9],
			$str[26],
			$str[32],
			$str[40],
			$str[48],
			$str[36],
			$str[34],
			$str[33],
			$str[31],
			$str[30],
			$str[27],
			);

		my @WeekNames = (
			$str[25],
			$str[24],
			$str[28],
			$str[7],
			$str[6],
			$str[5],
			$str[4],
			);

		my $full_weekday = $WeekNames[$weekday_index];
		my $short_weekday = substr($full_weekday, 0, 3);

		my $full_monthname = $MonthNames[$month_index];
		my $short_monthname = substr($full_monthname, 0, 3); #localize bug?

		if ($format_type == 0) {
			$date_str = "$month/$day/$year $relhour:$min:$sec $ampm";
			}
		elsif ($format_type == 1) {
			$date_str = "$full_weekday, $full_monthname $day, $year";
			}
		elsif ($format_type == 2) {
			$date_str = "$month/$day/$year";
			}
		elsif ($format_type == 3) {
			$date_str = "$relhour:$min:$sec $ampm";
			}
		elsif ($format_type == 4) {
			$date_str = "$milhour:$min";
			}
		elsif ($format_type == 10) {
			$date_str = "$short_weekday $month/$day/$year $relhour:$min:$sec $ampm";
			}
		elsif ($format_type == 11) {
			$date_str = "$short_weekday, $day $short_monthname $year $milhour:$min:$sec -0000";
			}
		elsif ($format_type == 12) {
			$date_str = "$year-$month-$day $milhour:$min:$sec";
			}
		elsif ($format_type == 14) {
			$date_str = "$month/$day/$year $milhour:$min";
			}
		}
	return $date_str;
	}





sub SetDefaults {
	my ($text, $p_params) = @_;

	# short-circuit:
	if ((ref($p_params) ne 'HASH') or (not (%$p_params))) {
		return $text;
		}


	my @array = split(m!<(INPUT|SELECT|TEXTAREA)([^\>]+?)\>!is, $text);

	my $finaltext = $array[0];

	my $setval;

	my $x = 1;
	for ($x = 1; $x < $#array; $x += 3) {

		my ($uctag, $origtag, $attribs, $trail) = (uc($array[$x]), $array[$x], $array[$x+1] || '', $array[$x+2] || '');

		Tweak: {

			my $tag_name = '';
			if ($attribs =~ m! NAME\s*=\s*\"([^\"]+?)\"!is) {
				$tag_name = $1;
				}
			elsif ($attribs =~ m! NAME\s*=\s*(\S+)!is) {
				$tag_name = $1;
				}
			else {

				# we cannot modify what we do not understand:
				last Tweak;
				}
			last Tweak unless (defined($$p_params{$tag_name}));
			$setval = &html_encode($$p_params{$tag_name});


			if ($uctag eq 'INPUT') {

				# discover VALUE and TYPE
				my $type = 'TEXT';
				if ($attribs =~ m! TYPE\s*=\s*\"([^\"]+?)\"!is) {
					$type = uc($1);
					}
				elsif ($attribs =~ m! TYPE\s*=\s*(\S+)!is) {
					$type = uc($1);
					}

				# discover VALUE and TYPE
				my $value = '';
				if ($attribs =~ m! VALUE\s*=\s*\"([^\"]+?)\"!is) {
					$value = $1;
					}
				elsif ($attribs =~ m! VALUE\s*=\s*(\S+)!is) {
					$value = $1;
					}

				# we can only set values for known types:

				if (($type eq 'RADIO') or ($type eq 'CHECKBOX')) {

					#changed 2001-11-15; strip pre-existing checks
					$attribs =~ s! (checked="checked"|checked)($| )!$2!ois;

					if ($setval eq $value) {
						$attribs = qq! checked="checked"$attribs!;
						}

					}
				elsif (($type eq 'TEXT') or ($type eq 'PASSWORD') or ($type eq 'HIDDEN')) {

					# but only hidden fields if value is null:

					last Tweak if (($type eq 'HIDDEN') and ($value ne ''));

					# replace any existing VALUE tag:
					my $qm_value = quotemeta($value);
					$attribs =~ s! value\s*=\s*\"$qm_value\"! value="$setval"!iso;
					$attribs =~ s! value\s*=\s*$qm_value! value="$setval"!iso;

					# add the tag if it's not present (i.e. if no VALUE was present in original tag)
					my $qm_setval = quotemeta($setval);
					unless ($attribs =~ m! VALUE="$qm_setval"!is) {
						$attribs = " value=\"$setval\"$attribs";
						}

					}
				}
			elsif ($uctag eq 'SELECT') {

# does not support <OPTION>value syntax, only <OPTION VALUE="value">value

				my $lc_set_value = lc($setval);

				my @frags = ();
				foreach (split(m!<option!is, $trail)) {

					#changed 2001-11-15; strip pre-existing "selected"
					$_ =~ s! (selected|selected="selected")($| )!$2!ois;

					if (m!VALUE\s*=\s*\"(.*?)\"!is) {
						if ($lc_set_value eq lc($1)) {
							$_ = ' selected="selected"' . $_;
							}
						}
					elsif (m!VALUE\s*=\s*(\S+)!is) {
						if ($lc_set_value eq lc($1)) {
							$_ = ' selected="selected"' . $_;
							}
						}
					push(@frags, $_);
					}
				$trail = join('<option', @frags);
				}
			elsif ($uctag eq 'TEXTAREA') {
				$trail =~ s!^.*?</(textarea)>!$setval</$1>!osi;
				}
			last Tweak;
			}

		$finaltext .= "<$origtag$attribs>$trail";
		}
	return $finaltext;
	}





sub SearchIndexFile {
	my $err = '';
	Err: {
		local $_;
		my ($file, $search_code, $r_pages_searched, $r_hits) = @_;

		my ($obj, $p_rhandle) = ();

		$obj = &LockFile_new();
		($err, $p_rhandle) = $obj->Read( $file );
		next Err if ($err);
		eval($search_code);
		die $@ if ($@);
		$err = $obj->Close();
		next Err if ($err);
		last Err;
		}
	continue {
		&ppstr(64,$err);
		}
	}





sub SearchDatabase {
	local $_;

	my $dbh = undef();
	my $sth = undef();

	my ($where_clause, $DocSearch, $r_hits) = @_;
	my @WordCount = ();

	my $pages_searched = 0;
	my $r_pages_searched = \$pages_searched;

	my ($WordMatches, $sort_num, $t, $d, $k, $hdr, $n_context_matches, $context_str, $delta, $text);

	my $err = '';
	Err: {
		my $query = "SELECT * FROM $Rules{'sql: table name: addresses'}";
		if ($where_clause) {
			$query .= ' WHERE ' . $where_clause;
			}

		$err = &get_dbh(\$dbh);
		next Err if ($err);

		unless ($sth = $dbh->prepare($query)) {
			$err = $str[45] . ' ' . $dbh->errstr();
			next Err;
			}
		unless ($sth->execute()) {
			$err = $str[29] . ' ' . $sth->errstr();
			next Err;
			}
		undef($@);
		my $p_data = ();
		while ($p_data = $sth->fetchrow_hashref()) {
			($err, $_) =  &text_record_from_hash( $p_data );
			next if ($err);
			eval($DocSearch);
			die($@) if ($@);
			}
		last Err;
		}
	continue {
		&ppstr(64,$err);
		}
	$sth->finish() if ($sth);
	$dbh->disconnect() if ($dbh);
	}





sub leadpad {
	my ($expr, $padch, $padlen) = @_;
	if (length($expr) <= $padlen) {
		return ($padch x ($padlen - length($expr))) . $expr;
		}
	else {
		return substr($expr, length($expr) - $padlen, 6);
		}
	}





sub text_record_from_hash {
	my ($p_pagedata) = @_;
	my ($err, $text_record) = ('', '');

	Err: {
		my @require_fields = ('url', 'promote', 'size', 'title', 'description', 'keywords', 'text', 'links');

		foreach (@require_fields) {
			next if (defined($$p_pagedata{$_}));
			$err = &pstr(21,$_);
			next Err;
			}

		&compress_hash( $p_pagedata );

		$text_record = '';
		foreach ('promote', 'dd', 'mm') {
			$text_record .= &leadpad( $$p_pagedata{$_}, '0', 2 );
			}

		#changed 0053 - not longer forcing size to be 6 digits
		$text_record .= $$p_pagedata{'yyyy'} . $$p_pagedata{'size'};


		foreach ('url', 'title', 'description') {
			$$p_pagedata{$_} =~ s'= '=%20'og;
			}

		$text_record .= ' ' . $$p_pagedata{'lastmodtime'};
		$text_record .= ' ' . $$p_pagedata{'lastindex'};

		$text_record .= ' u= ' . $$p_pagedata{'url'};
		$text_record .= ' t= ' . $$p_pagedata{'title'};
		$text_record .= ' d= ' . $$p_pagedata{'description'};
		$text_record .= ' uM=' . $$p_pagedata{'um'};
		$text_record .= 'uT=' . $$p_pagedata{'ut'};
		$text_record .= 'uD=' . $$p_pagedata{'ud'};
		$text_record .= 'uK=' . $$p_pagedata{'uk'};
		$text_record .= 'h=' . $$p_pagedata{'text'};
		$text_record .= 'l=' . $$p_pagedata{'links'};
		$text_record .= "\n";
		last Err;
		}
	return ($err, $text_record);
	}





sub compress_hash {
	my ($p_pagedata) = @_;

	return if ($$p_pagedata{'compressed'});

	# Solidify time fields:
	foreach ('lastindex', 'lastmodtime') {
		$$p_pagedata{$_} = time() unless ($$p_pagedata{$_});
		}
	unless (($$p_pagedata{'dd'}) and ($$p_pagedata{'mm'}) and ($$p_pagedata{'yyyy'})) {
		($$p_pagedata{'dd'}, $$p_pagedata{'mm'}, $$p_pagedata{'yyyy'}) = (localtime($$p_pagedata{'lastmodtime'}))[3..5];
		$$p_pagedata{'yyyy'} += 1900;
		}
	my %pairs = (
		'um' => 'url',
		'ut' => 'title',
		'ud' => 'description',
		'uk' => 'keywords',
		'text' => 'text',
		'links' => 'links',
		);
	my ($name, $value) = ();
	while (($name, $value) = each %pairs) {
		$$p_pagedata{$name} = &CompressStrip($$p_pagedata{$value});
		}
	$$p_pagedata{'compressed'} = 1;
	}





sub StandardVersion {
	my ($p_search_terms, %pagedata) = @_;

	local $_;
	foreach ('redirector', 'relevance', 'record_realm', 'context') {
		$pagedata{$_} = '' unless (defined($pagedata{$_}));
		}

	unless ((defined($pagedata{'dd'})) and (defined($pagedata{'mm'})) and (defined($pagedata{'yyyy'}))) {
		if ($pagedata{'lastindex'}) {
			($pagedata{'dd'}, $pagedata{'mm'}, $pagedata{'yyyy'}) = (localtime($pagedata{'lastmodtime'}))[3..5];
			$pagedata{'yyyy'} += 1900;
			}
		}


	$pagedata{'day'} = $pagedata{'dd'};

	$pagedata{'month'} = ('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec')[$pagedata{'mm'}];
	$pagedata{'year'} = $pagedata{'yyyy'};

	#changed 0053
	$pagedata{'size'} = &FormatNumber( ($pagedata{'size'} + 999) / 1000, 0, 1, 0, 1, $Rules{'ui: number format'} ) . 'k';

	if ($p_search_terms) {

		# use two marker chars for start-pattern and end-pattern
		# marker chars are those that are guaranteed to be stripped
		my $sm1 = chr(10);
		my $em1 = chr(13);

		my $sm2 = chr(7);
		my $em2 = chr(8);

		my $Term = '';
		foreach $Term (@$p_search_terms) {
			$Term =~ s!\*!!g; #changed 0054
			my $Temp = quotemeta(&Trim($Term)); #changed 0046
			next if ($Temp =~ m!$sm1|$em1|$sm2|$em2!s);
			$pagedata{'description'} =~ s!($Temp)!$sm1$1$em1!isg;
			$pagedata{'context'} =~ s!($Temp)!$sm2$1$em2!isg;
			}
		$pagedata{'description'} =~ s!$sm1!<B CLASS=hl1>!sg;
		$pagedata{'description'} =~ s!$em1!</B>!sg;
		$pagedata{'context'} =~ s!$sm2!<B CLASS=hl2>!sg;
		$pagedata{'context'} =~ s!$em2!</B>!sg;
		}


	if ($pagedata{'context'}) {
		$pagedata{'context_line'} = "<BR><B>$str[35]:</B> $pagedata{'context'}";
		}
	else {
		$pagedata{'context_line'} = '';
		}

	$pagedata{'admin_options'} = '' unless (defined($pagedata{'admin_options'}));

	$pagedata{'url'} = &rewrite_url( 1, $pagedata{'url'} );

	if ($pagedata{'url'} =~ m!^\w+\://([^/]+)!) {
		$pagedata{'host'} = $1;
		}

	#revcompat - 0033
	$pagedata{'target'} = '';
	#/revcompat

	#changed 0050
	$pagedata{'url_terms'} = &url_encode($const{'terms'});
	$pagedata{'url_url'} = &url_encode($pagedata{'url'});


	#changed 0044 - support URL's w/ embedded spaces for old browsers
	$pagedata{'url'} =~ s! !\%20!g;
	$pagedata{'html_url'} = $pagedata{'url'} = &html_encode($pagedata{'url'});


	#changed 0053 - all const avail
	my ($n,$v);
	while (($n,$v) = each %const) {
		$pagedata{$n} = $const{$n} unless defined($pagedata{$n});
		}

	return &PrintTemplate( 1, 'line_listing.txt', $Rules{'language'}, \%pagedata, 0, \%const);
	}





sub str_jumptext {

	my ( $start_pos, $units_per_page, $maximum, $url, $b_is_exact_count ) = @_;

	$start_pos = 1 if ($start_pos < 1);

	my $end_pos = $start_pos + $units_per_page - 1;


	unless ($b_is_exact_count) {
		$b_is_exact_count = 1 if ($maximum < $end_pos);
		}

	$end_pos = $maximum if ($maximum < $end_pos);

	my ($jump_sum, $jumptext) = ('', '');

	if ($b_is_exact_count) {
		$jump_sum = &pstr(15, $start_pos, $end_pos, $maximum );
		}
	else {
		$jump_sum = &pstr(15, $start_pos, $end_pos, $end_pos . '+' );

		# Okay, we've printed what we know.  Now, for purposes of generating advance links, pretend that there's at least one page beyond this one (we know that if max < curr+units then we would have toggled to b_is_exact_count earlier.  and if max already exceeds this page's worth fo data, then there's no need to tweak it:

		if ($maximum == $end_pos) {
			$maximum++;
			}

		}



	if ($maximum > $units_per_page) {

		# Time for a scrolling thing - "<- Previous 1 2 3 4 5 Next ->"

		$jumptext .= '<p class="fd_results">';
		$jumptext .= $str[16];
		$jumptext .= ' ';

		if ($start_pos > 1) {
			$jumptext .= "[ <a href=\"$url" . ($start_pos - $units_per_page) . "\">&lt;&lt; $str[17]</a> ] ";
			}

		my $nlinks = 1 + int(($maximum - 1) / $units_per_page);
		my $thislink = 1 + int($start_pos / $units_per_page);

		my $start = 1;
		if ($thislink > 15) {
			$start = $thislink - 15;
			}

		my $x = 0;
		for ($x = $start; $x <= $nlinks; $x++) {
			if ($x == $thislink) {
				$jumptext .= " <b>$x</b>";
				}
			else {
				$jumptext .= " <a href=\"$url" . (1 + (($x - 1) * $units_per_page)) . "\">$x</a>\n";
				}
			last if ($x > ($start + 18));
			}

		if ($maximum > $end_pos) {
			$jumptext .= " [ <a href=\"$url" . ($start_pos + $units_per_page) . "\">$str[18] &gt;&gt;</a> ]";
			}

		$jumptext .= "</p>\n";
		}
	return ('<p class="fd_results">' . $jump_sum . '</p>', $jumptext);#changed 0054 - para
	}





sub Assert {
	return if ($_[0]);
	my ($package, $file, $line) = caller();
	print "Content-Type: text/html\015\012\015\012";
	print "<hr /><h1><pre>Assertion Error:<br />	Package: $package<br />	File: $file<br />	Line: $line</pre></h1><hr />";
	}





sub LoadRules {
	my ($DEFAULT_LANGUAGE) = @_;
	my $err = '';
	Err: {
		%Rules = ();

		my $FDR = &FD_Rules_new();
		$Rules{'file'} = $FDR->{'file'};

		my $text = '';
		($err, $text) = &ReadFile($Rules{'file'});
		next Err if ($err);

		my $line = '';
		foreach $line (split(m!\r|\n!s, $text)) {
			next if ($line =~ m!^\s*\#!); # skip comments
			next unless ($line =~ m!(.*?)=(.*)!);
			my ($name, $value) = (lc(&Trim($1)), &Trim($2));
			my ($is_valid, $valid_value) = $FDR->_fdr_validate($name, $value);
			$Rules{$name} = $valid_value;
			}
		my $r_defaults = $FDR->{'r_defaults'};
		if (($r_defaults) and ('HASH' eq ref($r_defaults))) {
			my %defhash = %$r_defaults;
			local $_;
			while (defined($_ = each %defhash)) {
				next if defined($Rules{$_});
				if ($_ eq 'language') {
					$Rules{$_} = $DEFAULT_LANGUAGE;
					next;
					}
				$Rules{$_} = $defhash{$_}[0];
				}
			}

		# build derived values:

		if ($Rules{'admin notify: sendmail program'}) {
			my $b_is_valid = 0;
			foreach (@sendmail) {
				$b_is_valid = 1 if ($_ eq $Rules{'admin notify: sendmail program'});
				}
			unless ($b_is_valid) {
				$Rules{'admin notify: sendmail program'} = '';
				}
			}

		foreach ('wildcard match','ignore words','ext') {
			next unless ($Rules{$_});
			$Rules{$_} =~ s!\?\{!!sg; # strip code-exec regex
			}


		my %NewWords = ();
		foreach (split(m!\s+!s, &RawTranslate($Rules{'ignore words'}))) {
			$NewWords{quotemeta($_)}++;
			}
		my $frag = join('|', sort keys %NewWords);
		$frag =~ s!^\|!!;
		$frag =~ s!\|$!!;
		$const{'code_strip_ignored_words'} = "s! ($frag) ! !sog;";

		my @ignored_extensions = split(m!\s+!, $Rules{'crawler: ignore links to'});
		if (@ignored_extensions) {
			my %ig_ext = ();
			foreach (@ignored_extensions) {
				$ig_ext{quotemeta(lc($_))}++;
				}
			$const{'pattern_is_ignored_extension'} = '\.(' . join('|', sort keys %ig_ext) . ')$';
			}
		else {
			$const{'pattern_is_ignored_extension'} = '';
			}
		}
	return $err;
	}





sub str_search_form {
	my ($action) = @_;

	my %replace = %const;

	#revcompat - 0032
	$replace{'displayterms'} = '';
	$replace{'selectmatch'} = '<select name="match"><option value="1">All</option><option value="0">Any</option></select>';
	#/revcompat

	$replace{'realm_options'} = '';
	$replace{'selectrealm'} = '<select name="Realm"><option value="All">[ All ]</option>';

	my $p_realm = ();
	foreach $p_realm ($realms->listrealms('no_error')) {
		$replace{'selectrealm'}.= "\t<option value=\"$$p_realm{'html_name'}\">$$p_realm{'html_name'}</option>\n";
		$replace{'realm_options'} .= "\t<option value=\"$$p_realm{'html_name'}\">$$p_realm{'html_name'}</option>\n";
		}
	$replace{'selectrealm'} .= '</select>';

	my $html = qq!<form method="get" action="$action">\n!;

	my $custom = &PrintTemplate( 1, 'searchform.htm', $Rules{'language'}, \%replace );

	local $_;
	foreach (keys %FORM) {
		next unless (m!^p:!);
		my $qm_n = quotemeta($_);
		next if ($custom =~ m!$qm_n!s); # if user already has something like "p:pm" in their custom search form, don't risk double-ing up with a hidden field
		my ($n, $v) = &he( $_, $FORM{$_} );
		$html .= qq!<input type="hidden" name="$n" value="$v" />\n!;
		}

	# optimize against loading common_parse_page.pl
	unless ($realms->realm_count('is_runtime')) {
		$html .= qq!<input type="hidden" name="nocpp" value="1" />\n!;
		}

	$html .= $custom;
	$html .= "\n</form>\n";

	my %defaults = %FORM;
	$defaults{'Terms'} = $FORM{'Terms'} || '';
	return &SetDefaults($html,\%defaults);
	}





sub parse_search_terms {
	my ($str_terms, $str_match) = @_;
	my ($bTermsExist, $Ignored_Terms, $Important_Terms, $DocSearch, $RealmSearch, $where_clause, $sort_method, @search_terms) = (0, '', '', '', '', '', 0);


	# use match==2 to force search-as-phrase
	if ($str_match eq '2') {
		$str_terms =~ s!\s+!_!sg;
		}

	my $terms = $str_terms;

	my $IgnoreQuotedTerms = 0;
	unless ($str_match) {
		# if this is a non-special string - without meta characters, but containing a phrase - addx a special phrased version
		# of the terms for better matching:
		if (($terms =~ m! !) and not ($terms =~ m!(\W|\-|not |and |or )!i)) {
			$terms = "\"$terms\" $terms";
			$IgnoreQuotedTerms = 1;
			}
		}

	$terms = ' '.$terms.' ';
	$terms =~ s'\s+' 'g;

	my ($i, $ProcTerms) = (0, '');
	foreach (split(m!\"!, $terms)) {
		tr! !_! if $i;
		$i = not $i;
		$ProcTerms .= $_;
		}

	$ProcTerms =~ s' not ' -'ig;
	$ProcTerms =~ s' and ' +'ig;
	$ProcTerms =~ s' or ' |'ig;

	my ($EvalForbid, $EvalRequired, $EvalOptional, $EvalExtraRequired, $EvalExtraOptional) = ('', '', '', '', '');
	my $tm = $Rules{'multiplier: title'};
	my $km = $Rules{'multiplier: keyword'};
	my $dm = $Rules{'multiplier: description'};

	my (@invalid_terms, @valid_terms) = ();

	my $default_type = ($str_match) ? 3 : 2;

	my $chars_per_context_hit = 36;


	my (@required_clauses, @optional_clauses) = ();

	Term: foreach (split(m!\s+!, $ProcTerms)) {

		# Remove the underscores that are binding the phrases together:
		s!_! !g;

		next unless ($_);

		my ($type, $is_attrib_search, $str_pattern, $sql_clause) = &format_term_ex($_, $default_type);

		if ($type == 0) {
			push(@invalid_terms, $_);
			next;
			}

		if ($sql_clause) {
			if ($type == 2) {
				push(@optional_clauses, $sql_clause);
				}
			else {
				push(@required_clauses, $sql_clause);
				}
			}

		# only get the search context if this is *not* an attrib search

		if ($type == 1) {
			$EvalForbid .= "\tlast SearchBlock if (m!$str_pattern!o);\n";
			}
		elsif ($type == 2) {

			if (($Rules{'show examples: enable'}) and (not ($is_attrib_search))) {

$EvalOptional .= <<"EOM";

	if (\$n_context_matches) {
		\$delta = scalar (\@WordCount = (\$text =~ m!([^\=]{0,$chars_per_context_hit})($str_pattern)([^\=]{0,$chars_per_context_hit})!og));
		if (\$delta) {
			\$WordMatches += \$delta;
			my \$x = 0;
			while ((\$x + 2) <= \$#WordCount) {
				my \$full_context = \$WordCount[\$x] . \$WordCount[\$x + 1] . \$WordCount[\$x + 2];
				\$x += 3;
				next unless (\$full_context =~ m! (.*) !);
				\$context_str .= "\$1... ";
				\$n_context_matches--;
				last unless (\$n_context_matches);
				}
			}
		else {
			# second-try pattern match, for those outside the h= l= area:
			\$WordMatches += scalar (\@WordCount = m!$str_pattern!og);
			}
		}
	else {
		\$WordMatches += scalar (\@WordCount = m!$str_pattern!og);
		}

EOM

				}
			else {


$EvalOptional .= <<"EOM";

	\$WordMatches += scalar (\@WordCount = m!$str_pattern!og);

EOM

				}
			}
		elsif ($type == 3) {

			if (($Rules{'show examples: enable'}) and (not ($is_attrib_search))) {

$EvalRequired .= <<"EOM";

	if (\$n_context_matches) {
		\$delta = scalar (\@WordCount = (\$text =~ m!([^\=]{0,$chars_per_context_hit})($str_pattern)([^\=]{0,$chars_per_context_hit})!og));
		if (\$delta) {
			\$WordMatches += \$delta;
			my \$x = 0;
			while ((\$x + 2) <= \$#WordCount) {
				my \$full_context = \$WordCount[\$x] . \$WordCount[\$x + 1] . \$WordCount[\$x + 2];
				\$x += 3;
				next unless (\$full_context =~ m! (.*) !);
				\$context_str .= "\$1... ";
				\$n_context_matches--;
				last unless (\$n_context_matches);
				}
			}
		else {
			# second-try pattern match, for those outside the h= l= area:

			\$delta = scalar (\@WordCount = m!$str_pattern!og);
			last SearchBlock unless (\$delta);
			\$WordMatches += \$delta;

			}
		}
	else {
		\$delta = scalar (\@WordCount = m!$str_pattern!og);
		last SearchBlock unless (\$delta);
		\$WordMatches += \$delta;
		}



EOM

				}
			else {


$EvalRequired .= <<"EOM";

		\$delta = scalar (\@WordCount = m!$str_pattern!og);
		last SearchBlock unless (\$delta);
		\$WordMatches += \$delta;

EOM

				}
			}

		if ($type == 1) {
			push(@invalid_terms, $_);
			}
		else {
			push(@valid_terms, $_);
			$EvalExtraRequired .= "\t\$WordMatches += $tm * (\@WordCount = (\$t =~ m!$str_pattern!og));\n" if $tm;
			$EvalExtraRequired .= "\t\$WordMatches += $dm * (\@WordCount = (\$d =~ m!$str_pattern!og));\n" if $dm;
			$EvalExtraRequired .= "\t\$WordMatches += $km * (\@WordCount = (\$k =~ m!$str_pattern!og));\n" if $km;
			$bTermsExist = 1;
			}

		# strip leading characters:
		s!^(\-|\+|\|)!!;
		push(@search_terms, $_) if ($_);
		}




	# double-quote terms with embedded spaces:
	@invalid_terms = map { m! ! ? "$_" : $_ } @invalid_terms;

	# double-quote terms with embedded spaces:
	if ($IgnoreQuotedTerms) {
		@valid_terms = map { m! ! ? '' : $_ } @valid_terms;
		}
	else {
		@valid_terms = map { m! ! ? "$_" : $_ } @valid_terms;
		}

	$Ignored_Terms = join(', ', @invalid_terms);
	$Important_Terms = join(', ', @valid_terms);

# extract $text early if we're doing context matching - otherwise wait till later


my $sort_code = '';

$sort_method = $Rules{'sorting: default sort method'};
if (($FORM{'sort-method'}) and ($FORM{'sort-method'} =~ m!^\d+$!) and (0 < $FORM{'sort-method'}) and ($FORM{'sort-method'} < 7)) {
	$sort_method = $FORM{'sort-method'};
	}


if (($sort_method < 3) and (($Rules{'sorting: time sensitive'}) or ($FORM{'p:ts'}))) {



	$sort_code = <<'EOM';

m!^\d+ (\d+)!;
my $age = $private{'script_start_time'} - $1;

if ($age < 172800) {
	$WordMatches *= 4;
	}
elsif ($age < 345600) {
	$WordMatches *= 3;
	}
elsif ($age < 691200) {
	$WordMatches *= 2;
	}

EOM
	}


# relevance:
if ($sort_method == 1) {
	$sort_code .= ' $sort_num = 10E6 - ($WordMatches * substr($_,0,2)); ';
	}
# reverse relevance:
elsif ($sort_method == 2) {
	$sort_code .= ' $sort_num = 10E6 + ($WordMatches * substr($_,0,2)); ';
	}
# by lastmod time
elsif ($sort_method == 3) {
$sort_code = <<'EOM';
	m!^\d+ (\d+)!;
	$sort_num = 2147400000 - $1;
	$sort_num = '0' x (10 - length($sort_num)) . $sort_num;
EOM
	}
elsif ($sort_method == 4) {
$sort_code = <<'EOM';
	m!^\d+ (\d+)!;
	$sort_num = $1;
	$sort_num = '0' x (10 - length($sort_num)) . $sort_num;
EOM
	}
# by lastindex time
elsif ($sort_method == 5) {
$sort_code = <<'EOM';
	m!^\d+ \d+ (\d+)!;
	$sort_num = 2147400000 - $1;
	$sort_num = '0' x (10 - length($sort_num)) . $sort_num;
EOM
	}
elsif ($sort_method == 6) {
$sort_code = <<'EOM';
	m!^\d+ \d+ (\d+)!;
	$sort_num = $1;
	$sort_num = '0' x (10 - length($sort_num)) . $sort_num;
EOM
	}

if ($Rules{'sorting: randomize equally-relevant search results'}) {
	$sort_code .= ' $sort_num .= 1000 + int(rand(8999)); ';
	}
$sort_code .= ' $sort_num .=  "." . (10E6 - ($WordMatches * substr($_,0,2))); ';


if ($Rules{'show examples: enable'}) {

$DocSearch = <<"EOM";

	SearchBlock: {
		\$\$r_pages_searched++;
		\$WordMatches = 0;
		\$text = '';
		$EvalForbid

		\$n_context_matches = $Rules{'show examples: number to display'};
		\$context_str = '';

		unless (m!^(.*?)uM=.*?uT=(.*?)uD=(.*?)uK=(.*?)h=(.*)l=!o) { \$\$r_pages_searched--; last SearchBlock; }
		(\$hdr, \$t, \$d, \$k, \$text) = (\$1, \$2, \$3, \$4, \$5);
		$EvalRequired
		$EvalOptional
		last SearchBlock unless \$WordMatches;
		$EvalExtraRequired
		$EvalExtraOptional
		$sort_code
		push(\@\$r_hits, \$sort_num . '.' . \$hdr . ' c= ' . \$context_str . ' r= ' . \$const{'record_realm'});
		}

EOM

	}
elsif (($EvalExtraRequired) or ($EvalExtraOptional)) {

$DocSearch = <<"EOM";

	SearchBlock: {
		\$\$r_pages_searched++;
		\$WordMatches = 0;
		$EvalForbid
		$EvalRequired
		$EvalOptional
		last SearchBlock unless \$WordMatches;
		unless (m!^(.*?)uM=.*?uT=(.*?)uD=(.*?)uK=(.*?)h=!o) { \$\$r_pages_searched--; last SearchBlock; }
		(\$hdr, \$t, \$d, \$k) = (\$1, \$2, \$3, \$4);
		$EvalExtraRequired
		$EvalExtraOptional
		$sort_code
		push(\@\$r_hits, \$sort_num . '.' . \$hdr . ' c=  r= ' . \$const{'record_realm'});
		}

EOM

	}
else {
$DocSearch = <<"EOM";

	SearchBlock: {
		\$\$r_pages_searched++;
		\$WordMatches = 0;
		$EvalForbid
		$EvalRequired
		$EvalOptional
		last SearchBlock unless \$WordMatches;
		unless (m!^(.*?)uM=!o) { \$\$r_pages_searched--; last SearchBlock; }
		\$hdr = \$1;
		$sort_code
		push(\@\$r_hits, \$sort_num . '.' . \$hdr . ' c=  r= ' . \$const{'record_realm'});
		}
EOM

	}


	$RealmSearch = <<"EOM";
my \@WordCount = ();
my (\$WordMatches, \$sort_num, \$t, \$d, \$k, \$hdr, \$n_context_matches, \$context_str, \$delta, \$text);
Record: while (defined(\$_ = readline(\$\$p_rhandle))) {
$DocSearch
	}

EOM



	$where_clause = '';
	if (@required_clauses) {
		$where_clause = '(' . join(') AND (', @required_clauses) . ')';
		}
	elsif (@optional_clauses) {
		$where_clause = '(' . join(') OR (', @optional_clauses) . ')';
		}

	return ($bTermsExist, $Ignored_Terms, $Important_Terms, $DocSearch, $RealmSearch, $where_clause, @search_terms);
	}





sub format_term_ex {
	local $_ = defined($_[0]) ? $_[0] : '';
	my $default_type = $_[1] || 2;

	my $WildCard = 'thewildcardisaveryspecialcharacter';
	my $WildSearch = $Rules{'wildcard match'};

	my ($type, $is_attrib_search, $lead_pattern, $term_pattern, $trail_pattern, $sql_clause) = ($default_type, 0, '', '', '', '');

	# Ignore wildcard-enhanced terms with less than 3 real characters:
	if ((m!\*!) and ((length($_) - (s!\*!\*!g)) < 3)) {
		$type = 0;
		}

	s!\*+!$WildCard!g;

	if (s!^\-!!o) {
		$type = 1;
		}
	elsif (s!^\|!!o) {
		$type = 2;
		}
	elsif (s!^\+!!o) {
		$type = 3;
		}

	my $attrib_str = '';

	if (m!^(url|host|domain):(.+)!is) {
		$_ = $2;
		$lead_pattern = ' uM=.*?(';
		$trail_pattern = ').*?uT= ';
		$is_attrib_search = 1;
		$attrib_str = 'um';
		}
	elsif (m!^title:(.+)!is) {
		$_ = $1;
		$lead_pattern = ' uT=.*?(';
		$trail_pattern = ').*?uD= ';
		$is_attrib_search = 1;
		$attrib_str = 'ut';
		}
	elsif (m!^text:(.+)!is) {
		$attrib_str = 'text';
		$_ = $1;
		$lead_pattern = ' h=.*?(';
		$trail_pattern = ').*?l= ';
		$is_attrib_search = 1;
		}
	elsif (m!^link:(.+)!is) {
		$_ = $1;
		$lead_pattern = ' l=.*?(';
		$trail_pattern = ')';
		$is_attrib_search = 1;
		$attrib_str = 'links';
		}
	#end changes
	$term_pattern = &CompressStrip($_);

	# What if CompressStrip removed all words as ignored words?
	if ($term_pattern =~ m!^\s*$!) {
		$type = 0;
		}

	if ((not ($sql_clause)) and ($term_pattern ne '  ')) {
		unless ($is_attrib_search) {
			$attrib_str = "um, ' ', ut, ' ', ud, ' ', keywords, ' ', text, ' ', links";
			}

		my $sql_term_pattern = $term_pattern;
		$sql_term_pattern =~ s!\'!''!sg;
		if ($type == 1) {
			$sql_clause = "CONCAT('. ', $attrib_str, ' .') NOT LIKE '%$term_pattern%'";
			}
		else {
			$sql_clause = "CONCAT('. ', $attrib_str, ' .') LIKE '%$term_pattern%'";
			}
		}
	$sql_clause =~ s!$WildCard!\%!g;
	$term_pattern = quotemeta($term_pattern);

	# should we match plurals?
	# thanks to http://owl.english.purdue.edu/handouts/grammar/g_spelnoun.html
	if ($FORM{'p:pm'}) {

		# if the word ends in [vowel]o, addx s
		# otherwise add "es" or "s" (varies)

		if ($term_pattern =~ m!^(.*)(a|e|i|o|u)(o)(\\) $!i) {
			$term_pattern = "$1$2$3s?$4 ";
			}
		elsif ($term_pattern =~ m!^(.*)(o)(\\) $!i) {
			$term_pattern = "$1$2(es|s)?$4 ";
			}

		# words ending in "f/fe" => "ves"
		# skip this one; false pos on "gif" => "gives"
		# very few nouns of this type

		# words ending in "is" become "es"

		elsif ($term_pattern =~ m!^(.*)(is)(\\) $!i) {
			$term_pattern = "$1(i|e)s$3 ";
			}

		# words ending in [consonant]y becomes "ies"

		elsif ($term_pattern =~ m!^(.*)([^a|e|i|o|u])(y)(\\) $!i) {
			$term_pattern = "$1$2(ies|y)$4 ";
			}

# plural -> sing

		# words ending in "ies" => "y|ie"
		elsif ($term_pattern =~ m!^(.*)(ies)(\\) $!i) {
			$term_pattern = "$1(y|ie|ies)$3 ";
			}
		# words ending in "es" => "''|e|is|es"
		elsif ($term_pattern =~ m!^(.*)(es)(\\) $!i) {
			$term_pattern = "$1(|e|is|es)$3 ";
			}
		# words ending in "s"; trailing s optional; additional trailing "es" allowed
		elsif ($term_pattern =~ m!^(.*)(s)(\\) $!i) {
			$term_pattern = "$1$2?(es)?$3 ";
			}
#/




		# hissing sound addx "-es"

		elsif ($term_pattern =~ m!^(.*)(z|x|sh|ch)(\\) $!i) {
			$term_pattern = "$1$2(es)?$3 ";
			}

		# all others - addx optional "s"

		elsif ($term_pattern =~ m!^(.*)(\\) $!i) {
			$term_pattern = "$1s?$2 ";
			}
		}

	$term_pattern =~ s!$WildCard!$WildSearch!g;
	my $pattern = "$lead_pattern$term_pattern$trail_pattern";
	return ($type, $is_attrib_search, $pattern, $sql_clause);
	}





sub LockFile_new {
	$private{'global_lockfile_count'}++;
	my $name = $private{'global_lockfile_count'};

	my $self = {
		'timeout' => 30,
		'create_if_needed' => 0,
		'rname' => '',
		'wname' => '',
		'ename' => '',
		'filename' => '',
		'mode' => -1,
		};
	bless($self);

#changed 0044 - re comp.perl.misc discussion of problems with *GLOB references

my $code = <<"EOM";

	\$self->{'p_rhandle'} = \\*ReadHandle$name;
	\$self->{'p_whandle'} = \\*WriteHandle$name;
	\$self->{'p_ehandle'} = \\*ExclusiveLockHandle$name;

EOM




	eval($code);


	my %params = @_;
	foreach (keys %params) {
		$self->{$_} = $params{$_};
		}
	return $self;
	}





sub FlockEx {
	my ($filehandle_ref, $mode) = @_;
	my $rv = 1;
	if ($const{'bypass_file_locking'} == 1) {
		# ok
		}
	elsif ($const{'bypass_file_locking'} == 2) {
		$rv = flock($$filehandle_ref, $mode);
		}
	else {
		eval {
			$rv = flock($$filehandle_ref, $mode);
			};
		if ($@) {
			$rv = 1;
			}
		else {
			$const{'bypass_file_locking'} = 2;
			}
		}
	return $rv;
	}





sub Read {
	my ($self, $filename) = @_;

	$self->{'rname'} = $filename;
	$self->{'ename'} = "$filename.exclusive_lock_request";
	$self->{'wname'} = "$filename.working_copy";

	my ($p_rhandle, $rname, $p_whandle, $wname, $p_ehandle, $ename) = ($self->{'p_rhandle'}, $self->{'rname'}, $self->{'p_whandle'}, $self->{'wname'}, $self->{'p_ehandle'}, $self->{'ename'});

	my $err = '';
	Err: {
		my $success = 0;

		$err = $self->LockFile_get_read_access();
		next Err if ($err);

		#&Assert('GLOB' eq ref($p_rhandle));

		unless (open($$p_rhandle, "<$rname")) {
			$err = &pstr(44,$rname,$!);
			next Err;
			}
		unless (binmode($$p_rhandle)) {
			$err = &pstr(39,$rname,$!);
			next Err;
			}
		my $attempts = $self->{'timeout'};
		while ($attempts > 0) {
			my $rv = &FlockEx($p_rhandle, 5);
			if ($rv) {
				$success = 1;
				last;
				}
			$attempts--;
			sleep(1);
			}
		unless ($success) {
			$err =  &pstr(41, $rname, $! );
			next Err;
			}
		}
	return ($err,$p_rhandle);
	}





sub Close {
	my ($self) = @_;
	return &freeh($self->{'p_rhandle'},$self->{'rname'});
	}





sub LockFile_get_read_access {
	my ($self) = @_;
	my $err = '';
	Err: {
		my $attempts = $self->{'timeout'};
		while ((-e $self->{'ename'}) and ($attempts > 0)) {
			# If an "exlusive lock request" file exists, wait up to timeout seconds for it to disappear. If it doesn't, and if it's age is
			# also less than timeout seconds, return an error:
			# is she recent?
			my $lastmodt = (stat($self->{'ename'}))[9];
			my $age = time() - $lastmodt;
			last unless ($age < $self->{'timeout'});
			$attempts--;
			sleep(1);
			}
		unless ($attempts > 0) {
			$err = &pstr(44, $self->{'rname'}, &pstr(37, $self->{'timeout'} ) );
			next Err;
			}
		while (($attempts > 0) and (-e $self->{'wname'})) {
			# How old is the write file?
			my $lastmodt = (stat($self->{'wname'}))[9];
			my $age = time() - $lastmodt;
			if ($age > $self->{'timeout'}) {
				# claim it for ourselves - but if the core file doesn't exist, rename this one over to it's spot.
				unless (-e $self->{'rname'}) {
					unless (rename($self->{'wname'}, $self->{'rname'})) {
						$err = &pstr(38,$self->{'wname'},$self->{'rname'},$!);
						next Err;
						}
					}
				last;
				}
			sleep(1);
			$attempts--;
			}

		unless ($attempts > 0) {
			$err = &pstr(44, $self->{'rname'}, &pstr(37, $self->{'timeout'} ) );
			next Err;
			}


		# Okay, by now we've waited for the .exclusive_lock_request file and the .working_copy.  If the orginal target file still doesn't exist, and if we are so directed, create it:

		if (($self->{'create_if_needed'}) and (not (-e $self->{'rname'}))) {
			# okay, it don't exist and we're config'ed to created it...
			unless (open(TEMP, '>' . $self->{'rname'})) {
				$err = &pstr(43, $self->{'rname'}, $! );
				next Err;
				}
			binmode(TEMP);
			print TEMP '';
			close(TEMP);
			chmod($private{'file_mask'}, $self->{'rname'});
			}

		}
	return $err;
	}





sub FD_Rules_new {
	my $self = {};
	bless($self);

		# name => [default, data_type_number ]

	my %defaults = (
		'wildcard match' => ['\S{0,4}', 5],
		'parse fdse-index-as header' => [1,1],
		'time interval between restarts' => [15,3,10,60],


		'use standard io' => [1,1],
		'delete index file with realm' => [0,1],
		'allow filtered realms' => [0,1],


		'mode' => [1,3,0,3],
		'regkey' => ['',5],
		'no frames' => [0, 1],

		'redirector' => ['', 5],
		'default match' => [0, 3, 0, 2],
		'language' => ['english', 5],

		'admin notify: smtp server' => ['', 5],
		'admin notify: email address' => ['', 5],
		'admin notify: sendmail program' => ['',5],

		'ui: number format' => [0,2],
		'ui: date format' => [12,2],

		'ui: search form display' => [2, 3, 0, 3],

		'security: session timeout' => [60, 3, 10, 1000000],

		'allow index entire site' => [0, 1],

		'handling url search terms' => [2, 3, 1, 3],

		'sorting: randomize equally-relevant search results' => [0,1],
		'sorting: default sort method' => [1,3,1,6],
		'sorting: time sensitive' => [0,1],

		#changed 0054
		'logging: enable' => [1,1],

		'multi-line add-url form - admin' => [0,1],
		'multi-line add-url form - visitors' => [0,1],


		'network timeout' => [10,3,1,1000000],

		'allowanonadd: notify admin' => [0, 1],
		'allowanonadd: log' => [0,1],
		'allowanonadd: require user email' => [0, 1],
		'allowanonadd' => [0, 1],
		'require anon approval' => [1, 1],

		'allowbinaryfiles' => [1, 1],
		'allowsymboliclinks' => [1, 1],

		'character conversion: accent insensitive' => [1, 1],
		'character conversion: case insensitive' => [1, 1],

		'crawler: days til refresh' => [30, 3, 1, 100],
		'crawler: follow offsite links' => [1, 1],
		'crawler: follow query strings' => [1, 1],
		'crawler: ignore links to' => ['gif jpg jpe js css wav ram ra mpeg mpg avi zip exe doc xls pdf gz', 5],
		'crawler: max pages per batch' => [10, 2],
		'crawler: max redirects' => [6, 2],
		'crawler: minimum whitespace' => [0.01, 4],
		'crawler: rogue' => [0, 1],
		'crawler: user agent' => ['Mozilla/4.0 (compatible: FDSE robot)', 5],

		'ext' => ['html htm shtml shtm stm mp3', 5],
		'file' => ['', 5],
		'forbid all cap descriptions' => [1, 1],
		'forbid all cap titles' => [1, 1],
		'hits per page' => [10, 3, 1, 99999],

		'ignore words' => ['your you www with will why who which where when what web we was want w used use two to this they these there then then them their the that than t so site should see s re page our other org or only one on of now not no new net name n my ms mrs mr most more me may like just its it is in if i http how he have has get from for find ed do d com can by but been be b at as are any and an also all after about a ', 5],

		'index alt text' => [1, 1],
		'index links' => [0, 1],

		'max characters: auto description' => [150, 2],
		'max characters: description' => [384, 2],
		'max characters: file' => [64000, 2],
		'max characters: keywords' => [256, 2],
		'max characters: text' => [64000, 2],
		'max characters: title' => [96, 2],
		'max characters: url' => [128, 3, 16, 255],

		'max index file size' => [10000000, 2],
		'minimum page size' => [128, 2],

		'multiplier: description' => [0, 3, 0, 100],
		'multiplier: keyword' => [0, 3, 0, 100],
		'multiplier: title' => [0, 3, 0, 100],

		'password' => ['', 5],

		'show examples: enable' => [0, 1],
		'show examples: number to display' => [1, 2],

		'sql: logfile' => [0, 1],
		'sql: enable' => [0, 1],

		'sql: database' => ['', 5],
		'sql: hostname' => ['', 5],
		'sql: password' => ['', 5],
		'sql: username' => ['', 5],

		'sql: table name: addresses' => ['addresses', 5],
		'sql: table name: realms' => ['realms', 5],
		'sql: table name: logs' => ['logs', 5],

		'timeout' => [30, 2],
		'trustsymboliclinks' => [0, 1],

		'pics_rasci_enable' => [0, 1],
		'pics_rasci_handle' => [1, 1],

		'pics_ss_enable' => [0, 1],
		'pics_ss_handle' => [1, 1],

		'pics_rasci_n' => [2, 2],
		'pics_rasci_s' => [2, 2],
		'pics_rasci_l' => [2, 2],
		'pics_rasci_v' => [2, 2],

		'pics_ss_000' => [3, 2],
		'pics_ss_001' => [3, 2],
		'pics_ss_002' => [3, 2],
		'pics_ss_003' => [3, 2],
		'pics_ss_004' => [3, 2],
		'pics_ss_005' => [3, 2],
		'pics_ss_006' => [3, 2],
		'pics_ss_007' => [3, 2],
		'pics_ss_008' => [3, 2],
		'pics_ss_009' => [3, 2],
		'pics_ss_00A' => [3, 2],

		);
	$self->{'r_defaults'} = \%defaults;
	$self->{'delim'} = '=';
	$self->{'separ'} = "\015\012";
	$self->{'file'} = 'settings.pl';
	if (-e 'settings.cgi') {
		$self->{'file'} = 'settings.cgi';
		}
	return $self;
	}





sub _fdr_validate {
	my ($self, $name, $value) = @_;
	my ($is_valid, $valid_value) = (1, $value);
	my $r_defaults = $self->{'r_defaults'};
	if (defined($$r_defaults{$name})) {
		my $type = $$r_defaults{$name}[1];
		if ($type == 1) {
			if ((not defined($value)) or ($value eq '')) {
				$valid_value = $value = 0;
				}
			$is_valid = (($value eq '0') or ($value eq '1'));
			}
		elsif ($type == 2) {
			$is_valid = ($value =~ m!^\d+$!);
			}
		elsif ($type == 3) {
			$is_valid = (($value =~ m!^\d+$!) and ($value >= $$r_defaults{$name}[2]) and ($value <= $$r_defaults{$name}[3]));
			}
		elsif ($type == 4) {
			$is_valid = ($value =~ m!^\d+\.?\d*$!); #changed 0053
			}
		unless ($is_valid) {
			$valid_value = $$r_defaults{$name}[0];
			}
		}
	return ($is_valid, $valid_value);
	}





sub fdse_realms_new {
	my $self = {};
	bless($self);

	my (@realms, %realms_by_name, @delete_realm_ids) = ();

	$self->{'realms'} = \@realms;
	$self->{'p_realms_by_name'} = \%realms_by_name;
	$self->{'p_delete_realm_ids'} = \@delete_realm_ids;

	$self->{'need_approval'} = 0;
	$self->{'use_db'} = 0;
	$self->{'file'} = 'search.realms.txt';
	$self->{'last_realm_err'} = '';
	return $self;
	}





sub load {
	my ($self) = @_;

	my $dbh = undef();
	my $sth = undef();

	# clear original list:
	my $ref_realms = $self->{'realms'};
	@$ref_realms = ();

	my $err = '';
	Err: {
		if ($self->{'use_db'}) {

			$err = &get_dbh(\$dbh);
			next Err if ($err);

			unless ($sth = $dbh->prepare("SELECT realm_id, name, file, is_runtime, basedir, baseurl, pagecount FROM $Rules{'sql: table name: realms'}")) {
				$err = "unable to prepare statement - " . $dbh->errstr();
				next Err;
				}

			unless ($sth->execute()) {
				$err = "unable to execute statement - " . $sth->errstr();
				next Err;
				}

			my @data = ();
			while (@data = $sth->fetchrow_array()) {

				my $type = $data[3];

				my $is_runtime = ($type == 5) ? 1 : 0;
				my $is_filefed = ($type == 2) ? 1 : 0;

				$self->add($data[0], $data[1], 1, $data[2], $is_runtime, $data[4], $data[5], '', $data[6], $is_filefed);
				}

			$sth->finish();
			$sth = undef();
			$dbh->disconnect();
			$dbh = undef();
			}
		else {

			my ($obj, $p_rhandle) = ();

			$obj = &LockFile_new(
				'create_if_needed' => 1,
				);
			($err, $p_rhandle) = $obj->Read( $self->{'file'} );
			next Err if ($err);

			while (defined($_ = readline($$p_rhandle))) {
				my @Fields = split(m!\|!, &Trim($_));
				next unless ($Fields[0] and $Fields[1]);
				my ($name, $file, $base_dir, $base_url, $exclude, $pagecount, $is_filefed) = @Fields;
				my $is_runtime = ($file eq 'RUNTIME') ? 1 : 0;

				#0054 untaint fields:
				if ($file =~ m!\.\.!) {
					$err = "realm file name cannot contain '..' substring";
					next Err;
					}
				&untaintme( \$file );
				&untaintme( \$base_dir );

				$self->add(0, $name, 0, $file, $is_runtime, $base_dir, $base_url, $exclude, $pagecount, $is_filefed);
				}

			$err = $obj->Close();
			next Err if ($err);

			}
		last Err;
		}
	continue {
		$self->{'last_realm_err'} = $err;
		}
	$sth->finish() if ($sth);
	$dbh->disconnect() if ($dbh);
	return $err;
	}





sub use_database {
	my ($self, $value) = @_;
	$self->{'use_db'} = $value;
	}





sub hashref {
	my ($self, $name) = @_;
	my ($err, $ref) = ('');
	my $p_realms_by_name = $self->{'p_realms_by_name'};
	unless ($ref = $$p_realms_by_name{$name}) {
		$err = &pstr(55,&html_encode($name));
		}
	return ($err, $ref);
	}





sub listrealms {
	my ($self, $attrib) = @_;
	my @realms = ();
	my %names = ();
	my $ref_realms = $self->{'realms'};
	my $RH;
	foreach $RH (@$ref_realms) {
		next unless ($$RH{$attrib});
		$names{$$RH{'name'}} = $RH;
		}
	foreach (sort keys %names) {
		push(@realms, $names{$_});
		}
	return @realms;
	}





sub realm_count {
	my ($self, $attrib) = @_;
	my $count = 0;
	my $ref_realms = $self->{'realms'};
	my $ref_hash;
	foreach $ref_hash (@$ref_realms) {
		$count++ if $$ref_hash{$attrib};
		}
	return $count;
	}





sub html_select_ex {
	my ($self, $attrib, $default, $class, $width1) = @_;
	my ($count, $html) = (0, '');
	$default = '' unless (defined($default));
	$count = $self->realm_count($attrib);
	my $ref_realms = $self->{'realms'};
	my $ref_hash;
	if ($count == 1) {
		foreach $ref_hash (@$ref_realms) {
			if ($$ref_hash{$attrib}) {
				$html = '<INPUT TYPE=hidden NAME=Realm VALUE="' . $$ref_hash{'html_name'} . '">';
				last;
				}
			}
		}
	elsif ($count > 1) {
		my $options = '';
		foreach $ref_hash ($self->listrealms($attrib)) {
			if ($default eq $$ref_hash{'name'}) {
				$options .= '<OPTION SELECTED>' . $$ref_hash{'name'};
				}
			else {
				$options .= '<OPTION>' . $$ref_hash{'name'};
				}
			}
		if ($class) {
			$html = "<TR CLASS=$class>";
			}
		else {
			$html = "<TR>";
			}
		if ($width1) {
			$html .= "<TD ALIGN=right WIDTH=$width1>";
			}
		else {
			$html .= "<TD ALIGN=right>";
			}
		$html .= "<B>$str[46]:</B></TD>\n\t<TD><SELECT NAME=Realm>$options</SELECT></TD>\n</TR>\n";
		}
	else {
		$html = '';
		}
	return ($count, $html);
	}





sub add {
	my ($self, $realm_id, $name, $is_database, $file, $is_runtime, $base_dir, $base_url, $exclude, $pagecount, $is_filefed) = @_;
	if (($file) and (open(FILE, "<$file.pagecount"))) {
		binmode(FILE);
		$pagecount = <FILE>;
		$pagecount =~ s!\,|\015|\012!!g;
		close(FILE);
		}
	$pagecount = 0 unless ($pagecount);

	my $need_approval = ((-e "$file.need_approval") and ((-s "$file.need_approval") > 10)) ? 1: 0;

	# assign file-fed attributes:
	my %RH = (
		'is_runtime'  => $is_runtime,
		'is_database' => $is_database,
		'is_filefed'  => $is_filefed,

		'pagecount' => $pagecount,

		'name' => $name,
		'html_name' => &html_encode($name),
		'url_name' => &url_encode($name),
		'file' => $file,
		'base_dir' => $base_dir,
		'base_url' => $base_url,
		'exclude' => $exclude,
		'need_approval' => $need_approval,
		);


	my $type = 1;

	if ($base_url eq 'http://filtered:1/') {
		$type = 6;
		}
	elsif ($is_runtime) {
		$type = 5;
		}
	elsif ($is_filefed) {
		$type = 2;
		}
	elsif (($base_dir) and ($base_url)) {
		$type = 4;
		}
	elsif ($base_url) {
		$type = 3;
		}
	else {
		$type = 1;
		}

	$RH{'type'} = $type;

	if ($RH{'need_approval'}) {
		$self->{'need_approval'} = 1;
		}

	# cleanse data:
	while (defined($_ = each %RH)) {
		$RH{$_} = &Trim($RH{$_});
		$RH{$_} = '' unless defined($RH{$_});
		$RH{$_} =~ s!\015|\012|\|!!sg;
		}
	$RH{'base_dir'} =~ s!/$!!;

	my ($err,$clean_url) = (&parse_url_ex($RH{'base_url'}))[0,1];
	unless ($err) {
		$RH{'base_url'} = $clean_url;
		}

	$RH{'is_error'} = 0;
	$RH{'no_error'} = 1;
	$RH{'err'} = '';

	# Try to create a file if we're going to be needing it:
	if (($RH{'file'}) and (not ($RH{'is_runtime'})) and (not (-e $RH{'file'})) and (not (-e "$RH{'file'}.exclusive_lock_request")) and (not (-e "$RH{'file'}.working_copy"))) {
		if (open(FILE, '>>' . $RH{'file'})) {
			binmode(FILE);
			close(FILE);
			}
		else {
			$RH{'is_error'} = 1;
			$RH{'no_error'} = 0;
			$RH{'err'} = &pstr(42, $RH{'file'}, $! );
			}
		}

	# set derived attributes:

	$RH{'all'} = 1;

	$RH{'has_index_data'} = not $RH{'is_runtime'};

	if ($self->{'use_db'}) {
		$RH{'is_database'} = 1;
		$RH{'has_file'} = 0;
		}
	else {
		$RH{'is_database'} = 0;
		$RH{'has_file'} = not $RH{'is_runtime'};
		}

	if ($RH{'base_url'} eq 'http://filtered:1/') {
		$RH{'has_base_url'} = 0;
		$RH{'has_no_base_url'} = 1;
		}
	elsif ($RH{'base_url'}) {
		$RH{'has_base_url'} = 1;
		$RH{'has_no_base_url'} = 0;
		}
	else {
		$RH{'has_base_url'} = 0;
		$RH{'has_no_base_url'} = 1;
		}

	$RH{'is_open_realm'} = ($RH{'type'} == 1) ? 1 : 0;
	$RH{'realm_id'} = $realm_id;

	my $p_realms_by_name = $self->{'p_realms_by_name'};
	$$p_realms_by_name{$name} = \%RH;

	my $p_realms = $self->{'realms'};
	push(@$p_realms, \%RH);
	}





sub freeh {
	my ($p_handle,$name,$b_delete) = @_;
	my $err = '';
	unless (&FlockEx($p_handle,8)) {
		$err = &pstr(49,&html_encode($name),$!);
		}
	unless (close($$p_handle)) {
		$err = &pstr(52,&html_encode($name),$!);
		}
	if ($b_delete) {
		unless (unlink($name)) {
			$err = &pstr(54,&html_encode($name),$!);
			}
		}
	return $err;
	}



1;
