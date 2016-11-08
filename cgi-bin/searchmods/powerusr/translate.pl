#!/usr/bin/perl -w
use strict;
require 'lib.pl';
no locale;

my $usage = <<"EOM";

Usage:
	translate.pl
	translate.pl "language-name"

Assumes that this script is in "search/searchmods/powerusr", along with all
the language.txt files, and that the files to be translated are in
"search/searchdata/templates".

The script opens all the English template files.  Then, for each language,
it opens the appropriate "language.txt" file in the translator folder.
That file contains English::other string mappings.  Then it rebuilds all
of the template files for the non-English language, mapping them into the
appropriate language.

Copyright 2002 Zoltan Milosevic

See http://www.xav.com/scripts/search/help/1000.html for more information.

EOM

use vars qw! %global_no_translate $transdir !;

my $VERSION = '2.0.0.0055';
my $PRODUCT = 'Fluid Dynamics Search Engine';

#http://www.historyguide.de/info/tools/languagecode.html
my @languages = (
	'dk',
	'dutch',
	'fi',
	'french',
	'german',
	'italian',
	'lv',
	'nb',
	'pl',
	'portuguese',
	'ro',
	'sl',
	'spanish',
	'sr',
	'sv',
	'tl',
	'tr',
	);

if ($ARGV[0]) {
	@languages = @ARGV;
	}

