#!/usr/bin/perl
#
# File:     RandomPageFromList.pl
# Author:   Angus McIntyre
# Date:     04.12.95
# Updated:  22.11.96 7:54 pm
#
# Choose a page at random, and jump to it. This variant of the
# random-page script requires a text file that contains a list of
# URLs, one of which will be picked and returned. You can specify
# the file to use by passing a 'file' argument, e.g.
#
#   http://foo.com/cgi-bin/RandomPageFromList.pl?file=MyURLs.txt
#
# If no file is specified, the script defaults to using a file
# called 'RandomURLs.txt' in the same directory as the script (or
# in a directory specified by a constant defined in the script).
#
# ---------------------------------------------------------------------------
#   REVISION HISTORY
#
#   04.12.95    SLAM    First implementation.
#
#   22.11.96    SLAM    Fixed for UNIX, extended, cleaned up, and commented.
#
# ---------------------------------------------------------------------------
#   LEGAL NOTICE
# 
# This script may be freely copied, distributed and modified. Use  of the 
# script is at the risk of the user. The script is presented "as-is" without 
# any warranty, and the author is not liable for any loss or damages arising 
# out of the use of or failure to use this script. This notice must appear  
# in any modified copy of the script in which the name of the original  
# author also appears.
# ---------------------------------------------------------------------------

# ---------------------------------------------------------------------------
#                               CONSTANTS
# ---------------------------------------------------------------------------

# Set to flush output

$|=1;

# $DEFAULT_URL_FILE: path to default file containing a list of URLs

$DEFAULT_URL_FILE = "RandomURLs.txt";

# $PATH_SEPARATOR: path separator for your operating system, e.g. '/' 
# for UNIX, '\' for DOS/Windows, ':' for MacOS etc.

$PATH_SEPARATOR = "/";

# $FULL_PATH_PATTERN: a pattern that recognises whether a pathname passed
# represents a complete pathname. For UNIX (as shown below) this is fairly
# simple. For MacOS and DOS/Windows you'll need to be more ingenious.

$FULL_PATH_PATTERN = "^/";

# $ALLOWED_URL_PATTERN: a pattern designed to check that the line returned 
# as a URL *is* in fact a plausible URL. If it isn't, the script will die 
# with an error message. This is partly a security feature, and partly a
# convenience feature. It's a security feature because it should help to
# stop malicious people from getting anything of interest from system
# files such as the password file; it's a convenience feature because we
# don't want to serve the user anything that isn't a valid URL anyway.

$ALLOWED_URL_PATTERN = "^http://|^ftp://|^gopher://";

# $ALLOWED_PATH_PATTERN: a pattern designed to check that the final
# pathname to the file isn't something it shouldn't be. I set the pattern
# to match 'URLs.txt' and then make all my URL lists end with that string.
# An alternative approach would be to specify the path to your HTML
# directory so that, whatever happens, the script won't be allowed to get
# any file that isn't in your area.

$ALLOWED_PATH_PATTERN = ".*URLs.txt\$";

# $LISTFILE_BASE: if you want to specify the directory containing the
# list files, you can use this variable. Otherwise the files will be
# assumed to be relative to the directory containing the script to be
# executed. The string should end with a path separator.

$LISTFILE_BASE = "";

# ---------------------------------------------------------------------------
#                               MAIN ROUTINE
# ---------------------------------------------------------------------------

# Call the main routine. If an error occurs, report it gracefully.

eval("&main");
if ($@) { &report_error($@); }

# main
#
# Main routine. Read the input from CGI, set up the random number
# generator, read the list of permitted URLs from the appropriate
# file, and return one chosen at random.
#
# Note that the 'srand()' option is set up for the Macintosh, where
# the usual form - 'srand($$|time)' does odd things (because the
# Macintosh doesn't have pids?). If you're always getting the same
# URL, try changing 'srand()' to 'srand($$|time)' or 'srand(time)'.

sub main {
    local(%input);
    &read_cgi_input(*input);
    srand();
    local(@urls) = &read_url_file($input{"file"} || $DEFAULT_URL_FILE);
    &perform_redirect(&random_pick_from_list(@urls));
}

# ---------------------------------------------------------------------------
#                           ERROR REPORTING
# ---------------------------------------------------------------------------

# report_error
#
# Report the error

sub report_error {
    local($error) = @_;
    print <<"EndOfHTML";
Content-type: text/html

<HTML><HEAD><TITLE>Random URL Error</TITLE></HEAD>
<BODY><H1>Random URL Error</H1>
<P>No random URL could be returned because the error:
<BLOCKQUOTE><TT>$error</TT></BLOCKQUOTE>
occurred. Sorry.</P></BODY></HTML>
EndOfHTML
}