my $err = '';
Err: {

	my $dir = '../../searchdata/templates';
	$transdir = '../../searchmods/powerusr';

	print $usage;

	unless (chdir($dir)) {
		$err = "unable to chdir to folder '$dir' - $!";
		next Err;
		}

	unless (-d $transdir) {
		$err = "transdir '$transdir' does not exist";
		next Err;
		}


	my %context = ();
	($err, %context) = &load_context();
	next Err if ($err);

	my $llang = ();
	foreach $llang (@languages) {
		next if ($llang eq '-strip');

		my $text = '';
		my $trans = '';
		my $new = ();

		print "Initiating language '$llang'\n";

		my $cfile = "$transdir/$llang.txt";

		my %str_used_phrases = ();
		my %lcache = ();
		my %authority = ();
		my %time = ();

		$err = &load_cache( $cfile, \%lcache, \%time, \%authority, \%str_used_phrases);
		next Err if ($err);

		print "Loaded cache (" . scalar (keys %lcache) . ")\n";
		my $filetext = ();

		($err, $filetext) = &ReadFile("english/strings.txt");
		next Err if ($err);

		my $count = 0;
		foreach $text (split(m!\n!, $filetext)) {
			$count++;
			$trans = &Trim($text);
			if ($count > 1) {
				($err, $trans) = &Translate( $text, $llang, \%lcache, \%str_used_phrases, \%authority );
				if ($err) {
					print "<P><B>Error:</B> $err.</P>\n";
					}
				#print "Trans [$llang:$count]: $trans\n";
				}
			$new .= "$trans\n";
			}

		$err = &WriteFile("$llang/strings.txt", $new);
		next Err if ($err);


		$err = &save_cache( $cfile, \%lcache, \%time, \%authority, \%str_used_phrases, \%context, 0 );
		next Err if ($err);


		my $tmp_err_msg = '';

		($err, $filetext) = &ReadFile("english/settings_desc.txt");
		next Err if ($err);

		$new = '';
		$text = '';
		$count = 0;
		foreach $text (split(m!\n!, $filetext)) {
			$count++;
			next unless ($text =~ m!^(.*?)=(.*)$!);
			my ($name, $value) = (&Trim($1), &Trim($2));
			$trans = $value;

			($tmp_err_msg, $trans) = &Translate( $value, $llang, \%lcache, \%str_used_phrases, \%authority );
			if ($tmp_err_msg) {
				print "<P><B>Error:</B> $err.</P>\n";
				}
			#print "Trans [$llang:$count]: $trans\n";

			$new .= "$name = $trans\n";
			}

		$err = &WriteFile("$llang/settings_desc.txt", $new);
		next Err if ($err);


		$err = &save_cache( $cfile, \%lcache, \%time, \%authority, \%str_used_phrases, \%context, 0 );
		next Err if ($err);



		my @files = ();
		opendir(DIR, "english") || die $!;
		foreach (readdir(DIR)) {
			next if (-d "english/$_");
			next if ($_ eq 'settings_desc.txt');
			next if ($_ eq 'strings.txt');
			push(@files, $_);
			}
		closedir(DIR);
		my $file = ();
		foreach $file (@files) {

			my $text = '';

			#changed 0043 -- added support for override files

			if (-e "$transdir/override/$llang/$file") {
				print "Using override file - $llang/$file\n";
				($err,$text) = &ReadFile("$transdir/override/$llang/$file");
				next Err if ($err);
				$err = &WriteFile("$llang/$file",$text);
				next Err if ($err);
				next;
				}


			($err, $filetext) = &ReadFile("english/$file");
			next Err if ($err);

			my $new = '';
			my $count = 0;

			while ($filetext =~ m!^(.*?)>([^\w\<\>]*)([^\<\>]+?)([^\w\<\>]*)<(.*)$!s) {
				my ($start, $mid, $end1, $end2) = ("$1>$2", $3, "$4<", $5);
				$filetext = $end2;
				$new .= $start;

				$trans = $mid;

				($tmp_err_msg, $trans) = &Translate( $trans, $llang, \%lcache, \%str_used_phrases, \%authority );
				if ($tmp_err_msg) {
					print "<P><B>Error:</B> $err.</P>\n";
					}
				$new .= $trans;
				$new .= $end1;
				}
			$new .= $filetext;

			# Now fix for those damn submit buttons

			$filetext = $new;
			$new = '';

			while ($filetext =~ m!^(.*?)<INPUT TYPE="?submit"? CLASS="?submit"? VALUE="(.*?)"\s*/?>(.*)$!is) {
				my ($start, $value, $end) = ($1, $2, $3);

				#print "Hello... translating '$value' to $llang\n";

				$new .= $start;
				$new .= qq!<input type="submit" class="submit" value="!;

				$trans = $value;
				($tmp_err_msg, $trans) = &Translate( $trans, $llang, \%lcache, \%str_used_phrases, \%authority );
				if ($tmp_err_msg) {
					print "<P><B>Error:</B> $err.</P>\n";
					}
				$new .= $trans;

				$new .= qq!" />!;

				$filetext = $end;
				}
			$new .= $filetext;

			$err = &WriteFile("$llang/$file", $new);
			next Err if ($err);

			}

		$err = &save_cache( $cfile, \%lcache, \%time, \%authority, \%str_used_phrases, \%context, 1 );
		next Err if ($err);
		}

	last Err;
	}
continue {
	print "Error: $err.\n";
	}




=item load_context()

Usage:
	my ($err, %context) = &load_context();

Loads the "Context:" header for each string, pulling it from the english.txt file in the translator directory.  These context strings will be written into the other translator files as hints to help human translators.

=cut