# ---------------------------------------------------------------------------
#                                SUBROUTINES
# ---------------------------------------------------------------------------

# read_url_file
#
# Read a file containing URLs, one per line. This is mostly obvious, the only 
# tricky line is the 'push' ... 'if', which adds a line to the list of URLs 
# only if the line isn't blank and doesn't begin with a comment '#' marker.
#
# The function starts with a little bit of messing about intended to get a
# usable path to a file containing a 

sub read_url_file {
    local($filename) = @_;
    local($pathname,@path,@urls);
    
    # Try various strategies to get a path to the URL file.
    
    SWITCH: {
        
        # First possibility; we have a full path to the file required.
        # This is simple to handle (but for security reasons, you may
        # prefer not to set up your pages to use full path references).
        
        $pathname = $filename, last SWITCH
            if (m|$FULL_PATH_PATTERN|);
            
        # Second possibility; the constant $LISTFILE_BASE has been 
        # defined, and the URL is taken as being relative to that.
        
        $pathname = $LISTFILE_BASE . $filename, last SWITCH
            if ($LISTFILE_BASE);
            
        # Third and last possibility; we assume that the list file is
        # relative to the script, and use the script's full path (as
        # found in '$0') to get the path to the file.
        
        @path = split($PATH_SEPARATOR,$0);
        pop(@path);
        push(@path,$filename);
        $pathname = join($PATH_SEPARATOR,@path);
    }
    
    # Make sure that the user isn't trying to pull a fast one on us
    # by having a go for the password file or something similar. Test
    # the path against a pre-defined pattern.
    
    die "this script isn't allowed to read '$pathname'" 
        unless ($pathname =~ $ALLOWED_PATH_PATTERN);
    
    # Read the file containing the URLs
    
    open(FILE,$pathname) || die "can't open list file '$pathname': $!";
    while(<FILE>) {
        chop;
        push(@urls,$_) if !/(^\s*$|^\s*#)/;
    }
    close(FILE);
    return @urls;
}

# random_pick_from_list
#
# Pick an item at random from a list. This is actually a general-purpose
# function for performing a selection from a list.

sub random_pick_from_list {
    local(@list) = @_;
    $list[int(rand(scalar(@list)))];
}

# perform_redirect
#
# Print out a redirection. The redirection is done by the 'Location:' line,
# and normally this would be all we need. However, some browsers can't
# handle redirections so, out of courtesy, we wrap the whole thing up in a
# piece of HTML that explains what went wrong and what to do about it.

sub perform_redirect {
    local($url) = @_;
    
    # Check to see that the item returned was actually a valid URL. In the
    # case that it's not valid, it would be nice to print the item out, but
    # that's precisely what we don't want to do, for security reasons - if
    # someone's trying to sneak information from our secret files, we don't
    # want to hand them confidential strings as part of the error message.
    # Hence this rather unhelpful little message.
    
    die "the line read from the file was not a valid URL"
        unless ($url =~ $ALLOWED_URL_PATTERN);
    
    # Now do the redirection.
    
    print <<"EndOfHTML";
Content-type: text/html
Location: $url

<HTML>
<HEAD>
<TITLE>Redirection</TITLE>
</HEAD>
<BODY>
<H1>Redirection</H1>
<P>It appears that your browser cannot handle redirections
automatically. You can proceed to the randomly-selected page 
by clicking <A HREF="$url">here</A>.</P>
</BODY>
EndOfHTML
}

# ---------------------------------------------------------------------------
#                           CGI INPUT LIBRARY
# ---------------------------------------------------------------------------

# read_cgi_input
# Arguments:    1. *data = name of associative array to write to
# Returns:      Undefined
#
# Get the data provided by either a GET or POST and write the data to the 
# associative array indicated. This is provided for the sake of completeness.
# The correct thing to do is, of course, to use the standard 'CGI-lib.pl'
# library or a variant, and 'require' it at the beginning of the script,
# rather than laboriously 'rolling your own' each time.

sub read_cgi_input {
    local(*data) = @_;
    local($buffer);
    
    # Get the form variable information as required according to the
    # method in use. This allows the function to handle both GET
    # and POST data.
    
    if ($ENV{"REQUEST_METHOD"} eq "POST") {
        read(STDIN, $buffer, $ENV{'CONTENT_LENGTH'});
    }
    elsif ($ENV{"REQUEST_METHOD"} eq "GET") {
        $buffer = $ENV{'QUERY_STRING'};
    }
    
    # Split the name-value pairs, decode the Web-encoded values and
    # assign them to entries in the input associative array.
    
    @pairs = split(/&/, $buffer);
    foreach $pair (@pairs) {
        ($name, $value) = split(/=/, $pair);
        $value =~ tr/+/ /;
        $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
        $data{$name} = $value;
    }
}