sub load_context {
	my %context = ();
	my $err = '';
	Err: {
		local $_;

		my $text = '';
		($err, $text) = &ReadFile("$transdir/english.txt");
		next Err if ($err);

		&force_CRLF( \$text );

		$text =~ s!\015\012!\012!sg;
		$text =~ s!\$\$s!\$s!sg;


		foreach (split(m!\012\012+!s, $text)) {
			next if (m!^##!);

			if (m!^\s*Authority:.*?Time:.*?Instances:\s*\d+\n\s*Context:(.*?)\n--\n(.+?)\n!s) {
				my ($name, $context) = (&Trim($2), &Trim($1));
				$context{$name} = $context;
				}
			}
		}
	return ($err, %context);
	}



=item load_cache($$$$$$)

Usage:
	$err = &load_cache( $cfile, \%lcache, \%time, \%authority, \%str_used );
	next Err if ($err);

=cut


sub load_cache {
	my ($cfile, $p_lcache, $p_time, $p_authority, $p_str_used) = @_;
	my $err = '';
	Err: {
		unless (-e $cfile) {
			$err = &WriteFile($cfile, '');
			next Err if ($err);
			}
		my $text = '';

		%global_no_translate = ();
		($err, $text) = &ReadFile("$transdir/global_no_translate.txt");
		next Err if ($err);
		foreach (split(m!\n+!s, $text)) {
			my $name = &Trim($_);
			#print "no translate: $name\n";
			$global_no_translate{$name} = 1;
			$$p_lcache{$name} = $name;
			$$p_str_used{$name} = 0;
			$$p_time{$name} = 0;
			$$p_authority{$name} = 'Global NoTranslate List';
			}

		($err, $text) = &ReadFile($cfile);
		next Err if ($err);
		&force_CRLF( \$text );
		$text =~ s!\015\012!\012!sg;
		$text =~ s!\$\$s!\$s!sg;

		my %tempa = ();


		foreach (split(m!\012\012+!s, $text)) {
			next if (m!^##!);
			my ($name, $value, $authority, $time) = ('', '', '', '');
			if (m!^\s*Authority:(.*?)Time:(.*?)Instances:\s*\d+\n--\n(.+)\n(.+)$!s) {
				($authority, $time, $name, $value) = ( &Trim($1), &Trim($2), &Trim($3), &Trim($4) );
				}
			elsif (m!^\s*Authority:(.*?)Time:(.*?)Instances:\s*\d+\n\s*Context:.*?\n--\n(.+)\n(.+)$!s) {
				($authority, $time, $name, $value) = ( &Trim($1), &Trim($2), &Trim($3), &Trim($4) );
				}
			elsif (m!^(.+)\n(.+)$!s) {
				($name, $value) = (&Trim($1), &Trim($2) );
				$authority = "Altavista/Babelfish";
				$time = scalar localtime(time);
				$time .= " - " . time();
				}
			else {
				next;
				}
			next if ($global_no_translate{$name});

			if ($$p_lcache{$name}) {
				if ($$p_lcache{$name} eq $value) {
					#print "Same value...\n";
					}
				elsif (1) {
					print "Warning: overwriting value: " . substr($name, 0, 20) . "\n";

print "Prior Authority: $$p_authority{$name}\n";

					#next if ($$p_authority{$name} =~ m!(Michele Zangrossi)!);

						print "\n\n";
						print "English: $name\n";
						print "\n\n";
						print "NEW: $value\n";
						print "\n\n";
						print "$$p_authority{$name}: $$p_lcache{$name}\n";
						print "\n\n";



					$time = 0;
					#print "Updated value\n";
					}

				}

			$$p_lcache{$name} = $value;
			$$p_str_used{$name} = 0;
			$$p_time{$name} = $time;
			$$p_authority{$name} = $authority;


			$tempa{$authority}++;
			}
		foreach (sort keys %tempa) {
			print "Authority: $_ - $tempa{$_}\n";
			}

		}
#exit;
	return $err;
	}


=item save_cache($$$$$$)

Usage:
	$err = &save_cache( $cfile, \%lcache, \%time, \%authority, \%str_used, \%context, $b_warn );
	next Err if ($err);

=cut

sub save_cache {
	my ($cfile, $p_lcache, $p_time, $p_authority, $p_str_used, $p_context, $b_warn) = @_;
	my $err = '';
	Err: {

		my ($total, $human, $av, $nt, $gnt) = (0, 0, 0, 0, 0);

		# Save cache:
		my $timestr = scalar localtime(time);
		my $new = <<"EOM";
###
### Product: $PRODUCT
### Version: $VERSION
### Last updated: $timestr
###
### File format:
###
###		Authority: Person or software responsible for translated fragment
###		Time: time translation was performed (read-only)
###		Instances: number of times this fragment is used in the files (read-only)
###		Context: optional help text about how the string is used (read-only)
###		--
###		english string
###		translated string
###
### To contribute, simply edit the "translated string" and enter your name
### in the Authority field, and email to zoltanm\@xav.com.  The Time field
### will be automatically updated.  If you are correcting another person's
### translation, please note this.
###
### Those who update at least 40 strings from "NEED TRANSLATION" status
### (or "worthlessly translated" status) will receive a free registered
### version of the product and will have their names included in the
### "translated by" credits that are included in the product (if they want).
### If they've already paid the shareware fee, they will be sent \$35 which
### is what the fee becomes after transaction costs.* They will also win
### the love of many who speak their native language and you never know
### where that could lead.
###
### To be useful, translations must be distributable by Fluid Dynamics and
### end users must be free to modify them. Thus, translations cannot be
### accepted unless you transfer copyright to Fluid Dynamics. Your free
### registered version and mention in the product is a thank-you gesture for
### that transfer.
###
### * limit one refunded license per person !  :)
###

EOM

		my $english = $new;

		foreach (sort (keys %$p_lcache)) {
			unless ($$p_str_used{$_}) {
				print "Warning: haven't used string '$_' yet\n" if ($b_warn);
				$$p_str_used{$_} = 0;

				next if (($ARGV[1]) and ($ARGV[1] eq '-strip'));
				}

			my $time = $$p_time{$_};
			unless ($time) {
				$time = scalar localtime(time) . ' - ' . time();
				}

			next if ($global_no_translate{$_});
			$total++;

			if ($$p_authority{$_} eq 'NEED TRANSLATION') {
				$nt++;
				}
			elsif ($$p_authority{$_} =~ m!Altavista!i) {
				$av++;
				}
			else {
				$human++;
				}





			$$p_context{$_} = $$p_context{$_} || '';


			$english .= "\n\n";
			$english .= " Authority: NEEDS TRANSLATION\n";
			$english .= "      Time: $time\n";
			$english .= " Instances: $$p_str_used{$_}\n";
			$english .= "   Context: $$p_context{$_}\n";
			$english .= "--\n";
			$english .= "$_\n";
			$english .= "$_";
			$english .= "\n\n";

			$new .= "\n\n";
			$new .= " Authority: $$p_authority{$_}\n";
			$new .= "      Time: $time\n";
			$new .= " Instances: $$p_str_used{$_}\n";
			$new .= "   Context: $$p_context{$_}\n";
			$new .= "--\n";
			$new .= "$_\n";
			$new .= "$$p_lcache{$_}";
			$new .= "\n\n";
			}
		$err = &WriteFile("$transdir/english.txt", $english);
		next Err if ($err);

		$err = &WriteFile($cfile, $new);
		next Err if ($err);

		if ($b_warn) {
			print "[$cfile] Total: $total; $human human, $av AV, $gnt GNT, $nt Need Translation\n\n";
			}

		}
	return $err;
	}


sub Translate {
	my ($text, $la, $p_lcache, $p_str_used, $p_authority) = @_;

	my $trans = $text;

	my $err = '';
	Err: {

		unless ('HASH' eq ref($p_lcache)) {
			$err = "invalid arg - lcache not supplied";
			next Err;
			}

		my %bblfish = (
			'french' => 'fr',
			'german' => 'de',
			'italian' => 'it',
			'portuguese' => 'pt',
			'spanish' => 'es',
			'japanese' => 'ja',
			'korean' => 'ko',
			'chinese' => 'zh',
			);

		# don't sweat the whitespace
		last Err if ($trans =~ m!^\s+$!);

		last Err unless (length($trans)); # or nulls!

		last Err if ($trans eq '.');

		$$p_str_used{$text}++;

		if (defined($$p_lcache{$text})) {
			$trans = $$p_lcache{$text};
			last Err;
			}

		# uh-oh
		$$p_authority{$text} = "NEED TRANSLATION";
		$$p_lcache{$text} = $text;
		last Err;
		}
	return ($err, $trans);
	}
